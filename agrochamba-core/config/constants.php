<?php
/**
 * Plugin Constants
 *
 * @package AgroChamba
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin paths
define('AGROCHAMBA_PLUGIN_FILE', dirname(__DIR__) . '/agrochamba-core.php');
define('AGROCHAMBA_PLUGIN_DIR', dirname(__DIR__));
define('AGROCHAMBA_SRC_DIR', AGROCHAMBA_PLUGIN_DIR . '/src');
define('AGROCHAMBA_INCLUDES_DIR', AGROCHAMBA_PLUGIN_DIR . '/includes');
define('AGROCHAMBA_TEMPLATES_DIR', AGROCHAMBA_PLUGIN_DIR . '/templates');
define('AGROCHAMBA_ASSETS_DIR', AGROCHAMBA_PLUGIN_DIR . '/assets');

// Plugin URLs
define('AGROCHAMBA_PLUGIN_URL', plugins_url('', AGROCHAMBA_PLUGIN_FILE));
define('AGROCHAMBA_ASSETS_URL', AGROCHAMBA_PLUGIN_URL . '/assets');

// Plugin version
define('AGROCHAMBA_VERSION', '2.0.0');

// Feature flags / toggles
if (!defined('AGROCHAMBA_USE_MODULE_LOADER')) {
    // Cuando es true, el plugin usará el cargador moderno (PSR-4) en vez de los módulos legacy
    define('AGROCHAMBA_USE_MODULE_LOADER', false);
}

// Cache TTL (Time To Live)
define('AGROCHAMBA_CACHE_JOBS_LIST_TTL', 5 * MINUTE_IN_SECONDS);
define('AGROCHAMBA_CACHE_SINGLE_JOB_TTL', 15 * MINUTE_IN_SECONDS);
define('AGROCHAMBA_CACHE_TAXONOMIES_TTL', 30 * MINUTE_IN_SECONDS);
define('AGROCHAMBA_CACHE_COMPANY_PROFILE_TTL', 15 * MINUTE_IN_SECONDS);

// Rate limiting
define('AGROCHAMBA_RATE_LIMIT_REQUESTS', 100);
define('AGROCHAMBA_RATE_LIMIT_WINDOW', 60); // seconds

// Image sizes
define('AGROCHAMBA_IMAGE_CARD_WIDTH', 400);
define('AGROCHAMBA_IMAGE_CARD_HEIGHT', 300);
define('AGROCHAMBA_IMAGE_DETAIL_WIDTH', 800);
define('AGROCHAMBA_IMAGE_DETAIL_HEIGHT', 600);
define('AGROCHAMBA_IMAGE_THUMB_WIDTH', 150);
define('AGROCHAMBA_IMAGE_THUMB_HEIGHT', 150);
