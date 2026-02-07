package agrochamba.com.ui.discounts

import android.Manifest
import android.net.Uri
import android.util.Log
import android.widget.Toast
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.camera.core.Camera
import androidx.camera.core.CameraSelector
import androidx.camera.core.ImageAnalysis
import androidx.camera.core.ImageProxy
import androidx.camera.core.Preview
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Error
import androidx.compose.material.icons.filled.FlashlightOff
import androidx.compose.material.icons.filled.FlashlightOn
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.CornerRadius
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.BlendMode
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.CompositingStrategy
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalLifecycleOwner
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import androidx.lifecycle.viewmodel.compose.viewModel
import agrochamba.com.data.DiscountValidationResponse
import agrochamba.com.data.MerchantDiscount
import agrochamba.com.data.RedemptionData
import coil.compose.AsyncImage
import coil.request.ImageRequest
import com.google.mlkit.vision.barcode.BarcodeScanning
import com.google.mlkit.vision.barcode.common.Barcode
import com.google.mlkit.vision.common.InputImage
import java.util.concurrent.Executors

private val ValidateGreen = Color(0xFF16A34A)
private val ValidateGreenDark = Color(0xFF166534)

/**
 * Pantalla para que el comercio escanee el QR del usuario
 * y valide/canjee un descuento.
 *
 * Flujo:
 * 1. Comercio selecciona el descuento que quiere aplicar
 * 2. Escanea el QR del usuario (formato: agrochamba://discount/{dni})
 * 3. Se valida si el usuario puede canjear
 * 4. Si es valido, el comercio confirma el canje
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ValidateDiscountScreen(
    discount: MerchantDiscount,
    onNavigateBack: () -> Unit,
    viewModel: DiscountsViewModel = viewModel()
) {
    val context = LocalContext.current
    val uiState = viewModel.uiState
    var hasCameraPermission by remember { mutableStateOf(false) }
    var scanned by remember { mutableStateOf(false) }
    var scannedDni by remember { mutableStateOf<String?>(null) }
    var torchEnabled by remember { mutableStateOf(false) }
    var cameraRef by remember { mutableStateOf<Camera?>(null) }

    fun handleBarcodeResult(rawValue: String) {
        // Esperamos formato: agrochamba://discount/{dni}
        val uri = Uri.parse(rawValue)
        val extractedDni = when {
            uri.scheme == "agrochamba" && uri.host == "discount" -> {
                uri.pathSegments.firstOrNull()
            }
            // Tambien aceptar el DNI directo (desde fotocheck normal)
            rawValue.matches(Regex("^\\d{8,12}$")) -> rawValue
            else -> null
        }

        if (extractedDni != null) {
            scannedDni = extractedDni
            viewModel.validateUserForDiscount(discount.id, extractedDni)
        } else {
            Toast.makeText(context, "QR no valido. Pide al usuario que muestre su QR de Agrochamba.", Toast.LENGTH_LONG).show()
            scanned = false
        }
    }

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
                title = { Text("Validar Descuento", color = Color.White) },
                navigationIcon = {
                    IconButton(onClick = {
                        viewModel.clearValidation()
                        onNavigateBack()
                    }) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Volver", tint = Color.White)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = ValidateGreenDark
                )
            )
        }
    ) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            when {
                // Mostrar resultado de validacion
                uiState.validationResult != null -> {
                    ValidationResultView(
                        validation = uiState.validationResult,
                        discount = discount,
                        scannedDni = scannedDni ?: "",
                        isRedeeming = uiState.isRedeeming,
                        redemptionResult = uiState.redemptionResult,
                        redemptionError = uiState.redemptionError,
                        onConfirmRedeem = {
                            scannedDni?.let { dni ->
                                viewModel.redeemDiscount(discount.id, dni)
                            }
                        },
                        onScanAnother = {
                            viewModel.clearValidation()
                            scanned = false
                            scannedDni = null
                        },
                        onGoBack = {
                            viewModel.clearValidation()
                            onNavigateBack()
                        }
                    )
                }

                // Validando...
                uiState.isValidating -> {
                    Box(
                        modifier = Modifier.fillMaxSize(),
                        contentAlignment = Alignment.Center
                    ) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            CircularProgressIndicator(color = ValidateGreen)
                            Spacer(modifier = Modifier.height(16.dp))
                            Text(
                                "Validando usuario...",
                                style = MaterialTheme.typography.bodyLarge,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }

                // Error de validacion sin resultado
                uiState.redemptionError != null && uiState.validationResult == null -> {
                    Box(
                        modifier = Modifier.fillMaxSize(),
                        contentAlignment = Alignment.Center
                    ) {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            modifier = Modifier.padding(32.dp)
                        ) {
                            Icon(
                                Icons.Default.Error,
                                contentDescription = null,
                                modifier = Modifier.size(64.dp),
                                tint = MaterialTheme.colorScheme.error
                            )
                            Spacer(modifier = Modifier.height(16.dp))
                            Text(
                                text = uiState.redemptionError,
                                style = MaterialTheme.typography.bodyLarge,
                                textAlign = TextAlign.Center
                            )
                            Spacer(modifier = Modifier.height(16.dp))
                            Button(onClick = {
                                viewModel.clearValidation()
                                scanned = false
                                scannedDni = null
                            }) {
                                Text("Intentar de nuevo")
                            }
                        }
                    }
                }

                // Scanner de camara
                hasCameraPermission -> {
                    // Camera preview
                    ValidateCameraPreview(
                        onBarcodeDetected = { barcode ->
                            if (scanned) return@ValidateCameraPreview
                            scanned = true
                            val rawValue = barcode.rawValue ?: return@ValidateCameraPreview
                            Log.d("ValidateDiscount", "QR detectado: $rawValue")
                            handleBarcodeResult(rawValue)
                        },
                        onCameraReady = { camera -> cameraRef = camera }
                    )

                    // Overlay
                    ValidateScanOverlay()

                    // Info del descuento y controles
                    Column(
                        modifier = Modifier
                            .fillMaxSize()
                            .padding(bottom = 32.dp),
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.SpaceBetween
                    ) {
                        // Chip con info del descuento arriba
                        Card(
                            modifier = Modifier
                                .padding(16.dp)
                                .fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(
                                containerColor = Color.Black.copy(alpha = 0.7f)
                            )
                        ) {
                            Row(
                                modifier = Modifier.padding(12.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Box(
                                    modifier = Modifier
                                        .clip(RoundedCornerShape(8.dp))
                                        .background(Color(0xFFFF6B35))
                                        .padding(horizontal = 8.dp, vertical = 4.dp)
                                ) {
                                    Text(
                                        text = when (discount.discountType) {
                                            "2x1" -> "2x1"
                                            "fixed" -> discount.discountValue ?: "-"
                                            else -> "-${discount.discountPercentage}%"
                                        },
                                        style = MaterialTheme.typography.titleSmall,
                                        fontWeight = FontWeight.Bold,
                                        color = Color.White
                                    )
                                }
                                Spacer(modifier = Modifier.width(12.dp))
                                Column {
                                    Text(
                                        text = discount.title,
                                        style = MaterialTheme.typography.bodyMedium,
                                        fontWeight = FontWeight.Bold,
                                        color = Color.White
                                    )
                                    Text(
                                        text = discount.merchantName,
                                        style = MaterialTheme.typography.bodySmall,
                                        color = Color.White.copy(alpha = 0.7f)
                                    )
                                }
                            }
                        }

                        // Instrucciones y controles abajo
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text(
                                text = "Escanea el QR del usuario Agrochamba",
                                style = MaterialTheme.typography.bodyMedium,
                                color = Color.White,
                                textAlign = TextAlign.Center,
                                modifier = Modifier.padding(horizontal = 32.dp)
                            )

                            Spacer(modifier = Modifier.height(16.dp))

                            TextButton(
                                onClick = {
                                    torchEnabled = !torchEnabled
                                    cameraRef?.cameraControl?.enableTorch(torchEnabled)
                                }
                            ) {
                                Icon(
                                    imageVector = if (torchEnabled) Icons.Default.FlashlightOff else Icons.Default.FlashlightOn,
                                    contentDescription = null,
                                    tint = Color.White,
                                    modifier = Modifier.size(20.dp)
                                )
                                Spacer(modifier = Modifier.width(8.dp))
                                Text(
                                    text = if (torchEnabled) "Apagar linterna" else "Encender linterna",
                                    color = Color.White
                                )
                            }
                        }
                    }
                }

                // Sin permiso de camara
                else -> {
                    Box(
                        modifier = Modifier.fillMaxSize(),
                        contentAlignment = Alignment.Center
                    ) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text("Se necesita permiso de camara", style = MaterialTheme.typography.bodyLarge)
                            Spacer(modifier = Modifier.height(16.dp))
                            Button(onClick = { permissionLauncher.launch(Manifest.permission.CAMERA) }) {
                                Text("Dar permiso")
                            }
                        }
                    }
                }
            }
        }
    }
}

/**
 * Vista del resultado de validacion
 */
