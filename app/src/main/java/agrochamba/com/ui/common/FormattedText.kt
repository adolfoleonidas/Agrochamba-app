package agrochamba.com.ui.common

import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.text.Html
import android.text.Spanned
import android.text.style.StyleSpan
import android.text.style.UnderlineSpan
import android.graphics.Typeface
import androidx.compose.foundation.gestures.detectTapGestures
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.*
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

/**
 * FormattedText - Editor profesional de texto
 *
 * FORMATO INLINE:
 * - *negrita* (WhatsApp) o **negrita** (Markdown)
 * - _cursiva_ (WhatsApp)
 * - ~tachado~ (WhatsApp)
 * - `código` (Markdown)
 *
 * ESTRUCTURA:
 * - # Título (H1)
 * - ## Subtítulo (H2)
 * - ### Encabezado (H3)
 * - > Cita/blockquote
 * - - Lista con viñetas
 * - 1. Lista numerada
 *
 * LINKS:
 * - URLs: https://... o www....
 * - Teléfonos: 9 a 15 dígitos
 * - Emails: texto@dominio.com
 *
 * HTML:
 * - <strong>, <b> → negrita
 * - <em>, <i> → cursiva
 * - <u> → subrayado
 * - <br>, </p> → saltos de línea
 * - <li> → viñetas
 */
@Composable
fun FormattedText(
    text: String,
    modifier: Modifier = Modifier,
    style: TextStyle = MaterialTheme.typography.bodyLarge
) {
    val context = LocalContext.current
    val primaryColor = MaterialTheme.colorScheme.primary

    // Estado para el texto procesado
    var processedText by remember { mutableStateOf<AnnotatedString?>(null) }

    // Procesar en background
    LaunchedEffect(text, primaryColor) {
        processedText = withContext(Dispatchers.Default) {
            try {
                processTextSimple(text, primaryColor)
            } catch (e: Exception) {
                android.util.Log.e("FormattedText", "Error: ${e.message}")
                buildAnnotatedString { append(stripHtmlTags(text)) }
            }
        }
    }

    // Mostrar texto procesado o placeholder
    val displayText = processedText ?: remember {
        buildAnnotatedString { append("Cargando...") }
    }

    var textLayoutResult by remember { mutableStateOf<TextLayoutResult?>(null) }

    Text(
        text = displayText,
        style = style.copy(lineHeight = 24.sp),
        modifier = modifier.pointerInput(displayText) {
            detectTapGestures { tapOffset ->
                textLayoutResult?.let { layout ->
                    val offset = layout.getOffsetForPosition(tapOffset)
                    handleClick(offset, displayText, context)
                }
            }
        },
        onTextLayout = { textLayoutResult = it }
    )
}

/**
 * Procesa el texto de forma simple y rápida
 */
private fun processTextSimple(text: String, linkColor: Color): AnnotatedString {
    // Limpiar marcadores y decodificar entidades
    val cleanedText = cleanText(text)

    // Convertir HTML a Spanned si tiene tags HTML
    return if (cleanedText.contains('<')) {
        val spanned = htmlToSpanned(cleanedText)
        spannedToAnnotatedString(spanned, linkColor)
    } else {
        // Texto plano o markdown
        buildAnnotatedString {
            processPlainText(cleanedText, linkColor)
        }
    }
}

/**
 * Convierte HTML a Spanned usando Html.fromHtml nativo
 */
