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
        loadFormData()
        loadExistingImages()
    }

    private fun loadFormData() {
        viewModelScope.launch {
            // Preservar las imágenes existentes al actualizar el estado
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
                    // Preservar las imágenes existentes
                    existingImageUrls = currentImageUrls,
                    existingImageIds = currentImageIds,
                    imagesLoaded = currentImagesLoaded
                )
            } catch (e: Exception) {
                uiState = uiState.copy(
                    isLoading = false, 
                    error = "No se pudieron cargar los datos del formulario.",
                    // Preservar las imágenes existentes incluso en caso de error
                    existingImageUrls = currentImageUrls,
                    existingImageIds = currentImageIds,
                    imagesLoaded = currentImagesLoaded
                )
            }
        }
    }

    private fun loadExistingImages() {
        viewModelScope.launch {
            try {
                android.util.Log.d("EditJobViewModel", "Cargando imágenes existentes para trabajo ID: ${job.id}")
                
                val token = AuthManager.token
                val authHeader = token?.let { "Bearer $it" }
                
                // Estrategia 1: Usar gallery_ids directamente del job.meta (más confiable)
                val galleryIds = job.meta?.galleryIds
                val (imageUrls, imageIds) = if (!galleryIds.isNullOrEmpty()) {
                    android.util.Log.d("EditJobViewModel", "Cargando desde gallery_ids: $galleryIds")
                    try {
                        // Cargar URLs de las imágenes usando los IDs
                        val imagesWithIds = galleryIds.mapNotNull { id ->
                            try {
                                val media = WordPressApi.retrofitService.getMediaById(id)
                                val url = media.getImageUrl()
                                if (url != null) {
                                    android.util.Log.d("EditJobViewModel", "Imagen cargada: ID=$id, URL=$url")
                                    Pair(url, id)
                                } else {
                                    android.util.Log.w("EditJobViewModel", "No se pudo obtener URL para ID $id")
                                    null
                                }
                            } catch (e: Exception) {
                                android.util.Log.w("EditJobViewModel", "Error cargando media ID $id: ${e.message}")
                                null
                            }
                        }
                        android.util.Log.d("EditJobViewModel", "Imágenes cargadas desde gallery_ids: ${imagesWithIds.size}")
                        Pair(imagesWithIds.map { it.first }, imagesWithIds.map { it.second })
                    } catch (e: Exception) {
                        android.util.Log.w("EditJobViewModel", "Error procesando gallery_ids: ${e.message}")
                        Pair(emptyList(), emptyList())
                    }
                } else {
                    // Estrategia 2: Intentar con getJobImages (requiere autenticación)
                    try {
                        if (authHeader != null) {
                            // Nota: getJobImages puede requerir autenticación, pero actualmente no la tiene
                            // Intentamos de todas formas
                            val response = WordPressApi.retrofitService.getJobImages(job.id)
                            val urls = response.images.mapNotNull { it.getDetailUrl() }
                            val ids = response.images.mapNotNull { it.id }
                            android.util.Log.d("EditJobViewModel", "Imágenes cargadas desde getJobImages: ${urls.size}")
                            Pair(urls, ids)
                        } else {
                            throw Exception("No hay token de autenticación")
                        }
                    } catch (e: Exception) {
                        android.util.Log.w("EditJobViewModel", "Error en getJobImages: ${e.message}")
                        // Estrategia 3: Intentar con getMediaForPost
                        try {
                            android.util.Log.d("EditJobViewModel", "Intentando cargar desde getMediaForPost")
                            val media = WordPressApi.retrofitService.getMediaForPost(job.id)
                            val urls = media.mapNotNull { it.getImageUrl() }
                            val ids = media.mapNotNull { it.id }
                            android.util.Log.d("EditJobViewModel", "Imágenes cargadas desde getMediaForPost: ${urls.size}")
                            Pair(urls, ids)
                        } catch (e: Exception) {
                            android.util.Log.w("EditJobViewModel", "Error en getMediaForPost: ${e.message}")
                            // Estrategia 4: Usar embedded como último recurso
                            val embeddedUrls = job.embedded?.featuredMedia?.mapNotNull { it.getImageUrl() } ?: emptyList()
                            val embeddedIds = job.embedded?.featuredMedia?.mapNotNull { it.id } ?: emptyList()
                            android.util.Log.d("EditJobViewModel", "Imágenes cargadas desde embedded: ${embeddedUrls.size}")
                            Pair(embeddedUrls, embeddedIds)
                        }
                    }
                }
                
                // Agregar featured_media_url si existe y no está ya en la lista
                val featuredMediaUrl = job.embedded?.featuredMedia?.firstOrNull()?.getImageUrl()
                val featuredMediaId = job.embedded?.featuredMedia?.firstOrNull()?.id
                
                val finalUrls = imageUrls.toMutableList()
                val finalIds = imageIds.toMutableList()
                
                // Si hay featured_media_url y no está en la lista, agregarlo al inicio
                if (featuredMediaUrl != null && featuredMediaId != null && 
                    !finalUrls.contains(featuredMediaUrl) && !finalIds.contains(featuredMediaId)) {
                    finalUrls.add(0, featuredMediaUrl)
                    finalIds.add(0, featuredMediaId)
                    android.util.Log.d("EditJobViewModel", "Agregada imagen destacada al inicio")
                }
                
                android.util.Log.d("EditJobViewModel", "Finalizando carga: ${finalUrls.size} URLs, ${finalIds.size} IDs")
                
                uiState = uiState.copy(
                    existingImageUrls = finalUrls,
                    existingImageIds = finalIds,
                    imagesLoaded = true
                )
                
                android.util.Log.d("EditJobViewModel", "Estado actualizado. existingImageUrls.size = ${uiState.existingImageUrls.size}")
            } catch (e: Exception) {
                android.util.Log.e("EditJobViewModel", "Error general cargando imágenes: ${e.message}", e)
                // Si hay error, intentar al menos con embedded
                val embeddedUrls = job.embedded?.featuredMedia?.mapNotNull { it.getImageUrl() } ?: emptyList()
                val embeddedIds = job.embedded?.featuredMedia?.mapNotNull { it.id }?.filterNotNull() ?: emptyList()
                uiState = uiState.copy(
                    existingImageUrls = embeddedUrls,
                    existingImageIds = embeddedIds,
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

