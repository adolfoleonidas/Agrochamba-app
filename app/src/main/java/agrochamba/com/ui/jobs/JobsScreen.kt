package agrochamba.com.ui.jobs

import androidx.compose.foundation.background
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
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.itemsIndexed
import com.google.accompanist.swiperefresh.SwipeRefresh
import com.google.accompanist.swiperefresh.rememberSwipeRefreshState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Agriculture
import androidx.compose.material.icons.filled.AttachMoney
import androidx.compose.material.icons.filled.Bookmark
import androidx.compose.material.icons.filled.BookmarkBorder
import androidx.compose.material.icons.filled.Business
import androidx.compose.material.icons.filled.DateRange
import androidx.compose.material.icons.filled.DirectionsBus
import androidx.compose.material.icons.filled.Favorite
import androidx.compose.material.icons.filled.FavoriteBorder
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Restaurant
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Tune
import androidx.compose.material.icons.filled.Work
import androidx.compose.material.icons.filled.AccessTime
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.FilterChip
import androidx.compose.material3.FilterChipDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import agrochamba.com.data.Category
import agrochamba.com.data.JobPost
import agrochamba.com.data.WordPressApi
import coil.compose.AsyncImage
import coil.request.ImageRequest
import kotlinx.coroutines.delay
import java.text.SimpleDateFormat
import java.util.Locale
import androidx.compose.ui.graphics.ColorFilter
import androidx.compose.ui.res.painterResource
import agrochamba.com.R

fun getEmojiForCrop(cropName: String): String {
    return when {
        cropName.contains("Uva", ignoreCase = true) -> "🍇"
        cropName.contains("Arándano", ignoreCase = true) || cropName.contains("Arandano", ignoreCase = true) -> "🫐"
        cropName.contains("Palta", ignoreCase = true) -> "🥑"
        cropName.contains("Mango", ignoreCase = true) -> "🥭"
        cropName.contains("Fresa", ignoreCase = true) -> "🍓"
        else -> "🌱"
    }
}

/**
 * Composable para mostrar el logo de AgroChamba con un tinte gris
 * Se usa en estados vacíos y placeholders de imágenes
 */
@Composable
fun AgroChambaLogoPlaceholder(
    modifier: Modifier = Modifier,
    size: androidx.compose.ui.unit.Dp = 80.dp,
    alpha: Float = 0.5f
) {
    androidx.compose.foundation.Image(
        painter = painterResource(id = R.mipmap.ic_launcher_foreground),
        contentDescription = "Logo de AgroChamba",
        modifier = modifier.size(size),
        colorFilter = ColorFilter.tint(
            color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = alpha),
            blendMode = androidx.compose.ui.graphics.BlendMode.SrcIn
        )
    )
}

fun formatDate(dateString: String?): String {
    if (dateString.isNullOrBlank()) return ""
    try {
        val parser = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.getDefault())
        val date = parser.parse(dateString) ?: return ""
        val now = System.currentTimeMillis()
        val diff = now - date.time

        val seconds = diff / 1000
        val minutes = seconds / 60
        val hours = minutes / 60
        val days = hours / 24

        return when {
            days > 7 -> {
                val formatter = SimpleDateFormat("dd/MM/yyyy", Locale.getDefault())
                formatter.format(date)
            }
            days == 1L -> "Hace 1 día"
            days > 1L -> "Hace $days días"
            hours == 1L -> "Hace 1 hora"
            hours > 1L -> "Hace $hours horas"
            minutes == 1L -> "Hace 1 minuto"
            minutes > 1L -> "Hace $minutes minutos"
            else -> "Hace unos segundos"
        }
    } catch (e: Exception) {
        return dateString.substringBefore("T") // Fallback
    }
}

