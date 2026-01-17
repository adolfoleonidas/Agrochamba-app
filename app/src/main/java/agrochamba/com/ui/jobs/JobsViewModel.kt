package agrochamba.com.ui.jobs

import android.util.Log
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.Category
import agrochamba.com.data.JobPost
import agrochamba.com.data.MediaItem
import agrochamba.com.data.LocationSearchResult
import agrochamba.com.data.LocationType
import agrochamba.com.data.PeruLocations
import agrochamba.com.data.UbicacionCompleta
import agrochamba.com.data.WordPressApi
import agrochamba.com.util.Result
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.Job
import kotlinx.coroutines.async
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch
import retrofit2.HttpException

/**
 * Ubicación seleccionada con información de nivel (departamento/provincia/distrito)
 * Permite filtrar a diferentes niveles de granularidad
 */
data class SelectedLocationFilter(
    val departamento: String,
    val provincia: String? = null,
    val distrito: String? = null,
    val displayLabel: String, // Lo que se muestra al usuario
    val tipo: LocationType // Nivel de filtrado
) {
    /**
     * Verifica si un trabajo coincide con este filtro de ubicación
     */
    fun matches(jobUbicacion: UbicacionCompleta?): Boolean {
        if (jobUbicacion == null) return false
        
        return when (tipo) {
            LocationType.DEPARTAMENTO -> 
                jobUbicacion.departamento.equals(departamento, ignoreCase = true)
            LocationType.PROVINCIA -> 
                jobUbicacion.departamento.equals(departamento, ignoreCase = true) &&
                jobUbicacion.provincia.equals(provincia, ignoreCase = true)
            LocationType.DISTRITO -> 
                jobUbicacion.departamento.equals(departamento, ignoreCase = true) &&
                jobUbicacion.provincia.equals(provincia, ignoreCase = true) &&
                jobUbicacion.distrito.equals(distrito, ignoreCase = true)
        }
    }
    
    /**
     * Verifica si un trabajo coincide usando el nombre de taxonomía (fallback)
     */
    fun matchesTaxonomyName(taxonomyName: String): Boolean {
        val normalizedName = taxonomyName.lowercase().trim()
        return when (tipo) {
            LocationType.DEPARTAMENTO -> 
                normalizedName.contains(departamento.lowercase())
            LocationType.PROVINCIA -> 
                normalizedName.contains(provincia?.lowercase() ?: "") ||
                normalizedName.contains(departamento.lowercase())
            LocationType.DISTRITO -> 
                normalizedName.contains(distrito?.lowercase() ?: "") ||
                normalizedName.contains(provincia?.lowercase() ?: "")
        }
    }
    
    companion object {
        fun fromSearchResult(result: LocationSearchResult): SelectedLocationFilter {
            return SelectedLocationFilter(
                departamento = result.departamento,
                provincia = result.provincia,
                distrito = result.distrito,
                displayLabel = result.displayLabel,
                tipo = result.tipo
            )
        }
    }
}

// El estado ahora incluye la lista de medios para el trabajo seleccionado
data class JobsScreenState(
    val allJobs: List<JobPost> = emptyList(),
    val filteredJobs: List<JobPost> = emptyList(),
    val locationCategories: List<Category> = emptyList(),
    val companyCategories: List<Category> = emptyList(),
    val jobTypeCategories: List<Category> = emptyList(),
    val cropCategories: List<Category> = emptyList(),
    val searchQuery: String = "",
    val selectedLocation: Category? = null, // Mantener para compatibilidad
    val selectedLocationFilter: SelectedLocationFilter? = null, // Nuevo: ubicación con nivel
    val selectedCompany: Category? = null,
    val selectedJobType: Category? = null,
    val selectedCrop: Category? = null,
    val selectedJob: JobPost? = null,
    val selectedJobMedia: List<MediaItem> = emptyList(), // Lista de imágenes para el detalle
    val isLoading: Boolean = true,
    val isLoadingMore: Boolean = false,
    val canLoadMore: Boolean = true,
    val currentPage: Int = 1,
    val isError: Boolean = false,
    val errorMessage: String? = null
)

@HiltViewModel
class JobsViewModel @Inject constructor() : androidx.lifecycle.ViewModel() {

    var uiState by mutableStateOf(JobsScreenState())
        private set

