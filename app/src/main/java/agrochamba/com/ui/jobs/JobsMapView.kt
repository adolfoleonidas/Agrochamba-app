package agrochamba.com.ui.jobs

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.expandVertically
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.shrinkVertically
import androidx.compose.foundation.background
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
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.List
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Map
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.rememberModalBottomSheetState
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
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import agrochamba.com.data.JobPost
import agrochamba.com.utils.htmlToString
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.style.TextAlign
import kotlinx.coroutines.launch

/**
 * =============================================================================
 * VIEW MODE TOGGLE - Toggle para cambiar entre vista de lista y mapa
 * =============================================================================
 */

@Composable
fun ViewModeToggle(
    isMapView: Boolean,
    onToggle: (Boolean) -> Unit,
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier
            .clip(RoundedCornerShape(20.dp))
            .background(MaterialTheme.colorScheme.surfaceVariant)
            .padding(4.dp),
        horizontalArrangement = Arrangement.spacedBy(4.dp)
    ) {
        // Botón Lista
        FilterChip(
            selected = !isMapView,
            onClick = { onToggle(false) },
            label = { Text("Lista") },
            leadingIcon = {
                Icon(
                    imageVector = Icons.Default.List,
                    contentDescription = null,
                    modifier = Modifier.size(18.dp)
                )
            }
        )
        
        // Botón Mapa
        FilterChip(
            selected = isMapView,
            onClick = { onToggle(true) },
            label = { Text("Mapa") },
            leadingIcon = {
                Icon(
                    imageVector = Icons.Default.Map,
                    contentDescription = null,
                    modifier = Modifier.size(18.dp)
                )
            }
        )
    }
}

/**
 * =============================================================================
 * JOBS MAP VIEW - Vista de mapa con trabajos agrupados por ubicación
 * =============================================================================
 * 
 * Muestra trabajos en un mapa con:
 * - Clusters por departamento/provincia
 * - Marcadores con conteo de trabajos
 * - Bottom sheet con lista de trabajos al tocar
 * 
 * NOTA: Actualmente deshabilitado hasta configurar MAPBOX_DOWNLOADS_TOKEN
 * en gradle.properties con un secret token de Mapbox.
 */

/**
 * Coordenadas aproximadas de departamentos del Perú
 */
data class GeoPoint(val lat: Double, val lng: Double)

val departamentoCoords = mapOf(
    "Amazonas" to GeoPoint(-6.2298, -77.8522),
    "Áncash" to GeoPoint(-9.5295, -77.5319),
    "Apurímac" to GeoPoint(-14.0505, -72.8816),
    "Arequipa" to GeoPoint(-16.4090, -71.5375),
    "Ayacucho" to GeoPoint(-13.1588, -74.2242),
    "Cajamarca" to GeoPoint(-7.1619, -78.5128),
    "Cusco" to GeoPoint(-13.5319, -71.9675),
    "Huancavelica" to GeoPoint(-12.7869, -74.9764),
    "Huánuco" to GeoPoint(-9.9306, -76.2422),
    "Ica" to GeoPoint(-14.0678, -75.7286),
    "Junín" to GeoPoint(-12.0651, -75.2049),
    "La Libertad" to GeoPoint(-8.1150, -79.0300),
    "Lambayeque" to GeoPoint(-6.7701, -79.8412),
    "Lima" to GeoPoint(-12.0464, -77.0428),
    "Loreto" to GeoPoint(-3.7437, -73.2516),
    "Madre de Dios" to GeoPoint(-12.5930, -69.1892),
    "Moquegua" to GeoPoint(-17.1953, -70.9356),
    "Pasco" to GeoPoint(-10.6829, -76.2568),
    "Piura" to GeoPoint(-5.1783, -80.6548),
    "Puno" to GeoPoint(-15.8402, -70.0219),
    "San Martín" to GeoPoint(-6.4895, -76.3600),
    "Tacna" to GeoPoint(-18.0147, -70.2536),
    "Tumbes" to GeoPoint(-3.5670, -80.4515),
    "Ucayali" to GeoPoint(-8.3791, -74.5539)
)

