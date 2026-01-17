package agrochamba.com.ui.common

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.expandHorizontally
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.shrinkHorizontally
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.FilterList
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Work
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/**
 * =============================================================================
 * FILTER CHIPS BAR - Barra de chips de filtro removibles
 * =============================================================================
 * 
 * Muestra:
 * - Contador de resultados
 * - Chips de filtros activos con botón ✕ para quitar
 * - Patrón Google Jobs / Indeed
 */

/**
 * Modelo de filtro activo
 */
data class ActiveFilter(
    val id: String,
    val label: String,
    val icon: String? = null, // Emoji
    val type: FilterType = FilterType.OTHER
)

enum class FilterType {
    LOCATION,
    CROP,
    JOB_TYPE,
    SALARY,
    BENEFITS,
    OTHER
}

/**
 * Barra completa con contador y chips
 */
@Composable
fun FilterChipsBar(
    resultCount: Int,
    filters: List<ActiveFilter>,
    onRemoveFilter: (ActiveFilter) -> Unit,
    onClearAll: () -> Unit,
    searchQuery: String = "",
    locationName: String? = null,
    modifier: Modifier = Modifier,
    isLoading: Boolean = false
) {
    Column(
        modifier = modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp)
    ) {
        // Contador de resultados
        ResultsCounter(
            count = resultCount,
            searchQuery = searchQuery,
            locationName = locationName,
            isLoading = isLoading
        )
        
        // Chips de filtros activos
        AnimatedVisibility(
            visible = filters.isNotEmpty(),
            enter = expandHorizontally() + fadeIn(),
            exit = shrinkHorizontally() + fadeOut()
        ) {
            Column {
                Spacer(Modifier.height(8.dp))
                FilterChipsRow(
                    filters = filters,
                    onRemoveFilter = onRemoveFilter,
                    onClearAll = onClearAll
                )
            }
        }
    }
}

/**
 * Contador de resultados
 */
