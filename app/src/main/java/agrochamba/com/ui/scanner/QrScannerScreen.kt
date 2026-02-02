package agrochamba.com.ui.scanner

import android.Manifest
import android.content.Intent
import android.net.Uri
import android.util.Log
import android.widget.Toast
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.camera.core.CameraSelector
import androidx.camera.core.ImageAnalysis
import androidx.camera.core.ImageProxy
import androidx.camera.core.Preview
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.CornerRadius
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.BlendMode
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.CompositingStrategy
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalLifecycleOwner
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import androidx.navigation.NavController
import com.google.mlkit.vision.barcode.BarcodeScanning
import com.google.mlkit.vision.barcode.common.Barcode
import com.google.mlkit.vision.common.InputImage
import java.util.concurrent.Executors

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun QrScannerScreen(navController: NavController) {
    val context = LocalContext.current
    var hasCameraPermission by remember { mutableStateOf(false) }
    var scanned by remember { mutableStateOf(false) }

    val permissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestPermission()
    ) { granted ->
        hasCameraPermission = granted
        if (!granted) {
            Toast.makeText(context, "Se necesita permiso de camara para escanear", Toast.LENGTH_LONG).show()
        }
    }

    LaunchedEffect(Unit) {
        permissionLauncher.launch(Manifest.permission.CAMERA)
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Escanear QR") },
                navigationIcon = {
                    IconButton(onClick = { navController.popBackStack() }) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Volver")
                    }
                }
            )
        }
    ) { innerPadding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            if (hasCameraPermission) {
                CameraPreviewWithScanner(
                    onBarcodeDetected = { barcode ->
                        if (scanned) return@CameraPreviewWithScanner
                        scanned = true
                        val rawValue = barcode.rawValue ?: return@CameraPreviewWithScanner
                        Log.d("QrScanner", "Codigo detectado: $rawValue")

                        when {
                            rawValue.contains("agrochamba.com") -> {
                                // URL de agrochamba -> navegar in-app
                                Toast.makeText(context, "Enlace Agrochamba detectado", Toast.LENGTH_SHORT).show()
                                // Intentar extraer ruta del perfil de empresa
                                val uri = Uri.parse(rawValue)
                                val pathSegments = uri.pathSegments
                                if (pathSegments.contains("empresa") && pathSegments.size > 1) {
                                    val companyName = pathSegments.last()
                                    navController.popBackStack()
                                    navController.navigate("company_profile/$companyName")
                                } else {
                                    // Abrir en browser como fallback
                                    val intent = Intent(Intent.ACTION_VIEW, Uri.parse(rawValue))
                                    context.startActivity(intent)
                                    navController.popBackStack()
                                }
                            }
                            rawValue.startsWith("http://") || rawValue.startsWith("https://") -> {
                                // URL general -> abrir en browser
                                val intent = Intent(Intent.ACTION_VIEW, Uri.parse(rawValue))
                                context.startActivity(intent)
                                navController.popBackStack()
                            }
                            else -> {
                                // Texto plano -> mostrar Toast
                                Toast.makeText(context, rawValue, Toast.LENGTH_LONG).show()
                                scanned = false // Permitir re-escanear
                            }
                        }
                    }
                )

                // Overlay con recuadro de escaneo
                ScanOverlay()
            } else {
                Column(
                    modifier = Modifier.fillMaxSize(),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.Center
                ) {
                    Text(
                        text = "Se necesita permiso de camara",
                        style = MaterialTheme.typography.bodyLarge
                    )
                    Spacer(modifier = Modifier.height(16.dp))
                    Button(onClick = { permissionLauncher.launch(Manifest.permission.CAMERA) }) {
                        Text("Dar permiso")
                    }
                }
            }
        }
    }
}

@Composable
private fun CameraPreviewWithScanner(onBarcodeDetected: (Barcode) -> Unit) {
    val context = LocalContext.current
    val lifecycleOwner = LocalLifecycleOwner.current
    val cameraExecutor = remember { Executors.newSingleThreadExecutor() }

    DisposableEffect(Unit) {
        onDispose { cameraExecutor.shutdown() }
    }

    AndroidView(
        modifier = Modifier.fillMaxSize(),
        factory = { ctx ->
            val previewView = PreviewView(ctx)
            val cameraProviderFuture = ProcessCameraProvider.getInstance(ctx)

            cameraProviderFuture.addListener({
                val cameraProvider = cameraProviderFuture.get()

                val preview = Preview.Builder().build().also {
                    it.surfaceProvider = previewView.surfaceProvider
                }

                val barcodeScanner = BarcodeScanning.getClient()

                val imageAnalysis = ImageAnalysis.Builder()
                    .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST)
                    .build()
                    .also { analysis ->
                        analysis.setAnalyzer(cameraExecutor) { imageProxy ->
                            processImage(imageProxy, barcodeScanner, onBarcodeDetected)
                        }
                    }

                try {
                    cameraProvider.unbindAll()
                    cameraProvider.bindToLifecycle(
                        lifecycleOwner,
                        CameraSelector.DEFAULT_BACK_CAMERA,
                        preview,
                        imageAnalysis
                    )
                } catch (e: Exception) {
                    Log.e("QrScanner", "Error al iniciar camara", e)
                }
            }, ContextCompat.getMainExecutor(ctx))

            previewView
        }
    )
}

@androidx.annotation.OptIn(androidx.camera.core.ExperimentalGetImage::class)
private fun processImage(
    imageProxy: ImageProxy,
    scanner: com.google.mlkit.vision.barcode.BarcodeScanner,
    onDetected: (Barcode) -> Unit
) {
    val mediaImage = imageProxy.image
    if (mediaImage != null) {
        val inputImage = InputImage.fromMediaImage(mediaImage, imageProxy.imageInfo.rotationDegrees)
        scanner.process(inputImage)
            .addOnSuccessListener { barcodes ->
                barcodes.firstOrNull()?.let { barcode ->
                    onDetected(barcode)
                }
            }
            .addOnFailureListener { e ->
                Log.e("QrScanner", "Error al escanear", e)
            }
            .addOnCompleteListener {
                imageProxy.close()
            }
    } else {
        imageProxy.close()
    }
}

@Composable
private fun ScanOverlay() {
    val primaryColor = MaterialTheme.colorScheme.primary
    Canvas(
        modifier = Modifier
            .fillMaxSize()
            .graphicsLayer { compositingStrategy = CompositingStrategy.Offscreen }
    ) {
        val scanSize = size.minDimension * 0.65f
        val left = (size.width - scanSize) / 2
        val top = (size.height - scanSize) / 2

        // Fondo semi-transparente
        drawRect(
            color = Color.Black.copy(alpha = 0.5f),
            size = size
        )

        // Recortar el area de escaneo (transparente)
        drawRoundRect(
            color = Color.Transparent,
            topLeft = Offset(left, top),
            size = Size(scanSize, scanSize),
            cornerRadius = CornerRadius(16.dp.toPx()),
            blendMode = BlendMode.Clear
        )

        // Borde del area de escaneo
        drawRoundRect(
            color = primaryColor,
            topLeft = Offset(left, top),
            size = Size(scanSize, scanSize),
            cornerRadius = CornerRadius(16.dp.toPx()),
            style = Stroke(width = 3.dp.toPx())
        )
    }
}
