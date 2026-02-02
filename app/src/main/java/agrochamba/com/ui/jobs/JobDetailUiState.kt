package agrochamba.com.ui.jobs

import agrochamba.com.data.CompanyProfileResponse
import agrochamba.com.data.JobPost
import agrochamba.com.data.MediaItem
import agrochamba.com.data.UbicacionCompleta

/**
 * Estados de UI para la pantalla de detalle de trabajo
 * Siguiendo el patrón de sealed interface para manejo exhaustivo de estados
 */
sealed interface JobDetailUiState {

    /**
     * Estado inicial mientras se carga el trabajo
     */
    data object Loading : JobDetailUiState

    /**
     * Estado de éxito con todos los datos del trabajo
     */
    data class Success(
        val job: JobPost,
        val mediaItems: List<MediaItem> = emptyList(),
        val companyProfile: CompanyProfileResponse? = null,
        val ubicacionCompleta: UbicacionCompleta? = null,
        val allImageUrls: List<String> = emptyList(),
        val allFullImageUrls: List<String> = emptyList(),
        val companyName: String? = null,
        val isLoadingCompany: Boolean = false
    ) : JobDetailUiState

    /**
     * Estado de error cuando falla la carga
     */
    data class Error(
        val message: String,
        val canRetry: Boolean = true
    ) : JobDetailUiState
}

/**
 * Acciones que el usuario puede realizar en la pantalla de detalle
 */
sealed interface JobDetailAction {
    data object NavigateBack : JobDetailAction
    data class OpenImage(val index: Int) : JobDetailAction
    data object CloseImage : JobDetailAction
    data class ContactPhone(val phone: String) : JobDetailAction
    data class ContactWhatsApp(val phone: String) : JobDetailAction
    data class ContactEmail(val email: String) : JobDetailAction
    data class NavigateToCompany(val companyName: String) : JobDetailAction
    data object Retry : JobDetailAction
}
