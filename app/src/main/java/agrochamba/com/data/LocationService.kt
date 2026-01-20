package agrochamba.com.data

import android.Manifest
import android.annotation.SuppressLint
import android.content.Context
import android.content.pm.PackageManager
import android.location.Geocoder
import android.location.Location
import android.os.Build
import android.util.Log
import androidx.core.content.ContextCompat
import com.google.android.gms.location.FusedLocationProviderClient
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import com.google.android.gms.tasks.CancellationTokenSource
import kotlinx.coroutines.suspendCancellableCoroutine
import java.util.Locale
import kotlin.coroutines.resume

/**
 * =============================================================================
 * LOCATION SERVICE - Servicio de ubicación GPS
 * =============================================================================
 * 
 * Proporciona:
 * - Obtención de ubicación actual del dispositivo
 * - Reverse geocoding para obtener departamento/provincia/distrito
 * - Mapeo a ubicaciones válidas de PeruLocations
 */
class LocationService(private val context: Context) {
    
    private val fusedLocationClient: FusedLocationProviderClient = 
        LocationServices.getFusedLocationProviderClient(context)
    
    private val geocoder: Geocoder by lazy { 
        Geocoder(context, Locale("es", "PE")) 
    }
    
    companion object {
        private const val TAG = "LocationService"
        
        @Volatile
        private var INSTANCE: LocationService? = null
        
        fun getInstance(context: Context): LocationService {
            return INSTANCE ?: synchronized(this) {
                INSTANCE ?: LocationService(context.applicationContext).also { INSTANCE = it }
            }
        }
    }
    
    /**
     * Verifica si tenemos permisos de ubicación
     */
    fun hasLocationPermission(): Boolean {
        return ContextCompat.checkSelfPermission(
            context,
            Manifest.permission.ACCESS_FINE_LOCATION
        ) == PackageManager.PERMISSION_GRANTED ||
        ContextCompat.checkSelfPermission(
            context,
            Manifest.permission.ACCESS_COARSE_LOCATION
        ) == PackageManager.PERMISSION_GRANTED
    }
    
    /**
     * Obtiene la ubicación actual del dispositivo
     */
    @SuppressLint("MissingPermission")
    suspend fun getCurrentLocation(): Location? {
        if (!hasLocationPermission()) {
            Log.w(TAG, "No location permission")
            return null
        }
        
        return suspendCancellableCoroutine { continuation ->
            val cancellationTokenSource = CancellationTokenSource()
            
            fusedLocationClient.getCurrentLocation(
                Priority.PRIORITY_BALANCED_POWER_ACCURACY,
                cancellationTokenSource.token
            ).addOnSuccessListener { location ->
                continuation.resume(location)
            }.addOnFailureListener { exception ->
                Log.e(TAG, "Error getting location", exception)
                continuation.resume(null)
            }
            
            continuation.invokeOnCancellation {
                cancellationTokenSource.cancel()
            }
        }
    }
    
    /**
     * Obtiene la última ubicación conocida (más rápido pero menos preciso)
     */
    @SuppressLint("MissingPermission")
    suspend fun getLastKnownLocation(): Location? {
        if (!hasLocationPermission()) {
            return null
        }
        
        return suspendCancellableCoroutine { continuation ->
            fusedLocationClient.lastLocation
                .addOnSuccessListener { location ->
                    continuation.resume(location)
                }
                .addOnFailureListener {
                    continuation.resume(null)
                }
        }
    }
    
    /**
     * Resultado del reverse geocoding
     */
    data class GeocodingResult(
        val departamento: String?,
        val provincia: String?,
        val distrito: String?,
        val direccion: String?,
        val lat: Double,
        val lng: Double
    )
    
