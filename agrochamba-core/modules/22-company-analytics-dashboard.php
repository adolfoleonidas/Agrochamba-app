<?php
/**
 * =============================================================
 * MÓDULO 22: DASHBOARD DE ANALYTICS PARA EMPRESAS
 * =============================================================
 * 
 * Este módulo proporciona un dashboard completo de analytics
 * para empresas que publican trabajos:
 * 
 * - Estadísticas de trabajos publicados
 * - Métricas de engagement (vistas, likes, comentarios, guardados)
 * - Gráficos de tendencias
 * - Comparativas con otros trabajos
 * - Análisis de rendimiento por ubicación, tipo de puesto, etc.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. FUNCIONES DE ANALYTICS
// ==========================================
if (!function_exists('agrochamba_get_company_jobs_stats')) {
    /**
     * Obtiene estadísticas de todos los trabajos de una empresa
     */
    function agrochamba_get_company_jobs_stats($user_id, $date_from = null, $date_to = null) {
        $empresa_term = agrochamba_get_empresa_term_by_user_id($user_id);
        
        if (!$empresa_term) {
            return array(
                'total_jobs' => 0,
                'active_jobs' => 0,
                'total_views' => 0,
                'total_likes' => 0,
                'total_comments' => 0,
                'total_saved' => 0,
                'average_views_per_job' => 0,
                'average_engagement_rate' => 0,
            );
        }
        
        $args = array(
            'post_type' => 'trabajo',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'empresa',
                    'field' => 'term_id',
                    'terms' => $empresa_term->term_id,
                ),
            ),
            'fields' => 'ids',
        );
        
        // Filtrar por rango de fechas si se especifica
        if ($date_from || $date_to) {
            $args['date_query'] = array();
            if ($date_from) {
                $args['date_query']['after'] = $date_from;
            }
            if ($date_to) {
                $args['date_query']['before'] = $date_to;
            }
        }
        
        $query = new WP_Query($args);
        $job_ids = $query->posts;
        
        $stats = array(
            'total_jobs' => count($job_ids),
            'active_jobs' => 0,
            'total_views' => 0,
            'total_likes' => 0,
            'total_comments' => 0,
            'total_saved' => 0,
            'jobs' => array(),
        );
        
        foreach ($job_ids as $job_id) {
            $job_stats = agrochamba_get_job_stats($job_id);
            
            $stats['total_views'] += $job_stats['views'];
            $stats['total_likes'] += $job_stats['likes'];
            $stats['total_comments'] += $job_stats['comments'];
            $stats['total_saved'] += $job_stats['saved'];
            
            if ($job_stats['views'] > 0) {
                $stats['active_jobs']++;
            }
            
            $stats['jobs'][] = array(
                'id' => $job_id,
                'title' => get_the_title($job_id),
                'date' => get_post_time('c', false, $job_id),
                'stats' => $job_stats,
            );
        }
        
        // Calcular promedios
        if ($stats['total_jobs'] > 0) {
            $stats['average_views_per_job'] = round($stats['total_views'] / $stats['total_jobs'], 2);
            $stats['average_engagement_rate'] = round(
                (($stats['total_likes'] + $stats['total_comments'] + $stats['total_saved']) / max(1, $stats['total_views'])) * 100,
                2
            );
        } else {
            $stats['average_views_per_job'] = 0;
            $stats['average_engagement_rate'] = 0;
        }
        
        return $stats;
    }
}

if (!function_exists('agrochamba_get_job_stats')) {
    /**
     * Obtiene estadísticas de un trabajo individual
     */
    function agrochamba_get_job_stats($job_id) {
        // Vistas
        $views = intval(get_post_meta($job_id, '_trabajo_views', true) ?: 0);
        
        // Likes (favoritos)
        $likes = 0;
        $users = get_users(array('fields' => 'ID'));
        foreach ($users as $user_id) {
            $favorites = get_user_meta($user_id, 'favorite_jobs', true);
            if (is_array($favorites) && in_array($job_id, $favorites)) {
                $likes++;
            }
        }
        
        // Guardados
        $saved = 0;
        foreach ($users as $user_id) {
            $saved_jobs = get_user_meta($user_id, 'saved_jobs', true);
            if (is_array($saved_jobs) && in_array($job_id, $saved_jobs)) {
                $saved++;
            }
        }
        
        // Comentarios
        $comments = get_comments_number($job_id);
        
        // Score de relevancia
        $relevance_score = floatval(get_post_meta($job_id, '_trabajo_relevance_score', true) ?: 0);
        
        // Engagement rate
        $engagement_rate = 0;
        if ($views > 0) {
            $engagement_rate = round((($likes + $comments + $saved) / $views) * 100, 2);
        }
        
        return array(
            'views' => $views,
            'likes' => $likes,
            'comments' => $comments,
            'saved' => $saved,
            'relevance_score' => $relevance_score,
            'engagement_rate' => $engagement_rate,
        );
    }
}

