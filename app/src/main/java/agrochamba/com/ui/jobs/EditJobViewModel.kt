package agrochamba.com.ui.jobs

import android.content.Context
import android.net.Uri
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AIService
import agrochamba.com.data.AuthManager
import agrochamba.com.data.SettingsManager
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
    val categorias: List<Category> = emptyList(),
    val selectedImages: List<Uri> = emptyList(),
    val existingImageUrls: List<String> = emptyList(), // URLs de imágenes existentes
    val existingImageIds: List<Int> = emptyList(), // IDs de imágenes existentes (para mapear con URLs)
    val featuredImageIndex: Int = 0,
    val imagesLoaded: Boolean = false, // Flag para saber si las imágenes ya se cargaron
    val userCompanyId: Int? = null,
    // Estados de IA
    val isAIEnhancing: Boolean = false,
    val isOCRProcessing: Boolean = false,
    val aiError: String? = null,
    val aiSuccess: String? = null,
    // Límites de uso de IA
    val aiUsesRemaining: Int = -1, // -1 = ilimitado o desconocido
    val aiIsPremium: Boolean = false
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
        loadAIUsageStatus()
    }
    
    /**
     * Cargar el estado de uso de IA del usuario
     */
    private fun loadAIUsageStatus() {
        viewModelScope.launch {
            when (val result = AIService.getUsageStatus()) {
                is AIService.AIResult.Success -> {
                    val status = result.data
                    uiState = uiState.copy(
                        aiUsesRemaining = status.remaining,
                        aiIsPremium = status.isPremium
                    )
                    android.util.Log.d("EditJobViewModel", "AI usage loaded: remaining=${status.remaining}, isPremium=${status.isPremium}")
                }
                is AIService.AIResult.Error -> {
                    // Si falla, asumir admin (sin límites) para no bloquear la funcionalidad
                    if (AuthManager.isUserAdmin()) {
                        uiState = uiState.copy(aiUsesRemaining = -1, aiIsPremium = true)
                    }
                    android.util.Log.w("EditJobViewModel", "Could not load AI usage: ${result.message}")
                }
                is AIService.AIResult.Loading -> {}
            }
        }
    }
    
    /**
     * Actualizar el estado de uso de IA desde la respuesta
     */
    private fun updateAIUsageFromService() {
        AIService.usageStatus?.let { status ->
            uiState = uiState.copy(
                aiUsesRemaining = status.remaining,
                aiIsPremium = status.isPremium
            )
        }
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
                val categoriasDeferred = async { WordPressApi.retrofitService.getCategories() }

                uiState = uiState.copy(
                    ubicaciones = ubicacionesDeferred.await(),
                    empresas = empresasDeferred.await(),
                    cultivos = cultivosDeferred.await(),
                    tiposPuesto = tiposPuestoDeferred.await(),
                    categorias = categoriasDeferred.await(),
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
        
        // Calcular el índice destacado de forma segura
        val newFeaturedIndex = when {
            newImages.isEmpty() -> 0 // Si no hay imágenes, usar 0 por defecto
            uiState.selectedImages.isEmpty() && newImages.isNotEmpty() -> 0 // Si es la primera selección, usar 0
            else -> {
                // Asegurar que el índice esté dentro del rango válido
                val maxIndex = newImages.size - 1
                if (maxIndex >= 0) {
                    uiState.featuredImageIndex.coerceIn(0, maxIndex)
                } else {
                    0
                }
            }
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
    
    // ==========================================
    // FUNCIONES DE IA
    // ==========================================

    /**
     * Mejorar texto de la descripción usando IA
     */
    fun enhanceTextWithAI(
        currentText: String,
        type: String = "job",
        onResult: (String) -> Unit
    ) {
        if (currentText.isBlank()) {
            uiState = uiState.copy(aiError = "Escribe algo primero para que la IA pueda mejorarlo")
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(isAIEnhancing = true, aiError = null, aiSuccess = null)

            when (val result = AIService.enhanceText(currentText, type)) {
                is AIService.AIResult.Success -> {
                    updateAIUsageFromService()
                    uiState = uiState.copy(
                        isAIEnhancing = false,
                        aiSuccess = "✨ Texto mejorado con IA"
                    )
                    onResult(result.data)
                }
                is AIService.AIResult.Error -> {
                    if (result.isLimitReached) {
                        uiState = uiState.copy(
                            isAIEnhancing = false,
                            aiError = result.message,
                            aiUsesRemaining = 0,
                            aiIsPremium = false
                        )
                    } else {
                        uiState = uiState.copy(
                            isAIEnhancing = false,
                            aiError = result.message
                        )
                    }
                }
                is AIService.AIResult.Loading -> {
                    // Already handling in isAIEnhancing
                }
            }
        }
    }

    /**
     * Generar título optimizado para SEO
     */
    fun generateTitleWithAI(
        description: String,
        location: String? = null,
        onResult: (String) -> Unit
    ) {
        if (description.isBlank()) {
            uiState = uiState.copy(aiError = "Primero escribe una descripción para generar el título")
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(isAIEnhancing = true, aiError = null, aiSuccess = null)

            when (val result = AIService.generateTitle(description, location)) {
                is AIService.AIResult.Success -> {
                    updateAIUsageFromService()
                    uiState = uiState.copy(
                        isAIEnhancing = false,
                        aiSuccess = if (result.data.isSeoOptimal) 
                            "✨ Título SEO óptimo generado" 
                            else "✨ Título generado"
                    )
                    onResult(result.data.title)
                }
                is AIService.AIResult.Error -> {
                    if (result.isLimitReached) {
                        uiState = uiState.copy(
                            isAIEnhancing = false,
                            aiError = result.message,
                            aiUsesRemaining = 0,
                            aiIsPremium = false
                        )
                    } else {
                        uiState = uiState.copy(
                            isAIEnhancing = false,
                            aiError = result.message
                        )
                    }
                }
                is AIService.AIResult.Loading -> {
                    // Already handling in isAIEnhancing
                }
            }
        }
    }

    /**
     * Extraer texto de imagen (OCR) y opcionalmente mejorarlo
     */
    /**
     * Extraer texto de imagen (OCR) y opcionalmente mejorarlo
     * Soporta tanto URLs HTTP como URIs locales (content://)
     */
    fun extractTextFromImage(
        imageUrl: String,
        enhance: Boolean = true,
        context: android.content.Context? = null,
        onResult: (extractedText: String, enhancedText: String?) -> Unit
    ) {
        viewModelScope.launch {
            uiState = uiState.copy(isOCRProcessing = true, aiError = null, aiSuccess = null)
            
            // Determinar si es un URI local que necesita subirse primero
            val finalImageUrl: String
            if (imageUrl.startsWith("content://") || imageUrl.startsWith("file://")) {
                // Es un URI local, necesitamos subir la imagen primero
                if (context == null) {
                    uiState = uiState.copy(
                        isOCRProcessing = false,
                        aiError = "Error: No se puede procesar imagen local sin contexto"
                    )
                    return@launch
                }
                
                uiState = uiState.copy(loadingMessage = "Subiendo imagen...")
                
                val uploadResult = uploadImageForOCR(context, android.net.Uri.parse(imageUrl))
                if (uploadResult == null) {
                    uiState = uiState.copy(
                        isOCRProcessing = false,
                        aiError = "Error al subir la imagen para OCR"
                    )
                    return@launch
                }
                finalImageUrl = uploadResult
                uiState = uiState.copy(loadingMessage = "Extrayendo texto...")
            } else {
                // Ya es una URL HTTP, usarla directamente
                finalImageUrl = imageUrl
            }

            when (val result = AIService.extractTextFromImage(imageUrl = finalImageUrl, enhance = enhance)) {
                is AIService.AIResult.Success -> {
                    updateAIUsageFromService()
                    uiState = uiState.copy(
                        isOCRProcessing = false,
                        aiSuccess = if (result.data.enhancedText != null) 
                            "📄 Texto extraído y mejorado" 
                            else "📄 Texto extraído de la imagen"
                    )
                    onResult(result.data.extractedText, result.data.enhancedText)
                }
                is AIService.AIResult.Error -> {
                    if (result.isLimitReached) {
                        uiState = uiState.copy(
                            isOCRProcessing = false,
                            aiError = result.message,
                            aiUsesRemaining = 0,
                            aiIsPremium = false
                        )
                    } else {
                        uiState = uiState.copy(
                            isOCRProcessing = false,
                            aiError = result.message
                        )
                    }
                }
                is AIService.AIResult.Loading -> {
                    // Already handling in isOCRProcessing
                }
            }
        }
    }
    
    /**
     * Subir imagen temporalmente para OCR
     */
    private suspend fun uploadImageForOCR(context: android.content.Context, uri: android.net.Uri): String? {
        return try {
            val token = AuthManager.token ?: return null
            
            val inputStream = context.contentResolver.openInputStream(uri)
            val bytes = inputStream?.readBytes()
            inputStream?.close()
            
            if (bytes == null || bytes.isEmpty()) return null
            
            val mimeType = context.contentResolver.getType(uri) ?: "image/jpeg"
            val mediaType = mimeType.toMediaTypeOrNull() ?: "image/jpeg".toMediaTypeOrNull()
            val fileName = "ocr_temp_${System.currentTimeMillis()}.jpg"
            
            val requestFile = okhttp3.RequestBody.create(mediaType, bytes)
            val body = okhttp3.MultipartBody.Part.createFormData("file", fileName, requestFile)
            
            val mediaItem = WordPressApi.retrofitService.uploadImage(
                "Bearer $token",
                body,
                emptyMap()
            )
            
            // Retornar la URL de la imagen subida
            mediaItem.source_url
        } catch (e: Exception) {
            android.util.Log.e("EditJobViewModel", "Error uploading image for OCR", e)
            null
        }
    }

    /**
     * Limpiar mensajes de IA
     */
    fun clearAIMessages() {
        uiState = uiState.copy(aiError = null, aiSuccess = null)
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
                val ubicacionCompleta = jobData["_ubicacion_completa"] as? Map<*, *>

                if (title.isNullOrBlank()) {
                    throw Exception("El título es obligatorio.")
                }
                if (content.isNullOrBlank()) {
                    throw Exception("La descripción es obligatoria.")
                }
                // Validar ubicación: aceptar ubicacion_id O _ubicacion_completa con departamento
                val tieneUbicacionCompleta = ubicacionCompleta != null &&
                    (ubicacionCompleta["departamento"] as? String)?.isNotBlank() == true
                if (ubicacionId == null && !tieneUbicacionCompleta) {
                    throw Exception("La ubicación es obligatoria.")
                }

                // Si hay nuevas imágenes, subirlas
                val finalJobData = jobData.toMutableMap()
                
                // IMPORTANTE: Asegurar que publish_to_facebook esté presente (puede venir como Boolean o String)
                val publishToFacebook = when (val value = jobData["publish_to_facebook"]) {
                    is Boolean -> value
                    is String -> value.toBoolean()
                    is Number -> value.toInt() != 0
                    else -> false
                }
                finalJobData["publish_to_facebook"] = publishToFacebook
                
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
                
                // Enviar preferencia de publicación en Facebook (imágenes adjuntas o link preview)
                val useLinkPreview = SettingsManager.facebookUseLinkPreview
                finalJobData["facebook_use_link_preview"] = useLinkPreview
                
                // Enviar preferencia de acortar contenido en Facebook
                val shortenContent = SettingsManager.facebookShortenContent
                finalJobData["facebook_shorten_content"] = shortenContent
                
                // Asegurar que publish_to_facebook esté presente (ya se estableció arriba, pero por seguridad lo verificamos)
                finalJobData["publish_to_facebook"] = publishToFacebook
                
                // Comentarios habilitados (por defecto true, siempre enviar)
                val comentariosHabilitados = jobData["comentarios_habilitados"] as? Boolean ?: true
                finalJobData["comentarios_habilitados"] = comentariosHabilitados

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

