package agrochamba.com.data

import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import agrochamba.com.BuildConfig
import agrochamba.com.network.DetailedLoggingInterceptor
import okhttp3.OkHttpClient
import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.Response
import retrofit2.Retrofit
import retrofit2.converter.moshi.MoshiConverterFactory
import retrofit2.http.*
import java.util.concurrent.TimeUnit

private const val BASE_URL = "https://agrochamba.com/wp-json/"

private val moshi = Moshi.Builder()
    .add(TitleAdapterFactory())
    .add(ContentAdapterFactory())
    .add(BooleanAdapterFactory())
    .add(EmpresaDataAdapterFactory())
    .add(UbicacionCompletaAdapterFactory()) // Maneja _ubicacion_completa que puede ser objeto o array vacío
    .addLast(KotlinJsonAdapterFactory())
    .build()

private val okHttpClient: OkHttpClient by lazy {
    OkHttpClient.Builder()
        .connectTimeout(20, TimeUnit.SECONDS)
        .writeTimeout(60, TimeUnit.SECONDS)
        .readTimeout(60, TimeUnit.SECONDS)
        .retryOnConnectionFailure(true)
        .apply {
            if (BuildConfig.DEBUG) {
                addInterceptor(DetailedLoggingInterceptor())
            }
        }
        .build()
}

private val retrofit = Retrofit.Builder()
    .addConverterFactory(MoshiConverterFactory.create(moshi))
    .baseUrl(BASE_URL)
    .client(okHttpClient)
    .build()

interface WordPressApiService {
    @GET("wp/v2/trabajos?_embed")
    suspend fun getJobs(
        @Query("page") page: Int,
        @Query("per_page") perPage: Int = 10
    ): List<JobPost>
    
    @GET("wp/v2/trabajos/{id}?_embed")
    suspend fun getJobById(
        @Header("Authorization") token: String,
        @Path("id") id: Int
    ): retrofit2.Response<JobPost>

    @GET("wp/v2/ubicacion?per_page=100")
    suspend fun getUbicaciones(): List<Category>

    @GET("wp/v2/empresa?per_page=100")
    suspend fun getEmpresas(): List<Category>

    @GET("wp/v2/tipo_puesto?per_page=100")
    suspend fun getTiposPuesto(): List<Category>

    @GET("wp/v2/cultivo?per_page=100")
    suspend fun getCultivos(): List<Category>

    @GET("wp/v2/categories?per_page=100")
    suspend fun getCategories(): List<Category>

    @GET("wp/v2/media")
    suspend fun getMediaForPost(@Query("parent") postId: Int): List<MediaItem>

    @GET("wp/v2/media/{id}")
    suspend fun getMediaById(@Path("id") id: Int): MediaItem

    @POST("jwt-auth/v1/token")
    suspend fun login(@Body credentials: Map<String, String>): TokenResponse
    
    @POST("agrochamba/v1/login")
    suspend fun customLogin(@Body credentials: Map<String, String>): TokenResponse

    @POST("agrochamba/v1/register-user")
    suspend fun registerUser(@Body userData: Map<String, String>): TokenResponse

    @POST("agrochamba/v1/register-company")
    suspend fun registerCompany(@Body companyData: Map<String, String>): TokenResponse

    @POST("agrochamba/v1/lost-password")
    suspend fun forgotPassword(@Body user: Map<String, String>): Response<Unit>

    @POST("agrochamba/v1/reset-password")
    suspend fun resetPassword(@Body data: Map<String, String>): Response<Unit>

    @POST("agrochamba/v1/jobs")
    suspend fun createJob(
        @Header("Authorization") token: String,
        @Body jobData: Map<String, @JvmSuppressWildcards Any>
    ): CreateJobResponse

    // Nuevo: Endpoint para subir imágenes
    @Multipart
    @POST("wp/v2/media")
    suspend fun uploadImage(
        @Header("Authorization") token: String,
        @Part file: MultipartBody.Part,
        @PartMap fields: Map<String, @JvmSuppressWildcards RequestBody>
    ): MediaItem

    @GET("agrochamba/v1/me/jobs")
    suspend fun getMyJobs(
        @Header("Authorization") token: String,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 20
    ): MyJobsResponse

    @GET("agrochamba/v1/jobs/{id}/images")
    suspend fun getJobImages(@Path("id") id: Int): JobImagesResponse

