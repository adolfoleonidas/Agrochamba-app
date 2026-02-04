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

// ==========================================
// RESUMEN DE DISPONIBILIDAD POR UBICACIÓN
// ==========================================

/**
 * Response para resumen de trabajadores por ubicación
 */
@JsonClass(generateAdapter = true)
data class ResumenDisponiblesResponse(
    val success: Boolean,
    @Json(name = "total_disponibles") val totalDisponibles: Int,
    @Json(name = "sin_ubicacion") val sinUbicacion: Int,
    @Json(name = "por_ubicacion") val porUbicacion: List<UbicacionResumen>
)

/**
 * Resumen de una ubicación
 */
@JsonClass(generateAdapter = true)
data class UbicacionResumen(
    val ubicacion: String,
    val cantidad: Int,
    @Json(name = "con_experiencia") val conExperiencia: Int,
    @Json(name = "rendimiento_promedio") val rendimientoPromedio: Double,
    val trabajadores: List<TrabajadorMinimo>
)

/**
 * Datos mínimos de un trabajador (para listas resumidas)
 */
@JsonClass(generateAdapter = true)
data class TrabajadorMinimo(
    val id: Int,
    val nombre: String
)

/**
 * Response para mapa de trabajadores
 */
@JsonClass(generateAdapter = true)
data class MapaTrabajadoresResponse(
    val success: Boolean,
    val ubicaciones: List<UbicacionMapa>,
    val otros: Int,
    val total: Int
)

/**
 * Ubicación para mapa con coordenadas
 */
@JsonClass(generateAdapter = true)
data class UbicacionMapa(
    val id: String,
    val nombre: String,
    val lat: Double,
    val lng: Double,
    val cantidad: Int
)

// ==========================================
// DISPONIBILIDAD DEL TRABAJADOR (ESTILO UBER)
// ==========================================

/**
 * Response para obtener mi estado de disponibilidad
 */
@JsonClass(generateAdapter = true)
data class DisponibilidadResponse(
    val success: Boolean,
    @Json(name = "disponible_para_trabajo") val disponibleParaTrabajo: Boolean,
    @Json(name = "tiene_contrato_activo") val tieneContratoActivo: Boolean,
    @Json(name = "visible_para_empresas") val visibleParaEmpresas: Boolean,
    val ubicacion: String?,
    @Json(name = "ubicacion_lat") val ubicacionLat: Double?,
    @Json(name = "ubicacion_lng") val ubicacionLng: Double?,
    val mensaje: String
)

/**
 * Response para actualizar disponibilidad
 */
@JsonClass(generateAdapter = true)
data class UpdateDisponibilidadResponse(
    val success: Boolean,
    @Json(name = "disponible_para_trabajo") val disponibleParaTrabajo: Boolean,
    @Json(name = "tiene_contrato_activo") val tieneContratoActivo: Boolean,
    @Json(name = "visible_para_empresas") val visibleParaEmpresas: Boolean,
    val mensaje: String
)

/**
 * Trabajador disponible con distancia (para empresas)
 */
@JsonClass(generateAdapter = true)
data class TrabajadorDisponibleConDistancia(
    val id: Int,
    val nombre: String,
    val email: String?,
    val foto: String?,
    val ubicacion: String?,
    val lat: Double?,
    val lng: Double?,
    @Json(name = "distancia_km") val distanciaKm: Double?,
    val experiencia: String?,
    val rendimiento: Double?,
    @Json(name = "disponible_desde") val disponibleDesde: String?,
    @Json(name = "ultimo_empleador") val ultimoEmpleador: String?
)

/**
 * Response con trabajadores y distancia
 */
@JsonClass(generateAdapter = true)
data class TrabajadoresConDistanciaResponse(
    val success: Boolean,
    val data: List<TrabajadorDisponibleConDistancia>,
    val total: Int
)
