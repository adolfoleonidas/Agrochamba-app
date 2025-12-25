<?php
/**
 * =============================================================
 * MÓDULO 21: SISTEMA DE RECOMENDACIONES PERSONALIZADAS
 * =============================================================
 * 
 * Este módulo implementa un sistema de recomendaciones personalizadas
 * basado en el comportamiento del usuario:
 * 
 * - Historial de trabajos vistos
 * - Trabajos guardados y favoritos
 * - Ubicación preferida
 * - Tipo de puesto/cultivo preferido
 * - Similitud con otros usuarios (collaborative filtering)
 * 
 * Algoritmo híbrido que combina filtrado colaborativo y basado en contenido.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. TRACKING DE COMPORTAMIENTO DEL USUARIO
// ==========================================
if (!function_exists('agrochamba_track_job_view')) {
    /**
     * Registra cuando un usuario ve un trabajo
     */
    function agrochamba_track_job_view($job_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id || $user_id <= 0) {
            return; // No trackear usuarios no logueados
        }
        
        $viewed_jobs = get_user_meta($user_id, 'viewed_jobs', true);
        if (!is_array($viewed_jobs)) {
            $viewed_jobs = array();
        }
        
        // Agregar con timestamp
        $viewed_jobs[$job_id] = array(
            'job_id' => $job_id,
            'timestamp' => current_time('timestamp'),
            'date' => current_time('mysql')
        );
        
        // Mantener solo los últimos 100 trabajos vistos
        if (count($viewed_jobs) > 100) {
            // Ordenar por timestamp y mantener los más recientes
            uasort($viewed_jobs, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
            $viewed_jobs = array_slice($viewed_jobs, 0, 100, true);
        }
        
        update_user_meta($user_id, 'viewed_jobs', $viewed_jobs);
        
        // Disparar hook para actualizar recomendaciones
        do_action('agrochamba_user_job_viewed', $user_id, $job_id);
    }
}

// Hook para trackear vistas cuando se visita un trabajo
add_action('agrochamba_job_view_counted', function($job_id) {
    if (is_user_logged_in()) {
        agrochamba_track_job_view($job_id);
    }
}, 20, 1);

// Trackear cuando se guarda un trabajo
add_action('agrochamba_saved_toggled', function($job_id) {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $saved_jobs = get_user_meta($user_id, 'saved_jobs', true);
        if (is_array($saved_jobs) && in_array($job_id, $saved_jobs)) {
            // Trabajo guardado - incrementar peso de preferencia
            agrochamba_update_user_preferences($user_id, $job_id, 'saved');
        }
    }
}, 20, 1);

// Trackear cuando se da like a un trabajo
add_action('agrochamba_favorite_toggled', function($job_id) {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $favorites = get_user_meta($user_id, 'favorite_jobs', true);
        if (is_array($favorites) && in_array($job_id, $favorites)) {
            // Trabajo favorito - incrementar peso de preferencia
            agrochamba_update_user_preferences($user_id, $job_id, 'favorite');
        }
    }
}, 20, 1);

