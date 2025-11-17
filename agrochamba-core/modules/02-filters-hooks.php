<?php
/**
 * =============================================================
 * MÓDULO 2: FILTROS Y HOOKS GENERALES
 * =============================================================
 * 
 * Filtros y hooks que modifican el comportamiento general
 * de WordPress para el sistema AgroChamba
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. AÑADIR ROLES DE USUARIO AL TOKEN JWT
// ==========================================
if (!function_exists('agrochamba_add_user_roles_to_jwt_response')) {
    function agrochamba_add_user_roles_to_jwt_response($data, $user) {
        if (isset($user->roles)) {
            $data['roles'] = $user->roles;
        }
        return $data;
    }
    add_filter('jwt_auth_token_before_dispatch', 'agrochamba_add_user_roles_to_jwt_response', 10, 2);
}

// ==========================================
// 2. PERMITIR SUBIDA DE IMÁGENES PARA ROLES ESPECÍFICOS
// ==========================================
if (!function_exists('agrochamba_allow_media_uploads_for_roles')) {
    function agrochamba_allow_media_uploads_for_roles($allcaps, $cap, $args) {
        // Verificar si se está solicitando la capacidad de subir archivos
        if (isset($cap[0]) && $cap[0] === 'upload_files') {
            $user_id = isset($args[0]) ? $args[0] : get_current_user_id();
            $user = get_userdata($user_id);
            
            if ($user) {
                $allowed_roles = array('employer', 'administrator', 'editor');
            
                // Si el usuario tiene uno de los roles permitidos, darle la capacidad
                if (array_intersect($allowed_roles, $user->roles)) {
                    $allcaps['upload_files'] = true;
                }
            }
        }
        
        return $allcaps;
    }
    add_filter('user_has_cap', 'agrochamba_allow_media_uploads_for_roles', 10, 3);
}

// Permitir subida de archivos también mediante REST API
if (!function_exists('agrochamba_rest_allow_upload')) {
    function agrochamba_rest_allow_upload($result, $server, $request) {
        // Permitir subida de archivos para usuarios autenticados con roles permitidos
        if ($request->get_route() === '/wp/v2/media' && is_user_logged_in()) {
            $user = wp_get_current_user();
            $allowed_roles = array('employer', 'administrator', 'editor');
            
            if (array_intersect($allowed_roles, $user->roles)) {
                return true;
            }
        }
        
        return $result;
    }
    add_filter('rest_pre_dispatch', 'agrochamba_rest_allow_upload', 10, 3);
}

// ==========================================
// 4. VALIDAR TAMAÑO DE ARCHIVOS EN UPLOADS
// ==========================================
if (!function_exists('agrochamba_validate_upload_size')) {
    function agrochamba_validate_upload_size($file) {
        // Solo validar imágenes
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        if (!in_array($file['type'], $allowed_types)) {
            return $file; // Dejar que WordPress maneje otros tipos
        }
        
        // Tamaño máximo configurable (por defecto 5MB)
        $max_file_size = apply_filters('agrochamba_max_upload_size', 5 * 1024 * 1024); // 5MB
        
        if ($file['size'] > $max_file_size) {
            $max_size_mb = round($max_file_size / (1024 * 1024), 1);
            $file['error'] = sprintf('El archivo es demasiado grande. El tamaño máximo permitido es %s MB.', $max_size_mb);
        }
        
        return $file;
    }
    add_filter('wp_handle_upload_prefilter', 'agrochamba_validate_upload_size');
}

// ==========================================
// 3. AÑADIR CAMPOS RUC Y RAZÓN SOCIAL AL PERFIL DE USUARIO EN WP-ADMIN
// ==========================================
if (!function_exists('agrochamba_add_company_user_fields')) {
    function agrochamba_add_company_user_fields($user) {
        // Solo muestra estos campos si el usuario es 'employer' o es un nuevo usuario
        if (!in_array('employer', $user->roles) && $user->ID != 0) {
            return;
        }
        ?>
        <h3><?php _e('Datos de la Empresa', 'agrochamba'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="ruc">RUC</label></th>
                <td>
                    <input type="text" name="ruc" id="ruc" value="<?php echo esc_attr(get_the_author_meta('ruc', $user->ID)); ?>" class="regular-text" /><br />
                    <span class="description">Ingrese el RUC de la empresa.</span>
                </td>
            </tr>
            <tr>
                <th><label for="razon_social">Razón Social</label></th>
                <td>
                    <input type="text" name="razon_social" id="razon_social" value="<?php echo esc_attr(get_the_author_meta('razon_social', $user->ID)); ?>" class="regular-text" /><br />
                    <span class="description">Ingrese la razón social de la empresa.</span>
                </td>
            </tr>
        </table>
        <?php
    }
    add_action('show_user_profile', 'agrochamba_add_company_user_fields');
    add_action('edit_user_profile', 'agrochamba_add_company_user_fields');
    add_action('user_new_form', 'agrochamba_add_company_user_fields');
}

// ==========================================
// 4. GUARDAR CAMPOS RUC Y RAZÓN SOCIAL
// ==========================================
if (!function_exists('agrochamba_save_company_user_fields')) {
    function agrochamba_save_company_user_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        if (isset($_POST['ruc'])) {
            update_user_meta($user_id, 'ruc', sanitize_text_field($_POST['ruc']));
        }
        if (isset($_POST['razon_social'])) {
            update_user_meta($user_id, 'razon_social', sanitize_text_field($_POST['razon_social']));
        }
    }
    add_action('personal_options_update', 'agrochamba_save_company_user_fields');
    add_action('edit_user_profile_update', 'agrochamba_save_company_user_fields');
    add_action('user_register', 'agrochamba_save_company_user_fields');
}

