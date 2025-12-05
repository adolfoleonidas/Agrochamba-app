package agrochamba.com.data

import com.squareup.moshi.Json

/**
 * Respuesta del endpoint /agrochamba/v1/me/jobs
 * WordPress retorna: { success: true, jobs: [...], total: X, total_pages: Y, current_page: Z }
 */
data class MyJobsResponse(
    val success: Boolean,
    val jobs: List<MyJobResponse>,
    val total: Int,
    @Json(name = "total_pages") val totalPages: Int,
    @Json(name = "current_page") val currentPage: Int
)

