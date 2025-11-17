package agrochamba.com.ui.auth

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.WordPressApi
import kotlinx.coroutines.launch
import org.json.JSONObject
import retrofit2.HttpException

data class RegisterScreenState(
    val isLoading: Boolean = false,
    val error: String? = null,
    val registrationSuccess: Boolean = false
)

class RegisterViewModel : ViewModel() {

    var uiState by mutableStateOf(RegisterScreenState())
        private set

    fun register(username: String, email: String, password: String) {
        uiState = uiState.copy(isLoading = true, error = null)
        viewModelScope.launch {
            try {
                // Validaciones básicas
                if (username.isBlank() || email.isBlank() || password.isBlank()) {
                    throw Exception("Todos los campos son obligatorios.")
                }

                val userData = mapOf(
                    "username" to username,
                    "email" to email,
                    "password" to password
                )
                
                android.util.Log.d("RegisterViewModel", "Intentando registrar usuario: $username")
                android.util.Log.d("RegisterViewModel", "URL: https://agrochamba.com/wp-json/agrochamba/v1/register-user")
                
                val response = WordPressApi.retrofitService.registerUser(userData)

                // Verificar que la respuesta tenga token
                if (response.token.isNullOrEmpty()) {
                    throw Exception("No se recibió un token válido del servidor.")
                }

                // Si el registro es exitoso, iniciamos sesión automáticamente
                AuthManager.login(response)

                android.util.Log.d("RegisterViewModel", "Registro exitoso")
                uiState = uiState.copy(
                    isLoading = false,
                    registrationSuccess = true
                )

            } catch (e: HttpException) {
                val errorBody = e.response()?.errorBody()?.string()
                android.util.Log.e("RegisterViewModel", "Error HTTP: ${e.code()}, Body: $errorBody")
                
                val errorMessage = if (errorBody != null) {
                    try {
                        val errorJson = JSONObject(errorBody)
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
                
                uiState = uiState.copy(
                    isLoading = false,
                    error = errorMessage
                )
            } catch (e: Exception) {
                android.util.Log.e("RegisterViewModel", "Error general: ${e.message}", e)
                uiState = uiState.copy(
                    isLoading = false,
                    error = e.message ?: "Ocurrió un error. Inténtalo de nuevo."
                )
            }
        }
    }
}
