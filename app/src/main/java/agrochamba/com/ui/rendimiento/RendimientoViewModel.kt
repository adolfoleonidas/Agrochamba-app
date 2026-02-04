package agrochamba.com.ui.rendimiento

import android.util.Log
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.RendimientoCategoria
import agrochamba.com.data.RendimientoItem
import agrochamba.com.data.WordPressApi
import kotlinx.coroutines.launch

private const val TAG = "RendimientoViewModel"

/**
 * Estado de la UI para la pantalla de rendimiento
 */
data class RendimientoUiState(
    val isLoading: Boolean = true,
    val error: String? = null,
    val totalGeneral: Double = 0.0,
    val periodo: String = "semana",
    val categorias: List<RendimientoCategoria> = emptyList(),
    val historial: List<RendimientoItem> = emptyList()
)

/**
 * ViewModel para la pantalla de rendimiento del trabajador
 */
class RendimientoViewModel : ViewModel() {

    var uiState by mutableStateOf(RendimientoUiState())
        private set

    init {
        loadRendimiento()
    }

    /**
     * Cargar resumen de rendimiento
     */
    fun loadRendimiento(periodo: String = "semana") {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)

            try {
                val token = AuthManager.token
                if (token.isNullOrBlank()) {
                    uiState = uiState.copy(
                        isLoading = false,
                        error = "Debes iniciar sesión para ver tu rendimiento"
                    )
                    return@launch
                }

                val authHeader = "Bearer $token"

                // Obtener resumen
                val resumenResponse = WordPressApi.retrofitService.getRendimientoResumen(
                    token = authHeader,
                    periodo = periodo
                )

                if (resumenResponse.success) {
                    uiState = uiState.copy(
                        isLoading = false,
                        totalGeneral = resumenResponse.totalGeneral,
                        periodo = resumenResponse.periodo,
                        categorias = resumenResponse.categorias
                    )
                    Log.d(TAG, "Resumen cargado: ${resumenResponse.categorias.size} categorías, total: ${resumenResponse.totalGeneral}")
                } else {
                    uiState = uiState.copy(
                        isLoading = false,
                        error = "Error al cargar el resumen"
                    )
                }

            } catch (e: retrofit2.HttpException) {
                val errorCode = e.code()
                Log.e(TAG, "HTTP Error $errorCode: ${e.message()}")

                val errorMsg = when (errorCode) {
                    401 -> "Sesión expirada. Inicia sesión nuevamente."
                    403 -> "No tienes permisos para ver esta información."
                    404 -> "No se encontraron registros de rendimiento."
                    else -> "Error al cargar datos: $errorCode"
                }

                uiState = uiState.copy(
                    isLoading = false,
                    error = errorMsg
                )
            } catch (e: Exception) {
                Log.e(TAG, "Error loading rendimiento", e)
                uiState = uiState.copy(
                    isLoading = false,
                    error = "Error de conexión: ${e.localizedMessage}"
                )
            }
        }
    }

    /**
     * Cargar historial detallado de rendimientos
     */
    fun loadHistorial(categoria: String? = null) {
        viewModelScope.launch {
            try {
                val token = AuthManager.token ?: return@launch
                val authHeader = "Bearer $token"

                val response = WordPressApi.retrofitService.getRendimiento(
                    token = authHeader,
                    categoria = categoria
                )

                if (response.success) {
                    uiState = uiState.copy(historial = response.data)
                    Log.d(TAG, "Historial cargado: ${response.data.size} registros")
                }
            } catch (e: Exception) {
                Log.e(TAG, "Error loading historial", e)
            }
        }
    }

    /**
     * Cambiar período de visualización
     */
    fun changePeriodo(nuevoPeriodo: String) {
        if (nuevoPeriodo != uiState.periodo) {
            loadRendimiento(nuevoPeriodo)
        }
    }

    /**
     * Refrescar datos
     */
    fun refresh() {
        loadRendimiento(uiState.periodo)
    }
}