@Composable
fun JobsScreen(jobsViewModel: JobsViewModel = viewModel()) {
    val uiState = jobsViewModel.uiState

    if (uiState.selectedJob == null) {
        JobsListWithSearchScreen(jobsViewModel)
    } else {
        JobDetailScreen(
            job = uiState.selectedJob,
            mediaItems = uiState.selectedJobMedia, // Pasamos la lista de imágenes
            onNavigateUp = { jobsViewModel.onDetailScreenNavigated() },
            navController = null // No tenemos navController aquí, pero JobDetailScreen lo maneja opcionalmente
        )
    }
}

@Composable
fun JobsListWithSearchScreen(jobsViewModel: JobsViewModel) {
    val uiState = jobsViewModel.uiState
    Column(modifier = Modifier.fillMaxSize()) {
        SearchAndFilterPanel(uiState = uiState, onFilterChange = jobsViewModel::onFilterChange)

        when {
            uiState.isLoading -> LoadingScreen()
            uiState.isError -> ErrorScreen(
                onRetry = { jobsViewModel.retry() },
                errorMessage = uiState.errorMessage
            )
            else -> JobsListScreen(
                jobs = uiState.filteredJobs,
                onJobClicked = { job -> jobsViewModel.selectJob(job) },
                onLoadMore = { jobsViewModel.loadMoreJobs() },
                onRefresh = { jobsViewModel.refresh() },
                isRefreshing = uiState.isLoading,
                uiState = uiState,
                viewModel = jobsViewModel
            )
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SearchAndFilterPanel(
    uiState: JobsScreenState,
    onFilterChange: (String?, Category?, Category?, Category?, Category?) -> Unit
) {
    // Estado para controlar la visibilidad del modal de filtros avanzados
    var showAdvancedFiltersModal by remember { mutableStateOf(false) }
    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
    
    // Sincronizar estado local con el estado del ViewModel
    var searchQuery by remember { mutableStateOf(uiState.searchQuery) }
    var selectedLocation by remember { mutableStateOf<Category?>(uiState.selectedLocation) }
    var selectedCompany by remember { mutableStateOf<Category?>(uiState.selectedCompany) }
    var selectedJobType by remember { mutableStateOf<Category?>(uiState.selectedJobType) }
    var selectedCrop by remember { mutableStateOf<Category?>(uiState.selectedCrop) }

    // Sincronizar cuando cambia el estado del ViewModel
    LaunchedEffect(
        uiState.searchQuery, 
        uiState.selectedLocation, 
        uiState.selectedCompany,
        uiState.selectedJobType,
        uiState.selectedCrop
    ) {
        searchQuery = uiState.searchQuery
        selectedLocation = uiState.selectedLocation
        selectedCompany = uiState.selectedCompany
        selectedJobType = uiState.selectedJobType
        selectedCrop = uiState.selectedCrop
    }

    // Aplicar filtros con debounce para búsqueda
    LaunchedEffect(searchQuery, selectedLocation, selectedCompany, selectedJobType, selectedCrop) {
        // Debounce solo para cambios en la búsqueda (para evitar demasiadas llamadas mientras el usuario escribe)
        if (searchQuery != uiState.searchQuery) {
        delay(300)
        }
        onFilterChange(searchQuery, selectedLocation, selectedCompany, selectedJobType, selectedCrop)
    }

    Column(modifier = Modifier.padding(16.dp)) {
        OutlinedTextField(
            value = searchQuery,
            onValueChange = { searchQuery = it },
            label = { Text("Buscar trabajo") },
            modifier = Modifier.fillMaxWidth(),
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Buscar") },
            trailingIcon = {
                IconButton(
                    onClick = { showAdvancedFiltersModal = true }
                ) {
                    Icon(
                        Icons.Default.Tune,
                        contentDescription = "Filtros avanzados",
                        tint = if (selectedCompany != null || selectedJobType != null) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
        )

        Spacer(Modifier.height(8.dp))

        var isLocationExpanded by remember { mutableStateOf(false) }
        ExposedDropdownMenuBox(
            expanded = isLocationExpanded,
            onExpandedChange = { isLocationExpanded = !isLocationExpanded }
        ) {
            OutlinedTextField(
                value = selectedLocation?.name ?: "Todas las ubicaciones",
                onValueChange = {},
                readOnly = true,
                label = { Text("Ubicación") },
                leadingIcon = { Icon(Icons.Default.LocationOn, contentDescription = "Ubicación") },
                trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = isLocationExpanded) },
                modifier = Modifier.fillMaxWidth().menuAnchor()
            )
            ExposedDropdownMenu(expanded = isLocationExpanded, onDismissRequest = { isLocationExpanded = false }) {
                DropdownMenuItem(
                    text = { Text("Todas") },
                    onClick = { selectedLocation = null; isLocationExpanded = false; }
                )
                uiState.locationCategories.forEach { item ->
                    DropdownMenuItem(
                        text = { Text(item.name) },
                        onClick = { selectedLocation = item; isLocationExpanded = false; }
                    )
                }
            }
        }

        Spacer(Modifier.height(16.dp))

        // Filtro de Cultivo (chips horizontales deslizables) - Siempre visible
        Text("Cultivo", style = MaterialTheme.typography.labelMedium, modifier = Modifier.padding(bottom = 8.dp))
        LazyRow(
            horizontalArrangement = Arrangement.spacedBy(8.dp),
            modifier = Modifier.fillMaxWidth()
        ) {
            item {
                FilterChip(
                    selected = selectedCrop == null,
                    onClick = { selectedCrop = null },
                    label = { Text("Todos") },
                    leadingIcon = { Icon(Icons.Default.Agriculture, contentDescription = "Todos los cultivos", modifier = Modifier.size(18.dp)) }
                )
            }
            items(uiState.cropCategories) { crop ->
                FilterChip(
                    selected = selectedCrop?.id == crop.id,
                    onClick = { selectedCrop = crop },
                    label = { Text(crop.name) },
                    leadingIcon = { Text(getEmojiForCrop(crop.name)) }
                )
            }
        }
    }

    // Modal de filtros avanzados
    if (showAdvancedFiltersModal) {
        ModalBottomSheet(
            onDismissRequest = { showAdvancedFiltersModal = false },
            sheetState = sheetState
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp)
                    .padding(bottom = 32.dp)
            ) {
                // Título del modal
                Text(
                    text = "Filtros Avanzados",
                    style = MaterialTheme.typography.titleLarge,
                    modifier = Modifier.padding(bottom = 24.dp)
                )

                // Filtro de Empresa
                var isCompanyExpanded by remember { mutableStateOf(false) }
                ExposedDropdownMenuBox(
                    expanded = isCompanyExpanded,
                    onExpandedChange = { isCompanyExpanded = !isCompanyExpanded }
                ) {
                    OutlinedTextField(
                        value = selectedCompany?.name ?: "Todas las empresas",
                        onValueChange = {},
                        readOnly = true,
                        label = { Text("Empresa") },
                        leadingIcon = { Icon(Icons.Default.Business, contentDescription = "Empresa") },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = isCompanyExpanded) },
                        modifier = Modifier.fillMaxWidth().menuAnchor()
                    )
                    ExposedDropdownMenu(expanded = isCompanyExpanded, onDismissRequest = { isCompanyExpanded = false }) {
                        DropdownMenuItem(
                            text = { Text("Todas") },
                            onClick = { selectedCompany = null; isCompanyExpanded = false; }
                        )
                        uiState.companyCategories.forEach { item ->
                            DropdownMenuItem(
                                text = { Text(item.name) },
                                onClick = { selectedCompany = item; isCompanyExpanded = false; }
                            )
                        }
                    }
                }

                Spacer(Modifier.height(16.dp))

                // Filtro de Tipo de Puesto
                var isJobTypeExpanded by remember { mutableStateOf(false) }
                ExposedDropdownMenuBox(
                    expanded = isJobTypeExpanded,
                    onExpandedChange = { isJobTypeExpanded = !isJobTypeExpanded }
                ) {
                    OutlinedTextField(
                        value = selectedJobType?.name ?: "Todos los tipos de puesto",
                        onValueChange = {},
                        readOnly = true,
                        label = { Text("Tipo de Puesto") },
                        leadingIcon = { Icon(Icons.Default.Work, contentDescription = "Tipo de Puesto") },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = isJobTypeExpanded) },
                        modifier = Modifier.fillMaxWidth().menuAnchor()
                    )
                    ExposedDropdownMenu(expanded = isJobTypeExpanded, onDismissRequest = { isJobTypeExpanded = false }) {
                        DropdownMenuItem(
                            text = { Text("Todos") },
                            onClick = { selectedJobType = null; isJobTypeExpanded = false; }
                        )
                        uiState.jobTypeCategories.forEach { item ->
                            DropdownMenuItem(
                                text = { Text(item.name) },
                                onClick = { selectedJobType = item; isJobTypeExpanded = false; }
                            )
                        }
                    }
                }

                Spacer(Modifier.height(24.dp))

                // Botón para aplicar/cerrar
                Button(
                    onClick = { showAdvancedFiltersModal = false },
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text("Aplicar Filtros")
                }
            }
        }
    }
}


