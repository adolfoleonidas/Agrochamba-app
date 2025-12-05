package agrochamba.com.domain.usecase.auth

import agrochamba.com.data.AuthManager
import agrochamba.com.domain.repository.UserRepository
import agrochamba.com.util.Result

/**
 * Caso de uso para registrar una empresa usando WordPress API.
 */
class RegisterCompanyUseCase(
    private val userRepository: UserRepository
) {
    suspend operator fun invoke(
        username: String,
        email: String,
        password: String,
        ruc: String,
        razonSocial: String
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
        if (ruc.isBlank()) {
            return Result.Error(Exception("El RUC es obligatorio."))
        }
        if (razonSocial.isBlank()) {
            return Result.Error(Exception("La razón social es obligatoria."))
        }
        if (password.length < 6) {
            return Result.Error(Exception("La contraseña debe tener al menos 6 caracteres."))
        }
        if (username.length < 3) {
            return Result.Error(Exception("El nombre de usuario debe tener al menos 3 caracteres."))
        }
        if (!ruc.all { it.isDigit() } || ruc.length < 8) {
            return Result.Error(Exception("El RUC debe ser numérico y tener al menos 8 dígitos."))
        }
        
        // Registrar empresa en WordPress
        val authResult = userRepository.registerCompany(
            username.trim(),
            email.trim(),
            password,
            ruc.trim(),
            razonSocial.trim()
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

