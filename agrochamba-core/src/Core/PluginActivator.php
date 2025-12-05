<?php
/**
 * Plugin Activator - Handle plugin activation/deactivation
 *
 * @package AgroChamba\Core
 */

namespace AgroChamba\Core;

if (!defined('ABSPATH')) {
    exit;
}

class PluginActivator {

    /**
     * Run on plugin activation
     */
    public static function activate() {
        // Register post types and taxonomies
        \AgroChamba\PostTypes\TrabajoPostType::register();
        \AgroChamba\Taxonomies\UbicacionTaxonomy::register();
        \AgroChamba\Taxonomies\CultivoTaxonomy::register();
        \AgroChamba\Taxonomies\TipoPuestoTaxonomy::register();
        \AgroChamba\Taxonomies\EmpresaTaxonomy::register();

        // Ensure employer role has upload_files capability
        self::ensure_employer_capabilities();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Run on plugin deactivation
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Ensure employer role has necessary capabilities
     */
    private static function ensure_employer_capabilities() {
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
            // Ensure capabilities
            $capabilities = array('upload_files', 'edit_posts', 'publish_posts', 'delete_posts');
            foreach ($capabilities as $cap) {
                if (!$role->has_cap($cap)) {
                    $role->add_cap($cap);
                }
            }
        }
    }
}
