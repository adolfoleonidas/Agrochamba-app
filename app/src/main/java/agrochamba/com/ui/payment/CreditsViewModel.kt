package agrochamba.com.ui.payment

import android.util.Log
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.CreditPackage
import agrochamba.com.data.WordPressApi
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

data class CreditsScreenState(
    val isLoading: Boolean = true,
    val error: String? = null,
    val balance: Int = 0,
    val isUnlimited: Boolean = false,
    val packages: List<CreditPackage> = emptyList(),
    val costs: Map<String, Int> = mapOf(
        "publish_job" to 5,
        "ai_enhance" to 1,
        "ai_title" to 1,
        "ai_ocr" to 2
    ),
    val purchasingPackageId: String? = null,
    val checkoutOpened: Boolean = false,
    val purchaseSuccess: Boolean = false
)

@HiltViewModel
class CreditsViewModel @Inject constructor() : ViewModel() {

    var uiState by mutableStateOf(CreditsScreenState())
        private set

    fun loadData() {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)
            try {
                val token = AuthManager.token ?: throw Exception("No autenticado")
                val authHeader = "Bearer $token"

                // Cargar balance y paquetes en paralelo
                val balanceResponse = WordPressApi.retrofitService.getCreditsBalance(authHeader)
                val packagesResponse = WordPressApi.retrofitService.getCreditPackages(authHeader)

                val costs = if (balanceResponse.costs != null) {
                    mapOf(
                        "publish_job" to balanceResponse.costs.publishJob,
                        "ai_enhance" to balanceResponse.costs.aiEnhance,
                        "ai_title" to balanceResponse.costs.aiTitle,
                        "ai_ocr" to balanceResponse.costs.aiOcr
                    )
                } else {
                    uiState.costs
                }

                uiState = uiState.copy(
                    isLoading = false,
                    balance = balanceResponse.balance,
                    isUnlimited = balanceResponse.isUnlimited,
                    packages = packagesResponse.packages,
                    costs = costs
                )
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isLoading = false,
                    error = "Error al cargar: ${e.message}"
                )
                Log.e("CreditsViewModel", "Error loading data", e)
            }
        }
    }

    fun purchasePackage(packageId: String, onInitPoint: (String) -> Unit) {
        viewModelScope.launch {
            uiState = uiState.copy(purchasingPackageId = packageId, error = null)
            try {
                val token = AuthManager.token ?: throw Exception("No autenticado")
                val authHeader = "Bearer $token"
                val data = mapOf<String, Any>("package_id" to packageId)

                val response = WordPressApi.retrofitService.purchaseCredits(authHeader, data)

                if (response.success && response.initPoint != null) {
                    uiState = uiState.copy(
                        purchasingPackageId = null,
                        checkoutOpened = true
                    )
                    onInitPoint(response.initPoint)
                } else {
                    throw Exception(response.message ?: "Error al crear el pago")
                }
            } catch (e: Exception) {
                uiState = uiState.copy(
                    purchasingPackageId = null,
                    error = e.message ?: "Error al procesar la compra"
                )
                Log.e("CreditsViewModel", "Error purchasing package", e)
            }
        }
    }

    fun onPurchaseSuccess() {
        uiState = uiState.copy(
            checkoutOpened = false,
            purchaseSuccess = true
        )
        refreshBalance()
    }

    fun onPurchaseFailure() {
        uiState = uiState.copy(
            checkoutOpened = false,
            error = "El pago no se completo. Puedes intentarlo nuevamente."
        )
    }

    fun refreshBalance() {
        viewModelScope.launch {
            try {
                val token = AuthManager.token ?: return@launch
                val authHeader = "Bearer $token"
                val response = WordPressApi.retrofitService.getCreditsBalance(authHeader)
                uiState = uiState.copy(
                    balance = response.balance,
                    isUnlimited = response.isUnlimited
                )
            } catch (e: Exception) {
                Log.e("CreditsViewModel", "Error refreshing balance", e)
            }
        }
    }
}
