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
import androidx.compose.material.icons.filled.Stars
import androidx.compose.material.icons.filled.TrendingUp
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp

/**
 * Pantalla dedicada para mostrar el rendimiento del trabajador
 * Muestra estadísticas de: Embalaje, Selección, Clamshell, etc.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RendimientoScreen(
    onNavigateBack: () -> Unit
) {
    // TODO: Conectar con API real para obtener datos de rendimiento
    // Por ahora usamos datos de ejemplo
    val rendimientoItems = remember {
        listOf(
            RendimientoItem(
                categoria = "Embalaje",
                valor = 146,
                unidad = "cajas",
                tendencia = TendenciaRendimiento.SUBIENDO,
                fechaRegistro = "03 Feb 2026"
            ),
            RendimientoItem(
                categoria = "Selección",
                valor = 89,
                unidad = "bandejas",
                tendencia = TendenciaRendimiento.ESTABLE,
                fechaRegistro = "03 Feb 2026"
            ),
            RendimientoItem(
                categoria = "Clamshell",
                valor = 234,
                unidad = "unidades",
                tendencia = TendenciaRendimiento.SUBIENDO,
                fechaRegistro = "02 Feb 2026"
            )
        )
    }

    val totalPuntaje = remember(rendimientoItems) {
        rendimientoItems.sumOf { it.valor }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Mi Rendimiento") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Volver")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface
                )
            )
        }
    ) { padding ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .padding(horizontal = 16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Tarjeta de resumen total
            item {
                ResumenTotalCard(totalPuntaje = totalPuntaje)
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
            items(rendimientoItems) { item ->
                RendimientoItemCard(item = item)
            }

            // Espacio al final
            item {
                Spacer(modifier = Modifier.height(16.dp))
            }
        }
    }
}

@Composable
private fun ResumenTotalCard(totalPuntaje: Int) {
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
                text = totalPuntaje.toString(),
                style = MaterialTheme.typography.displayMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onPrimaryContainer
            )

            Text(
                text = "Esta semana",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onPrimaryContainer.copy(alpha = 0.7f)
            )
        }
    }
}

@Composable
private fun RendimientoItemCard(item: RendimientoItem) {
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
                    text = item.categoria,
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.SemiBold
                )
                Text(
                    text = item.fechaRegistro,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            // Valor y tendencia
            Column(horizontalAlignment = Alignment.End) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(
                        text = "${item.valor}",
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.primary
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    if (item.tendencia == TendenciaRendimiento.SUBIENDO) {
                        Icon(
                            imageVector = Icons.Default.TrendingUp,
                            contentDescription = "Subiendo",
                            modifier = Modifier.size(20.dp),
                            tint = MaterialTheme.colorScheme.primary
                        )
                    }
                }
                Text(
                    text = item.unidad,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        }
    }
}

// Modelos de datos
data class RendimientoItem(
    val categoria: String,
    val valor: Int,
    val unidad: String,
    val tendencia: TendenciaRendimiento,
    val fechaRegistro: String
)

enum class TendenciaRendimiento {
    SUBIENDO,
    BAJANDO,
    ESTABLE
}
