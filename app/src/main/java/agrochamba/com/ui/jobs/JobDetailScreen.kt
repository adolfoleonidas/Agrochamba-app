package agrochamba.com.ui.jobs

import android.content.Intent
import android.net.Uri
import android.os.Build
import android.text.Html
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.ui.graphics.Brush
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.AttachMoney
import androidx.compose.material.icons.filled.Business
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.DirectionsBus
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Restaurant
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.Work
import androidx.compose.material.icons.filled.Email
import androidx.compose.material.icons.filled.Language
import androidx.compose.material.icons.filled.Phone
import androidx.compose.material.icons.filled.Public
import androidx.compose.material.icons.filled.Close
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.remember
import androidx.lifecycle.viewmodel.compose.viewModel
import agrochamba.com.data.CompanyProfileResponse
import agrochamba.com.data.WordPressApi
import agrochamba.com.data.UbicacionCompleta
import agrochamba.com.ui.common.LocationDetailView
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Divider
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.navigation.NavController
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import agrochamba.com.data.JobPost
import agrochamba.com.data.MediaItem
import agrochamba.com.ui.common.FormattedText
import agrochamba.com.utils.htmlToString
import coil.compose.AsyncImage
import coil.request.ImageRequest
import coil.size.Size
import androidx.compose.ui.graphics.ColorFilter
import androidx.compose.ui.res.painterResource
import agrochamba.com.R

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun JobDetailScreen(
    job: JobPost,
    mediaItems: List<MediaItem>,
    onNavigateUp: () -> Unit,
    navController: NavController? = null,
    modifier: Modifier = Modifier
) {
    var fullscreenImageIndex by remember { mutableStateOf<Int?>(null) }
    
    // Obtener todas las URLs de imágenes disponibles (optimizadas para el slider)
    val allImageUrls = remember(mediaItems, job) {
        val urls = mutableListOf<String>()
        
        // 1. Agregar imágenes de mediaItems
        mediaItems.forEach { media ->
            media.getImageUrl()?.let { url ->
                if (url !in urls) urls.add(url)
            }
        }
        
        // 2. Si no hay imágenes, intentar desde embedded
        if (urls.isEmpty()) {
            job.embedded?.featuredMedia?.forEach { media ->
                media.getImageUrl()?.let { url ->
                    if (url !in urls) urls.add(url)
                }
            }
        }
        
        urls
    }
    
    // Obtener todas las URLs completas para pantalla completa
    val allFullImageUrls = remember(mediaItems, job) {
        val urls = mutableListOf<String>()
        
        // 1. Agregar imágenes completas de mediaItems
        mediaItems.forEach { media ->
            media.getFullImageUrl()?.let { url ->
                if (url !in urls) urls.add(url)
            }
        }
        
        // 2. Si no hay imágenes, intentar desde embedded
        if (urls.isEmpty()) {
            job.embedded?.featuredMedia?.forEach { media ->
                media.getFullImageUrl()?.let { url ->
                    if (url !in urls) urls.add(url)
                }
            }
        }
        
        urls
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Detalle del Trabajo") },
                navigationIcon = {
                    IconButton(onClick = onNavigateUp) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, "Volver atrás")
                    }
                }
            )
        }
    ) { innerPadding ->
        JobDetailContent(
            job = job,
            mediaItems = mediaItems,
            allImageUrls = allImageUrls,
            allFullImageUrls = allFullImageUrls,
            onImageClick = { clickedUrl ->
                // Encontrar el índice de la imagen clickeada
                // Primero intentar encontrar por URL exacta o parcial
                val index = allFullImageUrls.indexOfFirst { url ->
                    // Comparar URLs (puede haber variaciones con parámetros o tamaños)
                    val clickedFileName = clickedUrl.substringAfterLast("/").substringBefore("?")
                    val urlFileName = url.substringAfterLast("/").substringBefore("?")
                    clickedFileName == urlFileName || 
                    url.contains(clickedFileName) || 
                    clickedUrl.contains(urlFileName)
                }
                fullscreenImageIndex = if (index >= 0) index else {
                    // Si no se encuentra, buscar en las URLs optimizadas y mapear al índice correspondiente
                    val optimizedIndex = allImageUrls.indexOfFirst { url ->
                        val clickedFileName = clickedUrl.substringAfterLast("/").substringBefore("?")
                        val urlFileName = url.substringAfterLast("/").substringBefore("?")
                        clickedFileName == urlFileName || url.contains(clickedFileName)
                    }
                    optimizedIndex.coerceIn(0, allFullImageUrls.size - 1)
                }
            },
            navController = navController,
            modifier = modifier.padding(innerPadding)
        )
    }

    if (fullscreenImageIndex != null && allFullImageUrls.isNotEmpty()) {
        FullscreenImageSlider(
            imageUrls = allFullImageUrls,
            initialIndex = fullscreenImageIndex ?: 0,
            onDismiss = { fullscreenImageIndex = null }
        )
    }
}

