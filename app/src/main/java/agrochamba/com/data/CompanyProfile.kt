package agrochamba.com.data

import com.squareup.moshi.Json

data class CompanyProfileResponse(
    @Json(name = "user_id") val userId: Int,
    @Json(name = "company_name") val companyName: String,
    @Json(name = "profile_photo_url") val profilePhotoUrl: String?,
    val description: String?,
    val address: String?,
    val phone: String?,
    val website: String?,
    val facebook: String?,
    val instagram: String?,
    val linkedin: String?,
    val twitter: String?,
    val email: String?
)

data class CompanyProfileWithJobsResponse(
    val company: CompanyInfo,
    val jobs: List<CompanyJob>,
    @Json(name = "jobs_count") val jobsCount: Int
)

data class CompanyInfo(
    @Json(name = "user_id") val userId: Int,
    @Json(name = "company_name") val companyName: String,
    @Json(name = "profile_photo_url") val profilePhotoUrl: String?,
    val description: String?,
    val address: String?,
    val phone: String?,
    val website: String?,
    val facebook: String?,
    val instagram: String?,
    val linkedin: String?,
    val twitter: String?,
    val email: String?
)

data class CompanyJob(
    val id: Int,
    val title: Title?,
    val excerpt: Content?,
    val date: String?,
    val link: String?,
    @Json(name = "featured_image_url") val featuredImageUrl: String?,
    val ubicacion: String?,
    val cultivo: String?,
    @Json(name = "tipo_puesto") val tipoPuesto: String?,
    @Json(name = "salario_min") val salarioMin: String?,
    @Json(name = "salario_max") val salarioMax: String?
)

