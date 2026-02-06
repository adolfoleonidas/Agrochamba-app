package agrochamba.com.ui.home

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Agriculture
import androidx.compose.material.icons.filled.Assignment
import androidx.compose.material.icons.filled.Build
import androidx.compose.material.icons.filled.ChevronRight
import androidx.compose.material.icons.filled.Engineering
import androidx.compose.material.icons.filled.LocalShipping
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Stars
import androidx.compose.material.icons.filled.Tune
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.PowerSettingsNew
import androidx.compose.material.icons.filled.ToggleOff
import androidx.compose.material.icons.filled.ToggleOn
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material.icons.filled.Work
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.QrCode2
import androidx.compose.material.icons.outlined.Route
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Switch
import androidx.compose.material3.SwitchDefaults
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import agrochamba.com.data.AuthManager
import agrochamba.com.data.JobPost
import agrochamba.com.data.UserProfileResponse
import agrochamba.com.ui.theme.AgroGreen
import agrochamba.com.ui.theme.AgroGreenLight
import agrochamba.com.ui.theme.AgroChambaGreen
import agrochamba.com.ui.theme.CategoryCosecha
import agrochamba.com.ui.theme.CategoryIngenieria
import agrochamba.com.ui.theme.CategoryLogistica
import agrochamba.com.ui.theme.CategoryMantenimiento
import agrochamba.com.ui.theme.LiveIndicator
import agrochamba.com.ui.theme.PremiumBadge
import agrochamba.com.utils.htmlToString
import coil.compose.AsyncImage
import coil.request.ImageRequest

/**
 * Header del Home con saludo, foto de perfil, rendimiento y notificaciones
 */
@Composable
fun HomeHeader(
    userProfile: UserProfileResponse?,
    rendimientoScore: Int? = null,
    onNotificationClick: () -> Unit,
    onProfileClick: () -> Unit,
    onRendimientoClick: () -> Unit = {},
    onFotocheckClick: () -> Unit = {},
    modifier: Modifier = Modifier
) {
    val displayName = userProfile?.displayName
        ?: userProfile?.firstName
        ?: AuthManager.userDisplayName
        ?: "Usuario"

    val profilePhotoUrl = userProfile?.profilePhotoUrl
    val isWorker = userProfile?.isEnterprise != true

    Column(modifier = modifier.padding(horizontal = 16.dp, vertical = 12.dp)) {
        // Fila superior: Avatar, nombre y notificaciones
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Foto de perfil y saludo
            Row(
                verticalAlignment = Alignment.CenterVertically,
                modifier = Modifier.weight(1f)
            ) {
                // Avatar
                Box(
                    modifier = Modifier
                        .size(52.dp)
                        .clip(CircleShape)
                        .background(MaterialTheme.colorScheme.surfaceVariant)
                        .border(2.dp, MaterialTheme.colorScheme.primary, CircleShape)
                        .clickable(onClick = onProfileClick),
                    contentAlignment = Alignment.Center
                ) {
                    if (profilePhotoUrl != null) {
                        AsyncImage(
                            model = ImageRequest.Builder(LocalContext.current)
                                .data(profilePhotoUrl)
                                .crossfade(true)
                                .build(),
                            contentDescription = "Foto de perfil",
                            modifier = Modifier
                                .fillMaxSize()
                                .clip(CircleShape),
                            contentScale = ContentScale.Crop
                        )
                    } else {
                        Icon(
                            Icons.Default.Person,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.size(28.dp)
                        )
                    }

                    // Indicador de estado online
                    Box(
                        modifier = Modifier
                            .align(Alignment.BottomEnd)
                            .size(14.dp)
                            .background(AgroGreen, CircleShape)
                            .border(2.dp, MaterialTheme.colorScheme.background, CircleShape)
                    )
                }

                Spacer(modifier = Modifier.width(12.dp))

                // Textos de saludo
                Column(modifier = Modifier.clickable(onClick = onProfileClick)) {
                    Text(
                        text = "Hola, bienvenido!",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    Text(
                        text = displayName,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onBackground
                    )
                }
            }

            // Boton de notificaciones
            Box(
                modifier = Modifier
                    .size(44.dp)
                    .clip(CircleShape)
                    .background(MaterialTheme.colorScheme.surfaceVariant)
                    .clickable(onClick = onNotificationClick),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    Icons.Default.Notifications,
                    contentDescription = "Notificaciones",
                    tint = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.size(24.dp)
                )
            }
        }

        // Solo para trabajadores: Puntajes y Fotocheck
        if (isWorker) {
            Spacer(modifier = Modifier.height(12.dp))

            // Fila de acciones rápidas: Rendimiento y Fotocheck
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                // Card de Rendimiento/Puntajes
                Card(
                    modifier = Modifier
                        .weight(1f)
                        .clickable(onClick = onRendimientoClick),
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.5f)
                    )
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Icon(
                                Icons.Default.Stars,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.primary,
                                modifier = Modifier.size(20.dp)
                            )
                            Column {
                                Text(
                                    text = "Mi Rendimiento",
                                    style = MaterialTheme.typography.labelMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                                Text(
                                    text = if (rendimientoScore != null) "$rendimientoScore pts" else "Ver puntajes",
                                    style = MaterialTheme.typography.titleSmall,
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.primary
                                )
                            }
                        }
                        Icon(
                            Icons.Default.Add,
                            contentDescription = "Ver más",
                            tint = MaterialTheme.colorScheme.primary,
                            modifier = Modifier.size(20.dp)
                        )
                    }
                }

                // Card de Fotocheck QR
                Card(
                    modifier = Modifier
                        .weight(1f)
                        .clickable(onClick = onFotocheckClick),
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.secondaryContainer.copy(alpha = 0.5f)
                    )
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Icon(
                                Icons.Default.QrCode2,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.secondary,
                                modifier = Modifier.size(20.dp)
                            )
                            Column {
                                Text(
                                    text = "Fotocheck",
                                    style = MaterialTheme.typography.labelMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                                Text(
                                    text = "Mostrar QR",
                                    style = MaterialTheme.typography.titleSmall,
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.secondary
                                )
                            }
                        }
                        Icon(
                            Icons.Default.ChevronRight,
                            contentDescription = "Abrir",
                            tint = MaterialTheme.colorScheme.secondary,
                            modifier = Modifier.size(20.dp)
                        )
                    }
                }
            }
        }
    }
}

