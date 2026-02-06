<?php
/**
 * Controlador de Foto de Perfil
 *
 * @package AgroChamba
 * @subpackage API\Profile
 */

namespace AgroChamba\API\Profile;

use WP_Error;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

class ProfilePhoto
{
    const API_NAMESPACE = 'agrochamba/v1';

    public static function init(): void
    {
        add_action('rest_api_init', [__CLASS__, 'register_routes'], 20);
    }

    public static function register_routes(): void
    {
        $routes = rest_get_server()->get_routes();

        if (!isset($routes['/' . self::API_NAMESPACE . '/me/profile/photo'])) {
            register_rest_route(self::API_NAMESPACE, '/me/profile/photo', [
                [
                    'methods' => 'POST',
                    'callback' => [__CLASS__, 'upload_photo'],
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    },
                ],
                [
                    'methods' => 'DELETE',
                    'callback' => [__CLASS__, 'delete_photo'],
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    },
                ],
            ]);
        }
    }

    public static function upload_photo($request)
    {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para subir foto de perfil.', ['status' => 401]);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Permitir subir mediante "file" en $_FILES o URL en body
        if (empty($_FILES['file'])) {
            return new WP_Error('no_file', 'No se encontró el archivo en el campo "file".', ['status' => 400]);
        }

        $user_id = get_current_user_id();

        // Validar tipo de archivo
        $file = $_FILES['file'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowed_types)) {
            return new WP_Error(
                'invalid_file_type',
                'Tipo de archivo no permitido. Solo se permiten imágenes (JPEG, PNG, GIF, WebP).',
                ['status' => 400]
            );
        }

        // Validar tamaño (máximo 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            return new WP_Error(
                'file_too_large',
                'El archivo es demasiado grande. El tamaño máximo permitido es 5MB.',
                ['status' => 400]
            );
        }

        // Usar wp_handle_upload directamente para evitar problemas con test_form en REST API
        $upload_overrides = ['test_form' => false];
        $movefile = wp_handle_upload($file, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            // El archivo se subió correctamente, ahora creamos el attachment
            $filename = $movefile['file'];
            $filetype = wp_check_filetype(basename($filename), null);
            
            $attachment = [
                'post_mime_type' => $filetype['type'],
                'post_title'     => sanitize_file_name(preg_replace('/\.[^.]+$/', '', basename($filename))),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ];

            $attachment_id = wp_insert_attachment($attachment, $filename, 0);

            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attachment_id, $filename);
                wp_update_attachment_metadata($attachment_id, $attach_data);
            } else {
                return new WP_Error('upload_error', 'Error al crear el attachment: ' . $attachment_id->get_error_message(), ['status' => 500]);
            }
        } else {
            return new WP_Error('upload_error', 'Error al subir el archivo: ' . $movefile['error'], ['status' => 400]);
        }

        $user = get_userdata($user_id);
        $is_enterprise = $user && (in_array('employer', (array)$user->roles, true) || in_array('administrator', (array)$user->roles, true));
        
        if ($is_enterprise) {
            // Para empresas: guardar como featured_image del CPT
            $empresa = agrochamba_get_empresa_by_user_id($user_id);
            
            if ($empresa) {
                // Eliminar foto anterior del CPT si existe
                $old_logo_id = get_post_thumbnail_id($empresa->ID);
                if ($old_logo_id && $old_logo_id != $attachment_id) {
                    wp_delete_attachment($old_logo_id, true);
                }
                
                // Establecer como featured_image del CPT
                set_post_thumbnail($empresa->ID, $attachment_id);
            } else {
                // Si no existe CPT, crear uno automáticamente
                if (function_exists('agrochamba_create_empresa_on_user_register')) {
                    agrochamba_create_empresa_on_user_register($user_id);
                    $empresa = agrochamba_get_empresa_by_user_id($user_id);
                    if ($empresa) {
                        set_post_thumbnail($empresa->ID, $attachment_id);
                    }
                }
            }
            
            // También mantener en user_meta para compatibilidad (pero el CPT es la fuente de verdad)
            update_user_meta($user_id, 'profile_photo_id', (int)$attachment_id);
        } else {
            // Para usuarios normales: guardar en user_meta como antes
            $old_photo_id = (int)get_user_meta($user_id, 'profile_photo_id', true);
            if ($old_photo_id && $old_photo_id != $attachment_id) {
                wp_delete_attachment($old_photo_id, true);
            }
            update_user_meta($user_id, 'profile_photo_id', (int)$attachment_id);
        }

        $photo_url = wp_get_attachment_image_url($attachment_id, 'full');
        // Asegurar que los valores sean string o null (no false) para compatibilidad con la app
        $full_url = wp_get_attachment_image_url($attachment_id, 'full');
        $photo_urls = [
            'full' => $full_url ?: null,
            'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail') ?: $full_url ?: null,
            'medium' => wp_get_attachment_image_url($attachment_id, 'medium') ?: $full_url ?: null,
            'large' => wp_get_attachment_image_url($attachment_id, 'large') ?: $full_url ?: null,
        ];

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Foto de perfil actualizada correctamente.',
            'photo_id' => (int)$attachment_id,
            'photo_url' => $photo_url,
            'photo_urls' => $photo_urls,
        ], 200);
    }

    public static function delete_photo($request)
    {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para eliminar tu foto de perfil.', ['status' => 401]);
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $is_enterprise = $user && (in_array('employer', (array)$user->roles, true) || in_array('administrator', (array)$user->roles, true));
        
        if ($is_enterprise) {
            // Para empresas: eliminar featured_image del CPT
            $empresa = agrochamba_get_empresa_by_user_id($user_id);
            
            if ($empresa) {
                $logo_id = get_post_thumbnail_id($empresa->ID);
                if ($logo_id) {
                    wp_delete_attachment($logo_id, true);
                    delete_post_meta($empresa->ID, '_thumbnail_id');
                }
            }
            
            // También eliminar de user_meta para compatibilidad
            delete_user_meta($user_id, 'profile_photo_id');
        } else {
            // Para usuarios normales: eliminar de user_meta como antes
            $photo_id = (int)get_user_meta($user_id, 'profile_photo_id', true);
            if ($photo_id) {
                wp_delete_attachment($photo_id, true);
                delete_user_meta($user_id, 'profile_photo_id');
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Foto de perfil eliminada correctamente.',
        ], 200);
    }
}
