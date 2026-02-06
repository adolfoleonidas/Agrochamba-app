package agrochamba.com.notifications

/**
 * Tipos de notificaciones soportadas por la app.
 * Cada módulo puede agregar sus propios tipos aquí.
 */
enum class NotificationType(
    val channelId: String,
    val channelName: String,
    val channelDescription: String
) {
    // ═══════════════════════════════════════════════════════════════
    // POSTULACIONES
    // ═══════════════════════════════════════════════════════════════
    APPLICATION_STATUS_CHANGED(
        channelId = "applications",
        channelName = "Postulaciones",
        channelDescription = "Cambios en el estado de tus postulaciones"
    ),
    NEW_APPLICANT(
        channelId = "applicants",
        channelName = "Nuevos Postulantes",
        channelDescription = "Nuevas postulaciones a tus trabajos"
    ),

    // ═══════════════════════════════════════════════════════════════
    // TRABAJOS
    // ═══════════════════════════════════════════════════════════════
    NEW_JOB_IN_ZONE(
        channelId = "jobs",
        channelName = "Nuevos Trabajos",
        channelDescription = "Trabajos nuevos en tu zona"
    ),
    JOB_EXPIRING(
        channelId = "jobs",
        channelName = "Nuevos Trabajos",
        channelDescription = "Trabajos que están por vencer"
    ),

    // ═══════════════════════════════════════════════════════════════
    // FAVORITOS
    // ═══════════════════════════════════════════════════════════════
    FAVORITE_JOB_UPDATED(
        channelId = "favorites",
        channelName = "Favoritos",
        channelDescription = "Actualizaciones de trabajos guardados"
    ),
    FAVORITE_JOB_EXPIRING(
        channelId = "favorites",
        channelName = "Favoritos",
        channelDescription = "Trabajos guardados por vencer"
    ),

    // ═══════════════════════════════════════════════════════════════
    // MENSAJES
    // ═══════════════════════════════════════════════════════════════
    NEW_MESSAGE(
        channelId = "messages",
        channelName = "Mensajes",
        channelDescription = "Nuevos mensajes de empresas o trabajadores"
    ),

    // ═══════════════════════════════════════════════════════════════
    // SISTEMA / PROMOCIONES
    // ═══════════════════════════════════════════════════════════════
    SYSTEM_ANNOUNCEMENT(
        channelId = "system",
        channelName = "Anuncios",
        channelDescription = "Anuncios importantes de AgroChamba"
    ),
    PROMOTION(
        channelId = "promotions",
        channelName = "Promociones",
        channelDescription = "Ofertas y descuentos especiales"
    ),

    // ═══════════════════════════════════════════════════════════════
    // CRÉDITOS
    // ═══════════════════════════════════════════════════════════════
    CREDITS_LOW(
        channelId = "credits",
        channelName = "Créditos",
        channelDescription = "Avisos sobre tus créditos"
    ),
    CREDITS_PURCHASED(
        channelId = "credits",
        channelName = "Créditos",
        channelDescription = "Confirmación de compra de créditos"
    ),

    // ═══════════════════════════════════════════════════════════════
    // GENERAL
    // ═══════════════════════════════════════════════════════════════
    GENERAL(
        channelId = "general",
        channelName = "General",
        channelDescription = "Notificaciones generales"
    );

    companion object {
        fun fromString(type: String?): NotificationType {
            return values().find { it.name.equals(type, ignoreCase = true) } ?: GENERAL
        }

        /**
         * Obtener todos los canales únicos para crear en el sistema
         */
        fun getAllChannels(): List<Triple<String, String, String>> {
            return values()
                .distinctBy { it.channelId }
                .map { Triple(it.channelId, it.channelName, it.channelDescription) }
        }
    }
}
