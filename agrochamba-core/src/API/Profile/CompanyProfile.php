<?php
/**
 * Controlador de Perfil de Empresa
 *
 * @package AgroChamba
 * @subpackage API\Profile
 */

namespace AgroChamba\API\Profile;

use WP_Error;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

class CompanyProfile
{
    const API_NAMESPACE = 'agrochamba/v1';

    public static function init(): void
    {
        add_action('rest_api_init', [__CLASS__, 'register_routes'], 20);
    }

    public static function register_routes(): void
    {
        $routes = rest_get_server()->get_routes();

        // Perfil de empresa del usuario autenticado
        if (!isset($routes['/' . self::API_NAMESPACE . '/me/company-profile'])) {
            register_rest_route(self::API_NAMESPACE, '/me/company-profile', [
                [
                    'methods' => 'GET',
                    'callback' => [__CLASS__, 'get_my_company_profile'],
                    'permission_callback' => function () { return is_user_logged_in(); },
                ],
                [
                    'methods' => 'PUT',
                    'callback' => [__CLASS__, 'update_my_company_profile'],
                    'permission_callback' => function () { return is_user_logged_in(); },
                ],
            ]);
        }

        // Perfil público por ID
        if (!isset($routes['/' . self::API_NAMESPACE . '/companies/(?P<user_id>\d+)/profile'])) {
            register_rest_route(self::API_NAMESPACE, '/companies/(?P<user_id>\d+)/profile', [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_company_profile_by_id'],
                'permission_callback' => '__return_true',
                'args' => [
                    'user_id' => [
                        'required' => true,
                        'validate_callback' => function ($param) { return is_numeric($param); }
                    ]
                ]
            ]);
        }

        // Perfil público por nombre (?name=...)
        if (!isset($routes['/' . self::API_NAMESPACE . '/companies/profile'])) {
            register_rest_route(self::API_NAMESPACE, '/companies/profile', [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_company_profile_by_name'],
                'permission_callback' => '__return_true',
            ]);
        }
    }

