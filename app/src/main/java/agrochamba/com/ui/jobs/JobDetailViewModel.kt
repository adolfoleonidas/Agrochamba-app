package agrochamba.com.ui.jobs

import android.util.Log
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.CompanyProfileResponse
import agrochamba.com.data.JobPost
import agrochamba.com.data.LocationType
import agrochamba.com.data.MediaItem
import agrochamba.com.data.UbicacionCompleta
import agrochamba.com.data.WordPressApi
import agrochamba.com.utils.htmlToString
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

/**
 * ViewModel para la pantalla de detalle de trabajo
 * Maneja la carga del perfil de empresa y la lógica de negocio
 */
@HiltViewModel
class JobDetailViewModel @Inject constructor() : ViewModel() {

    private val _uiState = MutableStateFlow<JobDetailUiState>(JobDetailUiState.Loading)
    val uiState: StateFlow<JobDetailUiState> = _uiState.asStateFlow()

    // Eventos one-time para navegación y acciones
    private val _events = MutableSharedFlow<JobDetailEvent>()
    val events = _events.asSharedFlow()

    // Estado para el visor de imágenes fullscreen
    private val _fullscreenImageIndex = MutableStateFlow<Int?>(null)
    val fullscreenImageIndex: StateFlow<Int?> = _fullscreenImageIndex.asStateFlow()

    private var currentJob: JobPost? = null
    private var currentMediaItems: List<MediaItem> = emptyList()

    /**
     * Inicializa el ViewModel con los datos del trabajo
     * Llamar desde el Composable cuando se recibe el job y los mediaItems
     */
    fun initialize(job: JobPost, mediaItems: List<MediaItem>) {
        if (currentJob?.id == job.id) {
            Log.d(TAG, "Job ${job.id} already loaded, skipping")
            return
        }

        currentJob = job
        currentMediaItems = mediaItems

        Log.d(TAG, "Initializing with job: ${job.id} - ${job.title?.rendered}")

        viewModelScope.launch {
            // Primero mostrar los datos que ya tenemos
            val initialState = buildSuccessState(job, mediaItems, null, isLoadingCompany = true, isCheckingApplication = true)
            _uiState.value = initialState

            // Cargar estado de postulación y perfil de empresa en paralelo
            launch { checkApplicationStatus(job.id) }
            loadCompanyProfile(job, mediaItems)
        }
    }

    /**
     * Construye el estado de éxito con todos los datos procesados
     */
    private fun buildSuccessState(
        job: JobPost,
        mediaItems: List<MediaItem>,
        companyProfile: CompanyProfileResponse?,
        isLoadingCompany: Boolean = false,
        isCheckingApplication: Boolean = false,
        hasApplied: Boolean = false,
        applicationStatus: String? = null,
        applicationStatusLabel: String? = null
    ): JobDetailUiState.Success {
        val terms = job.embedded?.terms?.flatten() ?: emptyList()
        val companyName = terms.find { it.taxonomy == "empresa" }?.name
        val locationName = terms.find { it.taxonomy == "ubicacion" }?.name

        // Construir URLs de imágenes
        val allImageUrls = buildImageUrls(mediaItems, job, fullSize = false)
        val allFullImageUrls = buildImageUrls(mediaItems, job, fullSize = true)

        // Construir ubicación completa
        val ubicacionCompleta = buildUbicacionCompleta(job, locationName)

        return JobDetailUiState.Success(
            job = job,
            mediaItems = mediaItems,
            companyProfile = companyProfile,
            ubicacionCompleta = ubicacionCompleta,
            allImageUrls = allImageUrls,
            allFullImageUrls = allFullImageUrls,
            companyName = companyName,
            isLoadingCompany = isLoadingCompany,
            isCheckingApplication = isCheckingApplication,
            hasApplied = hasApplied,
            applicationStatus = applicationStatus,
            applicationStatusLabel = applicationStatusLabel
        )
    }

