package agrochamba.com.ui.payment

import android.util.Log
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.WordPressApi
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import retrofit2.HttpException
import javax.inject.Inject

data class PaymentScreenState(
    val isLoading: Boolean = false,
    val loadingMessage: String = "Preparando pago...",
    val error: String? = null,
    val initPoint: String? = null,
    val preferenceId: String? = null,
    val jobId: Int? = null,
    // Resultado del pago
    val paymentFree: Boolean = false,
    val alreadyPaid: Boolean = false,
    val paymentApproved: Boolean = false,
    val paymentPending: Boolean = false,
    val paymentFailed: Boolean = false,
    val checkoutOpened: Boolean = false
)

@HiltViewModel
class PaymentViewModel @Inject constructor() : ViewModel() {

    var uiState by mutableStateOf(PaymentScreenState())
        private set

    /**
     * Crear preferencia de pago en Mercado Pago via backend.
     */
    fun createPaymentPreference(jobId: Int) {
        viewModelScope.launch {
            uiState = uiState.copy(
                isLoading = true,
                error = null,
                loadingMessage = "Preparando pago...",
                paymentFailed = false
            )
            try {
                val token = AuthManager.token
                    ?: throw Exception("Debes iniciar sesion para realizar pagos.")

                val authHeader = "Bearer $token"
                val data = mapOf<String, Any>("job_id" to jobId)

                val response = WordPressApi.retrofitService.createPaymentPreference(authHeader, data)

                if (response.success) {
                    when {
                        response.paymentFree == true -> {
                            uiState = uiState.copy(
                                isLoading = false,
                                paymentFree = true
                            )
                        }
                        response.alreadyPaid == true -> {
                            uiState = uiState.copy(
                                isLoading = false,
                                alreadyPaid = true
                            )
                        }
                        response.initPoint != null -> {
                            uiState = uiState.copy(
                                isLoading = false,
                                initPoint = response.initPoint,
                                preferenceId = response.preferenceId,
                                jobId = response.jobId
                            )
                        }
                        else -> {
                            throw Exception("Respuesta inesperada del servidor.")
                        }
                    }
                } else {
                    throw Exception(response.message ?: "Error al crear preferencia de pago.")
                }
            } catch (e: HttpException) {
                val errorMessage = when (e.code()) {
                    403 -> "No tienes permiso para realizar esta accion."
                    500 -> "Error del servidor. Intenta mas tarde."
                    else -> "Error al procesar el pago (${e.code()})"
                }
                uiState = uiState.copy(isLoading = false, error = errorMessage)
                Log.e("PaymentViewModel", "HTTP error creating preference", e)
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isLoading = false,
                    error = e.message ?: "Error inesperado."
                )
                Log.e("PaymentViewModel", "Error creating preference", e)
            }
        }
    }

    /**
     * Llamado cuando se abre el Custom Tab de checkout.
     * Inicia polling del estado de pago.
     */
    fun onCheckoutOpened() {
        uiState = uiState.copy(checkoutOpened = true)
    }

    /**
     * Verificar estado de pago despues de regresar del checkout.
     */
    fun checkPaymentStatus(jobId: Int) {
        viewModelScope.launch {
            uiState = uiState.copy(
                isLoading = true,
                loadingMessage = "Verificando pago...",
                error = null
            )
            try {
                val token = AuthManager.token ?: throw Exception("No autenticado")
                val authHeader = "Bearer $token"

                // Intentar verificar con reintentos (el webhook puede tardar)
                var attempts = 0
                val maxAttempts = 5
                var lastStatus = "pending"

                while (attempts < maxAttempts) {
                    val status = WordPressApi.retrofitService.getPaymentStatus(authHeader, jobId)

                    if (status.success) {
                        lastStatus = status.paymentStatus ?: "pending"
                        when (lastStatus) {
                            "approved" -> {
                                uiState = uiState.copy(
                                    isLoading = false,
                                    paymentApproved = true
                                )
                                return@launch
                            }
                            "rejected", "cancelled" -> {
                                uiState = uiState.copy(
                                    isLoading = false,
                                    paymentFailed = true
                                )
                                return@launch
                            }
                            "in_process", "pending" -> {
                                // Esperar y reintentar
                                attempts++
                                if (attempts < maxAttempts) {
                                    delay(2000L * attempts) // Backoff: 2s, 4s, 6s, 8s
                                }
                            }
                            "none" -> {
                                // El usuario cerro sin pagar
                                uiState = uiState.copy(
                                    isLoading = false,
                                    initPoint = uiState.initPoint // Mantener el init_point para reintentar
                                )
                                return@launch
                            }
                            else -> {
                                attempts++
                                if (attempts < maxAttempts) {
                                    delay(2000L * attempts)
                                }
                            }
                        }
                    } else {
                        attempts++
                        if (attempts < maxAttempts) {
                            delay(2000L * attempts)
                        }
                    }
                }

                // Si agotamos los intentos, mostrar como pendiente
                if (lastStatus == "pending" || lastStatus == "in_process") {
                    uiState = uiState.copy(
                        isLoading = false,
                        paymentPending = true
                    )
                } else {
                    uiState = uiState.copy(
                        isLoading = false,
                        initPoint = uiState.initPoint
                    )
                }
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isLoading = false,
                    error = "No se pudo verificar el pago: ${e.message}"
                )
                Log.e("PaymentViewModel", "Error checking payment", e)
            }
        }
    }

    fun onCheckoutError(message: String) {
        uiState = uiState.copy(error = message)
    }

    /**
     * Manejar deep link de retorno de Mercado Pago.
     */
    fun handlePaymentResult(status: String, jobId: Int) {
        when (status) {
            "success" -> {
                // Verificar con el backend
                checkPaymentStatus(jobId)
            }
            "failure" -> {
                uiState = uiState.copy(paymentFailed = true, isLoading = false)
            }
            "pending" -> {
                uiState = uiState.copy(paymentPending = true, isLoading = false)
            }
        }
    }
}
