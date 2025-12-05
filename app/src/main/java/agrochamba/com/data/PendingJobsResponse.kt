package agrochamba.com.data

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

/**
 * Respuesta del endpoint /agrochamba/v1/admin/pending-jobs
 * WordPress retorna: { success: true, jobs: [...], total: X }
 */
@JsonClass(generateAdapter = true)
data class PendingJobsResponse(
    val success: Boolean,
    val jobs: List<PendingJobPost>,
    val total: Int
)

