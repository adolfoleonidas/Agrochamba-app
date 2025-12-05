package agrochamba.com.ui.jobs

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.JobPost
import agrochamba.com.data.PendingJobPost
import agrochamba.com.data.WordPressApi
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import retrofit2.Response

data class ModerationState(
    val pendingJobs: List<PendingJobPost> = emptyList(),
    val isLoading: Boolean = false,
    val error: String? = null,
    val successMessage: String? = null
)

class ModerationViewModel : ViewModel() {
    private val _uiState = MutableStateFlow(ModerationState())
    val uiState: StateFlow<ModerationState> = _uiState.asStateFlow()

    init {
        loadPendingJobs()
    }

    fun loadPendingJobs() {
        val token = AuthManager.token ?: return
        _uiState.value = _uiState.value.copy(isLoading = true, error = null)
        
        viewModelScope.launch {
            try {
                val authHeader = "Bearer $token"
                val response = WordPressApi.retrofitService.getPendingJobs(authHeader, page = 1, perPage = 100)
                val jobs = response.jobs
                android.util.Log.d("ModerationViewModel", "Trabajos pendientes cargados: ${jobs.size}")
                _uiState.value = _uiState.value.copy(
                    pendingJobs = jobs,
                    isLoading = false
                )
            } catch (e: retrofit2.HttpException) {
                val errorMessage = try {
                    val errorBody = e.response()?.errorBody()?.string()
                    android.util.Log.e("ModerationViewModel", "Error HTTP: ${e.code()}, Body: $errorBody")
                    errorBody ?: "Error ${e.code()}: ${e.message()}"
                } catch (ex: Exception) {
                    android.util.Log.e("ModerationViewModel", "Error al parsear error: ${ex.message}")
                    "Error al cargar trabajos pendientes: ${e.message}"
                }
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = errorMessage
                )
            } catch (e: Exception) {
                android.util.Log.e("ModerationViewModel", "Error al cargar trabajos pendientes: ${e.message}", e)
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = "Error al cargar trabajos pendientes: ${e.message}"
                )
            }
        }
    }

    fun approveJob(jobId: Int) {
        val token = AuthManager.token ?: return
        _uiState.value = _uiState.value.copy(isLoading = true, error = null, successMessage = null)
        
        viewModelScope.launch {
            try {
                val authHeader = "Bearer $token"
                val response = WordPressApi.retrofitService.approveJob(authHeader, jobId)
                
                if (response.isSuccessful) {
                    android.util.Log.d("ModerationViewModel", "Trabajo $jobId aprobado correctamente")
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        successMessage = "Trabajo aprobado correctamente",
                        pendingJobs = _uiState.value.pendingJobs.filter { it.id != jobId }
                    )
                } else {
                    val errorBody = response.errorBody()?.string()
                    android.util.Log.e("ModerationViewModel", "Error al aprobar trabajo: ${response.code()}, Body: $errorBody")
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        error = "Error al aprobar el trabajo (${response.code()})"
                    )
                }
            } catch (e: Exception) {
                android.util.Log.e("ModerationViewModel", "Error al aprobar trabajo: ${e.message}", e)
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = e.message ?: "Error al aprobar el trabajo"
                )
            }
        }
    }

    fun rejectJob(jobId: Int, reason: String? = null) {
        val token = AuthManager.token ?: return
        _uiState.value = _uiState.value.copy(isLoading = true, error = null, successMessage = null)
        
        viewModelScope.launch {
            try {
                val authHeader = "Bearer $token"
                val body = if (reason != null && reason.isNotBlank()) mapOf("reason" to reason) else null
                val response = WordPressApi.retrofitService.rejectJob(authHeader, jobId, body)
                
                if (response.isSuccessful) {
                    android.util.Log.d("ModerationViewModel", "Trabajo $jobId rechazado correctamente")
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        successMessage = "Trabajo rechazado correctamente",
                        pendingJobs = _uiState.value.pendingJobs.filter { it.id != jobId }
                    )
                } else {
                    val errorBody = response.errorBody()?.string()
                    android.util.Log.e("ModerationViewModel", "Error al rechazar trabajo: ${response.code()}, Body: $errorBody")
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        error = "Error al rechazar el trabajo (${response.code()})"
                    )
                }
            } catch (e: Exception) {
                android.util.Log.e("ModerationViewModel", "Error al rechazar trabajo: ${e.message}", e)
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = e.message ?: "Error al rechazar el trabajo"
                )
            }
        }
    }

    fun clearMessages() {
        _uiState.value = _uiState.value.copy(error = null, successMessage = null)
    }
}

