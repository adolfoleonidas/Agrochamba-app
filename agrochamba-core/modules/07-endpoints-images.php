<?php
/**
 * =============================================================
 * MÓDULO 7: ENDPOINTS DE IMÁGENES
 * =============================================================
 * 
 * Endpoints:
 * - GET /agrochamba/v1/jobs/{id}/images - Obtener imágenes de un trabajo
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================
// SHIM DE COMPATIBILIDAD → Delegar a controlador namespaced
// =============================================================
if (!defined('AGROCHAMBA_IMAGES_CONTROLLER_NAMESPACE_INITIALIZED')) {
    define('AGROCHAMBA_IMAGES_CONTROLLER_NAMESPACE_INITIALIZED', true);

    if (class_exists('AgroChamba\\API\\Media\\ImagesController')) {
        if (function_exists('error_log')) {
            error_log('AgroChamba: Delegando endpoints de imágenes a AgroChamba\\API\\Media\\ImagesController (migración namespaces).');
        }
        \AgroChamba\API\Media\ImagesController::init();
        return; // Evitar registrar endpoints legacy duplicados
    } else {
        if (function_exists('error_log')) {
            error_log('AgroChamba: No se encontró AgroChamba\\API\\Media\\ImagesController. Usando implementación procedural legacy.');
        }
    }
}

// ==========================================
// OBTENER IMÁGENES DE UN TRABAJO
// ==========================================
if (!function_exists('agrochamba_get_job_images')) {
    function agrochamba_get_job_images($request) {
        $post_id = intval($request->get_param('id'));
        
        // Verificar que el post existe
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', 'Post no encontrado con ID: ' . $post_id, array('status' => 404));
        }
        
        // Verificar que es de tipo 'trabajo'
        if ($post->post_type !== 'trabajo') {
            return new WP_Error('invalid_type', 'El post con ID ' . $post_id . ' no es de tipo "trabajo", es de tipo "' . $post->post_type . '"', array('status' => 400));
        }
        
        // Recopilar TODAS las imágenes del trabajo (sin duplicados)
        $all_image_ids = array();
        
        // 1. Obtener imagen destacada (featured media)
        $featured_id = get_post_thumbnail_id($post_id);
        if ($featured_id) {
            $all_image_ids[] = $featured_id;
        }
        
        // 2. Obtener imágenes de la galería (campo meta gallery_ids)
        $gallery_ids = get_post_meta($post_id, 'gallery_ids', true);
        if (!empty($gallery_ids) && is_array($gallery_ids)) {
            foreach ($gallery_ids as $gallery_id) {
                if (!in_array($gallery_id, $all_image_ids)) {
                    $all_image_ids[] = $gallery_id;
                }
            }
        }
        
        // 3. Obtener imágenes insertadas en el contenido del editor (attachments con post_parent)
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_parent' => $post_id,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
        
        foreach ($attachments as $attachment) {
            if (!in_array($attachment->ID, $all_image_ids)) {
                $all_image_ids[] = $attachment->ID;
            }
        }
        
        // 4. También buscar imágenes en el contenido HTML (por si acaso)
        $content = $post->post_content;
        if (!empty($content)) {
            preg_match_all('/wp-image-(\d+)/', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $img_id) {
                    $img_id = intval($img_id);
                    if ($img_id > 0 && !in_array($img_id, $all_image_ids)) {
                        $all_image_ids[] = $img_id;
                    }
                }
            }
        }
        
        // Si no hay imágenes, devolver array vacío
        if (empty($all_image_ids)) {
            return new WP_REST_Response(array('images' => array()), 200);
        }
        
        // Obtener información de cada imagen con tamaños optimizados
        $images = array();
        foreach ($all_image_ids as $image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            if ($image_url) {
                // Usar función helper si está disponible, sino usar tamaños estándar
                if (function_exists('agrochamba_get_optimized_image_url')) {
                    $images[] = array(
                        'id' => $image_id,
                        'source_url' => $image_url,
                        'card_url' => agrochamba_get_optimized_image_url($image_id, 'card'),
                        'detail_url' => agrochamba_get_optimized_image_url($image_id, 'detail'),
                        'thumb_url' => agrochamba_get_optimized_image_url($image_id, 'thumb'),
                        'thumbnail_url' => wp_get_attachment_image_url($image_id, 'thumbnail'),
                        'medium_url' => wp_get_attachment_image_url($image_id, 'medium'),
                        'large_url' => wp_get_attachment_image_url($image_id, 'large')
                    );
                } else {
                    // Fallback a tamaños estándar
                    $images[] = array(
                        'id' => $image_id,
                        'source_url' => $image_url,
                        'thumbnail_url' => wp_get_attachment_image_url($image_id, 'thumbnail'),
                        'medium_url' => wp_get_attachment_image_url($image_id, 'medium'),
                        'large_url' => wp_get_attachment_image_url($image_id, 'large')
                    );
                }
            }
        }
        
        return new WP_REST_Response(array('images' => $images), 200);
    }
}

// ==========================================
// REGISTRAR ENDPOINT
// ==========================================
add_action('rest_api_init', function () {
    $routes = rest_get_server()->get_routes();
    $route_exists = isset($routes['/agrochamba/v1/jobs/(?P<id>\d+)/images']);
    
    if (!$route_exists) {
        register_rest_route('agrochamba/v1', '/jobs/(?P<id>\d+)/images', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_job_images',
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

