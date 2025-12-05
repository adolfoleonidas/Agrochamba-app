<?php
/**
 * =============================================================
 * MÓDULO 10: SISTEMA DE CACHÉ
 * =============================================================
 * 
 * Sistema de caché para mejorar el rendimiento de la API:
 * - Caché de taxonomías (ubicaciones, empresas, cultivos, tipos de puesto)
 * - Caché de listados de trabajos públicos
 * - Invalidación automática cuando se crean/actualizan trabajos
 * - Caché de perfiles de empresa
 */

if (!defined('ABSPATH')) {
    exit;
}

// Shim de compatibilidad: delegar a clase namespaced si está disponible
if (!defined('AGROCHAMBA_CACHE_SERVICE_NAMESPACE_INITIALIZED')) {
    define('AGROCHAMBA_CACHE_SERVICE_NAMESPACE_INITIALIZED', true);
    if (class_exists('AgroChamba\\Services\\CacheService')) {
        if (function_exists('error_log')) {
            error_log('AgroChamba: Cargando sistema de caché mediante AgroChamba\\Services\\CacheService (migración namespaces).');
        }
        \AgroChamba\Services\CacheService::init();
        // Mantener compatibilidad: las funciones legacy podrían seguir siendo llamadas
        // pero evitamos redefinirlas aquí si la clase ya está disponible. Retornamos
        // para prevenir duplicación de lógica procedural.
        return;
    } else {
        if (function_exists('error_log')) {
            error_log('AgroChamba: No se encontró AgroChamba\\Services\\CacheService. Usando implementación procedural legacy.');
        }
    }
}

// ==========================================
// 1. CONSTANTES DE CACHÉ
// ==========================================
if (!defined('AGROCHAMBA_CACHE_TAXONOMIES')) {
    define('AGROCHAMBA_CACHE_TAXONOMIES', 'agrochamba_cache_taxonomies');
}
if (!defined('AGROCHAMBA_CACHE_TAXONOMIES_TTL')) {
    define('AGROCHAMBA_CACHE_TAXONOMIES_TTL', 3600); // 1 hora
}
if (!defined('AGROCHAMBA_CACHE_JOBS_LIST_TTL')) {
    define('AGROCHAMBA_CACHE_JOBS_LIST_TTL', 300); // 5 minutos
}
if (!defined('AGROCHAMBA_CACHE_COMPANY_PROFILE_TTL')) {
    define('AGROCHAMBA_CACHE_COMPANY_PROFILE_TTL', 1800); // 30 minutos
}

// ==========================================
// 2. OBTENER TAXONOMÍAS CON CACHÉ
// ==========================================
if (!function_exists('agrochamba_get_cached_taxonomies')) {
    function agrochamba_get_cached_taxonomies($taxonomy) {
        $cache_key = AGROCHAMBA_CACHE_TAXONOMIES . '_' . $taxonomy;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Si no hay caché, obtener de la base de datos
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (is_wp_error($terms)) {
            return array();
        }
        
        // Formatear términos para la API
        $formatted_terms = array();
        foreach ($terms as $term) {
            $formatted_terms[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'taxonomy' => $taxonomy,
                'count' => $term->count
            );
        }
        
        // Guardar en caché
        set_transient($cache_key, $formatted_terms, AGROCHAMBA_CACHE_TAXONOMIES_TTL);
        
        return $formatted_terms;
    }
}

// ==========================================
// 3. INVALIDAR CACHÉ DE TAXONOMÍAS
// ==========================================
if (!function_exists('agrochamba_invalidate_taxonomy_cache')) {
    function agrochamba_invalidate_taxonomy_cache($taxonomy = null) {
        if ($taxonomy) {
            // Invalidar una taxonomía específica
            $cache_key = AGROCHAMBA_CACHE_TAXONOMIES . '_' . $taxonomy;
            delete_transient($cache_key);
        } else {
            // Invalidar todas las taxonomías
            $taxonomies = array('ubicacion', 'empresa', 'cultivo', 'tipo_puesto');
            foreach ($taxonomies as $tax) {
                $cache_key = AGROCHAMBA_CACHE_TAXONOMIES . '_' . $tax;
                delete_transient($cache_key);
            }
        }
    }
    
}

