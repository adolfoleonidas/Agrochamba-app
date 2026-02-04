package agrochamba.com.ui.jobs

import agrochamba.com.utils.htmlToString

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
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
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
import androidx.compose.material.icons.filled.Close
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
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.runtime.collectAsState
import androidx.compose.ui.focus.onFocusChanged
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.foundation.layout.heightIn
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.expandVertically
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.shrinkVertically
import androidx.compose.material3.Divider
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material.icons.filled.Add
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
import agrochamba.com.data.AuthManager
import agrochamba.com.data.Category
import agrochamba.com.data.JobPost
import agrochamba.com.data.UserProfileResponse
import agrochamba.com.ui.common.ActiveFilter
import agrochamba.com.ui.common.CreateAlertBanner
import agrochamba.com.ui.common.FilterChipsBar
import agrochamba.com.ui.common.FilterType
import agrochamba.com.ui.common.NoResultsMessage
import agrochamba.com.ui.disponibilidad.DisponibilidadViewModel
import agrochamba.com.ui.home.AvisosOperativosSection
import agrochamba.com.ui.home.CategoriasSection
import agrochamba.com.ui.home.CategoriaJob
import agrochamba.com.ui.home.DisponibilidadBanner
import agrochamba.com.ui.home.EmpleosDestacadosSection
import agrochamba.com.ui.home.HomeHeader
import agrochamba.com.ui.home.HomeSearchBar
import agrochamba.com.ui.home.TipoAviso
import agrochamba.com.ui.home.AvisosViewModel
import agrochamba.com.ui.home.defaultCategorias
import coil.compose.AsyncImage
import coil.request.ImageRequest
import kotlinx.coroutines.delay
import java.text.SimpleDateFormat
import java.util.Locale
import androidx.compose.ui.graphics.ColorFilter
import androidx.compose.ui.res.painterResource
import agrochamba.com.R
import agrochamba.com.data.LocationSearchResult
import agrochamba.com.data.LocationType
import agrochamba.com.data.PeruLocations
import agrochamba.com.data.UbicacionCompleta
import agrochamba.com.data.repository.LocationRepository
import java.util.Date
import androidx.activity.compose.BackHandler

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
fun JobsScreen(
    jobsViewModel: JobsViewModel = viewModel(),
    userProfile: UserProfileResponse? = null,
    rendimientoScore: Int? = null,
    onNavigateToProfile: () -> Unit = {},
    onNavigateToNotifications: () -> Unit = {},
    onNavigateToRoutes: () -> Unit = {},
    onNavigateToRendimiento: () -> Unit = {},
    onNavigateToFotocheck: () -> Unit = {}
) {
    android.util.Log.d("JobsScreen", "📱 JobsScreen() INICIADO")

    val uiState = jobsViewModel.uiState
    android.util.Log.d("JobsScreen", "📊 Estado: isLoading=${uiState.isLoading}, jobs=${uiState.allJobs.size}, filtered=${uiState.filteredJobs.size}")

    // Manejar el botón atrás del sistema cuando se está viendo un trabajo
    // Si hay un trabajo seleccionado, volver a la lista en lugar de cerrar la app
    BackHandler(enabled = uiState.selectedJob != null) {
        jobsViewModel.onDetailScreenNavigated()
    }

    if (uiState.selectedJob == null) {
        android.util.Log.d("JobsScreen", "📋 Mostrando JobsListWithSearchScreen...")
        JobsListWithSearchScreen(
            jobsViewModel = jobsViewModel,
            userProfile = userProfile,
            rendimientoScore = rendimientoScore,
            onNavigateToProfile = onNavigateToProfile,
            onNavigateToNotifications = onNavigateToNotifications,
            onNavigateToRoutes = onNavigateToRoutes,
            onNavigateToRendimiento = onNavigateToRendimiento,
            onNavigateToFotocheck = onNavigateToFotocheck
        )
    } else {
        android.util.Log.d("JobsScreen", "📄 Mostrando JobDetailScreen para: ${uiState.selectedJob.id}")
        JobDetailScreen(
            job = uiState.selectedJob,
            mediaItems = uiState.selectedJobMedia,
            onNavigateUp = { jobsViewModel.onDetailScreenNavigated() },
            navController = null
        )
    }
}