@Composable
private fun ValidationResultView(
    validation: DiscountValidationResponse,
    discount: MerchantDiscount,
    scannedDni: String,
    isRedeeming: Boolean,
    redemptionResult: RedemptionData?,
    redemptionError: String?,
    onConfirmRedeem: () -> Unit,
    onScanAnother: () -> Unit,
    onGoBack: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        when {
            // Canje exitoso
            redemptionResult != null -> {
                Icon(
                    Icons.Default.CheckCircle,
                    contentDescription = null,
                    modifier = Modifier.size(80.dp),
                    tint = ValidateGreen
                )
                Spacer(modifier = Modifier.height(20.dp))
                Text(
                    text = "Descuento aplicado",
                    style = MaterialTheme.typography.headlineSmall,
                    fontWeight = FontWeight.Bold,
                    color = ValidateGreen
                )
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = "Se aplico ${
                        when (discount.discountType) {
                            "2x1" -> "2x1"
                            "fixed" -> discount.discountValue ?: ""
                            else -> "${discount.discountPercentage}% de descuento"
                        }
                    }",
                    style = MaterialTheme.typography.bodyLarge,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    textAlign = TextAlign.Center
                )
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = "Cliente: ${redemptionResult.userName}",
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.Medium
                )

                Spacer(modifier = Modifier.height(32.dp))

                Button(
                    onClick = onScanAnother,
                    modifier = Modifier.fillMaxWidth(),
                    colors = ButtonDefaults.buttonColors(containerColor = ValidateGreen)
                ) {
                    Text("Escanear otro cliente")
                }
                Spacer(modifier = Modifier.height(8.dp))
                TextButton(onClick = onGoBack) {
                    Text("Volver")
                }
            }

            // Error al canjear
            redemptionError != null -> {
                Icon(
                    Icons.Default.Error,
                    contentDescription = null,
                    modifier = Modifier.size(64.dp),
                    tint = MaterialTheme.colorScheme.error
                )
                Spacer(modifier = Modifier.height(16.dp))
                Text(
                    text = redemptionError,
                    style = MaterialTheme.typography.bodyLarge,
                    textAlign = TextAlign.Center
                )
                Spacer(modifier = Modifier.height(24.dp))
                Button(onClick = onScanAnother) {
                    Text("Intentar de nuevo")
                }
            }

            // Canjeando...
            isRedeeming -> {
                CircularProgressIndicator(color = ValidateGreen)
                Spacer(modifier = Modifier.height(16.dp))
                Text(
                    "Aplicando descuento...",
                    style = MaterialTheme.typography.bodyLarge
                )
            }

            // Resultado de validacion - usuario puede canjear
            validation.canRedeem -> {
                // Foto del usuario
                Box(
                    modifier = Modifier
                        .size(80.dp)
                        .clip(CircleShape)
                        .background(MaterialTheme.colorScheme.primaryContainer),
                    contentAlignment = Alignment.Center
                ) {
                    if (validation.userPhoto != null) {
                        AsyncImage(
                            model = ImageRequest.Builder(LocalContext.current)
                                .data(validation.userPhoto)
                                .crossfade(true)
                                .build(),
                            contentDescription = "Foto del usuario",
                            modifier = Modifier
                                .fillMaxSize()
                                .clip(CircleShape),
                            contentScale = ContentScale.Crop
                        )
                    } else {
                        Icon(
                            Icons.Default.Person,
                            contentDescription = null,
                            modifier = Modifier.size(48.dp),
                            tint = MaterialTheme.colorScheme.onPrimaryContainer
                        )
                    }
                }

                Spacer(modifier = Modifier.height(16.dp))

                Text(
                    text = validation.userName ?: "Usuario Agrochamba",
                    style = MaterialTheme.typography.headlineSmall,
                    fontWeight = FontWeight.Bold
                )
                Text(
                    text = "DNI: $scannedDni",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                Spacer(modifier = Modifier.height(8.dp))

                Text(
                    text = "Usos: ${validation.timesUsed}/${validation.maxUses}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                Spacer(modifier = Modifier.height(24.dp))

                // Card con detalle del descuento a aplicar
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(
                        containerColor = Color(0xFFF0FDF4)
                    )
                ) {
                    Column(
                        modifier = Modifier.padding(20.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text(
                            text = "Descuento a aplicar",
                            style = MaterialTheme.typography.labelMedium,
                            color = ValidateGreen
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = when (discount.discountType) {
                                "2x1" -> "2x1"
                                "fixed" -> discount.discountValue ?: "-"
                                else -> "${discount.discountPercentage}% OFF"
                            },
                            style = MaterialTheme.typography.headlineLarge,
                            fontWeight = FontWeight.ExtraBold,
                            color = ValidateGreen
                        )
                        Text(
                            text = discount.title,
                            style = MaterialTheme.typography.bodyMedium,
                            color = ValidateGreenDark
                        )
                    }
                }

                Spacer(modifier = Modifier.height(32.dp))

                Button(
                    onClick = onConfirmRedeem,
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(56.dp),
                    shape = RoundedCornerShape(16.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = ValidateGreen)
                ) {
                    Icon(Icons.Default.CheckCircle, contentDescription = null)
                    Spacer(modifier = Modifier.width(8.dp))
                    Text(
                        "Confirmar Descuento",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )
                }

                Spacer(modifier = Modifier.height(8.dp))

                TextButton(onClick = onScanAnother) {
                    Text("Cancelar")
                }
            }

            // Usuario NO puede canjear
            else -> {
                Icon(
                    Icons.Default.Close,
                    contentDescription = null,
                    modifier = Modifier
                        .size(80.dp)
                        .clip(CircleShape)
                        .background(MaterialTheme.colorScheme.errorContainer)
                        .padding(16.dp),
                    tint = MaterialTheme.colorScheme.error
                )
                Spacer(modifier = Modifier.height(16.dp))
                Text(
                    text = "No se puede aplicar descuento",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.error
                )
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = validation.message ?: "Este usuario no puede canjear este descuento",
                    style = MaterialTheme.typography.bodyLarge,
                    textAlign = TextAlign.Center,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                Spacer(modifier = Modifier.height(32.dp))

                Button(
                    onClick = onScanAnother,
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text("Escanear otro cliente")
                }
                Spacer(modifier = Modifier.height(8.dp))
                TextButton(onClick = onGoBack) {
                    Text("Volver")
                }
            }
        }
    }
}

