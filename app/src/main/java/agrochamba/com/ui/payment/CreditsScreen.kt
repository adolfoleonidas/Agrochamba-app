package agrochamba.com.ui.payment

import android.net.Uri
import android.util.Log
import androidx.browser.customtabs.CustomTabsIntent
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Star
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalLifecycleOwner
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import agrochamba.com.MainActivity
import agrochamba.com.data.CreditPackage

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CreditsScreen(
    navController: NavController,
    viewModel: CreditsViewModel = viewModel()
) {
    val uiState = viewModel.uiState
    val context = LocalContext.current

    LaunchedEffect(Unit) {
        viewModel.loadData()
    }

    // Manejar retorno de Mercado Pago
    val lifecycleOwner = LocalLifecycleOwner.current
    DisposableEffect(lifecycleOwner) {
        val observer = LifecycleEventObserver { _, event ->
            if (event == Lifecycle.Event.ON_RESUME && uiState.checkoutOpened) {
                val deepLink = MainActivity.consumePaymentDeepLink()
                if (deepLink != null && deepLink.status == "success") {
                    viewModel.onPurchaseSuccess()
                } else if (deepLink != null && deepLink.status == "failure") {
                    viewModel.onPurchaseFailure()
                } else {
                    // Refrescar saldo por si el webhook ya proceso
                    viewModel.refreshBalance()
                }
            }
        }
        lifecycleOwner.lifecycle.addObserver(observer)
        onDispose { lifecycleOwner.lifecycle.removeObserver(observer) }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Creditos") },
                navigationIcon = {
                    IconButton(onClick = { navController.popBackStack() }) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Volver")
                    }
                }
            )
        }
    ) { innerPadding ->
        when {
            uiState.isLoading -> {
                Box(
                    modifier = Modifier.fillMaxSize().padding(innerPadding),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator()
                }
            }

            uiState.purchaseSuccess -> {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(innerPadding)
                        .padding(32.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.Center
                ) {
                    Icon(
                        Icons.Default.CheckCircle,
                        contentDescription = null,
                        modifier = Modifier.size(80.dp),
                        tint = MaterialTheme.colorScheme.primary
                    )
                    Spacer(modifier = Modifier.height(16.dp))
                    Text(
                        text = "Compra exitosa",
                        style = MaterialTheme.typography.headlineMedium,
                        fontWeight = FontWeight.Bold
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    Text(
                        text = "Tus creditos han sido acreditados.",
                        style = MaterialTheme.typography.bodyLarge,
                        textAlign = TextAlign.Center,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    if (uiState.balance >= 0) {
                        Spacer(modifier = Modifier.height(8.dp))
                        Text(
                            text = "Saldo actual: ${uiState.balance} creditos",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.primary
                        )
                    }
                    Spacer(modifier = Modifier.height(32.dp))
                    Button(
                        onClick = { navController.popBackStack() },
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text("Continuar")
                    }
                }
            }

            else -> {
                LazyColumn(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(innerPadding)
                        .padding(horizontal = 16.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    // Saldo actual
                    item {
                        Spacer(modifier = Modifier.height(8.dp))
                        BalanceCard(
                            balance = uiState.balance,
                            isUnlimited = uiState.isUnlimited
                        )
                    }

                    // Tabla de costos
                    item {
                        Spacer(modifier = Modifier.height(8.dp))
                        Text(
                            text = "Costos",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        CostsCard(uiState.costs)
                    }

                    // Plan gratuito
                    item {
                        Spacer(modifier = Modifier.height(8.dp))
                        FreePlanCard()
                    }

                    // Paquetes
                    item {
                        Spacer(modifier = Modifier.height(8.dp))
                        Text(
                            text = "Comprar creditos",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold
                        )
                    }

                    items(uiState.packages) { pkg ->
                        PackageCard(
                            pkg = pkg,
                            isLoading = uiState.purchasingPackageId == pkg.id,
                            onBuy = {
                                viewModel.purchasePackage(pkg.id) { initPoint ->
                                    try {
                                        val intent = CustomTabsIntent.Builder().build()
                                        intent.launchUrl(context, Uri.parse(initPoint))
                                    } catch (e: Exception) {
                                        Log.e("CreditsScreen", "Error opening checkout", e)
                                    }
                                }
                            }
                        )
                    }

                    // Error
                    if (uiState.error != null) {
                        item {
                            Card(
                                colors = CardDefaults.cardColors(
                                    containerColor = MaterialTheme.colorScheme.errorContainer
                                )
                            ) {
                                Text(
                                    text = uiState.error,
                                    modifier = Modifier.padding(16.dp),
                                    color = MaterialTheme.colorScheme.onErrorContainer
                                )
                            }
                        }
                    }

                    item { Spacer(modifier = Modifier.height(16.dp)) }
                }
            }
        }
    }
}

@Composable
fun BalanceCard(balance: Int, isUnlimited: Boolean) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.primaryContainer
        )
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(
                text = "Tu saldo",
                style = MaterialTheme.typography.titleSmall,
                color = MaterialTheme.colorScheme.onPrimaryContainer
            )
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                text = if (isUnlimited) "Ilimitado" else "$balance",
                style = MaterialTheme.typography.headlineLarge,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onPrimaryContainer
            )
            if (!isUnlimited) {
                Text(
                    text = "creditos disponibles",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onPrimaryContainer.copy(alpha = 0.7f)
                )
            }
        }
    }
}