/**
 * Barra de busqueda estilizada - Abre pantalla de búsqueda al tocar
 */
@Composable
fun HomeSearchBar(
    searchQuery: String,
    locationLabel: String? = null,
    onSearchClick: () -> Unit,
    onFilterClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        // Campo de búsqueda (clickeable, no editable)
        Card(
            modifier = Modifier
                .weight(1f)
                .height(56.dp)
                .clickable(onClick = onSearchClick),
            shape = RoundedCornerShape(16.dp),
            colors = CardDefaults.cardColors(
                containerColor = MaterialTheme.colorScheme.surfaceVariant
            ),
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
        ) {
            Row(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(horizontal = 16.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                Icon(
                    Icons.Default.Search,
                    contentDescription = "Buscar",
                    tint = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.size(24.dp)
                )
                Column(
                    modifier = Modifier.weight(1f),
                    verticalArrangement = Arrangement.Center
                ) {
                    if (searchQuery.isNotBlank() || locationLabel != null) {
                        // Mostrar búsqueda activa
                        Text(
                            text = searchQuery.ifBlank { "Buscar trabajo" },
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurface,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis
                        )
                        if (locationLabel != null) {
                            Text(
                                text = locationLabel,
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.primary,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis
                            )
                        }
                    } else {
                        // Placeholder
                        Text(
                            text = "¿Qué trabajo buscas hoy?",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }
        }

        // Boton de filtros avanzados
        Box(
            modifier = Modifier
                .size(56.dp)
                .clip(RoundedCornerShape(16.dp))
                .background(MaterialTheme.colorScheme.primary)
                .clickable(onClick = onFilterClick),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                Icons.Default.Tune,
                contentDescription = "Filtros",
                tint = MaterialTheme.colorScheme.onPrimary,
                modifier = Modifier.size(24.dp)
            )
        }
    }
}

/**
 * Barra de búsqueda editable (para la pantalla de búsqueda)
 */
@Composable
fun HomeSearchBarEditable(
    searchQuery: String,
    onSearchQueryChange: (String) -> Unit,
    onFilterClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        // Campo de búsqueda editable
        OutlinedTextField(
            value = searchQuery,
            onValueChange = onSearchQueryChange,
            placeholder = {
                Text(
                    "¿Qué trabajo buscas hoy?",
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            },
            leadingIcon = {
                Icon(
                    Icons.Default.Search,
                    contentDescription = "Buscar",
                    tint = MaterialTheme.colorScheme.onSurfaceVariant
                )
            },
            modifier = Modifier.weight(1f),
            shape = RoundedCornerShape(16.dp),
            colors = OutlinedTextFieldDefaults.colors(
                unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant,
                focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant,
                unfocusedBorderColor = Color.Transparent,
                focusedBorderColor = MaterialTheme.colorScheme.primary,
                cursorColor = MaterialTheme.colorScheme.primary,
                focusedTextColor = MaterialTheme.colorScheme.onSurface,
                unfocusedTextColor = MaterialTheme.colorScheme.onSurface
            ),
            singleLine = true
        )

        // Boton de filtros
        Box(
            modifier = Modifier
                .size(56.dp)
                .clip(RoundedCornerShape(16.dp))
                .background(MaterialTheme.colorScheme.primary)
                .clickable(onClick = onFilterClick),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                Icons.Default.Tune,
                contentDescription = "Filtros",
                tint = MaterialTheme.colorScheme.onPrimary,
                modifier = Modifier.size(24.dp)
            )
        }
    }
}

/**
 * Seccion de Avisos Operativos con carrusel
 */
@Composable
fun AvisosOperativosSection(
    avisos: List<AvisoOperativo>,
    onVerRutas: () -> Unit,
    isAdminOrEmpresa: Boolean = false,
    onCrearAviso: (TipoAviso, String) -> Unit = { _, _ -> },
    onEditarAviso: (AvisoOperativo) -> Unit = { },
    onEliminarAviso: (Int) -> Unit = { },
    modifier: Modifier = Modifier
) {
    var showCrearAvisoDialog by remember { mutableStateOf(false) }

    // Dialog para crear aviso
    if (showCrearAvisoDialog) {
        CrearAvisoDialog(
            onDismiss = { showCrearAvisoDialog = false },
            onPublicar = { tipo, mensaje ->
                onCrearAviso(tipo, mensaje)
                showCrearAvisoDialog = false
            }
        )
    }

    Column(modifier = modifier.padding(top = 24.dp)) {
        // Header de la seccion
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                text = "AVISOS OPERATIVOS",
                style = MaterialTheme.typography.labelLarge,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                letterSpacing = 1.sp
            )

            // Indicador LIVE
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(6.dp)
            ) {
                Box(
                    modifier = Modifier
                        .size(8.dp)
                        .background(LiveIndicator, CircleShape)
                )
                Text(
                    text = "LIVE",
                    style = MaterialTheme.typography.labelMedium,
                    fontWeight = FontWeight.Bold,
                    color = LiveIndicator
                )
            }
        }

        Spacer(modifier = Modifier.height(12.dp))

        // Campo para crear aviso (solo admin/empresa)
        if (isAdminOrEmpresa) {
            CrearAvisoPrompt(
                onClick = { showCrearAvisoDialog = true },
                modifier = Modifier.padding(horizontal = 16.dp)
            )
            Spacer(modifier = Modifier.height(12.dp))
        }

        // Carrusel de avisos
        LazyRow(
            contentPadding = PaddingValues(horizontal = 16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            items(avisos) { aviso ->
                AvisoCard(
                    aviso = aviso,
                    onVerRutas = onVerRutas,
                    isAdminOrEmpresa = isAdminOrEmpresa,
                    onEditar = { onEditarAviso(aviso) },
                    onEliminar = { onEliminarAviso(aviso.id) }
                )
            }
        }

        // Indicadores del carrusel
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(top = 12.dp),
            horizontalArrangement = Arrangement.Center
        ) {
            repeat(avisos.size.coerceAtMost(4)) { index ->
                Box(
                    modifier = Modifier
                        .padding(horizontal = 3.dp)
                        .size(if (index == 0) 20.dp else 8.dp, 8.dp)
                        .clip(RoundedCornerShape(4.dp))
                        .background(
                            if (index == 0) MaterialTheme.colorScheme.primary
                            else MaterialTheme.colorScheme.surfaceVariant
                        )
                )
            }
        }
    }
}

