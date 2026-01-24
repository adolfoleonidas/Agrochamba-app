package agrochamba.com.ui.jobs

import android.content.Intent
import android.net.Uri
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import androidx.navigation.NavController
import agrochamba.com.R
import agrochamba.com.data.*
import agrochamba.com.ui.common.FormattedText
import agrochamba.com.ui.common.LocationDetailView
import agrochamba.com.utils.htmlToString
import coil.compose.AsyncImage
import coil.request.ImageRequest
import coil.size.Size

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun JobDetailScreen(
    job: JobPost,
    mediaItems: List<MediaItem>,
    onNavigateUp: () -> Unit,
    navController: NavController? = null,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val scrollState = rememberScrollState()
    var fullscreenImageIndex by remember { mutableStateOf<Int?>(null) }

    // Extraer datos
    val terms = job.embedded?.terms?.flatten() ?: emptyList()
    val companyName = terms.find { it.taxonomy == "empresa" }?.name
    val locationName = terms.find { it.taxonomy == "ubicacion" }?.name

    // URLs de imágenes
    val allImageUrls = remember(mediaItems, job) {
        val urls = mutableListOf<String>()
        mediaItems.forEach { media -> media.getImageUrl()?.let { if (it !in urls) urls.add(it) } }
        if (urls.isEmpty()) {
            job.embedded?.featuredMedia?.forEach { media ->
                media.getImageUrl()?.let { if (it !in urls) urls.add(it) }
            }
        }
        urls
    }

    val allFullImageUrls = remember(mediaItems, job) {
        val urls = mutableListOf<String>()
        mediaItems.forEach { media -> media.getFullImageUrl()?.let { if (it !in urls) urls.add(it) } }
        if (urls.isEmpty()) {
            job.embedded?.featuredMedia?.forEach { media ->
                media.getFullImageUrl()?.let { if (it !in urls) urls.add(it) }
            }
        }
        urls
    }

    // Ubicación completa
    val ubicacionCompleta = remember(job.meta?.ubicacionCompleta, job.ubicacionDisplay, locationName) {
        job.meta?.ubicacionCompleta?.let { return@remember it }
        job.ubicacionDisplay?.let { display ->
            if (!display.departamento.isNullOrBlank()) {
                val nivel = when (display.nivel?.uppercase()) {
                    "DISTRITO" -> LocationType.DISTRITO
                    "PROVINCIA" -> LocationType.PROVINCIA
                    else -> LocationType.DEPARTAMENTO
                }
                return@remember UbicacionCompleta(
                    departamento = display.departamento,
                    provincia = display.provincia ?: "",
                    distrito = display.distrito ?: "",
                    direccion = display.direccion,
                    lat = display.lat,
                    lng = display.lng,
                    nivel = nivel
                )
            }
        }
        if (locationName == null) return@remember null
        val parts = locationName.split(",").map { it.trim() }
        when (parts.size) {
            1 -> UbicacionCompleta(departamento = parts[0], provincia = "", distrito = "", nivel = LocationType.DEPARTAMENTO)
            2 -> UbicacionCompleta(departamento = parts[1], provincia = parts[0], distrito = "", nivel = LocationType.PROVINCIA)
            3 -> UbicacionCompleta(departamento = parts[2], provincia = parts[1], distrito = parts[0], nivel = LocationType.DISTRITO)
            else -> UbicacionCompleta(departamento = parts.lastOrNull() ?: "", provincia = parts.getOrNull(parts.size - 2) ?: "", distrito = parts.firstOrNull() ?: "", nivel = LocationType.DISTRITO)
        }
    }

    // Estado de empresa
    var companyProfile by remember { mutableStateOf<CompanyProfileResponse?>(null) }

    LaunchedEffect(companyName) {
        if (companyName != null) {
            try {
                companyProfile = WordPressApi.retrofitService.getCompanyProfileByName(companyName.htmlToString())
            } catch (e: Exception) { companyProfile = null }
        }
    }

    Scaffold(
        containerColor = MaterialTheme.colorScheme.background
    ) { innerPadding ->
        Box(modifier = modifier.fillMaxSize()) {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(scrollState)
            ) {
                // ═══════════════════════════════════════════════════════════
                // HERO SECTION - Imagen con overlay
                // ═══════════════════════════════════════════════════════════
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(280.dp)
                ) {
                    // Imagen de fondo
                    if (allImageUrls.isNotEmpty()) {
                        HeroImageSlider(
                            imageUrls = allImageUrls,
                            onImageClick = { index -> fullscreenImageIndex = index }
                        )
                    } else {
                        // Placeholder elegante
                        Box(
                            modifier = Modifier
                                .fillMaxSize()
                                .background(
                                    Brush.verticalGradient(
                                        colors = listOf(
                                            MaterialTheme.colorScheme.primary.copy(alpha = 0.8f),
                                            MaterialTheme.colorScheme.primaryContainer
                                        )
                                    )
                                ),
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(
                                painter = painterResource(id = R.drawable.ic_launcher_foreground),
                                contentDescription = null,
                                modifier = Modifier.size(100.dp),
                                tint = Color.White.copy(alpha = 0.3f)
                            )
                        }
                    }

                    // Gradiente oscuro inferior para legibilidad
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(120.dp)
                            .align(Alignment.BottomCenter)
                            .background(
                                Brush.verticalGradient(
                                    colors = listOf(
                                        Color.Transparent,
                                        Color.Black.copy(alpha = 0.7f)
                                    )
                                )
                            )
                    )

                    // Botón de volver
                    IconButton(
                        onClick = onNavigateUp,
                        modifier = Modifier
                            .padding(16.dp)
                            .align(Alignment.TopStart)
                            .background(
                                Color.Black.copy(alpha = 0.3f),
                                CircleShape
                            )
                    ) {
                        Icon(
                            Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "Volver",
                            tint = Color.White
                        )
                    }

                    // Contador de imágenes
                    if (allImageUrls.size > 1) {
                        Surface(
                            modifier = Modifier
                                .align(Alignment.TopEnd)
                                .padding(16.dp),
                            shape = RoundedCornerShape(16.dp),
                            color = Color.Black.copy(alpha = 0.5f)
                        ) {
                            Text(
                                text = "${allImageUrls.size} fotos",
                                modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                                color = Color.White,
                                style = MaterialTheme.typography.labelMedium
                            )
                        }
                    }
                }

                // ═══════════════════════════════════════════════════════════
                // CONTENIDO PRINCIPAL
                // ═══════════════════════════════════════════════════════════
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .offset(y = (-24).dp)
                        .background(
                            MaterialTheme.colorScheme.background,
                            RoundedCornerShape(topStart = 24.dp, topEnd = 24.dp)
                        )
                        .padding(horizontal = 20.dp)
                        .padding(top = 24.dp)
                ) {
                    // ─────────────────────────────────────────────────────────
                    // TÍTULO Y EMPRESA
                    // ─────────────────────────────────────────────────────────
                    Text(
                        text = job.title?.rendered?.htmlToString() ?: "Sin título",
                        style = MaterialTheme.typography.headlineSmall,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface,
                        lineHeight = 32.sp
                    )

                    Spacer(Modifier.height(12.dp))

                    // Empresa clickeable
                    if (companyName != null) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier
                                .clip(RoundedCornerShape(8.dp))
                                .clickable {
                                    navController?.navigate("company_profile/${companyName.htmlToString()}")
                                }
                                .padding(vertical = 4.dp)
                        ) {
                            // Logo de empresa o icono
                            val logoUrl = companyProfile?.logoUrl ?: companyProfile?.profilePhotoUrl
                            if (logoUrl != null) {
                                AsyncImage(
                                    model = ImageRequest.Builder(context).data(logoUrl).build(),
                                    contentDescription = null,
                                    modifier = Modifier
                                        .size(32.dp)
                                        .clip(CircleShape),
                                    contentScale = ContentScale.Crop
                                )
                            } else {
                                Box(
                                    modifier = Modifier
                                        .size(32.dp)
                                        .background(
                                            MaterialTheme.colorScheme.primaryContainer,
                                            CircleShape
                                        ),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Icon(
                                        Icons.Default.Business,
                                        contentDescription = null,
                                        modifier = Modifier.size(18.dp),
                                        tint = MaterialTheme.colorScheme.primary
                                    )
                                }
                            }
                            Spacer(Modifier.width(10.dp))
                            Text(
                                text = companyName.htmlToString(),
                                style = MaterialTheme.typography.titleMedium,
                                color = MaterialTheme.colorScheme.primary,
                                fontWeight = FontWeight.Medium
                            )
                            Icon(
                                Icons.Default.ChevronRight,
                                contentDescription = null,
                                modifier = Modifier.size(20.dp),
                                tint = MaterialTheme.colorScheme.primary.copy(alpha = 0.7f)
                            )
                        }
                    }

                    Spacer(Modifier.height(20.dp))

                    // ─────────────────────────────────────────────────────────
                    // QUICK INFO - Chips con información clave
                    // ─────────────────────────────────────────────────────────
                    QuickInfoSection(job = job, ubicacion = ubicacionCompleta)

                    Spacer(Modifier.height(24.dp))

                    // ─────────────────────────────────────────────────────────
                    // BENEFICIOS (si existen)
                    // ─────────────────────────────────────────────────────────
                    val hasBenefits = job.meta?.alojamiento == true ||
                                      job.meta?.transporte == true ||
                                      job.meta?.alimentacion == true

                    if (hasBenefits) {
                        BenefitsSection(job = job)
                        Spacer(Modifier.height(24.dp))
                    }

                    // ─────────────────────────────────────────────────────────
                    // DESCRIPCIÓN DEL TRABAJO
                    // ─────────────────────────────────────────────────────────
                    job.content?.rendered?.let { content ->
                        if (content.trim().isNotBlank()) {
                            ContentSection(
                                title = "Descripción",
                                icon = Icons.Outlined.Description
                            ) {
                                FormattedText(
                                    text = content,
                                    style = MaterialTheme.typography.bodyLarge.copy(
                                        lineHeight = 26.sp,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    ),
                                    modifier = Modifier.fillMaxWidth()
                                )
                            }
                            Spacer(Modifier.height(20.dp))
                        }
                    }

                    // ─────────────────────────────────────────────────────────
                    // REQUISITOS
                    // ─────────────────────────────────────────────────────────
                    job.meta?.requisitos?.let { requisitos ->
                        if (requisitos.isNotBlank()) {
                            ContentSection(
                                title = "Requisitos",
                                icon = Icons.Outlined.Checklist
                            ) {
                                val items = requisitos.htmlToString()
                                    .split("\n")
                                    .filter { it.trim().isNotEmpty() }

                                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                                    items.forEach { item ->
                                        RequirementRow(text = item.trim())
                                    }
                                }
                            }
                            Spacer(Modifier.height(20.dp))
                        }
                    }

                    // ─────────────────────────────────────────────────────────
                    // DETALLES DEL PUESTO
                    // ─────────────────────────────────────────────────────────
                    val hasDetails = !job.meta?.vacantes.isNullOrBlank() ||
                                     !job.meta?.tipoContrato.isNullOrBlank() ||
                                     !job.meta?.jornada.isNullOrBlank()

                    if (hasDetails) {
                        ContentSection(
                            title = "Detalles del puesto",
                            icon = Icons.Outlined.Info
                        ) {
                            Column(verticalArrangement = Arrangement.spacedBy(16.dp)) {
                                job.meta?.vacantes?.takeIf { it.isNotBlank() }?.let {
                                    DetailItem(
                                        icon = Icons.Outlined.Groups,
                                        label = "Vacantes",
                                        value = "$it disponibles"
                                    )
                                }
                                job.meta?.tipoContrato?.takeIf { it.isNotBlank() }?.let {
                                    DetailItem(
                                        icon = Icons.Outlined.Assignment,
                                        label = "Tipo de contrato",
                                        value = it
                                    )
                                }
                                job.meta?.jornada?.takeIf { it.isNotBlank() }?.let {
                                    DetailItem(
                                        icon = Icons.Outlined.Schedule,
                                        label = "Jornada",
                                        value = it
                                    )
                                }
                            }
                        }
                        Spacer(Modifier.height(20.dp))
                    }

                    // ─────────────────────────────────────────────────────────
                    // UBICACIÓN
                    // ─────────────────────────────────────────────────────────
                    ubicacionCompleta?.let { ubicacion ->
                        if (ubicacion.departamento.isNotBlank()) {
                            ContentSection(
                                title = "Ubicación",
                                icon = Icons.Outlined.LocationOn
                            ) {
                                LocationCard(ubicacion = ubicacion)
                            }
                            Spacer(Modifier.height(20.dp))
                        }
                    }

                    // ─────────────────────────────────────────────────────────
                    // INFORMACIÓN DE LA EMPRESA
                    // ─────────────────────────────────────────────────────────
                    if (companyName != null && companyProfile != null) {
                        ContentSection(
                            title = "Acerca de la empresa",
                            icon = Icons.Outlined.Business
                        ) {
                            CompanyCard(
                                companyProfile = companyProfile!!,
                                companyName = companyName,
                                navController = navController,
                                context = context
                            )
                        }
                        Spacer(Modifier.height(20.dp))
                    }

                    // Espacio inferior para el botón flotante
                    Spacer(Modifier.height(80.dp))
                }
            }

            // ═══════════════════════════════════════════════════════════
            // BOTÓN DE CONTACTO FLOTANTE
            // ═══════════════════════════════════════════════════════════
            AnimatedVisibility(
                visible = companyProfile?.phone != null || companyProfile?.email != null,
                enter = fadeIn(),
                exit = fadeOut(),
                modifier = Modifier
                    .align(Alignment.BottomCenter)
                    .padding(20.dp)
            ) {
                ContactButton(
                    phone = companyProfile?.phone,
                    email = companyProfile?.email,
                    context = context
                )
            }
        }
    }

    // Fullscreen image viewer
    if (fullscreenImageIndex != null && allFullImageUrls.isNotEmpty()) {
        FullscreenImageViewer(
            imageUrls = allFullImageUrls,
            initialIndex = fullscreenImageIndex ?: 0,
            onDismiss = { fullscreenImageIndex = null }
        )
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// COMPONENTES
// ═══════════════════════════════════════════════════════════════════════════

@OptIn(ExperimentalFoundationApi::class)
@Composable
private fun HeroImageSlider(
    imageUrls: List<String>,
    onImageClick: (Int) -> Unit
) {
    val pagerState = rememberPagerState(pageCount = { imageUrls.size })
    val context = LocalContext.current

    Box(modifier = Modifier.fillMaxSize()) {
        HorizontalPager(
            state = pagerState,
            modifier = Modifier.fillMaxSize()
        ) { page ->
            AsyncImage(
                model = ImageRequest.Builder(context)
                    .data(imageUrls[page])
                    .crossfade(true)
                    .build(),
                contentDescription = null,
                modifier = Modifier
                    .fillMaxSize()
                    .clickable { onImageClick(page) },
                contentScale = ContentScale.Crop
            )
        }

        // Indicadores
        if (imageUrls.size > 1) {
            Row(
                modifier = Modifier
                    .align(Alignment.BottomCenter)
                    .padding(bottom = 40.dp),
                horizontalArrangement = Arrangement.spacedBy(6.dp)
            ) {
                repeat(imageUrls.size) { index ->
                    Box(
                        modifier = Modifier
                            .size(if (pagerState.currentPage == index) 8.dp else 6.dp)
                            .background(
                                if (pagerState.currentPage == index) Color.White
                                else Color.White.copy(alpha = 0.5f),
                                CircleShape
                            )
                    )
                }
            }
        }
    }
}

@Composable
private fun QuickInfoSection(job: JobPost, ubicacion: UbicacionCompleta?) {
    LazyRow(
        horizontalArrangement = Arrangement.spacedBy(10.dp)
    ) {
        // Salario
        val salario = when {
            !job.meta?.salarioMin.isNullOrBlank() && !job.meta?.salarioMax.isNullOrBlank() ->
                "S/ ${job.meta?.salarioMin} - ${job.meta?.salarioMax}"
            !job.meta?.salarioMin.isNullOrBlank() -> "S/ ${job.meta?.salarioMin}+"
            else -> null
        }

        salario?.let {
            item {
                InfoChip(
                    icon = Icons.Filled.Payments,
                    text = it,
                    containerColor = MaterialTheme.colorScheme.primaryContainer,
                    contentColor = MaterialTheme.colorScheme.onPrimaryContainer
                )
            }
        }

        // Ubicación
        ubicacion?.let {
            val locationText = when (it.nivel) {
                LocationType.DISTRITO -> "${it.distrito}, ${it.provincia}"
                LocationType.PROVINCIA -> "${it.provincia}, ${it.departamento}"
                else -> it.departamento
            }
            if (locationText.isNotBlank()) {
                item {
                    InfoChip(
                        icon = Icons.Filled.LocationOn,
                        text = locationText,
                        containerColor = MaterialTheme.colorScheme.secondaryContainer,
                        contentColor = MaterialTheme.colorScheme.onSecondaryContainer
                    )
                }
            }
        }

        // Tipo de contrato
        job.meta?.tipoContrato?.takeIf { it.isNotBlank() }?.let {
            item {
                InfoChip(
                    icon = Icons.Filled.WorkOutline,
                    text = it,
                    containerColor = MaterialTheme.colorScheme.tertiaryContainer,
                    contentColor = MaterialTheme.colorScheme.onTertiaryContainer
                )
            }
        }

        // Vacantes
        job.meta?.vacantes?.takeIf { it.isNotBlank() }?.let {
            item {
                InfoChip(
                    icon = Icons.Filled.Groups,
                    text = "$it vacantes",
                    containerColor = MaterialTheme.colorScheme.surfaceVariant,
                    contentColor = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        }
    }
}

@Composable
private fun InfoChip(
    icon: ImageVector,
    text: String,
    containerColor: Color,
    contentColor: Color
) {
    Surface(
        shape = RoundedCornerShape(20.dp),
        color = containerColor
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 14.dp, vertical = 10.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            Icon(
                icon,
                contentDescription = null,
                modifier = Modifier.size(18.dp),
                tint = contentColor
            )
            Text(
                text = text,
                style = MaterialTheme.typography.labelLarge,
                color = contentColor,
                fontWeight = FontWeight.Medium
            )
        }
    }
}

@Composable
private fun BenefitsSection(job: JobPost) {
    Text(
        text = "Beneficios incluidos",
        style = MaterialTheme.typography.titleMedium,
        fontWeight = FontWeight.SemiBold,
        color = MaterialTheme.colorScheme.onSurface
    )

    Spacer(Modifier.height(12.dp))

    Row(
        horizontalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        if (job.meta?.alojamiento == true) {
            BenefitChip(icon = Icons.Filled.Home, text = "Alojamiento")
        }
        if (job.meta?.transporte == true) {
            BenefitChip(icon = Icons.Filled.DirectionsBus, text = "Transporte")
        }
        if (job.meta?.alimentacion == true) {
            BenefitChip(icon = Icons.Filled.Restaurant, text = "Alimentación")
        }
    }
}

@Composable
private fun BenefitChip(icon: ImageVector, text: String) {
    Surface(
        shape = RoundedCornerShape(12.dp),
        color = Color(0xFF4CAF50).copy(alpha = 0.1f),
        border = androidx.compose.foundation.BorderStroke(
            1.dp,
            Color(0xFF4CAF50).copy(alpha = 0.3f)
        )
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            Icon(
                icon,
                contentDescription = null,
                modifier = Modifier.size(18.dp),
                tint = Color(0xFF4CAF50)
            )
            Text(
                text = text,
                style = MaterialTheme.typography.labelMedium,
                color = Color(0xFF2E7D32),
                fontWeight = FontWeight.Medium
            )
        }
    }
}

@Composable
private fun ContentSection(
    title: String,
    icon: ImageVector,
    content: @Composable () -> Unit
) {
    Column {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            Icon(
                icon,
                contentDescription = null,
                modifier = Modifier.size(22.dp),
                tint = MaterialTheme.colorScheme.primary
            )
            Text(
                text = title,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
                color = MaterialTheme.colorScheme.onSurface
            )
        }

        Spacer(Modifier.height(14.dp))

        content()
    }
}

