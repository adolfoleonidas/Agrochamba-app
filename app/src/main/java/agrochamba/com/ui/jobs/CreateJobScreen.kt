package agrochamba.com.ui.jobs

import android.net.Uri
import android.util.Log
import android.widget.Toast
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.gestures.detectDragGesturesAfterLongPress
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.AddPhotoAlternate
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ArrowForwardIos
import androidx.compose.material.icons.filled.Business
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.ExpandMore
import androidx.compose.material.icons.filled.Folder
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Link
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Public
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.Star
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.zIndex
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import agrochamba.com.data.AuthManager
import agrochamba.com.data.Category
import agrochamba.com.ui.common.RichTextEditor
import agrochamba.com.utils.textToHtml
import coil.compose.AsyncImage

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CreateJobScreen(navController: NavController, viewModel: CreateJobViewModel = viewModel(key = "create_job")) {
    val uiState = viewModel.uiState
    val context = LocalContext.current

    val imagePickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetMultipleContents(),
        onResult = { uris -> viewModel.onImagesSelected(uris) }
    )

    // Usar key para asegurar que el estado se reinicialice cada vez que se navega a esta pantalla
    var title by remember { mutableStateOf("") }
    var description by remember { mutableStateOf("") }
    var salarioMin by remember { mutableStateOf("") }
    var salarioMax by remember { mutableStateOf("") }
    var vacantes by remember { mutableStateOf("") }
    var selectedUbicacion by remember { mutableStateOf<Category?>(null) }
    var selectedEmpresa by remember { mutableStateOf<Category?>(null) }
    var selectedCultivo by remember { mutableStateOf<Category?>(null) }
    var selectedTipoPuesto by remember { mutableStateOf<Category?>(null) }
    var alojamiento by remember { mutableStateOf(false) }
    var transporte by remember { mutableStateOf(false) }
    var alimentacion by remember { mutableStateOf(false) }
    // IMPORTANTE: Siempre inicializar en false para que el usuario tenga control explícito
    var publishToFacebook by remember { mutableStateOf(false) }
    // Comentarios habilitados por defecto (true)
    var comentariosHabilitados by remember { mutableStateOf(true) }
    
    // Selector de tipo de publicación (solo para admins)
    val isAdmin = AuthManager.isUserAdmin()
    var tipoPublicacion by remember { mutableStateOf("trabajo") } // "trabajo" o "post" (blog)
    
    var showMoreOptions by remember { mutableStateOf(false) }

    // Reiniciar el estado cuando se navega a esta pantalla
    LaunchedEffect(Unit) {
        // Asegurar que el estado se reinicialice cuando se entra a la pantalla
        publishToFacebook = false
    }
    
    // Reiniciar el estado cuando se crea un trabajo exitosamente
    LaunchedEffect(uiState.postSuccess) {
        if (uiState.postSuccess) {
            // Reiniciar el estado antes de navegar
            publishToFacebook = false
            val mensaje = if (tipoPublicacion == "trabajo") {
                "¡Trabajo creado con éxito! Está pendiente de revisión por un administrador."
            } else {
                "¡Artículo de blog creado con éxito!"
            }
            Toast.makeText(context, mensaje, Toast.LENGTH_LONG).show()
            navController.popBackStack()
        }
    }

    // Auto-seleccionar empresa del usuario si no es admin
    LaunchedEffect(uiState.userCompanyId, uiState.empresas) {
        val isAdmin = AuthManager.isUserAdmin()
        if (!isAdmin && uiState.userCompanyId != null && selectedEmpresa == null && uiState.empresas.isNotEmpty()) {
            selectedEmpresa = uiState.empresas.find { it.id == uiState.userCompanyId }
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (tipoPublicacion == "trabajo") "Nuevo Trabajo" else "Nuevo Blog") },
                navigationIcon = { 
                    IconButton(onClick = { navController.popBackStack() }) { 
                        Icon(Icons.Default.ArrowBack, null) 
                    }
                }
            )
        },
        bottomBar = {
            BottomActionBar(
                isLoading = uiState.isLoading,
                loadingMessage = uiState.loadingMessage,
                onPublish = {
                    // Debug: verificar el tipo de publicación
                    android.util.Log.d("CreateJobScreen", "Tipo de publicación seleccionado: $tipoPublicacion")
                    
                    if (title.isBlank()) {
                        Toast.makeText(context, "El título es obligatorio", Toast.LENGTH_SHORT).show()
                        return@BottomActionBar
                    }
                    if (description.isBlank()) {
                        Toast.makeText(context, "La descripción es obligatoria", Toast.LENGTH_SHORT).show()
                        return@BottomActionBar
                    }
                    
                    // Validaciones específicas según el tipo de publicación
                    // IMPORTANTE: Solo validar ubicación y empresa si es TRABAJO
                    if (tipoPublicacion == "trabajo") {
                        android.util.Log.d("CreateJobScreen", "Validando campos de TRABAJO")
                        // Validaciones para trabajos
                        if (selectedUbicacion == null) {
                            Toast.makeText(context, "La ubicación es obligatoria", Toast.LENGTH_SHORT).show()
                            return@BottomActionBar
                        }
                        
                        // Empresa es OPCIONAL - usar la seleccionada o la del usuario automáticamente
                        val empresaId = if (isAdmin) {
                            // Admin: puede seleccionar empresa o dejarla vacía
                            selectedEmpresa?.id
                        } else {
                            // Empresa normal: usar su empresa automáticamente si existe
                            uiState.userCompanyId
                        }
                        
                        val ubicacionIdValue = selectedUbicacion!!.id
                        
                        val jobData = mutableMapOf<String, Any?>(
                            "post_type" to "trabajo",
                            "title" to title.trim(),
                            "content" to description.textToHtml(),
                            "salario_min" to (salarioMin.toIntOrNull() ?: 0),
                            "salario_max" to (salarioMax.toIntOrNull() ?: 0),
                            "vacantes" to (vacantes.toIntOrNull() ?: 1),
                            "ubicacion_id" to ubicacionIdValue,
                            "cultivo_id" to selectedCultivo?.id,
                            "tipo_puesto_id" to selectedTipoPuesto?.id,
                            "alojamiento" to alojamiento,
                            "transporte" to transporte,
                            "alimentacion" to alimentacion,
                            "comentarios_habilitados" to comentariosHabilitados,
                            "publish_to_facebook" to publishToFacebook
                        )
                        
                        // Agregar empresa_id solo si está presente (opcional)
                        empresaId?.let { jobData["empresa_id"] = it }
                        
                        viewModel.createJob(jobData, context)
                    } else {
                        // Para blogs (post) - NO requiere ubicación ni empresa
                        android.util.Log.d("CreateJobScreen", "Creando BLOG - sin validar ubicación ni empresa")
                        val blogData = mapOf(
                            "post_type" to "post",
                            "title" to title.trim(),
                            "content" to description.textToHtml(),
                            "comentarios_habilitados" to comentariosHabilitados,
                            "publish_to_facebook" to publishToFacebook
                        )
                        viewModel.createJob(blogData, context)
                    }
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
                // Sección de imágenes - estilo TikTok
                ImageSection(
                    images = uiState.selectedImages,
                    onAddImage = { imagePickerLauncher.launch("image/*") },
                    onRemoveImage = { viewModel.removeImage(it) },
                    onReorderImages = { from, to -> viewModel.reorderImages(from, to) }
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
                            text = "Agrega un título llamativo",
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
                
                // Descripción (texto dinámico según el tipo)
                Column(modifier = Modifier.padding(horizontal = 16.dp)) {
                    Text(
                        if (tipoPublicacion == "trabajo") "Descripción del Trabajo" else "Descripción del Blog",
                        style = MaterialTheme.typography.labelLarge,
                        fontWeight = FontWeight.Medium,
                        color = MaterialTheme.colorScheme.onSurface,
                        modifier = Modifier.padding(bottom = 8.dp)
                    )
                    RichTextEditor(
                        value = description,
                        onValueChange = { description = it },
                        placeholder = if (tipoPublicacion == "trabajo") {
                            "Una descripción detallada permite obtener más visitas. Incluye información sobre el trabajo, requisitos y beneficios.\n\nUsa los botones de formato para resaltar texto importante."
                        } else {
                            "Escribe el contenido de tu artículo de blog. Usa los botones de formato para resaltar texto importante y hacer tu contenido más atractivo."
                        },
                        maxLines = 15,
                        enabled = !uiState.isLoading
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
                
                // Ubicación y Empresa - uno debajo del otro (solo para trabajos)
                if (tipoPublicacion == "trabajo") {
                    Column(modifier = Modifier.padding(horizontal = 16.dp)) {
                        // Selector de Ubicación (arriba)
                        CategoryDropdown(
                            label = "Ubicación *",
                            items = uiState.ubicaciones,
                            selectedItem = selectedUbicacion,
                            modifier = Modifier.fillMaxWidth(),
                            leadingIcon = Icons.Default.LocationOn
                        ) { cat -> selectedUbicacion = cat }
                        
                        Spacer(Modifier.height(12.dp))
                        
                        // Selector de Empresa (abajo) - SIEMPRE VISIBLE
                        CategoryDropdown(
                            label = "Empresa",
                            items = uiState.empresas,
                            selectedItem = selectedEmpresa,
                            modifier = Modifier.fillMaxWidth(),
                            leadingIcon = Icons.Default.Business
                        ) { cat -> 
                            android.util.Log.d("CreateJobScreen", "Empresa seleccionada: ${cat.name}")
                            selectedEmpresa = cat 
                        }
                    }
                    
                    Spacer(Modifier.height(16.dp))
                }
                
                // Botón para mostrar más detalles (solo para trabajos)
                if (tipoPublicacion == "trabajo") {
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
                    
                    // Opciones avanzadas expandibles
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
}

@Composable
private fun ImageSection(
    images: List<Uri>,
    onAddImage: () -> Unit,
    onRemoveImage: (Uri) -> Unit,
    onReorderImages: (Int, Int) -> Unit
) {
    Column(modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp)) {
        // Mostrar todas las imágenes seleccionadas en una fila compacta
        LazyRow(
            horizontalArrangement = Arrangement.spacedBy(6.dp),
            modifier = Modifier.fillMaxWidth()
        ) {
            // Mostrar todas las imágenes seleccionadas (pequeñas como TikTok)
            itemsIndexed(images) { index, uri ->
                TikTokImagePreview(
                    uri = uri,
                    isFeatured = index == 0,
                    onRemove = { onRemoveImage(uri) },
                    modifier = Modifier.size(70.dp)
                )
            }
            
            // Botón grande para agregar más imágenes (estilo TikTok)
            if (images.size < 10) {
                item {
                    TikTokAddImageButton(
                        onClick = onAddImage,
                        modifier = Modifier.size(70.dp)
                    )
                }
            }
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
        
        // Botón de eliminar pequeño en la esquina superior derecha
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
        
        // Badge "Portada" solo en la primera imagen
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
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Icon(
                Icons.Default.Add,
                contentDescription = "Agregar imagen",
                tint = MaterialTheme.colorScheme.primary,
                modifier = Modifier.size(28.dp)
            )
        }
    }
}

@Composable
private fun androidx.compose.foundation.layout.RowScope.ActionButton(
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
private fun BottomActionBar(
    isLoading: Boolean,
    loadingMessage: String,
    onPublish: () -> Unit
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
            // Botón Borradores (deshabilitado por ahora)
            OutlinedButton(
                onClick = { /* TODO: Guardar borrador */ },
                modifier = Modifier.weight(1f),
                enabled = false
            ) {
                Icon(Icons.Default.Folder, contentDescription = null, modifier = Modifier.size(18.dp))
                Spacer(Modifier.width(8.dp))
                Text("Borradores")
            }
            
            // Botón Publicar
            Button(
                onClick = onPublish,
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
                    Text("Publicar")
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