@Composable
fun ResultsCounter(
    count: Int,
    searchQuery: String = "",
    locationName: String? = null,
    isLoading: Boolean = false,
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically
    ) {
        if (isLoading) {
            Text(
                text = "Buscando...",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        } else {
            // Número destacado
            Text(
                text = "$count",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.primary
            )
            
            Spacer(Modifier.width(6.dp))
            
            // Texto descriptivo
            val description = buildString {
                append(if (count == 1) "trabajo" else "trabajos")
                
                if (searchQuery.isNotBlank()) {
                    append(" de \"$searchQuery\"")
                }
                
                if (locationName != null) {
                    append(" en $locationName")
                }
            }
            
            Text(
                text = description,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

/**
 * Fila horizontal de chips de filtro
 */
@Composable
fun FilterChipsRow(
    filters: List<ActiveFilter>,
    onRemoveFilter: (ActiveFilter) -> Unit,
    onClearAll: () -> Unit,
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier
            .fillMaxWidth()
            .horizontalScroll(rememberScrollState()),
        horizontalArrangement = Arrangement.spacedBy(8.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        // Label "Filtros:"
        Row(
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                imageVector = Icons.Default.FilterList,
                contentDescription = null,
                modifier = Modifier.size(16.dp),
                tint = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Spacer(Modifier.width(4.dp))
            Text(
                text = "Filtros:",
                style = MaterialTheme.typography.labelMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        
        // Chips de filtros
        filters.forEach { filter ->
            RemovableFilterChip(
                filter = filter,
                onRemove = { onRemoveFilter(filter) }
            )
        }
        
        // Botón "Limpiar todo" si hay más de 1 filtro
        if (filters.size > 1) {
            Surface(
                modifier = Modifier
                    .clip(RoundedCornerShape(16.dp))
                    .clickable { onClearAll() },
                color = MaterialTheme.colorScheme.errorContainer.copy(alpha = 0.5f),
                shape = RoundedCornerShape(16.dp)
            ) {
                Text(
                    text = "Limpiar todo",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.error,
                    modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp)
                )
            }
        }
    }
}

/**
 * Chip de filtro individual removible
 */
@Composable
fun RemovableFilterChip(
    filter: ActiveFilter,
    onRemove: () -> Unit,
    modifier: Modifier = Modifier
) {
    val chipColor = when (filter.type) {
        FilterType.LOCATION -> MaterialTheme.colorScheme.primaryContainer
        FilterType.CROP -> Color(0xFFE8F5E9) // Verde claro
        FilterType.JOB_TYPE -> Color(0xFFE3F2FD) // Azul claro
        FilterType.SALARY -> Color(0xFFFFF8E1) // Amarillo claro
        FilterType.BENEFITS -> Color(0xFFF3E5F5) // Púrpura claro
        FilterType.OTHER -> MaterialTheme.colorScheme.surfaceVariant
    }
    
    val iconColor = when (filter.type) {
        FilterType.LOCATION -> MaterialTheme.colorScheme.primary
        FilterType.CROP -> Color(0xFF2E7D32)
        FilterType.JOB_TYPE -> Color(0xFF1565C0)
        FilterType.SALARY -> Color(0xFFF9A825)
        FilterType.BENEFITS -> Color(0xFF7B1FA2)
        FilterType.OTHER -> MaterialTheme.colorScheme.onSurfaceVariant
    }
    
    Surface(
        modifier = modifier,
        color = chipColor,
        shape = RoundedCornerShape(16.dp)
    ) {
        Row(
            modifier = Modifier.padding(start = 10.dp, end = 4.dp, top = 4.dp, bottom = 4.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Emoji o ícono
            if (filter.icon != null) {
                Text(
                    text = filter.icon,
                    fontSize = 14.sp
                )
                Spacer(Modifier.width(4.dp))
            }
            
            // Label
            Text(
                text = filter.label,
                style = MaterialTheme.typography.labelMedium,
                fontWeight = FontWeight.Medium,
                color = iconColor
            )
            
            Spacer(Modifier.width(4.dp))
            
            // Botón de cerrar
            Box(
                modifier = Modifier
                    .size(20.dp)
                    .clip(CircleShape)
                    .background(iconColor.copy(alpha = 0.2f))
                    .clickable { onRemove() },
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = Icons.Default.Close,
                    contentDescription = "Quitar filtro",
                    modifier = Modifier.size(12.dp),
                    tint = iconColor
                )
            }
        }
    }
}

/**
 * Chip simple (no removible) para mostrar información
 */
@Composable
fun InfoChip(
    text: String,
    icon: String? = null,
    color: Color = MaterialTheme.colorScheme.surfaceVariant,
    textColor: Color = MaterialTheme.colorScheme.onSurfaceVariant,
    modifier: Modifier = Modifier
) {
    Surface(
        modifier = modifier,
        color = color,
        shape = RoundedCornerShape(16.dp)
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            if (icon != null) {
                Text(text = icon, fontSize = 12.sp)
                Spacer(Modifier.width(4.dp))
            }
            Text(
                text = text,
                style = MaterialTheme.typography.labelMedium,
                color = textColor
            )
        }
    }
}

/**
 * Barra de estado de búsqueda cuando no hay resultados
 */
@Composable
fun NoResultsMessage(
    searchQuery: String,
    locationName: String?,
    suggestions: List<String> = emptyList(),
    onSuggestionClick: (String) -> Unit = {},
    onClearFilters: () -> Unit = {},
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier
            .fillMaxWidth()
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text(
            text = "😔",
            fontSize = 48.sp
        )
        
        Spacer(Modifier.height(16.dp))
        
        Text(
            text = "No encontramos trabajos exactos",
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurface
        )
        
        Spacer(Modifier.height(8.dp))
        
        val searchText = buildString {
            if (searchQuery.isNotBlank()) append("\"$searchQuery\"")
            if (locationName != null) {
                if (isNotEmpty()) append(" en ")
                append(locationName)
            }
        }
        
        if (searchText.isNotBlank()) {
            Text(
                text = searchText,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        
        if (suggestions.isNotEmpty()) {
            Spacer(Modifier.height(24.dp))
            
            Text(
                text = "Sugerencias:",
                style = MaterialTheme.typography.labelLarge,
                fontWeight = FontWeight.Medium,
                color = MaterialTheme.colorScheme.onSurface
            )
            
            Spacer(Modifier.height(12.dp))
            
            suggestions.forEach { suggestion ->
                Surface(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 4.dp)
                        .clip(RoundedCornerShape(8.dp))
                        .clickable { onSuggestionClick(suggestion) },
                    color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.5f),
                    shape = RoundedCornerShape(8.dp)
                ) {
                    Row(
                        modifier = Modifier.padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(text = "💡", fontSize = 16.sp)
                        Spacer(Modifier.width(8.dp))
                        Text(
                            text = suggestion,
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.primary
                        )
                    }
                }
            }
        }
        
        Spacer(Modifier.height(16.dp))
        
        // Botón para limpiar filtros
        Surface(
            modifier = Modifier
                .clip(RoundedCornerShape(20.dp))
                .clickable { onClearFilters() },
            color = MaterialTheme.colorScheme.primary,
            shape = RoundedCornerShape(20.dp)
        ) {
            Text(
                text = "Ver todos los trabajos",
                style = MaterialTheme.typography.labelLarge,
                color = MaterialTheme.colorScheme.onPrimary,
                modifier = Modifier.padding(horizontal = 24.dp, vertical = 12.dp)
            )
        }
    }
}

