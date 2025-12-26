package agrochamba.com.data.repository

import agrochamba.com.data.TokenResponse
import agrochamba.com.data.WordPressApi
import agrochamba.com.domain.repository.UserRepository
import agrochamba.com.util.Result
import retrofit2.HttpException

/**
 * Implementación del repositorio de usuarios usando WordPress API.
 */
class UserRepositoryImpl : UserRepository {

    override suspend fun login(email: String, password: String): Result<TokenResponse> {
        return try {
            // Intentar primero con el endpoint personalizado de AgroChamba
            val response = WordPressApi.retrofitService.customLogin(
                mapOf(
                    "username" to email,
                    "password" to password
                )
            )
            Result.Success(response)
        } catch (e: HttpException) {
            // Si falla, intentar con el endpoint estándar de JWT
            try {
                val response = WordPressApi.retrofitService.login(
                    mapOf(
                        "username" to email,
                        "password" to password
                    )
                )
                Result.Success(response)
            } catch (e2: Exception) {
                Result.Error(e2)
            }
        } catch (e: Exception) {
            Result.Error(e)
        }
    }

    override suspend fun registerUser(
        username: String,
        email: String,
        password: String
    ): Result<TokenResponse> {
        return try {
            val response = WordPressApi.retrofitService.registerUser(
                mapOf(
                    "username" to username,
                    "email" to email,
                    "password" to password
                )
            )
            Result.Success(response)
        } catch (e: Exception) {
            Result.Error(e)
        }
    }

    override suspend fun registerCompany(
        username: String,
        email: String,
        password: String,
        ruc: String,
        razonSocial: String
    ): Result<TokenResponse> {
        return try {
            val response = WordPressApi.retrofitService.registerCompany(
                mapOf(
                    "username" to username,
                    "email" to email,
                    "password" to password,
                    "ruc" to ruc,
                    "razon_social" to razonSocial
                )
            )
            Result.Success(response)
        } catch (e: Exception) {
            Result.Error(e)
        }
    }

    override suspend fun sendPasswordReset(email: String): Result<Unit> {
        return try {
            val response = WordPressApi.retrofitService.forgotPassword(
                mapOf("user_login" to email)
            )
            if (response.isSuccessful) {
                Result.Success(Unit)
            } else {
                // Intentar leer el mensaje de error del cuerpo de la respuesta
                val errorMessage = try {
                    response.errorBody()?.string() ?: "Error al enviar el código de restablecimiento"
                } catch (e: Exception) {
                    "Error al enviar el código de restablecimiento"
                }
                Result.Error(Exception(errorMessage))
            }
        } catch (e: Exception) {
            Result.Error(e)
        }
    }
}

