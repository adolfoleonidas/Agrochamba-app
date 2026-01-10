package agrochamba.com.ui.auth

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import agrochamba.com.data.FacebookPage

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FacebookPagesScreen(
    navController: NavController,
    viewModel: FacebookPagesViewModel = viewModel()
) {
    val uiState = viewModel.uiState
    var showAddDialog by remember { mutableStateOf(false) }
    var pageToDelete by remember { mutableStateOf<FacebookPage?>(null) }
    
    // Snackbar para mensajes
    val snackbarHostState = remember { SnackbarHostState() }
    
    LaunchedEffect(uiState.error, uiState.successMessage) {
        uiState.error?.let {
            snackbarHostState.showSnackbar(it, duration = SnackbarDuration.Long)
            viewModel.clearMessages()
        }
        uiState.successMessage?.let {
            snackbarHostState.showSnackbar(it, duration = SnackbarDuration.Short)
            viewModel.clearMessages()
        }
    }
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Páginas de Facebook") },
                navigationIcon = {
                    IconButton(onClick = { navController.popBackStack() }) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Volver")
                    }
                },
                actions = {
                    IconButton(onClick = { viewModel.loadPages() }) {
                        Icon(Icons.Default.Refresh, contentDescription = "Recargar")
                    }
                }
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) },
        floatingActionButton = {
            ExtendedFloatingActionButton(
                onClick = { showAddDialog = true },
                icon = { Icon(Icons.Default.Add, "Agregar") },
                text = { Text("Agregar Página") },
                containerColor = MaterialTheme.colorScheme.primary
            )
        }
    ) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            when {
                uiState.isLoading -> {
                    CircularProgressIndicator(
                        modifier = Modifier.align(Alignment.Center)
                    )
                }
                uiState.pages.isEmpty() -> {
                    EmptyPagesState(
                        modifier = Modifier.align(Alignment.Center),
                        onAddClick = { showAddDialog = true }
                    )
                }
                else -> {
                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        contentPadding = PaddingValues(16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        item {
                            Text(
                                text = "Las publicaciones se compartirán en todas las páginas habilitadas.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                modifier = Modifier.padding(bottom = 8.dp)
                            )
                        }
                        
                        items(uiState.pages) { page ->
                            FacebookPageCard(
                                page = page,
                                isTesting = uiState.isTestingPage == page.id,
                                isDeleting = uiState.isDeletingPage == page.id,
                                onTestClick = { viewModel.testPage(page.id) },
                                onDeleteClick = { pageToDelete = page },
                                onToggleEnabled = { enabled ->
                                    viewModel.togglePageEnabled(page.id, enabled)
                                }
                            )
                        }
                        
                        // Espacio para el FAB
                        item { Spacer(modifier = Modifier.height(80.dp)) }
                    }
                }
            }
        }
    }
    
    // Diálogo para agregar página
    if (showAddDialog) {
        AddFacebookPageDialog(
            isLoading = uiState.isAddingPage,
            onDismiss = { showAddDialog = false },
            onConfirm = { pageId, pageName, pageToken ->
                viewModel.addPage(pageId, pageName, pageToken)
                showAddDialog = false
            }
        )
    }
    
    // Diálogo de confirmación para eliminar
    pageToDelete?.let { page ->
        AlertDialog(
            onDismissRequest = { pageToDelete = null },
            icon = { Icon(Icons.Default.Delete, contentDescription = null, tint = MaterialTheme.colorScheme.error) },
            title = { Text("¿Eliminar página?") },
            text = { 
                Text("Se eliminará la página \"${page.pageName}\" de la configuración. Las publicaciones futuras no se compartirán en esta página.")
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        viewModel.deletePage(page.id)
                        pageToDelete = null
                    },
                    colors = ButtonDefaults.textButtonColors(
                        contentColor = MaterialTheme.colorScheme.error
                    )
                ) {
                    Text("Eliminar")
                }
            },
            dismissButton = {
                TextButton(onClick = { pageToDelete = null }) {
                    Text("Cancelar")
                }
            }
        )
    }
}

@Composable
private fun EmptyPagesState(
    modifier: Modifier = Modifier,
    onAddClick: () -> Unit
) {
    Column(
        modifier = modifier.padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Icon(
            imageVector = Icons.Default.Facebook,
            contentDescription = null,
            modifier = Modifier.size(64.dp),
            tint = MaterialTheme.colorScheme.primary.copy(alpha = 0.5f)
        )
        Spacer(modifier = Modifier.height(16.dp))
        Text(
            text = "Sin páginas configuradas",
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.Bold
        )
        Spacer(modifier = Modifier.height(8.dp))
        Text(
            text = "Agrega páginas de Facebook para publicar tus trabajos automáticamente en múltiples páginas.",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.padding(horizontal = 16.dp)
        )
        Spacer(modifier = Modifier.height(24.dp))
        Button(onClick = onAddClick) {
            Icon(Icons.Default.Add, contentDescription = null)
            Spacer(modifier = Modifier.width(8.dp))
            Text("Agregar Primera Página")
        }
    }
}

