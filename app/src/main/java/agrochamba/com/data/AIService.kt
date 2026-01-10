package agrochamba.com.data

import android.net.Uri
import android.util.Log
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue

/**
 * Servicio para interactuar con los endpoints de IA
 * 
 * Proporciona funcionalidades para:
 * - Mejorar texto de ofertas laborales
 * - Generar títulos optimizados para SEO
 * - Extraer texto de imágenes (OCR)
 * 
 * Sistema de límites:
 * - Empresas: 3 usos gratuitos
 * - Administradores: Sin límites
 */
object AIService {
    private const val TAG = "AIService"

    // Estado de uso de IA (observable)
    var usageStatus by mutableStateOf<UsageStatus?>(null)
        private set

    /**
     * Estado de uso de IA
     */
    data class UsageStatus(
        val allowed: Boolean,
        val remaining: Int,  // -1 = ilimitado
        val used: Int,
        val limit: Int,      // -1 = ilimitado
        val isPremium: Boolean,
        val message: String?
    ) {
        val isUnlimited: Boolean get() = remaining == -1 || isPremium
        val hasUsesLeft: Boolean get() = isUnlimited || remaining > 0
    }

    /**
     * Resultado de una operación de IA
     */
    sealed class AIResult<T> {
        data class Success<T>(val data: T, val usage: UsageStatus? = null) : AIResult<T>()
        data class Error<T>(val message: String, val code: String? = null, val isLimitReached: Boolean = false) : AIResult<T>()
        class Loading<T> : AIResult<T>()
    }

    /**
     * Obtener estado de uso de IA del usuario actual
     */
    suspend fun getUsageStatus(): AIResult<UsageStatus> {
        val token = AuthManager.token
        if (token.isNullOrEmpty()) {
            return AIResult.Error("Debes iniciar sesión para usar esta función")
        }

        return try {
            val response = WordPressApi.retrofitService.getAIUsageStatus(
                token = "Bearer $token"
            )

            if (response.success) {
                val status = UsageStatus(
                    allowed = response.allowed,
                    remaining = response.remaining,
                    used = response.used,
                    limit = response.limit,
                    isPremium = response.isPremium,
                    message = response.message
                )
                usageStatus = status
                Log.d(TAG, "Estado de uso: remaining=${status.remaining}, isPremium=${status.isPremium}")
                AIResult.Success(status)
            } else {
                AIResult.Error(response.message ?: "Error al obtener estado de uso")
            }
        } catch (e: Exception) {
            Log.e(TAG, "Error al obtener estado de uso", e)
            AIResult.Error("Error de conexión")
        }
    }

    /**
     * Actualizar estado de uso desde respuesta de IA
     */
    private fun updateUsageFromResponse(usage: AIUsageInfo?) {
        usage?.let {
            usageStatus = UsageStatus(
                allowed = it.remaining > 0 || it.isPremium,
                remaining = it.remaining,
                used = it.used,
                limit = it.limit,
                isPremium = it.isPremium,
                message = null
            )
        }
    }

    /**
     * Mejora el texto de una oferta laboral usando IA
     * 
     * @param text Texto a mejorar
     * @param type Tipo de contenido: "job" o "blog"
     * @return Resultado con el texto mejorado
     */
    suspend fun enhanceText(text: String, type: String = "job"): AIResult<String> {
        val token = AuthManager.token
        if (token.isNullOrEmpty()) {
            return AIResult.Error("Debes iniciar sesión para usar esta función")
        }

        return try {
            val response = WordPressApi.retrofitService.enhanceText(
                token = "Bearer $token",
                data = mapOf(
                    "text" to text,
                    "type" to type
                )
            )

            if (response.success && !response.enhancedText.isNullOrEmpty()) {
                Log.d(TAG, "Texto mejorado exitosamente. Tokens usados: ${response.tokensUsed}")
                // Actualizar estado de uso
                updateUsageFromResponse(response.usage)
                AIResult.Success(response.enhancedText, usageStatus)
            } else {
                Log.w(TAG, "Error al mejorar texto: ${response.message}")
                AIResult.Error(response.message ?: "No se pudo mejorar el texto")
            }
        } catch (e: retrofit2.HttpException) {
            val errorBody = try { e.response()?.errorBody()?.string() } catch (_: Exception) { null }
            val isLimitReached = e.code() == 403 && (errorBody?.contains("limit_reached") == true)
            
            val errorMessage = when {
                isLimitReached -> "Has alcanzado el límite de 3 usos gratuitos. Actualiza a premium para uso ilimitado."
                e.code() == 401 -> "Sesión expirada. Inicia sesión nuevamente."
                e.code() == 503 -> "El servicio de IA no está disponible en este momento."
                else -> "Error al mejorar el texto (${e.code()})"
            }
            Log.e(TAG, "Error HTTP al mejorar texto: ${e.code()}", e)
            
            if (isLimitReached) {
                // Actualizar estado local
                usageStatus = usageStatus?.copy(allowed = false, remaining = 0)
            }
            
            AIResult.Error(errorMessage, if (isLimitReached) "limit_reached" else null, isLimitReached)
        } catch (e: Exception) {
            Log.e(TAG, "Error al mejorar texto", e)
            AIResult.Error("Error de conexión. Verifica tu internet.")
        }
    }

