<?php
/**
 * Helper Functions
 *
 * Global helper functions used throughout the plugin
 *
 * @package AgroChamba
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get optimized image URL
 *
 * @param int $attachment_id Attachment ID
 * @param string $size Image size (card, detail, thumb)
 * @return string|false Image URL or false
 */
function agrochamba_get_optimized_image_url($attachment_id, $size = 'card') {
    $size_map = array(
        'card' => 'agrochamba_card',
        'detail' => 'agrochamba_detail',
        'thumb' => 'agrochamba_thumb',
    );

    $wp_size = isset($size_map[$size]) ? $size_map[$size] : 'full';
    $url = wp_get_attachment_image_url($attachment_id, $wp_size);

    return $url ? $url : wp_get_attachment_image_url($attachment_id, 'full');
}

/**
 * Generate JWT token helper
 *
 * @param string $username Username
 * @param string $password Password
 * @return string|null JWT token or null on failure
 */
function agrochamba_generate_jwt_token($username, $password) {
    $login_credentials = array(
        'username' => $username,
        'password' => $password
    );

    $token_url = rest_url('jwt-auth/v1/token');
    $token_response = wp_remote_post($token_url, array(
        'body' => json_encode($login_credentials),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 10
    ));

    if (!is_wp_error($token_response)) {
        $response_code = wp_remote_retrieve_response_code($token_response);
        if ($response_code === 200) {
            $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
            if (isset($token_body['token'])) {
                return $token_body['token'];
            }
        }
    }

    return null;
}

/**
 * Check if user is employer or admin
 *
 * @param WP_User|int $user User object or ID
 * @return bool
 */
function agrochamba_is_employer($user = null) {
    if (is_null($user)) {
        $user = wp_get_current_user();
    } elseif (is_int($user)) {
        $user = get_userdata($user);
    }

    if (!$user || !$user->exists()) {
        return false;
    }

    return in_array('employer', $user->roles) || in_array('administrator', $user->roles);
}

/**
 * Log message (wrapper for error_log)
 *
 * @param string $message Message to log
 * @param string $level Log level (info, error, debug)
 */
function agrochamba_log($message, $level = 'info') {
    if (WP_DEBUG && WP_DEBUG_LOG) {
        error_log('[AgroChamba][' . strtoupper($level) . '] ' . $message);
    }
}

/**
 * Obtener empresa CPT por user_id
 *
 * @param int $user_id ID del usuario WordPress
 * @return WP_Post|null Post de empresa o null si no existe
 */
function agrochamba_get_empresa_by_user_id($user_id) {
    if (!class_exists('AgroChamba\\PostTypes\\EmpresaPostType')) {
        return null;
    }
    return \AgroChamba\PostTypes\EmpresaPostType::get_empresa_by_user_id($user_id);
}

/**
 * Obtener user_id por empresa_id
 *
 * @param int $empresa_id ID del post de empresa
 * @return int|null ID del usuario o null si no existe
 */
function agrochamba_get_user_id_by_empresa_id($empresa_id) {
    if (!class_exists('AgroChamba\\PostTypes\\EmpresaPostType')) {
        return null;
    }
    return \AgroChamba\PostTypes\EmpresaPostType::get_user_id_by_empresa_id($empresa_id);
}

/**
 * Crear empresa CPT automáticamente cuando se crea un usuario employer
 *
 * @param int $user_id ID del usuario creado
 */
function agrochamba_create_empresa_on_user_register($user_id) {
    $user = get_userdata($user_id);
    
    // Solo crear si es employer
    if (!$user || !in_array('employer', $user->roles)) {
        return;
    }
    
    // Verificar si ya existe una empresa para este usuario
    $existing_empresa = agrochamba_get_empresa_by_user_id($user_id);
    if ($existing_empresa) {
        return;
    }
    
    // Obtener razón social del usuario
    $razon_social = get_user_meta($user_id, 'razon_social', true);
    $display_name = $user->display_name;
    $title = !empty($razon_social) ? $razon_social : $display_name;
    
    // Crear el post de empresa
    // Crear como 'publish' para que sea visible inmediatamente
    // Los datos se pueden completar después desde el perfil
    $empresa_id = wp_insert_post([
        'post_type'   => 'empresa',
        'post_title'  => $title,
        'post_status' => 'publish', // Publicar automáticamente para que aparezca en el CPT
        'post_author' => $user_id,
    ]);
    
    if (!is_wp_error($empresa_id) && $empresa_id > 0) {
        // Asociar con el usuario
        update_post_meta($empresa_id, '_empresa_user_id', $user_id);

        // IMPORTANTE: Guardar el ID del CPT en el user_meta para el login
        update_user_meta($user_id, 'empresa_cpt_id', $empresa_id);

        // Sincronizar datos básicos del usuario al CPT
        $ruc = get_user_meta($user_id, 'ruc', true);
        if ($ruc) {
            update_post_meta($empresa_id, '_empresa_ruc', $ruc);
        }
        if ($razon_social) {
            update_post_meta($empresa_id, '_empresa_razon_social', $razon_social);
        }
        update_post_meta($empresa_id, '_empresa_nombre_comercial', $display_name);

        // Sincronizar todos los datos de empresa del usuario al CPT
        agrochamba_sync_user_data_to_empresa_cpt($user_id, $empresa_id);
    }
}
add_action('user_register', 'agrochamba_create_empresa_on_user_register', 20);

