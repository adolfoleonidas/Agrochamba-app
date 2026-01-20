package agrochamba.com.ui.company

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
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Business
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Star
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExtendedFloatingActionButton
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.window.Dialog
import androidx.navigation.NavController
import agrochamba.com.data.SedeEmpresa
import agrochamba.com.data.UbicacionCompleta
import agrochamba.com.data.repository.LocationRepository
import agrochamba.com.ui.common.LocationSearchField

/**
 * Pantalla de gestión de sedes de empresa
 * 
 * Permite:
 * - Ver todas las sedes registradas
 * - Crear nuevas sedes
 * - Editar sedes existentes
 * - Eliminar sedes
 * - Marcar sede como principal
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SedesManagementScreen(
    navController: NavController
) {
    val context = LocalContext.current
    val locationRepository = remember { LocationRepository.getInstance(context) }
    val sedes by locationRepository.companySedes.collectAsState(initial = emptyList())
    
    var showCreateDialog by remember { mutableStateOf(false) }
    var sedeToEdit by remember { mutableStateOf<SedeEmpresa?>(null) }
    var sedeToDelete by remember { mutableStateOf<SedeEmpresa?>(null) }
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = { 
                    Column {
                        Text("Mis Sedes")
                        Text(
                            text = "${sedes.size} ubicaciones registradas",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                },
                navigationIcon = {
                    IconButton(onClick = { navController.popBackStack() }) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Volver")
                    }
                }
            )
        },
        floatingActionButton = {
            // Solo mostrar FAB cuando hay sedes (cuando está vacío, el EmptySedesState tiene su propio botón)
            if (sedes.isNotEmpty()) {
                ExtendedFloatingActionButton(
                    onClick = { showCreateDialog = true },
                    icon = { Icon(Icons.Default.Add, contentDescription = null) },
                    text = { Text("Nueva Sede") }
                )
            }
        }
    ) { paddingValues ->
        if (sedes.isEmpty()) {
            // Estado vacío
            EmptySedesState(
                onCreateClick = { showCreateDialog = true },
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
            )
        } else {
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentPadding = androidx.compose.foundation.layout.PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                // Info de ayuda
                item {
                    Surface(
                        shape = RoundedCornerShape(12.dp),
                        color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.3f)
                    ) {
                        Row(
                            modifier = Modifier.padding(16.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Icon(
                                Icons.Default.LocationOn,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.primary
                            )
                            Spacer(Modifier.width(12.dp))
                            Text(
                                text = "Las sedes te permiten seleccionar ubicaciones rápidamente al publicar ofertas de trabajo.",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurface
                            )
                        }
                    }
                }
                
                items(sedes, key = { it.id }) { sede ->
                    SedeCard(
                        sede = sede,
                        onEdit = { sedeToEdit = sede },
                        onDelete = { sedeToDelete = sede },
                        onSetPrimary = {
                            val updatedSede = sede.copy(esPrincipal = true)
                            locationRepository.updateSede(updatedSede)
                        }
                    )
                }
                
                // Espacio para FAB
                item {
                    Spacer(Modifier.height(80.dp))
                }
            }
        }
    }
    
    // Diálogo para crear sede
    if (showCreateDialog) {
        CreateSedeDialog(
            onConfirm = { nombre, ubicacion, esPrincipal ->
                val nuevaSede = SedeEmpresa(
                    id = java.util.UUID.randomUUID().toString(),
                    nombre = nombre,
                    ubicacion = ubicacion,
                    esPrincipal = esPrincipal,
                    activa = true
                )
                locationRepository.addSede(nuevaSede)
                showCreateDialog = false
            },
            onDismiss = { showCreateDialog = false }
        )
    }
    
    // Diálogo para editar sede
    sedeToEdit?.let { sede ->
        EditSedeDialog(
            sede = sede,
            onConfirm = { nombre, ubicacion, esPrincipal ->
                val updatedSede = sede.copy(
                    nombre = nombre,
                    ubicacion = ubicacion,
                    esPrincipal = esPrincipal
                )
                locationRepository.updateSede(updatedSede)
                sedeToEdit = null
            },
            onDismiss = { sedeToEdit = null }
        )
    }
    
    // Diálogo para confirmar eliminación
    sedeToDelete?.let { sede ->
        AlertDialog(
            onDismissRequest = { sedeToDelete = null },
            icon = {
                Icon(
                    Icons.Default.Delete,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.error
                )
            },
            title = { Text("Eliminar sede") },
            text = {
                Column {
                    Text("¿Estás seguro de que deseas eliminar esta sede?")
                    Spacer(Modifier.height(8.dp))
                    Surface(
                        shape = RoundedCornerShape(8.dp),
                        color = MaterialTheme.colorScheme.surfaceVariant
                    ) {
                        Row(
                            modifier = Modifier.padding(12.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Icon(
                                Icons.Default.Business,
                                contentDescription = null,
                                modifier = Modifier.size(20.dp)
                            )
                            Spacer(Modifier.width(8.dp))
                            Column {
                                Text(
                                    text = sede.nombre,
                                    fontWeight = FontWeight.Medium
                                )
                                Text(
                                    text = sede.ubicacion.formatOneLine(),
                                    style = MaterialTheme.typography.bodySmall
                                )
                            }
                        }
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        locationRepository.removeSede(sede.id)
                        sedeToDelete = null
                    },
                    colors = androidx.compose.material3.ButtonDefaults.buttonColors(
                        containerColor = MaterialTheme.colorScheme.error
                    )
                ) {
                    Text("Eliminar")
                }
            },
            dismissButton = {
                OutlinedButton(onClick = { sedeToDelete = null }) {
                    Text("Cancelar")
                }
            }
        )
    }
}

@Composable
private fun EmptySedesState(
    onCreateClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier.padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(
            Icons.Default.Business,
            contentDescription = null,
            modifier = Modifier.size(80.dp),
            tint = MaterialTheme.colorScheme.primary.copy(alpha = 0.5f)
        )
        
        Spacer(Modifier.height(24.dp))
        
        Text(
            text = "Aún no tienes sedes registradas",
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.Bold,
            textAlign = TextAlign.Center
        )
        
        Spacer(Modifier.height(8.dp))
        
        Text(
            text = "Las sedes te permiten publicar ofertas de trabajo más rápido, sin tener que seleccionar la ubicación cada vez.",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            textAlign = TextAlign.Center
        )
        
        Spacer(Modifier.height(32.dp))
        
        Button(onClick = onCreateClick) {
            Icon(Icons.Default.Add, contentDescription = null)
            Spacer(Modifier.width(8.dp))
            Text("Crear primera sede")
        }
    }
}

@Composable
private fun SedeCard(
    sede: SedeEmpresa,
    onEdit: () -> Unit,
    onDelete: () -> Unit,
    onSetPrimary: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        colors = if (sede.esPrincipal) {
            CardDefaults.cardColors(
                containerColor = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.3f)
            )
        } else {
            CardDefaults.cardColors()
        }
    ) {
        Column(
            modifier = Modifier.padding(16.dp)
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Icono
                Box(
                    modifier = Modifier
                        .size(48.dp)
                        .background(
                            if (sede.esPrincipal) MaterialTheme.colorScheme.primary
                            else MaterialTheme.colorScheme.surfaceVariant,
                            CircleShape
                        ),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        Icons.Default.Business,
                        contentDescription = null,
                        tint = if (sede.esPrincipal) MaterialTheme.colorScheme.onPrimary
                              else MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.size(24.dp)
                    )
                }
                
                Spacer(Modifier.width(16.dp))
                
                Column(modifier = Modifier.weight(1f)) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(
                            text = sede.nombre,
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold
                        )
                        if (sede.esPrincipal) {
                            Spacer(Modifier.width(8.dp))
                            Surface(
                                shape = RoundedCornerShape(4.dp),
                                color = MaterialTheme.colorScheme.primary
                            ) {
                                Text(
                                    text = "Principal",
                                    style = MaterialTheme.typography.labelSmall,
                                    color = MaterialTheme.colorScheme.onPrimary,
                                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp)
                                )
                            }
                        }
                    }
                    
                    Text(
                        text = sede.ubicacion.formatOneLine(),
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    
                    sede.ubicacion.direccion?.takeIf { it.isNotBlank() }?.let { direccion ->
                        Text(
                            text = "📮 $direccion",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }
            
            HorizontalDivider(modifier = Modifier.padding(vertical = 12.dp))
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                if (!sede.esPrincipal) {
                    TextButton(onClick = onSetPrimary) {
                        Icon(Icons.Default.Star, contentDescription = null, modifier = Modifier.size(18.dp))
                        Spacer(Modifier.width(4.dp))
                        Text("Hacer principal")
                    }
                } else {
                    Spacer(Modifier.weight(1f))
                }
                
                Row {
                    IconButton(onClick = onEdit) {
                        Icon(
                            Icons.Default.Edit,
                            contentDescription = "Editar",
                            tint = MaterialTheme.colorScheme.primary
                        )
                    }
                    IconButton(onClick = onDelete) {
                        Icon(
                            Icons.Default.Delete,
                            contentDescription = "Eliminar",
                            tint = MaterialTheme.colorScheme.error
                        )
                    }
                }
            }
        }
    }
}

/**
 * Diálogo para crear una nueva sede de empresa
 * Público para poder reutilizarse desde otras pantallas (ej: CreateJobScreen)
 */
