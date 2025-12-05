package agrochamba.com.data

import com.squareup.moshi.Json

/**
 * Molde para la respuesta que WordPress nos da al iniciar sesión correctamente.
 * Ahora incluye la lista de roles del usuario y el ID de la empresa a la que pertenece.
 */
data class TokenResponse(
    val token: String?,
    @Json(name = "user_display_name") val userDisplayName: String?,
    @Json(name = "user_email") val userEmail: String?,
    @Json(name = "user_nicename") val userNicename: String?,
    val roles: List<String>?,
    @Json(name = "user_company_id") val userCompanyId: Int? // ID de la empresa del usuario (si es un empleador)
)
