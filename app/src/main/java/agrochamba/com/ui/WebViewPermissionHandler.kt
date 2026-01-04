package agrochamba.com.ui

import android.Manifest
import android.content.pm.PackageManager
import android.webkit.GeolocationPermissions
import android.webkit.PermissionRequest
import android.webkit.WebChromeClient
import android.webkit.WebView
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.platform.LocalContext
import androidx.core.content.ContextCompat

/**
 * Maneja permisos solicitados por WebViews
 * 
 * Esta clase gestiona las solicitudes de permisos que los sitios web
 * dentro de las WebViews pueden necesitar, como:
 * - Geolocalización (GPS)
 * - Cámara
 * - Micrófono
 * - Almacenamiento
 */
class WebViewPermissionHandler(
    private val onPermissionResult: (String, Boolean) -> Unit
) {
    /**
     * Maneja solicitudes de geolocalización desde WebView
     */
    fun handleGeolocationRequest(
        origin: String?,
        callback: GeolocationPermissions.Callback?
    ) {
        // Por ahora, denegar geolocalización por defecto
        // Se puede implementar solicitud de permiso aquí si es necesario
        callback?.invoke(origin, false, false)
    }

    /**
     * Maneja solicitudes de permisos generales desde WebView
     */
    fun handlePermissionRequest(request: PermissionRequest?) {
        // Por ahora, denegar todos los permisos por defecto
        // Se puede implementar solicitud de permiso aquí si es necesario
        request?.deny()
    }
}

/**
 * Crea un WebChromeClient que maneja permisos de WebView
 */
@Composable
fun rememberWebViewChromeClient(
    onPermissionRequested: (String) -> Unit = {}
): WebChromeClient {
    val context = LocalContext.current
    
    return remember {
        object : WebChromeClient() {
            override fun onGeolocationPermissionsShowPrompt(
                origin: String?,
                callback: GeolocationPermissions.Callback?
            ) {
                // Verificar si tenemos permiso de ubicación
                val hasLocationPermission = ContextCompat.checkSelfPermission(
                    context,
                    Manifest.permission.ACCESS_FINE_LOCATION
                ) == PackageManager.PERMISSION_GRANTED ||
                ContextCompat.checkSelfPermission(
                    context,
                    Manifest.permission.ACCESS_COARSE_LOCATION
                ) == PackageManager.PERMISSION_GRANTED

                if (hasLocationPermission) {
                    // Si tenemos permiso, otorgarlo
                    callback?.invoke(origin, true, false)
                } else {
                    // Si no tenemos permiso, denegar y notificar que se necesita
                    callback?.invoke(origin, false, false)
                    onPermissionRequested("Ubicación")
                }
            }

            override fun onPermissionRequest(request: PermissionRequest?) {
                // Manejar solicitudes de permisos (cámara, micrófono, etc.)
                val resources = request?.resources ?: emptyArray()
                
                // Verificar qué permisos se solicitan
                val needsCamera = resources.contains(PermissionRequest.RESOURCE_VIDEO_CAPTURE) ||
                                 resources.contains(PermissionRequest.RESOURCE_AUDIO_CAPTURE)
                val needsMicrophone = resources.contains(PermissionRequest.RESOURCE_AUDIO_CAPTURE)
                
                // Por ahora, denegar todos los permisos
                // Se puede implementar solicitud de permiso aquí si es necesario
                request?.deny()
                
                if (needsCamera) {
                    onPermissionRequested("Cámara")
                }
                if (needsMicrophone) {
                    onPermissionRequested("Micrófono")
                }
            }
        }
    }
}

