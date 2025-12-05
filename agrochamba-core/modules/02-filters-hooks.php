<?php
/**
 * Módulo obsoleto: 02-filters-hooks.php
 * 
 * Este archivo ha sido reemplazado por includes/hooks.php según el
 * plan de reorganización. Se mantiene como stub para asegurar
 * compatibilidad si algún entorno aún intenta cargarlo.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Evitar redefiniciones si por error se incluye este archivo
if (!defined('AGROCHAMBA_HOOKS_MODULE_DEPRECATED')) {
    define('AGROCHAMBA_HOOKS_MODULE_DEPRECATED', true);
    if (function_exists('error_log')) {
        error_log('AgroChamba: El módulo modules/02-filters-hooks.php está obsoleto. Usar includes/hooks.php.');
    }
}

// Cargar el archivo correcto para mantener funcionalidad
if (defined('AGROCHAMBA_INCLUDES_DIR')) {
    $hooks_file = AGROCHAMBA_INCLUDES_DIR . '/hooks.php';
    if (file_exists($hooks_file)) {
        require_once $hooks_file;
        return;
    }
}

// Fallback: no hacer nada si no se encuentra el archivo nuevo
return;

