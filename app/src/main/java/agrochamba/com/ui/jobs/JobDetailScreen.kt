package agrochamba.com.ui.jobs

import android.content.Intent
import android.net.Uri
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import kotlinx.coroutines.delay
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
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.navigation.NavController
import agrochamba.com.R
import agrochamba.com.data.*
import agrochamba.com.data.AuthManager
import agrochamba.com.ui.common.FormattedText
import agrochamba.com.utils.htmlToString
import coil.compose.AsyncImage
import coil.request.ImageRequest
import coil.size.Size

/**
 * Pantalla de detalle de trabajo - Composable Stateful
 * Se conecta al ViewModel y maneja eventos de navegación
 */
@Composable
fun JobDetailScreen(
    job: JobPost,
    mediaItems: List<MediaItem>,
    onNavigateUp: () -> Unit,
    navController: NavController? = null,
    modifier: Modifier = Modifier,
    viewModel: JobDetailViewModel = hiltViewModel()
) {
    val context = LocalContext.current
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    val fullscreenImageIndex by viewModel.fullscreenImageIndex.collectAsStateWithLifecycle()

    // Inicializar ViewModel con los datos
    LaunchedEffect(job.id) {
        viewModel.initialize(job, mediaItems)
    }

    // Manejar eventos del ViewModel
    LaunchedEffect(Unit) {
        viewModel.events.collect { event ->
            when (event) {
                is JobDetailEvent.NavigateBack -> onNavigateUp()
                is JobDetailEvent.OpenDialer -> {
                    context.startActivity(Intent(Intent.ACTION_DIAL, Uri.parse("tel:${event.phone}")))
                }
                is JobDetailEvent.OpenWhatsApp -> {
                    context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse("https://wa.me/${event.phoneNumber}")))
                }
                is JobDetailEvent.OpenEmail -> {
                    context.startActivity(Intent(Intent.ACTION_SENDTO, Uri.parse("mailto:${event.email}")))
                }
                is JobDetailEvent.NavigateToCompany -> {
                    navController?.navigate("company_profile/${event.companyName}")
                }
            }
        }
    }

    // Renderizar según el estado
    when (val state = uiState) {
        is JobDetailUiState.Loading -> {
            JobDetailLoadingContent(
                onNavigateUp = { viewModel.onAction(JobDetailAction.NavigateBack) },
                modifier = modifier
            )
        }
        is JobDetailUiState.Success -> {
            JobDetailSuccessContent(
                state = state,
                fullscreenImageIndex = fullscreenImageIndex,
                onAction = viewModel::onAction,
                navController = navController,
                modifier = modifier
            )
        }
        is JobDetailUiState.Error -> {
            JobDetailErrorContent(
                message = state.message,
                canRetry = state.canRetry,
                onRetry = { viewModel.onAction(JobDetailAction.Retry) },
                onNavigateUp = { viewModel.onAction(JobDetailAction.NavigateBack) },
                modifier = modifier
            )
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// LOADING STATE
// ═══════════════════════════════════════════════════════════════════════════════

@Composable
private fun JobDetailLoadingContent(
    onNavigateUp: () -> Unit,
    modifier: Modifier = Modifier
) {
    Scaffold(
        topBar = {
            SmallTopAppBar(
                title = { },
                navigationIcon = {
                    IconButton(onClick = onNavigateUp) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Volver")
                    }
                }
            )
        }
    ) { innerPadding ->
        Box(
            modifier = modifier
                .fillMaxSize()
                .padding(innerPadding),
            contentAlignment = Alignment.Center
        ) {
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                CircularProgressIndicator(
                    modifier = Modifier.size(48.dp),
                    color = MaterialTheme.colorScheme.primary
                )
                Text(
                    text = "Cargando detalles...",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// ERROR STATE
// ═══════════════════════════════════════════════════════════════════════════════

@Composable
private fun JobDetailErrorContent(
    message: String,
    canRetry: Boolean,
    onRetry: () -> Unit,
    onNavigateUp: () -> Unit,
    modifier: Modifier = Modifier
) {
    Scaffold(
        topBar = {
            SmallTopAppBar(
                title = { Text("Error") },
                navigationIcon = {
                    IconButton(onClick = onNavigateUp) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Volver")
                    }
                }
            )
        }
    ) { innerPadding ->
        Box(
            modifier = modifier
                .fillMaxSize()
                .padding(innerPadding),
            contentAlignment = Alignment.Center
        ) {
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(16.dp),
                modifier = Modifier.padding(32.dp)
            ) {
                Icon(
                    Icons.Outlined.ErrorOutline,
                    contentDescription = null,
                    modifier = Modifier.size(64.dp),
                    tint = MaterialTheme.colorScheme.error
                )
                Text(
                    text = message,
                    style = MaterialTheme.typography.bodyLarge,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    textAlign = androidx.compose.ui.text.style.TextAlign.Center
                )
                if (canRetry) {
                    Button(onClick = onRetry) {
                        Icon(Icons.Default.Refresh, contentDescription = null)
                        Spacer(Modifier.width(8.dp))
                        Text("Reintentar")
                    }
                }
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// SUCCESS STATE - Contenido principal
// ═══════════════════════════════════════════════════════════════════════════════

@Composable
private fun JobDetailSuccessContent(
    state: JobDetailUiState.Success,
    fullscreenImageIndex: Int?,
    onAction: (JobDetailAction) -> Unit,
    navController: NavController?,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val scrollState = rememberScrollState()

    Scaffold(
        containerColor = MaterialTheme.colorScheme.background
    ) { innerPadding ->
        Box(modifier = modifier.fillMaxSize()) {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(scrollState)
            ) {
                // Hero Section
                HeroSection(
                    imageUrls = state.allImageUrls,
                    onImageClick = { index -> onAction(JobDetailAction.OpenImage(index)) },
                    onNavigateBack = { onAction(JobDetailAction.NavigateBack) }
                )

                // Contenido Principal
                MainContent(
                    state = state,
                    onAction = onAction,
                    navController = navController,
                    context = context
                )
            }

            // Botones flotantes: Postularme y/o Contactar
            val isLoggedIn = AuthManager.token != null
            val isEnterprise = AuthManager.isUserAnEnterprise()
            val canApply = isLoggedIn && !isEnterprise && !state.hasApplied
            val hasContact = state.companyProfile?.phone != null || state.companyProfile?.email != null

            AnimatedVisibility(
                visible = canApply || state.hasApplied || hasContact,
                enter = fadeIn(),
                exit = fadeOut(),
                modifier = Modifier
                    .align(Alignment.BottomCenter)
                    .padding(20.dp)
            ) {
                FloatingActionButtons(
                    state = state,
                    isLoggedIn = isLoggedIn,
                    isEnterprise = isEnterprise,
                    onAction = onAction
                )
            }
        }
    }

    // Fullscreen image viewer
    if (fullscreenImageIndex != null && state.allFullImageUrls.isNotEmpty()) {
        FullscreenImageViewer(
            imageUrls = state.allFullImageUrls,
            initialIndex = fullscreenImageIndex,
            onDismiss = { onAction(JobDetailAction.CloseImage) }
        )
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// HERO SECTION
// ═══════════════════════════════════════════════════════════════════════════════

@OptIn(ExperimentalFoundationApi::class)
@Composable
private fun HeroSection(
    imageUrls: List<String>,
    onImageClick: (Int) -> Unit,
    onNavigateBack: () -> Unit
) {
    Box(
        modifier = Modifier
            .fillMaxWidth()
            .height(280.dp)
    ) {
        if (imageUrls.isNotEmpty()) {
            HeroImageSlider(
                imageUrls = imageUrls,
                onImageClick = onImageClick
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

        // Gradiente oscuro inferior
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
            onClick = onNavigateBack,
            modifier = Modifier
                .padding(16.dp)
                .align(Alignment.TopStart)
                .background(Color.Black.copy(alpha = 0.3f), CircleShape)
        ) {
            Icon(
                Icons.AutoMirrored.Filled.ArrowBack,
                contentDescription = "Volver",
                tint = Color.White
            )
        }

        // Contador de imágenes
        if (imageUrls.size > 1) {
            Surface(
                modifier = Modifier
                    .align(Alignment.TopEnd)
                    .padding(16.dp),
                shape = RoundedCornerShape(16.dp),
                color = Color.Black.copy(alpha = 0.5f)
            ) {
                Text(
                    text = "${imageUrls.size} fotos",
                    modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                    color = Color.White,
                    style = MaterialTheme.typography.labelMedium
                )
            }
        }
    }
}

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

// ═══════════════════════════════════════════════════════════════════════════════
// MAIN CONTENT
// ═══════════════════════════════════════════════════════════════════════════════

@Composable
private fun MainContent(
    state: JobDetailUiState.Success,
    onAction: (JobDetailAction) -> Unit,
    navController: NavController?,
    context: android.content.Context
) {
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
        // Título
        Text(
            text = state.job.title?.rendered?.htmlToString() ?: "Sin título",
            style = MaterialTheme.typography.headlineSmall,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurface,
            lineHeight = 32.sp
        )

        Spacer(Modifier.height(12.dp))

        // Empresa
        state.companyName?.let { companyName ->
            CompanyHeader(
                companyName = companyName,
                companyProfile = state.companyProfile,
                isLoading = state.isLoadingCompany,
                onAction = onAction,
                context = context
            )
        }

        Spacer(Modifier.height(20.dp))

        // Quick Info
        QuickInfoSection(job = state.job, ubicacion = state.ubicacionCompleta)

        Spacer(Modifier.height(24.dp))

        // Beneficios
        val hasBenefits = state.job.meta?.alojamiento == true ||
                state.job.meta?.transporte == true ||
                state.job.meta?.alimentacion == true

        if (hasBenefits) {
            BenefitsSection(job = state.job)
            Spacer(Modifier.height(24.dp))
        }

        // Descripción
        state.job.content?.rendered?.let { content ->
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

        // Requisitos
        state.job.meta?.requisitos?.let { requisitos ->
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

        // Detalles del puesto
        val hasDetails = !state.job.meta?.vacantes.isNullOrBlank() ||
                !state.job.meta?.tipoContrato.isNullOrBlank() ||
                !state.job.meta?.jornada.isNullOrBlank()

        if (hasDetails) {
            ContentSection(
                title = "Detalles del puesto",
                icon = Icons.Outlined.Info
            ) {
                Column(verticalArrangement = Arrangement.spacedBy(16.dp)) {
                    state.job.meta?.vacantes?.takeIf { it.isNotBlank() }?.let {
                        DetailItem(
                            icon = Icons.Outlined.Groups,
                            label = "Vacantes",
                            value = "$it disponibles"
                        )
                    }
                    state.job.meta?.tipoContrato?.takeIf { it.isNotBlank() }?.let {
                        DetailItem(
                            icon = Icons.Outlined.Assignment,
                            label = "Tipo de contrato",
                            value = it
                        )
                    }
                    state.job.meta?.jornada?.takeIf { it.isNotBlank() }?.let {
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

        // Información de la empresa
        if (state.companyName != null && state.companyProfile != null) {
            ContentSection(
                title = "Acerca de la empresa",
                icon = Icons.Outlined.Business
            ) {
                CompanyCard(
                    companyProfile = state.companyProfile,
                    companyName = state.companyName,
                    onAction = onAction,
                    context = context
                )
            }
            Spacer(Modifier.height(20.dp))
        }

        // Espacio para el botón flotante
        Spacer(Modifier.height(80.dp))
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// COMPONENTES
// ═══════════════════════════════════════════════════════════════════════════════

@Composable
private fun CompanyHeader(
    companyName: String,
    companyProfile: CompanyProfileResponse?,
    isLoading: Boolean,
    onAction: (JobDetailAction) -> Unit,
    context: android.content.Context
) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier
            .clip(RoundedCornerShape(8.dp))
            .clickable {
                onAction(JobDetailAction.NavigateToCompany(companyName.htmlToString()))
            }
            .padding(vertical = 4.dp)
    ) {
        // Logo de empresa
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
                if (isLoading) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(16.dp),
                        strokeWidth = 2.dp
                    )
                } else {
                    Icon(
                        Icons.Default.Business,
                        contentDescription = null,
                        modifier = Modifier.size(18.dp),
                        tint = MaterialTheme.colorScheme.primary
                    )
                }
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
        border = BorderStroke(1.dp, Color(0xFF4CAF50).copy(alpha = 0.3f))
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
private fun CompanyCard(
    companyProfile: CompanyProfileResponse,
    companyName: String,
    onAction: (JobDetailAction) -> Unit,
    context: android.content.Context
) {
    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onAction(JobDetailAction.NavigateToCompany(companyName)) },
        shape = RoundedCornerShape(16.dp),
        color = MaterialTheme.colorScheme.surface,
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant)
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
                                .clickable { onAction(JobDetailAction.ContactPhone(phone)) }
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
                                .clickable { onAction(JobDetailAction.ContactEmail(email)) }
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
private fun FloatingActionButtons(
    state: JobDetailUiState.Success,
    isLoggedIn: Boolean,
    isEnterprise: Boolean,
    onAction: (JobDetailAction) -> Unit
) {
    var showApplyDialog by remember { mutableStateOf(false) }
    var applyMessage by remember { mutableStateOf("") }
    var showContactOptions by remember { mutableStateOf(false) }

    val hasContact = state.companyProfile?.phone != null || state.companyProfile?.email != null
    val canApply = isLoggedIn && !isEnterprise && !state.hasApplied

    Column(
        modifier = Modifier.fillMaxWidth(),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        // Botón Postularme (solo para trabajadores no postulados)
        if (canApply) {
            Button(
                onClick = { showApplyDialog = true },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(56.dp)
                    .shadow(8.dp, RoundedCornerShape(16.dp)),
                shape = RoundedCornerShape(16.dp),
                enabled = !state.isApplying && !state.isCheckingApplication,
                colors = ButtonDefaults.buttonColors(
                    containerColor = Color(0xFF4CAF50)
                )
            ) {
                if (state.isApplying) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(20.dp),
                        color = Color.White,
                        strokeWidth = 2.dp
                    )
                } else {
                    Icon(
                        Icons.Filled.Send,
                        contentDescription = null,
                        modifier = Modifier.size(20.dp)
                    )
                }
                Spacer(Modifier.width(8.dp))
                Text(
                    text = if (state.isApplying) "Postulando..." else "Postularme",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold
                )
            }
        }

        // Badge de estado de postulación (si ya se postuló)
        if (state.hasApplied && state.applicationStatusLabel != null) {
            Surface(
                modifier = Modifier
                    .fillMaxWidth()
                    .shadow(4.dp, RoundedCornerShape(16.dp)),
                shape = RoundedCornerShape(16.dp),
                color = when (state.applicationStatus) {
                    "aceptado" -> Color(0xFF4CAF50)
                    "rechazado" -> Color(0xFFF44336)
                    "visto" -> Color(0xFF2196F3)
                    "en_proceso" -> Color(0xFF283593)
                    "entrevista" -> Color(0xFFE65100)
                    "finalista" -> Color(0xFF00695C)
                    else -> Color(0xFFFFA000)
                }
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp),
                    horizontalArrangement = Arrangement.Center,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Icon(
                        when (state.applicationStatus) {
                            "aceptado" -> Icons.Filled.CheckCircle
                            "rechazado" -> Icons.Filled.Cancel
                            "visto" -> Icons.Filled.RemoveRedEye
                            "en_proceso" -> Icons.Filled.Groups
                            "entrevista" -> Icons.Filled.DateRange
                            "finalista" -> Icons.Filled.Star
                            else -> Icons.Filled.Schedule
                        },
                        contentDescription = null,
                        modifier = Modifier.size(20.dp),
                        tint = Color.White
                    )
                    Spacer(Modifier.width(8.dp))
                    Text(
                        text = "Ya postulado: ${state.applicationStatusLabel}",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                        color = Color.White
                    )
                }
            }
        }

        // Botón de contacto (si hay info de contacto)
        if (hasContact) {
            OutlinedButton(
                onClick = {
                    val phone = state.companyProfile?.phone
                    val email = state.companyProfile?.email
                    if (phone != null && email != null) {
                        showContactOptions = true
                    } else if (phone != null) {
                        onAction(JobDetailAction.ContactPhone(phone))
                    } else if (email != null) {
                        onAction(JobDetailAction.ContactEmail(email))
                    }
                },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(48.dp),
                shape = RoundedCornerShape(16.dp),
                border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary)
            ) {
                Icon(
                    Icons.Filled.Phone,
                    contentDescription = null,
                    modifier = Modifier.size(18.dp)
                )
                Spacer(Modifier.width(8.dp))
                Text(
                    text = "Contactar empresa",
                    style = MaterialTheme.typography.bodyLarge,
                    fontWeight = FontWeight.Medium
                )
            }
        }
    }

    // Dialog para postularse
    if (showApplyDialog) {
        AlertDialog(
            onDismissRequest = { showApplyDialog = false },
            title = { Text("Postularme") },
            text = {
                Column {
                    Text(
                        text = "¿Deseas postularte a este trabajo?",
                        style = MaterialTheme.typography.bodyMedium
                    )
                    Spacer(Modifier.height(16.dp))
                    OutlinedTextField(
                        value = applyMessage,
                        onValueChange = { applyMessage = it },
                        label = { Text("Mensaje (opcional)") },
                        placeholder = { Text("Escribe un mensaje para la empresa...") },
                        modifier = Modifier.fillMaxWidth(),
                        minLines = 3,
                        maxLines = 5
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        onAction(JobDetailAction.ApplyToJob(applyMessage))
                        showApplyDialog = false
                        applyMessage = ""
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4CAF50))
                ) {
                    Text("Postularme")
                }
            },
            dismissButton = {
                TextButton(onClick = { showApplyDialog = false }) {
                    Text("Cancelar")
                }
            }
        )
    }

    // Dialog de opciones de contacto
    if (showContactOptions) {
        AlertDialog(
            onDismissRequest = { showContactOptions = false },
            title = { Text("Contactar") },
            text = { Text("¿Cómo deseas contactar a la empresa?") },
            confirmButton = {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    state.companyProfile?.phone?.let { phone ->
                        TextButton(onClick = {
                            onAction(JobDetailAction.ContactPhone(phone))
                            showContactOptions = false
                        }) {
                            Text("Llamar")
                        }
                        TextButton(onClick = {
                            onAction(JobDetailAction.ContactWhatsApp(phone))
                            showContactOptions = false
                        }) {
                            Text("WhatsApp")
                        }
                    }
                    state.companyProfile?.email?.let { email ->
                        TextButton(onClick = {
                            onAction(JobDetailAction.ContactEmail(email))
                            showContactOptions = false
                        }) {
                            Text("Email")
                        }
                    }
                }
            },
            dismissButton = {
                TextButton(onClick = { showContactOptions = false }) {
                    Text("Cancelar")
                }
            }
        )
    }

    // Snackbar de éxito/error
    if (state.applySuccess) {
        LaunchedEffect(Unit) {
            kotlinx.coroutines.delay(3000)
            onAction(JobDetailAction.ClearApplySuccess)
        }
    }

    state.applyError?.let { error ->
        AlertDialog(
            onDismissRequest = { onAction(JobDetailAction.ClearApplyError) },
            title = { Text("Error") },
            text = { Text(error) },
            confirmButton = {
                TextButton(onClick = { onAction(JobDetailAction.ClearApplyError) }) {
                    Text("OK")
                }
            }
        )
    }
}

@Composable
private fun ContactButton(
    phone: String?,
    email: String?,
    onAction: (JobDetailAction) -> Unit
) {
    var showOptions by remember { mutableStateOf(false) }

    Button(
        onClick = {
            if (phone != null && email != null) {
                showOptions = true
            } else if (phone != null) {
                onAction(JobDetailAction.ContactPhone(phone))
            } else if (email != null) {
                onAction(JobDetailAction.ContactEmail(email))
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
                            onAction(JobDetailAction.ContactPhone(phone))
                            showOptions = false
                        }) {
                            Text("Llamar")
                        }
                        TextButton(onClick = {
                            onAction(JobDetailAction.ContactWhatsApp(phone))
                            showOptions = false
                        }) {
                            Text("WhatsApp")
                        }
                    }
                    if (email != null) {
                        TextButton(onClick = {
                            onAction(JobDetailAction.ContactEmail(email))
                            showOptions = false
                        }) {
                            Text("Email")
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

// Alias for backward compatibility
@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun SmallTopAppBar(
    title: @Composable () -> Unit,
    navigationIcon: @Composable () -> Unit
) {
    TopAppBar(
        title = title,
        navigationIcon = navigationIcon
    )
}
