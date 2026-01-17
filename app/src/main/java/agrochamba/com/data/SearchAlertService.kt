package agrochamba.com.data

import android.content.Context
import android.content.SharedPreferences
import android.util.Log
import com.squareup.moshi.JsonAdapter
import com.squareup.moshi.Moshi
import com.squareup.moshi.Types
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import java.util.UUID

/**
 * =============================================================================
 * SEARCH ALERT SERVICE - Sistema de alertas de búsqueda
 * =============================================================================
 * 
 * Permite al usuario:
 * - Guardar búsquedas para recibir notificaciones
 * - Gestionar sus alertas activas
 * - Recibir push cuando hay nuevos trabajos que coinciden
 */

/**
 * Modelo de alerta de búsqueda
 */
data class SearchAlert(
    val id: String = UUID.randomUUID().toString(),
    val query: String = "",
    val locationId: Int? = null,
    val locationName: String? = null,
    val cropId: Int? = null,
    val cropName: String? = null,
    val jobTypeId: Int? = null,
    val jobTypeName: String? = null,
    val createdAt: Long = System.currentTimeMillis(),
    val isActive: Boolean = true,
    val lastNotifiedAt: Long? = null,
    val lastJobCount: Int = 0
) {
    /**
     * Genera un nombre descriptivo para la alerta
     */
    fun getDisplayName(): String {
        val parts = mutableListOf<String>()
        
        if (query.isNotBlank()) {
            parts.add("\"$query\"")
        }
        
        locationName?.let { parts.add("en $it") }
        cropName?.let { parts.add("de $it") }
        jobTypeName?.let { parts.add("($it)") }
        
        return if (parts.isEmpty()) "Todos los trabajos" else parts.joinToString(" ")
    }
    
    /**
     * Genera descripción corta para notificación
     */
    fun getShortDescription(): String {
        return when {
            query.isNotBlank() && locationName != null -> "$query en $locationName"
            query.isNotBlank() -> query
            locationName != null -> "Trabajos en $locationName"
            else -> "Nuevos trabajos"
        }
    }
}

/**
 * Servicio para gestionar alertas de búsqueda
 */
class SearchAlertService(private val context: Context) {
    
    private val prefs: SharedPreferences = context.getSharedPreferences(
        PREFS_NAME, Context.MODE_PRIVATE
    )
    
    private val moshi: Moshi = Moshi.Builder()
        .addLast(KotlinJsonAdapterFactory())
        .build()
    
    private val alertListType = Types.newParameterizedType(
        List::class.java, SearchAlert::class.java
    )
    private val alertListAdapter: JsonAdapter<List<SearchAlert>> = 
        moshi.adapter(alertListType)
    
    private val _alerts = MutableStateFlow<List<SearchAlert>>(emptyList())
    val alerts: StateFlow<List<SearchAlert>> = _alerts.asStateFlow()
    
    init {
        loadAlerts()
    }
    
    /**
     * Crea una nueva alerta de búsqueda
     */
    fun createAlert(
        query: String = "",
        locationId: Int? = null,
        locationName: String? = null,
        cropId: Int? = null,
        cropName: String? = null,
        jobTypeId: Int? = null,
        jobTypeName: String? = null
    ): SearchAlert {
        val alert = SearchAlert(
            query = query,
            locationId = locationId,
            locationName = locationName,
            cropId = cropId,
            cropName = cropName,
            jobTypeId = jobTypeId,
            jobTypeName = jobTypeName
        )
        
        val current = _alerts.value.toMutableList()
        
        // Verificar si ya existe una alerta similar
        val exists = current.any { existing ->
            existing.query == alert.query &&
            existing.locationId == alert.locationId &&
            existing.cropId == alert.cropId &&
            existing.jobTypeId == alert.jobTypeId
        }
        
        if (!exists) {
            current.add(0, alert)
            _alerts.value = current
            saveAlerts()
            
            Log.d(TAG, "Alert created: ${alert.getDisplayName()}")
        }
        
        return alert
    }
    
    /**
     * Elimina una alerta
     */
    fun deleteAlert(alertId: String) {
        val updated = _alerts.value.filter { it.id != alertId }
        _alerts.value = updated
        saveAlerts()
    }
    
    /**
     * Activa/desactiva una alerta
     */
    fun toggleAlert(alertId: String) {
        val updated = _alerts.value.map { alert ->
            if (alert.id == alertId) {
                alert.copy(isActive = !alert.isActive)
            } else alert
        }
        _alerts.value = updated
        saveAlerts()
    }
    
    /**
     * Obtiene todas las alertas activas
     */
    fun getActiveAlerts(): List<SearchAlert> {
        return _alerts.value.filter { it.isActive }
    }
    
    /**
     * Actualiza el contador de trabajos de una alerta
     */
    fun updateAlertJobCount(alertId: String, newCount: Int) {
        val updated = _alerts.value.map { alert ->
            if (alert.id == alertId) {
                alert.copy(
                    lastJobCount = newCount,
                    lastNotifiedAt = System.currentTimeMillis()
                )
            } else alert
        }
        _alerts.value = updated
        saveAlerts()
    }
    
    /**
     * Verifica si ya existe una alerta para esta búsqueda
     */
    fun hasAlertFor(
        query: String,
        locationId: Int?,
        cropId: Int?,
        jobTypeId: Int?
    ): Boolean {
        return _alerts.value.any { alert ->
            alert.query == query &&
            alert.locationId == locationId &&
            alert.cropId == cropId &&
            alert.jobTypeId == jobTypeId
        }
    }
    
    private fun loadAlerts() {
        val json = prefs.getString(KEY_ALERTS, null)
        if (json != null) {
            try {
                val list = alertListAdapter.fromJson(json)
                _alerts.value = list ?: emptyList()
            } catch (e: Exception) {
                Log.e(TAG, "Error loading alerts", e)
                _alerts.value = emptyList()
            }
        }
    }
    
    private fun saveAlerts() {
        try {
            val json = alertListAdapter.toJson(_alerts.value)
            prefs.edit().putString(KEY_ALERTS, json).apply()
        } catch (e: Exception) {
            Log.e(TAG, "Error saving alerts", e)
        }
    }
    
    companion object {
        private const val TAG = "SearchAlertService"
        private const val PREFS_NAME = "search_alerts_prefs"
        private const val KEY_ALERTS = "alerts"
        
        @Volatile
        private var INSTANCE: SearchAlertService? = null
        
        fun getInstance(context: Context): SearchAlertService {
            return INSTANCE ?: synchronized(this) {
                INSTANCE ?: SearchAlertService(context.applicationContext).also { INSTANCE = it }
            }
        }
    }
}

