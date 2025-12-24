<?php
/**
 * =============================================================
 * MÓDULO 8: ENDPOINTS DE FAVORITOS, GUARDADOS Y SEGUIR EMPRESAS
 * =============================================================
 * 
 * FAVORITOS Y GUARDADOS (para trabajos/jobs):
 * - POST /agrochamba/v1/favorites - Agregar/quitar favorito de trabajo
 * - GET /agrochamba/v1/favorites - Obtener favoritos del usuario (trabajos)
 * - POST /agrochamba/v1/saved - Agregar/quitar guardado de trabajo
 * - GET /agrochamba/v1/saved - Obtener guardados del usuario (trabajos)
 * - GET /agrochamba/v1/jobs/{id}/favorite-saved-status - Verificar estado de favorito/guardado
 * 
 * SEGUIR EMPRESAS (funcionalidad independiente):
 * - POST /agrochamba/v1/companies/{id}/follow - Seguir/dejar de seguir empresa
 * - GET /agrochamba/v1/companies/{id}/follow-status - Verificar estado de seguimiento
 * 
 * NOTA: Los favoritos son para TRABAJOS, el seguir es para EMPRESAS.
 * Son funcionalidades completamente independientes y no se confunden.
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================
// SHIM DE COMPATIBILIDAD → Delegar a controlador namespaced
// =============================================================
if (!defined('AGROCHAMBA_FAVORITES_CONTROLLER_NAMESPACE_INITIALIZED')) {
    define('AGROCHAMBA_FAVORITES_CONTROLLER_NAMESPACE_INITIALIZED', true);

    // Si existe el controlador moderno, delegar y salir para evitar duplicidad
    if (class_exists('AgroChamba\\API\\Favorites\\FavoritesController')) {
        if (function_exists('error_log')) {
            error_log('AgroChamba: Delegando endpoints de favoritos a AgroChamba\\API\\Favorites\\FavoritesController (migración namespaces).');
        }
        \AgroChamba\API\Favorites\FavoritesController::init();
        return; // Evitar registrar endpoints legacy duplicados
    } else {
        if (function_exists('error_log')) {
            error_log('AgroChamba: No se encontró AgroChamba\\API\\Favorites\\FavoritesController. Usando implementación procedural legacy.');
        }
    }
}

// ==========================================
// 1. AGREGAR/QUITAR FAVORITO
// ==========================================
if (!function_exists('agrochamba_toggle_favorite')) {
    function agrochamba_toggle_favorite($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para usar favoritos.', array('status' => 401));
        }

        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        $job_id = isset($params['job_id']) ? intval($params['job_id']) : 0;

        if ($job_id <= 0) {
            return new WP_Error('invalid_job_id', 'ID de trabajo inválido.', array('status' => 400));
        }

        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'trabajo') {
            return new WP_Error('job_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        $favorites = get_user_meta($user_id, 'favorite_jobs', true);
        if (!is_array($favorites)) {
            $favorites = array();
        }

        $is_favorite = in_array($job_id, $favorites);
        
        if ($is_favorite) {
            $favorites = array_values(array_diff($favorites, array($job_id)));
            $action = 'removed';
        } else {
            if (!in_array($job_id, $favorites)) {
                $favorites[] = $job_id;
            }
            $action = 'added';
        }

        update_user_meta($user_id, 'favorite_jobs', $favorites);

        return new WP_REST_Response(array(
            'success' => true,
            'action' => $action,
            'is_favorite' => !$is_favorite,
            'favorites_count' => count($favorites)
        ), 200);
    }
}

// ==========================================
// 2. OBTENER FAVORITOS
// ==========================================
if (!function_exists('agrochamba_get_favorites')) {
    function agrochamba_get_favorites($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para ver favoritos.', array('status' => 401));
        }

        $user_id = get_current_user_id();
        $favorites = get_user_meta($user_id, 'favorite_jobs', true);
        
        if (!is_array($favorites) || empty($favorites)) {
            return new WP_REST_Response(array('jobs' => array()), 200);
        }

        $jobs = array();
        foreach ($favorites as $job_id) {
            $job = get_post($job_id);
            if ($job && $job->post_type === 'trabajo') {
                $jobs[] = array(
                    'id' => $job->ID,
                    'title' => $job->post_title,
                    'date' => $job->post_date,
                    'link' => get_permalink($job->ID)
                );
            }
        }

        return new WP_REST_Response(array('jobs' => $jobs), 200);
    }
}

// ==========================================
// 3. AGREGAR/QUITAR GUARDADO
// ==========================================
if (!function_exists('agrochamba_toggle_saved')) {
    function agrochamba_toggle_saved($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para guardar trabajos.', array('status' => 401));
        }

        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        $job_id = isset($params['job_id']) ? intval($params['job_id']) : 0;

        if ($job_id <= 0) {
            return new WP_Error('invalid_job_id', 'ID de trabajo inválido.', array('status' => 400));
        }

        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'trabajo') {
            return new WP_Error('job_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        $saved = get_user_meta($user_id, 'saved_jobs', true);
        if (!is_array($saved)) {
            $saved = array();
        }

        $is_saved = in_array($job_id, $saved);
        
        if ($is_saved) {
            $saved = array_values(array_diff($saved, array($job_id)));
            $action = 'removed';
        } else {
            if (!in_array($job_id, $saved)) {
                $saved[] = $job_id;
            }
            $action = 'added';
        }

        update_user_meta($user_id, 'saved_jobs', $saved);

        return new WP_REST_Response(array(
            'success' => true,
            'action' => $action,
            'is_saved' => !$is_saved,
            'saved_count' => count($saved)
        ), 200);
    }
}

// ==========================================
// 4. OBTENER GUARDADOS
// ==========================================
if (!function_exists('agrochamba_get_saved')) {
    function agrochamba_get_saved($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para ver trabajos guardados.', array('status' => 401));
        }

        $user_id = get_current_user_id();
        $saved = get_user_meta($user_id, 'saved_jobs', true);
        
        if (!is_array($saved) || empty($saved)) {
            return new WP_REST_Response(array('jobs' => array()), 200);
        }

        $jobs = array();
        foreach ($saved as $job_id) {
            $job = get_post($job_id);
            if ($job && $job->post_type === 'trabajo') {
                $jobs[] = array(
                    'id' => $job->ID,
                    'title' => $job->post_title,
                    'date' => $job->post_date,
                    'link' => get_permalink($job->ID)
                );
            }
        }

        return new WP_REST_Response(array('jobs' => $jobs), 200);
    }
}

// ==========================================
// 5. VERIFICAR ESTADO DE FAVORITO/GUARDADO
// ==========================================
if (!function_exists('agrochamba_check_favorite_saved_status')) {
    function agrochamba_check_favorite_saved_status($request) {
        $user_id = 0;
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
        }

        $job_id = intval($request->get_param('id'));

        if ($job_id <= 0) {
            return new WP_Error('invalid_job_id', 'ID de trabajo inválido.', array('status' => 400));
        }

        if ($user_id === 0) {
            return new WP_REST_Response(array(
                'is_favorite' => false,
                'is_saved' => false
            ), 200);
        }

        $favorites = get_user_meta($user_id, 'favorite_jobs', true);
        $saved = get_user_meta($user_id, 'saved_jobs', true);

        if (!is_array($favorites)) {
            $favorites = array();
        }
        if (!is_array($saved)) {
            $saved = array();
        }

        return new WP_REST_Response(array(
            'is_favorite' => in_array($job_id, $favorites),
            'is_saved' => in_array($job_id, $saved)
        ), 200);
    }
}

// ==========================================
// REGISTRAR ENDPOINTS
// ==========================================
add_action('rest_api_init', function () {
    $routes = rest_get_server()->get_routes();
    
    // Favoritos
    if (!isset($routes['/agrochamba/v1/favorites'])) {
        register_rest_route('agrochamba/v1', '/favorites', array(
            array(
                'methods' => 'POST',
                'callback' => 'agrochamba_toggle_favorite',
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'GET',
                'callback' => 'agrochamba_get_favorites',
                'permission_callback' => '__return_true',
            ),
        ));
    }

    // Guardados
    if (!isset($routes['/agrochamba/v1/saved'])) {
        register_rest_route('agrochamba/v1', '/saved', array(
            array(
                'methods' => 'POST',
                'callback' => 'agrochamba_toggle_saved',
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'GET',
                'callback' => 'agrochamba_get_saved',
                'permission_callback' => '__return_true',
            ),
        ));
    }

    // Estado de favorito/guardado
    if (!isset($routes['/agrochamba/v1/jobs/(?P<id>\d+)/favorite-saved-status'])) {
        register_rest_route('agrochamba/v1', '/jobs/(?P<id>\d+)/favorite-saved-status', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_check_favorite_saved_status',
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }
    
    // Seguir/Dejar de seguir empresa
    if (!isset($routes['/agrochamba/v1/companies/(?P<id>\d+)/follow'])) {
        register_rest_route('agrochamba/v1', '/companies/(?P<id>\d+)/follow', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_toggle_follow_company',
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }
    
    // Verificar estado de seguimiento de empresa
    if (!isset($routes['/agrochamba/v1/companies/(?P<id>\d+)/follow-status'])) {
        register_rest_route('agrochamba/v1', '/companies/(?P<id>\d+)/follow-status', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_check_follow_status',
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }
    
    // Obtener empresas que sigue el usuario
    if (!isset($routes['/agrochamba/v1/me/following-companies'])) {
        register_rest_route('agrochamba/v1', '/me/following-companies', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_following_companies',
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ));
    }
    
    // Obtener contadores de interacciones de un trabajo (likes, guardados, comentarios)
    if (!isset($routes['/agrochamba/v1/jobs/(?P<id>\d+)/counters'])) {
        register_rest_route('agrochamba/v1', '/jobs/(?P<id>\d+)/counters', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_job_counters',
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }
}, 20);

// ==========================================
// FUNCIONES PARA SEGUIR EMPRESAS
// ==========================================

/**
 * Agregar/quitar seguimiento de empresa
 */