/**
 * Vista de mapa con trabajos
 * 
 * @param jobs Lista de trabajos a mostrar en el mapa
 * @param onJobClick Callback cuando se hace click en un trabajo
 * @param modifier Modifier para customizar el layout
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun JobsMapView(
    jobs: List<JobPost>,
    onJobClick: (JobPost) -> Unit,
    modifier: Modifier = Modifier
) {
    var selectedCluster by remember { mutableStateOf<String?>(null) }
    var showBottomSheet by remember { mutableStateOf(false) }
    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
    val scope = rememberCoroutineScope()
    
    // Agrupar trabajos por departamento
    val jobsByDepartment = remember(jobs) {
        jobs.groupBy { job ->
            // Intentar extraer departamento: meta > ubicacionDisplay > taxonomía
            val ubicacionCompleta = job.meta?.ubicacionCompleta
            val ubicacionDisplay = job.ubicacionDisplay
            when {
                ubicacionCompleta != null && ubicacionCompleta.departamento.isNotBlank() -> {
                    ubicacionCompleta.departamento
                }
                !ubicacionDisplay?.departamento.isNullOrBlank() -> {
                    ubicacionDisplay!!.departamento!!
                }
                else -> {
                    // Fallback: extraer de la taxonomía embebida
                    job.embedded?.terms?.flatten()
                        ?.find { term ->
                            departamentoCoords.keys.any { dep ->
                                term.name?.contains(dep, ignoreCase = true) == true
                            }
                        }?.name?.split(",")?.firstOrNull()?.trim() ?: "Otro"
                }
            }
        }.filter { it.key != "Otro" || it.value.isNotEmpty() }
    }
    
    Box(modifier = modifier.fillMaxSize()) {
        // Placeholder mientras Mapbox no está configurado
        MapPlaceholder(
            jobsByDepartment = jobsByDepartment,
            onClusterClick = { department ->
                selectedCluster = department
                showBottomSheet = true
            }
        )
        
        // Leyenda
        MapLegend(
            totalJobs = jobs.size,
            departmentCount = jobsByDepartment.size,
            modifier = Modifier
                .align(Alignment.TopEnd)
                .padding(16.dp)
        )
    }
    
    // Bottom sheet con lista de trabajos del cluster seleccionado
    if (showBottomSheet && selectedCluster != null) {
        ModalBottomSheet(
            onDismissRequest = {
                showBottomSheet = false
                selectedCluster = null
            },
            sheetState = sheetState
        ) {
            ClusterJobsList(
                department = selectedCluster!!,
                jobs = jobsByDepartment[selectedCluster] ?: emptyList(),
                onJobClick = { job ->
                    scope.launch {
                        sheetState.hide()
                        showBottomSheet = false
                        selectedCluster = null
                        onJobClick(job)
                    }
                },
                onClose = {
                    scope.launch {
                        sheetState.hide()
                        showBottomSheet = false
                        selectedCluster = null
                    }
                }
            )
        }
    }
}

/**
 * Placeholder del mapa mientras Mapbox no está configurado
 */
@Composable
private fun MapPlaceholder(
    jobsByDepartment: Map<String, List<JobPost>>,
    onClusterClick: (String) -> Unit
) {
    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(Color(0xFFE8F5E9))
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(16.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            // Título
            Text(
                text = "🗺️ Trabajos por Departamento",
                style = MaterialTheme.typography.headlineSmall,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(bottom = 16.dp)
            )
            
            // Mensaje sobre Mapbox
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 16.dp),
                colors = CardDefaults.cardColors(
                    containerColor = MaterialTheme.colorScheme.primaryContainer
                )
            ) {
                Text(
                    text = "📍 Toca un departamento para ver los trabajos disponibles",
                    style = MaterialTheme.typography.bodyMedium,
                    modifier = Modifier.padding(16.dp),
                    textAlign = TextAlign.Center
                )
            }
            
            // Lista de departamentos con trabajos
            LazyColumn(
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                items(
                    jobsByDepartment.entries.sortedByDescending { it.value.size }.toList()
                ) { (department, departmentJobs) ->
                    DepartmentCard(
                        department = department,
                        jobCount = departmentJobs.size,
                        onClick = { onClusterClick(department) }
                    )
                }
            }
        }
    }
}