    /**
     * Carga el perfil de empresa desde la API
     */
    private suspend fun loadCompanyProfile(job: JobPost, mediaItems: List<MediaItem>) {
        val terms = job.embedded?.terms?.flatten() ?: emptyList()
        val companyName = terms.find { it.taxonomy == "empresa" }?.name

        if (companyName == null) {
            Log.d(TAG, "No company name found for job ${job.id}")
            _uiState.value = buildSuccessState(job, mediaItems, null, isLoadingCompany = false)
            return
        }

        try {
            Log.d(TAG, "Loading company profile for: $companyName")
            val profile = WordPressApi.retrofitService.getCompanyProfileByName(companyName.htmlToString())
            Log.d(TAG, "Company profile loaded: ${profile.companyName}")
            _uiState.value = buildSuccessState(job, mediaItems, profile, isLoadingCompany = false)
        } catch (e: Exception) {
            Log.e(TAG, "Error loading company profile: ${e.message}", e)
            // No mostrar error al usuario, solo log - el perfil de empresa es opcional
            _uiState.value = buildSuccessState(job, mediaItems, null, isLoadingCompany = false)
        }
    }

    /**
     * Construye la lista de URLs de imágenes
     */
    private fun buildImageUrls(mediaItems: List<MediaItem>, job: JobPost, fullSize: Boolean): List<String> {
        val urls = mutableListOf<String>()

        mediaItems.forEach { media ->
            val url = if (fullSize) media.getFullImageUrl() else media.getImageUrl()
            url?.let { if (it !in urls) urls.add(it) }
        }

        if (urls.isEmpty()) {
            job.embedded?.featuredMedia?.forEach { media ->
                val url = if (fullSize) media.getFullImageUrl() else media.getImageUrl()
                url?.let { if (it !in urls) urls.add(it) }
            }
        }

        return urls
    }

    /**
     * Construye la ubicación completa desde los diferentes sources
     */
    private fun buildUbicacionCompleta(job: JobPost, locationName: String?): UbicacionCompleta? {
        // Prioridad 1: meta.ubicacionCompleta
        job.meta?.ubicacionCompleta?.let { return it }

        // Prioridad 2: ubicacionDisplay de la API
        job.ubicacionDisplay?.let { display ->
            if (!display.departamento.isNullOrBlank()) {
                val nivel = when (display.nivel?.uppercase()) {
                    "DISTRITO" -> LocationType.DISTRITO
                    "PROVINCIA" -> LocationType.PROVINCIA
                    else -> LocationType.DEPARTAMENTO
                }
                return UbicacionCompleta(
                    departamento = display.departamento,
                    provincia = display.provincia ?: "",
                    distrito = display.distrito ?: "",
                    direccion = display.direccion,
                    lat = display.lat,
                    lng = display.lng,
                    nivel = nivel
                )
            }
        }

        // Prioridad 3: Parsear del nombre de taxonomía
        if (locationName == null) return null

        val parts = locationName.split(",").map { it.trim() }
        return when (parts.size) {
            1 -> UbicacionCompleta(
                departamento = parts[0],
                provincia = "",
                distrito = "",
                nivel = LocationType.DEPARTAMENTO
            )
            2 -> UbicacionCompleta(
                departamento = parts[1],
                provincia = parts[0],
                distrito = "",
                nivel = LocationType.PROVINCIA
            )
            3 -> UbicacionCompleta(
                departamento = parts[2],
                provincia = parts[1],
                distrito = parts[0],
                nivel = LocationType.DISTRITO
            )
            else -> UbicacionCompleta(
                departamento = parts.lastOrNull() ?: "",
                provincia = parts.getOrNull(parts.size - 2) ?: "",
                distrito = parts.firstOrNull() ?: "",
                nivel = LocationType.DISTRITO
            )
        }
    }

    /**
     * Procesa las acciones del usuario
     */
    fun onAction(action: JobDetailAction) {
        viewModelScope.launch {
            when (action) {
                is JobDetailAction.NavigateBack -> {
                    _events.emit(JobDetailEvent.NavigateBack)
                }
                is JobDetailAction.OpenImage -> {
                    _fullscreenImageIndex.value = action.index
                }
                is JobDetailAction.CloseImage -> {
                    _fullscreenImageIndex.value = null
                }
                is JobDetailAction.ContactPhone -> {
                    _events.emit(JobDetailEvent.OpenDialer(action.phone))
                }
                is JobDetailAction.ContactWhatsApp -> {
                    val cleanPhone = action.phone.replace(Regex("[\\s\\-()]"), "")
                    val whatsappNumber = if (cleanPhone.startsWith("+")) cleanPhone else "+51$cleanPhone"
                    _events.emit(JobDetailEvent.OpenWhatsApp(whatsappNumber.removePrefix("+")))
                }
                is JobDetailAction.ContactEmail -> {
                    _events.emit(JobDetailEvent.OpenEmail(action.email))
                }
                is JobDetailAction.NavigateToCompany -> {
                    _events.emit(JobDetailEvent.NavigateToCompany(action.companyName))
                }
                is JobDetailAction.Retry -> {
                    currentJob?.let { job ->
                        initialize(job, currentMediaItems)
                    }
                }
                is JobDetailAction.ApplyToJob -> {
                    applyToJob(action.message)
                }
                is JobDetailAction.ClearApplyError -> {
                    updateSuccessState { it.copy(applyError = null) }
                }
                is JobDetailAction.ClearApplySuccess -> {
                    updateSuccessState { it.copy(applySuccess = false) }
                }
            }
        }
    }

