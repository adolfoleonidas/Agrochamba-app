package agrochamba.com.ui.common

/**
 * Utilidades y extensiones para migración a StateFlow.
 * Este archivo documenta el patrón de migración usado en la app.
 */

/**
 * Patrón de migración de mutableStateOf a StateFlow:
 * 
 * ANTES:
 * ```kotlin
 * var uiState by mutableStateOf(State())
 *     private set
 * 
 * fun update() {
 *     uiState = uiState.copy(...)
 * }
 * ```
 * 
 * DESPUÉS:
 * ```kotlin
 * private val _uiState = MutableStateFlow(State())
 * val uiState: StateFlow<State> = _uiState.asStateFlow()
 * 
 * fun update() {
 *     _uiState.value = _uiState.value.copy(...)
 * }
 * ```
 * 
 * En la UI:
 * ANTES: val uiState = viewModel.uiState
 * DESPUÉS: val uiState by viewModel.uiState.collectAsState()
 */