/**
 * Tarjeta de departamento con contador de trabajos
 */
@Composable
private fun DepartmentCard(
    department: String,
    jobCount: Int,
    onClick: () -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surface
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically
            ) {
                Icon(
                    imageVector = Icons.Default.LocationOn,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(24.dp)
                )
                Spacer(modifier = Modifier.width(12.dp))
                Text(
                    text = department,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Medium
                )
            }
            
            // Badge con contador
            Box(
                modifier = Modifier
                    .clip(RoundedCornerShape(12.dp))
                    .background(MaterialTheme.colorScheme.primaryContainer)
                    .padding(horizontal = 12.dp, vertical = 4.dp)
            ) {
                Text(
                    text = "$jobCount ${if (jobCount == 1) "trabajo" else "trabajos"}",
                    style = MaterialTheme.typography.labelMedium,
                    color = MaterialTheme.colorScheme.onPrimaryContainer
                )
            }
        }
    }
}

/**
 * Leyenda del mapa
 */
@Composable
private fun MapLegend(
    totalJobs: Int,
    departmentCount: Int,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier,
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surface.copy(alpha = 0.95f)
        )
    ) {
        Column(
            modifier = Modifier.padding(12.dp)
        ) {
            Text(
                text = "📊 Resumen",
                style = MaterialTheme.typography.labelLarge,
                fontWeight = FontWeight.Bold
            )
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                text = "$totalJobs trabajos",
                style = MaterialTheme.typography.bodySmall
            )
            Text(
                text = "$departmentCount departamentos",
                style = MaterialTheme.typography.bodySmall
            )
        }
    }
}

/**
 * Lista de trabajos de un cluster/departamento
 */
@Composable
private fun ClusterJobsList(
    department: String,
    jobs: List<JobPost>,
    onJobClick: (JobPost) -> Unit,
    onClose: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp)
            .padding(bottom = 32.dp)
    ) {
        // Header
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column {
                Text(
                    text = "📍 $department",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold
                )
                Text(
                    text = "${jobs.size} ${if (jobs.size == 1) "trabajo disponible" else "trabajos disponibles"}",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            IconButton(onClick = onClose) {
                Icon(
                    imageVector = Icons.Default.Close,
                    contentDescription = "Cerrar"
                )
            }
        }
        
        Spacer(modifier = Modifier.height(16.dp))
        
        // Lista de trabajos
        LazyColumn(
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            items(jobs) { job ->
                ClusterJobCard(
                    job = job,
                    onClick = { onJobClick(job) }
                )
            }
        }
    }
}

/**
 * Tarjeta de trabajo en el cluster
 */
@Composable
private fun ClusterJobCard(
    job: JobPost,
    onClick: () -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant
        )
    ) {
        Column(
            modifier = Modifier.padding(12.dp)
        ) {
            Text(
                text = job.title?.rendered?.htmlToString() ?: "Sin título",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Medium,
                maxLines = 2,
                overflow = TextOverflow.Ellipsis
            )
            
            Spacer(modifier = Modifier.height(4.dp))
            
            // Ubicación completa si está disponible (meta > ubicacionDisplay)
            val ubicacion = job.meta?.ubicacionCompleta
            val ubicacionDisplay = job.ubicacionDisplay
            val distrito = ubicacion?.distrito?.takeIf { it.isNotBlank() } ?: ubicacionDisplay?.distrito
            val provincia = ubicacion?.provincia?.takeIf { it.isNotBlank() } ?: ubicacionDisplay?.provincia
            if (!distrito.isNullOrBlank() && !provincia.isNullOrBlank()) {
                Text(
                    text = "📌 $distrito, $provincia",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            
            // Salario si está disponible
            val salarioMin = job.meta?.salarioMin?.toIntOrNull()
            val salarioMax = job.meta?.salarioMax?.toIntOrNull()
            if (salarioMin != null && salarioMin > 0) {
                Text(
                    text = if (salarioMax != null && salarioMax > salarioMin) {
                        "💰 S/ $salarioMin - S/ $salarioMax"
                    } else {
                        "💰 S/ $salarioMin"
                    },
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.primary
                )
            }
        }
    }
}
