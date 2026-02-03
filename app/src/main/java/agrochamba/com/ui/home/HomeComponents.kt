package agrochamba.com.ui.home

import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.tween
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
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
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Agriculture
import androidx.compose.material.icons.filled.Build
import androidx.compose.material.icons.filled.Engineering
import androidx.compose.material.icons.filled.LocalShipping
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Tune
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material.icons.outlined.Route
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
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
import agrochamba.com.ui.theme.CategoryCosecha
import agrochamba.com.ui.theme.CategoryIngenieria
import agrochamba.com.ui.theme.CategoryLogistica
import agrochamba.com.ui.theme.CategoryMantenimiento
import agrochamba.com.ui.theme.DarkBackground
import agrochamba.com.ui.theme.DarkCard
import agrochamba.com.ui.theme.DarkCardElevated
import agrochamba.com.ui.theme.DarkSurface
import agrochamba.com.ui.theme.LiveIndicator
import agrochamba.com.ui.theme.PremiumBadge
import agrochamba.com.ui.theme.TextMuted
import agrochamba.com.ui.theme.TextPrimary
import agrochamba.com.ui.theme.TextSecondary
import agrochamba.com.utils.htmlToString
import coil.compose.AsyncImage
import coil.request.ImageRequest

/**
 * Header del Home con saludo, foto de perfil y notificaciones
 */
@Composable
fun HomeHeader(
    userProfile: UserProfileResponse?,
    onNotificationClick: () -> Unit,
    onProfileClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    val displayName = userProfile?.displayName
        ?: userProfile?.firstName
        ?: AuthManager.userDisplayName
        ?: "Usuario"

    val profilePhotoUrl = userProfile?.profilePhotoUrl

    Row(
        modifier = modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 12.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        // Foto de perfil y saludo
        Row(
            verticalAlignment = Alignment.CenterVertically,
            modifier = Modifier.clickable(onClick = onProfileClick)
        ) {
            // Avatar
            Box(
                modifier = Modifier
                    .size(52.dp)
                    .clip(CircleShape)
                    .background(DarkCardElevated)
                    .border(2.dp, AgroGreen, CircleShape),
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
                        tint = TextSecondary,
                        modifier = Modifier.size(28.dp)
                    )
                }

                // Indicador de estado online
                Box(
                    modifier = Modifier
                        .align(Alignment.BottomEnd)
                        .size(14.dp)
                        .background(AgroGreen, CircleShape)
                        .border(2.dp, DarkBackground, CircleShape)
                )
            }

            Spacer(modifier = Modifier.width(12.dp))

            // Textos de saludo
            Column {
                Text(
                    text = "Hola, bienvenido!",
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary
                )
                Text(
                    text = displayName,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary
                )
            }
        }

        // Botón de notificaciones
        Box(
            modifier = Modifier
                .size(44.dp)
                .clip(CircleShape)
                .background(DarkCardElevated)
                .clickable(onClick = onNotificationClick),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                Icons.Default.Notifications,
                contentDescription = "Notificaciones",
                tint = TextSecondary,
                modifier = Modifier.size(24.dp)
            )
            // Badge de notificaciones (opcional)
            // Box(modifier = Modifier.align(Alignment.TopEnd).size(8.dp).background(LiveIndicator, CircleShape))
        }
    }
}

/**
 * Barra de búsqueda estilizada
 */
@Composable
fun HomeSearchBar(
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
        // Campo de búsqueda
        OutlinedTextField(
            value = searchQuery,
            onValueChange = onSearchQueryChange,
            placeholder = {
                Text(
                    "Que trabajo buscas hoy?",
                    color = TextMuted
                )
            },
            leadingIcon = {
                Icon(
                    Icons.Default.Search,
                    contentDescription = "Buscar",
                    tint = TextMuted
                )
            },
            modifier = Modifier.weight(1f),
            shape = RoundedCornerShape(16.dp),
            colors = OutlinedTextFieldDefaults.colors(
                unfocusedContainerColor = DarkCard,
                focusedContainerColor = DarkCard,
                unfocusedBorderColor = Color.Transparent,
                focusedBorderColor = AgroGreen,
                cursorColor = AgroGreen,
                focusedTextColor = TextPrimary,
                unfocusedTextColor = TextPrimary
            ),
            singleLine = true
        )

        // Botón de filtros
        Box(
            modifier = Modifier
                .size(56.dp)
                .clip(RoundedCornerShape(16.dp))
                .background(AgroGreen)
                .clickable(onClick = onFilterClick),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                Icons.Default.Tune,
                contentDescription = "Filtros",
                tint = Color.White,
                modifier = Modifier.size(24.dp)
            )
        }
    }
}

/**
 * Sección de Avisos Operativos con carrusel
 */
@Composable
fun AvisosOperativosSection(
    avisos: List<AvisoOperativo>,
    onVerRutas: () -> Unit,
    modifier: Modifier = Modifier
) {
    Column(modifier = modifier.padding(top = 24.dp)) {
        // Header de la sección
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
                color = TextSecondary,
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

        // Carrusel de avisos
        LazyRow(
            contentPadding = PaddingValues(horizontal = 16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            items(avisos) { aviso ->
                AvisoCard(aviso = aviso, onVerRutas = onVerRutas)
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
                        .background(if (index == 0) AgroGreen else DarkCardElevated)
                )
            }
        }
    }
}

