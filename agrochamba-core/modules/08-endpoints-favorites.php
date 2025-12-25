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
        
        // Disparar hook para recalcular score de relevancia
        do_action('agrochamba_favorite_toggled', $job_id);

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
        
        // Disparar hook para recalcular score de relevancia
        do_action('agrochamba_saved_toggled', $job_id);

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

        // Contar compartidos
        $shared_count = intval(get_post_meta($job_id, '_trabajo_shared_count', true) ?: 0);

        return new WP_REST_Response(array(
            'likes' => $favorites_count,
            'saved' => $saved_count,
            'comments' => $comments_count,
            'views' => $views_count,
            'shared' => $shared_count,
            'is_favorite' => $is_favorite,
            'is_saved' => $is_saved,
        ), 200);
    }
}

// ==========================================
// ENDPOINT PARA REGISTRAR COMPARTIDOS
// ==========================================
if (!function_exists('agrochamba_register_share_endpoint')) {
    function agrochamba_register_share_endpoint() {
        register_rest_route('agrochamba/v1', '/jobs/(?P<id>\d+)/share', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_track_job_share',
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
    add_action('rest_api_init', 'agrochamba_register_share_endpoint');
}

if (!function_exists('agrochamba_track_job_share')) {
    /**
     * Endpoint para registrar cuando se comparte un trabajo
     */
    function agrochamba_track_job_share($request) {
        $job_id = intval($request->get_param('id'));
        
        if ($job_id <= 0) {
            return new WP_Error('invalid_job_id', 'ID de trabajo inválido.', array('status' => 400));
        }
        
        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'trabajo') {
            return new WP_Error('job_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }
        
        // Incrementar contador de compartidos
        $new_count = agrochamba_increment_job_shared_count($job_id);
        
        return new WP_REST_Response(array(
            'success' => true,
            'shared_count' => $new_count,
            'message' => 'Compartido registrado correctamente.'
        ), 200);
    }
}

// ==========================================
// ENDPOINT PARA CARGAR MÁS TRABAJOS (AJAX)
// ==========================================
if (!function_exists('agrochamba_register_load_more_endpoint')) {
    function agrochamba_register_load_more_endpoint() {
        register_rest_route('agrochamba/v1', '/jobs/load-more', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_load_more_jobs',
            'permission_callback' => '__return_true',
            'args' => array(
                'page' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'ubicacion' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'cultivo' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'empresa' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                's' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'orderby' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => 'date',
                ),
            ),
        ));
    }
    add_action('rest_api_init', 'agrochamba_register_load_more_endpoint');
}

if (!function_exists('agrochamba_load_more_jobs')) {
    /**
     * Endpoint para cargar más trabajos vía AJAX
     */
    function agrochamba_load_more_jobs($request) {
        $page = intval($request->get_param('page'));
        $ubicacion = $request->get_param('ubicacion');
        $cultivo = $request->get_param('cultivo');
        $empresa = $request->get_param('empresa');
        $search = $request->get_param('s');
        $orderby = $request->get_param('orderby') ?: 'date';
        
        // Configurar query args similar a archive-trabajo.php
        $args = array(
            'post_type' => 'trabajo',
            'post_status' => 'publish',
            'paged' => $page,
            'posts_per_page' => get_option('posts_per_page', 12),
        );
        
        // Aplicar filtros de taxonomía
        $tax_query = array('relation' => 'AND');
        
        if (!empty($ubicacion)) {
            $tax_query[] = array(
                'taxonomy' => 'ubicacion',
                'field' => 'slug',
                'terms' => $ubicacion,
            );
        }
        
        if (!empty($cultivo)) {
            $tax_query[] = array(
                'taxonomy' => 'cultivo',
                'field' => 'slug',
                'terms' => $cultivo,
            );
        }
        
        if (!empty($empresa)) {
            $tax_query[] = array(
                'taxonomy' => 'empresa',
                'field' => 'slug',
                'terms' => $empresa,
            );
        }
        
        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }
        
        // Búsqueda
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        // Ordenamiento
        if ($orderby === 'relevance') {
            $args['meta_key'] = '_trabajo_relevance_score';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
        } elseif ($orderby === 'smart') {
            $args['meta_key'] = '_trabajo_smart_score';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
        } else {
            // Por defecto: más recientes
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            return new WP_REST_Response(array(
                'success' => false,
                'html' => '',
                'has_more' => false,
                'message' => 'No hay más trabajos disponibles.'
            ), 200);
        }
        
        // Capturar el HTML de las cards
        ob_start();
        
        while ($query->have_posts()): $query->the_post();
            $trabajo_id = get_the_ID();
            
            // Obtener datos del trabajo (mismo código que archive-trabajo.php)
            $salario_min = get_post_meta($trabajo_id, 'salario_min', true);
            $salario_max = get_post_meta($trabajo_id, 'salario_max', true);
            $vacantes = get_post_meta($trabajo_id, 'vacantes', true);
            $alojamiento = get_post_meta($trabajo_id, 'alojamiento', true);
            $transporte = get_post_meta($trabajo_id, 'transporte', true);
            $alimentacion = get_post_meta($trabajo_id, 'alimentacion', true);
            
            // Obtener taxonomías
            $ubicaciones = wp_get_post_terms($trabajo_id, 'ubicacion', array('fields' => 'names'));
            $cultivos = wp_get_post_terms($trabajo_id, 'cultivo', array('fields' => 'names'));
            $empresas = wp_get_post_terms($trabajo_id, 'empresa', array('fields' => 'names'));
            
            $ubicacion = !empty($ubicaciones) ? $ubicaciones[0] : '';
            $cultivo = !empty($cultivos) ? $cultivos[0] : '';
            $empresa = !empty($empresas) ? $empresas[0] : '';
            
            // Imagen destacada
            $featured_image_id = get_post_thumbnail_id($trabajo_id);
            $featured_image_url = $featured_image_id ? wp_get_attachment_image_url($featured_image_id, 'large') : null;
            $featured_image_srcset = $featured_image_id ? wp_get_attachment_image_srcset($featured_image_id, 'large') : null;
            $featured_image_sizes = $featured_image_id ? wp_get_attachment_image_sizes($featured_image_id, 'large') : null;
            
            // Calcular salario
            $salario_text = '';
            if ($salario_min && $salario_max) {
                $salario_text = 'S/ ' . number_format($salario_min, 0, '.', ',') . ' - S/ ' . number_format($salario_max, 0, '.', ',');
            } elseif ($salario_min) {
                $salario_text = 'Desde S/ ' . number_format($salario_min, 0, '.', ',');
            }
            
            // Badge
            $badge = '';
            $badge_class = '';
            $post_date = get_the_date('U');
            $days_old = (current_time('timestamp') - $post_date) / (60 * 60 * 24);
            
            if ($days_old <= 7) {
                $badge = 'Nuevo';
                $badge_class = 'badge-new';
            } elseif (($vacantes && intval($vacantes) >= 5) || ($salario_min && intval($salario_min) >= 3000)) {
                $badge = 'Urgente';
                $badge_class = 'badge-urgent';
            } elseif ($alojamiento || $transporte || $alimentacion) {
                $badge = 'Con beneficios';
                $badge_class = 'badge-benefits';
            } elseif ($salario_min && intval($salario_min) >= 2000) {
                $badge = 'Buen salario';
                $badge_class = 'badge-salary';
            }
            
            // Excerpt
            $excerpt = get_the_excerpt();
            if (empty($excerpt)) {
                $excerpt = wp_trim_words(get_the_content(), 20);
            }
            
            // Obtener contadores
            $views = get_post_meta($trabajo_id, '_trabajo_views', true);
            $views_count = intval($views);
            if ($views_count < 0) {
                $views_count = 0;
            }
            
            // Contar favoritos
            $favorites_count = 0;
            $users = get_users(array('fields' => 'ID'));
            foreach ($users as $user_id) {
                $favorites = get_user_meta($user_id, 'favorite_jobs', true);
                if (is_array($favorites) && in_array($trabajo_id, $favorites)) {
                    $favorites_count++;
                }
            }
            
            // Contar guardados
            $saved_count = 0;
            foreach ($users as $user_id) {
                $saved = get_user_meta($user_id, 'saved_jobs', true);
                if (is_array($saved) && in_array($trabajo_id, $saved)) {
                    $saved_count++;
                }
            }
            
            // Contar comentarios
            $comments_count = get_comments_number($trabajo_id);
            
            // Contar compartidos
            $shared_count = intval(get_post_meta($trabajo_id, '_trabajo_shared_count', true) ?: 0);
            
            // Estado del usuario actual
            $is_favorite = false;
            $is_saved = false;
            if (is_user_logged_in()) {
                $current_user_id = get_current_user_id();
                $user_favorites = get_user_meta($current_user_id, 'favorite_jobs', true);
                $user_saved = get_user_meta($current_user_id, 'saved_jobs', true);
                
                if (is_array($user_favorites) && in_array($trabajo_id, $user_favorites)) {
                    $is_favorite = true;
                }
                if (is_array($user_saved) && in_array($trabajo_id, $user_saved)) {
                    $is_saved = true;
                }
            }
            
            // Renderizar card HTML directamente
            $job_title = get_the_title();
            $job_permalink = get_permalink();
            $job_time = human_time_diff(get_the_time('U'), current_time('timestamp')) . ' atrás';
            
            // Generar HTML de la card
            $card_html = '<article class="trabajo-card" data-job-id="' . esc_attr($trabajo_id) . '">';
            $card_html .= '<a href="' . esc_url($job_permalink) . '" class="trabajo-card-link">';
            
            // Imagen
            if ($featured_image_url) {
                $card_html .= '<div class="trabajo-card-image">';
                $card_html .= '<img src="' . esc_url($featured_image_url) . '" alt="' . esc_attr($job_title) . '"';
                if ($featured_image_srcset) {
                    $card_html .= ' srcset="' . esc_attr($featured_image_srcset) . '"';
                }
                if ($featured_image_sizes) {
                    $card_html .= ' sizes="' . esc_attr($featured_image_sizes) . '"';
                }
                $card_html .= ' loading="lazy">';
                if ($badge) {
                    $card_html .= '<span class="trabajo-badge ' . esc_attr($badge_class) . '">' . esc_html($badge) . '</span>';
                }
                $card_html .= '</div>';
            } else {
                $card_html .= '<div class="trabajo-card-image-placeholder">';
                $card_html .= '<svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
                if ($badge) {
                    $card_html .= '<span class="trabajo-badge ' . esc_attr($badge_class) . '">' . esc_html($badge) . '</span>';
                }
                $card_html .= '</div>';
            }
            
            // Contenido
            $card_html .= '<div class="trabajo-card-content">';
            $card_html .= '<h2 class="trabajo-card-title">' . esc_html($job_title) . '</h2>';
            
            if ($empresa) {
                $card_html .= '<div class="trabajo-card-empresa">';
                $card_html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>';
                $card_html .= '<span>' . esc_html($empresa) . '</span></div>';
            }
            
            $card_html .= '<div class="trabajo-card-info">';
            if ($ubicacion) {
                $card_html .= '<div class="info-item">';
                $card_html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
                $card_html .= '<span>' . esc_html($ubicacion) . '</span></div>';
            }
            if ($salario_text) {
                $card_html .= '<div class="info-item">';
                $card_html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>';
                $card_html .= '<span>' . esc_html($salario_text) . '</span></div>';
            }
            $card_html .= '</div>';
            
            if ($excerpt) {
                $card_html .= '<p class="trabajo-card-excerpt">' . esc_html(wp_trim_words($excerpt, 15)) . '</p>';
            }
            
            $card_html .= '<div class="trabajo-card-footer">';
            $card_html .= '<span class="trabajo-card-date">' . esc_html($job_time) . '</span>';
            if ($vacantes && intval($vacantes) > 1) {
                $card_html .= '<span class="trabajo-card-vacantes">' . esc_html($vacantes) . ' vacantes</span>';
            }
            $card_html .= '</div></div></a>';
            
            // Interacciones
            $card_html .= '<div class="trabajo-card-interactions">';
            $card_html .= '<div class="interaction-counters">';
            $card_html .= '<div class="counter-group">';
            $card_html .= '<span class="counter-item"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
            $card_html .= '<span class="counter-value" data-counter="likes">' . esc_html($favorites_count) . '</span></span>';
            $card_html .= '<span class="counter-item"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';
            $card_html .= '<span class="counter-value" data-counter="comments">' . esc_html($comments_count) . '</span></span>';
            if ($shared_count > 0) {
                $card_html .= '<span class="counter-item"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>';
                $card_html .= '<span class="counter-value" data-counter="shared">' . esc_html($shared_count) . '</span></span>';
            }
            $card_html .= '</div>';
            $card_html .= '<div class="counter-group">';
            $card_html .= '<span class="counter-item views-counter"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
            $card_html .= '<span class="counter-value" data-counter="views">' . esc_html($views_count) . '</span></span>';
            $card_html .= '</div></div>';
            
            // Botones de acción
            $card_html .= '<div class="interaction-buttons">';
            if (is_user_logged_in()) {
                $like_class = $is_favorite ? 'active' : '';
                $like_fill = $is_favorite ? 'currentColor' : 'none';
                $card_html .= '<button class="interaction-btn like-btn ' . $like_class . '" data-job-id="' . esc_attr($trabajo_id) . '" onclick="event.preventDefault(); toggleLike(' . esc_js($trabajo_id) . ', this);">';
                $card_html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="' . $like_fill . '" stroke="currentColor" stroke-width="2"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
                $card_html .= '<span class="btn-text">Me gusta</span>';
                if ($favorites_count > 0) {
                    $card_html .= '<span class="btn-count" data-count="' . esc_attr($favorites_count) . '">' . esc_html($favorites_count) . '</span>';
                }
                $card_html .= '</button>';
                
                $card_html .= '<a href="' . esc_url($job_permalink . '#comments') . '" class="interaction-btn comment-btn">';
                $card_html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';
                $card_html .= '<span class="btn-text">Comentar</span>';
                if ($comments_count > 0) {
                    $card_html .= '<span class="btn-count">' . esc_html($comments_count) . '</span>';
                }
                $card_html .= '</a>';
                
                $card_html .= '<button class="interaction-btn share-btn" data-job-id="' . esc_attr($trabajo_id) . '" data-job-title="' . esc_attr($job_title) . '" data-job-url="' . esc_url($job_permalink) . '" onclick="event.preventDefault(); shareJob(' . esc_js($trabajo_id) . ', this);">';
                $card_html .= '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 14l4 -4l-4 -4" /><path d="M19 10h-11a4 4 0 1 0 0 8h1" /></svg>';
                $card_html .= '<span class="btn-text">Compartir</span></button>';
                
                // Menú de tres puntos (solo para usuarios logueados)
                $save_class = $is_saved ? 'active' : '';
                $save_fill = $is_saved ? 'currentColor' : 'none';
                $card_html .= '<div class="more-options-wrapper">';
                $card_html .= '<button class="interaction-btn more-options-btn" onclick="event.preventDefault(); toggleMoreOptions(this);">';
                $card_html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>';
                $card_html .= '</button>';
                $card_html .= '<div class="more-options-menu" style="display: none;">';
                $card_html .= '<button class="more-options-item save-btn-menu ' . $save_class . '" data-job-id="' . esc_attr($trabajo_id) . '" onclick="event.preventDefault(); toggleSave(' . esc_js($trabajo_id) . ', this);">';
                $card_html .= '<svg width="18" height="18" viewBox="0 0 24 24" fill="' . $save_fill . '" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>';
                $card_html .= '<span>' . ($is_saved ? 'Guardado' : 'Guardar') . '</span></button></div></div>';
            } else {
                $card_html .= '<a href="' . esc_url(wp_login_url($job_permalink)) . '" class="interaction-btn like-btn">';
                $card_html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
                $card_html .= '<span class="btn-text">Me gusta</span>';
                if ($favorites_count > 0) {
                    $card_html .= '<span class="btn-count" data-count="' . esc_attr($favorites_count) . '">' . esc_html($favorites_count) . '</span>';
                }
                $card_html .= '</a>';
                
                $card_html .= '<a href="' . esc_url(wp_login_url($job_permalink . '#comments')) . '" class="interaction-btn comment-btn">';
                $card_html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';
                $card_html .= '<span class="btn-text">Comentar</span>';
                if ($comments_count > 0) {
                    $card_html .= '<span class="btn-count">' . esc_html($comments_count) . '</span>';
                }
                $card_html .= '</a>';
                
                $card_html .= '<button class="interaction-btn share-btn" data-job-id="' . esc_attr($trabajo_id) . '" data-job-title="' . esc_attr($job_title) . '" data-job-url="' . esc_url($job_permalink) . '" onclick="event.preventDefault(); shareJob(' . esc_js($trabajo_id) . ', this);">';
                $card_html .= '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 14l4 -4l-4 -4" /><path d="M19 10h-11a4 4 0 1 0 0 8h1" /></svg>';
                $card_html .= '<span class="btn-text">Compartir</span></button>';
                // NO incluir menú de tres puntos para usuarios no logueados
            }
            $card_html .= '</div></div></article>';
            
            echo $card_html;
        endwhile;
        
        wp_reset_postdata();
        
        $html = ob_get_clean();
        
        return new WP_REST_Response(array(
            'success' => true,
            'html' => $html,
            'has_more' => $page < $query->max_num_pages,
            'current_page' => $page,
            'max_pages' => $query->max_num_pages,
        ), 200);
    }
}

