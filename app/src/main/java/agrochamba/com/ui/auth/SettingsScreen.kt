package agrochamba.com.ui.auth

import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import agrochamba.com.data.SettingsManager
import agrochamba.com.ui.common.BenefitSwitch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SettingsScreen(navController: NavController) {
    // Leer el estado directamente desde SettingsManager.
    // Nota: `facebookUseLinkPreview` en SettingsManager es SnapshotState-backed,
    // por lo que su lectura dentro de un @Composable causará recomposición automática.
    val facebookUseLinkPreview = SettingsManager.facebookUseLinkPreview
    
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
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            Text(
                text = "Publicación en Facebook",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold
            )
            
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
                }
            }
        }
    }
}

