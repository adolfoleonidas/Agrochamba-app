<?php
/**
 * =============================================================
 * MÓDULO 25: GESTIÓN DE MÚLTIPLES PÁGINAS DE FACEBOOK
 * =============================================================
 * 
 * Permite configurar y gestionar múltiples páginas de Facebook
 * para publicar trabajos en todas ellas simultáneamente.
 * 
 * Endpoints REST API:
 * - GET  /agrochamba/v1/facebook/pages - Listar páginas configuradas
 * - POST /agrochamba/v1/facebook/pages - Agregar nueva página
 * - DELETE /agrochamba/v1/facebook/pages/{id} - Eliminar página
 * - PUT /agrochamba/v1/facebook/pages/{id} - Actualizar página
 * - POST /agrochamba/v1/facebook/pages/{id}/test - Probar página
 * 
 * @package AgroChamba
 * @subpackage Modules
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// CONSTANTES
// ==========================================
define('AGROCHAMBA_FB_PAGES_OPTION', 'agrochamba_facebook_pages');

// ==========================================
// FUNCIONES DE GESTIÓN DE PÁGINAS
// ==========================================

/**
 * Obtener todas las páginas de Facebook configuradas
 * 
 * @return array Lista de páginas
 */
if (!function_exists('agrochamba_get_facebook_pages')) {
    function agrochamba_get_facebook_pages() {
        $pages = get_option(AGROCHAMBA_FB_PAGES_OPTION, array());
        
        // Migrar configuración legacy (página única) si existe
        if (empty($pages)) {
            $legacy_page_id = get_option('agrochamba_facebook_page_id', '');
            $legacy_page_token = get_option('agrochamba_facebook_page_token', '');
            
            if (!empty($legacy_page_id) && !empty($legacy_page_token)) {
                $pages = array(
                    array(
                        'id' => uniqid('page_'),
                        'page_id' => $legacy_page_id,
                        'page_name' => 'Página Principal',
                        'page_token' => $legacy_page_token,
                        'enabled' => true,
                        'created_at' => current_time('mysql'),
                        'is_primary' => true
                    )
                );
                update_option(AGROCHAMBA_FB_PAGES_OPTION, $pages);
            }
        }
        
        return $pages;
    }
}

/**
 * Guardar páginas de Facebook
 * 
 * @param array $pages Lista de páginas
 * @return bool
 */
if (!function_exists('agrochamba_save_facebook_pages')) {
    function agrochamba_save_facebook_pages($pages) {
        return update_option(AGROCHAMBA_FB_PAGES_OPTION, $pages);
    }
}

/**
 * Obtener una página por su ID interno
 * 
 * @param string $internal_id ID interno de la página
 * @return array|null
 */
if (!function_exists('agrochamba_get_facebook_page_by_id')) {
    function agrochamba_get_facebook_page_by_id($internal_id) {
        $pages = agrochamba_get_facebook_pages();
        foreach ($pages as $page) {
            if ($page['id'] === $internal_id) {
                return $page;
            }
        }
        return null;
    }
}

/**
 * Agregar una nueva página de Facebook
 * 
 * @param array $page_data Datos de la página
 * @return array|WP_Error
 */
if (!function_exists('agrochamba_add_facebook_page')) {
    function agrochamba_add_facebook_page($page_data) {
        $pages = agrochamba_get_facebook_pages();
        
        // Validar datos requeridos
        if (empty($page_data['page_id']) || empty($page_data['page_token'])) {
            return new WP_Error('missing_data', 'Se requiere Page ID y Page Token');
        }
        
        // Verificar que no exista una página con el mismo page_id
        foreach ($pages as $page) {
            if ($page['page_id'] === $page_data['page_id']) {
                return new WP_Error('duplicate', 'Esta página ya está configurada');
            }
        }
        
        // Generar ID interno único
        $new_page = array(
            'id' => uniqid('page_'),
            'page_id' => sanitize_text_field($page_data['page_id']),
            'page_name' => sanitize_text_field($page_data['page_name'] ?? 'Página ' . (count($pages) + 1)),
            'page_token' => sanitize_text_field($page_data['page_token']),
            'enabled' => isset($page_data['enabled']) ? (bool) $page_data['enabled'] : true,
            'created_at' => current_time('mysql'),
            'is_primary' => empty($pages) // La primera página es la principal
        );
        
        $pages[] = $new_page;
        agrochamba_save_facebook_pages($pages);
        
        // Devolver sin el token por seguridad
        $safe_page = $new_page;
        $safe_page['page_token'] = '***' . substr($new_page['page_token'], -4);
        
        return $safe_page;
    }
}