@Composable
fun CostsCard(costs: Map<String, Int>) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            CostRow("Publicar trabajo", costs["publish_job"] ?: 5)
            CostRow("IA - Mejorar texto", costs["ai_enhance"] ?: 1)
            CostRow("IA - Generar titulo", costs["ai_title"] ?: 1)
            CostRow("IA - OCR imagen", costs["ai_ocr"] ?: 2)
        }
    }
}

@Composable
fun CostRow(label: String, credits: Int) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Text(text = label, style = MaterialTheme.typography.bodyMedium)
        Text(
            text = "$credits cr.",
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.primary
        )
    }
}

@Composable
fun FreePlanCard() {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.secondaryContainer
        ),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outline.copy(alpha = 0.3f))
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            Text(
                text = "Plan Gratuito",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSecondaryContainer
            )
            Spacer(modifier = Modifier.height(12.dp))

            Text(
                text = "Incluido gratis",
                style = MaterialTheme.typography.labelMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSecondaryContainer
            )
            Spacer(modifier = Modifier.height(4.dp))
            FreePlanRow(included = true, text = "3 publicaciones por semana")
            FreePlanRow(included = true, text = "Visibilidad basica")
            FreePlanRow(included = true, text = "Funciones basicas de IA")

            Spacer(modifier = Modifier.height(12.dp))

            Text(
                text = "No incluido",
                style = MaterialTheme.typography.labelMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSecondaryContainer.copy(alpha = 0.7f)
            )
            Spacer(modifier = Modifier.height(4.dp))
            FreePlanRow(included = false, text = "Compartir en Facebook")
            FreePlanRow(included = false, text = "Mayor visibilidad y prioridad")
        }
    }
}

@Composable
private fun FreePlanRow(included: Boolean, text: String) {
    Row(
        modifier = Modifier.padding(vertical = 2.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(
            imageVector = if (included) Icons.Default.Check else Icons.Default.Close,
            contentDescription = null,
            modifier = Modifier.size(18.dp),
            tint = if (included) MaterialTheme.colorScheme.primary
                   else MaterialTheme.colorScheme.onSecondaryContainer.copy(alpha = 0.5f)
        )
        Spacer(modifier = Modifier.width(8.dp))
        Text(
            text = text,
            style = MaterialTheme.typography.bodyMedium,
            color = if (included) MaterialTheme.colorScheme.onSecondaryContainer
                    else MaterialTheme.colorScheme.onSecondaryContainer.copy(alpha = 0.5f)
        )
    }
}

@Composable
fun PackageCard(pkg: CreditPackage, isLoading: Boolean, onBuy: () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        border = if (pkg.popular) BorderStroke(2.dp, MaterialTheme.colorScheme.primary) else null
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(
                        text = pkg.label,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )
                    if (pkg.popular) {
                        Spacer(modifier = Modifier.width(8.dp))
                        Icon(
                            Icons.Default.Star,
                            contentDescription = "Popular",
                            modifier = Modifier.size(16.dp),
                            tint = MaterialTheme.colorScheme.primary
                        )
                    }
                }
                Spacer(modifier = Modifier.height(2.dp))
                Text(
                    text = pkg.description,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                // Precio por crédito
                val pricePerCredit = pkg.price / pkg.credits
                Text(
                    text = "S/ %.2f por credito".format(pricePerCredit),
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            Spacer(modifier = Modifier.width(12.dp))

            Button(
                onClick = onBuy,
                enabled = !isLoading,
                shape = RoundedCornerShape(12.dp)
            ) {
                if (isLoading) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(16.dp),
                        strokeWidth = 2.dp,
                        color = MaterialTheme.colorScheme.onPrimary
                    )
                } else {
                    Text("S/ %.2f".format(pkg.price))
                }
            }
        }
    }
}
