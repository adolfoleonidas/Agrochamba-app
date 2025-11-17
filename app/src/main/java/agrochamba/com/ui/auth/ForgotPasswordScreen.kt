package agrochamba.com.ui.auth

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Email
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Password
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController

@Composable
fun ForgotPasswordScreen(navController: NavController, viewModel: ForgotPasswordViewModel = viewModel()) {
    val uiState = viewModel.uiState
    val context = LocalContext.current
    var userLogin by remember { mutableStateOf("") } // El estado ahora vive en el padre

    LaunchedEffect(uiState.passwordResetSuccess) {
        if (uiState.passwordResetSuccess) {
            Toast.makeText(context, "¡Contraseña actualizada con éxito!", Toast.LENGTH_SHORT).show()
            navController.popBackStack()
        }
    }

    Box(modifier = Modifier.fillMaxSize()) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
        )

        when (uiState.currentStep) {
            // Le pasamos el estado y la capacidad de cambiarlo al Paso 1
            ResetStep.AskForCode -> Step1_AskForCode(viewModel, userLogin, onUserLoginChange = { userLogin = it })
            // Le pasamos el valor recordado del usuario al Paso 2
            ResetStep.SubmitCode -> Step2_SubmitCode(viewModel, userLogin)
        }
    }
}

@Composable
private fun Step1_AskForCode(viewModel: ForgotPasswordViewModel, userLogin: String, onUserLoginChange: (String) -> Unit) {
    val uiState = viewModel.uiState

    Column(
        modifier = Modifier.fillMaxSize().padding(32.dp).verticalScroll(rememberScrollState()),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text("Recuperar Contraseña", style = MaterialTheme.typography.headlineMedium, textAlign = TextAlign.Center)
        Spacer(modifier = Modifier.height(16.dp))
        Text(
            "Ingresa tu correo o nombre de usuario.",
            style = MaterialTheme.typography.bodyMedium,
            textAlign = TextAlign.Center
        )
        Spacer(modifier = Modifier.height(32.dp))

        OutlinedTextField(
            value = userLogin,
            onValueChange = onUserLoginChange, // Usamos la función del padre
            label = { Text("Usuario o Correo Electrónico") },
            modifier = Modifier.fillMaxWidth(),
            leadingIcon = { Icon(Icons.Default.Email, contentDescription = null) },
            singleLine = true
        )

        Spacer(modifier = Modifier.height(24.dp))

        if (uiState.isLoading) {
            CircularProgressIndicator()
        } else {
            Button(
                onClick = { viewModel.requestPasswordReset(userLogin) },
                modifier = Modifier.fillMaxWidth().height(48.dp)
            ) {
                Text("Enviar Código")
            }
        }

        uiState.error?.let {
            Spacer(modifier = Modifier.height(16.dp))
            Text(text = it, color = MaterialTheme.colorScheme.error, textAlign = TextAlign.Center)
        }
    }
}

@Composable
private fun Step2_SubmitCode(viewModel: ForgotPasswordViewModel, userLogin: String) {
    val uiState = viewModel.uiState
    var code by remember { mutableStateOf("") }
    var newPassword by remember { mutableStateOf("") }

    Column(
        modifier = Modifier.fillMaxSize().padding(32.dp).verticalScroll(rememberScrollState()),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text("Verifica tu Cuenta", style = MaterialTheme.typography.headlineMedium, textAlign = TextAlign.Center)
        Spacer(modifier = Modifier.height(16.dp))
        Text(
            uiState.successMessage ?: "Revisa tu correo e ingresa el código de 6 dígitos.",
            style = MaterialTheme.typography.bodyMedium,
            textAlign = TextAlign.Center
        )
        Spacer(modifier = Modifier.height(32.dp))

        OutlinedTextField(
            value = code,
            onValueChange = { code = it },
            label = { Text("Código de 6 dígitos") },
            modifier = Modifier.fillMaxWidth(),
            leadingIcon = { Icon(Icons.Default.Password, contentDescription = null) },
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number)
        )

        Spacer(modifier = Modifier.height(16.dp))

        OutlinedTextField(
            value = newPassword,
            onValueChange = { newPassword = it },
            label = { Text("Nueva Contraseña") },
            modifier = Modifier.fillMaxWidth(),
            leadingIcon = { Icon(Icons.Default.Lock, contentDescription = null) },
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password)
        )

        Spacer(modifier = Modifier.height(24.dp))

        if (uiState.isLoading) {
            CircularProgressIndicator()
        } else {
            Button(
                onClick = { viewModel.resetPassword(userLogin, code, newPassword) }, // Ahora userLogin tiene el valor correcto
                modifier = Modifier.fillMaxWidth().height(48.dp)
            ) {
                Text("Cambiar Contraseña")
            }
        }

        uiState.error?.let {
            Spacer(modifier = Modifier.height(16.dp))
            Text(text = it, color = MaterialTheme.colorScheme.error, textAlign = TextAlign.Center)
        }
    }
}