package agrochamba.com.ui.common

import androidx.compose.foundation.layout.*
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.*
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.sp

/**
 * Componente que renderiza texto con formato HTML/Markdown de forma visual
 */
@Composable
fun FormattedText(
    text: String,
    modifier: Modifier = Modifier,
    style: TextStyle = MaterialTheme.typography.bodyLarge
) {
    val colorScheme = MaterialTheme.colorScheme
    val defaultColor = style.color ?: colorScheme.onSurface
    
    val annotatedString = remember(text, defaultColor) {
        buildAnnotatedString {
            parseFormattedText(text, style, defaultColor)
        }
    }
    
    Text(
        text = annotatedString,
        style = style.copy(lineHeight = style.lineHeight ?: 24.sp),
        modifier = modifier
    )
}

/**
 * Parsea texto con formato HTML/Markdown y aplica estilos visuales
 */
private fun AnnotatedString.Builder.parseFormattedText(text: String, baseStyle: TextStyle, defaultColor: androidx.compose.ui.graphics.Color) {
    // Primero convertir HTML a Markdown si es necesario
    val markdownText = if (text.contains("<")) {
        text.htmlToMarkdownForDisplay()
    } else {
        text
    }
    
    // Parsear Markdown y aplicar estilos
    parseMarkdown(markdownText, baseStyle, defaultColor)
}

/**
 * Convierte HTML básico a Markdown para visualización
 */
private fun String.htmlToMarkdownForDisplay(): String {
    var markdown = this
    
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
 * Parsea Markdown y aplica estilos visuales usando AnnotatedString
 * Respeta EXACTAMENTE los saltos de línea del editor
 */
private fun AnnotatedString.Builder.parseMarkdown(text: String, baseStyle: TextStyle, defaultColor: androidx.compose.ui.graphics.Color) {
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
            parseInlineFormatting(content, baseStyle, defaultColor)
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
            parseInlineFormatting(content, baseStyle, defaultColor)
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
            parseInlineFormatting(trimmedLine, baseStyle, defaultColor)
            // SIEMPRE agregar salto al final (excepto última línea)
            if (index < lines.size - 1) {
                append("\n")
            }
        }
    }
}

/**
 * Parsea formato inline (negrita, cursiva) en una línea
 */
private fun AnnotatedString.Builder.parseInlineFormatting(text: String, baseStyle: TextStyle, defaultColor: androidx.compose.ui.graphics.Color) {
    var currentIndex = 0
    val textLength = text.length
    
    while (currentIndex < textLength) {
        // Buscar formato de negrita **texto**
        val boldMatch = Regex("\\*\\*([^*]+)\\*\\*").find(text, currentIndex)
        
        // Buscar formato de cursiva *texto* (que no sea parte de **)
        val italicMatch = Regex("(?<!\\*)\\*([^*]+)\\*(?!\\*)").find(text, currentIndex)
        
        // Encontrar el próximo match más cercano
        val matches = listOfNotNull(
            boldMatch?.let { MatchInfo(it.range.first, it, "bold") },
            italicMatch?.let { MatchInfo(it.range.first, it, "italic") }
        )
        
        if (matches.isEmpty()) {
            // No hay más formato, agregar el resto del texto
            append(text.substring(currentIndex))
            break
        }
        
        val nextMatch = matches.minByOrNull { it.position }!!
        
        // Agregar texto antes del match
        if (nextMatch.position > currentIndex) {
            append(text.substring(currentIndex, nextMatch.position))
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
            else -> {
                currentIndex = nextMatch.match.range.last + 1
            }
        }
    }
}

/**
 * Clase auxiliar para manejar matches
 */
private data class MatchInfo(
    val position: Int,
    val match: MatchResult,
    val type: String
)