    @PUT("agrochamba/v1/jobs/{id}")
    suspend fun updateJob(
        @Header("Authorization") token: String,
        @Path("id") id: Int,
        @Body jobData: Map<String, @JvmSuppressWildcards Any>
    ): Response<UpdateJobResponse>

    @DELETE("agrochamba/v1/jobs/{id}")
    suspend fun deleteJob(
        @Header("Authorization") token: String,
        @Path("id") id: Int
    ): Response<Unit>

    // Endpoints para favoritos y guardados
    @POST("agrochamba/v1/favorites")
    suspend fun toggleFavorite(
        @Header("Authorization") token: String,
        @Body data: Map<String, Int>
    ): FavoriteResponse

    @GET("agrochamba/v1/favorites")
    suspend fun getFavorites(@Header("Authorization") token: String): FavoritesListResponse

    @POST("agrochamba/v1/saved")
    suspend fun toggleSaved(
        @Header("Authorization") token: String,
        @Body data: Map<String, Int>
    ): SavedResponse

    @GET("agrochamba/v1/saved")
    suspend fun getSaved(@Header("Authorization") token: String): SavedListResponse

    @GET("agrochamba/v1/jobs/{id}/favorite-saved-status")
    suspend fun getFavoriteSavedStatus(
        @Header("Authorization") token: String,
        @Path("id") id: Int
    ): FavoriteSavedStatusResponse

    // Endpoints para perfil de usuario
    @GET("agrochamba/v1/me/profile")
    suspend fun getUserProfile(@Header("Authorization") token: String): UserProfileResponse

    @PUT("agrochamba/v1/me/profile")
    suspend fun updateUserProfile(
        @Header("Authorization") token: String,
        @Body profileData: Map<String, @JvmSuppressWildcards Any>
    ): Response<UpdateProfileResponse>

    @Multipart
    @POST("agrochamba/v1/me/profile/photo")
    suspend fun uploadProfilePhoto(
        @Header("Authorization") token: String,
        @Part file: MultipartBody.Part
    ): ProfilePhotoResponse

    @DELETE("agrochamba/v1/me/profile/photo")
    suspend fun deleteProfilePhoto(@Header("Authorization") token: String): Response<Unit>

    // Endpoint para obtener perfil de empresa por nombre (público)
    @GET("agrochamba/v1/companies/profile")
    suspend fun getCompanyProfileByName(@Query("name") companyName: String): CompanyProfileResponse

    // Endpoint para obtener perfil completo de empresa con trabajos
    @GET("agrochamba/v1/companies/{company_name}/profile-with-jobs")
    suspend fun getCompanyProfileWithJobs(@Path("company_name") companyName: String): CompanyProfileWithJobsResponse

    // ==========================================
    // ENDPOINTS DE MODERACIÓN (SOLO ADMINS)
    // ==========================================
    
    @GET("agrochamba/v1/admin/pending-jobs")
    suspend fun getPendingJobs(
        @Header("Authorization") token: String,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 20
    ): PendingJobsResponse
    
    @POST("agrochamba/v1/admin/jobs/{id}/approve")
    suspend fun approveJob(
        @Header("Authorization") token: String,
        @Path("id") id: Int,
        @Body data: Map<String, String> = emptyMap()
    ): Response<Unit>
    
    @POST("agrochamba/v1/admin/jobs/{id}/reject")
    suspend fun rejectJob(
        @Header("Authorization") token: String,
        @Path("id") id: Int,
        @Body reason: Map<String, String>? = null
    ): Response<Unit>
    
    // Listar todos los trabajos (con paginación y filtros)
    @GET("agrochamba/v1/admin/jobs")
    suspend fun getAdminJobs(
        @Header("Authorization") token: String,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 20,
        @Query("status") status: String = "all",
        @Query("search") search: String = "",
        @Query("orderby") orderby: String = "date",
        @Query("order") order: String = "DESC"
    ): AdminJobsListResponse
    
    // Obtener un trabajo específico para preview
    @GET("agrochamba/v1/admin/jobs/{id}")
    suspend fun getAdminJobDetail(
        @Header("Authorization") token: String,
        @Path("id") id: Int
    ): AdminJobDetailResponse
    
    // Actualizar un trabajo (CRUD Update)
    @PUT("agrochamba/v1/admin/jobs/{id}")
    suspend fun updateAdminJob(
        @Header("Authorization") token: String,
        @Path("id") id: Int,
        @Body data: Map<String, @JvmSuppressWildcards Any?>
    ): AdminJobDetailResponse
    
