package agrochamba.com.ui.common

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.expandVertically
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.shrinkVertically
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Business
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.MyLocation
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.outlined.StarBorder
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Divider
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.focus.onFocusChanged
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import agrochamba.com.data.LocationSearchResult
import agrochamba.com.data.LocationService
import agrochamba.com.data.LocationType
import agrochamba.com.data.SedeEmpresa
import agrochamba.com.data.UbicacionCompleta
import agrochamba.com.data.repository.LocationRepository
import agrochamba.com.data.repository.QuickLocationSuggestion
import agrochamba.com.data.repository.SuggestionType
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import android.Manifest
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts

/**
 * =============================================================================
 * SMART LOCATION SELECTOR - Selector inteligente de ubicaciones
 * =============================================================================
 * 
 * Componente completo que incluye:
 * - Selector de sedes (para empresas)
 * - Campo de búsqueda con autocompletado
 * - Sugerencias rápidas (recientes, favoritas)
 * - Validación automática
 */

/**
 * Selector completo de ubicación con sedes y búsqueda
 * Usado en CreateJobScreen para empresas
 * 
 * Flujo de prioridad:
 * 1. Seleccionar sede existente (principal)
 * 2. Crear nueva ubicación (secundario)
 * 
 * @param selectedLocation Ubicación actualmente seleccionada
 * @param onLocationSelected Callback cuando se selecciona una ubicación
 * @param sedes Lista de sedes de la empresa
 * @param showSedesFirst Si true, muestra sedes primero (recomendado)
 * @param canManageSedes Si true, permite crear/editar sedes (solo empresas/admins)
 * @param onSedeCreated Callback cuando se crea una nueva sede
 * @param label Etiqueta del selector
 * @param placeholder Placeholder del campo de búsqueda
 */
@Composable
fun SmartLocationSelector(
    selectedLocation: UbicacionCompleta?,
    onLocationSelected: (UbicacionCompleta) -> Unit,
    sedes: List<SedeEmpresa> = emptyList(),
    showSedesFirst: Boolean = true,
    canManageSedes: Boolean = false,
    onSedeCreated: ((SedeEmpresa) -> Unit)? = null,
    label: String = "Ubicación",
    placeholder: String = "Buscar ubicación...",
    modifier: Modifier = Modifier,
    enabled: Boolean = true
) {
    val context = LocalContext.current
    val locationRepository = remember { LocationRepository.getInstance(context) }
    
    var showSearch by remember { mutableStateOf(false) }
    var selectedSedeId by remember { mutableStateOf<String?>(null) }
    var showSaveAsSedeDialog by remember { mutableStateOf(false) }
    var pendingLocationToSave by remember { mutableStateOf<UbicacionCompleta?>(null) }
    
    // Detectar si la ubicación actual corresponde a una sede
    LaunchedEffect(selectedLocation, sedes) {
        selectedSedeId = sedes.find { sede ->
            sede.ubicacion.departamento == selectedLocation?.departamento &&
            sede.ubicacion.provincia == selectedLocation?.provincia &&
            sede.ubicacion.distrito == selectedLocation?.distrito
        }?.id
    }
    
    Column(modifier = modifier.fillMaxWidth()) {
        // Label
        Text(
            text = label,
            style = MaterialTheme.typography.labelLarge,
            fontWeight = FontWeight.Medium,
            color = MaterialTheme.colorScheme.onSurface,
            modifier = Modifier.padding(bottom = 8.dp)
        )
        
        // Mostrar sedes primero si hay sedes disponibles
        if (showSedesFirst && sedes.isNotEmpty()) {
            SedesSelectorSection(
                sedes = sedes,
                selectedSedeId = selectedSedeId,
                onSedeSelected = { sede ->
                    selectedSedeId = sede.id
                    showSearch = false
                    onLocationSelected(sede.ubicacion)
                    // Agregar al historial reciente
                    locationRepository.addToRecent(sede.ubicacion)
                },
                onNewLocationClick = {
                    selectedSedeId = null
                    showSearch = true
                },
                enabled = enabled
            )
        }
        
        // Mostrar búsqueda si no hay sedes o si eligió "Nueva ubicación"
        AnimatedVisibility(
            visible = !showSedesFirst || sedes.isEmpty() || showSearch,
            enter = expandVertically() + fadeIn(),
            exit = shrinkVertically() + fadeOut()
        ) {
            Column {
                if (sedes.isNotEmpty()) {
                    Spacer(Modifier.height(12.dp))
                }
                
                LocationSearchField(
                    selectedLocation = if (selectedSedeId == null) selectedLocation else null,
                    onLocationSelected = { ubicacion ->
                        onLocationSelected(ubicacion)
                        locationRepository.addToRecent(ubicacion)
                        
                        // Si puede gestionar sedes y es una nueva ubicación, preguntar si guardar
                        if (canManageSedes && onSedeCreated != null) {
                            val isNewLocation = !sedes.any { sede ->
                                sede.ubicacion.departamento == ubicacion.departamento &&
                                sede.ubicacion.provincia == ubicacion.provincia &&
                                sede.ubicacion.distrito == ubicacion.distrito
                            }
                            if (isNewLocation) {
                                pendingLocationToSave = ubicacion
                                showSaveAsSedeDialog = true
                            }
                        }
                    },
                    placeholder = placeholder,
                    enabled = enabled
                )
            }
        }
        
        // Mostrar ubicación seleccionada si existe
        selectedLocation?.takeIf { it.distrito.isNotBlank() }?.let { ubicacion ->
            Spacer(Modifier.height(8.dp))
            SelectedLocationChip(
                ubicacion = ubicacion,
                onClear = {
                    selectedSedeId = null
                    showSearch = sedes.isNotEmpty()
                    onLocationSelected(UbicacionCompleta("", "", ""))
                }
            )
        }
    }
    
    // Diálogo para guardar como sede
    if (showSaveAsSedeDialog && pendingLocationToSave != null) {
        SaveAsSedeDialog(
            ubicacion = pendingLocationToSave!!,
            onConfirm = { nombre, esPrincipal ->
                val nuevaSede = SedeEmpresa(
                    id = java.util.UUID.randomUUID().toString(),
                    nombre = nombre,
                    ubicacion = pendingLocationToSave!!,
                    esPrincipal = esPrincipal,
                    activa = true
                )
                locationRepository.addSede(nuevaSede)
                onSedeCreated?.invoke(nuevaSede)
                showSaveAsSedeDialog = false
                pendingLocationToSave = null
            },
            onDismiss = {
                showSaveAsSedeDialog = false
                pendingLocationToSave = null
            }
        )
    }
}

