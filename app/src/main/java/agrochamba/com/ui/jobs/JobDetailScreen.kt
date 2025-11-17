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
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.remember
import androidx.lifecycle.viewmodel.compose.viewModel
import agrochamba.com.data.CompanyProfileResponse
import agrochamba.com.data.WordPressApi
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
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import agrochamba.com.data.JobPost
import agrochamba.com.data.MediaItem
import agrochamba.com.utils.htmlToString
import coil.compose.AsyncImage
import coil.request.ImageRequest
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
    var fullscreenImageUrl by remember { mutableStateOf<String?>(null) }

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
            onImageClick = { fullscreenImageUrl = it },
            navController = navController,
            modifier = modifier.padding(innerPadding)
        )
    }

    if (fullscreenImageUrl != null) {
        Dialog(
            onDismissRequest = { fullscreenImageUrl = null },
            properties = DialogProperties(usePlatformDefaultWidth = false)
        ) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .background(Color.Black.copy(alpha = 0.8f))
                    .clickable { fullscreenImageUrl = null },
                contentAlignment = Alignment.Center
            ) {
                AsyncImage(
                    model = ImageRequest.Builder(LocalContext.current).data(fullscreenImageUrl).crossfade(true).build(),
                    contentDescription = "Imagen a pantalla completa",
                    modifier = Modifier.fillMaxWidth(),
                    contentScale = ContentScale.Fit
                )
            }
        }
    }
}

@Composable
private fun JobDetailContent(
    job: JobPost,
    mediaItems: List<MediaItem>,
    onImageClick: (String) -> Unit,
    navController: NavController? = null,
    modifier: Modifier = Modifier
) {
    val scrollState = rememberScrollState()
    val context = LocalContext.current
    val terms = job.embedded?.terms?.flatten() ?: emptyList()
    val companyName = terms.find { it.taxonomy == "empresa" }?.name
    val locationName = terms.find { it.taxonomy == "ubicacion" }?.name
    
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
        // Obtener todas las URLs de imágenes disponibles
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
            
        if (allImageUrls.isNotEmpty()) {
            // SLIDER DE IMÁGENES
            ImageSlider(
                imageUrls = allImageUrls,
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

        Column(modifier = Modifier.padding(horizontal = 20.dp)) {
            Spacer(Modifier.height(24.dp))

            // --- TÍTULO Y EMPRESA ---
            Text(
                job.title?.rendered?.htmlToString() ?: "Sin título",
                style = MaterialTheme.typography.headlineLarge,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface
            )
            
            if (companyName != null) {
                Spacer(Modifier.height(8.dp))
                Text(
                    companyName.htmlToString(),
                    style = MaterialTheme.typography.titleMedium,
                    color = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.clickable {
                        // Navegar al perfil de la empresa
                        navController?.navigate("company_profile/${companyName.htmlToString()}")
                    }
                )
            }

            Spacer(Modifier.height(20.dp))

            // --- INFORMACIÓN PRINCIPAL EN FILA ---
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(16.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                if (locationName != null) {
                    SimpleInfoRow(icon = Icons.Default.LocationOn, text = locationName.htmlToString())
                }
                job.meta?.let {
                    val salario = when {
                        !it.salarioMin.isNullOrBlank() && !it.salarioMax.isNullOrBlank() -> "S/ ${it.salarioMin} - S/ ${it.salarioMax}"
                        !it.salarioMin.isNullOrBlank() -> "S/ ${it.salarioMin}+"
                        else -> null
                    }
                    if (salario != null) {
                        SimpleInfoRow(icon = Icons.Default.AttachMoney, text = salario)
                    }
                }
            }

            Spacer(Modifier.height(32.dp))

            // --- DESCRIPCIÓN ---
            job.content?.rendered?.let {
                SimpleSectionTitle("Descripción del Trabajo")
                Spacer(Modifier.height(12.dp))
                Text(
                    it.htmlToString(),
                    style = MaterialTheme.typography.bodyLarge,
                    lineHeight = MaterialTheme.typography.bodyLarge.lineHeight * 1.6,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Spacer(Modifier.height(32.dp))
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

            // --- INFORMACIÓN DE LA EMPRESA ---
            if (companyName != null && companyProfile != null) {
                Divider(modifier = Modifier.padding(vertical = 16.dp))
                SimpleSectionTitle("Acerca de la Empresa")
                Spacer(Modifier.height(12.dp))
                
                // Nombre de la empresa
                Text(
                    text = companyProfile!!.companyName,
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                
                // Descripción
                val companyDescription = companyProfile!!.description
                if (!companyDescription.isNullOrBlank()) {
                    Spacer(Modifier.height(12.dp))
                    Text(
                        text = companyDescription,
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        lineHeight = MaterialTheme.typography.bodyMedium.lineHeight * 1.5
                    )
                }
                
                Spacer(Modifier.height(16.dp))
                
                // Información de contacto
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    // Dirección
                    val companyAddress = companyProfile!!.address
                    if (!companyAddress.isNullOrBlank()) {
                        Row(
                            verticalAlignment = Alignment.Top,
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Icon(
                                Icons.Default.LocationOn,
                                contentDescription = null,
                                modifier = Modifier.size(20.dp),
                                tint = MaterialTheme.colorScheme.primary
                            )
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = "Dirección",
                                    style = MaterialTheme.typography.labelMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                                Text(
                                    text = companyAddress,
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onSurface
                                )
                            }
                        }
                    }
                    
                    // Teléfono
                    val companyPhone = companyProfile!!.phone
                    if (!companyPhone.isNullOrBlank()) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Icon(
                                Icons.Default.Phone,
                                contentDescription = null,
                                modifier = Modifier.size(20.dp),
                                tint = MaterialTheme.colorScheme.primary
                            )
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = "Teléfono",
                                    style = MaterialTheme.typography.labelMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                                Text(
                                    text = companyPhone,
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onSurface
                                )
                            }
                        }
                    }
                    
                    // Email
                    val companyEmail = companyProfile!!.email
                    if (!companyEmail.isNullOrBlank()) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Icon(
                                Icons.Default.Email,
                                contentDescription = null,
                                modifier = Modifier.size(20.dp),
                                tint = MaterialTheme.colorScheme.primary
                            )
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = "Email",
                                    style = MaterialTheme.typography.labelMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                                Text(
                                    text = companyEmail,
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onSurface
                                )
                            }
                        }
                    }
                    
                    // Sitio web
                    val companyWebsite = companyProfile!!.website
                    if (!companyWebsite.isNullOrBlank()) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Icon(
                                Icons.Default.Language,
                                contentDescription = null,
                                modifier = Modifier.size(20.dp),
                                tint = MaterialTheme.colorScheme.primary
                            )
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = "Sitio web",
                                    style = MaterialTheme.typography.labelMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                                Text(
                                    text = companyWebsite,
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.primary,
                                    modifier = Modifier.clickable {
                                        // Abrir en navegador
                                        Intent(Intent.ACTION_VIEW, Uri.parse(companyWebsite)).also {
                                            context.startActivity(it)
                                        }
                                    }
                                )
                            }
                        }
                    }
                    
                    // Redes sociales
                    val companyFacebook = companyProfile!!.facebook
                    val companyInstagram = companyProfile!!.instagram
                    val companyLinkedin = companyProfile!!.linkedin
                    val companyTwitter = companyProfile!!.twitter
                    val socialLinks = listOfNotNull(
                        if (!companyFacebook.isNullOrBlank()) "Facebook" to companyFacebook else null,
                        if (!companyInstagram.isNullOrBlank()) "Instagram" to companyInstagram else null,
                        if (!companyLinkedin.isNullOrBlank()) "LinkedIn" to companyLinkedin else null,
                        if (!companyTwitter.isNullOrBlank()) "Twitter" to companyTwitter else null
                    )
                    
                    if (socialLinks.isNotEmpty()) {
                        Spacer(Modifier.height(8.dp))
                        Text(
                            text = "Redes Sociales",
                            style = MaterialTheme.typography.labelMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.padding(bottom = 8.dp)
                        )
                        Row(
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            socialLinks.forEach { (name, url) ->
                                TextButton(
                                    onClick = {
                                        Intent(Intent.ACTION_VIEW, Uri.parse(url)).also {
                                            context.startActivity(it)
                                        }
                                    }
                                ) {
                                    Text(name)
                                }
                            }
                        }
                    }
                }
                
                Spacer(Modifier.height(32.dp))
            }

            Spacer(Modifier.height(32.dp)) // Espacio final
        }
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
            style = MaterialTheme.typography.titleLarge,
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
            tint = MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.size(18.dp)
        )
        Spacer(Modifier.width(6.dp))
        Text(
            text,
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
    }
}

