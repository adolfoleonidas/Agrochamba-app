package agrochamba.com.utils

import android.os.Build
import android.text.Html
import java.util.concurrent.ConcurrentHashMap

/**
 * Archivo de utilidades para funciones relacionadas con Strings.
 * OPTIMIZADO v3: CERO REGEX en la ruta crítica de htmlToString.
 */

// =============================================================================
// CACHE PARA RESULTADOS
// =============================================================================

private object HtmlCache {
    private const val MAX_CACHE_SIZE = 200
    val cache = ConcurrentHashMap<Int, String>()

    fun getOrCompute(input: String, compute: () -> String): String {
        if (input.length > 2000) return compute()
        val hash = input.hashCode()
        return cache.getOrPut(hash) {
            if (cache.size > MAX_CACHE_SIZE) cache.clear()
            compute()
        }
    }
}

// Regex pre-compilada UNA SOLA VEZ para limpieza de espacios
private val WHITESPACE_REGEX = Regex("\\s+")

/**
 * Convierte HTML a texto plano de forma RÁPIDA.
 * CERO REGEX en la ruta de ejecución - solo usa indexOf/substring.
 */
fun String.htmlToString(): String {
    if (this.isBlank()) return ""
    if (this.length < 10 && !this.contains('<')) return this.trim()
    return HtmlCache.getOrCompute(this) { htmlToStringUltraFast() }
}

/**
 * Versión ultra-rápida - NO USA REGEX.
 * Solo métodos nativos de String (indexOf, substring).
 */
fun String.htmlToStringUltraFast(): String {
    if (this.isBlank()) return ""

    // Limitar tamaño
    val input = if (this.length > 15000) this.take(15000) else this

    // Sin HTML? Retornar limpio
    if (!input.contains('<')) {
        return cleanWhitespace(input)
    }

    // Limpiar tags problemáticos SIN REGEX
    var cleaned = input
    cleaned = removeTagContent(cleaned, "script")
    cleaned = removeTagContent(cleaned, "style")
    cleaned = removeTagContent(cleaned, "noscript")
    cleaned = removeTagContent(cleaned, "iframe")
    cleaned = removeTagContent(cleaned, "object")
    cleaned = removeTagContent(cleaned, "embed")
    cleaned = removeSelfClosingTag(cleaned, "img")

    // Convertir HTML a texto (Android's Html class es muy rápido)
    val result = try {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
            Html.fromHtml(cleaned, Html.FROM_HTML_MODE_LEGACY).toString()
        } else {
            @Suppress("DEPRECATION")
            Html.fromHtml(cleaned).toString()
        }
    } catch (e: Exception) {
        // Si falla Html.fromHtml, eliminar todos los tags manualmente
        removeAllTags(cleaned)
    }

    return cleanWhitespace(result)
}

/**
 * Elimina un tag y su contenido usando SOLO indexOf/substring.
 * Ejemplo: removeTagContent("<script>alert(1)</script>", "script") → ""
 */
private fun removeTagContent(html: String, tag: String): String {
    if (!html.contains("<$tag", ignoreCase = true)) return html

    val sb = StringBuilder(html.length)
    var i = 0
    val len = html.length
    val openTag = "<$tag"
    val closeTag = "</$tag>"
    val openLen = openTag.length
    val closeLen = closeTag.length

    while (i < len) {
        // Buscar inicio del tag
        val start = html.indexOf(openTag, i, ignoreCase = true)
        if (start == -1) {
            sb.append(html, i, len)
            break
        }

        // Agregar contenido antes del tag
        sb.append(html, i, start)

        // Buscar fin del tag
        val end = html.indexOf(closeTag, start, ignoreCase = true)
        if (end != -1) {
            i = end + closeLen
        } else {
            // No hay tag de cierre, buscar solo el fin del tag de apertura
            val tagEnd = html.indexOf('>', start)
            i = if (tagEnd != -1) tagEnd + 1 else start + openLen
        }
    }

    return sb.toString()
}

/**
 * Elimina tags auto-cerrados como <img>, <br>, etc.
 */
private fun removeSelfClosingTag(html: String, tag: String): String {
    if (!html.contains("<$tag", ignoreCase = true)) return html

    val sb = StringBuilder(html.length)
    var i = 0
    val len = html.length
    val openTag = "<$tag"
    val openLen = openTag.length

    while (i < len) {
        val start = html.indexOf(openTag, i, ignoreCase = true)
        if (start == -1) {
            sb.append(html, i, len)
            break
        }

        sb.append(html, i, start)

        val tagEnd = html.indexOf('>', start)
        i = if (tagEnd != -1) tagEnd + 1 else start + openLen
    }

    return sb.toString()
}

/**
 * Elimina TODOS los tags HTML (fallback si Html.fromHtml falla)
 */