/**
 * Sección de selector de sedes
 */
@Composable
private fun SedesSelectorSection(
    sedes: List<SedeEmpresa>,
    selectedSedeId: String?,
    onSedeSelected: (SedeEmpresa) -> Unit,
    onNewLocationClick: () -> Unit,
    enabled: Boolean
) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .background(
                MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f),
                RoundedCornerShape(12.dp)
            )
            .padding(12.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Text(
            text = "Selecciona una sede:",
            style = MaterialTheme.typography.labelMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        
        sedes.filter { it.activa }.forEach { sede ->
            SedeItem(
                sede = sede,
                isSelected = sede.id == selectedSedeId,
                onClick = { onSedeSelected(sede) },
                enabled = enabled
            )
        }
        
        // Opción para nueva ubicación
        NewLocationItem(
            isSelected = selectedSedeId == null,
            onClick = onNewLocationClick,
            enabled = enabled
        )
    }
}

/**
 * Item de sede individual
 */
@Composable
private fun SedeItem(
    sede: SedeEmpresa,
    isSelected: Boolean,
    onClick: () -> Unit,
    enabled: Boolean
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(8.dp))
            .background(
                if (isSelected) MaterialTheme.colorScheme.primaryContainer
                else MaterialTheme.colorScheme.surface
            )
            .border(
                width = if (isSelected) 2.dp else 1.dp,
                color = if (isSelected) MaterialTheme.colorScheme.primary
                       else MaterialTheme.colorScheme.outline.copy(alpha = 0.3f),
                shape = RoundedCornerShape(8.dp)
            )
            .clickable(enabled = enabled) { onClick() }
            .padding(12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        // Radio button visual
        Box(
            modifier = Modifier
                .size(20.dp)
                .border(
                    width = 2.dp,
                    color = if (isSelected) MaterialTheme.colorScheme.primary
                           else MaterialTheme.colorScheme.outline,
                    shape = CircleShape
                ),
            contentAlignment = Alignment.Center
        ) {
            if (isSelected) {
                Box(
                    modifier = Modifier
                        .size(12.dp)
                        .background(MaterialTheme.colorScheme.primary, CircleShape)
                )
            }
        }
        
        Spacer(Modifier.width(12.dp))
        
        // Icono de sede
        Icon(
            imageVector = Icons.Default.Business,
            contentDescription = null,
            tint = if (isSelected) MaterialTheme.colorScheme.primary
                   else MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.size(20.dp)
        )
        
        Spacer(Modifier.width(8.dp))
        
        // Información de la sede
        Column(modifier = Modifier.weight(1f)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    text = sede.nombre,
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.Medium,
                    color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer
                           else MaterialTheme.colorScheme.onSurface
                )
                if (sede.esPrincipal) {
                    Spacer(Modifier.width(4.dp))
                    Text(
                        text = "• Principal",
                        style = MaterialTheme.typography.labelSmall,
                        color = MaterialTheme.colorScheme.primary
                    )
                }
            }
            Text(
                text = sede.ubicacion.formatForSedeSelector(),
                style = MaterialTheme.typography.bodySmall,
                color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer.copy(alpha = 0.7f)
                       else MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        
        // Check si está seleccionado
        if (isSelected) {
            Icon(
                imageVector = Icons.Default.Check,
                contentDescription = "Seleccionado",
                tint = MaterialTheme.colorScheme.primary,
                modifier = Modifier.size(20.dp)
            )
        }
    }
}

