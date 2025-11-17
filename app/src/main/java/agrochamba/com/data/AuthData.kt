package agrochamba.com.data

import com.squareup.moshi.Json

/**
 * Molde para la respuesta que WordPress nos da al iniciar sesión correctamente.
 * Ahora incluye la lista de roles del usuario.
 */
data class TokenResponse(
    val token: String?,
    @Json(name = "user_display_name") val userDisplayName: String?,
    @Json(name = "user_email") val userEmail: String?,
    @Json(name = "user_nicename") val userNicename: String?,
    val roles: List<String>? // Campo para recibir los roles (ej: ["employer", "subscriber"])
)
