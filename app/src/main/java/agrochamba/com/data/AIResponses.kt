package agrochamba.com.data

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

/**
 * Información de uso de IA (límites y usos)
 */
@JsonClass(generateAdapter = true)
data class AIUsageInfo(
    @Json(name = "remaining") val remaining: Int = 0,
    @Json(name = "used") val used: Int = 0,
    @Json(name = "limit") val limit: Int = 3,
    @Json(name = "is_premium") val isPremium: Boolean = false
)

/**
 * Respuesta del endpoint de estado de uso de IA
 */
@JsonClass(generateAdapter = true)
data class AIUsageStatusResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "allowed") val allowed: Boolean = false,
    @Json(name = "remaining") val remaining: Int = 0,
    @Json(name = "used") val used: Int = 0,
    @Json(name = "limit") val limit: Int = 3,
    @Json(name = "is_premium") val isPremium: Boolean = false,
    @Json(name = "message") val message: String? = null
)

/**
 * Respuesta del endpoint de mejora de texto con IA
 */
@JsonClass(generateAdapter = true)
data class AIEnhanceTextResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "original_text") val originalText: String? = null,
    @Json(name = "enhanced_text") val enhancedText: String? = null,
    @Json(name = "tokens_used") val tokensUsed: Int? = null,
    @Json(name = "usage") val usage: AIUsageInfo? = null,
    @Json(name = "message") val message: String? = null,
    @Json(name = "code") val code: String? = null
)

/**
 * Respuesta del endpoint de generación de título SEO
 */
@JsonClass(generateAdapter = true)
data class AIGenerateTitleResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "title") val title: String? = null,
    @Json(name = "character_count") val characterCount: Int? = null,
    @Json(name = "seo_optimal") val seoOptimal: Boolean? = null,
    @Json(name = "usage") val usage: AIUsageInfo? = null,
    @Json(name = "message") val message: String? = null,
    @Json(name = "code") val code: String? = null
)

/**
 * Respuesta del endpoint de OCR (Extracción de texto de imagen)
 */
@JsonClass(generateAdapter = true)
data class AIOCRResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "extracted_text") val extractedText: String? = null,
    @Json(name = "enhanced_text") val enhancedText: String? = null,
    @Json(name = "usage") val usage: AIUsageInfo? = null,
    @Json(name = "message") val message: String? = null,
    @Json(name = "code") val code: String? = null
)