    init {
        Log.d("JobsViewModel", "🏗️ JobsViewModel INIT - Iniciando carga de datos")
        try {
            loadInitialData()
        } catch (e: Exception) {
            Log.e("JobsViewModel", "💥 Error en init: ${e.message}", e)
        }
    }

    private fun loadInitialData() {
        Log.d("JobsViewModel", "📥 loadInitialData() iniciado")
        viewModelScope.launch {
            uiState = JobsScreenState()
            try {
                Log.d("JobsViewModel", "🌐 Cargando datos desde API...")
                val jobsDeferred = async { WordPressApi.retrofitService.getJobs(page = 1) }
                val locationsDeferred = async { WordPressApi.retrofitService.getUbicaciones() }
                val companiesDeferred = async { WordPressApi.retrofitService.getEmpresas() }
                val jobTypesDeferred = async { WordPressApi.retrofitService.getTiposPuesto() }
                val cropsDeferred = async { WordPressApi.retrofitService.getCultivos() }

                Log.d("JobsViewModel", "⏳ Esperando respuestas de API...")
                val jobs = jobsDeferred.await()
                Log.d("JobsViewModel", "✅ Jobs cargados: ${jobs.size}")
                val locations = locationsDeferred.await()
                Log.d("JobsViewModel", "✅ Locations cargados: ${locations.size}")
                val companies = companiesDeferred.await()
                Log.d("JobsViewModel", "✅ Companies cargados: ${companies.size}")
                val jobTypes = jobTypesDeferred.await()
                Log.d("JobsViewModel", "✅ JobTypes cargados: ${jobTypes.size}")
                val crops = cropsDeferred.await()
                Log.d("JobsViewModel", "✅ Crops cargados: ${crops.size}")

                // Cargar imágenes para trabajos que no tienen featuredMedia pero tienen gallery_ids
                // También verificar si featuredMedia existe pero no tiene URL válida
                // Hacerlo en paralelo para mejor rendimiento
                val imageUrlsMap = mutableMapOf<Int, String>()
                
                // Primero, verificar featuredMedia embebido y agregar URLs válidas
                jobs.forEach { job ->
                    val featuredUrl = job.embedded?.featuredMedia?.firstOrNull()?.getImageUrl()
                    if (!featuredUrl.isNullOrBlank()) {
                        imageUrlsMap[job.id] = featuredUrl
                    }
                }
                
                // Luego, cargar imágenes desde gallery_ids para trabajos que no tienen featuredMedia válido
                val imageLoadDeferreds = jobs.mapNotNull { job ->
                    val hasValidFeaturedImage = !imageUrlsMap.containsKey(job.id)
                    if (hasValidFeaturedImage && !job.meta?.galleryIds.isNullOrEmpty()) {
                        val firstImageId = job.meta?.galleryIds?.firstOrNull()
                        if (firstImageId != null) {
                            async {
                                try {
                                    val mediaItem = WordPressApi.retrofitService.getMediaById(firstImageId)
                                    mediaItem.getImageUrl()?.let { url ->
                                        Pair(job.id, url)
                                    } ?: null
                                } catch (e: Exception) {
                                    Log.e("JobsViewModel", "Error loading image for job ${job.id} (ID: $firstImageId): ${e.message}")
                                    null
                                }
                            }
                        } else null
                    } else null
                }
                
                // Esperar todas las cargas en paralelo
                imageLoadDeferreds.forEach { deferred ->
                    deferred.await()?.let { (jobId, url) ->
                        if (!imageUrlsMap.containsKey(jobId)) {
                            imageUrlsMap[jobId] = url
                        }
                    }
                }
                
                Log.d("JobsViewModel", "Loaded ${imageUrlsMap.size} images for ${jobs.size} jobs")
                jobImageUrls.value = imageUrlsMap

                uiState = uiState.copy(
                    allJobs = jobs,
                    locationCategories = locations,
                    companyCategories = companies,
                    jobTypeCategories = jobTypes,
                    cropCategories = crops,
                    isLoading = false,
                    canLoadMore = jobs.isNotEmpty()
                )
                // Aplicar filtros después de cargar los datos
                applyFilters()
            } catch (e: Exception) {
                Log.e("JobsViewModel", "Error loading initial data", e)
                uiState = uiState.copy(isLoading = false, isError = true, errorMessage = e.message)
            }
        }
    }

