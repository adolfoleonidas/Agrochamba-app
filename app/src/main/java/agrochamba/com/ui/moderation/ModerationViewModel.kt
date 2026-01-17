package agrochamba.com.ui.moderation

import android.util.Log
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.*
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch

private const val TAG = "ModerationViewModel"

/**
 * ViewModel para la pantalla de moderación de trabajos.
 * Soporta filtrado por estado: "pending" (pendientes) y "all" (todos).
 */
class ModerationViewModel : ViewModel() {

    // Estado de la UI
    private val _uiState = MutableStateFlow(ModerationUiState())
    val uiState: StateFlow<ModerationUiState> = _uiState.asStateFlow()

    // Trabajo seleccionado para preview
    private val _selectedJob = MutableStateFlow<JobPost?>(null)
    val selectedJob: StateFlow<JobPost?> = _selectedJob.asStateFlow()

    // Filtro actual - Por defecto "pending" (pendientes)
    private val _currentFilter = MutableStateFlow("pending")
    val currentFilter: StateFlow<String> = _currentFilter.asStateFlow()

    // Búsqueda
    private val _searchQuery = MutableStateFlow("")
    val searchQuery: StateFlow<String> = _searchQuery.asStateFlow()

    // Paginación
    private val _currentPage = MutableStateFlow(1)
    private val _hasMorePages = MutableStateFlow(false)
    val hasMorePages: StateFlow<Boolean> = _hasMorePages.asStateFlow()

    // Trabajos seleccionados para acciones masivas
    private val _selectedJobIds = MutableStateFlow<Set<Int>>(emptySet())
    val selectedJobIds: StateFlow<Set<Int>> = _selectedJobIds.asStateFlow()
    
    // Cache de todos los trabajos para filtrado local
    private var allJobsCache: List<JobPost> = emptyList()

    init {
        loadAllJobs()
    }

    fun isAdmin(): Boolean {
        return AuthManager.isUserAdmin()
    }

