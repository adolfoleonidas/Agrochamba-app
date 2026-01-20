package agrochamba.com

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import dagger.hilt.android.AndroidEntryPoint
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.BedroomParent
import androidx.compose.material.icons.filled.DateRange
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Route
import androidx.compose.material.icons.filled.Work
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.derivedStateOf
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import androidx.navigation.NavDestination.Companion.hierarchy
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import agrochamba.com.data.AppDataHolder
import agrochamba.com.data.AuthManager
import agrochamba.com.data.SettingsManager
import agrochamba.com.ui.auth.CompanyRegisterScreen
import agrochamba.com.ui.auth.ForgotPasswordScreen
import agrochamba.com.ui.auth.LoginScreen
import agrochamba.com.ui.auth.EditProfileScreen
import agrochamba.com.ui.auth.ProfileScreen
import agrochamba.com.ui.auth.RegisterChoiceScreen
import agrochamba.com.ui.auth.RegisterScreen
import agrochamba.com.ui.auth.SettingsScreen
import agrochamba.com.ui.auth.FacebookPagesScreen
import agrochamba.com.ui.jobs.CompanyProfileScreen
import agrochamba.com.ui.jobs.CreateJobScreen
import agrochamba.com.ui.jobs.EditJobScreen
import agrochamba.com.ui.jobs.EditJobByIdScreen
import agrochamba.com.ui.jobs.FavoritesScreen
import agrochamba.com.ui.jobs.JobDetailScreen
import agrochamba.com.ui.jobs.JobsScreen
import agrochamba.com.ui.moderation.ModerationScreen
import agrochamba.com.ui.jobs.MyJobsScreen
import agrochamba.com.ui.jobs.SavedScreen
import agrochamba.com.ui.theme.AgrochambaTheme
import agrochamba.com.ui.WebViewScreen

sealed class Screen(val route: String, val label: String? = null, val icon: ImageVector? = null) {
    object Jobs : Screen("jobs", "Chambas", Icons.Default.Work)
    object Routes : Screen("routes", "Rutas", Icons.Default.Route)
    object Dates : Screen("dates", "Fechas", Icons.Default.DateRange)
    object Rooms : Screen("rooms", "Cuartos", Icons.Default.BedroomParent)
    object Profile : Screen("profile", "Perfil", Icons.Default.Person)

    object Login : Screen("login") 
    object RegisterChoice : Screen("register_choice")
    object Register : Screen("register")
    object CompanyRegister : Screen("company_register")
    object ForgotPassword : Screen("forgot_password")
    object CreateJob : Screen("create_job")
    object PrivacyPolicy : Screen("privacy")
    object MyJobDetail : Screen("my_job_detail")
    object EditProfile : Screen("edit_profile")
    object MyJobs : Screen("my_jobs")
    object EditJob : Screen("edit_job")
    object CompanyProfile : Screen("company_profile/{companyName}")
    object Moderation : Screen("moderation")
    object Favorites : Screen("favorites")
    object Saved : Screen("saved")
    object Settings : Screen("settings")
    object FacebookPages : Screen("facebook_pages")
    object SedesManagement : Screen("sedes_management")
}

// Bottom bar items según tipo de usuario:
// - Usuarios normales: 5 items (con Cuartos)
// - Empresas/Admin: 4 items + FAB en el centro
val bottomBarItemsNormal = listOf(Screen.Jobs, Screen.Routes, Screen.Dates, Screen.Rooms, Screen.Profile)
val bottomBarItemsEnterpriseLeft = listOf(Screen.Jobs, Screen.Routes)
val bottomBarItemsEnterpriseRight = listOf(Screen.Dates, Screen.Profile)

@AndroidEntryPoint
class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        // Handler global de excepciones para debugging
        Thread.setDefaultUncaughtExceptionHandler { thread, throwable ->
            android.util.Log.e("CRASH_HANDLER", "💥💥💥 APP CRASH 💥💥💥")
            android.util.Log.e("CRASH_HANDLER", "Thread: ${thread.name}")
            android.util.Log.e("CRASH_HANDLER", "Exception: ${throwable.javaClass.simpleName}")
            android.util.Log.e("CRASH_HANDLER", "Message: ${throwable.message}")
            android.util.Log.e("CRASH_HANDLER", "StackTrace:", throwable)
            throwable.cause?.let { cause ->
                android.util.Log.e("CRASH_HANDLER", "Caused by: ${cause.javaClass.simpleName}")
                android.util.Log.e("CRASH_HANDLER", "Cause message: ${cause.message}")
                android.util.Log.e("CRASH_HANDLER", "Cause StackTrace:", cause)
            }
            // Re-throw para que el sistema maneje el crash normalmente
            throw throwable
        }
        
        android.util.Log.d("MainActivity", "🚀 onCreate iniciado")
        AuthManager.init(this)
        android.util.Log.d("MainActivity", "✅ AuthManager inicializado")
        SettingsManager.init(this)
        android.util.Log.d("MainActivity", "✅ SettingsManager inicializado")
        
        setContent {
            android.util.Log.d("MainActivity", "🎨 setContent ejecutando")
            AgrochambaTheme {
                AppEntry()
            }
        }
    }
}

