package agrochamba.com.ui.auth

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowForwardIos
import androidx.compose.material.icons.filled.Bookmark
import androidx.compose.material.icons.filled.Business
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.ExitToApp
import androidx.compose.material.icons.filled.Favorite
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.Work
import androidx.compose.material.icons.filled.BedroomParent
import androidx.compose.material.icons.filled.AccountBalanceWallet
import androidx.compose.material.icons.filled.LocalOffer
import androidx.compose.material.icons.filled.Send
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import agrochamba.com.ui.common.MenuItem
import agrochamba.com.ui.common.MenuItemWithBadge
import agrochamba.com.ui.common.MenuItemWithCount
import androidx.compose.material3.Divider
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import agrochamba.com.Screen
import agrochamba.com.data.AuthManager
import agrochamba.com.data.ModerationNotificationManager
import agrochamba.com.data.WordPressApi
import agrochamba.com.ui.moderation.ModerationViewModel
import agrochamba.com.utils.htmlToString
import agrochamba.com.utils.DebugManager
import agrochamba.com.utils.SecretTapDetector
import coil.compose.AsyncImage
import coil.request.ImageRequest
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProfileScreen(navController: NavController, viewModel: ProfileViewModel = viewModel()) {
    val uiState = viewModel.uiState
    val profile = uiState.userProfile
    val displayName = profile?.displayName ?: AuthManager.userDisplayName ?: "Usuario"
    val username = AuthManager.userDisplayName?.lowercase()?.replace(" ", "") ?: "usuario"
    val profilePhotoUrl = profile?.profilePhotoUrl
    val context = LocalContext.current

    // Estado del modo debug
    val isDebugEnabled by DebugManager.isEnabledFlow.collectAsState()

    // Detector de tap secreto para activar modo debug (tap 5 veces en la foto)
    val secretTapDetector = remember {
        SecretTapDetector(
            requiredTaps = 5,
            onSecretActivated = {
                // Solo permitir a admins activar el modo debug
                if (AuthManager.isUserAdmin()) {
                    DebugManager.toggle()
                    DebugManager.showToggleToast(context)
                }
            }
        )
    }

    // Observar el contador de trabajos pendientes
    val pendingJobsCount by ModerationNotificationManager.pendingJobsCount.collectAsState()
    
    // Cargar trabajos pendientes si el usuario es administrador
    LaunchedEffect(Unit) {
        if (AuthManager.isUserAdmin()) {
            // Cargar trabajos pendientes para actualizar el contador
            val token = AuthManager.token
            if (token != null) {
                try {
                    val authHeader = "Bearer $token"
                    val response = WordPressApi.retrofitService.getPendingJobs(authHeader, page = 1, perPage = 100)
                    ModerationNotificationManager.updatePendingJobsCount(response.data.size)
                } catch (e: Exception) {
                    android.util.Log.e("ProfileScreen", "Error al cargar trabajos pendientes: ${e.message}")
                    // En caso de error, mantener el contador en 0
                    ModerationNotificationManager.updatePendingJobsCount(0)
                }
            }
        } else {
            // Si no es admin, resetear el contador
            ModerationNotificationManager.resetCount()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Mi Perfil") },
                actions = {
                    IconButton(onClick = { navController.navigate(Screen.Settings.route) }) {
                        Icon(Icons.Default.Settings, contentDescription = "Configuración")
                    }
                }
            )
        }
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues),
            contentPadding = PaddingValues(bottom = 16.dp)
        ) {
            item {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(24.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    // Foto de perfil con tap secreto para activar debug (5 taps)
                    Box(
                        modifier = Modifier
                            .size(100.dp)
                            .clip(CircleShape)
                            .background(
                                if (isDebugEnabled) MaterialTheme.colorScheme.errorContainer
                                else MaterialTheme.colorScheme.primaryContainer
                            )
                            .clickable { secretTapDetector.onTap() },
                        contentAlignment = Alignment.Center
                    ) {
                        if (profilePhotoUrl != null) {
                            AsyncImage(
                                model = ImageRequest.Builder(LocalContext.current)
                                    .data(profilePhotoUrl)
                                    .crossfade(true)
                                    .build(),
                                contentDescription = "Foto de perfil",
                                modifier = Modifier.fillMaxSize(),
                                contentScale = ContentScale.Crop
                            )
                        } else {
                            Icon(
                                Icons.Default.Person,
                                contentDescription = null,
                                modifier = Modifier.size(60.dp),
                                tint = if (isDebugEnabled) MaterialTheme.colorScheme.onErrorContainer
                                       else MaterialTheme.colorScheme.onPrimaryContainer
                            )
                        }
                        // Indicador visual de modo debug activo
                        if (isDebugEnabled) {
                            Box(
                                modifier = Modifier
                                    .align(Alignment.BottomEnd)
                                    .size(24.dp)
                                    .background(MaterialTheme.colorScheme.error, CircleShape),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    text = "D",
                                    style = MaterialTheme.typography.labelSmall,
                                    color = MaterialTheme.colorScheme.onError,
                                    fontWeight = FontWeight.Bold
                                )
                            }
                        }
                    }
                    Spacer(modifier = Modifier.height(16.dp))
                    Text(
                        text = displayName,
                        style = MaterialTheme.typography.headlineSmall,
                        fontWeight = FontWeight.Bold
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = "@$username",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    Spacer(modifier = Modifier.height(16.dp))
                    OutlinedButton(
                        onClick = { navController.navigate(Screen.EditProfile.route) }
                    ) {
                        Icon(Icons.Default.Edit, contentDescription = null, modifier = Modifier.size(18.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Editar Perfil")
                    }
                }
            }

            item {
                Spacer(modifier = Modifier.height(8.dp))
            }

            item {
                MenuItemWithCount(
                    icon = Icons.Default.Favorite,
                    title = "Mis Favoritos",
                    count = uiState.favorites.size,
                    onClick = { navController.navigate(Screen.Favorites.route) },
                    isLoading = uiState.isLoadingFavorites
                )
            }

            item {
                MenuItemWithCount(
                    icon = Icons.Default.Bookmark,
                    title = "Mis Guardados",
                    count = uiState.saved.size,
                    onClick = { navController.navigate(Screen.Saved.route) },
                    isLoading = uiState.isLoadingSaved
                )
            }

            // Mis Postulaciones (solo para trabajadores, no empresas)
            if (!AuthManager.isUserAnEnterprise()) {
                item {
                    MenuItem(
                        icon = Icons.Default.Send,
                        title = "Mis Postulaciones",
                        onClick = { navController.navigate(Screen.Applications.route) }
                    )
                }
            }

            // Créditos (solo para empresas/admin)
            if (AuthManager.isUserAnEnterprise()) {
                item {
                    MenuItem(
                        icon = Icons.Default.AccountBalanceWallet,
                        title = "Mis Créditos",
                        onClick = { navController.navigate(Screen.Credits.route) }
                    )
                }
            }

            // Cuartos (visible para todos los usuarios)
            item {
                MenuItem(
                    icon = Icons.Default.BedroomParent,
                    title = "Cuartos",
                    onClick = { navController.navigate(Screen.Rooms.route) }
                )
            }

            // Descuentos con comercios aliados (visible para todos)
            item {
                MenuItem(
                    icon = Icons.Default.LocalOffer,
                    title = "Descuentos",
                    onClick = { navController.navigate(Screen.Discounts.route) }
                )
            }

            item {
                Divider(modifier = Modifier.padding(vertical = 8.dp))
            }

            if (AuthManager.isUserAnEnterprise()) {
                item {
                    MenuItemWithCount(
                        icon = Icons.Default.Work,
                        title = "Mis Anuncios",
                        count = uiState.myJobs.size,
                        onClick = { navController.navigate(Screen.MyJobs.route) },
                        isLoading = uiState.isLoading
                    )
                }
            }

            if (AuthManager.isUserAdmin()) {
                item {
                    MenuItemWithBadge(
                        icon = Icons.Default.Settings,
                        title = "Moderar Trabajos",
                        badgeCount = pendingJobsCount,
                        onClick = { navController.navigate(Screen.Moderation.route) }
                    )
                }
            }

            item {
                Divider(modifier = Modifier.padding(vertical = 8.dp))
            }

            item {
                MenuItem(
                    icon = Icons.Default.Settings,
                    title = "Política de Privacidad",
                    onClick = { navController.navigate(Screen.PrivacyPolicy.route) }
                )
            }

            item {
                Divider(modifier = Modifier.padding(vertical = 8.dp))
            }

            item {
                var showDialog by remember { mutableStateOf(false) }
                MenuItem(
                    icon = Icons.Default.ExitToApp,
                    title = "Cerrar Sesión",
                    onClick = { showDialog = true }
                )
                if (showDialog) {
                    AlertDialog(
                        onDismissRequest = { showDialog = false },
                        title = { Text("Cerrar Sesión") },
                        text = { Text("¿Estás seguro de que quieres cerrar sesión?") },
                        confirmButton = {
                            Button(
                                onClick = {
                                    showDialog = false
                                    AuthManager.logout()
                                },
                                colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                            ) {
                                Text("Cerrar Sesión")
                            }
                        },
                        dismissButton = {
                            TextButton(onClick = { showDialog = false }) {
                                Text("Cancelar")
                            }
                        }
                    )
                }
            }

            // Panel de Debug (solo visible para admins cuando debug está activo)
            if (isDebugEnabled && AuthManager.isUserAdmin()) {
                item {
                    Spacer(modifier = Modifier.height(16.dp))
                    DebugPanel(
                        onDisableDebug = {
                            DebugManager.disable()
                            DebugManager.showToggleToast(context)
                        }
                    )
                }
            }
        }
    }
}