/**
 * Prompt estilo Facebook para crear aviso
 */
@Composable
fun CrearAvisoPrompt(
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .clickable(onClick = onClick)
                .padding(12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Box(
                modifier = Modifier
                    .size(40.dp)
                    .clip(CircleShape)
                    .background(MaterialTheme.colorScheme.primary.copy(alpha = 0.1f)),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    Icons.Default.Notifications,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(20.dp)
                )
            }
            Text(
                text = "¿Qué quieres comunicar hoy?",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

/**
 * Tipos de aviso disponibles
 */
enum class TipoAviso(val label: String, val icon: ImageVector) {
    ANUNCIO("Anuncio general", Icons.Default.Notifications),
    HORARIO("Horario de ingreso", Icons.Default.Schedule),
    CLIMA("Alerta de clima", Icons.Default.Warning),
    RESUMEN("Resumen de trabajos", Icons.Default.Assignment)
}

/**
 * Dialog para crear un nuevo aviso operativo
 */
@Composable
fun CrearAvisoDialog(
    onDismiss: () -> Unit,
    onPublicar: (TipoAviso, String) -> Unit
) {
    var tipoSeleccionado by remember { mutableStateOf(TipoAviso.ANUNCIO) }
    var mensaje by remember { mutableStateOf("") }
    var expanded by remember { mutableStateOf(false) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Text(
                text = "Crear Aviso Operativo",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold
            )
        },
        text = {
            Column(
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Selector de tipo
                Text(
                    text = "Tipo de aviso",
                    style = MaterialTheme.typography.labelMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                // Dropdown simulado con cards
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    TipoAviso.entries.forEach { tipo ->
                        Card(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { tipoSeleccionado = tipo },
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(
                                containerColor = if (tipoSeleccionado == tipo)
                                    MaterialTheme.colorScheme.primaryContainer
                                else
                                    MaterialTheme.colorScheme.surfaceVariant
                            )
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(12.dp),
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                Icon(
                                    tipo.icon,
                                    contentDescription = null,
                                    tint = if (tipoSeleccionado == tipo)
                                        MaterialTheme.colorScheme.primary
                                    else
                                        MaterialTheme.colorScheme.onSurfaceVariant,
                                    modifier = Modifier.size(20.dp)
                                )
                                Text(
                                    text = tipo.label,
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = if (tipoSeleccionado == tipo) FontWeight.SemiBold else FontWeight.Normal,
                                    color = if (tipoSeleccionado == tipo)
                                        MaterialTheme.colorScheme.primary
                                    else
                                        MaterialTheme.colorScheme.onSurface
                                )
                            }
                        }
                    }
                }

                Spacer(modifier = Modifier.height(8.dp))

                // Campo de mensaje
                OutlinedTextField(
                    value = mensaje,
                    onValueChange = { mensaje = it },
                    placeholder = {
                        Text(
                            text = when (tipoSeleccionado) {
                                TipoAviso.ANUNCIO -> "Escribe tu anuncio..."
                                TipoAviso.HORARIO -> "Ej: Operativos 6:00 AM, Administrativos 8:00 AM"
                                TipoAviso.CLIMA -> "Ej: Temperatura de 28°C, recuerden hidratarse"
                                TipoAviso.RESUMEN -> "Escribe el resumen de trabajos disponibles..."
                            }
                        )
                    },
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(120.dp),
                    shape = RoundedCornerShape(12.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = MaterialTheme.colorScheme.primary,
                        unfocusedBorderColor = MaterialTheme.colorScheme.outline
                    )
                )
            }
        },
        confirmButton = {
            TextButton(
                onClick = { onPublicar(tipoSeleccionado, mensaje) },
                enabled = mensaje.isNotBlank()
            ) {
                Text(
                    text = "Publicar",
                    fontWeight = FontWeight.Bold,
                    color = if (mensaje.isNotBlank())
                        MaterialTheme.colorScheme.primary
                    else
                        MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text(
                    text = "Cancelar",
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        },
        containerColor = MaterialTheme.colorScheme.surface
    )
}

/**
 * Dialog para editar un aviso operativo existente
 */
@Composable
fun EditarAvisoDialog(
    aviso: AvisoOperativo,
    onDismiss: () -> Unit,
    onGuardar: () -> Unit,
    viewModel: AvisosViewModel = androidx.lifecycle.viewmodel.compose.viewModel()
) {
    // Determinar tipo inicial basado en el aviso
    val tipoInicial = when (aviso) {
        is AvisoOperativo.Anuncio -> TipoAviso.ANUNCIO
        is AvisoOperativo.HorarioIngreso -> TipoAviso.HORARIO
        is AvisoOperativo.AlertaClima -> TipoAviso.CLIMA
        is AvisoOperativo.ResumenTrabajos -> TipoAviso.RESUMEN
    }

    // Obtener mensaje inicial
    val mensajeInicial = when (aviso) {
        is AvisoOperativo.Anuncio -> aviso.mensaje
        is AvisoOperativo.HorarioIngreso -> "Operativos: ${aviso.horaOperativos}, Administrativos: ${aviso.horaAdministrativos}"
        is AvisoOperativo.AlertaClima -> aviso.mensaje
        is AvisoOperativo.ResumenTrabajos -> aviso.contenidoCompleto
    }

    var tipoSeleccionado by remember { mutableStateOf(tipoInicial) }
    var titulo by remember { mutableStateOf(aviso.titulo) }
    var mensaje by remember { mutableStateOf(mensajeInicial) }
    var ubicacion by remember { mutableStateOf(
        if (aviso is AvisoOperativo.ResumenTrabajos) aviso.ubicacion else ""
    ) }
    var horaOperativos by remember { mutableStateOf(
        if (aviso is AvisoOperativo.HorarioIngreso) aviso.horaOperativos else "06:00 AM"
    ) }
    var horaAdministrativos by remember { mutableStateOf(
        if (aviso is AvisoOperativo.HorarioIngreso) aviso.horaAdministrativos else "08:00 AM"
    ) }

    val uiState = viewModel.uiState

    AlertDialog(
        onDismissRequest = { if (!uiState.isUpdating) onDismiss() },
        title = {
            Text(
                text = "Editar Aviso",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold
            )
        },
        text = {
            Column(
                modifier = Modifier.verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Selector de tipo
                Text(
                    text = "Tipo de aviso",
                    style = MaterialTheme.typography.labelMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    TipoAviso.entries.forEach { tipo ->
                        Card(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { tipoSeleccionado = tipo },
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(
                                containerColor = if (tipoSeleccionado == tipo)
                                    MaterialTheme.colorScheme.primaryContainer
                                else
                                    MaterialTheme.colorScheme.surfaceVariant
                            )
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(12.dp),
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                Icon(
                                    tipo.icon,
                                    contentDescription = null,
                                    tint = if (tipoSeleccionado == tipo)
                                        MaterialTheme.colorScheme.primary
                                    else
                                        MaterialTheme.colorScheme.onSurfaceVariant,
                                    modifier = Modifier.size(20.dp)
                                )
                                Text(
                                    text = tipo.label,
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = if (tipoSeleccionado == tipo) FontWeight.SemiBold else FontWeight.Normal,
                                    color = if (tipoSeleccionado == tipo)
                                        MaterialTheme.colorScheme.primary
                                    else
                                        MaterialTheme.colorScheme.onSurface
                                )
                            }
                        }
                    }
                }

                Spacer(modifier = Modifier.height(8.dp))

                // Campo de título
                OutlinedTextField(
                    value = titulo,
                    onValueChange = { titulo = it },
                    label = { Text("Título") },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    singleLine = true
                )

                // Campos específicos por tipo
                when (tipoSeleccionado) {
                    TipoAviso.RESUMEN -> {
                        OutlinedTextField(
                            value = ubicacion,
                            onValueChange = { ubicacion = it },
                            label = { Text("Ubicación") },
                            placeholder = { Text("Ej: Ica, Lima, Arequipa...") },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp),
                            singleLine = true
                        )
                    }
                    TipoAviso.HORARIO -> {
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            OutlinedTextField(
                                value = horaOperativos,
                                onValueChange = { horaOperativos = it },
                                label = { Text("Hora Operativos") },
                                modifier = Modifier.weight(1f),
                                shape = RoundedCornerShape(12.dp),
                                singleLine = true
                            )
                            OutlinedTextField(
                                value = horaAdministrativos,
                                onValueChange = { horaAdministrativos = it },
                                label = { Text("Hora Admin") },
                                modifier = Modifier.weight(1f),
                                shape = RoundedCornerShape(12.dp),
                                singleLine = true
                            )
                        }
                    }
                    else -> { }
                }

                // Campo de mensaje/contenido
                OutlinedTextField(
                    value = mensaje,
                    onValueChange = { mensaje = it },
                    label = { Text("Contenido") },
                    placeholder = {
                        Text(
                            text = when (tipoSeleccionado) {
                                TipoAviso.ANUNCIO -> "Escribe tu anuncio..."
                                TipoAviso.HORARIO -> "Información adicional (opcional)"
                                TipoAviso.CLIMA -> "Ej: Temperatura de 28°C, recuerden hidratarse"
                                TipoAviso.RESUMEN -> "Escribe el resumen de trabajos disponibles..."
                            }
                        )
                    },
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(120.dp),
                    shape = RoundedCornerShape(12.dp)
                )

                // Mostrar error si existe
                uiState.updateError?.let { error ->
                    Text(
                        text = error,
                        color = MaterialTheme.colorScheme.error,
                        style = MaterialTheme.typography.bodySmall
                    )
                }
            }
        },
        confirmButton = {
            TextButton(
                onClick = {
                    viewModel.updateAviso(
                        avisoId = aviso.id,
                        tipo = tipoSeleccionado,
                        titulo = titulo,
                        mensaje = mensaje,
                        ubicacion = ubicacion.ifBlank { null },
                        horaOperativos = horaOperativos.ifBlank { null },
                        horaAdministrativos = horaAdministrativos.ifBlank { null }
                    )
                },
                enabled = mensaje.isNotBlank() && !uiState.isUpdating
            ) {
                if (uiState.isUpdating) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(16.dp),
                        strokeWidth = 2.dp
                    )
                } else {
                    Text(
                        text = "Guardar",
                        fontWeight = FontWeight.Bold,
                        color = if (mensaje.isNotBlank())
                            MaterialTheme.colorScheme.primary
                        else
                            MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
        },
        dismissButton = {
            TextButton(
                onClick = onDismiss,
                enabled = !uiState.isUpdating
            ) {
                Text(
                    text = "Cancelar",
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        },
        containerColor = MaterialTheme.colorScheme.surface
    )

    // Cerrar el dialog cuando se actualice con éxito
    if (uiState.updateSuccess) {
        viewModel.clearUpdateState()
        onGuardar()
    }
}

/**
 * Card de Aviso Operativo - Altura fija para consistencia
 */
@Composable
fun AvisoCard(
    aviso: AvisoOperativo,
    onVerRutas: () -> Unit,
    isAdminOrEmpresa: Boolean = false,
    onEditar: () -> Unit = {},
    onEliminar: () -> Unit = {}
) {
    var showDetailDialog by remember { mutableStateOf(false) }
    var showEditDialog by remember { mutableStateOf(false) }
    var showDeleteConfirm by remember { mutableStateOf(false) }

    // Dialog para ResumenTrabajos
    if (aviso is AvisoOperativo.ResumenTrabajos && showDetailDialog) {
        ResumenTrabajosDialog(
            aviso = aviso,
            onDismiss = { showDetailDialog = false }
        )
    }

    // Dialog para AlertaClima y Anuncio
    if ((aviso is AvisoOperativo.AlertaClima || aviso is AvisoOperativo.Anuncio) && showDetailDialog) {
        AvisoDetailDialog(
            aviso = aviso,
            onDismiss = { showDetailDialog = false }
        )
    }

    // Dialog de confirmación para eliminar
    if (showDeleteConfirm) {
        AlertDialog(
            onDismissRequest = { showDeleteConfirm = false },
            title = { Text("Eliminar aviso") },
            text = { Text("¿Estás seguro de que deseas eliminar este aviso?") },
            confirmButton = {
                TextButton(
                    onClick = {
                        showDeleteConfirm = false
                        onEliminar()
                    }
                ) {
                    Text("Eliminar", color = MaterialTheme.colorScheme.error)
                }
            },
            dismissButton = {
                TextButton(onClick = { showDeleteConfirm = false }) {
                    Text("Cancelar")
                }
            }
        )
    }

    // Dialog de edición
    if (showEditDialog && aviso.id > 0) {
        EditarAvisoDialog(
            aviso = aviso,
            onDismiss = { showEditDialog = false },
            onGuardar = {
                showEditDialog = false
                onEditar()
            }
        )
    }

    Card(
        modifier = Modifier
            .width(280.dp)
            .height(180.dp), // Altura fija para todas las cards
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(16.dp),
            verticalArrangement = Arrangement.SpaceBetween
        ) {
            // Header con icono y botones de edición
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier.weight(1f)
                ) {
                    Box(
                        modifier = Modifier
                            .size(32.dp)
                            .clip(CircleShape)
                            .background(aviso.accentColor.copy(alpha = 0.15f)),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            aviso.icon,
                            contentDescription = null,
                            tint = aviso.accentColor,
                            modifier = Modifier.size(18.dp)
                        )
                    }
                    Text(
                        text = aviso.titulo,
                        style = MaterialTheme.typography.titleSmall,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                }

                // Botones de edición (solo para admin/empresa y avisos con ID válido)
                if (isAdminOrEmpresa && aviso.id > 0) {
                    Row(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                        Box(
                            modifier = Modifier
                                .size(24.dp)
                                .clip(CircleShape)
                                .background(MaterialTheme.colorScheme.primaryContainer)
                                .clickable { showEditDialog = true },
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(
                                Icons.Default.Build,
                                contentDescription = "Editar",
                                tint = MaterialTheme.colorScheme.primary,
                                modifier = Modifier.size(14.dp)
                            )
                        }
                        Box(
                            modifier = Modifier
                                .size(24.dp)
                                .clip(CircleShape)
                                .background(MaterialTheme.colorScheme.errorContainer)
                                .clickable { showDeleteConfirm = true },
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(
                                Icons.Default.Warning,
                                contentDescription = "Eliminar",
                                tint = MaterialTheme.colorScheme.error,
                                modifier = Modifier.size(14.dp)
                            )
                        }
                    }
                }
            }

            // Contenido del aviso (área central flexible)
            Box(modifier = Modifier.weight(1f).padding(vertical = 8.dp)) {
                when (aviso) {
                    is AvisoOperativo.HorarioIngreso -> {
                        HorarioIngresoContent(aviso)
                    }
                    is AvisoOperativo.AlertaClima -> {
                        AlertaClimaContent(aviso)
                    }
                    is AvisoOperativo.Anuncio -> {
                        AnuncioContent(aviso)
                    }
                    is AvisoOperativo.ResumenTrabajos -> {
                        ResumenTrabajosContentCompact(aviso)
                    }
                }
            }

            // Botón de acción según tipo de aviso
            when (aviso) {
                is AvisoOperativo.HorarioIngreso -> {
                    AvisoActionButton(
                        text = "Ver Rutas de Hoy",
                        icon = Icons.Outlined.Route,
                        onClick = onVerRutas
                    )
                }
                is AvisoOperativo.ResumenTrabajos -> {
                    AvisoActionButton(
                        text = "Leer más",
                        icon = Icons.Default.ChevronRight,
                        onClick = { showDetailDialog = true },
                        isPrimary = true
                    )
                }
                is AvisoOperativo.AlertaClima, is AvisoOperativo.Anuncio -> {
                    AvisoActionButton(
                        text = "Leer más",
                        icon = Icons.Default.ChevronRight,
                        onClick = { showDetailDialog = true }
                    )
                }
            }
        }
    }
}

