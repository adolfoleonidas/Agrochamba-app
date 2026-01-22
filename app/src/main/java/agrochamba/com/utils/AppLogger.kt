package agrochamba.com.utils

import android.util.Log

/**
 * =============================================================================
 * APP LOGGER - Logger condicional para debug
 * =============================================================================
 *
 * Registra logs SOLO cuando el modo debug está activado.
 * Esto evita llenar logcat en producción y mejora el rendimiento.
 *
 * Uso:
 * - AppLogger.d("TAG", "mensaje") // Debug
 * - AppLogger.i("TAG", "mensaje") // Info
 * - AppLogger.w("TAG", "mensaje") // Warning
 * - AppLogger.e("TAG", "mensaje", exception) // Error
 *
 * Los logs de nivel ERROR siempre se registran (son importantes para crashes).
 */
object AppLogger {

    private const val APP_PREFIX = "ACH_" // Prefijo para filtrar fácil en logcat

    /**
     * Log de nivel DEBUG
     * Solo se muestra si debug está activado
     */
    fun d(tag: String, message: String) {
        if (DebugManager.isEnabled) {
            Log.d("$APP_PREFIX$tag", message)
        }
    }

    /**
     * Log de nivel INFO
     * Solo se muestra si debug está activado
     */
    fun i(tag: String, message: String) {
        if (DebugManager.isEnabled) {
            Log.i("$APP_PREFIX$tag", message)
        }
    }

    /**
     * Log de nivel WARNING
     * Solo se muestra si debug está activado
     */
    fun w(tag: String, message: String, throwable: Throwable? = null) {
        if (DebugManager.isEnabled) {
            if (throwable != null) {
                Log.w("$APP_PREFIX$tag", message, throwable)
            } else {
                Log.w("$APP_PREFIX$tag", message)
            }
        }
    }

    /**
     * Log de nivel ERROR
     * SIEMPRE se registra (es importante para debugging de crashes)
     */
    fun e(tag: String, message: String, throwable: Throwable? = null) {
        // Los errores siempre se loggean
        if (throwable != null) {
            Log.e("$APP_PREFIX$tag", message, throwable)
        } else {
            Log.e("$APP_PREFIX$tag", message)
        }

        // También guardar en el crash log si es un error significativo
        throwable?.let {
            if (DebugManager.isEnabled) {
                DebugManager.logCrash(it)
            }
        }
    }

    /**
     * Log de nivel VERBOSE
     * Solo se muestra si debug está activado
     * Útil para logs muy detallados (ej: cada item de una lista)
     */
    fun v(tag: String, message: String) {
        if (DebugManager.isEnabled) {
            Log.v("$APP_PREFIX$tag", message)
        }
    }

    // =========================================================================
    // HELPERS PARA DEBUGGING ESPECÍFICO
    // =========================================================================

    /**
     * Log para debugging de API responses
     */
    fun api(endpoint: String, response: String) {
        d("API", "[$endpoint] Response: ${response.take(500)}...")
    }

    /**
     * Log para debugging de Jobs/Trabajos
     */
    fun job(jobId: Int?, action: String, details: String = "") {
        d("JOB", "Job #$jobId - $action${if (details.isNotEmpty()) ": $details" else ""}")
    }

    /**
     * Log para debugging de ubicaciones
     */
    fun location(action: String, details: String) {
        d("LOCATION", "$action: $details")
    }

    /**
     * Log para debugging de sedes
     */
    fun sede(sedeId: String?, action: String, details: String = "") {
        d("SEDE", "Sede $sedeId - $action${if (details.isNotEmpty()) ": $details" else ""}")
    }

    /**
     * Log para debugging de navegación
     */
    fun nav(screen: String, action: String = "opened") {
        d("NAV", "$screen - $action")
    }

    /**
     * Log para debugging de autenticación
     */
    fun auth(action: String, details: String = "") {
        d("AUTH", "$action${if (details.isNotEmpty()) ": $details" else ""}")
    }

    /**
     * Log para datos que pueden causar crashes
     * Útil para detectar campos null/vacíos
     */
    fun validateData(tag: String, data: Map<String, Any?>) {
        if (!DebugManager.isEnabled) return

        val issues = mutableListOf<String>()
        data.forEach { (key, value) ->
            when {
                value == null -> issues.add("⚠️ $key is NULL")
                value is String && value.isBlank() -> issues.add("⚠️ $key is EMPTY")
                value is List<*> && value.isEmpty() -> issues.add("⚠️ $key list is EMPTY")
                value is Int && value == 0 -> issues.add("ℹ️ $key is 0")
            }
        }

        if (issues.isNotEmpty()) {
            w(tag, "Validación de datos:\n${issues.joinToString("\n")}")
        } else {
            d(tag, "✅ Datos válidos: ${data.keys.joinToString(", ")}")
        }
    }

    /**
     * Log de inicio de pantalla con datos relevantes
     */
    fun screenStart(screenName: String, params: Map<String, Any?> = emptyMap()) {
        if (!DebugManager.isEnabled) return

        val paramsStr = if (params.isNotEmpty()) {
            "\nParams: ${params.entries.joinToString { "${it.key}=${it.value}" }}"
        } else ""

        i("SCREEN", "📱 $screenName iniciado$paramsStr")
    }

    /**
     * Log de operación con tiempo
     */
    inline fun <T> timed(tag: String, operation: String, block: () -> T): T {
        if (!DebugManager.isEnabled) return block()

        val start = System.currentTimeMillis()
        val result = block()
        val duration = System.currentTimeMillis() - start
        d(tag, "⏱️ $operation completado en ${duration}ms")
        return result
    }
}
