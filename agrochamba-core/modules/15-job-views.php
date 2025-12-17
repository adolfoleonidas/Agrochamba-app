<?php
/**
 * =============================================================
 * MÓDULO 15: CONTEO DE VISTAS DE TRABAJOS
 * =============================================================
 * 
 * Sistema para contar y mostrar las vistas de cada trabajo
 * 
 * Funciones:
 * - Contar vistas cuando se visita un trabajo
 * - Endpoint para obtener contador de vistas
 * - Hook para incrementar vistas automáticamente
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. INCREMENTAR VISTAS AL VISITAR TRABAJO
// ==========================================
// Este sistema cuenta las vistas CADA VEZ que alguien visita la URL específica de un trabajo
// Ejemplo: https://tudominio.com/trabajo/nombre-del-trabajo/
// Las vistas se guardan en la base de datos en wp_postmeta con la clave '_trabajo_views'
if (!function_exists('agrochamba_track_job_view')) {
    /**
     * Incrementar contador de vistas de un trabajo
     * 
     * Esta función se ejecuta cada vez que alguien visita la página individual de un trabajo
     * Guarda el contador en la base de datos (wp_postmeta)
     * 
     * @param int $post_id ID del trabajo
     * @return int Nuevo número de vistas
     */
    function agrochamba_track_job_view($post_id) {
        if (!$post_id || get_post_type($post_id) !== 'trabajo') {
            return 0;
        }

        // Obtener contador actual desde la base de datos
        $views = get_post_meta($post_id, '_trabajo_views', true);
        $views = intval($views);
        
        // Si no existe o es negativo, inicializar en 0
        if ($views < 0) {
            $views = 0;
        }
        
        // Incrementar el contador
        $views++;
        
        // Guardar en la base de datos
        $result = update_post_meta($post_id, '_trabajo_views', $views);
        
        // Si falló update_post_meta (porque no existe), crear el meta con add_post_meta
        if (!$result) {
            add_post_meta($post_id, '_trabajo_views', $views, true);
        }
        
        return $views;
    }
    
    // Hook para contar vistas cuando se visita un trabajo (página individual)
    // Este hook se ejecuta CADA VEZ que alguien visita la URL específica de un trabajo
    // Ejemplo: cuando alguien visita https://tudominio.com/trabajo/nombre-del-trabajo/
    add_action('wp', function() {
        // Verificar si estamos en una página individual de trabajo
        if (is_singular('trabajo') && !is_admin() && !wp_doing_ajax()) {
            global $post;
            $post_id = $post ? $post->ID : get_the_ID();
            
            // Verificar que tenemos un ID válido y es un trabajo
            if ($post_id && get_post_type($post_id) === 'trabajo') {
                // NO contar si:
                // 1. Es preview (modo edición/previsualización)
                // 2. El usuario puede editar el post (autor/admin editando desde el frontend)
                $is_preview = isset($_GET['preview']) && $_GET['preview'] === 'true';
                $can_edit = current_user_can('edit_post', $post_id);
                
                if (!$is_preview && !$can_edit) {
                    // CONTAR LA VISTA - esto se ejecuta cada vez que alguien visita la URL del trabajo
                    // Se guarda en la base de datos en la tabla wp_postmeta con la clave '_trabajo_views'
                    agrochamba_track_job_view($post_id);
                }
            }
        }
    }, 99);
}

// ==========================================
// 2. ENDPOINTS PARA VISTAS
// ==========================================
if (!function_exists('agrochamba_register_views_routes')) {
    function agrochamba_register_views_routes() {
        // Obtener contador de vistas de un trabajo
        register_rest_route('agrochamba/v1', '/jobs/(?P<id>\d+)/views', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_job_views',
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
        
        // Incrementar vistas (para tracking desde JavaScript si es necesario)
        register_rest_route('agrochamba/v1', '/jobs/(?P<id>\d+)/views', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_increment_job_views',
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
    add_action('rest_api_init', 'agrochamba_register_views_routes');
}

/**
 * Obtener contador de vistas de un trabajo
 */
if (!function_exists('agrochamba_get_job_views')) {
    function agrochamba_get_job_views($request) {
        $job_id = intval($request->get_param('id'));
        
        if ($job_id <= 0) {
            return new WP_Error('invalid_job_id', 'ID de trabajo inválido.', array('status' => 400));
        }

        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'trabajo') {
            return new WP_Error('job_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        $views = get_post_meta($job_id, '_trabajo_views', true);
        $views = intval($views);

        return new WP_REST_Response(array(
            'views' => $views,
            'job_id' => $job_id
        ), 200);
    }
}

/**
 * Incrementar vistas de un trabajo
 */
if (!function_exists('agrochamba_increment_job_views')) {
    function agrochamba_increment_job_views($request) {
        $job_id = intval($request->get_param('id'));
        
        if ($job_id <= 0) {
            return new WP_Error('invalid_job_id', 'ID de trabajo inválido.', array('status' => 400));
        }

        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'trabajo') {
            return new WP_Error('job_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }

        $views = agrochamba_track_job_view($job_id);

        return new WP_REST_Response(array(
            'success' => true,
            'views' => $views,
            'job_id' => $job_id
        ), 200);
    }
}

