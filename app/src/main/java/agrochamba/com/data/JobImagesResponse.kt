package agrochamba.com.data

import com.squareup.moshi.Json

data class JobImagesResponse(
    val images: List<JobImage>
)

data class JobImage(
    val id: Int,
    @Json(name = "source_url") val source_url: String?,
    @Json(name = "card_url") val card_url: String?,
    @Json(name = "detail_url") val detail_url: String?,
    @Json(name = "thumb_url") val thumb_url: String?,
    @Json(name = "thumbnail_url") val thumbnail_url: String?,
    @Json(name = "medium_url") val medium_url: String?,
    @Json(name = "large_url") val large_url: String?
) {
    /**
     * Obtiene la URL optimizada para cards (lista de trabajos)
     */
    fun getCardUrl(): String? {
        return card_url ?: medium_url ?: thumbnail_url ?: source_url
    }
    
    /**
     * Obtiene la URL optimizada para detalle (slider)
     */
    fun getDetailUrl(): String? {
        return detail_url ?: large_url ?: source_url
    }
    
    /**
     * Obtiene la URL optimizada para miniaturas
     */
    fun getThumbUrl(): String? {
        return thumb_url ?: thumbnail_url ?: source_url
    }
    
    /**
     * Obtiene la URL completa (fallback)
     */
    fun getFullUrl(): String? {
        return source_url ?: large_url ?: medium_url
    }
}