    /**
     * Convierte coordenadas GPS a dirección (reverse geocoding)
     */
    @Suppress("DEPRECATION")
    suspend fun reverseGeocode(lat: Double, lng: Double): GeocodingResult? {
        return try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                // Android 13+: usar callback
                suspendCancellableCoroutine { continuation ->
                    geocoder.getFromLocation(lat, lng, 1) { addresses ->
                        if (addresses.isNotEmpty()) {
                            val address = addresses[0]
                            continuation.resume(GeocodingResult(
                                departamento = address.adminArea, // Región/Departamento
                                provincia = address.subAdminArea, // Provincia
                                distrito = address.locality ?: address.subLocality, // Distrito
                                direccion = address.getAddressLine(0),
                                lat = lat,
                                lng = lng
                            ))
                        } else {
                            continuation.resume(null)
                        }
                    }
                }
            } else {
                // Android 12 y anteriores: método síncrono
                val addresses = geocoder.getFromLocation(lat, lng, 1)
                if (!addresses.isNullOrEmpty()) {
                    val address = addresses[0]
                    GeocodingResult(
                        departamento = address.adminArea,
                        provincia = address.subAdminArea,
                        distrito = address.locality ?: address.subLocality,
                        direccion = address.getAddressLine(0),
                        lat = lat,
                        lng = lng
                    )
                } else null
            }
        } catch (e: Exception) {
            Log.e(TAG, "Geocoding error", e)
            null
        }
    }
    
    /**
     * Obtiene la ubicación actual y la mapea a UbicacionCompleta válida
     */
    suspend fun getCurrentLocationAsUbicacion(): UbicacionCompleta? {
        val location = getCurrentLocation() ?: getLastKnownLocation()
        if (location == null) {
            Log.w(TAG, "Could not get location")
            return null
        }
        
        val geocoded = reverseGeocode(location.latitude, location.longitude)
        if (geocoded == null) {
            Log.w(TAG, "Could not geocode location")
            return null
        }
        
        // Intentar encontrar en PeruLocations para validar
        return findBestMatch(geocoded)
    }
    
    /**
     * Busca la mejor coincidencia en PeruLocations basándose en el geocoding
     */
    private fun findBestMatch(geocoded: GeocodingResult): UbicacionCompleta? {
        Log.d(TAG, "Finding best match for: distrito=${geocoded.distrito}, provincia=${geocoded.provincia}, departamento=${geocoded.departamento}")
        
        // Normalizar departamento de Google
        val normalizedDepartamento = geocoded.departamento?.let { normalizeDepartamento(it) }
        
        // 1. Intentar búsqueda exacta con contexto completo (distrito + provincia + departamento)
        geocoded.distrito?.let { distrito ->
            val result = PeruLocations.resolveFromDistritoWithContext(
                distrito = distrito,
                provincia = geocoded.provincia,
                departamento = normalizedDepartamento
            )
            if (result != null) {
                Log.d(TAG, "Found exact match with context: ${result.distrito}, ${result.provincia}, ${result.departamento}")
                return result.copy(
                    direccion = geocoded.direccion,
                    coordenadas = Coordenadas(geocoded.lat, geocoded.lng)
                )
            }
        }
        
        // 2. Intentar solo por distrito (sin contexto)
        geocoded.distrito?.let { distrito ->
            val result = PeruLocations.resolveFromDistrito(distrito)
            if (result != null) {
                Log.d(TAG, "Found match by district only: ${result.distrito}, ${result.provincia}, ${result.departamento}")
                return result.copy(
                    direccion = geocoded.direccion,
                    coordenadas = Coordenadas(geocoded.lat, geocoded.lng)
                )
            }
        }
        
        // 3. Intentar por provincia
        geocoded.provincia?.let { provincia ->
            val result = PeruLocations.resolveFromProvincia(provincia, normalizedDepartamento)
            if (result != null) {
                Log.d(TAG, "Found match by province: ${result.provincia}, ${result.departamento}")
                return result.copy(
                    direccion = geocoded.direccion,
                    coordenadas = Coordenadas(geocoded.lat, geocoded.lng)
                )
            }
        }
        
        // 4. Intentar por departamento
        normalizedDepartamento?.let { departamento ->
            val searchResults = PeruLocations.searchLocation(departamento, 1)
            if (searchResults.isNotEmpty()) {
                Log.d(TAG, "Found match by department search")
                return searchResults.first().toUbicacionCompleta()?.copy(
                    direccion = geocoded.direccion,
                    coordenadas = Coordenadas(geocoded.lat, geocoded.lng)
                )
            }
        }
        
        // 5. Si nada funciona, crear ubicación con lo que tenemos
        Log.w(TAG, "No exact match found, creating fallback location")
        return if (geocoded.departamento != null) {
            UbicacionCompleta(
                departamento = normalizedDepartamento ?: geocoded.departamento,
                provincia = geocoded.provincia ?: geocoded.departamento,
                distrito = geocoded.distrito ?: geocoded.provincia ?: geocoded.departamento,
                direccion = geocoded.direccion,
                coordenadas = Coordenadas(geocoded.lat, geocoded.lng)
            )
        } else null
    }
    
    /**
     * Mapeo de departamentos de Google a PeruLocations
     * Google a veces devuelve nombres diferentes
     */
    private fun normalizeDepartamento(googleName: String): String {
        return when (googleName.lowercase()) {
            "lima region", "lima province", "callao" -> "Lima"
            "la libertad region" -> "La Libertad"
            "madre de dios region" -> "Madre de Dios"
            "san martin region" -> "San Martín"
            else -> googleName.replace(" Region", "").replace(" region", "").trim()
        }
    }
}

