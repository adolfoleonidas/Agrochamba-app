package agrochamba.com.ui

import android.content.Intent
import android.graphics.Bitmap
import android.os.Message
import android.webkit.CookieManager
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Share
import androidx.compose.material.icons.filled.WifiOff
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import com.google.accompanist.web.AccompanistWebChromeClient
import com.google.accompanist.web.AccompanistWebViewClient
import com.google.accompanist.web.LoadingState
import com.google.accompanist.web.WebView
import com.google.accompanist.web.rememberWebViewNavigator
import com.google.accompanist.web.rememberWebViewState

@Composable
fun WebViewScreen(url: String, showButtons: Boolean = true) { // Nuevo parámetro
    val state = rememberWebViewState(url)
    val navigator = rememberWebViewNavigator()
    var isLoading by remember { mutableStateOf(false) }
    var isOffline by remember { mutableStateOf(false) }
    val context = LocalContext.current

    val webViewClient = object : AccompanistWebViewClient() {
        override fun onPageStarted(view: WebView, url: String?, favicon: Bitmap?) {
            super.onPageStarted(view, url, favicon)
            isLoading = true
            isOffline = false
        }

        override fun onPageFinished(view: WebView, url: String?) {
            super.onPageFinished(view, url)
            isLoading = false
        }

        override fun onReceivedError(
            view: WebView?,
            errorCode: Int,
            description: String?,
            failingUrl: String?
        ) {
            super.onReceivedError(view, errorCode, description, failingUrl)
            if (errorCode == WebViewClient.ERROR_HOST_LOOKUP || errorCode == WebViewClient.ERROR_CONNECT) {
                isOffline = true
            }
        }
    }

    val chromeClient = object : AccompanistWebChromeClient() {
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
    }

    Scaffold(
        floatingActionButton = {
            if (showButtons && !isLoading) { // Condición para mostrar los botones
                Column(horizontalAlignment = Alignment.End) {
                    FloatingActionButton(
                        onClick = {
                            val currentUrl = state.lastLoadedUrl ?: url
                            val sendIntent = Intent().apply {
                                action = Intent.ACTION_SEND
                                putExtra(Intent.EXTRA_TEXT, currentUrl)
                                type = "text/plain"
                            }
                            try {
                                sendIntent.setPackage("com.whatsapp")
                                context.startActivity(sendIntent)
                            } catch (e: Exception) {
                                val shareIntent = Intent.createChooser(sendIntent, "Compartir URL")
                                context.startActivity(shareIntent)
                            }
                        }
                    ) {
                        Icon(Icons.Default.Share, contentDescription = "Compartir")
                    }
                    Spacer(modifier = Modifier.height(16.dp))
                    FloatingActionButton(onClick = { navigator.reload() }) {
                        Icon(Icons.Default.Refresh, contentDescription = "Recargar")
                    }
                }
            }
        }
    ) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            if (isOffline) {
                OfflineScreen(onRetry = { 
                    isOffline = false
                    navigator.reload()
                 })
            } else {
                WebView(
                    state = state,
                    modifier = Modifier.fillMaxSize(),
                    navigator = navigator,
                    chromeClient = chromeClient,
                    onCreated = { webView ->
                        webView.settings.javaScriptEnabled = true
                        webView.settings.domStorageEnabled = true
                        webView.settings.javaScriptCanOpenWindowsAutomatically = true
                        webView.settings.setSupportMultipleWindows(true)

                        val cookieManager = CookieManager.getInstance()
                        cookieManager.setAcceptCookie(true)
                        cookieManager.setAcceptThirdPartyCookies(webView, true)
                    },
                    client = webViewClient
                )
                if (isLoading && state.loadingState !is LoadingState.Loading) {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                }
            }
        }
    }
}

@Composable
fun OfflineScreen(onRetry: () -> Unit) {
    Column(
        modifier = Modifier.fillMaxSize().padding(16.dp),
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