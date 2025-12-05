package agrochamba.com.domain.repository

import agrochamba.com.util.Result
import kotlinx.coroutines.flow.Flow

/**
 * Interfaz del repositorio de trabajos.
 * Define las operaciones relacionadas con trabajos.
 */
interface JobsRepository {
    fun streamPublishedJobs(limit: Long = 50): Flow<Result<List<Map<String, Any>>>>
    suspend fun createJob(data: Map<String, Any?>): Result<String>
    suspend fun updateJob(jobId: String, updates: Map<String, Any?>): Result<Unit>
    suspend fun deleteJob(jobId: String): Result<Unit>
    fun observeJob(jobId: String): Flow<Result<Map<String, Any?>>>
    fun observeJobMeta(jobId: String): Flow<Result<Map<String, Any?>>>
}

