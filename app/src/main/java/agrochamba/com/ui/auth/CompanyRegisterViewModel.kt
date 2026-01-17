package agrochamba.com.ui.auth

import androidx.lifecycle.viewModelScope
import agrochamba.com.domain.usecase.auth.RegisterCompanyUseCase
import agrochamba.com.util.WordPressErrorMapper
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class CompanyRegisterState(
    val isLoading: Boolean = false,
    val error: String? = null,
    val registrationSuccess: Boolean = false
)

@HiltViewModel
class CompanyRegisterViewModel @Inject constructor(
    private val registerCompanyUseCase: RegisterCompanyUseCase
) : androidx.lifecycle.ViewModel() {

    private val _uiState = MutableStateFlow(CompanyRegisterState())
    val uiState: StateFlow<CompanyRegisterState> = _uiState.asStateFlow()

    fun registerCompany(
        username: String,
        email: String,
        password: String,
        ruc: String,
        razonSocial: String
    ) {
        _uiState.value = _uiState.value.copy(isLoading = true, error = null)
        viewModelScope.launch {
            try {
                // Usar caso de uso para registro de empresa
                when (val result = registerCompanyUseCase(username, email, password, ruc, razonSocial)) {
                    is agrochamba.com.util.Result.Success -> {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false, 
                            registrationSuccess = true
                        )
                    }
                    is agrochamba.com.util.Result.Error -> {
                        // Usar mapRegisterError para mensajes seguros (no revelar datos existentes)
                        throw WordPressErrorMapper.mapRegisterError(result.exception)
                    }
                }

            } catch (e: Exception) {
                android.util.Log.e("CompanyRegisterViewModel", "Error general: ${e.message}", e)
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = e.message ?: "No se pudo completar el registro. Inténtalo de nuevo."
                )
            }
        }
    }
}