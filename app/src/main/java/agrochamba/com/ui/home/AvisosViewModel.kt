package agrochamba.com.ui.home

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.WordPressApi
import kotlinx.coroutines.launch

data class AvisosUiState(
    val isLoading: Boolean = true,
    val avisos: List<AvisoOperativo> = emptyList(),
    val error: String? = null,
    val isCreating: Boolean = false,
    val createSuccess: Boolean = false,
    val createError: String? = null,
    val isUpdating: Boolean = false,
    val updateSuccess: Boolean = false,
    val updateError: String? = null,
    val isDeleting: Boolean = false,
    val deleteSuccess: Boolean = false,
    val deleteError: String? = null
)

class AvisosViewModel : ViewModel() {

    var uiState by mutableStateOf(AvisosUiState())
        private set

    init {
        loadAvisos()
    }

    fun loadAvisos(ubicacion: String? = null) {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)

            try {
                val response = WordPressApi.retrofitService.getAvisos(
                    ubicacion = ubicacion,
                    perPage = 10
                )

                val avisos = if (response.isNotEmpty()) {
                    response.map { it.toUiModel() }
                } else {
                    // Usar avisos por defecto si no hay en el backend
                    defaultAvisos
                }

                uiState = uiState.copy(
                    isLoading = false,
                    avisos = avisos
                )
            } catch (e: Exception) {
                android.util.Log.e("AvisosViewModel", "Error cargando avisos", e)
                // Usar avisos por defecto en caso de error
                uiState = uiState.copy(
                    isLoading = false,
                    avisos = defaultAvisos,
                    error = null // No mostrar error, usar fallback silenciosamente
                )
            }
        }
    }

    fun createAviso(tipo: TipoAviso, mensaje: String, ubicacion: String? = null) {
        val token = AuthManager.token ?: return

        viewModelScope.launch {
            uiState = uiState.copy(isCreating = true, createError = null, createSuccess = false)

            try {
                val tipoBackend = when (tipo) {
                    TipoAviso.ANUNCIO -> "anuncio"
                    TipoAviso.HORARIO -> "horario_ingreso"
                    TipoAviso.CLIMA -> "alerta_clima"
                    TipoAviso.RESUMEN -> "resumen_trabajos"
                }

                val data = mutableMapOf<String, Any>(
                    "title" to getTituloForTipo(tipo),
                    "content" to mensaje,
                    "tipo_aviso" to tipoBackend
                )

                // Agregar campos específicos por tipo
                when (tipo) {
                    TipoAviso.RESUMEN -> {
                        data["ubicacion"] = ubicacion ?: "Perú"
                        data["preview"] = mensaje.take(150)
                    }
                    TipoAviso.CLIMA -> {
                        if (ubicacion != null) data["ubicacion"] = ubicacion
                    }
                    TipoAviso.HORARIO -> {
                        // Parsear horas del mensaje si es posible
                        // Por ahora usar valores por defecto
                        data["hora_operativos"] = "06:00 AM"
                        data["hora_administrativos"] = "08:00 AM"
                    }
                    else -> { }
                }

                val response = WordPressApi.retrofitService.createAviso(
                    token = "Bearer $token",
                    data = data
                )

                if (response.success) {
                    uiState = uiState.copy(
                        isCreating = false,
                        createSuccess = true
                    )
                    // Recargar avisos
                    loadAvisos()
                } else {
                    uiState = uiState.copy(
                        isCreating = false,
                        createError = response.message
                    )
                }
            } catch (e: Exception) {
                android.util.Log.e("AvisosViewModel", "Error creando aviso", e)
                uiState = uiState.copy(
                    isCreating = false,
                    createError = e.message ?: "Error al crear el aviso"
                )
            }
        }
    }

    private fun getTituloForTipo(tipo: TipoAviso): String {
        return when (tipo) {
            TipoAviso.ANUNCIO -> "Anuncio"
            TipoAviso.HORARIO -> "Horarios de Ingreso"
            TipoAviso.CLIMA -> "Alerta de Clima"
            TipoAviso.RESUMEN -> "Resumen de Trabajos"
        }
    }

    fun clearCreateState() {
        uiState = uiState.copy(createSuccess = false, createError = null)
    }

    fun updateAviso(
        avisoId: Int,
        tipo: TipoAviso,
        titulo: String,
        mensaje: String,
        ubicacion: String? = null,
        horaOperativos: String? = null,
        horaAdministrativos: String? = null
    ) {
        val token = AuthManager.token ?: return

        viewModelScope.launch {
            uiState = uiState.copy(isUpdating = true, updateError = null, updateSuccess = false)

            try {
                val tipoBackend = when (tipo) {
                    TipoAviso.ANUNCIO -> "anuncio"
                    TipoAviso.HORARIO -> "horario_ingreso"
                    TipoAviso.CLIMA -> "alerta_clima"
                    TipoAviso.RESUMEN -> "resumen_trabajos"
                }

                val data = mutableMapOf<String, Any>(
                    "title" to titulo,
                    "content" to mensaje,
                    "tipo_aviso" to tipoBackend
                )

                // Agregar campos específicos por tipo
                when (tipo) {
                    TipoAviso.RESUMEN -> {
                        data["ubicacion"] = ubicacion ?: "Perú"
                        data["preview"] = mensaje.take(150)
                    }
                    TipoAviso.CLIMA -> {
                        if (ubicacion != null) data["ubicacion"] = ubicacion
                    }
                    TipoAviso.HORARIO -> {
                        data["hora_operativos"] = horaOperativos ?: "06:00 AM"
                        data["hora_administrativos"] = horaAdministrativos ?: "08:00 AM"
                    }
                    else -> { }
                }

                val response = WordPressApi.retrofitService.updateAviso(
                    token = "Bearer $token",
                    avisoId = avisoId,
                    data = data
                )

                if (response.success) {
                    uiState = uiState.copy(
                        isUpdating = false,
                        updateSuccess = true
                    )
                    // Recargar avisos
                    loadAvisos()
                } else {
                    uiState = uiState.copy(
                        isUpdating = false,
                        updateError = response.message
                    )
                }
            } catch (e: Exception) {
                android.util.Log.e("AvisosViewModel", "Error actualizando aviso", e)
                uiState = uiState.copy(
                    isUpdating = false,
                    updateError = e.message ?: "Error al actualizar el aviso"
                )
            }
        }
    }

    fun deleteAviso(avisoId: Int) {
        val token = AuthManager.token ?: return

        viewModelScope.launch {
            uiState = uiState.copy(isDeleting = true, deleteError = null, deleteSuccess = false)

            try {
                val response = WordPressApi.retrofitService.deleteAviso(
                    token = "Bearer $token",
                    avisoId = avisoId
                )

                if (response.isSuccessful) {
                    uiState = uiState.copy(
                        isDeleting = false,
                        deleteSuccess = true
                    )
                    // Recargar avisos
                    loadAvisos()
                } else {
                    uiState = uiState.copy(
                        isDeleting = false,
                        deleteError = "Error al eliminar el aviso: ${response.code()}"
                    )
                }
            } catch (e: Exception) {
                android.util.Log.e("AvisosViewModel", "Error eliminando aviso", e)
                uiState = uiState.copy(
                    isDeleting = false,
                    deleteError = e.message ?: "Error al eliminar el aviso"
                )
            }
        }
    }

    fun clearUpdateState() {
        uiState = uiState.copy(updateSuccess = false, updateError = null)
    }

    fun clearDeleteState() {
        uiState = uiState.copy(deleteSuccess = false, deleteError = null)
    }
}