@Composable
private fun JobDetailContent(
    job: JobPost,
    mediaItems: List<MediaItem>,
    allImageUrls: List<String>,
    allFullImageUrls: List<String>,
    onImageClick: (String) -> Unit,
    navController: NavController? = null,
    modifier: Modifier = Modifier
) {
    val scrollState = rememberScrollState()
    val context = LocalContext.current
    val terms = job.embedded?.terms?.flatten() ?: emptyList()
    val companyName = terms.find { it.taxonomy == "empresa" }?.name
    val locationName = terms.find { it.taxonomy == "ubicacion" }?.name
    
    // Usar ubicación completa del meta field si existe, sino parsear del nombre de taxonomía
    val ubicacionCompleta = remember(job.meta?.ubicacionCompleta, locationName) {
        // Prioridad 1: usar _ubicacion_completa del meta field
        job.meta?.ubicacionCompleta?.let { return@remember it }
        
        // Prioridad 2: parsear del nombre de la taxonomía
        if (locationName == null) return@remember null
        
        val parts = locationName.split(",").map { it.trim() }
        when (parts.size) {
            1 -> UbicacionCompleta(
                departamento = parts[0],
                provincia = parts[0],
                distrito = parts[0]
            )
            2 -> UbicacionCompleta(
                departamento = parts[1],
                provincia = parts[0],
                distrito = parts[0]
            )
            3 -> UbicacionCompleta(
                departamento = parts[2],
                provincia = parts[1],
                distrito = parts[0]
            )
            else -> UbicacionCompleta(
                departamento = parts.lastOrNull() ?: "",
                provincia = parts.getOrNull(parts.size - 2) ?: "",
                distrito = parts.firstOrNull() ?: ""
            )
        }
    }
    
    // Estado para información de la empresa
    var companyProfile by remember { mutableStateOf<CompanyProfileResponse?>(null) }
    var isLoadingCompany by remember { mutableStateOf(false) }
    
    // Cargar información de la empresa cuando hay un nombre
    LaunchedEffect(companyName) {
        if (companyName != null) {
            isLoadingCompany = true
            try {
                companyProfile = WordPressApi.retrofitService.getCompanyProfileByName(companyName.htmlToString())
            } catch (e: Exception) {
                // Si falla, simplemente no mostramos la información de la empresa
                companyProfile = null
            } finally {
                isLoadingCompany = false
            }
        }
    }

    Column(modifier = modifier.fillMaxSize().verticalScroll(scrollState)) {
        // --- HEADER CON SLIDER DE IMÁGENES ---
            
        if (allImageUrls.isNotEmpty()) {
            // SLIDER DE IMÁGENES
            ImageSlider(
                imageUrls = allImageUrls,
                fullImageUrls = allFullImageUrls, // Pasar URLs completas para pantalla completa
                onImageClick = onImageClick,
                modifier = Modifier.fillMaxWidth()
            )
        } else {
            // Si no hay imágenes, mostrar placeholder
            Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(300.dp)
                    .background(
                        Brush.verticalGradient(
                            colors = listOf(
                                MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.3f),
                                MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                            )
                        ),
                        RoundedCornerShape(0.dp)
                    ),
                contentAlignment = Alignment.Center
            ) {
                AgroChambaLogoPlaceholder(
                    size = 80.dp,
                    alpha = 0.5f
                )
            }
        }

        Column(modifier = Modifier.padding(horizontal = 16.dp)) {
            Spacer(Modifier.height(20.dp))

            // --- TÍTULO ---
            Text(
                job.title?.rendered?.htmlToString() ?: "Sin título",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface,
                lineHeight = MaterialTheme.typography.headlineMedium.lineHeight * 1.2
            )
            
            Spacer(Modifier.height(16.dp))
            
            // --- EMPRESA CON ICONO ---
            if (companyName != null) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.clickable {
                        // Navegar al perfil de la empresa
                        navController?.navigate("company_profile/${companyName.htmlToString()}")
                    }
                ) {
                    Icon(
                        Icons.Default.Business,
                        contentDescription = null,
                        tint = MaterialTheme.colorScheme.primary,
                        modifier = Modifier.size(20.dp)
                    )
                    Spacer(Modifier.width(8.dp))
                    Text(
                        companyName.htmlToString(),
                        style = MaterialTheme.typography.titleSmall,
                        fontWeight = FontWeight.Medium,
                        color = MaterialTheme.colorScheme.primary
                    )
                }
            }

            Spacer(Modifier.height(20.dp))

            // --- SALARIO (en fila simple) ---
            job.meta?.let {
                val salario = when {
                    !it.salarioMin.isNullOrBlank() && !it.salarioMax.isNullOrBlank() -> "S/ ${it.salarioMin} - S/ ${it.salarioMax}"
                    !it.salarioMin.isNullOrBlank() -> "S/ ${it.salarioMin}+"
                    else -> null
                }
                if (salario != null) {
                    SimpleInfoRow(icon = Icons.Default.AttachMoney, text = salario)
                    Spacer(Modifier.height(16.dp))
                }
            }
            
            // --- UBICACIÓN COMPLETA (Departamento, Provincia, Distrito) ---
            ubicacionCompleta?.let { ubicacion ->
                if (ubicacion.departamento.isNotBlank()) {
                    LocationDetailView(
                        ubicacion = ubicacion,
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            }

            Spacer(Modifier.height(24.dp))

            // --- DESCRIPCIÓN ---
            job.content?.rendered?.let {
                SimpleSectionTitle("Descripción del Trabajo")
                Spacer(Modifier.height(14.dp))
                if (it.trim().isNotBlank()) {
                    FormattedText(
                        text = it,
                        style = MaterialTheme.typography.bodyLarge.copy(
                    lineHeight = MaterialTheme.typography.bodyLarge.lineHeight * 1.6,
                            color = MaterialTheme.colorScheme.onSurface
                        ),
                        modifier = Modifier.fillMaxWidth()
                )
                Spacer(Modifier.height(28.dp))
                }
            }
            
            // --- REQUISITOS ---
            job.meta?.requisitos?.let {
                if(it.isNotBlank()) {
                    SimpleSectionTitle("Requisitos")
                    Spacer(Modifier.height(12.dp))
                    val requisitos = it.htmlToString().split("\n").filter { line -> line.trim().isNotEmpty() }
                    requisitos.forEach { req ->
                        RequirementItem(text = req.trim())
                    }
                    Spacer(Modifier.height(32.dp))
                }
            }

            // --- BENEFICIOS ---
            val benefits = listOfNotNull(
                if (job.meta?.alojamiento == true) "Alojamiento incluido" to Icons.Default.Home else null,
                if (job.meta?.transporte == true) "Transporte" to Icons.Default.DirectionsBus else null,
                if (job.meta?.alimentacion == true) "Alimentación" to Icons.Default.Restaurant else null
            )
            if (benefits.isNotEmpty()) {
                SimpleSectionTitle("Beneficios")
                Spacer(Modifier.height(12.dp))
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    benefits.forEach { (text, icon) ->
                        BenefitItemSimple(text = text, icon = icon)
                    }
                }
                Spacer(Modifier.height(32.dp))
            }

            // --- DETALLES ADICIONALES ---
            job.meta?.let {
                if (!it.vacantes.isNullOrBlank() || !it.tipoContrato.isNullOrBlank() || !it.jornada.isNullOrBlank()) {
                    SimpleSectionTitle("Detalles del Puesto")
                    Spacer(Modifier.height(12.dp))
                    if (!it.vacantes.isNullOrBlank()) {
                        SimpleDetailRow(label = "Vacantes", value = "${it.vacantes} disponibles")
                    }
                    if (!it.tipoContrato.isNullOrBlank()) {
                        SimpleDetailRow(label = "Tipo de Contrato", value = it.tipoContrato)
                    }
                    if (!it.jornada.isNullOrBlank()) {
                        SimpleDetailRow(label = "Jornada", value = it.jornada)
                    }
                    Spacer(Modifier.height(32.dp))
                }
            }

            // --- TARJETA DE INFORMACIÓN DE LA EMPRESA ---
            if (companyName != null && companyProfile != null) {
                Spacer(Modifier.height(8.dp))
                CompanyInfoCard(
                    companyProfile = companyProfile!!,
                    context = context,
                    navController = navController,
                    companyName = companyName
                )
                Spacer(Modifier.height(24.dp))
            }

            Spacer(Modifier.height(32.dp)) // Espacio final
        }
    }
}

