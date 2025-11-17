package agrochamba.com.ui.jobs

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import agrochamba.com.data.AppDataHolder
import agrochamba.com.data.CompanyJob
import agrochamba.com.data.CompanyProfileWithJobsResponse
import agrochamba.com.data.JobPost
import agrochamba.com.data.WordPressApi
import agrochamba.com.utils.htmlToString
import kotlinx.coroutines.launch
import coil.compose.AsyncImage
import coil.request.ImageRequest

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CompanyProfileScreen(
    companyName: String,
    navController: NavController
) {
    val context = LocalContext.current
    var profileData by remember { mutableStateOf<CompanyProfileWithJobsResponse?>(null) }
    var isLoading by remember { mutableStateOf(true) }
    var error by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(companyName) {
        isLoading = true
        error = null
        try {
            profileData = WordPressApi.retrofitService.getCompanyProfileWithJobs(companyName)
        } catch (e: Exception) {
            error = "No se pudo cargar el perfil de la empresa: ${e.message}"
        } finally {
            isLoading = false
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Perfil de Empresa") },
                navigationIcon = {
                    IconButton(onClick = { navController.popBackStack() }) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Volver")
                    }
                }
            )
        }
    ) { paddingValues ->
        if (isLoading) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator()
            }
        } else if (error != null) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentAlignment = Alignment.Center
            ) {
                Column(
                    horizontalAlignment = Alignment.CenterHorizontally,
                    modifier = Modifier.padding(16.dp)
                ) {
                    Icon(
                        Icons.Default.Error,
                        contentDescription = null,
                        modifier = Modifier.size(64.dp),
                        tint = MaterialTheme.colorScheme.error
                    )
                    Spacer(modifier = Modifier.height(16.dp))
                    Text(
                        text = error ?: "Error desconocido",
                        color = MaterialTheme.colorScheme.error,
                        style = MaterialTheme.typography.bodyLarge
                    )
                }
            }
        } else if (profileData != null) {
            CompanyProfileContent(
                profileData = profileData!!,
                navController = navController,
                modifier = Modifier.padding(paddingValues)
            )
        }
    }
}

@Composable
private fun CompanyProfileContent(
    profileData: CompanyProfileWithJobsResponse,
    navController: NavController,
    modifier: Modifier = Modifier
) {
    val company = profileData.company
    val jobs = profileData.jobs
    val context = LocalContext.current

    LazyColumn(
        modifier = modifier.fillMaxSize(),
        contentPadding = PaddingValues(horizontal = 20.dp, vertical = 16.dp),
        verticalArrangement = Arrangement.spacedBy(24.dp)
    ) {
        // Header con foto y nombre
        item {
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                modifier = Modifier.fillMaxWidth()
            ) {
                // Foto de perfil
                Box(
                    modifier = Modifier
                        .size(120.dp)
                        .clip(RoundedCornerShape(60.dp)),
                    contentAlignment = Alignment.Center
                ) {
                    if (company.profilePhotoUrl != null) {
                        AsyncImage(
                            model = ImageRequest.Builder(LocalContext.current)
                                .data(company.profilePhotoUrl)
                                .crossfade(true)
                                .build(),
                            contentDescription = "Foto de perfil de ${company.companyName}",
                            modifier = Modifier.fillMaxSize(),
                            contentScale = ContentScale.Crop
                        )
                    } else {
                        Icon(
                            Icons.Default.Business,
                            contentDescription = null,
                            modifier = Modifier.size(60.dp),
                            tint = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
                Spacer(modifier = Modifier.height(16.dp))
                Text(
                    text = company.companyName,
                    style = MaterialTheme.typography.headlineMedium,
                    fontWeight = FontWeight.Bold
                )
            }
        }

        // Descripción
        if (!company.description.isNullOrBlank()) {
            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.surfaceVariant
                    )
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Text(
                            text = "Acerca de la Empresa",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            modifier = Modifier.padding(bottom = 8.dp)
                        )
                        Text(
                            text = company.description,
                            style = MaterialTheme.typography.bodyMedium,
                            lineHeight = MaterialTheme.typography.bodyMedium.lineHeight * 1.5
                        )
                    }
                }
            }
        }

        // Información de contacto
        item {
            Card(
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(
                    modifier = Modifier.padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    Text(
                        text = "Información de Contacto",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier.padding(bottom = 4.dp)
                    )

                    if (!company.address.isNullOrBlank()) {
                        ContactInfoRow(
                            icon = Icons.Default.LocationOn,
                            label = "Dirección",
                            value = company.address
                        )
                    }

                    if (!company.phone.isNullOrBlank()) {
                        ContactInfoRow(
                            icon = Icons.Default.Phone,
                            label = "Teléfono",
                            value = company.phone
                        )
                    }

                    if (!company.email.isNullOrBlank()) {
                        ContactInfoRow(
                            icon = Icons.Default.Email,
                            label = "Email",
                            value = company.email
                        )
                    }

                    if (!company.website.isNullOrBlank()) {
                        ContactInfoRow(
                            icon = Icons.Default.Language,
                            label = "Sitio web",
                            value = company.website,
                            isClickable = true,
                            onClick = {
                                Intent(Intent.ACTION_VIEW, Uri.parse(company.website)).also {
                                    context.startActivity(it)
                                }
                            }
                        )
                    }

                    // Redes sociales
                    val socialLinks = listOfNotNull(
                        if (!company.facebook.isNullOrBlank()) "Facebook" to company.facebook else null,
                        if (!company.instagram.isNullOrBlank()) "Instagram" to company.instagram else null,
                        if (!company.linkedin.isNullOrBlank()) "LinkedIn" to company.linkedin else null,
                        if (!company.twitter.isNullOrBlank()) "Twitter" to company.twitter else null
                    )

                    if (socialLinks.isNotEmpty()) {
                        Spacer(modifier = Modifier.height(8.dp))
                        Text(
                            text = "Redes Sociales",
                            style = MaterialTheme.typography.labelLarge,
                            fontWeight = FontWeight.Bold
                        )
                        Row(
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                            modifier = Modifier.padding(top = 4.dp)
                        ) {
                            socialLinks.forEach { (name, url) ->
                                FilterChip(
                                    selected = false,
                                    onClick = {
                                        Intent(Intent.ACTION_VIEW, Uri.parse(url)).also {
                                            context.startActivity(it)
                                        }
                                    },
                                    label = { Text(name) }
                                )
                            }
                        }
                    }
                }
            }
        }

        // Trabajos activos
        item {
            Text(
                text = "Ofertas Laborales Activas (${jobs.size})",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold
            )
        }

        if (jobs.isEmpty()) {
            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.surfaceVariant
                    )
                ) {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(32.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            Icon(
                                Icons.Default.WorkOff,
                                contentDescription = null,
                                modifier = Modifier.size(48.dp),
                                tint = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                            Spacer(modifier = Modifier.height(16.dp))
                            Text(
                                text = "No hay ofertas laborales activas",
                                style = MaterialTheme.typography.bodyLarge,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }
            }
        } else {
            items(jobs) { job ->
                val scope = rememberCoroutineScope()
                CompanyJobCard(
                    job = job,
                    onClick = {
                        // Cargar el trabajo completo desde la API y navegar al detalle
                        scope.launch {
                            try {
                                val jobsList = WordPressApi.retrofitService.getJobs(page = 1, perPage = 100)
                                val fullJob = jobsList.find { it.id == job.id }
                                if (fullJob != null) {
                                    AppDataHolder.selectedJob = fullJob
                                    navController.navigate("my_job_detail")
                                }
                            } catch (e: Exception) {
                                // Si falla, simplemente no navegamos
                            }
                        }
                    }
                )
            }
        }
    }
}

