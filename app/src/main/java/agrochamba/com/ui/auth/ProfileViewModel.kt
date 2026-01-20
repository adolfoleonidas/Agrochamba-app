package agrochamba.com.ui.auth

import android.content.Context
import android.net.Uri
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.ApiErrorUtils
import agrochamba.com.data.AuthManager
import agrochamba.com.data.JobPost
import agrochamba.com.data.MyJobResponse
import agrochamba.com.data.UserProfileResponse
import agrochamba.com.data.WordPressApi
import agrochamba.com.data.toJobPost
import kotlinx.coroutines.launch
import retrofit2.HttpException
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody
import okio.BufferedSink
import okio.source

data class ProfileScreenState(
    val isLoading: Boolean = false,
    val error: String? = null,
    val myJobs: List<MyJobResponse> = emptyList(),
    val userProfile: UserProfileResponse? = null,
    val isLoadingProfile: Boolean = false,
    val favorites: List<JobPost> = emptyList(),
    val saved: List<JobPost> = emptyList(),
    val isLoadingFavorites: Boolean = false,
    val isLoadingSaved: Boolean = false,
    // Flag para indicar que una eliminación fue exitosa (utilizado por la UI para disparar efectos)
    val deleteSuccess: Boolean = false,
    // Flag para indicar que una actualización de perfil fue exitosa
    val updateSuccess: Boolean = false
)

class ProfileViewModel : ViewModel() {

    var uiState by mutableStateOf(ProfileScreenState())
        private set

    init {
        loadMyJobs()
        loadUserProfile()
        loadFavorites()
        loadSaved()
    }

    fun loadUserProfile() {
        viewModelScope.launch {
            loadUserProfileInternal()
        }
    }

    /**
     * Versión suspendible interna para cargar el perfil.
     * Útil cuando necesitamos esperar a que termine (ej: después de actualizar)
     */
    private suspend fun loadUserProfileInternal() {
        uiState = uiState.copy(isLoadingProfile = true, error = null)
        try {
            val token = AuthManager.token ?: throw Exception("No estás autenticado.")
            val authHeader = "Bearer $token"

            val profile = WordPressApi.retrofitService.getUserProfile(authHeader)
            uiState = uiState.copy(
                isLoadingProfile = false,
                userProfile = profile
            )

            // Actualizar el display name en AuthManager si cambió
            if (profile.displayName != AuthManager.userDisplayName) {
                AuthManager.userDisplayName = profile.displayName
            }
        } catch (e: Exception) {
            uiState = uiState.copy(
                isLoadingProfile = false,
                error = "No se pudo cargar el perfil: ${e.message}"
            )
        }
    }

