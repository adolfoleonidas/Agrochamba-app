package agrochamba.com.ui.common

import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.text.Html
import android.text.Spanned
import android.text.style.StyleSpan
import android.text.style.UnderlineSpan
import android.text.style.URLSpan
import android.graphics.Typeface
import androidx.compose.foundation.text.ClickableText
import androidx.compose.material3.MaterialTheme
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
 * FormattedText v3.0 - WYSIWYG: renderiza fielmente sin modificar contenido
 *
 * FORMATO SOPORTADO:
 * - Encabezados: ## (h2), ### (h3), <h2>, <h3>
 * - Negrita: **texto** o <strong>
 * - Cursiva: *texto* o <em>
 * - Listas: • con indentación
 * - Links: clickeables con subrayado
 * - Teléfonos: clickeables con diálogo (Llamar/WhatsApp)
 * - Emails: clickeables
 *
 * PRINCIPIO WYSIWYG:
 * - Lo que el usuario escribe = lo que se muestra
 * - Sin emojis automáticos ni modificaciones
 * - El usuario controla el contenido completamente
 *
 * RENDIMIENTO:
 * - Procesamiento en background thread
 * - Sin regex pesados en main thread
 */

@Composable
fun FormattedText(
    text: String,
    modifier: Modifier = Modifier,
    style: TextStyle = MaterialTheme.typography.bodyLarge
) {
    val context = LocalContext.current
    val primaryColor = MaterialTheme.colorScheme.primary
    val sectionColor = MaterialTheme.colorScheme.primary

    var processedText by remember { mutableStateOf<AnnotatedString?>(null) }

    // Estado para el diálogo de opciones de teléfono
    var showPhoneDialog by remember { mutableStateOf(false) }
    var selectedPhone by remember { mutableStateOf("") }

    LaunchedEffect(text, primaryColor) {
        processedText = withContext(Dispatchers.Default) {
            try {
                processText(text, primaryColor, sectionColor)
            } catch (e: Exception) {
                android.util.Log.e("FormattedText", "Error: ${e.message}")
                buildAnnotatedString { append(stripHtmlTags(text)) }
            }
        }
    }

    val displayText = processedText ?: remember {
        buildAnnotatedString { append("") }
    }

    // Line-height de 1.6x es óptimo para legibilidad (recomendación: 1.5-1.7)
    val optimizedLineHeight = (style.fontSize.value * 1.6f).sp

    ClickableText(
        text = displayText,
        style = style.copy(lineHeight = optimizedLineHeight),
        modifier = modifier,
        onClick = { offset ->
            handleClickWithCallback(
                offset = offset,
                text = displayText,
                context = context,
                onPhoneClick = { phone ->
                    selectedPhone = phone
                    showPhoneDialog = true
                }
            )
        }
    )

    // Diálogo de opciones para teléfono
    if (showPhoneDialog && selectedPhone.isNotBlank()) {
        PhoneOptionsDialog(
            phone = selectedPhone,
            onDismiss = { showPhoneDialog = false },
            onCallClick = {
                context.startActivity(Intent(Intent.ACTION_DIAL, Uri.parse("tel:$selectedPhone")))
                showPhoneDialog = false
            },
            onWhatsAppClick = {
                // Formatear número para WhatsApp (quitar espacios y guiones)
                val cleanPhone = selectedPhone.replace(Regex("[\\s\\-()]"), "")
                val whatsappNumber = if (cleanPhone.startsWith("+")) cleanPhone else "+51$cleanPhone"
                val whatsappUri = Uri.parse("https://wa.me/${whatsappNumber.removePrefix("+")}")
                context.startActivity(Intent(Intent.ACTION_VIEW, whatsappUri))
                showPhoneDialog = false
            }
        )
    }
}

/**
 * Procesa el texto completo
 */
private fun processText(text: String, linkColor: Color, sectionColor: Color): AnnotatedString {
    val cleanedText = cleanText(text)

    return if (cleanedText.contains('<')) {
        processHtmlText(cleanedText, linkColor, sectionColor)
    } else {
        buildAnnotatedString {
            processPlainText(cleanedText, linkColor, sectionColor)
        }
    }
}

/**
 * Procesa texto HTML
 */
