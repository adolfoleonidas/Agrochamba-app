package agrochamba.com.data

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

/**
 * Modelos de datos para el sistema de moderación de trabajos
 */

// ==========================================
// RESPUESTAS DE MODERACIÓN
// ==========================================

@JsonClass(generateAdapter = true)
data class AdminJobsListResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "data") val data: List<AdminJobItem>,
    @Json(name = "pagination") val pagination: PaginationInfo
)

@JsonClass(generateAdapter = true)
data class PaginationInfo(
    @Json(name = "page") val page: Int,
    @Json(name = "per_page") val perPage: Int,
    @Json(name = "total") val total: Int,
    @Json(name = "total_pages") val totalPages: Int
)

@JsonClass(generateAdapter = true)
data class AdminJobItem(
    @Json(name = "id") val id: Int,
    @Json(name = "title") val title: String,
    @Json(name = "status") val status: String,
    @Json(name = "status_label") val statusLabel: String,
    @Json(name = "date") val date: String,
    @Json(name = "date_formatted") val dateFormatted: String,
    @Json(name = "modified") val modified: String? = null,
    @Json(name = "author") val author: JobAuthor,
    @Json(name = "permalink") val permalink: String? = null,
    @Json(name = "edit_link") val editLink: String? = null,
    @Json(name = "featured_image") val featuredImage: FeaturedImageInfo? = null,
    @Json(name = "empresa") val empresa: JobEmpresaInfo? = null,
    @Json(name = "ubicacion") val ubicacion: UbicacionCompleta? = null,
    @Json(name = "moderation") val moderation: ModerationInfo? = null,
    @Json(name = "ai_moderation") val aiModeration: Map<String, Any?>? = null
)

@JsonClass(generateAdapter = true)
data class JobAuthor(
    @Json(name = "id") val id: Int,
    @Json(name = "name") val name: String,
    @Json(name = "email") val email: String? = null
)

@JsonClass(generateAdapter = true)
data class FeaturedImageInfo(
    @Json(name = "id") val id: Int,
    @Json(name = "thumbnail") val thumbnail: String? = null,
    @Json(name = "medium") val medium: String? = null,
    @Json(name = "full") val full: String? = null
)

@JsonClass(generateAdapter = true)
data class JobEmpresaInfo(
    @Json(name = "id") val id: Int,
    @Json(name = "name") val name: String,
    @Json(name = "logo") val logo: String? = null
)

@JsonClass(generateAdapter = true)
data class ModerationInfo(
    @Json(name = "status") val status: String? = null,
    @Json(name = "approved_by") val approvedBy: Int? = null,
    @Json(name = "approved_date") val approvedDate: String? = null,
    @Json(name = "rejected_by") val rejectedBy: Int? = null,
    @Json(name = "rejected_date") val rejectedDate: String? = null,
    @Json(name = "rejection_reason") val rejectionReason: String? = null
)

// ==========================================
// DETALLE DE TRABAJO PARA ADMIN
// ==========================================

@JsonClass(generateAdapter = true)
data class AdminJobDetailResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "data") val data: AdminJobDetail,
    @Json(name = "message") val message: String? = null
)

@JsonClass(generateAdapter = true)
data class AdminJobDetail(
    @Json(name = "id") val id: Int,
    @Json(name = "title") val title: String,
    @Json(name = "content") val content: String? = null,
    @Json(name = "content_html") val contentHtml: String? = null,
    @Json(name = "excerpt") val excerpt: String? = null,
    @Json(name = "status") val status: String,
    @Json(name = "status_label") val statusLabel: String,
    @Json(name = "date") val date: String,
    @Json(name = "date_formatted") val dateFormatted: String,
    @Json(name = "modified") val modified: String? = null,
    @Json(name = "author") val author: JobAuthor,
    @Json(name = "permalink") val permalink: String? = null,
    @Json(name = "edit_link") val editLink: String? = null,
    @Json(name = "featured_image") val featuredImage: FeaturedImageInfo? = null,
    @Json(name = "empresa") val empresa: JobEmpresaInfo? = null,
    @Json(name = "ubicacion") val ubicacion: UbicacionCompleta? = null,
    @Json(name = "moderation") val moderation: ModerationInfo? = null,
    @Json(name = "ai_moderation") val aiModeration: Map<String, Any?>? = null,
    @Json(name = "meta") val meta: JobMetaAdmin? = null,
    @Json(name = "gallery") val gallery: List<FeaturedImageInfo>? = null,
    @Json(name = "taxonomies") val taxonomies: JobTaxonomies? = null
)

