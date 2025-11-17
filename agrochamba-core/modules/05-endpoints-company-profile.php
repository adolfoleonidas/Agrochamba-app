<?php
/**
 * =============================================================
 * MÓDULO 5: ENDPOINTS DE PERFIL DE EMPRESA
 * =============================================================
 * 
 * Endpoints:
 * - GET /agrochamba/v1/me/company-profile - Obtener perfil de empresa del usuario
 * - PUT /agrochamba/v1/me/company-profile - Actualizar perfil de empresa
 * - GET /agrochamba/v1/companies/{user_id}/profile - Ver perfil de empresa por ID
 * - GET /agrochamba/v1/companies/profile?name=NombreEmpresa - Ver perfil de empresa por nombre
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. OBTENER PERFIL DE EMPRESA DEL USUARIO ACTUAL
// ==========================================
if (!function_exists('agrochamba_get_company_profile')) {
    function agrochamba_get_company_profile($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para ver tu perfil de empresa.', array('status' => 401));
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!in_array('employer', $user->roles) && !in_array('administrator', $user->roles)) {
            return new WP_Error('not_enterprise', 'Solo las empresas pueden tener un perfil de empresa.', array('status' => 403));
        }

        $description = get_user_meta($user_id, 'company_description', true);

        return new WP_REST_Response(array(
            'user_id' => $user_id,
            'company_name' => $user->display_name,
            'description' => $description ?: '',
            'email' => $user->user_email
        ), 200);
    }
}

// ==========================================
// 2. ACTUALIZAR PERFIL DE EMPRESA
// ==========================================
if (!function_exists('agrochamba_update_company_profile')) {
    function agrochamba_update_company_profile($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para actualizar tu perfil de empresa.', array('status' => 401));
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!in_array('employer', $user->roles) && !in_array('administrator', $user->roles)) {
            return new WP_Error('not_enterprise', 'Solo las empresas pueden actualizar su perfil de empresa.', array('status' => 403));
        }

        $params = $request->get_json_params();
        $description = isset($params['description']) ? sanitize_textarea_field($params['description']) : '';

        update_user_meta($user_id, 'company_description', $description);

        // Invalidar caché del perfil de empresa
        if (function_exists('agrochamba_invalidate_company_cache')) {
            agrochamba_invalidate_company_cache($user->display_name);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Perfil de empresa actualizado correctamente.',
            'description' => $description
        ), 200);
    }
}

// ==========================================
// 3. OBTENER PERFIL DE EMPRESA POR ID
// ==========================================
if (!function_exists('agrochamba_get_company_profile_by_id')) {
    function agrochamba_get_company_profile_by_id($request) {
        $user_id = intval($request->get_param('user_id'));

        if ($user_id <= 0) {
            return new WP_Error('invalid_user_id', 'ID de usuario inválido.', array('status' => 400));
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'Usuario no encontrado.', array('status' => 404));
        }

        if (!in_array('employer', $user->roles) && !in_array('administrator', $user->roles)) {
            return new WP_Error('not_enterprise', 'Este usuario no es una empresa.', array('status' => 400));
        }

        $description = get_user_meta($user_id, 'company_description', true);

        return new WP_REST_Response(array(
            'user_id' => $user_id,
            'company_name' => $user->display_name,
            'description' => $description ?: '',
            'email' => $user->user_email
        ), 200);
    }
}

// ==========================================
// 4. OBTENER PERFIL DE EMPRESA POR NOMBRE
// ==========================================
if (!function_exists('agrochamba_get_company_profile_by_name')) {
    function agrochamba_get_company_profile_by_name($request) {
        $company_name = sanitize_text_field($request->get_param('name'));

        if (empty($company_name)) {
            return new WP_Error('invalid_name', 'Nombre de empresa requerido.', array('status' => 400));
        }

        // Buscar usuario por display_name
        $users = get_users(array(
            'meta_key' => 'display_name',
            'meta_value' => $company_name,
            'role__in' => array('employer', 'administrator'),
            'number' => 1
        ));

        if (empty($users)) {
            $user = get_user_by('login', $company_name);
            if (!$user) {
                $user = get_user_by('slug', sanitize_title($company_name));
            }
        } else {
            $user = $users[0];
        }

        if (!$user) {
            return new WP_Error('company_not_found', 'Empresa no encontrada.', array('status' => 404));
        }

        // Intentar obtener del caché
        if (function_exists('agrochamba_get_cached_company_profile')) {
            $cached = agrochamba_get_cached_company_profile($company_name);
            if ($cached !== false) {
                return new WP_REST_Response($cached, 200);
            }
        }

        $description = get_user_meta($user->ID, 'company_description', true);

        $response_data = array(
            'user_id' => $user->ID,
            'company_name' => $user->display_name,
            'description' => $description ?: '',
            'email' => $user->user_email
        );

        // Guardar en caché
        if (function_exists('agrochamba_get_cached_company_profile')) {
            $cache_key = 'agrochamba_cache_company_' . md5($company_name);
            set_transient($cache_key, $response_data, AGROCHAMBA_CACHE_COMPANY_PROFILE_TTL);
        }

        return new WP_REST_Response($response_data, 200);
    }
}

// ==========================================
// REGISTRAR ENDPOINTS
// ==========================================
add_action('rest_api_init', function () {
    $routes = rest_get_server()->get_routes();
    
    // Perfil de empresa del usuario actual
    if (!isset($routes['/agrochamba/v1/me/company-profile'])) {
        register_rest_route('agrochamba/v1', '/me/company-profile', array(
            array(
                'methods' => 'GET',
                'callback' => 'agrochamba_get_company_profile',
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'PUT',
                'callback' => 'agrochamba_update_company_profile',
                'permission_callback' => '__return_true',
            ),
        ));
    }

    // Perfil de empresa por ID
    if (!isset($routes['/agrochamba/v1/companies/(?P<user_id>\d+)/profile'])) {
        register_rest_route('agrochamba/v1', '/companies/(?P<user_id>\d+)/profile', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_company_profile_by_id',
            'permission_callback' => '__return_true',
            'args' => array(
                'user_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }

    // Perfil de empresa por nombre
    if (!isset($routes['/agrochamba/v1/companies/profile'])) {
        register_rest_route('agrochamba/v1', '/companies/profile', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_company_profile_by_name',
            'permission_callback' => '__return_true',
            'args' => array(
                'name' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return !empty($param);
                    }
                ),
            ),
        ));
    }
}, 20);

