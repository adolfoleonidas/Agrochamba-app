package agrochamba.com.ui.auth

import android.net.Uri
import android.widget.Toast
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.CameraAlt
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import agrochamba.com.data.AuthManager
import coil.compose.AsyncImage
import coil.request.ImageRequest

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EditProfileScreen(navController: NavController, viewModel: ProfileViewModel = viewModel()) {
    val uiState = viewModel.uiState
    val context = LocalContext.current
    val profile = uiState.userProfile

    // Estados para los campos del formulario
    var displayName by remember { mutableStateOf(profile?.displayName ?: "") }
    var firstName by remember { mutableStateOf(profile?.firstName ?: "") }
    var lastName by remember { mutableStateOf(profile?.lastName ?: "") }
    var email by remember { mutableStateOf(profile?.email ?: "") }
    var phone by remember { mutableStateOf(profile?.phone ?: "") }
    var bio by remember { mutableStateOf(profile?.bio ?: "") }
    var companyDescription by remember { mutableStateOf(profile?.companyDescription ?: "") }
    var companyAddress by remember { mutableStateOf(profile?.companyAddress ?: "") }
    var companyPhone by remember { mutableStateOf(profile?.companyPhone ?: "") }
    var companyWebsite by remember { mutableStateOf(profile?.companyWebsite ?: "") }
    var companyFacebook by remember { mutableStateOf(profile?.companyFacebook ?: "") }
    var companyInstagram by remember { mutableStateOf(profile?.companyInstagram ?: "") }
    var companyLinkedin by remember { mutableStateOf(profile?.companyLinkedin ?: "") }
    var companyTwitter by remember { mutableStateOf(profile?.companyTwitter ?: "") }
    var selectedPhotoUri by remember { mutableStateOf<Uri?>(null) }

    // Actualizar campos cuando se carga el perfil
    LaunchedEffect(profile) {
        profile?.let {
            displayName = it.displayName
            firstName = it.firstName ?: ""
            lastName = it.lastName ?: ""
            email = it.email
            phone = it.phone ?: ""
            bio = it.bio ?: ""
            companyDescription = it.companyDescription ?: ""
            companyAddress = it.companyAddress ?: ""
            companyPhone = it.companyPhone ?: ""
            companyWebsite = it.companyWebsite ?: ""
            companyFacebook = it.companyFacebook ?: ""
            companyInstagram = it.companyInstagram ?: ""
            companyLinkedin = it.companyLinkedin ?: ""
            companyTwitter = it.companyTwitter ?: ""
        }
    }

    // Launcher para seleccionar imagen
    val imagePickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetContent()
    ) { uri: Uri? ->
        uri?.let {
            selectedPhotoUri = it
            viewModel.uploadProfilePhoto(it, context)
        }
    }

    // Mostrar mensaje de éxito y navegar de vuelta cuando se actualice el perfil
    // Usamos updateSuccess para asegurar que los datos ya están cargados
    LaunchedEffect(uiState.updateSuccess) {
        if (uiState.updateSuccess) {
            Toast.makeText(
                context,
                "Perfil actualizado correctamente",
                Toast.LENGTH_SHORT
            ).show()
            navController.popBackStack()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Editar Perfil") },
                navigationIcon = {
                    IconButton(onClick = { navController.popBackStack() }) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Volver")
                    }
                }
            )
        }
    ) { paddingValues ->
        if (uiState.isLoadingProfile) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator()
            }
        } else {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
                    .padding(16.dp)
                    .verticalScroll(rememberScrollState())
            ) {
                // Foto de perfil
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 16.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Box(
                            modifier = Modifier
                                .size(120.dp)
                                .clip(CircleShape)
                                .background(MaterialTheme.colorScheme.primaryContainer),
                            contentAlignment = Alignment.Center
                        ) {
                            when {
                                selectedPhotoUri != null -> {
                                    // Mostrar imagen seleccionada
                                    AsyncImage(
                                        model = ImageRequest.Builder(context)
                                            .data(selectedPhotoUri)
                                            .crossfade(true)
                                            .build(),
                                        contentDescription = "Foto de perfil seleccionada",
                                        modifier = Modifier.fillMaxSize(),
                                        contentScale = ContentScale.Crop
                                    )
                                }
                                profile?.profilePhotoUrl != null -> {
                                    // Mostrar foto actual
                                    AsyncImage(
                                        model = ImageRequest.Builder(context)
                                            .data(profile.profilePhotoUrl)
                                            .crossfade(true)
                                            .build(),
                                        contentDescription = "Foto de perfil",
                                        modifier = Modifier.fillMaxSize(),
                                        contentScale = ContentScale.Crop
                                    )
                                }
                                else -> {
                                    // Icono por defecto
                                    Icon(
                                        Icons.Default.Person,
                                        contentDescription = null,
                                        modifier = Modifier.size(60.dp),
                                        tint = MaterialTheme.colorScheme.onPrimaryContainer
                                    )
                                }
                            }
                        }
                        
                        Row(
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            OutlinedButton(
                                onClick = { imagePickerLauncher.launch("image/*") },
                                enabled = !uiState.isLoading
                            ) {
                                Icon(
                                    Icons.Default.CameraAlt,
                                    contentDescription = null,
                                    modifier = Modifier.size(18.dp)
                                )
                                Spacer(modifier = Modifier.width(4.dp))
                                Text("Cambiar Foto")
                            }
                            
                            if (profile?.profilePhotoUrl != null || selectedPhotoUri != null) {
                                OutlinedButton(
                                    onClick = {
                                        selectedPhotoUri = null
                                        viewModel.deleteProfilePhoto()
                                    },
                                    enabled = !uiState.isLoading,
                                    colors = ButtonDefaults.outlinedButtonColors(
                                        contentColor = MaterialTheme.colorScheme.error
                                    )
                                ) {
                                    Icon(
                                        Icons.Default.Delete,
                                        contentDescription = null,
                                        modifier = Modifier.size(18.dp)
                                    )
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text("Eliminar")
                                }
                            }
                        }
                    }
                }

                Spacer(modifier = Modifier.height(8.dp))

                // Campos del formulario
                OutlinedTextField(
                    value = displayName,
                    onValueChange = { displayName = it },
                    label = { Text("Nombre a mostrar") },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !uiState.isLoading
                )

                Spacer(modifier = Modifier.height(16.dp))

                Row(modifier = Modifier.fillMaxWidth()) {
                    OutlinedTextField(
                        value = firstName,
                        onValueChange = { firstName = it },
                        label = { Text("Nombre") },
                        modifier = Modifier.weight(1f),
                        enabled = !uiState.isLoading
                    )
                    Spacer(modifier = Modifier.width(8.dp))
                    OutlinedTextField(
                        value = lastName,
                        onValueChange = { lastName = it },
                        label = { Text("Apellido") },
                        modifier = Modifier.weight(1f),
                        enabled = !uiState.isLoading
                    )
                }

                Spacer(modifier = Modifier.height(16.dp))

                OutlinedTextField(
                    value = email,
                    onValueChange = { email = it },
                    label = { Text("Correo electrónico") },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !uiState.isLoading
                )

                Spacer(modifier = Modifier.height(16.dp))

                OutlinedTextField(
                    value = phone,
                    onValueChange = { phone = it },
                    label = { Text("Teléfono") },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !uiState.isLoading
                )

                Spacer(modifier = Modifier.height(16.dp))

                OutlinedTextField(
                    value = bio,
                    onValueChange = { bio = it },
                    label = { Text("Biografía") },
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(120.dp),
                    maxLines = 5,
                    enabled = !uiState.isLoading
                )

                // Si es empresa, mostrar campo de descripción
                if (profile?.isEnterprise == true) {
                    Spacer(modifier = Modifier.height(16.dp))
                    
                    Text(
                        text = "Descripción de la Empresa",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier.padding(bottom = 8.dp)
                    )
                    
                    OutlinedTextField(
                        value = companyDescription,
                        onValueChange = { companyDescription = it },
                        label = { Text("Acerca de tu empresa") },
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(150.dp),
                        maxLines = 8,
                        enabled = !uiState.isLoading
                    )
                    
                    Spacer(modifier = Modifier.height(16.dp))
                    
                    OutlinedTextField(
                        value = companyAddress,
                        onValueChange = { companyAddress = it },
                        label = { Text("Dirección") },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !uiState.isLoading,
                        placeholder = { Text("Ej: Av. Principal 123, Lima, Perú") }
                    )
                    
                    Spacer(modifier = Modifier.height(16.dp))
                    
                    OutlinedTextField(
                        value = companyPhone,
                        onValueChange = { companyPhone = it },
                        label = { Text("Teléfono de la empresa") },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !uiState.isLoading
                    )
                    
                    Spacer(modifier = Modifier.height(16.dp))
                    
                    OutlinedTextField(
                        value = companyWebsite,
                        onValueChange = { companyWebsite = it },
                        label = { Text("Sitio web") },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !uiState.isLoading,
                        placeholder = { Text("https://www.ejemplo.com") }
                    )
                    
                    Spacer(modifier = Modifier.height(16.dp))
                    
                    Text(
                        text = "Redes Sociales",
                        style = MaterialTheme.typography.titleSmall,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier.padding(bottom = 8.dp)
                    )
                    
                    OutlinedTextField(
                        value = companyFacebook,
                        onValueChange = { companyFacebook = it },
                        label = { Text("Facebook") },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !uiState.isLoading,
                        placeholder = { Text("https://facebook.com/empresa") }
                    )
                    
                    Spacer(modifier = Modifier.height(12.dp))
                    
                    OutlinedTextField(
                        value = companyInstagram,
                        onValueChange = { companyInstagram = it },
                        label = { Text("Instagram") },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !uiState.isLoading,
                        placeholder = { Text("https://instagram.com/empresa") }
                    )
                    
                    Spacer(modifier = Modifier.height(12.dp))
                    
                    OutlinedTextField(
                        value = companyLinkedin,
                        onValueChange = { companyLinkedin = it },
                        label = { Text("LinkedIn") },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !uiState.isLoading,
                        placeholder = { Text("https://linkedin.com/company/empresa") }
                    )
                    
                    Spacer(modifier = Modifier.height(12.dp))
                    
                    OutlinedTextField(
                        value = companyTwitter,
                        onValueChange = { companyTwitter = it },
                        label = { Text("Twitter") },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !uiState.isLoading,
                        placeholder = { Text("https://twitter.com/empresa") }
                    )
                }

                Spacer(modifier = Modifier.height(24.dp))

                // Botón guardar
                if (uiState.isLoading) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.Center,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        CircularProgressIndicator(modifier = Modifier.size(24.dp))
                        Spacer(modifier = Modifier.width(16.dp))
                        Text("Guardando...")
                    }
                } else {
                    Button(
                        onClick = {
                            val profileData = mutableMapOf<String, Any>(
                                "display_name" to displayName,
                                "first_name" to firstName,
                                "last_name" to lastName,
                                "email" to email,
                                "phone" to phone,
                                "bio" to bio
                            )
                            
                            // Si es empresa, agregar información de empresa
                            if (profile?.isEnterprise == true) {
                                profileData["company_description"] = companyDescription
                                profileData["company_address"] = companyAddress
                                profileData["company_phone"] = companyPhone
                                profileData["company_website"] = companyWebsite
                                profileData["company_facebook"] = companyFacebook
                                profileData["company_instagram"] = companyInstagram
                                profileData["company_linkedin"] = companyLinkedin
                                profileData["company_twitter"] = companyTwitter
                            }

                            viewModel.updateProfile(profileData)
                        },
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(48.dp)
                    ) {
                        Text("Guardar Cambios", style = MaterialTheme.typography.titleMedium)
                    }
                }

                // Mostrar error si existe
                uiState.error?.let { error ->
                    Spacer(modifier = Modifier.height(16.dp))
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        colors = CardDefaults.cardColors(
                            containerColor = MaterialTheme.colorScheme.errorContainer
                        )
                    ) {
                        Text(
                            text = error,
                            color = MaterialTheme.colorScheme.onErrorContainer,
                            modifier = Modifier.padding(16.dp)
                        )
                    }
                }
            }
        }
    }
}

