<?php
/**
 * Controlador REST API para Logo de Empresa
 * 
 * Endpoints para que las empresas puedan actualizar su logo
 *
 * @package AgroChamba\API\Empresas
 */

namespace AgroChamba\API\Empresas;

use WP_Error;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

class EmpresaLogoController
{
    const API_NAMESPACE = 'agrochamba/v1';

    /**
     * Registrar rutas REST API
     */
    public static function register_routes(): void
    {
        // Actualizar logo de empresa
        register_rest_route(self::API_NAMESPACE, '/empresas/(?P<id>\d+)/logo', [
            [
                'methods' => 'POST',
                'callback' => [self::class, 'upload_logo'],
                'permission_callback' => [self::class, 'check_permissions'],
                'args' => [
                    'id' => [
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        },
                    ],
                ],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [self::class, 'delete_logo'],
                'permission_callback' => [self::class, 'check_permissions'],
                'args' => [
                    'id' => [
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        },
                    ],
                ],
            ],
        ]);

        // Actualizar logo de mi empresa (para empresas)
        register_rest_route(self::API_NAMESPACE, '/me/empresa/logo', [
            [
                'methods' => 'POST',
                'callback' => [self::class, 'upload_my_empresa_logo'],
                'permission_callback' => function() {
                    return is_user_logged_in();
                },
            ],
            [
                'methods' => 'DELETE',
                'callback' => [self::class, 'delete_my_empresa_logo'],
                'permission_callback' => function() {
                    return is_user_logged_in();
                },
            ],
        ]);
    }

    /**
     * Verificar permisos para editar empresa
     */
    public static function check_permissions($request): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $empresa_id = intval($request->get_param('id'));
        $current_user = wp_get_current_user();

        // Administradores pueden editar cualquier empresa
        if (in_array('administrator', $current_user->roles)) {
            return true;
        }

        // Empresas solo pueden editar su propia empresa
        if (in_array('employer', $current_user->roles)) {
            $empresa = agrochamba_get_empresa_by_user_id($current_user->ID);
            return $empresa && $empresa->ID === $empresa_id;
        }

        return false;
    }

    /**
     * Subir logo de empresa
     */
    public static function upload_logo($request): WP_REST_Response|WP_Error
    {
        $empresa_id = intval($request->get_param('id'));
        $empresa = get_post($empresa_id);

        if (!$empresa || $empresa->post_type !== 'empresa') {
            return new WP_Error(
                'empresa_not_found',
                'Empresa no encontrada.',
                ['status' => 404]
            );
        }

        if (empty($_FILES['file'])) {
            return new WP_Error(
                'no_file',
                'No se encontró el archivo en el campo "file".',
                ['status' => 400]
            );
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

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

        // Subir archivo
        $attachment_id = media_handle_upload('file', 0);
        
        if (is_wp_error($attachment_id)) {
            return new WP_Error(
                'upload_error',
                'Error al subir la imagen: ' . $attachment_id->get_error_message(),
                ['status' => 500]
            );
        }

        // Eliminar logo anterior si existe
        $old_logo_id = get_post_thumbnail_id($empresa_id);
        if ($old_logo_id && $old_logo_id != $attachment_id) {
            wp_delete_attachment($old_logo_id, true);
        }

        // Establecer como imagen destacada (logo)
        set_post_thumbnail($empresa_id, $attachment_id);

        $logo_url = wp_get_attachment_image_url($attachment_id, 'full');

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Logo actualizado correctamente.',
            'logo_id' => $attachment_id,
            'logo_url' => $logo_url,
        ], 200);
    }

    /**
     * Eliminar logo de empresa
     */
    public static function delete_logo($request): WP_REST_Response|WP_Error
    {
        $empresa_id = intval($request->get_param('id'));
        $empresa = get_post($empresa_id);

        if (!$empresa || $empresa->post_type !== 'empresa') {
            return new WP_Error(
                'empresa_not_found',
                'Empresa no encontrada.',
                ['status' => 404]
            );
        }

        $logo_id = get_post_thumbnail_id($empresa_id);
        
        if ($logo_id) {
            delete_post_thumbnail($empresa_id);
            wp_delete_attachment($logo_id, true);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Logo eliminado correctamente.',
        ], 200);
    }

    /**
     * Subir logo de mi empresa (para usuarios employer)
     */
    public static function upload_my_empresa_logo($request): WP_REST_Response|WP_Error
    {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                'Debes iniciar sesión para actualizar el logo.',
                ['status' => 401]
            );
        }

        $current_user = wp_get_current_user();
        
        if (!in_array('employer', $current_user->roles)) {
            return new WP_Error(
                'not_employer',
                'Solo las empresas pueden actualizar su logo.',
                ['status' => 403]
            );
        }

        $empresa = agrochamba_get_empresa_by_user_id($current_user->ID);
        
        if (!$empresa) {
            return new WP_Error(
                'empresa_not_found',
                'No tienes una empresa asociada. Contacta al administrador.',
                ['status' => 404]
            );
        }

        // Usar el método de upload_logo con el ID de la empresa
        $request->set_param('id', $empresa->ID);
        return self::upload_logo($request);
    }

    /**
     * Eliminar logo de mi empresa (para usuarios employer)
     */
    public static function delete_my_empresa_logo($request): WP_REST_Response|WP_Error
    {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                'Debes iniciar sesión para eliminar el logo.',
                ['status' => 401]
            );
        }

        $current_user = wp_get_current_user();
        
        if (!in_array('employer', $current_user->roles)) {
            return new WP_Error(
                'not_employer',
                'Solo las empresas pueden eliminar su logo.',
                ['status' => 403]
            );
        }

        $empresa = agrochamba_get_empresa_by_user_id($current_user->ID);
        
        if (!$empresa) {
            return new WP_Error(
                'empresa_not_found',
                'No tienes una empresa asociada.',
                ['status' => 404]
            );
        }

        // Usar el método de delete_logo con el ID de la empresa
        $request->set_param('id', $empresa->ID);
        return self::delete_logo($request);
    }
}