/**
 * Item para nueva ubicación
 */
@Composable
private fun NewLocationItem(
    isSelected: Boolean,
    onClick: () -> Unit,
    enabled: Boolean
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(8.dp))
            .background(
                if (isSelected) MaterialTheme.colorScheme.secondaryContainer
                else Color.Transparent
            )
            .border(
                width = 1.dp,
                color = if (isSelected) MaterialTheme.colorScheme.secondary
                       else MaterialTheme.colorScheme.outline.copy(alpha = 0.5f),
                shape = RoundedCornerShape(8.dp)
            )
            .clickable(enabled = enabled) { onClick() }
            .padding(12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        // Radio button visual
        Box(
            modifier = Modifier
                .size(20.dp)
                .border(
                    width = 2.dp,
                    color = if (isSelected) MaterialTheme.colorScheme.secondary
                           else MaterialTheme.colorScheme.outline,
                    shape = CircleShape
                ),
            contentAlignment = Alignment.Center
        ) {
            if (isSelected) {
                Box(
                    modifier = Modifier
                        .size(12.dp)
                        .background(MaterialTheme.colorScheme.secondary, CircleShape)
                )
            }
        }
        
        Spacer(Modifier.width(12.dp))
        
        Icon(
            imageVector = Icons.Default.Add,
            contentDescription = null,
            tint = if (isSelected) MaterialTheme.colorScheme.secondary
                   else MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.size(20.dp)
        )
        
        Spacer(Modifier.width(8.dp))
        
        Text(
            text = "Nueva ubicación",
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.Medium,
            color = if (isSelected) MaterialTheme.colorScheme.onSecondaryContainer
                   else MaterialTheme.colorScheme.onSurfaceVariant
        )
    }
}

