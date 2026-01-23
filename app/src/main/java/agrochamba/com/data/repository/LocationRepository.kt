package agrochamba.com.data.repository

import agrochamba.com.data.LocationSearchResult
import agrochamba.com.data.LocationType
import agrochamba.com.data.PeruLocations
import agrochamba.com.data.SedeEmpresa
import agrochamba.com.data.UbicacionCompleta
import agrochamba.com.data.WordPressApi
import android.content.Context
import android.content.SharedPreferences
import com.squareup.moshi.JsonAdapter
import com.squareup.moshi.Moshi
import com.squareup.moshi.Types
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.withContext

/**
 * =============================================================================
 * LOCATION REPOSITORY - Gestión de ubicaciones offline-first
 * =============================================================================
 * 
 * Este repositorio maneja:
 * - Búsqueda de ubicaciones (offline, usando PeruLocations)
 * - Historial de ubicaciones recientes
 * - Ubicaciones favoritas del usuario
 * - Sedes de empresa (sincronizadas con backend)
 */
class LocationRepository(private val context: Context) {
    
    private val prefs: SharedPreferences = context.getSharedPreferences(
        PREFS_NAME, Context.MODE_PRIVATE
    )
    
    private val moshi: Moshi = Moshi.Builder()
        .addLast(KotlinJsonAdapterFactory())
        .build()
    
    // Adaptadores JSON para serialización
    private val ubicacionAdapter: JsonAdapter<UbicacionCompleta> = 
        moshi.adapter(UbicacionCompleta::class.java)
    
    private val ubicacionListType = Types.newParameterizedType(
        List::class.java, UbicacionCompleta::class.java
    )
    private val ubicacionListAdapter: JsonAdapter<List<UbicacionCompleta>> = 
        moshi.adapter(ubicacionListType)
    
    private val sedeListType = Types.newParameterizedType(
        List::class.java, SedeEmpresa::class.java
    )
    private val sedeListAdapter: JsonAdapter<List<SedeEmpresa>> = 
        moshi.adapter(sedeListType)
    
    // State flows para observar cambios
    private val _recentLocations = MutableStateFlow<List<UbicacionCompleta>>(emptyList())
    val recentLocations: Flow<List<UbicacionCompleta>> = _recentLocations.asStateFlow()
    
    private val _favoriteLocations = MutableStateFlow<List<UbicacionCompleta>>(emptyList())
    val favoriteLocations: Flow<List<UbicacionCompleta>> = _favoriteLocations.asStateFlow()
    
    private val _companySedes = MutableStateFlow<List<SedeEmpresa>>(emptyList())
    val companySedes: Flow<List<SedeEmpresa>> = _companySedes.asStateFlow()
    
    init {
        // Cargar datos guardados al iniciar
        loadRecentLocations()
        loadFavoriteLocations()
        loadCompanySedes()
    }
    
    // =========================================================================
    // BÚSQUEDA OFFLINE-FIRST
    // =========================================================================
    
    /**
     * Búsqueda inteligente de ubicaciones (OFFLINE - instantánea)
     * No requiere conexión a internet
     */
    suspend fun searchLocations(query: String, limit: Int = 10): List<LocationSearchResult> {
        return withContext(Dispatchers.Default) {
            PeruLocations.searchLocation(query, limit)
        }
    }
    
    /**
     * Búsqueda solo de distritos
     */
    suspend fun searchDistritos(query: String, limit: Int = 10): List<LocationSearchResult> {
        return withContext(Dispatchers.Default) {
            PeruLocations.searchDistritos(query, limit)
        }
    }
    
    /**
     * Resuelve ubicación completa desde un distrito
     */
    fun resolveFromDistrito(distrito: String): UbicacionCompleta? {
        return PeruLocations.resolveFromDistrito(distrito)
    }
    
    /**
     * Valida si una ubicación es válida
     */
    fun isValidLocation(ubicacion: UbicacionCompleta): Boolean {
        return PeruLocations.isValidLocation(ubicacion)
    }
    
    /**
     * Normaliza y corrige una ubicación
     */
    fun normalizeLocation(ubicacion: UbicacionCompleta): UbicacionCompleta? {
        return PeruLocations.normalizeLocation(ubicacion)
    }
    
    // =========================================================================
    // HISTORIAL DE UBICACIONES RECIENTES
    // =========================================================================
    
