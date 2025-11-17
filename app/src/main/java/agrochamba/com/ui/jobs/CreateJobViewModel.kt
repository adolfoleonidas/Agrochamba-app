package agrochamba.com.ui.jobs

import android.content.Context
import android.net.Uri
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.Category
import agrochamba.com.data.WordPressApi
import kotlinx.coroutines.async
import kotlinx.coroutines.awaitAll
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody
import okio.BufferedSink
import okio.source

data class CreateJobScreenState(
    val isLoading: Boolean = true,
    val loadingMessage: String = "Cargando formulario...",
    val error: String? = null,
    val postSuccess: Boolean = false,
    val ubicaciones: List<Category> = emptyList(),
    val empresas: List<Category> = emptyList(),
    val cultivos: List<Category> = emptyList(),
    val tiposPuesto: List<Category> = emptyList(),
    val selectedImages: List<Uri> = emptyList(),
    val featuredImageIndex: Int = 0, // Índice de la imagen destacada
    val userCompanyId: Int? = null // ID de la empresa del usuario si es empresa
)

class CreateJobViewModel : ViewModel() {

    var uiState by mutableStateOf(CreateJobScreenState())
        private set

    init {
        loadFormData()
    }

    private fun loadFormData() {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)
            try {
                val ubicacionesDeferred = async { WordPressApi.retrofitService.getUbicaciones() }
                val empresasDeferred = async { WordPressApi.retrofitService.getEmpresas() }
                val cultivosDeferred = async { WordPressApi.retrofitService.getCultivos() }
                val tiposPuestoDeferred = async { WordPressApi.retrofitService.getTiposPuesto() }

                val ubicaciones = ubicacionesDeferred.await()
                val empresas = empresasDeferred.await()
                val cultivos = cultivosDeferred.await()
                val tiposPuesto = tiposPuestoDeferred.await()
                
                // Si el usuario es empresa, buscar su empresa por nombre (usando displayName)
                var userCompanyId: Int? = null
                if (AuthManager.isUserAnEnterprise()) {
                    val userDisplayName = AuthManager.userDisplayName
                    if (userDisplayName != null) {
                        // Buscar la empresa que coincida con el nombre del usuario
                        userCompanyId = empresas.find { 
                            it.name.equals(userDisplayName, ignoreCase = true) 
                        }?.id
                    }
                }

                uiState = uiState.copy(
                    ubicaciones = ubicaciones,
                    empresas = empresas,
                    cultivos = cultivos,
                    tiposPuesto = tiposPuesto,
                    userCompanyId = userCompanyId,
                    isLoading = false
                )
            } catch (e: Exception) {
                uiState = uiState.copy(isLoading = false, error = "No se pudieron cargar los datos del formulario.")
            }
        }
    }

    fun onImagesSelected(uris: List<Uri>) {
        val currentImages = uiState.selectedImages.toMutableList()
        currentImages.addAll(uris)
        val newImages = currentImages.take(10) // Limitar a 10 imágenes
        // Si no hay imágenes destacadas y agregamos nuevas, la primera será destacada
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
        val indexToRemove = currentImages.indexOf(uri)
        currentImages.remove(uri)
        val updatedImages = currentImages
        
        // Ajustar el índice de la imagen destacada si es necesario
        val newFeaturedIndex = when {
            updatedImages.isEmpty() -> 0
            indexToRemove < uiState.featuredImageIndex -> uiState.featuredImageIndex - 1
            indexToRemove == uiState.featuredImageIndex -> {
                // Si eliminamos la imagen destacada, la primera será la nueva destacada
                0.coerceAtMost(updatedImages.size - 1)
            }
            else -> uiState.featuredImageIndex
        }.coerceIn(0, updatedImages.size - 1)
        
        uiState = uiState.copy(
            selectedImages = updatedImages,
            featuredImageIndex = newFeaturedIndex
        )
    }

    fun setFeaturedImage(index: Int) {
        if (index in uiState.selectedImages.indices) {
            uiState = uiState.copy(featuredImageIndex = index)
        }
    }

    fun reorderImages(fromIndex: Int, toIndex: Int) {
        if (fromIndex == toIndex || fromIndex !in uiState.selectedImages.indices || toIndex !in uiState.selectedImages.indices) {
            return
        }
        val images = uiState.selectedImages.toMutableList()
        val item = images.removeAt(fromIndex)
        images.add(toIndex, item)
        // La primera imagen siempre es la destacada después de reordenar
        uiState = uiState.copy(
            selectedImages = images,
            featuredImageIndex = 0
        )
    }

    fun createJob(jobData: Map<String, Any>, context: Context) {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null, loadingMessage = "Publicando, por favor espera...")
            try {
                val token = AuthManager.token ?: throw Exception("No estás autenticado.")
                val authHeader = "Bearer $token"

                val finalJobData = jobData.toMutableMap()
                
                // 1. Subir imágenes solo si hay imágenes seleccionadas
                if (uiState.selectedImages.isNotEmpty()) {
                    uiState = uiState.copy(loadingMessage = "Subiendo imágenes...")
                    val uploadedImageIds = mutableListOf<Int>()
                    val totalImages = uiState.selectedImages.size
                    
                    // Subir imágenes una por una para mejor manejo de errores
                    uiState.selectedImages.forEachIndexed { index, uri ->
                        try {
                            uiState = uiState.copy(loadingMessage = "Subiendo imagen ${index + 1} de $totalImages...")
                            
                            // Validar que el URI sea accesible
                            val inputStream = context.contentResolver.openInputStream(uri)
                            if (inputStream == null) {
                                android.util.Log.e("CreateJobViewModel", "No se pudo abrir el archivo: $uri")
                                return@forEachIndexed
                            }
                            inputStream.close()
                            
                            val requestBody = createStreamingRequestBody(context, uri)
                            if (requestBody == null) {
                                android.util.Log.e("CreateJobViewModel", "No se pudo crear RequestBody para: $uri")
                                return@forEachIndexed
                            }
                            
                            val fileName = getFileName(context, uri) ?: "image.jpg"
                            val part = MultipartBody.Part.createFormData("file", fileName, requestBody)
                            val mediaItem = WordPressApi.retrofitService.uploadImage(authHeader, part, emptyMap())
                            
                            mediaItem.id?.let { id ->
                                uploadedImageIds.add(id)
                                android.util.Log.d("CreateJobViewModel", "Imagen subida exitosamente: ID=$id")
                            } ?: run {
                                android.util.Log.e("CreateJobViewModel", "La imagen se subió pero no se recibió ID")
                            }
                        } catch (e: Exception) {
                            android.util.Log.e("CreateJobViewModel", "Error al subir imagen ${index + 1}: ${e.message}", e)
                            // Continuar con las siguientes imágenes en lugar de fallar completamente
                        }
                    }

                    if (uploadedImageIds.isNotEmpty()) {
                        // La primera imagen siempre es la destacada
                        finalJobData["featured_media"] = uploadedImageIds[0]
                        finalJobData["gallery_ids"] = uploadedImageIds
                        android.util.Log.d("CreateJobViewModel", "Total de imágenes subidas: ${uploadedImageIds.size}")
                    } else {
                        throw Exception("No se pudo subir ninguna imagen. Verifica que los archivos sean válidos.")
                    }
                }

                // 2. Crear el post con los IDs de las imágenes
                uiState = uiState.copy(loadingMessage = "Creando anuncio...")
                val response = WordPressApi.retrofitService.createJob(authHeader, finalJobData)

                if (response.isSuccessful) {
                    uiState = uiState.copy(isLoading = false, postSuccess = true)
                } else {
                    val errorBody = response.errorBody()?.string() ?: "Error desconocido"
                    throw Exception("Error al publicar el trabajo: ${response.code()} - $errorBody")
                }
            } catch (e: Exception) {
                uiState = uiState.copy(isLoading = false, error = e.message ?: "Ocurrió un error desconocido")
            }
        }
    }

    // Función para crear un RequestBody que hace streaming del contenido de la URI
    private fun createStreamingRequestBody(context: Context, uri: Uri): RequestBody? {
        return try {
            val contentType = context.contentResolver.getType(uri)?.toMediaTypeOrNull() 
                ?: "image/jpeg".toMediaTypeOrNull()
            
            if (contentType == null) {
                android.util.Log.e("CreateJobViewModel", "No se pudo determinar el tipo de contenido para: $uri")
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
                        android.util.Log.e("CreateJobViewModel", "Error al obtener tamaño del archivo", e)
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
                        android.util.Log.e("CreateJobViewModel", "Error al escribir archivo al sink", e)
                        throw e
                    }
                }
            }
        } catch (e: Exception) {
            android.util.Log.e("CreateJobViewModel", "Error al crear RequestBody", e)
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
            android.util.Log.e("CreateJobViewModel", "Error al obtener nombre de archivo", e)
            "image.jpg"
        }
    }
}