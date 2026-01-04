package agrochamba.com.data

import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

/**
 * Gestor de notificaciones de moderación
 * Mantiene el contador de trabajos pendientes de moderación
 */
object ModerationNotificationManager {
    private val _pendingJobsCount = MutableStateFlow(0)
    val pendingJobsCount: StateFlow<Int> = _pendingJobsCount.asStateFlow()
    
    /**
     * Actualizar el contador de trabajos pendientes
     */
    fun updatePendingJobsCount(count: Int) {
        _pendingJobsCount.value = count
    }
    
    /**
     * Incrementar el contador
     */
    fun incrementCount() {
        _pendingJobsCount.value = _pendingJobsCount.value + 1
    }
    
    /**
     * Decrementar el contador
     */
    fun decrementCount() {
        _pendingJobsCount.value = maxOf(0, _pendingJobsCount.value - 1)
    }
    
    /**
     * Resetear el contador
     */
    fun resetCount() {
        _pendingJobsCount.value = 0
    }
    
    /**
     * Verificar si hay trabajos pendientes
     */
    fun hasPendingJobs(): Boolean {
        return _pendingJobsCount.value > 0
    }
}

