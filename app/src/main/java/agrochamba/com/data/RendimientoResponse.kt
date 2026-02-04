package agrochamba.com.data

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

/**
 * Response para lista de rendimientos
 */
@JsonClass(generateAdapter = true)
data class RendimientoListResponse(
    val success: Boolean,
    val data: List<RendimientoItem>,
    val total: Int
)

/**
 * Item individual de rendimiento
 */
@JsonClass(generateAdapter = true)
data class RendimientoItem(
    val id: Int,
    @Json(name = "trabajador_id") val trabajadorId: String? = null,
    @Json(name = "trabajador_nombre") val trabajadorNombre: String,
    @Json(name = "trabajador_dni") val trabajadorDni: String? = null,
    val categoria: String,
    @Json(name = "categoria_label") val categoriaLabel: String,
    val valor: Double,
    val unidad: String,
    @Json(name = "fecha_registro") val fechaRegistro: String,
    val turno: String? = null,
    val observaciones: String? = null,
    @Json(name = "empresa_id") val empresaId: String? = null,
    @Json(name = "created_at") val createdAt: String? = null
)

/**
 * Response para resumen de rendimiento
 */
@JsonClass(generateAdapter = true)
data class RendimientoResumenResponse(
    val success: Boolean,
    @Json(name = "total_general") val totalGeneral: Double,
    val periodo: String,
    @Json(name = "fecha_inicio") val fechaInicio: String,
    val categorias: List<RendimientoCategoria>
)

/**
 * Resumen por categoría
 */
@JsonClass(generateAdapter = true)
data class RendimientoCategoria(
    val categoria: String,
    val total: Double,
    val unidad: String,
    val registros: Int,
    @Json(name = "ultima_fecha") val ultimaFecha: String,
    val tendencia: String // "subiendo", "bajando", "estable"
)

/**
 * Response para crear rendimiento
 */
@JsonClass(generateAdapter = true)
data class CreateRendimientoResponse(
    val success: Boolean,
    val message: String,
    val data: RendimientoItem? = null
)
