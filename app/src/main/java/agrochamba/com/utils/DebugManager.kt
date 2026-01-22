package agrochamba.com.utils

import android.content.Context
import android.content.SharedPreferences
import android.widget.Toast
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

/**
 * =============================================================================
 * DEBUG MANAGER - Control global del modo debug
 * =============================================================================
 *
 * Permite activar/desactivar el modo debug de forma oculta.
 * Solo accesible para admins mediante gesto secreto (tap 5 veces).
 *
 * Uso:
 * - DebugManager.init(context) en Application.onCreate()
 * - DebugManager.isEnabled para verificar si está activo
 * - DebugManager.toggle() para cambiar el estado
 */
object DebugManager {

    private const val PREFS_NAME = "debug_prefs"
    private const val KEY_DEBUG_ENABLED = "debug_enabled"
    private const val KEY_LAST_CRASH_LOG = "last_crash_log"
    private const val KEY_CRASH_COUNT = "crash_count"

    private var prefs: SharedPreferences? = null

    private val _isEnabled = MutableStateFlow(false)
    val isEnabledFlow: StateFlow<Boolean> = _isEnabled.asStateFlow()

    /** Estado actual del modo debug */
    val isEnabled: Boolean
        get() = _isEnabled.value

    /** Último crash registrado */
    var lastCrashLog: String? = null
        private set

    /** Contador de crashes */
    var crashCount: Int = 0
        private set

    /**
     * Inicializa el DebugManager con contexto de la app
     * Llamar en Application.onCreate()
     */
    fun init(context: Context) {
        prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        _isEnabled.value = prefs?.getBoolean(KEY_DEBUG_ENABLED, false) ?: false
        lastCrashLog = prefs?.getString(KEY_LAST_CRASH_LOG, null)
        crashCount = prefs?.getInt(KEY_CRASH_COUNT, 0) ?: 0
    }

    /**
     * Activa o desactiva el modo debug
     */
    fun toggle(): Boolean {
        _isEnabled.value = !_isEnabled.value
        prefs?.edit()?.putBoolean(KEY_DEBUG_ENABLED, _isEnabled.value)?.apply()
        updateANRWatchdog()
        return _isEnabled.value
    }

    /**
     * Activa el modo debug
     */
    fun enable() {
        _isEnabled.value = true
        prefs?.edit()?.putBoolean(KEY_DEBUG_ENABLED, true)?.apply()
        updateANRWatchdog()
    }

    /**
     * Desactiva el modo debug
     */
    fun disable() {
        _isEnabled.value = false
        prefs?.edit()?.putBoolean(KEY_DEBUG_ENABLED, false)?.apply()
        updateANRWatchdog()
    }

    /**
     * Inicia o detiene el ANR Watchdog según el estado del debug
     */
    private fun updateANRWatchdog() {
        if (_isEnabled.value) {
            ANRWatchdog.start()
        } else {
            ANRWatchdog.stop()
        }
    }

    /**
     * Registra un crash para análisis posterior
     */
    fun logCrash(throwable: Throwable) {
        crashCount++
        lastCrashLog = buildString {
            appendLine("=== CRASH ${crashCount} ===")
            appendLine("Fecha: ${java.util.Date()}")
            appendLine("Error: ${throwable.message}")
            appendLine("Tipo: ${throwable::class.simpleName}")
            appendLine("Stack trace:")
            appendLine(throwable.stackTraceToString().take(2000))
        }

        prefs?.edit()?.apply {
            putString(KEY_LAST_CRASH_LOG, lastCrashLog)
            putInt(KEY_CRASH_COUNT, crashCount)
            apply()
        }
    }

    /**
     * Limpia el log de crashes
     */
    fun clearCrashLog() {
        lastCrashLog = null
        crashCount = 0
        prefs?.edit()?.apply {
            remove(KEY_LAST_CRASH_LOG)
            putInt(KEY_CRASH_COUNT, 0)
            apply()
        }
    }

    /**
     * Helper para mostrar toast de activación/desactivación
     */
    fun showToggleToast(context: Context) {
        val message = if (isEnabled) {
            "🔧 Modo Debug ACTIVADO\nLos logs están visibles"
        } else {
            "🚀 Modo Debug DESACTIVADO\nApp en modo normal"
        }
        Toast.makeText(context, message, Toast.LENGTH_LONG).show()
    }
}

/**
 * Helper para detectar tap múltiple (gesto secreto)
 * Uso típico: en un elemento de UI, llamar onTap() cada vez que se toque
 */
class SecretTapDetector(
    private val requiredTaps: Int = 5,
    private val timeoutMs: Long = 3000,
    private val onSecretActivated: () -> Unit
) {
    private var tapCount = 0
    private var lastTapTime = 0L

    fun onTap() {
        val currentTime = System.currentTimeMillis()

        // Reset si pasó mucho tiempo desde el último tap
        if (currentTime - lastTapTime > timeoutMs) {
            tapCount = 0
        }

        tapCount++
        lastTapTime = currentTime

        if (tapCount >= requiredTaps) {
            onSecretActivated()
            tapCount = 0
        }
    }

    fun reset() {
        tapCount = 0
    }
}