@Composable
fun CreateSedeDialog(
    onConfirm: (nombre: String, ubicacion: UbicacionCompleta, esPrincipal: Boolean) -> Unit,
    onDismiss: () -> Unit
) {
    var nombre by remember { mutableStateOf("") }
    var ubicacion by remember { mutableStateOf<UbicacionCompleta?>(null) }
    var direccion by remember { mutableStateOf("") }
    var esPrincipal by remember { mutableStateOf(false) }
    var nombreError by remember { mutableStateOf<String?>(null) }
    var ubicacionError by remember { mutableStateOf<String?>(null) }
    
    Dialog(onDismissRequest = onDismiss) {
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            shape = RoundedCornerShape(16.dp)
        ) {
            Column(
                modifier = Modifier
                    .padding(24.dp)
                    .fillMaxWidth(),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Título
                Text(
                    text = "Nueva Sede",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold
                )
                
                // Campo nombre
                OutlinedTextField(
                    value = nombre,
                    onValueChange = { 
                        nombre = it
                        nombreError = null
                    },
                    label = { Text("Nombre de la sede *") },
                    placeholder = { Text("Ej: Oficina Principal, Almacén Ica...") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    isError = nombreError != null,
                    supportingText = nombreError?.let { { Text(it) } },
                    leadingIcon = {
                        Icon(Icons.Default.Business, contentDescription = null)
                    }
                )
                
                // Selector de ubicación
                Column {
                    Text(
                        text = "Ubicación *",
                        style = MaterialTheme.typography.labelLarge,
                        modifier = Modifier.padding(bottom = 8.dp)
                    )
                    
                    LocationSearchField(
                        selectedLocation = ubicacion,
                        onLocationSelected = { 
                            ubicacion = it
                            ubicacionError = null
                            // Sugerir nombre si está vacío
                            if (nombre.isBlank() && it.distrito.isNotBlank()) {
                                nombre = "Sede ${it.distrito}"
                            }
                        },
                        placeholder = "Buscar distrito, provincia o departamento..."
                    )
                    
                    if (ubicacionError != null) {
                        Text(
                            text = ubicacionError!!,
                            color = MaterialTheme.colorScheme.error,
                            style = MaterialTheme.typography.bodySmall,
                            modifier = Modifier.padding(top = 4.dp)
                        )
                    }
                }
                
                // Dirección exacta (opcional)
                OutlinedTextField(
                    value = direccion,
                    onValueChange = { direccion = it },
                    label = { Text("Dirección exacta (opcional)") },
                    placeholder = { Text("Ej: Av. Principal 123, Piso 2") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true
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
                            text = "Sede principal",
                            style = MaterialTheme.typography.bodyMedium,
                            fontWeight = FontWeight.Medium
                        )
                        Text(
                            text = "Se seleccionará por defecto",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                    Switch(
                        checked = esPrincipal,
                        onCheckedChange = { esPrincipal = it }
                    )
                }
                
                // Botones
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    OutlinedButton(
                        onClick = onDismiss,
                        modifier = Modifier.weight(1f)
                    ) {
                        Text("Cancelar")
                    }
                    
                    Button(
                        onClick = {
                            var hasError = false
                            
                            if (nombre.isBlank()) {
                                nombreError = "Ingresa un nombre"
                                hasError = true
                            }
                            
                            if (ubicacion == null || ubicacion?.distrito?.isBlank() == true) {
                                ubicacionError = "Selecciona una ubicación"
                                hasError = true
                            }
                            
                            if (!hasError && ubicacion != null) {
                                val ubicacionConDireccion = if (direccion.isNotBlank()) {
                                    ubicacion!!.copy(direccion = direccion.trim())
                                } else {
                                    ubicacion!!
                                }
                                onConfirm(nombre.trim(), ubicacionConDireccion, esPrincipal)
                            }
                        },
                        modifier = Modifier.weight(1f)
                    ) {
                        Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(18.dp))
                        Spacer(Modifier.width(4.dp))
                        Text("Crear")
                    }
                }
            }
        }
    }
}

