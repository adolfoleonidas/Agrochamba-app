package agrochamba.com.utils

import android.os.Build
import android.text.Html

/**
 * Archivo de utilidades para funciones relacionadas con Strings.
 */

/**
 * Convierte una cadena de texto que contiene HTML a un String plano.
 * Esta es la única definición que debe existir en el proyecto.
 */
fun String.htmlToString(): String {
    return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
        Html.fromHtml(this, Html.FROM_HTML_MODE_LEGACY).toString()
    } else {
        @Suppress("DEPRECATION")
        Html.fromHtml(this).toString()
    }
}
