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

// Load autoloader
require_once AGROCHAMBA_SRC_DIR . '/Core/Autoloader.php';

// Register autoloader
$autoloader = new \AgroChamba\Core\Autoloader();
$autoloader->register();

// Load helper functions
require_once AGROCHAMBA_INCLUDES_DIR . '/functions.php';
require_once AGROCHAMBA_INCLUDES_DIR . '/hooks.php';
require_once AGROCHAMBA_INCLUDES_DIR . '/empresa-functions.php';
