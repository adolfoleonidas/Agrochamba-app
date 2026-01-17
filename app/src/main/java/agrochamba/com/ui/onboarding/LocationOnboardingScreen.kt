package agrochamba.com.ui.onboarding

import android.Manifest
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
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
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.MyLocation
import androidx.compose.material.icons.filled.Public
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import agrochamba.com.data.LocationService
import agrochamba.com.data.UbicacionCompleta
import agrochamba.com.data.repository.LocationRepository
import agrochamba.com.ui.common.LocationSearchField
import kotlinx.coroutines.launch

/**
 * =============================================================================
 * LOCATION ONBOARDING SCREEN - Pantalla de primera vez para ubicación
 * =============================================================================
 * 
 * Se muestra la primera vez que el usuario abre la app para:
 * - Configurar su ubicación preferida
 * - Obtener permisos de GPS
 * - Personalizar la experiencia
 */

/**
 * Pantalla de onboarding para TRABAJADORES
 */
@Composable
fun WorkerLocationOnboarding(
    onComplete: (UbicacionCompleta?) -> Unit,
    onSkip: () -> Unit,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val locationService = remember { LocationService.getInstance(context) }
    val locationRepository = remember { LocationRepository.getInstance(context) }
    
    var selectedOption by remember { mutableStateOf<LocationOption?>(null) }
    var detectedLocation by remember { mutableStateOf<UbicacionCompleta?>(null) }
    var customLocation by remember { mutableStateOf<UbicacionCompleta?>(null) }
    var isDetecting by remember { mutableStateOf(false) }
    var showSearch by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    
    // Launcher para permisos
    val permissionLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { permissions ->
        val granted = permissions[Manifest.permission.ACCESS_FINE_LOCATION] == true ||
                     permissions[Manifest.permission.ACCESS_COARSE_LOCATION] == true
        if (granted) {
            scope.launch {
                isDetecting = true
                error = null
                val ubicacion = locationService.getCurrentLocationAsUbicacion()
                isDetecting = false
                if (ubicacion != null) {
                    detectedLocation = ubicacion
                    selectedOption = LocationOption.GPS
                } else {
                    error = "No pudimos detectar tu ubicación"
                }
            }
        } else {
            error = "Permiso de ubicación denegado"
        }
    }
    
    fun detectLocation() {
        if (locationService.hasLocationPermission()) {
            scope.launch {
                isDetecting = true
                error = null
                val ubicacion = locationService.getCurrentLocationAsUbicacion()
                isDetecting = false
                if (ubicacion != null) {
                    detectedLocation = ubicacion
                    selectedOption = LocationOption.GPS
                } else {
                    error = "No pudimos detectar tu ubicación"
                }
            }
        } else {
            permissionLauncher.launch(arrayOf(
                Manifest.permission.ACCESS_FINE_LOCATION,
                Manifest.permission.ACCESS_COARSE_LOCATION
            ))
        }
    }
    
    Box(
        modifier = modifier
            .fillMaxSize()
            .background(
                Brush.verticalGradient(
                    colors = listOf(
                        MaterialTheme.colorScheme.primary.copy(alpha = 0.1f),
                        MaterialTheme.colorScheme.surface
                    )
                )
            )
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .verticalScroll(rememberScrollState())
                .padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(Modifier.height(48.dp))
            
            // Icono
            Box(
                modifier = Modifier
                    .size(80.dp)
                    .background(MaterialTheme.colorScheme.primary, CircleShape),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = Icons.Default.LocationOn,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.onPrimary,
                    modifier = Modifier.size(40.dp)
                )
            }
            
            Spacer(Modifier.height(24.dp))
            
            // Título
            Text(
                text = "👋 ¡Bienvenido a Agrochamba!",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold,
                textAlign = TextAlign.Center,
                color = MaterialTheme.colorScheme.onSurface
            )
            
            Spacer(Modifier.height(12.dp))
            
            Text(
                text = "¿Dónde te gustaría trabajar?",
                style = MaterialTheme.typography.titleMedium,
                textAlign = TextAlign.Center,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            
            Spacer(Modifier.height(32.dp))
            
            // Opción 1: GPS
            LocationOptionCard(
                icon = Icons.Default.MyLocation,
                title = "Usar mi ubicación actual",
                subtitle = if (detectedLocation != null) 
                    "Detectamos: ${detectedLocation!!.formatForSedeSelector()}" 
                else if (isDetecting) 
                    "Detectando..." 
                else 
                    "Detectar automáticamente",
                isSelected = selectedOption == LocationOption.GPS,
                isLoading = isDetecting,
                error = if (selectedOption == LocationOption.GPS) error else null,
                onClick = { detectLocation() }
            )
            
            Spacer(Modifier.height(12.dp))
            
            // Opción 2: Buscar
            LocationOptionCard(
                icon = Icons.Default.Search,
                title = "Buscar otra zona",
                subtitle = customLocation?.formatForSedeSelector() ?: "Elegir manualmente",
                isSelected = selectedOption == LocationOption.SEARCH,
                onClick = { 
                    showSearch = true
                    selectedOption = LocationOption.SEARCH
                }
            )
            
            // Campo de búsqueda expandible
            AnimatedVisibility(
                visible = showSearch,
                enter = expandVertically() + fadeIn(),
                exit = shrinkVertically() + fadeOut()
            ) {
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(top = 8.dp),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        LocationSearchField(
                            selectedLocation = customLocation,
                            onLocationSelected = { ubicacion ->
                                customLocation = ubicacion
                                selectedOption = LocationOption.SEARCH
                            },
                            placeholder = "Buscar departamento, provincia..."
                        )
                    }
                }
            }
            
            Spacer(Modifier.height(12.dp))
            
            // Opción 3: Todo el Perú
            LocationOptionCard(
                icon = Icons.Default.Public,
                title = "Estoy dispuesto a viajar",
                subtitle = "Ver trabajos de todo el Perú",
                isSelected = selectedOption == LocationOption.NATIONWIDE,
                onClick = { 
                    selectedOption = LocationOption.NATIONWIDE
                    showSearch = false
                }
            )
            
            Spacer(Modifier.height(32.dp))
            
            // Nota
            Text(
                text = "💡 Podrás cambiar esto cuando quieras",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
            
            Spacer(Modifier.weight(1f))
            
            // Botón continuar
            Button(
                onClick = {
                    val location = when (selectedOption) {
                        LocationOption.GPS -> detectedLocation
                        LocationOption.SEARCH -> customLocation
                        LocationOption.NATIONWIDE -> null
                        null -> null
                    }
                    
                    // Guardar preferencia
                    if (location != null) {
                        locationRepository.setPreferredLocation(location)
                    }
                    locationRepository.setSearchNationwide(selectedOption == LocationOption.NATIONWIDE)
                    
                    onComplete(location)
                },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(56.dp),
                enabled = selectedOption != null,
                shape = RoundedCornerShape(16.dp)
            ) {
                Text(
                    text = "Continuar",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
            }
            
            Spacer(Modifier.height(12.dp))
            
            // Saltar
            Text(
                text = "Omitir por ahora",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.primary,
                modifier = Modifier
                    .clickable { onSkip() }
                    .padding(8.dp)
            )
            
            Spacer(Modifier.height(24.dp))
        }
    }
}