/**
 * Card de Aviso Operativo
 */
@Composable
fun AvisoCard(
    aviso: AvisoOperativo,
    onVerRutas: () -> Unit
) {
    Card(
        modifier = Modifier.width(300.dp),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = DarkCard)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            // Header con icono
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Box(
                    modifier = Modifier
                        .size(32.dp)
                        .clip(CircleShape)
                        .background(AgroGreen.copy(alpha = 0.2f)),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        aviso.icon,
                        contentDescription = null,
                        tint = AgroGreen,
                        modifier = Modifier.size(18.dp)
                    )
                }
                Text(
                    text = aviso.titulo,
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.Bold,
                    color = TextPrimary
                )
            }

            Spacer(modifier = Modifier.height(16.dp))

            // Contenido del aviso
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
            }

            Spacer(modifier = Modifier.height(16.dp))

            // Botón de acción
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .clip(RoundedCornerShape(12.dp))
                    .background(DarkCardElevated)
                    .clickable(onClick = onVerRutas)
                    .padding(12.dp),
                contentAlignment = Alignment.Center
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Icon(
                        Icons.Outlined.Route,
                        contentDescription = null,
                        tint = AgroGreen,
                        modifier = Modifier.size(18.dp)
                    )
                    Text(
                        text = "Ver Rutas de Hoy",
                        style = MaterialTheme.typography.labelLarge,
                        fontWeight = FontWeight.Medium,
                        color = AgroGreen
                    )
                }
            }
        }
    }
}

@Composable
private fun HorarioIngresoContent(aviso: AvisoOperativo.HorarioIngreso) {
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Text("Operativos", color = TextSecondary, style = MaterialTheme.typography.bodyMedium)
            Text(aviso.horaOperativos, color = AgroGreen, fontWeight = FontWeight.Bold)
        }
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Text("Administrativos", color = TextSecondary, style = MaterialTheme.typography.bodyMedium)
            Text(aviso.horaAdministrativos, color = AgroGreen, fontWeight = FontWeight.Bold)
        }
    }
}

@Composable
private fun AlertaClimaContent(aviso: AvisoOperativo.AlertaClima) {
    Text(
        text = aviso.mensaje,
        style = MaterialTheme.typography.bodyMedium,
        color = TextSecondary
    )
}

@Composable
private fun AnuncioContent(aviso: AvisoOperativo.Anuncio) {
    Text(
        text = aviso.mensaje,
        style = MaterialTheme.typography.bodyMedium,
        color = TextSecondary,
        maxLines = 3,
        overflow = TextOverflow.Ellipsis
    )
}

/**
 * Sección de Categorías
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
                color = TextPrimary
            )
            Text(
                text = "VER TODAS",
                style = MaterialTheme.typography.labelMedium,
                fontWeight = FontWeight.Bold,
                color = AgroGreen,
                modifier = Modifier.clickable(onClick = onVerTodas)
            )
        }

        Spacer(modifier = Modifier.height(16.dp))

        // Grid de categorías
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
            color = TextSecondary,
            fontWeight = FontWeight.Medium
        )
    }
}

/**
 * Sección de Empleos Destacados
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
            color = TextPrimary,
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
                                    AgroGreen.copy(alpha = 0.3f),
                                    DarkCard
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
                // Título del empleo
                Text(
                    text = empleo.title?.rendered?.htmlToString() ?: "Sin título",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold,
                    color = Color.White,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis
                )

                Spacer(modifier = Modifier.height(4.dp))

                // Empresa y ubicación
                Text(
                    text = buildString {
                        append(companyName.htmlToString())
                        if (locationName.isNotBlank()) {
                            append(" • ")
                            append(locationName)
                        }
                    },
                    style = MaterialTheme.typography.bodyMedium,
                    color = Color.White.copy(alpha = 0.8f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )

                Spacer(modifier = Modifier.height(16.dp))

                // Salario y botón postular
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

                    // Botón Postular
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

// Data classes para los avisos
sealed class AvisoOperativo(
    open val titulo: String,
    open val icon: ImageVector
) {
    data class HorarioIngreso(
        override val titulo: String = "HORARIOS DE INGRESO",
        override val icon: ImageVector = Icons.Default.Schedule,
        val horaOperativos: String = "06:00 AM",
        val horaAdministrativos: String = "08:00 AM"
    ) : AvisoOperativo(titulo, icon)

    data class AlertaClima(
        override val titulo: String = "ALERTA CLIMA",
        override val icon: ImageVector = Icons.Default.Warning,
        val mensaje: String
    ) : AvisoOperativo(titulo, icon)

    data class Anuncio(
        override val titulo: String,
        override val icon: ImageVector = Icons.Default.Notifications,
        val mensaje: String
    ) : AvisoOperativo(titulo, icon)
}

// Data class para categorías
data class CategoriaJob(
    val id: String,
    val nombre: String,
    val icon: ImageVector,
    val backgroundColor: Color,
    val iconColor: Color = Color.White
)

// Categorías por defecto
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

// Avisos de ejemplo
val defaultAvisos = listOf(
    AvisoOperativo.HorarioIngreso(),
    AvisoOperativo.AlertaClima(
        mensaje = "La temperatura de hoy será de 28°C. Se recomienda hidratarse constantemente."
    )
)