    public static function get_my_company_profile($request)
    {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para ver tu perfil de empresa.', ['status' => 401]);
        }
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'Usuario no encontrado.', ['status' => 404]);
        }
        if (!self::is_enterprise($user)) {
            return new WP_Error('not_enterprise', 'Solo las empresas pueden tener perfil de empresa.', ['status' => 403]);
        }
        return self::build_company_profile_response($user_id, $user);
    }

    public static function update_my_company_profile($request)
    {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para actualizar tu perfil de empresa.', ['status' => 401]);
        }
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'Usuario no encontrado.', ['status' => 404]);
        }
        if (!self::is_enterprise($user)) {
            return new WP_Error('not_enterprise', 'Solo las empresas pueden actualizar su perfil de empresa.', ['status' => 403]);
        }
        $params = $request->get_json_params();
        
        // Obtener o crear CPT de empresa
        $empresa = agrochamba_get_empresa_by_user_id($user_id);
        
        if (!$empresa) {
            // Si no existe CPT, crear uno automáticamente
            if (function_exists('agrochamba_create_empresa_on_user_register')) {
                agrochamba_create_empresa_on_user_register($user_id);
                $empresa = agrochamba_get_empresa_by_user_id($user_id);
            }
            
            if (!$empresa) {
                return new WP_Error('empresa_not_found', 'No se pudo crear o encontrar el perfil de empresa.', ['status' => 500]);
            }
        }
        
        // Actualizar datos en el CPT en lugar de user_meta
        $empresa_updates = [];
        
        if (isset($params['description'])) {
            $desc = sanitize_textarea_field($params['description']);
            $empresa_updates['post_content'] = $desc;
        }
        
        if (isset($params['phone'])) {
            $phone = preg_replace('/[^0-9+\-\s]/', '', (string)$params['phone']);
            update_post_meta($empresa->ID, '_empresa_telefono', substr($phone, 0, 30));
        }
        
        if (isset($params['website'])) {
            update_post_meta($empresa->ID, '_empresa_website', esc_url_raw((string)$params['website']));
        }
        
        // Actualizar otros campos si vienen en el request
        $cpt_field_mapping = [
            'address' => '_empresa_direccion',
            'facebook' => '_empresa_facebook',
            'instagram' => '_empresa_instagram',
            'linkedin' => '_empresa_linkedin',
            'twitter' => '_empresa_twitter',
            'sector' => '_empresa_sector',
            'ciudad' => '_empresa_ciudad',
        ];
        
        foreach ($cpt_field_mapping as $param_key => $cpt_key) {
            if (isset($params[$param_key])) {
                if (in_array($param_key, ['facebook', 'instagram', 'linkedin', 'twitter', 'website'])) {
                    update_post_meta($empresa->ID, $cpt_key, esc_url_raw((string)$params[$param_key]));
                } else {
                    update_post_meta($empresa->ID, $cpt_key, sanitize_text_field($params[$param_key]));
                }
            }
        }
        
        // Actualizar el post si hay cambios en contenido
        if (!empty($empresa_updates)) {
            $empresa_updates['ID'] = $empresa->ID;
            wp_update_post($empresa_updates);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Perfil de empresa actualizado correctamente.',
        ], 200);
    }

    public static function get_company_profile_by_id($request)
    {
        $user_id = intval($request->get_param('user_id'));
        if ($user_id <= 0) {
            return new WP_Error('invalid_user_id', 'ID de usuario inválido.', ['status' => 400]);
        }
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'Usuario no encontrado.', ['status' => 404]);
        }
        if (!self::is_enterprise($user)) {
            return new WP_Error('not_enterprise', 'Este usuario no es una empresa.', ['status' => 400]);
        }
        return self::build_company_profile_response($user_id, $user);
    }

    public static function get_company_profile_by_name($request)
    {
        $name = isset($_GET['name']) ? sanitize_text_field((string)$_GET['name']) : '';
        if ($name === '') {
            return new WP_Error('invalid_name', 'El parámetro name es requerido.', ['status' => 400]);
        }
        $user = get_user_by('login', $name);
        if (!$user) {
            // Intentar por nicename o display_name (búsqueda simple)
            $user = get_user_by('slug', sanitize_title($name));
        }
        if (!$user) {
            return new WP_Error('user_not_found', 'Empresa no encontrada.', ['status' => 404]);
        }
        if (!self::is_enterprise($user)) {
            return new WP_Error('not_enterprise', 'Este usuario no es una empresa.', ['status' => 400]);
        }
        return self::build_company_profile_response($user->ID, $user);
    }

    private static function is_enterprise($user): bool
    {
        $roles = is_object($user) ? (array)$user->roles : [];
        return in_array('employer', $roles, true) || in_array('administrator', $roles, true);
    }

    private static function build_company_profile_response(int $user_id, $user): WP_REST_Response
    {
        // Leer datos desde CPT de empresa en lugar de user_meta
        $empresa = agrochamba_get_empresa_by_user_id($user_id);
        
        if ($empresa) {
            $description = (string)$empresa->post_content;
            $website = (string)get_post_meta($empresa->ID, '_empresa_website', true);
            $phone = (string)get_post_meta($empresa->ID, '_empresa_telefono', true);
            $address = (string)get_post_meta($empresa->ID, '_empresa_direccion', true);
            $nombre_comercial = get_post_meta($empresa->ID, '_empresa_nombre_comercial', true) ?: $user->display_name;
            
            // Logo desde featured_image
            $logo_id = get_post_thumbnail_id($empresa->ID);
            $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : null;
            
            return new WP_REST_Response([
                'user_id' => $user_id,
                'empresa_id' => $empresa->ID,
                'company_name' => $nombre_comercial,
                'description' => $description,
                'email' => $user->user_email,
                'company_website' => $website,
                'company_phone' => $phone,
                'company_address' => $address,
                'logo_url' => $logo_url,
                'company_facebook' => (string)get_post_meta($empresa->ID, '_empresa_facebook', true),
                'company_instagram' => (string)get_post_meta($empresa->ID, '_empresa_instagram', true),
                'company_linkedin' => (string)get_post_meta($empresa->ID, '_empresa_linkedin', true),
                'company_twitter' => (string)get_post_meta($empresa->ID, '_empresa_twitter', true),
                'company_sector' => (string)get_post_meta($empresa->ID, '_empresa_sector', true),
                'company_ciudad' => (string)get_post_meta($empresa->ID, '_empresa_ciudad', true),
            ], 200);
        } else {
            // Fallback a user_meta si no existe CPT (para compatibilidad)
            $description = (string)get_user_meta($user_id, 'company_description', true);
            $website = (string)get_user_meta($user_id, 'company_website', true);
            $phone = (string)get_user_meta($user_id, 'company_phone', true);
            $address = (string)get_user_meta($user_id, 'company_address', true);

            return new WP_REST_Response([
                'user_id' => $user_id,
                'company_name' => $user->display_name,
                'description' => $description,
                'email' => $user->user_email,
                'company_website' => $website,
                'company_phone' => $phone,
                'company_address' => $address,
            ], 200);
        }
    }
}