    fun loadMoreJobs() {
        if (uiState.isLoadingMore || !uiState.canLoadMore) return
        viewModelScope.launch {
            uiState = uiState.copy(isLoadingMore = true)
            try {
                val nextPage = uiState.currentPage + 1
                val newJobs = WordPressApi.retrofitService.getJobs(page = nextPage)
                val updatedJobs = uiState.allJobs + newJobs
                uiState = uiState.copy(
                    allJobs = updatedJobs,
                    currentPage = nextPage,
                    isLoadingMore = false,
                    canLoadMore = newJobs.isNotEmpty()
                )
                applyFilters()
            } catch (e: HttpException) {
                if (e.code() == 400) {
                    uiState = uiState.copy(isLoadingMore = false, canLoadMore = false)
                } else {
                    uiState = uiState.copy(isLoadingMore = false, isError = true, errorMessage = e.message)
                }
            } catch (e: Exception) {
                uiState = uiState.copy(isLoadingMore = false, isError = true, errorMessage = e.message)
            }
        }
    }

    fun onFilterChange(query: String?, location: Category?, company: Category?, jobType: Category?, crop: Category?) {
        // Si se pasa null explícitamente, limpiar el filtro. Si es null implícito, mantener el valor actual
        uiState = uiState.copy(
            searchQuery = query ?: "",
            selectedLocation = location, // null significa "todos"
            selectedCompany = company, // null significa "todos"
            selectedJobType = jobType, // null significa "todos"
            selectedCrop = crop // null significa "todos"
        )
        applyFilters()
    }
    
    /**
     * Filtrado inteligente de ubicación (departamento/provincia/distrito)
     * Este es el método principal para el buscador tipo Google
     */
    fun onLocationFilterChange(locationFilter: SelectedLocationFilter?) {
        // Buscar la categoría de departamento para compatibilidad con filtros existentes
        val matchingCategory = if (locationFilter != null) {
            uiState.locationCategories.find { 
                it.name.equals(locationFilter.departamento, ignoreCase = true) 
            }
        } else null
        
        uiState = uiState.copy(
            selectedLocationFilter = locationFilter,
            selectedLocation = matchingCategory
        )
        applyFilters()
    }
    
    /**
     * Limpiar todos los filtros
     */
    fun clearAllFilters() {
        uiState = uiState.copy(
            searchQuery = "",
            selectedLocation = null,
            selectedLocationFilter = null,
            selectedCompany = null,
            selectedJobType = null,
            selectedCrop = null
        )
        applyFilters()
    }