/**
 * Actualizar una página de Facebook
 * 
 * @param string $internal_id ID interno de la página
 * @param array $page_data Datos a actualizar
 * @return array|WP_Error
 */
if (!function_exists('agrochamba_update_facebook_page')) {
    function agrochamba_update_facebook_page($internal_id, $page_data) {
        $pages = agrochamba_get_facebook_pages();
        $found = false;
        
        foreach ($pages as &$page) {
            if ($page['id'] === $internal_id) {
                // Actualizar campos permitidos
                if (isset($page_data['page_name'])) {
                    $page['page_name'] = sanitize_text_field($page_data['page_name']);
                }
                if (isset($page_data['page_id'])) {
                    $page['page_id'] = sanitize_text_field($page_data['page_id']);
                }
                if (isset($page_data['page_token']) && !empty($page_data['page_token'])) {
                    $page['page_token'] = sanitize_text_field($page_data['page_token']);
                }
                if (isset($page_data['enabled'])) {
                    $page['enabled'] = (bool) $page_data['enabled'];
                }
                $page['updated_at'] = current_time('mysql');
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            return new WP_Error('not_found', 'Página no encontrada');
        }
        
        agrochamba_save_facebook_pages($pages);
        
        // Devolver sin el token por seguridad
        $safe_page = $page;
        $safe_page['page_token'] = '***' . substr($page['page_token'], -4);
        
        return $safe_page;
    }
}

/**
 * Eliminar una página de Facebook
 * 
 * @param string $internal_id ID interno de la página
 * @return bool|WP_Error
 */
if (!function_exists('agrochamba_delete_facebook_page')) {
    function agrochamba_delete_facebook_page($internal_id) {
        $pages = agrochamba_get_facebook_pages();
        $new_pages = array();
        $found = false;
        
        foreach ($pages as $page) {
            if ($page['id'] === $internal_id) {
                $found = true;
            } else {
                $new_pages[] = $page;
            }
        }
        
        if (!$found) {
            return new WP_Error('not_found', 'Página no encontrada');
        }
        
        // Si se eliminó la página principal, hacer la siguiente página la principal
        if (!empty($new_pages) && !array_filter($new_pages, fn($p) => $p['is_primary'] ?? false)) {
            $new_pages[0]['is_primary'] = true;
        }
        
        agrochamba_save_facebook_pages($new_pages);
        return true;
    }
}

/**
 * Probar una página de Facebook
 * 
 * @param string $internal_id ID interno de la página
 * @return array|WP_Error
 */
if (!function_exists('agrochamba_test_facebook_page')) {
    function agrochamba_test_facebook_page($internal_id) {
        $page = agrochamba_get_facebook_page_by_id($internal_id);
        
        if (!$page) {
            return new WP_Error('not_found', 'Página no encontrada');
        }
        
        // Verificar token con Facebook Graph API
        $graph_url = "https://graph.facebook.com/v18.0/{$page['page_id']}?fields=name,id,access_token&access_token={$page['page_token']}";
        
        $response = wp_remote_get($graph_url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            return new WP_Error('connection_error', 'Error de conexión: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code !== 200) {
            $error_message = isset($response_body['error']['message']) 
                ? $response_body['error']['message'] 
                : 'Error desconocido al verificar la página';
            return new WP_Error('api_error', $error_message);
        }
        
        // Actualizar el nombre de la página si está disponible
        if (isset($response_body['name'])) {
            $pages = agrochamba_get_facebook_pages();
            foreach ($pages as &$p) {
                if ($p['id'] === $internal_id) {
                    $p['page_name'] = $response_body['name'];
                    $p['last_verified'] = current_time('mysql');
                    break;
                }
            }
            agrochamba_save_facebook_pages($pages);
        }
        
        return array(
            'success' => true,
            'page_name' => $response_body['name'] ?? $page['page_name'],
            'page_id' => $response_body['id'] ?? $page['page_id'],
            'message' => 'Conexión verificada correctamente'
        );
    }
}

/**
 * Obtener páginas habilitadas para publicar
 * 
 * @return array
 */
if (!function_exists('agrochamba_get_enabled_facebook_pages')) {
    function agrochamba_get_enabled_facebook_pages() {
        $pages = agrochamba_get_facebook_pages();
        return array_filter($pages, fn($page) => $page['enabled'] ?? true);
    }
}

// ==========================================
// ENDPOINTS REST API
// ==========================================

add_action('rest_api_init', function () {
    // Listar páginas
    register_rest_route('agrochamba/v1', '/facebook/pages', array(
        'methods' => 'GET',
        'callback' => 'agrochamba_rest_get_facebook_pages',
        'permission_callback' => 'agrochamba_facebook_admin_permission'
    ));
    
    // Agregar página
    register_rest_route('agrochamba/v1', '/facebook/pages', array(
        'methods' => 'POST',
        'callback' => 'agrochamba_rest_add_facebook_page',
        'permission_callback' => 'agrochamba_facebook_admin_permission'
    ));
    
    // Actualizar página
    register_rest_route('agrochamba/v1', '/facebook/pages/(?P<id>[a-zA-Z0-9_]+)', array(
        'methods' => 'PUT',
        'callback' => 'agrochamba_rest_update_facebook_page',
        'permission_callback' => 'agrochamba_facebook_admin_permission'
    ));
    
    // Eliminar página
    register_rest_route('agrochamba/v1', '/facebook/pages/(?P<id>[a-zA-Z0-9_]+)', array(
        'methods' => 'DELETE',
        'callback' => 'agrochamba_rest_delete_facebook_page',
        'permission_callback' => 'agrochamba_facebook_admin_permission'
    ));
    
    // Probar página
    register_rest_route('agrochamba/v1', '/facebook/pages/(?P<id>[a-zA-Z0-9_]+)/test', array(
        'methods' => 'POST',
        'callback' => 'agrochamba_rest_test_facebook_page',
        'permission_callback' => 'agrochamba_facebook_admin_permission'
    ));
});

/**
 * Verificar permisos de administrador
 */
if (!function_exists('agrochamba_facebook_admin_permission')) {
    function agrochamba_facebook_admin_permission($request) {
        // Intentar validar token JWT primero
        if (function_exists('agrochamba_validate_auth')) {
            $auth_result = agrochamba_validate_auth($request);
            if ($auth_result === true || !is_wp_error($auth_result)) {
                // Verificar que sea administrador
                $user = wp_get_current_user();
                if (in_array('administrator', $user->roles)) {
                    return true;
                }
            }
        }
        
        // Fallback: verificar si está logueado como admin
        if (is_user_logged_in() && current_user_can('manage_options')) {
            return true;
        }
        
        return new WP_Error(
            'rest_forbidden',
            'Solo los administradores pueden gestionar las páginas de Facebook',
            array('status' => 403)
        );
    }
}

/**
 * GET: Listar páginas
 */
if (!function_exists('agrochamba_rest_get_facebook_pages')) {
    function agrochamba_rest_get_facebook_pages($request) {
        $pages = agrochamba_get_facebook_pages();
        
        // Ocultar tokens en la respuesta
        $safe_pages = array_map(function($page) {
            $page['page_token'] = !empty($page['page_token']) 
                ? '***' . substr($page['page_token'], -4) 
                : '';
            return $page;
        }, $pages);
        
        return rest_ensure_response(array(
            'success' => true,
            'pages' => array_values($safe_pages),
            'count' => count($safe_pages)
        ));
    }
}

/**
 * POST: Agregar página
 */
if (!function_exists('agrochamba_rest_add_facebook_page')) {
    function agrochamba_rest_add_facebook_page($request) {
        $params = $request->get_json_params();
        
        if (empty($params)) {
            $params = $request->get_params();
        }
        
        $result = agrochamba_add_facebook_page($params);
        
        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message()
            ), 400);
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Página agregada correctamente',
            'page' => $result
        ));
    }
}

