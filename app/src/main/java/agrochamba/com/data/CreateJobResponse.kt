package agrochamba.com.data

import com.squareup.moshi.Json

/**
 * Respuesta del endpoint de creación de trabajos en WordPress.
 */
data class CreateJobResponse(
    val success: Boolean,
    val message: String?,
    @Json(name = "post_id") val postId: Int?,
    val status: String?,
    @Json(name = "post_type") val postType: String? = null,
    @Json(name = "credits_balance") val creditsBalance: Int? = null,
    @Json(name = "job_tier") val jobTier: String? = null
)