// ==========================================
// 2. ACTUALIZAR PREFERENCIAS DEL USUARIO
// ==========================================
if (!function_exists('agrochamba_update_user_preferences')) {
    /**
     * Actualiza las preferencias del usuario basadas en interacciones
     */
    function agrochamba_update_user_preferences($user_id, $job_id, $interaction_type) {
        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'trabajo') {
            return;
        }
        
        $preferences = get_user_meta($user_id, 'job_preferences', true);
        if (!is_array($preferences)) {
            $preferences = array(
                'ubicaciones' => array(),
                'cultivos' => array(),
                'tipos_puesto' => array(),
                'empresas' => array(),
            );
        }
        
        // Pesos según tipo de interacción
        $weights = array(
            'viewed' => 1,
            'saved' => 3,
            'favorite' => 5,
            'applied' => 10, // Si se implementa aplicación
        );
        
        $weight = isset($weights[$interaction_type]) ? $weights[$interaction_type] : 1;
        
        // Obtener taxonomías del trabajo
        $ubicaciones = wp_get_post_terms($job_id, 'ubicacion', array('fields' => 'ids'));
        $cultivos = wp_get_post_terms($job_id, 'cultivo', array('fields' => 'ids'));
        $tipos_puesto = wp_get_post_terms($job_id, 'tipo_puesto', array('fields' => 'ids'));
        $empresas = wp_get_post_terms($job_id, 'empresa', array('fields' => 'ids'));
        
        // Actualizar preferencias con pesos
        foreach ($ubicaciones as $ubicacion_id) {
            if (!isset($preferences['ubicaciones'][$ubicacion_id])) {
                $preferences['ubicaciones'][$ubicacion_id] = 0;
            }
            $preferences['ubicaciones'][$ubicacion_id] += $weight;
        }
        
        foreach ($cultivos as $cultivo_id) {
            if (!isset($preferences['cultivos'][$cultivo_id])) {
                $preferences['cultivos'][$cultivo_id] = 0;
            }
            $preferences['cultivos'][$cultivo_id] += $weight;
        }
        
        foreach ($tipos_puesto as $tipo_id) {
            if (!isset($preferences['tipos_puesto'][$tipo_id])) {
                $preferences['tipos_puesto'][$tipo_id] = 0;
            }
            $preferences['tipos_puesto'][$tipo_id] += $weight;
        }
        
        foreach ($empresas as $empresa_id) {
            if (!isset($preferences['empresas'][$empresa_id])) {
                $preferences['empresas'][$empresa_id] = 0;
            }
            $preferences['empresas'][$empresa_id] += $weight;
        }
        
        // Normalizar y mantener solo los top 20 de cada categoría
        foreach ($preferences as $key => &$values) {
            arsort($values);
            $preferences[$key] = array_slice($values, 0, 20, true);
        }
        
        update_user_meta($user_id, 'job_preferences', $preferences);
    }
}

// ==========================================
// 3. ALGORITMO DE RECOMENDACIONES
// ==========================================
if (!function_exists('agrochamba_get_personalized_recommendations')) {
    /**
     * Obtiene recomendaciones personalizadas para un usuario
     * 
     * @param int $user_id ID del usuario
     * @param int $limit Número de recomendaciones a retornar
     * @return array IDs de trabajos recomendados con scores
     */
    function agrochamba_get_personalized_recommendations($user_id, $limit = 20) {
        $preferences = get_user_meta($user_id, 'job_preferences', true);
        $viewed_jobs = get_user_meta($user_id, 'viewed_jobs', true);
        $saved_jobs = get_user_meta($user_id, 'saved_jobs', true);
        $favorite_jobs = get_user_meta($user_id, 'favorite_jobs', true);
        
        // Trabajos ya vistos/interactuados (excluir de recomendaciones)
        $excluded_jobs = array();
        if (is_array($viewed_jobs)) {
            $excluded_jobs = array_merge($excluded_jobs, array_keys($viewed_jobs));
        }
        if (is_array($saved_jobs)) {
            $excluded_jobs = array_merge($excluded_jobs, $saved_jobs);
        }
        if (is_array($favorite_jobs)) {
            $excluded_jobs = array_merge($excluded_jobs, $favorite_jobs);
        }
        $excluded_jobs = array_unique($excluded_jobs);
        
        // Obtener ubicación preferida del usuario
        $user_location = get_user_meta($user_id, 'preferred_location', true);
        
        // Construir query de trabajos candidatos
        $args = array(
            'post_type' => 'trabajo',
            'post_status' => 'publish',
            'posts_per_page' => 200, // Obtener más candidatos para filtrar
            'post__not_in' => $excluded_jobs,
            'fields' => 'ids',
        );
        
        // Filtrar por ubicación preferida si existe
        if (!empty($user_location)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ubicacion',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($user_location),
                ),
            );
        }
        
        $candidate_jobs = get_posts($args);
        
        // Calcular score de recomendación para cada trabajo
        $job_scores = array();
        
        foreach ($candidate_jobs as $job_id) {
            $score = agrochamba_calculate_recommendation_score($job_id, $user_id, $preferences);
            if ($score > 0) {
                $job_scores[$job_id] = $score;
            }
        }
        
        // Ordenar por score descendente
        arsort($job_scores);
        
        // Retornar top N trabajos
        return array_slice(array_keys($job_scores), 0, $limit, true);
    }
}

