<?php
/**
 * =============================================================
 * MÓDULO 19: SISTEMA DE SCORING DE RELEVANCIA PARA TRABAJOS
 * =============================================================
 * 
 * Este módulo implementa un sistema de scoring similar a Facebook
 * que calcula la relevancia de cada trabajo basándose en:
 * - Me gusta (likes)
 * - Comentarios
 * - Guardados
 * - Compartidos (si se implementa)
 * - Vistas
 * - Tiempo desde publicación (decay factor)
 * 
 * El score se almacena en post_meta para optimización y se recalcula
 * automáticamente cuando cambian los contadores.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. FUNCIÓN PARA CALCULAR SCORE DE RELEVANCIA
// ==========================================
if (!function_exists('agrochamba_calculate_relevance_score')) {
    /**
     * Calcula el score de relevancia de un trabajo
     * 
     * Fórmula inspirada en algoritmos de redes sociales:
     * Score = (Likes × 1.0 + Comentarios × 2.0 + Guardados × 3.0 + 
     *          Compartidos × 4.0 + Vistas × 0.1) / Decay Factor
     * 
     * Decay Factor: Reduce el score con el tiempo para dar prioridad
     * a contenido más reciente. Fórmula: 1 + (días desde publicación / 30)
     * 
     * @param int $job_id ID del trabajo
     * @return float Score de relevancia
     */
    function agrochamba_calculate_relevance_score($job_id) {
        // Pesos de cada señal de engagement (ajustables)
        $weights = array(
            'likes' => 1.0,        // Me gusta: peso base
            'comments' => 2.0,     // Comentarios: más valiosos que likes
            'saved' => 3.0,        // Guardados: indican interés a largo plazo
            'shared' => 4.0,       // Compartidos: máximo engagement (si se implementa)
            'views' => 0.1,        // Vistas: peso bajo para evitar manipulación
        );
        
        // Obtener contadores
        $likes = agrochamba_get_job_likes_count($job_id);
        $comments = get_comments_number($job_id);
        $saved = agrochamba_get_job_saved_count($job_id);
        $shared = agrochamba_get_job_shared_count($job_id); // Por ahora 0
        $views = intval(get_post_meta($job_id, '_trabajo_views', true) ?: 0);
        
        // Calcular score base
        $base_score = (
            ($likes * $weights['likes']) +
            ($comments * $weights['comments']) +
            ($saved * $weights['saved']) +
            ($shared * $weights['shared']) +
            ($views * $weights['views'])
        );
        
        // Aplicar decay factor (reducir score con el tiempo)
        $post_date = get_post_time('U', true, $job_id);
        $current_time = current_time('timestamp');
        $days_old = max(0, ($current_time - $post_date) / DAY_IN_SECONDS);
        
        // Decay factor: aumenta con el tiempo, reduciendo el score
        // Trabajos nuevos (0 días) tienen factor 1.0
        // Trabajos de 30 días tienen factor 2.0
        // Trabajos de 90 días tienen factor 4.0
        $decay_factor = 1.0 + ($days_old / 30.0);
        
        // Score final
        $final_score = $base_score / $decay_factor;
        
        // Asegurar que el score sea positivo
        return max(0, round($final_score, 2));
    }
}

// ==========================================
// 2. FUNCIONES AUXILIARES PARA CONTADORES
// ==========================================
if (!function_exists('agrochamba_get_job_likes_count')) {
    /**
     * Obtiene el número de likes (favoritos) de un trabajo
     */
    function agrochamba_get_job_likes_count($job_id) {
        $count = 0;
        $users = get_users(array('fields' => 'ID'));
        foreach ($users as $user_id) {
            $favorites = get_user_meta($user_id, 'favorite_jobs', true);
            if (is_array($favorites) && in_array($job_id, $favorites)) {
                $count++;
            }
        }
        return $count;
    }
}