if (!function_exists('agrochamba_get_company_trends')) {
    /**
     * Obtiene tendencias de engagement por período
     */
    function agrochamba_get_company_trends($user_id, $period = '30days') {
        $date_to = current_time('mysql');
        
        switch ($period) {
            case '7days':
                $date_from = date('Y-m-d H:i:s', strtotime('-7 days'));
                $interval = '1 day';
                break;
            case '30days':
                $date_from = date('Y-m-d H:i:s', strtotime('-30 days'));
                $interval = '1 day';
                break;
            case '90days':
                $date_from = date('Y-m-d H:i:s', strtotime('-90 days'));
                $interval = '3 days';
                break;
            case '1year':
                $date_from = date('Y-m-d H:i:s', strtotime('-1 year'));
                $interval = '1 week';
                break;
            default:
                $date_from = date('Y-m-d H:i:s', strtotime('-30 days'));
                $interval = '1 day';
        }
        
        $empresa_term = agrochamba_get_empresa_term_by_user_id($user_id);
        if (!$empresa_term) {
            return array();
        }
        
        $args = array(
            'post_type' => 'trabajo',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'empresa',
                    'field' => 'term_id',
                    'terms' => $empresa_term->term_id,
                ),
            ),
            'date_query' => array(
                array(
                    'after' => $date_from,
                    'before' => $date_to,
                ),
            ),
            'fields' => 'ids',
        );
        
        $query = new WP_Query($args);
        $job_ids = $query->posts;
        
        // Agrupar por período
        $trends = array();
        
        foreach ($job_ids as $job_id) {
            $post_date = get_post_time('Y-m-d', false, $job_id);
            
            // Normalizar fecha según intervalo
            if ($interval === '1 day') {
                $period_key = $post_date;
            } elseif ($interval === '3 days') {
                $timestamp = strtotime($post_date);
                $week_start = date('Y-m-d', strtotime('monday this week', $timestamp));
                $period_key = $week_start;
            } elseif ($interval === '1 week') {
                $timestamp = strtotime($post_date);
                $week_start = date('Y-m-d', strtotime('monday this week', $timestamp));
                $period_key = $week_start;
            } else {
                $period_key = $post_date;
            }
            
            if (!isset($trends[$period_key])) {
                $trends[$period_key] = array(
                    'date' => $period_key,
                    'jobs_published' => 0,
                    'total_views' => 0,
                    'total_likes' => 0,
                    'total_comments' => 0,
                    'total_saved' => 0,
                );
            }
            
            $job_stats = agrochamba_get_job_stats($job_id);
            
            $trends[$period_key]['jobs_published']++;
            $trends[$period_key]['total_views'] += $job_stats['views'];
            $trends[$period_key]['total_likes'] += $job_stats['likes'];
            $trends[$period_key]['total_comments'] += $job_stats['comments'];
            $trends[$period_key]['total_saved'] += $job_stats['saved'];
        }
        
        // Ordenar por fecha
        ksort($trends);
        
        return array_values($trends);
    }
}