    /**
     * Carga todos los trabajos y luego aplica el filtro actual
     */
    private fun loadAllJobs() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }

            try {
                val token = AuthManager.token
                if (token == null) {
                    _uiState.update { it.copy(isLoading = false, error = "No autenticado") }
                    return@launch
                }

                // Cargar trabajos del endpoint estándar
                val jobs = WordPressApi.retrofitService.getJobs(
                    page = 1,
                    perPage = 100
                )
                
                allJobsCache = jobs
                
                // Contar pendientes (si el endpoint devuelve status)
                val pendingCount = jobs.count { it.status == "pending" }
                
                // Aplicar filtro
                applyFilter(pendingCount)
                
            } catch (e: Exception) {
                Log.e(TAG, "Error loading jobs: ${e.message}", e)
                _uiState.update { it.copy(isLoading = false, error = e.message ?: "Error desconocido") }
            }
        }
    }
    
    /**
     * Aplica el filtro actual a la lista de trabajos cacheados
     */
    private fun applyFilter(pendingCount: Int) {
        val filteredJobs = when (_currentFilter.value) {
            "pending" -> allJobsCache.filter { it.status == "pending" }
            else -> allJobsCache
        }
        
        // Aplicar búsqueda si hay query
        val searchedJobs = if (_searchQuery.value.isNotBlank()) {
            val query = _searchQuery.value.lowercase()
            filteredJobs.filter { job ->
                job.title?.rendered?.lowercase()?.contains(query) == true ||
                job.content?.rendered?.lowercase()?.contains(query) == true
            }
        } else {
            filteredJobs
        }
        
        _uiState.update { it.copy(
            isLoading = false,
            jobs = searchedJobs,
            totalJobs = allJobsCache.size,
            pendingCount = pendingCount
        )}
        
        _hasMorePages.value = false // Por ahora sin paginación
    }

    fun loadJobs(resetPage: Boolean = true) {
        loadAllJobs()
    }

    fun loadMore() {
        // Por ahora no hay paginación real, todo se carga de una vez
    }

    fun setFilter(status: String) {
        _currentFilter.value = status
        _selectedJobIds.value = emptySet()
        
        // Re-aplicar filtro sin recargar
        val pendingCount = allJobsCache.count { it.status == "pending" }
        applyFilter(pendingCount)
    }

    fun setSearchQuery(query: String) {
        _searchQuery.value = query
    }

    fun search() {
        val pendingCount = allJobsCache.count { it.status == "pending" }
        applyFilter(pendingCount)
    }

    fun selectJob(job: JobPost) {
        _selectedJob.value = job
    }

    fun clearSelectedJob() {
        _selectedJob.value = null
    }

    fun approveJob(jobId: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isProcessing = true) }
            
            try {
                val token = AuthManager.token ?: return@launch
                
                val response = WordPressApi.retrofitService.approveJob(
                    token = "Bearer $token",
                    id = jobId
                )
                
                if (response.isSuccessful) {
                    _uiState.update { it.copy(
                        isProcessing = false,
                        successMessage = "Trabajo aprobado"
                    )}
                    loadAllJobs()
                } else {
                    _uiState.update { it.copy(
                        isProcessing = false,
                        error = "Error al aprobar"
                    )}
                }
            } catch (e: Exception) {
                Log.e(TAG, "Error approving job: ${e.message}", e)
                _uiState.update { it.copy(
                    isProcessing = false,
                    error = "Error: ${e.message}"
                )}
            }
        }
    }

    fun rejectJob(jobId: Int, reason: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isProcessing = true) }
            
            try {
                val token = AuthManager.token ?: return@launch
                
                val response = WordPressApi.retrofitService.rejectJob(
                    token = "Bearer $token",
                    id = jobId,
                    reason = mapOf("reason" to reason)
                )
                
                if (response.isSuccessful) {
                    _uiState.update { it.copy(
                        isProcessing = false,
                        successMessage = "Trabajo rechazado"
                    )}
                    loadAllJobs()
                } else {
                    _uiState.update { it.copy(
                        isProcessing = false,
                        error = "Error al rechazar"
                    )}
                }
            } catch (e: Exception) {
                Log.e(TAG, "Error rejecting job: ${e.message}", e)
                _uiState.update { it.copy(
                    isProcessing = false,
                    error = "Error: ${e.message}"
                )}
            }
        }
    }

    fun deleteJob(jobId: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isProcessing = true) }
            
            try {
                val token = AuthManager.token
                if (token == null) {
                    _uiState.update { it.copy(isProcessing = false, error = "No autenticado") }
                    return@launch
                }
                
                val response = WordPressApi.retrofitService.deleteJob(
                    token = "Bearer $token",
                    id = jobId
                )
                
                if (response.isSuccessful) {
                    // Remover del cache local
                    allJobsCache = allJobsCache.filter { it.id != jobId }
                    
                    _uiState.update { it.copy(
                        isProcessing = false,
                        successMessage = "Trabajo eliminado"
                    )}
                    clearSelectedJob()
                    
                    // Re-aplicar filtro
                    val pendingCount = allJobsCache.count { it.status == "pending" }
                    applyFilter(pendingCount)
                } else {
                    _uiState.update { it.copy(
                        isProcessing = false,
                        error = "Error al eliminar: ${response.code()}"
                    )}
                }
            } catch (e: Exception) {
                Log.e(TAG, "Error deleting job: ${e.message}", e)
                _uiState.update { it.copy(
                    isProcessing = false,
                    error = e.message ?: "Error desconocido"
                )}
            }
        }
    }

    // Selección múltiple
    fun toggleJobSelection(jobId: Int) {
        _selectedJobIds.update { current ->
            if (current.contains(jobId)) {
                current - jobId
            } else {
                current + jobId
            }
        }
    }

    fun selectAllJobs() {
        _selectedJobIds.value = _uiState.value.jobs.map { it.id }.toSet()
    }

    fun clearSelection() {
        _selectedJobIds.value = emptySet()
    }

    fun clearMessages() {
        _uiState.update { it.copy(error = null, successMessage = null) }
    }
}

data class ModerationUiState(
    val isLoading: Boolean = false,
    val isLoadingDetail: Boolean = false,
    val isProcessing: Boolean = false,
    val jobs: List<JobPost> = emptyList(),
    val totalJobs: Int = 0,
    val pendingCount: Int = 0,
    val error: String? = null,
    val successMessage: String? = null
)
