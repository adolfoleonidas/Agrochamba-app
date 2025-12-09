package agrochamba.com.ui.common

import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.text.Html
import androidx.compose.foundation.gestures.detectTapGestures
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.text.InlineTextContent
import androidx.compose.foundation.text.appendInlineContent
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Phone
import androidx.compose.material.icons.filled.Message
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.text.*
import androidx.compose.ui.text.Placeholder
import androidx.compose.ui.text.PlaceholderVerticalAlign
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/**
 * Componente que renderiza texto con formato HTML/Markdown de forma visual
 * Detecta automáticamente teléfonos, emails y URLs y los hace clickeables
 */
@Composable
fun FormattedText(
    text: String,
    modifier: Modifier = Modifier,
    style: TextStyle = MaterialTheme.typography.bodyLarge
) {
    val colorScheme = MaterialTheme.colorScheme
    val defaultColor = style.color ?: colorScheme.onSurface
    val context = LocalContext.current
    val primaryColor = colorScheme.primary
    
    val density = LocalDensity.current
    val iconSize = with(density) { 18.dp.toSp() }
    
    val annotatedStringWithIcons = remember(text, defaultColor, primaryColor, iconSize) {
        buildAnnotatedString {
            parseFormattedText(text, style, defaultColor, primaryColor)
        }
    }
    
    val inlineContentMap: Map<String, InlineTextContent> = remember(primaryColor, iconSize, density) {
        mapOf<String, InlineTextContent>(
            "phoneIcon" to InlineTextContent(
                placeholder = Placeholder(
                    width = iconSize,
                    height = iconSize,
                    placeholderVerticalAlign = PlaceholderVerticalAlign.TextCenter
                )
            ) {
                Icon(
                    imageVector = Icons.Default.Phone,
                    contentDescription = "Llamar",
                    tint = primaryColor,
                    modifier = Modifier.size(18.dp)
                )
            },
            "whatsappIcon" to InlineTextContent(
                placeholder = Placeholder(
                    width = iconSize,
                    height = iconSize,
                    placeholderVerticalAlign = PlaceholderVerticalAlign.TextCenter
                )
            ) {
                Icon(
                    imageVector = Icons.Default.Message,
                    contentDescription = "WhatsApp",
                    tint = Color(0xFF25D366), // Color verde de WhatsApp
                    modifier = Modifier.size(18.dp)
                )
            },
        )
    }
    
    var textLayoutResult by remember { mutableStateOf<androidx.compose.ui.text.TextLayoutResult?>(null) }
    
    Text(
        text = annotatedStringWithIcons,
        style = style.copy(lineHeight = style.lineHeight ?: 24.sp),
        modifier = modifier.pointerInput(Unit) {
            detectTapGestures { tapOffset ->
                textLayoutResult?.let { layout ->
                    val offset = layout.getOffsetForPosition(tapOffset)
                    handleTextClick(offset, annotatedStringWithIcons, context)
                }
            }
        },
        inlineContent = inlineContentMap,
        onTextLayout = { layout ->
            textLayoutResult = layout
        }
    )
}

/**
 * Maneja los clicks en el texto, detectando annotations y ejecutando acciones correspondientes
 */
