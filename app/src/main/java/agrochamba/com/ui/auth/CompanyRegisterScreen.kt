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
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.VisibilityOff
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.collectAsState
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
import androidx.hilt.navigation.compose.hiltViewModel
import agrochamba.com.Screen

@Composable
fun CompanyRegisterScreen(navController: NavController, viewModel: CompanyRegisterViewModel = hiltViewModel()) {
    val uiState by viewModel.uiState.collectAsState()
    var username by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var ruc by remember { mutableStateOf("") }
    var razonSocial by remember { mutableStateOf("") }
    var passwordVisible by remember { mutableStateOf(false) }
    
    // Estados de error por campo
    var usernameError by remember { mutableStateOf<String?>(null) }
    var emailError by remember { mutableStateOf<String?>(null) }
    var passwordError by remember { mutableStateOf<String?>(null) }
    var rucError by remember { mutableStateOf<String?>(null) }
    var razonSocialError by remember { mutableStateOf<String?>(null) }
    
    // Función de validación local
    fun validateFields(): Boolean {
        var isValid = true
        
        // Validar RUC
        if (ruc.isBlank()) {
            rucError = "El RUC es obligatorio"
            isValid = false
        } else if (ruc.length != 11 || !ruc.all { it.isDigit() }) {
            rucError = "El RUC debe tener 11 dígitos"
            isValid = false
        } else {
            rucError = null
        }
        
        // Validar Razón Social
        if (razonSocial.isBlank()) {
            razonSocialError = "La razón social es obligatoria"
            isValid = false
        } else if (razonSocial.length < 3) {
            razonSocialError = "La razón social debe tener al menos 3 caracteres"
            isValid = false
        } else {
            razonSocialError = null
        }
        
        // Validar Username
        if (username.isBlank()) {
            usernameError = "El nombre de usuario es obligatorio"
            isValid = false
        } else if (username.length < 4) {
            usernameError = "El nombre de usuario debe tener al menos 4 caracteres"
            isValid = false
        } else if (!username.matches(Regex("^[a-zA-Z0-9_]+$"))) {
            usernameError = "Solo letras, números y guiones bajos"
            isValid = false
        } else {
            usernameError = null
        }
        
        // Validar Email
        if (email.isBlank()) {
            emailError = "El correo electrónico es obligatorio"
            isValid = false
        } else if (!android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            emailError = "Ingresa un correo electrónico válido"
            isValid = false
        } else {
            emailError = null
        }
        
        // Validar Password
        if (password.isBlank()) {
            passwordError = "La contraseña es obligatoria"
            isValid = false
        } else if (password.length < 6) {
            passwordError = "La contraseña debe tener al menos 6 caracteres"
            isValid = false
        } else {
            passwordError = null
        }
        
        return isValid
    }
    
    // Navegar después de registro exitoso
    LaunchedEffect(uiState.registrationSuccess) {
        if (uiState.registrationSuccess) {
            navController.navigate(Screen.Login.route) {
                popUpTo(Screen.RegisterChoice.route) { inclusive = true }
            }
        }
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
            Text(
                "Registro para Empresas", 
                style = MaterialTheme.typography.headlineMedium, 
                textAlign = TextAlign.Center
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                "Crea una cuenta para publicar ofertas de trabajo",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
            Spacer(modifier = Modifier.height(32.dp))

            // RUC
            OutlinedTextField(
                value = ruc, 
                onValueChange = { 
                    ruc = it.filter { char -> char.isDigit() }.take(11)
                    rucError = null
                }, 
                label = { Text("RUC") }, 
                placeholder = { Text("Ej: 20123456789") },
                modifier = Modifier.fillMaxWidth(), 
                leadingIcon = { Icon(Icons.Default.Badge, null) }, 
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number), 
                singleLine = true,
                isError = rucError != null,
                supportingText = rucError?.let { { Text(it, color = MaterialTheme.colorScheme.error) } }
            )
            Spacer(modifier = Modifier.height(12.dp))
            
            // Razón Social
            OutlinedTextField(
                value = razonSocial, 
                onValueChange = { 
                    razonSocial = it
                    razonSocialError = null
                }, 
                label = { Text("Razón Social") },
                placeholder = { Text("Nombre de la empresa") },
                modifier = Modifier.fillMaxWidth(), 
                leadingIcon = { Icon(Icons.Default.Apartment, null) }, 
                singleLine = true,
                isError = razonSocialError != null,
                supportingText = razonSocialError?.let { { Text(it, color = MaterialTheme.colorScheme.error) } }
            )
            Spacer(modifier = Modifier.height(12.dp))
            
            // Username
            OutlinedTextField(
                value = username, 
                onValueChange = { 
                    username = it.lowercase().filter { char -> char.isLetterOrDigit() || char == '_' }
                    usernameError = null
                }, 
                label = { Text("Nombre de usuario") },
                placeholder = { Text("Ej: miempresa") },
                modifier = Modifier.fillMaxWidth(), 
                leadingIcon = { Icon(Icons.Default.Person, null) }, 
                singleLine = true,
                isError = usernameError != null,
                supportingText = usernameError?.let { { Text(it, color = MaterialTheme.colorScheme.error) } }
            )
            Spacer(modifier = Modifier.height(12.dp))
            
            // Email
            OutlinedTextField(
                value = email, 
                onValueChange = { 
                    email = it.trim()
                    emailError = null
                }, 
                label = { Text("Correo electrónico") },
                placeholder = { Text("Ej: contacto@empresa.com") },
                modifier = Modifier.fillMaxWidth(), 
                leadingIcon = { Icon(Icons.Default.Email, null) }, 
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email), 
                singleLine = true,
                isError = emailError != null,
                supportingText = emailError?.let { { Text(it, color = MaterialTheme.colorScheme.error) } }
            )
            Spacer(modifier = Modifier.height(12.dp))
            
            // Password
            OutlinedTextField(
                value = password, 
                onValueChange = { 
                    password = it
                    passwordError = null
                }, 
                label = { Text("Contraseña") },
                placeholder = { Text("Mínimo 6 caracteres") },
                modifier = Modifier.fillMaxWidth(), 
                leadingIcon = { Icon(Icons.Default.Lock, null) },
                trailingIcon = {
                    IconButton(onClick = { passwordVisible = !passwordVisible }) {
                        Icon(
                            imageVector = if (passwordVisible) Icons.Default.Visibility else Icons.Default.VisibilityOff,
                            contentDescription = if (passwordVisible) "Ocultar contraseña" else "Mostrar contraseña"
                        )
                    }
                },
                singleLine = true, 
                visualTransformation = if (passwordVisible) VisualTransformation.None else PasswordVisualTransformation(), 
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
                isError = passwordError != null,
                supportingText = passwordError?.let { { Text(it, color = MaterialTheme.colorScheme.error) } }
            )

            Spacer(modifier = Modifier.height(24.dp))

            // Error general del servidor
            uiState.error?.let {
                Card(
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.errorContainer
                    ),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text(
                        text = it, 
                        color = MaterialTheme.colorScheme.onErrorContainer, 
                        textAlign = TextAlign.Center,
                        modifier = Modifier.padding(16.dp),
                        style = MaterialTheme.typography.bodyMedium
                    )
                }
                Spacer(modifier = Modifier.height(16.dp))
            }

            if (uiState.isLoading) {
                CircularProgressIndicator()
            } else {
                Button(
                    onClick = { 
                        if (validateFields()) {
                            viewModel.registerCompany(username, email, password, ruc, razonSocial) 
                        }
                    },
                    modifier = Modifier.fillMaxWidth().height(48.dp)
                ) {
                    Text("Registrar Empresa")
                }
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
