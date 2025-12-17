package agrochamba.com.data

import android.content.Context
import android.content.SharedPreferences
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

object SettingsManager {
    private const val PREFS_NAME = "app_settings"
    private const val KEY_FACEBOOK_USE_LINK_PREVIEW = "facebook_use_link_preview"
    private const val KEY_FACEBOOK_SHORTEN_CONTENT = "facebook_shorten_content"
    
    private var prefs: SharedPreferences? = null
    private val scope = CoroutineScope(Dispatchers.Main + SupervisorJob())
    
    // Por defecto: usar imágenes adjuntas (false = adjuntar imágenes, true = usar link preview)
    var facebookUseLinkPreview by mutableStateOf(false)
    
    // Por defecto: no acortar contenido (false = mostrar contenido completo, true = acortar y agregar link)
    var facebookShortenContent by mutableStateOf(false)
    
    fun init(context: Context) {
        prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        loadSettings()
    }
    
    /**
     * Carga la configuración desde SharedPreferences de forma síncrona.
     * Esto asegura que los valores estén disponibles inmediatamente después de init().
     */
    private fun loadSettings() {
        // Cargar de forma síncrona para que esté disponible inmediatamente
        val useLinkPreview = prefs?.getBoolean(KEY_FACEBOOK_USE_LINK_PREVIEW, false) ?: false
        val shortenContent = prefs?.getBoolean(KEY_FACEBOOK_SHORTEN_CONTENT, false) ?: false
        facebookUseLinkPreview = useLinkPreview
        facebookShortenContent = shortenContent
    }
    
    fun applyFacebookUseLinkPreview(useLinkPreview: Boolean) {
        facebookUseLinkPreview = useLinkPreview
        scope.launch(Dispatchers.IO) {
            prefs?.edit()?.apply {
                putBoolean(KEY_FACEBOOK_USE_LINK_PREVIEW, useLinkPreview)
                apply()
            }
        }
    }
    
    fun applyFacebookShortenContent(shortenContent: Boolean) {
        facebookShortenContent = shortenContent
        scope.launch(Dispatchers.IO) {
            prefs?.edit()?.apply {
                putBoolean(KEY_FACEBOOK_SHORTEN_CONTENT, shortenContent)
                apply()
            }
        }
    }
}