/**
 * Campo de búsqueda de ubicaciones con autocompletado
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun LocationSearchField(
    selectedLocation: UbicacionCompleta?,
    onLocationSelected: (UbicacionCompleta) -> Unit,
    placeholder: String = "Buscar distrito, provincia o departamento...",
    modifier: Modifier = Modifier,
    enabled: Boolean = true
) {
    val context = LocalContext.current
    val locationRepository = remember { LocationRepository.getInstance(context) }
    val scope = rememberCoroutineScope()
    val focusManager = LocalFocusManager.current
    
    var query by remember { mutableStateOf("") }
    var searchResults by remember { mutableStateOf<List<LocationSearchResult>>(emptyList()) }
    var quickSuggestions by remember { mutableStateOf<List<QuickLocationSuggestion>>(emptyList()) }
    var isSearching by remember { mutableStateOf(false) }
    var isFocused by remember { mutableStateOf(false) }
    var searchJob by remember { mutableStateOf<Job?>(null) }
    
    // Estado para GPS
    val locationService = remember { LocationService.getInstance(context) }
    var isGettingLocation by remember { mutableStateOf(false) }
    var gpsError by remember { mutableStateOf<String?>(null) }
    
    // Launcher para permisos de ubicación
    val permissionLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { permissions ->
        val granted = permissions[Manifest.permission.ACCESS_FINE_LOCATION] == true ||
                     permissions[Manifest.permission.ACCESS_COARSE_LOCATION] == true
        if (granted) {
            // Permiso otorgado, obtener ubicación
            scope.launch {
                isGettingLocation = true
                gpsError = null
                val ubicacion = locationService.getCurrentLocationAsUbicacion()
                isGettingLocation = false
                if (ubicacion != null) {
                    onLocationSelected(ubicacion)
                    query = ubicacion.formatOneLine()
                    focusManager.clearFocus()
                } else {
                    gpsError = "No pudimos detectar tu ubicación"
                }
            }
        } else {
            gpsError = "Permiso de ubicación denegado"
        }
    }
    
    // Función para obtener ubicación GPS
    fun getGpsLocation() {
        if (locationService.hasLocationPermission()) {
            scope.launch {
                isGettingLocation = true
                gpsError = null
                val ubicacion = locationService.getCurrentLocationAsUbicacion()
                isGettingLocation = false
                if (ubicacion != null) {
                    onLocationSelected(ubicacion)
                    query = ubicacion.formatOneLine()
                    focusManager.clearFocus()
                } else {
                    gpsError = "No pudimos detectar tu ubicación"
                }
            }
        } else {
            permissionLauncher.launch(arrayOf(
                Manifest.permission.ACCESS_FINE_LOCATION,
                Manifest.permission.ACCESS_COARSE_LOCATION
            ))
        }
    }
    
    // Cargar sugerencias rápidas al inicio
    LaunchedEffect(Unit) {
        quickSuggestions = locationRepository.getQuickSuggestions()
    }
    
    // Actualizar query cuando se selecciona ubicación externamente
    LaunchedEffect(selectedLocation) {
        if (selectedLocation != null && selectedLocation.distrito.isNotBlank()) {
            query = selectedLocation.formatOneLine()
        }
    }
    
    Column(modifier = modifier.fillMaxWidth()) {
        // Botón GPS: "Usar mi ubicación actual"
        Surface(
            modifier = Modifier
                .fillMaxWidth()
                .clip(RoundedCornerShape(12.dp))
                .clickable(enabled = enabled && !isGettingLocation) { getGpsLocation() },
            color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.3f),
            shape = RoundedCornerShape(12.dp)
        ) {
            Row(
                modifier = Modifier.padding(12.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                if (isGettingLocation) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(20.dp),
                        strokeWidth = 2.dp
                    )
                } else {
                    Icon(
                        imageVector = Icons.Default.MyLocation,
                        contentDescription = null,
                        tint = MaterialTheme.colorScheme.primary,
                        modifier = Modifier.size(20.dp)
                    )
                }
                Spacer(Modifier.width(12.dp))
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = if (isGettingLocation) "Detectando ubicación..." else "📍 Usar mi ubicación actual",
                        style = MaterialTheme.typography.bodyMedium,
                        fontWeight = FontWeight.Medium,
                        color = MaterialTheme.colorScheme.primary
                    )
                    if (gpsError != null) {
                        Text(
                            text = gpsError!!,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.error
                        )
                    }
                }
            }
        }
        
        Spacer(Modifier.height(12.dp))
        
        // Campo de búsqueda
        OutlinedTextField(
            value = query,
            onValueChange = { newQuery ->
                query = newQuery
                
                // Cancelar búsqueda anterior
                searchJob?.cancel()
                
                if (newQuery.length >= 2) {
                    isSearching = true
                    searchJob = scope.launch {
                        delay(300) // Debounce
                        searchResults = locationRepository.searchLocations(newQuery)
                        isSearching = false
                    }
                } else {
                    searchResults = emptyList()
                    isSearching = false
                }
            },
            placeholder = { 
                Text(
                    text = placeholder,
                    color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.6f)
                ) 
            },
            leadingIcon = {
                Icon(
                    imageVector = Icons.Default.Search,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.onSurfaceVariant
                )
            },
            trailingIcon = {
                when {
                    isSearching -> CircularProgressIndicator(
                        modifier = Modifier.size(20.dp),
                        strokeWidth = 2.dp
                    )
                    query.isNotEmpty() -> IconButton(onClick = {
                        query = ""
                        searchResults = emptyList()
                    }) {
                        Icon(Icons.Default.Close, contentDescription = "Limpiar")
                    }
                }
            },
            modifier = Modifier
                .fillMaxWidth()
                .onFocusChanged { isFocused = it.isFocused },
            enabled = enabled,
            singleLine = true,
            shape = RoundedCornerShape(12.dp),
            keyboardOptions = KeyboardOptions(imeAction = ImeAction.Search),
            keyboardActions = KeyboardActions(
                onSearch = {
                    if (searchResults.isNotEmpty()) {
                        searchResults.first().toUbicacionCompleta()?.let { 
                            onLocationSelected(it)
                            query = it.formatOneLine()
                            focusManager.clearFocus()
                        }
                    }
                }
            )
        )
        
        // Resultados de búsqueda o sugerencias rápidas
        // Mostrar cuando: hay resultados, hay sugerencias, o hay búsqueda sin resultados (para mostrar alternativas)
        val showDropdown = isFocused && (
            searchResults.isNotEmpty() || 
            (query.isEmpty() && quickSuggestions.isNotEmpty()) ||
            (query.length >= 2 && !isSearching) // Mostrar alternativas cuando no hay resultados
        )
        
        AnimatedVisibility(
            visible = showDropdown,
            enter = expandVertically() + fadeIn(),
            exit = shrinkVertically() + fadeOut()
        ) {
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 4.dp),
                shape = RoundedCornerShape(12.dp),
                elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
            ) {
                LazyColumn(
                    modifier = Modifier
                        .fillMaxWidth()
                        .heightIn(max = 300.dp),
                    contentPadding = PaddingValues(vertical = 8.dp)
                ) {
                    if (query.isEmpty() && quickSuggestions.isNotEmpty()) {
                        // Mostrar sugerencias rápidas
                        item {
                            Text(
                                text = "Sugerencias",
                                style = MaterialTheme.typography.labelMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)
                            )
                        }
                        
                        items(quickSuggestions) { suggestion ->
                            QuickSuggestionItem(
                                suggestion = suggestion,
                                onClick = {
                                    onLocationSelected(suggestion.ubicacion)
                                    query = suggestion.ubicacion.formatOneLine()
                                    focusManager.clearFocus()
                                }
                            )
                        }
                    } else {
                        // Mostrar resultados de búsqueda
                        items(searchResults) { result ->
                            SearchResultItem(
                                result = result,
                                onClick = {
                                    result.toUbicacionCompleta()?.let { ubicacion ->
                                        onLocationSelected(ubicacion)
                                        query = ubicacion.formatOneLine()
                                        focusManager.clearFocus()
                                    }
                                }
                            )
                        }
                        
                        if (searchResults.isEmpty() && query.length >= 2 && !isSearching) {
                            // Mostrar departamentos populares como alternativa
                            item {
                                Column(
                                    modifier = Modifier.padding(16.dp)
                                ) {
                                    Text(
                                        text = "Prueba con estos departamentos:",
                                        style = MaterialTheme.typography.labelMedium,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                                        modifier = Modifier.padding(bottom = 12.dp)
                                    )
                                }
                            }
                            
                            // Departamentos más populares
                            val popularDepartamentos = listOf(
                                "Ica" to "🍇 Zona agrícola principal",
                                "Lima" to "🏙️ Capital del Perú",
                                "La Libertad" to "🌾 Agroindustria del norte",
                                "Arequipa" to "🏔️ Sur productivo",
                                "Piura" to "🥭 Frutas tropicales",
                                "Lambayeque" to "🌿 Costa norte"
                            )
                            
                            items(popularDepartamentos) { (depto, descripcion) ->
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .clickable {
                                            // Buscar el departamento
                                            scope.launch {
                                                val results = locationRepository.searchLocations(depto, 1)
                                                results.firstOrNull()?.toUbicacionCompleta()?.let { ubicacion ->
                                                    onLocationSelected(ubicacion)
                                                    query = ubicacion.formatOneLine()
                                                    focusManager.clearFocus()
                                                }
                                            }
                                        }
                                        .padding(horizontal = 16.dp, vertical = 12.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Icon(
                                        imageVector = Icons.Default.LocationOn,
                                        contentDescription = null,
                                        tint = MaterialTheme.colorScheme.primary,
                                        modifier = Modifier.size(20.dp)
                                    )
                                    Spacer(Modifier.width(12.dp))
                                    Column(modifier = Modifier.weight(1f)) {
                                        Text(
                                            text = depto,
                                            style = MaterialTheme.typography.bodyMedium,
                                            fontWeight = FontWeight.Medium
                                        )
                                        Text(
                                            text = descripcion,
                                            style = MaterialTheme.typography.bodySmall,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

/**
 * Item de resultado de búsqueda
 */
