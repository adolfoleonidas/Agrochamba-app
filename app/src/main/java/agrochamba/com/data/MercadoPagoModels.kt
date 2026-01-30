package agrochamba.com.data

import com.squareup.moshi.Json

/**
 * Respuesta al crear una preferencia de pago en Mercado Pago.
 */
data class PaymentPreferenceResponse(
    val success: Boolean,
    @Json(name = "init_point") val initPoint: String? = null,
    @Json(name = "preference_id") val preferenceId: String? = null,
    @Json(name = "job_id") val jobId: Int? = null,
    val amount: Double? = null,
    val currency: String? = null,
    val message: String? = null,
    // Campos para admin (no requiere pago)
    @Json(name = "payment_free") val paymentFree: Boolean? = null,
    // Campos para ya pagado
    @Json(name = "already_paid") val alreadyPaid: Boolean? = null
)

/**
 * Respuesta al consultar el estado de pago de un trabajo.
 */
data class PaymentStatusResponse(
    val success: Boolean,
    @Json(name = "job_id") val jobId: Int? = null,
    @Json(name = "payment_status") val paymentStatus: String? = null,
    @Json(name = "payment_id") val paymentId: String? = null,
    val amount: Double? = null,
    @Json(name = "payment_date") val paymentDate: String? = null,
    @Json(name = "post_status") val postStatus: String? = null
)

/**
 * Configuracion publica de Mercado Pago.
 */
data class PaymentConfigResponse(
    val success: Boolean,
    @Json(name = "public_key") val publicKey: String? = null,
    @Json(name = "job_price") val jobPrice: Double? = null,
    val currency: String? = null,
    @Json(name = "currency_symbol") val currencySymbol: String? = null,
    @Json(name = "is_sandbox") val isSandbox: Boolean? = null,
    @Json(name = "payment_required") val paymentRequired: Boolean? = null
)