private fun htmlToSpanned(html: String): Spanned {
    val input = if (html.length > 20000) html.take(20000) else html

    // Pre-procesar para conservar estructura
    var processed = input
        // Eliminar comentarios de Gutenberg (WordPress block editor)
        .replace(Regex("<!--.*?-->"), "")
        // Saltos de línea
        .replace("<br>", "\n", ignoreCase = true)
        .replace("<br/>", "\n", ignoreCase = true)
        .replace("<br />", "\n", ignoreCase = true)
        // Párrafos: agregar doble salto para separar visualmente
        .replace("</p>", "\n\n", ignoreCase = true)
        .replace("<p>", "", ignoreCase = true)
        .replace(Regex("<p[^>]*>"), "") // <p class="..."> etc.
        // Divs
        .replace("</div>", "\n", ignoreCase = true)
        .replace("<div>", "", ignoreCase = true)
        .replace(Regex("<div[^>]*>"), "")
        // Encabezados
        .replace(Regex("</h[1-6]>"), "\n\n")
        .replace(Regex("<h[1-6][^>]*>"), "\n")
        // Listas
        .replace("<li>", "\n• ", ignoreCase = true)
        .replace("</li>", "", ignoreCase = true)
        .replace("<ul>", "", ignoreCase = true)
        .replace("</ul>", "\n", ignoreCase = true)
        .replace("<ol>", "", ignoreCase = true)
        .replace("</ol>", "\n", ignoreCase = true)
        // Limpiar múltiples saltos de línea (máximo 2)
        .replace(Regex("\n{3,}"), "\n\n")
        // Limpiar espacios antes de saltos de línea
        .replace(Regex(" +\n"), "\n")

    // Eliminar tags peligrosos
    processed = removeTagContent(processed, "script")
    processed = removeTagContent(processed, "style")

    return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
        Html.fromHtml(processed, Html.FROM_HTML_MODE_LEGACY)
    } else {
        @Suppress("DEPRECATION")
        Html.fromHtml(processed)
    }
}

/**
 * Convierte Spanned a AnnotatedString conservando estilos
 */
private fun spannedToAnnotatedString(spanned: Spanned, linkColor: Color): AnnotatedString {
    val text = spanned.toString()
        .replace("\u00A0", " ")
        .replace("\u200B", "")

    return buildAnnotatedString {
        append(text)

        // Aplicar estilos de negrita/cursiva
        spanned.getSpans(0, spanned.length, StyleSpan::class.java).forEach { span ->
            val start = spanned.getSpanStart(span)
            val end = spanned.getSpanEnd(span)
            if (start >= 0 && end <= text.length && start < end) {
                when (span.style) {
                    Typeface.BOLD -> addStyle(SpanStyle(fontWeight = FontWeight.Bold), start, end)
                    Typeface.ITALIC -> addStyle(SpanStyle(fontStyle = FontStyle.Italic), start, end)
                    Typeface.BOLD_ITALIC -> addStyle(
                        SpanStyle(fontWeight = FontWeight.Bold, fontStyle = FontStyle.Italic),
                        start, end
                    )
                }
            }
        }

        // Aplicar subrayado
        spanned.getSpans(0, spanned.length, UnderlineSpan::class.java).forEach { span ->
            val start = spanned.getSpanStart(span)
            val end = spanned.getSpanEnd(span)
            if (start >= 0 && end <= text.length && start < end) {
                addStyle(SpanStyle(textDecoration = TextDecoration.Underline), start, end)
            }
        }

        // Detectar y marcar teléfonos y URLs
        detectPhonesAndUrls(text, linkColor)
    }
}

/**
 * Procesa texto plano (markdown simple)
 */
private fun AnnotatedString.Builder.processPlainText(text: String, linkColor: Color) {
    val lines = text.split('\n')

    lines.forEachIndexed { index, line ->
        processLine(line.trim(), linkColor)
        if (index < lines.size - 1) append('\n')
    }
}

/**
 * Procesa una línea de texto con formato de bloque
 */
