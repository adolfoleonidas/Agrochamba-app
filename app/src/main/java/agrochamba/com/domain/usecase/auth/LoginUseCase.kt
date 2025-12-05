package agrochamba.com.domain.usecase.auth

import agrochamba.com.data.AuthManager
import agrochamba.com.domain.repository.UserRepository
import agrochamba.com.util.Result

/**
 * Caso de uso para iniciar sesión usando WordPress API.
 * Maneja la lógica de negocio del login.
 */
class LoginUseCase(
    private val userRepository: UserRepository
) {
    suspend operator fun invoke(usernameOrEmail: String, password: String): Result<Unit> {
        // Validar que se haya ingresado algo
        if (usernameOrEmail.isBlank()) {
            return Result.Error(Exception("Ingresa tu usuario o correo electrónico."))
        }
        
        // Autenticar con WordPress (acepta username o email)
        val authResult = userRepository.login(usernameOrEmail.trim(), password)
        
        if (authResult is Result.Success) {
            val tokenResponse = authResult.data
            
            // Guardar sesión en AuthManager
            AuthManager.login(tokenResponse)
        }
        
        return if (authResult is Result.Success) {
            Result.Success(Unit)
        } else {
            authResult as Result.Error
        }
    }
}

