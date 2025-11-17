package agrochamba.com.data

import android.content.Context
import android.content.SharedPreferences
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

object AuthManager {
    private const val PREFS_NAME = "auth_prefs"
    private const val KEY_TOKEN = "auth_token"
    private const val KEY_DISPLAY_NAME = "user_display_name"
    private const val KEY_ROLES = "user_roles"

    private var prefs: SharedPreferences? = null
    private val scope = CoroutineScope(Dispatchers.Main + SupervisorJob())

    var token by mutableStateOf<String?>(null)
    var userDisplayName by mutableStateOf<String?>(null)
    var userRoles by mutableStateOf<List<String>>(emptyList())
    var isInitializing by mutableStateOf(true)

    val isLoggedIn: Boolean
        get() = token != null

    fun init(context: Context) {
        prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        loadSession()
    }

    private fun loadSession() {
        scope.launch {
            val (loadedToken, loadedName, loadedRoles) = withContext(Dispatchers.IO) {
                val token = prefs?.getString(KEY_TOKEN, null)
                val name = prefs?.getString(KEY_DISPLAY_NAME, null)
                val roles = prefs?.getStringSet(KEY_ROLES, emptySet())?.toList() ?: emptyList()
                Triple(token, name, roles)
            }
            token = loadedToken
            userDisplayName = loadedName
            userRoles = loadedRoles
            isInitializing = false
        }
    }

    fun login(response: TokenResponse) {
        token = response.token
        userDisplayName = response.userDisplayName
        userRoles = response.roles ?: emptyList()

        android.util.Log.d("AuthManager", "Login - Roles recibidos: ${userRoles}")
        android.util.Log.d("AuthManager", "Login - Es empresa: ${isUserAnEnterprise()}")

        scope.launch(Dispatchers.IO) {
            prefs?.edit()?.apply {
                putString(KEY_TOKEN, token)
                putString(KEY_DISPLAY_NAME, userDisplayName)
                putStringSet(KEY_ROLES, userRoles.toSet())
                apply()
            }
        }
    }

    fun logout() {
        token = null
        userDisplayName = null
        userRoles = emptyList()

        scope.launch(Dispatchers.IO) {
            prefs?.edit()?.clear()?.apply()
        }
    }

    fun isUserAnEnterprise(): Boolean {
        val isEnterprise = userRoles.contains("employer") || userRoles.contains("administrator")
        android.util.Log.d("AuthManager", "isUserAnEnterprise - Roles actuales: $userRoles, Es empresa: $isEnterprise")
        return isEnterprise
    }
    
    fun isUserAdmin(): Boolean {
        return userRoles.contains("administrator")
    }
}