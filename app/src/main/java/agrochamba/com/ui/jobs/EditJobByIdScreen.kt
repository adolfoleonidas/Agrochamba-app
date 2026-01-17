package agrochamba.com.ui.jobs

import android.util.Log
import android.widget.Toast
import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import agrochamba.com.data.*

/**
 * Pantalla que carga un trabajo por ID para editarlo.
 * Útil para la moderación donde se navega por ID en lugar de tener el objeto completo.
 */
@Composable
fun EditJobByIdScreen(
    jobId: Int,
    navController: NavController,
    viewModel: EditJobByIdViewModel = viewModel(
        key = "edit_job_by_id_$jobId",
        factory = EditJobByIdViewModelFactory(jobId)
    )
) {
    val context = LocalContext.current
    val uiState by viewModel.uiState.collectAsState()
    val job by viewModel.job.collectAsState()

    // Cargar el trabajo al iniciar
    LaunchedEffect(jobId) {
        viewModel.loadJob(jobId)
    }

    when {
        uiState.isLoading -> {
            Box(
                modifier = Modifier.fillMaxSize(),
                contentAlignment = Alignment.Center
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    CircularProgressIndicator()
                    Spacer(modifier = Modifier.height(16.dp))
                    Text("Cargando trabajo...")
                }
            }
        }
        uiState.error != null -> {
            Box(
                modifier = Modifier.fillMaxSize(),
                contentAlignment = Alignment.Center
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(
                        "Error: ${uiState.error}",
                        color = MaterialTheme.colorScheme.error
                    )
                    Spacer(modifier = Modifier.height(16.dp))
                    Button(onClick = { viewModel.loadJob(jobId) }) {
                        Text("Reintentar")
                    }
                    Spacer(modifier = Modifier.height(8.dp))
                    TextButton(onClick = { navController.popBackStack() }) {
                        Text("Volver")
                    }
                }
            }
        }
        job != null -> {
            EditJobScreen(
                job = job!!,
                navController = navController
            )
        }
    }
}