if (!function_exists('agrochamba_get_company_performance_by_category')) {
    /**
     * Obtiene rendimiento por categoría (ubicación, tipo de puesto, cultivo)
     */
    function agrochamba_get_company_performance_by_category($user_id, $taxonomy = 'ubicacion') {
        $empresa_term = agrochamba_get_empresa_term_by_user_id($user_id);
        
        if (!$empresa_term) {
            return array();
        }
        
        $args = array(
            'post_type' => 'trabajo',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'empresa',
                    'field' => 'term_id',
                    'terms' => $empresa_term->term_id,
                ),
            ),
            'fields' => 'ids',
        );
        
        $query = new WP_Query($args);
        $job_ids = $query->posts;
        
        $category_stats = array();
        
        foreach ($job_ids as $job_id) {
            $terms = wp_get_post_terms($job_id, $taxonomy, array('fields' => 'ids'));
            
            if (!empty($terms) && !is_wp_error($terms)) {
                $job_stats = agrochamba_get_job_stats($job_id);
                
                foreach ($terms as $term_id) {
                    if (!isset($category_stats[$term_id])) {
                        $term = get_term($term_id);
                        $category_stats[$term_id] = array(
                            'id' => $term_id,
                            'name' => $term->name,
                            'slug' => $term->slug,
                            'jobs_count' => 0,
                            'total_views' => 0,
                            'total_likes' => 0,
                            'total_comments' => 0,
                            'total_saved' => 0,
                        );
                    }
                    
                    $category_stats[$term_id]['jobs_count']++;
                    $category_stats[$term_id]['total_views'] += $job_stats['views'];
                    $category_stats[$term_id]['total_likes'] += $job_stats['likes'];
                    $category_stats[$term_id]['total_comments'] += $job_stats['comments'];
                    $category_stats[$term_id]['total_saved'] += $job_stats['saved'];
                }
            }
        }
        
        // Calcular promedios y ordenar
        foreach ($category_stats as &$stats) {
            if ($stats['jobs_count'] > 0) {
                $stats['average_views'] = round($stats['total_views'] / $stats['jobs_count'], 2);
                $stats['average_engagement_rate'] = round(
                    (($stats['total_likes'] + $stats['total_comments'] + $stats['total_saved']) / max(1, $stats['total_views'])) * 100,
                    2
                );
            } else {
                $stats['average_views'] = 0;
                $stats['average_engagement_rate'] = 0;
            }
        }
        
        // Ordenar por total de vistas
        uasort($category_stats, function($a, $b) {
            return $b['total_views'] - $a['total_views'];
        });
        
        return array_values($category_stats);
    }
}

if (!function_exists('agrochamba_get_empresa_term_by_user_id')) {
    /**
     * Obtiene el término de empresa asociado a un usuario
     */
    function agrochamba_get_empresa_term_by_user_id($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return null;
        }
        
        // Intentar obtener por display_name
        $display_name = $user->display_name;
        $empresa_term = get_term_by('name', $display_name, 'empresa');
        
        if (!$empresa_term) {
            // Intentar por user_login
            $empresa_term = get_term_by('name', $user->user_login, 'empresa');
        }
        
        return $empresa_term ? $empresa_term : null;
    }
}