    /**
     * Agrega una ubicación al historial reciente
     */
    fun addToRecent(ubicacion: UbicacionCompleta) {
        val current = _recentLocations.value.toMutableList()
        
        // Remover si ya existe (para moverlo al inicio)
        current.removeAll { it.distrito == ubicacion.distrito && 
                           it.provincia == ubicacion.provincia &&
                           it.departamento == ubicacion.departamento }
        
        // Agregar al inicio
        current.add(0, ubicacion)
        
        // Mantener máximo 10 recientes
        val updated = current.take(MAX_RECENT_LOCATIONS)
        
        _recentLocations.value = updated
        saveRecentLocations(updated)
    }
    
    /**
     * Obtiene ubicaciones recientes
     */
    fun getRecentLocations(): List<UbicacionCompleta> = _recentLocations.value
    
    /**
     * Limpia historial reciente
     */
    fun clearRecentLocations() {
        _recentLocations.value = emptyList()
        prefs.edit().remove(KEY_RECENT_LOCATIONS).apply()
    }
    
    private fun loadRecentLocations() {
        val json = prefs.getString(KEY_RECENT_LOCATIONS, null)
        if (json != null) {
            try {
                val list = ubicacionListAdapter.fromJson(json)
                _recentLocations.value = list ?: emptyList()
            } catch (e: Exception) {
                _recentLocations.value = emptyList()
            }
        }
    }
    
    private fun saveRecentLocations(locations: List<UbicacionCompleta>) {
        val json = ubicacionListAdapter.toJson(locations)
        prefs.edit().putString(KEY_RECENT_LOCATIONS, json).apply()
    }
    
    // =========================================================================
    // UBICACIONES FAVORITAS
    // =========================================================================
    
    /**
     * Agrega una ubicación a favoritos
     */
    fun addToFavorites(ubicacion: UbicacionCompleta) {
        val current = _favoriteLocations.value.toMutableList()
        
        // Evitar duplicados
        val exists = current.any { 
            it.distrito == ubicacion.distrito && 
            it.provincia == ubicacion.provincia &&
            it.departamento == ubicacion.departamento 
        }
        
        if (!exists) {
            current.add(ubicacion)
            _favoriteLocations.value = current
            saveFavoriteLocations(current)
        }
    }
    
    /**
     * Remueve una ubicación de favoritos
     */
    fun removeFromFavorites(ubicacion: UbicacionCompleta) {
        val updated = _favoriteLocations.value.filter { 
            !(it.distrito == ubicacion.distrito && 
              it.provincia == ubicacion.provincia &&
              it.departamento == ubicacion.departamento)
        }
        _favoriteLocations.value = updated
        saveFavoriteLocations(updated)
    }
    
    /**
     * Verifica si una ubicación está en favoritos
     */
    fun isFavorite(ubicacion: UbicacionCompleta): Boolean {
        return _favoriteLocations.value.any { 
            it.distrito == ubicacion.distrito && 
            it.provincia == ubicacion.provincia &&
            it.departamento == ubicacion.departamento 
        }
    }
    
    /**
     * Obtiene ubicaciones favoritas
     */
    fun getFavoriteLocations(): List<UbicacionCompleta> = _favoriteLocations.value
    
    private fun loadFavoriteLocations() {
        val json = prefs.getString(KEY_FAVORITE_LOCATIONS, null)
        if (json != null) {
            try {
                val list = ubicacionListAdapter.fromJson(json)
                _favoriteLocations.value = list ?: emptyList()
            } catch (e: Exception) {
                _favoriteLocations.value = emptyList()
            }
        }
    }
    
    private fun saveFavoriteLocations(locations: List<UbicacionCompleta>) {
        val json = ubicacionListAdapter.toJson(locations)
        prefs.edit().putString(KEY_FAVORITE_LOCATIONS, json).apply()
    }
    
    // =========================================================================
    // SEDES DE EMPRESA (CACHE LOCAL)
    // =========================================================================
    
    /**
     * Guarda las sedes de la empresa (cache local)
     */
    fun saveCompanySedes(sedes: List<SedeEmpresa>) {
        _companySedes.value = sedes
        val json = sedeListAdapter.toJson(sedes)
        prefs.edit().putString(KEY_COMPANY_SEDES, json).apply()
    }
    
