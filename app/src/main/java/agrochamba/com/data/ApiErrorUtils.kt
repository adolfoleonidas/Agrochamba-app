package agrochamba.com.data

import org.json.JSONObject
import retrofit2.HttpException
import java.io.EOFException
import java.net.ConnectException
import java.net.ProtocolException
import java.net.SocketTimeoutException
import java.net.UnknownHostException
import javax.net.ssl.SSLException
import javax.net.ssl.SSLHandshakeException
import javax.net.ssl.SSLPeerUnverifiedException

/**
 * Utilidad para transformar excepciones de red en mensajes legibles para el usuario
 * mostrando el código HTTP, el código/mensaje específico de WordPress si existe,
 * y un texto por defecto cuando no se puede parsear.
 */
object ApiErrorUtils {
    /**
     * Devuelve un mensaje detallado y legible del error ocurrido en una llamada HTTP.
     * Incluye:
     * - Código HTTP (si aplica)
     * - Código WP (campo "code") cuando el cuerpo es JSON de WordPress
     * - Mensaje de error provisto por la API (campo "message")
     * - Mensajes específicos para timeouts, DNS, SSL, conexión, respuesta inválida
     */
    fun getReadableApiError(e: Throwable, fallback: String? = null): String {
        return when (e) {
            is HttpException -> formatHttpException(e, fallback)
            is SocketTimeoutException -> "Tiempo de espera agotado al comunicarse con el servidor. Intenta nuevamente."
            is UnknownHostException -> "Sin conexión o el dominio no se puede resolver. Verifica tu internet."
            is ConnectException -> "No se pudo conectar al servidor. Revisa tu conexión e inténtalo otra vez."
            is SSLHandshakeException, is SSLPeerUnverifiedException, is SSLException -> "Problema de seguridad (SSL/TLS) al conectar con el servidor."
            is EOFException, is ProtocolException -> "Respuesta inválida del servidor. Intenta de nuevo."
            else -> e.message ?: (fallback ?: "Ocurrió un error inesperado.")
        }
    }

    private fun formatHttpException(e: HttpException, fallback: String?): String {
        val status = e.code()
        val bodyStr = try { e.response()?.errorBody()?.string() } catch (_: Exception) { null }
        var wpCode: String? = null
        var wpMessage: String? = null
        if (!bodyStr.isNullOrBlank()) {
            try {
                val json = JSONObject(bodyStr)
                wpCode = json.optString("code", null)
                wpMessage = json.optString("message", null)
            } catch (_: Exception) { /* cuerpo no es JSON */ }
        }

        // Para errores 429 (rate limit), mostrar mensaje formal y amigable
        if (status == 429) {
            return "Has realizado demasiadas solicitudes en poco tiempo. Por favor, espera unos minutos antes de intentar nuevamente."
        }

        val hint = when (status) {
            400 -> "(Solicitud inválida: revisa los campos enviados)"
            401 -> "(No autorizado: token inválido o expirado)"
            403 -> "(Prohibido: sin permisos para esta acción)"
            404 -> "(Recurso no encontrado)"
            413 -> "(Archivo demasiado grande: supera el límite del servidor)"
            in 500..599 -> "(Error del servidor: intenta nuevamente en unos segundos)"
            else -> null
        }

        return buildString {
            // Si hay mensaje de WordPress, usarlo directamente (ya es amigable)
            if (!wpMessage.isNullOrBlank()) {
                append(wpMessage)
            } else {
                append("Error HTTP $status")
                if (!wpCode.isNullOrBlank()) append(" · $wpCode")
                hint?.let { append(" $it") }
                if (fallback != null) {
                    if (length > 0) append(" — ")
                    append(fallback)
                }
            }
        }
    }

    /**
     * Indica si el error es transitorio y conviene reintentar la operación.
     */
    fun isTransientError(t: Throwable): Boolean {
        return when (t) {
            is SocketTimeoutException, is UnknownHostException, is ConnectException,
            is EOFException, is ProtocolException, is SSLException -> true
            is HttpException -> isRetriableHttpCode(t.code())
            else -> false
        }
    }

    fun isRetriableHttpCode(code: Int): Boolean = code == 429 || code == 502 || code == 503 || code == 504
}
