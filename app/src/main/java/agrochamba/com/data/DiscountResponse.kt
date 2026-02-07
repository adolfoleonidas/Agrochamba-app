package agrochamba.com.data

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

/**
 * Response para lista de descuentos disponibles
 */
@JsonClass(generateAdapter = true)
data class DiscountsListResponse(
    val success: Boolean,
    val data: List<MerchantDiscount>,
    val total: Int
)

/**
 * Descuento de un comercio aliado
 */
@JsonClass(generateAdapter = true)
data class MerchantDiscount(
    val id: Int,
    @Json(name = "merchant_name") val merchantName: String,
    @Json(name = "merchant_logo") val merchantLogo: String? = null,
    @Json(name = "merchant_address") val merchantAddress: String? = null,
    @Json(name = "merchant_phone") val merchantPhone: String? = null,
    val category: String,
    @Json(name = "category_label") val categoryLabel: String,
    val title: String,
    val description: String,
    @Json(name = "discount_percentage") val discountPercentage: Int,
    @Json(name = "discount_type") val discountType: String = "percentage", // percentage, fixed, 2x1
    @Json(name = "discount_value") val discountValue: String? = null,
    val conditions: String? = null,
    @Json(name = "valid_from") val validFrom: String? = null,
    @Json(name = "valid_until") val validUntil: String? = null,
    @Json(name = "is_active") val isActive: Boolean = true,
    @Json(name = "max_uses_per_user") val maxUsesPerUser: Int = 1,
    @Json(name = "times_redeemed") val timesRedeemed: Int = 0,
    @Json(name = "image_url") val imageUrl: String? = null
)

/**
 * Response para validar/canjear un descuento
 */
@JsonClass(generateAdapter = true)
data class RedeemDiscountResponse(
    val success: Boolean,
    val message: String,
    val data: RedemptionData? = null
)

@JsonClass(generateAdapter = true)
data class RedemptionData(
    @Json(name = "redemption_id") val redemptionId: Int,
    @Json(name = "discount_id") val discountId: Int,
    @Json(name = "user_id") val userId: Int,
    @Json(name = "user_name") val userName: String,
    @Json(name = "user_dni") val userDni: String? = null,
    @Json(name = "discount_title") val discountTitle: String,
    @Json(name = "merchant_name") val merchantName: String,
    @Json(name = "redeemed_at") val redeemedAt: String
)

/**
 * Response para verificar si un usuario puede canjear un descuento
 */
@JsonClass(generateAdapter = true)
data class DiscountValidationResponse(
    val success: Boolean,
    @Json(name = "can_redeem") val canRedeem: Boolean,
    @Json(name = "user_name") val userName: String? = null,
    @Json(name = "user_dni") val userDni: String? = null,
    @Json(name = "user_photo") val userPhoto: String? = null,
    val message: String? = null,
    @Json(name = "times_used") val timesUsed: Int = 0,
    @Json(name = "max_uses") val maxUses: Int = 1
)

/**
 * Response para historial de canjes del usuario
 */
@JsonClass(generateAdapter = true)
data class RedemptionHistoryResponse(
    val success: Boolean,
    val data: List<RedemptionHistoryItem>,
    val total: Int
)

@JsonClass(generateAdapter = true)
data class RedemptionHistoryItem(
    val id: Int,
    @Json(name = "discount_id") val discountId: Int,
    @Json(name = "discount_title") val discountTitle: String,
    @Json(name = "merchant_name") val merchantName: String,
    @Json(name = "merchant_logo") val merchantLogo: String? = null,
    @Json(name = "discount_percentage") val discountPercentage: Int,
    @Json(name = "redeemed_at") val redeemedAt: String
)

/**
 * Categorías de descuentos disponibles
 */
object DiscountCategories {
    const val RESTAURANT = "restaurant"
    const val HOTEL = "hotel"
    const val STORE = "store"
    const val TRANSPORT = "transport"
    const val HEALTH = "health"
    const val ENTERTAINMENT = "entertainment"
    const val ALL = "all"

    fun getLabel(category: String): String = when (category) {
        RESTAURANT -> "Restaurantes"
        HOTEL -> "Hoteles"
        STORE -> "Tiendas"
        TRANSPORT -> "Transporte"
        HEALTH -> "Salud"
        ENTERTAINMENT -> "Entretenimiento"
        ALL -> "Todos"
        else -> category.replaceFirstChar { it.uppercase() }
    }

    fun getEmoji(category: String): String = when (category) {
        RESTAURANT -> "\uD83C\uDF7D"
        HOTEL -> "\uD83C\uDFE8"
        STORE -> "\uD83D\uDED2"
        TRANSPORT -> "\uD83D\uDE8C"
        HEALTH -> "\u2695"
        ENTERTAINMENT -> "\uD83C\uDFAC"
        else -> "\uD83C\uDF81"
    }
}
