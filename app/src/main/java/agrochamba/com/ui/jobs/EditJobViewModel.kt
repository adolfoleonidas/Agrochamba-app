package agrochamba.com.ui.jobs

import android.content.Context
import android.net.Uri
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.Category
import agrochamba.com.data.JobPost
import agrochamba.com.data.MyJobResponse
import agrochamba.com.data.WordPressApi
import kotlinx.coroutines.async
import kotlinx.coroutines.awaitAll
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody
import okio.BufferedSink
import okio.source

data class EditJobScreenState(
    val isLoading: Boolean = true,
    val loadingMessage: String = "Cargando formulario...",
    val error: String? = null,
    val updateSuccess: Boolean = false,
    val deleteSuccess: Boolean = false,
    val ubicaciones: List<Category> = emptyList(),
    val empresas: List<Category> = emptyList(),
    val cultivos: List<Category> = emptyList(),
    val tiposPuesto: List<Category> = emptyList(),
    val selectedImages: List<Uri> = emptyList(),
    val existingImageUrls: List<String> = emptyList(), // URLs de imágenes existentes
    val existingImageIds: List<Int> = emptyList(), // IDs de imágenes existentes (para mapear con URLs)
    val featuredImageIndex: Int = 0,
    val imagesLoaded: Boolean = false, // Flag para saber si las imágenes ya se cargaron
    val userCompanyId: Int? = null
)

class EditJobViewModel(private val job: JobPost) : ViewModel() {
    var uiState by mutableStateOf(EditJobScreenState())
        private set

    init {
        android.util.Log.d("EditJobViewModel", "=== ViewModel INICIALIZADO para trabajo ID: ${job.id} ===")
        // Asegurar que el estado esté limpio al inicializar
        uiState = uiState.copy(
            existingImageUrls = emptyList(),
            existingImageIds = emptyList(),
            imagesLoaded = false,
            selectedImages = emptyList(),
            featuredImageIndex = 0
        )
        loadFormData()
        loadExistingImages()
    }
    
    /**
     * Recarga las imágenes cuando cambia el trabajo
     * IMPORTANTE: Esta función debe llamarse cuando cambia el job.id
     */
    fun reloadImages() {
        android.util.Log.d("EditJobViewModel", "reloadImages() llamado para trabajo ID: ${job.id}")
        // Resetear completamente el estado de imágenes antes de cargar nuevas
        uiState = uiState.copy(
            existingImageUrls = emptyList(),
            existingImageIds = emptyList(),
            imagesLoaded = false,
            selectedImages = emptyList(),
            featuredImageIndex = 0
        )
        // Cargar imágenes del nuevo trabajo
        loadExistingImages()
    }

