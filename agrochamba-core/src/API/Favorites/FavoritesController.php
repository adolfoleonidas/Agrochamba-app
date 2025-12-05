<?php
/**
 * Controlador de Favoritos y Guardados
 *
 * Maneja los favoritos y trabajos guardados por los usuarios
 *
 * @package AgroChamba
 * @subpackage API\Favorites
 * @since 2.0.0
 */

namespace AgroChamba\API\Favorites;

use WP_Error;
use WP_REST_Response;
use WP_REST_Request;

class FavoritesController {

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'agrochamba/v1';

    /**
     * Inicializar el controlador
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'), 20);
    }

    /**
     * Registrar rutas de la API
     */
    public static function register_routes() {
        $routes = rest_get_server()->get_routes();

        // Endpoint de Favoritos (GET y POST)
        if (!isset($routes['/' . self::API_NAMESPACE . '/favorites'])) {
            register_rest_route(self::API_NAMESPACE, '/favorites', array(
                array(
                    'methods' => 'POST',
                    'callback' => array(__CLASS__, 'toggle_favorite'),
                    'permission_callback' => '__return_true',
                ),
                array(
                    'methods' => 'GET',
                    'callback' => array(__CLASS__, 'get_favorites'),
                    'permission_callback' => '__return_true',
                ),
            ));
        }

        // Endpoint de Guardados (GET y POST)
        if (!isset($routes['/' . self::API_NAMESPACE . '/saved'])) {
            register_rest_route(self::API_NAMESPACE, '/saved', array(
                array(
                    'methods' => 'POST',
                    'callback' => array(__CLASS__, 'toggle_saved'),
                    'permission_callback' => '__return_true',
                ),
                array(
                    'methods' => 'GET',
                    'callback' => array(__CLASS__, 'get_saved'),
                    'permission_callback' => '__return_true',
                ),
            ));
        }

        // Verificar estado de favorito/guardado
        if (!isset($routes['/' . self::API_NAMESPACE . '/jobs/(?P<id>\d+)/favorite-saved-status'])) {
            register_rest_route(self::API_NAMESPACE, '/jobs/(?P<id>\d+)/favorite-saved-status', array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'check_status'),
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
    }

    /**
     * Agregar/quitar favorito
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function toggle_favorite($request) {
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

    /**
     * Obtener favoritos del usuario
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function get_favorites($request) {
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

    /**
     * Agregar/quitar guardado
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function toggle_saved($request) {
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

    /**
     * Obtener guardados del usuario
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function get_saved($request) {
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

    /**
     * Verificar estado de favorito/guardado
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function check_status($request) {
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
