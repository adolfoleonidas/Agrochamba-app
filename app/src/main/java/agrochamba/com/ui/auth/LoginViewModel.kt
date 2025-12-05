package agrochamba.com.ui.auth

import android.util.Patterns
import androidx.lifecycle.viewModelScope
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
    private val loginUseCase: LoginUseCase
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
                when (val result = loginUseCase(loginValue, password)) {
                    is agrochamba.com.util.Result.Success -> {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            loginSuccess = true
                        )
                    }
                    is agrochamba.com.util.Result.Error -> {
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
}