/**
 * Sincronizar datos de usuario al CPT Empresa
 * Mueve información de user_meta al CPT para no recargar usuarios
 *
 * @param int $user_id ID del usuario
 * @param int $empresa_id ID del CPT Empresa (opcional, se busca si no se proporciona)
 * @return bool True si se sincronizó correctamente
 */
function agrochamba_sync_user_data_to_empresa_cpt($user_id, $empresa_id = null) {
    if (!$empresa_id) {
        $empresa = agrochamba_get_empresa_by_user_id($user_id);
        if (!$empresa) {
            return false;
        }
        $empresa_id = $empresa->ID;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }

    // Sincronizar datos básicos
    $ruc = get_user_meta($user_id, 'ruc', true);
    if ($ruc && !get_post_meta($empresa_id, '_empresa_ruc', true)) {
        update_post_meta($empresa_id, '_empresa_ruc', $ruc);
    }

    $razon_social = get_user_meta($user_id, 'razon_social', true);
    if ($razon_social && !get_post_meta($empresa_id, '_empresa_razon_social', true)) {
        update_post_meta($empresa_id, '_empresa_razon_social', $razon_social);
    }

    // Sincronizar información de empresa desde user_meta al CPT
    $company_fields = [
        'company_description' => 'post_content', // Se guarda en el contenido del post
        'company_address' => '_empresa_direccion',
        'company_phone' => '_empresa_telefono',
        'company_website' => '_empresa_website',
        'company_facebook' => '_empresa_facebook',
        'company_instagram' => '_empresa_instagram',
        'company_linkedin' => '_empresa_linkedin',
        'company_twitter' => '_empresa_twitter',
        'company_sector' => '_empresa_sector',
        'company_ciudad' => '_empresa_ciudad',
    ];

    foreach ($company_fields as $user_meta_key => $empresa_meta_key) {
        $value = get_user_meta($user_id, $user_meta_key, true);
        if ($value) {
            if ($empresa_meta_key === 'post_content') {
                // Actualizar contenido del post si está vacío
                $post = get_post($empresa_id);
                if ($post && empty($post->post_content)) {
                    wp_update_post([
                        'ID' => $empresa_id,
                        'post_content' => $value,
                    ]);
                }
            } else {
                // Solo actualizar si no existe en el CPT
                if (!get_post_meta($empresa_id, $empresa_meta_key, true)) {
                    update_post_meta($empresa_id, $empresa_meta_key, $value);
                }
            }
        }
    }

    return true;
}
add_action('user_register', function($user_id) {
    // Sincronizar cuando se crea un nuevo usuario employer
    $user = get_userdata($user_id);
    if ($user && in_array('employer', $user->roles)) {
        agrochamba_sync_user_data_to_empresa_cpt($user_id);
    }
}, 30);

/**
 * Obtener contador de ofertas activas de una empresa
 *
 * @param int $empresa_id ID del CPT Empresa
 * @return int Número de ofertas activas
 */
function agrochamba_get_empresa_ofertas_count($empresa_id) {
    if (!$empresa_id) {
        return 0;
    }
    
    $args = [
        'post_type' => 'trabajo',
        'post_status' => 'publish',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'empresa_id',
                'value' => $empresa_id,
                'compare' => '=',
            ],
            [
                'key' => 'estado',
                'value' => 'activa',
                'compare' => '=',
            ],
        ],
        'posts_per_page' => -1,
        'fields' => 'ids',
    ];
    
    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Obtener todas las ofertas de una empresa
 *
 * @param int $empresa_id ID del CPT Empresa
 * @param array $args Argumentos adicionales para WP_Query
 * @return WP_Query Query con las ofertas
 */
function agrochamba_get_empresa_ofertas($empresa_id, $args = []) {
    $default_args = [
        'post_type' => 'trabajo',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'empresa_id',
                'value' => $empresa_id,
                'compare' => '=',
            ],
        ],
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ];
    
    // Si el usuario proporciona su propio meta_query, necesitamos combinarlo correctamente
    if (isset($args['meta_query'])) {
        // Combinar con la condición de empresa_id usando relación AND
        $args['meta_query'] = [
            'relation' => 'AND',
            [
                'key' => 'empresa_id',
                'value' => $empresa_id,
                'compare' => '=',
            ],
            $args['meta_query'],
        ];
    }
    
    $query_args = wp_parse_args($args, $default_args);
    return new WP_Query($query_args);
}