@Composable
private fun SearchResultItem(
    result: LocationSearchResult,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        // Icono según tipo
        val (icon, iconColor) = when (result.tipo) {
            LocationType.DEPARTAMENTO -> Icons.Default.LocationOn to MaterialTheme.colorScheme.primary
            LocationType.PROVINCIA -> Icons.Default.LocationOn to MaterialTheme.colorScheme.secondary
            LocationType.DISTRITO -> Icons.Default.LocationOn to MaterialTheme.colorScheme.tertiary
        }
        
        Icon(
            imageVector = icon,
            contentDescription = null,
            tint = iconColor,
            modifier = Modifier.size(20.dp)
        )
        
        Spacer(Modifier.width(12.dp))
        
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = result.texto,
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = FontWeight.Medium
            )
            Text(
                text = when (result.tipo) {
                    LocationType.DEPARTAMENTO -> "Departamento"
                    LocationType.PROVINCIA -> "${result.departamento}"
                    LocationType.DISTRITO -> "${result.provincia}, ${result.departamento}"
                },
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        
        // Badge de tipo
        Surface(
            shape = RoundedCornerShape(4.dp),
            color = when (result.tipo) {
                LocationType.DEPARTAMENTO -> MaterialTheme.colorScheme.primaryContainer
                LocationType.PROVINCIA -> MaterialTheme.colorScheme.secondaryContainer
                LocationType.DISTRITO -> MaterialTheme.colorScheme.tertiaryContainer
            }
        ) {
            Text(
                text = when (result.tipo) {
                    LocationType.DEPARTAMENTO -> "Dpto"
                    LocationType.PROVINCIA -> "Prov"
                    LocationType.DISTRITO -> "Dist"
                },
                style = MaterialTheme.typography.labelSmall,
                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
            )
        }
    }
}

