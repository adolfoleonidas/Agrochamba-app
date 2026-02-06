package agrochamba.com.data

import com.squareup.moshi.Json

/**
 * Modelos para el sistema de notificaciones push
 */

/**
 * Respuesta genérica de la API
 */
data class GenericApiResponse(
    val success: Boolean,
    val message: String? = null
)

/**
 * Respuesta al registrar token FCM
 */
data class FcmTokenResponse(
    val success: Boolean,
    val message: String? = null,
    @Json(name = "device_registered") val deviceRegistered: Boolean = false
)

/**
 * Configuración de notificaciones del usuario
 */
data class NotificationSettings(
    @Json(name = "applications") val applications: Boolean = true,
    @Json(name = "new_jobs") val newJobs: Boolean = true,
    @Json(name = "favorites") val favorites: Boolean = true,
    @Json(name = "messages") val messages: Boolean = true,
    @Json(name = "promotions") val promotions: Boolean = false,
    @Json(name = "system") val system: Boolean = true
)

/**
 * Respuesta de configuración de notificaciones
 */
data class NotificationSettingsResponse(
    val success: Boolean,
    val settings: NotificationSettings? = null,
    val message: String? = null
)
