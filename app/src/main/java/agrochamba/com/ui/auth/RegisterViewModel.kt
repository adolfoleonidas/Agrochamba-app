package agrochamba.com.ui.auth

import androidx.lifecycle.viewModelScope
import agrochamba.com.domain.usecase.auth.RegisterUserUseCase
import agrochamba.com.util.WordPressErrorMapper
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class RegisterScreenState(
    val isLoading: Boolean = false,
    val error: String? = null,
    val registrationSuccess: Boolean = false
)

@HiltViewModel
class RegisterViewModel @Inject constructor(
    private val registerUserUseCase: RegisterUserUseCase
) : androidx.lifecycle.ViewModel() {

    private val _uiState = MutableStateFlow(RegisterScreenState())
    val uiState: StateFlow<RegisterScreenState> = _uiState.asStateFlow()

    fun register(username: String, email: String, password: String) {
        _uiState.value = _uiState.value.copy(isLoading = true, error = null)
        viewModelScope.launch {
            try {
                // Usar caso de uso para registro
                when (val result = registerUserUseCase(username, email, password)) {
                    is agrochamba.com.util.Result.Success -> {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false, 
                            registrationSuccess = true
                        )
                    }
                    is agrochamba.com.util.Result.Error -> {
                        throw WordPressErrorMapper.mapAuthError(result.exception)
                    }
                }

            } catch (e: Exception) {
                android.util.Log.e("RegisterViewModel", "Error general: ${e.message}", e)
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = e.message ?: "Ocurrió un error. Inténtalo de nuevo."
                )
            }
        }
    }
}