    /**
     * Obtiene las sedes de la empresa
     */
    fun getCompanySedes(): List<SedeEmpresa> = _companySedes.value
    
    /**
     * Obtiene la sede principal
     */
    fun getPrimarySede(): SedeEmpresa? {
        return _companySedes.value.find { it.esPrincipal } 
            ?: _companySedes.value.firstOrNull()
    }
    
    /**
     * Agrega una nueva sede
     */
    fun addSede(sede: SedeEmpresa) {
        val current = _companySedes.value.toMutableList()
        
        // Si es principal, desmarcar las demás
        if (sede.esPrincipal) {
            current.replaceAll { it.copy(esPrincipal = false) }
        }
        
        current.add(sede)
        saveCompanySedes(current)
    }
    
    /**
     * Actualiza una sede existente
     */
    fun updateSede(sede: SedeEmpresa) {
        val current = _companySedes.value.toMutableList()
        val index = current.indexOfFirst { it.id == sede.id }
        
        if (index >= 0) {
            // Si es principal, desmarcar las demás
            if (sede.esPrincipal) {
                current.replaceAll { 
                    if (it.id != sede.id) it.copy(esPrincipal = false) else it 
                }
            }
            current[index] = sede
            saveCompanySedes(current)
        }
    }
    
    /**
     * Elimina una sede
     */
    fun removeSede(sedeId: String) {
        val updated = _companySedes.value.filter { it.id != sedeId }
        saveCompanySedes(updated)
    }
    
    /**
     * Crea una nueva sede con ubicación validada
     */
    fun createSede(nombre: String, ubicacion: UbicacionCompleta, esPrincipal: Boolean = false): SedeEmpresa? {
        // Validar ubicación
        if (!isValidLocation(ubicacion)) return null
        
        // Normalizar ubicación
        val normalizedUbicacion = normalizeLocation(ubicacion) ?: return null
        
        return PeruLocations.createSede(nombre, normalizedUbicacion, esPrincipal)
    }
    
    private fun loadCompanySedes() {
        val json = prefs.getString(KEY_COMPANY_SEDES, null)
        if (json != null) {
            try {
                val list = sedeListAdapter.fromJson(json)
                _companySedes.value = list ?: emptyList()
            } catch (e: Exception) {
                _companySedes.value = emptyList()
            }
        }
    }
    
    // =========================================================================
    // SINCRONIZACIÓN CON BACKEND
    // =========================================================================
    
    private var _isSyncing = MutableStateFlow(false)
    val isSyncing: Flow<Boolean> = _isSyncing.asStateFlow()
    
    private var _syncError = MutableStateFlow<String?>(null)
    val syncError: Flow<String?> = _syncError.asStateFlow()
    
    /**
     * Sincroniza las sedes con el backend
     * Carga las sedes desde la API y las guarda localmente
     */
    suspend fun syncSedesFromBackend(token: String, companyId: Int): Boolean {
        return withContext(Dispatchers.IO) {
            try {
                _isSyncing.value = true
                _syncError.value = null
                
                val response = WordPressApi.retrofitService.getCompanySedes(
                    token = "Bearer $token",
                    companyId = companyId
                )
                
                // Convertir y guardar localmente
                val sedes = response.sedes.map { it.toSedeEmpresa() }
                saveCompanySedes(sedes)
                
                android.util.Log.d("LocationRepository", "Sincronizadas ${sedes.size} sedes desde backend")
                true
            } catch (e: Exception) {
                android.util.Log.e("LocationRepository", "Error sincronizando sedes: ${e.message}")
                _syncError.value = "Error al cargar sedes: ${e.message}"
                false
            } finally {
                _isSyncing.value = false
            }
        }
    }
    