@Composable
fun AppEntry() {
    val isInitializing = AuthManager.isInitializing
    val isLoggedIn = AuthManager.isLoggedIn

    // Logs de diagnóstico visibles en Logcat
    LaunchedEffect(isInitializing, isLoggedIn) {
        android.util.Log.d(
            "AppEntry",
            "🔄 state => isInitializing=${isInitializing}, isLoggedIn=${isLoggedIn}, token=${AuthManager.token?.take(20)}..., displayName=${AuthManager.userDisplayName}"
        )
    }

    // UI de diagnóstico (visible para el usuario) mientras inicializa
    if (isInitializing) {
        android.util.Log.d("AppEntry", "⏳ Mostrando pantalla de inicialización...")
        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                CircularProgressIndicator()
                Text(
                    text = "Inicializando sesión…",
                    style = MaterialTheme.typography.bodyMedium,
                    modifier = Modifier.padding(top = 12.dp)
                )
                // Información adicional para depuración
                Text(
                    text = "Esperando AuthManager",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }
    } else if (isLoggedIn) {
        android.util.Log.d("AppEntry", "✅ Usuario logueado, mostrando MainAppScreen...")
        MainAppScreen()
    } else {
        android.util.Log.d("AppEntry", "🔐 Usuario no logueado, mostrando AuthNavigator...")
        AuthNavigator()
    }
}

