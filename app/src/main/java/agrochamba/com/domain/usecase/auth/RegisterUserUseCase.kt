package agrochamba.com.domain.usecase.auth

import agrochamba.com.data.AuthManager
import agrochamba.com.domain.repository.UserRepository
import agrochamba.com.util.Result

/**
 * Caso de uso para registrar un usuario normal (worker) usando WordPress API.
 */
class RegisterUserUseCase(
    private val userRepository: UserRepository
) {
    suspend operator fun invoke(
        username: String,
        email: String,
        password: String
    ): Result<Unit> {
        // Validaciones
        if (username.isBlank()) {
            return Result.Error(Exception("El nombre de usuario es obligatorio."))
        }
        if (email.isBlank()) {
            return Result.Error(Exception("El correo electrónico es obligatorio."))
        }
        if (password.isBlank()) {
            return Result.Error(Exception("La contraseña es obligatoria."))
        }
        if (password.length < 6) {
            return Result.Error(Exception("La contraseña debe tener al menos 6 caracteres."))
        }
        if (username.length < 3) {
            return Result.Error(Exception("El nombre de usuario debe tener al menos 3 caracteres."))
        }
        
        // Registrar usuario en WordPress
        val authResult = userRepository.registerUser(
            username.trim(),
            email.trim(),
            password
        )
        
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