/**
 * Item de sugerencia rápida
 */
@Composable
private fun QuickSuggestionItem(
    suggestion: QuickLocationSuggestion,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        // Icono según tipo de sugerencia
        val icon = when (suggestion.tipo) {
            SuggestionType.PREFERRED -> Icons.Default.MyLocation
            SuggestionType.SEDE -> Icons.Default.Business
            SuggestionType.FAVORITE -> Icons.Default.Star
            SuggestionType.RECENT -> Icons.Default.History
        }
        
        val iconColor = when (suggestion.tipo) {
            SuggestionType.PREFERRED -> MaterialTheme.colorScheme.primary
            SuggestionType.SEDE -> MaterialTheme.colorScheme.secondary
            SuggestionType.FAVORITE -> Color(0xFFFFB300) // Amarillo
            SuggestionType.RECENT -> MaterialTheme.colorScheme.onSurfaceVariant
        }
        
        Icon(
            imageVector = icon,
            contentDescription = null,
            tint = iconColor,
            modifier = Modifier.size(20.dp)
        )
        
        Spacer(Modifier.width(12.dp))
        
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = suggestion.ubicacion.formatForSedeSelector(),
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = FontWeight.Medium
            )
            Text(
                text = suggestion.ubicacion.departamento,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

/**
 * Chip que muestra la ubicación seleccionada
 */
@Composable
fun SelectedLocationChip(
    ubicacion: UbicacionCompleta,
    onClear: (() -> Unit)? = null,
    onFavoriteToggle: (() -> Unit)? = null,
    isFavorite: Boolean = false,
    modifier: Modifier = Modifier
) {
    if (ubicacion.distrito.isBlank()) return
    
    Surface(
        modifier = modifier,
        shape = RoundedCornerShape(8.dp),
        color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.5f),
        border = androidx.compose.foundation.BorderStroke(
            1.dp,
            MaterialTheme.colorScheme.primary.copy(alpha = 0.3f)
        )
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                imageVector = Icons.Default.LocationOn,
                contentDescription = null,
                tint = MaterialTheme.colorScheme.primary,
                modifier = Modifier.size(18.dp)
            )
            
            Spacer(Modifier.width(8.dp))
            
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = ubicacion.distrito,
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.Medium,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
                Text(
                    text = "${ubicacion.provincia}, ${ubicacion.departamento}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }
            
            onFavoriteToggle?.let {
                IconButton(
                    onClick = it,
                    modifier = Modifier.size(32.dp)
                ) {
                    Icon(
                        imageVector = if (isFavorite) Icons.Default.Star else Icons.Outlined.StarBorder,
                        contentDescription = if (isFavorite) "Quitar de favoritos" else "Agregar a favoritos",
                        tint = if (isFavorite) Color(0xFFFFB300) else MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.size(18.dp)
                    )
                }
            }
            
            onClear?.let {
                IconButton(
                    onClick = it,
                    modifier = Modifier.size(32.dp)
                ) {
                    Icon(
                        imageVector = Icons.Default.Close,
                        contentDescription = "Quitar ubicación",
                        tint = MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.size(18.dp)
                    )
                }
            }
        }
    }
}

