package agrochamba.com.ui.auth

import android.util.Log
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.FacebookPage
import agrochamba.com.data.FacebookPageRequest
import agrochamba.com.data.WordPressApi
import kotlinx.coroutines.launch

data class FacebookPagesState(
    val isLoading: Boolean = true,
    val pages: List<FacebookPage> = emptyList(),
    val error: String? = null,
    val successMessage: String? = null,
    val isAddingPage: Boolean = false,
    val isTestingPage: String? = null, // ID de la página que se está probando
    val isDeletingPage: String? = null // ID de la página que se está eliminando
)

class FacebookPagesViewModel : ViewModel() {
    
    var uiState by mutableStateOf(FacebookPagesState())
        private set
    
    init {
        loadPages()
    }
    
    /**
     * Cargar todas las páginas configuradas
     */
    fun loadPages() {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)
            
            try {
                val token = AuthManager.token
                if (token.isNullOrEmpty()) {
                    uiState = uiState.copy(
                        isLoading = false,
                        error = "Debes iniciar sesión como administrador"
                    )
                    return@launch
                }
                
                val response = WordPressApi.retrofitService.getFacebookPages("Bearer $token")
                
                if (response.success) {
                    uiState = uiState.copy(
                        isLoading = false,
                        pages = response.pages,
                        error = null
                    )
                    Log.d("FacebookPagesVM", "Páginas cargadas: ${response.pages.size}")
                } else {
                    uiState = uiState.copy(
                        isLoading = false,
                        error = response.message ?: "Error al cargar las páginas"
                    )
                }
            } catch (e: retrofit2.HttpException) {
                val errorMessage = when (e.code()) {
                    403 -> "No tienes permisos de administrador"
                    401 -> "Sesión expirada. Inicia sesión nuevamente."
                    else -> "Error al cargar las páginas (${e.code()})"
                }
                uiState = uiState.copy(isLoading = false, error = errorMessage)
                Log.e("FacebookPagesVM", "Error HTTP: ${e.code()}", e)
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isLoading = false,
                    error = "Error de conexión: ${e.message}"
                )
                Log.e("FacebookPagesVM", "Error al cargar páginas", e)
            }
        }
    }
    
    /**
     * Agregar una nueva página
     */
    fun addPage(pageId: String, pageName: String, pageToken: String) {
        if (pageId.isBlank() || pageToken.isBlank()) {
            uiState = uiState.copy(error = "El Page ID y Page Token son obligatorios")
            return
        }
        
        viewModelScope.launch {
            uiState = uiState.copy(isAddingPage = true, error = null, successMessage = null)
            
            try {
                val token = AuthManager.token
                if (token.isNullOrEmpty()) {
                    uiState = uiState.copy(isAddingPage = false, error = "Sesión expirada")
                    return@launch
                }
                
                val request = FacebookPageRequest(
                    pageId = pageId,
                    pageName = pageName.ifBlank { "Nueva Página" },
                    pageToken = pageToken
                )
                
                val response = WordPressApi.retrofitService.addFacebookPage("Bearer $token", request)
                
                if (response.success && response.page != null) {
                    uiState = uiState.copy(
                        isAddingPage = false,
                        successMessage = "Página agregada correctamente",
                        pages = uiState.pages + response.page
                    )
                    Log.d("FacebookPagesVM", "Página agregada: ${response.page.pageName}")
                } else {
                    uiState = uiState.copy(
                        isAddingPage = false,
                        error = response.message ?: "Error al agregar la página"
                    )
                }
            } catch (e: retrofit2.HttpException) {
                val errorBody = try { e.response()?.errorBody()?.string() } catch (_: Exception) { null }
                val errorMessage = when {
                    e.code() == 400 && errorBody?.contains("duplicate") == true -> "Esta página ya está configurada"
                    e.code() == 403 -> "No tienes permisos de administrador"
                    else -> "Error al agregar la página (${e.code()})"
                }
                uiState = uiState.copy(isAddingPage = false, error = errorMessage)
                Log.e("FacebookPagesVM", "Error HTTP al agregar: ${e.code()}", e)
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isAddingPage = false,
                    error = "Error de conexión: ${e.message}"
                )
                Log.e("FacebookPagesVM", "Error al agregar página", e)
            }
        }
    }
    
    /**
     * Eliminar una página
     */
    fun deletePage(internalId: String) {
        viewModelScope.launch {
            uiState = uiState.copy(isDeletingPage = internalId, error = null, successMessage = null)
            
            try {
                val token = AuthManager.token
                if (token.isNullOrEmpty()) {
                    uiState = uiState.copy(isDeletingPage = null, error = "Sesión expirada")
                    return@launch
                }
                
                val response = WordPressApi.retrofitService.deleteFacebookPage("Bearer $token", internalId)
                
                if (response.success) {
                    uiState = uiState.copy(
                        isDeletingPage = null,
                        successMessage = "Página eliminada correctamente",
                        pages = uiState.pages.filter { it.id != internalId }
                    )
                    Log.d("FacebookPagesVM", "Página eliminada: $internalId")
                } else {
                    uiState = uiState.copy(
                        isDeletingPage = null,
                        error = response.message ?: "Error al eliminar la página"
                    )
                }
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isDeletingPage = null,
                    error = "Error al eliminar: ${e.message}"
                )
                Log.e("FacebookPagesVM", "Error al eliminar página", e)
            }
        }
    }
    
    /**
     * Probar conexión con una página
     */
    fun testPage(internalId: String) {
        viewModelScope.launch {
            uiState = uiState.copy(isTestingPage = internalId, error = null, successMessage = null)
            
            try {
                val token = AuthManager.token
                if (token.isNullOrEmpty()) {
                    uiState = uiState.copy(isTestingPage = null, error = "Sesión expirada")
                    return@launch
                }
                
                val response = WordPressApi.retrofitService.testFacebookPage("Bearer $token", internalId)
                
                if (response.success) {
                    uiState = uiState.copy(
                        isTestingPage = null,
                        successMessage = "✅ ${response.pageName}: Conexión verificada"
                    )
                    // Recargar para obtener el nombre actualizado si cambió
                    loadPages()
                } else {
                    uiState = uiState.copy(
                        isTestingPage = null,
                        error = response.message ?: "Error al verificar la página"
                    )
                }
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isTestingPage = null,
                    error = "Error al probar: ${e.message}"
                )
                Log.e("FacebookPagesVM", "Error al probar página", e)
            }
        }
    }
    
    /**
     * Habilitar/deshabilitar una página
     */
    fun togglePageEnabled(internalId: String, enabled: Boolean) {
        viewModelScope.launch {
            try {
                val token = AuthManager.token ?: return@launch
                
                val response = WordPressApi.retrofitService.updateFacebookPage(
                    "Bearer $token",
                    internalId,
                    mapOf("enabled" to enabled)
                )
                
                if (response.success) {
                    // Actualizar localmente
                    uiState = uiState.copy(
                        pages = uiState.pages.map { page ->
                            if (page.id == internalId) page.copy(enabled = enabled) else page
                        }
                    )
                }
            } catch (e: Exception) {
                Log.e("FacebookPagesVM", "Error al actualizar página", e)
            }
        }
    }
    
    /**
     * Limpiar mensajes
     */
    fun clearMessages() {
        uiState = uiState.copy(error = null, successMessage = null)
    }
}

