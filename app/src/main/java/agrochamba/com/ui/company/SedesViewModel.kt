package agrochamba.com.ui.company

import android.app.Application
import android.util.Log
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.SedeEmpresa
import agrochamba.com.data.UbicacionCompleta
import agrochamba.com.data.WordPressApi
import agrochamba.com.data.repository.LocationRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import retrofit2.HttpException
import javax.inject.Inject

data class SedesUiState(
    val isLoading: Boolean = true,
    val isSaving: Boolean = false,
    val sedes: List<SedeEmpresa> = emptyList(),
    val error: String? = null,
    val successMessage: String? = null
)

@HiltViewModel
class SedesViewModel @Inject constructor(
    private val application: Application
) : ViewModel() {

    var uiState by mutableStateOf(SedesUiState())
        private set

    private val locationRepository: LocationRepository by lazy {
        LocationRepository.getInstance(application)
    }

    init {
        loadSedes()
    }

    /**
     * Sincroniza las sedes con el cache local del LocationRepository
     * para que otras partes de la app tengan acceso a las sedes actualizadas
     */
    private fun syncLocalCache(sedes: List<SedeEmpresa>) {
        locationRepository.saveCompanySedes(sedes)
    }

    /**
     * Carga las sedes desde el backend
     */
    fun loadSedes() {
        val token = AuthManager.token
        val companyId = AuthManager.userCompanyId

        Log.d("SedesViewModel", "loadSedes() - token: ${token?.take(20)}..., companyId: $companyId")

        if (token == null) {
            uiState = uiState.copy(
                isLoading = false,
                error = "Debes iniciar sesión para gestionar sedes"
            )
            return
        }

        if (companyId == null) {
            uiState = uiState.copy(
                isLoading = false,
                error = "No tienes una empresa asociada. Regístrate como empresa primero."
            )
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)
            try {
                Log.d("SedesViewModel", "Llamando a getCompanySedes para empresa ID: $companyId")
                val response = WordPressApi.retrofitService.getCompanySedes(
                    token = "Bearer $token",
                    companyId = companyId
                )

                val sedes = response.sedes.map { it.toSedeEmpresa() }
                uiState = uiState.copy(
                    isLoading = false,
                    sedes = sedes
                )
                // Sincronizar con cache local
                syncLocalCache(sedes)
                Log.d("SedesViewModel", "Cargadas ${sedes.size} sedes desde el backend")
            } catch (e: retrofit2.HttpException) {
                val errorBody = e.response()?.errorBody()?.string()
                Log.e("SedesViewModel", "HTTP Error ${e.code()}: $errorBody", e)
                val errorMsg = when (e.code()) {
                    404 -> "Endpoint no encontrado. Verifica que el plugin esté activo y actualiza los permalinks en WordPress."
                    401 -> "Sesión expirada. Inicia sesión nuevamente."
                    403 -> "No tienes permiso para ver las sedes de esta empresa."
                    else -> "Error del servidor (${e.code()})"
                }
                uiState = uiState.copy(
                    isLoading = false,
                    error = errorMsg
                )
            } catch (e: Exception) {
                Log.e("SedesViewModel", "Error cargando sedes: ${e.message}", e)
                uiState = uiState.copy(
                    isLoading = false,
                    error = "Error al cargar las sedes: ${e.message}"
                )
            }
        }
    }

    /**
     * Crea una nueva sede en el backend
     */
    fun createSede(nombre: String, ubicacion: UbicacionCompleta, esPrincipal: Boolean) {
        val token = AuthManager.token
        val companyId = AuthManager.userCompanyId

        Log.d("SedesViewModel", "createSede() - companyId: $companyId")

        if (token == null) {
            uiState = uiState.copy(error = "Debes iniciar sesión")
            return
        }

        if (companyId == null) {
            uiState = uiState.copy(error = "No tienes una empresa asociada")
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(isSaving = true, error = null)
            try {
                val sedeData = mapOf(
                    "nombre" to nombre,
                    "departamento" to ubicacion.departamento,
                    "provincia" to ubicacion.provincia,
                    "distrito" to ubicacion.distrito,
                    "direccion" to ubicacion.direccion,
                    "es_principal" to esPrincipal,
                    "lat" to ubicacion.lat,
                    "lng" to ubicacion.lng
                )

                Log.d("SedesViewModel", "Creando sede para empresa $companyId: $sedeData")

                val response = WordPressApi.retrofitService.createSede(
                    token = "Bearer $token",
                    companyId = companyId,
                    sedeData = sedeData
                )

                if (response.success) {
                    val nuevaSede = response.sede.toSedeEmpresa()
                    val updatedSedes = if (nuevaSede.esPrincipal) {
                        // Si la nueva es principal, desmarcar las demás
                        uiState.sedes.map { it.copy(esPrincipal = false) } + nuevaSede
                    } else {
                        uiState.sedes + nuevaSede
                    }

                    uiState = uiState.copy(
                        isSaving = false,
                        sedes = updatedSedes,
                        successMessage = "Sede creada exitosamente"
                    )
                    // Sincronizar con cache local
                    syncLocalCache(updatedSedes)
                    Log.d("SedesViewModel", "Sede creada: ${nuevaSede.nombre}")
                } else {
                    uiState = uiState.copy(
                        isSaving = false,
                        error = response.message
                    )
                }
            } catch (e: HttpException) {
                val errorBody = e.response()?.errorBody()?.string()
                Log.e("SedesViewModel", "HTTP Error ${e.code()} creando sede: $errorBody", e)

                // Intentar extraer mensaje del error JSON
                val errorMsg = try {
                    val json = org.json.JSONObject(errorBody ?: "{}")
                    json.optString("message", "Error ${e.code()}")
                } catch (jsonEx: Exception) {
                    when (e.code()) {
                        401 -> "Sesión expirada. Inicia sesión nuevamente."
                        403 -> "No tienes permiso para crear sedes en esta empresa."
                        404 -> "Empresa no encontrada."
                        else -> "Error del servidor (${e.code()})"
                    }
                }

                uiState = uiState.copy(
                    isSaving = false,
                    error = errorMsg
                )
            } catch (e: Exception) {
                Log.e("SedesViewModel", "Error creando sede: ${e.message}", e)
                uiState = uiState.copy(
                    isSaving = false,
                    error = "Error al crear la sede: ${e.message}"
                )
            }
        }
    }

    /**
     * Actualiza una sede existente en el backend
     */
    fun updateSede(sede: SedeEmpresa) {
        val token = AuthManager.token
        val companyId = AuthManager.userCompanyId

        if (token == null || companyId == null) {
            uiState = uiState.copy(error = "Error de autenticación")
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(isSaving = true, error = null)
            try {
                val sedeData = mapOf(
                    "nombre" to sede.nombre,
                    "departamento" to sede.ubicacion.departamento,
                    "provincia" to sede.ubicacion.provincia,
                    "distrito" to sede.ubicacion.distrito,
                    // Enviar "" en lugar de null para que el backend borre la dirección
                    "direccion" to (sede.ubicacion.direccion ?: ""),
                    "es_principal" to sede.esPrincipal,
                    "activa" to sede.activa,
                    "lat" to sede.ubicacion.lat,
                    "lng" to sede.ubicacion.lng
                )

                val response = WordPressApi.retrofitService.updateSede(
                    token = "Bearer $token",
                    companyId = companyId,
                    sedeId = sede.id,
                    sedeData = sedeData
                )

                if (response.success) {
                    val sedeActualizada = response.sede.toSedeEmpresa()
                    val updatedSedes = if (sedeActualizada.esPrincipal) {
                        // Si esta es principal, desmarcar las demás
                        uiState.sedes.map {
                            if (it.id == sedeActualizada.id) sedeActualizada
                            else it.copy(esPrincipal = false)
                        }
                    } else {
                        uiState.sedes.map {
                            if (it.id == sedeActualizada.id) sedeActualizada else it
                        }
                    }

                    uiState = uiState.copy(
                        isSaving = false,
                        sedes = updatedSedes,
                        successMessage = "Sede actualizada exitosamente"
                    )
                    // Sincronizar con cache local
                    syncLocalCache(updatedSedes)
                    Log.d("SedesViewModel", "Sede actualizada: ${sedeActualizada.nombre}")
                } else {
                    uiState = uiState.copy(
                        isSaving = false,
                        error = response.message
                    )
                }
            } catch (e: Exception) {
                Log.e("SedesViewModel", "Error actualizando sede: ${e.message}", e)
                uiState = uiState.copy(
                    isSaving = false,
                    error = "Error al actualizar la sede: ${e.message}"
                )
            }
        }
    }

    /**
     * Marca una sede como principal
     */
    fun setAsPrimary(sede: SedeEmpresa) {
        updateSede(sede.copy(esPrincipal = true))
    }

    /**
     * Elimina una sede del backend
     */
    fun deleteSede(sedeId: String) {
        val token = AuthManager.token
        val companyId = AuthManager.userCompanyId

        if (token == null || companyId == null) {
            uiState = uiState.copy(error = "Error de autenticación")
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(isSaving = true, error = null)
            try {
                val response = WordPressApi.retrofitService.deleteSede(
                    token = "Bearer $token",
                    companyId = companyId,
                    sedeId = sedeId
                )

                if (response.isSuccessful) {
                    val updatedSedes = uiState.sedes.filter { it.id != sedeId }

                    // Si quedaron sedes y ninguna es principal, hacer la primera como principal
                    val finalSedes = if (updatedSedes.isNotEmpty() && updatedSedes.none { it.esPrincipal }) {
                        updatedSedes.mapIndexed { index, sede ->
                            if (index == 0) sede.copy(esPrincipal = true) else sede
                        }
                    } else {
                        updatedSedes
                    }

                    uiState = uiState.copy(
                        isSaving = false,
                        sedes = finalSedes,
                        successMessage = "Sede eliminada exitosamente"
                    )
                    // Sincronizar con cache local
                    syncLocalCache(finalSedes)
                    Log.d("SedesViewModel", "Sede eliminada: $sedeId")
                } else {
                    uiState = uiState.copy(
                        isSaving = false,
                        error = "Error al eliminar la sede"
                    )
                }
            } catch (e: Exception) {
                Log.e("SedesViewModel", "Error eliminando sede: ${e.message}", e)
                uiState = uiState.copy(
                    isSaving = false,
                    error = "Error al eliminar la sede: ${e.message}"
                )
            }
        }
    }

    /**
     * Limpia el mensaje de error
     */
    fun clearError() {
        uiState = uiState.copy(error = null)
    }

    /**
     * Limpia el mensaje de éxito
     */
    fun clearSuccessMessage() {
        uiState = uiState.copy(successMessage = null)
    }
}
