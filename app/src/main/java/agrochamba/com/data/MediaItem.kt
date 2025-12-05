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
    
    // Función helper para obtener la URL de la imagen original (full size)
    fun getFullImageUrl(): String? {
        // Priorizar la URL full desde media_details
        val fullUrl = mediaDetails?.sizes?.full?.source_url ?: source_url ?: url ?: link
        
        // Si la URL contiene parámetros de tamaño (ej: -300x200, -scaled, etc.), intentar obtener la original
        return fullUrl?.let { url ->
            // Eliminar parámetros de tamaño comunes de WordPress
            var cleanUrl = url
                .replace("-\\d+x\\d+".toRegex(), "") // Eliminar -300x200
                .replace("-scaled", "") // Eliminar -scaled
                .replace("-\\d+w".toRegex(), "") // Eliminar -1024w
            
            // Si la URL tiene query parameters relacionados con tamaño, eliminarlos
            if (cleanUrl.contains("?")) {
                val baseUrl = cleanUrl.substringBefore("?")
                val params = cleanUrl.substringAfter("?").split("&")
                val filteredParams = params.filterNot { 
                    it.startsWith("w=") || 
                    it.startsWith("h=") || 
                    it.startsWith("resize=") ||
                    it.startsWith("fit=")
                }
                cleanUrl = if (filteredParams.isNotEmpty()) {
                    "$baseUrl?${filteredParams.joinToString("&")}"
                } else {
                    baseUrl
                }
            }
            
            cleanUrl
        }
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
