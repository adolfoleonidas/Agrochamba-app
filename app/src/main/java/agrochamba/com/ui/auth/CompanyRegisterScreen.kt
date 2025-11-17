package agrochamba.com.ui.auth

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Apartment
import androidx.compose.material.icons.filled.Badge
import androidx.compose.material.icons.filled.Email
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import agrochamba.com.Screen

@Composable
fun CompanyRegisterScreen(navController: NavController, viewModel: CompanyRegisterViewModel = viewModel()) {
    val uiState = viewModel.uiState
    var username by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var ruc by remember { mutableStateOf("") }
    var razonSocial by remember { mutableStateOf("") }
    var passwordVisible by remember { mutableStateOf(false) }

    LaunchedEffect(uiState.registrationSuccess) {
        // La navegación ahora la controla el componente AppEntry
    }

    Box(modifier = Modifier.fillMaxSize()) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
        )

        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(32.dp)
                .verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.Center,
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text("Registro para Empresas", style = MaterialTheme.typography.headlineMedium, textAlign = TextAlign.Center)
            Spacer(modifier = Modifier.height(32.dp))

            OutlinedTextField(value = ruc, onValueChange = { ruc = it }, label = { Text("RUC") }, modifier = Modifier.fillMaxWidth(), leadingIcon = { Icon(Icons.Default.Badge, null) }, keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number), singleLine = true)
            Spacer(modifier = Modifier.height(16.dp))
            OutlinedTextField(value = razonSocial, onValueChange = { razonSocial = it }, label = { Text("Razón Social") }, modifier = Modifier.fillMaxWidth(), leadingIcon = { Icon(Icons.Default.Apartment, null) }, singleLine = true)
            Spacer(modifier = Modifier.height(16.dp))
            OutlinedTextField(value = username, onValueChange = { username = it }, label = { Text("Nombre de usuario (admin)") }, modifier = Modifier.fillMaxWidth(), leadingIcon = { Icon(Icons.Default.Person, null) }, singleLine = true)
            Spacer(modifier = Modifier.height(16.dp))
            OutlinedTextField(value = email, onValueChange = { email = it }, label = { Text("Correo electrónico (admin)") }, modifier = Modifier.fillMaxWidth(), leadingIcon = { Icon(Icons.Default.Email, null) }, keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email), singleLine = true)
            Spacer(modifier = Modifier.height(16.dp))
            OutlinedTextField(value = password, onValueChange = { password = it }, label = { Text("Contraseña") }, modifier = Modifier.fillMaxWidth(), leadingIcon = { Icon(Icons.Default.Lock, null) }, singleLine = true, visualTransformation = if (passwordVisible) VisualTransformation.None else PasswordVisualTransformation(), keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password))

            Spacer(modifier = Modifier.height(24.dp))

            if (uiState.isLoading) {
                CircularProgressIndicator()
            } else {
                Button(
                    onClick = { viewModel.registerCompany(username, email, password, ruc, razonSocial) },
                    modifier = Modifier.fillMaxWidth().height(48.dp)
                ) {
                    Text("Registrar Empresa")
                }
            }

            uiState.error?.let {
                Spacer(modifier = Modifier.height(16.dp))
                Text(text = it, color = MaterialTheme.colorScheme.error, textAlign = TextAlign.Center)
            }

            Spacer(modifier = Modifier.height(24.dp))

            Text(
                text = "¿Ya tienes una cuenta? Inicia Sesión",
                modifier = Modifier.clickable { navController.navigate(Screen.Login.route) { popUpTo(Screen.Login.route) { inclusive = true } } },
                color = MaterialTheme.colorScheme.primary,
                fontWeight = FontWeight.Bold
            )
        }
    }
}