@Composable
fun LoadingScreen() {
    Column(modifier = Modifier.fillMaxSize(), verticalArrangement = Arrangement.Center, horizontalAlignment = Alignment.CenterHorizontally) {
        CircularProgressIndicator()
    }
}

@Composable
fun ErrorScreen(onRetry: () -> Unit, errorMessage: String?) {
    Column(modifier = Modifier.fillMaxSize(), verticalArrangement = Arrangement.Center, horizontalAlignment = Alignment.CenterHorizontally) {
        Text("Error al cargar los trabajos.", textAlign = TextAlign.Center)
        Spacer(modifier = Modifier.height(8.dp))
        if (errorMessage != null) {
            Text(
                text = "Detalle: $errorMessage",
                style = MaterialTheme.typography.bodySmall,
                textAlign = TextAlign.Center,
                color = Color.Gray
            )
        }
        Spacer(modifier = Modifier.height(16.dp))
        Button(onClick = onRetry) {
            Text("Reintentar")
        }
    }
}

@Composable
fun JobsListScreen(
    jobs: List<JobPost>,
    onJobClicked: (JobPost) -> Unit,
    onLoadMore: () -> Unit,
    onRefresh: () -> Unit,
    isRefreshing: Boolean,
    uiState: JobsScreenState,
    viewModel: JobsViewModel
) {
    val swipeRefreshState = rememberSwipeRefreshState(isRefreshing = isRefreshing)
    
    SwipeRefresh(
        state = swipeRefreshState,
        onRefresh = onRefresh
    ) {
        if (jobs.isEmpty() && !uiState.isLoading) {
            EmptyStateScreen()
            return@SwipeRefresh
        }
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            itemsIndexed(jobs) { index, job ->
                if (index == jobs.size - 1 && uiState.canLoadMore && !uiState.isLoadingMore) {
                    LaunchedEffect(Unit) { onLoadMore() }
                }
                JobCard(
                    job = job,
                    onClick = { onJobClicked(job) },
                    viewModel = viewModel
                )
            }

            if (uiState.isLoadingMore) {
                item {
                    Row(modifier = Modifier.fillMaxWidth().padding(8.dp), horizontalArrangement = Arrangement.Center) {
                        CircularProgressIndicator()
                    }
                }
            }
        }
    }
}