/**
 * Botón de acción reutilizable para avisos
 */
@Composable
private fun AvisoActionButton(
    text: String,
    icon: ImageVector,
    onClick: () -> Unit,
    isPrimary: Boolean = false
) {
    Box(
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(10.dp))
            .background(
                if (isPrimary) MaterialTheme.colorScheme.primary
                else MaterialTheme.colorScheme.surfaceVariant
            )
            .clickable(onClick = onClick)
            .padding(10.dp),
        contentAlignment = Alignment.Center
    ) {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            Icon(
                icon,
                contentDescription = null,
                tint = if (isPrimary) MaterialTheme.colorScheme.onPrimary
                       else MaterialTheme.colorScheme.primary,
                modifier = Modifier.size(16.dp)
            )
            Text(
                text = text,
                style = MaterialTheme.typography.labelMedium,
                fontWeight = FontWeight.Medium,
                color = if (isPrimary) MaterialTheme.colorScheme.onPrimary
                        else MaterialTheme.colorScheme.primary
            )
        }
    }
}

@Composable
private fun HorarioIngresoContent(aviso: AvisoOperativo.HorarioIngreso) {
    Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Text(
                "Operativos",
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                style = MaterialTheme.typography.bodySmall
            )
            Text(
                aviso.horaOperativos,
                color = MaterialTheme.colorScheme.primary,
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = FontWeight.Bold
            )
        }
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Text(
                "Administrativos",
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                style = MaterialTheme.typography.bodySmall
            )
            Text(
                aviso.horaAdministrativos,
                color = MaterialTheme.colorScheme.primary,
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = FontWeight.Bold
            )
        }
    }
}

