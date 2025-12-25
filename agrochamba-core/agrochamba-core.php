<?php
/**
 * Plugin Name: AgroChamba Core
 * Plugin URI: https://agrochamba.com
 * Description: Sistema completo de gestión de trabajos agrícolas con API REST personalizada
 * Version: 2.0.0
 * Author: AgroChamba Team
 * Author URI: https://agrochamba.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: agrochamba
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package AgroChamba
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// ==========================================
// BOOTSTRAP
// ==========================================
require_once __DIR__ . '/config/bootstrap.php';

// ==========================================
// CARGAR MÓDULOS
// ==========================================
if (!AGROCHAMBA_USE_MODULE_LOADER) {
    function agrochamba_load_modules()
    {
        // Evitar cargar dos veces
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $modules_dir = AGROCHAMBA_PLUGIN_DIR . '/modules/';

        // Lista de módulos en orden de carga
        $modules = array(
            '00-security-cors.php',           // Seguridad y CORS (PRIMERO)
            '01-cpt-taxonomies.php',          // Custom Post Types y Taxonomías
            // '02-filters-hooks.php',        // MOVIDO A: includes/hooks.php (Cargado por bootstrap)
            '03-endpoints-auth.php',          // Autenticación y registro
            '04-endpoints-user-profile.php',  // Perfil de usuario
            '05-endpoints-company-profile.php', // Perfil de empresa
            '06-endpoints-jobs.php',          // Gestión de trabajos
            '07-endpoints-images.php',        // Gestión de imágenes
            '08-endpoints-favorites.php',     // Favoritos y guardados
            '09-facebook-integration.php',    // Integración con Facebook
            '10-cache-system.php',            // Sistema de caché
            '11-image-optimization.php',      // Optimización de imágenes
            '12-regenerate-thumbnails.php',   // Regeneración de thumbnails
            '13-ai-moderation.php',           // Moderación automática con IA
            '14-company-notifications.php',    // Notificaciones de empresas seguidas
            '15-job-views.php',                // Conteo de vistas de trabajos
            '16-job-comments.php',             // Sistema de comentarios para trabajos
            '17-custom-auth-pages.php',        // Páginas de autenticación personalizadas
            '18-custom-user-panel.php',        // Panel de usuario personalizado
            '19-job-relevance-scoring.php',    // Sistema de scoring de relevancia
            '20-smart-job-sorting.php',        // Ordenamiento inteligente de trabajos
        );

        // Cargar cada módulo
        foreach ($modules as $module) {
            $file = $modules_dir . $module;
            if (file_exists($file)) {
                require_once $file;
            } else {
                error_log("AgroChamba: No se pudo cargar el módulo: {$module}");
            }
        }

        // Hook para que otros plugins puedan añadir funcionalidad
        do_action('agrochamba_modules_loaded');
    }

    // Cargar módulos
    add_action('plugins_loaded', 'agrochamba_load_modules', 10);
} else {
    // Modo moderno: usar el cargador PSR-4 para inicializar todo el plugin
    add_action('plugins_loaded', function () {
        if (class_exists('AgroChamba\\Core\\ModuleLoader')) {
            \AgroChamba\Core\ModuleLoader::init();
            do_action('agrochamba_modules_loaded');
        } else {
            error_log('AgroChamba: ModuleLoader no encontrado. Verifique el autoloader.');
        }
    }, 10);
}

// ==========================================
// INIT HOOK
// ==========================================
add_action('init', function () {
    // Asegurar que existe el rol employer
    $role = get_role('employer');
    if (!$role) {
        add_role('employer', 'Empresa', array(
            'read' => true,
            'edit_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
            'delete_published_posts' => true,
            'upload_files' => true,
        ));
    } else {
        // Asegurar capabilities para el rol employer
        $capabilities = array('upload_files', 'edit_posts', 'publish_posts', 'delete_posts');
        foreach ($capabilities as $cap) {
            if (!$role->has_cap($cap)) {
                $role->add_cap($cap);
            }
        }
    }
}, 1);

// ==========================================
// ACTIVACIÓN Y DESACTIVACIÓN
// ==========================================

/**
 * Activar el plugin
 */
function agrochamba_activate()
{
    // Asegurar capabilities necesarias
    $role = get_role('employer');
    if ($role && !$role->has_cap('upload_files')) {
        $role->add_cap('upload_files');
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'agrochamba_activate');

/**
 * Desactivar el plugin
 */
function agrochamba_deactivate()
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'agrochamba_deactivate');

// ==========================================
// INFORMACIÓN DEL PLUGIN
// ==========================================

/**
 * Añadir enlace a configuración en la página de plugins
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=agrochamba-facebook') . '">Configuración</a>';
    array_unshift($links, $settings_link);
    return $links;
});

/**
 * Añadir información en el footer del admin
 */
add_filter('admin_footer_text', function ($text) {
    // En algunos contextos (activación, CLI, AJAX temprano) get_current_screen puede no existir o devolver null
    if (!function_exists('get_current_screen')) {
        return $text;
    }

    $screen = get_current_screen();
    if (!$screen || !isset($screen->id)) {
        return $text;
    }

    if (strpos((string)$screen->id, 'agrochamba') !== false) {
        return 'AgroChamba Core v' . AGROCHAMBA_VERSION . ' | Desarrollado con ❤️ por el equipo de AgroChamba';
    }
    return $text;
});
