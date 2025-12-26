package agrochamba.com.domain.usecase.auth

import agrochamba.com.domain.repository.UserRepository
import agrochamba.com.util.Result

/**
 * Caso de uso para enviar código de restablecimiento de contraseña.
 * Acepta tanto correo electrónico como nombre de usuario.
 */
class SendPasswordResetUseCase(
    private val userRepository: UserRepository
) {
    suspend operator fun invoke(userLogin: String): Result<Unit> {
        val trimmedLogin = userLogin.trim()
        
        if (trimmedLogin.isBlank()) {
            return Result.Error(Exception("Por favor, ingresa tu usuario o correo electrónico."))
        }
        
        return userRepository.sendPasswordReset(trimmedLogin)
    }
}

