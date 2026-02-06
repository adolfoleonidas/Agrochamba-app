package agrochamba.com.ui.applications

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.ApplicationData
import agrochamba.com.data.ApplicationStatus
import agrochamba.com.data.AuthManager
import agrochamba.com.data.WordPressApi
import kotlinx.coroutines.launch

data class ApplicationsUiState(
    val isLoading: Boolean = false,
    val applications: List<ApplicationData> = emptyList(),
    val error: String? = null,

    // Estado para postularse
    val isApplying: Boolean = false,
    val applySuccess: Boolean = false,
    val applyError: String? = null,

    // Estado para cancelar
    val isCancelling: Boolean = false,
    val cancelSuccess: Boolean = false,
    val cancelError: String? = null,

    // Estado de verificación de postulación
    val hasApplied: Boolean = false,
    val applicationStatus: String? = null,
    val applicationStatusLabel: String? = null,
    val isCheckingStatus: Boolean = false
)

class ApplicationsViewModel : ViewModel() {

    var uiState by mutableStateOf(ApplicationsUiState())
        private set

    init {
        loadApplications()
    }

    /**
     * Cargar todas mis postulaciones
     */
    fun loadApplications() {
        val token = AuthManager.token ?: return

        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)

            try {
                val response = WordPressApi.retrofitService.getMyApplications("Bearer $token")
                uiState = uiState.copy(
                    isLoading = false,
                    applications = response.applications
                )
            } catch (e: Exception) {
                android.util.Log.e("ApplicationsViewModel", "Error cargando postulaciones", e)
                uiState = uiState.copy(
                    isLoading = false,
                    error = e.message ?: "Error al cargar postulaciones"
                )
            }
        }
    }

    /**
     * Postularse a un trabajo
     */
    fun applyToJob(jobId: Int, message: String = "") {
        val token = AuthManager.token ?: return

        viewModelScope.launch {
            uiState = uiState.copy(isApplying = true, applyError = null, applySuccess = false)

            try {
                val data = mutableMapOf<String, Any>(
                    "job_id" to jobId
                )
                if (message.isNotBlank()) {
                    data["message"] = message
                }

                val response = WordPressApi.retrofitService.createApplication(
                    token = "Bearer $token",
                    data = data
                )

                if (response.success) {
                    uiState = uiState.copy(
                        isApplying = false,
                        applySuccess = true,
                        hasApplied = true,
                        applicationStatus = ApplicationStatus.PENDING.value,
                        applicationStatusLabel = ApplicationStatus.PENDING.label
                    )
                    // Recargar lista de postulaciones
                    loadApplications()
                } else {
                    uiState = uiState.copy(
                        isApplying = false,
                        applyError = response.message
                    )
                }
            } catch (e: retrofit2.HttpException) {
                val errorBody = e.response()?.errorBody()?.string()
                val errorMessage = when {
                    errorBody?.contains("already_applied") == true -> "Ya te has postulado a este trabajo"
                    errorBody?.contains("not_allowed") == true -> "Las empresas no pueden postularse"
                    else -> e.message ?: "Error al postularse"
                }
                uiState = uiState.copy(
                    isApplying = false,
                    applyError = errorMessage
                )
            } catch (e: Exception) {
                android.util.Log.e("ApplicationsViewModel", "Error postulándose", e)
                uiState = uiState.copy(
                    isApplying = false,
                    applyError = e.message ?: "Error al postularse"
                )
            }
        }
    }

    /**
     * Cancelar postulación
     */
    fun cancelApplication(jobId: Int) {
        val token = AuthManager.token ?: return

        viewModelScope.launch {
            uiState = uiState.copy(isCancelling = true, cancelError = null, cancelSuccess = false)

            try {
                val response = WordPressApi.retrofitService.cancelApplication(
                    token = "Bearer $token",
                    jobId = jobId
                )

                if (response.success) {
                    uiState = uiState.copy(
                        isCancelling = false,
                        cancelSuccess = true,
                        hasApplied = false,
                        applicationStatus = null,
                        applicationStatusLabel = null
                    )
                    // Recargar lista de postulaciones
                    loadApplications()
                } else {
                    uiState = uiState.copy(
                        isCancelling = false,
                        cancelError = response.message
                    )
                }
            } catch (e: Exception) {
                android.util.Log.e("ApplicationsViewModel", "Error cancelando postulación", e)
                uiState = uiState.copy(
                    isCancelling = false,
                    cancelError = e.message ?: "Error al cancelar postulación"
                )
            }
        }
    }

    /**
     * Verificar si ya me postulé a un trabajo específico
     */
    fun checkApplicationStatus(jobId: Int) {
        val token = AuthManager.token

        if (token == null) {
            uiState = uiState.copy(
                hasApplied = false,
                applicationStatus = null,
                applicationStatusLabel = null
            )
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(isCheckingStatus = true)

            try {
                val response = WordPressApi.retrofitService.getApplicationStatus(
                    token = "Bearer $token",
                    jobId = jobId
                )

                uiState = uiState.copy(
                    isCheckingStatus = false,
                    hasApplied = response.hasApplied,
                    applicationStatus = response.status,
                    applicationStatusLabel = response.statusLabel
                )
            } catch (e: Exception) {
                android.util.Log.e("ApplicationsViewModel", "Error verificando estado", e)
                uiState = uiState.copy(
                    isCheckingStatus = false,
                    hasApplied = false
                )
            }
        }
    }

    fun clearApplyState() {
        uiState = uiState.copy(applySuccess = false, applyError = null)
    }

    fun clearCancelState() {
        uiState = uiState.copy(cancelSuccess = false, cancelError = null)
    }

    /**
     * Obtener el conteo de postulaciones por estado
     */
    fun getApplicationsCount(): Map<String, Int> {
        val counts = mutableMapOf<String, Int>()
        uiState.applications.forEach { app ->
            val status = app.status
            counts[status] = (counts[status] ?: 0) + 1
        }
        return counts
    }
}