@Composable
private fun AlertaClimaContent(aviso: AvisoOperativo.AlertaClima) {
    Text(
        text = aviso.mensaje,
        style = MaterialTheme.typography.bodySmall,
        color = MaterialTheme.colorScheme.onSurfaceVariant,
        maxLines = 4,
        overflow = TextOverflow.Ellipsis,
        lineHeight = 18.sp
    )
}

@Composable
private fun AnuncioContent(aviso: AvisoOperativo.Anuncio) {
    Text(
        text = aviso.mensaje,
        style = MaterialTheme.typography.bodySmall,
        color = MaterialTheme.colorScheme.onSurfaceVariant,
        maxLines = 4,
        overflow = TextOverflow.Ellipsis,
        lineHeight = 18.sp
    )
}

@Composable
private fun ResumenTrabajosContentCompact(aviso: AvisoOperativo.ResumenTrabajos) {
    Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
        // Ubicacion badge
        Box(
            modifier = Modifier
                .clip(RoundedCornerShape(6.dp))
                .background(MaterialTheme.colorScheme.primary.copy(alpha = 0.1f))
                .padding(horizontal = 6.dp, vertical = 2.dp)
        ) {
            Text(
                text = "📍 ${aviso.ubicacion}",
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.primary,
                fontWeight = FontWeight.Medium
            )
        }

        // Preview del contenido
        Text(
            text = aviso.preview,
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            maxLines = 2,
            overflow = TextOverflow.Ellipsis,
            lineHeight = 16.sp
        )
    }
}