    // Eliminar un trabajo (CRUD Delete)
    @DELETE("agrochamba/v1/admin/jobs/{id}")
    suspend fun deleteAdminJob(
        @Header("Authorization") token: String,
        @Path("id") id: Int,
        @Query("force") force: Boolean = false
    ): AdminJobDeleteResponse
    
    // Estadísticas de moderación
    @GET("agrochamba/v1/admin/moderation/stats")
    suspend fun getModerationStats(
        @Header("Authorization") token: String
    ): ModerationStatsResponse
    
    // Acción masiva
    @POST("agrochamba/v1/admin/jobs/bulk-action")
    suspend fun bulkAction(
        @Header("Authorization") token: String,
        @Body data: Map<String, @JvmSuppressWildcards Any>
    ): BulkActionResponse

    // ==========================================
    // ENDPOINTS DE IA (Mejora de texto y OCR)
    // ==========================================
    
    @GET("agrochamba/v1/ai/usage")
    suspend fun getAIUsageStatus(
        @Header("Authorization") token: String
    ): AIUsageStatusResponse
    
    @POST("agrochamba/v1/ai/enhance-text")
    suspend fun enhanceText(
        @Header("Authorization") token: String,
        @Body data: Map<String, String>
    ): AIEnhanceTextResponse
    
    @POST("agrochamba/v1/ai/generate-title")
    suspend fun generateTitle(
        @Header("Authorization") token: String,
        @Body data: Map<String, String>
    ): AIGenerateTitleResponse
    
    @POST("agrochamba/v1/ai/ocr")
    suspend fun extractTextFromImage(
        @Header("Authorization") token: String,
        @Body data: Map<String, @JvmSuppressWildcards Any>
    ): AIOCRResponse
    
    // ==========================================
    // ENDPOINTS DE GESTIÓN DE PÁGINAS DE FACEBOOK
    // ==========================================
    
    @GET("agrochamba/v1/facebook/pages")
    suspend fun getFacebookPages(
        @Header("Authorization") token: String
    ): FacebookPagesResponse
    
    @POST("agrochamba/v1/facebook/pages")
    suspend fun addFacebookPage(
        @Header("Authorization") token: String,
        @Body data: FacebookPageRequest
    ): FacebookPageResponse
    
    @PUT("agrochamba/v1/facebook/pages/{id}")
    suspend fun updateFacebookPage(
        @Header("Authorization") token: String,
        @Path("id") pageId: String,
        @Body data: Map<String, @JvmSuppressWildcards Any>
    ): FacebookPageResponse
    
    @DELETE("agrochamba/v1/facebook/pages/{id}")
    suspend fun deleteFacebookPage(
        @Header("Authorization") token: String,
        @Path("id") pageId: String
    ): FacebookPageDeleteResponse
    
    @POST("agrochamba/v1/facebook/pages/{id}/test")
    suspend fun testFacebookPage(
        @Header("Authorization") token: String,
        @Path("id") pageId: String
    ): FacebookPageTestResponse
    
    // ==========================================
    // ENDPOINTS DE SEDES DE EMPRESA
    // ==========================================
    
    @GET("agrochamba/v1/companies/{id}/sedes")
    suspend fun getCompanySedes(
        @Header("Authorization") token: String,
        @Path("id") companyId: Int
    ): SedesResponse
    
    @POST("agrochamba/v1/companies/{id}/sedes")
    suspend fun createSede(
        @Header("Authorization") token: String,
        @Path("id") companyId: Int,
        @Body sedeData: Map<String, @JvmSuppressWildcards Any?>
    ): CreateSedeResponse
    
    @PUT("agrochamba/v1/companies/{company_id}/sedes/{sede_id}")
    suspend fun updateSede(
        @Header("Authorization") token: String,
        @Path("company_id") companyId: Int,
        @Path("sede_id") sedeId: String,
        @Body sedeData: Map<String, @JvmSuppressWildcards Any?>
    ): UpdateSedeResponse
    
    @DELETE("agrochamba/v1/companies/{company_id}/sedes/{sede_id}")
    suspend fun deleteSede(
        @Header("Authorization") token: String,
        @Path("company_id") companyId: Int,
        @Path("sede_id") sedeId: String
    ): Response<DeleteSedeResponse>
}

object WordPressApi {
    val retrofitService: WordPressApiService by lazy {
        retrofit.create(WordPressApiService::class.java)
    }
}