    fun loadMyJobs() {
        // Solo carga los trabajos si el usuario es una empresa o admin
        if (!AuthManager.isUserAnEnterprise()) {
            uiState = uiState.copy(isLoading = false, myJobs = emptyList())
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)
            try {
                val token = AuthManager.token ?: throw Exception("No estás autenticado.")
                val authHeader = "Bearer $token"

                val response = WordPressApi.retrofitService.getMyJobs(authHeader, page = 1, perPage = 100)
                val jobs = response.jobs
                android.util.Log.d("ProfileViewModel", "Jobs cargados: ${jobs.size}")
                uiState = uiState.copy(isLoading = false, myJobs = jobs)

            } catch (e: Exception) {
                android.util.Log.e("ProfileViewModel", "Error al cargar trabajos: ${e.message}", e)
                // Usar ApiErrorUtils para obtener un mensaje amigable
                val friendlyMessage = ApiErrorUtils.getReadableApiError(e, "No se pudieron cargar tus anuncios")
                uiState = uiState.copy(isLoading = false, error = friendlyMessage)
            }
        }
    }

    fun deleteJob(jobId: Int) {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)
            try {
                val token = AuthManager.token ?: throw Exception("No estás autenticado.")
                val authHeader = "Bearer $token"

                val response = WordPressApi.retrofitService.deleteJob(authHeader, jobId)

                if (response.isSuccessful) {
                    // Recargar la lista de trabajos después de eliminar
                    uiState = uiState.copy(deleteSuccess = true, isLoading = false)
                    loadMyJobs()
                    // Resetear el flag después de un momento
                    kotlinx.coroutines.delay(100)
                    uiState = uiState.copy(deleteSuccess = false)
                } else {
                    // Usar ApiErrorUtils para obtener un mensaje amigable
                    val friendlyMessage = try {
                        ApiErrorUtils.getReadableApiError(
                            HttpException(response),
                            "No se pudo eliminar el trabajo"
                        )
                    } catch (e: Exception) {
                        "Error al eliminar el trabajo (${response.code()})"
                    }
                    uiState = uiState.copy(isLoading = false, error = friendlyMessage)
                }
            } catch (e: Exception) {
                // Usar ApiErrorUtils para obtener un mensaje amigable
                val friendlyMessage = ApiErrorUtils.getReadableApiError(e, "No se pudo eliminar el trabajo")
                uiState = uiState.copy(isLoading = false, error = friendlyMessage)
            }
        }
    }

    fun updateProfile(profileData: Map<String, Any>) {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)
            try {
                val token = AuthManager.token ?: throw Exception("No estás autenticado.")
                val authHeader = "Bearer $token"

                val response = WordPressApi.retrofitService.updateUserProfile(authHeader, profileData)

                if (response.isSuccessful) {
                    // Recargar el perfil y ESPERAR a que termine antes de quitar el loading
                    loadUserProfileInternal()
                    uiState = uiState.copy(isLoading = false, updateSuccess = true)
                    // Resetear el flag después de un momento
                    kotlinx.coroutines.delay(100)
                    uiState = uiState.copy(updateSuccess = false)
                } else {
                    uiState = uiState.copy(
                        isLoading = false,
                        error = "Error al actualizar el perfil: ${response.code()}"
                    )
                }
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isLoading = false,
                    error = "Error al actualizar el perfil: ${e.message}"
                )
            }
        }
    }

    fun uploadProfilePhoto(uri: Uri, context: Context) {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)
            try {
                val token = AuthManager.token ?: throw Exception("No estás autenticado.")
                val authHeader = "Bearer $token"

                val requestBody = createStreamingRequestBody(context, uri)
                val part = MultipartBody.Part.createFormData("file", "profile_photo.jpg", requestBody)

                val response = WordPressApi.retrofitService.uploadProfilePhoto(authHeader, part)

                if (response.success) {
                    // Recargar el perfil y ESPERAR a que termine
                    loadUserProfileInternal()
                    uiState = uiState.copy(isLoading = false, updateSuccess = true)
                    kotlinx.coroutines.delay(100)
                    uiState = uiState.copy(updateSuccess = false)
                } else {
                    uiState = uiState.copy(
                        isLoading = false,
                        error = "Error al subir la foto de perfil"
                    )
                }
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isLoading = false,
                    error = "Error al subir la foto: ${e.message}"
                )
            }
        }
    }

    fun deleteProfilePhoto() {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)
            try {
                val token = AuthManager.token ?: throw Exception("No estás autenticado.")
                val authHeader = "Bearer $token"

                val response = WordPressApi.retrofitService.deleteProfilePhoto(authHeader)

                if (response.isSuccessful) {
                    // Recargar el perfil y ESPERAR a que termine
                    loadUserProfileInternal()
                    uiState = uiState.copy(isLoading = false, updateSuccess = true)
                    kotlinx.coroutines.delay(100)
                    uiState = uiState.copy(updateSuccess = false)
                } else {
                    uiState = uiState.copy(
                        isLoading = false,
                        error = "Error al eliminar la foto: ${response.code()}"
                    )
                }
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isLoading = false,
                    error = "Error al eliminar la foto: ${e.message}"
                )
            }
        }
    }

    fun loadFavorites() {
        viewModelScope.launch {
            uiState = uiState.copy(isLoadingFavorites = true, error = null)
            try {
                val token = AuthManager.token ?: throw Exception("No estás autenticado.")
                val authHeader = "Bearer $token"

                val response = WordPressApi.retrofitService.getFavorites(authHeader)
                // Necesitamos convertir FavoriteJob a JobPost completo
                // Por ahora, cargamos los trabajos completos por ID
                val jobIds = response.jobs.map { it.id }
                val favoritesList = if (jobIds.isNotEmpty()) {
                    // Cargar trabajos completos desde la API con _embed
                    try {
                        val allJobs = WordPressApi.retrofitService.getJobs(page = 1, perPage = 100)
                        // Filtrar y mantener el orden de los IDs
                        val jobsMap = allJobs.associateBy { it.id }
                        jobIds.mapNotNull { id -> jobsMap[id] }
                    } catch (e: Exception) {
                        emptyList()
                    }
                } else {
                    emptyList()
                }

                uiState = uiState.copy(
                    isLoadingFavorites = false,
                    favorites = favoritesList
                )
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isLoadingFavorites = false,
                    error = "No se pudieron cargar los favoritos: ${e.message}"
                )
            }
        }
    }

    fun loadSaved() {
        viewModelScope.launch {
            uiState = uiState.copy(isLoadingSaved = true, error = null)
            try {
                val token = AuthManager.token ?: throw Exception("No estás autenticado.")
                val authHeader = "Bearer $token"

                val response = WordPressApi.retrofitService.getSaved(authHeader)
                // Necesitamos convertir FavoriteJob a JobPost completo
                val jobIds = response.jobs.map { it.id }
                val savedList = if (jobIds.isNotEmpty()) {
                    // Cargar trabajos completos desde la API con _embed
                    try {
                        val allJobs = WordPressApi.retrofitService.getJobs(page = 1, perPage = 100)
                        // Filtrar y mantener el orden de los IDs
                        val jobsMap = allJobs.associateBy { it.id }
                        jobIds.mapNotNull { id -> jobsMap[id] }
                    } catch (e: Exception) {
                        emptyList()
                    }
                } else {
                    emptyList()
                }

                uiState = uiState.copy(
                    isLoadingSaved = false,
                    saved = savedList
                )
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isLoadingSaved = false,
                    error = "No se pudieron cargar los guardados: ${e.message}"
                )
            }
        }
    }

    private fun createStreamingRequestBody(context: Context, uri: Uri): RequestBody {
        return object : RequestBody() {
            override fun contentType() = context.contentResolver.getType(uri)?.toMediaTypeOrNull()

            override fun writeTo(sink: BufferedSink) {
                context.contentResolver.openInputStream(uri)?.source()?.use {
                    sink.writeAll(it)
                }
            }
        }
    }
}