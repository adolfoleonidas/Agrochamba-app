package agrochamba.com.domain.usecase.jobs

import agrochamba.com.data.AuthManager
import agrochamba.com.domain.repository.JobsRepository
import agrochamba.com.util.Result

/**
 * Caso de uso para eliminar un trabajo.
 * Valida permisos antes de eliminar.
 */
class DeleteJobUseCase(
    private val jobsRepository: JobsRepository
) {
    suspend operator fun invoke(jobId: String): Result<Unit> {
        val uid = AuthManager.token
        if (uid == null) {
            return Result.Error(Exception("Debes iniciar sesión para eliminar trabajos."))
        }
        
        // Nota: La validación de ownership se hace en las reglas de Firestore
        
        return jobsRepository.deleteJob(jobId)
    }
}