@Composable
private fun RequirementItem(text: String) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 6.dp),
        verticalAlignment = Alignment.Top
    ) {
        Icon(
            Icons.Default.CheckCircle,
            contentDescription = null,
            tint = MaterialTheme.colorScheme.primary,
            modifier = Modifier.size(20.dp)
        )
        Spacer(Modifier.width(12.dp))
        Text(
            text = text,
            style = MaterialTheme.typography.bodyLarge,
            color = MaterialTheme.colorScheme.onSurface,
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
                RoundedCornerShape(8.dp)
            )
            .padding(12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(
            Icons.Default.CheckCircle,
            contentDescription = null,
            tint = MaterialTheme.colorScheme.primary,
            modifier = Modifier.size(20.dp)
        )
        Spacer(Modifier.width(12.dp))
        Icon(
            icon,
            contentDescription = null,
            tint = MaterialTheme.colorScheme.primary,
            modifier = Modifier.size(18.dp)
        )
        Spacer(Modifier.width(8.dp))
        Text(
            text = text,
            style = MaterialTheme.typography.bodyMedium,
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
            .padding(vertical = 8.dp)
    ) {
        Text(
            "$label: ",
            style = MaterialTheme.typography.bodyLarge,
            fontWeight = FontWeight.Medium,
            color = MaterialTheme.colorScheme.onSurface
        )
        Text(
            value,
            style = MaterialTheme.typography.bodyLarge,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
    }
}

@OptIn(ExperimentalFoundationApi::class)
@Composable
private fun ImageSlider(
    imageUrls: List<String>,
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
            var imageLoadError by remember(page) { mutableStateOf(false) }
            
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .clickable { onImageClick(imageUrl) }
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
