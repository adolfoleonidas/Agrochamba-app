package agrochamba.com.ui.jobs

import android.content.Context
import android.net.Uri
import android.widget.Toast
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.RowScope
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ArrowForwardIos
import androidx.compose.material.icons.filled.Business
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Folder
import androidx.compose.material.icons.filled.Public
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.Star
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import agrochamba.com.data.AuthManager
import agrochamba.com.data.Category
import agrochamba.com.data.JobPost
import agrochamba.com.data.UbicacionCompleta
import agrochamba.com.data.SedeEmpresa
import agrochamba.com.data.repository.LocationRepository
import agrochamba.com.utils.htmlToString
import agrochamba.com.utils.htmlToMarkdown
import agrochamba.com.utils.textToHtml
import agrochamba.com.ui.common.RichTextEditor
import agrochamba.com.ui.common.SearchableDropdown
import agrochamba.com.ui.common.SmartLocationSelector
import coil.compose.AsyncImage
import coil.request.ImageRequest

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EditJobScreen(
    job: JobPost,
    navController: NavController,
    viewModel: EditJobViewModel = androidx.lifecycle.viewmodel.compose.viewModel(
        key = "edit_job_${job.id}", // Clave única por trabajo para evitar reutilización
        factory = EditJobViewModelFactory(job)
    )
) {
    val uiState = viewModel.uiState
    val context = LocalContext.current

    val imagePickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetMultipleContents(),
        onResult = { uris -> viewModel.onImagesSelected(uris) }
    )

    // Obtener taxonomías del job
    val terms = job.embedded?.terms?.flatten() ?: emptyList()
    val initialUbicacionTerm = terms.find { it.taxonomy == "ubicacion" }
    val initialEmpresaTerm = terms.find { it.taxonomy == "empresa" }
    val initialCultivoTerm = terms.find { it.taxonomy == "cultivo" }
    val initialTipoPuestoTerm = terms.find { it.taxonomy == "tipo_puesto" }
    // Buscar categorías de WordPress (para blogs)
    val initialCategoriaTerms = terms.filter { it.taxonomy == "category" }

    var title by remember { mutableStateOf(job.title?.rendered?.htmlToString() ?: "") }
    var description by remember { mutableStateOf(job.content?.rendered?.htmlToMarkdown() ?: "") }
    var salarioMin by remember { mutableStateOf(job.meta?.salarioMin ?: "") }
    var salarioMax by remember { mutableStateOf(job.meta?.salarioMax ?: "") }
    var vacantes by remember { mutableStateOf(job.meta?.vacantes ?: "") }
    
    var selectedUbicacion by remember { mutableStateOf<Category?>(null) }
    // Nueva ubicación estructurada (departamento, provincia, distrito)
    var selectedUbicacionCompleta by remember { 
        mutableStateOf(
            // Cargar ubicación completa desde el job si existe
            job.meta?.ubicacionCompleta?.let { uc ->
                UbicacionCompleta(
                    departamento = uc.departamento,
                    provincia = uc.provincia,
                    distrito = uc.distrito,
                    direccion = uc.direccion
                )
            }
        ) 
    }
    var selectedEmpresa by remember { mutableStateOf<Category?>(null) }
    var selectedCultivo by remember { mutableStateOf<Category?>(null) }
    var selectedTipoPuesto by remember { mutableStateOf<Category?>(null) }
    var selectedCategoria by remember { mutableStateOf<Category?>(null) }
    
    // Sedes de empresa cargadas desde el repositorio
    val locationRepository = remember { LocationRepository.getInstance(context) }
    val companySedes by locationRepository.companySedes.collectAsState(initial = emptyList())
    
    // Selector de tipo de publicación (solo para admins)
    val isAdmin = AuthManager.isUserAdmin()
    // Determinar tipo inicial: si tiene ubicación es trabajo, si no tiene ubicación pero tiene categorías es blog
    var tipoPublicacion by remember { 
        mutableStateOf(
            if (initialUbicacionTerm != null) "trabajo" 
            else if (initialCategoriaTerms.isNotEmpty()) "post" 
            else "trabajo" // Por defecto trabajo
        ) 
    }
    
    LaunchedEffect(uiState.ubicaciones, initialUbicacionTerm) {
        if (uiState.ubicaciones.isNotEmpty() && initialUbicacionTerm != null && selectedUbicacion == null) {
            selectedUbicacion = uiState.ubicaciones.find { it.id == initialUbicacionTerm.id }
        }
    }
    
    LaunchedEffect(uiState.empresas, initialEmpresaTerm, uiState.userCompanyId) {
        if (uiState.empresas.isNotEmpty() && selectedEmpresa == null) {
            // Si hay empresa inicial del trabajo, usarla
            if (initialEmpresaTerm != null) {
            selectedEmpresa = uiState.empresas.find { it.id == initialEmpresaTerm.id }
            } 
            // Si no hay empresa inicial pero el usuario es empresa normal, usar su empresa automáticamente
            else if (uiState.userCompanyId != null && !AuthManager.isUserAdmin()) {
                selectedEmpresa = uiState.empresas.find { it.id == uiState.userCompanyId }
            }
        }
    }
    
    LaunchedEffect(uiState.cultivos, initialCultivoTerm) {
        if (uiState.cultivos.isNotEmpty() && initialCultivoTerm != null && selectedCultivo == null) {
            selectedCultivo = uiState.cultivos.find { it.id == initialCultivoTerm.id }
        }
    }
    
    LaunchedEffect(uiState.tiposPuesto, initialTipoPuestoTerm) {
        if (uiState.tiposPuesto.isNotEmpty() && initialTipoPuestoTerm != null && selectedTipoPuesto == null) {
            selectedTipoPuesto = uiState.tiposPuesto.find { it.id == initialTipoPuestoTerm.id }
        }
    }
    
    // Cargar categoría inicial si existe
    LaunchedEffect(uiState.categorias, initialCategoriaTerms) {
        if (uiState.categorias.isNotEmpty() && initialCategoriaTerms.isNotEmpty() && selectedCategoria == null) {
            // Usar la primera categoría encontrada
            val firstCategoriaTerm = initialCategoriaTerms.first()
            selectedCategoria = uiState.categorias.find { it.id == firstCategoriaTerm.id }
        }
    }
    
    var alojamiento by remember { mutableStateOf(job.meta?.alojamiento ?: false) }
    var transporte by remember { mutableStateOf(job.meta?.transporte ?: false) }
    var alimentacion by remember { mutableStateOf(job.meta?.alimentacion ?: false) }
    // IMPORTANTE: Siempre inicializar en false para que el usuario tenga control explícito
    // No usar facebookPostId como indicador porque puede haber sido publicado automáticamente sin consentimiento
    var publishToFacebook by remember { mutableStateOf(false) }
    // Comentarios habilitados por defecto (true)
    // TODO: Cargar desde API cuando esté disponible (comment_status)
    var comentariosHabilitados by remember { mutableStateOf(true) }
    
    var showMoreOptions by remember { mutableStateOf(false) }
    var showOCRDialog by remember { mutableStateOf(false) }
    
    // IMPORTANTE: Recargar imágenes cuando cambia el trabajo
    // Esto asegura que cada trabajo tenga sus propias imágenes cargadas
    // Usar una variable remember para rastrear el último job.id procesado
    var lastProcessedJobId by remember { mutableStateOf<Int?>(null) }
    LaunchedEffect(job.id) {
        if (lastProcessedJobId != job.id) {
            android.util.Log.d("EditJobScreen", "Job ID cambió de $lastProcessedJobId a ${job.id}, recargando imágenes...")
            lastProcessedJobId = job.id
            viewModel.reloadImages()
        }
    }

    // El ViewModel ya carga las imágenes en su init, no necesitamos recargarlas aquí
    // Esto evita interferencias y asegura que las imágenes se carguen correctamente

    LaunchedEffect(uiState.updateSuccess) {
        if (uiState.updateSuccess) {
            val mensaje = if (tipoPublicacion == "trabajo") {
                "¡Trabajo actualizado con éxito!"
            } else {
                "¡Artículo de blog actualizado con éxito!"
            }
            Toast.makeText(context, mensaje, Toast.LENGTH_LONG).show()
            navController.popBackStack()
        }
    }

    LaunchedEffect(uiState.deleteSuccess) {
        if (uiState.deleteSuccess) {
            Toast.makeText(context, "Trabajo eliminado", Toast.LENGTH_LONG).show()
            navController.popBackStack()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (tipoPublicacion == "trabajo") "Editar Trabajo" else "Editar Blog") },
                navigationIcon = { 
                    IconButton(onClick = { navController.popBackStack() }) { 
                        Icon(Icons.Default.ArrowBack, null) 
                    }
                }
            )
        },
        bottomBar = {
            EditBottomActionBar(
                isLoading = uiState.isLoading,
                loadingMessage = uiState.loadingMessage,
                onSave = {
                    // Debug: verificar el tipo de publicación
                    android.util.Log.d("EditJobScreen", "Tipo de publicación seleccionado: $tipoPublicacion")
                    
                    if (title.isBlank()) {
                        Toast.makeText(context, "El título es obligatorio", Toast.LENGTH_SHORT).show()
                        return@EditBottomActionBar
                    }
                    if (description.isBlank()) {
                        Toast.makeText(context, "La descripción es obligatoria", Toast.LENGTH_SHORT).show()
                        return@EditBottomActionBar
                    }
                    
                    // Validaciones específicas según el tipo de publicación
                    if (tipoPublicacion == "trabajo") {
                        android.util.Log.d("EditJobScreen", "Validando campos de TRABAJO")
                        android.util.Log.d("EditJobScreen", "selectedUbicacionCompleta: $selectedUbicacionCompleta")
                        android.util.Log.d("EditJobScreen", "selectedUbicacion (Category): $selectedUbicacion")
                        
                        // Validación de ubicación: usar el nuevo sistema (UbicacionCompleta) como fuente principal
                        // Una ubicación válida debe tener al menos el departamento seleccionado
                        val ubicacionValida = selectedUbicacionCompleta != null && 
                            selectedUbicacionCompleta!!.departamento.isNotBlank()
                        
                        if (!ubicacionValida) {
                            Toast.makeText(context, "La ubicación es obligatoria", Toast.LENGTH_SHORT).show()
                            return@EditBottomActionBar
                        }
                        
                        // Sincronizar automáticamente el Category (para compatibilidad con backend)
                        if (selectedUbicacion == null && selectedUbicacionCompleta != null) {
                            val deptoName = selectedUbicacionCompleta!!.departamento
                            selectedUbicacion = uiState.ubicaciones.find { cat ->
                                cat.name.equals(deptoName, ignoreCase = true)
                            }
                            android.util.Log.d("EditJobScreen", "Sincronizado Category: ${selectedUbicacion?.name} para departamento: $deptoName")
                        }
                        
                        // Empresa es OPCIONAL - usar la seleccionada o la del usuario automáticamente
                        val empresaId = if (isAdmin) {
                            // Admin: puede seleccionar empresa o dejarla vacía
                            selectedEmpresa?.id
                        } else {
                            // Empresa normal: usar su empresa automáticamente si existe
                            uiState.userCompanyId
                        }
                        
                        // ubicacion_id puede ser null si no existe un Category correspondiente al departamento
                        val ubicacionIdValue = selectedUbicacion?.id
                        
                        val jobData = mutableMapOf<String, Any?>(
                            "post_type" to "trabajo",
                            "title" to title.trim(),
                            "content" to description.textToHtml(),
                            "salario_min" to (salarioMin.toIntOrNull() ?: 0),
                            "salario_max" to (salarioMax.toIntOrNull() ?: 0),
                            "vacantes" to (vacantes.toIntOrNull() ?: 1),
                            "cultivo_id" to selectedCultivo?.id,
                            "tipo_puesto_id" to selectedTipoPuesto?.id,
                            "alojamiento" to alojamiento,
                            "transporte" to transporte,
                            "alimentacion" to alimentacion,
                            "comentarios_habilitados" to comentariosHabilitados,
                            "publish_to_facebook" to publishToFacebook
                        )
                        
                        // Agregar ubicacion_id solo si existe el Category correspondiente
                        ubicacionIdValue?.let { jobData["ubicacion_id"] = it }
                        
                        // Agregar ubicación completa - SIEMPRE (es la fuente principal de datos de ubicación)
                        // NOTA: El backend espera _ubicacion_completa (con underscore)
                        // Incluye el nivel de especificidad para respetar lo que el usuario seleccionó
                        selectedUbicacionCompleta?.let { ubicacion ->
                            jobData["_ubicacion_completa"] = mapOf(
                                "departamento" to ubicacion.departamento,
                                "provincia" to ubicacion.provincia,
                                "distrito" to ubicacion.distrito,
                                "direccion" to (ubicacion.direccion ?: ""),
                                "lat" to (ubicacion.obtenerCoordenadas()?.lat ?: 0.0),
                                "lng" to (ubicacion.obtenerCoordenadas()?.lng ?: 0.0),
                                "nivel" to ubicacion.nivel.name // DEPARTAMENTO, PROVINCIA o DISTRITO
                            )
                        }
                        
                        // Agregar empresa_id solo si está presente (opcional)
                        empresaId?.let { jobData["empresa_id"] = it }
                        
                        // Filtrar valores null y convertir a Map<String, Any>
                        val finalJobData = jobData.filterValues { it != null }.mapValues { it.value!! }
                        viewModel.updateJob(finalJobData, context)
                    } else {
                        // Para blogs (post) - NO requiere ubicación ni empresa
                        android.util.Log.d("EditJobScreen", "Actualizando BLOG - sin validar ubicación ni empresa")
                        val blogData = mutableMapOf<String, Any?>(
                            "post_type" to "post",
                            "title" to title.trim(),
                            "content" to description.textToHtml(),
                            "comentarios_habilitados" to comentariosHabilitados,
                            "publish_to_facebook" to publishToFacebook
                        )
                        // Agregar categoría si está seleccionada
                        selectedCategoria?.id?.let { blogData["categories"] = listOf(it) }
                        
                        // Filtrar valores null y convertir a Map<String, Any>
                        val finalBlogData = blogData.filterValues { it != null }.mapValues { it.value!! }
                        viewModel.updateJob(finalBlogData, context)
                    }
                },
                onDelete = {
                    viewModel.deleteJob(context)
                }
            )
        }
    ) { paddingValues ->
        if (uiState.isLoading && uiState.loadingMessage.startsWith("Cargando")) {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    CircularProgressIndicator()
                    Spacer(Modifier.height(16.dp))
                    Text(uiState.loadingMessage)
                }
            }
        } else {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
                    .verticalScroll(rememberScrollState())
            ) {
                // Sección de imágenes - estilo TikTok (combinando existentes y nuevas)
                // Debug: Log del estado de imágenes
                LaunchedEffect(uiState.existingImageUrls.size, uiState.imagesLoaded) {
                    android.util.Log.d("EditJobScreen", "Estado imágenes - URLs: ${uiState.existingImageUrls.size}, Loaded: ${uiState.imagesLoaded}, Job ID: ${job.id}")
                }
                
                EditImageSection(
                    existingImageUrls = uiState.existingImageUrls,
                    existingImageIds = uiState.existingImageIds,
                    newImages = uiState.selectedImages,
                    imagesLoaded = uiState.imagesLoaded,
                    isLoading = uiState.isLoading,
                    onAddImage = { imagePickerLauncher.launch("image/*") },
                    onRemoveExisting = { viewModel.removeExistingImage(it) },
                    onRemoveNew = { viewModel.removeImage(it) },
                    onReorderExisting = { from, to -> viewModel.reorderExistingImages(from, to) },
                    onReorderNew = { from, to -> viewModel.reorderImages(from, to) }
                )
                
                Spacer(Modifier.height(24.dp))
                
                // Título con placeholder descriptivo
                Column(modifier = Modifier.padding(horizontal = 16.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(
                            text = "Título del anuncio",
                            style = MaterialTheme.typography.titleSmall,
                            fontWeight = FontWeight.Medium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.padding(bottom = 8.dp)
                        )
                        // Contador de caracteres (límite máximo: 200, recomendación SEO: 50-60)
                        val maxLength = 200 // Límite máximo permitido
                        val seoOptimalMax = 60 // Recomendación SEO de Google
                        val seoOptimalMin = 50 // Recomendación SEO de Google
                        Text(
                            text = "${title.length}/$maxLength",
                            style = MaterialTheme.typography.bodySmall,
                            color = when {
                                title.length > maxLength -> Color(0xFFD32F2F) // Rojo si excede límite máximo
                                title.length > seoOptimalMax -> Color(0xFFFF9800) // Naranja si excede recomendación SEO
                                title.length >= seoOptimalMin -> Color(0xFF4CAF50) // Verde si está en rango óptimo SEO
                                else -> MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.6f) // Gris si está por debajo
                            },
                            modifier = Modifier.padding(bottom = 8.dp)
                        )
                    }
                    TextField(
                        value = title,
                        onValueChange = { newTitle ->
                            // Permitir hasta 200 caracteres (límite máximo)
                            if (newTitle.length <= 200) {
                                title = newTitle
                            }
                        },
                        placeholder = {
                            Text(
                                "Ej: Se busca personal para cosecha de uva en Ica",
                                color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.6f)
                            )
                        },
                        modifier = Modifier.fillMaxWidth(),
                        colors = TextFieldDefaults.colors(
                            focusedContainerColor = Color.Transparent,
                            unfocusedContainerColor = Color.Transparent,
                            focusedIndicatorColor = when {
                                title.length > 200 -> Color(0xFFD32F2F) // Rojo si excede límite máximo
                                title.length > 60 -> Color(0xFFFF9800) // Naranja si excede recomendación SEO
                                else -> MaterialTheme.colorScheme.primary
                            },
                            unfocusedIndicatorColor = when {
                                title.length > 200 -> Color(0xFFD32F2F)
                                title.length > 60 -> Color(0xFFFF9800)
                                else -> MaterialTheme.colorScheme.outline
                            }
                        ),
                        textStyle = MaterialTheme.typography.bodyLarge,
                        maxLines = 3, // Permitir hasta 3 líneas para ver el título completo
                        minLines = 1
                    )
                    // Mensaje de ayuda SEO (solo recomendación, no bloquea)
                    if (title.length > 0) {
                        Text(
                            text = when {
                                title.length <= 50 -> "✓ Título óptimo para SEO (50-60 caracteres recomendado)"
                                title.length <= 60 -> "✓ Título dentro del rango SEO recomendado"
                                title.length <= 100 -> "⚠ Título largo: puede truncarse en resultados de búsqueda de Google"
                                title.length <= 200 -> "⚠ Título muy largo: se truncará significativamente en resultados"
                                else -> "⚠ Título extremadamente largo: se truncará completamente en resultados de búsqueda"
                            },
                            style = MaterialTheme.typography.bodySmall,
                            color = when {
                                title.length <= 60 -> Color(0xFF4CAF50) // Verde
                                title.length <= 100 -> Color(0xFFFF9800) // Naranja
                                else -> Color(0xFFD32F2F) // Rojo
                            },
                            modifier = Modifier.padding(top = 4.dp)
                        )
                    }
                }
                
                Spacer(Modifier.height(16.dp))
                
                // Descripción
                Column(modifier = Modifier.padding(horizontal = 16.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(
                            if (tipoPublicacion == "trabajo") "Descripción del Trabajo" else "Descripción del Blog",
                            style = MaterialTheme.typography.labelLarge,
                            fontWeight = FontWeight.Medium,
                            color = MaterialTheme.colorScheme.onSurface,
                            modifier = Modifier.padding(bottom = 8.dp)
                        )
                        // Mensaje de estado de IA
                        uiState.aiSuccess?.let { success ->
                            Text(
                                text = success,
                                style = MaterialTheme.typography.labelSmall,
                                color = Color(0xFF4CAF50),
                                modifier = Modifier.padding(bottom = 8.dp)
                            )
                        }
                        uiState.aiError?.let { error ->
                            Text(
                                text = error,
                                style = MaterialTheme.typography.labelSmall,
                                color = MaterialTheme.colorScheme.error,
                                modifier = Modifier.padding(bottom = 8.dp)
                            )
                        }
                    }
                    RichTextEditor(
                        value = description,
                        onValueChange = { 
                            description = it
                            viewModel.clearAIMessages()
                        },
                        placeholder = if (tipoPublicacion == "trabajo") {
                            "Una descripción detallada permite obtener más visitas. Incluye información sobre el trabajo, requisitos y beneficios.\n\nUsa los botones de formato para resaltar texto importante.\n\n✨ Usa el botón IA para mejorar tu texto automáticamente."
                        } else {
                            "Escribe el contenido de tu artículo de blog aquí.\n\nUsa los botones de formato para resaltar texto importante.\n\n✨ Usa el botón IA para mejorar tu texto automáticamente."
                        },
                        maxLines = 15,
                        enabled = !uiState.isLoading && !uiState.isAIEnhancing && !uiState.isOCRProcessing,
                        // Funciones de IA
                        onAIEnhanceClick = {
                            viewModel.enhanceTextWithAI(
                                currentText = description,
                                type = if (tipoPublicacion == "trabajo") "job" else "blog"
                            ) { enhancedText ->
                                description = enhancedText
                            }
                        },
                        onOCRClick = if (uiState.existingImageUrls.isNotEmpty() || uiState.selectedImages.isNotEmpty()) {
                            {
                                showOCRDialog = true
                            }
                        } else null,
                        isAILoading = uiState.isAIEnhancing,
                        isOCRLoading = uiState.isOCRProcessing,
                        // Límites de uso de IA
                        aiUsesRemaining = uiState.aiUsesRemaining,
                        aiIsPremium = uiState.aiIsPremium,
                        onUpgradeToPremiumClick = {
                            // TODO: Navegar a pantalla de suscripción premium
                        }
                    )
                }
                
                Spacer(Modifier.height(24.dp))
                
                // Selector de tipo de publicación (solo para admins)
                if (isAdmin) {
                    Column(modifier = Modifier.padding(horizontal = 16.dp)) {
                        Text(
                            "Tipo de Publicación",
                            style = MaterialTheme.typography.labelLarge,
                            fontWeight = FontWeight.Medium,
                            color = MaterialTheme.colorScheme.onSurface,
                            modifier = Modifier.padding(bottom = 8.dp)
                        )
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            // Botón Trabajo
                            FilterChip(
                                selected = tipoPublicacion == "trabajo",
                                onClick = { tipoPublicacion = "trabajo" },
                                label = { Text("📋 Trabajo") },
                                modifier = Modifier.weight(1f)
                            )
                            // Botón Blog
                            FilterChip(
                                selected = tipoPublicacion == "post",
                                onClick = { tipoPublicacion = "post" },
                                label = { Text("📝 Blog") },
                                modifier = Modifier.weight(1f)
                            )
                        }
                    }
                    Spacer(Modifier.height(16.dp))
                }
                
                // Selector de Categoría (solo para blogs)
                if (tipoPublicacion == "post") {
                    Column(modifier = Modifier.padding(horizontal = 16.dp)) {
                        CategoryDropdown(
                            label = "Categoría",
                            items = uiState.categorias,
                            selectedItem = selectedCategoria,
                            modifier = Modifier.fillMaxWidth(),
                            leadingIcon = Icons.Default.Folder
                        ) { cat -> selectedCategoria = cat }
                    }
                    Spacer(Modifier.height(16.dp))
                }
                
                // Ubicación y Empresa - uno debajo del otro (solo para trabajos)
                if (tipoPublicacion == "trabajo") {
                    Column(modifier = Modifier.padding(horizontal = 16.dp)) {
                        // Selector de Ubicación Inteligente con Sedes
                        SmartLocationSelector(
                            selectedLocation = selectedUbicacionCompleta,
                            onLocationSelected = { ubicacion ->
                                android.util.Log.d("EditJobScreen", "Ubicación seleccionada: depto=${ubicacion.departamento}, prov=${ubicacion.provincia}, dist=${ubicacion.distrito}")
                                selectedUbicacionCompleta = ubicacion
                                
                                // Buscar Category correspondiente al departamento para compatibilidad
                                if (ubicacion.departamento.isNotBlank()) {
                                    val deptoNormalizado = ubicacion.departamento.trim().lowercase()
                                    
                                    // Buscar coincidencia exacta primero
                                    var matchingCategory = uiState.ubicaciones.find { cat ->
                                        cat.name.trim().lowercase() == deptoNormalizado
                                    }
                                    
                                    // Si no hay coincidencia exacta, buscar si contiene el nombre
                                    if (matchingCategory == null) {
                                        matchingCategory = uiState.ubicaciones.find { cat ->
                                            cat.name.lowercase().contains(deptoNormalizado) ||
                                            deptoNormalizado.contains(cat.name.lowercase())
                                        }
                                    }
                                    
                                    selectedUbicacion = matchingCategory
                                    android.util.Log.d("EditJobScreen", "Category sincronizado: ${matchingCategory?.name ?: "NO ENCONTRADO"}")
                                }
                            },
                            sedes = companySedes,
                            showSedesFirst = companySedes.isNotEmpty(),
                            canManageSedes = true,
                            onSedeCreated = { nuevaSede ->
                                android.util.Log.d("EditJobScreen", "Nueva sede creada: ${nuevaSede.nombre}")
                            },
                            label = "Ubicación *",
                            placeholder = "Buscar distrito, provincia o departamento...",
                            modifier = Modifier.fillMaxWidth()
                        )
                        
                        Spacer(Modifier.height(12.dp))
                        
                        // Selector de Empresa (abajo) - SIEMPRE VISIBLE - con búsqueda
                        if (isAdmin) {
                            // Admin: puede seleccionar cualquier empresa
                            SearchableDropdown(
                                label = "Empresa",
                                items = uiState.empresas,
                                selectedItem = selectedEmpresa,
                                onItemSelected = { cat -> selectedEmpresa = cat },
                                modifier = Modifier.fillMaxWidth(),
                                leadingIcon = Icons.Default.Business,
                                placeholder = "Buscar empresa...",
                                emptyMessage = "No se encontraron empresas"
                            )
                        } else if (selectedEmpresa != null || uiState.userCompanyId != null) {
                            // Empresa normal: mostrar su empresa (solo lectura)
                            val empresaNombre = selectedEmpresa?.name 
                                ?: uiState.empresas.find { it.id == uiState.userCompanyId }?.name 
                                ?: "Tu empresa"
                            OutlinedTextField(
                                value = empresaNombre,
                                onValueChange = {},
                                readOnly = true,
                                enabled = false,
                                label = { Text("Empresa") },
                                modifier = Modifier.fillMaxWidth(),
                                leadingIcon = { Icon(Icons.Default.Business, contentDescription = null) },
                                colors = OutlinedTextFieldDefaults.colors(
                                    disabledTextColor = MaterialTheme.colorScheme.onSurface,
                                    disabledBorderColor = MaterialTheme.colorScheme.outline.copy(alpha = 0.5f),
                                    disabledLabelColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                    disabledLeadingIconColor = MaterialTheme.colorScheme.onSurfaceVariant
                                ),
                                supportingText = {
                                    Text(
                                        "Tu empresa se asigna automáticamente",
                                        color = MaterialTheme.colorScheme.primary.copy(alpha = 0.8f)
                                )
                                }
                            )
                        }
                    }
                    
                    Spacer(Modifier.height(16.dp))
                    
                    // Botón para mostrar más detalles (solo para trabajos)
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        ActionButton(
                            text = if (showMoreOptions) "Ocultar Detalles" else "Más Detalles",
                            icon = null,
                            onClick = { showMoreOptions = !showMoreOptions }
                        )
                    }
                    
                    Spacer(Modifier.height(16.dp))
                    
                    // Opciones avanzadas expandibles (solo para trabajos)
                    if (showMoreOptions) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp)
                            .background(
                                MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f),
                                RoundedCornerShape(12.dp)
                            )
                            .padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(16.dp)
                    ) {
                        Text(
                            "Información del Trabajo",
                            style = MaterialTheme.typography.titleSmall,
                            fontWeight = FontWeight.Medium,
                            color = MaterialTheme.colorScheme.primary
                        )
                        
                        // Salario Mín/Máx
                        Row(Modifier.fillMaxWidth()) {
                            OutlinedTextField(
                                value = salarioMin,
                                onValueChange = { salarioMin = it },
                                label = { Text("Salario Mín.") },
                                modifier = Modifier.weight(1f),
                                keyboardOptions = androidx.compose.foundation.text.KeyboardOptions(
                                    keyboardType = androidx.compose.ui.text.input.KeyboardType.Number
                                )
                            )
                            Spacer(Modifier.width(8.dp))
                            OutlinedTextField(
                                value = salarioMax,
                                onValueChange = { salarioMax = it },
                                label = { Text("Salario Máx.") },
                                modifier = Modifier.weight(1f),
                                keyboardOptions = androidx.compose.foundation.text.KeyboardOptions(
                                    keyboardType = androidx.compose.ui.text.input.KeyboardType.Number
                                )
                            )
                        }
                        
                        // Vacantes
                        OutlinedTextField(
                            value = vacantes,
                            onValueChange = { vacantes = it },
                            label = { Text("Nº de Vacantes") },
                            modifier = Modifier.fillMaxWidth(),
                            keyboardOptions = androidx.compose.foundation.text.KeyboardOptions(
                                keyboardType = androidx.compose.ui.text.input.KeyboardType.Number
                            )
                        )
                        
                        Divider()
                        
                        Text(
                            "Categorías",
                            style = MaterialTheme.typography.titleSmall,
                            fontWeight = FontWeight.Medium,
                            color = MaterialTheme.colorScheme.primary
                        )
                        
                        // Mostrar selector de empresas solo para admins
                        // Las empresas normales solo pueden editar trabajos de su propia empresa
                        val isAdmin = AuthManager.isUserAdmin()
                        val currentEmpresa = selectedEmpresa
                        
                        if (uiState.userCompanyId != null && !isAdmin) {
                            // Empresa normal: mostrar solo lectura con su empresa
                            OutlinedTextField(
                                value = currentEmpresa?.name ?: "Tu empresa",
                                onValueChange = {},
                                readOnly = true,
                                enabled = false,
                                label = { Text("Empresa") },
                                modifier = Modifier.fillMaxWidth(),
                                leadingIcon = { Icon(Icons.Default.Business, contentDescription = null) },
                                colors = TextFieldDefaults.colors(
                                    disabledContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                                )
                            )
                        } else if (isAdmin) {
                            // Admin: puede seleccionar cualquier empresa
                            SearchableDropdown(
                                label = "Empresa",
                                items = uiState.empresas,
                                selectedItem = selectedEmpresa,
                                onItemSelected = { cat -> selectedEmpresa = cat },
                                placeholder = "Buscar empresa...",
                                emptyMessage = "No se encontraron empresas"
                            )
                        }
                        
                        CategoryDropdown(
                            label = "Cultivo",
                            items = uiState.cultivos,
                            selectedItem = selectedCultivo
                        ) { cat -> selectedCultivo = cat }
                        
                        CategoryDropdown(
                            label = "Tipo de Puesto",
                            items = uiState.tiposPuesto,
                            selectedItem = selectedTipoPuesto
                        ) { cat -> selectedTipoPuesto = cat }
                        
                        Divider()
                        
                        Text(
                            "Beneficios Incluidos",
                            style = MaterialTheme.typography.titleSmall,
                            fontWeight = FontWeight.Medium,
                            color = MaterialTheme.colorScheme.primary
                        )
                        
                        BenefitSwitch(
                            text = "Alojamiento",
                            checked = alojamiento,
                            onCheckedChange = { alojamiento = it }
                        )
                        BenefitSwitch(
                            text = "Transporte",
                            checked = transporte,
                            onCheckedChange = { transporte = it }
                        )
                        BenefitSwitch(
                            text = "Alimentación",
                            checked = alimentacion,
                            onCheckedChange = { alimentacion = it }
                        )
                    }
                    }
                }
                
                // Opciones adicionales
                Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                    // Switch para permitir comentarios (por defecto activado)
                    BenefitSwitch(
                        text = "💬 Permitir comentarios",
                        checked = comentariosHabilitados,
                        onCheckedChange = { comentariosHabilitados = it }
                    )
                    
                    BenefitSwitch(
                        text = "Publicar también en Facebook",
                        checked = publishToFacebook,
                        onCheckedChange = { publishToFacebook = it }
                    )
                    OptionRow(
                        icon = Icons.Default.Public,
                        text = "Todo el mundo puede ver esta publicación",
                        onClick = { /* TODO: Configurar privacidad */ }
                    )
                    OptionRow(
                        icon = Icons.Default.Settings,
                        text = "Más opciones",
                        onClick = { /* TODO: Mostrar más opciones */ }
                    )
                }
                
                Spacer(Modifier.height(80.dp)) // Espacio para la barra inferior
                
                uiState.error?.let { error ->
                    Card(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        colors = CardDefaults.cardColors(
                            containerColor = MaterialTheme.colorScheme.errorContainer
                        )
                    ) {
                        Text(
                            error,
                            color = MaterialTheme.colorScheme.onErrorContainer,
                            modifier = Modifier.padding(16.dp)
                        )
                    }
                }
            }
        }
    }

    // Diálogo para seleccionar imagen para OCR
    if (showOCRDialog && (uiState.existingImageUrls.isNotEmpty() || uiState.selectedImages.isNotEmpty())) {
        EditOCRImageSelectorDialog(
            existingImageUrls = uiState.existingImageUrls,
            newImages = uiState.selectedImages,
            isProcessing = uiState.isOCRProcessing,
            onExistingImageSelected = { imageUrl ->
                showOCRDialog = false
                // Extraer texto de la imagen existente (ya es URL HTTP)
                viewModel.extractTextFromImage(
                    imageUrl = imageUrl,
                    enhance = true,
                    context = null // No necesita context porque ya es URL HTTP
                ) { extractedText, enhancedText ->
                    val textToUse = enhancedText ?: extractedText
                    if (textToUse.isNotBlank()) {
                        description = if (description.isBlank()) {
                            textToUse
                        } else {
                            "$description\n\n$textToUse"
                        }
                    }
                }
            },
            onNewImageSelected = { uri ->
                showOCRDialog = false
                // Extraer texto de la nueva imagen (necesita subirse primero)
                viewModel.extractTextFromImage(
                    imageUrl = uri.toString(),
                    enhance = true,
                    context = context
                ) { extractedText, enhancedText ->
                    val textToUse = enhancedText ?: extractedText
                    if (textToUse.isNotBlank()) {
                        description = if (description.isBlank()) {
                            textToUse
                        } else {
                            "$description\n\n$textToUse"
                        }
                    }
                }
            },
            onDismiss = { showOCRDialog = false }
        )
    }
}