@Composable
fun AuthNavigator() {
    val navController = rememberNavController()
    NavHost(navController = navController, startDestination = Screen.Login.route) {
        composable(Screen.Login.route) { LoginScreen(navController) }
        composable(Screen.RegisterChoice.route) { RegisterChoiceScreen(navController) }
        composable(Screen.Register.route) { RegisterScreen(navController) }
        composable(Screen.CompanyRegister.route) { CompanyRegisterScreen(navController) }
        composable(Screen.ForgotPassword.route) { ForgotPasswordScreen(navController) }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MainAppScreen() {
    android.util.Log.d("MainAppScreen", "🏠 MainAppScreen() INICIADO")
    
    val navController = rememberNavController()
    android.util.Log.d("MainAppScreen", "✅ NavController creado")
    
    // Observar directamente el estado de roles para que Compose detecte cambios
    // Leer userRoles directamente - Compose detectará cambios porque es un mutableStateOf
    val userRoles = AuthManager.userRoles
    android.util.Log.d("MainAppScreen", "📋 UserRoles: $userRoles")
    
    val isUserEnterprise = remember(userRoles) { 
        userRoles.contains("employer") || userRoles.contains("administrator")
    }
    android.util.Log.d("MainAppScreen", "🏢 isUserEnterprise: $isUserEnterprise")
    
    // Log para debugging
    LaunchedEffect(isUserEnterprise, userRoles) {
        android.util.Log.d("MainAppScreen", "🔄 LaunchedEffect - isUserEnterprise: $isUserEnterprise, Roles: $userRoles")
    }

    // Detectar la ruta actual para ocultar el FAB en ciertas pantallas
    val navBackStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = navBackStackEntry?.destination?.route
    val currentDestination = navBackStackEntry?.destination
    
    // Ocultar FAB cuando ya estamos en crear trabajo o editar trabajo
    val showFab = isUserEnterprise && currentRoute != Screen.CreateJob.route && 
                  currentRoute != Screen.EditJob.route && 
                  !currentRoute.orEmpty().startsWith("${Screen.EditJob.route}/")

    Scaffold(
        bottomBar = {
            NavigationBar {
                if (isUserEnterprise) {
                    // EMPRESAS/ADMIN: Layout con FAB en el centro
                    // Items de la izquierda (Chambas, Rutas)
                    bottomBarItemsEnterpriseLeft.forEach { screen ->
                        NavigationBarItem(
                            icon = { Icon(screen.icon!!, contentDescription = screen.label) },
                            label = { Text(screen.label!!) },
                            selected = currentDestination?.hierarchy?.any { it.route == screen.route } == true,
                            onClick = { navController.navigate(screen.route) { 
                                popUpTo(navController.graph.findStartDestination().id) { saveState = true }
                                launchSingleTop = true
                                restoreState = true
                            } }
                        )
                    }
                    
                    // FAB en el centro (solo cuando no están en crear/editar trabajo)
                    if (showFab) {
                        NavigationBarItem(
                            icon = { 
                                Box(
                                    modifier = Modifier
                                        .size(48.dp)
                                        .background(
                                            MaterialTheme.colorScheme.primary,
                                            shape = CircleShape
                                        ),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Icon(
                                        Icons.Default.Add, 
                                        contentDescription = "Publicar",
                                        tint = MaterialTheme.colorScheme.onPrimary
                                    )
                                }
                            },
                            label = { Text("Publicar") },
                            selected = currentRoute == Screen.CreateJob.route,
                            onClick = { 
                                navController.navigate(Screen.CreateJob.route) {
                                    launchSingleTop = true
                                }
                            }
                        )
                    }
                    
                    // Items de la derecha (Fechas, Perfil)
                    bottomBarItemsEnterpriseRight.forEach { screen ->
                        NavigationBarItem(
                            icon = { Icon(screen.icon!!, contentDescription = screen.label) },
                            label = { Text(screen.label!!) },
                            selected = currentDestination?.hierarchy?.any { it.route == screen.route } == true,
                            onClick = { navController.navigate(screen.route) { 
                                popUpTo(navController.graph.findStartDestination().id) { saveState = true }
                                launchSingleTop = true
                                restoreState = true
                            } }
                        )
                    }
                } else {
                    // USUARIOS NORMALES: 5 items (con Cuartos)
                    bottomBarItemsNormal.forEach { screen ->
                        NavigationBarItem(
                            icon = { Icon(screen.icon!!, contentDescription = screen.label) },
                            label = { Text(screen.label!!) },
                            selected = currentDestination?.hierarchy?.any { it.route == screen.route } == true,
                            onClick = { navController.navigate(screen.route) { 
                                popUpTo(navController.graph.findStartDestination().id) { saveState = true }
                                launchSingleTop = true
                                restoreState = true
                            } }
                        )
                    }
                }
            }
        }
    ) { innerPadding ->
        NavHost(navController, startDestination = Screen.Jobs.route, Modifier.padding(innerPadding)) {
            composable(Screen.Jobs.route) { JobsScreen() }
            composable(Screen.Routes.route) { WebViewScreen(url = "https://agrobus.agrochamba.com/") }
            composable(Screen.Dates.route) { WebViewScreen(url = "https://agrochamba.com/wp-content/fechas.html") }
            composable(Screen.Rooms.route) { WebViewScreen(url = "https://cuartos.agrochamba.com/") }
            composable(Screen.Profile.route) { ProfileScreen(navController) } 
            composable(Screen.CreateJob.route) { CreateJobScreen(navController) } 
            composable(Screen.PrivacyPolicy.route) { WebViewScreen(url = "https://agrochamba.com/politica-de-privacidad/") }
            
            composable(Screen.MyJobDetail.route) {
                AppDataHolder.selectedJob?.let { job ->
                    JobDetailScreen(
                        job = job,
                        mediaItems = job.embedded?.featuredMedia ?: emptyList(),
                        onNavigateUp = { navController.popBackStack() },
                        navController = navController
                    )
                } ?: navController.popBackStack()
            }
            composable(Screen.EditProfile.route) {
                EditProfileScreen(navController = navController)
            }
            composable(Screen.MyJobs.route) {
                MyJobsScreen(navController = navController)
            }
            composable(Screen.EditJob.route) {
                AppDataHolder.selectedJob?.let { job ->
                    EditJobScreen(
                        job = job,
                        navController = navController
                    )
                } ?: navController.popBackStack()
            }
            // Ruta para editar trabajo por ID (usado por moderación)
            composable("${Screen.EditJob.route}/{jobId}") { backStackEntry ->
                val jobId = backStackEntry.arguments?.getString("jobId")?.toIntOrNull()
                if (jobId != null) {
                    // Cargar el trabajo por ID y mostrar la pantalla de edición
                    EditJobByIdScreen(
                        jobId = jobId,
                        navController = navController
                    )
                } else {
                    navController.popBackStack()
                }
            }
            composable("${Screen.CompanyProfile.route}/{companyName}") { backStackEntry ->
                val companyName = backStackEntry.arguments?.getString("companyName") ?: ""
                CompanyProfileScreen(
                    companyName = companyName,
                    navController = navController
                )
            }
            composable(Screen.Moderation.route) {
                agrochamba.com.ui.moderation.ModerationScreen(
                    onNavigateBack = { navController.popBackStack() },
                    onNavigateToEditJob = { jobId -> 
                        navController.navigate("${Screen.EditJob.route}/$jobId")
                    }
                )
            }
            composable(Screen.Favorites.route) {
                FavoritesScreen(navController = navController)
            }
            composable(Screen.Saved.route) {
                SavedScreen(navController = navController)
            }
            composable(Screen.Settings.route) {
                SettingsScreen(navController = navController)
            }
            composable(Screen.FacebookPages.route) {
                FacebookPagesScreen(navController = navController)
            }
            composable(Screen.SedesManagement.route) {
                agrochamba.com.ui.company.SedesManagementScreen(navController = navController)
            }
        }
    }
}