// ==========================================
// 2. ENDPOINTS REST API
// ==========================================
if (!function_exists('agrochamba_register_analytics_endpoints')) {
    function agrochamba_register_analytics_endpoints() {
        // Dashboard principal
        register_rest_route('agrochamba/v1', '/analytics/dashboard', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_analytics_dashboard',
            'permission_callback' => function() {
                return is_user_logged_in() && (current_user_can('employer') || current_user_can('administrator'));
            },
            'args' => array(
                'date_from' => array(
                    'default' => null,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'date_to' => array(
                    'default' => null,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Tendencias
        register_rest_route('agrochamba/v1', '/analytics/trends', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_analytics_trends',
            'permission_callback' => function() {
                return is_user_logged_in() && (current_user_can('employer') || current_user_can('administrator'));
            },
            'args' => array(
                'period' => array(
                    'default' => '30days',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Rendimiento por categoría
        register_rest_route('agrochamba/v1', '/analytics/performance', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_analytics_performance',
            'permission_callback' => function() {
                return is_user_logged_in() && (current_user_can('employer') || current_user_can('administrator'));
            },
            'args' => array(
                'taxonomy' => array(
                    'default' => 'ubicacion',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Estadísticas de un trabajo específico
        register_rest_route('agrochamba/v1', '/analytics/jobs/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_job_analytics',
            'permission_callback' => function() {
                return is_user_logged_in() && (current_user_can('employer') || current_user_can('administrator'));
            },
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
    add_action('rest_api_init', 'agrochamba_register_analytics_endpoints');
}

if (!function_exists('agrochamba_get_analytics_dashboard')) {
    /**
     * Endpoint para obtener dashboard completo de analytics
     */
    function agrochamba_get_analytics_dashboard($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión.', array('status' => 401));
        }
        
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        if (!in_array('employer', $user->roles) && !in_array('administrator', $user->roles)) {
            return new WP_Error('rest_forbidden', 'Solo las empresas pueden acceder al dashboard.', array('status' => 403));
        }
        
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');
        
        $stats = agrochamba_get_company_jobs_stats($user_id, $date_from, $date_to);
        
        // Obtener top 5 trabajos por engagement
        $top_jobs = array();
        if (!empty($stats['jobs'])) {
            usort($stats['jobs'], function($a, $b) {
                $score_a = $a['stats']['relevance_score'] + $a['stats']['engagement_rate'];
                $score_b = $b['stats']['relevance_score'] + $b['stats']['engagement_rate'];
                return $score_b <=> $score_a;
            });
            $top_jobs = array_slice($stats['jobs'], 0, 5);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'stats' => array(
                'total_jobs' => $stats['total_jobs'],
                'active_jobs' => $stats['active_jobs'],
                'total_views' => $stats['total_views'],
                'total_likes' => $stats['total_likes'],
                'total_comments' => $stats['total_comments'],
                'total_saved' => $stats['total_saved'],
                'average_views_per_job' => $stats['average_views_per_job'],
                'average_engagement_rate' => $stats['average_engagement_rate'],
            ),
            'top_jobs' => $top_jobs,
        ), 200);
    }
}

if (!function_exists('agrochamba_get_analytics_trends')) {
    /**
     * Endpoint para obtener tendencias
     */
    function agrochamba_get_analytics_trends($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión.', array('status' => 401));
        }
        
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        if (!in_array('employer', $user->roles) && !in_array('administrator', $user->roles)) {
            return new WP_Error('rest_forbidden', 'Solo las empresas pueden acceder a las tendencias.', array('status' => 403));
        }
        
        $period = $request->get_param('period') ?: '30days';
        $trends = agrochamba_get_company_trends($user_id, $period);
        
        return new WP_REST_Response(array(
            'success' => true,
            'period' => $period,
            'trends' => $trends,
        ), 200);
    }
}

if (!function_exists('agrochamba_get_analytics_performance')) {
    /**
     * Endpoint para obtener rendimiento por categoría
     */
    function agrochamba_get_analytics_performance($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión.', array('status' => 401));
        }
        
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        if (!in_array('employer', $user->roles) && !in_array('administrator', $user->roles)) {
            return new WP_Error('rest_forbidden', 'Solo las empresas pueden acceder al rendimiento.', array('status' => 403));
        }
        
        $taxonomy = $request->get_param('taxonomy') ?: 'ubicacion';
        $performance = agrochamba_get_company_performance_by_category($user_id, $taxonomy);
        
        return new WP_REST_Response(array(
            'success' => true,
            'taxonomy' => $taxonomy,
            'performance' => $performance,
        ), 200);
    }
}

if (!function_exists('agrochamba_get_job_analytics')) {
    /**
     * Endpoint para obtener analytics de un trabajo específico
     */
    function agrochamba_get_job_analytics($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión.', array('status' => 401));
        }
        
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        if (!in_array('employer', $user->roles) && !in_array('administrator', $user->roles)) {
            return new WP_Error('rest_forbidden', 'Solo las empresas pueden acceder a los analytics.', array('status' => 403));
        }
        
        $job_id = intval($request->get_param('id'));
        $job = get_post($job_id);
        
        if (!$job || $job->post_type !== 'trabajo') {
            return new WP_Error('job_not_found', 'Trabajo no encontrado.', array('status' => 404));
        }
        
        // Verificar que el trabajo pertenece a la empresa del usuario
        $empresa_term = agrochamba_get_empresa_term_by_user_id($user_id);
        if ($empresa_term) {
            $job_empresas = wp_get_post_terms($job_id, 'empresa', array('fields' => 'ids'));
            if (!in_array($empresa_term->term_id, $job_empresas)) {
                return new WP_Error('rest_forbidden', 'No tienes permiso para ver este trabajo.', array('status' => 403));
            }
        }
        
        $stats = agrochamba_get_job_stats($job_id);
        
        return new WP_REST_Response(array(
            'success' => true,
            'job' => array(
                'id' => $job->ID,
                'title' => $job->post_title,
                'date' => get_post_time('c', false, $job_id),
                'link' => get_permalink($job_id),
            ),
            'stats' => $stats,
        ), 200);
    }
}

