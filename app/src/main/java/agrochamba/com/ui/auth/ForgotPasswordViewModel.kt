package agrochamba.com.ui.auth

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.ApiErrorResponse
import agrochamba.com.data.WordPressApi
import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import kotlinx.coroutines.launch

enum class ResetStep {
    AskForCode,
    SubmitCode
}

data class ForgotPasswordScreenState(
    val isLoading: Boolean = false,
    val error: String? = null,
    val successMessage: String? = null,
    val currentStep: ResetStep = ResetStep.AskForCode,
    val passwordResetSuccess: Boolean = false
)

class ForgotPasswordViewModel : ViewModel() {

    var uiState by mutableStateOf(ForgotPasswordScreenState())
        private set

    private val moshi = Moshi.Builder().add(KotlinJsonAdapterFactory()).build()
    private val errorAdapter = moshi.adapter(ApiErrorResponse::class.java)

    fun requestPasswordReset(userLogin: String) {
        uiState = uiState.copy(isLoading = true, error = null, successMessage = null)
        viewModelScope.launch {
            try {
                if (userLogin.isBlank()) throw Exception("El campo no puede estar vacío.")

                val userData = mapOf("user_login" to userLogin)
                val response = WordPressApi.retrofitService.forgotPassword(userData)

                if (response.isSuccessful) {
                    uiState = uiState.copy(
                        isLoading = false,
                        successMessage = "Si el usuario existe, recibirás un correo con el código.",
                        currentStep = ResetStep.SubmitCode
                    )
                } else {
                    throw Exception("No se pudo procesar la solicitud.")
                }

            } catch (e: Exception) {
                uiState = uiState.copy(isLoading = false, error = e.message)
            }
        }
    }

    fun resetPassword(userLogin: String, code: String, newPassword: String) {
        uiState = uiState.copy(isLoading = true, error = null, successMessage = null)
        viewModelScope.launch {
            try {
                if (code.isBlank() || newPassword.isBlank()) throw Exception("El código y la nueva contraseña son obligatorios.")

                val resetData = mapOf(
                    "user_login" to userLogin,
                    "code" to code,
                    "password" to newPassword
                )
                val response = WordPressApi.retrofitService.resetPassword(resetData)

                if (response.isSuccessful) {
                    uiState = uiState.copy(
                        isLoading = false,
                        passwordResetSuccess = true
                    )
                } else {
                    val errorBody = response.errorBody()?.string()
                    val errorMessage = if (errorBody != null) {
                        try {
                            errorAdapter.fromJson(errorBody)?.message ?: "Error de servidor"
                        } catch (e: Exception) {
                            "Error de servidor"
                        }
                    } else {
                        "Error desconocido"
                    }
                    throw Exception(errorMessage)
                }

            } catch (e: Exception) {
                uiState = uiState.copy(isLoading = false, error = e.message)
            }
        }
    }
}