@Composable
private fun ContactInfoRow(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    label: String,
    value: String,
    isClickable: Boolean = false,
    onClick: (() -> Unit)? = null
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .then(
                if (isClickable && onClick != null) {
                    Modifier.clickable(onClick = onClick)
                } else {
                    Modifier
                }
            ),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        Icon(
            icon,
            contentDescription = null,
            modifier = Modifier.size(20.dp),
            tint = MaterialTheme.colorScheme.primary
        )
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = label,
                style = MaterialTheme.typography.labelMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Text(
                text = value,
                style = MaterialTheme.typography.bodyMedium,
                color = if (isClickable) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurface
            )
        }
    }
}

@Composable
private fun CompanyJobCard(
    job: CompanyJob,
    onClick: () -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            // Imagen
            if (job.featuredImageUrl != null) {
                AsyncImage(
                    model = ImageRequest.Builder(LocalContext.current)
                        .data(job.featuredImageUrl)
                        .crossfade(true)
                        .build(),
                    contentDescription = null,
                    modifier = Modifier
                        .size(80.dp)
                        .clip(RoundedCornerShape(8.dp)),
                    contentScale = ContentScale.Crop
                )
            }

            // Información
            Column(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                Text(
                    text = job.title?.rendered?.htmlToString() ?: "Sin título",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    maxLines = 2
                )

                if (job.ubicacion != null) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        Icon(
                            Icons.Default.LocationOn,
                            contentDescription = null,
                            modifier = Modifier.size(14.dp),
                            tint = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                        Text(
                            text = job.ubicacion,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }

                val salario = when {
                    !job.salarioMin.isNullOrBlank() && !job.salarioMax.isNullOrBlank() ->
                        "S/ ${job.salarioMin} - S/ ${job.salarioMax}"
                    !job.salarioMin.isNullOrBlank() -> "S/ ${job.salarioMin}+"
                    else -> null
                }

                if (salario != null) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        Icon(
                            Icons.Default.AttachMoney,
                            contentDescription = null,
                            modifier = Modifier.size(14.dp),
                            tint = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                        Text(
                            text = salario,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }
        }
    }
}