private fun processHtmlText(html: String, linkColor: Color, sectionColor: Color): AnnotatedString {
    val input = if (html.length > 20000) html.take(20000) else html

    // Pre-procesar HTML para estructura
    var processed = input
        // Eliminar comentarios de Gutenberg
        .replace(Regex("<!--.*?-->"), "")
        // Normalizar br
        .replace("<br/>", "<br>", ignoreCase = true)
        .replace("<br />", "<br>", ignoreCase = true)
        // Párrafos con doble salto
        .replace("</p>", "<br><br>", ignoreCase = true)
        .replace("<p>", "", ignoreCase = true)
        .replace(Regex("<p[^>]*>"), "")
        // Divs
        .replace("</div>", "<br>", ignoreCase = true)
        .replace("<div>", "", ignoreCase = true)
        .replace(Regex("<div[^>]*>"), "")
        // Encabezados
        .replace(Regex("</h[1-6]>"), "<br><br>")
        .replace(Regex("<h[1-6][^>]*>"), "<br>")
        // Listas - sin espaciado extra
        .replace("<ul>", "", ignoreCase = true)
        .replace("</ul>", "", ignoreCase = true)
        .replace("<ol>", "", ignoreCase = true)
        .replace("</ol>", "", ignoreCase = true)
        .replace(Regex("<li[^>]*>"), "<br>• ")
        .replace("</li>", "", ignoreCase = true)
        // Limpiar múltiples br
        .replace(Regex("(<br>){3,}"), "<br><br>")
        .replace(Regex("^(<br>)+"), "")
        .replace(Regex("(<br>)+$"), "")
        .replace(Regex(" +<br>"), "<br>")

    // Eliminar tags peligrosos
    processed = removeTagContent(processed, "script")
    processed = removeTagContent(processed, "style")

    // Convertir HTML a Spanned
    val spanned = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
        Html.fromHtml(processed, Html.FROM_HTML_MODE_LEGACY)
    } else {
        @Suppress("DEPRECATION")
        Html.fromHtml(processed)
    }

    // Limpiar caracteres especiales
    val text = spanned.toString()
        .replace("\u00A0", " ")
        .replace("\u200B", "")
        .replace("\uFFFC", "")
        .replace("\uFFFD", "")
        .replace("\u200C", "")
        .replace("\u200D", "")
        .replace("\u2028", "\n")
        .replace("\u2029", "\n\n")
        .trim()

    return buildAnnotatedString {
        // Procesar línea por línea
        val lines = text.split('\n')
        var isFirstLine = true

        lines.forEach { line ->
            if (line.isBlank()) {
                if (!isFirstLine) append('\n')
                return@forEach
            }

            if (!isFirstLine) append('\n')
            isFirstLine = false

            val trimmedLine = line.trim()

            when {
                // Item de lista con viñeta
                trimmedLine.startsWith("•") || trimmedLine.startsWith("-") || trimmedLine.startsWith("*") -> {
                    withStyle(SpanStyle(color = sectionColor)) {
                        append("• ")
                    }
                    appendStyledText(trimmedLine.removePrefix("•").removePrefix("-").removePrefix("*").trim(), spanned, linkColor)
                }
                // Lista numerada
                trimmedLine.matches(Regex("^\\d+\\.\\s.*")) -> {
                    val num = trimmedLine.takeWhile { it.isDigit() || it == '.' }
                    withStyle(SpanStyle(color = sectionColor, fontWeight = FontWeight.Medium)) {
                        append("$num ")
                    }
                    appendStyledText(trimmedLine.removePrefix(num).trim(), spanned, linkColor)
                }
                // Texto normal
                else -> appendStyledText(trimmedLine, spanned, linkColor)
            }
        }

        // Detectar links y teléfonos
        detectLinksAndPhones(toString(), linkColor)
    }
}

/**
 * Añade texto con estilos del Spanned original
 */
