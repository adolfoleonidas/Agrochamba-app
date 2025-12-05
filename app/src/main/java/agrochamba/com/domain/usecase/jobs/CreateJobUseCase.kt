package agrochamba.com.domain.usecase.jobs

import agrochamba.com.data.AuthManager
import agrochamba.com.domain.repository.JobsRepository
import agrochamba.com.util.Result

/**
 * Caso de uso para crear un trabajo.
 * Valida permisos y crea el trabajo.
 */
class CreateJobUseCase(
    private val jobsRepository: JobsRepository
) {
    suspend operator fun invoke(jobData: Map<String, Any?>): Result<String> {
        // Validar que el usuario esté autenticado
        val uid = AuthManager.token
        if (uid == null) {
            return Result.Error(Exception("Debes iniciar sesión para crear trabajos."))
        }
        
        // Validar que sea empresa o admin
        if (!AuthManager.isUserAnEnterprise()) {
            return Result.Error(Exception("Solo las empresas pueden publicar trabajos."))
        }
        
        // Validar campos requeridos según reglas de Firestore
        val title = jobData["title"] as? String
        val description = jobData["description"] as? String
        
        if (title.isNullOrBlank()) {
            return Result.Error(Exception("El título es obligatorio."))
        }
        if (title.length < 5) {
            return Result.Error(Exception("El título debe tener al menos 5 caracteres."))
        }
        if (title.length > 200) {
            return Result.Error(Exception("El título no puede tener más de 200 caracteres."))
        }
        
        if (description.isNullOrBlank()) {
            return Result.Error(Exception("La descripción es obligatoria."))
        }
        if (description.length < 20) {
            return Result.Error(Exception("La descripción debe tener al menos 20 caracteres."))
        }
        if (description.length > 5000) {
            return Result.Error(Exception("La descripción no puede tener más de 5000 caracteres."))
        }
        
        // Agregar ownerUid y status al jobData
        val dataWithOwner = jobData.toMutableMap().apply {
            put("ownerUid", uid)
            put("status", "published")
        }
        
        return jobsRepository.createJob(dataWithOwner)
    }
}

