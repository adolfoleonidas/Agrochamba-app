package agrochamba.com.data

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

/**
 * Estados posibles de un contrato
 */
enum class EstadoContrato(val value: String) {
    PENDIENTE("pendiente"),
    ACTIVO("activo"),
    FINALIZADO("finalizado"),
    CANCELADO("cancelado"),
    RECHAZADO("rechazado")
}

/**
 * Response para lista de contratos
 */
@JsonClass(generateAdapter = true)
data class ContratosListResponse(
    val success: Boolean,
    val data: List<ContratoItem>,
    val total: Int? = null
)

/**
 * Item individual de contrato
 */
@JsonClass(generateAdapter = true)
data class ContratoItem(
    val id: Int,
    @Json(name = "empresa_id") val empresaId: String?,
    @Json(name = "empresa_nombre") val empresaNombre: String?,
    @Json(name = "trabajador_id") val trabajadorId: String?,
    @Json(name = "trabajador_nombre") val trabajadorNombre: String?,
    @Json(name = "campana_id") val campanaId: String?,
    @Json(name = "campana_titulo") val campanaTitulo: String?,
    val estado: String,
    val puesto: String?,
    @Json(name = "salario_acordado") val salarioAcordado: String?,
    @Json(name = "fecha_oferta") val fechaOferta: String?,
    @Json(name = "fecha_aceptacion") val fechaAceptacion: String?,
    @Json(name = "fecha_inicio") val fechaInicio: String?,
    @Json(name = "fecha_fin") val fechaFin: String?,
    val notas: String?,
    @Json(name = "created_at") val createdAt: String?,
    val trabajador: TrabajadorResumen? = null
)

/**
 * Resumen de trabajador (incluido en contratos para empresas)
 */
@JsonClass(generateAdapter = true)
data class TrabajadorResumen(
    val id: Int,
    val nombre: String,
    val email: String?,
    val foto: String?,
    val telefono: String?,
    val dni: String?,
    val rendimiento: Double?
)

/**
 * Response para crear contrato
 */
@JsonClass(generateAdapter = true)
data class CreateContratoResponse(
    val success: Boolean,
    val message: String,
    val data: ContratoItem? = null
)

/**
 * Response simple (aceptar/rechazar/finalizar)
 */
@JsonClass(generateAdapter = true)
data class ContratoActionResponse(
    val success: Boolean,
    val message: String,
    val data: ContratoItem? = null
)

// ==========================================
// CAMPAÑAS
// ==========================================

/**
 * Response para lista de campañas
 */
@JsonClass(generateAdapter = true)
data class CampanasListResponse(
    val success: Boolean,
    val data: List<CampanaItem>,
    val total: Int? = null
)

/**
 * Item individual de campaña
 */
@JsonClass(generateAdapter = true)
data class CampanaItem(
    val id: Int,
    val titulo: String,
    val descripcion: String?,
    @Json(name = "empresa_id") val empresaId: String?,
    @Json(name = "empresa_nombre") val empresaNombre: String?,
    @Json(name = "fecha_inicio") val fechaInicio: String?,
    @Json(name = "fecha_fin") val fechaFin: String?,
    val ubicacion: String?,
    val cultivo: String?,
    @Json(name = "tipo_trabajo") val tipoTrabajo: String?,
    val vacantes: Int?,
    @Json(name = "salario_referencial") val salarioReferencial: String?,
    val requisitos: String?,
    val estado: String,
    @Json(name = "created_at") val createdAt: String?
)

/**
 * Response para crear campaña
 */
@JsonClass(generateAdapter = true)
data class CreateCampanaResponse(
    val success: Boolean,
    val message: String,
    val data: CampanaItem? = null
)

// ==========================================
// TRABAJADORES DISPONIBLES (CRM)
// ==========================================

/**
 * Response para trabajadores disponibles
 */
@JsonClass(generateAdapter = true)
data class TrabajadoresDisponiblesResponse(
    val success: Boolean,
    val data: List<TrabajadorDisponible>,
    val total: Int
)

/**
 * Trabajador disponible para contratar
 */
@JsonClass(generateAdapter = true)
data class TrabajadorDisponible(
    val id: Int,
    val nombre: String,
    val email: String?,
    val foto: String?,
    val ubicacion: String?,
    val experiencia: String?,
    val rendimiento: Double?,
    @Json(name = "disponible_desde") val disponibleDesde: String?,
    @Json(name = "ultimo_empleador") val ultimoEmpleador: String?
)
