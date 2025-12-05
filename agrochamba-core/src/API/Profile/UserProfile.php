<?php
/**
 * Controlador de Perfil de Usuario
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

class UserProfile
{
    const API_NAMESPACE = 'agrochamba/v1';

    public static function init(): void
    {
        add_action('rest_api_init', [__CLASS__, 'register_routes'], 20);
    }

    public static function register_routes(): void
    {
        $routes = rest_get_server()->get_routes();

        if (!isset($routes['/' . self::API_NAMESPACE . '/me/profile'])) {
            register_rest_route(self::API_NAMESPACE, '/me/profile', [
                [
                    'methods' => 'GET',
                    'callback' => [__CLASS__, 'get_profile'],
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    },
                ],
                [
                    'methods' => 'PUT',
                    'callback' => [__CLASS__, 'update_profile'],
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    },
                ],
            ]);
        }
    }

    public static function get_profile($request)
    {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para ver tu perfil.', ['status' => 401]);
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'Usuario no encontrado.', ['status' => 404]);
        }

        $is_enterprise = in_array('employer', (array)$user->roles, true) || in_array('administrator', (array)$user->roles, true);
        
        // Para empresas, leer foto desde CPT; para usuarios normales, desde user_meta
        if ($is_enterprise) {
            $empresa = agrochamba_get_empresa_by_user_id($user_id);
            $profile_photo_id = $empresa ? get_post_thumbnail_id($empresa->ID) : null;
            $profile_photo_url = $profile_photo_id ? wp_get_attachment_image_url($profile_photo_id, 'full') : null;
        } else {
            $profile_photo_id = get_user_meta($user_id, 'profile_photo_id', true);
            $profile_photo_url = $profile_photo_id ? wp_get_attachment_image_url($profile_photo_id, 'full') : null;
        }

        // Para empresas, usar razón social del CPT como display_name si está disponible
        $display_name_to_show = $user->display_name;
        if ($is_enterprise) {
            $empresa = agrochamba_get_empresa_by_user_id($user_id);
            if ($empresa) {
                $razon_social = get_post_meta($empresa->ID, '_empresa_razon_social', true);
                $nombre_comercial = get_post_meta($empresa->ID, '_empresa_nombre_comercial', true);
                // Priorizar razón social, luego nombre comercial, luego display_name del usuario
                $display_name_to_show = !empty($razon_social) ? $razon_social : (!empty($nombre_comercial) ? $nombre_comercial : $user->display_name);
            }
        }

        $data = [
            'user_id' => $user_id,
            'username' => $user->user_login,
            'display_name' => $display_name_to_show, // Mostrar razón social para empresas
            'email' => $user->user_email,
            'first_name' => get_user_meta($user_id, 'first_name', true),
            'last_name' => get_user_meta($user_id, 'last_name', true),
            'roles' => $user->roles,
            'is_enterprise' => $is_enterprise,
            'profile_photo_id' => $profile_photo_id ? intval($profile_photo_id) : null,
            'profile_photo_url' => $profile_photo_url,
            'phone' => (string)get_user_meta($user_id, 'phone', true),
            'bio' => (string)get_user_meta($user_id, 'bio', true),
        ];

        if ($is_enterprise) {
            // Leer datos desde CPT de empresa en lugar de user_meta
            $empresa = agrochamba_get_empresa_by_user_id($user_id);
            
            if ($empresa) {
                $data = array_merge($data, [
                    'company_description' => (string)$empresa->post_content,
                    'company_address' => (string)get_post_meta($empresa->ID, '_empresa_direccion', true),
                    'company_phone' => (string)get_post_meta($empresa->ID, '_empresa_telefono', true),
                    'company_website' => (string)get_post_meta($empresa->ID, '_empresa_website', true),
                    'company_facebook' => (string)get_post_meta($empresa->ID, '_empresa_facebook', true),
                    'company_instagram' => (string)get_post_meta($empresa->ID, '_empresa_instagram', true),
                    'company_linkedin' => (string)get_post_meta($empresa->ID, '_empresa_linkedin', true),
                    'company_twitter' => (string)get_post_meta($empresa->ID, '_empresa_twitter', true),
                    'company_sector' => (string)get_post_meta($empresa->ID, '_empresa_sector', true),
                    'company_ciudad' => (string)get_post_meta($empresa->ID, '_empresa_ciudad', true),
                ]);
            } else {
                // Fallback a user_meta si no existe CPT (para compatibilidad)
                $data = array_merge($data, [
                    'company_description' => (string)get_user_meta($user_id, 'company_description', true),
                    'company_address' => (string)get_user_meta($user_id, 'company_address', true),
                    'company_phone' => (string)get_user_meta($user_id, 'company_phone', true),
                    'company_website' => (string)get_user_meta($user_id, 'company_website', true),
                    'company_facebook' => (string)get_user_meta($user_id, 'company_facebook', true),
                    'company_instagram' => (string)get_user_meta($user_id, 'company_instagram', true),
                    'company_linkedin' => (string)get_user_meta($user_id, 'company_linkedin', true),
                    'company_twitter' => (string)get_user_meta($user_id, 'company_twitter', true),
                ]);
            }
        }

        return new WP_REST_Response($data, 200);
    }

    public static function update_profile($request)
    {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para actualizar tu perfil.', ['status' => 401]);
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'Usuario no encontrado.', ['status' => 404]);
        }

        $params = $request->get_json_params();

        $updates = [];
        if (isset($params['first_name'])) {
            $updates['first_name'] = sanitize_text_field($params['first_name']);
        }
        if (isset($params['last_name'])) {
            $updates['last_name'] = sanitize_text_field($params['last_name']);
        }
        if (isset($params['display_name'])) {
            $updates['display_name'] = sanitize_text_field($params['display_name']);
        }

        // Campos libres del meta de usuario
        $meta_updates = [];
        if (isset($params['phone'])) {
            $phone = preg_replace('/[^0-9+\-\s]/', '', (string)$params['phone']);
            $meta_updates['phone'] = substr($phone, 0, 30);
        }
        if (isset($params['bio'])) {
            $bio = wp_strip_all_tags((string)$params['bio']);
            $meta_updates['bio'] = substr($bio, 0, 1000);
        }

        $is_enterprise = in_array('employer', (array)$user->roles, true) || in_array('administrator', (array)$user->roles, true);
        
        // Si es empresa, guardar datos en CPT en lugar de user_meta
        if ($is_enterprise) {
            $empresa = agrochamba_get_empresa_by_user_id($user_id);
            
            if ($empresa) {
                // Actualizar datos en el CPT de empresa
                $empresa_updates = [];
                
                // Mapeo de campos del request a campos del CPT
                $cpt_field_mapping = [
                    'company_description' => 'post_content',
                    'company_address' => '_empresa_direccion',
                    'company_phone' => '_empresa_telefono',
                    'company_website' => '_empresa_website',
                    'company_facebook' => '_empresa_facebook',
                    'company_instagram' => '_empresa_instagram',
                    'company_linkedin' => '_empresa_linkedin',
                    'company_twitter' => '_empresa_twitter',
                    'company_sector' => '_empresa_sector',
                    'company_ciudad' => '_empresa_ciudad',
                ];
                
                foreach ($cpt_field_mapping as $param_key => $cpt_key) {
                    if (isset($params[$param_key])) {
                        if ($cpt_key === 'post_content') {
                            $empresa_updates['post_content'] = sanitize_textarea_field($params[$param_key]);
                        } elseif (in_array($param_key, ['company_website', 'company_facebook', 'company_linkedin'])) {
                            update_post_meta($empresa->ID, $cpt_key, esc_url_raw((string)$params[$param_key]));
                        } else {
                            update_post_meta($empresa->ID, $cpt_key, sanitize_text_field($params[$param_key]));
                        }
                    }
                }
                
                        // Actualizar título y nombre comercial si cambió display_name
                        if (isset($updates['display_name'])) {
                            $empresa_updates['post_title'] = $updates['display_name'];
                            update_post_meta($empresa->ID, '_empresa_nombre_comercial', $updates['display_name']);
                            // También actualizar razón social si no existe
                            $razon_social = get_post_meta($empresa->ID, '_empresa_razon_social', true);
                            if (empty($razon_social)) {
                                update_post_meta($empresa->ID, '_empresa_razon_social', $updates['display_name']);
                            }
                        }
                
                // Actualizar contenido del post si hay cambios
                if (!empty($empresa_updates)) {
                    $empresa_updates['ID'] = $empresa->ID;
                    wp_update_post($empresa_updates);
                }
            } else {
                // Si no existe CPT, crear uno automáticamente
                if (function_exists('agrochamba_create_empresa_on_user_register')) {
                    agrochamba_create_empresa_on_user_register($user_id);
                    // Reintentar actualización después de crear el CPT
                    $empresa = agrochamba_get_empresa_by_user_id($user_id);
                    if ($empresa && isset($params['company_description'])) {
                        wp_update_post([
                            'ID' => $empresa->ID,
                            'post_content' => sanitize_textarea_field($params['company_description'])
                        ]);
                    }
                }
            }
        } else {
            // Para usuarios normales, guardar en user_meta como antes
            foreach ($meta_updates as $key => $value) {
                update_user_meta($user_id, $key, $value);
            }
        }

        // Aplicar updates básicos del usuario (display_name, first_name, last_name)
        if (!empty($updates)) {
            $userdata = ['ID' => $user_id] + $updates;
            wp_update_user($userdata);
        }
        
        // Para usuarios no-empresa, aplicar meta_updates
        if (!$is_enterprise) {
            foreach ($meta_updates as $key => $value) {
                update_user_meta($user_id, $key, $value);
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.',
        ], 200);
    }
}
