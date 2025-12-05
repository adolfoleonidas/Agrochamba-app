<?php
/**
 * Controlador de Imágenes
 *
 * Proporciona endpoints relacionados a imágenes asociadas a trabajos.
 *
 * @package AgroChamba
 * @subpackage API\Media
 */

namespace AgroChamba\API\Media;

use WP_Error;
use WP_REST_Response;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class ImagesController
{
    /**
     * Namespace de la API
     */
    private const API_NAMESPACE = 'agrochamba/v1';

    /**
     * Inicializa el controlador registrando los hooks necesarios.
     */
    public static function init(): void
    {
        add_action('rest_api_init', [self::class, 'register_routes'], 20);
    }

    /**
     * Registrar rutas de la API
     */
    public static function register_routes(): void
    {
        $routes = rest_get_server()->get_routes();
        $route_key = '/' . self::API_NAMESPACE . '/jobs/(?P<id>\d+)/images';

        if (!isset($routes[$route_key])) {
            register_rest_route(self::API_NAMESPACE, '/jobs/(?P<id>\d+)/images', [
                'methods' => 'GET',
                'callback' => [self::class, 'get_job_images'],
                'permission_callback' => '__return_true',
                'args' => [
                    'id' => [
                        'required' => true,
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        },
                    ],
                ],
            ]);
        }
    }

    /**
     * Obtener imágenes asociadas a un trabajo
     */
    public static function get_job_images(WP_REST_Request $request)
    {
        $post_id = intval($request->get_param('id'));

        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', 'Post no encontrado con ID: ' . $post_id, ['status' => 404]);
        }

        if ($post->post_type !== 'trabajo') {
            return new WP_Error('invalid_type', 'El post con ID ' . $post_id . ' no es de tipo "trabajo", es de tipo "' . $post->post_type . '"', ['status' => 400]);
        }

        $all_image_ids = [];

        // 1. Imagen destacada
        $featured_id = get_post_thumbnail_id($post_id);
        if ($featured_id) {
            $all_image_ids[] = $featured_id;
        }

        // 2. Galería (meta gallery_ids)
        $gallery_ids = get_post_meta($post_id, 'gallery_ids', true);
        if (!empty($gallery_ids) && is_array($gallery_ids)) {
            foreach ($gallery_ids as $gallery_id) {
                if (!in_array($gallery_id, $all_image_ids)) {
                    $all_image_ids[] = $gallery_id;
                }
            }
        }

        // 3. Adjuntos con post_parent
        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_parent' => $post_id,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ]);
        foreach ($attachments as $attachment) {
            if (!in_array($attachment->ID, $all_image_ids)) {
                $all_image_ids[] = $attachment->ID;
            }
        }

        // 4. Buscar en el contenido
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

        if (empty($all_image_ids)) {
            return new WP_REST_Response(['images' => []], 200);
        }

        $images = [];
        foreach ($all_image_ids as $image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            if ($image_url) {
                if (function_exists('agrochamba_get_optimized_image_url')) {
                    $images[] = [
                        'id' => $image_id,
                        'source_url' => $image_url,
                        'card_url' => agrochamba_get_optimized_image_url($image_id, 'card'),
                        'detail_url' => agrochamba_get_optimized_image_url($image_id, 'detail'),
                        'thumb_url' => agrochamba_get_optimized_image_url($image_id, 'thumb'),
                        'thumbnail_url' => wp_get_attachment_image_url($image_id, 'thumbnail'),
                        'medium_url' => wp_get_attachment_image_url($image_id, 'medium'),
                        'large_url' => wp_get_attachment_image_url($image_id, 'large'),
                    ];
                } else {
                    $images[] = [
                        'id' => $image_id,
                        'source_url' => $image_url,
                        'thumbnail_url' => wp_get_attachment_image_url($image_id, 'thumbnail'),
                        'medium_url' => wp_get_attachment_image_url($image_id, 'medium'),
                        'large_url' => wp_get_attachment_image_url($image_id, 'large'),
                    ];
                }
            }
        }

        return new WP_REST_Response(['images' => $images], 200);
    }
}