@Composable
fun JobsListWithSearchScreen(
    jobsViewModel: JobsViewModel,
    userProfile: UserProfileResponse? = null,
    rendimientoScore: Int? = null,
    onNavigateToProfile: () -> Unit = {},
    onNavigateToNotifications: () -> Unit = {},
    onNavigateToRoutes: () -> Unit = {},
    onNavigateToRendimiento: () -> Unit = {},
    onNavigateToFotocheck: () -> Unit = {}
) {
    android.util.Log.d("JobsListWithSearchScreen", "🔍 JobsListWithSearchScreen() INICIADO")

    val uiState = jobsViewModel.uiState
    android.util.Log.d("JobsListWithSearchScreen", "📊 uiState obtenido: isLoading=${uiState.isLoading}")

    // ViewModel de disponibilidad (solo para trabajadores, no empresas)
    val isWorker = userProfile?.isEnterprise != true
    val disponibilidadViewModel: DisponibilidadViewModel = viewModel()
    val disponibilidadState = disponibilidadViewModel.uiState

    // ViewModel de avisos operativos
    val avisosViewModel: AvisosViewModel = viewModel()
    val avisosState = avisosViewModel.uiState

    // Estado para mostrar modal de filtros avanzados
    var showAdvancedFilters by remember { mutableStateOf(false) }

    // Estado para mostrar pantalla de búsqueda
    var showSearchScreen by remember { mutableStateOf(false) }

    // Determinar si hay filtros activos (para mostrar vista Home o vista filtrada)
    val hasActiveFilters = uiState.searchQuery.isNotBlank() ||
        uiState.selectedLocationFilter != null ||
        uiState.selectedLocation != null ||
        uiState.selectedCrop != null ||
        uiState.selectedJobType != null ||
        uiState.selectedCompany != null

    // Construir lista de filtros activos
    android.util.Log.d("JobsListWithSearchScreen", "🏗️ Construyendo filtros activos...")
    val activeFilters = remember(
        uiState.selectedLocationFilter,
        uiState.selectedLocation,
        uiState.selectedCrop,
        uiState.selectedJobType,
        uiState.selectedCompany
    ) {
        android.util.Log.d("JobsListWithSearchScreen", "🔧 Dentro de remember para activeFilters")
        mutableListOf<ActiveFilter>().apply {
            // Priorizar selectedLocationFilter sobre selectedLocation
            val locationLabel = uiState.selectedLocationFilter?.displayLabel
                ?: uiState.selectedLocation?.name
            val locationIcon = when (uiState.selectedLocationFilter?.tipo) {
                LocationType.DEPARTAMENTO -> "📍"
                LocationType.PROVINCIA -> "🏘️"
                LocationType.DISTRITO -> "📌"
                null -> "📍"
            }

            if (locationLabel != null) {
                add(ActiveFilter(
                    id = "location",
                    label = locationLabel,
                    icon = locationIcon,
                    type = FilterType.LOCATION
                ))
            }
            uiState.selectedCrop?.let {
                add(ActiveFilter(
                    id = "crop",
                    label = it.name,
                    icon = getEmojiForCrop(it.name),
                    type = FilterType.CROP
                ))
            }
            uiState.selectedJobType?.let {
                add(ActiveFilter(
                    id = "jobType",
                    label = it.name,
                    icon = "💼",
                    type = FilterType.JOB_TYPE
                ))
            }
            uiState.selectedCompany?.let {
                add(ActiveFilter(
                    id = "company",
                    label = it.name,
                    icon = "🏢",
                    type = FilterType.OTHER
                ))
            }
        }
    }

    // Obtener empleos destacados (los primeros 5 con imagen)
    val empleosDestacados = remember(uiState.allJobs) {
        uiState.allJobs
            .filter { job ->
                val hasImage = job.embedded?.featuredMedia?.firstOrNull()?.source_url != null ||
                    job.featuredImageUrl != null
                hasImage
            }
            .take(5)
    }

    // Pantalla de búsqueda
    if (showSearchScreen) {
        SearchScreen(
            initialQuery = uiState.searchQuery,
            initialLocation = uiState.selectedLocationFilter,
            onSearch = { query, location ->
                // Aplicar filtros y cerrar pantalla de búsqueda
                jobsViewModel.onFilterChange(
                    query,
                    uiState.selectedLocation,
                    uiState.selectedCompany,
                    uiState.selectedJobType,
                    uiState.selectedCrop
                )
                if (location != null) {
                    jobsViewModel.onLocationFilterChange(location)
                }
                showSearchScreen = false
            },
            onBack = { showSearchScreen = false }
        )
        return
    }

    // Fondo del tema (claro por defecto)
    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
    ) {
        if (!hasActiveFilters) {
            // ====================================================
            // VISTA HOME: Sin filtros activos
            // ====================================================
            LazyColumn(
                modifier = Modifier.fillMaxSize(),
                contentPadding = PaddingValues(bottom = 100.dp)
            ) {
                // Header con perfil y notificaciones
                item {
                    HomeHeader(
                        userProfile = userProfile,
                        rendimientoScore = rendimientoScore,
                        onNotificationClick = onNavigateToNotifications,
                        onProfileClick = onNavigateToProfile,
                        onRendimientoClick = onNavigateToRendimiento,
                        onFotocheckClick = onNavigateToFotocheck
                    )
                }

                // Banner de disponibilidad (solo para trabajadores)
                if (isWorker && !disponibilidadState.isLoading) {
                    item {
                        Spacer(modifier = Modifier.height(8.dp))
                        DisponibilidadBanner(
                            disponibleParaTrabajo = disponibilidadState.disponibleParaTrabajo,
                            tieneContratoActivo = disponibilidadState.tieneContratoActivo,
                            visibleParaEmpresas = disponibilidadState.visibleParaEmpresas,
                            ubicacion = disponibilidadState.ubicacion,
                            isLoading = disponibilidadState.isLoading,
                            onToggleDisponibilidad = { disponibilidadViewModel.toggleDisponibilidad() }
                        )
                    }
                }

                // Barra de búsqueda
                item {
                    Spacer(modifier = Modifier.height(8.dp))
                    HomeSearchBar(
                        searchQuery = uiState.searchQuery,
                        locationLabel = uiState.selectedLocationFilter?.displayLabel
                            ?: uiState.selectedLocation?.name,
                        onSearchClick = { showSearchScreen = true },
                        onFilterClick = { showAdvancedFilters = true }
                    )
                }

                // Avisos Operativos
                item {
                    val isAdminOrEmpresa = userProfile?.isEnterprise == true ||
                                           AuthManager.isUserAdmin()
                    AvisosOperativosSection(
                        avisos = avisosState.avisos,
                        onVerRutas = onNavigateToRoutes,
                        isAdminOrEmpresa = isAdminOrEmpresa,
                        onCrearAviso = { tipo, mensaje ->
                            avisosViewModel.createAviso(tipo, mensaje)
                        }
                    )
                }

                // Categorías
                item {
                    CategoriasSection(
                        categorias = defaultCategorias,
                        onCategoriaClick = { categoria ->
                            // Buscar la categoría correspondiente en jobTypeCategories o cropCategories
                            val matchingJobType = uiState.jobTypeCategories.find {
                                it.name.contains(categoria.nombre, ignoreCase = true)
                            }
                            val matchingCrop = uiState.cropCategories.find {
                                it.name.contains(categoria.nombre, ignoreCase = true)
                            }

                            if (matchingJobType != null) {
                                jobsViewModel.onFilterChange(
                                    uiState.searchQuery,
                                    uiState.selectedLocation,
                                    uiState.selectedCompany,
                                    matchingJobType,
                                    uiState.selectedCrop
                                )
                            } else if (matchingCrop != null) {
                                jobsViewModel.onFilterChange(
                                    uiState.searchQuery,
                                    uiState.selectedLocation,
                                    uiState.selectedCompany,
                                    uiState.selectedJobType,
                                    matchingCrop
                                )
                            } else {
                                // Buscar por nombre en el query
                                jobsViewModel.onFilterChange(
                                    categoria.nombre,
                                    uiState.selectedLocation,
                                    uiState.selectedCompany,
                                    uiState.selectedJobType,
                                    uiState.selectedCrop
                                )
                            }
                        },
                        onVerTodas = { /* Navegar a pantalla de categorías */ }
                    )
                }

                // Empleos Destacados (horizontal)
                item {
                    if (empleosDestacados.isNotEmpty()) {
                        EmpleosDestacadosSection(
                            empleos = empleosDestacados,
                            onEmpleoClick = { empleo -> jobsViewModel.selectJob(empleo) },
                            onPostular = { empleo -> jobsViewModel.selectJob(empleo) }
                        )
                    }
                }

                // Titulo "Todos los Empleos"
                item {
                    Spacer(modifier = Modifier.height(24.dp))
                    Text(
                        text = "Todos los Empleos",
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onBackground,
                        modifier = Modifier.padding(horizontal = 16.dp)
                    )
                    Spacer(modifier = Modifier.height(12.dp))
                }

                // Loading state
                if (uiState.isLoading) {
                    item {
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(32.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                        }
                    }
                } else if (uiState.isError) {
                    item {
                        ErrorScreen(
                            onRetry = { jobsViewModel.retry() },
                            errorMessage = uiState.errorMessage
                        )
                    }
                } else {
                    // Lista de todos los empleos
                    itemsIndexed(uiState.filteredJobs) { index, job ->
                        if (index == uiState.filteredJobs.size - 1 && uiState.canLoadMore && !uiState.isLoadingMore) {
                            LaunchedEffect(Unit) { jobsViewModel.loadMoreJobs() }
                        }
                        Box(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            SafeJobCard(
                                job = job,
                                onClick = { jobsViewModel.selectJob(job) },
                                viewModel = jobsViewModel
                            )
                        }
                    }

                    if (uiState.isLoadingMore) {
                        item {
                            Box(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(16.dp),
                                contentAlignment = Alignment.Center
                            ) {
                                CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                            }
                        }
                    }
                }
            }
        } else {
            // ====================================================
            // VISTA CON FILTROS: Mostrar resultados filtrados
            // ====================================================
            Column(modifier = Modifier.fillMaxSize()) {
                // Header simplificado con búsqueda
                HomeSearchBar(
                    searchQuery = uiState.searchQuery,
                    locationLabel = uiState.selectedLocationFilter?.displayLabel
                        ?: uiState.selectedLocation?.name,
                    onSearchClick = { showSearchScreen = true },
                    onFilterClick = { showAdvancedFilters = true },
                    modifier = Modifier.padding(top = 16.dp)
                )

                Spacer(modifier = Modifier.height(8.dp))

                // Contador de resultados y chips de filtros
                if (!uiState.isLoading && !uiState.isError) {
                    FilterChipsBar(
                        resultCount = uiState.filteredJobs.size,
                        filters = activeFilters,
                        onRemoveFilter = { filter ->
                            when (filter.id) {
                                "location" -> {
                                    jobsViewModel.onLocationFilterChange(null)
                                }
                                "crop" -> jobsViewModel.onFilterChange(
                                    uiState.searchQuery, uiState.selectedLocation, uiState.selectedCompany,
                                    uiState.selectedJobType, null
                                )
                                "jobType" -> jobsViewModel.onFilterChange(
                                    uiState.searchQuery, uiState.selectedLocation, uiState.selectedCompany,
                                    null, uiState.selectedCrop
                                )
                                "company" -> jobsViewModel.onFilterChange(
                                    uiState.searchQuery, uiState.selectedLocation, null,
                                    uiState.selectedJobType, uiState.selectedCrop
                                )
                            }
                        },
                        onClearAll = {
                            jobsViewModel.clearAllFilters()
                        },
                        searchQuery = uiState.searchQuery,
                        locationName = uiState.selectedLocationFilter?.displayLabel ?: uiState.selectedLocation?.name,
                        isLoading = uiState.isLoading,
                        modifier = Modifier.padding(vertical = 8.dp)
                    )

                    // Banner para crear alerta
                    val hasActiveLocationFilter = uiState.selectedLocationFilter != null || uiState.selectedLocation != null
                    if (uiState.filteredJobs.isNotEmpty() &&
                        (uiState.searchQuery.isNotBlank() || hasActiveLocationFilter)) {
                        CreateAlertBanner(
                            searchQuery = uiState.searchQuery,
                            locationId = uiState.selectedLocation?.id,
                            locationName = uiState.selectedLocationFilter?.displayLabel ?: uiState.selectedLocation?.name,
                            cropId = uiState.selectedCrop?.id,
                            cropName = uiState.selectedCrop?.name,
                            jobTypeId = uiState.selectedJobType?.id,
                            jobTypeName = uiState.selectedJobType?.name,
                            onDismiss = { /* Usuario rechazó la alerta */ }
                        )
                    }
                }

                when {
                    uiState.isLoading -> LoadingScreen()
                    uiState.isError -> ErrorScreen(
                        onRetry = { jobsViewModel.retry() },
                        errorMessage = uiState.errorMessage
                    )
                    uiState.filteredJobs.isEmpty() -> {
                        val locationFilter = uiState.selectedLocationFilter
                        val locationDisplayName = locationFilter?.displayLabel
                            ?: uiState.selectedLocation?.name

                        val hierarchicalSuggestions = buildList {
                            if (locationFilter != null) {
                                when (locationFilter.tipo) {
                                    LocationType.DISTRITO -> {
                                        add("Buscar en toda la provincia ${locationFilter.provincia}")
                                        add("Buscar en todo ${locationFilter.departamento}")
                                    }
                                    LocationType.PROVINCIA -> {
                                        add("Buscar en todo ${locationFilter.departamento}")
                                    }
                                    LocationType.DEPARTAMENTO -> { }
                                }
                            }
                            add("Ver todos los trabajos")
                        }

                        NoResultsMessage(
                            searchQuery = uiState.searchQuery,
                            locationName = locationDisplayName,
                            suggestions = hierarchicalSuggestions,
                            onSuggestionClick = { suggestion ->
                                when {
                                    suggestion.startsWith("Buscar en toda la provincia") && locationFilter != null -> {
                                        val provinciaFilter = SelectedLocationFilter(
                                            departamento = locationFilter.departamento,
                                            provincia = locationFilter.provincia,
                                            distrito = null,
                                            displayLabel = "${locationFilter.provincia}, ${locationFilter.departamento}",
                                            tipo = LocationType.PROVINCIA
                                        )
                                        jobsViewModel.onLocationFilterChange(provinciaFilter)
                                    }
                                    suggestion.startsWith("Buscar en todo") && locationFilter != null -> {
                                        val deptoFilter = SelectedLocationFilter(
                                            departamento = locationFilter.departamento,
                                            provincia = null,
                                            distrito = null,
                                            displayLabel = locationFilter.departamento,
                                            tipo = LocationType.DEPARTAMENTO
                                        )
                                        jobsViewModel.onLocationFilterChange(deptoFilter)
                                    }
                                    suggestion == "Ver todos los trabajos" -> {
                                        jobsViewModel.clearAllFilters()
                                    }
                                    else -> jobsViewModel.clearAllFilters()
                                }
                            },
                            onClearFilters = {
                                jobsViewModel.onFilterChange("", null, null, null, null)
                            }
                        )
                    }
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
    }

    // Modal de filtros avanzados
    if (showAdvancedFilters) {
        AdvancedFiltersModal(
            uiState = uiState,
            onFilterChange = jobsViewModel::onFilterChange,
            onLocationFilterChange = jobsViewModel::onLocationFilterChange,
            onDismiss = { showAdvancedFilters = false }
        )
    }
}

/**
 * Modal de filtros avanzados
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdvancedFiltersModal(
    uiState: JobsScreenState,
    onFilterChange: (String?, Category?, Category?, Category?, Category?) -> Unit,
    onLocationFilterChange: (SelectedLocationFilter?) -> Unit,
    onDismiss: () -> Unit
) {
    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)

    var selectedLocation by remember { mutableStateOf<Category?>(uiState.selectedLocation) }
    var selectedLocationFilter by remember { mutableStateOf<SelectedLocationFilter?>(uiState.selectedLocationFilter) }
    var selectedCompany by remember { mutableStateOf<Category?>(uiState.selectedCompany) }
    var selectedJobType by remember { mutableStateOf<Category?>(uiState.selectedJobType) }
    var selectedCrop by remember { mutableStateOf<Category?>(uiState.selectedCrop) }

    ModalBottomSheet(
        onDismissRequest = onDismiss,
        sheetState = sheetState,
        containerColor = MaterialTheme.colorScheme.surface
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp)
                .padding(bottom = 32.dp)
        ) {
            // Titulo
            Text(
                text = "Filtros Avanzados",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface,
                modifier = Modifier.padding(bottom = 24.dp)
            )

            // Buscador de ubicacion
            Text(
                text = "Ubicacion",
                style = MaterialTheme.typography.labelLarge,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.padding(bottom = 8.dp)
            )
            agrochamba.com.ui.common.LocationSearchBar(
                selectedLocation = selectedLocationFilter,
                onLocationSelected = { filter ->
                    selectedLocationFilter = filter
                    selectedLocation = if (filter != null) {
                        uiState.locationCategories.find {
                            it.name.equals(filter.departamento, ignoreCase = true)
                        }
                    } else null
                },
                placeholder = "Donde buscas trabajo?",
                showGpsButton = true
            )

            Spacer(Modifier.height(16.dp))

            // Filtro de Cultivo
            Text(
                text = "Cultivo",
                style = MaterialTheme.typography.labelLarge,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.padding(bottom = 8.dp)
            )
            LazyRow(
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                item {
                    FilterChip(
                        selected = selectedCrop == null,
                        onClick = { selectedCrop = null },
                        label = { Text("Todos", color = if (selectedCrop == null) MaterialTheme.colorScheme.onPrimary else MaterialTheme.colorScheme.onSurfaceVariant) },
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = MaterialTheme.colorScheme.primary,
                            containerColor = MaterialTheme.colorScheme.surfaceVariant
                        )
                    )
                }
                items(uiState.cropCategories) { crop ->
                    FilterChip(
                        selected = selectedCrop?.id == crop.id,
                        onClick = { selectedCrop = crop },
                        label = {
                            Text(
                                "${getEmojiForCrop(crop.name)} ${crop.name}",
                                color = if (selectedCrop?.id == crop.id) MaterialTheme.colorScheme.onPrimary else MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        },
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = MaterialTheme.colorScheme.primary,
                            containerColor = MaterialTheme.colorScheme.surfaceVariant
                        )
                    )
                }
            }

            Spacer(Modifier.height(16.dp))

            // Filtro de Empresa
            var isCompanyExpanded by remember { mutableStateOf(false) }
            Text(
                text = "Empresa",
                style = MaterialTheme.typography.labelLarge,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.padding(bottom = 8.dp)
            )
            ExposedDropdownMenuBox(
                expanded = isCompanyExpanded,
                onExpandedChange = { isCompanyExpanded = !isCompanyExpanded }
            ) {
                OutlinedTextField(
                    value = selectedCompany?.name ?: "Todas las empresas",
                    onValueChange = {},
                    readOnly = true,
                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = isCompanyExpanded) },
                    modifier = Modifier.fillMaxWidth().menuAnchor(),
                    colors = OutlinedTextFieldDefaults.colors(
                        unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant,
                        focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant,
                        unfocusedBorderColor = Color.Transparent,
                        focusedBorderColor = MaterialTheme.colorScheme.primary,
                        unfocusedTextColor = MaterialTheme.colorScheme.onSurface,
                        focusedTextColor = MaterialTheme.colorScheme.onSurface
                    ),
                    shape = RoundedCornerShape(12.dp)
                )
                ExposedDropdownMenu(
                    expanded = isCompanyExpanded,
                    onDismissRequest = { isCompanyExpanded = false }
                ) {
                    DropdownMenuItem(
                        text = { Text("Todas") },
                        onClick = { selectedCompany = null; isCompanyExpanded = false }
                    )
                    uiState.companyCategories.forEach { item ->
                        DropdownMenuItem(
                            text = { Text(item.name) },
                            onClick = { selectedCompany = item; isCompanyExpanded = false }
                        )
                    }
                }
            }

            Spacer(Modifier.height(16.dp))

            // Filtro de Tipo de Puesto
            var isJobTypeExpanded by remember { mutableStateOf(false) }
            Text(
                text = "Tipo de Puesto",
                style = MaterialTheme.typography.labelLarge,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.padding(bottom = 8.dp)
            )
            ExposedDropdownMenuBox(
                expanded = isJobTypeExpanded,
                onExpandedChange = { isJobTypeExpanded = !isJobTypeExpanded }
            ) {
                OutlinedTextField(
                    value = selectedJobType?.name ?: "Todos los tipos",
                    onValueChange = {},
                    readOnly = true,
                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = isJobTypeExpanded) },
                    modifier = Modifier.fillMaxWidth().menuAnchor(),
                    colors = OutlinedTextFieldDefaults.colors(
                        unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant,
                        focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant,
                        unfocusedBorderColor = Color.Transparent,
                        focusedBorderColor = MaterialTheme.colorScheme.primary,
                        unfocusedTextColor = MaterialTheme.colorScheme.onSurface,
                        focusedTextColor = MaterialTheme.colorScheme.onSurface
                    ),
                    shape = RoundedCornerShape(12.dp)
                )
                ExposedDropdownMenu(
                    expanded = isJobTypeExpanded,
                    onDismissRequest = { isJobTypeExpanded = false }
                ) {
                    DropdownMenuItem(
                        text = { Text("Todos") },
                        onClick = { selectedJobType = null; isJobTypeExpanded = false }
                    )
                    uiState.jobTypeCategories.forEach { item ->
                        DropdownMenuItem(
                            text = { Text(item.name) },
                            onClick = { selectedJobType = item; isJobTypeExpanded = false }
                        )
                    }
                }
            }

            Spacer(Modifier.height(24.dp))

            // Boton aplicar
            Button(
                onClick = {
                    onFilterChange(uiState.searchQuery, selectedLocation, selectedCompany, selectedJobType, selectedCrop)
                    if (selectedLocationFilter != null) {
                        onLocationFilterChange(selectedLocationFilter)
                    }
                    onDismiss()
                },
                modifier = Modifier.fillMaxWidth(),
                colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary),
                shape = RoundedCornerShape(12.dp)
            ) {
                Text(
                    "Aplicar Filtros",
                    modifier = Modifier.padding(vertical = 8.dp),
                    fontWeight = FontWeight.Bold
                )
            }
        }
    }
}


/**
 * Panel de búsqueda y filtros con experiencia tipo Google
 * 
 * Arquitectura UX:
 * 1. Campo de búsqueda de texto (para buscar por título, descripción, etc.)
 * 2. Buscador inteligente de ubicación (departamento/provincia/distrito)
 * 3. Chips de cultivo para filtro rápido
 * 4. Filtros avanzados en modal (empresa, tipo de puesto)
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SearchAndFilterPanel(
    uiState: JobsScreenState,
    onFilterChange: (String?, Category?, Category?, Category?, Category?) -> Unit,
    onLocationFilterChange: ((SelectedLocationFilter?) -> Unit)? = null
) {
    // Estado para controlar la visibilidad del modal de filtros avanzados
    var showAdvancedFiltersModal by remember { mutableStateOf(false) }
    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
    
    // Sincronizar estado local con el estado del ViewModel
    var searchQuery by remember { mutableStateOf(uiState.searchQuery) }
    var selectedLocation by remember { mutableStateOf<Category?>(uiState.selectedLocation) }
    var selectedLocationFilter by remember { mutableStateOf<SelectedLocationFilter?>(uiState.selectedLocationFilter) }
    var selectedCompany by remember { mutableStateOf<Category?>(uiState.selectedCompany) }
    var selectedJobType by remember { mutableStateOf<Category?>(uiState.selectedJobType) }
    var selectedCrop by remember { mutableStateOf<Category?>(uiState.selectedCrop) }

    // Sincronizar cuando cambia el estado del ViewModel
    LaunchedEffect(
        uiState.searchQuery, 
        uiState.selectedLocation, 
        uiState.selectedLocationFilter,
        uiState.selectedCompany,
        uiState.selectedJobType,
        uiState.selectedCrop
    ) {
        searchQuery = uiState.searchQuery
        selectedLocation = uiState.selectedLocation
        selectedLocationFilter = uiState.selectedLocationFilter
        selectedCompany = uiState.selectedCompany
        selectedJobType = uiState.selectedJobType
        selectedCrop = uiState.selectedCrop
    }

    // Aplicar filtros con debounce para búsqueda de texto
    LaunchedEffect(searchQuery, selectedCompany, selectedJobType, selectedCrop) {
        if (searchQuery != uiState.searchQuery) {
        delay(300)
        }
        onFilterChange(searchQuery, selectedLocation, selectedCompany, selectedJobType, selectedCrop)
    }

    Column(modifier = Modifier.padding(16.dp)) {
        // =================================================================
        // BUSCADOR DE TEXTO (para trabajos, empresas, etc.)
        // =================================================================
        OutlinedTextField(
            value = searchQuery,
            onValueChange = { searchQuery = it },
            placeholder = { Text("Buscar trabajo, empresa...") },
            modifier = Modifier.fillMaxWidth(),
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Buscar") },
            trailingIcon = {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    if (searchQuery.isNotEmpty()) {
                        IconButton(onClick = { searchQuery = "" }) {
                            Icon(Icons.Default.Close, contentDescription = "Limpiar")
                        }
                    }
                IconButton(
                    onClick = { showAdvancedFiltersModal = true }
                ) {
                    Icon(
                        Icons.Default.Tune,
                        contentDescription = "Filtros avanzados",
                            tint = if (selectedCompany != null || selectedJobType != null) 
                                MaterialTheme.colorScheme.primary 
                            else 
                                MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
            },
            singleLine = true,
            shape = RoundedCornerShape(12.dp)
        )

        Spacer(Modifier.height(12.dp))

        // =================================================================
        // BUSCADOR INTELIGENTE DE UBICACIÓN (tipo Google)
        // Con botón GPS integrado para encontrar trabajos cercanos
        // =================================================================
        agrochamba.com.ui.common.LocationSearchBar(
            selectedLocation = selectedLocationFilter,
            onLocationSelected = { filter ->
                selectedLocationFilter = filter
                // También actualizar la categoría para compatibilidad
                selectedLocation = if (filter != null) {
                    uiState.locationCategories.find { 
                        it.name.equals(filter.departamento, ignoreCase = true) 
                    }
                } else null
                
                // Notificar al ViewModel
                onLocationFilterChange?.invoke(filter)
            },
            placeholder = "¿Dónde buscas trabajo?",
            showGpsButton = true
            // El GPS se maneja internamente en LocationSearchBar
        )

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
                // Wrapper seguro para capturar errores en cards específicas
                SafeJobCard(
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

// Data class para información de la etiqueta
data class BadgeInfo(
    val text: String,
    val backgroundColor: Color,
    val textColor: Color
)

/**
 * Extrae solo el departamento de un nombre de ubicación.
 * Soporta formatos: "Departamento", "Provincia, Departamento", "Distrito, Provincia, Departamento"
 */
fun extractDepartamento(locationName: String?): String? {
    if (locationName.isNullOrBlank()) return null
    
    val parts = locationName.split(",").map { it.trim() }
    // El departamento siempre es el último elemento
    return parts.lastOrNull()?.takeIf { it.isNotBlank() }
}

/**
 * Wrapper seguro para JobCard que valida datos antes de renderizar
 * Evita que un trabajo con datos problemáticos crashee toda la app
 */
@Composable
fun SafeJobCard(job: JobPost, onClick: () -> Unit, viewModel: JobsViewModel) {
    // Validar datos antes de renderizar
    val validationResult = remember(job.id) {
        validateJobData(job)
    }

    // Log de debug para cada card (solo si debug está activo)
    LaunchedEffect(job.id) {
        if (agrochamba.com.utils.DebugManager.isEnabled) {
            android.util.Log.d("ACH_CARD", "Renderizando Job #${job.id}: ${job.title?.rendered?.take(30)}...")

            if (validationResult.issues.isNotEmpty()) {
                android.util.Log.w("ACH_CARD", "⚠️ Job #${job.id} tiene issues: ${validationResult.issues}")
            }
        }
    }

    if (!validationResult.isValid) {
        // Mostrar card de error en lugar de crashear
        ErrorJobCard(
            jobId = job.id,
            jobTitle = job.title?.rendered?.take(50) ?: "Sin título",
            errorMessage = validationResult.issues.joinToString(", "),
            onClick = onClick
        )
    } else {
        // Datos válidos, renderizar card normal
        JobCard(
            job = job,
            onClick = onClick,
            viewModel = viewModel
        )
    }
}

/**
 * Resultado de validación de datos de un trabajo
 */
data class JobValidationResult(
    val isValid: Boolean,
    val issues: List<String>
)

/**
 * Valida los datos de un trabajo para evitar crashes
 * Detecta problemas comunes: null, vacío, caracteres especiales, datos muy largos
 */
fun validateJobData(job: JobPost): JobValidationResult {
    val issues = mutableListOf<String>()

    // Validar campos críticos que pueden causar crashes
    try {
        // Título - puede tener HTML problemático
        val title = job.title?.rendered
        if (title == null) {
            issues.add("titulo_null")
        } else if (title.length > 1000) {
            issues.add("titulo_muy_largo(${title.length})")
        }

        // Excerpt - puede ser muy largo o tener HTML roto
        val excerpt = job.excerpt?.rendered
        if (excerpt != null && excerpt.length > 10000) {
            issues.add("excerpt_muy_largo(${excerpt.length})")
        }

        // Content - puede ser enorme
        val content = job.content?.rendered
        if (content != null && content.length > 100000) {
            issues.add("content_muy_largo(${content.length})")
        }

        // Meta - verificar campos numéricos que llegan como string
        job.meta?.let { meta ->
            // Salario - puede venir como string no numérico
            meta.salarioMin?.let { salario ->
                if (salario.isNotBlank() && salario.toDoubleOrNull() == null) {
                    issues.add("salarioMin_no_numerico($salario)")
                }
            }
            meta.salarioMax?.let { salario ->
                if (salario.isNotBlank() && salario.toDoubleOrNull() == null) {
                    issues.add("salarioMax_no_numerico($salario)")
                }
            }
            meta.vacantes?.let { vacantes ->
                if (vacantes.isNotBlank() && vacantes.toIntOrNull() == null) {
                    issues.add("vacantes_no_numerico($vacantes)")
                }
            }
        }

        // Fecha - puede tener formato inválido
        job.date?.let { date ->
            if (date.isNotBlank()) {
                try {
                    // Intentar parsear la fecha
                    java.text.SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", java.util.Locale.getDefault()).parse(date)
                } catch (e: Exception) {
                    try {
                        java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss", java.util.Locale.getDefault()).parse(date)
                    } catch (e2: Exception) {
                        issues.add("fecha_formato_invalido($date)")
                    }
                }
            }
        }

        // Embedded terms - puede tener estructura rota
        job.embedded?.terms?.let { termsList ->
            termsList.forEach { terms ->
                terms?.forEach { term ->
                    if (term.name != null && term.name.length > 500) {
                        issues.add("term_nombre_muy_largo(${term.taxonomy})")
                    }
                }
            }
        }

    } catch (e: Exception) {
        issues.add("error_validacion: ${e.message?.take(50)}")
        android.util.Log.e("ACH_VALIDATION", "Error validando Job #${job.id}", e)
    }

    // Consideramos válido si no hay issues críticos
    // Issues menores (como fecha inválida) no invalidan la card
    val criticalIssues = issues.filter {
        it.contains("null") || it.contains("muy_largo") || it.contains("error_validacion")
    }

    return JobValidationResult(
        isValid = criticalIssues.isEmpty(),
        issues = issues
    )
}

/**
 * Card que se muestra cuando hay un error al renderizar un trabajo
 */
@Composable
fun ErrorJobCard(
    jobId: Int,
    jobTitle: String,
    errorMessage: String?,
    onClick: () -> Unit
) {
    Card(
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.errorContainer.copy(alpha = 0.3f)
        ),
        shape = RoundedCornerShape(16.dp),
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
    ) {
        Column(
            modifier = Modifier.padding(16.dp)
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically
            ) {
                Icon(
                    Icons.Default.Work,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.error,
                    modifier = Modifier.size(24.dp)
                )
                Spacer(Modifier.width(8.dp))
                Text(
                    text = jobTitle.htmlToString(),
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.Bold,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }

            Spacer(Modifier.height(8.dp))

            Text(
                text = "⚠️ Error al cargar esta oferta (ID: $jobId)",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.error
            )

            if (agrochamba.com.utils.DebugManager.isEnabled && errorMessage != null) {
                Spacer(Modifier.height(4.dp))
                Text(
                    text = "Debug: ${errorMessage.take(100)}",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onErrorContainer,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis
                )
            }

            Spacer(Modifier.height(8.dp))

            Text(
                text = "Toca para intentar ver detalles",
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
fun JobCard(job: JobPost, onClick: () -> Unit, viewModel: JobsViewModel) {
    val terms = job.embedded?.terms?.flatten() ?: emptyList()
    val companyName = terms.find { it.taxonomy == "empresa" }?.name

    // UX: En cards mostramos SOLO el departamento
    // PRIORIDAD: meta.ubicacionCompleta > ubicacionDisplay > taxonomía embebida
    val locationName = remember(job.meta?.ubicacionCompleta, job.ubicacionDisplay, job.embedded?.terms) {
        // 1. Primero intentar desde meta.ubicacionCompleta (fuente principal)
        job.meta?.ubicacionCompleta?.departamento?.takeIf { it.isNotBlank() }
            // 2. Segundo intentar desde ubicacion_display (agregado por API)
            ?: job.ubicacionDisplay?.departamento?.takeIf { it.isNotBlank() }
            // 3. Fallback a taxonomía embebida
            ?: terms.find { it.taxonomy == "ubicacion" }?.name?.let { extractDepartamento(it) }
    }
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
    val shortDescription: String? = remember(job.excerpt) {
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
    
    // Determinar etiqueta a mostrar (prioridad: Destacado > Nuevo > Urgente > Con beneficios > Buen salario)
    val jobBadge = remember(job.date, job.meta, beneficios) {
        // 1. Destacado (si está marcado como sticky - por ahora no hay campo, pero se puede agregar)
        // Por ahora lo omitimos hasta que el backend lo implemente
        // if (job.meta?.isFeatured == true) return@remember BadgeInfo("Destacado", Color(0xFFFFE0B2), Color(0xFFE65100))
        
        // 2. Nuevo - trabajos publicados en las últimas 48 horas
        val jobDate = job.date?.let { 
            try {
                java.text.SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", java.util.Locale.getDefault()).parse(it)
                    ?: java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss", java.util.Locale.getDefault()).parse(it)
            } catch (e: Exception) {
                null
            }
        }
        val hoursSincePublication = jobDate?.let {
            val now = System.currentTimeMillis()
            val jobTime = it.time
            (now - jobTime) / (1000 * 60 * 60) // Convertir a horas
        } ?: Long.MAX_VALUE
        
        if (hoursSincePublication <= 48) {
            return@remember BadgeInfo("Nuevo", Color(0xFFE3F2FD), Color(0xFF1976D2))
        }
        
        // 3. Urgente - si tiene muchas vacantes (más de 5) o salario muy alto
        val vacantes = job.meta?.vacantes?.toIntOrNull() ?: 0
        val salarioMin = job.meta?.salarioMin?.toIntOrNull() ?: 0
        if (vacantes >= 5 || salarioMin >= 3000) {
            return@remember BadgeInfo("Urgente", Color(0xFFFFEBEE), Color(0xFFD32F2F))
        }
        
        // 4. Con beneficios - si tiene alojamiento, transporte o alimentación
        if (beneficios.isNotEmpty()) {
            return@remember BadgeInfo("Con beneficios", Color(0xFFE8F5E9), Color(0xFF2E7D32))
        }
        
        // 5. Buen salario - si el salario mínimo es mayor a 2000 soles
        if (salarioMin >= 2000) {
            return@remember BadgeInfo("Buen salario", Color(0xFFFFF9C4), Color(0xFFF57F17))
        }
        
        // Sin etiqueta
        null
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
                    
                    // Etiqueta dinámica en la esquina superior derecha
                    jobBadge?.let { badge ->
                        Box(
                            modifier = Modifier
                                .align(Alignment.TopEnd)
                                .padding(8.dp)
                                .background(
                                    badge.backgroundColor,
                                    RoundedCornerShape(12.dp)
                                )
                                .padding(horizontal = 8.dp, vertical = 4.dp)
                        ) {
                            Text(
                                text = badge.text,
                                style = MaterialTheme.typography.labelSmall,
                                fontWeight = FontWeight.Bold,
                                color = badge.textColor
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
                            horizontalArrangement = Arrangement.spacedBy(6.dp),
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
                                    tint = if (isFavorite) Color(0xFFE91E63) else Color(0xFF757575)
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
                                    tint = if (isSaved) Color(0xFF2196F3) else Color(0xFF757575)
                                )
                            }
                        }
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
                // Título y etiqueta dinámica
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
                    
                    jobBadge?.let { badge ->
                        Box(
                            modifier = Modifier
                                .background(
                                    badge.backgroundColor,
                                    RoundedCornerShape(12.dp)
                                )
                                .padding(horizontal = 8.dp, vertical = 4.dp)
                        ) {
                            Text(
                                text = badge.text,
                                style = MaterialTheme.typography.labelSmall,
                                fontWeight = FontWeight.Bold,
                                color = badge.textColor
                            )
                        }
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
                            horizontalArrangement = Arrangement.spacedBy(6.dp),
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
                                    tint = if (isFavorite) Color(0xFFE91E63) else Color(0xFF757575)
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
                                    tint = if (isSaved) Color(0xFF2196F3) else Color(0xFF757575)
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
