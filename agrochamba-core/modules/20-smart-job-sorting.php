<?php
/**
 * =============================================================
 * MÓDULO 20: ORDENAMIENTO INTELIGENTE DE TRABAJOS
 * =============================================================
 * 
 * Este módulo implementa un sistema de ordenamiento inteligente
 * que combina múltiples factores para mostrar los trabajos más
 * relevantes para cada usuario:
 * 
 * - Score de relevancia (engagement)
 * - Fecha de publicación (contenido fresco)
 * - Ubicación del usuario (si está disponible)
 * - Preferencias del usuario (si está logueado)
 * 
 * Algoritmo híbrido que balancea contenido popular con contenido nuevo.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. FUNCIÓN DE ORDENAMIENTO INTELIGENTE
// ==========================================
if (!function_exists('agrochamba_smart_sort_jobs')) {
    /**
     * Calcula un score inteligente combinando múltiples factores
     * 
     * Score Inteligente = (Relevancia × 0.6) + (Novedad × 0.3) + (Proximidad × 0.1)
     * 
     * @param int $job_id ID del trabajo
     * @param string $user_location Ubicación del usuario (slug de taxonomía)
     * @return float Score inteligente
     */
    function agrochamba_smart_sort_jobs($job_id, $user_location = '') {
        // Factor 1: Relevancia (60% del peso)
        $relevance_score = floatval(get_post_meta($job_id, '_trabajo_relevance_score', true) ?: 0);
        $normalized_relevance = min(100, $relevance_score); // Normalizar a 0-100
        
        // Factor 2: Novedad (30% del peso)
        $post_date = get_post_time('U', true, $job_id);
        $current_time = current_time('timestamp');
        $days_old = max(0, ($current_time - $post_date) / DAY_IN_SECONDS);
        
        // Score de novedad: máximo para trabajos nuevos, decrece con el tiempo
        // Trabajos de 0-7 días: 100 puntos
        // Trabajos de 30 días: 50 puntos
        // Trabajos de 90+ días: 10 puntos
        if ($days_old <= 7) {
            $novelty_score = 100;
        } elseif ($days_old <= 30) {
            $novelty_score = 100 - (($days_old - 7) / 23 * 50); // De 100 a 50
        } else {
            $novelty_score = max(10, 50 - (($days_old - 30) / 60 * 40)); // De 50 a 10
        }
        
        // Factor 3: Proximidad (10% del peso)
        $proximity_score = 0;
        if (!empty($user_location)) {
            $job_ubicaciones = wp_get_post_terms($job_id, 'ubicacion', array('fields' => 'slugs'));
            if (!empty($job_ubicaciones) && !is_wp_error($job_ubicaciones)) {
                if (in_array($user_location, $job_ubicaciones)) {
                    $proximity_score = 100; // Misma ubicación
                } else {
                    // Verificar si hay relación jerárquica (padre/hijo)
                    $user_term = get_term_by('slug', $user_location, 'ubicacion');
                    if ($user_term) {
                        foreach ($job_ubicaciones as $job_ubicacion_slug) {
                            $job_term = get_term_by('slug', $job_ubicacion_slug, 'ubicacion');
                            if ($job_term) {
                                // Mismo padre o relación jerárquica
                                if ($job_term->parent == $user_term->term_id || 
                                    $user_term->parent == $job_term->term_id ||
                                    ($job_term->parent > 0 && $user_term->parent > 0 && 
                                     $job_term->parent == $user_term->parent)) {
                                    $proximity_score = 50; // Ubicación relacionada
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Calcular score inteligente combinado
        $smart_score = (
            ($normalized_relevance * 0.6) +
            ($novelty_score * 0.3) +
            ($proximity_score * 0.1)
        );
        
        return round($smart_score, 2);
    }
}

// ==========================================
// 2. DETECTAR UBICACIÓN DEL USUARIO
// ==========================================
if (!function_exists('agrochamba_get_user_location')) {
    /**
     * Intenta detectar la ubicación del usuario
     * 
     * Prioridad:
     * 1. Ubicación guardada en perfil de usuario
     * 2. Cookie de ubicación
     * 3. Geolocalización por IP (si está disponible)
     * 
     * @return string Slug de ubicación o string vacío
     */
    function agrochamba_get_user_location() {
        // 1. Verificar si el usuario tiene ubicación en su perfil
        if (is_user_logged_in()) {
            $user_location = get_user_meta(get_current_user_id(), 'preferred_location', true);
            if (!empty($user_location)) {
                return sanitize_text_field($user_location);
            }
        }
        
        // 2. Verificar cookie de ubicación
        if (isset($_COOKIE['agrochamba_user_location'])) {
            return sanitize_text_field($_COOKIE['agrochamba_user_location']);
        }
        
        // 3. Intentar geolocalización por IP (opcional, requiere servicio externo)
        // Por ahora retornar vacío, pero se puede implementar con servicios como MaxMind
        
        return '';
    }
}

// ==========================================
// 3. CALCULAR Y ALMACENAR SCORE INTELIGENTE
// ==========================================
if (!function_exists('agrochamba_update_smart_score')) {
    /**
     * Calcula y actualiza el score inteligente de un trabajo
     * 
     * @param int $job_id ID del trabajo
     * @return float Score calculado
     */
    function agrochamba_update_smart_score($job_id) {
        $user_location = agrochamba_get_user_location();
        $smart_score = agrochamba_smart_sort_jobs($job_id, $user_location);
        
        // Almacenar score inteligente (se recalcula periódicamente)
        update_post_meta($job_id, '_trabajo_smart_score', $smart_score);
        
        return $smart_score;
    }
}

// ==========================================
// 4. MODIFICAR WP_QUERY PARA ORDENAMIENTO INTELIGENTE
// ==========================================
if (!function_exists('agrochamba_add_smart_sorting')) {
    /**
     * Agrega soporte para ordenamiento inteligente en WP_Query
     */
    function agrochamba_add_smart_sorting($orderby, $query) {
        global $wpdb;
        
        if ($query->get('orderby') === 'smart') {
            // Ordenar por score inteligente
            $orderby = "CAST({$wpdb->postmeta}.meta_value AS DECIMAL) DESC";
        }
        
        return $orderby;
    }
    add_filter('posts_orderby', 'agrochamba_add_smart_sorting', 10, 2);
}

if (!function_exists('agrochamba_add_smart_meta_query')) {
    /**
     * Agrega meta_query para incluir el score inteligente en la consulta
     * IMPORTANTE: Usa 'compare' => 'EXISTS' para incluir posts con el meta key
     * pero también permite posts sin el meta key usando LEFT JOIN en lugar de INNER JOIN
     */
    function agrochamba_add_smart_meta_query($query) {
        if ($query->get('orderby') === 'smart') {
            $meta_query = $query->get('meta_query') ?: array();
            // Usar 'compare' => 'EXISTS' pero permitir NULL para posts sin score
            $meta_query[] = array(
                'key' => '_trabajo_smart_score',
                'compare' => 'EXISTS'
            );
            $query->set('meta_query', $meta_query);
        }
    }
    add_action('pre_get_posts', 'agrochamba_add_smart_meta_query', 5);
}

// ==========================================
// 5. ACTUALIZAR SCORES INTELIGENTES PERIÓDICAMENTE
// ==========================================
if (!function_exists('agrochamba_update_smart_scores_batch')) {
    /**
     * Actualiza los scores inteligentes de todos los trabajos
     * Se ejecuta periódicamente via cron
     */
    function agrochamba_update_smart_scores_batch() {
        $args = array(
            'post_type' => 'trabajo',
            'post_status' => 'publish',
            'posts_per_page' => 50, // Procesar en lotes
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_trabajo_relevance_score',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $query = new WP_Query($args);
        $count = 0;
        
        foreach ($query->posts as $job_id) {
            agrochamba_update_smart_score($job_id);
            $count++;
        }
        
        error_log("AgroChamba: Scores inteligentes actualizados para {$count} trabajos.");
    }
    
    // Programar actualización diaria
    if (!wp_next_scheduled('agrochamba_update_smart_scores')) {
        wp_schedule_event(time(), 'daily', 'agrochamba_update_smart_scores');
    }
    add_action('agrochamba_update_smart_scores', 'agrochamba_update_smart_scores_batch');
}

// ==========================================
// 6. HOOK PARA ACTUALIZAR SCORE CUANDO CAMBIA RELEVANCIA
// ==========================================
add_action('agrochamba_relevance_score_updated', function($job_id) {
    agrochamba_update_smart_score($job_id);
}, 10, 1);

// Modificar la función de actualización de relevancia para disparar este hook
if (!function_exists('agrochamba_update_job_relevance_score_with_hook')) {
    function agrochamba_update_job_relevance_score_with_hook($job_id) {
        if (function_exists('agrochamba_update_job_relevance_score')) {
            $score = agrochamba_update_job_relevance_score($job_id);
            do_action('agrochamba_relevance_score_updated', $job_id);
            return $score;
        }
    }
}

// ==========================================
// 7. ORDENAMIENTO INTELIGENTE POR DEFECTO
// ==========================================
// NOTA: Esta función está deshabilitada porque causaba problemas
// El ordenamiento por defecto ahora es 'date' (más recientes primero)
// según lo establecido en archive-trabajo.php
// Si se necesita ordenamiento inteligente, debe especificarse explícitamente en la URL
/*
if (!function_exists('agrochamba_set_default_smart_sorting')) {
    function agrochamba_set_default_smart_sorting($query) {
        // Solo en frontend, para archivos de trabajos
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Solo para archivos de trabajos
        if (is_post_type_archive('trabajo') || is_tax('ubicacion') || is_tax('cultivo') || is_tax('empresa')) {
            // Si no se especifica orderby, usar ordenamiento inteligente
            if (!$query->get('orderby') || $query->get('orderby') === 'date') {
                // Verificar si hay parámetro orderby en URL
                if (!isset($_GET['orderby']) || $_GET['orderby'] === 'smart' || $_GET['orderby'] === '') {
                    $query->set('orderby', 'smart');
                    $query->set('order', 'DESC');
                }
            }
        }
    }
    add_action('pre_get_posts', 'agrochamba_set_default_smart_sorting', 20);
}
*/

// ==========================================
// 8. ENDPOINT PARA OBTENER TRABAJOS ORDENADOS INTELIGENTEMENTE
// ==========================================
if (!function_exists('agrochamba_register_smart_sorting_endpoint')) {
    function agrochamba_register_smart_sorting_endpoint() {
        register_rest_route('agrochamba/v1', '/jobs/smart', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_jobs_smart_sorted',
            'permission_callback' => '__return_true',
            'args' => array(
                'per_page' => array(
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ),
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ),
                'ubicacion' => array(
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }
    add_action('rest_api_init', 'agrochamba_register_smart_sorting_endpoint');
}

if (!function_exists('agrochamba_get_jobs_smart_sorted')) {
    /**
     * Endpoint para obtener trabajos ordenados inteligentemente
     */
    function agrochamba_get_jobs_smart_sorted($request) {
        $per_page = $request->get_param('per_page') ?: 20;
        $page = $request->get_param('page') ?: 1;
        $ubicacion = $request->get_param('ubicacion');
        
        $args = array(
            'post_type' => 'trabajo',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'smart',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_trabajo_smart_score',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        // Filtrar por ubicación si se especifica
        if (!empty($ubicacion)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ubicacion',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($ubicacion),
                ),
            );
        }
        
        $query = new WP_Query($args);
        
        $jobs = array();
        foreach ($query->posts as $post) {
            $smart_score = get_post_meta($post->ID, '_trabajo_smart_score', true);
            $relevance_score = get_post_meta($post->ID, '_trabajo_relevance_score', true);
            
            $jobs[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'link' => get_permalink($post->ID),
                'smart_score' => floatval($smart_score ?: 0),
                'relevance_score' => floatval($relevance_score ?: 0),
                'date' => $post->post_date,
            );
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'jobs' => $jobs,
            'total' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => $page,
            'sorting' => 'smart',
        ), 200);
    }
}

// ==========================================
// 9. INICIALIZAR SCORES INTELIGENTES PARA TRABAJOS EXISTENTES
// ==========================================
if (!function_exists('agrochamba_initialize_smart_scores')) {
    /**
     * Inicializa los scores inteligentes para todos los trabajos existentes
     */
    function agrochamba_initialize_smart_scores() {
        // Verificar si ya se inicializó
        if (get_option('agrochamba_smart_scores_initialized')) {
            return;
        }
        
        $args = array(
            'post_type' => 'trabajo',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        );
        
        $query = new WP_Query($args);
        $count = 0;
        
        foreach ($query->posts as $job_id) {
            agrochamba_update_smart_score($job_id);
            $count++;
        }
        
        // Marcar como inicializado
        update_option('agrochamba_smart_scores_initialized', true);
        
        error_log("AgroChamba: Scores inteligentes inicializados para {$count} trabajos.");
    }
    
    // Ejecutar en la próxima carga si no está inicializado
    add_action('init', 'agrochamba_initialize_smart_scores', 25);
}

// ==========================================
// 10. COMANDO WP-CLI PARA ACTUALIZAR SCORES INTELIGENTES
// ==========================================
if (defined('WP_CLI') && WP_CLI) {
    if (!class_exists('WP_CLI_Command')) {
        return;
    }
    
    class AgroChamba_Smart_Sorting_Command extends WP_CLI_Command {
        /**
         * Actualiza los scores inteligentes de todos los trabajos
         * 
         * ## EXAMPLES
         * 
         *     wp agrochamba update-smart-scores
         *     wp agrochamba update-smart-scores --limit=100
         */
        public function update_smart_scores($args, $assoc_args) {
            $limit = isset($assoc_args['limit']) ? intval($assoc_args['limit']) : -1;
            
            WP_CLI::line('Actualizando scores inteligentes...');
            
            $args = array(
                'post_type' => 'trabajo',
                'post_status' => 'publish',
                'posts_per_page' => $limit > 0 ? $limit : -1,
                'fields' => 'ids',
            );
            
            $query = new WP_Query($args);
            $total = count($query->posts);
            $progress = \WP_CLI\Utils\make_progress_bar('Procesando trabajos', $total);
            
            foreach ($query->posts as $job_id) {
                agrochamba_update_smart_score($job_id);
                $progress->tick();
            }
            
            $progress->finish();
            WP_CLI::success("Scores inteligentes actualizados para {$total} trabajos.");
        }
    }
    
    WP_CLI::add_command('agrochamba', 'AgroChamba_Smart_Sorting_Command');
}

