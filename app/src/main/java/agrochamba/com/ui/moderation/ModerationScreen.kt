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
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import androidx.lifecycle.viewmodel.compose.viewModel
import coil.compose.AsyncImage
import agrochamba.com.data.JobPost
import agrochamba.com.ui.common.FormattedText
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
    val isSelectionMode by viewModel.isSelectionMode.collectAsState()

    var showDeleteDialog by remember { mutableStateOf<Int?>(null) }
    var showPreviewDialog by remember { mutableStateOf(false) }
    // Guardar el job completo para el BottomSheet de acciones
    var jobForActionsMenu by remember { mutableStateOf<JobPost?>(null) }

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
            
            // Barra de acciones masivas (cuando está en modo selección)
            if (isSelectionMode) {
                // Determinar estados de los seleccionados
                val selectedJobs = uiState.jobs.filter { selectedJobIds.contains(it.id) }
                val pendingJobs = selectedJobs.filter { it.status == "pending" || it.status == "draft" }
                val publishedJobs = selectedJobs.filter { it.status == "publish" }
                val hasPendingSelected = pendingJobs.isNotEmpty()
                val hasPublishedSelected = publishedJobs.isNotEmpty()

                BulkActionsBar(
                    selectedCount = selectedJobIds.size,
                    totalCount = uiState.jobs.size,
                    pendingCount = pendingJobs.size,
                    publishedCount = publishedJobs.size,
                    hasPendingSelected = hasPendingSelected,
                    hasPublishedSelected = hasPublishedSelected,
                    onApproveAll = {
                        // Solo aprobar los pendientes
                        pendingJobs.forEach { job ->
                            viewModel.approveJob(job.id)
                        }
                        viewModel.clearSelection()
                    },
                    onRejectAll = {
                        // Solo rechazar los pendientes
                        pendingJobs.forEach { job ->
                            viewModel.rejectJob(job.id, "Rechazado en acción masiva")
                        }
                        viewModel.clearSelection()
                    },
                    onDeleteAll = {
                        selectedJobIds.forEach { jobId ->
                            viewModel.deleteJob(jobId)
                        }
                        viewModel.clearSelection()
                    },
                    onSelectAll = { viewModel.selectAllJobs() },
                    onClearSelection = { viewModel.clearSelection() }
                )
            }

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
                                isSelectionMode = isSelectionMode,
                                isSelected = selectedJobIds.contains(job.id),
                                onLongPress = { viewModel.enterSelectionMode(job.id) },
                                onSelect = { viewModel.toggleJobSelection(job.id) },
                                onClick = { 
                                    if (isSelectionMode) {
                                        viewModel.toggleJobSelection(job.id)
                                    } else {
                                        viewModel.selectJob(job)
                                        showPreviewDialog = true
                                    }
                                },
                                onMenuClick = { jobForActionsMenu = job }
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

    // Diálogo de vista previa de pantalla completa
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
            },
            onApprove = {
                viewModel.approveJob(selectedJob!!.id)
                showPreviewDialog = false
                viewModel.clearSelectedJob()
            },
            onReject = {
                // Por ahora rechazar sin motivo (se puede mejorar con diálogo)
                viewModel.rejectJob(selectedJob!!.id, "Rechazado por el moderador")
                showPreviewDialog = false
                viewModel.clearSelectedJob()
            }
        )
    }

    // BottomSheet de acciones para un trabajo (estilo apps profesionales)
    jobForActionsMenu?.let { job ->
        JobActionsBottomSheet(
            job = job,
            onDismiss = { jobForActionsMenu = null },
            onPreview = {
                jobForActionsMenu = null
                viewModel.selectJob(job)
                showPreviewDialog = true
            },
            onApprove = {
                jobForActionsMenu = null
                viewModel.approveJob(job.id)
            },
            onReject = {
                jobForActionsMenu = null
                viewModel.rejectJob(job.id, "Rechazado por el moderador")
            },
            onEdit = {
                jobForActionsMenu = null
                onNavigateToEditJob(job.id)
            },
            onDelete = {
                jobForActionsMenu = null
                showDeleteDialog = job.id
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

/**
 * Barra de acciones masivas cuando hay trabajos seleccionados
 * - Si solo hay pendientes: Aprobar, Rechazar
 * - Si solo hay publicados: Eliminar
 * - Si hay mezcla: Aprobar (X), Rechazar (X), Eliminar
 */
@Composable
private fun BulkActionsBar(
    selectedCount: Int,
    totalCount: Int,
    pendingCount: Int,
    publishedCount: Int,
    hasPendingSelected: Boolean,
    hasPublishedSelected: Boolean,
    onApproveAll: () -> Unit,
    onRejectAll: () -> Unit,
    onDeleteAll: () -> Unit,
    onSelectAll: () -> Unit,
    onClearSelection: () -> Unit
) {
    var showDeleteConfirmDialog by remember { mutableStateOf(false) }
    val isMixed = hasPendingSelected && hasPublishedSelected

    Surface(
        modifier = Modifier.fillMaxWidth(),
        color = MaterialTheme.colorScheme.primaryContainer,
        shadowElevation = 4.dp
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 8.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            // Contador de seleccionados + botón cerrar
            Row(verticalAlignment = Alignment.CenterVertically) {
                IconButton(onClick = onClearSelection, modifier = Modifier.size(36.dp)) {
                    Icon(Icons.Default.Close, contentDescription = "Salir de selección", modifier = Modifier.size(20.dp))
                }
                Text(
                    text = "$selectedCount de $totalCount",
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.Medium
                )
                // Seleccionar todos
                TextButton(
                    onClick = onSelectAll,
                    contentPadding = PaddingValues(horizontal = 8.dp)
                ) {
                    Text("Todos", style = MaterialTheme.typography.labelMedium)
                }
            }

            // Acciones según el estado de los seleccionados
            Row(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                // Aprobar (solo si hay pendientes)
                if (hasPendingSelected) {
                    FilledTonalButton(
                        onClick = onApproveAll,
                        colors = ButtonDefaults.filledTonalButtonColors(
                            containerColor = Color(0xFF4CAF50).copy(alpha = 0.2f),
                            contentColor = Color(0xFF2E7D32)
                        ),
                        contentPadding = PaddingValues(horizontal = 12.dp)
                    ) {
                        Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(Modifier.width(4.dp))
                        // Si es mezcla, mostrar cuántos se aprobarán
                        Text(
                            text = if (isMixed) "Aprobar ($pendingCount)" else "Aprobar",
                            style = MaterialTheme.typography.labelMedium
                        )
                    }

                    FilledTonalButton(
                        onClick = onRejectAll,
                        colors = ButtonDefaults.filledTonalButtonColors(
                            containerColor = Color(0xFFFF9800).copy(alpha = 0.2f),
                            contentColor = Color(0xFFE65100)
                        ),
                        contentPadding = PaddingValues(horizontal = 12.dp)
                    ) {
                        Icon(Icons.Default.Block, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(Modifier.width(4.dp))
                        Text(
                            text = if (isMixed) "Rechazar ($pendingCount)" else "Rechazar",
                            style = MaterialTheme.typography.labelMedium
                        )
                    }
                }

                // Eliminar (siempre disponible, pero más prominente si no hay pendientes)
                FilledTonalButton(
                    onClick = { showDeleteConfirmDialog = true },
                    colors = ButtonDefaults.filledTonalButtonColors(
                        containerColor = Color(0xFFD32F2F).copy(alpha = 0.2f),
                        contentColor = Color(0xFFD32F2F)
                    ),
                    contentPadding = PaddingValues(horizontal = 12.dp)
                ) {
                    Icon(Icons.Default.Delete, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(Modifier.width(4.dp))
                    Text("Eliminar", style = MaterialTheme.typography.labelMedium)
                }
            }
        }
    }

    // Diálogo de confirmación para eliminar masivamente
    if (showDeleteConfirmDialog) {
        AlertDialog(
            onDismissRequest = { showDeleteConfirmDialog = false },
            icon = {
                Icon(
                    Icons.Default.Delete,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.error
                )
            },
            title = { Text("Eliminar $selectedCount trabajos") },
            text = { Text("¿Estás seguro de que deseas eliminar los trabajos seleccionados? Esta acción no se puede deshacer.") },
            confirmButton = {
                Button(
                    onClick = {
                        showDeleteConfirmDialog = false
                        onDeleteAll()
                    },
                    colors = ButtonDefaults.buttonColors(
                        containerColor = MaterialTheme.colorScheme.error
                    )
                ) {
                    Text("Eliminar")
                }
            },
            dismissButton = {
                TextButton(onClick = { showDeleteConfirmDialog = false }) {
                    Text("Cancelar")
                }
            }
        )
    }
}

/**
 * BottomSheet de acciones para un trabajo individual
 * Estilo profesional como Instagram, WhatsApp, etc.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun JobActionsBottomSheet(
    job: JobPost,
    onDismiss: () -> Unit,
    onPreview: () -> Unit,
    onApprove: () -> Unit,
    onReject: () -> Unit,
    onEdit: () -> Unit,
    onDelete: () -> Unit
) {
    val isPending = job.status == "pending" || job.status == "draft"
    val sheetState = rememberModalBottomSheetState()

    ModalBottomSheet(
        onDismissRequest = onDismiss,
        sheetState = sheetState,
        dragHandle = { BottomSheetDefaults.DragHandle() }
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 32.dp) // Espacio para navegación del sistema
        ) {
            // Título del trabajo
            Text(
                text = job.title?.rendered?.replace(Regex("<[^>]*>"), "") ?: "Sin título",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                maxLines = 2,
                overflow = TextOverflow.Ellipsis,
                modifier = Modifier.padding(horizontal = 24.dp, vertical = 16.dp)
            )

            HorizontalDivider()

            // Ver detalles
            ActionSheetItem(
                icon = Icons.Default.Visibility,
                text = "Ver detalles",
                onClick = onPreview
            )

            // Aprobar (solo para pendientes)
            if (isPending) {
                ActionSheetItem(
                    icon = Icons.Default.Check,
                    text = "Aprobar",
                    tint = Color(0xFF4CAF50),
                    onClick = onApprove
                )

                ActionSheetItem(
                    icon = Icons.Default.Block,
                    text = "Rechazar",
                    tint = Color(0xFFFF9800),
                    onClick = onReject
                )
            }

            HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp))

            // Editar
            ActionSheetItem(
                icon = Icons.Default.Edit,
                text = "Editar",
                onClick = onEdit
            )

            // Eliminar
            ActionSheetItem(
                icon = Icons.Default.Delete,
                text = "Eliminar",
                tint = Color(0xFFD32F2F),
                onClick = onDelete
            )
        }
    }
}

/**
 * Item individual del ActionSheet
 */
@Composable
private fun ActionSheetItem(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    text: String,
    tint: Color = MaterialTheme.colorScheme.onSurface,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(horizontal = 24.dp, vertical = 16.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        Icon(
            imageVector = icon,
            contentDescription = null,
            tint = tint,
            modifier = Modifier.size(24.dp)
        )
        Text(
            text = text,
            style = MaterialTheme.typography.bodyLarge,
            color = tint
        )
    }
}

@OptIn(ExperimentalFoundationApi::class)
@Composable
private fun JobCard(
    job: JobPost,
    isSelectionMode: Boolean,
    isSelected: Boolean,
    onLongPress: () -> Unit,
    onSelect: () -> Unit,
    onClick: () -> Unit,
    onMenuClick: () -> Unit
) {
    val isPending = job.status == "pending" || job.status == "draft"
    
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .combinedClickable(
                onClick = onClick,
                onLongClick = onLongPress
            ),
        shape = RoundedCornerShape(12.dp),
        border = if (isSelected) BorderStroke(2.dp, MaterialTheme.colorScheme.primary) else null,
        colors = CardDefaults.cardColors(
            containerColor = if (isSelected) 
                MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.3f)
            else 
                MaterialTheme.colorScheme.surface
        )
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            // Checkbox (solo en modo selección) o Imagen
            if (isSelectionMode) {
                Checkbox(
                    checked = isSelected,
                    onCheckedChange = { onSelect() },
                    modifier = Modifier.size(24.dp)
                )
            }
            
            // Imagen
            AsyncImage(
                model = job.featuredImageUrl ?: job.embedded?.featuredMedia?.firstOrNull()?.source_url,
                contentDescription = null,
                modifier = Modifier
                    .size(56.dp)
                    .clip(RoundedCornerShape(8.dp))
                    .background(MaterialTheme.colorScheme.surfaceVariant),
                contentScale = ContentScale.Crop
            )
            
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
                
                // Fecha y Estado
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    // Fecha
                    job.date?.let { date ->
                        Text(
                            text = date.take(10), // Solo la fecha YYYY-MM-DD
                            style = MaterialTheme.typography.labelSmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                    
                    // Estado con badge
                    StatusBadge(status = job.status)
                }
            }
            
            // Menú de 3 puntos (solo fuera del modo selección)
            if (!isSelectionMode) {
                Box {
                    IconButton(
                        onClick = onMenuClick,
                        modifier = Modifier.size(36.dp)
                    ) {
                        Icon(
                            Icons.Default.MoreVert,
                            contentDescription = "Más opciones",
                            modifier = Modifier.size(20.dp),
                            tint = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
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

/**
 * Diálogo de previsualización de trabajo de pantalla completa
 * Diseño similar a JobDetailScreen para que el admin vea exactamente
 * cómo se verá el trabajo cuando esté publicado
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun PreviewDialog(
    job: JobPost,
    onDismiss: () -> Unit,
    onEdit: () -> Unit,
    onApprove: () -> Unit = {},
    onReject: () -> Unit = {}
) {
    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Scaffold(
            topBar = {
                TopAppBar(
                    title = { Text("Vista Previa") },
                    navigationIcon = {
                        IconButton(onClick = onDismiss) {
                            Icon(Icons.Default.Close, contentDescription = "Cerrar")
                        }
                    },
                    actions = {
                        // Estado del trabajo
                        StatusBadge(status = job.status)
                        Spacer(Modifier.width(8.dp))
                    }
                )
            },
            bottomBar = {
                // Barra de acciones inferior
                Surface(
                    shadowElevation = 8.dp,
                    color = MaterialTheme.colorScheme.surface
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        // Botón Editar
                        OutlinedButton(
                            onClick = onEdit,
                            modifier = Modifier.weight(1f)
                        ) {
                            Icon(Icons.Default.Edit, contentDescription = null, modifier = Modifier.size(18.dp))
                            Spacer(Modifier.width(6.dp))
                            Text("Editar")
                        }
                        
                        // Botón Aprobar (solo si está pendiente/draft)
                        if (job.status == "pending" || job.status == "draft") {
                            Button(
                                onClick = onApprove,
                                modifier = Modifier.weight(1f),
                                colors = ButtonDefaults.buttonColors(
                                    containerColor = Color(0xFF4CAF50)
                                )
                            ) {
                                Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(18.dp))
                                Spacer(Modifier.width(6.dp))
                                Text("Aprobar")
                            }
                        }
                    }
                }
            }
        ) { paddingValues ->
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
                    .verticalScroll(rememberScrollState())
            ) {
                // Imagen principal
                val imageUrl = job.featuredImageUrl 
                    ?: job.embedded?.featuredMedia?.firstOrNull()?.source_url
                
                if (imageUrl != null) {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(250.dp)
                    ) {
                        AsyncImage(
                            model = imageUrl,
                            contentDescription = null,
                            modifier = Modifier.fillMaxSize(),
                            contentScale = ContentScale.Crop
                        )
                        // Gradiente inferior
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(80.dp)
                                .align(Alignment.BottomCenter)
                                .background(
                                    Brush.verticalGradient(
                                        colors = listOf(
                                            Color.Transparent,
                                            Color.Black.copy(alpha = 0.6f)
                                        )
                                    )
                                )
                        )
                    }
                }
                
                // Contenido
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp)
                ) {
                    // Título
                    Text(
                        text = job.title?.rendered?.htmlToString() ?: "Sin título",
                        style = MaterialTheme.typography.headlineSmall,
                        fontWeight = FontWeight.Bold
                    )
                    
                    Spacer(Modifier.height(16.dp))
                    
                    // Info rápida
                    job.meta?.let { meta ->
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            colors = CardDefaults.cardColors(
                                containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                            )
                        ) {
                            Column(
                                modifier = Modifier.padding(16.dp),
                                verticalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                // Salario
                                if (!meta.salarioMin.isNullOrBlank() || !meta.salarioMax.isNullOrBlank()) {
                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                        Icon(
                                            Icons.Default.AttachMoney,
                                            contentDescription = null,
                                            modifier = Modifier.size(20.dp),
                                            tint = Color(0xFF4CAF50)
                                        )
                                        Spacer(Modifier.width(12.dp))
                                        Text(
                                            text = "S/ ${meta.salarioMin ?: "0"} - ${meta.salarioMax ?: "0"}",
                                            style = MaterialTheme.typography.titleMedium,
                                            fontWeight = FontWeight.SemiBold,
                                            color = Color(0xFF4CAF50)
                                        )
                                    }
                                }
                                
                                // Vacantes
                                if (!meta.vacantes.isNullOrBlank()) {
                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                        Icon(
                                            Icons.Default.Group,
                                            contentDescription = null,
                                            modifier = Modifier.size(20.dp),
                                            tint = MaterialTheme.colorScheme.primary
                                        )
                                        Spacer(Modifier.width(12.dp))
                                        Text(
                                            text = "${meta.vacantes} vacantes",
                                            style = MaterialTheme.typography.bodyLarge
                                        )
                                    }
                                }
                                
                                // Ubicación
                                meta.ubicacionCompleta?.let { ubicacion ->
                                    if (ubicacion.departamento.isNotBlank()) {
                                        Row(verticalAlignment = Alignment.CenterVertically) {
                                            Icon(
                                                Icons.Default.LocationOn,
                                                contentDescription = null,
                                                modifier = Modifier.size(20.dp),
                                                tint = MaterialTheme.colorScheme.primary
                                            )
                                            Spacer(Modifier.width(12.dp))
                                            Text(
                                                text = ubicacion.formatOneLine(),
                                                style = MaterialTheme.typography.bodyLarge
                                            )
                                        }
                                    }
                                }
                                
                                // Beneficios
                                val beneficios = mutableListOf<String>()
                                if (meta.alojamiento == true) beneficios.add("🏠 Alojamiento")
                                if (meta.transporte == true) beneficios.add("🚌 Transporte")
                                if (meta.alimentacion == true) beneficios.add("🍽️ Alimentación")
                                
                                if (beneficios.isNotEmpty()) {
                                    Row(
                                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                                    ) {
                                        beneficios.forEach { beneficio ->
                                            Surface(
                                                shape = RoundedCornerShape(16.dp),
                                                color = MaterialTheme.colorScheme.primaryContainer
                                            ) {
                                                Text(
                                                    text = beneficio,
                                                    modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                                                    style = MaterialTheme.typography.labelMedium
                                                )
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    Spacer(Modifier.height(20.dp))
                    
                    // Descripción del trabajo
                    Text(
                        text = "Descripción",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )
                    
                    Spacer(Modifier.height(8.dp))
                    
                    // Usar FormattedText para renderizar HTML correctamente
                    val contentHtml = job.content?.rendered ?: ""
                    if (contentHtml.isNotBlank()) {
                        FormattedText(
                            text = contentHtml,
                            style = MaterialTheme.typography.bodyMedium,
                            modifier = Modifier.fillMaxWidth()
                        )
                    } else {
                        Text(
                            text = "Sin descripción",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                    
                    // Espacio para el bottom bar
                    Spacer(Modifier.height(80.dp))
                }
            }
        }
    }
}
