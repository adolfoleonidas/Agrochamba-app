package agrochamba.com.data

import com.squareup.moshi.Json

data class UserProfileResponse(
    @Json(name = "user_id") val userId: Int,
    val username: String,
    @Json(name = "display_name") val displayName: String,
    val email: String,
    @Json(name = "first_name") val firstName: String?,
    @Json(name = "last_name") val lastName: String?,
    val dni: String? = null,
    val roles: List<String>,
    @Json(name = "is_enterprise") val isEnterprise: Boolean,
    @Json(name = "profile_photo_id") val profilePhotoId: Int?,
    @Json(name = "profile_photo_url") val profilePhotoUrl: String?,
    val phone: String?,
    val bio: String?,
    @Json(name = "company_description") val companyDescription: String?,
    @Json(name = "company_address") val companyAddress: String?,
    @Json(name = "company_phone") val companyPhone: String?,
    @Json(name = "company_website") val companyWebsite: String?,
    @Json(name = "company_facebook") val companyFacebook: String?,
    @Json(name = "company_instagram") val companyInstagram: String?,
    @Json(name = "company_linkedin") val companyLinkedin: String?,
    @Json(name = "company_twitter") val companyTwitter: String?
)

data class UpdateProfileResponse(
    val success: Boolean,
    val message: String,
    @Json(name = "updated_fields") val updatedFields: List<String>?
)

data class ProfilePhotoResponse(
    val success: Boolean,
    val message: String,
    @Json(name = "photo_id") val photoId: Int,
    @Json(name = "photo_urls") val photoUrls: PhotoUrls
)

data class PhotoUrls(
    val full: String,
    val thumbnail: String,
    val medium: String,
    val large: String
)

data class FavoriteResponse(
    val success: Boolean,
    val action: String,
    @Json(name = "is_favorite") val isFavorite: Boolean,
    @Json(name = "favorites_count") val favoritesCount: Int
)

data class FavoritesListResponse(
    val jobs: List<FavoriteJob>
)

data class FavoriteJob(
    val id: Int,
    val title: String,
    val date: String,
    val link: String
)

data class SavedResponse(
    val success: Boolean,
    val action: String,
    @Json(name = "is_saved") val isSaved: Boolean,
    @Json(name = "saved_count") val savedCount: Int
)

data class SavedListResponse(
    val jobs: List<FavoriteJob>
)

data class FavoriteSavedStatusResponse(
    @Json(name = "is_favorite") val isFavorite: Boolean,
    @Json(name = "is_saved") val isSaved: Boolean
)

