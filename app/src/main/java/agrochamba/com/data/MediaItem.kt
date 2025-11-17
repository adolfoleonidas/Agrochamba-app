package agrochamba.com.data

import com.squareup.moshi.Json

/**
 * Representa un solo ítem (como una imagen) de la biblioteca de medios de WordPress.
 * WordPress REST API puede devolver diferentes estructuras dependiendo del contexto.
 */
data class MediaItem(
    val id: Int? = null,
    @Json(name = "source_url") val source_url: String? = null,
    // Campos alternativos que WordPress puede devolver
    @Json(name = "url") val url: String? = null,
    @Json(name = "link") val link: String? = null,
    @Json(name = "media_details") val mediaDetails: MediaDetails? = null
) {
    // Función helper para obtener la URL de la imagen
    fun getImageUrl(): String? {
        return source_url ?: url ?: link ?: mediaDetails?.sizes?.full?.source_url
    }
}

data class MediaDetails(
    val sizes: MediaSizes?
)

data class MediaSizes(
    @Json(name = "full") val full: MediaSize?,
    @Json(name = "large") val large: MediaSize?,
    @Json(name = "medium") val medium: MediaSize?,
    @Json(name = "thumbnail") val thumbnail: MediaSize?
)

data class MediaSize(
    @Json(name = "source_url") val source_url: String?,
    val width: Int?,
    val height: Int?
)
