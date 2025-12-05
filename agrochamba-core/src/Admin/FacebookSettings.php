<?php
/**
 * Facebook Settings Admin Page
 *
 * @package AgroChamba\Admin
 */

namespace AgroChamba\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class FacebookSettings
{
    /**
     * Initialize Facebook Settings
     */
    public static function init()
    {
        // La página de configuración está en modules/09-facebook-integration.php
        // Este método existe para compatibilidad con ModuleLoader
        // El menú se registra automáticamente cuando se incluye el módulo
    }
}