/**
 * Panel de Debug - Muestra información útil para debugging
 */
@Composable
private fun DebugPanel(onDisableDebug: () -> Unit) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.errorContainer.copy(alpha = 0.3f)
        )
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "Panel de Debug",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.error
                )
                TextButton(
                    onClick = onDisableDebug,
                    colors = ButtonDefaults.textButtonColors(
                        contentColor = MaterialTheme.colorScheme.error
                    )
                ) {
                    Text("Desactivar")
                }
            }

            Divider(color = MaterialTheme.colorScheme.error.copy(alpha = 0.3f))

            // Información del usuario
            DebugInfoRow("Display Name", AuthManager.userDisplayName ?: "null")
            DebugInfoRow("Company ID", AuthManager.userCompanyId?.toString() ?: "null")
            DebugInfoRow("Es Admin", AuthManager.isUserAdmin().toString())
            DebugInfoRow("Es Empresa", AuthManager.isUserAnEnterprise().toString())
            DebugInfoRow("Token", if (AuthManager.token != null) "OK (${AuthManager.token?.take(20)}...)" else "null")

            Divider(color = MaterialTheme.colorScheme.error.copy(alpha = 0.3f))

            // Información de crashes
            DebugInfoRow("Crashes detectados", DebugManager.crashCount.toString())

            if (DebugManager.lastCrashLog != null) {
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = "Último crash:",
                    style = MaterialTheme.typography.labelMedium,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.error
                )
                Text(
                    text = DebugManager.lastCrashLog?.take(500) ?: "",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f),
                    maxLines = 10,
                    overflow = TextOverflow.Ellipsis
                )

                TextButton(
                    onClick = { DebugManager.clearCrashLog() },
                    colors = ButtonDefaults.textButtonColors(
                        contentColor = MaterialTheme.colorScheme.error
                    )
                ) {
                    Text("Limpiar logs")
                }
            }

            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = "Tip: Los logs aparecen en Logcat con prefijo ACH_",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
private fun DebugInfoRow(label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Text(
            text = label,
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Text(
            text = value,
            style = MaterialTheme.typography.bodySmall,
            fontWeight = FontWeight.Medium,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis,
            modifier = Modifier.weight(1f, fill = false)
        )
    }
}

// Componentes movidos a ui/common/MenuItems.kt y ui/common/JobCard.kt