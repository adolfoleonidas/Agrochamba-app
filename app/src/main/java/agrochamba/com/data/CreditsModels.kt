package agrochamba.com.data

import com.squareup.moshi.Json

/**
 * Saldo de créditos del usuario.
 */
data class CreditsBalanceResponse(
    val success: Boolean,
    val balance: Int, // -1 = ilimitado (admin)
    @Json(name = "is_unlimited") val isUnlimited: Boolean = false,
    val costs: CreditCosts? = null,
    @Json(name = "free_post") val freePost: FreePostStatus? = null
)

data class FreePostStatus(
    val allowed: Boolean = false,
    val used: Int = 0,
    val limit: Int = 1,
    val remaining: Int = 0
)

data class CreditCosts(
    @Json(name = "publish_job") val publishJob: Int = 5,
    @Json(name = "ai_enhance") val aiEnhance: Int = 1,
    @Json(name = "ai_title") val aiTitle: Int = 1,
    @Json(name = "ai_ocr") val aiOcr: Int = 2
)

/**
 * Paquetes de créditos disponibles para compra.
 */
data class CreditPackagesResponse(
    val success: Boolean,
    val packages: List<CreditPackage> = emptyList()
)

data class CreditPackage(
    val id: String,
    val credits: Int,
    val price: Double,
    val currency: String = "PEN",
    val label: String,
    val description: String,
    val popular: Boolean = false
)

/**
 * Respuesta al comprar un paquete de créditos (genera preferencia MP).
 */
data class CreditPurchaseResponse(
    val success: Boolean,
    @Json(name = "init_point") val initPoint: String? = null,
    @Json(name = "preference_id") val preferenceId: String? = null,
    @Json(name = "package") val creditPackage: CreditPackage? = null,
    val message: String? = null
)

/**
 * Historial de transacciones de créditos.
 */
data class CreditHistoryResponse(
    val success: Boolean,
    val history: List<CreditTransaction> = emptyList(),
    val balance: Int = 0
)

data class CreditTransaction(
    @Json(name = "user_id") val userId: Int = 0,
    val type: String = "",         // credit, debit, admin_free
    val amount: Int = 0,
    val balance: Int = 0,
    val reason: String = "",
    @Json(name = "reference_id") val referenceId: String = "",
    val date: String = ""
)