private fun AnnotatedString.Builder.processLine(line: String, linkColor: Color) {
    // ### Encabezado H3
    if (line.startsWith("### ")) {
        withStyle(SpanStyle(fontWeight = FontWeight.Bold, fontSize = 16.sp)) {
            processInlineFormatting(line.substring(4), linkColor)
        }
        return
    }

    // ## Subtítulo H2
    if (line.startsWith("## ")) {
        withStyle(SpanStyle(fontWeight = FontWeight.Bold, fontSize = 18.sp)) {
            processInlineFormatting(line.substring(3), linkColor)
        }
        return
    }

    // # Título H1
    if (line.startsWith("# ")) {
        withStyle(SpanStyle(fontWeight = FontWeight.Bold, fontSize = 20.sp)) {
            processInlineFormatting(line.substring(2), linkColor)
        }
        return
    }

    // > Cita/blockquote
    if (line.startsWith("> ")) {
        withStyle(SpanStyle(fontStyle = FontStyle.Italic, color = Color.Gray)) {
            append("│ ")
            processInlineFormatting(line.substring(2), linkColor)
        }
        return
    }

    // Lista con viñeta (- o *)
    if (line.startsWith("- ") || (line.startsWith("* ") && !line.startsWith("**"))) {
        append("  • ")
        processInlineFormatting(line.substring(2), linkColor)
        return
    }

    // Lista numerada (1. 2. etc)
    val numMatch = line.takeWhile { it.isDigit() }
    if (numMatch.isNotEmpty() && line.getOrNull(numMatch.length) == '.') {
        append("  $numMatch. ")
        processInlineFormatting(line.substring(numMatch.length + 1).trimStart(), linkColor)
        return
    }

    // Línea normal
    processInlineFormatting(line, linkColor)
}

/**
 * Procesa formato inline estilo WhatsApp + Markdown
 * - *negrita* (WhatsApp) o **negrita** (Markdown)
 * - _cursiva_ (WhatsApp)
 * - ~tachado~ (WhatsApp)
 * - URLs clickeables
 * - Teléfonos clickeables
 */
private fun AnnotatedString.Builder.processInlineFormatting(text: String, linkColor: Color) {
    var i = 0
    val len = text.length

    while (i < len) {
        // **negrita** (Markdown estándar)
        if (i + 1 < len && text[i] == '*' && text[i + 1] == '*') {
            val end = findClosing(text, i + 2, "**")
            if (end != -1 && end > i + 2) {
                withStyle(SpanStyle(fontWeight = FontWeight.Bold)) {
                    append(text.substring(i + 2, end))
                }
                i = end + 2
                continue
            }
        }

        // *negrita* (WhatsApp style) - solo si hay contenido y está rodeado de espacios/inicio/fin
        if (text[i] == '*' && (i == 0 || text[i - 1].isWhitespace() || text[i - 1] in ".,;:!?")) {
            val end = findWhatsAppClosing(text, i + 1, '*')
            if (end != -1 && end > i + 1) {
                withStyle(SpanStyle(fontWeight = FontWeight.Bold)) {
                    append(text.substring(i + 1, end))
                }
                i = end + 1
                continue
            }
        }

        // _cursiva_ (WhatsApp style)
        if (text[i] == '_' && (i == 0 || text[i - 1].isWhitespace() || text[i - 1] in ".,;:!?")) {
            val end = findWhatsAppClosing(text, i + 1, '_')
            if (end != -1 && end > i + 1) {
                withStyle(SpanStyle(fontStyle = FontStyle.Italic)) {
                    append(text.substring(i + 1, end))
                }
                i = end + 1
                continue
            }
        }

        // ~tachado~ (WhatsApp style)
        if (text[i] == '~' && (i == 0 || text[i - 1].isWhitespace() || text[i - 1] in ".,;:!?")) {
            val end = findWhatsAppClosing(text, i + 1, '~')
            if (end != -1 && end > i + 1) {
                withStyle(SpanStyle(textDecoration = TextDecoration.LineThrough)) {
                    append(text.substring(i + 1, end))
                }
                i = end + 1
                continue
            }
        }

        // `código` (Markdown)
        if (text[i] == '`') {
            val end = text.indexOf('`', i + 1)
            if (end != -1 && end > i + 1) {
                withStyle(SpanStyle(
                    fontFamily = androidx.compose.ui.text.font.FontFamily.Monospace,
                    background = Color(0xFFE8E8E8)
                )) {
                    append(text.substring(i + 1, end))
                }
                i = end + 1
                continue
            }
        }

        // URL (http:// o https:// o www.)
        if (text.regionMatches(i, "http://", 0, 7, ignoreCase = true) ||
            text.regionMatches(i, "https://", 0, 8, ignoreCase = true) ||
            text.regionMatches(i, "www.", 0, 4, ignoreCase = true)) {
            val url = extractUrl(text, i)
            if (url != null) {
                val startPos = length
                withStyle(SpanStyle(color = linkColor, textDecoration = TextDecoration.Underline)) {
                    append(url.first)
                }
                addStringAnnotation("URL", url.second, startPos, length)
                i = url.third
                continue
            }
        }

        // Teléfono
        if (text[i].isDigit() || (text[i] == '+' && i + 1 < len && text[i + 1].isDigit())) {
            val phone = extractPhone(text, i)
            if (phone != null) {
                val startPos = length
                withStyle(SpanStyle(color = linkColor, textDecoration = TextDecoration.Underline)) {
                    append(phone.first)
                }
                addStringAnnotation("PHONE", phone.second, startPos, length)
                i = phone.third
                continue
            }
        }

        // Caracter normal
        append(text[i])
        i++
    }
}