private fun handleTextClick(
    offset: Int,
    annotatedString: AnnotatedString,
    context: Context
) {
    // Obtener todas las annotations en esta posición
    // Para iconos inline, necesitamos buscar annotations que contengan el offset
    
    // Luego verificar WhatsApp (icono de WhatsApp)
    annotatedString.getStringAnnotations(
        tag = "PHONE_WHATSAPP",
        start = offset,
        end = offset
    ).firstOrNull()?.let { annotation ->
        val phone = annotation.item
        // Abrir WhatsApp con el número usando formato wa.me
        val intent = Intent(Intent.ACTION_VIEW).apply {
            data = Uri.parse("https://wa.me/$phone")
            setPackage("com.whatsapp")
        }
        try {
            context.startActivity(intent)
        } catch (e: Exception) {
            // Si WhatsApp no está instalado, intentar con el navegador (WhatsApp Web)
            val webIntent = Intent(Intent.ACTION_VIEW, Uri.parse("https://wa.me/$phone"))
            context.startActivity(webIntent)
        }
        return
    }
    
    // Verificar si el offset está dentro de algún rango de PHONE_WHATSAPP (para iconos inline)
    annotatedString.getStringAnnotations(tag = "PHONE_WHATSAPP", start = 0, end = annotatedString.length).forEach { annotation ->
        if (offset >= annotation.start && offset <= annotation.end) {
            val phone = annotation.item
            val intent = Intent(Intent.ACTION_VIEW).apply {
                data = Uri.parse("https://wa.me/$phone")
                setPackage("com.whatsapp")
            }
            try {
                context.startActivity(intent)
            } catch (e: Exception) {
                val webIntent = Intent(Intent.ACTION_VIEW, Uri.parse("https://wa.me/$phone"))
                context.startActivity(webIntent)
            }
            return
        }
    }
    
    // Luego verificar teléfono normal (icono de teléfono)
    annotatedString.getStringAnnotations(
        tag = "PHONE_CALL",
        start = offset,
        end = offset
    ).firstOrNull()?.let { annotation ->
        val phone = annotation.item
        val intent = Intent(Intent.ACTION_DIAL, Uri.parse("tel:$phone"))
        context.startActivity(intent)
        return
    }
    
    // Verificar si el offset está dentro de algún rango de PHONE_CALL (para iconos inline)
    annotatedString.getStringAnnotations(tag = "PHONE_CALL", start = 0, end = annotatedString.length).forEach { annotation ->
        if (offset >= annotation.start && offset <= annotation.end) {
            val phone = annotation.item
            val intent = Intent(Intent.ACTION_DIAL, Uri.parse("tel:$phone"))
            context.startActivity(intent)
            return
        }
    }
    
    // Mantener compatibilidad con el tag antiguo PHONE (si existe)
    annotatedString.getStringAnnotations(
        tag = "PHONE",
        start = offset,
        end = offset
    ).firstOrNull()?.let { annotation ->
        val phone = annotation.item
        val intent = Intent(Intent.ACTION_DIAL, Uri.parse("tel:$phone"))
        context.startActivity(intent)
        return
    }
    
    annotatedString.getStringAnnotations(
        tag = "EMAIL",
        start = offset,
        end = offset
    ).firstOrNull()?.let { annotation ->
        val email = annotation.item
        val intent = Intent(Intent.ACTION_SENDTO).apply {
            data = Uri.parse("mailto:$email")
        }
        context.startActivity(intent)
        return
    }
    
    annotatedString.getStringAnnotations(
        tag = "URL",
        start = offset,
        end = offset
    ).firstOrNull()?.let { annotation ->
        val url = annotation.item
        val finalUrl = if (url.startsWith("http://") || url.startsWith("https://")) {
            url
        } else {
            "https://$url"
        }
        val intent = Intent(Intent.ACTION_VIEW, Uri.parse(finalUrl))
        context.startActivity(intent)
        return
    }
}

/**
 * Parsea texto con formato HTML/Markdown y aplica estilos visuales
 * También detecta y marca teléfonos, emails y URLs como clickeables
 */
private fun AnnotatedString.Builder.parseFormattedText(
    text: String, 
    baseStyle: TextStyle, 
    defaultColor: androidx.compose.ui.graphics.Color,
    linkColor: androidx.compose.ui.graphics.Color
) {
    // Primero convertir HTML a Markdown si es necesario
    val markdownText = if (text.contains("<")) {
        text.htmlToMarkdownForDisplay()
    } else {
        text
    }
    
    // Parsear Markdown y aplicar estilos, detectando también links
    parseMarkdown(markdownText, baseStyle, defaultColor, linkColor)
}

/**
 * Convierte HTML básico a Markdown para visualización
 */