@Composable
private fun RequirementRow(text: String) {
    Row(
        verticalAlignment = Alignment.Top,
        horizontalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        Box(
            modifier = Modifier
                .padding(top = 8.dp)
                .size(6.dp)
                .background(MaterialTheme.colorScheme.primary, CircleShape)
        )
        Text(
            text = text,
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            lineHeight = 22.sp
        )
    }
}

@Composable
private fun DetailItem(
    icon: ImageVector,
    label: String,
    value: String
) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(14.dp)
    ) {
        Box(
            modifier = Modifier
                .size(40.dp)
                .background(
                    MaterialTheme.colorScheme.surfaceVariant,
                    RoundedCornerShape(10.dp)
                ),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                icon,
                contentDescription = null,
                modifier = Modifier.size(22.dp),
                tint = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        Column {
            Text(
                text = label,
                style = MaterialTheme.typography.labelMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Text(
                text = value,
                style = MaterialTheme.typography.bodyLarge,
                fontWeight = FontWeight.Medium,
                color = MaterialTheme.colorScheme.onSurface
            )
        }
    }
}

@Composable
private fun LocationCard(ubicacion: UbicacionCompleta) {
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
    ) {
        Column(
            modifier = Modifier.padding(16.dp)
        ) {
            // Ubicación principal
            val mainLocation = when (ubicacion.nivel) {
                LocationType.DISTRITO -> ubicacion.distrito
                LocationType.PROVINCIA -> ubicacion.provincia
                else -> ubicacion.departamento
            }

            Text(
                text = mainLocation,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
                color = MaterialTheme.colorScheme.onSurface
            )

            // Ubicación completa
            val fullLocation = listOf(
                ubicacion.distrito.takeIf { it.isNotBlank() && ubicacion.nivel == LocationType.DISTRITO },
                ubicacion.provincia.takeIf { it.isNotBlank() },
                ubicacion.departamento
            ).filterNotNull().distinct().joinToString(", ")

            if (fullLocation != mainLocation) {
                Text(
                    text = fullLocation,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            // Dirección si existe
            ubicacion.direccion?.takeIf { it.isNotBlank() }?.let {
                Spacer(Modifier.height(8.dp))
                Row(
                    verticalAlignment = Alignment.Top,
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    Icon(
                        Icons.Outlined.Place,
                        contentDescription = null,
                        modifier = Modifier.size(16.dp),
                        tint = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    Text(
                        text = it,
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
        }
    }
}

@Composable
private fun CompanyCard(
    companyProfile: CompanyProfileResponse,
    companyName: String,
    navController: NavController?,
    context: android.content.Context
) {
    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { navController?.navigate("company_profile/$companyName") },
        shape = RoundedCornerShape(16.dp),
        color = MaterialTheme.colorScheme.surface,
        border = androidx.compose.foundation.BorderStroke(
            1.dp,
            MaterialTheme.colorScheme.outlineVariant
        )
    ) {
        Column(
            modifier = Modifier.padding(16.dp)
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(14.dp)
            ) {
                // Logo
                val logoUrl = companyProfile.logoUrl ?: companyProfile.profilePhotoUrl
                if (logoUrl != null) {
                    AsyncImage(
                        model = ImageRequest.Builder(context).data(logoUrl).build(),
                        contentDescription = null,
                        modifier = Modifier
                            .size(56.dp)
                            .clip(RoundedCornerShape(12.dp)),
                        contentScale = ContentScale.Crop
                    )
                } else {
                    Box(
                        modifier = Modifier
                            .size(56.dp)
                            .background(
                                MaterialTheme.colorScheme.primaryContainer,
                                RoundedCornerShape(12.dp)
                            ),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            Icons.Default.Business,
                            contentDescription = null,
                            modifier = Modifier.size(28.dp),
                            tint = MaterialTheme.colorScheme.primary
                        )
                    }
                }

                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = companyProfile.companyName,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                    companyProfile.description?.takeIf { it.isNotBlank() }?.let {
                        Text(
                            text = if (it.length > 80) it.take(80) + "..." else it,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            maxLines = 2,
                            overflow = TextOverflow.Ellipsis
                        )
                    }
                }

                Icon(
                    Icons.Default.ChevronRight,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            // Contacto rápido
            val hasContact = !companyProfile.phone.isNullOrBlank() || !companyProfile.email.isNullOrBlank()
            if (hasContact) {
                Spacer(Modifier.height(12.dp))
                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                Spacer(Modifier.height(12.dp))

                Row(
                    horizontalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    companyProfile.phone?.takeIf { it.isNotBlank() }?.let { phone ->
                        Row(
                            modifier = Modifier
                                .clip(RoundedCornerShape(8.dp))
                                .clickable {
                                    context.startActivity(Intent(Intent.ACTION_DIAL, Uri.parse("tel:$phone")))
                                }
                                .padding(4.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(4.dp)
                        ) {
                            Icon(
                                Icons.Outlined.Phone,
                                contentDescription = null,
                                modifier = Modifier.size(16.dp),
                                tint = MaterialTheme.colorScheme.primary
                            )
                            Text(
                                text = "Llamar",
                                style = MaterialTheme.typography.labelMedium,
                                color = MaterialTheme.colorScheme.primary
                            )
                        }
                    }

                    companyProfile.email?.takeIf { it.isNotBlank() }?.let { email ->
                        Row(
                            modifier = Modifier
                                .clip(RoundedCornerShape(8.dp))
                                .clickable {
                                    context.startActivity(Intent(Intent.ACTION_SENDTO, Uri.parse("mailto:$email")))
                                }
                                .padding(4.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(4.dp)
                        ) {
                            Icon(
                                Icons.Outlined.Email,
                                contentDescription = null,
                                modifier = Modifier.size(16.dp),
                                tint = MaterialTheme.colorScheme.primary
                            )
                            Text(
                                text = "Email",
                                style = MaterialTheme.typography.labelMedium,
                                color = MaterialTheme.colorScheme.primary
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun ContactButton(
    phone: String?,
    email: String?,
    context: android.content.Context
) {
    var showOptions by remember { mutableStateOf(false) }

    Button(
        onClick = {
            if (phone != null && email != null) {
                showOptions = true
            } else if (phone != null) {
                context.startActivity(Intent(Intent.ACTION_DIAL, Uri.parse("tel:$phone")))
            } else if (email != null) {
                context.startActivity(Intent(Intent.ACTION_SENDTO, Uri.parse("mailto:$email")))
            }
        },
        modifier = Modifier
            .fillMaxWidth()
            .height(56.dp)
            .shadow(8.dp, RoundedCornerShape(16.dp)),
        shape = RoundedCornerShape(16.dp),
        colors = ButtonDefaults.buttonColors(
            containerColor = MaterialTheme.colorScheme.primary
        )
    ) {
        Icon(
            Icons.Filled.Send,
            contentDescription = null,
            modifier = Modifier.size(20.dp)
        )
        Spacer(Modifier.width(8.dp))
        Text(
            text = "Contactar",
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.SemiBold
        )
    }

    if (showOptions) {
        AlertDialog(
            onDismissRequest = { showOptions = false },
            title = { Text("Contactar") },
            text = { Text("¿Cómo deseas contactar a la empresa?") },
            confirmButton = {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    if (phone != null) {
                        TextButton(onClick = {
                            context.startActivity(Intent(Intent.ACTION_DIAL, Uri.parse("tel:$phone")))
                            showOptions = false
                        }) {
                            Text("📞 Llamar")
                        }
                        TextButton(onClick = {
                            val cleanPhone = phone.replace(Regex("[\\s\\-()]"), "")
                            val whatsappNumber = if (cleanPhone.startsWith("+")) cleanPhone else "+51$cleanPhone"
                            context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse("https://wa.me/${whatsappNumber.removePrefix("+")}")))
                            showOptions = false
                        }) {
                            Text("💬 WhatsApp")
                        }
                    }
                    if (email != null) {
                        TextButton(onClick = {
                            context.startActivity(Intent(Intent.ACTION_SENDTO, Uri.parse("mailto:$email")))
                            showOptions = false
                        }) {
                            Text("📧 Email")
                        }
                    }
                }
            },
            dismissButton = {
                TextButton(onClick = { showOptions = false }) {
                    Text("Cancelar")
                }
            }
        )
    }
}

@OptIn(ExperimentalFoundationApi::class)
@Composable
private fun FullscreenImageViewer(
    imageUrls: List<String>,
    initialIndex: Int,
    onDismiss: () -> Unit
) {
    val pagerState = rememberPagerState(
        pageCount = { imageUrls.size },
        initialPage = initialIndex.coerceIn(0, imageUrls.size - 1)
    )
    val context = LocalContext.current

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(Color.Black)
        ) {
            HorizontalPager(
                state = pagerState,
                modifier = Modifier.fillMaxSize()
            ) { page ->
                AsyncImage(
                    model = ImageRequest.Builder(context)
                        .data(imageUrls[page])
                        .crossfade(true)
                        .size(Size.ORIGINAL)
                        .build(),
                    contentDescription = null,
                    modifier = Modifier
                        .fillMaxSize()
                        .clickable { onDismiss() },
                    contentScale = ContentScale.Fit
                )
            }

            // Contador
            if (imageUrls.size > 1) {
                Surface(
                    modifier = Modifier
                        .align(Alignment.TopCenter)
                        .padding(top = 48.dp),
                    shape = RoundedCornerShape(16.dp),
                    color = Color.Black.copy(alpha = 0.6f)
                ) {
                    Text(
                        text = "${pagerState.currentPage + 1} / ${imageUrls.size}",
                        modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp),
                        color = Color.White,
                        style = MaterialTheme.typography.bodyMedium
                    )
                }
            }

            // Botón cerrar
            IconButton(
                onClick = onDismiss,
                modifier = Modifier
                    .align(Alignment.TopEnd)
                    .padding(16.dp)
                    .background(Color.Black.copy(alpha = 0.5f), CircleShape)
            ) {
                Icon(
                    Icons.Default.Close,
                    contentDescription = "Cerrar",
                    tint = Color.White
                )
            }
        }
    }
}
