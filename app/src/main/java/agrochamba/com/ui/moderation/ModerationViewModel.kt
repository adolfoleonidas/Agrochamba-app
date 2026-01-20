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
    
    // Modo de selección múltiple (activado por long press)
    private val _isSelectionMode = MutableStateFlow(false)
    val isSelectionMode: StateFlow<Boolean> = _isSelectionMode.asStateFlow()
    
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
     * Usa el endpoint de admin que incluye todos los estados (publish, pending, draft)
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

                // Usar endpoint de admin que incluye TODOS los estados (publish, pending, draft)
                val response = WordPressApi.retrofitService.getAdminJobs(
                    token = "Bearer $token",
                    page = 1,
                    perPage = 100,
                    status = "all" // Incluye todos los estados
                )
                
                // Convertir AdminJobItem a JobPost para compatibilidad con la UI existente
                val jobs = response.data.map { adminJob ->
                    JobPost(
                        id = adminJob.id,
                        status = adminJob.status,
                        title = Title(rendered = adminJob.title),
                        date = adminJob.date,
                        featuredImageUrl = adminJob.featuredImage?.medium ?: adminJob.featuredImage?.full,
                        meta = JobMeta(
                            salarioMin = null,
                            salarioMax = null,
                            vacantes = null,
                            tipoContrato = null,
                            jornada = null,
                            alojamiento = null,
                            transporte = null,
                            alimentacion = null,
                            requisitos = null,
                            beneficios = null,
                            galleryIds = null,
                            facebookPostId = null,
                            ubicacionCompleta = adminJob.ubicacion
                        )
                    )
                }
                
                allJobsCache = jobs
                
                // Contar pendientes y borradores (ambos necesitan moderación)
                val pendingCount = jobs.count { it.status == "pending" || it.status == "draft" }
                
                Log.d(TAG, "Loaded ${jobs.size} jobs. Pending/Draft: $pendingCount")
                
                // Aplicar filtro
                applyFilter(pendingCount)
                
            } catch (e: Exception) {
                Log.e(TAG, "Error loading admin jobs: ${e.message}", e)
                _uiState.update { it.copy(isLoading = false, error = e.message ?: "Error desconocido") }
            }
        }
    }
    
    /**
     * Aplica el filtro actual a la lista de trabajos cacheados
     */
    private fun applyFilter(pendingCount: Int) {
        val filteredJobs = when (_currentFilter.value) {
            // "pending" incluye tanto pending como draft (ambos necesitan moderación)
            "pending" -> allJobsCache.filter { it.status == "pending" || it.status == "draft" }
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
        
        // Re-aplicar filtro sin recargar (pending + draft = pendientes de moderación)
        val pendingCount = allJobsCache.count { it.status == "pending" || it.status == "draft" }
        applyFilter(pendingCount)
    }

    fun setSearchQuery(query: String) {
        _searchQuery.value = query
    }

    fun search() {
        val pendingCount = allJobsCache.count { it.status == "pending" || it.status == "draft" }
        applyFilter(pendingCount)
    }


    /**
     * Selecciona un trabajo y carga sus detalles completos para preview
     */
    fun selectJob(job: JobPost) {
        _selectedJob.value = job
        // Cargar detalles completos en segundo plano
        loadJobDetails(job.id)
    }
    
    /**
     * Carga los detalles completos del trabajo desde el endpoint admin
     */
    private fun loadJobDetails(jobId: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingDetail = true) }
            
            try {
                val token = AuthManager.token ?: return@launch
                
                val response = WordPressApi.retrofitService.getAdminJobDetail(
                    token = "Bearer $token",
                    id = jobId
                )
                
                // Convertir AdminJobDetail a JobPost con contenido completo
                val detail = response.data
                val jobWithContent = JobPost(
                    id = detail.id,
                    status = detail.status,
                    title = Title(rendered = detail.title),
                    content = Content(rendered = detail.contentHtml ?: detail.content),
                    date = detail.date,
                    featuredImageUrl = detail.featuredImage?.medium ?: detail.featuredImage?.full,
                    meta = JobMeta(
                        salarioMin = detail.meta?.salarioMin?.toString(),
                        salarioMax = detail.meta?.salarioMax?.toString(),
                        vacantes = detail.meta?.vacantes?.toString(),
                        tipoContrato = null,
                        jornada = null,
                        alojamiento = detail.meta?.alojamiento,
                        transporte = detail.meta?.transporte,
                        alimentacion = detail.meta?.alimentacion,
                        requisitos = null,
                        beneficios = null,
                        galleryIds = null,
                        facebookPostId = null,
                        ubicacionCompleta = detail.ubicacion
                    )
                )
                
                _selectedJob.value = jobWithContent
                _uiState.update { it.copy(isLoadingDetail = false) }
                
                Log.d(TAG, "Loaded job details for ID $jobId: ${detail.title}")
                
            } catch (e: Exception) {
                Log.e(TAG, "Error loading job details: ${e.message}", e)
                _uiState.update { it.copy(isLoadingDetail = false) }
                // Mantener el job original aunque no se puedan cargar los detalles
            }
        }
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
                    id = jobId,
                    data = emptyMap() // Body requerido por Retrofit
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
                    
                    // Re-aplicar filtro (pending + draft = pendientes de moderación)
                    val pendingCount = allJobsCache.count { it.status == "pending" || it.status == "draft" }
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

    // ==========================================
    // MODO DE SELECCIÓN MÚLTIPLE
    // ==========================================
    
    /**
     * Activa el modo de selección y selecciona el primer elemento (long press)
     */
    fun enterSelectionMode(jobId: Int) {
        _isSelectionMode.value = true
        _selectedJobIds.value = setOf(jobId)
    }
    
    /**
     * Sale del modo de selección y limpia la selección
     */
    fun exitSelectionMode() {
        _isSelectionMode.value = false
        _selectedJobIds.value = emptySet()
    }
    
    /**
     * Alterna la selección de un trabajo (solo en modo selección)
     */
    fun toggleJobSelection(jobId: Int) {
        if (!_isSelectionMode.value) return
        
        _selectedJobIds.update { current ->
            val newSet = if (current.contains(jobId)) {
                current - jobId
            } else {
                current + jobId
            }
            // Si no queda ninguno seleccionado, salir del modo selección
            if (newSet.isEmpty()) {
                _isSelectionMode.value = false
            }
            newSet
        }
    }

    /**
     * Selecciona todos los trabajos visibles
     */
    fun selectAllJobs() {
        _isSelectionMode.value = true
        _selectedJobIds.value = _uiState.value.jobs.map { it.id }.toSet()
    }
    
    /**
     * Selecciona trabajos por fecha
     */
    fun selectJobsByDate(date: String) {
        _isSelectionMode.value = true
        _selectedJobIds.value = _uiState.value.jobs
            .filter { it.date?.startsWith(date) == true }
            .map { it.id }
            .toSet()
    }

    fun clearSelection() {
        exitSelectionMode()
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