@Composable
fun JobCard(job: JobPost, onClick: () -> Unit, viewModel: JobsViewModel) {
    val terms = job.embedded?.terms?.flatten() ?: emptyList()
    val companyName = terms.find { it.taxonomy == "empresa" }?.name
    val locationName = terms.find { it.taxonomy == "ubicacion" }?.name
    val cropName = terms.find { it.taxonomy == "cultivo" }?.name
    
    // Obtener imagen desde featuredMedia embebido o desde el mapa de imágenes cargadas
    val jobImageUrls by viewModel.jobImageUrls
    val imageUrl = remember(job.id, job.embedded?.featuredMedia, jobImageUrls) {
        // Primero intentar desde featuredMedia embebido
        val featuredUrl = job.embedded?.featuredMedia?.firstOrNull()?.getImageUrl()
        
        // Si no hay imagen destacada, buscar en el mapa de imágenes cargadas desde gallery_ids
        featuredUrl ?: jobImageUrls[job.id]
    }
    
    // Cargar estado inicial de favoritos/guardados
    LaunchedEffect(job.id) {
        viewModel.loadFavoriteSavedStatus(job.id)
    }
    
    val publicationDate = formatDate(job.date)
    
    // Observar cambios en el estado de favoritos/guardados
    // Leer directamente del estado observable del ViewModel
    val favoriteStatusMap by viewModel.favoriteStatus
    val savedStatusMap by viewModel.savedStatus
    
    val isFavorite = favoriteStatusMap[job.id] ?: false
    val isSaved = savedStatusMap[job.id] ?: false
    
    // Usamos el excerpt para la descripción corta
    val shortDescription = remember(job.excerpt) {
        job.excerpt?.rendered?.htmlToString()
    }
    
    // Calcular salario
    val salario = remember(job.meta) {
        when {
            !job.meta?.salarioMin.isNullOrBlank() && !job.meta?.salarioMax.isNullOrBlank() -> 
                "S/ ${job.meta?.salarioMin} - S/ ${job.meta?.salarioMax}"
            !job.meta?.salarioMin.isNullOrBlank() -> "S/ ${job.meta?.salarioMin}+"
            else -> null
        }
    }
    
    // Tipo de puesto (duración) - excluir "operario"
    val tipoPuesto = terms.find { it.taxonomy == "tipo_puesto" }?.name?.let { name ->
        val nameLower = name.lowercase()
        if (nameLower.contains("operario")) null else name
    }

    // Beneficios
    val beneficios = remember(job.meta) {
        mutableListOf<String>().apply {
            if (job.meta?.alojamiento == true) add("Alojamiento incluido")
            if (job.meta?.transporte == true) add("Transporte")
            if (job.meta?.alimentacion == true) add("Comidas")
        }
    }
    
    // Determinar si tiene imagen válida
    val hasImage = !imageUrl.isNullOrBlank()

    Card(
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        shape = RoundedCornerShape(16.dp),
        modifier = Modifier
            .fillMaxWidth()
            .height(if (hasImage) 320.dp else 180.dp) // Más grande con imagen, más pequeña sin imagen
            .clickable(onClick = onClick)
    ) {
        if (hasImage) {
            // DISEÑO CON IMAGEN: Imagen arriba, contenido abajo
        Column(modifier = Modifier.fillMaxSize()) {
            // Imagen destacada en la parte superior
                Box(modifier = Modifier.fillMaxWidth().height(140.dp)) {
                    var imageLoadError by remember(job.id, imageUrl) { mutableStateOf(false) }
                    
                    if (imageLoadError) {
                        Box(
                            modifier = Modifier
                                .fillMaxSize()
                                .background(
                                    Brush.verticalGradient(
                                        colors = listOf(
                                            MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.3f),
                                            MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                                        )
                                    ),
                                    RoundedCornerShape(topStart = 16.dp, topEnd = 16.dp)
                                ),
                            contentAlignment = Alignment.Center
                        ) {
                            AgroChambaLogoPlaceholder(size = 48.dp, alpha = 0.5f)
                        }
                    } else {
                    AsyncImage(
                            model = ImageRequest.Builder(LocalContext.current)
                                .data(imageUrl)
                                .crossfade(true)
                                .build(),
                        contentDescription = "Imagen del trabajo",
                        modifier = Modifier
                                .fillMaxSize()
                            .clip(RoundedCornerShape(topStart = 16.dp, topEnd = 16.dp)),
                            contentScale = ContentScale.Crop,
                            onError = {
                                android.util.Log.e("JobCard", "Error loading image: $imageUrl")
                                imageLoadError = true
                            },
                            onSuccess = { imageLoadError = false }
                        )
                    }
                    
                    // Tag "Destacado" en la esquina superior derecha
                    Box(
                        modifier = Modifier
                            .align(Alignment.TopEnd)
                            .padding(8.dp)
                            .background(
                                Color(0xFFFFE0B2),
                                RoundedCornerShape(12.dp)
                            )
                            .padding(horizontal = 8.dp, vertical = 4.dp)
                    ) {
                        Text(
                            text = "Destacado",
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFFE65100)
                        )
                    }
                    
                    // Iconos de favorito/guardar en la esquina superior izquierda
                    Row(
                        modifier = Modifier
                            .align(Alignment.TopStart)
                            .padding(8.dp),
                        horizontalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        IconButton(
                            onClick = { viewModel.toggleFavorite(job.id) },
                            modifier = Modifier.size(32.dp)
                        ) {
                            Icon(
                                if (isFavorite) Icons.Default.Favorite else Icons.Default.FavoriteBorder,
                                contentDescription = "Favorito",
                                modifier = Modifier.size(18.dp),
                                tint = if (isFavorite) Color(0xFFE91E63) else Color.White.copy(alpha = 0.8f)
                            )
                        }
                        IconButton(
                            onClick = { viewModel.toggleSaved(job.id) },
                            modifier = Modifier.size(32.dp)
                        ) {
                            Icon(
                                if (isSaved) Icons.Default.Bookmark else Icons.Default.BookmarkBorder,
                                contentDescription = "Guardar",
                                modifier = Modifier.size(18.dp),
                                tint = if (isSaved) Color(0xFF2196F3) else Color.White.copy(alpha = 0.8f)
                        )
                    }
                }
            }
            
            // Contenido de texto abajo
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                        .padding(12.dp)
                    .weight(1f)
            ) {
                    // Título
                Text(
                    text = job.title?.rendered?.htmlToString() ?: "Título no disponible",
                        style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.Bold,
                        maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                        color = Color(0xFF1B5E20)
                )
                
                Spacer(modifier = Modifier.height(4.dp))
                
                // Nombre de la empresa
                if (companyName != null) {
                    Text(
                        text = companyName.htmlToString(),
                            style = MaterialTheme.typography.bodySmall,
                            color = Color(0xFF616161),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                        Spacer(modifier = Modifier.height(6.dp))
                    }
                    
                    // Descripción/Excerpt
                    if (shortDescription != null) {
                        Text(
                            text = shortDescription,
                            style = MaterialTheme.typography.bodySmall,
                            maxLines = 2,
                            overflow = TextOverflow.Ellipsis,
                            color = Color(0xFF616161)
                        )
                    Spacer(modifier = Modifier.height(8.dp))
                    }
                    
                    // Información en fila: Ubicación, Salario, Duración
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        if (locationName != null) {
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(4.dp)
                            ) {
                                Icon(
                                    Icons.Default.LocationOn,
                                    contentDescription = null,
                                    modifier = Modifier.size(14.dp),
                                    tint = Color(0xFF2E7D32)
                                )
                                Text(
                                    text = locationName.htmlToString(),
                                    style = MaterialTheme.typography.bodySmall,
                                    color = Color(0xFF424242),
                                    maxLines = 1,
                                    overflow = TextOverflow.Ellipsis
                                )
                            }
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
                                    tint = Color(0xFF2E7D32)
                                )
                                Text(
                                    text = salario,
                                    style = MaterialTheme.typography.bodySmall,
                                    fontWeight = FontWeight.Bold,
                                    color = Color(0xFF424242),
                                    maxLines = 1,
                                    overflow = TextOverflow.Ellipsis
                                )
                            }
                        }
                        
                        if (tipoPuesto != null) {
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(4.dp)
                            ) {
                                Icon(
                                    Icons.Default.AccessTime,
                                    contentDescription = null,
                                    modifier = Modifier.size(14.dp),
                                    tint = Color(0xFF2E7D32)
                                )
                                Text(
                                    text = tipoPuesto.htmlToString(),
                                    style = MaterialTheme.typography.bodySmall,
                                    color = Color(0xFF424242),
                                    maxLines = 1,
                                    overflow = TextOverflow.Ellipsis
                                )
                            }
                        }
                    }
                    
                    // Tags de beneficios
                    if (beneficios.isNotEmpty()) {
                        Spacer(modifier = Modifier.height(8.dp))
                        LazyRow(
                            horizontalArrangement = Arrangement.spacedBy(6.dp)
                        ) {
                            items(beneficios) { beneficio ->
                                Row(
                                    modifier = Modifier
                                        .background(
                                            Color(0xFFE8F5E9),
                                            RoundedCornerShape(8.dp)
                                        )
                                        .padding(horizontal = 8.dp, vertical = 4.dp),
                                    verticalAlignment = Alignment.CenterVertically,
                                    horizontalArrangement = Arrangement.spacedBy(4.dp)
                                ) {
                                    Icon(
                                        Icons.Default.CheckCircle,
                                        contentDescription = null,
                                        modifier = Modifier.size(12.dp),
                                        tint = Color(0xFF2E7D32)
                                    )
                                    Text(
                                        text = beneficio,
                                        style = MaterialTheme.typography.labelSmall,
                                        color = Color(0xFF2E7D32)
                                    )
                                }
                            }
                        }
                    }
                    
                    Spacer(modifier = Modifier.weight(1f))
                    
                    // Fila inferior: fecha
                    if (publicationDate.isNotBlank()) {
                        Text(
                            text = publicationDate,
                            style = MaterialTheme.typography.bodySmall,
                            color = Color(0xFF9E9E9E)
                        )
                    }
                }
            }
        } else {
            // DISEÑO SIN IMAGEN: Solo contenido
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(12.dp)
            ) {
                // Título y tag destacado
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.Top
                ) {
                    Text(
                        text = job.title?.rendered?.htmlToString() ?: "Título no disponible",
                        style = MaterialTheme.typography.titleSmall,
                        fontWeight = FontWeight.Bold,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                        color = Color(0xFF1B5E20),
                        modifier = Modifier.weight(1f)
                    )
                    
                    Box(
                        modifier = Modifier
                            .background(
                                Color(0xFFFFE0B2),
                                RoundedCornerShape(12.dp)
                            )
                            .padding(horizontal = 8.dp, vertical = 4.dp)
                    ) {
                        Text(
                            text = "Destacado",
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFFE65100)
                        )
                    }
                }
                
                Spacer(modifier = Modifier.height(4.dp))
                
                // Nombre de la empresa
                if (companyName != null) {
                    Text(
                        text = companyName.htmlToString(),
                        style = MaterialTheme.typography.bodySmall,
                        color = Color(0xFF616161),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    Spacer(modifier = Modifier.height(6.dp))
                }
                
                // Descripción
                if (shortDescription != null) {
                    Text(
                        text = shortDescription,
                        style = MaterialTheme.typography.bodySmall,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis,
                        color = Color(0xFF616161)
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                }
                
                // Información: Ubicación, Salario, Duración (en fila para ahorrar espacio)
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    if (locationName != null) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(4.dp)
                        ) {
                            Icon(
                                Icons.Default.LocationOn,
                                contentDescription = null,
                                modifier = Modifier.size(14.dp),
                                tint = Color(0xFF2E7D32)
                            )
                            Text(
                                text = locationName.htmlToString(),
                                style = MaterialTheme.typography.bodySmall,
                                color = Color(0xFF424242),
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis
                            )
                        }
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
                                tint = Color(0xFF2E7D32)
                            )
                            Text(
                                text = salario,
                                style = MaterialTheme.typography.bodySmall,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFF424242),
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis
                            )
                        }
                    }
                    
                    if (tipoPuesto != null) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(4.dp)
                        ) {
                            Icon(
                                Icons.Default.AccessTime,
                                contentDescription = null,
                                modifier = Modifier.size(14.dp),
                                tint = Color(0xFF2E7D32)
                            )
                            Text(
                                text = tipoPuesto.htmlToString(),
                                style = MaterialTheme.typography.bodySmall,
                                color = Color(0xFF424242),
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis
                            )
                        }
                    }
                }
                
                // Tags de beneficios (más compactos)
                if (beneficios.isNotEmpty()) {
                    Spacer(modifier = Modifier.height(6.dp))
                    LazyRow(
                        horizontalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        items(beneficios) { beneficio ->
                            Row(
                                modifier = Modifier
                                    .background(
                                        Color(0xFFE8F5E9),
                                        RoundedCornerShape(6.dp)
                                    )
                                    .padding(horizontal = 6.dp, vertical = 3.dp),
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(3.dp)
                            ) {
                                Icon(
                                    Icons.Default.CheckCircle,
                                    contentDescription = null,
                                    modifier = Modifier.size(10.dp),
                                    tint = Color(0xFF2E7D32)
                                )
                                Text(
                                    text = beneficio,
                                    style = MaterialTheme.typography.labelSmall,
                                    color = Color(0xFF2E7D32),
                                    fontSize = 10.sp
                                )
                            }
                        }
                    }
                }
                
                Spacer(modifier = Modifier.height(6.dp))
                
                // Fila inferior: fecha e iconos
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    if (publicationDate.isNotBlank()) {
                        Text(
                            text = publicationDate,
                            style = MaterialTheme.typography.bodySmall,
                            color = Color(0xFF9E9E9E)
                        )
                    } else {
                        Spacer(modifier = Modifier.width(1.dp))
                    }
                    
                    Row(
                        horizontalArrangement = Arrangement.spacedBy(4.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        IconButton(
                            onClick = { viewModel.toggleFavorite(job.id) },
                            modifier = Modifier.size(32.dp)
                        ) {
                            Icon(
                                if (isFavorite) Icons.Default.Favorite else Icons.Default.FavoriteBorder,
                                contentDescription = "Favorito",
                                modifier = Modifier.size(18.dp),
                                tint = if (isFavorite) Color(0xFFE91E63) else Color(0xFF9E9E9E)
                            )
                        }
                        IconButton(
                            onClick = { viewModel.toggleSaved(job.id) },
                            modifier = Modifier.size(32.dp)
                        ) {
                            Icon(
                                if (isSaved) Icons.Default.Bookmark else Icons.Default.BookmarkBorder,
                                contentDescription = "Guardar",
                                modifier = Modifier.size(18.dp),
                                tint = if (isSaved) Color(0xFF2196F3) else Color(0xFF9E9E9E)
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun InfoChip(icon: ImageVector, text: String, modifier: Modifier = Modifier) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = modifier
            .background(
                MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                RoundedCornerShape(6.dp)
            )
            .padding(horizontal = 8.dp, vertical = 4.dp)
    ) {
        Icon(
            imageVector = icon,
            contentDescription = null,
            tint = MaterialTheme.colorScheme.primary,
            modifier = Modifier.size(16.dp)
        )
        Spacer(modifier = Modifier.width(6.dp))
        Text(
            text = text,
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis
        )
    }
}

@Composable
private fun EmptyStateScreen() {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(32.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        // Logo de marca en lugar de ícono genérico
        AgroChambaLogoPlaceholder(
            size = 80.dp,
            alpha = 0.5f
        )
        Spacer(modifier = Modifier.height(24.dp))
        Text(
            text = "No se encontraron trabajos",
            style = MaterialTheme.typography.headlineSmall,
            fontWeight = FontWeight.Bold,
            textAlign = TextAlign.Center
        )
        Spacer(modifier = Modifier.height(8.dp))
        Text(
            text = "Intenta ajustar los filtros de búsqueda o vuelve más tarde",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            textAlign = TextAlign.Center
        )
    }
}