/**
 * Dialog para mostrar el contenido completo del Resumen de Trabajos
 */
@Composable
fun ResumenTrabajosDialog(
    aviso: AvisoOperativo.ResumenTrabajos,
    onDismiss: () -> Unit
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Icon(
                    aviso.icon,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(24.dp)
                )
                Column {
                    Text(
                        text = aviso.titulo,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )
                    Text(
                        text = "📍 ${aviso.ubicacion}",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.primary
                    )
                }
            }
        },
        text = {
            Column(
                modifier = Modifier
                    .verticalScroll(rememberScrollState())
            ) {
                Text(
                    text = aviso.contenidoCompleto,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    lineHeight = 24.sp
                )
            }
        },
        confirmButton = {
            TextButton(onClick = onDismiss) {
                Text(
                    text = "Cerrar",
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary
                )
            }
        },
        containerColor = MaterialTheme.colorScheme.surface,
        titleContentColor = MaterialTheme.colorScheme.onSurface,
        textContentColor = MaterialTheme.colorScheme.onSurfaceVariant
    )
}

/**
 * Dialog genérico para mostrar el contenido completo de AlertaClima y Anuncio
 */
@Composable
fun AvisoDetailDialog(
    aviso: AvisoOperativo,
    onDismiss: () -> Unit
) {
    val mensaje = when (aviso) {
        is AvisoOperativo.AlertaClima -> aviso.mensaje
        is AvisoOperativo.Anuncio -> aviso.mensaje
        else -> ""
    }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Icon(
                    aviso.icon,
                    contentDescription = null,
                    tint = aviso.accentColor,
                    modifier = Modifier.size(24.dp)
                )
                Text(
                    text = aviso.titulo,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
            }
        },
        text = {
            Column(
                modifier = Modifier
                    .verticalScroll(rememberScrollState())
            ) {
                Text(
                    text = mensaje,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    lineHeight = 24.sp
                )
            }
        },
        confirmButton = {
            TextButton(onClick = onDismiss) {
                Text(
                    text = "Cerrar",
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary
                )
            }
        },
        containerColor = MaterialTheme.colorScheme.surface,
        titleContentColor = MaterialTheme.colorScheme.onSurface,
        textContentColor = MaterialTheme.colorScheme.onSurfaceVariant
    )
}

/**
 * Seccion de Categorias
 */
@Composable
fun CategoriasSection(
    categorias: List<CategoriaJob>,
    onCategoriaClick: (CategoriaJob) -> Unit,
    onVerTodas: () -> Unit,
    modifier: Modifier = Modifier
) {
    Column(modifier = modifier.padding(top = 24.dp)) {
        // Header
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                text = "Categorias",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onBackground
            )
            Text(
                text = "VER TODAS",
                style = MaterialTheme.typography.labelMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.primary,
                modifier = Modifier.clickable(onClick = onVerTodas)
            )
        }

        Spacer(modifier = Modifier.height(16.dp))

        // Grid de categorias
        LazyRow(
            contentPadding = PaddingValues(horizontal = 16.dp),
            horizontalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            items(categorias) { categoria ->
                CategoriaItem(
                    categoria = categoria,
                    onClick = { onCategoriaClick(categoria) }
                )
            }
        }
    }
}

@Composable
fun CategoriaItem(
    categoria: CategoriaJob,
    onClick: () -> Unit
) {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        modifier = Modifier.clickable(onClick = onClick)
    ) {
        Box(
            modifier = Modifier
                .size(72.dp)
                .clip(RoundedCornerShape(16.dp))
                .background(categoria.backgroundColor),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                categoria.icon,
                contentDescription = categoria.nombre,
                tint = categoria.iconColor,
                modifier = Modifier.size(32.dp)
            )
        }

        Spacer(modifier = Modifier.height(8.dp))

        Text(
            text = categoria.nombre,
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            fontWeight = FontWeight.Medium
        )
    }
}

/**
 * Seccion de Empleos Destacados
 */
@Composable
fun EmpleosDestacadosSection(
    empleos: List<JobPost>,
    onEmpleoClick: (JobPost) -> Unit,
    onPostular: (JobPost) -> Unit,
    modifier: Modifier = Modifier
) {
    Column(modifier = modifier.padding(top = 24.dp)) {
        // Header
        Text(
            text = "Empleos Destacados",
            style = MaterialTheme.typography.titleLarge,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onBackground,
            modifier = Modifier.padding(horizontal = 16.dp)
        )

        Spacer(modifier = Modifier.height(16.dp))

        // Carrusel de empleos
        LazyRow(
            contentPadding = PaddingValues(horizontal = 16.dp),
            horizontalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            items(empleos) { empleo ->
                EmpleoDestacadoCard(
                    empleo = empleo,
                    onClick = { onEmpleoClick(empleo) },
                    onPostular = { onPostular(empleo) }
                )
            }
        }
    }
}

