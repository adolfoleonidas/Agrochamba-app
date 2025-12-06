package agrochamba.com.utils

import android.os.Build
import android.text.Html

/**
 * Archivo de utilidades para funciones relacionadas con Strings.
 */

/**
 * Convierte HTML a texto preservando formato básico (saltos de línea, párrafos).
 * Útil para edición de contenido donde se necesita mantener la estructura.
 * Elimina elementos problemáticos pero preserva saltos de línea y espacios básicos.
 */
fun String.htmlToEditableText(): String {
    var cleaned = this
    
    // Eliminar elementos problemáticos que pueden dejar iconos o espacios
    val problematicTags = listOf(
        "object", "embed", "iframe", "applet", "param",
        "script", "style", "noscript"
    )
    
    problematicTags.forEach { tag ->
        cleaned = cleaned.replace(
            Regex("<$tag[^>]*>.*?</$tag>", setOf(RegexOption.DOT_MATCHES_ALL, RegexOption.IGNORE_CASE)),
            ""
        )
        cleaned = cleaned.replace(
            Regex("<$tag[^>]*/?>", RegexOption.IGNORE_CASE),
            ""
        )
    }
    
    // Eliminar imágenes
    cleaned = cleaned.replace(
        Regex("<img[^>]*>", RegexOption.IGNORE_CASE),
        ""
    )
    
    // Convertir elementos de bloque a saltos de línea antes de convertir HTML
    // Preservar párrafos, divs, listas, etc.
    cleaned = cleaned.replace(Regex("<(p|div|br|li)[^>]*>", RegexOption.IGNORE_CASE), "\n")
    cleaned = cleaned.replace(Regex("</(p|div|li)>", RegexOption.IGNORE_CASE), "\n")
    cleaned = cleaned.replace(Regex("<br[^>]*/?>", RegexOption.IGNORE_CASE), "\n")
    
    // Convertir listas
    cleaned = cleaned.replace(Regex("<(ul|ol)[^>]*>", RegexOption.IGNORE_CASE), "\n")
    cleaned = cleaned.replace(Regex("</(ul|ol)>", RegexOption.IGNORE_CASE), "\n")
    
    // Convertir HTML a texto
    val result = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
        Html.fromHtml(cleaned, Html.FROM_HTML_MODE_LEGACY).toString()
    } else {
        @Suppress("DEPRECATION")
        Html.fromHtml(cleaned).toString()
    }
    
    // Limpiar pero preservar estructura básica
    return result
        .replace(Regex("\\r\\n"), "\n") // Normalizar saltos de línea
        .replace(Regex("\\r"), "\n")
        .replace(Regex("\\n{3,}"), "\n\n") // Limitar saltos múltiples a máximo 2
        .replace(Regex("[ \\t]+"), " ") // Normalizar espacios múltiples en la misma línea
        .replace(Regex(" \\n"), "\n") // Eliminar espacios antes de saltos de línea
        .replace(Regex("\\n "), "\n") // Eliminar espacios después de saltos de línea
        .trim() // Eliminar espacios al inicio y final
}

/**
 * Convierte Markdown básico a HTML.
 * Soporta: **negrita**, *cursiva*, listas con viñetas (-) y numeradas (1.)
 */
fun String.markdownToHtml(): String {
    if (this.isBlank()) return ""
    
    var html = this
    
    // Convertir listas numeradas
    html = Regex("^(\\d+)\\.\\s+(.+)$", RegexOption.MULTILINE).replace(html) { matchResult ->
        val content = matchResult.groupValues[2]
        "<li>${processInlineMarkdown(content)}</li>"
    }
    html = wrapListItems(html, "<ol>", "</ol>")
    
    // Convertir listas con viñetas
    html = Regex("^[-*]\\s+(.+)$", RegexOption.MULTILINE).replace(html) { matchResult ->
        val content = matchResult.groupValues[1]
        "<li>${processInlineMarkdown(content)}</li>"
    }
    html = wrapListItems(html, "<ul>", "</ul>")
    
    // Dividir en párrafos (líneas separadas por saltos de línea dobles)
    val paragraphs = html.split(Regex("\\n\\s*\\n"))
    
    return paragraphs.joinToString("") { paragraph ->
        val trimmed = paragraph.trim()
        if (trimmed.isEmpty()) {
            ""
        } else if (trimmed.startsWith("<ul>") || trimmed.startsWith("<ol>")) {
            // Ya es una lista, mantenerla tal cual
            trimmed
        } else {
            // Convertir saltos de línea simples a <br> y envolver en <p>
            val withBreaks = trimmed.replace("\n", "<br>")
            "<p>${processInlineMarkdown(withBreaks)}</p>"
        }
    }
}

/**
 * Procesa formato inline de Markdown (**negrita**, *cursiva*)
 */
private fun processInlineMarkdown(text: String): String {
    var result = text
    
    // Convertir **negrita**
    result = Regex("\\*\\*([^*]+)\\*\\*").replace(result) { "<strong>${it.groupValues[1]}</strong>" }
    
    // Convertir *cursiva* (solo si no está dentro de **)
    result = Regex("(?<!\\*)\\*([^*]+)\\*(?!\\*)").replace(result) { "<em>${it.groupValues[1]}</em>" }
    
    return result
}

/**
 * Envuelve elementos <li> consecutivos en tags de lista
 */
private fun wrapListItems(text: String, openTag: String, closeTag: String): String {
    return Regex("(<li>.*?</li>\\s*)+", RegexOption.DOT_MATCHES_ALL).replace(text) { matchResult ->
        val content = matchResult.value.trim()
        if (content.isNotEmpty()) {
            "$openTag\n$content\n$closeTag"
        } else {
            content
        }
    }
}