    private fun loadFormData() {
        viewModelScope.launch {
            // Preservar imágenes existentes cuando se actualiza el estado
            val currentImageUrls = uiState.existingImageUrls
            val currentImageIds = uiState.existingImageIds
            val currentImagesLoaded = uiState.imagesLoaded
            
            uiState = uiState.copy(isLoading = true, error = null)
            try {
                val ubicacionesDeferred = async { WordPressApi.retrofitService.getUbicaciones() }
                val empresasDeferred = async { WordPressApi.retrofitService.getEmpresas() }
                val cultivosDeferred = async { WordPressApi.retrofitService.getCultivos() }
                val tiposPuestoDeferred = async { WordPressApi.retrofitService.getTiposPuesto() }

                uiState = uiState.copy(
                    ubicaciones = ubicacionesDeferred.await(),
                    empresas = empresasDeferred.await(),
                    cultivos = cultivosDeferred.await(),
                    tiposPuesto = tiposPuestoDeferred.await(),
                    userCompanyId = AuthManager.userCompanyId,
                    isLoading = false,
                    // PRESERVAR imágenes existentes
                    existingImageUrls = currentImageUrls,
                    existingImageIds = currentImageIds,
                    imagesLoaded = currentImagesLoaded
                )
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isLoading = false, 
                    error = "No se pudieron cargar los datos del formulario.",
                    // PRESERVAR imágenes existentes
                    existingImageUrls = currentImageUrls,
                    existingImageIds = currentImageIds,
                    imagesLoaded = currentImagesLoaded
                )
            }
        }
    }

    /**
     * Carga las imágenes existentes de un trabajo.
     * Funciona igual para una o varias imágenes usando la misma lógica.
     * IMPORTANTE: Siempre usa job.id del ViewModel actual para asegurar que carga las imágenes correctas.
     */
    fun loadExistingImages() {
        viewModelScope.launch {
            try {
                val currentJobId = job.id
                android.util.Log.d("EditJobViewModel", "=== loadExistingImages() llamado para trabajo ID: $currentJobId ===")
                android.util.Log.d("EditJobViewModel", "Estado actual antes de resetear: existingImageUrls.size=${uiState.existingImageUrls.size}, imagesLoaded=${uiState.imagesLoaded}")
                
                // Resetear estado COMPLETAMENTE antes de cargar
                uiState = uiState.copy(
                    imagesLoaded = false,
                    existingImageUrls = emptyList(),
                    existingImageIds = emptyList()
                )
                
                android.util.Log.d("EditJobViewModel", "Estado reseteado. Ahora cargando imágenes para trabajo ID: $currentJobId")
                
                val finalUrls = mutableListOf<String>()
                val finalIds = mutableListOf<Int>()
                
                // ESTRATEGIA 1: getJobImages (endpoint que funciona para una o varias imágenes)
                // Usar currentJobId para asegurar que siempre usamos el ID correcto
                try {
                    android.util.Log.d("EditJobViewModel", "Llamando getJobImages con ID: $currentJobId")
                    val response = WordPressApi.retrofitService.getJobImages(currentJobId)
                    android.util.Log.d("EditJobViewModel", "getJobImages retornó ${response.images.size} imágenes para trabajo ID: $currentJobId")
                    
                    response.images.forEach { image ->
                        val url = image.getDetailUrl() ?: image.getFullUrl()
                        val id = image.id
                        if (url != null && url.isNotBlank() && !finalUrls.contains(url)) {
                            finalUrls.add(url)
                            finalIds.add(id)
                            android.util.Log.d("EditJobViewModel", "✅ Imagen desde getJobImages: ID=$id")
                        }
                    }
                } catch (e: Exception) {
                    android.util.Log.w("EditJobViewModel", "getJobImages falló: ${e.message}")
                }
                
                // ESTRATEGIA 2: Si no hay imágenes, usar featuredMedia desde embedded
                if (finalUrls.isEmpty()) {
                    val featuredMedia = job.embedded?.featuredMedia?.firstOrNull()
                    val featuredUrl = featuredMedia?.getImageUrl()
                    val featuredId = featuredMedia?.id
                    
                    if (featuredUrl != null && featuredUrl.isNotBlank()) {
                        finalUrls.add(featuredUrl)
                        finalIds.add(featuredId ?: -1)
                        android.util.Log.d("EditJobViewModel", "✅ Imagen desde embedded: ID=${featuredId ?: -1}")
                    }
                }
                
                // ESTRATEGIA 3: Agregar imágenes adicionales desde gallery_ids (evitando duplicados)
                val galleryIds = job.meta?.galleryIds
                if (!galleryIds.isNullOrEmpty()) {
                    galleryIds.forEach { id ->
                        if (!finalIds.contains(id)) {
                            try {
                                val media = WordPressApi.retrofitService.getMediaById(id)
                                val url = media.getImageUrl()
                                if (url != null && url.isNotBlank() && !finalUrls.contains(url)) {
                                    finalUrls.add(url)
                                    finalIds.add(id)
                                    android.util.Log.d("EditJobViewModel", "✅ Imagen desde gallery_ids: ID=$id")
                                }
                            } catch (e: Exception) {
                                android.util.Log.w("EditJobViewModel", "Error cargando media ID $id: ${e.message}")
                            }
                        }
                    }
                }
                
                android.util.Log.d("EditJobViewModel", "Total imágenes cargadas: ${finalUrls.size}")
                
                // Actualizar estado
                uiState = uiState.copy(
                    existingImageUrls = finalUrls.toList(),
                    existingImageIds = finalIds.toList(),
                    imagesLoaded = true
                )
            } catch (e: Exception) {
                android.util.Log.e("EditJobViewModel", "Error cargando imágenes: ${e.message}", e)
                
                // En caso de error, intentar al menos con embedded
                val embeddedUrls = job.embedded?.featuredMedia?.mapNotNull { media ->
                    media.getImageUrl()?.takeIf { it.isNotBlank() }
                } ?: emptyList()
                val embeddedIds = job.embedded?.featuredMedia?.mapNotNull { it.id } ?: emptyList()
                
                val finalIds = embeddedIds.mapIndexed { index, id ->
                    if (index < embeddedUrls.size) id ?: -1 else -1
                }
                
                uiState = uiState.copy(
                    existingImageUrls = embeddedUrls,
                    existingImageIds = finalIds,
                    imagesLoaded = true
                )
            }
        }
    }

    fun onImagesSelected(uris: List<Uri>) {
        val currentImages = uiState.selectedImages.toMutableList()
        currentImages.addAll(uris)
        val newImages = currentImages.take(10)
        val newFeaturedIndex = if (uiState.selectedImages.isEmpty() && newImages.isNotEmpty()) {
            0
        } else {
            uiState.featuredImageIndex.coerceIn(0, newImages.size - 1)
        }
        uiState = uiState.copy(
            selectedImages = newImages,
            featuredImageIndex = newFeaturedIndex
        )
    }

    fun removeImage(uri: Uri) {
        val currentImages = uiState.selectedImages.toMutableList()
        currentImages.remove(uri)
        uiState = uiState.copy(selectedImages = currentImages)
    }

    fun removeExistingImage(imageUrl: String) {
        val currentImages = uiState.existingImageUrls.toMutableList()
        val currentIds = uiState.existingImageIds.toMutableList()
        val indexToRemove = currentImages.indexOf(imageUrl)
        if (indexToRemove >= 0) {
            currentImages.removeAt(indexToRemove)
            if (indexToRemove < currentIds.size) {
                currentIds.removeAt(indexToRemove)
            }
            android.util.Log.d("EditJobViewModel", "Imagen existente eliminada. Restantes: ${currentImages.size}")
            uiState = uiState.copy(
                existingImageUrls = currentImages,
                existingImageIds = currentIds
            )
        }
    }

    fun reorderExistingImages(fromIndex: Int, toIndex: Int) {
        if (fromIndex == toIndex || fromIndex !in uiState.existingImageUrls.indices || toIndex !in uiState.existingImageUrls.indices) {
            return
        }
        val images = uiState.existingImageUrls.toMutableList()
        val ids = uiState.existingImageIds.toMutableList()
        
        val imageItem = images.removeAt(fromIndex)
        val idItem = if (fromIndex < ids.size) ids.removeAt(fromIndex) else null
        
        images.add(toIndex, imageItem)
        if (idItem != null && toIndex <= ids.size) {
            ids.add(toIndex, idItem)
        }
        
        uiState = uiState.copy(
            existingImageUrls = images,
            existingImageIds = ids
        )
    }

    fun reorderImages(fromIndex: Int, toIndex: Int) {
        if (fromIndex == toIndex || fromIndex !in uiState.selectedImages.indices || toIndex !in uiState.selectedImages.indices) {
            return
        }
        val images = uiState.selectedImages.toMutableList()
        val item = images.removeAt(fromIndex)
        images.add(toIndex, item)
        uiState = uiState.copy(
            selectedImages = images,
            featuredImageIndex = 0
        )
    }
    
    // Función unificada para reordenar entre imágenes existentes y nuevas
    fun reorderAllImages(fromTotalIndex: Int, toTotalIndex: Int) {
        val existingCount = uiState.existingImageUrls.size
        val totalCount = existingCount + uiState.selectedImages.size
        
        if (fromTotalIndex == toTotalIndex || 
            fromTotalIndex < 0 || fromTotalIndex >= totalCount ||
            toTotalIndex < 0 || toTotalIndex >= totalCount) {
            return
        }
        
        // Caso 1: Reordenar dentro de imágenes existentes
        if (fromTotalIndex < existingCount && toTotalIndex < existingCount) {
            reorderExistingImages(fromTotalIndex, toTotalIndex)
            return
        }
        
        // Caso 2: Reordenar dentro de nuevas imágenes
        if (fromTotalIndex >= existingCount && toTotalIndex >= existingCount) {
            reorderImages(fromTotalIndex - existingCount, toTotalIndex - existingCount)
            return
        }
        
        // Caso 3: Mover de existentes a nuevas (convertir existente a nueva)
        if (fromTotalIndex < existingCount && toTotalIndex >= existingCount) {
            // No permitimos convertir existentes a nuevas, solo reordenar
            return
        }
        
        // Caso 4: Mover de nuevas a existentes (no permitido, las nuevas se suben al guardar)
        // Por ahora, solo permitimos reordenar dentro del mismo grupo
    }

    fun updateJob(jobData: Map<String, Any>, context: Context) {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null, loadingMessage = "Actualizando trabajo...")
            try {
                val token = AuthManager.token ?: throw Exception("No estás autenticado.")
                val authHeader = "Bearer $token"

                // Validaciones básicas
                val title = jobData["title"] as? String
                val content = jobData["content"] as? String
                val ubicacionId = jobData["ubicacion_id"] as? Number
                
                if (title.isNullOrBlank()) {
                    throw Exception("El título es obligatorio.")
                }
                if (content.isNullOrBlank()) {
                    throw Exception("La descripción es obligatoria.")
                }
                if (ubicacionId == null) {
                    throw Exception("La ubicación es obligatoria.")
                }

                // Si hay nuevas imágenes, subirlas
                val finalJobData = jobData.toMutableMap()
                
                // Combinar imágenes existentes y nuevas
                val allImageIds = mutableListOf<Int>()
                
                // Agregar IDs de imágenes existentes que no fueron eliminadas
                // Usar existingImageIds que mantiene el mapeo con las URLs mostradas
                if (uiState.existingImageIds.isNotEmpty()) {
                    allImageIds.addAll(uiState.existingImageIds)
                }
                
                // Subir nuevas imágenes y agregar sus IDs
                if (uiState.selectedImages.isNotEmpty()) {
                    uiState = uiState.copy(loadingMessage = "Subiendo imágenes nuevas...")
                    val uploadedImageIds = mutableListOf<Int>()
                    val totalImages = uiState.selectedImages.size
                    
                    // Subir imágenes una por una para mejor manejo de errores
                    uiState.selectedImages.forEachIndexed { index, uri ->
                        try {
                            uiState = uiState.copy(loadingMessage = "Subiendo imagen ${index + 1} de $totalImages...")
                            
                            // Validar que el URI sea accesible
                            val inputStream = context.contentResolver.openInputStream(uri)
                            if (inputStream == null) {
                                android.util.Log.e("EditJobViewModel", "No se pudo abrir el archivo: $uri")
                                return@forEachIndexed
                            }
                            inputStream.close()
                            
                            val requestBody = createStreamingRequestBody(context, uri)
                            if (requestBody == null) {
                                android.util.Log.e("EditJobViewModel", "No se pudo crear RequestBody para: $uri")
                                return@forEachIndexed
                            }
                            
                            val fileName = getFileName(context, uri) ?: "image.jpg"
                            val part = MultipartBody.Part.createFormData("file", fileName, requestBody)
                            val mediaItem = WordPressApi.retrofitService.uploadImage(authHeader, part, emptyMap())
                            
                            mediaItem.id?.let { id ->
                                uploadedImageIds.add(id)
                                android.util.Log.d("EditJobViewModel", "Imagen subida exitosamente: ID=$id")
                            } ?: run {
                                android.util.Log.e("EditJobViewModel", "La imagen se subió pero no se recibió ID")
                            }
                        } catch (e: Exception) {
                            android.util.Log.e("EditJobViewModel", "Error al subir imagen ${index + 1}: ${e.message}", e)
                            // Continuar con las siguientes imágenes en lugar de fallar completamente
                        }
                    }
                    
                    if (uploadedImageIds.isNotEmpty()) {
                        allImageIds.addAll(uploadedImageIds)
                        android.util.Log.d("EditJobViewModel", "Total de imágenes nuevas subidas: ${uploadedImageIds.size}")
                    } else if (uiState.selectedImages.isNotEmpty()) {
                        android.util.Log.w("EditJobViewModel", "No se pudo subir ninguna imagen nueva, pero continuando con las existentes")
                    }
                }
                
                // Si hay imágenes, establecer la primera como destacada
                if (allImageIds.isNotEmpty()) {
                    finalJobData["featured_media"] = allImageIds[0]
                    finalJobData["gallery_ids"] = allImageIds
                } else {
                    // Si no hay imágenes, limpiar featured_media y gallery_ids
                    finalJobData["featured_media"] = 0
                    finalJobData["gallery_ids"] = emptyList<Int>()
                }

                val response = WordPressApi.retrofitService.updateJob(authHeader, job.id, finalJobData)

                if (response.isSuccessful) {
                    uiState = uiState.copy(isLoading = false, updateSuccess = true)
                } else {
                    val errorBody = response.errorBody()?.string() ?: "Error desconocido"
                    throw Exception("Error al actualizar el trabajo: ${response.code()} - $errorBody")
                }
            } catch (e: Exception) {
                uiState = uiState.copy(isLoading = false, error = e.message)
            }
        }
    }

    fun deleteJob(context: Context) {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null, loadingMessage = "Eliminando trabajo...")
            try {
                val token = AuthManager.token ?: throw Exception("No estás autenticado.")
                val authHeader = "Bearer $token"

                val response = WordPressApi.retrofitService.deleteJob(authHeader, job.id)

                if (response.isSuccessful) {
                    uiState = uiState.copy(isLoading = false, deleteSuccess = true)
                } else {
                    throw Exception("Error al eliminar el trabajo: ${response.code()}")
                }
            } catch (e: Exception) {
                uiState = uiState.copy(isLoading = false, error = e.message)
            }
        }
    }

    private fun createStreamingRequestBody(context: Context, uri: Uri): RequestBody? {
        return try {
            val contentType = context.contentResolver.getType(uri)?.toMediaTypeOrNull() 
                ?: "image/jpeg".toMediaTypeOrNull()
            
            if (contentType == null) {
                android.util.Log.e("EditJobViewModel", "No se pudo determinar el tipo de contenido para: $uri")
                return null
            }
            
            object : RequestBody() {
                override fun contentType() = contentType

                override fun contentLength(): Long {
                    return try {
                        context.contentResolver.openFileDescriptor(uri, "r")?.use { pfd ->
                            pfd.statSize
                        } ?: -1L
                    } catch (e: Exception) {
                        android.util.Log.e("EditJobViewModel", "Error al obtener tamaño del archivo", e)
                        -1L
                    }
                }

                override fun writeTo(sink: BufferedSink) {
                    try {
                        context.contentResolver.openInputStream(uri)?.use { inputStream ->
                            inputStream.source().use { source ->
                                sink.writeAll(source)
                            }
                        } ?: throw Exception("No se pudo abrir el archivo: $uri")
                    } catch (e: Exception) {
                        android.util.Log.e("EditJobViewModel", "Error al escribir archivo al sink", e)
                        throw e
                    }
                }
            }
        } catch (e: Exception) {
            android.util.Log.e("EditJobViewModel", "Error al crear RequestBody", e)
            null
        }
    }
    
    // Función para obtener el nombre del archivo desde la URI
    private fun getFileName(context: Context, uri: Uri): String? {
        return try {
            var fileName: String? = null
            
            // Intentar obtener el nombre desde la URI
            if (uri.scheme == "content") {
                val cursor = context.contentResolver.query(uri, null, null, null, null)
                cursor?.use {
                    if (it.moveToFirst()) {
                        val nameIndex = it.getColumnIndex(android.provider.OpenableColumns.DISPLAY_NAME)
                        if (nameIndex != -1) {
                            fileName = it.getString(nameIndex)
                        }
                    }
                }
            }
            
            // Si no se encontró, intentar desde el path
            if (fileName.isNullOrBlank()) {
                val path = uri.path
                if (!path.isNullOrBlank()) {
                    fileName = path.substringAfterLast('/')
                }
            }
            
            // Si aún no hay nombre, usar uno por defecto basado en el tipo
            if (fileName.isNullOrBlank()) {
                val mimeType = context.contentResolver.getType(uri)
                fileName = when {
                    mimeType?.startsWith("image/png") == true -> "image.png"
                    mimeType?.startsWith("image/jpeg") == true -> "image.jpg"
                    mimeType?.startsWith("image/jpg") == true -> "image.jpg"
                    mimeType?.startsWith("image/webp") == true -> "image.webp"
                    else -> "image.jpg"
                }
            }
            
            fileName
        } catch (e: Exception) {
            android.util.Log.e("EditJobViewModel", "Error al obtener nombre de archivo", e)
            "image.jpg"
        }
    }
}

class EditJobViewModelFactory(private val job: JobPost) : ViewModelProvider.Factory {
    override fun <T : ViewModel> create(modelClass: Class<T>): T {
        if (modelClass.isAssignableFrom(EditJobViewModel::class.java)) {
            @Suppress("UNCHECKED_CAST")
            return EditJobViewModel(job) as T
        }
        throw IllegalArgumentException("Unknown ViewModel class")
    }
}

