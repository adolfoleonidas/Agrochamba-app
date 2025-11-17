package agrochamba.com.data

// El molde para una Categoría de WordPress, ahora con todos sus campos
data class Category(
    val id: Int,
    val name: String,
    val slug: String? = null,
    val parent: Int? = null,
    val taxonomy: String? = null // Añadimos el campo que faltaba
)