@JsonClass(generateAdapter = true)
data class JobMetaAdmin(
    @Json(name = "salario_min") val salarioMin: Int? = null,
    @Json(name = "salario_max") val salarioMax: Int? = null,
    @Json(name = "vacantes") val vacantes: Int? = null,
    @Json(name = "fecha_inicio") val fechaInicio: String? = null,
    @Json(name = "fecha_fin") val fechaFin: String? = null,
    @Json(name = "duracion_dias") val duracionDias: Int? = null,
    @Json(name = "tipo_contrato") val tipoContrato: String? = null,
    @Json(name = "jornada") val jornada: String? = null,
    @Json(name = "requisitos") val requisitos: String? = null,
    @Json(name = "beneficios") val beneficios: String? = null,
    @Json(name = "experiencia") val experiencia: String? = null,
    @Json(name = "genero") val genero: String? = null,
    @Json(name = "edad_minima") val edadMinima: Int? = null,
    @Json(name = "edad_maxima") val edadMaxima: Int? = null,
    @Json(name = "estado") val estado: String? = null,
    @Json(name = "contacto_whatsapp") val contactoWhatsapp: String? = null,
    @Json(name = "contacto_email") val contactoEmail: String? = null,
    @Json(name = "google_maps_url") val googleMapsUrl: String? = null,
    @Json(name = "alojamiento") val alojamiento: Boolean? = null,
    @Json(name = "transporte") val transporte: Boolean? = null,
    @Json(name = "alimentacion") val alimentacion: Boolean? = null
)

@JsonClass(generateAdapter = true)
data class JobTaxonomies(
    @Json(name = "ubicacion") val ubicacion: List<String>? = null,
    @Json(name = "cultivo") val cultivo: List<String>? = null,
    @Json(name = "tipo_puesto") val tipoPuesto: List<String>? = null
)

// ==========================================
// ELIMINAR TRABAJO
// ==========================================

@JsonClass(generateAdapter = true)
data class AdminJobDeleteResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "message") val message: String,
    @Json(name = "deleted_job") val deletedJob: DeletedJobInfo? = null
)

@JsonClass(generateAdapter = true)
data class DeletedJobInfo(
    @Json(name = "id") val id: Int,
    @Json(name = "title") val title: String
)

// ==========================================
// ESTADÍSTICAS DE MODERACIÓN
// ==========================================

@JsonClass(generateAdapter = true)
data class ModerationStatsResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "data") val data: ModerationStatsData
)

@JsonClass(generateAdapter = true)
data class ModerationStatsData(
    @Json(name = "counts") val counts: JobCounts,
    @Json(name = "recent_approved") val recentApproved: List<RecentModeratedJob>? = null,
    @Json(name = "recent_rejected") val recentRejected: List<RecentRejectedJob>? = null,
    @Json(name = "oldest_pending") val oldestPending: List<OldestPendingJob>? = null
)

@JsonClass(generateAdapter = true)
data class JobCounts(
    @Json(name = "pending") val pending: Int,
    @Json(name = "publish") val publish: Int,
    @Json(name = "draft") val draft: Int,
    @Json(name = "trash") val trash: Int,
    @Json(name = "total") val total: Int
)

@JsonClass(generateAdapter = true)
data class RecentModeratedJob(
    @Json(name = "id") val id: Int,
    @Json(name = "title") val title: String,
    @Json(name = "approved_date") val approvedDate: String? = null
)

@JsonClass(generateAdapter = true)
data class RecentRejectedJob(
    @Json(name = "id") val id: Int,
    @Json(name = "title") val title: String,
    @Json(name = "rejection_reason") val rejectionReason: String? = null
)

@JsonClass(generateAdapter = true)
data class OldestPendingJob(
    @Json(name = "id") val id: Int,
    @Json(name = "title") val title: String,
    @Json(name = "created_date") val createdDate: String,
    @Json(name = "waiting_hours") val waitingHours: Int
)

// ==========================================
// ACCIÓN MASIVA
// ==========================================

@JsonClass(generateAdapter = true)
data class BulkActionResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "message") val message: String,
    @Json(name = "results") val results: BulkActionResults? = null
)

@JsonClass(generateAdapter = true)
data class BulkActionResults(
    @Json(name = "success") val success: List<Int>,
    @Json(name = "failed") val failed: List<BulkActionFailed>? = null
)

@JsonClass(generateAdapter = true)
data class BulkActionFailed(
    @Json(name = "id") val id: Int,
    @Json(name = "reason") val reason: String
)

// ==========================================
// RESPUESTA DE TRABAJOS PENDIENTES (EXISTENTE)
// ==========================================

@JsonClass(generateAdapter = true)
data class PendingJobsResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "data") val data: List<AdminJobItem>,
    @Json(name = "total") val total: Int? = null
)
