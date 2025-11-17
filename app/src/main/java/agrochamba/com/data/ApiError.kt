package agrochamba.com.data

/**
 * Molde para decodificar las respuestas de error de la API de WordPress.
 */
data class ApiErrorResponse(
    val code: String?,
    val message: String?,
    val data: ApiErrorData?
)

data class ApiErrorData(
    val status: Int?
)
