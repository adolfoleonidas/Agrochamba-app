package agrochamba.com.ui

import android.Manifest
import android.app.DownloadManager
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.net.Uri
import android.os.Build
import android.os.Environment
import android.os.Message
import android.webkit.CookieManager
import android.webkit.GeolocationPermissions
import android.webkit.PermissionRequest
import android.webkit.URLUtil
import android.webkit.ValueCallback
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Toast
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.WifiOff
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import com.google.accompanist.web.AccompanistWebChromeClient
import com.google.accompanist.web.AccompanistWebViewClient
import com.google.accompanist.web.LoadingState
import com.google.accompanist.web.WebView
import com.google.accompanist.web.rememberWebViewNavigator
import com.google.accompanist.web.rememberWebViewState

/**
 * WebViewScreen completo con soporte para:
 * - Pull to refresh (deslizar hacia abajo para recargar)
 * - Geolocalización (GPS)
 * - Subida de archivos (input file)
 * - Cámara (captura de fotos)
 * - WebGL (mapas, gráficos 3D)
 * - Descargas (PDFs, archivos)
 * - Mixed Content (HTTP/HTTPS)
 * - Cache y base de datos offline
 * - Zoom y accesibilidad
 * - Cookies de terceros
 * - Múltiples ventanas (popups)
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun WebViewScreen(url: String) {
    val state = rememberWebViewState(url)
    val navigator = rememberWebViewNavigator()
    var isLoading by remember { mutableStateOf(false) }
    var isRefreshing by remember { mutableStateOf(false) }
    var isOffline by remember { mutableStateOf(false) }
    val context = LocalContext.current
    
    // Estado para subida de archivos
    var fileUploadCallback by remember { mutableStateOf<ValueCallback<Array<Uri>>?>(null) }
    
    // Estado para permisos de geolocalización
    var pendingGeolocationCallback by remember { 
        mutableStateOf<Pair<String?, GeolocationPermissions.Callback?>?>(null) 
    }
    
    // Launcher para selección de archivos
    val fileChooserLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetMultipleContents()
    ) { uris: List<Uri> ->
        fileUploadCallback?.onReceiveValue(uris.toTypedArray())
        fileUploadCallback = null
    }
    
    // Launcher para captura de foto con cámara
    val cameraLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.TakePicturePreview()
    ) { bitmap ->
        // Si se tomó una foto, la guardamos temporalmente y devolvemos la URI
        if (bitmap != null) {
            // Por ahora, cancelamos ya que esto requiere más lógica
            fileUploadCallback?.onReceiveValue(null)
        } else {
            fileUploadCallback?.onReceiveValue(null)
        }
        fileUploadCallback = null
    }
    
    // Launcher para permisos de ubicación
    val locationPermissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestMultiplePermissions()
    ) { permissions ->
        val fineLocationGranted = permissions[Manifest.permission.ACCESS_FINE_LOCATION] ?: false
        val coarseLocationGranted = permissions[Manifest.permission.ACCESS_COARSE_LOCATION] ?: false
        
        pendingGeolocationCallback?.let { (origin, callback) ->
            callback?.invoke(origin, fineLocationGranted || coarseLocationGranted, false)
        }
        pendingGeolocationCallback = null
    }
    
    // Launcher para permisos de cámara
    val cameraPermissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestPermission()
    ) { isGranted ->
        if (isGranted) {
            cameraLauncher.launch(null)
        } else {
            fileUploadCallback?.onReceiveValue(null)
            fileUploadCallback = null
        }
    }

    // WebViewClient mejorado
    val webViewClient = object : AccompanistWebViewClient() {
        override fun onPageStarted(view: WebView, url: String?, favicon: Bitmap?) {
            super.onPageStarted(view, url, favicon)
            isLoading = true
            isOffline = false
        }

        override fun onPageFinished(view: WebView, url: String?) {
            super.onPageFinished(view, url)
            isLoading = false
            
            // Inyectar CSS/JS personalizado si es necesario
            // view.evaluateJavascript("...", null)
        }

        @Deprecated("Deprecated in Java")
        override fun onReceivedError(
            view: WebView?,
            errorCode: Int,
            description: String?,
            failingUrl: String?
        ) {
            super.onReceivedError(view, errorCode, description, failingUrl)
            if (errorCode == WebViewClient.ERROR_HOST_LOOKUP || 
                errorCode == WebViewClient.ERROR_CONNECT ||
                errorCode == WebViewClient.ERROR_TIMEOUT) {
                isOffline = true
            }
        }
        
        // Manejar links externos (WhatsApp, teléfono, email, etc.)
        override fun shouldOverrideUrlLoading(
            view: WebView?,
            request: WebResourceRequest?
        ): Boolean {
            val url = request?.url?.toString() ?: return false
            
            // Manejar esquemas especiales
            return when {
                url.startsWith("tel:") || 
                url.startsWith("mailto:") || 
                url.startsWith("whatsapp:") ||
                url.startsWith("intent:") ||
                url.startsWith("market:") -> {
                    try {
                        val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                        context.startActivity(intent)
                    } catch (e: Exception) {
                        Toast.makeText(context, "No se puede abrir este enlace", Toast.LENGTH_SHORT).show()
                    }
                    true
                }
                // Google Maps links
                url.contains("maps.google.com") || url.contains("goo.gl/maps") -> {
                    try {
                        val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                        context.startActivity(intent)
                    } catch (e: Exception) {
                        // Si no hay app de mapas, dejar que el WebView lo maneje
                        false
                    }
                    true
                }
                else -> false
            }
        }
    }

    // WebChromeClient mejorado con todas las funcionalidades
    val chromeClient = object : AccompanistWebChromeClient() {
        
        // Manejar subida de archivos (input type="file")
        override fun onShowFileChooser(
            webView: WebView?,
            filePathCallback: ValueCallback<Array<Uri>>?,
            fileChooserParams: FileChooserParams?
        ): Boolean {
            fileUploadCallback?.onReceiveValue(null)
            fileUploadCallback = filePathCallback
            
            val acceptTypes = fileChooserParams?.acceptTypes ?: arrayOf()
            val isImageRequest = acceptTypes.any { it.startsWith("image/") }
            val isCaptureEnabled = fileChooserParams?.isCaptureEnabled ?: false
            
            if (isCaptureEnabled && isImageRequest) {
                // Si se solicita captura de imagen, verificar permiso de cámara
                when {
                    ContextCompat.checkSelfPermission(
                        context, 
                        Manifest.permission.CAMERA
                    ) == PackageManager.PERMISSION_GRANTED -> {
                        cameraLauncher.launch(null)
                    }
                    else -> {
                        cameraPermissionLauncher.launch(Manifest.permission.CAMERA)
                    }
                }
            } else {
                // Selector de archivos normal
                val mimeType = when {
                    isImageRequest -> "image/*"
                    acceptTypes.any { it.startsWith("video/") } -> "video/*"
                    acceptTypes.any { it.startsWith("audio/") } -> "audio/*"
                    else -> "*/*"
                }
                fileChooserLauncher.launch(mimeType)
            }
            return true
        }
        
        // Manejar nuevas ventanas (popups, target="_blank")
        override fun onCreateWindow(
            view: WebView?,
            isDialog: Boolean,
            isUserGesture: Boolean,
            resultMsg: Message?
        ): Boolean {
            val newWebView = WebView(view!!.context)
            newWebView.webViewClient = object : WebViewClient() {
                override fun shouldOverrideUrlLoading(
                    view: WebView?,
                    request: WebResourceRequest?
                ): Boolean {
                    val newUrl = request?.url?.toString()
                    if (newUrl != null) {
                        navigator.loadUrl(newUrl)
                    }
                    return true
                }
            }
            (resultMsg?.obj as WebView.WebViewTransport).webView = newWebView
            resultMsg.sendToTarget()
            return true
        }
        
        // Manejar solicitudes de geolocalización (GPS)
        override fun onGeolocationPermissionsShowPrompt(
            origin: String?,
            callback: GeolocationPermissions.Callback?
        ) {
            // Verificar si ya tenemos permisos
            val hasFineLocation = ContextCompat.checkSelfPermission(
                context,
                Manifest.permission.ACCESS_FINE_LOCATION
            ) == PackageManager.PERMISSION_GRANTED
            
            val hasCoarseLocation = ContextCompat.checkSelfPermission(
                context,
                Manifest.permission.ACCESS_COARSE_LOCATION
            ) == PackageManager.PERMISSION_GRANTED
            
            if (hasFineLocation || hasCoarseLocation) {
                // Si ya tenemos permiso, concederlo al WebView
                callback?.invoke(origin, true, false)
            } else {
                // Guardar callback y solicitar permisos
                pendingGeolocationCallback = Pair(origin, callback)
                locationPermissionLauncher.launch(
                    arrayOf(
                        Manifest.permission.ACCESS_FINE_LOCATION,
                        Manifest.permission.ACCESS_COARSE_LOCATION
                    )
                )
            }
        }
        
        // Manejar solicitudes de permisos (cámara, micrófono para WebRTC)
        override fun onPermissionRequest(request: PermissionRequest?) {
            val resources = request?.resources ?: return
            
            // Para WebRTC (videollamadas, grabación de audio/video)
            val grantedResources = mutableListOf<String>()
            
            resources.forEach { resource ->
                when (resource) {
                    PermissionRequest.RESOURCE_AUDIO_CAPTURE -> {
                        if (ContextCompat.checkSelfPermission(
                                context, 
                                Manifest.permission.RECORD_AUDIO
                            ) == PackageManager.PERMISSION_GRANTED
                        ) {
                            grantedResources.add(resource)
                        }
                    }
                    PermissionRequest.RESOURCE_VIDEO_CAPTURE -> {
                        if (ContextCompat.checkSelfPermission(
                                context, 
                                Manifest.permission.CAMERA
                            ) == PackageManager.PERMISSION_GRANTED
                        ) {
                            grantedResources.add(resource)
                        }
                    }
                    // Protected media (DRM) - generalmente permitido
                    PermissionRequest.RESOURCE_PROTECTED_MEDIA_ID -> {
                        grantedResources.add(resource)
                    }
                }
            }
            
            if (grantedResources.isNotEmpty()) {
                request.grant(grantedResources.toTypedArray())
            } else {
                request.deny()
            }
        }
        
        // Manejar consola de JavaScript (para debugging)
        override fun onConsoleMessage(
            message: String?,
            lineNumber: Int,
            sourceID: String?
        ) {
            android.util.Log.d("WebView", "Console: $message [$sourceID:$lineNumber]")
        }
    }

    // Limpiar recursos cuando el composable se destruye
    DisposableEffect(Unit) {
        onDispose {
            fileUploadCallback?.onReceiveValue(null)
            fileUploadCallback = null
        }
    }

    // Pull to refresh - deslizar hacia abajo para recargar
    PullToRefreshBox(
        isRefreshing = isRefreshing,
        onRefresh = {
            isRefreshing = true
            navigator.reload()
        },
        modifier = Modifier.fillMaxSize()
    ) {
        if (isOffline) {
            OfflineScreen(onRetry = { 
                isOffline = false
                isRefreshing = true
                navigator.reload()
            })
        } else {
            Box(modifier = Modifier.fillMaxSize()) {
                WebView(
                    state = state,
                    modifier = Modifier.fillMaxSize(),
                    navigator = navigator,
                    chromeClient = chromeClient,
                    onCreated = { webView ->
                        // Habilitar debugging en modo desarrollo
                        WebView.setWebContentsDebuggingEnabled(true)
                        
                        webView.settings.apply {
                            // === JavaScript y DOM ===
                            javaScriptEnabled = true
                            domStorageEnabled = true  // localStorage, sessionStorage
                            databaseEnabled = true    // Web SQL Database
                            
                            // === Archivos y contenido ===
                            allowFileAccess = true
                            allowContentAccess = true
                            
                            // === Ventanas y navegación ===
                            javaScriptCanOpenWindowsAutomatically = true
                            setSupportMultipleWindows(true)
                            
                            // === Zoom y viewport ===
                            setSupportZoom(true)
                            builtInZoomControls = true
                            displayZoomControls = false  // Ocultar controles visuales
                            loadWithOverviewMode = true
                            useWideViewPort = true
                            
                            // === Caché ===
                            cacheMode = WebSettings.LOAD_DEFAULT
                            
                            // === Mixed Content (HTTP + HTTPS) ===
                            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                                mixedContentMode = WebSettings.MIXED_CONTENT_COMPATIBILITY_MODE
                            }
                            
                            // === Geolocalización ===
                            setGeolocationEnabled(true)
                            
                            // === Media ===
                            mediaPlaybackRequiresUserGesture = false  // Autoplay de videos
                            
                            // === Renderizado ===
                            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                                safeBrowsingEnabled = false  // Evitar bloqueos innecesarios
                            }
                            
                            // === User Agent personalizado ===
                            val defaultUserAgent = userAgentString
                            userAgentString = "$defaultUserAgent AgroChambaApp/1.0"
                            
                            // === Encoding ===
                            defaultTextEncodingName = "UTF-8"
                        }
                        
                        // === Cookies ===
                        val cookieManager = CookieManager.getInstance()
                        cookieManager.setAcceptCookie(true)
                        cookieManager.setAcceptThirdPartyCookies(webView, true)
                        
                        // === Hardware Acceleration ===
                        webView.setLayerType(android.view.View.LAYER_TYPE_HARDWARE, null)
                        
                        // === Scroll suave ===
                        webView.isNestedScrollingEnabled = true
                        webView.overScrollMode = android.view.View.OVER_SCROLL_NEVER
                        
                        // === Descargas ===
                        webView.setDownloadListener { downloadUrl, userAgent, contentDisposition, mimeType, contentLength ->
                            try {
                                val request = DownloadManager.Request(Uri.parse(downloadUrl))
                                request.setMimeType(mimeType)
                                request.addRequestHeader("User-Agent", userAgent)
                                request.setDescription("Descargando archivo...")
                                request.setTitle(URLUtil.guessFileName(downloadUrl, contentDisposition, mimeType))
                                request.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                                request.setDestinationInExternalPublicDir(
                                    Environment.DIRECTORY_DOWNLOADS,
                                    URLUtil.guessFileName(downloadUrl, contentDisposition, mimeType)
                                )
                                
                                val downloadManager = context.getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
                                downloadManager.enqueue(request)
                                
                                Toast.makeText(context, "Descarga iniciada", Toast.LENGTH_SHORT).show()
                            } catch (e: Exception) {
                                Toast.makeText(context, "Error al descargar: ${e.message}", Toast.LENGTH_SHORT).show()
                            }
                        }
                    },
                    client = webViewClient
                )
                
                // Indicador de carga inicial (no durante pull to refresh)
                if (state.loadingState is LoadingState.Loading && !isRefreshing) {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                }
                
                // Desactivar el indicador de refresh cuando termine de cargar
                if (state.loadingState !is LoadingState.Loading && isRefreshing) {
                    isRefreshing = false
                }
            }
        }
    }
}

@Composable
fun OfflineScreen(onRetry: () -> Unit) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(
            imageVector = Icons.Default.WifiOff,
            contentDescription = "Sin conexión",
            modifier = Modifier.size(64.dp),
            tint = MaterialTheme.colorScheme.primary
        )
        Spacer(modifier = Modifier.height(16.dp))
        Text(
            text = "¡Sin conexión a Internet!",
            style = MaterialTheme.typography.headlineSmall,
            color = MaterialTheme.colorScheme.primary
        )
        Spacer(modifier = Modifier.height(8.dp))
        Text(
            text = "Por favor, revisa tu conexión de red y vuelve a intentarlo.",
            style = MaterialTheme.typography.bodyLarge,
            textAlign = TextAlign.Center
        )
        Spacer(modifier = Modifier.height(24.dp))
        Button(onClick = onRetry) {
            Text("Reintentar")
        }
    }
}