if (!function_exists('agrochamba_toggle_follow_company')) {
    function agrochamba_toggle_follow_company($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para seguir empresas.', array('status' => 401));
        }

        $user_id = get_current_user_id();
        $empresa_id = intval($request->get_param('id'));

        if ($empresa_id <= 0) {
            return new WP_Error('invalid_empresa_id', 'ID de empresa inválido.', array('status' => 400));
        }

        $empresa = get_post($empresa_id);
        if (!$empresa || $empresa->post_type !== 'empresa') {
            return new WP_Error('empresa_not_found', 'Empresa no encontrada.', array('status' => 404));
        }

        // Obtener empresas que sigue el usuario
        $following = get_user_meta($user_id, 'following_companies', true);
        if (!is_array($following)) {
            $following = array();
        }

        // Obtener seguidores de la empresa
        $followers = get_post_meta($empresa_id, '_empresa_followers', true);
        if (!is_array($followers)) {
            $followers = array();
        }

        $is_following = in_array($empresa_id, $following);
        
        if ($is_following) {
            // Dejar de seguir
            $following = array_values(array_diff($following, array($empresa_id)));
            $followers = array_values(array_diff($followers, array($user_id)));
            $action = 'unfollowed';
        } else {
            // Seguir
            if (!in_array($empresa_id, $following)) {
                $following[] = $empresa_id;
            }
            if (!in_array($user_id, $followers)) {
                $followers[] = $user_id;
            }
            $action = 'followed';
        }

        // Guardar cambios
        update_user_meta($user_id, 'following_companies', $following);
        update_post_meta($empresa_id, '_empresa_followers', $followers);
        update_post_meta($empresa_id, '_empresa_followers_count', count($followers));

        return new WP_REST_Response(array(
            'success' => true,
            'action' => $action,
            'is_following' => !$is_following,
            'followers_count' => count($followers)
        ), 200);
    }
}

