package agrochamba.com

import android.os.Bundle
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
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
import androidx.compose.ui.viewinterop.AndroidView
import agrochamba.com.BuildConfig
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
import agrochamba.com.ui.auth.CompanyRegisterScreen
import agrochamba.com.ui.auth.ForgotPasswordScreen
import agrochamba.com.ui.auth.LoginScreen
import agrochamba.com.ui.auth.EditProfileScreen
import agrochamba.com.ui.auth.ProfileScreen
import agrochamba.com.ui.auth.RegisterChoiceScreen
import agrochamba.com.ui.auth.RegisterScreen
import agrochamba.com.ui.jobs.CompanyProfileScreen
import agrochamba.com.ui.jobs.CreateJobScreen
import agrochamba.com.ui.jobs.EditJobScreen
import agrochamba.com.ui.jobs.JobDetailScreen
import agrochamba.com.ui.jobs.JobsScreen
import agrochamba.com.ui.jobs.ModerationScreen
import agrochamba.com.ui.jobs.MyJobsScreen
import agrochamba.com.ui.theme.AgrochambaTheme

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
}

val bottomBarItems = listOf(Screen.Jobs, Screen.Routes, Screen.Dates, Screen.Rooms, Screen.Profile)

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        AuthManager.init(this)
        setContent {
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

    if (isInitializing) {
        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            CircularProgressIndicator()
        }
    } else if (isLoggedIn) {
        MainAppScreen()
    } else {
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
    val navController = rememberNavController()
    // Observar directamente el estado de roles para que Compose detecte cambios
    // Leer userRoles directamente - Compose detectará cambios porque es un mutableStateOf
    val userRoles = AuthManager.userRoles
    val isUserEnterprise = remember(userRoles) { 
        userRoles.contains("employer") || userRoles.contains("administrator")
    }
    
    // Log para debugging
    LaunchedEffect(isUserEnterprise, userRoles) {
        android.util.Log.d("MainAppScreen", "isUserEnterprise: $isUserEnterprise")
        android.util.Log.d("MainAppScreen", "Roles del usuario: $userRoles")
    }

    Scaffold(
        bottomBar = {
            BottomAppBar {
                val navBackStackEntry by navController.currentBackStackEntryAsState()
                val currentDestination = navBackStackEntry?.destination

                bottomBarItems.forEach { screen ->
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
        },
        floatingActionButton = {
            if (isUserEnterprise) {
                FloatingActionButton(onClick = { navController.navigate(Screen.CreateJob.route) }) {
                    Icon(Icons.Default.Add, contentDescription = "Publicar Anuncio")
                }
            }
        }
    ) { innerPadding ->
        NavHost(navController, startDestination = Screen.Jobs.route, Modifier.padding(innerPadding)) {
            composable(Screen.Jobs.route) { JobsScreen() }
            composable(Screen.Routes.route) { WebViewScreen(url = BuildConfig.WEB_ROUTES_URL) }
            composable(Screen.Dates.route) { WebViewScreen(url = BuildConfig.WEB_DATES_URL) }
            composable(Screen.Rooms.route) { WebViewScreen(url = BuildConfig.WEB_ROOMS_URL) }
            composable(Screen.Profile.route) { ProfileScreen(navController) } 
            composable(Screen.CreateJob.route) { CreateJobScreen(navController) } 
            composable(Screen.PrivacyPolicy.route) { WebViewScreen(url = BuildConfig.PRIVACY_URL) }
            
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
            composable("${Screen.CompanyProfile.route}/{companyName}") { backStackEntry ->
                val companyName = backStackEntry.arguments?.getString("companyName") ?: ""
                CompanyProfileScreen(
                    companyName = companyName,
                    navController = navController
                )
            }
            composable(Screen.Moderation.route) {
                ModerationScreen(navController = navController)
            }
        }
    }
}

@Composable
fun WebViewScreen(url: String) {
    AndroidView(factory = {
        WebView(it).apply {
            webViewClient = WebViewClient()
            // Endurecimiento de WebView
            settings.javaScriptEnabled = true
            settings.domStorageEnabled = true
            settings.allowFileAccess = false
            settings.allowContentAccess = false
            settings.mixedContentMode = android.webkit.WebSettings.MIXED_CONTENT_NEVER_ALLOW
            try { WebView.setWebContentsDebuggingEnabled(false) } catch (_: Throwable) {}
            try { settings.safeBrowsingEnabled = true } catch (_: Throwable) {}
            loadUrl(url)
        }
    })
}
