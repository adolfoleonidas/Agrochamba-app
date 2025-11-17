package agrochamba.com.ui.jobs

import android.text.Html

// Función única y centralizada para limpiar el HTML del texto
fun String.htmlToString(): String {
    return Html.fromHtml(this, Html.FROM_HTML_MODE_LEGACY).toString()
}