@Composable
private fun EditSedeDialog(
    sede: SedeEmpresa,
    onConfirm: (nombre: String, ubicacion: UbicacionCompleta, esPrincipal: Boolean) -> Unit,
    onDismiss: () -> Unit
) {
    var nombre by remember { mutableStateOf(sede.nombre) }
    var ubicacion by remember { mutableStateOf<UbicacionCompleta?>(sede.ubicacion) }
    var direccion by remember { mutableStateOf(sede.ubicacion.direccion ?: "") }
    var esPrincipal by remember { mutableStateOf(sede.esPrincipal) }
    var nombreError by remember { mutableStateOf<String?>(null) }
    
    Dialog(onDismissRequest = onDismiss) {
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            shape = RoundedCornerShape(16.dp)
        ) {
            Column(
                modifier = Modifier
                    .padding(24.dp)
                    .fillMaxWidth(),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Título
                Text(
                    text = "Editar Sede",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold
                )
                
                // Campo nombre
                OutlinedTextField(
                    value = nombre,
                    onValueChange = { 
                        nombre = it
                        nombreError = null
                    },
                    label = { Text("Nombre de la sede *") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    isError = nombreError != null,
                    supportingText = nombreError?.let { { Text(it) } },
                    leadingIcon = {
                        Icon(Icons.Default.Business, contentDescription = null)
                    }
                )
                
                // Selector de ubicación
                Column {
                    Text(
                        text = "Ubicación *",
                        style = MaterialTheme.typography.labelLarge,
                        modifier = Modifier.padding(bottom = 8.dp)
                    )
                    
                    LocationSearchField(
                        selectedLocation = ubicacion,
                        onLocationSelected = { ubicacion = it },
                        placeholder = "Buscar distrito, provincia o departamento..."
                    )
                }
                
                // Dirección exacta (opcional)
                OutlinedTextField(
                    value = direccion,
                    onValueChange = { direccion = it },
                    label = { Text("Dirección exacta (opcional)") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true
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
                            text = "Sede principal",
                            style = MaterialTheme.typography.bodyMedium,
                            fontWeight = FontWeight.Medium
                        )
                    }
                    Switch(
                        checked = esPrincipal,
                        onCheckedChange = { esPrincipal = it }
                    )
                }
                
                // Botones
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    OutlinedButton(
                        onClick = onDismiss,
                        modifier = Modifier.weight(1f)
                    ) {
                        Text("Cancelar")
                    }
                    
                    Button(
                        onClick = {
                            if (nombre.isBlank()) {
                                nombreError = "Ingresa un nombre"
                            } else if (ubicacion != null) {
                                val ubicacionConDireccion = if (direccion.isNotBlank()) {
                                    ubicacion!!.copy(direccion = direccion.trim())
                                } else {
                                    ubicacion!!.copy(direccion = null)
                                }
                                onConfirm(nombre.trim(), ubicacionConDireccion, esPrincipal)
                            }
                        },
                        modifier = Modifier.weight(1f)
                    ) {
                        Text("Guardar")
                    }
                }
            }
        }
    }
}