@Composable
private fun CompanyInfoCard(
    companyProfile: CompanyProfileResponse,
    context: android.content.Context,
    navController: NavController?,
    companyName: String
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surface
        ),
        shape = RoundedCornerShape(24.dp),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            // Foto de perfil centrada arriba
            val photoUrl = companyProfile.logoUrl ?: companyProfile.profilePhotoUrl
            photoUrl?.let { url ->
                Card(
                    shape = CircleShape,
                    modifier = Modifier.size(100.dp),
                    elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
                ) {
                    AsyncImage(
                        model = ImageRequest.Builder(context).data(url).build(),
                        contentDescription = "Logo de empresa",
                        modifier = Modifier.fillMaxSize(),
                        contentScale = ContentScale.Crop
                    )
                }
            } ?: run {
                Box(
                    modifier = Modifier
                        .size(100.dp)
                        .background(
                            MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.3f),
                            CircleShape
                        ),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        Icons.Default.Business,
                        contentDescription = null,
                        modifier = Modifier.size(50.dp),
                        tint = MaterialTheme.colorScheme.primary
                    )
                }
            }
            
            Spacer(Modifier.height(16.dp))
            
            // Nombre de la empresa
            Text(
                text = companyProfile.companyName,
                style = MaterialTheme.typography.headlineSmall,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface,
                textAlign = TextAlign.Center
            )
            
            Spacer(Modifier.height(8.dp))
            
            // Descripción
            companyProfile.description?.takeIf { it.isNotBlank() }?.let { desc ->
                Text(
                    text = if (desc.length > 150) desc.take(150) + "..." else desc,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    textAlign = TextAlign.Center,
                    lineHeight = MaterialTheme.typography.bodyMedium.lineHeight * 1.5
                )
            }
            
            // Solo mostrar divider y contacto si hay información de contacto
            val hasContactInfo = !companyProfile.address.isNullOrBlank() || 
                                !companyProfile.phone.isNullOrBlank() || 
                                !companyProfile.email.isNullOrBlank() || 
                                !companyProfile.website.isNullOrBlank()
            
            if (hasContactInfo) {
                Spacer(Modifier.height(20.dp))
                Divider(
                    color = MaterialTheme.colorScheme.outline.copy(alpha = 0.15f),
                    thickness = 1.dp,
                    modifier = Modifier.fillMaxWidth()
                )
                Spacer(Modifier.height(20.dp))
            } else {
                Spacer(Modifier.height(20.dp))
            }
            
            // Información de contacto
            Column(
                modifier = Modifier.fillMaxWidth(),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Dirección
                companyProfile.address?.takeIf { it.isNotBlank() }?.let { address ->
                    ContactInfoRowModern(
                        icon = Icons.Default.LocationOn,
                        text = address,
                        onClick = null
                    )
                }
                
                // Teléfono
                companyProfile.phone?.takeIf { it.isNotBlank() }?.let { phone ->
                    ContactInfoRowModern(
                        icon = Icons.Default.Phone,
                        text = phone,
                        onClick = {
                            val intent = Intent(Intent.ACTION_DIAL, Uri.parse("tel:$phone"))
                            context.startActivity(intent)
                        }
                    )
                }
                
                // Email
                companyProfile.email?.takeIf { it.isNotBlank() }?.let { email ->
                    ContactInfoRowModern(
                        icon = Icons.Default.Email,
                        text = email,
                        onClick = {
                            val intent = Intent(Intent.ACTION_SENDTO).apply {
                                data = Uri.parse("mailto:$email")
                            }
                            context.startActivity(intent)
                        }
                    )
                }
                
                // Sitio web
                companyProfile.website?.takeIf { it.isNotBlank() }?.let { website ->
                    ContactInfoRowModern(
                        icon = Icons.Default.Language,
                        text = website.replace(Regex("^https?://"), "").replace("/$", ""),
                        onClick = {
                            val url = if (website.startsWith("http")) website else "https://$website"
                            val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                            context.startActivity(intent)
                        }
                    )
                }
            }
            
            // Botón para ver perfil completo
            Spacer(Modifier.height(20.dp))
            Button(
                onClick = {
                    navController?.navigate("company_profile/$companyName")
                },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(12.dp),
                colors = androidx.compose.material3.ButtonDefaults.buttonColors(
                    containerColor = MaterialTheme.colorScheme.primary
                )
            ) {
                Text(
                    text = "Ver Perfil Completo",
                    style = MaterialTheme.typography.labelLarge,
                    fontWeight = FontWeight.SemiBold,
                    modifier = Modifier.padding(vertical = 4.dp)
                )
            }
        }
    }
}

