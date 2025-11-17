package agrochamba.com.data

import com.squareup.moshi.Json

// El molde principal, ahora con todos los metadatos
data class JobPost(
    val id: Int,
    val link: String?,
    val date: String?,
    val title: Title?,
    val content: Content?,
    val excerpt: Content?,
    val meta: JobMeta?, // Objeto que contiene todos los campos personalizados
    @Json(name = "_embedded") val embedded: Embedded?
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
    @Json(name = "gallery_ids") val galleryIds: List<Int>? // IDs de las imágenes de la galería
)


data class Embedded(
    // Ahora usamos MediaItem, que es nuestro molde maestro para imágenes
    @Json(name = "wp:featuredmedia") val featuredMedia: List<MediaItem>?,
    @Json(name = "wp:term") val terms: List<List<Category>>?
)
