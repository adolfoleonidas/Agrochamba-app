package agrochamba.com.data

import com.squareup.moshi.Json

/**
 * Modelo de datos para la respuesta del endpoint /agrochamba/v1/me/jobs
 * Este endpoint devuelve un formato personalizado con taxonomías directas
 */
data class MyJobResponse(
    val id: Int,
    val title: Title?,
    val content: Content?,
    val excerpt: Content?,
    val date: String?,
    val modified: String?,
    val status: String?,
    val link: String?,
    @Json(name = "featured_media") val featuredMedia: Int?,
    @Json(name = "featured_media_url") val featuredMediaUrl: String?,
    @Json(name = "gallery_ids") val galleryIds: List<Int>?,
    val ubicacion: TaxonomyData?,
    val cultivo: TaxonomyData?,
    @Json(name = "tipo_puesto") val tipoPuesto: TaxonomyData?,
    val empresa: TaxonomyData?,
    @Json(name = "salario_min") val salarioMin: String?,
    @Json(name = "salario_max") val salarioMax: String?,
    val vacantes: String?,
    val alojamiento: Boolean?,
    val transporte: Boolean?,
    val alimentacion: Boolean?,
    @Json(name = "facebook_post_id") val facebookPostId: String? // ID del post en Facebook si fue publicado
)

data class TaxonomyData(
    val id: Int,
    val name: String,
    val slug: String
)

data class UpdateJobResponse(
    val success: Boolean,
    val message: String,
    @Json(name = "post_id") val postId: Int
)

/**
 * Función de extensión para convertir MyJobResponse a JobPost
 * Esto permite mantener compatibilidad con código existente que espera JobPost
 */
fun MyJobResponse.toJobPost(): JobPost {
    // Convertir taxonomías a formato Category
    val terms = mutableListOf<List<agrochamba.com.data.Category>>()
    
    ubicacion?.let {
        terms.add(listOf(agrochamba.com.data.Category(
            id = it.id,
            name = it.name,
            slug = it.slug,
            taxonomy = "ubicacion"
        )))
    }
    
    cultivo?.let {
        terms.add(listOf(agrochamba.com.data.Category(
            id = it.id,
            name = it.name,
            slug = it.slug,
            taxonomy = "cultivo"
        )))
    }
    
    tipoPuesto?.let {
        terms.add(listOf(agrochamba.com.data.Category(
            id = it.id,
            name = it.name,
            slug = it.slug,
            taxonomy = "tipo_puesto"
        )))
    }
    
    empresa?.let {
        terms.add(listOf(agrochamba.com.data.Category(
            id = it.id,
            name = it.name,
            slug = it.slug,
            taxonomy = "empresa"
        )))
    }
    
    // Crear featured media si existe
    val featuredMedia = if (featuredMediaUrl != null && featuredMedia != null) {
        listOf(agrochamba.com.data.MediaItem(
            id = featuredMedia,
            source_url = featuredMediaUrl
        ))
    } else {
        emptyList()
    }
    
    return JobPost(
        id = id,
        link = link,
        date = date,
        title = title,
        content = content,
        excerpt = excerpt,
        meta = agrochamba.com.data.JobMeta(
            salarioMin = salarioMin,
            salarioMax = salarioMax,
            vacantes = vacantes,
            tipoContrato = null,
            jornada = null,
            alojamiento = alojamiento,
            transporte = transporte,
            alimentacion = alimentacion,
            requisitos = null,
            beneficios = null,
            galleryIds = galleryIds,
            facebookPostId = facebookPostId
        ),
        embedded = agrochamba.com.data.Embedded(
            featuredMedia = featuredMedia,
            terms = if (terms.isNotEmpty()) terms else null
        )
    )
}