@Composable
fun EmpleoDestacadoCard(
    empleo: JobPost,
    onClick: () -> Unit,
    onPostular: () -> Unit
) {
    val context = LocalContext.current
    val imageUrl = empleo.embedded?.featuredMedia?.firstOrNull()?.source_url
        ?: empleo.featuredImageUrl

    val terms = empleo.embedded?.terms?.flatten() ?: emptyList()
    val companyName = terms.find { it.taxonomy == "empresa" }?.name ?: "Empresa"
    val locationName = empleo.meta?.ubicacionCompleta?.departamento
        ?: empleo.ubicacionDisplay?.departamento
        ?: terms.find { it.taxonomy == "ubicacion" }?.name?.split(",")?.lastOrNull()?.trim()
        ?: ""

    val salario = when {
        !empleo.meta?.salarioMin.isNullOrBlank() && !empleo.meta?.salarioMax.isNullOrBlank() ->
            "$${empleo.meta?.salarioMin}"
        !empleo.meta?.salarioMin.isNullOrBlank() -> "$${empleo.meta?.salarioMin}"
        else -> null
    }

    Card(
        modifier = Modifier
            .width(300.dp)
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent)
    ) {
        Box(modifier = Modifier.height(380.dp)) {
            // Imagen de fondo
            if (imageUrl != null) {
                AsyncImage(
                    model = ImageRequest.Builder(context)
                        .data(imageUrl)
                        .crossfade(true)
                        .build(),
                    contentDescription = null,
                    modifier = Modifier.fillMaxSize(),
                    contentScale = ContentScale.Crop
                )
            } else {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .background(
                            Brush.verticalGradient(
                                colors = listOf(
                                    MaterialTheme.colorScheme.primary.copy(alpha = 0.3f),
                                    MaterialTheme.colorScheme.surfaceVariant
                                )
                            )
                        )
                )
            }

            // Gradiente oscuro en la parte inferior
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .background(
                        Brush.verticalGradient(
                            colors = listOf(
                                Color.Transparent,
                                Color.Black.copy(alpha = 0.3f),
                                Color.Black.copy(alpha = 0.8f)
                            ),
                            startY = 0f,
                            endY = 1000f
                        )
                    )
            )

            // Badge PREMIUM
            Box(
                modifier = Modifier
                    .align(Alignment.TopStart)
                    .padding(12.dp)
                    .clip(RoundedCornerShape(8.dp))
                    .background(PremiumBadge)
                    .padding(horizontal = 12.dp, vertical = 6.dp)
            ) {
                Text(
                    text = "PREMIUM",
                    style = MaterialTheme.typography.labelSmall,
                    fontWeight = FontWeight.Bold,
                    color = Color.White,
                    letterSpacing = 1.sp
                )
            }

            // Contenido inferior
            Column(
                modifier = Modifier
                    .align(Alignment.BottomStart)
                    .padding(16.dp)
            ) {
                // Titulo del empleo
                Text(
                    text = empleo.title?.rendered?.htmlToString() ?: "Sin titulo",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold,
                    color = Color.White,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis
                )

                Spacer(modifier = Modifier.height(4.dp))

                // Empresa y ubicacion
                Text(
                    text = buildString {
                        append(companyName.htmlToString())
                        if (locationName.isNotBlank()) {
                            append(" - ")
                            append(locationName)
                        }
                    },
                    style = MaterialTheme.typography.bodyMedium,
                    color = Color.White.copy(alpha = 0.8f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )

                Spacer(modifier = Modifier.height(16.dp))

                // Salario y boton postular
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    if (salario != null) {
                        Column {
                            Text(
                                text = salario,
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Bold,
                                color = AgroGreenLight
                            )
                            Text(
                                text = "/mes",
                                style = MaterialTheme.typography.bodySmall,
                                color = Color.White.copy(alpha = 0.7f)
                            )
                        }
                    } else {
                        Spacer(modifier = Modifier.width(1.dp))
                    }

                    // Boton Postular
                    Box(
                        modifier = Modifier
                            .clip(RoundedCornerShape(24.dp))
                            .background(Color.White)
                            .clickable(onClick = onPostular)
                            .padding(horizontal = 24.dp, vertical = 12.dp)
                    ) {
                        Text(
                            text = "Postular",
                            style = MaterialTheme.typography.labelLarge,
                            fontWeight = FontWeight.Bold,
                            color = Color.Black
                        )
                    }
                }
            }
        }
    }
}

// Colores de acento para cada tipo de aviso
val AvisoColorHorario = Color(0xFF2196F3) // Azul
val AvisoColorClima = Color(0xFFFF9800) // Naranja
val AvisoColorAnuncio = Color(0xFF9C27B0) // Púrpura
val AvisoColorResumen = Color(0xFF4CAF50) // Verde

// Data classes para los avisos
sealed class AvisoOperativo(
    open val id: Int,
    open val titulo: String,
    open val icon: ImageVector,
    open val accentColor: Color
) {
    data class HorarioIngreso(
        override val id: Int = 0,
        override val titulo: String = "HORARIOS DE INGRESO",
        override val icon: ImageVector = Icons.Default.Schedule,
        override val accentColor: Color = AvisoColorHorario,
        val horaOperativos: String = "06:00 AM",
        val horaAdministrativos: String = "08:00 AM"
    ) : AvisoOperativo(id, titulo, icon, accentColor)

    data class AlertaClima(
        override val id: Int = 0,
        override val titulo: String = "ALERTA CLIMA",
        override val icon: ImageVector = Icons.Default.Warning,
        override val accentColor: Color = AvisoColorClima,
        val mensaje: String
    ) : AvisoOperativo(id, titulo, icon, accentColor)

    data class Anuncio(
        override val id: Int = 0,
        override val titulo: String,
        override val icon: ImageVector = Icons.Default.Notifications,
        override val accentColor: Color = AvisoColorAnuncio,
        val mensaje: String
    ) : AvisoOperativo(id, titulo, icon, accentColor)

    data class ResumenTrabajos(
        override val id: Int = 0,
        override val titulo: String = "RESUMEN DE TRABAJOS",
        override val icon: ImageVector = Icons.Default.Assignment,
        override val accentColor: Color = AvisoColorResumen,
        val ubicacion: String,
        val preview: String,
        val contenidoCompleto: String
    ) : AvisoOperativo(id, titulo, icon, accentColor)
}

// Data class para categorias
data class CategoriaJob(
    val id: String,
    val nombre: String,
    val icon: ImageVector,
    val backgroundColor: Color,
    val iconColor: Color = Color.White
)

