package agrochamba.com.util

import retrofit2.HttpException

/**
 * Mapea errores de WordPress API a mensajes amigables para el usuario.
 * 
 * IMPORTANTE: Para errores de registro, usamos mensajes genéricos para 
 * evitar enumeration attacks (no revelar si un email/usuario ya existe).
 */
object WordPressErrorMapper {
    
    /**
     * Mapea errores de autenticación (login)
     */
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
    
    /**
     * Mapea errores de registro de usuario/empresa.
     * 
     * SEGURIDAD: No revelamos si un email/usuario ya existe para evitar
     * ataques de enumeración. Usamos mensajes genéricos.
     */
    fun mapRegisterError(ex: Throwable): Exception {
        return when (ex) {
            is HttpException -> {
                val errorBody = try {
                    ex.response()?.errorBody()?.string()?.lowercase() ?: ""
                } catch (e: Exception) { "" }
                
                when (ex.code()) {
                    400 -> {
                        // Analizar el cuerpo del error para dar mensajes útiles pero seguros
                        when {
                            // Email o username ya existe - mensaje genérico por seguridad
                            errorBody.contains("email") && (errorBody.contains("exist") || errorBody.contains("taken") || errorBody.contains("already")) ->
                                Exception("El correo electrónico ingresado no está disponible. Usa otro correo o inicia sesión si ya tienes cuenta.")
                            
                            errorBody.contains("username") && (errorBody.contains("exist") || errorBody.contains("taken") || errorBody.contains("already")) ->
                                Exception("El nombre de usuario ingresado no está disponible. Prueba con otro nombre de usuario.")
                            
                            // Mensaje genérico para otros casos de datos duplicados
                            errorBody.contains("exist") || errorBody.contains("taken") || errorBody.contains("already") ->
                                Exception("Los datos ingresados no están disponibles. Verifica e intenta con datos diferentes.")
                            
                            // Errores de formato
                            errorBody.contains("email") && errorBody.contains("invalid") ->
                                Exception("El formato del correo electrónico no es válido.")
                            
                            errorBody.contains("password") && (errorBody.contains("short") || errorBody.contains("weak")) ->
                                Exception("La contraseña es muy débil. Usa al menos 6 caracteres.")
                            
                            errorBody.contains("ruc") ->
                                Exception("El RUC ingresado no es válido. Verifica que tenga 11 dígitos.")
                            
                            else -> Exception("No se pudo completar el registro. Verifica tus datos e inténtalo de nuevo.")
                        }
                    }
                    409 -> Exception("Los datos ingresados no están disponibles. Verifica e intenta con datos diferentes.")
                    429 -> Exception("Demasiados intentos. Por favor espera unos minutos antes de intentar de nuevo.")
                    500, 502, 503 -> Exception("Error del servidor. Inténtalo más tarde.")
                    else -> Exception("No se pudo completar el registro. Inténtalo de nuevo.")
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

