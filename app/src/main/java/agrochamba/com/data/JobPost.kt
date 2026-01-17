package agrochamba.com.data

import com.squareup.moshi.Json

// El molde principal, ahora con todos los metadatos
data class JobPost(
    val id: Int,
    val link: String? = null,
    val date: String? = null,
    val status: String? = null, // publish, pending, draft, trash
    val title: Title? = null,
    val content: Content? = null,
    val excerpt: Content? = null,
    @Json(name = "featured_media") val featuredMedia: Int? = null,
    @Json(name = "featured_image_url") val featuredImageUrl: String? = null,
    val meta: JobMeta? = null, // Objeto que contiene todos los campos personalizados
    @Json(name = "_embedded") val embedded: Embedded? = null
)

data class Title(
    val rendered: String?
)

data class Content(
    val rendered: String?
)

// Molde para todos los campos personalizados que creaste
data class JobMeta(
    @Json(name = "salario_min") val salarioMin: String?,
    @Json(name = "salario_max") val salarioMax: String?,
    val vacantes: String?,
    @Json(name = "tipo_contrato") val tipoContrato: String?,
    val jornada: String?,
    val alojamiento: Boolean?,
    val transporte: Boolean?,
    val alimentacion: Boolean?,
    val requisitos: String?,
    val beneficios: String?,
    @Json(name = "gallery_ids") val galleryIds: List<Int>?, // IDs de las imágenes de la galería
    @Json(name = "facebook_post_id") val facebookPostId: String?, // ID del post en Facebook si fue publicado
    @Json(name = "_ubicacion_completa") val ubicacionCompleta: UbicacionCompleta? = null
)


data class Embedded(
    // Ahora usamos MediaItem, que es nuestro molde maestro para imágenes
    @Json(name = "wp:featuredmedia") val featuredMedia: List<MediaItem>?,
    @Json(name = "wp:term") val terms: List<List<Category>>?
)
