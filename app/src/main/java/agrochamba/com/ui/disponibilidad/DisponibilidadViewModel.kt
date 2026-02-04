package agrochamba.com.ui.disponibilidad

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.WordPressApi
import kotlinx.coroutines.launch

data class DisponibilidadUiState(
    val isLoading: Boolean = true,
    val disponibleParaTrabajo: Boolean = false,
    val tieneContratoActivo: Boolean = false,
    val visibleParaEmpresas: Boolean = false,
    val ubicacion: String? = null,
    val ubicacionLat: Double? = null,
    val ubicacionLng: Double? = null,
    val mensaje: String = "",
    val error: String? = null
)

/**
 * ViewModel para gestionar la disponibilidad del trabajador (estilo Uber)
 * El trabajador puede activar/desactivar su disponibilidad para ser encontrado por empresas
 */
class DisponibilidadViewModel : ViewModel() {

    var uiState by mutableStateOf(DisponibilidadUiState())
        private set

    init {
        loadDisponibilidad()
    }

    fun loadDisponibilidad() {
        val token = AuthManager.authToken ?: return

        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)

            try {
                val response = WordPressApi.retrofitService.getMiDisponibilidad("Bearer $token")

                if (response.success) {
                    uiState = uiState.copy(
                        isLoading = false,
                        disponibleParaTrabajo = response.disponibleParaTrabajo,
                        tieneContratoActivo = response.tieneContratoActivo,
                        visibleParaEmpresas = response.visibleParaEmpresas,
                        ubicacion = response.ubicacion,
                        ubicacionLat = response.ubicacionLat,
                        ubicacionLng = response.ubicacionLng,
                        mensaje = response.mensaje
                    )
                } else {
                    uiState = uiState.copy(
                        isLoading = false,
                        error = "No se pudo cargar tu estado de disponibilidad"
                    )
                }
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isLoading = false,
                    error = e.message ?: "Error de conexión"
                )
            }
        }
    }

    /**
     * Toggle de disponibilidad (tipo Uber)
     */
    fun toggleDisponibilidad() {
        val token = AuthManager.authToken ?: return
        val nuevoEstado = !uiState.disponibleParaTrabajo

        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)

            try {
                val response = WordPressApi.retrofitService.setMiDisponibilidad(
                    token = "Bearer $token",
                    data = mapOf("disponible" to nuevoEstado)
                )

                if (response.success) {
                    uiState = uiState.copy(
                        isLoading = false,
                        disponibleParaTrabajo = response.disponibleParaTrabajo,
                        tieneContratoActivo = response.tieneContratoActivo,
                        visibleParaEmpresas = response.visibleParaEmpresas,
                        mensaje = response.mensaje
                    )
                } else {
                    uiState = uiState.copy(
                        isLoading = false,
                        error = "No se pudo actualizar tu disponibilidad"
                    )
                }
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isLoading = false,
                    error = e.message ?: "Error de conexión"
                )
            }
        }
    }

    /**
     * Actualizar ubicación y coordenadas
     */
    fun updateUbicacion(ubicacion: String, lat: Double? = null, lng: Double? = null) {
        val token = AuthManager.authToken ?: return

        viewModelScope.launch {
            try {
                val data = mutableMapOf<String, Any>("ubicacion" to ubicacion)
                if (lat != null && lng != null) {
                    data["lat"] = lat
                    data["lng"] = lng
                }

                val response = WordPressApi.retrofitService.setMiDisponibilidad(
                    token = "Bearer $token",
                    data = data
                )

                if (response.success) {
                    uiState = uiState.copy(
                        ubicacion = ubicacion,
                        ubicacionLat = lat,
                        ubicacionLng = lng
                    )
                }
            } catch (e: Exception) {
                // Silently fail - ubicación no es crítica
            }
        }
    }
}
