package agrochamba.com.domain.usecase.jobs

import agrochamba.com.data.AuthManager
import agrochamba.com.domain.repository.JobsRepository
import agrochamba.com.util.Result

/**
 * Caso de uso para actualizar un trabajo.
 * Valida permisos antes de actualizar.
 */
class UpdateJobUseCase(
    private val jobsRepository: JobsRepository
) {
    suspend operator fun invoke(
        jobId: String,
        updates: Map<String, Any?>
    ): Result<Unit> {
        val uid = AuthManager.token
        if (uid == null) {
            return Result.Error(Exception("Debes iniciar sesión para editar trabajos."))
        }
        
        // Nota: La validación de ownership se hace en las reglas de Firestore
        // Aquí solo validamos que esté autenticado
        
        return jobsRepository.updateJob(jobId, updates)
    }
}