    /**
     * Verifica el estado de postulación del usuario actual para este trabajo
     */
    private suspend fun checkApplicationStatus(jobId: Int) {
        val token = AuthManager.token
        if (token == null) {
            Log.d(TAG, "No token available, user not logged in")
            updateSuccessState { it.copy(isCheckingApplication = false) }
            return
        }

        try {
            Log.d(TAG, "Checking application status for job $jobId")
            val response = WordPressApi.retrofitService.getApplicationStatus(
                token = "Bearer $token",
                jobId = jobId
            )
            Log.d(TAG, "Application status: hasApplied=${response.hasApplied}, status=${response.status}")
            updateSuccessState { state ->
                state.copy(
                    isCheckingApplication = false,
                    hasApplied = response.hasApplied,
                    applicationStatus = response.status,
                    applicationStatusLabel = response.statusLabel
                )
            }
        } catch (e: Exception) {
            Log.e(TAG, "Error checking application status: ${e.message}", e)
            updateSuccessState { it.copy(isCheckingApplication = false) }
        }
    }

    /**
     * Postularse al trabajo actual
     */
    private fun applyToJob(message: String) {
        val jobId = currentJob?.id ?: return
        val token = AuthManager.token ?: return

        viewModelScope.launch {
            updateSuccessState { it.copy(isApplying = true, applyError = null) }

            try {
                Log.d(TAG, "Applying to job $jobId")
                val data = mutableMapOf<String, Any>("job_id" to jobId)
                if (message.isNotBlank()) {
                    data["message"] = message
                }

                val response = WordPressApi.retrofitService.createApplication(
                    token = "Bearer $token",
                    data = data
                )

                if (response.success) {
                    Log.d(TAG, "Successfully applied to job $jobId")
                    updateSuccessState { state ->
                        state.copy(
                            isApplying = false,
                            applySuccess = true,
                            hasApplied = true,
                            applicationStatus = "pendiente",
                            applicationStatusLabel = "Pendiente"
                        )
                    }
                } else {
                    Log.e(TAG, "Failed to apply: ${response.message}")
                    updateSuccessState { it.copy(isApplying = false, applyError = response.message) }
                }
            } catch (e: retrofit2.HttpException) {
                val errorBody = e.response()?.errorBody()?.string()
                val errorMessage = when {
                    errorBody?.contains("already_applied") == true -> "Ya te has postulado a este trabajo"
                    errorBody?.contains("not_allowed") == true -> "Las empresas no pueden postularse"
                    else -> e.message ?: "Error al postularse"
                }
                Log.e(TAG, "HTTP error applying: $errorMessage", e)
                updateSuccessState { it.copy(isApplying = false, applyError = errorMessage) }
            } catch (e: Exception) {
                Log.e(TAG, "Error applying to job: ${e.message}", e)
                updateSuccessState { it.copy(isApplying = false, applyError = e.message ?: "Error al postularse") }
            }
        }
    }

    /**
     * Helper para actualizar el estado Success de forma segura
     */
    private fun updateSuccessState(update: (JobDetailUiState.Success) -> JobDetailUiState.Success) {
        val currentState = _uiState.value
        if (currentState is JobDetailUiState.Success) {
            _uiState.value = update(currentState)
        }
    }

    companion object {
        private const val TAG = "JobDetailViewModel"
    }
}

/**
 * Eventos one-time que requieren acción de la UI (navegación, intents, etc.)
 */
sealed interface JobDetailEvent {
    data object NavigateBack : JobDetailEvent
    data class OpenDialer(val phone: String) : JobDetailEvent
    data class OpenWhatsApp(val phoneNumber: String) : JobDetailEvent
    data class OpenEmail(val email: String) : JobDetailEvent
    data class NavigateToCompany(val companyName: String) : JobDetailEvent
}
