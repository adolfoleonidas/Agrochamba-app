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
    function agrochamba_add_user_roles_to_jwt_response($data, $user)
    {
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
// Asegura que los roles employer, administrator y editor puedan subir archivos
// Esto es importante para la app móvil y subidas mediante REST API
// NOTA: Esta función solo AGREGA permisos, nunca los quita
if (!function_exists('agrochamba_allow_media_uploads_for_roles')) {
    function agrochamba_allow_media_uploads_for_roles($allcaps, $cap, $args)
    {
        // Verificar si se está solicitando la capacidad de subir archivos
        if (isset($cap[0]) && $cap[0] === 'upload_files') {
            // IMPORTANTE: Si el usuario YA tiene el permiso, devolver inmediatamente
            // Esto previene cualquier interferencia con permisos existentes de WordPress
            if (!empty($allcaps['upload_files'])) {
                return $allcaps;
            }

            // Obtener el user_id de los argumentos
            // IMPORTANTE: En el filtro 'user_has_cap', $args[1] siempre contiene el user_id
            // que se está verificando. NO usar get_current_user_id() como fallback porque
            // puede verificar permisos para el usuario incorrecto cuando WordPress verifica
            // capacidades para un usuario específico diferente del actual.
            if (!isset($args[1]) || empty($args[1])) {
                // Si no hay user_id en los args, no podemos verificar permisos de forma segura
                return $allcaps;
            }
            
            $user_id = intval($args[1]);
            
            $user = get_userdata($user_id);
            
            // Verificar que el usuario existe y tiene roles
            if (!$user || empty($user->roles)) {
                return $allcaps;
            }
            
            $allowed_roles = array('employer', 'administrator', 'editor');
            $user_roles = (array)$user->roles;

            // Solo AGREGAR la capacidad a roles específicos si no la tienen
            // Esto asegura que nunca quitamos permisos, solo los agregamos
            if (array_intersect($allowed_roles, $user_roles)) {
                $allcaps['upload_files'] = true;
            }
        }

        return $allcaps;
    }
    // Usar prioridad 20 para que se ejecute después de otros filtros de permisos
    // pero antes de que WordPress haga verificaciones finales
    add_filter('user_has_cap', 'agrochamba_allow_media_uploads_for_roles', 20, 3);
}

// ==========================================
// DIAGNÓSTICO: Verificar permisos de subida (temporal)
// ==========================================
// Esta función ayuda a diagnosticar problemas de permisos
// Puedes eliminarla después de resolver el problema
if (!function_exists('agrochamba_debug_upload_permissions') && defined('WP_DEBUG') && WP_DEBUG) {
    function agrochamba_debug_upload_permissions() {
        if (is_admin() && current_user_can('manage_options')) {
            $user = wp_get_current_user();
            $can_upload = current_user_can('upload_files');
            $roles = implode(', ', $user->roles);
            
            error_log(sprintf(
                '[AgroChamba Debug] Usuario: %s (ID: %d) | Roles: %s | Puede subir: %s',
                $user->user_login,
                $user->ID,
                $roles,
                $can_upload ? 'SÍ' : 'NO'
            ));
        }
    }
    add_action('admin_init', 'agrochamba_debug_upload_permissions');
}

// Permitir subida de archivos también mediante REST API
// TEMPORALMENTE DESACTIVADO PARA DIAGNÓSTICO
/*
if (!function_exists('agrochamba_rest_allow_upload')) {
    function agrochamba_rest_allow_upload($result, $server, $request)
    {
        // IMPORTANTE: Este hook debe devolver una respuesta solo si quiere
        // interceptar por completo la petición. Devolver "true" aquí rompe
        // la respuesta esperada de /wp/v2/media (WordPress espera un objeto JSON del adjunto).
        //
        // No necesitamos forzar nada aquí porque ya otorgamos la capacidad
        // 'upload_files' a los roles permitidos mediante el filtro
        // 'user_has_cap' de más arriba.

        // Por lo tanto, simplemente no interferimos con la petición.
        return $result; // dejar que WordPress maneje normalmente la subida
    }
    // Mantenemos el hook para futura compatibilidad, pero sin alterar el flujo
    add_filter('rest_pre_dispatch', 'agrochamba_rest_allow_upload', 10, 3);
}
*/

// ==========================================
// 4. VALIDAR TAMAÑO DE ARCHIVOS EN UPLOADS
// ==========================================
// Valida el tamaño de archivos para prevenir subidas excesivamente grandes
// Solo aplica a imágenes subidas mediante la API de AgroChamba, NO a subidas del admin de WordPress
// TEMPORALMENTE DESACTIVADO para diagnosticar problemas de subida
// Si necesitas reactivarlo, asegúrate de que solo se aplique a subidas de la API, no al admin
/*
if (!function_exists('agrochamba_validate_upload_size')) {
    function agrochamba_validate_upload_size($file)
    {
        // Si ya hay un error, no hacer nada adicional
        if (!empty($file['error'])) {
            return $file;
        }

        // NO aplicar validación a subidas desde el admin de WordPress
        // Solo aplicar a subidas mediante REST API de AgroChamba
        if (!defined('REST_REQUEST') || !REST_REQUEST) {
            return $file; // Dejar que WordPress maneje subidas del admin normalmente
        }

        // Verificar si es una petición de nuestra API
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, '/wp-json/agrochamba/v1/') === false) {
            return $file; // No es nuestra API, dejar que WordPress maneje
        }

        // Validar que el archivo tenga los datos necesarios
        if (empty($file['type']) || empty($file['size'])) {
            return $file; // Dejar que WordPress maneje la validación estándar
        }

        // Solo validar imágenes
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        
        // Verificar si es una imagen por tipo MIME o por extensión
        $is_image = in_array($file['type'], $allowed_types, true);
        
        // Si no es una imagen según el tipo MIME, verificar por extensión como respaldo
        if (!$is_image && !empty($file['name'])) {
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            $is_image = in_array($file_ext, $image_extensions, true);
        }

        // Solo aplicar validación de tamaño a imágenes
        if (!$is_image) {
            return $file; // Dejar que WordPress maneje otros tipos
        }

        // Tamaño máximo configurable (por defecto 5MB)
        $max_file_size = apply_filters('agrochamba_max_upload_size', 5 * 1024 * 1024); // 5MB

        // Validar tamaño solo si es mayor que 0
        if ($file['size'] > 0 && $file['size'] > $max_file_size) {
            $max_size_mb = round($max_file_size / (1024 * 1024), 1);
            $file['error'] = sprintf('El archivo es demasiado grande. El tamaño máximo permitido es %s MB.', $max_size_mb);
        }

        return $file;
    }
    add_filter('wp_handle_upload_prefilter', 'agrochamba_validate_upload_size', 10, 1);
}
*/

// ==========================================
// 3. AÑADIR CAMPOS RUC Y RAZÓN SOCIAL AL PERFIL DE USUARIO EN WP-ADMIN
// ==========================================
if (!function_exists('agrochamba_add_company_user_fields')) {
    function agrochamba_add_company_user_fields($user_or_form_type)
    {
        // En 'show_user_profile' y 'edit_user_profile' se recibe un objeto WP_User.
        // En 'user_new_form' se recibe un string con el tipo de formulario (por ej. 'add-new-user').
        $user = ($user_or_form_type instanceof WP_User) ? $user_or_form_type : null;

        $user_id = $user && isset($user->ID) ? (int) $user->ID : 0;
        $roles = $user && isset($user->roles) ? (array) $user->roles : array();

        // Solo muestra estos campos si el usuario es 'employer' o si es un nuevo usuario (ID = 0)
        if ($user && !in_array('employer', $roles, true) && $user_id !== 0) {
            return;
        }
        ?>
        <h3><?php _e('Datos de la Empresa', 'agrochamba'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="ruc">RUC</label></th>
                <td>
                    <input type="text" name="ruc" id="ruc"
                        value="<?php echo esc_attr($user ? get_the_author_meta('ruc', $user->ID) : ''); ?>"
                        class="regular-text" /><br />
                    <span class="description">Ingrese el RUC de la empresa.</span>
                </td>
            </tr>
            <tr>
                <th><label for="razon_social">Razón Social</label></th>
                <td>
                    <input type="text" name="razon_social" id="razon_social"
                        value="<?php echo esc_attr($user ? get_the_author_meta('razon_social', $user->ID) : ''); ?>"
                        class="regular-text" /><br />
                    <span class="description">Ingrese la razón social de la empresa.</span>
                </td>
            </tr>
        </table>
        <?php
    }
    add_action('show_user_profile', 'agrochamba_add_company_user_fields');
    add_action('edit_user_profile', 'agrochamba_add_company_user_fields');
    // En el formulario de nuevo usuario, WordPress pasa un string, por lo que la función maneja ambos casos.
    add_action('user_new_form', 'agrochamba_add_company_user_fields');
}

// ==========================================
// 4. GUARDAR CAMPOS RUC Y RAZÓN SOCIAL
// ==========================================
if (!function_exists('agrochamba_save_company_user_fields')) {
    function agrochamba_save_company_user_fields($user_id)
    {
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