/**
 * Diálogo para guardar una ubicación como sede
 */
@Composable
private fun SaveAsSedeDialog(
    ubicacion: UbicacionCompleta,
    onConfirm: (nombre: String, esPrincipal: Boolean) -> Unit,
    onDismiss: () -> Unit
) {
    var nombre by remember { mutableStateOf("") }
    var esPrincipal by remember { mutableStateOf(false) }
    var nombreError by remember { mutableStateOf<String?>(null) }
    
    // Sugerir nombre basado en ubicación
    LaunchedEffect(ubicacion) {
        if (nombre.isBlank()) {
            nombre = "Sede ${ubicacion.distrito}"
        }
    }
    
    androidx.compose.ui.window.Dialog(onDismissRequest = onDismiss) {
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            shape = RoundedCornerShape(16.dp),
            elevation = CardDefaults.cardElevation(defaultElevation = 8.dp)
        ) {
            Column(
                modifier = Modifier.padding(24.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Título
                Row(
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Icon(
                        imageVector = Icons.Default.Business,
                        contentDescription = null,
                        tint = MaterialTheme.colorScheme.primary,
                        modifier = Modifier.size(28.dp)
                    )
                    Spacer(Modifier.width(12.dp))
                    Column {
                        Text(
                            text = "Guardar como Sede",
                            style = MaterialTheme.typography.titleLarge,
                            fontWeight = FontWeight.Bold
                        )
                        Text(
                            text = "Reutiliza esta ubicación en futuras publicaciones",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
                
                Divider()
                
                // Ubicación seleccionada (solo lectura)
                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                ) {
                    Row(
                        modifier = Modifier.padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(
                            imageVector = Icons.Default.LocationOn,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.primary,
                            modifier = Modifier.size(20.dp)
                        )
                        Spacer(Modifier.width(8.dp))
                        Column {
                            Text(
                                text = "${ubicacion.distrito}, ${ubicacion.provincia}",
                                style = MaterialTheme.typography.bodyMedium,
                                fontWeight = FontWeight.Medium
                            )
                            Text(
                                text = ubicacion.departamento,
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }
                
                // Campo nombre de sede
                OutlinedTextField(
                    value = nombre,
                    onValueChange = { 
                        nombre = it
                        nombreError = null
                    },
                    label = { Text("Nombre de la sede") },
                    placeholder = { Text("Ej: Oficina Principal, Almacén Ica...") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    isError = nombreError != null,
                    supportingText = nombreError?.let { { Text(it) } },
                    leadingIcon = {
                        Icon(Icons.Default.Business, contentDescription = null)
                    }
                )
                
                // Switch sede principal
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable { esPrincipal = !esPrincipal }
                        .padding(vertical = 4.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Column(modifier = Modifier.weight(1f)) {
                        Text(
                            text = "Marcar como sede principal",
                            style = MaterialTheme.typography.bodyMedium,
                            fontWeight = FontWeight.Medium
                        )
                        Text(
                            text = "Se seleccionará por defecto al crear trabajos",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                    androidx.compose.material3.Switch(
                        checked = esPrincipal,
                        onCheckedChange = { esPrincipal = it }
                    )
                }
                
                Spacer(Modifier.height(8.dp))
                
                // Botones
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    androidx.compose.material3.OutlinedButton(
                        onClick = onDismiss,
                        modifier = Modifier.weight(1f)
                    ) {
                        Text("Omitir")
                    }
                    
                    androidx.compose.material3.Button(
                        onClick = {
                            if (nombre.isBlank()) {
                                nombreError = "Ingresa un nombre para la sede"
                            } else {
                                onConfirm(nombre.trim(), esPrincipal)
                            }
                        },
                        modifier = Modifier.weight(1f)
                    ) {
                        Icon(
                            Icons.Default.Check,
                            contentDescription = null,
                            modifier = Modifier.size(18.dp)
                        )
                        Spacer(Modifier.width(4.dp))
                        Text("Guardar")
                    }
                }
            }
        }
    }
}

/**
 * Vista compacta de ubicación para cards
 */
@Composable
fun LocationBadge(
    departamento: String,
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(
            imageVector = Icons.Default.LocationOn,
            contentDescription = null,
            tint = MaterialTheme.colorScheme.primary,
            modifier = Modifier.size(14.dp)
        )
        Spacer(Modifier.width(4.dp))
        Text(
            text = departamento,
            style = MaterialTheme.typography.labelMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis
        )
    }
}

/**
 * Vista completa de ubicación para detalles del trabajo
 * Muestra: Distrito, Provincia, Departamento + Dirección exacta
 */
@Composable
fun LocationDetailView(
    ubicacion: UbicacionCompleta,
    modifier: Modifier = Modifier
) {
    // Ubicación jerárquica en una línea
    val locationLine = listOf(ubicacion.distrito, ubicacion.provincia, ubicacion.departamento)
        .filter { it.isNotBlank() }
        .joinToString(", ")
    
    // Dirección exacta (opcional)
    val direccion = ubicacion.direccion?.takeIf { it.isNotBlank() }

    Column(
        modifier = modifier
            .fillMaxWidth()
            .background(
                MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.15f),
                RoundedCornerShape(12.dp)
            )
            .border(
                width = 1.dp,
                color = MaterialTheme.colorScheme.primary.copy(alpha = 0.2f),
                shape = RoundedCornerShape(12.dp)
            )
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        // Ubicación principal (Distrito, Provincia, Departamento)
        Row(verticalAlignment = Alignment.CenterVertically) {
            Icon(
                imageVector = Icons.Default.LocationOn,
                contentDescription = null,
                tint = MaterialTheme.colorScheme.primary,
                modifier = Modifier.size(20.dp)
            )
            Spacer(Modifier.width(8.dp))
            Text(
                text = locationLine,
                style = MaterialTheme.typography.bodyLarge,
                fontWeight = FontWeight.SemiBold,
                color = MaterialTheme.colorScheme.onSurface
            )
        }

        // Dirección exacta (si existe)
        if (direccion != null) {
            Row(
                verticalAlignment = Alignment.Top,
                modifier = Modifier.padding(start = 2.dp)
            ) {
                Text(
                    text = "📮",
                    fontSize = 14.sp
                )
                Spacer(Modifier.width(10.dp))
                Text(
                    text = direccion,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    lineHeight = 20.sp
                )
            }
        }
        
        // Coordenadas (si existen, mostrar enlace a mapa)
        ubicacion.obtenerCoordenadas()?.let { coords ->
            if (coords.lat != 0.0 && coords.lng != 0.0) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.padding(start = 2.dp)
                ) {
                    Text(
                        text = "🗺️",
                        fontSize = 14.sp
                    )
                    Spacer(Modifier.width(10.dp))
                    Text(
                        text = "Ver en mapa",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.primary,
                        fontWeight = FontWeight.Medium
                    )
                }
            }
        }
    }
}

@Composable
private fun LocationDetailRow(
    label: String,
    value: String,
    icon: String
) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(
            text = icon,
            fontSize = 14.sp
        )
        Spacer(Modifier.width(8.dp))
        Text(
            text = "$label:",
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.width(90.dp)
        )
        Text(
            text = value,
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.Medium,
            modifier = Modifier.weight(1f)
        )
    }
}