if (!function_exists('agrochamba_get_job_saved_count')) {
    /**
     * Obtiene el número de guardados de un trabajo
     */
    function agrochamba_get_job_saved_count($job_id) {
        $count = 0;
        $users = get_users(array('fields' => 'ID'));
        foreach ($users as $user_id) {
            $saved = get_user_meta($user_id, 'saved_jobs', true);
            if (is_array($saved) && in_array($job_id, $saved)) {
                $count++;
            }
        }
        return $count;
    }
}

if (!function_exists('agrochamba_get_job_shared_count')) {
    /**
     * Obtiene el número de compartidos de un trabajo
     * Por ahora retorna 0, pero se puede implementar tracking de compartidos
     */
    function agrochamba_get_job_shared_count($job_id) {
        // Por ahora retornar 0, pero se puede implementar:
        // return intval(get_post_meta($job_id, '_trabajo_shared_count', true) ?: 0);
        return 0;
    }
}

// ==========================================
// 3. FUNCIÓN PARA ACTUALIZAR SCORE
// ==========================================
if (!function_exists('agrochamba_update_job_relevance_score')) {
    /**
     * Calcula y actualiza el score de relevancia de un trabajo
     * 
     * @param int $job_id ID del trabajo
     * @return float Score calculado
     */
    function agrochamba_update_job_relevance_score($job_id) {
        $score = agrochamba_calculate_relevance_score($job_id);
        update_post_meta($job_id, '_trabajo_relevance_score', $score);
        
        // Disparar hook para actualizar score inteligente
        do_action('agrochamba_relevance_score_updated', $job_id);
        
        return $score;
    }
}

// ==========================================
// 4. HOOKS PARA RECALCULAR SCORE AUTOMÁTICAMENTE
// ==========================================

// Recalcular cuando se agrega/quita un favorito
add_action('agrochamba_favorite_toggled', function($job_id) {
    agrochamba_update_job_relevance_score($job_id);
}, 10, 1);

// Recalcular cuando se agrega/quita un guardado
add_action('agrochamba_saved_toggled', function($job_id) {
    agrochamba_update_job_relevance_score($job_id);
}, 10, 1);

// Recalcular cuando se agrega un comentario
add_action('wp_insert_comment', function($comment_id, $comment) {
    if ($comment->comment_post_ID && get_post_type($comment->comment_post_ID) === 'trabajo') {
        agrochamba_update_job_relevance_score($comment->comment_post_ID);
    }
}, 10, 2);

// Recalcular cuando se elimina un comentario
add_action('delete_comment', function($comment_id) {
    $comment = get_comment($comment_id);
    if ($comment && get_post_type($comment->comment_post_ID) === 'trabajo') {
        agrochamba_update_job_relevance_score($comment->comment_post_ID);
    }
});

// Recalcular cuando se actualiza el contador de vistas
add_action('agrochamba_job_view_counted', function($job_id) {
    // Solo recalcular cada 10 vistas para optimizar rendimiento
    $views = intval(get_post_meta($job_id, '_trabajo_views', true) ?: 0);
    if ($views % 10 === 0) {
        agrochamba_update_job_relevance_score($job_id);
    }
}, 10, 1);

// Recalcular cuando se publica un nuevo trabajo
add_action('publish_trabajo', function($post_id) {
    agrochamba_update_job_relevance_score($post_id);
}, 10, 1);

// ==========================================
// 5. MODIFICAR QUERIES PARA ORDENAR POR RELEVANCIA
// ==========================================
if (!function_exists('agrochamba_add_relevance_orderby')) {
    /**
     * Agrega soporte para ordenar por relevancia en WP_Query
     */
    function agrochamba_add_relevance_orderby($orderby, $query) {
        global $wpdb;
        
        if ($query->get('orderby') === 'relevance') {
            $orderby = "CAST({$wpdb->postmeta}.meta_value AS DECIMAL) DESC";
        }
        
        return $orderby;
    }
    add_filter('posts_orderby', 'agrochamba_add_relevance_orderby', 10, 2);
}