// ==========================================
// 4. CACHÉ DE LISTADOS DE TRABAJOS PÚBLICOS
// ==========================================
if (!function_exists('agrochamba_get_cached_jobs_list')) {
    function agrochamba_get_cached_jobs_list($args, $cache_key_suffix = '') {
        // Crear clave de caché basada en los argumentos
        $cache_key = 'agrochamba_cache_jobs_' . md5(serialize($args) . $cache_key_suffix);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Si no hay caché, obtener de la base de datos
        $query = new WP_Query($args);
        $jobs = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post = get_post(get_the_ID());
                $jobs[] = $post;
            }
            wp_reset_postdata();
        }
        
        // Guardar resultado en caché
        $result = array(
            'jobs' => $jobs,
            'total' => $query->found_posts,
            'total_pages' => $query->max_num_pages
        );
        
        set_transient($cache_key, $result, AGROCHAMBA_CACHE_JOBS_LIST_TTL);
        
        return $result;
    }
}

// ==========================================
// 5. INVALIDAR CACHÉ DE TRABAJOS
// ==========================================
if (!function_exists('agrochamba_invalidate_jobs_cache')) {
    function agrochamba_invalidate_jobs_cache() {
        // Eliminar todos los transients de trabajos
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_agrochamba_cache_jobs_%' 
            OR option_name LIKE '_transient_timeout_agrochamba_cache_jobs_%'"
        );
    }
    
    // Invalidar cuando se crea/actualiza/elimina un trabajo
    add_action('save_post_trabajo', 'agrochamba_invalidate_jobs_cache', 10);
    add_action('delete_post', function($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === 'trabajo') {
            agrochamba_invalidate_jobs_cache();
        }
    }, 10);
    
    // Invalidar cuando se cambia el estado de un trabajo (aprobación/rechazo)
    add_action('transition_post_status', function($new_status, $old_status, $post) {
        if ($post->post_type === 'trabajo' && $new_status !== $old_status) {
            agrochamba_invalidate_jobs_cache();
        }
    }, 10, 3);
}

// ==========================================
// 6. CACHÉ DE PERFILES DE EMPRESA
// ==========================================
if (!function_exists('agrochamba_get_cached_company_profile')) {
    function agrochamba_get_cached_company_profile($company_name) {
        $cache_key = 'agrochamba_cache_company_' . md5($company_name);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Si no hay caché, obtener de la base de datos
        // Esta función debe ser llamada desde el endpoint correspondiente
        // y el resultado se guardará en caché allí
        
        return false; // Indicar que no hay caché
    }
}

// ==========================================
// 7. INVALIDAR CACHÉ DE PERFILES DE EMPRESA
// ==========================================
if (!function_exists('agrochamba_invalidate_company_cache')) {
    function agrochamba_invalidate_company_cache($company_name = null) {
        if ($company_name) {
            // Invalidar un perfil específico
            $cache_key = 'agrochamba_cache_company_' . md5($company_name);
            delete_transient($cache_key);
        } else {
            // Invalidar todos los perfiles
            global $wpdb;
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_agrochamba_cache_company_%' 
                OR option_name LIKE '_transient_timeout_agrochamba_cache_company_%'"
            );
        }
    }
    
    // Invalidar cuando se actualiza el perfil de usuario
    add_action('profile_update', function($user_id) {
        $user = get_userdata($user_id);
        if ($user && (in_array('employer', $user->roles) || in_array('administrator', $user->roles))) {
            agrochamba_invalidate_company_cache($user->display_name);
        }
    }, 10);
}

// ==========================================
// 8. LIMPIAR TODO EL CACHÉ
// ==========================================
if (!function_exists('agrochamba_clear_all_cache')) {
    function agrochamba_clear_all_cache() {
        agrochamba_invalidate_taxonomy_cache();
        agrochamba_invalidate_jobs_cache();
        agrochamba_invalidate_company_cache();
    }
}