private fun String.htmlToMarkdownForDisplay(): String {
    var markdown = this
    
    // PRIMERO: Eliminar elementos problemáticos que pueden dejar iconos o espacios
    // Estos elementos se eliminan completamente con su contenido
    val problematicTags = listOf(
        "object", "embed", "iframe", "applet", "param",
        "script", "style", "noscript"
    )
    
    problematicTags.forEach { tag ->
        // Eliminar tags de apertura y cierre con todo su contenido
        markdown = markdown.replace(
            Regex("<$tag[^>]*>.*?</$tag>", setOf(RegexOption.DOT_MATCHES_ALL, RegexOption.IGNORE_CASE)),
            ""
        )
        // Eliminar tags auto-cerrados
        markdown = markdown.replace(
            Regex("<$tag[^>]*/?>", RegexOption.IGNORE_CASE),
            ""
        )
    }
    
    // Eliminar imágenes que pueden dejar espacios o iconos
    markdown = markdown.replace(
        Regex("<img[^>]*>", RegexOption.IGNORE_CASE),
        ""
    )
    
    // SEGUNDO: Decodificar entidades HTML como &#8212; (em dash), &nbsp;, etc.
    // Esto debe hacerse ANTES de procesar las etiquetas HTML
    markdown = decodeHtmlEntities(markdown)
    
    // Convertir <strong> y <b> a **
    markdown = Regex("<strong>(.*?)</strong>", RegexOption.DOT_MATCHES_ALL).replace(markdown) { 
        "**${it.groupValues[1]}**"
    }
    markdown = Regex("<b>(.*?)</b>", RegexOption.DOT_MATCHES_ALL).replace(markdown) { 
        "**${it.groupValues[1]}**"
    }
    
    // Convertir <em> y <i> a *
    markdown = Regex("<em>(.*?)</em>", RegexOption.DOT_MATCHES_ALL).replace(markdown) { 
        "*${it.groupValues[1]}*"
    }
    markdown = Regex("<i>(.*?)</i>", RegexOption.DOT_MATCHES_ALL).replace(markdown) { 
        "*${it.groupValues[1]}*"
    }
    
    // Convertir listas numeradas
    markdown = Regex("<ol>.*?</ol>", RegexOption.DOT_MATCHES_ALL).replace(markdown) { listMatch ->
        var counter = 1
        Regex("<li>(.*?)</li>", RegexOption.DOT_MATCHES_ALL).replace(listMatch.value) { liMatch ->
            "${counter++}. ${liMatch.groupValues[1].trim()}\n"
        }
    }
    
    // Convertir listas con viñetas
    markdown = Regex("<ul>.*?</ul>", RegexOption.DOT_MATCHES_ALL).replace(markdown) { listMatch ->
        Regex("<li>(.*?)</li>", RegexOption.DOT_MATCHES_ALL).replace(listMatch.value) { liMatch ->
            "- ${liMatch.groupValues[1].trim()}\n"
        }
    }
    
    // Convertir <p> preservando saltos de línea exactos
    markdown = Regex("<p[^>]*>(.*?)</p>", RegexOption.DOT_MATCHES_ALL).replace(markdown) { 
        val content = it.groupValues[1]
        // Preservar el contenido tal cual, incluyendo saltos de línea internos
        "$content\n"
    }
    
    // Convertir <br> a saltos de línea simples
    markdown = Regex("<br\\s*/?>", RegexOption.IGNORE_CASE).replace(markdown, "\n")
    
    // Limpiar HTML restante pero preservar saltos de línea
    markdown = Regex("<[^>]+>").replace(markdown, "")
    
    // NO limpiar saltos de línea múltiples - respetar exactamente lo que el usuario escribió
    return markdown
}

/**
 * Decodifica entidades HTML como &nbsp;, &amp;, etc.
 * PRESERVA los guiones tal como están escritos (---, --, etc.)
 * Solo decodifica entidades HTML estándar, sin convertir guiones
 */
private fun decodeHtmlEntities(text: String): String {
    // Usar Html.fromHtml para decodificar entidades HTML estándar
    // Esto convierte &nbsp; a espacio, &amp; a &, &lt; a <, &gt; a >, etc.
    // PERO preserva los guiones tal como están
    val decoded = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
        Html.fromHtml(text, Html.FROM_HTML_MODE_LEGACY).toString()
    } else {
        @Suppress("DEPRECATION")
        Html.fromHtml(text).toString()
    }
    
    // Si el usuario escribió --- en el editor, debe mostrarse como ---
    // Si hay &#8212; en el HTML, se decodificará a — (em dash)
    // Pero si el usuario escribió --- originalmente, debe preservarse
    // No hacer conversiones automáticas de guiones
    return decoded
}

/**
 * Parsea Markdown y aplica estilos visuales usando AnnotatedString
 * Respeta EXACTAMENTE los saltos de línea del editor
 * También detecta teléfonos, emails y URLs para hacerlos clickeables
 */
private fun AnnotatedString.Builder.parseMarkdown(
    text: String, 
    baseStyle: TextStyle, 
    defaultColor: androidx.compose.ui.graphics.Color,
    linkColor: androidx.compose.ui.graphics.Color
) {
    // Dividir el texto en líneas preservando TODOS los saltos de línea
    val lines = text.split("\n")
    var inList = false
    var listType: String? = null
    
    lines.forEachIndexed { index, line ->
        val trimmedLine = line.trim()
        
        // Detectar listas numeradas
        val numberedMatch = Regex("^(\\d+)\\.\\s+(.+)$").find(trimmedLine)
        if (numberedMatch != null) {
            if (!inList || listType != "numbered") {
                if (inList && index > 0) append("\n")
                inList = true
                listType = "numbered"
            } else if (index > 0) {
                append("\n")
            }
            val number = numberedMatch.groupValues[1]
            val content = numberedMatch.groupValues[2]
            withStyle(
                style = SpanStyle(
                    fontSize = baseStyle.fontSize,
                    color = baseStyle.color ?: defaultColor
                )
            ) {
                append("$number. ")
            }
            parseInlineFormatting(content, baseStyle, defaultColor, linkColor)
            // SIEMPRE agregar salto al final de la línea (excepto última)
            if (index < lines.size - 1) {
                append("\n")
            }
            return@forEachIndexed
        }
        
        // Detectar listas con viñetas
        val bulletMatch = Regex("^[-*]\\s+(.+)$").find(trimmedLine)
        if (bulletMatch != null) {
            if (!inList || listType != "bullet") {
                if (inList && index > 0) append("\n")
                inList = true
                listType = "bullet"
            } else if (index > 0) {
                append("\n")
            }
            val content = bulletMatch.groupValues[1]
            withStyle(
                style = SpanStyle(
                    fontSize = baseStyle.fontSize,
                    color = baseStyle.color ?: defaultColor
                )
            ) {
                append("• ")
            }
            parseInlineFormatting(content, baseStyle, defaultColor, linkColor)
            // SIEMPRE agregar salto al final de la línea (excepto última)
            if (index < lines.size - 1) {
                append("\n")
            }
            return@forEachIndexed
        }
        
        // Si estaba en lista y ahora no, cerrar lista
        if (inList && trimmedLine.isNotEmpty()) {
            inList = false
            listType = null
            if (index > 0) append("\n")
        }
        
        // Procesar línea normal - RESPETAR EXACTAMENTE los saltos de línea
        if (trimmedLine.isEmpty()) {
            // Línea vacía = salto de línea (párrafo)
            // SIEMPRE agregar salto si no es la última línea
            if (index < lines.size - 1) {
                append("\n")
            }
        } else {
            // Línea con contenido
            parseInlineFormatting(trimmedLine, baseStyle, defaultColor, linkColor)
            // SIEMPRE agregar salto al final (excepto última línea)
            if (index < lines.size - 1) {
                append("\n")
            }
        }
    }
}