    /**
     * Crea una sede en el backend y la guarda localmente
     */
    suspend fun createSedeInBackend(token: String, companyId: Int, sede: SedeEmpresa): SedeEmpresa? {
        return withContext(Dispatchers.IO) {
            try {
                val sedeData = mapOf(
                    "nombre" to sede.nombre,
                    "departamento" to sede.ubicacion.departamento,
                    "provincia" to sede.ubicacion.provincia,
                    "distrito" to sede.ubicacion.distrito,
                    "direccion" to sede.ubicacion.direccion,
                    "es_principal" to sede.esPrincipal,
                    "lat" to sede.ubicacion.lat,
                    "lng" to sede.ubicacion.lng
                )
                
                val response = WordPressApi.retrofitService.createSede(
                    token = "Bearer $token",
                    companyId = companyId,
                    sedeData = sedeData
                )
                
                if (response.success) {
                    val nuevaSede = response.sede.toSedeEmpresa()
                    // Agregar localmente
                    addSede(nuevaSede)
                    android.util.Log.d("LocationRepository", "Sede creada en backend: ${nuevaSede.nombre}")
                    nuevaSede
                } else {
                    null
                }
            } catch (e: Exception) {
                android.util.Log.e("LocationRepository", "Error creando sede en backend: ${e.message}")
                // Guardar localmente de todos modos para offline
                addSede(sede)
                sede
            }
        }
    }
    
    /**
     * Actualiza una sede en el backend
     */
    suspend fun updateSedeInBackend(token: String, companyId: Int, sede: SedeEmpresa): Boolean {
        return withContext(Dispatchers.IO) {
            try {
                val sedeData = mapOf(
                    "nombre" to sede.nombre,
                    "departamento" to sede.ubicacion.departamento,
                    "provincia" to sede.ubicacion.provincia,
                    "distrito" to sede.ubicacion.distrito,
                    // Enviar "" en lugar de null para que el backend borre la dirección
                    "direccion" to (sede.ubicacion.direccion ?: ""),
                    "es_principal" to sede.esPrincipal,
                    "activa" to sede.activa,
                    "lat" to sede.ubicacion.lat,
                    "lng" to sede.ubicacion.lng
                )
                
                val response = WordPressApi.retrofitService.updateSede(
                    token = "Bearer $token",
                    companyId = companyId,
                    sedeId = sede.id,
                    sedeData = sedeData
                )
                
                if (response.success) {
                    updateSede(response.sede.toSedeEmpresa())
                    true
                } else {
                    false
                }
            } catch (e: Exception) {
                android.util.Log.e("LocationRepository", "Error actualizando sede en backend: ${e.message}")
                // Actualizar localmente de todos modos
                updateSede(sede)
                false
            }
        }
    }
    
    /**
     * Elimina una sede del backend
     */
    suspend fun deleteSedeFromBackend(token: String, companyId: Int, sedeId: String): Boolean {
        return withContext(Dispatchers.IO) {
            try {
                val response = WordPressApi.retrofitService.deleteSede(
                    token = "Bearer $token",
                    companyId = companyId,
                    sedeId = sedeId
                )
                
                if (response.isSuccessful) {
                    removeSede(sedeId)
                    true
                } else {
                    false
                }
            } catch (e: Exception) {
                android.util.Log.e("LocationRepository", "Error eliminando sede del backend: ${e.message}")
                // Eliminar localmente de todos modos
                removeSede(sedeId)
                false
            }
        }
    }
    
    /**
     * Limpia el error de sincronización
     */
    fun clearSyncError() {
        _syncError.value = null
    }
    
    // =========================================================================
    // UBICACIÓN PREFERIDA DEL USUARIO (PARA BÚSQUEDA)
    // =========================================================================
    
    /**
     * Guarda la ubicación preferida del usuario para búsquedas
     */
    fun setPreferredLocation(ubicacion: UbicacionCompleta?) {
        if (ubicacion != null) {
            val json = ubicacionAdapter.toJson(ubicacion)
            prefs.edit().putString(KEY_PREFERRED_LOCATION, json).apply()
        } else {
            prefs.edit().remove(KEY_PREFERRED_LOCATION).apply()
        }
    }
    
    /**
     * Obtiene la ubicación preferida del usuario
     */
    fun getPreferredLocation(): UbicacionCompleta? {
        val json = prefs.getString(KEY_PREFERRED_LOCATION, null) ?: return null
        return try {
            ubicacionAdapter.fromJson(json)
        } catch (e: Exception) {
            null
        }
    }
    
    /**
     * Indica si el usuario quiere ver trabajos de todo el país
     */
    fun setSearchNationwide(nationwide: Boolean) {
        prefs.edit().putBoolean(KEY_SEARCH_NATIONWIDE, nationwide).apply()
    }
    
