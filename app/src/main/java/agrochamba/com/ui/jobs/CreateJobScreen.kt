package agrochamba.com.ui.jobs

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
import androidx.compose.material.icons.filled.Business
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Star
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.zIndex
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import agrochamba.com.data.Category
import coil.compose.AsyncImage

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CreateJobScreen(navController: NavController, viewModel: CreateJobViewModel = viewModel()) {
    val uiState = viewModel.uiState
    val context = LocalContext.current

    val imagePickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetMultipleContents(),
        onResult = { uris -> viewModel.onImagesSelected(uris) }
    )

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
    
    // Si el usuario es empresa, seleccionar automáticamente su empresa
    LaunchedEffect(uiState.userCompanyId, uiState.empresas) {
        if (uiState.userCompanyId != null && selectedEmpresa == null && uiState.empresas.isNotEmpty()) {
            selectedEmpresa = uiState.empresas.find { it.id == uiState.userCompanyId }
        }
    }

    LaunchedEffect(uiState.postSuccess) {
        if (uiState.postSuccess) {
            Toast.makeText(context, "¡Trabajo publicado con éxito!", Toast.LENGTH_LONG).show()
            navController.popBackStack()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(title = { Text("Publicar Nuevo Trabajo") },
                navigationIcon = { IconButton(onClick = { navController.popBackStack() }) { Icon(Icons.Default.ArrowBack, null) } })
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
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    itemsIndexed(uiState.selectedImages) { index, uri ->
                        ImagePreviewItem(
                            uri = uri,
                            onRemove = { viewModel.removeImage(uri) },
                            isFeatured = index == 0,
                            onMove = { fromIndex, toIndex ->
                                viewModel.reorderImages(fromIndex, toIndex)
                            },
                            index = index
                        )
                    }
                    if (uiState.selectedImages.size < 10) {
                        item { ImagePickerBox(onClick = { imagePickerLauncher.launch("image/*") }) }
                    }
                }
                Spacer(Modifier.height(24.dp))

                OutlinedTextField(value = title, onValueChange = { title = it }, label = { Text("Título del Puesto") }, modifier = Modifier.fillMaxWidth())
                Spacer(Modifier.height(16.dp))
                OutlinedTextField(value = description, onValueChange = { description = it }, label = { Text("Descripción del Puesto") }, modifier = Modifier.fillMaxWidth().height(150.dp))
                Spacer(Modifier.height(16.dp))

                Row(Modifier.fillMaxWidth()) {
                    OutlinedTextField(value = salarioMin, onValueChange = { salarioMin = it }, label = { Text("Salario Mín.") }, modifier = Modifier.weight(1f))
                    Spacer(Modifier.width(8.dp))
                    OutlinedTextField(value = salarioMax, onValueChange = { salarioMax = it }, label = { Text("Salario Máx.") }, modifier = Modifier.weight(1f))
                }
                Spacer(Modifier.height(16.dp))
                OutlinedTextField(value = vacantes, onValueChange = { vacantes = it }, label = { Text("Nº de Vacantes") }, modifier = Modifier.fillMaxWidth())
                Spacer(Modifier.height(16.dp))

                CategoryDropdown(label = "Ubicación", items = uiState.ubicaciones, selectedItem = selectedUbicacion) { cat -> selectedUbicacion = cat }
                Spacer(Modifier.height(16.dp))
                
                // Si el usuario es empresa, mostrar su empresa y deshabilitar el dropdown
                val currentEmpresa = selectedEmpresa
                if (uiState.userCompanyId != null && currentEmpresa != null) {
                    OutlinedTextField(
                        value = currentEmpresa.name ?: "",
                        onValueChange = {},
                        readOnly = true,
                        enabled = false,
                        label = { Text("Empresa") },
                        modifier = Modifier.fillMaxWidth(),
                        trailingIcon = {
                            Icon(
                                Icons.Default.Business,
                                contentDescription = "Empresa",
                                tint = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.6f)
                            )
                        }
                    )
                    Text(
                        text = "Tu empresa se asigna automáticamente. Para cambiarla, actualiza tu nombre en el perfil.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f),
                        modifier = Modifier.padding(top = 4.dp)
                    )
                } else {
                    CategoryDropdown(label = "Empresa", items = uiState.empresas, selectedItem = selectedEmpresa) { cat -> selectedEmpresa = cat }
                }
                Spacer(Modifier.height(16.dp))
                CategoryDropdown(label = "Cultivo", items = uiState.cultivos, selectedItem = selectedCultivo) { cat -> selectedCultivo = cat }
                Spacer(Modifier.height(16.dp))
                CategoryDropdown(label = "Tipo de Puesto", items = uiState.tiposPuesto, selectedItem = selectedTipoPuesto) { cat -> selectedTipoPuesto = cat }
                Spacer(Modifier.height(24.dp))

                Text("Beneficios Incluidos", style = MaterialTheme.typography.titleMedium)
                BenefitSwitch(text = "Alojamiento", checked = alojamiento, onCheckedChange = { alojamiento = it })
                BenefitSwitch(text = "Transporte", checked = transporte, onCheckedChange = { transporte = it })
                BenefitSwitch(text = "Alimentación", checked = alimentacion, onCheckedChange = { alimentacion = it })

                Spacer(Modifier.height(24.dp))

                if (uiState.isLoading) {
                     Row(verticalAlignment = Alignment.CenterVertically) {
                        CircularProgressIndicator()
                        Spacer(Modifier.width(16.dp))
                        Text(uiState.loadingMessage)
                    }
                } else {
                    Button(onClick = { 
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
                        viewModel.createJob(jobData, context)
                     }, modifier = Modifier.fillMaxWidth().height(48.dp)) {
                        Text("Publicar Trabajo")
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

@Composable
private fun ImagePickerBox(onClick: () -> Unit) {
    Box(
        modifier = Modifier
            .size(100.dp)
            .clip(RoundedCornerShape(12.dp))
            .border(1.dp, MaterialTheme.colorScheme.outline, RoundedCornerShape(12.dp))
            .clickable(onClick = onClick),
        contentAlignment = Alignment.Center
    ) {
        Icon(Icons.Default.AddPhotoAlternate, contentDescription = "Añadir imagen", tint = MaterialTheme.colorScheme.onSurfaceVariant)
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


@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun CategoryDropdown(
    label: String, 
    items: List<Category>, 
    selectedItem: Category? = null,
    onItemSelected: (Category) -> Unit
) {
    var expanded by remember { mutableStateOf(false) }
    var selectedText by remember { mutableStateOf(selectedItem?.name ?: "") }

    LaunchedEffect(selectedItem) {
        selectedText = selectedItem?.name ?: ""
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
                DropdownMenuItem(text = { Text(item.name) }, onClick = {
                    selectedText = item.name
                    onItemSelected(item)
                    expanded = false
                })
            }
        }
    }
}

@Composable
private fun BenefitSwitch(text: String, checked: Boolean, onCheckedChange: (Boolean) -> Unit) {
    Row(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp), verticalAlignment = Alignment.CenterVertically) {
        Text(text, modifier = Modifier.weight(1f))
        Switch(checked = checked, onCheckedChange = onCheckedChange)
    }
}
