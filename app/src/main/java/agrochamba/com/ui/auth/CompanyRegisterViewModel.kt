package agrochamba.com.ui.auth

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.ApiErrorResponse
import agrochamba.com.data.AuthManager
import agrochamba.com.data.WordPressApi
import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import kotlinx.coroutines.launch

data class CompanyRegisterState(
    val isLoading: Boolean = false,
    val error: String? = null,
    val registrationSuccess: Boolean = false
)

class CompanyRegisterViewModel : ViewModel() {

    var uiState by mutableStateOf(CompanyRegisterState())
        private set

    private val moshi = Moshi.Builder().add(KotlinJsonAdapterFactory()).build()
    private val errorAdapter = moshi.adapter(ApiErrorResponse::class.java)

    fun registerCompany(
        username: String,
        email: String,
        password: String,
        ruc: String,
        razonSocial: String
    ) {
        uiState = uiState.copy(isLoading = true, error = null)
        viewModelScope.launch {
            try {
                if (username.isBlank() || email.isBlank() || password.isBlank() || ruc.isBlank() || razonSocial.isBlank()) {
                    throw Exception("Todos los campos son obligatorios.")
                }

                val companyData = mapOf(
                    "username" to username,
                    "email" to email,
                    "password" to password,
                    "ruc" to ruc,
                    "razon_social" to razonSocial
                )
                
                val response = WordPressApi.retrofitService.registerCompany(companyData)
                
                AuthManager.login(response)

                uiState = uiState.copy(isLoading = false, registrationSuccess = true)

            } catch (e: retrofit2.HttpException) {
                val errorBody = e.response()?.errorBody()?.string()
                android.util.Log.e("CompanyRegisterViewModel", "Error HTTP: ${e.code()}, Body: $errorBody")
                
                val errorMessage = if (errorBody != null) {
                    try {
                        val errorJson = org.json.JSONObject(errorBody)
                        val code = errorJson.optString("code", "")
                        val message = errorJson.optString("message", "")
                        
                        when {
                            e.code() == 404 -> "El endpoint de registro no está disponible. Verifica que el plugin esté activo."
                            message.isNotEmpty() -> message
                            code == "rest_user_exists" -> "El nombre de usuario ya está en uso."
                            code == "rest_email_exists" -> "El email ya está registrado."
                            code == "rest_invalid_param" -> "Datos inválidos. Verifica los campos."
                            else -> "Error al registrar: ${e.code()}"
                        }
                    } catch (ex: Exception) {
                        if (e.code() == 404) {
                            "Error 404: El endpoint no está disponible. Verifica que el plugin esté activo en WordPress."
                        } else {
                            "Error al registrar: ${e.code()}"
                        }
                    }
                } else {
                    if (e.code() == 404) {
                        "Error 404: El endpoint de registro no está disponible."
                    } else {
                        "Error al registrar: ${e.code()}"
                    }
                }
                
                uiState = uiState.copy(isLoading = false, error = errorMessage)
            } catch (e: Exception) {
                android.util.Log.e("CompanyRegisterViewModel", "Error general: ${e.message}", e)
                uiState = uiState.copy(isLoading = false, error = e.message ?: "Ocurrió un error inesperado.")
            }
        }
    }
}