    private fun applyFilters() {
        val normalizedQuery = uiState.searchQuery.trim()
        val locationResult = if (normalizedQuery.isNotBlank()) {
            PeruLocations.searchLocation(normalizedQuery, 1).firstOrNull()
        } else {
            null
        }

        val filtered = uiState.allJobs.filter { job ->
            // ==========================================
            // 1. FILTRO DE BÚSQUEDA (query)
            // ==========================================
            val matchesQuery = if (uiState.searchQuery.isBlank()) {
                true // Si no hay búsqueda, mostrar todos
            } else {
                val query = uiState.searchQuery.lowercase()
                val title = job.title?.rendered?.lowercase() ?: ""
                val content = job.content?.rendered?.lowercase() ?: ""
                val excerpt = job.excerpt?.rendered?.lowercase() ?: ""
                
                // Obtener nombres de taxonomías para búsqueda
                val jobTerms = job.embedded?.terms?.flatten() ?: emptyList()
                val empresaNames = jobTerms
                    .filter { it.taxonomy == "empresa" }
                    .map { it.name.lowercase() }
                    .joinToString(" ")
                val ubicacionNames = buildString {
                    jobTerms
                    .filter { it.taxonomy == "ubicacion" }
                    .map { it.name.lowercase() }
                    .joinToString(" ")
                        .takeIf { it.isNotBlank() }
                        ?.let { append(it) }
                    job.meta?.ubicacionCompleta?.let { ubicacion ->
                        if (isNotEmpty()) append(" ")
                        append(
                            listOf(ubicacion.distrito, ubicacion.provincia, ubicacion.departamento)
                                .filter { it.isNotBlank() }
                                .joinToString(" ")
                                .lowercase()
                        )
                    }
                }
                val cultivoNames = jobTerms
                    .filter { it.taxonomy == "cultivo" }
                    .map { it.name.lowercase() }
                    .joinToString(" ")
                val tipoPuestoNames = jobTerms
                    .filter { it.taxonomy == "tipo_puesto" }
                    .map { it.name.lowercase() }
                    .joinToString(" ")
                
                // Buscar en título, contenido, excerpt y nombres de taxonomías
                val matchesText = title.contains(query) || 
                content.contains(query) || 
                excerpt.contains(query) ||
                empresaNames.contains(query) ||
                ubicacionNames.contains(query) ||
                cultivoNames.contains(query) ||
                tipoPuestoNames.contains(query)

                // Búsqueda inteligente por ubicación
                val matchesLocationQuery = locationResult?.let { result ->
                    matchesLocationResult(job, result)
                } ?: false

                matchesText || matchesLocationQuery
            }
            
            // ==========================================
            // 2. FILTRO INTELIGENTE DE UBICACIÓN
            // ==========================================
            val matchesLocation = if (uiState.selectedLocationFilter != null) {
                val locationFilter = uiState.selectedLocationFilter!!
                
                // Primero intentar con ubicación completa (trabajos nuevos)
                val jobUbicacion = job.meta?.ubicacionCompleta ?: extractFromUbicacionTerm(job)
                
                if (jobUbicacion != null) {
                    locationFilter.matches(jobUbicacion)
                } else {
                    // Fallback: buscar en taxonomía (trabajos antiguos)
                    val jobTerms = job.embedded?.terms?.flatten() ?: emptyList()
                    val ubicacionTerms = jobTerms.filter { it.taxonomy == "ubicacion" }
                    ubicacionTerms.any { term -> locationFilter.matchesTaxonomyName(term.name) }
                }
            } else if (uiState.selectedLocation != null) {
                // Compatibilidad: filtrar por categoría de departamento
                val jobTerms = job.embedded?.terms?.flatten() ?: emptyList()
                val ubicacionIds = jobTerms.filter { it.taxonomy == "ubicacion" }.map { it.id }.toSet()
                ubicacionIds.contains(uiState.selectedLocation!!.id)
            } else {
                true // Sin filtro de ubicación
            }
            
            // ==========================================
            // 3. OTROS FILTROS POR TAXONOMÍAS
            // ==========================================
            val jobTerms = job.embedded?.terms?.flatten() ?: emptyList()
            val empresaTerms = jobTerms.filter { it.taxonomy == "empresa" }
            val tipoPuestoTerms = jobTerms.filter { it.taxonomy == "tipo_puesto" }
            val cultivoTerms = jobTerms.filter { it.taxonomy == "cultivo" }
            
            val empresaIds = empresaTerms.map { it.id }.toSet()
            val tipoPuestoIds = tipoPuestoTerms.map { it.id }.toSet()
            val cultivoIds = cultivoTerms.map { it.id }.toSet()
            
            val matchesCompany = uiState.selectedCompany == null || empresaIds.contains(uiState.selectedCompany!!.id)
            val matchesJobType = uiState.selectedJobType == null || tipoPuestoIds.contains(uiState.selectedJobType!!.id)
            val matchesCrop = uiState.selectedCrop == null || cultivoIds.contains(uiState.selectedCrop!!.id)

            // Todos los filtros deben cumplirse (AND lógico)
            matchesQuery && matchesLocation && matchesCompany && matchesJobType && matchesCrop
        }
        uiState = uiState.copy(filteredJobs = filtered)
    }

    private fun matchesLocationResult(job: JobPost, result: LocationSearchResult): Boolean {
        val ubicacion = job.meta?.ubicacionCompleta ?: extractFromUbicacionTerm(job)
        if (ubicacion == null) return false

        fun equalsIgnoreCase(a: String, b: String) = a.equals(b, ignoreCase = true)

        return when (result.tipo) {
            LocationType.DEPARTAMENTO -> equalsIgnoreCase(ubicacion.departamento, result.departamento)
            LocationType.PROVINCIA -> equalsIgnoreCase(ubicacion.provincia, result.provincia ?: "") &&
                equalsIgnoreCase(ubicacion.departamento, result.departamento)
            LocationType.DISTRITO -> equalsIgnoreCase(ubicacion.distrito, result.distrito ?: "") &&
                equalsIgnoreCase(ubicacion.provincia, result.provincia ?: "") &&
                equalsIgnoreCase(ubicacion.departamento, result.departamento)
        }
    }

