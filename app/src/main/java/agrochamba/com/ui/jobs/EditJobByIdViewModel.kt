package agrochamba.com.ui.jobs

import android.util.Log
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.*
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch

private const val TAG = "EditJobByIdViewModel"

/**
 * ViewModel para cargar un trabajo por ID para edición.
 * Simplificado para usar el endpoint estándar de obtener un trabajo.
 */
class EditJobByIdViewModel(
    private val jobId: Int
) : ViewModel() {

    private val _uiState = MutableStateFlow(EditJobByIdUiState())
    val uiState: StateFlow<EditJobByIdUiState> = _uiState.asStateFlow()

    private val _job = MutableStateFlow<JobPost?>(null)
    val job: StateFlow<JobPost?> = _job.asStateFlow()

    fun loadJob(jobId: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }

            try {
                val token = AuthManager.token
                if (token == null) {
                    _uiState.update { it.copy(isLoading = false, error = "No autenticado") }
                    return@launch
                }

                // Usar el endpoint para obtener un trabajo específico
                val response = WordPressApi.retrofitService.getJobById(
                    token = "Bearer $token",
                    id = jobId
                )

                if (response.isSuccessful && response.body() != null) {
                    _job.value = response.body()
                    _uiState.update { it.copy(isLoading = false) }
                    Log.d(TAG, "Trabajo cargado exitosamente: ${response.body()?.title?.rendered}")
                } else {
                    _uiState.update { it.copy(
                        isLoading = false, 
                        error = "No se pudo cargar el trabajo: ${response.code()}"
                    )}
                }
            } catch (e: Exception) {
                Log.e(TAG, "Error loading job: ${e.message}", e)
                _uiState.update { it.copy(
                    isLoading = false, 
                    error = e.message ?: "Error desconocido"
                )}
            }
        }
    }
}

data class EditJobByIdUiState(
    val isLoading: Boolean = false,
    val error: String? = null
)

/**
 * Factory para crear EditJobByIdViewModel con el jobId
 */
class EditJobByIdViewModelFactory(
    private val jobId: Int
) : ViewModelProvider.Factory {
    @Suppress("UNCHECKED_CAST")
    override fun <T : ViewModel> create(modelClass: Class<T>): T {
        if (modelClass.isAssignableFrom(EditJobByIdViewModel::class.java)) {
            return EditJobByIdViewModel(jobId) as T
        }
        throw IllegalArgumentException("Unknown ViewModel class")
    }
}
