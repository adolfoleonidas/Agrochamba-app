package agrochamba.com.util

import retrofit2.HttpException

/**
 * Mapea errores de WordPress API a mensajes amigables para el usuario.
 */
object WordPressErrorMapper {
    fun mapAuthError(ex: Throwable): Exception {
        return when (ex) {
            is HttpException -> {
                when (ex.code()) {
                    400 -> Exception("Datos inválidos. Verifica tu información e inténtalo de nuevo.")
                    401 -> Exception("Correo electrónico o contraseña incorrectos.")
                    403 -> Exception("No tienes permiso para realizar esta acción.")
                    404 -> Exception("Usuario no encontrado.")
                    429 -> Exception("Demasiados intentos. Inténtalo más tarde.")
                    500, 502, 503 -> Exception("Error del servidor. Inténtalo más tarde.")
                    else -> {
                        // Intentar leer el mensaje del cuerpo de la respuesta
                        val errorMessage = try {
                            ex.response()?.errorBody()?.string() ?: ex.message()
                        } catch (e: Exception) {
                            ex.message()
                        }
                        Exception(errorMessage ?: "Error de autenticación. Inténtalo de nuevo.")
                    }
                }
            }
            is java.net.UnknownHostException -> Exception("Sin conexión a internet. Revisa tu conexión e inténtalo de nuevo.")
            is java.net.SocketTimeoutException -> Exception("Tiempo de espera agotado. Inténtalo de nuevo.")
            else -> Exception(ex.message ?: "Error inesperado. Inténtalo de nuevo.")
        }
    }
    
    fun mapResetError(ex: Throwable): Exception {
        return when (ex) {
            is HttpException -> {
                when (ex.code()) {
                    400 -> Exception("Usuario o correo no puede estar vacío.")
                    404 -> Exception("Si el usuario existe, se ha enviado un código de 6 dígitos a tu correo electrónico.")
                    429 -> Exception("Demasiados intentos. Inténtalo más tarde.")
                    500, 502, 503 -> Exception("Error del servidor. Inténtalo más tarde.")
                    else -> {
                        // Intentar leer el mensaje del cuerpo de la respuesta
                        val errorMessage = try {
                            ex.response()?.errorBody()?.string() ?: ex.message()
                        } catch (e: Exception) {
                            ex.message()
                        }
                        Exception(errorMessage ?: "Error al enviar el código de restablecimiento. Por favor, intenta nuevamente.")
                    }
                }
            }
            is java.net.UnknownHostException -> Exception("Sin conexión a internet. Revisa tu conexión e inténtalo de nuevo.")
            is java.net.SocketTimeoutException -> Exception("Tiempo de espera agotado. Inténtalo de nuevo.")
            else -> Exception(ex.message ?: "Error de conexión. Por favor, verifica tu conexión a internet e intenta nuevamente.")
        }
    }
}