    private fun extractFromUbicacionTerm(job: JobPost): UbicacionCompleta? {
        val terms = job.embedded?.terms?.flatten() ?: emptyList()
        val locationName = terms.firstOrNull { it.taxonomy == "ubicacion" }?.name ?: return null
        val parts = locationName.split(",").map { it.trim() }.filter { it.isNotBlank() }

        return when (parts.size) {
            1 -> UbicacionCompleta(
                departamento = parts[0],
                provincia = parts[0],
                distrito = parts[0]
            )
            2 -> UbicacionCompleta(
                departamento = parts[1],
                provincia = parts[0],
                distrito = parts[0]
            )
            3 -> UbicacionCompleta(
                departamento = parts[2],
                provincia = parts[1],
                distrito = parts[0]
            )
            else -> UbicacionCompleta(
                departamento = parts.lastOrNull() ?: "",
                provincia = parts.getOrNull(parts.size - 2) ?: "",
                distrito = parts.firstOrNull() ?: ""
            )
        }
    }

    // Al seleccionar un trabajo, también pedimos su galería de imágenes
    fun selectJob(job: JobPost) {
        uiState = uiState.copy(selectedJob = job, selectedJobMedia = emptyList()) // Limpia la galería anterior
        viewModelScope.launch {
            try {
                // Intentar obtener todas las imágenes del trabajo
                val allMedia = mutableListOf<MediaItem>()
                
                // 1. Intentar desde getJobImages (endpoint específico)
                try {
                    val response = WordPressApi.retrofitService.getJobImages(job.id)
                    val mediaFromResponse = response.images.mapNotNull { image ->
                        // Usar detail_url para el slider de detalle
                        image.getDetailUrl()?.let { url ->
                            MediaItem(id = image.id, source_url = url)
                        }
                    }
                    allMedia.addAll(mediaFromResponse)
                    Log.d("JobsViewModel", "Cargadas ${mediaFromResponse.size} imágenes desde getJobImages")
                } catch (e: Exception) {
                    Log.w("JobsViewModel", "Error en getJobImages: ${e.message}")
                }
                
                // 2. Si no hay imágenes, intentar desde gallery_ids
                if (allMedia.isEmpty() && !job.meta?.galleryIds.isNullOrEmpty()) {
                    try {
                        val galleryIds = job.meta?.galleryIds ?: emptyList()
                        val mediaFromGallery = galleryIds.mapNotNull { id ->
                            try {
                                val media = WordPressApi.retrofitService.getMediaById(id)
                                media.getImageUrl()?.let { url ->
                                    MediaItem(id = id, source_url = url)
                                }
                            } catch (e: Exception) {
                                null
                            }
                        }
                        allMedia.addAll(mediaFromGallery)
                        Log.d("JobsViewModel", "Cargadas ${mediaFromGallery.size} imágenes desde gallery_ids")
                    } catch (e: Exception) {
                        Log.w("JobsViewModel", "Error cargando desde gallery_ids: ${e.message}")
                    }
                }
                
                // 3. Si aún no hay imágenes, intentar desde getMediaForPost
                if (allMedia.isEmpty()) {
            try {
                val media = WordPressApi.retrofitService.getMediaForPost(job.id)
                        allMedia.addAll(media)
                        Log.d("JobsViewModel", "Cargadas ${media.size} imágenes desde getMediaForPost")
                    } catch (e: Exception) {
                        Log.w("JobsViewModel", "Error en getMediaForPost: ${e.message}")
                    }
                }
                
                // 4. Si aún no hay imágenes, usar featuredMedia embebido
                if (allMedia.isEmpty()) {
                    val featuredMedia = job.embedded?.featuredMedia?.filterNotNull() ?: emptyList()
                    allMedia.addAll(featuredMedia)
                    Log.d("JobsViewModel", "Cargadas ${featuredMedia.size} imágenes desde embedded")
                }
                
                uiState = uiState.copy(selectedJobMedia = allMedia)
                Log.d("JobsViewModel", "Total de imágenes cargadas: ${allMedia.size}")
            } catch (e: Exception) {
                Log.e("JobsViewModel", "Error loading media for job ${job.id}", e)
                // Si hay error, intentar al menos con embedded
                val featuredMedia = job.embedded?.featuredMedia?.filterNotNull() ?: emptyList()
                uiState = uiState.copy(selectedJobMedia = featuredMedia)
            }
        }
    }