private fun AnnotatedString.Builder.appendStyledText(text: String, spanned: Spanned, linkColor: Color) {
    val startIndex = length
    append(text)
    val endIndex = length

    // Buscar estilos en el spanned original que apliquen a este texto
    val spannedText = spanned.toString()
    val textStart = spannedText.indexOf(text)
    if (textStart >= 0) {
        val textEnd = textStart + text.length

        spanned.getSpans(textStart, textEnd, StyleSpan::class.java).forEach { span ->
            val spanStart = maxOf(0, spanned.getSpanStart(span) - textStart)
            val spanEnd = minOf(text.length, spanned.getSpanEnd(span) - textStart)
            if (spanStart < spanEnd && spanStart >= 0 && spanEnd <= text.length) {
                when (span.style) {
                    Typeface.BOLD -> addStyle(
                        SpanStyle(fontWeight = FontWeight.Bold),
                        startIndex + spanStart,
                        startIndex + spanEnd
                    )
                    Typeface.ITALIC -> addStyle(
                        SpanStyle(fontStyle = FontStyle.Italic),
                        startIndex + spanStart,
                        startIndex + spanEnd
                    )
                    Typeface.BOLD_ITALIC -> addStyle(
                        SpanStyle(fontWeight = FontWeight.Bold, fontStyle = FontStyle.Italic),
                        startIndex + spanStart,
                        startIndex + spanEnd
                    )
                }
            }
        }

        spanned.getSpans(textStart, textEnd, UnderlineSpan::class.java).forEach { span ->
            val spanStart = maxOf(0, spanned.getSpanStart(span) - textStart)
            val spanEnd = minOf(text.length, spanned.getSpanEnd(span) - textStart)
            if (spanStart < spanEnd && spanStart >= 0 && spanEnd <= text.length) {
                addStyle(
                    SpanStyle(textDecoration = TextDecoration.Underline),
                    startIndex + spanStart,
                    startIndex + spanEnd
                )
            }
        }

        // Procesar links HTML (<a href="...">)
        spanned.getSpans(textStart, textEnd, URLSpan::class.java).forEach { span ->
            val spanStart = maxOf(0, spanned.getSpanStart(span) - textStart)
            val spanEnd = minOf(text.length, spanned.getSpanEnd(span) - textStart)
            if (spanStart < spanEnd && spanStart >= 0 && spanEnd <= text.length) {
                val url = span.url
                if (!url.isNullOrBlank()) {
                    // Agregar estilo de link
                    addStyle(
                        SpanStyle(
                            color = linkColor,
                            textDecoration = TextDecoration.Underline
                        ),
                        startIndex + spanStart,
                        startIndex + spanEnd
                    )
                    // Agregar anotación para hacer clickeable
                    addStringAnnotation("URL", url, startIndex + spanStart, startIndex + spanEnd)
                }
            }
        }
    }
}

/**
 * Procesa texto plano (sin HTML)
 */
private fun AnnotatedString.Builder.processPlainText(text: String, linkColor: Color, sectionColor: Color) {
    val lines = text.split('\n')
    var isFirstLine = true

    lines.forEach { rawLine ->
        val line = rawLine.trim()

        if (line.isBlank()) {
            if (!isFirstLine) append('\n')
            return@forEach
        }

        if (!isFirstLine) append('\n')
        isFirstLine = false

        when {
            // Encabezados Markdown - WYSIWYG: se muestran como el usuario los escribió
            line.startsWith("### ") -> {
                withStyle(SpanStyle(fontWeight = FontWeight.Bold, fontSize = 15.sp, color = sectionColor)) {
                    append(line.substring(4))
                }
            }
            line.startsWith("## ") -> {
                withStyle(SpanStyle(fontWeight = FontWeight.SemiBold, fontSize = 17.sp, color = sectionColor)) {
                    append(line.substring(3))
                }
            }
            line.startsWith("# ") -> {
                withStyle(SpanStyle(fontWeight = FontWeight.Bold, fontSize = 18.sp, color = sectionColor)) {
                    append(line.substring(2))
                }
            }
            // Lista con viñeta
            line.startsWith("- ") || line.startsWith("* ") -> {
                withStyle(SpanStyle(color = sectionColor)) { append("• ") }
                processInlineFormatting(line.substring(2), linkColor)
            }
            // Lista numerada
            line.matches(Regex("^\\d+\\.\\s.*")) -> {
                val num = line.takeWhile { it.isDigit() || it == '.' || it == ' ' }
                withStyle(SpanStyle(color = sectionColor, fontWeight = FontWeight.Medium)) {
                    append(num)
                }
                processInlineFormatting(line.substring(num.length), linkColor)
            }
            // Cita
            line.startsWith("> ") -> {
                withStyle(SpanStyle(fontStyle = FontStyle.Italic, color = Color.Gray)) {
                    append("│ ")
                    processInlineFormatting(line.substring(2), linkColor)
                }
            }
            // Línea normal
            else -> processInlineFormatting(line, linkColor)
        }
    }
}

