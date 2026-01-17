package agrochamba.com.ui.common

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.expandVertically
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.shrinkVertically
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.NotificationsActive
import androidx.compose.material.icons.filled.NotificationsOff
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import agrochamba.com.data.SearchAlert
import agrochamba.com.data.SearchAlertService

/**
 * =============================================================================
 * SEARCH ALERT BANNER - Banner para crear alertas de búsqueda
 * =============================================================================
 * 
 * Se muestra después de una búsqueda exitosa para ofrecer al usuario
 * crear una alerta y recibir notificaciones.
 */

/**
 * Banner que sugiere crear una alerta
 */
@Composable
fun CreateAlertBanner(
    searchQuery: String,
    locationId: Int? = null,
    locationName: String? = null,
    cropId: Int? = null,
    cropName: String? = null,
    jobTypeId: Int? = null,
    jobTypeName: String? = null,
    onDismiss: () -> Unit,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val alertService = remember { SearchAlertService.getInstance(context) }
    
    var isCreated by remember { mutableStateOf(false) }
    var isDismissed by remember { mutableStateOf(false) }
    
    // Verificar si ya existe una alerta para esta búsqueda
    val hasExistingAlert = remember(searchQuery, locationId, cropId, jobTypeId) {
        alertService.hasAlertFor(searchQuery, locationId, cropId, jobTypeId)
    }
    
    AnimatedVisibility(
        visible = !isDismissed && !hasExistingAlert,
        enter = expandVertically() + fadeIn(),
        exit = shrinkVertically() + fadeOut()
    ) {
        Card(
            modifier = modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 8.dp),
            shape = RoundedCornerShape(16.dp),
            colors = CardDefaults.cardColors(
                containerColor = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.7f)
            )
        ) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Icono
                Icon(
                    imageVector = if (isCreated) Icons.Default.NotificationsActive else Icons.Default.Notifications,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(32.dp)
                )
                
                Spacer(Modifier.width(12.dp))
                
                // Texto
                Column(modifier = Modifier.weight(1f)) {
                    if (isCreated) {
                        Text(
                            text = "✓ Alerta creada",
                            style = MaterialTheme.typography.titleSmall,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.primary
                        )
                        Text(
                            text = "Te notificaremos cuando haya nuevos trabajos",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onPrimaryContainer
                        )
                    } else {
                        Text(
                            text = "🔔 ¿Crear alerta para esta búsqueda?",
                            style = MaterialTheme.typography.titleSmall,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onPrimaryContainer
                        )
                        Text(
                            text = "Te notificaremos cuando haya nuevos trabajos",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onPrimaryContainer.copy(alpha = 0.8f)
                        )
                    }
                }
                
                Spacer(Modifier.width(8.dp))
                
                // Botones
                if (isCreated) {
                    IconButton(
                        onClick = { isDismissed = true },
                        modifier = Modifier.size(32.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Close,
                            contentDescription = "Cerrar",
                            tint = MaterialTheme.colorScheme.onPrimaryContainer
                        )
                    }
                } else {
                    Column(
                        horizontalAlignment = Alignment.End,
                        verticalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        Button(
                            onClick = {
                                alertService.createAlert(
                                    query = searchQuery,
                                    locationId = locationId,
                                    locationName = locationName,
                                    cropId = cropId,
                                    cropName = cropName,
                                    jobTypeId = jobTypeId,
                                    jobTypeName = jobTypeName
                                )
                                isCreated = true
                            },
                            colors = ButtonDefaults.buttonColors(
                                containerColor = MaterialTheme.colorScheme.primary
                            ),
                            modifier = Modifier.height(32.dp)
                        ) {
                            Text("Crear", fontSize = 12.sp)
                        }
                        
                        Text(
                            text = "No, gracias",
                            style = MaterialTheme.typography.labelSmall,
                            color = MaterialTheme.colorScheme.onPrimaryContainer.copy(alpha = 0.6f),
                            modifier = Modifier.clickable { 
                                isDismissed = true
                                onDismiss()
                            }
                        )
                    }
                }
            }
        }
    }
}

