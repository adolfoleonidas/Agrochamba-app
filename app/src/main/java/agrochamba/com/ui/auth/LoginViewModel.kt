package agrochamba.com.ui.auth

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.TokenResponse
import agrochamba.com.data.WordPressApi
import kotlinx.coroutines.launch
import org.json.JSONObject
import retrofit2.HttpException

data class LoginScreenState(
    val isLoading: Boolean = false,
    val error: String? = null,
    val loginSuccess: Boolean = false
)

class LoginViewModel : ViewModel() {

    var uiState by mutableStateOf(LoginScreenState())
        private set

    fun login(username: String, password: String) {
        uiState = uiState.copy(isLoading = true, error = null)
        viewModelScope.launch {
            try {
                val credentials = mapOf("username" to username, "password" to password)
                
                var response: TokenResponse? = null
                var lastException: Exception? = null
                
                // Si el usuario ingresó un email, usar directamente el endpoint personalizado
                // Si es username, intentar primero el estándar y luego el personalizado si falla
                val isEmail = username.contains("@")
                
                if (isEmail) {
                    // Para emails, usar directamente el endpoint personalizado
                    try {
                        response = WordPressApi.retrofitService.customLogin(credentials)
                    } catch (e: HttpException) {
                        lastException = e
                        // Log del error para debugging
                        val errorBody = e.response()?.errorBody()?.string()
                        android.util.Log.e("LoginViewModel", "Custom login failed: ${e.code()}, Body: $errorBody")
                        // Si falla, intentar también con el estándar por si acaso
                        try {
                            response = WordPressApi.retrofitService.login(credentials)
                        } catch (e2: Exception) {
                            // Si ambos fallan, usar el error original
                            android.util.Log.e("LoginViewModel", "Standard login also failed: ${e2.message}")
                        }
                    } catch (e: Exception) {
                        lastException = e
                        android.util.Log.e("LoginViewModel", "Login exception: ${e.message}")
                    }
                } else {
                    // Para username, intentar primero el estándar (como funcionaba antes)
                    try {
                        response = WordPressApi.retrofitService.login(credentials)
                    } catch (e: HttpException) {
                        lastException = e
                        // Si falla, intentar con el endpoint personalizado
                        try {
                            response = WordPressApi.retrofitService.customLogin(credentials)
                        } catch (e2: Exception) {
                            // Si ambos fallan, usar el error original
                        }
                    } catch (e: Exception) {
                        lastException = e
                    }
                }
                
                // Si no se obtuvo respuesta, lanzar el error
                if (response == null || response.token.isNullOrEmpty()) {
                    var errorMessage = "Usuario o contraseña incorrectos."
                    
                    if (lastException is HttpException) {
                        val errorBody = lastException.response()?.errorBody()?.string()
                        android.util.Log.e("LoginViewModel", "HTTP Error: ${lastException.code()}, Body: $errorBody")
                        
                        if (errorBody != null) {
                            try {
                                // Intentar parsear como JSON
                                val errorJson = JSONObject(errorBody)
                                val code = errorJson.optString("code", "")
                                val message = errorJson.optString("message", "")
                                val data = errorJson.optJSONObject("data")
                                val status = data?.optInt("status", 0)
                                
                                when {
                                    message.isNotEmpty() -> errorMessage = message
                                    code == "incorrect_password" || code == "invalid_username" -> errorMessage = "Usuario o contraseña incorrectos."
                                    status == 403 || lastException.code() == 403 -> errorMessage = "Usuario o contraseña incorrectos."
                                    else -> errorMessage = "Usuario o contraseña incorrectos."
                                }
                            } catch (ex: Exception) {
                                // Si no es JSON, podría ser HTML (error de WordPress)
                                if (errorBody.contains("ERROR") || errorBody.contains("incorrectos")) {
                                    errorMessage = "Usuario o contraseña incorrectos."
                                } else {
                                    errorMessage = "Error de conexión. Por favor, intenta nuevamente."
                                }
                                android.util.Log.e("LoginViewModel", "Error parsing response: ${ex.message}")
                            }
                        }
                    } else if (lastException != null) {
                        errorMessage = lastException.message ?: "Error de conexión. Por favor, intenta nuevamente."
                    }
                    
                    throw Exception(errorMessage)
                }
                
                // Le entregamos los datos al AuthManager para que los guarde
                AuthManager.login(response)

                uiState = uiState.copy(
                    isLoading = false,
                    loginSuccess = true
                )

            } catch (e: Exception) {
                uiState = uiState.copy(
                    isLoading = false,
                    error = e.message ?: "Usuario o contraseña incorrectos."
                )
            }
        }
    }
}