/**
 * Diálogo para seleccionar imagen para OCR en EditJobScreen
 */
@Composable
private fun EditOCRImageSelectorDialog(
    existingImageUrls: List<String>,
    newImages: List<Uri>,
    isProcessing: Boolean,
    onExistingImageSelected: (String) -> Unit,
    onNewImageSelected: (Uri) -> Unit,
    onDismiss: () -> Unit
) {
    val context = LocalContext.current
    
    androidx.compose.ui.window.Dialog(onDismissRequest = { if (!isProcessing) onDismiss() }) {
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            shape = RoundedCornerShape(16.dp)
        ) {
            Column(
                modifier = Modifier.padding(20.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                // Icono y título
                Icon(
                    Icons.Default.Folder,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(40.dp)
                )
                Spacer(Modifier.height(12.dp))
                Text(
                    text = "Selecciona una imagen",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
                Spacer(Modifier.height(8.dp))
                Text(
                    text = "La IA extraerá el texto de la imagen y lo convertirá en una descripción profesional",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    textAlign = androidx.compose.ui.text.style.TextAlign.Center
                )
                
                Spacer(Modifier.height(20.dp))
                
                if (isProcessing) {
                    // Mostrar indicador de carga
                    CircularProgressIndicator(
                        modifier = Modifier.size(48.dp)
                    )
                    Spacer(Modifier.height(12.dp))
                    Text(
                        text = "Procesando imagen...",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.primary
                    )
                } else {
                    // Grid de imágenes
                    LazyRow(
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        // Imágenes existentes
                        itemsIndexed(existingImageUrls) { index, imageUrl ->
                            Box(
                                modifier = Modifier
                                    .size(80.dp)
                                    .clip(RoundedCornerShape(8.dp))
                                    .border(
                                        2.dp,
                                        MaterialTheme.colorScheme.outline.copy(alpha = 0.3f),
                                        RoundedCornerShape(8.dp)
                                    )
                                    .clickable { onExistingImageSelected(imageUrl) }
                            ) {
                                AsyncImage(
                                    model = ImageRequest.Builder(context)
                                        .data(imageUrl)
                                        .crossfade(true)
                                        .build(),
                                    contentDescription = "Imagen ${index + 1}",
                                    modifier = Modifier.fillMaxSize(),
                                    contentScale = ContentScale.Crop
                                )
                                // Overlay para indicar que es clickeable
                                Box(
                                    modifier = Modifier
                                        .fillMaxSize()
                                        .background(Color.Black.copy(alpha = 0.3f)),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Icon(
                                        Icons.Default.Folder,
                                        contentDescription = null,
                                        tint = Color.White,
                                        modifier = Modifier.size(24.dp)
                                    )
                                }
                            }
                        }
                        
                        // Nuevas imágenes
                        itemsIndexed(newImages) { index, uri ->
                            Box(
                                modifier = Modifier
                                    .size(80.dp)
                                    .clip(RoundedCornerShape(8.dp))
                                    .border(
                                        2.dp,
                                        MaterialTheme.colorScheme.primary.copy(alpha = 0.5f),
                                        RoundedCornerShape(8.dp)
                                    )
                                    .clickable { onNewImageSelected(uri) }
                            ) {
                                AsyncImage(
                                    model = uri,
                                    contentDescription = "Nueva imagen ${index + 1}",
                                    modifier = Modifier.fillMaxSize(),
                                    contentScale = ContentScale.Crop
                                )
                                // Overlay para indicar que es clickeable
                                Box(
                                    modifier = Modifier
                                        .fillMaxSize()
                                        .background(Color.Black.copy(alpha = 0.3f)),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Icon(
                                        Icons.Default.Folder,
                                        contentDescription = null,
                                        tint = Color.White,
                                        modifier = Modifier.size(24.dp)
                                    )
                                }
                            }
                        }
                    }
                }
                
                Spacer(Modifier.height(20.dp))
                
                // Botón cancelar
                OutlinedButton(
                    onClick = onDismiss,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !isProcessing
                ) {
                    Text("Cancelar")
                }
            }
        }
    }
}

@Composable
private fun EditImageSection(
    existingImageUrls: List<String>,
    existingImageIds: List<Int>,
    newImages: List<Uri>,
    imagesLoaded: Boolean,
    isLoading: Boolean,
    onAddImage: () -> Unit,
    onRemoveExisting: (String) -> Unit,
    onRemoveNew: (Uri) -> Unit,
    onReorderExisting: (Int, Int) -> Unit,
    onReorderNew: (Int, Int) -> Unit
) {
    Column(modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp)) {
        val totalImages = existingImageUrls.size + newImages.size
        
        // Mostrar loading SOLO si no hay imágenes y está cargando
        if (totalImages == 0 && !imagesLoaded && isLoading) {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(70.dp),
                contentAlignment = Alignment.Center
            ) {
                Column(
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    CircularProgressIndicator(modifier = Modifier.size(40.dp))
                    Text(
                        "Cargando imágenes...",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
        } else {
            // SIEMPRE mostrar el LazyRow (incluso si está vacío, para mostrar el botón de agregar)
            LazyRow(
                horizontalArrangement = Arrangement.spacedBy(6.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                // Mostrar imágenes existentes primero
                itemsIndexed(existingImageUrls) { index, imageUrl ->
                    EditTikTokImagePreview(
                        imageUrl = imageUrl,
                        isFeatured = index == 0 && newImages.isEmpty(),
                        onRemove = { onRemoveExisting(imageUrl) },
                        modifier = Modifier.size(70.dp)
                    )
                }
                
                // Mostrar nuevas imágenes seleccionadas
                itemsIndexed(newImages) { index, uri ->
                    val totalIndex = existingImageUrls.size + index
                    TikTokImagePreview(
                        uri = uri,
                        isFeatured = totalIndex == 0,
                        onRemove = { onRemoveNew(uri) },
                        modifier = Modifier.size(70.dp)
                    )
                }
                
                // Botón para agregar más imágenes (SIEMPRE visible si hay menos de 10)
                if (totalImages < 10) {
                    item {
                        TikTokAddImageButton(
                            onClick = onAddImage,
                            modifier = Modifier.size(70.dp)
                        )
                    }
                }
            }
            
            // Mostrar mensaje solo si no hay imágenes Y ya se cargaron (debajo del botón)
            if (totalImages == 0 && imagesLoaded) {
                Spacer(Modifier.height(8.dp))
                Text(
                    "No hay imágenes en este trabajo",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f),
                    modifier = Modifier.padding(vertical = 8.dp)
                )
            }
        }
    }
}

@Composable
private fun EditTikTokImagePreview(
    imageUrl: String,
    isFeatured: Boolean,
    onRemove: () -> Unit,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    Box(modifier = modifier) {
        AsyncImage(
            model = ImageRequest.Builder(context)
                .data(imageUrl)
                .crossfade(true)
                .build(),
            contentDescription = if (isFeatured) "Imagen destacada" else "Imagen",
            modifier = Modifier
                .fillMaxSize()
                .clip(RoundedCornerShape(6.dp))
                .border(
                    width = if (isFeatured) 2.dp else 0.dp,
                    color = if (isFeatured) MaterialTheme.colorScheme.primary else Color.Transparent,
                    shape = RoundedCornerShape(6.dp)
                ),
            contentScale = ContentScale.Crop
        )
        
        // Botón de eliminar
        IconButton(
            onClick = onRemove,
            modifier = Modifier
                .align(Alignment.TopEnd)
                .size(20.dp)
                .background(Color.Black.copy(alpha = 0.7f), CircleShape)
                .padding(2.dp)
        ) {
            Icon(
                Icons.Default.Close,
                contentDescription = "Eliminar",
                tint = Color.White,
                modifier = Modifier.size(12.dp)
            )
        }
        
        // Badge "Portada"
        if (isFeatured) {
            Text(
                text = "Portada",
                style = MaterialTheme.typography.labelSmall,
                fontSize = 9.sp,
                color = Color.White,
                fontWeight = FontWeight.Bold,
                modifier = Modifier
                    .align(Alignment.BottomCenter)
                    .background(
                        Color.Black.copy(alpha = 0.7f),
                        RoundedCornerShape(bottomStart = 6.dp, bottomEnd = 6.dp)
                    )
                    .padding(horizontal = 6.dp, vertical = 2.dp)
            )
        }
    }
}

@Composable
private fun TikTokImagePreview(
    uri: Uri,
    isFeatured: Boolean,
    onRemove: () -> Unit,
    modifier: Modifier = Modifier
) {
    Box(modifier = modifier) {
        AsyncImage(
            model = uri,
            contentDescription = if (isFeatured) "Imagen destacada" else "Imagen",
            modifier = Modifier
                .fillMaxSize()
                .clip(RoundedCornerShape(6.dp))
                .border(
                    width = if (isFeatured) 2.dp else 0.dp,
                    color = if (isFeatured) MaterialTheme.colorScheme.primary else Color.Transparent,
                    shape = RoundedCornerShape(6.dp)
                ),
            contentScale = ContentScale.Crop
        )
        
        IconButton(
            onClick = onRemove,
            modifier = Modifier
                .align(Alignment.TopEnd)
                .size(20.dp)
                .background(Color.Black.copy(alpha = 0.7f), CircleShape)
                .padding(2.dp)
        ) {
            Icon(
                Icons.Default.Close,
                contentDescription = "Eliminar",
                tint = Color.White,
                modifier = Modifier.size(12.dp)
            )
        }
        
        if (isFeatured) {
            Text(
                text = "Portada",
                style = MaterialTheme.typography.labelSmall,
                fontSize = 9.sp,
                color = Color.White,
                fontWeight = FontWeight.Bold,
                modifier = Modifier
                    .align(Alignment.BottomCenter)
                    .background(
                        Color.Black.copy(alpha = 0.7f),
                        RoundedCornerShape(bottomStart = 6.dp, bottomEnd = 6.dp)
                    )
                    .padding(horizontal = 6.dp, vertical = 2.dp)
            )
        }
    }
}

@Composable
private fun TikTokAddImageButton(
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Box(
        modifier = modifier
            .clip(RoundedCornerShape(6.dp))
            .border(
                1.5.dp,
                MaterialTheme.colorScheme.primary.copy(alpha = 0.5f),
                RoundedCornerShape(6.dp)
            )
            .clickable(onClick = onClick)
            .background(
                MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.2f),
                RoundedCornerShape(6.dp)
            ),
        contentAlignment = Alignment.Center
    ) {
        Icon(
            Icons.Default.Add,
            contentDescription = "Agregar imagen",
            tint = MaterialTheme.colorScheme.primary,
            modifier = Modifier.size(28.dp)
        )
    }
}

@Composable
private fun RowScope.ActionButton(
    text: String,
    icon: androidx.compose.ui.graphics.vector.ImageVector?,
    onClick: () -> Unit
) {
    OutlinedButton(
        onClick = onClick,
        modifier = Modifier.weight(1f),
        colors = ButtonDefaults.outlinedButtonColors(
            contentColor = MaterialTheme.colorScheme.onSurface
        )
    ) {
        icon?.let {
            Icon(it, contentDescription = null, modifier = Modifier.size(18.dp))
            Spacer(Modifier.width(4.dp))
        }
        Text(text, style = MaterialTheme.typography.bodyMedium)
    }
}

@Composable
private fun OptionRow(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    text: String,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(
            icon,
            contentDescription = null,
            modifier = Modifier.size(20.dp),
            tint = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Spacer(Modifier.width(12.dp))
        Text(
            text = text,
            style = MaterialTheme.typography.bodyMedium,
            modifier = Modifier.weight(1f)
        )
        Icon(
            Icons.Default.ArrowForwardIos,
            contentDescription = null,
            modifier = Modifier.size(16.dp),
            tint = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.6f)
        )
    }
}

