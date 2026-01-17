package agrochamba.com.data

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

/**
 * Modelos para la API de Sedes de Empresa
 */

/**
 * Respuesta al obtener sedes de una empresa
 */
@JsonClass(generateAdapter = true)
data class SedesResponse(
    @Json(name = "company_id") val companyId: Int,
    @Json(name = "company_name") val companyName: String,
    @Json(name = "sedes") val sedes: List<SedeApi>,
    @Json(name = "total") val total: Int
)

/**
 * Sede desde la API (formato del backend)
 */
@JsonClass(generateAdapter = true)
data class SedeApi(
    @Json(name = "id") val id: String,
    @Json(name = "nombre") val nombre: String,
    @Json(name = "departamento") val departamento: String,
    @Json(name = "provincia") val provincia: String,
    @Json(name = "distrito") val distrito: String,
    @Json(name = "direccion") val direccion: String? = null,
    @Json(name = "es_principal") val esPrincipal: Boolean = false,
    @Json(name = "activa") val activa: Boolean = true,
    @Json(name = "lat") val lat: Double? = null,
    @Json(name = "lng") val lng: Double? = null,
    @Json(name = "empresa_id") val empresaId: Int? = null,
    @Json(name = "empresa_nombre") val empresaNombre: String? = null
) {
    /**
     * Convierte a SedeEmpresa para uso local
     */
    fun toSedeEmpresa(): SedeEmpresa {
        return SedeEmpresa(
            id = id,
            nombre = nombre,
            ubicacion = UbicacionCompleta(
                departamento = departamento,
                provincia = provincia,
                distrito = distrito,
                direccion = direccion,
                lat = lat,
                lng = lng
            ),
            esPrincipal = esPrincipal,
            activa = activa
        )
    }
}

/**
 * Respuesta al crear una sede
 */
@JsonClass(generateAdapter = true)
data class CreateSedeResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "message") val message: String,
    @Json(name = "sede") val sede: SedeApi
)

/**
 * Respuesta al actualizar una sede
 */
@JsonClass(generateAdapter = true)
data class UpdateSedeResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "message") val message: String,
    @Json(name = "sede") val sede: SedeApi
)

/**
 * Respuesta al eliminar una sede
 */
@JsonClass(generateAdapter = true)
data class DeleteSedeResponse(
    @Json(name = "success") val success: Boolean,
    @Json(name = "message") val message: String
)

/**
 * Extensión para convertir SedeEmpresa a Map para enviar a la API
 */
fun SedeEmpresa.toApiMap(): Map<String, Any?> {
    return mapOf(
        "id" to id,
        "nombre" to nombre,
        "departamento" to ubicacion.departamento,
        "provincia" to ubicacion.provincia,
        "distrito" to ubicacion.distrito,
        "direccion" to ubicacion.direccion,
        "es_principal" to esPrincipal,
        "activa" to activa,
        "lat" to ubicacion.lat,
        "lng" to ubicacion.lng
    )
}
