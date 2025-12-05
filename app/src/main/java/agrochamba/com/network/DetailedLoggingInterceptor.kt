package agrochamba.com.network

import android.util.Log
import okhttp3.Interceptor
import okhttp3.Response
import okio.Buffer
import java.io.IOException
import java.nio.charset.Charset
import java.util.concurrent.TimeUnit

/**
 * Interceptor de logging detallado SOLO para entornos Debug.
 * Redacta el header Authorization y limita la vista de cuerpos grandes.
 */
class DetailedLoggingInterceptor : Interceptor {

    companion object {
        private const val TAG = "🔍 HTTP_DETAIL"
        private const val MAX_PREVIEW_BYTES = 1024 * 1024 // 1 MB
        private val UTF8: Charset = Charset.forName("UTF-8")
    }

    override fun intercept(chain: Interceptor.Chain): Response {
        val request = chain.request()
        val requestStartTime = System.nanoTime()

        // Construir headers con Authorization redactado
        val headersMasked = request.headers.newBuilder().apply {
            if (request.header("Authorization") != null) {
                set("Authorization", "Bearer ***REDACTED***")
            }
        }.build()

        // Log de REQUEST
        val contentLength = try { request.body?.contentLength() ?: -1 } catch (_: Exception) { -1 }
        Log.d(TAG, """
            ╔═══════════════════════════════════════════════════════════════
            ║ 📤 ENVIANDO REQUEST
            ╠═══════════════════════════════════════════════════════════════
            ║ 🌐 URL: ${request.url}
            ║ 📍 Método: ${request.method}
            ║ 🏷️  Headers (${headersMasked.size}):
            ${headersMasked.joinToString("\n") { "║    • ${it.first}: ${it.second}" }}
            ║ 
            ║ 📦 Body:
            ║    • Content-Type: ${request.body?.contentType()}
            ║    • Content-Length: ${if (contentLength >= 0) "$contentLength bytes (" + formatBytes(contentLength) + ")" else "desconocido"}
            ║    • Has Body: ${request.body != null}
            ${if (request.body != null && contentLength in 0..MAX_PREVIEW_BYTES) {
            "║    • Body Preview: ${getRequestBodyPreview(request)}"
        } else {
            "║    • Body Preview: [Binario o tamaño > 1MB]"
        }}
            ╚═══════════════════════════════════════════════════════════════
        """.trimIndent())

        // Ejecutar
        val response: Response = try {
            chain.proceed(request)
        } catch (e: Exception) {
            val requestDuration = TimeUnit.NANOSECONDS.toMillis(System.nanoTime() - requestStartTime)

            Log.e(TAG, """
                ╔═══════════════════════════════════════════════════════════════
                ║ ❌ ERROR EN REQUEST
                ╠═══════════════════════════════════════════════════════════════
                ║ 🌐 URL: ${request.url}
                ║ ⏱️  Duración: ${requestDuration}ms
                ║ 
                ║ 🔥 EXCEPCIÓN:
                ║    Tipo: ${e.javaClass.simpleName}
                ║    Mensaje: ${e.message}
                ║    
                ║ 📋 Diagnóstico:
                ${diagnosticException(e)}
                ║ 
                ║ 📚 Stack Trace:
                ${e.stackTraceToString().lines().take(10).joinToString("\n") { "║    $it" }}
                ╚═══════════════════════════════════════════════════════════════
            """.trimIndent())

            throw e
        }

        val requestDuration = TimeUnit.NANOSECONDS.toMillis(System.nanoTime() - requestStartTime)

        // Log de RESPONSE
        val responseBody = response.body
        val source = responseBody?.source()
        try { source?.request(Long.MAX_VALUE) } catch (_: Exception) {}
        val buffer = source?.buffer

        val responseBodyString = try {
            buffer?.clone()?.readString(UTF8) ?: ""
        } catch (e: Exception) {
            "[Error leyendo body: ${e.message}]"
        }

        val statusEmoji = when (response.code) {
            in 200..299 -> "✅"
            in 300..399 -> "↩️"
            in 400..499 -> "❌"
            in 500..599 -> "💥"
            else -> "❓"
        }

        Log.d(TAG, """
            ╔═══════════════════════════════════════════════════════════════
            ║ $statusEmoji RESPUESTA RECIBIDA
            ╠═══════════════════════════════════════════════════════════════
            ║ 🌐 URL: ${request.url}
            ║ 📊 Status: ${response.code} ${response.message}
            ║ ⏱️  Duración: ${requestDuration}ms
            ║ 🏷️  Response Headers (${response.headers.size}):
            ${response.headers.joinToString("\n") { "║    • ${it.first}: ${it.second}" }}
            ║ 
            ║ 📦 Response Body:
            ║    • Content-Type: ${responseBody?.contentType()}
            ║    • Content-Length: ${responseBody?.contentLength() ?: -1}
            ║    • Body (primeros 2000 chars):
            ${responseBodyString.take(2000).lines().joinToString("\n") { "║    $it" }}
            ╚═══════════════════════════════════════════════════════════════
        """.trimIndent())

        return response
    }

    private fun getRequestBodyPreview(request: okhttp3.Request): String {
        return try {
            val copy = request.newBuilder().build()
            val body = copy.body ?: return "[sin cuerpo]"
            val buffer = Buffer()
            body.writeTo(buffer)
            buffer.readString(UTF8).take(2000)
        } catch (e: IOException) {
            "[no se pudo leer el cuerpo: ${e.message}]"
        }
    }

    private fun diagnosticException(e: Exception): String = when (e) {
        is java.net.SocketTimeoutException -> "• Timeout: el servidor no respondió a tiempo."
        is java.net.UnknownHostException -> "• DNS/Internet: no se pudo resolver el host."
        is java.net.ConnectException -> "• Conexión: no se pudo establecer conexión."
        is javax.net.ssl.SSLHandshakeException,
        is javax.net.ssl.SSLPeerUnverifiedException,
        is javax.net.ssl.SSLException -> "• SSL/TLS: problema en el handshake o certificados."
        is java.io.EOFException, is java.net.ProtocolException -> "• Respuesta inválida del servidor."
        else -> "• ${e.javaClass.simpleName}: ${e.message}"
    }

    private fun formatBytes(bytes: Long): String {
        if (bytes < 0) return "desconocido"
        val kb = bytes / 1024.0
        val mb = kb / 1024.0
        return when {
            mb >= 1 -> String.format("%.2f MB", mb)
            kb >= 1 -> String.format("%.2f KB", kb)
            else -> "$bytes B"
        }
    }
}
