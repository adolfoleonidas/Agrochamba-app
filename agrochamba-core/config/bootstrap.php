<?php
/**
 * Bootstrap file - Initialize plugin
 *
 * @package AgroChamba
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load constants
require_once __DIR__ . '/constants.php';

// Load autoloader (only if file exists)
// Use both constant-based and relative path for compatibility
$autoloader_path = AGROCHAMBA_SRC_DIR . '/Core/Autoloader.php';
$autoloader_path_relative = __DIR__ . '/../src/Core/Autoloader.php';

$autoloader_file = file_exists($autoloader_path) ? $autoloader_path : (file_exists($autoloader_path_relative) ? $autoloader_path_relative : null);

if ($autoloader_file !== null) {
    require_once $autoloader_file;

// Register autoloader
    if (class_exists('\AgroChamba\Core\Autoloader')) {
$autoloader = new \AgroChamba\Core\Autoloader();
$autoloader->register();
    }
}

// Load helper functions
require_once AGROCHAMBA_INCLUDES_DIR . '/functions.php';
require_once AGROCHAMBA_INCLUDES_DIR . '/hooks.php';
require_once AGROCHAMBA_INCLUDES_DIR . '/empresa-functions.php';