/**
 * Convierte HTML a Markdown básico para edición.
 * Convierte <strong> a **, <em> a *, <li> a listas, etc.
 */
fun String.htmlToMarkdown(): String {
    if (this.isBlank()) return ""
    
    var markdown = this
    
    // Convertir <strong> a **
    markdown = Regex("<strong>(.*?)</strong>", RegexOption.DOT_MATCHES_ALL).replace(markdown) { 
        "**${it.groupValues[1]}**"
    }
    
    // Convertir <b> a **
    markdown = Regex("<b>(.*?)</b>", RegexOption.DOT_MATCHES_ALL).replace(markdown) { 
        "**${it.groupValues[1]}**"
    }
    
    // Convertir <em> a *
    markdown = Regex("<em>(.*?)</em>", RegexOption.DOT_MATCHES_ALL).replace(markdown) { 
        "*${it.groupValues[1]}*"
    }
    
    // Convertir <i> a *
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
    
    // Convertir <p> a saltos de línea dobles
    markdown = Regex("<p[^>]*>(.*?)</p>", RegexOption.DOT_MATCHES_ALL).replace(markdown) { 
        "${it.groupValues[1].trim()}\n\n"
    }
    
    // Convertir <br> y <br/> a saltos de línea
    markdown = Regex("<br\\s*/?>", RegexOption.IGNORE_CASE).replace(markdown, "\n")
    
    // Convertir <div> a saltos de línea
    markdown = Regex("<div[^>]*>(.*?)</div>", RegexOption.DOT_MATCHES_ALL).replace(markdown) { 
        "${it.groupValues[1].trim()}\n\n"
    }
    
    // Limpiar HTML restante
    markdown = Regex("<[^>]+>").replace(markdown, "")
    
    // Limpiar espacios y saltos de línea excesivos
    markdown = markdown
        .replace(Regex("\\n{3,}"), "\n\n")
        .replace(Regex(" +"), " ")
        .trim()
    
    return markdown
}

/**
 * Convierte texto plano con saltos de línea a HTML básico para preservar formato.
 * Útil antes de enviar contenido al servidor para que preserve saltos de línea y párrafos.
 * Si el texto ya contiene HTML, lo preserva tal cual.
 * Si contiene Markdown, lo convierte a HTML.
 */
fun String.textToHtml(): String {
    if (this.isBlank()) return ""
    
    // Si ya contiene HTML (detecta tags HTML básicos), preservarlo tal cual
    val hasHtml = Regex("<[a-z][\\s\\S]*>", RegexOption.IGNORE_CASE).containsMatchIn(this)
    if (hasHtml) {
        return this
    }
    
    // Si contiene Markdown (**, *, listas), convertirlo
    val hasMarkdown = Regex("\\*\\*|^[-*]\\s|^\\d+\\.\\s", RegexOption.MULTILINE).containsMatchIn(this)
    if (hasMarkdown) {
        return this.markdownToHtml()
    }
    
    // Dividir por saltos de línea dobles (párrafos) y simples (saltos de línea)
    val paragraphs = this.split(Regex("\\n\\s*\\n"))
    
    return paragraphs.joinToString("") { paragraph ->
        val lines = paragraph.trim().split("\n")
        if (lines.size > 1) {
            // Múltiples líneas = párrafo con <br>
            "<p>${lines.joinToString("<br>")}</p>"
        } else if (paragraph.trim().isNotEmpty()) {
            // Una línea = párrafo simple
            "<p>${paragraph.trim()}</p>"
        } else {
            ""
        }
    }
}

/**
 * Convierte una cadena de texto que contiene HTML a un String plano.
 * Esta es la única definición que debe existir en el proyecto.
 * Elimina elementos problemáticos como object, embed, iframe, etc. que pueden dejar iconos o espacios.
 * Para edición, usa htmlToEditableText() en su lugar.
 */
fun String.htmlToString(): String {
    var cleaned = this
    
    // Eliminar elementos problemáticos que pueden dejar iconos o espacios
    // Estos elementos se eliminan completamente con su contenido
    val problematicTags = listOf(
        "object", "embed", "iframe", "applet", "param",
        "script", "style", "noscript"
    )
    
    problematicTags.forEach { tag ->
        // Eliminar tags de apertura y cierre con todo su contenido
        cleaned = cleaned.replace(
            // Usar múltiples opciones con un Set: DOT_MATCHES_ALL para que "." abarque saltos de línea, e IGNORE_CASE
            Regex("<$tag[^>]*>.*?</$tag>", setOf(RegexOption.DOT_MATCHES_ALL, RegexOption.IGNORE_CASE)),
            ""
        )
        // Eliminar tags auto-cerrados
        cleaned = cleaned.replace(
            Regex("<$tag[^>]*/?>", RegexOption.IGNORE_CASE),
            ""
        )
    }
    
    // Eliminar imágenes que pueden dejar espacios
    cleaned = cleaned.replace(
        Regex("<img[^>]*>", RegexOption.IGNORE_CASE),
        ""
    )
    
    // Convertir HTML a texto
    val result = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
        Html.fromHtml(cleaned, Html.FROM_HTML_MODE_LEGACY).toString()
    } else {
        @Suppress("DEPRECATION")
        Html.fromHtml(cleaned).toString()
    }
    
    // Limpiar espacios en blanco múltiples y saltos de línea excesivos
    return result
        .replace(Regex("\\s+"), " ") // Reemplazar múltiples espacios con uno solo
        .replace(Regex("\\n\\s*\\n+"), "\n\n") // Limitar saltos de línea múltiples a máximo 2
        .trim() // Eliminar espacios al inicio y final
}