/**
 * Procesa formato inline (negrita, cursiva, links, teléfonos)
 */
private fun AnnotatedString.Builder.processInlineFormatting(text: String, linkColor: Color) {
    var i = 0
    val len = text.length

    while (i < len) {
        // **negrita**
        if (i + 1 < len && text[i] == '*' && text[i + 1] == '*') {
            val end = text.indexOf("**", i + 2)
            if (end > i + 2) {
                withStyle(SpanStyle(fontWeight = FontWeight.Bold)) {
                    append(text.substring(i + 2, end))
                }
                i = end + 2
                continue
            }
        }

        // *cursiva* (solo si está rodeado correctamente)
        if (text[i] == '*' && (i == 0 || text[i - 1].isWhitespace())) {
            val end = findClosingMarker(text, i + 1, '*')
            if (end > i + 1) {
                withStyle(SpanStyle(fontStyle = FontStyle.Italic)) {
                    append(text.substring(i + 1, end))
                }
                i = end + 1
                continue
            }
        }

        // _cursiva_
        if (text[i] == '_' && (i == 0 || text[i - 1].isWhitespace())) {
            val end = findClosingMarker(text, i + 1, '_')
            if (end > i + 1) {
                withStyle(SpanStyle(fontStyle = FontStyle.Italic)) {
                    append(text.substring(i + 1, end))
                }
                i = end + 1
                continue
            }
        }

        // URL
        if (text.regionMatches(i, "http://", 0, 7, ignoreCase = true) ||
            text.regionMatches(i, "https://", 0, 8, ignoreCase = true) ||
            text.regionMatches(i, "www.", 0, 4, ignoreCase = true)) {
            val url = extractUrl(text, i)
            if (url != null) {
                val startPos = length
                withStyle(SpanStyle(color = linkColor, textDecoration = TextDecoration.Underline)) {
                    append("🔗 ${url.first}")
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
                    append("📞 ${phone.first}")
                }
                addStringAnnotation("PHONE", phone.second, startPos, length)
                i = phone.third
                continue
            }
        }

        // Email
        if (text[i] == '@' || (i > 0 && text[i - 1].isLetterOrDigit() && text.indexOf('@', i) in (i + 1)..(i + 30))) {
            // Intentar extraer email comenzando antes del @
            val emailStart = (i - 20).coerceAtLeast(0)
            val potentialEmail = text.substring(emailStart, minOf(text.length, i + 50))
            val emailMatch = Regex("[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}").find(potentialEmail)
            if (emailMatch != null) {
                val globalStart = emailStart + emailMatch.range.first
                if (globalStart == i || (globalStart < i && globalStart + emailMatch.value.length > i)) {
                    // Ya pasamos el inicio del email
                    append(text[i])
                    i++
                    continue
                }
            }
        }

        append(text[i])
        i++
    }
}

/**
 * Detecta links y teléfonos en texto ya procesado
 */