/**
 * PUT: Actualizar página
 */
if (!function_exists('agrochamba_rest_update_facebook_page')) {
    function agrochamba_rest_update_facebook_page($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params();
        
        if (empty($params)) {
            $params = $request->get_params();
        }
        
        $result = agrochamba_update_facebook_page($id, $params);
        
        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message()
            ), 400);
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Página actualizada correctamente',
            'page' => $result
        ));
    }
}

/**
 * DELETE: Eliminar página
 */
if (!function_exists('agrochamba_rest_delete_facebook_page')) {
    function agrochamba_rest_delete_facebook_page($request) {
        $id = $request->get_param('id');
        
        $result = agrochamba_delete_facebook_page($id);
        
        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message()
            ), 400);
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Página eliminada correctamente'
        ));
    }
}

/**
 * POST: Probar página
 */
if (!function_exists('agrochamba_rest_test_facebook_page')) {
    function agrochamba_rest_test_facebook_page($request) {
        $id = $request->get_param('id');
        
        $result = agrochamba_test_facebook_page($id);
        
        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message()
            ), 400);
        }
        
        return rest_ensure_response($result);
    }
}

// ==========================================
// ACTUALIZAR PÁGINA DE ADMIN
// ==========================================

/**
 * Agregar sección de múltiples páginas a la configuración de Facebook
 */
add_action('admin_init', function() {
    // Esto se manejará en la página de configuración existente
});