private fun removeAllTags(html: String): String {
    val sb = StringBuilder(html.length)
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

/**
 * Limpia espacios en blanco múltiples SIN REGEX.
 */
private fun cleanWhitespace(text: String): String {
    val sb = StringBuilder(text.length)
    var lastWasSpace = false

    for (c in text) {
        if (c.isWhitespace()) {
            if (!lastWasSpace) {
                sb.append(' ')
                lastWasSpace = true
            }
        } else {
            sb.append(c)
            lastWasSpace = false
        }
    }

    return sb.toString().trim()
}

// =============================================================================
// FUNCIONES MENOS CRÍTICAS (pueden usar regex simples)
// =============================================================================

/**
 * Convierte HTML a texto preservando saltos de línea.
 * Usa regex simples ya que no se llama con tanta frecuencia.
 */
fun String.htmlToEditableText(): String {
    if (this.isBlank()) return ""

    val input = if (this.length > 20000) this.take(20000) else this

    var cleaned = input
    cleaned = removeTagContent(cleaned, "script")
    cleaned = removeTagContent(cleaned, "style")
    cleaned = removeTagContent(cleaned, "iframe")
    cleaned = removeSelfClosingTag(cleaned, "img")

    // Convertir tags de bloque a \n (regex simple, sin DOT_MATCHES_ALL)
    cleaned = cleaned
        .replace("<br>", "\n", ignoreCase = true)
        .replace("<br/>", "\n", ignoreCase = true)
        .replace("<br />", "\n", ignoreCase = true)
        .replace("</p>", "\n", ignoreCase = true)
        .replace("</div>", "\n", ignoreCase = true)
        .replace("</li>", "\n", ignoreCase = true)

    val result = try {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
            Html.fromHtml(cleaned, Html.FROM_HTML_MODE_LEGACY).toString()
        } else {
            @Suppress("DEPRECATION")
            Html.fromHtml(cleaned).toString()
        }
    } catch (e: Exception) {
        removeAllTags(cleaned)
    }

    return result
        .replace("\r\n", "\n")
        .replace("\r", "\n")
        .trim()
}

fun String.htmlToStringFast(): String = htmlToStringUltraFast()

/**
 * Convierte Markdown a HTML (usado raramente, puede usar regex)
 */
fun String.markdownToHtml(): String {
    if (this.isBlank()) return ""
    if (this.length > 10000) return this.take(10000)

    var html = this

    // Listas numeradas
    html = Regex("^(\\d+)\\.\\s+(.+)$", RegexOption.MULTILINE).replace(html) {
        "<li>${it.groupValues[2]}</li>"
    }

    // Listas con viñetas
    html = Regex("^[-*]\\s+(.+)$", RegexOption.MULTILINE).replace(html) {
        "<li>${it.groupValues[1]}</li>"
    }

    // Negrita y cursiva
    html = html.replace(Regex("\\*\\*([^*]+)\\*\\*")) { "<strong>${it.groupValues[1]}</strong>" }
    html = html.replace(Regex("\\*([^*]+)\\*")) { "<em>${it.groupValues[1]}</em>" }

    // Párrafos
    val paragraphs = html.split("\n\n")
    return paragraphs.joinToString("") { p ->
        val t = p.trim()
        when {
            t.isEmpty() -> ""
            t.startsWith("<li>") -> "<ul>$t</ul>"
            else -> "<p>${t.replace("\n", "<br>")}</p>"
        }
    }
}

/**
 * Convierte HTML a Markdown
 */
fun String.htmlToMarkdown(): String {
    if (this.isBlank()) return ""
    if (this.length > 20000) return this.take(20000)

    var md = this
    md = md.replace(Regex("<strong>(.*?)</strong>")) { "**${it.groupValues[1]}**" }
    md = md.replace(Regex("<b>(.*?)</b>")) { "**${it.groupValues[1]}**" }
    md = md.replace(Regex("<em>(.*?)</em>")) { "*${it.groupValues[1]}*" }
    md = md.replace(Regex("<i>(.*?)</i>")) { "*${it.groupValues[1]}*" }
    md = md.replace(Regex("<li>(.*?)</li>")) { "- ${it.groupValues[1].trim()}\n" }
    md = md.replace("<br>", "\n", ignoreCase = true)
    md = md.replace("<br/>", "\n", ignoreCase = true)
    md = md.replace(Regex("<p[^>]*>(.*?)</p>")) { "${it.groupValues[1].trim()}\n\n" }
    md = md.replace(Regex("<[^>]+>"), "")
    md = md.replace("&#8212;", "---").replace("&#8211;", "--")
    md = md.replace("—", "---").replace("–", "--")
    return md.trim()
}

/**
 * Convierte texto plano a HTML
 */
fun String.textToHtml(): String {
    if (this.isBlank()) return ""
    if (this.contains("<p>") || this.contains("<br")) return this
    if (this.contains("**")) return this.markdownToHtml()

    val paragraphs = this.split("\n\n")
    return paragraphs.joinToString("") { p ->
        if (p.trim().isNotEmpty()) "<p>${p.trim().replace("\n", "<br>")}</p>" else ""
    }
}
