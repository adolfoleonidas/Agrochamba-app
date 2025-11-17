package agrochamba.com.ui.jobs

import android.util.Log
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import agrochamba.com.data.JobPost
import agrochamba.com.data.PendingJobPost

// Extensiones para obtener valores de salario desde meta o campos directos
private val PendingJobPost.salarioMinValue: String?
    get() = this.salarioMin ?: this.meta?.salarioMin

private val PendingJobPost.salarioMaxValue: String?
    get() = this.salarioMax ?: this.meta?.salarioMax

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ModerationScreen(navController: NavController) {
    val viewModel: ModerationViewModel = viewModel()
    val uiState by viewModel.uiState.collectAsState()
    var showRejectDialog by remember { mutableStateOf<PendingJobPost?>(null) }
    var rejectReason by remember { mutableStateOf("") }

    LaunchedEffect(Unit) {
        viewModel.loadPendingJobs()
    }

    // Mostrar mensajes de éxito/error
    LaunchedEffect(uiState.successMessage, uiState.error) {
        if (uiState.successMessage != null || uiState.error != null) {
            kotlinx.coroutines.delay(3000)
            viewModel.clearMessages()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Moderar Trabajos") },
                navigationIcon = {
                    IconButton(onClick = { navController.popBackStack() }) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Volver")
                    }
                }
            )
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            // Mensajes de éxito/error
            uiState.successMessage?.let { message ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer)
                ) {
                    Text(
                        text = message,
                        modifier = Modifier.padding(16.dp),
                        color = MaterialTheme.colorScheme.onPrimaryContainer
                    )
                }
            }

            uiState.error?.let { error ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer)
                ) {
                    Text(
                        text = error,
                        modifier = Modifier.padding(16.dp),
                        color = MaterialTheme.colorScheme.onErrorContainer
                    )
                }
            }

            if (uiState.isLoading && uiState.pendingJobs.isEmpty()) {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator()
                }
            } else if (uiState.pendingJobs.isEmpty()) {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally,
                        modifier = Modifier.padding(32.dp)
                    ) {
                        Text(
                            text = "No hay trabajos pendientes",
                            style = MaterialTheme.typography.titleLarge,
                            modifier = Modifier.padding(bottom = 8.dp)
                        )
                        Text(
                            text = "Todos los trabajos han sido revisados",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    items(uiState.pendingJobs) { job ->
                        PendingJobCard(
                            job = job,
                            onApprove = { viewModel.approveJob(job.id) },
                            onReject = { showRejectDialog = job }
                        )
                    }
                }
            }
        }
    }

    // Diálogo para rechazar trabajo
    showRejectDialog?.let { job ->
        AlertDialog(
            onDismissRequest = { showRejectDialog = null },
            title = { Text("Rechazar Trabajo") },
            text = {
                Column {
                    Text("¿Estás seguro de que deseas rechazar este trabajo?")
                    Spacer(modifier = Modifier.height(16.dp))
                    OutlinedTextField(
                        value = rejectReason,
                        onValueChange = { rejectReason = it },
                        label = { Text("Razón (opcional)") },
                        modifier = Modifier.fillMaxWidth(),
                        maxLines = 3
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        viewModel.rejectJob(job.id, rejectReason.takeIf { it.isNotBlank() })
                        showRejectDialog = null
                        rejectReason = ""
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) {
                    Text("Rechazar")
                }
            },
            dismissButton = {
                TextButton(onClick = { showRejectDialog = null }) {
                    Text("Cancelar")
                }
            }
        )
    }
}

@Composable
fun PendingJobCard(
    job: PendingJobPost,
    onApprove: () -> Unit,
    onReject: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            // Título
            Text(
                text = job.title?.rendered ?: "Sin título",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(bottom = 8.dp)
            )

            // Información del autor
            job.authorName?.let { authorName ->
                Row(
                    modifier = Modifier.padding(bottom = 4.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "Empresa: ",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    Text(
                        text = authorName,
                        style = MaterialTheme.typography.bodyMedium,
                        fontWeight = FontWeight.Medium
                    )
                }
            }

            // Ubicación, cultivo, etc.
            job.ubicacion?.let { ubicacion ->
                Text(
                    text = "📍 $ubicacion",
                    style = MaterialTheme.typography.bodySmall,
                    modifier = Modifier.padding(vertical = 2.dp)
                )
            }

            job.cultivo?.let { cultivo ->
                Text(
                    text = "🌾 $cultivo",
                    style = MaterialTheme.typography.bodySmall,
                    modifier = Modifier.padding(vertical = 2.dp)
                )
            }

            // Salario
            if (!job.salarioMinValue.isNullOrEmpty() || !job.salarioMaxValue.isNullOrEmpty()) {
                val salarioText = when {
                    !job.salarioMinValue.isNullOrEmpty() && !job.salarioMaxValue.isNullOrEmpty() ->
                        "S/ ${job.salarioMinValue} - S/ ${job.salarioMaxValue}"
                    !job.salarioMinValue.isNullOrEmpty() -> "Desde S/ ${job.salarioMinValue}"
                    else -> "Hasta S/ ${job.salarioMaxValue}"
                }
                Text(
                    text = "💰 $salarioText",
                    style = MaterialTheme.typography.bodySmall,
                    modifier = Modifier.padding(vertical = 2.dp)
                )
            }

            // Descripción (primeros 150 caracteres)
            job.content?.rendered?.let { content ->
                val cleanContent = android.text.Html.fromHtml(content, android.text.Html.FROM_HTML_MODE_LEGACY).toString()
                Text(
                    text = if (cleanContent.length > 150) cleanContent.take(150) + "..." else cleanContent,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.padding(vertical = 8.dp)
                )
            }

            Divider(modifier = Modifier.padding(vertical = 8.dp))

            // Botones de acción
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Button(
                    onClick = onApprove,
                    modifier = Modifier.weight(1f),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = MaterialTheme.colorScheme.primary
                    )
                ) {
                    Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(18.dp))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("Aprobar")
                }

                OutlinedButton(
                    onClick = onReject,
                    modifier = Modifier.weight(1f),
                    colors = ButtonDefaults.outlinedButtonColors(
                        contentColor = MaterialTheme.colorScheme.error
                    )
                ) {
                    Icon(Icons.Default.Close, contentDescription = null, modifier = Modifier.size(18.dp))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("Rechazar")
                }
            }
        }
    }
}