/**
 * Parsea formato inline (negrita, cursiva) y detecta teléfonos, emails y URLs
 * También detecta palabras clave como "requisitos", "beneficios", etc. y les aplica estilo especial
 */
private fun AnnotatedString.Builder.parseInlineFormatting(
    text: String, 
    baseStyle: TextStyle, 
    defaultColor: androidx.compose.ui.graphics.Color,
    linkColor: androidx.compose.ui.graphics.Color
) {
    // Primero detectar y marcar teléfonos, emails y URLs
    var processedText = detectAndMarkLinks(text, linkColor, baseStyle, defaultColor)
    
    // Detectar y marcar palabras clave para estilo especial
    processedText = detectAndMarkKeywords(processedText)
    
    // Luego procesar formato de negrita y cursiva
    var currentIndex = 0
    val textLength = processedText.length
    
    while (currentIndex < textLength) {
        // Buscar formato de negrita **texto**
        val boldMatch = Regex("\\*\\*([^*]+)\\*\\*").find(processedText, currentIndex)
        
        // Buscar formato de cursiva *texto* (que no sea parte de **)
        val italicMatch = Regex("(?<!\\*)\\*([^*]+)\\*(?!\\*)").find(processedText, currentIndex)
        
        // Buscar links marcados (usamos marcadores especiales que agregamos)
        val linkMatch = Regex("\\[LINK_START:(\\w+):([^\\]]+)\\]").find(processedText, currentIndex)
        
        // Buscar palabras clave marcadas (incluye el marcador de fin y el texto después)
        // El formato es: [KEYWORD_START:texto][KEYWORD_END]texto
        // Capturar el texto después de [KEYWORD_END] hasta el siguiente marcador o fin de línea
        val keywordMatch = Regex("\\[KEYWORD_START:([^\\]]+)\\]\\[KEYWORD_END\\]([^\\[]*?)(?=\\[|$)").find(processedText, currentIndex)
        
        // Encontrar el próximo match más cercano
        val matches = listOfNotNull(
            boldMatch?.let { MatchInfo(it.range.first, it, "bold") },
            italicMatch?.let { MatchInfo(it.range.first, it, "italic") },
            linkMatch?.let { MatchInfo(it.range.first, it, "link") },
            keywordMatch?.let { MatchInfo(it.range.first, it, "keyword") }
        )
        
        if (matches.isEmpty()) {
            // No hay más formato, agregar el resto del texto limpiando marcadores
            val remainingText = processedText.substring(currentIndex)
                .replace(Regex("\\[LINK_START:[^\\]]+\\]"), "")
                .replace("[LINK_END]", "")
                .replace(Regex("\\[PHONE_CONTAINER_START\\]"), "")
                .replace(Regex("\\[PHONE_CONTAINER_END\\]"), "")
            append(remainingText)
            break
        }
        
        val nextMatch = matches.minByOrNull { it.position }!!
        
        // Agregar texto antes del match, limpiando marcadores
        if (nextMatch.position > currentIndex) {
            var startIndex = currentIndex
            var endIndex = nextMatch.position
            
            // Si el match es un link de tipo PHONE, verificar si hay un contenedor antes
            if (nextMatch.type == "link") {
                val linkMatch = nextMatch.match
                val linkType = linkMatch.groupValues[1]
                if (linkType == "PHONE") {
                    val beforeMatch = processedText.substring(0, linkMatch.range.first)
                    val containerStartIndex = beforeMatch.lastIndexOf("[PHONE_CONTAINER_START]")
                    if (containerStartIndex != -1) {
                        val afterContainerStart = beforeMatch.substring(containerStartIndex)
                        if (!afterContainerStart.contains("[PHONE_CONTAINER_END]")) {
                            // El contenedor está abierto, ajustar el índice de inicio para excluir el marcador
                            val containerStartPos = containerStartIndex
                            if (containerStartPos >= startIndex && containerStartPos < endIndex) {
                                // El marcador está dentro del rango, ajustar el índice de inicio
                                startIndex = containerStartPos + "[PHONE_CONTAINER_START]".length
                            }
                        }
                    }
                }
            }
            
            var beforeText = processedText.substring(startIndex, endIndex)
            
            // Limpiar todos los marcadores
            beforeText = beforeText
                .replace(Regex("\\[LINK_START:[^\\]]+\\]"), "")
                .replace("[LINK_END]", "")
                .replace(Regex("\\[PHONE_CONTAINER_START\\]"), "")
                .replace(Regex("\\[PHONE_CONTAINER_END\\]"), "")
            append(beforeText)
        }
        
        when (nextMatch.type) {
            "bold" -> {
                val content = nextMatch.match.groupValues[1]
                withStyle(
                    style = SpanStyle(
                        fontWeight = FontWeight.Bold,
                        fontSize = baseStyle.fontSize,
                        color = baseStyle.color ?: defaultColor
                    )
                ) {
                    append(content)
                }
                currentIndex = nextMatch.match.range.last + 1
            }
            "italic" -> {
                val content = nextMatch.match.groupValues[1]
                withStyle(
                    style = SpanStyle(
                        fontStyle = FontStyle.Italic,
                        fontSize = baseStyle.fontSize,
                        color = baseStyle.color ?: defaultColor
                    )
                ) {
                    append(content)
                }
                currentIndex = nextMatch.match.range.last + 1
            }
            "link" -> {
                val linkType = nextMatch.match.groupValues[1] // PHONE, WHATSAPP, EMAIL, URL
                val linkValue = nextMatch.match.groupValues[2] // El valor real (limpio para la acción)
                
                // Verificar si está dentro de un contenedor de teléfono especial
                val beforeMatch = processedText.substring(0, nextMatch.match.range.first)
                val isInPhoneContainer = beforeMatch.contains("[PHONE_CONTAINER_START") && 
                    !beforeMatch.substring(beforeMatch.lastIndexOf("[PHONE_CONTAINER_START")).contains("[PHONE_CONTAINER_END")
                
                // Si estamos dentro de un contenedor de teléfono, necesitamos encontrar dónde empieza realmente el texto del link
                // El formato es: [PHONE_CONTAINER_START][LINK_START:PHONE:...]número[LINK_END][PHONE_CONTAINER_END]
                val actualLinkStart = if (isInPhoneContainer) {
                    // Buscar el inicio del contenedor para saber dónde empezar a buscar el texto del link
                    val containerStart = beforeMatch.lastIndexOf("[PHONE_CONTAINER_START")
                    if (containerStart != -1) {
                        // El texto del link empieza después de [LINK_START:PHONE:...]
                        nextMatch.match.range.last + 1
                    } else {
                        nextMatch.match.range.last + 1
                    }
                } else {
                    nextMatch.match.range.last + 1
                }
                
                // Extraer el texto original del link (después del marcador hasta LINK_END)
                val linkTextStart = actualLinkStart
                val linkTextEnd = processedText.indexOf("[LINK_END]", linkTextStart)
                val linkText = if (linkTextEnd != -1) {
                    val extractedText = processedText.substring(linkTextStart, linkTextEnd)
                    // Limpiar cualquier marcador que pueda haber quedado dentro del texto
                    extractedText.replace(Regex("\\[LINK_START:[^\\]]+\\]"), "")
                        .replace("[LINK_END]", "")
                        .replace(Regex("\\[PHONE_CONTAINER_START\\]"), "")
                        .replace(Regex("\\[PHONE_CONTAINER_END\\]"), "")
                } else {
                    // Si no hay marcador de fin, buscar hasta el siguiente espacio o fin de línea
                    val nextSpace = processedText.indexOf(' ', linkTextStart)
                    val nextNewline = processedText.indexOf('\n', linkTextStart)
                    val nextLinkStart = processedText.indexOf("[LINK_START:", linkTextStart)
                    val end = when {
                        nextLinkStart != -1 && (nextSpace == -1 || nextLinkStart < nextSpace) && (nextNewline == -1 || nextLinkStart < nextNewline) -> nextLinkStart
                        nextSpace != -1 && nextNewline != -1 -> minOf(nextSpace, nextNewline)
                        nextSpace != -1 -> nextSpace
                        nextNewline != -1 -> nextNewline
                        else -> processedText.length
                    }
                    val extractedText = processedText.substring(linkTextStart, end)
                    // Limpiar cualquier marcador que pueda haber quedado dentro del texto
                    extractedText.replace(Regex("\\[LINK_START:[^\\]]+\\]"), "")
                        .replace("[LINK_END]", "")
                        .replace(Regex("\\[PHONE_CONTAINER_START\\]"), "")
                        .replace(Regex("\\[PHONE_CONTAINER_END\\]"), "")
                }
                
                // Si es un teléfono (PHONE) dentro de un contenedor especial, mostrar número primero y luego iconos a la derecha
                if (linkType == "PHONE" && isInPhoneContainer) {
                    // Para teléfonos dentro de contenedores, usar el linkValue (número limpio) o el linkText completo
                    // Asegurarse de que se extraiga el número completo hasta [LINK_END]
                    val fullPhoneText = if (linkTextEnd != -1) {
                        // Extraer el texto completo hasta [LINK_END], sin buscar espacios intermedios
                        val fullText = processedText.substring(linkTextStart, linkTextEnd)
                        fullText.replace(Regex("\\[LINK_START:[^\\]]+\\]"), "")
                            .replace("[LINK_END]", "")
                            .replace(Regex("\\[PHONE_CONTAINER_START\\]"), "")
                            .replace(Regex("\\[PHONE_CONTAINER_END\\]"), "")
                    } else {
                        // Si no hay [LINK_END], usar el linkValue (número limpio)
                        linkValue
                    }
                    
                    // Número de teléfono subrayado (PRIMERO)
                    val numberStart = length
                    withStyle(
                        style = SpanStyle(
                            color = linkColor,
                            textDecoration = TextDecoration.Underline
                        )
                    ) {
                        append(fullPhoneText)
                    }
                    val numberEnd = length
                    
                    // También hacer el número clickeable para llamar (por si el usuario hace clic en el número)
                    addStringAnnotation(
                        tag = "PHONE_CALL",
                        start = numberStart,
                        end = numberEnd,
                        annotation = linkValue
                    )
                    
                    // Margen de 16px después del número (usar espacios para simular el margen)
                    // Aproximadamente 5-6 espacios para simular 16px
                    append("      ") // Margen aproximado de 16px
                    
                    // Icono de teléfono Material Icons - clickeable para llamar
                    pushStringAnnotation(
                        tag = "PHONE_CALL",
                        annotation = linkValue
                    )
                    appendInlineContent("phoneIcon", "phoneIcon")
                    pop()
                    
                    // Espacio más grande entre iconos (aproximadamente 8-10dp)
                    append("   ")
                    
                    // Icono de WhatsApp Material Icons - clickeable para WhatsApp
                    pushStringAnnotation(
                        tag = "PHONE_WHATSAPP",
                        annotation = linkValue
                    )
                    appendInlineContent("whatsappIcon", "whatsappIcon")
                    pop()
                    
                    // Avanzar hasta después del marcador de fin del contenedor especial
                    val containerEnd = processedText.indexOf("[PHONE_CONTAINER_END]", linkTextEnd)
                    currentIndex = if (containerEnd != -1) {
                        containerEnd + "[PHONE_CONTAINER_END]".length
                    } else if (linkTextEnd != -1) {
                        linkTextEnd + "[LINK_END]".length
                    } else {
                        linkTextStart + linkText.length
                    }
                } else {
                    // Para otros tipos de links (EMAIL, URL, WHATSAPP antiguo), mantener el comportamiento anterior
                    val startPos = length
                    
                    // Agregar emoji según el tipo de link
                    val emoji = when (linkType) {
                        "WHATSAPP" -> "📱 "
                        "EMAIL" -> "✉️ "
                        else -> ""
                    }
                    
                    // Agregar emoji si existe
                    if (emoji.isNotEmpty()) {
                        append(emoji)
                    }
                    
                    // Aplicar estilo de subrayado
                    withStyle(
                        style = SpanStyle(
                            color = linkColor,
                            textDecoration = TextDecoration.Underline
                        )
                    ) {
                        append(linkText)
                    }
                    val endPos = length
                    
                    // Agregar annotation para hacerlo clickeable
                    val annotationStart = if (emoji.isNotEmpty()) startPos + emoji.length else startPos
                    addStringAnnotation(
                        tag = linkType,
                        start = annotationStart,
                        end = endPos,
                        annotation = linkValue
                    )
                    
                    // Avanzar hasta después del marcador de fin
                    currentIndex = if (linkTextEnd != -1) {
                        linkTextEnd + "[LINK_END]".length
                    } else {
                        linkTextStart + linkText.length
                    }
                }
            }
            "keyword" -> {
                val keywordText = nextMatch.match.groupValues[1] // El texto de la palabra clave (ej: "Contacto:")
                val keywordDisplayText = nextMatch.match.groupValues[2] // El texto después de [KEYWORD_END] (debe ser igual a keywordText)
                
                val startPos = length
                
                // Aplicar estilo especial: negrita y tamaño más grande
                // Solo mostrar el texto de la palabra clave, sin los marcadores
                // Usar keywordText que viene del marcador (ya incluye los dos puntos si los hay)
                withStyle(
                    style = SpanStyle(
                        fontWeight = FontWeight.Bold,
                        fontSize = baseStyle.fontSize * 1.15f, // 15% más grande
                        color = baseStyle.color ?: defaultColor
                    )
                ) {
                    append(keywordText)
                }
                
                // Avanzar hasta después de TODO el match: [KEYWORD_START:...][KEYWORD_END]texto
                // Esto elimina todos los marcadores y el texto duplicado
                currentIndex = nextMatch.match.range.last + 1
            }
            else -> {
                currentIndex = nextMatch.match.range.last + 1
            }
        }
    }
}