@Composable
private fun ValidateCameraPreview(
    onBarcodeDetected: (Barcode) -> Unit,
    onCameraReady: (Camera) -> Unit = {}
) {
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
                            processValidateImage(imageProxy, barcodeScanner, onBarcodeDetected)
                        }
                    }

                try {
                    cameraProvider.unbindAll()
                    val camera = cameraProvider.bindToLifecycle(
                        lifecycleOwner,
                        CameraSelector.DEFAULT_BACK_CAMERA,
                        preview,
                        imageAnalysis
                    )
                    onCameraReady(camera)
                } catch (e: Exception) {
                    Log.e("ValidateDiscount", "Error al iniciar camara", e)
                }
            }, ContextCompat.getMainExecutor(ctx))

            previewView
        }
    )
}

@androidx.annotation.OptIn(androidx.camera.core.ExperimentalGetImage::class)
private fun processValidateImage(
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
                Log.e("ValidateDiscount", "Error al escanear", e)
            }
            .addOnCompleteListener {
                imageProxy.close()
            }
    } else {
        imageProxy.close()
    }
}

@Composable
private fun ValidateScanOverlay() {
    Canvas(
        modifier = Modifier
            .fillMaxSize()
            .graphicsLayer { compositingStrategy = CompositingStrategy.Offscreen }
    ) {
        val scanSize = size.minDimension * 0.65f
        val left = (size.width - scanSize) / 2
        val top = (size.height - scanSize) / 2

        drawRect(
            color = Color.Black.copy(alpha = 0.5f),
            size = size
        )

        drawRoundRect(
            color = Color.Transparent,
            topLeft = Offset(left, top),
            size = Size(scanSize, scanSize),
            cornerRadius = CornerRadius(16.dp.toPx()),
            blendMode = BlendMode.Clear
        )

        drawRoundRect(
            color = ValidateGreen,
            topLeft = Offset(left, top),
            size = Size(scanSize, scanSize),
            cornerRadius = CornerRadius(16.dp.toPx()),
            style = Stroke(width = 3.dp.toPx())
        )
    }
}