    fun isSearchNationwide(): Boolean {
        return prefs.getBoolean(KEY_SEARCH_NATIONWIDE, false)
    }
    
    // =========================================================================
    // UTILIDADES
    // =========================================================================
    
    /**
     * Obtiene sugerencias combinadas (recientes + favoritas + sedes)
     * Para mostrar al usuario antes de que escriba
     */
    fun getQuickSuggestions(): List<QuickLocationSuggestion> {
        val suggestions = mutableListOf<QuickLocationSuggestion>()
        
        // Agregar ubicación preferida primero
        getPreferredLocation()?.let { 
            suggestions.add(QuickLocationSuggestion(
                ubicacion = it,
                tipo = SuggestionType.PREFERRED,
                label = "📍 Mi ubicación: ${it.formatForSedeSelector()}"
            ))
        }
        
        // Agregar sedes de empresa
        _companySedes.value.filter { it.activa }.take(3).forEach { sede ->
            suggestions.add(QuickLocationSuggestion(
                ubicacion = sede.ubicacion,
                tipo = SuggestionType.SEDE,
                label = if (sede.esPrincipal) "🏢 ${sede.nombre} (Principal)" else "🏢 ${sede.nombre}"
            ))
        }
        
        // Agregar favoritas
        _favoriteLocations.value.take(2).forEach { ubi ->
            suggestions.add(QuickLocationSuggestion(
                ubicacion = ubi,
                tipo = SuggestionType.FAVORITE,
                label = "⭐ ${ubi.formatForSedeSelector()}"
            ))
        }
        
        // Agregar recientes (que no estén ya en las otras listas)
        val existingKeys = suggestions.map { 
            "${it.ubicacion.departamento}:${it.ubicacion.provincia}:${it.ubicacion.distrito}" 
        }.toSet()
        
        _recentLocations.value
            .filter { 
                "${it.departamento}:${it.provincia}:${it.distrito}" !in existingKeys 
            }
            .take(3)
            .forEach { ubi ->
                suggestions.add(QuickLocationSuggestion(
                    ubicacion = ubi,
                    tipo = SuggestionType.RECENT,
                    label = "🕐 ${ubi.formatForSedeSelector()}"
                ))
            }
        
        return suggestions
    }
    
    /**
     * Obtiene estadísticas del dataset
     */
    fun getStats() = PeruLocations.getLocationStats()
    
    /**
     * Obtiene departamentos populares para sugerencias
     */
    fun getPopularDepartamentos() = PeruLocations.getPopularDepartamentos()
    
    /**
     * Verifica si el usuario ya completó el onboarding de ubicación
     */
    fun hasCompletedLocationOnboarding(): Boolean {
        return prefs.getBoolean(KEY_ONBOARDING_COMPLETED, false)
    }
    
    /**
     * Marca el onboarding como completado
     */
    fun setOnboardingCompleted() {
        prefs.edit().putBoolean(KEY_ONBOARDING_COMPLETED, true).apply()
    }
    
    companion object {
        private const val PREFS_NAME = "location_prefs"
        private const val KEY_RECENT_LOCATIONS = "recent_locations"
        private const val KEY_FAVORITE_LOCATIONS = "favorite_locations"
        private const val KEY_COMPANY_SEDES = "company_sedes"
        private const val KEY_PREFERRED_LOCATION = "preferred_location"
        private const val KEY_SEARCH_NATIONWIDE = "search_nationwide"
        private const val KEY_ONBOARDING_COMPLETED = "onboarding_location_completed"
        private const val MAX_RECENT_LOCATIONS = 10
        
        @Volatile
        private var INSTANCE: LocationRepository? = null
        
        fun getInstance(context: Context): LocationRepository {
            return INSTANCE ?: synchronized(this) {
                INSTANCE ?: LocationRepository(context.applicationContext).also { INSTANCE = it }
            }
        }
    }
}

/**
 * Tipo de sugerencia rápida
 */
enum class SuggestionType {
    PREFERRED,  // Ubicación preferida del usuario
    SEDE,       // Sede de la empresa
    FAVORITE,   // Ubicación favorita
    RECENT      // Ubicación reciente
}

/**
 * Sugerencia de ubicación para mostrar rápidamente
 */
data class QuickLocationSuggestion(
    val ubicacion: UbicacionCompleta,
    val tipo: SuggestionType,
    val label: String
)

