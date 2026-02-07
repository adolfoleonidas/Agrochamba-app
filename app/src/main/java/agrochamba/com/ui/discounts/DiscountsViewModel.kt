package agrochamba.com.ui.discounts

import android.util.Log
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.DiscountCategories
import agrochamba.com.data.DiscountValidationResponse
import agrochamba.com.data.MerchantDiscount
import agrochamba.com.data.RedemptionData
import agrochamba.com.data.RedemptionHistoryItem
import agrochamba.com.data.WordPressApi
import kotlinx.coroutines.launch

private const val TAG = "DiscountsViewModel"

data class DiscountsUiState(
    val isLoading: Boolean = true,
    val error: String? = null,
    val discounts: List<MerchantDiscount> = emptyList(),
    val selectedCategory: String = DiscountCategories.ALL,
    val selectedDiscount: MerchantDiscount? = null,
    val redemptionHistory: List<RedemptionHistoryItem> = emptyList(),
    // Estado de validacion (para comercios)
    val isValidating: Boolean = false,
    val validationResult: DiscountValidationResponse? = null,
    val isRedeeming: Boolean = false,
    val redemptionResult: RedemptionData? = null,
    val redemptionError: String? = null
)

class DiscountsViewModel : ViewModel() {

    var uiState by mutableStateOf(DiscountsUiState())
        private set

    init {
        loadDiscounts()
    }

    fun loadDiscounts(category: String? = null) {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)

            try {
                val token = AuthManager.token
                if (token.isNullOrBlank()) {
                    uiState = uiState.copy(
                        isLoading = false,
                        error = "Debes iniciar sesion para ver descuentos"
                    )
                    return@launch
                }

                val authHeader = "Bearer $token"
                val filterCategory = if (category == DiscountCategories.ALL) null else category

                val response = WordPressApi.retrofitService.getDiscounts(
                    token = authHeader,
                    category = filterCategory
                )

                if (response.success) {
                    uiState = uiState.copy(
                        isLoading = false,
                        discounts = response.data,
                        selectedCategory = category ?: DiscountCategories.ALL
                    )
                    Log.d(TAG, "Descuentos cargados: ${response.data.size}")
                } else {
                    uiState = uiState.copy(
                        isLoading = false,
                        error = "Error al cargar descuentos"
                    )
                }
            } catch (e: retrofit2.HttpException) {
                val errorCode = e.code()
                Log.e(TAG, "HTTP Error $errorCode: ${e.message()}")
                uiState = uiState.copy(
                    isLoading = false,
                    error = when (errorCode) {
                        401 -> "Sesion expirada. Inicia sesion nuevamente."
                        404 -> "No hay descuentos disponibles."
                        else -> "Error al cargar descuentos: $errorCode"
                    }
                )
            } catch (e: Exception) {
                Log.e(TAG, "Error loading discounts", e)
                uiState = uiState.copy(
                    isLoading = false,
                    error = "Error de conexion: ${e.localizedMessage}"
                )
            }
        }
    }

    fun selectCategory(category: String) {
        if (category != uiState.selectedCategory) {
            loadDiscounts(category)
        }
    }

    fun selectDiscount(discount: MerchantDiscount) {
        uiState = uiState.copy(selectedDiscount = discount)
    }

    fun clearSelectedDiscount() {
        uiState = uiState.copy(selectedDiscount = null)
    }

    /**
     * Validar usuario para descuento (comercio escanea el QR del usuario)
     * El QR contiene el DNI del usuario
     */
    fun validateUserForDiscount(discountId: Int, userDni: String) {
        viewModelScope.launch {
            uiState = uiState.copy(
                isValidating = true,
                validationResult = null,
                redemptionError = null
            )

            try {
                val token = AuthManager.token
                if (token.isNullOrBlank()) {
                    uiState = uiState.copy(
                        isValidating = false,
                        redemptionError = "Debes iniciar sesion"
                    )
                    return@launch
                }

                val authHeader = "Bearer $token"
                val response = WordPressApi.retrofitService.validateDiscount(
                    token = authHeader,
                    discountId = discountId,
                    data = mapOf("user_dni" to userDni)
                )

                uiState = uiState.copy(
                    isValidating = false,
                    validationResult = response
                )
                Log.d(TAG, "Validacion: canRedeem=${response.canRedeem}, user=${response.userName}")
            } catch (e: Exception) {
                Log.e(TAG, "Error validating discount", e)
                uiState = uiState.copy(
                    isValidating = false,
                    redemptionError = "Error al validar: ${e.localizedMessage}"
                )
            }
        }
    }

    /**
     * Confirmar canje del descuento (comercio confirma)
     */
    fun redeemDiscount(discountId: Int, userDni: String) {
        viewModelScope.launch {
            uiState = uiState.copy(isRedeeming = true, redemptionError = null)

            try {
                val token = AuthManager.token
                if (token.isNullOrBlank()) {
                    uiState = uiState.copy(
                        isRedeeming = false,
                        redemptionError = "Debes iniciar sesion"
                    )
                    return@launch
                }

                val authHeader = "Bearer $token"
                val response = WordPressApi.retrofitService.redeemDiscount(
                    token = authHeader,
                    discountId = discountId,
                    data = mapOf("user_dni" to userDni)
                )

                if (response.success) {
                    uiState = uiState.copy(
                        isRedeeming = false,
                        redemptionResult = response.data
                    )
                    Log.d(TAG, "Descuento canjeado exitosamente: ${response.data?.redemptionId}")
                } else {
                    uiState = uiState.copy(
                        isRedeeming = false,
                        redemptionError = response.message
                    )
                }
            } catch (e: Exception) {
                Log.e(TAG, "Error redeeming discount", e)
                uiState = uiState.copy(
                    isRedeeming = false,
                    redemptionError = "Error al canjear: ${e.localizedMessage}"
                )
            }
        }
    }

    fun loadRedemptionHistory() {
        viewModelScope.launch {
            try {
                val token = AuthManager.token ?: return@launch
                val authHeader = "Bearer $token"
                val response = WordPressApi.retrofitService.getMyRedemptions(authHeader)
                if (response.success) {
                    uiState = uiState.copy(redemptionHistory = response.data)
                }
            } catch (e: Exception) {
                Log.e(TAG, "Error loading redemption history", e)
            }
        }
    }

    fun clearValidation() {
        uiState = uiState.copy(
            validationResult = null,
            redemptionResult = null,
            redemptionError = null,
            isValidating = false,
            isRedeeming = false
        )
    }

    fun refresh() {
        loadDiscounts(uiState.selectedCategory)
    }
}
