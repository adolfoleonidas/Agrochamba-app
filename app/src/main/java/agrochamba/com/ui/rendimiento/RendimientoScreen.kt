package agrochamba.com.ui.rendimiento

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.EmojiEvents
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.TrendingDown
import androidx.compose.material.icons.filled.TrendingUp
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import agrochamba.com.data.RendimientoCategoria

/**
 * Pantalla dedicada para mostrar el rendimiento del trabajador
 * Muestra estadísticas de: Embalaje, Selección, Clamshell, etc.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RendimientoScreen(
    onNavigateBack: () -> Unit,
    viewModel: RendimientoViewModel = viewModel()
) {
    val uiState = viewModel.uiState

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Mi Rendimiento") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Volver")
                    }
                },
                actions = {
                    IconButton(onClick = { viewModel.refresh() }) {
                        Icon(Icons.Default.Refresh, contentDescription = "Actualizar")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface
                )
            )
        }
    ) { padding ->
        when {
            uiState.isLoading -> {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(padding),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator()
                }
            }

            uiState.error != null -> {
                ErrorContent(
                    error = uiState.error,
                    onRetry = { viewModel.refresh() },
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(padding)
                )
            }

            uiState.categorias.isEmpty() -> {
                EmptyContent(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(padding)
                )
            }

            else -> {
                LazyColumn(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(padding)
                        .padding(horizontal = 16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    // Selector de período
                    item {
                        PeriodoSelector(
                            periodoActual = uiState.periodo,
                            onPeriodoChange = { viewModel.changePeriodo(it) }
                        )
                    }

                    // Tarjeta de resumen total
                    item {
                        ResumenTotalCard(
                            totalPuntaje = uiState.totalGeneral,
                            periodo = uiState.periodo
                        )
                    }

                    // Título de sección
                    item {
                        Text(
                            text = "Detalle por Categoría",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.SemiBold,
                            modifier = Modifier.padding(top = 8.dp)
                        )
                    }

                    // Lista de rendimientos por categoría
                    items(uiState.categorias) { categoria ->
                        RendimientoCategoriaCard(categoria = categoria)
                    }

                    // Espacio al final
                    item {
                        Spacer(modifier = Modifier.height(16.dp))
                    }
                }
            }
        }
    }
}

@Composable
private fun PeriodoSelector(
    periodoActual: String,
    onPeriodoChange: (String) -> Unit
) {
    val periodos = listOf(
        "semana" to "Esta semana",
        "mes" to "Este mes",
        "año" to "Este año"
    )

    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        periodos.forEach { (value, label) ->
            FilterChip(
                selected = periodoActual == value,
                onClick = { onPeriodoChange(value) },
                label = { Text(label) },
                modifier = Modifier.weight(1f)
            )
        }
    }
}

@Composable
private fun ResumenTotalCard(
    totalPuntaje: Double,
    periodo: String
) {
    val periodoLabel = when (periodo) {
        "semana" -> "Esta semana"
        "mes" -> "Este mes"
        "año" -> "Este año"
        else -> periodo
    }

    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.primaryContainer
        ),
        shape = RoundedCornerShape(16.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Icon(
                imageVector = Icons.Default.EmojiEvents,
                contentDescription = null,
                modifier = Modifier.size(48.dp),
                tint = MaterialTheme.colorScheme.primary
            )

            Spacer(modifier = Modifier.height(12.dp))

            Text(
                text = "Puntaje Total",
                style = MaterialTheme.typography.titleMedium,
                color = MaterialTheme.colorScheme.onPrimaryContainer
            )

            Text(
                text = totalPuntaje.toInt().toString(),
                style = MaterialTheme.typography.displayMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onPrimaryContainer
            )

            Text(
                text = periodoLabel,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onPrimaryContainer.copy(alpha = 0.7f)
            )
        }
    }
}

@Composable
private fun RendimientoCategoriaCard(categoria: RendimientoCategoria) {
    val tendenciaIcon = when (categoria.tendencia) {
        "subiendo" -> Icons.Default.TrendingUp
        "bajando" -> Icons.Default.TrendingDown
        else -> null
    }

    val tendenciaColor = when (categoria.tendencia) {
        "subiendo" -> MaterialTheme.colorScheme.primary
        "bajando" -> MaterialTheme.colorScheme.error
        else -> MaterialTheme.colorScheme.onSurfaceVariant
    }

    // Formatear fecha
    val fechaFormateada = try {
        val parts = categoria.ultimaFecha.split("-")
        if (parts.size == 3) {
            val meses = listOf("Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic")
            "${parts[2]} ${meses[parts[1].toInt() - 1]} ${parts[0]}"
        } else {
            categoria.ultimaFecha
        }
    } catch (e: Exception) {
        categoria.ultimaFecha
    }

    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant
        ),
        shape = RoundedCornerShape(12.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Icono de categoría
            Box(
                modifier = Modifier
                    .size(48.dp)
                    .clip(RoundedCornerShape(12.dp))
                    .background(MaterialTheme.colorScheme.primary.copy(alpha = 0.1f)),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = Icons.Default.Inventory2,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary
                )
            }

            Spacer(modifier = Modifier.width(16.dp))

            // Información de rendimiento
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = categoria.categoria.replaceFirstChar { it.uppercase() },
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.SemiBold
                )
                Text(
                    text = fechaFormateada,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Text(
                    text = "${categoria.registros} registros",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f)
                )
            }

            // Valor y tendencia
            Column(horizontalAlignment = Alignment.End) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(
                        text = categoria.total.toInt().toString(),
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.primary
                    )
                    if (tendenciaIcon != null) {
                        Spacer(modifier = Modifier.width(4.dp))
                        Icon(
                            imageVector = tendenciaIcon,
                            contentDescription = categoria.tendencia,
                            modifier = Modifier.size(20.dp),
                            tint = tendenciaColor
                        )
                    }
                }
                Text(
                    text = categoria.unidad,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        }
    }
}

@Composable
private fun ErrorContent(
    error: String,
    onRetry: () -> Unit,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier.padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text(
            text = error,
            style = MaterialTheme.typography.bodyLarge,
            textAlign = TextAlign.Center,
            color = MaterialTheme.colorScheme.error
        )
        Spacer(modifier = Modifier.height(16.dp))
        Button(onClick = onRetry) {
            Text("Reintentar")
        }
    }
}

@Composable
private fun EmptyContent(modifier: Modifier = Modifier) {
    Column(
        modifier = modifier.padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(
            imageVector = Icons.Default.Inventory2,
            contentDescription = null,
            modifier = Modifier.size(64.dp),
            tint = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.5f)
        )
        Spacer(modifier = Modifier.height(16.dp))
        Text(
            text = "Sin registros de rendimiento",
            style = MaterialTheme.typography.titleMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Spacer(modifier = Modifier.height(8.dp))
        Text(
            text = "Cuando tu empresa registre tu rendimiento, aparecerá aquí.",
            style = MaterialTheme.typography.bodyMedium,
            textAlign = TextAlign.Center,
            color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f)
        )
    }
}