/**
 * Busca el cierre de formato WhatsApp (ej: * para *negrita*)
 * El cierre debe estar seguido de espacio, puntuación o fin de texto
 */
private fun findWhatsAppClosing(text: String, start: Int, marker: Char): Int {
    var i = start
    while (i < text.length) {
        if (text[i] == marker) {
            // Verificar que el cierre sea válido (seguido de espacio, puntuación o fin)
            val nextChar = text.getOrNull(i + 1)
            if (nextChar == null || nextChar.isWhitespace() || nextChar in ".,;:!?)-") {
                return i
            }
        }
        i++
    }
    return -1
}

/**
 * Extrae una URL del texto
 */
private fun extractUrl(text: String, start: Int): Triple<String, String, Int>? {
    var i = start
    val sb = StringBuilder()

    // Leer hasta encontrar espacio o caracter no válido para URL
    while (i < text.length) {
        val c = text[i]
        if (c.isWhitespace() || c in "<>\"'") break
        sb.append(c)
        i++
    }

    val url = sb.toString().trimEnd('.', ',', ';', ':', '!', '?', ')')

    // Validar que sea una URL válida (mínimo dominio.ext)
    if (url.length > 5 && (url.contains("://") || url.startsWith("www."))) {
        val fullUrl = if (url.startsWith("www.")) "https://$url" else url
        return Triple(url, fullUrl, start + url.length)
    }
    return null
}

/**
 * Detecta teléfonos y URLs en texto ya procesado (para HTML)
 */
private fun AnnotatedString.Builder.detectPhonesAndUrls(text: String, linkColor: Color) {
    var i = 0
    while (i < text.length) {
        // Detectar URL
        if (text.regionMatches(i, "http://", 0, 7, ignoreCase = true) ||
            text.regionMatches(i, "https://", 0, 8, ignoreCase = true) ||
            text.regionMatches(i, "www.", 0, 4, ignoreCase = true)) {
            val url = extractUrl(text, i)
            if (url != null) {
                addStyle(
                    SpanStyle(color = linkColor, textDecoration = TextDecoration.Underline),
                    i, i + url.first.length
                )
                addStringAnnotation("URL", url.second, i, i + url.first.length)
                i += url.first.length
                continue
            }
        }

        // Detectar teléfono
        if (text[i].isDigit() || (text[i] == '+' && i + 1 < text.length && text[i + 1].isDigit())) {
            val phone = extractPhone(text, i)
            if (phone != null) {
                addStyle(
                    SpanStyle(color = linkColor, textDecoration = TextDecoration.Underline),
                    i, phone.third
                )
                addStringAnnotation("PHONE", phone.second, i, phone.third)
                i = phone.third
                continue
            }
        }
        i++
    }
}

/**
 * Extrae un teléfono: (displayText, cleanNumber, endIndex)
 */
