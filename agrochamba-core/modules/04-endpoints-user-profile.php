<?php
/**
 * =============================================================
 * MÓDULO 4: ENDPOINTS DE PERFIL DE USUARIO
 * =============================================================
 * 
 * Endpoints:
 * - GET /agrochamba/v1/me/profile - Obtener perfil del usuario actual
 * - PUT /agrochamba/v1/me/profile - Actualizar perfil del usuario actual
 * - POST /agrochamba/v1/me/profile/photo - Subir foto de perfil
 * - DELETE /agrochamba/v1/me/profile/photo - Eliminar foto de perfil
 * - GET /agrochamba/v1/users/{user_id}/profile - Ver perfil público de otro usuario
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================
// SHIM DE COMPATIBILIDAD → Delegar a controladores con namespace
// =============================================================
if (!defined('AGROCHAMBA_PROFILE_NAMESPACE_INITIALIZED')) {
    define('AGROCHAMBA_PROFILE_NAMESPACE_INITIALIZED', true);

    $delegated = false;
    if (class_exists('AgroChamba\\API\\Profile\\UserProfile')) {
        if (function_exists('error_log')) {
            error_log('AgroChamba: Delegando /me/profile a AgroChamba\\API\\Profile\\UserProfile (migración namespaces).');
        }
        \AgroChamba\API\Profile\UserProfile::init();
        $delegated = true;
    }
    if (class_exists('AgroChamba\\API\\Profile\\ProfilePhoto')) {
        if (function_exists('error_log')) {
            error_log('AgroChamba: Delegando /me/profile/photo a AgroChamba\\API\\Profile\\ProfilePhoto (migración namespaces).');
        }
        \AgroChamba\API\Profile\ProfilePhoto::init();
        $delegated = true;
    }

    // Si delegamos al menos un controlador, evitamos registrar endpoints legacy
    if ($delegated) {
        return;
    } else {
        if (function_exists('error_log')) {
            error_log('AgroChamba: No se encontraron controladores de Perfil con namespace. Usando implementación legacy.');
        }
    }
}

// ==========================================
// 1. OBTENER PERFIL DEL USUARIO ACTUAL
// ==========================================
if (!function_exists('agrochamba_get_user_profile')) {
    function agrochamba_get_user_profile($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para ver tu perfil.', array('status' => 401));
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'Usuario no encontrado.', array('status' => 404));
        }

        // Obtener información adicional del perfil
        $profile_photo_id = get_user_meta($user_id, 'profile_photo_id', true);
        $phone = get_user_meta($user_id, 'phone', true);
        $bio = get_user_meta($user_id, 'bio', true);
        $company_description = get_user_meta($user_id, 'company_description', true);
        $company_address = get_user_meta($user_id, 'company_address', true);
        $company_phone = get_user_meta($user_id, 'company_phone', true);
        $company_website = get_user_meta($user_id, 'company_website', true);
        $company_facebook = get_user_meta($user_id, 'company_facebook', true);
        $company_instagram = get_user_meta($user_id, 'company_instagram', true);
        $company_linkedin = get_user_meta($user_id, 'company_linkedin', true);
        $company_twitter = get_user_meta($user_id, 'company_twitter', true);

        // Obtener URL de la foto de perfil
        $profile_photo_url = null;
        if ($profile_photo_id) {
            $profile_photo_url = wp_get_attachment_image_url($profile_photo_id, 'full');
        }

        // Determinar si es empresa
        $is_enterprise = in_array('employer', $user->roles) || in_array('administrator', $user->roles);

        $profile_data = array(
            'user_id' => $user_id,
            'username' => $user->user_login,
            'display_name' => $user->display_name,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'roles' => $user->roles,
            'is_enterprise' => $is_enterprise,
            'profile_photo_id' => $profile_photo_id ? intval($profile_photo_id) : null,
            'profile_photo_url' => $profile_photo_url,
            'phone' => $phone ?: '',
            'bio' => $bio ?: '',
        );

        // Si es empresa, incluir información adicional
        if ($is_enterprise) {
            $profile_data['company_description'] = $company_description ?: '';
            $profile_data['company_address'] = $company_address ?: '';
            $profile_data['company_phone'] = $company_phone ?: '';
            $profile_data['company_website'] = $company_website ?: '';
            $profile_data['company_facebook'] = $company_facebook ?: '';
            $profile_data['company_instagram'] = $company_instagram ?: '';
            $profile_data['company_linkedin'] = $company_linkedin ?: '';
            $profile_data['company_twitter'] = $company_twitter ?: '';
        }

        return new WP_REST_Response($profile_data, 200);
    }
}

// ==========================================
// 2. ACTUALIZAR PERFIL DEL USUARIO ACTUAL
// ==========================================
if (!function_exists('agrochamba_update_user_profile')) {
    function agrochamba_update_user_profile($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para actualizar tu perfil.', array('status' => 401));
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'Usuario no encontrado.', array('status' => 404));
        }

        $params = $request->get_json_params();
        $updated_fields = array();

        // Actualizar display_name
        if (isset($params['display_name'])) {
            $display_name = sanitize_text_field($params['display_name']);
            if (!empty($display_name)) {
                $old_display_name = $user->display_name;
                wp_update_user(array('ID' => $user_id, 'display_name' => $display_name));
                $updated_fields[] = 'display_name';
                
                // ==========================================
                // ACTUALIZAR TAXONOMÍA DE EMPRESA SI ES EMPRESA
                // ==========================================
                if (in_array('employer', $user->roles) || in_array('administrator', $user->roles)) {
                    // Buscar el término antiguo
                    $old_term = get_term_by('name', $old_display_name, 'empresa');
                    
                    if ($old_term) {
                        // Actualizar el término existente
                        wp_update_term($old_term->term_id, 'empresa', array(
                            'name' => $display_name,
                            'slug' => sanitize_title($display_name)
                        ));
                        
                        // Actualizar el meta del usuario con el nuevo term_id
                        $updated_term = get_term_by('name', $display_name, 'empresa');
                        if ($updated_term) {
                            update_user_meta($user_id, 'empresa_term_id', $updated_term->term_id);
                        }
                    } else {
                        // Si no existe, crear uno nuevo
                        $term_result = wp_insert_term(
                            $display_name,
                            'empresa',
                            array(
                                'description' => 'Empresa: ' . $display_name,
                                'slug' => sanitize_title($display_name)
                            )
                        );
                        
                        if (!is_wp_error($term_result) && isset($term_result['term_id'])) {
                            update_user_meta($user_id, 'empresa_term_id', $term_result['term_id']);
                        }
                    }
                    
                    // Invalidar caché del perfil de empresa
                    if (function_exists('agrochamba_invalidate_company_cache')) {
                        agrochamba_invalidate_company_cache($old_display_name);
                        agrochamba_invalidate_company_cache($display_name);
                    }
                }
            }
        }

        // Actualizar first_name
        if (isset($params['first_name'])) {
            $first_name = sanitize_text_field($params['first_name']);
            wp_update_user(array('ID' => $user_id, 'first_name' => $first_name));
            $updated_fields[] = 'first_name';
        }

        // Actualizar last_name
        if (isset($params['last_name'])) {
            $last_name = sanitize_text_field($params['last_name']);
            wp_update_user(array('ID' => $user_id, 'last_name' => $last_name));
            $updated_fields[] = 'last_name';
        }

        // Actualizar email
        if (isset($params['email']) && is_email($params['email'])) {
            $email = sanitize_email($params['email']);
            $existing_user = get_user_by('email', $email);
            if (!$existing_user || $existing_user->ID == $user_id) {
                wp_update_user(array('ID' => $user_id, 'user_email' => $email));
                $updated_fields[] = 'email';
            }
        }

        // Actualizar teléfono
        if (isset($params['phone'])) {
            update_user_meta($user_id, 'phone', sanitize_text_field($params['phone']));
            $updated_fields[] = 'phone';
        }

        // Actualizar biografía
        if (isset($params['bio'])) {
            update_user_meta($user_id, 'bio', sanitize_textarea_field($params['bio']));
            $updated_fields[] = 'bio';
        }

        // Actualizar información de empresa (solo para empresas)
        $is_enterprise = in_array('employer', $user->roles) || in_array('administrator', $user->roles);
        if ($is_enterprise) {
            $company_fields = array(
                'company_description', 'company_address', 'company_phone', 'company_website',
                'company_facebook', 'company_instagram', 'company_linkedin', 'company_twitter'
            );
            
            foreach ($company_fields as $field) {
                if (isset($params[$field])) {
                    $value = in_array($field, array('company_website', 'company_facebook', 'company_instagram', 'company_linkedin', 'company_twitter'))
                        ? esc_url_raw($params[$field])
                        : sanitize_textarea_field($params[$field]);
                    update_user_meta($user_id, $field, $value);
                    $updated_fields[] = $field;
                }
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Perfil actualizado correctamente.',
            'updated_fields' => $updated_fields
        ), 200);
    }
}

// ==========================================
// 3. SUBIR FOTO DE PERFIL
// ==========================================
if (!function_exists('agrochamba_upload_profile_photo')) {
    function agrochamba_upload_profile_photo($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para subir una foto de perfil.', array('status' => 401));
        }

        $user_id = get_current_user_id();

        if (empty($_FILES['file'])) {
            return new WP_Error('no_file', 'No se ha subido ningún archivo.', array('status' => 400));
        }

        $file = $_FILES['file'];
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');

        if (!in_array($file['type'], $allowed_types)) {
            return new WP_Error('invalid_file_type', 'Tipo de archivo no permitido. Solo se permiten imágenes (JPG, PNG, GIF, WEBP).', array('status' => 400));
        }

        // Validar tamaño de archivo (máximo 5MB por defecto, configurable)
        $max_file_size = apply_filters('agrochamba_max_upload_size', 5 * 1024 * 1024); // 5MB en bytes
        if ($file['size'] > $max_file_size) {
            $max_size_mb = round($max_file_size / (1024 * 1024), 1);
            return new WP_Error(
                'file_too_large',
                sprintf('El archivo es demasiado grande. El tamaño máximo permitido es %s MB.', $max_size_mb),
                array('status' => 400)
            );
        }

        // Validar que el archivo no esté vacío
        if ($file['size'] === 0) {
            return new WP_Error('empty_file', 'El archivo está vacío.', array('status' => 400));
        }

        // Validar que el archivo sea realmente una imagen
        $image_info = @getimagesize($file['tmp_name']);
        if ($image_info === false) {
            return new WP_Error('invalid_image', 'El archivo no es una imagen válida.', array('status' => 400));
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error'])) {
            return new WP_Error('upload_error', $upload['error'], array('status' => 500));
        }

        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $upload['file']);

        if (is_wp_error($attach_id)) {
            return new WP_Error('attachment_error', 'Error al crear el attachment.', array('status' => 500));
        }

        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Eliminar foto anterior
        $old_photo_id = get_user_meta($user_id, 'profile_photo_id', true);
        if ($old_photo_id && $old_photo_id != $attach_id) {
            wp_delete_attachment($old_photo_id, true);
        }

        update_user_meta($user_id, 'profile_photo_id', $attach_id);

        $photo_urls = array(
            'full' => wp_get_attachment_image_url($attach_id, 'full'),
            'thumbnail' => wp_get_attachment_image_url($attach_id, 'thumbnail'),
            'medium' => wp_get_attachment_image_url($attach_id, 'medium'),
            'large' => wp_get_attachment_image_url($attach_id, 'large')
        );

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Foto de perfil actualizada correctamente.',
            'photo_id' => $attach_id,
            'photo_urls' => $photo_urls
        ), 200);
    }
}

// ==========================================
// 4. ELIMINAR FOTO DE PERFIL
// ==========================================
if (!function_exists('agrochamba_delete_profile_photo')) {
    function agrochamba_delete_profile_photo($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesión para eliminar tu foto de perfil.', array('status' => 401));
        }

        $user_id = get_current_user_id();
        $photo_id = get_user_meta($user_id, 'profile_photo_id', true);

        if (!$photo_id) {
            return new WP_Error('no_photo', 'No tienes una foto de perfil para eliminar.', array('status' => 404));
        }

        $deleted = wp_delete_attachment($photo_id, true);

        if (!$deleted) {
            return new WP_Error('delete_error', 'Error al eliminar la foto de perfil.', array('status' => 500));
        }

        delete_user_meta($user_id, 'profile_photo_id');

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Foto de perfil eliminada correctamente.'
        ), 200);
    }
}

// ==========================================
// 5. OBTENER PERFIL PÚBLICO DE OTRO USUARIO
// ==========================================
if (!function_exists('agrochamba_get_public_profile')) {
    function agrochamba_get_public_profile($request) {
        $user_id = intval($request->get_param('user_id'));

        if ($user_id <= 0) {
            return new WP_Error('invalid_user_id', 'ID de usuario inválido.', array('status' => 400));
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'Usuario no encontrado.', array('status' => 404));
        }

        $profile_photo_id = get_user_meta($user_id, 'profile_photo_id', true);
        $bio = get_user_meta($user_id, 'bio', true);
        $company_description = get_user_meta($user_id, 'company_description', true);
        $company_address = get_user_meta($user_id, 'company_address', true);
        $company_phone = get_user_meta($user_id, 'company_phone', true);
        $company_website = get_user_meta($user_id, 'company_website', true);
        $company_facebook = get_user_meta($user_id, 'company_facebook', true);
        $company_instagram = get_user_meta($user_id, 'company_instagram', true);
        $company_linkedin = get_user_meta($user_id, 'company_linkedin', true);
        $company_twitter = get_user_meta($user_id, 'company_twitter', true);

        $profile_photo_url = null;
        if ($profile_photo_id) {
            $profile_photo_url = wp_get_attachment_image_url($profile_photo_id, 'full');
        }

        $is_enterprise = in_array('employer', $user->roles) || in_array('administrator', $user->roles);

        $profile_data = array(
            'user_id' => $user_id,
            'display_name' => $user->display_name,
            'profile_photo_url' => $profile_photo_url,
            'bio' => $bio ?: '',
            'is_enterprise' => $is_enterprise,
        );

        if ($is_enterprise) {
            $profile_data['company_description'] = $company_description ?: '';
            $profile_data['company_address'] = $company_address ?: '';
            $profile_data['company_phone'] = $company_phone ?: '';
            $profile_data['company_website'] = $company_website ?: '';
            $profile_data['company_facebook'] = $company_facebook ?: '';
            $profile_data['company_instagram'] = $company_instagram ?: '';
            $profile_data['company_linkedin'] = $company_linkedin ?: '';
            $profile_data['company_twitter'] = $company_twitter ?: '';
        }

        return new WP_REST_Response($profile_data, 200);
    }
}

// ==========================================
// REGISTRAR ENDPOINTS
// ==========================================
add_action('rest_api_init', function () {
    $routes = rest_get_server()->get_routes();
    
    // Perfil del usuario actual
    if (!isset($routes['/agrochamba/v1/me/profile'])) {
        register_rest_route('agrochamba/v1', '/me/profile', array(
            array(
                'methods' => 'GET',
                'callback' => 'agrochamba_get_user_profile',
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'PUT',
                'callback' => 'agrochamba_update_user_profile',
                'permission_callback' => '__return_true',
            ),
        ));
    }

    // Foto de perfil
    if (!isset($routes['/agrochamba/v1/me/profile/photo'])) {
        register_rest_route('agrochamba/v1', '/me/profile/photo', array(
            array(
                'methods' => 'POST',
                'callback' => 'agrochamba_upload_profile_photo',
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'DELETE',
                'callback' => 'agrochamba_delete_profile_photo',
                'permission_callback' => '__return_true',
            ),
        ));
    }

    // Perfil público
    if (!isset($routes['/agrochamba/v1/users/(?P<user_id>\d+)/profile'])) {
        register_rest_route('agrochamba/v1', '/users/(?P<user_id>\d+)/profile', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_public_profile',
            'permission_callback' => '__return_true',
            'args' => array(
                'user_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }
}, 20);

