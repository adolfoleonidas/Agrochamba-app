package agrochamba.com.ui.jobs

import android.content.Context
import android.net.Uri
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
import androidx.compose.material.icons.filled.AddPhotoAlternate
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Star
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.clip
import androidx.compose.ui.zIndex
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import agrochamba.com.data.AppDataHolder
import agrochamba.com.data.Category
import agrochamba.com.data.JobPost
import agrochamba.com.utils.htmlToString
import coil.compose.AsyncImage
import coil.request.ImageRequest

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EditJobScreen(
    job: JobPost,
    navController: NavController,
    viewModel: EditJobViewModel = androidx.lifecycle.viewmodel.compose.viewModel(
        factory = EditJobViewModelFactory(job)
    )
) {
    val uiState = viewModel.uiState
    val context = LocalContext.current

    val imagePickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetMultipleContents(),
        onResult = { uris -> viewModel.onImagesSelected(uris) }
    )

    // Obtener taxonomías del job (puede venir de embedded o directamente del MyJobResponse si se convirtió)
    val terms = job.embedded?.terms?.flatten() ?: emptyList()
    val initialUbicacionTerm = terms.find { it.taxonomy == "ubicacion" }
    val initialEmpresaTerm = terms.find { it.taxonomy == "empresa" }
    val initialCultivoTerm = terms.find { it.taxonomy == "cultivo" }
    val initialTipoPuestoTerm = terms.find { it.taxonomy == "tipo_puesto" }

    var title by remember { mutableStateOf(job.title?.rendered?.htmlToString() ?: "") }
    var description by remember { mutableStateOf(job.content?.rendered?.htmlToString() ?: "") }
    var salarioMin by remember { mutableStateOf(job.meta?.salarioMin ?: "") }
    var salarioMax by remember { mutableStateOf(job.meta?.salarioMax ?: "") }
    var vacantes by remember { mutableStateOf(job.meta?.vacantes ?: "") }
    
    // Inicializar con null, se actualizará cuando se carguen las categorías
    var selectedUbicacion by remember { mutableStateOf<Category?>(null) }
    var selectedEmpresa by remember { mutableStateOf<Category?>(null) }
    var selectedCultivo by remember { mutableStateOf<Category?>(null) }
    var selectedTipoPuesto by remember { mutableStateOf<Category?>(null) }
    
    // Actualizar las selecciones cuando se carguen las categorías y haya términos iniciales
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
                title = { Text("Editar Trabajo") },
                navigationIcon = {
                    IconButton(onClick = { navController.popBackStack() }) {
                        Icon(Icons.Default.ArrowBack, null)
                    }
                }
            )
        }
    ) {
        if (uiState.isLoading && uiState.loadingMessage.startsWith("Cargando")) {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
                Text(uiState.loadingMessage, modifier = Modifier.padding(top = 80.dp))
            }
        } else {
            Column(
                modifier = Modifier
                    .padding(it)
                    .padding(16.dp)
                    .verticalScroll(rememberScrollState())
            ) {
                Text("Imágenes del Anuncio (hasta 10)", style = MaterialTheme.typography.titleMedium)
                Text(
                    text = "Mantén presionada una imagen y arrastra para reordenar. La primera es la destacada.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.padding(top = 4.dp)
                )
                Spacer(Modifier.height(8.dp))
                
                // Mostrar indicador de carga si las imágenes aún se están cargando
                if (!uiState.imagesLoaded && uiState.isLoading) {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(100.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        CircularProgressIndicator(modifier = Modifier.size(40.dp))
                    }
                } else {
                    LazyRow(
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        // Mostrar imágenes existentes primero - usar key para evitar que desaparezcan
                        itemsIndexed(
                            items = uiState.existingImageUrls,
                            key = { index, url -> "existing_${uiState.existingImageIds.getOrNull(index)}_$url" }
                        ) { index, imageUrl ->
                            ExistingImagePreviewItem(
                                imageUrl = imageUrl,
                                onRemove = { viewModel.removeExistingImage(imageUrl) },
                                isFeatured = index == 0 && uiState.selectedImages.isEmpty(),
                                onMove = { fromIndex, toIndex -> 
                                    // Reordenar dentro de existentes
                                    if (fromIndex < uiState.existingImageUrls.size && toIndex < uiState.existingImageUrls.size) {
                                        viewModel.reorderExistingImages(fromIndex, toIndex)
                                    }
                                },
                                index = index
                            )
                        }
                        // Mostrar nuevas imágenes seleccionadas
                        itemsIndexed(
                            items = uiState.selectedImages,
                            key = { index, uri -> "new_${uri}_$index" }
                        ) { index, uri ->
                            val totalIndex = uiState.existingImageUrls.size + index
                            ImagePreviewItem(
                                uri = uri,
                                onRemove = { viewModel.removeImage(uri) },
                                isFeatured = totalIndex == 0,
                                onMove = { fromIndex, toIndex -> 
                                    // Reordenar dentro de nuevas imágenes
                                    val existingCount = uiState.existingImageUrls.size
                                    val fromRelative = fromIndex - existingCount
                                    val toRelative = toIndex - existingCount
                                    if (fromRelative >= 0 && toRelative >= 0 && 
                                        fromRelative < uiState.selectedImages.size && 
                                        toRelative < uiState.selectedImages.size) {
                                        viewModel.reorderImages(fromRelative, toRelative)
                                    }
                                },
                                index = totalIndex
                            )
                        }
                        val totalImages = uiState.existingImageUrls.size + uiState.selectedImages.size
                        if (totalImages < 10) {
                            item { ImagePickerBox(onClick = { imagePickerLauncher.launch("image/*") }) }
                        }
                    }
                }
                Spacer(Modifier.height(24.dp))

                OutlinedTextField(
                    value = title,
                    onValueChange = { title = it },
                    label = { Text("Título del Puesto") },
                    modifier = Modifier.fillMaxWidth()
                )
                Spacer(Modifier.height(16.dp))
                OutlinedTextField(
                    value = description,
                    onValueChange = { description = it },
                    label = { Text("Descripción del Puesto") },
                    modifier = Modifier.fillMaxWidth().height(150.dp)
                )
                Spacer(Modifier.height(16.dp))

                Row(Modifier.fillMaxWidth()) {
                    OutlinedTextField(
                        value = salarioMin,
                        onValueChange = { salarioMin = it },
                        label = { Text("Salario Mín.") },
                        modifier = Modifier.weight(1f)
                    )
                    Spacer(Modifier.width(8.dp))
                    OutlinedTextField(
                        value = salarioMax,
                        onValueChange = { salarioMax = it },
                        label = { Text("Salario Máx.") },
                        modifier = Modifier.weight(1f)
                    )
                }
                Spacer(Modifier.height(16.dp))
                OutlinedTextField(
                    value = vacantes,
                    onValueChange = { vacantes = it },
                    label = { Text("Nº de Vacantes") },
                    modifier = Modifier.fillMaxWidth()
                )
                Spacer(Modifier.height(16.dp))

                CategoryDropdown(
                    label = "Ubicación",
                    items = uiState.ubicaciones,
                    selectedItem = selectedUbicacion
                ) { cat -> selectedUbicacion = cat }
                Spacer(Modifier.height(16.dp))
                CategoryDropdown(
                    label = "Empresa",
                    items = uiState.empresas,
                    selectedItem = selectedEmpresa
                ) { cat -> selectedEmpresa = cat }
                Spacer(Modifier.height(16.dp))
                CategoryDropdown(
                    label = "Cultivo",
                    items = uiState.cultivos,
                    selectedItem = selectedCultivo
                ) { cat -> selectedCultivo = cat }
                Spacer(Modifier.height(16.dp))
                CategoryDropdown(
                    label = "Tipo de Puesto",
                    items = uiState.tiposPuesto,
                    selectedItem = selectedTipoPuesto
                ) { cat -> selectedTipoPuesto = cat }
                Spacer(Modifier.height(24.dp))

                Text("Beneficios Incluidos", style = MaterialTheme.typography.titleMedium)
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

                Spacer(Modifier.height(24.dp))

                if (uiState.isLoading) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        CircularProgressIndicator()
                        Spacer(Modifier.width(16.dp))
                        Text(uiState.loadingMessage)
                    }
                } else {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Button(
                            onClick = {
                                val jobData = mapOf(
                                    "title" to title,
                                    "content" to description,
                                    "salario_min" to salarioMin,
                                    "salario_max" to salarioMax,
                                    "vacantes" to vacantes,
                                    "ubicacion_id" to (selectedUbicacion?.id ?: ""),
                                    "empresa_id" to (selectedEmpresa?.id ?: ""),
                                    "cultivo_id" to (selectedCultivo?.id ?: ""),
                                    "tipo_puesto_id" to (selectedTipoPuesto?.id ?: ""),
                                    "alojamiento" to alojamiento,
                                    "transporte" to transporte,
                                    "alimentacion" to alimentacion
                                )
                                viewModel.updateJob(jobData, context)
                            },
                            modifier = Modifier.weight(1f).height(48.dp)
                        ) {
                            Text("Guardar Cambios")
                        }
                        Button(
                            onClick = {
                                viewModel.deleteJob(context)
                            },
                            modifier = Modifier.height(48.dp),
                            colors = ButtonDefaults.buttonColors(
                                containerColor = MaterialTheme.colorScheme.errorContainer,
                                contentColor = MaterialTheme.colorScheme.onErrorContainer
                            )
                        ) {
                            Icon(Icons.Default.Delete, contentDescription = null, modifier = Modifier.size(18.dp))
                        }
                    }
                }

                uiState.error?.let { error ->
                    Spacer(Modifier.height(16.dp))
                    Text(error, color = MaterialTheme.colorScheme.error)
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
    var selectedText by remember { mutableStateOf(selectedItem?.name ?: "Seleccionar $label") }

    LaunchedEffect(selectedItem) {
        selectedText = selectedItem?.name ?: "Seleccionar $label"
    }

    ExposedDropdownMenuBox(expanded = expanded, onExpandedChange = { expanded = !expanded }) {
        OutlinedTextField(
            value = selectedText.ifEmpty { "Seleccionar $label" },
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
                        selectedText = item.name
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
        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(text, modifier = Modifier.weight(1f))
        Switch(checked = checked, onCheckedChange = onCheckedChange)
    }
}

@Composable
private fun ImagePreviewItem(
    uri: Uri,
    onRemove: () -> Unit,
    isFeatured: Boolean,
    onMove: (Int, Int) -> Unit,
    index: Int
) {
    val density = LocalDensity.current
    var offsetX by remember { mutableStateOf(0f) }
    var offsetY by remember { mutableStateOf(0f) }
    var isDragging by remember { mutableStateOf(false) }
    var showFullImage by remember { mutableStateOf(false) }
    
    Box(
        modifier = Modifier
            .size(100.dp)
            .then(
                if (isFeatured) {
                    Modifier.border(
                        width = 3.dp,
                        color = MaterialTheme.colorScheme.primary,
                        shape = RoundedCornerShape(12.dp)
                    )
                } else {
                    Modifier
                }
            )
            .then(
                if (isDragging) {
                    Modifier
                        .offset(x = offsetX.dp, y = offsetY.dp)
                        .alpha(0.8f)
                        .zIndex(1f)
                } else {
                    Modifier
                }
            )
            .pointerInput(uri) {
                detectDragGesturesAfterLongPress(
                    onDragStart = {
                        isDragging = true
                    },
                    onDrag = { change, dragAmount ->
                        change.consume()
                        offsetX += dragAmount.x
                        offsetY += dragAmount.y
                    },
                    onDragEnd = {
                        isDragging = false
                        val itemWidthPx = with(density) { 108.dp.toPx() }
                        val dragDistance = offsetX
                        val indexChange = (dragDistance / itemWidthPx).toInt()
                        val newIndex = (index + indexChange).coerceIn(0, 9)
                        if (newIndex != index && newIndex >= 0) {
                            onMove(index, newIndex)
                        }
                        offsetX = 0f
                        offsetY = 0f
                    }
                )
            }
    ) {
        AsyncImage(
            model = uri,
            contentDescription = "Vista previa de imagen. Mantén presionado y arrastra para reordenar. Toca para ver en tamaño completo.",
            modifier = Modifier
                .fillMaxSize()
                .clip(RoundedCornerShape(if (isFeatured) 9.dp else 12.dp))
                .clickable { showFullImage = true },
            contentScale = ContentScale.Crop
        )
        
        // Botón para eliminar
        IconButton(
            onClick = onRemove,
            modifier = Modifier
                .align(Alignment.TopEnd)
                .padding(4.dp)
                .background(Color.Black.copy(alpha = 0.7f), CircleShape)
        ) {
            Icon(
                Icons.Default.Close,
                contentDescription = "Eliminar imagen",
                tint = Color.White,
                modifier = Modifier.size(18.dp)
            )
        }
        
        // Indicador de imagen destacada
        if (isFeatured) {
            Row(
                modifier = Modifier
                    .align(Alignment.BottomCenter)
                    .padding(4.dp)
                    .background(
                        MaterialTheme.colorScheme.primary.copy(alpha = 0.9f),
                        RoundedCornerShape(4.dp)
                    )
                    .padding(horizontal = 6.dp, vertical = 2.dp),
                horizontalArrangement = Arrangement.Center,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Icon(
                    Icons.Default.Star,
                    contentDescription = null,
                    tint = Color.White,
                    modifier = Modifier.size(12.dp)
                )
                Spacer(Modifier.width(2.dp))
                Text(
                    "Destacada",
                    color = Color.White,
                    fontSize = 10.sp,
                    fontWeight = FontWeight.Bold
                )
            }
        }
        
        // Indicador de número de posición
        Text(
            text = "${index + 1}",
            color = Color.White,
            fontSize = 10.sp,
            fontWeight = FontWeight.Bold,
            modifier = Modifier
                .align(Alignment.TopStart)
                .padding(4.dp)
                .background(Color.Black.copy(alpha = 0.6f), CircleShape)
                .padding(horizontal = 6.dp, vertical = 2.dp)
        )
    }
    
    // Diálogo para ver imagen en tamaño completo
    if (showFullImage) {
        AlertDialog(
            onDismissRequest = { showFullImage = false },
            title = { Text("Imagen ${index + 1}") },
            text = {
                AsyncImage(
                    model = uri,
                    contentDescription = "Imagen completa",
                    modifier = Modifier.fillMaxWidth(),
                    contentScale = ContentScale.Fit
                )
            },
            confirmButton = {
                TextButton(onClick = { showFullImage = false }) {
                    Text("Cerrar")
                }
            },
            dismissButton = {
                TextButton(onClick = onRemove) {
                    Text("Eliminar", color = MaterialTheme.colorScheme.error)
                }
            }
        )
    }
}

@Composable
private fun ExistingImagePreviewItem(
    imageUrl: String,
    onRemove: () -> Unit,
    isFeatured: Boolean,
    onMove: (Int, Int) -> Unit,
    index: Int
) {
    val density = LocalDensity.current
    var offsetX by remember { mutableStateOf(0f) }
    var offsetY by remember { mutableStateOf(0f) }
    var isDragging by remember { mutableStateOf(false) }
    
    Box(
        modifier = Modifier
            .size(100.dp)
            .then(
                if (isFeatured) {
                    Modifier.border(
                        width = 3.dp,
                        color = MaterialTheme.colorScheme.primary,
                        shape = RoundedCornerShape(12.dp)
                    )
                } else {
                    Modifier
                }
            )
            .then(
                if (isDragging) {
                    Modifier
                        .offset(x = offsetX.dp, y = offsetY.dp)
                        .alpha(0.8f)
                } else {
                    Modifier
                }
            )
            .pointerInput(imageUrl) {
                detectDragGesturesAfterLongPress(
                    onDragStart = {
                        isDragging = true
                    },
                    onDrag = { change, dragAmount ->
                        change.consume()
                        offsetX += dragAmount.x
                        offsetY += dragAmount.y
                    },
                    onDragEnd = {
                        isDragging = false
                        val itemWidthPx = with(density) { 108.dp.toPx() }
                        val dragDistance = offsetX
                        val indexChange = (dragDistance / itemWidthPx).toInt()
                        val newIndex = (index + indexChange).coerceIn(0, 9)
                        if (newIndex != index && newIndex >= 0) {
                            onMove(index, newIndex)
                        }
                        offsetX = 0f
                        offsetY = 0f
                    }
                )
            }
    ) {
        val context = LocalContext.current
        var showFullImage by remember { mutableStateOf(false) }
        
        AsyncImage(
            model = ImageRequest.Builder(context)
                .data(imageUrl)
                .crossfade(true)
                .build(),
            contentDescription = "Vista previa de imagen existente. Mantén presionado y arrastra para reordenar. Toca para ver en tamaño completo.",
            modifier = Modifier
                .fillMaxSize()
                .clip(RoundedCornerShape(if (isFeatured) 9.dp else 12.dp))
                .clickable { showFullImage = true },
            contentScale = ContentScale.Crop
        )
        
        // Botón para eliminar
        IconButton(
            onClick = onRemove,
            modifier = Modifier
                .align(Alignment.TopEnd)
                .padding(4.dp)
                .background(Color.Black.copy(alpha = 0.7f), CircleShape)
        ) {
            Icon(
                Icons.Default.Close,
                contentDescription = "Eliminar imagen",
                tint = Color.White,
                modifier = Modifier.size(18.dp)
            )
        }
        
        // Indicador de imagen destacada
        if (isFeatured) {
            Row(
                modifier = Modifier
                    .align(Alignment.BottomCenter)
                    .padding(4.dp)
                    .background(
                        MaterialTheme.colorScheme.primary.copy(alpha = 0.9f),
                        RoundedCornerShape(4.dp)
                    )
                    .padding(horizontal = 6.dp, vertical = 2.dp),
                horizontalArrangement = Arrangement.Center,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Icon(
                    Icons.Default.Star,
                    contentDescription = null,
                    tint = Color.White,
                    modifier = Modifier.size(12.dp)
                )
                Spacer(Modifier.width(2.dp))
                Text(
                    "Destacada",
                    color = Color.White,
                    fontSize = 10.sp,
                    fontWeight = FontWeight.Bold
                )
            }
        }
        
        // Indicador de número de posición
        Text(
            text = "${index + 1}",
            color = Color.White,
            fontSize = 10.sp,
            fontWeight = FontWeight.Bold,
            modifier = Modifier
                .align(Alignment.TopStart)
                .padding(4.dp)
                .background(Color.Black.copy(alpha = 0.6f), CircleShape)
                .padding(horizontal = 6.dp, vertical = 2.dp)
        )
        
        // Diálogo para ver imagen en tamaño completo
        if (showFullImage) {
            AlertDialog(
                onDismissRequest = { showFullImage = false },
                title = { Text("Imagen ${index + 1}") },
                text = {
                    AsyncImage(
                        model = ImageRequest.Builder(context)
                            .data(imageUrl)
                            .build(),
                        contentDescription = "Imagen completa",
                        modifier = Modifier.fillMaxWidth(),
                        contentScale = ContentScale.Fit
                    )
                },
                confirmButton = {
                    TextButton(onClick = { showFullImage = false }) {
                        Text("Cerrar")
                    }
                },
                dismissButton = {
                    TextButton(onClick = onRemove) {
                        Text("Eliminar", color = MaterialTheme.colorScheme.error)
                    }
                }
            )
        }
    }
}

@Composable
private fun ImagePickerBox(onClick: () -> Unit) {
    Box(
        modifier = Modifier
            .size(100.dp)
            .border(2.dp, MaterialTheme.colorScheme.outline, RoundedCornerShape(12.dp))
            .clickable(onClick = onClick),
        contentAlignment = Alignment.Center
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Icon(
                Icons.Default.AddPhotoAlternate,
                contentDescription = "Agregar imagen",
                tint = MaterialTheme.colorScheme.primary,
                modifier = Modifier.size(32.dp)
            )
            Spacer(Modifier.height(4.dp))
            Text(
                "Agregar",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.primary
            )
        }
    }
}