@Composable
private fun ContactInfoRowModern(
    icon: ImageVector,
    text: String,
    onClick: (() -> Unit)?
) {
    val modifier = if (onClick != null) {
        Modifier
            .fillMaxWidth()
            .clickable { onClick() }
    } else {
        Modifier.fillMaxWidth()
    }
    
    Row(
        modifier = modifier,
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        Icon(
            icon,
            contentDescription = null,
            modifier = Modifier.size(22.dp),
            tint = MaterialTheme.colorScheme.primary
        )
        Text(
            text = text,
            style = MaterialTheme.typography.bodyLarge,
            fontWeight = FontWeight.Normal,
            color = MaterialTheme.colorScheme.onSurface,
            modifier = Modifier.weight(1f),
            maxLines = 2,
            overflow = TextOverflow.Ellipsis
        )
    }
}

@Composable
private fun SimpleSectionTitle(title: String) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Icon(
            Icons.Default.Work,
            contentDescription = null,
            tint = MaterialTheme.colorScheme.primary,
            modifier = Modifier.size(20.dp)
        )
        Spacer(Modifier.width(8.dp))
        Text(
            title,
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.primary
        )
    }
}

@Composable
private fun SimpleInfoRow(icon: ImageVector, text: String) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Icon(
            icon,
            contentDescription = null,
            tint = MaterialTheme.colorScheme.primary,
            modifier = Modifier.size(20.dp)
        )
        Spacer(Modifier.width(8.dp))
        Text(
            text,
            style = MaterialTheme.typography.bodyLarge,
            fontWeight = FontWeight.Medium,
            color = MaterialTheme.colorScheme.onSurface
        )
    }
}

