package agrochamba.com.ui.auth

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ChevronRight
import androidx.compose.material.icons.filled.Facebook
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import agrochamba.com.data.AuthManager
import agrochamba.com.data.SettingsManager
import agrochamba.com.ui.common.BenefitSwitch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SettingsScreen(navController: NavController) {
    // Leer el estado directamente desde SettingsManager.
    // Nota: `facebookUseLinkPreview` y `facebookShortenContent` en SettingsManager son SnapshotState-backed,
    // por lo que su lectura dentro de un @Composable causará recomposición automática.
    val facebookUseLinkPreview = SettingsManager.facebookUseLinkPreview
    val facebookShortenContent = SettingsManager.facebookShortenContent
    val isAdmin = AuthManager.isUserAdmin()
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Configuración") },
                navigationIcon = {
                    IconButton(onClick = { navController.popBackStack() }) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Volver")
                    }
                }
            )
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(16.dp)
                .verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            Text(
                text = "Publicación en Facebook",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold
            )
            
            // Opción para gestionar páginas de Facebook (solo admins)
            if (isAdmin) {
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable { navController.navigate("facebook_pages") },
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.3f)
                    )
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(
                            imageVector = Icons.Default.Facebook,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.primary,
                            modifier = Modifier.size(32.dp)
                        )
                        Spacer(modifier = Modifier.width(16.dp))
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                text = "Gestionar Páginas de Facebook",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Medium
                            )
                            Text(
                                text = "Configura múltiples páginas para publicar",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                        Icon(
                            imageVector = Icons.Default.ChevronRight,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
                
                Spacer(modifier = Modifier.height(8.dp))
            }
            
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(
                    containerColor = MaterialTheme.colorScheme.surfaceVariant
                )
            ) {
                Column(
                    modifier = Modifier.padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    BenefitSwitch(
                        text = "Usar preview del link en lugar de imágenes adjuntas",
                        checked = facebookUseLinkPreview,
                        onCheckedChange = { 
                            SettingsManager.applyFacebookUseLinkPreview(it)
                        }
                    )
                    
                    Text(
                        text = if (facebookUseLinkPreview) {
                            "Las publicaciones mostrarán un preview del link de tu trabajo, lo que puede generar más visitas a tu sitio web."
                        } else {
                            "Las imágenes se adjuntarán nativamente en Facebook, mostrando el contenido visual directamente en el post."
                        },
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.padding(top = 8.dp)
                    )
                    
                    Spacer(modifier = Modifier.height(16.dp))
                    
                    BenefitSwitch(
                        text = "Acortar contenido en Facebook",
                        checked = facebookShortenContent,
                        onCheckedChange = { 
                            SettingsManager.applyFacebookShortenContent(it)
                        }
                    )
                    
                    Text(
                        text = if (facebookShortenContent) {
                            "Solo se mostrará una parte del contenido en Facebook. Al final se agregará un link para leer los detalles completos en el sitio web, generando más tráfico."
                        } else {
                            "Se mostrará el contenido completo en la publicación de Facebook."
                        },
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.padding(top = 8.dp)
                    )
                }
            }
        }
    }
}