    fun onDetailScreenNavigated() {
        uiState = uiState.copy(selectedJob = null, selectedJobMedia = emptyList())
    }
    
    fun retry() {
        loadInitialData()
    }
    
    fun refresh() {
        loadInitialData()
    }
    
    // Mapa para rastrear el estado de favoritos y guardados por job ID (observable)
    val favoriteStatus = mutableStateOf<Map<Int, Boolean>>(emptyMap())
    val savedStatus = mutableStateOf<Map<Int, Boolean>>(emptyMap())
    
    // Mapa para almacenar URLs de imágenes cargadas desde gallery_ids
    val jobImageUrls = mutableStateOf<Map<Int, String>>(emptyMap())
    
    // Obtener estado de favorito/guardado para un trabajo
    fun isFavorite(jobId: Int): Boolean = favoriteStatus.value[jobId] ?: false
    fun isSaved(jobId: Int): Boolean = savedStatus.value[jobId] ?: false
    
    // Cargar estado inicial de favoritos/guardados para un trabajo
    fun loadFavoriteSavedStatus(jobId: Int) {
        val token = AuthManager.token ?: return
        viewModelScope.launch {
            try {
                val authHeader = "Bearer $token"
                val status = WordPressApi.retrofitService.getFavoriteSavedStatus(authHeader, jobId)
                favoriteStatus.value = favoriteStatus.value + (jobId to status.isFavorite)
                savedStatus.value = savedStatus.value + (jobId to status.isSaved)
            } catch (e: Exception) {
                Log.e("JobsViewModel", "Error loading favorite/saved status for job $jobId", e)
                // Si no está autenticado o hay error, asumimos que no está en favoritos/guardados
                favoriteStatus.value = favoriteStatus.value + (jobId to false)
                savedStatus.value = savedStatus.value + (jobId to false)
            }
        }
    }
    
    // Toggle favorito
    fun toggleFavorite(jobId: Int, onSuccess: ((Boolean) -> Unit)? = null) {
        val token = AuthManager.token ?: return
        viewModelScope.launch {
            try {
                val authHeader = "Bearer $token"
                val currentStatus = favoriteStatus.value[jobId] ?: false
                // Optimistic update
                favoriteStatus.value = favoriteStatus.value + (jobId to !currentStatus)
                
                val response = WordPressApi.retrofitService.toggleFavorite(
                    authHeader,
                    mapOf("job_id" to jobId)
                )
                
                favoriteStatus.value = favoriteStatus.value + (jobId to response.isFavorite)
                onSuccess?.invoke(response.isFavorite)
            } catch (e: Exception) {
                Log.e("JobsViewModel", "Error toggling favorite for job $jobId", e)
                // Revertir cambio optimista
                val currentStatus = favoriteStatus.value[jobId] ?: false
                favoriteStatus.value = favoriteStatus.value + (jobId to !currentStatus)
            }
        }
    }
    
    // Toggle guardado
    fun toggleSaved(jobId: Int, onSuccess: ((Boolean) -> Unit)? = null) {
        val token = AuthManager.token ?: return
        viewModelScope.launch {
            try {
                val authHeader = "Bearer $token"
                val currentStatus = savedStatus.value[jobId] ?: false
                // Optimistic update
                savedStatus.value = savedStatus.value + (jobId to !currentStatus)
                
                val response = WordPressApi.retrofitService.toggleSaved(
                    authHeader,
                    mapOf("job_id" to jobId)
                )
                
                savedStatus.value = savedStatus.value + (jobId to response.isSaved)
                onSuccess?.invoke(response.isSaved)
            } catch (e: Exception) {
                Log.e("JobsViewModel", "Error toggling saved for job $jobId", e)
                // Revertir cambio optimista
                val currentStatus = savedStatus.value[jobId] ?: false
                savedStatus.value = savedStatus.value + (jobId to !currentStatus)
            }
        }
    }

}