@Composable
private fun RequirementItem(text: String) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 8.dp),
        verticalAlignment = Alignment.Top
    ) {
        Icon(
            Icons.Default.CheckCircle,
            contentDescription = null,
            tint = MaterialTheme.colorScheme.primary,
            modifier = Modifier.size(22.dp)
        )
        Spacer(Modifier.width(12.dp))
        Text(
            text = text,
            style = MaterialTheme.typography.bodyLarge,
            color = MaterialTheme.colorScheme.onSurface,
            lineHeight = MaterialTheme.typography.bodyLarge.lineHeight * 1.5,
            fontWeight = FontWeight.Normal,
            modifier = Modifier.weight(1f)
        )
    }
}

@Composable
private fun BenefitItemSimple(text: String, icon: ImageVector) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .background(
                MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.3f),
                RoundedCornerShape(12.dp)
            )
            .padding(14.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(
            icon,
            contentDescription = null,
            tint = MaterialTheme.colorScheme.primary,
            modifier = Modifier.size(24.dp)
        )
        Spacer(Modifier.width(12.dp))
        Text(
            text = text,
            style = MaterialTheme.typography.bodyLarge,
            fontWeight = FontWeight.Medium,
            color = MaterialTheme.colorScheme.onSurface,
            modifier = Modifier.weight(1f)
        )
    }
}