// Categorias por defecto
val defaultCategorias = listOf(
    CategoriaJob(
        id = "cosecha",
        nombre = "Cosecha",
        icon = Icons.Default.Agriculture,
        backgroundColor = CategoryCosecha,
        iconColor = Color.White
    ),
    CategoriaJob(
        id = "logistica",
        nombre = "Logistica",
        icon = Icons.Default.LocalShipping,
        backgroundColor = CategoryLogistica,
        iconColor = Color.White
    ),
    CategoriaJob(
        id = "ingenieria",
        nombre = "Ingenieria",
        icon = Icons.Default.Engineering,
        backgroundColor = CategoryIngenieria,
        iconColor = Color.White
    ),
    CategoriaJob(
        id = "mantenimiento",
        nombre = "Mantenimiento",
        icon = Icons.Default.Build,
        backgroundColor = CategoryMantenimiento,
        iconColor = Color.White
    )
)

// Avisos de ejemplo (fallback cuando no hay conexión)
val defaultAvisos = listOf(
    AvisoOperativo.ResumenTrabajos(
        ubicacion = "Ica",
        preview = "• Beta recibirá personal para siembra de arándanos.\n• Athos requiere personal con experiencia para cosecha de granada...",
        contenidoCompleto = """📋 Resumen de trabajos para mañana en Ica

• Beta recibirá personal para siembra de arándanos.
• Athos requiere personal con experiencia para cosecha de granada.
• Agrícola Don Ricardo busca operarios para packing de uva.
• Camposol tiene vacantes para mantenimiento de sistemas de riego.
• Virú necesita supervisores de campo para espárrago.

⚠️ Importante:
Es fundamental asistir desde temprano para asegurar tu lugar. ¡No dejes pasar esta oportunidad!

📱 Descarga la app para recibir notificaciones de nuevas ofertas."""
    ),
    AvisoOperativo.HorarioIngreso(),
    AvisoOperativo.AlertaClima(
        mensaje = "La temperatura de hoy sera de 28C. Se recomienda hidratarse constantemente."
    )
)

/**
 * Convierte la respuesta del API a los modelos de UI
 */
fun agrochamba.com.data.AvisoOperativoResponse.toUiModel(): AvisoOperativo {
    return when (tipo) {
        "resumen_trabajos" -> AvisoOperativo.ResumenTrabajos(
            id = id,
            titulo = titulo.ifBlank { "RESUMEN DE TRABAJOS" },
            ubicacion = ubicacion ?: "Perú",
            preview = preview ?: contenido?.take(100) ?: "",
            contenidoCompleto = contenido ?: preview ?: ""
        )
        "horario_ingreso" -> AvisoOperativo.HorarioIngreso(
            id = id,
            titulo = titulo.ifBlank { "HORARIOS DE INGRESO" },
            horaOperativos = horaOperativos ?: "06:00 AM",
            horaAdministrativos = horaAdministrativos ?: "08:00 AM"
        )
        "alerta_clima" -> AvisoOperativo.AlertaClima(
            id = id,
            titulo = titulo.ifBlank { "ALERTA CLIMA" },
            mensaje = contenido ?: preview ?: "Sin información de clima"
        )
        else -> AvisoOperativo.Anuncio(
            id = id,
            titulo = titulo.ifBlank { "ANUNCIO" },
            mensaje = contenido ?: preview ?: ""
        )
    }
}

// ==========================================
// DISPONIBILIDAD DEL TRABAJADOR (ESTILO UBER)
// ==========================================

/**
 * Banner de disponibilidad para trabajadores - Estilo Uber
 * Muestra si el trabajador está visible para empresas y permite toggle
 */
@Composable
fun DisponibilidadBanner(
    disponibleParaTrabajo: Boolean,
    tieneContratoActivo: Boolean,
    visibleParaEmpresas: Boolean,
    ubicacion: String?,
    isLoading: Boolean,
    onToggleDisponibilidad: () -> Unit,
    modifier: Modifier = Modifier
) {
    val backgroundColor = when {
        tieneContratoActivo -> MaterialTheme.colorScheme.secondaryContainer
        visibleParaEmpresas -> AgroGreen.copy(alpha = 0.15f)
        else -> MaterialTheme.colorScheme.surfaceVariant
    }

    val statusColor = when {
        tieneContratoActivo -> MaterialTheme.colorScheme.secondary
        visibleParaEmpresas -> AgroGreen
        else -> MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.5f)
    }

    val statusText = when {
        tieneContratoActivo -> "Contrato activo"
        visibleParaEmpresas -> "Disponible para ofertas"
        else -> "No visible para empresas"
    }

    val statusDescription = when {
        tieneContratoActivo -> "Las empresas no pueden contactarte mientras tengas contrato activo"
        visibleParaEmpresas -> "Las empresas pueden ver tu perfil y contactarte"
        else -> "Activa tu disponibilidad para que las empresas te encuentren"
    }

    Card(
        modifier = modifier
            .fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = backgroundColor),
        shape = RoundedCornerShape(16.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Indicador de estado
            Box(
                modifier = Modifier
                    .size(48.dp)
                    .clip(CircleShape)
                    .background(statusColor.copy(alpha = 0.2f)),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = when {
                        tieneContratoActivo -> Icons.Default.Work
                        visibleParaEmpresas -> Icons.Default.PowerSettingsNew
                        else -> Icons.Default.PowerSettingsNew
                    },
                    contentDescription = null,
                    tint = statusColor,
                    modifier = Modifier.size(24.dp)
                )
            }

            Spacer(modifier = Modifier.width(12.dp))

            // Texto de estado
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = statusText,
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.SemiBold,
                    color = statusColor
                )
                Text(
                    text = statusDescription,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 2
                )
                if (ubicacion != null && visibleParaEmpresas) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier.padding(top = 4.dp)
                    ) {
                        Icon(
                            Icons.Default.LocationOn,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.size(14.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(
                            text = ubicacion,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }

            // Toggle (solo si no tiene contrato activo)
            if (!tieneContratoActivo) {
                if (isLoading) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(24.dp),
                        strokeWidth = 2.dp,
                        color = AgroGreen
                    )
                } else {
                    Switch(
                        checked = disponibleParaTrabajo,
                        onCheckedChange = { onToggleDisponibilidad() },
                        colors = SwitchDefaults.colors(
                            checkedThumbColor = Color.White,
                            checkedTrackColor = AgroGreen,
                            uncheckedThumbColor = Color.White,
                            uncheckedTrackColor = MaterialTheme.colorScheme.outline
                        )
                    )
                }
            }
        }
    }
}
