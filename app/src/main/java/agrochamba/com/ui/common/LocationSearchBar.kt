package agrochamba.com.ui.common

import android.Manifest
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.expandVertically
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.shrinkVertically
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.GpsFixed
import androidx.compose.material.icons.filled.GpsOff
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.MyLocation
import androidx.compose.material.icons.filled.Public
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.focus.FocusRequester
import androidx.compose.ui.focus.focusRequester
import androidx.compose.ui.focus.onFocusChanged
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import agrochamba.com.data.LocationSearchResult
import agrochamba.com.data.LocationService
import agrochamba.com.data.LocationType
import agrochamba.com.data.PeruLocations
import agrochamba.com.data.UbicacionCompleta
import agrochamba.com.data.repository.LocationRepository
import agrochamba.com.ui.jobs.SelectedLocationFilter
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

/**
 * ===========================================================================
 * BUSCADOR INTELIGENTE DE UBICACIÓN - Experiencia tipo Google
 * ===========================================================================
 * 
 * Características:
 * - Un solo campo de búsqueda principal
 * - Autocompletado en tiempo real
 * - Sugerencias jerárquicas claras (📍Departamento, 🏘️Provincia, 📌Distrito)
 * - Botón GPS para usar ubicación actual (activable/desactivable)
 * - No obliga a jerarquía rígida
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun LocationSearchBar(
    selectedLocation: SelectedLocationFilter?,
    onLocationSelected: (SelectedLocationFilter?) -> Unit,
    modifier: Modifier = Modifier,
    placeholder: String = "¿Dónde buscas trabajo?",
    showGpsButton: Boolean = true,
    onGpsClick: (() -> Unit)? = null
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val locationRepository = remember { LocationRepository.getInstance(context) }
    val locationService = remember { LocationService.getInstance(context) }
    val focusManager = LocalFocusManager.current
    val focusRequester = remember { FocusRequester() }
    
    var searchQuery by remember { mutableStateOf("") }
    var searchResults by remember { mutableStateOf<List<LocationSearchResult>>(emptyList()) }
    var isFocused by remember { mutableStateOf(false) }
    var showPopularDepartments by remember { mutableStateOf(false) }
    
    // Estado para GPS
    var isGpsLoading by remember { mutableStateOf(false) }
    var isGpsActive by remember { mutableStateOf(false) }
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
                isGpsLoading = true
                gpsError = null
                val ubicacion = locationService.getCurrentLocationAsUbicacion()
                isGpsLoading = false
                if (ubicacion != null) {
                    isGpsActive = true
                    val filter = SelectedLocationFilter(
                        departamento = ubicacion.departamento,
                        provincia = ubicacion.provincia,
                        distrito = ubicacion.distrito,
                        displayLabel = "📍 ${ubicacion.distrito}, ${ubicacion.provincia}",
                        tipo = LocationType.DISTRITO
                    )
                    onLocationSelected(filter)
                    focusManager.clearFocus()
                    
                    // Guardar en recientes
                    locationRepository.addToRecent(ubicacion)
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
                isGpsLoading = true
                gpsError = null
                val ubicacion = locationService.getCurrentLocationAsUbicacion()
                isGpsLoading = false
                if (ubicacion != null) {
                    isGpsActive = true
                    val filter = SelectedLocationFilter(
                        departamento = ubicacion.departamento,
                        provincia = ubicacion.provincia,
                        distrito = ubicacion.distrito,
                        displayLabel = "📍 ${ubicacion.distrito}, ${ubicacion.provincia}",
                        tipo = LocationType.DISTRITO
                    )
                    onLocationSelected(filter)
                    focusManager.clearFocus()
                    
                    // Guardar en recientes
                    locationRepository.addToRecent(ubicacion)
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
    
    // Ubicaciones recientes
    val recentLocations by locationRepository.recentLocations.collectAsState(initial = emptyList())
    
    // Departamentos populares
    val popularDepartments = remember { PeruLocations.getPopularDepartamentos() }
    
    // Búsqueda con debounce
    LaunchedEffect(searchQuery) {
        if (searchQuery.length >= 2) {
            delay(150) // Debounce rápido para respuesta inmediata
            searchResults = PeruLocations.searchLocation(searchQuery, 10)
        } else {
            searchResults = emptyList()
            showPopularDepartments = searchQuery.isEmpty() && isFocused
        }
    }
    
    // Mostrar populares cuando se enfoca sin texto
    LaunchedEffect(isFocused) {
        showPopularDepartments = isFocused && searchQuery.isEmpty()
    }
    
    // Desactivar GPS cuando se limpia la ubicación
    LaunchedEffect(selectedLocation) {
        if (selectedLocation == null) {
            isGpsActive = false
        }
    }
    
    Column(modifier = modifier) {
        // Campo de búsqueda principal
        OutlinedTextField(
            value = if (selectedLocation != null && !isFocused) {
                selectedLocation.displayLabel
            } else {
                searchQuery
            },
            onValueChange = { 
                searchQuery = it
                if (it.isEmpty()) {
                    onLocationSelected(null)
                    isGpsActive = false
                }
            },
            placeholder = { 
                Text(
                    text = placeholder,
                    style = MaterialTheme.typography.bodyMedium
                ) 
            },
            leadingIcon = { 
                // Indicador de GPS activo o ícono normal
                if (isGpsActive) {
                    Icon(
                        Icons.Default.GpsFixed, 
                        contentDescription = "GPS activo",
                        tint = Color(0xFF4CAF50) // Verde para GPS activo
                    )
                } else {
                    Icon(
                        Icons.Default.LocationOn, 
                        contentDescription = null,
                        tint = if (selectedLocation != null) {
                            MaterialTheme.colorScheme.primary
                        } else {
                            MaterialTheme.colorScheme.onSurfaceVariant
                        }
                    )
                }
            },
            trailingIcon = {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    // Botón GPS
                    if (showGpsButton && selectedLocation == null && !isGpsLoading) {
                        IconButton(onClick = { getGpsLocation() }) {
                            Icon(
                                Icons.Default.MyLocation, 
                                contentDescription = "Usar mi ubicación",
                                tint = MaterialTheme.colorScheme.primary
                            )
                        }
                    }
                    
                    // Indicador de carga GPS
                    if (isGpsLoading) {
                        CircularProgressIndicator(
                            modifier = Modifier.size(24.dp).padding(4.dp),
                            strokeWidth = 2.dp
                        )
                    }
                    
                    // Botón limpiar/desactivar GPS
                    if (selectedLocation != null || searchQuery.isNotEmpty()) {
                        IconButton(onClick = { 
                            searchQuery = ""
                            searchResults = emptyList()
                            onLocationSelected(null)
                            isGpsActive = false
                            gpsError = null
                        }) {
                            Icon(
                                if (isGpsActive) Icons.Default.GpsOff else Icons.Default.Close, 
                                contentDescription = if (isGpsActive) "Desactivar GPS" else "Limpiar"
                            )
                        }
                    }
                }
            },
            modifier = Modifier
                .fillMaxWidth()
                .focusRequester(focusRequester)
                .onFocusChanged { state ->
                    isFocused = state.isFocused
                    if (state.isFocused && selectedLocation != null) {
                        searchQuery = ""
                    }
                },
            singleLine = true,
            shape = RoundedCornerShape(12.dp),
            colors = OutlinedTextFieldDefaults.colors(
                focusedBorderColor = if (isGpsActive) Color(0xFF4CAF50) else MaterialTheme.colorScheme.primary,
                unfocusedBorderColor = if (isGpsActive) Color(0xFF4CAF50).copy(alpha = 0.5f) else MaterialTheme.colorScheme.outline.copy(alpha = 0.5f)
            ),
            isError = gpsError != null,
            supportingText = if (gpsError != null) {
                { Text(gpsError!!, color = MaterialTheme.colorScheme.error) }
            } else if (isGpsActive) {
                { 
                    Text(
                        "📍 Mostrando trabajos cerca de ti", 
                        color = Color(0xFF4CAF50),
                        style = MaterialTheme.typography.bodySmall
                    ) 
                }
            } else null
        )
        
        // Panel de sugerencias
        AnimatedVisibility(
            visible = isFocused && (searchResults.isNotEmpty() || showPopularDepartments),
            enter = fadeIn() + expandVertically(),
            exit = fadeOut() + shrinkVertically()
        ) {
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .heightIn(max = 350.dp),
                shape = RoundedCornerShape(bottomStart = 12.dp, bottomEnd = 12.dp),
                elevation = CardDefaults.cardElevation(defaultElevation = 8.dp)
            ) {
                LazyColumn {
                    // Opción "Usar mi ubicación" destacada
                    if (showGpsButton && !isGpsActive) {
                        item {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clickable { getGpsLocation() }
                                    .background(MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.3f))
                                    .padding(horizontal = 16.dp, vertical = 12.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Icon(
                                    Icons.Default.MyLocation,
                                    contentDescription = null,
                                    tint = MaterialTheme.colorScheme.primary,
                                    modifier = Modifier.size(24.dp)
                                )
                                Spacer(Modifier.width(12.dp))
                                Column(modifier = Modifier.weight(1f)) {
                                    Text(
                                        text = "📍 Usar mi ubicación actual",
                                        style = MaterialTheme.typography.bodyMedium,
                                        fontWeight = FontWeight.SemiBold,
                                        color = MaterialTheme.colorScheme.primary
                                    )
                                    Text(
                                        text = "Encontrar trabajos cerca de ti",
                                        style = MaterialTheme.typography.bodySmall,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                }
                                if (isGpsLoading) {
                                    CircularProgressIndicator(
                                        modifier = Modifier.size(20.dp),
                                        strokeWidth = 2.dp
                                    )
                                }
                            }
                            HorizontalDivider()
                        }
                    }
                    
                    // Opción "Todo el Perú"
                    item {
                        LocationOptionItem(
                            icon = "🌍",
                            title = "Todo el Perú",
                            subtitle = "Ver todos los trabajos",
                            onClick = {
                                searchQuery = ""
                                searchResults = emptyList()
                                onLocationSelected(null)
                                isGpsActive = false
                                focusManager.clearFocus()
                            }
                        )
                        HorizontalDivider(modifier = Modifier.padding(horizontal = 16.dp))
                    }
                    
                    // Resultados de búsqueda
                    if (searchResults.isNotEmpty()) {
                        items(searchResults) { result ->
                            val (icon, levelLabel) = when (result.tipo) {
                                LocationType.DEPARTAMENTO -> "📍" to "Departamento"
                                LocationType.PROVINCIA -> "🏘️" to "Provincia"
                                LocationType.DISTRITO -> "📌" to "Distrito"
                            }
                            
                            LocationOptionItem(
                                icon = icon,
                                title = result.texto,
                                subtitle = if (result.tipo != LocationType.DEPARTAMENTO) {
                                    "$levelLabel en ${result.departamento}"
                                } else {
                                    levelLabel
                                },
                                onClick = {
                                    val filter = SelectedLocationFilter.fromSearchResult(result)
                                    onLocationSelected(filter)
                                    searchQuery = result.displayLabel
                                    searchResults = emptyList()
                                    isGpsActive = false
                                    focusManager.clearFocus()
                                    
                                    // Guardar en recientes
                                    val ubicacion = UbicacionCompleta(
                                        departamento = result.departamento,
                                        provincia = result.provincia ?: result.departamento,
                                        distrito = result.distrito ?: result.provincia ?: result.departamento
                                    )
                                    locationRepository.addToRecent(ubicacion)
                                }
                            )
                        }
                    }
                    
                    // Departamentos populares (cuando no hay búsqueda)
                    if (showPopularDepartments && searchResults.isEmpty()) {
                        item {
                            Text(
                                text = "Departamentos populares",
                                style = MaterialTheme.typography.labelSmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)
                            )
                        }
                        
                        items(popularDepartments) { (name, description) ->
                            LocationOptionItem(
                                icon = description.take(2), // Emoji del departamento
                                title = name,
                                subtitle = description.drop(3).trim(),
                                onClick = {
                                    val filter = SelectedLocationFilter(
                                        departamento = name,
                                        provincia = null,
                                        distrito = null,
                                        displayLabel = name,
                                        tipo = LocationType.DEPARTAMENTO
                                    )
                                    onLocationSelected(filter)
                                    searchQuery = name
                                    isGpsActive = false
                                    focusManager.clearFocus()
                                }
                            )
                        }
                        
                        // Ubicaciones recientes
                        if (recentLocations.isNotEmpty()) {
                            item {
                                HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp))
                                Text(
                                    text = "Búsquedas recientes",
                                    style = MaterialTheme.typography.labelSmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                    modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)
                                )
                            }
                            
                            items(recentLocations.take(3)) { ubicacion ->
                                LocationOptionItem(
                                    icon = "🕐",
                                    title = ubicacion.distrito,
                                    subtitle = "${ubicacion.provincia}, ${ubicacion.departamento}",
                                    onClick = {
                                        val filter = SelectedLocationFilter(
                                            departamento = ubicacion.departamento,
                                            provincia = ubicacion.provincia,
                                            distrito = ubicacion.distrito,
                                            displayLabel = "${ubicacion.distrito}, ${ubicacion.provincia}",
                                            tipo = LocationType.DISTRITO
                                        )
                                        onLocationSelected(filter)
                                        searchQuery = filter.displayLabel
                                        isGpsActive = false
                                        focusManager.clearFocus()
                                    }
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

/**
 * Item de opción de ubicación en el dropdown
 */
@Composable
private fun LocationOptionItem(
    icon: String,
    title: String,
    subtitle: String,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(
            text = icon,
            fontSize = 20.sp,
            modifier = Modifier.width(32.dp)
        )
        
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = title,
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = FontWeight.Medium,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
            Text(
                text = subtitle,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
        }
    }
}