@Composable
private fun SimpleDetailRow(label: String, value: String) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 10.dp),
        verticalAlignment = Alignment.Top
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(
                label,
                style = MaterialTheme.typography.labelLarge,
                fontWeight = FontWeight.SemiBold,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Spacer(Modifier.height(4.dp))
            Text(
                value,
                style = MaterialTheme.typography.bodyLarge,
                fontWeight = FontWeight.Normal,
                color = MaterialTheme.colorScheme.onSurface
            )
        }
    }
}

@OptIn(ExperimentalFoundationApi::class)
@Composable
private fun FullscreenImageSlider(
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
                .background(Color.Black.copy(alpha = 0.95f))
        ) {
            HorizontalPager(
                state = pagerState,
                modifier = Modifier.fillMaxSize()
            ) { page ->
                val imageUrl = imageUrls[page]
                var imageLoadError by remember(page) { mutableStateOf(false) }
                
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .clickable { onDismiss() },
                    contentAlignment = Alignment.Center
                ) {
                    if (imageLoadError) {
                        // Mostrar mensaje de error
                        Text(
                            text = "Error al cargar la imagen",
                            color = Color.White,
                            style = MaterialTheme.typography.bodyLarge
                        )
                    } else {
                        AsyncImage(
                            model = ImageRequest.Builder(context)
                                .data(imageUrl)
                                .crossfade(true)
                                .size(Size.ORIGINAL) // Solicitar imagen original sin redimensionar
                                .allowHardware(false) // Desactivar hardware para mejor calidad
                                .build(),
                            contentDescription = "Imagen ${page + 1} de ${imageUrls.size}",
                            modifier = Modifier
                                .fillMaxSize(),
                            contentScale = ContentScale.Fit, // Mantener proporción completa sin recortar
                            onError = {
                                android.util.Log.e("FullscreenImageSlider", "Error loading image: $imageUrl")
                                imageLoadError = true
                            },
                            onSuccess = {
                                imageLoadError = false
                            }
                        )
                    }
                }
            }
            
            // Indicador de página (contador)
            if (imageUrls.size > 1) {
                Box(
                    modifier = Modifier
                        .align(Alignment.TopCenter)
                        .padding(top = 32.dp)
                        .background(
                            Color.Black.copy(alpha = 0.6f),
                            RoundedCornerShape(16.dp)
                        )
                        .padding(horizontal = 16.dp, vertical = 8.dp)
                ) {
                    Text(
                        text = "${pagerState.currentPage + 1} / ${imageUrls.size}",
                        color = Color.White,
                        style = MaterialTheme.typography.bodyMedium,
                        fontWeight = FontWeight.Medium
                    )
                }
            }
            
            // Botón de cerrar
            IconButton(
                onClick = onDismiss,
                modifier = Modifier
                    .align(Alignment.TopEnd)
                    .padding(16.dp)
                    .background(
                        Color.Black.copy(alpha = 0.6f),
                        CircleShape
                    )
            ) {
                Icon(
                    imageVector = Icons.Default.Close,
                    contentDescription = "Cerrar",
                    tint = Color.White,
                    modifier = Modifier.size(24.dp)
                )
            }
        }
    }
}

