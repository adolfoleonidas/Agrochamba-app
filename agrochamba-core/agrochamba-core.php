<?php
/**
 * Plugin Name: AgroChamba Core
 * Plugin URI: https://agrochamba.com
 * Description: Sistema completo de gestión de trabajos agrícolas con API REST personalizada
 * Version: 1.0.0
 * Author: AgroChamba Team
 * License: GPL v2 or later
 * Text Domain: agrochamba
 * 
 * =============================================================
 * SISTEMA CORE - CARGADOR PRINCIPAL
 * =============================================================
 * 
 * Este archivo carga todos los módulos del sistema AgroChamba
 * en el orden correcto para evitar conflictos.
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
if (!defined('AGROCHAMBA_VERSION')) {
define('AGROCHAMBA_VERSION', '1.0.0');
}
if (!defined('AGROCHAMBA_PLUGIN_DIR')) {
define('AGROCHAMBA_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('AGROCHAMBA_PLUGIN_URL')) {
define('AGROCHAMBA_PLUGIN_URL', plugin_dir_url(__FILE__));
}

/**
 * Cargar todos los módulos del sistema
 * Esta función se puede llamar múltiples veces de forma segura
 */
function agrochamba_load_modules() {
    // Evitar cargar dos veces
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    
    // 0. Seguridad, CORS y Rate Limiting (debe cargarse PRIMERO)
    require_once AGROCHAMBA_PLUGIN_DIR . 'modules/00-security-cors.php';
    
    // 1. Custom Post Types y Taxonomías (base del sistema)
    require_once AGROCHAMBA_PLUGIN_DIR . 'modules/01-cpt-taxonomies.php';
    
    // 2. Filtros y hooks generales
    require_once AGROCHAMBA_PLUGIN_DIR . 'modules/02-filters-hooks.php';
    
    // 3. Endpoints de autenticación y registro
    require_once AGROCHAMBA_PLUGIN_DIR . 'modules/03-endpoints-auth.php';
    
    // 4. Endpoints de perfil de usuario
    require_once AGROCHAMBA_PLUGIN_DIR . 'modules/04-endpoints-user-profile.php';
    
    // 5. Endpoints de perfil de empresa
    require_once AGROCHAMBA_PLUGIN_DIR . 'modules/05-endpoints-company-profile.php';
    
    // 6. Endpoints de trabajos
    require_once AGROCHAMBA_PLUGIN_DIR . 'modules/06-endpoints-jobs.php';
    
    // 7. Endpoints de imágenes
    require_once AGROCHAMBA_PLUGIN_DIR . 'modules/07-endpoints-images.php';
    
    // 8. Endpoints de favoritos y guardados
    require_once AGROCHAMBA_PLUGIN_DIR . 'modules/08-endpoints-favorites.php';
    
    // 9. Integración con Facebook
    require_once AGROCHAMBA_PLUGIN_DIR . 'modules/09-facebook-integration.php';
    
    // 10. Sistema de caché (debe cargarse después de los endpoints)
    require_once AGROCHAMBA_PLUGIN_DIR . 'modules/10-cache-system.php';
    
    // 11. Optimización de imágenes
    require_once AGROCHAMBA_PLUGIN_DIR . 'modules/11-image-optimization.php';
    
    // 12. Regeneración de thumbnails
    require_once AGROCHAMBA_PLUGIN_DIR . 'modules/12-regenerate-thumbnails.php';
}

// Cargar módulos inmediatamente cuando el plugin se carga
// Esto asegura que todos los hooks se registren antes de que WordPress los ejecute
agrochamba_load_modules();

/**
 * Activar el plugin - Flush rewrite rules
 */
function agrochamba_activate() {
    // Cargar módulos primero
    agrochamba_load_modules();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'agrochamba_activate');

/**
 * Desactivar el plugin - Flush rewrite rules
 */
function agrochamba_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'agrochamba_deactivate');

