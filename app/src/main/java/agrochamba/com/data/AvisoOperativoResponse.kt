package agrochamba.com.data

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

/**
 * Modelo de respuesta para Avisos Operativos desde el backend
 */
@JsonClass(generateAdapter = true)
data class AvisoOperativoResponse(
    val id: Int,
    val tipo: String, // resumen_trabajos, horario_ingreso, alerta_clima, anuncio
    val titulo: String,
    val contenido: String? = null,

    // Campos específicos por tipo
    val ubicacion: String? = null, // Para resumen_trabajos y alerta_clima
    val preview: String? = null, // Para resumen_trabajos

    @Json(name = "hora_operativos")
    val horaOperativos: String? = null, // Para horario_ingreso

    @Json(name = "hora_administrativos")
    val horaAdministrativos: String? = null, // Para horario_ingreso

    // Metadatos
    @Json(name = "fecha_creacion")
    val fechaCreacion: String? = null,

    @Json(name = "fecha_expiracion")
    val fechaExpiracion: String? = null,

    @Json(name = "empresa_id")
    val empresaId: Int? = null,

    @Json(name = "empresa_nombre")
    val empresaNombre: String? = null,

    val activo: Boolean = true
)

/**
 * Respuesta paginada de avisos
 */
@JsonClass(generateAdapter = true)
data class AvisosListResponse(
    val avisos: List<AvisoOperativoResponse>,
    val total: Int,
    val page: Int,
    @Json(name = "per_page")
    val perPage: Int
)

/**
 * Respuesta al crear un aviso
 */
@JsonClass(generateAdapter = true)
data class CreateAvisoResponse(
    val success: Boolean,
    val message: String,
    @Json(name = "post_id")
    val postId: Int? = null,
    val aviso: AvisoOperativoResponse? = null
)