/**
 * Detecta teléfonos, emails y URLs en el texto y los marca para procesamiento posterior
 * Todos los números de teléfono mostrarán ambos iconos (teléfono y WhatsApp) para que el usuario elija
 */
private fun detectAndMarkLinks(
    text: String,
    linkColor: androidx.compose.ui.graphics.Color,
    baseStyle: TextStyle,
    defaultColor: androidx.compose.ui.graphics.Color
): String {
    var result = text
    
    // Detectar todos los números de teléfono (sin diferenciar entre WhatsApp y teléfono normal)
    // Patrón más flexible para números de 9 dígitos (pueden estar juntos o separados de cualquier forma)
    // Ejemplos: 961088507, 961 088 507, 936-351-177, +51 936 351 177, whatsapp: 961088507
    // IMPORTANTE: Envolver cada número en un contenedor especial [PHONE_CONTAINER_START]...[PHONE_CONTAINER_END]
    // para evitar que se mezclen con otros elementos
    val phonePattern = Regex("""(?<![\w@/])(?<!\[LINK_START)(?<!\[PHONE_CONTAINER_START)(\+?\d{1,3}[\s-]?)?(\d{1,3}[\s-]?\d{1,3}[\s-]?\d{1,3}[\s-]?\d{1,3}[\s-]?\d{0,3})(?![\w@/])(?!\[LINK_END)(?!\[PHONE_CONTAINER_END)""")
    result = phonePattern.replace(result) { match ->
        // Verificar que no esté dentro de un contenedor de teléfono ya marcado
        val beforeText = result.substring(0, match.range.first)
        
        if (beforeText.contains("[PHONE_CONTAINER_START") && !beforeText.substring(beforeText.lastIndexOf("[PHONE_CONTAINER_START")).contains("[PHONE_CONTAINER_END")) {
            return@replace match.value
        }
        
        // Verificar que no esté dentro de un link ya marcado
        if (beforeText.contains("[LINK_START") && !beforeText.substring(beforeText.lastIndexOf("[LINK_START")).contains("[LINK_END")) {
            return@replace match.value
        }
        
        val originalPhone = match.value
        val cleanPhone = originalPhone.replace(Regex("""[\s-]"""), "")
        // Solo procesar si tiene entre 9 y 15 dígitos (número válido)
        if (cleanPhone.length >= 9 && cleanPhone.length <= 15) {
            // Envolver en un contenedor especial para evitar mezclas
            "[PHONE_CONTAINER_START][LINK_START:PHONE:$cleanPhone]$originalPhone[LINK_END][PHONE_CONTAINER_END]"
        } else {
            originalPhone
        }
    }
    
    // Patrón para emails (evitar detectar dentro de URLs o dentro de emails ya marcados)
    // Usar un patrón más estricto que capture emails completos
    val emailPattern = Regex("""(?<![/\w])(?<!\[LINK_START:EMAIL:)([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,})(?![/\w])(?!\[LINK_END)""")
    result = emailPattern.replace(result) { match ->
        val beforeText = result.substring(0, match.range.first)
        // Verificar que no esté dentro de un email ya marcado
        if (beforeText.contains("[LINK_START:EMAIL:") && !beforeText.substring(beforeText.lastIndexOf("[LINK_START:EMAIL:")).contains("[LINK_END]")) {
            return@replace match.value
        }
        // Verificar que no esté dentro de un teléfono marcado
        if (beforeText.contains("[PHONE_CONTAINER_START") && !beforeText.substring(beforeText.lastIndexOf("[PHONE_CONTAINER_START")).contains("[PHONE_CONTAINER_END")) {
            return@replace match.value
        }
        "[LINK_START:EMAIL:${match.value}]${match.value}[LINK_END]"
    }
    
    // Patrón para URLs (http, https, www, o dominios comunes)
    // Debe ir después de emails para evitar conflictos
    // IMPORTANTE: No detectar URLs que contengan @ (son emails) o que estén dentro de emails marcados
    val urlPattern = Regex("""(https?://[^\s<>"{}|\\^`\[\]@]+|www\.[^\s<>"{}|\\^`\[\]@]+|[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}[^\s<>"{}|\\^`\[\]@]*)""")
    result = urlPattern.replace(result) { match ->
        val url = match.value
        val beforeText = result.substring(0, match.range.first)
        val afterText = if (match.range.last + 1 < result.length) {
            result.substring(match.range.last + 1, minOf(match.range.last + 50, result.length))
        } else {
            ""
        }
        
        // NO procesar si contiene @ (es un email)
        if (url.contains("@")) {
            return@replace url
        }
        
        // NO procesar si después del match hay un @ cercano (es parte de un email)
        if (afterText.contains("@") && afterText.indexOf("@") < 30) {
            return@replace url
        }
        
        // Verificar que no esté dentro de un email ya marcado
        if (beforeText.contains("[LINK_START:EMAIL:") && !beforeText.substring(beforeText.lastIndexOf("[LINK_START:EMAIL:")).contains("[LINK_END]")) {
            return@replace url
        }
        
        // Verificar que no esté dentro de un teléfono marcado
        if (beforeText.contains("[PHONE_CONTAINER_START") && !beforeText.substring(beforeText.lastIndexOf("[PHONE_CONTAINER_START")).contains("[PHONE_CONTAINER_END")) {
            return@replace url
        }
        
        // Verificar que no esté dentro de otro link ya marcado
        if (beforeText.contains("[LINK_START:") && !beforeText.substring(beforeText.lastIndexOf("[LINK_START:")).contains("[LINK_END]")) {
            return@replace url
        }
        
        "[LINK_START:URL:$url]$url[LINK_END]"
    }
    
    // Limpiar marcadores de fin (no los necesitamos)
    result = result.replace("[LINK_END]", "")
    
    return result
}