if (!function_exists('agrochamba_calculate_recommendation_score')) {
    /**
     * Calcula el score de recomendación para un trabajo específico
     * 
     * Score = (Preferencias × 0.4) + (Relevancia × 0.3) + (Novedad × 0.2) + (Proximidad × 0.1)
     */
    function agrochamba_calculate_recommendation_score($job_id, $user_id, $preferences) {
        $score = 0;
        
        // Factor 1: Preferencias del usuario (40%)
        $preference_score = 0;
        if (is_array($preferences)) {
            // Obtener taxonomías del trabajo
            $job_ubicaciones = wp_get_post_terms($job_id, 'ubicacion', array('fields' => 'ids'));
            $job_cultivos = wp_get_post_terms($job_id, 'cultivo', array('fields' => 'ids'));
            $job_tipos = wp_get_post_terms($job_id, 'tipo_puesto', array('fields' => 'ids'));
            $job_empresas = wp_get_post_terms($job_id, 'empresa', array('fields' => 'ids'));
            
            // Calcular match con preferencias
            $matches = 0;
            $total_weight = 0;
            
            foreach ($job_ubicaciones as $ubicacion_id) {
                if (isset($preferences['ubicaciones'][$ubicacion_id])) {
                    $matches += $preferences['ubicaciones'][$ubicacion_id];
                    $total_weight += $preferences['ubicaciones'][$ubicacion_id];
                }
            }
            
            foreach ($job_cultivos as $cultivo_id) {
                if (isset($preferences['cultivos'][$cultivo_id])) {
                    $matches += $preferences['cultivos'][$cultivo_id];
                    $total_weight += $preferences['cultivos'][$cultivo_id];
                }
            }
            
            foreach ($job_tipos as $tipo_id) {
                if (isset($preferences['tipos_puesto'][$tipo_id])) {
                    $matches += $preferences['tipos_puesto'][$tipo_id];
                    $total_weight += $preferences['tipos_puesto'][$tipo_id];
                }
            }
            
            foreach ($job_empresas as $empresa_id) {
                if (isset($preferences['empresas'][$empresa_id])) {
                    $matches += $preferences['empresas'][$empresa_id];
                    $total_weight += $preferences['empresas'][$empresa_id];
                }
            }
            
            // Normalizar a 0-100
            if ($total_weight > 0) {
                $preference_score = min(100, ($matches / $total_weight) * 100);
            }
        }
        
        // Factor 2: Relevancia del trabajo (30%)
        $relevance_score = floatval(get_post_meta($job_id, '_trabajo_relevance_score', true) ?: 0);
        $normalized_relevance = min(100, $relevance_score);
        
        // Factor 3: Novedad (20%)
        $post_date = get_post_time('U', true, $job_id);
        $current_time = current_time('timestamp');
        $days_old = max(0, ($current_time - $post_date) / DAY_IN_SECONDS);
        
        if ($days_old <= 7) {
            $novelty_score = 100;
        } elseif ($days_old <= 30) {
            $novelty_score = 100 - (($days_old - 7) / 23 * 50);
        } else {
            $novelty_score = max(10, 50 - (($days_old - 30) / 60 * 40));
        }
        
        // Factor 4: Proximidad (10%)
        $proximity_score = 0;
        $user_location = get_user_meta($user_id, 'preferred_location', true);
        if (!empty($user_location)) {
            $job_ubicaciones = wp_get_post_terms($job_id, 'ubicacion', array('fields' => 'slugs'));
            if (!empty($job_ubicaciones) && !is_wp_error($job_ubicaciones)) {
                if (in_array($user_location, $job_ubicaciones)) {
                    $proximity_score = 100;
                }
            }
        }
        
        // Calcular score final
        $final_score = (
            ($preference_score * 0.4) +
            ($normalized_relevance * 0.3) +
            ($novelty_score * 0.2) +
            ($proximity_score * 0.1)
        );
        
        return round($final_score, 2);
    }
}

