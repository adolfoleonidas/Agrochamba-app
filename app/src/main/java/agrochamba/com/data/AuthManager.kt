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
    private const val KEY_COMPANY_ID = "user_company_id"

    private var prefs: SharedPreferences? = null
    private val scope = CoroutineScope(Dispatchers.Main + SupervisorJob())

    var token by mutableStateOf<String?>(null)
    var userDisplayName by mutableStateOf<String?>(null)
    private var _userRoles by mutableStateOf<List<String>>(emptyList())
    var userRoles: List<String>
        get() = _userRoles
        set(value) {
            _userRoles = value
            // Guardar roles en SharedPreferences cuando cambian
            scope.launch(Dispatchers.IO) {
                prefs?.edit()?.apply {
                    putStringSet(KEY_ROLES, value.toSet())
                    apply()
                }
            }
        }
    var userCompanyId by mutableStateOf<Int?>(null)
    var isInitializing by mutableStateOf(true)

    val isLoggedIn: Boolean
        get() = token != null

    fun init(context: Context) {
        prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        loadSession()
    }

    private fun loadSession() {
        scope.launch {
            // Usamos un objeto anónimo para devolver múltiples valores de forma segura
            val sessionData = withContext(Dispatchers.IO) {
                object {
                    val token = prefs?.getString(KEY_TOKEN, null)
                    val name = prefs?.getString(KEY_DISPLAY_NAME, null)
                    val roles = prefs?.getStringSet(KEY_ROLES, emptySet())?.toList() ?: emptyList()
                    val companyId = prefs?.getInt(KEY_COMPANY_ID, -1)
                }
            }
            token = sessionData.token
            userDisplayName = sessionData.name
            _userRoles = sessionData.roles
            userCompanyId = if (sessionData.companyId != -1) sessionData.companyId else null
            isInitializing = false
        }
    }

    fun login(response: TokenResponse) {
        token = response.token
        userDisplayName = response.userDisplayName
        _userRoles = response.roles ?: emptyList()
        userCompanyId = response.userCompanyId

        scope.launch(Dispatchers.IO) {
            prefs?.edit()?.apply {
                putString(KEY_TOKEN, token)
                putString(KEY_DISPLAY_NAME, userDisplayName)
                putStringSet(KEY_ROLES, userRoles.toSet())
                userCompanyId?.let { putInt(KEY_COMPANY_ID, it) } ?: remove(KEY_COMPANY_ID)
                apply()
            }
        }
    }

    fun logout() {
        // Limpiar estado local
        token = null
        userDisplayName = null
        _userRoles = emptyList()
        userCompanyId = null

        scope.launch(Dispatchers.IO) {
            prefs?.edit()?.clear()?.apply()
        }
    }

    fun isUserAnEnterprise(): Boolean {
        return userRoles.contains("employer") || userRoles.contains("administrator")
    }

    // Nueva función para verificar si es admin
    fun isUserAdmin(): Boolean {
        return userRoles.contains("administrator")
    }
}