private fun extractPhone(text: String, start: Int): Triple<String, String, Int>? {
    val display = StringBuilder()
    val clean = StringBuilder()
    var i = start

    if (text[i] == '+') {
        display.append('+')
        clean.append('+')
        i++
    }

    var digits = 0
    while (i < text.length) {
        when {
            text[i].isDigit() -> {
                display.append(text[i])
                clean.append(text[i])
                digits++
                i++
            }
            text[i] == ' ' || text[i] == '-' || text[i] == '.' -> {
                display.append(text[i])
                i++
            }
            else -> break
        }
    }

    return if (digits in 9..15) {
        Triple(display.toString().trim(), clean.toString(), i)
    } else null
}

private fun findClosing(text: String, start: Int, marker: String): Int {
    var i = start
    while (i <= text.length - marker.length) {
        if (text.substring(i, i + marker.length) == marker) return i
        i++
    }
    return -1
}

/**
 * Limpia el texto de marcadores y decodifica entidades HTML
 */
private fun cleanText(text: String): String {
    var result = text

    // Eliminar marcadores internos
    val markers = listOf("[KEYWORD_START:", "[KEYWORD_END]", "[PHONE_CONTAINER_START]",
        "[PHONE_CONTAINER_END]", "[LINK_START:", "[LINK_END]")

    for (marker in markers) {
        while (result.contains(marker)) {
            val start = result.indexOf(marker)
            if (start == -1) break
            if (marker.endsWith(":")) {
                val end = result.indexOf(']', start)
                if (end != -1) result = result.removeRange(start, end + 1)
                else break
            } else {
                result = result.replace(marker, "")
            }
        }
    }

    // Decodificar entidades HTML comunes
    if (result.contains('&')) {
        result = result
            .replace("&amp;", "&")
            .replace("&nbsp;", " ")
            .replace("&lt;", "<")
            .replace("&gt;", ">")
            .replace("&quot;", "\"")
            .replace("&#8211;", "–")
            .replace("&#8212;", "—")
            .replace("&#8216;", "'")
            .replace("&#8217;", "'")
            .replace("&#8220;", """)
            .replace("&#8221;", """)
            .replace("&#8230;", "…")
            .replace("&#160;", " ")
    }

    return result
}

private fun stripHtmlTags(html: String): String {
    val sb = StringBuilder()
    var inTag = false
    for (c in html) {
        when {
            c == '<' -> inTag = true
            c == '>' -> inTag = false
            !inTag -> sb.append(c)
        }
    }
    return sb.toString()
}

private fun removeTagContent(html: String, tag: String): String {
    if (!html.contains("<$tag", ignoreCase = true)) return html

    val sb = StringBuilder()
    var i = 0
    val openTag = "<$tag"
    val closeTag = "</$tag>"

    while (i < html.length) {
        val start = html.indexOf(openTag, i, ignoreCase = true)
        if (start == -1) {
            sb.append(html.substring(i))
            break
        }
        sb.append(html.substring(i, start))
        val end = html.indexOf(closeTag, start, ignoreCase = true)
        i = if (end != -1) end + closeTag.length else {
            val tagEnd = html.indexOf('>', start)
            if (tagEnd != -1) tagEnd + 1 else start + openTag.length
        }
    }
    return sb.toString()
}

/**
 * Maneja clicks en teléfonos, emails, URLs
 */
private fun handleClick(offset: Int, text: AnnotatedString, context: Context) {
    // Teléfono - abrir dialer
    text.getStringAnnotations("PHONE", offset, offset).firstOrNull()?.let {
        context.startActivity(Intent(Intent.ACTION_DIAL, Uri.parse("tel:${it.item}")))
        return
    }

    // Email
    text.getStringAnnotations("EMAIL", offset, offset).firstOrNull()?.let {
        context.startActivity(Intent(Intent.ACTION_SENDTO, Uri.parse("mailto:${it.item}")))
        return
    }

    // URL
    text.getStringAnnotations("URL", offset, offset).firstOrNull()?.let {
        val url = if (it.item.startsWith("http")) it.item else "https://${it.item}"
        context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
    }
}
