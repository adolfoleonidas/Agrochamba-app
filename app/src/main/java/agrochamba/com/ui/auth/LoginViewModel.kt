package agrochamba.com.ui.auth

import android.app.Application
import android.util.Patterns
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.repository.LocationRepository
import agrochamba.com.domain.usecase.auth.LoginUseCase
import agrochamba.com.util.WordPressErrorMapper
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class LoginScreenState(
    val isLoading: Boolean = false,
    val error: String? = null,
    val loginSuccess: Boolean = false
)

@HiltViewModel
class LoginViewModel @Inject constructor(
    private val loginUseCase: LoginUseCase,
    private val application: Application
) : androidx.lifecycle.ViewModel() {

    private val _uiState = MutableStateFlow(LoginScreenState())
    val uiState: StateFlow<LoginScreenState> = _uiState.asStateFlow()

    fun login(usernameOrEmail: String, password: String) {
        _uiState.value = _uiState.value.copy(isLoading = true, error = null)
        viewModelScope.launch {
            try {
                // Validaciones básicas
                if (usernameOrEmail.isBlank()) {
                    throw Exception("Ingresa tu usuario o correo electrónico.")
                }
                if (password.isBlank()) {
                    throw Exception("Ingresa tu contraseña.")
                }

                val loginValue = usernameOrEmail.trim()

                // Usar caso de uso para login (acepta username o email)
                android.util.Log.d("LoginViewModel", "🔐 Iniciando login para: $loginValue")
                
                when (val result = loginUseCase(loginValue, password)) {
                    is agrochamba.com.util.Result.Success -> {
                        android.util.Log.d("LoginViewModel", "✅ Login exitoso!")
                        android.util.Log.d("LoginViewModel", "📋 Token: ${AuthManager.token?.take(20)}...")
                        android.util.Log.d("LoginViewModel", "👤 User: ${AuthManager.userDisplayName}")
                        android.util.Log.d("LoginViewModel", "🏢 CompanyId: ${AuthManager.userCompanyId}")
                        android.util.Log.d("LoginViewModel", "🎭 Roles: ${AuthManager.userRoles}")
                        
                        // Sincronizar sedes si es empresa (no bloquea login)
                        try {
                            android.util.Log.d("LoginViewModel", "🔄 Intentando sincronizar sedes...")
                            syncSedesIfCompany()
                            android.util.Log.d("LoginViewModel", "✅ Sincronización de sedes completada")
                        } catch (e: Exception) {
                            android.util.Log.e("LoginViewModel", "⚠️ Error sincronizando sedes (no fatal): ${e.message}")
                        }
                        
                        android.util.Log.d("LoginViewModel", "🚀 Seteando loginSuccess = true")
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            loginSuccess = true
                        )
                        android.util.Log.d("LoginViewModel", "✅ Estado actualizado, navegación debería ocurrir")
                    }
                    is agrochamba.com.util.Result.Error -> {
                        android.util.Log.e("LoginViewModel", "❌ Login falló: ${result.exception.message}")
                        throw WordPressErrorMapper.mapAuthError(result.exception)
                    }
                }

            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = e.message ?: "No se pudo iniciar sesión. Inténtalo de nuevo."
                )
            }
        }
    }
    
    /**
     * Sincroniza las sedes de la empresa después del login
     */
    private suspend fun syncSedesIfCompany() {
        try {
            val token = AuthManager.token ?: return
            val companyId = AuthManager.userCompanyId ?: return
            
            // Solo sincronizar si es empresa o admin
            if (!AuthManager.isUserAnEnterprise()) return
            
            val locationRepository = LocationRepository.getInstance(application)
            locationRepository.syncSedesFromBackend(token, companyId)
            
            android.util.Log.d("LoginViewModel", "Sedes sincronizadas correctamente")
        } catch (e: Exception) {
            // No fallar el login si falla la sincronización
            android.util.Log.e("LoginViewModel", "Error sincronizando sedes: ${e.message}")
        }
    }
}
