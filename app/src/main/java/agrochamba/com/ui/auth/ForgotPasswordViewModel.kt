package agrochamba.com.ui.auth

import androidx.lifecycle.viewModelScope
import agrochamba.com.domain.usecase.auth.SendPasswordResetUseCase
import agrochamba.com.util.WordPressErrorMapper
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class ForgotPasswordScreenState(
    val isLoading: Boolean = false,
    val error: String? = null,
    val successMessage: String? = null,
    val passwordResetSuccess: Boolean = false
)

@HiltViewModel
class ForgotPasswordViewModel @Inject constructor(
    private val sendPasswordResetUseCase: SendPasswordResetUseCase
) : androidx.lifecycle.ViewModel() {

    private val _uiState = MutableStateFlow(ForgotPasswordScreenState())
    val uiState: StateFlow<ForgotPasswordScreenState> = _uiState.asStateFlow()

    fun requestPasswordReset(userLogin: String) {
        _uiState.value = _uiState.value.copy(isLoading = true, error = null, successMessage = null)
        viewModelScope.launch {
            try {
                // Usar caso de uso para enviar correo de restablecimiento
                when (val result = sendPasswordResetUseCase(userLogin)) {
                    is agrochamba.com.util.Result.Success -> {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            successMessage = "Si el correo existe, te enviamos un enlace para restablecer la contraseña.",
                            passwordResetSuccess = true
                        )
                    }
                    is agrochamba.com.util.Result.Error -> {
                        throw WordPressErrorMapper.mapResetError(result.exception)
                    }
                }

            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(isLoading = false, error = e.message ?: "No se pudo enviar el correo de restablecimiento")
            }
        }
    }

}