/**
 * Verificar estado de seguimiento de empresa
 */
if (!function_exists('agrochamba_check_follow_status')) {
    function agrochamba_check_follow_status($request) {
        $user_id = 0;
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
        }

        $empresa_id = intval($request->get_param('id'));

        if ($empresa_id <= 0) {
            return new WP_Error('invalid_empresa_id', 'ID de empresa inválido.', array('status' => 400));
        }

        // Obtener contador de seguidores
        $followers_count = get_post_meta($empresa_id, '_empresa_followers_count', true);
        if ($followers_count === '') {
            // Si no existe, calcularlo
            $followers = get_post_meta($empresa_id, '_empresa_followers', true);
            $followers_count = is_array($followers) ? count($followers) : 0;
            update_post_meta($empresa_id, '_empresa_followers_count', $followers_count);
        }

        if ($user_id === 0) {
            return new WP_REST_Response(array(
                'is_following' => false,
                'followers_count' => intval($followers_count)
            ), 200);
        }

        $following = get_user_meta($user_id, 'following_companies', true);
        if (!is_array($following)) {
            $following = array();
        }

        return new WP_REST_Response(array(
            'is_following' => in_array($empresa_id, $following),
            'followers_count' => intval($followers_count)
        ), 200);
    }
}

/**
 * Obtener empresas que sigue el usuario
 */