// ==========================================
// 4. COLLABORATIVE FILTERING BÁSICO
// ==========================================
if (!function_exists('agrochamba_find_similar_users')) {
    /**
     * Encuentra usuarios similares basado en interacciones compartidas
     */
    function agrochamba_find_similar_users($user_id, $limit = 10) {
        $user_saved = get_user_meta($user_id, 'saved_jobs', true);
        $user_favorites = get_user_meta($user_id, 'favorite_jobs', true);
        
        if (!is_array($user_saved)) {
            $user_saved = array();
        }
        if (!is_array($user_favorites)) {
            $user_favorites = array();
        }
        
        $user_jobs = array_unique(array_merge($user_saved, $user_favorites));
        
        if (empty($user_jobs)) {
            return array();
        }
        
        // Buscar usuarios que hayan interactuado con los mismos trabajos
        $similar_users = array();
        $users = get_users(array('exclude' => array($user_id), 'number' => 100));
        
        foreach ($users as $other_user) {
            $other_saved = get_user_meta($other_user->ID, 'saved_jobs', true);
            $other_favorites = get_user_meta($other_user->ID, 'favorite_jobs', true);
            
            if (!is_array($other_saved)) {
                $other_saved = array();
            }
            if (!is_array($other_favorites)) {
                $other_favorites = array();
            }
            
            $other_jobs = array_unique(array_merge($other_saved, $other_favorites));
            
            // Calcular similitud (Jaccard similarity)
            $intersection = count(array_intersect($user_jobs, $other_jobs));
            $union = count(array_unique(array_merge($user_jobs, $other_jobs)));
            
            if ($union > 0 && $intersection > 0) {
                $similarity = $intersection / $union;
                if ($similarity > 0.1) { // Al menos 10% de similitud
                    $similar_users[$other_user->ID] = $similarity;
                }
            }
        }
        
        arsort($similar_users);
        return array_slice(array_keys($similar_users), 0, $limit, true);
    }
}

// ==========================================
// 5. ENDPOINTS REST API
// ==========================================
if (!function_exists('agrochamba_register_recommendations_endpoints')) {
    function agrochamba_register_recommendations_endpoints() {
        register_rest_route('agrochamba/v1', '/recommendations', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_recommendations',
            'permission_callback' => function() {
                return is_user_logged_in();
            },
            'args' => array(
                'limit' => array(
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
        
        register_rest_route('agrochamba/v1', '/recommendations/preferences', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_user_preferences',
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ));
    }
    add_action('rest_api_init', 'agrochamba_register_recommendations_endpoints');
}

if (!function_exists('agrochamba_get_recommendations')) {
    /**
     * Endpoint para obtener recomendaciones personalizadas
     */
    function agrochamba_get_recommendations($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para ver recomendaciones.', array('status' => 401));
        }
        
        $user_id = get_current_user_id();
        $limit = $request->get_param('limit') ?: 20;
        
        $recommended_job_ids = agrochamba_get_personalized_recommendations($user_id, $limit);
        
        if (empty($recommended_job_ids)) {
            // Si no hay recomendaciones, retornar trabajos más relevantes
            $args = array(
                'post_type' => 'trabajo',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'relevance',
                'order' => 'DESC',
                'meta_query' => array(
                    array(
                        'key' => '_trabajo_relevance_score',
                        'compare' => 'EXISTS'
                    )
                )
            );
            
            $query = new WP_Query($args);
            $recommended_job_ids = wp_list_pluck($query->posts, 'ID');
        }
        
        $jobs = array();
        foreach ($recommended_job_ids as $job_id) {
            $job = get_post($job_id);
            if ($job) {
                $score = agrochamba_calculate_recommendation_score(
                    $job_id,
                    $user_id,
                    get_user_meta($user_id, 'job_preferences', true)
                );
                
                $jobs[] = array(
                    'id' => $job->ID,
                    'title' => $job->post_title,
                    'link' => get_permalink($job->ID),
                    'excerpt' => wp_trim_words($job->post_excerpt ?: $job->post_content, 20),
                    'recommendation_score' => $score,
                    'date' => $job->post_date,
                );
            }
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'jobs' => $jobs,
            'total' => count($jobs),
        ), 200);
    }
}

if (!function_exists('agrochamba_get_user_preferences')) {
    /**
     * Endpoint para obtener preferencias del usuario
     */
    function agrochamba_get_user_preferences($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión.', array('status' => 401));
        }
        
        $user_id = get_current_user_id();
        $preferences = get_user_meta($user_id, 'job_preferences', true);
        
        if (!is_array($preferences)) {
            $preferences = array(
                'ubicaciones' => array(),
                'cultivos' => array(),
                'tipos_puesto' => array(),
                'empresas' => array(),
            );
        }
        
        // Formatear con nombres de términos
        $formatted = array();
        
        foreach ($preferences as $taxonomy => $terms) {
            $formatted[$taxonomy] = array();
            foreach ($terms as $term_id => $weight) {
                $term = get_term($term_id);
                if ($term && !is_wp_error($term)) {
                    $formatted[$taxonomy][] = array(
                        'id' => $term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'weight' => $weight,
                    );
                }
            }
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'preferences' => $formatted,
        ), 200);
    }
}