private fun AnnotatedString.Builder.detectLinksAndPhones(text: String, linkColor: Color) {
    var i = 0
    while (i < text.length) {
        // URL (si no tiene emoji ya)
        if ((text.regionMatches(i, "http://", 0, 7, ignoreCase = true) ||
            text.regionMatches(i, "https://", 0, 8, ignoreCase = true) ||
            text.regionMatches(i, "www.", 0, 4, ignoreCase = true)) &&
            (i < 2 || !text.substring(maxOf(0, i - 2), i).contains("🔗"))) {
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

        // Teléfono (si no tiene emoji ya)
        if ((text[i].isDigit() || (text[i] == '+' && i + 1 < text.length && text[i + 1].isDigit())) &&
            (i < 2 || !text.substring(maxOf(0, i - 2), i).contains("📞"))) {
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

        // Email
        val remainingText = text.substring(i)
        val emailMatch = Regex("^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}").find(remainingText)
        if (emailMatch != null) {
            addStyle(
                SpanStyle(color = linkColor, textDecoration = TextDecoration.Underline),
                i, i + emailMatch.value.length
            )
            addStringAnnotation("EMAIL", emailMatch.value, i, i + emailMatch.value.length)
            i += emailMatch.value.length
            continue
        }

        i++
    }
}

private fun findClosingMarker(text: String, start: Int, marker: Char): Int {
    var i = start
    while (i < text.length) {
        if (text[i] == marker) {
            val nextChar = text.getOrNull(i + 1)
            if (nextChar == null || nextChar.isWhitespace() || nextChar in ".,;:!?)-") {
                return i
            }
        }
        i++
    }
    return -1
}

private fun extractUrl(text: String, start: Int): Triple<String, String, Int>? {
    var i = start
    val sb = StringBuilder()

    while (i < text.length) {
        val c = text[i]
        if (c.isWhitespace() || c in "<>\"'") break
        sb.append(c)
        i++
    }

    val url = sb.toString().trimEnd('.', ',', ';', ':', '!', '?', ')')

    if (url.length > 5 && (url.contains("://") || url.startsWith("www."))) {
        val fullUrl = if (url.startsWith("www.")) "https://$url" else url
        return Triple(url, fullUrl, start + url.length)
    }
    return null
}

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

    // Decodificar entidades HTML
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

private fun handleClick(offset: Int, text: AnnotatedString, context: Context) {
    text.getStringAnnotations("PHONE", offset, offset).firstOrNull()?.let {
        context.startActivity(Intent(Intent.ACTION_DIAL, Uri.parse("tel:${it.item}")))
        return
    }

    text.getStringAnnotations("EMAIL", offset, offset).firstOrNull()?.let {
        context.startActivity(Intent(Intent.ACTION_SENDTO, Uri.parse("mailto:${it.item}")))
        return
    }

    text.getStringAnnotations("URL", offset, offset).firstOrNull()?.let {
        val url = if (it.item.startsWith("http")) it.item else "https://${it.item}"
        context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
    }
}

/**
 * Versión de handleClick con callback para teléfonos (permite mostrar diálogo)
 */
private fun handleClickWithCallback(
    offset: Int,
    text: AnnotatedString,
    context: Context,
    onPhoneClick: (String) -> Unit
) {
    // Teléfono: usar callback para mostrar diálogo
    text.getStringAnnotations("PHONE", offset, offset).firstOrNull()?.let {
        onPhoneClick(it.item)
        return
    }

    // Email: abrir directamente
    text.getStringAnnotations("EMAIL", offset, offset).firstOrNull()?.let {
        context.startActivity(Intent(Intent.ACTION_SENDTO, Uri.parse("mailto:${it.item}")))
        return
    }

    // URL: abrir directamente
    text.getStringAnnotations("URL", offset, offset).firstOrNull()?.let {
        val url = if (it.item.startsWith("http")) it.item else "https://${it.item}"
        context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
    }
}

/**
 * Diálogo de opciones para teléfono (Llamar / WhatsApp)
 */
@Composable
private fun PhoneOptionsDialog(
    phone: String,
    onDismiss: () -> Unit,
    onCallClick: () -> Unit,
    onWhatsAppClick: () -> Unit
) {
    androidx.compose.material3.AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            androidx.compose.material3.Text(
                text = phone,
                style = androidx.compose.material3.MaterialTheme.typography.titleMedium
            )
        },
        text = {
            androidx.compose.material3.Text(
                text = "¿Qué deseas hacer?",
                style = androidx.compose.material3.MaterialTheme.typography.bodyMedium
            )
        },
        confirmButton = {
            // Botón WhatsApp
            androidx.compose.material3.TextButton(onClick = onWhatsAppClick) {
                androidx.compose.material3.Text("💬 WhatsApp")
            }
        },
        dismissButton = {
            androidx.compose.foundation.layout.Row {
                androidx.compose.material3.TextButton(onClick = onDismiss) {
                    androidx.compose.material3.Text("Cancelar")
                }
                // Botón Llamar
                androidx.compose.material3.TextButton(onClick = onCallClick) {
                    androidx.compose.material3.Text("📞 Llamar")
                }
            }
        }
    )
}
