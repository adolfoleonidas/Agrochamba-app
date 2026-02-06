package agrochamba.com.notifications

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.util.Log
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import agrochamba.com.MainActivity
import agrochamba.com.R
import agrochamba.com.data.AuthManager
import agrochamba.com.data.WordPressApi
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

/**
 * Manager centralizado de notificaciones.
 * Maneja la creación de canales, envío de notificaciones locales,
 * y sincronización del token FCM con el servidor.
 */
object AgroNotificationManager {

    private const val TAG = "AgroNotificationManager"
    private const val PREFS_NAME = "agro_notifications"
    private const val KEY_FCM_TOKEN = "fcm_token"
    private const val KEY_TOKEN_SYNCED = "fcm_token_synced"

    private var notificationId = 0

    /**
     * Inicializar el manager - llamar desde Application.onCreate()
     */
    fun initialize(context: Context) {
        createNotificationChannels(context)
    }

    /**
     * Crear todos los canales de notificación
     */
    private fun createNotificationChannels(context: Context) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val notificationManager = context.getSystemService(NotificationManager::class.java)

            NotificationType.getAllChannels().forEach { (channelId, channelName, channelDescription) ->
                val importance = when (channelId) {
                    "messages", "applications", "applicants" -> NotificationManager.IMPORTANCE_HIGH
                    "promotions" -> NotificationManager.IMPORTANCE_LOW
                    else -> NotificationManager.IMPORTANCE_DEFAULT
                }

                val channel = NotificationChannel(channelId, channelName, importance).apply {
                    description = channelDescription
                    enableVibration(importance == NotificationManager.IMPORTANCE_HIGH)
                }

                notificationManager.createNotificationChannel(channel)
            }

            Log.d(TAG, "Notification channels created")
        }
    }

    /**
     * Mostrar una notificación local
     */
    fun showNotification(
        context: Context,
        type: NotificationType,
        title: String,
        body: String,
        data: Map<String, String> = emptyMap()
    ) {
        // Verificar permiso en Android 13+
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS)
                != PackageManager.PERMISSION_GRANTED
            ) {
                Log.w(TAG, "Notification permission not granted")
                return
            }
        }

        // Crear intent para abrir la app
        val intent = Intent(context, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
            // Pasar datos para navegación
            data.forEach { (key, value) ->
                putExtra(key, value)
            }
            putExtra("notification_type", type.name)
        }

        val pendingIntent = PendingIntent.getActivity(
            context,
            notificationId,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        // Construir notificación
        val notification = NotificationCompat.Builder(context, type.channelId)
            .setSmallIcon(R.drawable.ic_launcher_foreground)
            .setContentTitle(title)
            .setContentText(body)
            .setStyle(NotificationCompat.BigTextStyle().bigText(body))
            .setPriority(getPriority(type))
            .setContentIntent(pendingIntent)
            .setAutoCancel(true)
            .setColor(ContextCompat.getColor(context, R.color.teal_700))
            .build()

        NotificationManagerCompat.from(context).notify(notificationId++, notification)
        Log.d(TAG, "Notification shown: $title")
    }

    /**
     * Obtener prioridad según el tipo
     */
    private fun getPriority(type: NotificationType): Int {
        return when (type) {
            NotificationType.APPLICATION_STATUS_CHANGED,
            NotificationType.NEW_APPLICANT,
            NotificationType.NEW_MESSAGE -> NotificationCompat.PRIORITY_HIGH

            NotificationType.PROMOTION -> NotificationCompat.PRIORITY_LOW

            else -> NotificationCompat.PRIORITY_DEFAULT
        }
    }

    /**
     * Guardar y sincronizar token FCM con el servidor
     */
    fun syncFcmToken(context: Context, token: String) {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        val savedToken = prefs.getString(KEY_FCM_TOKEN, null)

        // Solo sincronizar si es un token nuevo
        if (token == savedToken && prefs.getBoolean(KEY_TOKEN_SYNCED, false)) {
            Log.d(TAG, "Token already synced")
            return
        }

        // Guardar token localmente
        prefs.edit().putString(KEY_FCM_TOKEN, token).apply()

        // Sincronizar con el servidor si hay usuario logueado
        val authToken = AuthManager.token
        if (authToken != null) {
            CoroutineScope(Dispatchers.IO).launch {
                try {
                    val response = WordPressApi.retrofitService.registerFcmToken(
                        token = "Bearer $authToken",
                        data = mapOf("fcm_token" to token)
                    )

                    if (response.success) {
                        prefs.edit().putBoolean(KEY_TOKEN_SYNCED, true).apply()
                        Log.d(TAG, "FCM token synced with server")
                    } else {
                        Log.e(TAG, "Failed to sync FCM token: ${response.message}")
                    }
                } catch (e: Exception) {
                    Log.e(TAG, "Error syncing FCM token", e)
                }
            }
        } else {
            Log.d(TAG, "No auth token, FCM token saved locally for later sync")
            prefs.edit().putBoolean(KEY_TOKEN_SYNCED, false).apply()
        }
    }

    /**
     * Marcar token como no sincronizado (llamar al hacer logout)
     */
    fun invalidateTokenSync(context: Context) {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        prefs.edit().putBoolean(KEY_TOKEN_SYNCED, false).apply()
    }

    /**
     * Resincronizar token después del login
     */
    fun resyncTokenAfterLogin(context: Context) {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        val token = prefs.getString(KEY_FCM_TOKEN, null)

        if (token != null) {
            prefs.edit().putBoolean(KEY_TOKEN_SYNCED, false).apply()
            syncFcmToken(context, token)
        }
    }

    /**
     * Obtener el token FCM guardado
     */
    fun getSavedFcmToken(context: Context): String? {
        val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        return prefs.getString(KEY_FCM_TOKEN, null)
    }
}