@Composable
private fun FacebookPageCard(
    page: FacebookPage,
    isTesting: Boolean,
    isDeleting: Boolean,
    onTestClick: () -> Unit,
    onDeleteClick: () -> Unit,
    onToggleEnabled: (Boolean) -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = if (page.enabled) 
                MaterialTheme.colorScheme.surface 
            else 
                MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
        )
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Icono y nombre
                Icon(
                    imageVector = Icons.Default.Facebook,
                    contentDescription = null,
                    tint = if (page.enabled) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.size(24.dp)
                )
                Spacer(modifier = Modifier.width(12.dp))
                Column(modifier = Modifier.weight(1f)) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text(
                            text = page.pageName,
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Medium
                        )
                        if (page.isPrimary) {
                            Spacer(modifier = Modifier.width(8.dp))
                            Surface(
                                shape = RoundedCornerShape(4.dp),
                                color = MaterialTheme.colorScheme.primaryContainer
                            ) {
                                Text(
                                    text = "Principal",
                                    style = MaterialTheme.typography.labelSmall,
                                    color = MaterialTheme.colorScheme.primary,
                                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                                )
                            }
                        }
                    }
                    Text(
                        text = "ID: ${page.pageId}",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
                
                // Switch de habilitado
                Switch(
                    checked = page.enabled,
                    onCheckedChange = onToggleEnabled
                )
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            
            // Botones de acción
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                // Botón probar conexión
                OutlinedButton(
                    onClick = onTestClick,
                    enabled = !isTesting && !isDeleting,
                    modifier = Modifier.weight(1f)
                ) {
                    if (isTesting) {
                        CircularProgressIndicator(
                            modifier = Modifier.size(16.dp),
                            strokeWidth = 2.dp
                        )
                    } else {
                        Icon(Icons.Default.Verified, contentDescription = null, modifier = Modifier.size(16.dp))
                    }
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(if (isTesting) "Probando..." else "Probar")
                }
                
                // Botón eliminar
                OutlinedButton(
                    onClick = onDeleteClick,
                    enabled = !isTesting && !isDeleting,
                    colors = ButtonDefaults.outlinedButtonColors(
                        contentColor = MaterialTheme.colorScheme.error
                    )
                ) {
                    if (isDeleting) {
                        CircularProgressIndicator(
                            modifier = Modifier.size(16.dp),
                            strokeWidth = 2.dp,
                            color = MaterialTheme.colorScheme.error
                        )
                    } else {
                        Icon(Icons.Default.Delete, contentDescription = null, modifier = Modifier.size(16.dp))
                    }
                }
            }
            
            // Información adicional
            page.lastVerified?.let { verified ->
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = "✓ Verificada: $verified",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun AddFacebookPageDialog(
    isLoading: Boolean,
    onDismiss: () -> Unit,
    onConfirm: (pageId: String, pageName: String, pageToken: String) -> Unit
) {
    var pageId by remember { mutableStateOf("") }
    var pageName by remember { mutableStateOf("") }
    var pageToken by remember { mutableStateOf("") }
    var showToken by remember { mutableStateOf(false) }
    
    AlertDialog(
        onDismissRequest = { if (!isLoading) onDismiss() },
        icon = { Icon(Icons.Default.Facebook, contentDescription = null) },
        title = { Text("Agregar Página de Facebook") },
        text = {
            Column(
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                Text(
                    text = "Ingresa los datos de tu página de Facebook. Puedes obtenerlos desde el Facebook Business Suite o Graph API Explorer.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                
                OutlinedTextField(
                    value = pageName,
                    onValueChange = { pageName = it },
                    label = { Text("Nombre de la Página") },
                    placeholder = { Text("Ej: Mi Empresa Agrícola") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !isLoading
                )
                
                OutlinedTextField(
                    value = pageId,
                    onValueChange = { pageId = it },
                    label = { Text("Page ID *") },
                    placeholder = { Text("Ej: 123456789012345") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    enabled = !isLoading
                )
                
                OutlinedTextField(
                    value = pageToken,
                    onValueChange = { pageToken = it },
                    label = { Text("Page Access Token *") },
                    placeholder = { Text("Token de acceso de la página") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                    visualTransformation = if (showToken) VisualTransformation.None else PasswordVisualTransformation(),
                    trailingIcon = {
                        IconButton(onClick = { showToken = !showToken }) {
                            Icon(
                                imageVector = if (showToken) Icons.Default.VisibilityOff else Icons.Default.Visibility,
                                contentDescription = if (showToken) "Ocultar" else "Mostrar"
                            )
                        }
                    },
                    enabled = !isLoading
                )
                
                Text(
                    text = "* Campos obligatorios",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        },
        confirmButton = {
            Button(
                onClick = { onConfirm(pageId, pageName, pageToken) },
                enabled = pageId.isNotBlank() && pageToken.isNotBlank() && !isLoading
            ) {
                if (isLoading) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(16.dp),
                        strokeWidth = 2.dp,
                        color = MaterialTheme.colorScheme.onPrimary
                    )
                    Spacer(modifier = Modifier.width(8.dp))
                }
                Text(if (isLoading) "Agregando..." else "Agregar")
            }
        },
        dismissButton = {
            TextButton(
                onClick = onDismiss,
                enabled = !isLoading
            ) {
                Text("Cancelar")
            }
        }
    )
}