/**
 * Detecta palabras clave como "requisitos", "beneficios", "Informes y consultas", etc.
 * y las marca para aplicar estilo especial (negrita y tamaño más grande)
 */
private fun detectAndMarkKeywords(text: String): String {
    var result = text
    
    // Lista de palabras clave más comunes a detectar (case insensitive)
    // Solo las más esenciales para mantener el formato limpio
    val keywords = listOf(
        "requisitos",
        "beneficios",
        "funciones",
        "responsabilidades",
        "contacto",
        "informes",
        "consultas",
        "importante",
        "nota"
    )
    
    // Rastrea rangos ya marcados para evitar solapamientos
    val markedRanges = mutableListOf<IntRange>()
    
    // Detectar cada palabra clave (case insensitive)
    // IMPORTANTE: Procesar de más específicas a menos específicas
    keywords.forEach { keyword ->
        // Escapar caracteres especiales del regex
        val escapedKeyword = Regex.escape(keyword)
        
        // Patrón para detectar la palabra clave seguida de dos puntos opcionales
        // Puede estar al inicio de línea, después de espacio, o después de emoji
        val pattern = Regex("""(?i)(^|\n|\r|[\s])(\s*📞\s*)?($escapedKeyword)\s*:?""", RegexOption.MULTILINE)
        
        // Encontrar todas las coincidencias primero
        val matches = pattern.findAll(result).toList()
        
        // Procesar en orden inverso para mantener los índices correctos
        matches.reversed().forEach { match ->
            val matchRange = match.range
            
            // Verificar que este rango no se solape con ningún rango ya marcado
            val overlaps = markedRanges.any { markedRange ->
                matchRange.first < markedRange.last && matchRange.last > markedRange.first
            }
            
            if (!overlaps) {
                val prefix = match.groupValues[1] // espacio, salto de línea, etc.
                val emoji = match.groupValues[2] // emoji si existe
                val keywordText = match.groupValues[3] // la palabra clave encontrada
                
                // Construir el texto completo con emoji si existe y dos puntos
                val fullKeyword = if (emoji.isNotEmpty()) {
                    "$emoji$keywordText:"
                } else {
                    "$keywordText:"
                }
                
                // Verificar que no esté dentro de una palabra clave ya marcada
                val matchStart = match.range.first
                val beforeMatch = result.substring(0, matchStart)
                if (beforeMatch.contains("[KEYWORD_START") && !beforeMatch.substring(beforeMatch.lastIndexOf("[KEYWORD_START")).contains("[KEYWORD_END")) {
                    return@forEach
                }
                
                // Marcar la palabra clave completa
                val replacement = "$prefix[KEYWORD_START:$fullKeyword][KEYWORD_END]$fullKeyword"
                result = result.replaceRange(matchRange, replacement)
                
                // Calcular el nuevo rango después del reemplazo
                val newRange = IntRange(matchStart, matchStart + replacement.length - 1)
                markedRanges.add(newRange)
            }
        }
    }
    
    return result
}

/**
 * Clase auxiliar para manejar matches
 */
private data class MatchInfo(
    val position: Int,
    val match: MatchResult,
    val type: String
)

