package agrochamba.com.domain.usecase.auth

import agrochamba.com.domain.repository.UserRepository
import agrochamba.com.util.Result
import android.util.Patterns

/**
 * Caso de uso para enviar correo de restablecimiento de contraseña.
 */
class SendPasswordResetUseCase(
    private val userRepository: UserRepository
) {
    suspend operator fun invoke(email: String): Result<Unit> {
        val trimmedEmail = email.trim()
        
        if (trimmedEmail.isBlank()) {
            return Result.Error(Exception("Ingresa tu correo electrónico."))
        }
        
        if (!Patterns.EMAIL_ADDRESS.matcher(trimmedEmail).matches()) {
            return Result.Error(Exception("Correo electrónico inválido."))
        }
        
        return userRepository.sendPasswordReset(trimmedEmail)
    }
}

