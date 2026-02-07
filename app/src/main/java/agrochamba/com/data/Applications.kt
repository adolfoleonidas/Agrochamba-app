package agrochamba.com.data

import com.squareup.moshi.Json

/**
 * Modelos de datos para el sistema de postulaciones
 */

// ==========================================
// RESPONSES
// ==========================================

data class ApplicationResponse(
    val success: Boolean,
    val message: String,
    val application: ApplicationData? = null
)

data class ApplicationsListResponse(
    val applications: List<ApplicationData>
)

data class ApplicationActionResponse(
    val success: Boolean,
    val message: String,
    @Json(name = "new_status") val newStatus: String? = null,
    @Json(name = "status_label") val statusLabel: String? = null
)

data class ApplicationStatusResponse(
    @Json(name = "has_applied") val hasApplied: Boolean,
    val status: String? = null,
    @Json(name = "status_label") val statusLabel: String? = null,
    @Json(name = "applied_at") val appliedAt: String? = null
)

data class ApplicantsListResponse(
    val applicants: List<ApplicantData>,
    val total: Int = 0
)

// ==========================================
// DATA MODELS
// ==========================================

data class ApplicationData(
    @Json(name = "job_id") val jobId: Int,
    val status: String,
    @Json(name = "status_label") val statusLabel: String? = null,
    val message: String? = null,
    @Json(name = "applied_at") val appliedAt: String,
    val job: ApplicationJobSummary? = null
)

data class ApplicationJobSummary(
    val id: Int,
    val title: String,
    val status: String? = null,
    val date: String? = null,
    val empresa: String? = null,
    val ubicacion: String? = null,
    @Json(name = "salario_min") val salarioMin: String? = null,
    @Json(name = "salario_max") val salarioMax: String? = null
)

data class ApplicantData(
    @Json(name = "user_id") val userId: Int,
    @Json(name = "display_name") val displayName: String,
    val email: String,
    val phone: String? = null,
    val dni: String? = null,
    @Json(name = "profile_photo") val profilePhoto: String? = null,
    val status: String,
    @Json(name = "status_label") val statusLabel: String? = null,
    val message: String? = null,
    @Json(name = "applied_at") val appliedAt: String,
    @Json(name = "viewed_at") val viewedAt: String? = null
)

// ==========================================
// ESTADOS DE POSTULACIÓN
// ==========================================

enum class ApplicationStatus(val value: String, val label: String) {
    PENDING("pendiente", "Postulado"),
    VIEWED("visto", "CV Visto"),
    IN_PROCESS("en_proceso", "En Proceso"),
    INTERVIEW("entrevista", "Entrevista"),
    FINALIST("finalista", "Finalista"),
    ACCEPTED("aceptado", "Contratado"),
    REJECTED("rechazado", "No Seleccionado"),
    CANCELLED("cancelado", "Cancelado");

    fun canCancel(): Boolean = this in listOf(PENDING, VIEWED, IN_PROCESS)

    fun isTerminal(): Boolean = this in listOf(ACCEPTED, REJECTED, CANCELLED)

    companion object {
        fun fromValue(value: String): ApplicationStatus {
            return entries.find { it.value == value } ?: PENDING
        }
    }
}