@Composable
private fun EditBottomActionBar(
    isLoading: Boolean,
    loadingMessage: String,
    onSave: () -> Unit,
    onDelete: () -> Unit
) {
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shadowElevation = 8.dp,
        color = MaterialTheme.colorScheme.surface
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Botón Eliminar
            Button(
                onClick = onDelete,
                modifier = Modifier.weight(0.4f),
                enabled = !isLoading,
                colors = ButtonDefaults.buttonColors(
                    containerColor = MaterialTheme.colorScheme.errorContainer,
                    contentColor = MaterialTheme.colorScheme.onErrorContainer
                )
            ) {
                Icon(Icons.Default.Delete, contentDescription = null, modifier = Modifier.size(18.dp))
            }
            
            // Botón Guardar
            Button(
                onClick = onSave,
                modifier = Modifier.weight(1f),
                enabled = !isLoading,
                colors = ButtonDefaults.buttonColors(
                    containerColor = MaterialTheme.colorScheme.primary
                )
            ) {
                if (isLoading) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(18.dp),
                        color = MaterialTheme.colorScheme.onPrimary,
                        strokeWidth = 2.dp
                    )
                    Spacer(Modifier.width(8.dp))
                    Text(loadingMessage.take(20))
                } else {
                    Icon(Icons.Default.Star, contentDescription = null, modifier = Modifier.size(18.dp))
                    Spacer(Modifier.width(8.dp))
                    Text("Guardar")
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun CategoryDropdown(
    label: String, 
    items: List<Category>, 
    selectedItem: Category?,
    modifier: Modifier = Modifier,
    leadingIcon: androidx.compose.ui.graphics.vector.ImageVector? = null,
    onItemSelected: (Category) -> Unit
) {
    var expanded by remember { mutableStateOf(false) }

    ExposedDropdownMenuBox(expanded = expanded, onExpandedChange = { expanded = !expanded }) {
        OutlinedTextField(
            value = selectedItem?.name ?: "",
            onValueChange = {},
            readOnly = true,
            label = { Text(label) },
            leadingIcon = leadingIcon?.let { { Icon(it, contentDescription = null) } },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            modifier = modifier.fillMaxWidth().menuAnchor()
        )
        ExposedDropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            items.forEach { item ->
                DropdownMenuItem(
                    text = { Text(item.name) },
                    onClick = {
                        onItemSelected(item)
                        expanded = false
                    }
                )
            }
        }
    }
}

@Composable
private fun BenefitSwitch(text: String, checked: Boolean, onCheckedChange: (Boolean) -> Unit) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp)
            .clickable { onCheckedChange(!checked) },
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(text, modifier = Modifier.weight(1f))
        Switch(checked = checked, onCheckedChange = onCheckedChange)
    }
}
