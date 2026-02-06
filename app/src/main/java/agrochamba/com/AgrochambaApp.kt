package agrochamba.com

import android.app.Application
import android.util.Log
import agrochamba.com.notifications.AgroNotificationManager
import agrochamba.com.utils.ANRWatchdog
import agrochamba.com.utils.AppLogger
import agrochamba.com.utils.DebugManager
import com.google.firebase.messaging.FirebaseMessaging
import dagger.hilt.android.HiltAndroidApp

@HiltAndroidApp
class AgrochambaApp : Application() {

    override fun onCreate() {
        super.onCreate()

        // Inicializar el DebugManager (debe ser lo primero)
        DebugManager.init(this)

        // Configurar handler global de excepciones no capturadas
        setupGlobalExceptionHandler()

        // Iniciar ANR Watchdog si debug está activo
        if (DebugManager.isEnabled) {
            ANRWatchdog.start()
        }

        // Inicializar sistema de notificaciones push
        initializeNotifications()

        AppLogger.i("APP", "AgroChambaApp iniciada. Debug: ${DebugManager.isEnabled}")
    }

    /**
     * Configura el handler global para capturar crashes no manejados.
     * Esto permite:
     * 1. Registrar el error antes de que la app muera
     * 2. Guardar información para debugging posterior
     * 3. Mostrar un mensaje más amigable al usuario
     */
    private fun setupGlobalExceptionHandler() {
        val defaultHandler = Thread.getDefaultUncaughtExceptionHandler()

        Thread.setDefaultUncaughtExceptionHandler { thread, throwable ->
            // Siempre loggear el crash
            Log.e("GLOBAL_CRASH", "=== CRASH DETECTADO ===", throwable)
            Log.e("GLOBAL_CRASH", "Thread: ${thread.name}")
            Log.e("GLOBAL_CRASH", "Error: ${throwable.message}")

            // Guardar en DebugManager para análisis posterior
            DebugManager.logCrash(throwable)

            // Llamar al handler por defecto para que el sistema maneje el crash
            // (esto evita que la app quede en estado zombie)
            defaultHandler?.uncaughtException(thread, throwable)
        }
    }

    /**
     * Inicializa el sistema de notificaciones push (FCM)
     */
    private fun initializeNotifications() {
        // Crear canales de notificación (requerido en Android 8+)
        AgroNotificationManager.initialize(this)

        // Obtener token FCM actual
        FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
            if (task.isSuccessful) {
                val token = task.result
                AppLogger.d("FCM", "Token FCM obtenido")
                // Sincronizar con el servidor
                AgroNotificationManager.syncFcmToken(this, token)
            } else {
                AppLogger.e("FCM", "Error obteniendo token FCM", task.exception)
            }
        }
    }
}
