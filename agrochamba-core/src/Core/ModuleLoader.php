<?php
/**
 * Module Loader - Load all plugin components
 *
 * @package AgroChamba\Core
 */

namespace AgroChamba\Core;

if (!defined('ABSPATH')) {
    exit;
}

class ModuleLoader
{

    /**
     * Initialize all modules
     */
    public static function init()
    {
        // Security (namespaced)
        \AgroChamba\Security\CORSManager::init();

        // Post Types and Taxonomies
        \AgroChamba\PostTypes\TrabajoPostType::register();
        \AgroChamba\PostTypes\EmpresaPostType::register();
        \AgroChamba\Taxonomies\UbicacionTaxonomy::register();
        \AgroChamba\Taxonomies\CultivoTaxonomy::register();
        \AgroChamba\Taxonomies\TipoPuestoTaxonomy::register();
        \AgroChamba\Taxonomies\EmpresaTaxonomy::register();

        // Services
        \AgroChamba\Services\CacheService::init();

        // Media
        \AgroChamba\Media\ImageOptimizer::init();

        // Integrations
        \AgroChamba\Integrations\FacebookIntegration::init();

        // API Endpoints
        self::init_api_endpoints();

        // Admin
        if (is_admin()) {
            \AgroChamba\Admin\FacebookSettings::init();
            \AgroChamba\Admin\EmpresasDashboard::init();
            \AgroChamba\Admin\ApplicantsMetabox::init();
        }
    }

    /**
     * Initialize API endpoints
     */
    private static function init_api_endpoints()
    {
        add_action('rest_api_init', function () {
            // Auth endpoints
            \AgroChamba\API\Auth\RegisterCompany::register_routes();
            \AgroChamba\API\Auth\RegisterUser::register_routes();
            \AgroChamba\API\Auth\PasswordRecovery::register_routes();

            // Profile endpoints
            \AgroChamba\API\Profile\UserProfile::register_routes();
            \AgroChamba\API\Profile\CompanyProfile::register_routes();
            \AgroChamba\API\Profile\ProfilePhoto::register_routes();

            // Jobs endpoints
            \AgroChamba\API\Jobs\JobsManager::register_routes();

            // Favorites endpoints
            \AgroChamba\API\Favorites\FavoritesManager::register_routes();

            // Media endpoints
            \AgroChamba\API\Media\ImagesController::register_routes();

            // Empresas endpoints
            \AgroChamba\API\Empresas\EmpresasController::register_routes();
            \AgroChamba\API\Empresas\EmpresaLogoController::register_routes();

            // Applications endpoints (postulaciones)
            \AgroChamba\API\Applications\ApplicationsController::register_routes();
        }, 20);
    }
}