    /**
     * Genera un título optimizado para SEO basado en la descripción
     * 
     * @param description Descripción del trabajo
     * @param location Ubicación del trabajo (opcional)
     * @return Resultado con el título generado
     */
    suspend fun generateTitle(description: String, location: String? = null): AIResult<GeneratedTitle> {
        val token = AuthManager.token
        if (token.isNullOrEmpty()) {
            return AIResult.Error("Debes iniciar sesión para usar esta función")
        }

        return try {
            val data = mutableMapOf("description" to description)
            location?.let { data["location"] = it }

            val response = WordPressApi.retrofitService.generateTitle(
                token = "Bearer $token",
                data = data
            )

            if (response.success && !response.title.isNullOrEmpty()) {
                Log.d(TAG, "Título generado: ${response.title} (${response.characterCount} chars, SEO óptimo: ${response.seoOptimal})")
                // Actualizar estado de uso
                updateUsageFromResponse(response.usage)
                AIResult.Success(
                    GeneratedTitle(
                        title = response.title,
                        characterCount = response.characterCount ?: response.title.length,
                        isSeoOptimal = response.seoOptimal ?: false
                    ),
                    usageStatus
                )
            } else {
                Log.w(TAG, "Error al generar título: ${response.message}")
                AIResult.Error(response.message ?: "No se pudo generar el título")
            }
        } catch (e: retrofit2.HttpException) {
            val errorBody = try { e.response()?.errorBody()?.string() } catch (_: Exception) { null }
            val isLimitReached = e.code() == 403 && (errorBody?.contains("limit_reached") == true)
            
            val errorMessage = when {
                isLimitReached -> "Has alcanzado el límite de 3 usos gratuitos. Actualiza a premium para uso ilimitado."
                e.code() == 401 -> "Sesión expirada. Inicia sesión nuevamente."
                e.code() == 503 -> "El servicio de IA no está disponible en este momento."
                else -> "Error al generar el título (${e.code()})"
            }
            Log.e(TAG, "Error HTTP al generar título: ${e.code()}", e)
            
            if (isLimitReached) {
                usageStatus = usageStatus?.copy(allowed = false, remaining = 0)
            }
            
            AIResult.Error(errorMessage, if (isLimitReached) "limit_reached" else null, isLimitReached)
        } catch (e: Exception) {
            Log.e(TAG, "Error al generar título", e)
            AIResult.Error("Error de conexión. Verifica tu internet.")
        }
    }

    /**
     * Extrae texto de una imagen (OCR) y opcionalmente lo mejora
     * 
     * @param imageUrl URL de la imagen a procesar
     * @param imageId ID del media en WordPress (alternativo a imageUrl)
     * @param enhance Si debe mejorar el texto extraído
     * @return Resultado con el texto extraído y/o mejorado
     */
    suspend fun extractTextFromImage(
        imageUrl: String? = null,
        imageId: Int? = null,
        enhance: Boolean = true
    ): AIResult<OCRResult> {
        val token = AuthManager.token
        if (token.isNullOrEmpty()) {
            return AIResult.Error("Debes iniciar sesión para usar esta función")
        }

        if (imageUrl.isNullOrEmpty() && imageId == null) {
            return AIResult.Error("Debes proporcionar una imagen")
        }

        return try {
            val data = mutableMapOf<String, Any>(
                "enhance" to enhance
            )
            imageUrl?.let { data["image_url"] = it }
            imageId?.let { data["image_id"] = it }

            val response = WordPressApi.retrofitService.extractTextFromImage(
                token = "Bearer $token",
                data = data
            )

            if (response.success) {
                val extractedText = response.extractedText ?: ""
                val enhancedText = response.enhancedText
                
                // Actualizar estado de uso
                updateUsageFromResponse(response.usage)

                if (extractedText.isEmpty() && enhancedText.isNullOrEmpty()) {
                    Log.w(TAG, "No se encontró texto en la imagen")
                    AIResult.Error("No se encontró texto legible en la imagen")
                } else {
                    Log.d(TAG, "OCR exitoso. Texto extraído: ${extractedText.take(50)}...")
                    AIResult.Success(
                        OCRResult(
                            extractedText = extractedText,
                            enhancedText = enhancedText
                        ),
                        usageStatus
                    )
                }
            } else {
                Log.w(TAG, "Error OCR: ${response.message}")
                AIResult.Error(response.message ?: "No se pudo procesar la imagen")
            }
        } catch (e: retrofit2.HttpException) {
            val errorBody = try { e.response()?.errorBody()?.string() } catch (_: Exception) { null }
            val isLimitReached = e.code() == 403 && (errorBody?.contains("limit_reached") == true)
            
            val errorMessage = when {
                isLimitReached -> "Has alcanzado el límite de 3 usos gratuitos. Actualiza a premium para uso ilimitado."
                e.code() == 401 -> "Sesión expirada. Inicia sesión nuevamente."
                e.code() == 503 -> "El servicio de IA no está disponible en este momento."
                else -> "Error al procesar la imagen (${e.code()})"
            }
            Log.e(TAG, "Error HTTP en OCR: ${e.code()}", e)
            
            if (isLimitReached) {
                usageStatus = usageStatus?.copy(allowed = false, remaining = 0)
            }
            
            AIResult.Error(errorMessage, if (isLimitReached) "limit_reached" else null, isLimitReached)
        } catch (e: Exception) {
            Log.e(TAG, "Error en OCR", e)
            AIResult.Error("Error de conexión. Verifica tu internet.")
        }
    }

    /**
     * Datos de un título generado por IA
     */
    data class GeneratedTitle(
        val title: String,
        val characterCount: Int,
        val isSeoOptimal: Boolean
    )

    /**
     * Resultado de OCR
     */
    data class OCRResult(
        val extractedText: String,
        val enhancedText: String?
    )
}

