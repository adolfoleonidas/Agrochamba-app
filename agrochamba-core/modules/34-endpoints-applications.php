<?php
/**
 * =============================================================
 * MODULO 34: ENDPOINTS DE POSTULACIONES
 * =============================================================
 *
 * Endpoints:
 * - POST /agrochamba/v1/applications          - Crear postulacion
 * - GET  /agrochamba/v1/applications          - Mis postulaciones
 * - DELETE /agrochamba/v1/applications/{id}   - Cancelar postulacion
 * - GET  /agrochamba/v1/jobs/{id}/application-status - Estado de postulacion
 * - GET  /agrochamba/v1/jobs/{id}/applicants  - Postulantes de un trabajo
 * - PUT  /agrochamba/v1/applications/{id}/status - Actualizar estado
 */

if (!defined('ABSPATH')) {
    exit;
}

// API: Delegar al controlador namespaced
if (class_exists('AgroChamba\\API\\Applications\\ApplicationsController')) {
    \AgroChamba\API\Applications\ApplicationsController::init();
} else {
    error_log('AgroChamba: ApplicationsController no encontrado.');
}

// Admin: Inicializar metabox de postulantes y dashboard
if (is_admin()) {
    // ApplicantsMetabox se auto-inicializa al cargarse via autoloader (linea 495 del archivo)
    // Solo necesitamos que el autoloader lo cargue referenciando la clase
    class_exists('AgroChamba\\Admin\\ApplicantsMetabox');

    // Dashboard de postulaciones
    if (class_exists('AgroChamba\\Admin\\ApplicationsDashboard')) {
        \AgroChamba\Admin\ApplicationsDashboard::init();
    }
}
