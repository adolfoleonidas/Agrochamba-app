package agrochamba.com.data

import com.squareup.moshi.Json

/**
 * Modelo para trabajos pendientes de moderación
 * Extiende JobPost con campos adicionales específicos de moderación
 */
data class PendingJobPost(
    val id: Int,
    val link: String?,
    val date: String?,
    val title: Title?,
    val content: Content?,
    val excerpt: Content?,
    val meta: JobMeta?,
    @Json(name = "_embedded") val embedded: Embedded?,
    // Campos adicionales para moderación
    @Json(name = "author_name") val authorName: String? = null,
    @Json(name = "author_email") val authorEmail: String? = null,
    val ubicacion: EmpresaData? = null, // Puede ser String o objeto TaxonomyData
    val cultivo: EmpresaData? = null, // Puede ser String o objeto TaxonomyData
    val empresa: EmpresaData? = null, // Puede ser String o objeto TaxonomyData
    @Json(name = "tipo_puesto") val tipoPuesto: EmpresaData? = null, // Puede ser String o objeto TaxonomyData
    @Json(name = "salario_min") val salarioMin: String? = null,
    @Json(name = "salario_max") val salarioMax: String? = null,
    val vacantes: String? = null,
    // Información sobre solicitud de publicación en Facebook
    @Json(name = "facebook_publish_requested") val facebookPublishRequested: Boolean? = false,
    @Json(name = "facebook_use_link_preview") val facebookUseLinkPreview: Boolean? = false,
    @Json(name = "facebook_shorten_content") val facebookShortenContent: Boolean? = false
) {
    /**
     * Obtener el nombre de la empresa como string
     */
    val empresaName: String?
        get() = empresa?.name

    /**
     * Obtener ubicación como string
     */
    val ubicacionName: String?
        get() = ubicacion?.name

    /**
     * Obtener cultivo como string
     */
    val cultivoName: String?
        get() = cultivo?.name

    /**
     * Obtener tipo de puesto como string
     */
    val tipoPuestoName: String?
        get() = tipoPuesto?.name
    // Función para convertir a JobPost normal
    fun toJobPost(): JobPost {
        return JobPost(
            id = id,
            link = link,
            date = date,
            title = title,
            content = content,
            excerpt = excerpt,
            meta = meta,
            embedded = embedded
        )
    }
}

