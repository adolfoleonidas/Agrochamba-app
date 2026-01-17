package agrochamba.com.ui.moderation

import androidx.compose.foundation.*
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import coil.compose.AsyncImage
import agrochamba.com.data.JobPost
import agrochamba.com.utils.htmlToString

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ModerationScreen(
    onNavigateBack: () -> Unit,
    onNavigateToEditJob: (Int) -> Unit = {},
    viewModel: ModerationViewModel = viewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val currentFilter by viewModel.currentFilter.collectAsState()
    val selectedJobIds by viewModel.selectedJobIds.collectAsState()
    val selectedJob by viewModel.selectedJob.collectAsState()
    val searchQuery by viewModel.searchQuery.collectAsState()
    val hasMorePages by viewModel.hasMorePages.collectAsState()

    var showDeleteDialog by remember { mutableStateOf<Int?>(null) }
    var showPreviewDialog by remember { mutableStateOf(false) }

    // Snackbar para mensajes
    val snackbarHostState = remember { SnackbarHostState() }
    
    LaunchedEffect(uiState.successMessage, uiState.error) {
        uiState.successMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearMessages()
        }
        uiState.error?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearMessages()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Moderación") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Volver")
                    }
                },
                actions = {
                    IconButton(onClick = { viewModel.loadJobs() }) {
                        Icon(Icons.Default.Refresh, contentDescription = "Actualizar")
                    }
                }
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            // Chips de filtro
            FilterChipsSection(
                currentFilter = currentFilter,
                pendingCount = uiState.pendingCount,
                totalCount = uiState.totalJobs,
                onFilterChange = { viewModel.setFilter(it) }
            )
            
            // Barra de búsqueda
            SearchSection(
                searchQuery = searchQuery,
                onSearchChange = { viewModel.setSearchQuery(it) },
                onSearch = { viewModel.search() }
            )

            // Lista de trabajos
            when {
                uiState.isLoading && uiState.jobs.isEmpty() -> {
                    Box(
                        modifier = Modifier.fillMaxSize(),
                        contentAlignment = Alignment.Center
                    ) {
                        CircularProgressIndicator()
                    }
                }
                uiState.jobs.isEmpty() -> {
                    EmptyStateMessage(currentFilter = currentFilter)
                }
                else -> {
                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        items(uiState.jobs, key = { it.id }) { job ->
                            JobCard(
                                job = job,
                                isSelected = selectedJobIds.contains(job.id),
                                onSelect = { viewModel.toggleJobSelection(job.id) },
                                onPreview = { 
                                    viewModel.selectJob(job)
                                    showPreviewDialog = true 
                                },
                                onEdit = { onNavigateToEditJob(job.id) },
                                onDelete = { showDeleteDialog = job.id }
                            )
                        }

                        // Cargando más
                        if (uiState.isLoading) {
                            item {
                                Box(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(16.dp),
                                    contentAlignment = Alignment.Center
                                ) {
                                    CircularProgressIndicator(modifier = Modifier.size(24.dp))
                                }
                            }
                        }

                        // Cargar más
                        if (hasMorePages && !uiState.isLoading) {
                            item {
                                TextButton(
                                    onClick = { viewModel.loadMore() },
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Text("Cargar más")
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Diálogo de confirmación de eliminación
    showDeleteDialog?.let { jobId ->
        AlertDialog(
            onDismissRequest = { showDeleteDialog = null },
            icon = {
                Icon(
                    Icons.Default.Delete,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.error
                )
            },
            title = { Text("Eliminar trabajo") },
            text = { Text("¿Estás seguro de que deseas eliminar este trabajo? Esta acción no se puede deshacer.") },
            confirmButton = {
                Button(
                    onClick = {
                        viewModel.deleteJob(jobId)
                        showDeleteDialog = null
                    },
                    colors = ButtonDefaults.buttonColors(
                        containerColor = MaterialTheme.colorScheme.error
                    )
                ) {
                    Text("Eliminar")
                }
            },
            dismissButton = {
                TextButton(onClick = { showDeleteDialog = null }) {
                    Text("Cancelar")
                }
            }
        )
    }

    // Diálogo de vista previa
    if (showPreviewDialog && selectedJob != null) {
        PreviewDialog(
            job = selectedJob!!,
            onDismiss = { 
                showPreviewDialog = false
                viewModel.clearSelectedJob()
            },
            onEdit = { 
                showPreviewDialog = false
                onNavigateToEditJob(selectedJob!!.id)
            }
        )
    }
}

/**
 * Chips de filtro: Pendientes | Todos
 */
@Composable
private fun FilterChipsSection(
    currentFilter: String,
    pendingCount: Int,
    totalCount: Int,
    onFilterChange: (String) -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 8.dp),
        horizontalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        // Chip: Pendientes
        FilterChip(
            selected = currentFilter == "pending",
            onClick = { onFilterChange("pending") },
            label = { 
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text("Pendientes")
                    if (pendingCount > 0) {
                        Spacer(Modifier.width(6.dp))
                        Badge(
                            containerColor = if (currentFilter == "pending") 
                                MaterialTheme.colorScheme.primary 
                            else 
                                MaterialTheme.colorScheme.error
                        ) {
                            Text(
                                text = pendingCount.toString(),
                                style = MaterialTheme.typography.labelSmall
                            )
                        }
                    }
                }
            },
            leadingIcon = {
                Icon(
                    imageVector = if (currentFilter == "pending") Icons.Default.CheckCircle else Icons.Default.Schedule,
                    contentDescription = null,
                    modifier = Modifier.size(18.dp)
                )
            },
            colors = FilterChipDefaults.filterChipColors(
                selectedContainerColor = MaterialTheme.colorScheme.primaryContainer,
                selectedLabelColor = MaterialTheme.colorScheme.onPrimaryContainer
            )
        )
        
        // Chip: Todos
        FilterChip(
            selected = currentFilter == "all",
            onClick = { onFilterChange("all") },
            label = { 
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text("Todos")
                    Spacer(Modifier.width(6.dp))
                    Text(
                        text = "($totalCount)",
                        style = MaterialTheme.typography.labelSmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            },
            leadingIcon = {
                Icon(
                    imageVector = Icons.Default.List,
                    contentDescription = null,
                    modifier = Modifier.size(18.dp)
                )
            },
            colors = FilterChipDefaults.filterChipColors(
                selectedContainerColor = MaterialTheme.colorScheme.secondaryContainer,
                selectedLabelColor = MaterialTheme.colorScheme.onSecondaryContainer
            )
        )
    }
}

/**
 * Mensaje de estado vacío
 */
@Composable
private fun EmptyStateMessage(currentFilter: String) {
    Box(
        modifier = Modifier.fillMaxSize(),
        contentAlignment = Alignment.Center
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            modifier = Modifier.padding(32.dp)
        ) {
            Icon(
                imageVector = if (currentFilter == "pending") Icons.Default.CheckCircle else Icons.Default.Inbox,
                contentDescription = null,
                modifier = Modifier.size(72.dp),
                tint = MaterialTheme.colorScheme.primary.copy(alpha = 0.6f)
            )
            Spacer(modifier = Modifier.height(16.dp))
            Text(
                text = if (currentFilter == "pending") 
                    "¡Todo al día!" 
                else 
                    "No hay publicaciones",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.SemiBold
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = if (currentFilter == "pending") 
                    "No hay trabajos pendientes de moderación" 
                else 
                    "Aún no hay trabajos publicados",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
private fun SearchSection(
    searchQuery: String,
    onSearchChange: (String) -> Unit,
    onSearch: () -> Unit
) {
    OutlinedTextField(
        value = searchQuery,
        onValueChange = onSearchChange,
        placeholder = { Text("Buscar trabajos...") },
        leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
        trailingIcon = {
            if (searchQuery.isNotEmpty()) {
                IconButton(onClick = { onSearchChange("") }) {
                    Icon(Icons.Default.Clear, contentDescription = "Limpiar")
                }
            }
        },
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 4.dp),
        singleLine = true,
        shape = RoundedCornerShape(12.dp),
        keyboardActions = androidx.compose.foundation.text.KeyboardActions(
            onSearch = { onSearch() }
        )
    )
}

@Composable
private fun JobCard(
    job: JobPost,
    isSelected: Boolean,
    onSelect: () -> Unit,
    onPreview: () -> Unit,
    onEdit: () -> Unit,
    onDelete: () -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onPreview() },
        shape = RoundedCornerShape(12.dp),
        border = if (isSelected) BorderStroke(2.dp, MaterialTheme.colorScheme.primary) else null
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Checkbox de selección
            Checkbox(
                checked = isSelected,
                onCheckedChange = { onSelect() },
                modifier = Modifier.size(40.dp)
            )
            
            // Imagen
            AsyncImage(
                model = job.embedded?.featuredMedia?.firstOrNull()?.source_url,
                contentDescription = null,
                modifier = Modifier
                    .size(56.dp)
                    .clip(RoundedCornerShape(8.dp)),
                contentScale = ContentScale.Crop
            )
            
            Spacer(modifier = Modifier.width(12.dp))
            
            // Info
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = job.title?.rendered?.htmlToString() ?: "Sin título",
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.SemiBold,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis
                )
                
                Spacer(modifier = Modifier.height(4.dp))
                
                // Estado con badge
                StatusBadge(status = job.status)
            }
            
            Spacer(modifier = Modifier.width(8.dp))
            
            // Acciones - Solo íconos
            Row(
                horizontalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                // Ver
                IconButton(
                    onClick = onPreview,
                    modifier = Modifier
                        .size(36.dp)
                        .background(
                            MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.5f),
                            CircleShape
                        )
                ) {
                    Icon(
                        Icons.Default.Visibility,
                        contentDescription = "Ver",
                        modifier = Modifier.size(18.dp),
                        tint = MaterialTheme.colorScheme.primary
                    )
                }
                
                // Editar
                IconButton(
                    onClick = onEdit,
                    modifier = Modifier
                        .size(36.dp)
                        .background(
                            MaterialTheme.colorScheme.secondaryContainer.copy(alpha = 0.5f),
                            CircleShape
                        )
                ) {
                    Icon(
                        Icons.Default.Edit,
                        contentDescription = "Editar",
                        modifier = Modifier.size(18.dp),
                        tint = MaterialTheme.colorScheme.secondary
                    )
                }
                
                // Eliminar
                IconButton(
                    onClick = onDelete,
                    modifier = Modifier
                        .size(36.dp)
                        .background(
                            MaterialTheme.colorScheme.errorContainer.copy(alpha = 0.5f),
                            CircleShape
                        )
                ) {
                    Icon(
                        Icons.Default.Delete,
                        contentDescription = "Eliminar",
                        modifier = Modifier.size(18.dp),
                        tint = MaterialTheme.colorScheme.error
                    )
                }
            }
        }
    }
}

