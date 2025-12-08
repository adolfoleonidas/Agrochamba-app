package agrochamba.com.ui.jobs

import android.content.Context
import android.net.Uri
import android.widget.Toast
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
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
import androidx.compose.material.icons.filled.Delete
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
import agrochamba.com.utils.htmlToString
import agrochamba.com.utils.htmlToMarkdown
import agrochamba.com.utils.textToHtml
import agrochamba.com.ui.common.RichTextEditor
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

    var title by remember { mutableStateOf(job.title?.rendered?.htmlToString() ?: "") }
    var description by remember { mutableStateOf(job.content?.rendered?.htmlToMarkdown() ?: "") }
    var salarioMin by remember { mutableStateOf(job.meta?.salarioMin ?: "") }
    var salarioMax by remember { mutableStateOf(job.meta?.salarioMax ?: "") }
    var vacantes by remember { mutableStateOf(job.meta?.vacantes ?: "") }
    
    var selectedUbicacion by remember { mutableStateOf<Category?>(null) }
    var selectedEmpresa by remember { mutableStateOf<Category?>(null) }
    var selectedCultivo by remember { mutableStateOf<Category?>(null) }
    var selectedTipoPuesto by remember { mutableStateOf<Category?>(null) }
    
    LaunchedEffect(uiState.ubicaciones, initialUbicacionTerm) {
        if (uiState.ubicaciones.isNotEmpty() && initialUbicacionTerm != null && selectedUbicacion == null) {
            selectedUbicacion = uiState.ubicaciones.find { it.id == initialUbicacionTerm.id }
        }
    }
    
    LaunchedEffect(uiState.empresas, initialEmpresaTerm) {
        if (uiState.empresas.isNotEmpty() && initialEmpresaTerm != null && selectedEmpresa == null) {
            selectedEmpresa = uiState.empresas.find { it.id == initialEmpresaTerm.id }
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
    
    var alojamiento by remember { mutableStateOf(job.meta?.alojamiento ?: false) }
    var transporte by remember { mutableStateOf(job.meta?.transporte ?: false) }
    var alimentacion by remember { mutableStateOf(job.meta?.alimentacion ?: false) }
    // IMPORTANTE: Siempre inicializar en false para que el usuario tenga control explícito
    // No usar facebookPostId como indicador porque puede haber sido publicado automáticamente sin consentimiento
    var publishToFacebook by remember { mutableStateOf(false) }
    
    var showMoreOptions by remember { mutableStateOf(false) }
    
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
            Toast.makeText(context, "¡Trabajo actualizado con éxito!", Toast.LENGTH_LONG).show()
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
                title = { Text("Editar Anuncio") },
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
                    if (title.isBlank()) {
                        Toast.makeText(context, "El título es obligatorio", Toast.LENGTH_SHORT).show()
                        return@EditBottomActionBar
                    }
                    if (description.isBlank()) {
                        Toast.makeText(context, "La descripción es obligatoria", Toast.LENGTH_SHORT).show()
                        return@EditBottomActionBar
                    }
                    if (selectedUbicacion == null) {
                        Toast.makeText(context, "La ubicación es obligatoria", Toast.LENGTH_SHORT).show()
                        return@EditBottomActionBar
                    }
                    
                    // Guardar en variable local para evitar problemas de smart cast
                    val ubicacionId = selectedUbicacion!!.id
                    
                    // Si es empresa normal (no admin), usar automáticamente su empresa
                    // Si es admin, puede seleccionar cualquier empresa
                    val empresaIdToUse = if (AuthManager.isUserAdmin()) {
                        selectedEmpresa?.id
                    } else {
                        uiState.userCompanyId ?: selectedEmpresa?.id
                    }
                    
                    val jobData = mutableMapOf<String, Any>(
                        "title" to title.trim(),
                        "content" to description.textToHtml(),
                        "salario_min" to (salarioMin.toIntOrNull() ?: 0),
                        "salario_max" to (salarioMax.toIntOrNull() ?: 0),
                        "vacantes" to (vacantes.toIntOrNull() ?: 1),
                        "ubicacion_id" to ubicacionId, // Obligatorio
                        "alojamiento" to alojamiento,
                        "transporte" to transporte,
                        "alimentacion" to alimentacion,
                        "publish_to_facebook" to publishToFacebook
                    )
                    // Agregar IDs solo si no son null (opcionales)
                    empresaIdToUse?.let { jobData["empresa_id"] = it } // Usar empresa automática si no es admin
                    selectedCultivo?.id?.let { jobData["cultivo_id"] = it }
                    selectedTipoPuesto?.id?.let { jobData["tipo_puesto_id"] = it }
                    viewModel.updateJob(jobData, context)
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
                    Text(
                        text = "Título del anuncio",
                        style = MaterialTheme.typography.titleSmall,
                        fontWeight = FontWeight.Medium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.padding(bottom = 8.dp)
                    )
                    TextField(
                        value = title,
                        onValueChange = { title = it },
                        placeholder = {
                            Text(
                                "Ej: Se busca personal para cosecha de uva en Ica",
                                color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.6f)
                            )
                        },
                        modifier = Modifier.fillMaxWidth(),
                        colors = TextFieldDefaults.colors(
                            focusedContainerColor = Color.Transparent,
                            unfocusedContainerColor = Color.Transparent
                        ),
                        textStyle = MaterialTheme.typography.bodyLarge
                    )
                }
                
                Spacer(Modifier.height(16.dp))
                
                // Descripción
                Column(modifier = Modifier.padding(horizontal = 16.dp)) {
                    Text(
                        "Descripción del Trabajo",
                        style = MaterialTheme.typography.labelLarge,
                        fontWeight = FontWeight.Medium,
                        color = MaterialTheme.colorScheme.onSurface,
                        modifier = Modifier.padding(bottom = 8.dp)
                    )
                    RichTextEditor(
                        value = description,
                        onValueChange = { description = it },
                        placeholder = "Una descripción detallada permite obtener más visitas. Incluye información sobre el trabajo, requisitos y beneficios.\n\nUsa los botones de formato para resaltar texto importante.",
                        maxLines = 15,
                        enabled = !uiState.isLoading
                    )
                }
                
                Spacer(Modifier.height(24.dp))
                
                // Ubicación - siempre visible (campo básico más importante)
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp)
                ) {
                    CategoryDropdown(
                        label = "Ubicación *",
                        items = uiState.ubicaciones,
                        selectedItem = selectedUbicacion
                    ) { cat -> selectedUbicacion = cat }
                }
                
                Spacer(Modifier.height(16.dp))
                
                // Botón para mostrar más detalles
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
                            CategoryDropdown(
                                label = "Empresa",
                                items = uiState.empresas,
                                selectedItem = selectedEmpresa
                            ) { cat -> selectedEmpresa = cat }
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
                
                // Opciones adicionales
                Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
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
    onItemSelected: (Category) -> Unit
) {
    var expanded by remember { mutableStateOf(false) }

    ExposedDropdownMenuBox(expanded = expanded, onExpandedChange = { expanded = !expanded }) {
        OutlinedTextField(
            value = selectedItem?.name ?: "",
            onValueChange = {},
            readOnly = true,
            label = { Text(label) },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            modifier = Modifier.fillMaxWidth().menuAnchor()
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