@OptIn(ExperimentalFoundationApi::class)
@Composable
private fun ImageSlider(
    imageUrls: List<String>,
    fullImageUrls: List<String> = emptyList(),
    onImageClick: (String) -> Unit,
    modifier: Modifier = Modifier
) {
    if (imageUrls.isEmpty()) return
    
    val pagerState = rememberPagerState(pageCount = { imageUrls.size }, initialPage = 0)
    val context = LocalContext.current
    
    Box(modifier = modifier) {
        HorizontalPager(
            state = pagerState,
            modifier = Modifier
                .fillMaxWidth()
                .height(300.dp)
        ) { page ->
            val imageUrl = imageUrls[page]
            // Usar URL completa si está disponible, sino usar la optimizada
            val fullImageUrl = if (fullImageUrls.isNotEmpty() && page < fullImageUrls.size) {
                fullImageUrls[page]
            } else {
                imageUrl
            }
            var imageLoadError by remember(page) { mutableStateOf(false) }
            
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .clickable { onImageClick(fullImageUrl) }
            ) {
                if (imageLoadError) {
                    // Mostrar logo de marca cuando hay error al cargar imagen
                    Box(
                        modifier = Modifier
                            .fillMaxSize()
                            .background(
                                Brush.verticalGradient(
                                    colors = listOf(
                                        MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.3f),
                                        MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                                    )
                                )
                            ),
                        contentAlignment = Alignment.Center
                    ) {
                        AgroChambaLogoPlaceholder(
                            size = 80.dp,
                            alpha = 0.5f
                        )
                    }
                } else {
                    AsyncImage(
                        model = ImageRequest.Builder(context)
                            .data(imageUrl)
                            .crossfade(true)
                            .build(),
                        contentDescription = "Imagen ${page + 1} de ${imageUrls.size}",
                        modifier = Modifier.fillMaxSize(),
                        contentScale = ContentScale.Crop,
                        onError = {
                            android.util.Log.e("ImageSlider", "Error loading image: $imageUrl")
                            imageLoadError = true
                        },
                        onSuccess = {
                            imageLoadError = false
                        }
                    )
                }
            }
        }
        
        // Indicadores de página (dots) - solo mostrar si hay más de una imagen
        if (imageUrls.size > 1) {
            Row(
                modifier = Modifier
                    .align(Alignment.BottomCenter)
                    .padding(bottom = 16.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                repeat(imageUrls.size) { index ->
                    val isSelected = pagerState.currentPage == index
                    Box(
                        modifier = Modifier
                            .size(if (isSelected) 10.dp else 8.dp)
                            .background(
                                color = if (isSelected) 
                                    Color.White 
                                else 
                                    Color.White.copy(alpha = 0.5f),
                                shape = CircleShape
                            )
                    )
                }
            }
        }
    }
}
