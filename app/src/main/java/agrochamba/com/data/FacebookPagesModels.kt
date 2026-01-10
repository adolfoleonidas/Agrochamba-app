package agrochamba.com.data

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

/**
 * Modelo de una página de Facebook
 */
@JsonClass(generateAdapter = true)
data class FacebookPage(
    @Json(name = "id") val id: String,
    @Json(name = "page_id") val pageId: String,
    @Json(name = "page_name") val pageName: String,
    @Json(name = "page_token") val pageToken: String? = null, // Solo se envía al crear/editar
    @Json(name = "enabled") val enabled: Boolean = true,
    @Json(name = "is_primary") val isPrimary: Boolean = false,
    @Json(name = "created_at") val createdAt: String? = null,
    @Json(name = "updated_at") val updatedAt: String? = null,
    @Json(name = "last_verified") val lastVerified: String? = null
)

/**
 * Respuesta al listar páginas de Facebook
 */
@JsonClass(generateAdapter = true)
data class FacebookPagesResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "pages") val pages: List<FacebookPage> = emptyList(),
    @Json(name = "count") val count: Int = 0,
    @Json(name = "message") val message: String? = null
)

/**
 * Respuesta al agregar/actualizar una página
 */
@JsonClass(generateAdapter = true)
data class FacebookPageResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "page") val page: FacebookPage? = null,
    @Json(name = "message") val message: String? = null
)

/**
 * Respuesta al eliminar una página
 */
@JsonClass(generateAdapter = true)
data class FacebookPageDeleteResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "message") val message: String? = null
)

/**
 * Respuesta al probar una página
 */
@JsonClass(generateAdapter = true)
data class FacebookPageTestResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "page_name") val pageName: String? = null,
    @Json(name = "page_id") val pageId: String? = null,
    @Json(name = "message") val message: String? = null
)

/**
 * Datos para crear/actualizar una página
 */
@JsonClass(generateAdapter = true)
data class FacebookPageRequest(
    @Json(name = "page_id") val pageId: String,
    @Json(name = "page_name") val pageName: String,
    @Json(name = "page_token") val pageToken: String,
    @Json(name = "enabled") val enabled: Boolean = true
)