if (!function_exists('agrochamba_get_following_companies')) {
    function agrochamba_get_following_companies($request) {
        $user_id = get_current_user_id();
        
        $following = get_user_meta($user_id, 'following_companies', true);
        if (!is_array($following) || empty($following)) {
            return new WP_REST_Response(array(
                'companies' => array(),
                'total' => 0
            ), 200);
        }

        $companies = array();
        foreach ($following as $empresa_id) {
            $empresa = get_post($empresa_id);
            if (!$empresa || $empresa->post_type !== 'empresa' || $empresa->post_status !== 'publish') {
                continue;
            }

            $logo_id = get_post_thumbnail_id($empresa_id);
            $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
            
            $razon_social = get_post_meta($empresa_id, 'razon_social', true);
            $empresa_name = get_the_title($empresa_id);
            $display_name = !empty($razon_social) ? $razon_social : $empresa_name;

            // Contar ofertas activas
            $ofertas_args = array(
                'post_type' => 'trabajo',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'empresa',
                        'field' => 'term_id',
                        'terms' => wp_get_post_terms($empresa_id, 'empresa', array('fields' => 'ids')),
                    ),
                ),
            );
            $ofertas_query = new WP_Query($ofertas_args);
            $ofertas_count = $ofertas_query->found_posts;

            // Obtener seguidores
            $followers_count = get_post_meta($empresa_id, '_empresa_followers_count', true);
            if ($followers_count === '') {
                $followers = get_post_meta($empresa_id, '_empresa_followers', true);
                $followers_count = is_array($followers) ? count($followers) : 0;
            }

            $companies[] = array(
                'id' => $empresa_id,
                'name' => $empresa_name,
                'display_name' => $display_name,
                'logo_url' => $logo_url,
                'permalink' => get_permalink($empresa_id),
                'ofertas_count' => intval($ofertas_count),
                'followers_count' => intval($followers_count),
            );
        }

        return new WP_REST_Response(array(
            'companies' => $companies,
            'total' => count($companies)
        ), 200);
    }
}

/**
 * Obtener contadores de interacciones de un trabajo
 */
if (!function_exists('agrochamba_get_job_counters')) {
    function agrochamba_get_job_counters($request) {
        $job_id = intval($request->get_param('id'));
        
        if ($job_id <= 0) {
            return new WP_Error('invalid_job_id', 'ID de trabajo inválido.', array('status' => 400));
        }

        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'trabajo') {
            return new WP_Error('job_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        // Contar favoritos (likes)
        $favorites_count = 0;
        $users = get_users(array('fields' => 'ID'));
        foreach ($users as $user_id) {
            $favorites = get_user_meta($user_id, 'favorite_jobs', true);
            if (is_array($favorites) && in_array($job_id, $favorites)) {
                $favorites_count++;
            }
        }

        // Contar guardados
        $saved_count = 0;
        foreach ($users as $user_id) {
            $saved = get_user_meta($user_id, 'saved_jobs', true);
            if (is_array($saved) && in_array($job_id, $saved)) {
                $saved_count++;
            }
        }

        // Contar comentarios (usando comentarios de WordPress)
        $comments_count = get_comments_number($job_id);

        // Contar vistas
        // IMPORTANTE: Las vistas siempre deben ser el valor total almacenado en la BD
        // No deben verse afectadas por filtros o consultas
        $views = get_post_meta($job_id, '_trabajo_views', true);
        $views_count = intval($views);
        // Asegurar que siempre sea un número válido (mínimo 0)
        if ($views_count < 0) {
            $views_count = 0;
        }

        // Estado del usuario actual (si está logueado)
        $is_favorite = false;
        $is_saved = false;
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $user_favorites = get_user_meta($user_id, 'favorite_jobs', true);
            $user_saved = get_user_meta($user_id, 'saved_jobs', true);
            
            if (is_array($user_favorites) && in_array($job_id, $user_favorites)) {
                $is_favorite = true;
            }
            if (is_array($user_saved) && in_array($job_id, $user_saved)) {
                $is_saved = true;
            }
        }

        return new WP_REST_Response(array(
            'likes' => $favorites_count,
            'saved' => $saved_count,
            'comments' => $comments_count,
            'views' => $views_count,
            'is_favorite' => $is_favorite,
            'is_saved' => $is_saved,
        ), 200);
    }
}

