package agrochamba.com.ui.jobs

import android.content.Context
import android.net.Uri
import android.util.Log
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import agrochamba.com.data.AuthManager
import agrochamba.com.data.Category
import agrochamba.com.data.SettingsManager
import agrochamba.com.data.WordPressApi
import com.squareup.moshi.JsonEncodingException
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import retrofit2.HttpException
import java.util.Collections
import javax.inject.Inject

data class CreateJobScreenState(
    val isLoading: Boolean = true,
    val loadingMessage: String = "Cargando formulario...",
    val error: String? = null,
    val postSuccess: Boolean = false,
    val ubicaciones: List<Category> = emptyList(),
    val empresas: List<Category> = emptyList(),
    val cultivos: List<Category> = emptyList(),
    val tiposPuesto: List<Category> = emptyList(),
    val categorias: List<Category> = emptyList(),
    val selectedImages: List<Uri> = emptyList(),
    val userCompanyId: Int? = null
)

@HiltViewModel
class CreateJobViewModel @Inject constructor() : androidx.lifecycle.ViewModel() {

    var uiState by mutableStateOf(CreateJobScreenState())
        private set

    init {
        loadFormData()
    }

    private fun loadFormData() {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null)
            try {
                // Cargar catálogos desde WordPress API (taxonomías)
                val ubicaciones = WordPressApi.retrofitService.getUbicaciones()
                val empresas = WordPressApi.retrofitService.getEmpresas()
                val cultivos = WordPressApi.retrofitService.getCultivos()
                val tiposPuesto = WordPressApi.retrofitService.getTiposPuesto()
                // Cargar categorías nativas de WordPress (para blogs)
                val categorias = WordPressApi.retrofitService.getCategories()

                uiState = uiState.copy(
                    ubicaciones = ubicaciones,
                    empresas = empresas,
                    cultivos = cultivos,
                    tiposPuesto = tiposPuesto,
                    categorias = categorias,
                    userCompanyId = AuthManager.userCompanyId,
                    isLoading = false
                )
            } catch (e: Exception) {
                Log.e("CreateJobViewModel", "Error loading form data", e)
                uiState = uiState.copy(isLoading = false, error = "No se pudieron cargar los datos del formulario.")
            }
        }
    }

    fun onImagesSelected(uris: List<Uri>) {
        val currentImages = uiState.selectedImages.toMutableList()
        currentImages.addAll(uris)
        uiState = uiState.copy(selectedImages = currentImages.take(10))
    }

    fun removeImage(uri: Uri) {
        val updatedImages = uiState.selectedImages.toMutableList().also { it.remove(uri) }
        uiState = uiState.copy(selectedImages = updatedImages)
    }
    
    fun reorderImages(from: Int, to: Int) {
        if (from >= 0 && to >= 0 && from < uiState.selectedImages.size && to < uiState.selectedImages.size) {
            val mutableList = uiState.selectedImages.toMutableList()
            Collections.swap(mutableList, from, to)
            uiState = uiState.copy(selectedImages = mutableList)
        }
    }

    fun createJob(jobData: Map<String, Any?>, context: Context) {
        viewModelScope.launch {
            uiState = uiState.copy(isLoading = true, error = null, loadingMessage = "Publicando, por favor espera...")
            try {
                val token = AuthManager.token
                if (token == null) {
                    throw Exception("Debes iniciar sesión para crear trabajos.")
                }

                // Primero subir imágenes si hay
                val galleryIds = mutableListOf<Int>()
                if (uiState.selectedImages.isNotEmpty()) {
                    uiState = uiState.copy(loadingMessage = "Subiendo imágenes...")
                    for (imageUri in uiState.selectedImages) {
                        try {
                            // Leer el archivo desde el URI
                            val inputStream = context.contentResolver.openInputStream(imageUri)
                            val bytes = inputStream?.readBytes()
                            inputStream?.close()
                            
                            if (bytes != null && bytes.isNotEmpty()) {
                                // Determinar el tipo MIME basado en el URI
                                val mimeType = context.contentResolver.getType(imageUri) ?: "image/jpeg"
                                val mediaType = mimeType.toMediaTypeOrNull() ?: "image/jpeg".toMediaTypeOrNull()
                                
                                // Obtener el nombre del archivo si está disponible
                                val fileName = try {
                                    val cursor = context.contentResolver.query(
                                        imageUri,
                                        arrayOf(android.provider.OpenableColumns.DISPLAY_NAME),
                                        null,
                                        null,
                                        null
                                    )
                                    cursor?.use {
                                        if (it.moveToFirst()) {
                                            val nameIndex = it.getColumnIndex(android.provider.OpenableColumns.DISPLAY_NAME)
                                            if (nameIndex >= 0) it.getString(nameIndex) else null
                                        } else null
                                    } ?: "image_${System.currentTimeMillis()}.jpg"
                                } catch (e: Exception) {
                                    "image_${System.currentTimeMillis()}.jpg"
                                }
                                
                                val requestFile = okhttp3.RequestBody.create(mediaType, bytes)
                                val body = MultipartBody.Part.createFormData(
                                    "file",
                                    fileName,
                                    requestFile
                                )
                                
                                // Subir imagen a WordPress
                                val authHeader = "Bearer $token"
                                val mediaItem = WordPressApi.retrofitService.uploadImage(
                                    authHeader,
                                    body,
                                    emptyMap()
                                )
                                // Añadir solo si el ID no es nulo
                                mediaItem.id?.let { galleryIds.add(it) }
                            }
                        } catch (e: Exception) {
                            Log.e("CreateJobViewModel", "Error uploading image", e)
                            // Continuar aunque falle una imagen
                        }
                    }
                }

                // Preparar payload para WordPress
                uiState = uiState.copy(loadingMessage = "Creando trabajo...")
                
                // Validaciones básicas
                val title = jobData["title"] as? String
                val content = jobData["content"] as? String
                val postType = jobData["post_type"] as? String ?: "trabajo" // Por defecto trabajo
                val ubicacionId = jobData["ubicacion_id"] as? Number
                
                if (title.isNullOrBlank()) {
                    throw Exception("El título es obligatorio.")
                }
                if (content.isNullOrBlank()) {
                    throw Exception("La descripción es obligatoria.")
                }
                
                // Solo validar ubicación si es un trabajo (no para blogs)
                if (postType == "trabajo" && ubicacionId == null) {
                    throw Exception("La ubicación es obligatoria.")
                }
                
                val payload: MutableMap<String, Any> = mutableMapOf<String, Any>().apply {
                    // Campos obligatorios (no nulos)
                    put("title", title.trim())
                    put("content", content.trim())
                    
                    // IMPORTANTE: Siempre enviar post_type para que el backend sepa qué tipo crear
                    put("post_type", postType)
                    android.util.Log.d("CreateJobViewModel", "Enviando post_type: $postType")
                    
                    // Solo agregar ubicacion_id si es trabajo y está presente
                    if (postType == "trabajo" && ubicacionId != null) {
                        put("ubicacion_id", ubicacionId.toInt())
                    }

                    // Convertir valores numéricos
                    val salarioMin = (jobData["salario_min"] as? Number)?.toInt() ?: 0
                    val salarioMax = (jobData["salario_max"] as? Number)?.toInt() ?: 0
                    val vacantes = (jobData["vacantes"] as? Number)?.toInt() ?: 1

                    if (salarioMin > 0) put("salario_min", salarioMin)
                    if (salarioMax > 0) put("salario_max", salarioMax)
                    if (vacantes > 0) put("vacantes", vacantes)

                    // Taxonomías opcionales (solo si están seleccionadas)
                    (jobData["empresa_id"] as? Number)?.toInt()?.let { put("empresa_id", it) }
                    (jobData["cultivo_id"] as? Number)?.toInt()?.let { put("cultivo_id", it) }
                    (jobData["tipo_puesto_id"] as? Number)?.toInt()?.let { put("tipo_puesto_id", it) }
                    
                    // Categorías para blogs (solo si es post y hay categorías seleccionadas)
                    if (postType == "post" && jobData["categories"] != null) {
                        val categories = jobData["categories"] as? List<*>
                        if (categories != null && categories.isNotEmpty()) {
                            val categoryIds = categories.mapNotNull { (it as? Number)?.toInt() }
                            if (categoryIds.isNotEmpty()) {
                                put("categories", categoryIds)
                            }
                        }
                    }

                    // Beneficios (solo enviar si son true)
                    val alojamiento = jobData["alojamiento"] as? Boolean ?: false
                    val transporte = jobData["transporte"] as? Boolean ?: false
                    val alimentacion = jobData["alimentacion"] as? Boolean ?: false

                    if (alojamiento) put("alojamiento", true)
                    if (transporte) put("transporte", true)
                    if (alimentacion) put("alimentacion", true)
                    
                    // Comentarios habilitados (por defecto true, siempre enviar)
                    val comentariosHabilitados = jobData["comentarios_habilitados"] as? Boolean ?: true
                    put("comentarios_habilitados", comentariosHabilitados)

                    // Publicar en Facebook - SIEMPRE enviar el flag (true o false) para que el backend respete la decisión del usuario
                    val publishToFacebook = jobData["publish_to_facebook"] as? Boolean ?: false
                    put("publish_to_facebook", publishToFacebook)
                    
                    // Enviar preferencia de publicación en Facebook (imágenes adjuntas o link preview)
                    val useLinkPreview = SettingsManager.facebookUseLinkPreview
                    put("facebook_use_link_preview", useLinkPreview)
                    
                    // Enviar preferencia de acortar contenido en Facebook
                    val shortenContent = SettingsManager.facebookShortenContent
                    put("facebook_shorten_content", shortenContent)

                    // Galería de imágenes
                    if (galleryIds.isNotEmpty()) {
                        put("gallery_ids", galleryIds)
                    }
                }

                // Crear trabajo en WordPress
                val authHeader = "Bearer $token"
                val response = WordPressApi.retrofitService.createJob(authHeader, payload.toMap())

                // Verificar respuesta
                if (response.success) {
                    uiState = uiState.copy(isLoading = false, postSuccess = true)
                } else {
                    throw Exception(response.message ?: "No se pudo crear el trabajo.")
                }
            } catch (e: HttpException) {
                val errorMessage = try {
                    // Intentar leer el mensaje del cuerpo de la respuesta
                    val errorBody = e.response()?.errorBody()?.string()
                    if (!errorBody.isNullOrBlank()) {
                        // Intentar parsear el JSON de error de WordPress
                        val jsonStart = errorBody.indexOf("\"message\"")
                        if (jsonStart != -1) {
                            val messageStart = errorBody.indexOf("\"", jsonStart + 10) + 1
                            val messageEnd = errorBody.indexOf("\"", messageStart)
                            if (messageEnd != -1) {
                                errorBody.substring(messageStart, messageEnd)
                            } else {
                                null
                            }
                        } else {
                            null
                        }
                    } else {
                        null
                    } ?: when (e.code()) {
                        400 -> "Datos inválidos. Verifica la información."
                        401 -> "No estás autenticado. Inicia sesión nuevamente."
                        403 -> "No tienes permiso para crear trabajos."
                        500, 502, 503 -> "Error del servidor. Inténtalo más tarde."
                        else -> "Error al crear el trabajo (${e.code()})"
                    }
                } catch (parseError: Exception) {
                    when (e.code()) {
                        400 -> "Datos inválidos. Verifica la información."
                        401 -> "No estás autenticado. Inicia sesión nuevamente."
                        403 -> "No tienes permiso para crear trabajos."
                        500, 502, 503 -> "Error del servidor. Inténtalo más tarde."
                        else -> "Error al crear el trabajo (${e.code()})"
                    }
                }
                uiState = uiState.copy(isLoading = false, error = errorMessage)
            } catch (e: com.squareup.moshi.JsonEncodingException) {
                // Error específico de JSON malformado
                Log.e("CreateJobViewModel", "Error de JSON malformado: ${e.message}", e)
                uiState = uiState.copy(
                    isLoading = false, 
                    error = "Error al procesar la respuesta del servidor. Por favor, intenta nuevamente."
                )
            } catch (e: Exception) {
                Log.e("CreateJobViewModel", "Error inesperado: ${e.message}", e)
                uiState = uiState.copy(isLoading = false, error = e.message ?: "Ocurrió un error inesperado.")
            }
        }
    }
}