/**
 * Pantalla de onboarding para EMPRESAS
 */
@Composable
fun CompanySedesOnboarding(
    onComplete: (List<UbicacionCompleta>) -> Unit,
    onSkip: () -> Unit,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val locationRepository = remember { LocationRepository.getInstance(context) }
    
    var sedes by remember { mutableStateOf<List<SedeOnboarding>>(listOf(SedeOnboarding())) }
    
    Box(
        modifier = modifier
            .fillMaxSize()
            .background(
                Brush.verticalGradient(
                    colors = listOf(
                        MaterialTheme.colorScheme.secondary.copy(alpha = 0.1f),
                        MaterialTheme.colorScheme.surface
                    )
                )
            )
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .verticalScroll(rememberScrollState())
                .padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(Modifier.height(48.dp))
            
            // Icono
            Text(text = "🏢", fontSize = 64.sp)
            
            Spacer(Modifier.height(24.dp))
            
            // Título
            Text(
                text = "Configura tus sedes",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold,
                textAlign = TextAlign.Center,
                color = MaterialTheme.colorScheme.onSurface
            )
            
            Spacer(Modifier.height(12.dp))
            
            Text(
                text = "Agregar las ubicaciones donde contratas personal\nhará más rápido publicar trabajos.",
                style = MaterialTheme.typography.bodyMedium,
                textAlign = TextAlign.Center,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            
            Spacer(Modifier.height(32.dp))
            
            // Lista de sedes
            sedes.forEachIndexed { index, sede ->
                SedeInputCard(
                    index = index + 1,
                    sede = sede,
                    onUpdate = { updatedSede ->
                        sedes = sedes.toMutableList().also { it[index] = updatedSede }
                    },
                    onRemove = if (sedes.size > 1) {
                        { sedes = sedes.toMutableList().also { it.removeAt(index) } }
                    } else null
                )
                Spacer(Modifier.height(12.dp))
            }
            
            // Botón agregar sede
            OutlinedButton(
                onClick = { sedes = sedes + SedeOnboarding() },
                modifier = Modifier.fillMaxWidth()
            ) {
                Icon(Icons.Default.Add, contentDescription = null)
                Spacer(Modifier.width(8.dp))
                Text("Agregar otra sede")
            }
            
            Spacer(Modifier.height(32.dp))
            
            // Nota
            Text(
                text = "💡 Podrás modificar tus sedes en cualquier momento",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
            
            Spacer(Modifier.weight(1f))
            
            // Botón continuar
            val validSedes = sedes.filter { it.ubicacion != null }
            
            Button(
                onClick = {
                    val ubicaciones = validSedes.mapNotNull { it.ubicacion }
                    onComplete(ubicaciones)
                },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(56.dp),
                enabled = validSedes.isNotEmpty(),
                shape = RoundedCornerShape(16.dp)
            ) {
                Text(
                    text = if (validSedes.isEmpty()) "Agrega al menos una sede" else "Continuar",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
            }
            
            Spacer(Modifier.height(12.dp))
            
            // Saltar
            Text(
                text = "Configurar después",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.primary,
                modifier = Modifier
                    .clickable { onSkip() }
                    .padding(8.dp)
            )
            
            Spacer(Modifier.height(24.dp))
        }
    }
}

// Modelos auxiliares

enum class LocationOption {
    GPS, SEARCH, NATIONWIDE
}

data class SedeOnboarding(
    val nombre: String = "",
    val ubicacion: UbicacionCompleta? = null
)

// Componentes auxiliares

@Composable
private fun LocationOptionCard(
    icon: ImageVector,
    title: String,
    subtitle: String,
    isSelected: Boolean,
    isLoading: Boolean = false,
    error: String? = null,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Surface(
        modifier = modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(16.dp))
            .clickable { onClick() }
            .then(
                if (isSelected) Modifier.border(
                    2.dp,
                    MaterialTheme.colorScheme.primary,
                    RoundedCornerShape(16.dp)
                ) else Modifier
            ),
        color = if (isSelected) 
            MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.5f) 
        else 
            MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
        shape = RoundedCornerShape(16.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Radio button visual
            Box(
                modifier = Modifier
                    .size(24.dp)
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
                            .size(14.dp)
                            .background(MaterialTheme.colorScheme.primary, CircleShape)
                    )
                }
            }
            
            Spacer(Modifier.width(16.dp))
            
            // Icono
            if (isLoading) {
                CircularProgressIndicator(
                    modifier = Modifier.size(24.dp),
                    strokeWidth = 2.dp
                )
            } else {
                Icon(
                    imageVector = icon,
                    contentDescription = null,
                    tint = if (isSelected) MaterialTheme.colorScheme.primary 
                          else MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.size(24.dp)
                )
            }
            
            Spacer(Modifier.width(12.dp))
            
            // Texto
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = title,
                    style = MaterialTheme.typography.bodyLarge,
                    fontWeight = FontWeight.Medium,
                    color = MaterialTheme.colorScheme.onSurface
                )
                Text(
                    text = subtitle,
                    style = MaterialTheme.typography.bodySmall,
                    color = if (error != null) MaterialTheme.colorScheme.error
                           else MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            
            // Check si seleccionado
            if (isSelected) {
                Icon(
                    imageVector = Icons.Default.Check,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(24.dp)
                )
            }
        }
    }
}

@Composable
private fun SedeInputCard(
    index: Int,
    sede: SedeOnboarding,
    onUpdate: (SedeOnboarding) -> Unit,
    onRemove: (() -> Unit)?,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
        )
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "Sede $index${if (index == 1) " (Principal)" else ""}",
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                
                if (onRemove != null) {
                    Text(
                        text = "Eliminar",
                        style = MaterialTheme.typography.labelMedium,
                        color = MaterialTheme.colorScheme.error,
                        modifier = Modifier.clickable { onRemove() }
                    )
                }
            }
            
            Spacer(Modifier.height(12.dp))
            
            LocationSearchField(
                selectedLocation = sede.ubicacion,
                onLocationSelected = { ubicacion ->
                    onUpdate(sede.copy(ubicacion = ubicacion))
                },
                placeholder = "Buscar ubicación..."
            )
        }
    }
}

