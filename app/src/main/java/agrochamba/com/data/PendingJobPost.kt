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
    val ubicacion: String? = null,
    val cultivo: String? = null,
    val empresa: String? = null,
    @Json(name = "tipo_puesto") val tipoPuesto: String? = null,
    @Json(name = "salario_min") val salarioMin: String? = null,
    @Json(name = "salario_max") val salarioMax: String? = null,
    val vacantes: String? = null
) {
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