// ==========================================
// 9. HOOK PARA LIMPIAR CACHÉ AL ACTUALIZAR TAXONOMÍAS DESDE WP-ADMIN
// ==========================================
add_action('set_object_terms', function($object_id, $terms, $tt_ids, $taxonomy) {
    if ($taxonomy === 'ubicacion' || $taxonomy === 'empresa' || 
        $taxonomy === 'cultivo' || $taxonomy === 'tipo_puesto') {
        agrochamba_invalidate_taxonomy_cache($taxonomy);
        // También invalidar caché de trabajos ya que las taxonomías afectan los listados
        agrochamba_invalidate_jobs_cache();
    }
}, 10, 4);

// ==========================================
// 10. CACHÉ PARA RESPUESTAS DE API REST DE WORDPRESS (TAXONOMÍAS)
// ==========================================
if (!function_exists('agrochamba_cache_rest_taxonomy_response')) {
    function agrochamba_cache_rest_taxonomy_response($result, $server, $request) {
        // Solo cachear endpoints de taxonomías de AgroChamba
        $route = $request->get_route();
        $taxonomies = array('ubicacion', 'empresa', 'cultivo', 'tipo_puesto');
        
        foreach ($taxonomies as $taxonomy) {
            if (strpos($route, '/wp/v2/' . $taxonomy) !== false) {
                // Crear clave de caché basada en la ruta y parámetros
                $cache_key = 'agrochamba_rest_' . md5($route . serialize($request->get_params()));
                $cached = get_transient($cache_key);
                
                if ($cached !== false) {
                    return $cached;
                }
                
                // Si no hay caché, continuar con la petición normal
                // El resultado se guardará después con rest_post_dispatch
                break;
            }
        }
        
        return $result;
    }
    add_filter('rest_pre_dispatch', 'agrochamba_cache_rest_taxonomy_response', 10, 3);
}

// Guardar respuesta en caché después de la petición
if (!function_exists('agrochamba_save_rest_taxonomy_cache')) {
    function agrochamba_save_rest_taxonomy_cache($result, $server, $request) {
        $route = $request->get_route();
        $taxonomies = array('ubicacion', 'empresa', 'cultivo', 'tipo_puesto');
        
        foreach ($taxonomies as $taxonomy) {
            if (strpos($route, '/wp/v2/' . $taxonomy) !== false) {
                // Solo cachear respuestas exitosas
                if (!is_wp_error($result) && method_exists($result, 'get_status') && $result->get_status() === 200) {
                    $cache_key = 'agrochamba_rest_' . md5($route . serialize($request->get_params()));
                    set_transient($cache_key, $result, AGROCHAMBA_CACHE_TAXONOMIES_TTL);
                }
                break;
            }
        }
        
        return $result;
    }
    add_filter('rest_post_dispatch', 'agrochamba_save_rest_taxonomy_cache', 10, 3);
}

// ==========================================
// 11. INVALIDAR CACHÉ DE API REST AL ACTUALIZAR TAXONOMÍAS
// ==========================================
if (!function_exists('agrochamba_invalidate_rest_taxonomy_cache')) {
    function agrochamba_invalidate_rest_taxonomy_cache($term_id, $tt_id, $taxonomy) {
        if (in_array($taxonomy, array('ubicacion', 'empresa', 'cultivo', 'tipo_puesto'))) {
            // Limpiar caché de API REST para esta taxonomía
            global $wpdb;
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_agrochamba_rest_%' 
                OR option_name LIKE '_transient_timeout_agrochamba_rest_%'"
            );
            // También invalidar el caché de taxonomías
            agrochamba_invalidate_taxonomy_cache($taxonomy);
        }
    }
    add_action('created_term', 'agrochamba_invalidate_rest_taxonomy_cache', 10, 3);
    add_action('edited_term', 'agrochamba_invalidate_rest_taxonomy_cache', 10, 3);
    add_action('delete_term', 'agrochamba_invalidate_rest_taxonomy_cache', 10, 3);
}