if (!function_exists('agrochamba_add_relevance_meta_query')) {
    /**
     * Agrega meta_query para incluir el score de relevancia en la consulta
     */
    function agrochamba_add_relevance_meta_query($query) {
        if ($query->get('orderby') === 'relevance') {
            $meta_query = $query->get('meta_query') ?: array();
            $meta_query[] = array(
                'key' => '_trabajo_relevance_score',
                'compare' => 'EXISTS'
            );
            $query->set('meta_query', $meta_query);
        }
    }
    add_action('pre_get_posts', 'agrochamba_add_relevance_meta_query');
}

// ==========================================
// 6. ENDPOINT PARA OBTENER TRABAJOS POR RELEVANCIA
// ==========================================
if (!function_exists('agrochamba_register_relevance_endpoint')) {
    function agrochamba_register_relevance_endpoint() {
        register_rest_route('agrochamba/v1', '/jobs/relevance', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_jobs_by_relevance',
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
    add_action('rest_api_init', 'agrochamba_register_relevance_endpoint');
}

if (!function_exists('agrochamba_get_jobs_by_relevance')) {
    /**
     * Endpoint para obtener trabajos ordenados por relevancia
     */
    function agrochamba_get_jobs_by_relevance($request) {
        $per_page = $request->get_param('per_page') ?: 20;
        $page = $request->get_param('page') ?: 1;
        $ubicacion = $request->get_param('ubicacion');
        
        $args = array(
            'post_type' => 'trabajo',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'relevance',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_trabajo_relevance_score',
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
            $score = get_post_meta($post->ID, '_trabajo_relevance_score', true);
            $jobs[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'link' => get_permalink($post->ID),
                'relevance_score' => floatval($score ?: 0),
            );
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'jobs' => $jobs,
            'total' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => $page,
        ), 200);
    }
}

// ==========================================
// 7. COMANDO WP-CLI PARA RECALCULAR TODOS LOS SCORES
// ==========================================
if (defined('WP_CLI') && WP_CLI) {
    if (!class_exists('WP_CLI_Command')) {
        return;
    }
    
    class AgroChamba_Relevance_Command extends WP_CLI_Command {
        /**
         * Recalcula los scores de relevancia de todos los trabajos
         * 
         * ## EXAMPLES
         * 
         *     wp agrochamba recalculate-relevance
         *     wp agrochamba recalculate-relevance --limit=100
         */
        public function recalculate_relevance($args, $assoc_args) {
            $limit = isset($assoc_args['limit']) ? intval($assoc_args['limit']) : -1;
            
            WP_CLI::line('Recalculando scores de relevancia...');
            
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
                agrochamba_update_job_relevance_score($job_id);
                $progress->tick();
            }
            
            $progress->finish();
            WP_CLI::success("Scores recalculados para {$total} trabajos.");
        }
    }
    
    WP_CLI::add_command('agrochamba', 'AgroChamba_Relevance_Command');
}

// ==========================================
// 8. INICIALIZAR SCORES PARA TRABAJOS EXISTENTES
// ==========================================
if (!function_exists('agrochamba_initialize_relevance_scores')) {
    /**
     * Inicializa los scores de relevancia para todos los trabajos existentes
     * Se ejecuta una sola vez al activar el módulo
     */
    function agrochamba_initialize_relevance_scores() {
        // Verificar si ya se inicializó
        if (get_option('agrochamba_relevance_scores_initialized')) {
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
            agrochamba_update_job_relevance_score($job_id);
            $count++;
        }
        
        // Marcar como inicializado
        update_option('agrochamba_relevance_scores_initialized', true);
        
        error_log("AgroChamba: Scores de relevancia inicializados para {$count} trabajos.");
    }
    
    // Ejecutar en la próxima carga si no está inicializado
    add_action('init', 'agrochamba_initialize_relevance_scores', 20);
}

