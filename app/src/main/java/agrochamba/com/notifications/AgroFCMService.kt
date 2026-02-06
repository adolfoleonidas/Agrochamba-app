package agrochamba.com.notifications

import android.util.Log
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage

/**
 * Servicio de Firebase Cloud Messaging.
 * Recibe notificaciones push del servidor y las muestra.
 */
class AgroFCMService : FirebaseMessagingService() {

    companion object {
        private const val TAG = "AgroFCMService"
    }

    /**
     * Llamado cuando se recibe un nuevo token FCM.
     * Esto ocurre cuando:
     * - La app se instala por primera vez
     * - El usuario desinstala/reinstala la app
     * - El usuario borra los datos de la app
     * - El token expira
     */
    override fun onNewToken(token: String) {
        super.onNewToken(token)
        Log.d(TAG, "New FCM token received")

        // Sincronizar con el servidor
        AgroNotificationManager.syncFcmToken(applicationContext, token)
    }

    /**
     * Llamado cuando se recibe un mensaje push.
     * Puede contener notification y/o data payload.
     */
    override fun onMessageReceived(remoteMessage: RemoteMessage) {
        super.onMessageReceived(remoteMessage)
        Log.d(TAG, "Message received from: ${remoteMessage.from}")

        // Obtener datos del mensaje
        val data = remoteMessage.data
        val notification = remoteMessage.notification

        // Determinar tipo de notificación
        val type = NotificationType.fromString(data["type"])

        // Obtener título y cuerpo
        val title = notification?.title ?: data["title"] ?: getDefaultTitle(type)
        val body = notification?.body ?: data["body"] ?: data["message"] ?: ""

        if (title.isNotBlank() && body.isNotBlank()) {
            // Mostrar notificación
            AgroNotificationManager.showNotification(
                context = applicationContext,
                type = type,
                title = title,
                body = body,
                data = data
            )
        }

        // Log para debugging
        Log.d(TAG, "Notification: type=$type, title=$title")
    }

    /**
     * Obtener título por defecto según el tipo
     */
    private fun getDefaultTitle(type: NotificationType): String {
        return when (type) {
            NotificationType.APPLICATION_STATUS_CHANGED -> "Actualización de postulación"
            NotificationType.NEW_APPLICANT -> "Nuevo postulante"
            NotificationType.NEW_JOB_IN_ZONE -> "Nuevo trabajo disponible"
            NotificationType.FAVORITE_JOB_UPDATED -> "Trabajo actualizado"
            NotificationType.NEW_MESSAGE -> "Nuevo mensaje"
            NotificationType.CREDITS_LOW -> "Créditos bajos"
            NotificationType.CREDITS_PURCHASED -> "Compra confirmada"
            NotificationType.PROMOTION -> "Oferta especial"
            else -> "AgroChamba"
        }
    }
}
