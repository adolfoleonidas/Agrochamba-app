package agrochamba.com.utils

import android.os.Handler
import android.os.Looper
import android.util.Log

/**
 * =============================================================================
 * ANR WATCHDOG - Detector de bloqueos del hilo principal
 * =============================================================================
 *
 * Detecta cuando el hilo principal (UI) se bloquea por más de X segundos.
 * Esto ayuda a identificar ANRs (Application Not Responding).
 *
 * Un ANR ocurre cuando:
 * - El hilo principal está bloqueado por >5 segundos
 * - Android muestra "La app no responde"
 *
 * Causas comunes:
 * - Operaciones de red en el hilo principal
 * - Queries pesadas a base de datos
 * - Loops infinitos o muy largos
 * - Parsing de JSON grandes
 * - Carga de imágenes sin async
 */
object ANRWatchdog {

    private const val TAG = "ACH_ANR"
    private const val ANR_TIMEOUT_MS = 5000L // 5 segundos (igual que Android)
    private const val CHECK_INTERVAL_MS = 1000L // Verificar cada 1 segundo

    private var watchdogThread: Thread? = null
    private var isRunning = false
    private val mainHandler = Handler(Looper.getMainLooper())

    @Volatile
    private var lastTick = 0L

    @Volatile
    private var tickCompleted = true

    /**
     * Inicia el watchdog para detectar ANRs
     * Solo funciona si DebugManager.isEnabled es true
     */
    fun start() {
        if (isRunning) return
        if (!DebugManager.isEnabled) {
            Log.d(TAG, "ANR Watchdog no iniciado (debug desactivado)")
            return
        }

        isRunning = true
        tickCompleted = true
        lastTick = System.currentTimeMillis()

        watchdogThread = Thread({
            Log.i(TAG, "ANR Watchdog iniciado")

            while (isRunning && !Thread.interrupted()) {
                try {
                    // Enviar tick al hilo principal
                    tickCompleted = false
                    mainHandler.post {
                        tickCompleted = true
                        lastTick = System.currentTimeMillis()
                    }

                    // Esperar un poco
                    Thread.sleep(CHECK_INTERVAL_MS)

                    // Verificar si el tick fue completado
                    if (!tickCompleted) {
                        val blockTime = System.currentTimeMillis() - lastTick

                        if (blockTime > ANR_TIMEOUT_MS) {
                            // ANR detectado!
                            reportANR(blockTime)
                        } else if (blockTime > 2000) {
                            // Warning: UI lenta
                            Log.w(TAG, "⚠️ UI lenta: Main thread bloqueado por ${blockTime}ms")
                        }
                    }

                } catch (e: InterruptedException) {
                    break
                }
            }

            Log.i(TAG, "ANR Watchdog detenido")
        }, "ANR-Watchdog")

        watchdogThread?.start()
    }

    /**
     * Detiene el watchdog
     */
    fun stop() {
        isRunning = false
        watchdogThread?.interrupt()
        watchdogThread = null
    }

    /**
     * Reporta un ANR detectado
     */
    private fun reportANR(blockTimeMs: Long) {
        // Obtener stack trace del hilo principal
        val mainThread = Looper.getMainLooper().thread
        val stackTrace = mainThread.stackTrace

        val report = buildString {
            appendLine("🚨 ANR DETECTADO 🚨")
            appendLine("==========================================")
            appendLine("Main thread bloqueado por: ${blockTimeMs}ms")
            appendLine("Fecha: ${java.util.Date()}")
            appendLine("")
            appendLine("Stack trace del Main Thread:")
            appendLine("------------------------------------------")
            stackTrace.forEach { element ->
                appendLine("    at $element")
            }
            appendLine("==========================================")
        }

        // Loguear
        Log.e(TAG, report)

        // Guardar en DebugManager
        val anrException = ANRException("Main thread bloqueado por ${blockTimeMs}ms", stackTrace)
        DebugManager.logCrash(anrException)

        // También mostrar las primeras líneas relevantes (no de Android framework)
        val relevantTrace = stackTrace
            .filter { it.className.startsWith("agrochamba.") }
            .take(5)

        if (relevantTrace.isNotEmpty()) {
            Log.e(TAG, "📍 Código de la app involucrado:")
            relevantTrace.forEach { element ->
                Log.e(TAG, "    → ${element.className}.${element.methodName}() línea ${element.lineNumber}")
            }
        }
    }

    /**
     * Excepción personalizada para ANRs
     */
    class ANRException(message: String, private val mainThreadStack: Array<StackTraceElement>) : Exception(message) {
        init {
            stackTrace = mainThreadStack
        }
    }
}

/**
 * Helper para medir tiempo de operaciones y detectar bloqueos
 * Uso: measureBlock("CargarDatos") { ... código ... }
 */
inline fun <T> measureBlock(name: String, warnThresholdMs: Long = 100, block: () -> T): T {
    if (!DebugManager.isEnabled) return block()

    val start = System.currentTimeMillis()
    val result = block()
    val duration = System.currentTimeMillis() - start

    when {
        duration > 1000 -> Log.e("ACH_PERF", "🔴 $name tardó ${duration}ms (MUY LENTO)")
        duration > 500 -> Log.w("ACH_PERF", "🟠 $name tardó ${duration}ms (lento)")
        duration > warnThresholdMs -> Log.d("ACH_PERF", "🟡 $name tardó ${duration}ms")
    }

    return result
}