/**
 * Badge de estado del trabajo
 */
@Composable
private fun StatusBadge(status: String?) {
    val (text, backgroundColor, textColor) = when (status) {
        "publish" -> Triple("Publicado", Color(0xFF4CAF50).copy(alpha = 0.15f), Color(0xFF2E7D32))
        "pending" -> Triple("Pendiente", Color(0xFFFF9800).copy(alpha = 0.15f), Color(0xFFE65100))
        "draft" -> Triple("Borrador", Color(0xFF9E9E9E).copy(alpha = 0.15f), Color(0xFF616161))
        "trash" -> Triple("Papelera", Color(0xFFF44336).copy(alpha = 0.15f), Color(0xFFC62828))
        else -> Triple(status ?: "?", Color.Gray.copy(alpha = 0.15f), Color.Gray)
    }
    
    Surface(
        shape = RoundedCornerShape(4.dp),
        color = backgroundColor
    ) {
        Text(
            text = text,
            style = MaterialTheme.typography.labelSmall,
            color = textColor,
            fontWeight = FontWeight.Medium,
            modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
        )
    }
}

@Composable
private fun PreviewDialog(
    job: JobPost,
    onDismiss: () -> Unit,
    onEdit: () -> Unit
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { 
            Text(
                text = job.title?.rendered?.htmlToString() ?: "Sin título",
                maxLines = 2,
                overflow = TextOverflow.Ellipsis
            )
        },
        text = {
            Column(
                modifier = Modifier.verticalScroll(rememberScrollState())
            ) {
                // Estado
                StatusBadge(status = job.status)
                
                Spacer(modifier = Modifier.height(12.dp))
                
                // Imagen
                val imageUrl = job.embedded?.featuredMedia?.firstOrNull()?.source_url
                if (imageUrl != null) {
                    AsyncImage(
                        model = imageUrl,
                        contentDescription = null,
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(180.dp)
                            .clip(RoundedCornerShape(8.dp)),
                        contentScale = ContentScale.Crop
                    )
                    Spacer(modifier = Modifier.height(12.dp))
                }
                
                // Contenido
                Text(
                    text = job.content?.rendered?.htmlToString() ?: "Sin contenido",
                    style = MaterialTheme.typography.bodyMedium,
                    maxLines = 8,
                    overflow = TextOverflow.Ellipsis
                )
                
                Spacer(modifier = Modifier.height(12.dp))
                
                // Meta info
                job.meta?.let { meta ->
                    if (!meta.salarioMin.isNullOrBlank() || !meta.salarioMax.isNullOrBlank()) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(
                                Icons.Default.AttachMoney,
                                contentDescription = null,
                                modifier = Modifier.size(16.dp),
                                tint = MaterialTheme.colorScheme.primary
                            )
                            Spacer(Modifier.width(4.dp))
                            Text(
                                text = "S/ ${meta.salarioMin ?: "0"} - ${meta.salarioMax ?: "0"}",
                                style = MaterialTheme.typography.bodyMedium
                            )
                        }
                        Spacer(modifier = Modifier.height(4.dp))
                    }
                    
                    if (!meta.vacantes.isNullOrBlank()) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(
                                Icons.Default.Group,
                                contentDescription = null,
                                modifier = Modifier.size(16.dp),
                                tint = MaterialTheme.colorScheme.primary
                            )
                            Spacer(Modifier.width(4.dp))
                            Text(
                                text = "${meta.vacantes} vacantes",
                                style = MaterialTheme.typography.bodyMedium
                            )
                        }
                    }
                }
            }
        },
        confirmButton = {
            Button(onClick = onEdit) {
                Icon(Icons.Default.Edit, contentDescription = null, modifier = Modifier.size(18.dp))
                Spacer(modifier = Modifier.width(4.dp))
                Text("Editar")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cerrar")
            }
        }
    )
}