/**
 * Lista de alertas guardadas
 */
@Composable
fun SavedAlertsList(
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val alertService = remember { SearchAlertService.getInstance(context) }
    val alerts by alertService.alerts.collectAsState()
    
    Column(modifier = modifier.fillMaxWidth()) {
        Text(
            text = "🔔 Mis alertas de búsqueda",
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)
        )
        
        if (alerts.isEmpty()) {
            Text(
                text = "No tienes alertas guardadas",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.padding(horizontal = 16.dp, vertical = 16.dp)
            )
        } else {
            alerts.forEach { alert ->
                AlertItem(
                    alert = alert,
                    onToggle = { alertService.toggleAlert(alert.id) },
                    onDelete = { alertService.deleteAlert(alert.id) }
                )
            }
        }
    }
}

/**
 * Item de alerta individual
 */
@Composable
private fun AlertItem(
    alert: SearchAlert,
    onToggle: () -> Unit,
    onDelete: () -> Unit,
    modifier: Modifier = Modifier
) {
    Surface(
        modifier = modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 4.dp),
        shape = RoundedCornerShape(12.dp),
        color = if (alert.isActive) 
            MaterialTheme.colorScheme.surfaceVariant 
        else 
            MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Icono de estado
            Icon(
                imageVector = if (alert.isActive) Icons.Default.NotificationsActive else Icons.Default.NotificationsOff,
                contentDescription = null,
                tint = if (alert.isActive) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.size(24.dp)
            )
            
            Spacer(Modifier.width(12.dp))
            
            // Información
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = alert.getDisplayName(),
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.Medium,
                    color = if (alert.isActive) 
                        MaterialTheme.colorScheme.onSurface 
                    else 
                        MaterialTheme.colorScheme.onSurfaceVariant
                )
                
                if (alert.lastJobCount > 0) {
                    Text(
                        text = "${alert.lastJobCount} trabajos encontrados",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
            
            // Switch para activar/desactivar
            Switch(
                checked = alert.isActive,
                onCheckedChange = { onToggle() }
            )
            
            // Botón eliminar
            IconButton(
                onClick = onDelete,
                modifier = Modifier.size(32.dp)
            ) {
                Icon(
                    imageVector = Icons.Default.Delete,
                    contentDescription = "Eliminar alerta",
                    tint = MaterialTheme.colorScheme.error,
                    modifier = Modifier.size(20.dp)
                )
            }
        }
    }
}

/**
 * Botón compacto para crear alerta (para usar en la barra de filtros)
 */
@Composable
fun CreateAlertButton(
    onClick: () -> Unit,
    hasAlert: Boolean = false,
    modifier: Modifier = Modifier
) {
    Surface(
        modifier = modifier
            .clip(RoundedCornerShape(20.dp))
            .clickable(enabled = !hasAlert) { onClick() },
        color = if (hasAlert) 
            MaterialTheme.colorScheme.primaryContainer 
        else 
            MaterialTheme.colorScheme.primary,
        shape = RoundedCornerShape(20.dp)
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                imageVector = if (hasAlert) Icons.Default.NotificationsActive else Icons.Default.Notifications,
                contentDescription = null,
                modifier = Modifier.size(16.dp),
                tint = if (hasAlert) 
                    MaterialTheme.colorScheme.primary 
                else 
                    MaterialTheme.colorScheme.onPrimary
            )
            Spacer(Modifier.width(4.dp))
            Text(
                text = if (hasAlert) "Alerta activa" else "Crear alerta",
                style = MaterialTheme.typography.labelMedium,
                color = if (hasAlert) 
                    MaterialTheme.colorScheme.primary 
                else 
                    MaterialTheme.colorScheme.onPrimary
            )
        }
    }
}

