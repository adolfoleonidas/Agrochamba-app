package agrochamba.com.data

import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class PaginationInfo(
    val total: Int,
    val total_pages: Int,
    val current_page: Int,
    val per_page: Int,
    val has_next_page: Boolean,
    val has_prev_page: Boolean
)

@JsonClass(generateAdapter = true)
data class PaginatedResponse<T>(
    val data: List<T>,
    val pagination: PaginationInfo
)

