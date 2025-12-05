package agrochamba.com.domain.repository

import agrochamba.com.data.TokenResponse
import agrochamba.com.util.Result

/**
 * Interfaz del repositorio de usuarios.
 * Define las operaciones relacionadas con usuarios y autenticación usando WordPress API.
 */
interface UserRepository {
    suspend fun login(email: String, password: String): Result<TokenResponse>
    suspend fun registerUser(username: String, email: String, password: String): Result<TokenResponse>
    suspend fun registerCompany(username: String, email: String, password: String, ruc: String, razonSocial: String): Result<TokenResponse>
    suspend fun sendPasswordReset(email: String): Result<Unit>
}

