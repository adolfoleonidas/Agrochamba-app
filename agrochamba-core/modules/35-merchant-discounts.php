<?php
/**
 * =============================================================
 * MODULO 35: DESCUENTOS CON COMERCIOS ALIADOS
 * =============================================================
 *
 * Sistema de descuentos estilo Yape donde los usuarios de Agrochamba
 * obtienen descuentos en comercios aliados mostrando su QR.
 *
 * ENDPOINTS:
 * - GET  /agrochamba/v1/discounts              - Listar descuentos disponibles
 * - GET  /agrochamba/v1/discounts/{id}         - Detalle de un descuento
 * - POST /agrochamba/v1/discounts/{id}/validate - Validar usuario para descuento (comercio escanea QR)
 * - POST /agrochamba/v1/discounts/{id}/redeem   - Confirmar canje del descuento
 * - GET  /agrochamba/v1/discounts/my-redemptions - Historial de canjes del usuario
 *
 * DATOS:
 * - Descuentos se almacenan como opciones de WordPress (agrochamba_discounts)
 * - Canjes se almacenan en user_meta (agrochamba_redemptions)
 *
 * FLUJO:
 * 1. Usuario abre la app y ve los descuentos disponibles
 * 2. Va al comercio y muestra su QR (agrochamba://discount/{dni})
 * 3. Comercio escanea el QR con su app Agrochamba
 * 4. La app del comercio llama a /validate para verificar el usuario
 * 5. Si es valido, el comercio confirma con /redeem
 * 6. Se registra el canje y ambas partes ven la confirmacion
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// CREAR TABLA DE DESCUENTOS (si no existe)
// ==========================================
if (!function_exists('agrochamba_create_discounts_table')) {
    function agrochamba_create_discounts_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'agrochamba_discounts';
        $redemptions_table = $wpdb->prefix . 'agrochamba_redemptions';
        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de descuentos
        $sql_discounts = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            merchant_name varchar(255) NOT NULL,
            merchant_logo varchar(500) DEFAULT NULL,
            merchant_address varchar(500) DEFAULT NULL,
            merchant_phone varchar(50) DEFAULT NULL,
            category varchar(50) NOT NULL DEFAULT 'store',
            title varchar(255) NOT NULL,
            description text NOT NULL,
            discount_percentage int(3) NOT NULL DEFAULT 0,
            discount_type varchar(20) NOT NULL DEFAULT 'percentage',
            discount_value varchar(50) DEFAULT NULL,
            conditions text DEFAULT NULL,
            valid_from date DEFAULT NULL,
            valid_until date DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            max_uses_per_user int(5) NOT NULL DEFAULT 1,
            image_url varchar(500) DEFAULT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_category (category),
            KEY idx_is_active (is_active),
            KEY idx_valid_until (valid_until)
        ) $charset_collate;";

        // Tabla de canjes/redemptions
        $sql_redemptions = "CREATE TABLE IF NOT EXISTS $redemptions_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            discount_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            user_dni varchar(20) DEFAULT NULL,
            redeemed_by bigint(20) unsigned NOT NULL,
            redeemed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_discount_id (discount_id),
            KEY idx_user_id (user_id),
            KEY idx_user_dni (user_dni),
            KEY idx_redeemed_by (redeemed_by)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_discounts);
        dbDelta($sql_redemptions);
    }
    add_action('admin_init', 'agrochamba_create_discounts_table');
}

// ==========================================
// SEED: DESCUENTOS DE EJEMPLO (solo en primera ejecucion)
// ==========================================
if (!function_exists('agrochamba_seed_sample_discounts')) {
    function agrochamba_seed_sample_discounts() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'agrochamba_discounts';

        // Verificar si ya hay descuentos
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }

        $sample_discounts = array(
            array(
                'merchant_name' => 'Restaurante El Buen Sabor',
                'category' => 'restaurant',
                'title' => '15% en almuerzos',
                'description' => 'Descuento del 15% en todos los almuerzos de lunes a viernes',
                'discount_percentage' => 15,
                'discount_type' => 'percentage',
                'conditions' => 'Valido solo de lunes a viernes en horario de almuerzo (12:00 - 15:00). No acumulable con otras promociones.',
                'max_uses_per_user' => 5,
                'is_active' => 1,
            ),
            array(
                'merchant_name' => 'Hotel Campo Verde',
                'category' => 'hotel',
                'title' => '20% en hospedaje',
                'description' => 'Descuento del 20% en habitaciones simples y dobles',
                'discount_percentage' => 20,
                'discount_type' => 'percentage',
                'conditions' => 'Sujeto a disponibilidad. Reservar con 24 horas de anticipacion.',
                'max_uses_per_user' => 3,
                'is_active' => 1,
            ),
            array(
                'merchant_name' => 'Tienda AgroSupply',
                'category' => 'store',
                'title' => '10% en herramientas',
                'description' => 'Descuento del 10% en herramientas agricolas y EPP',
                'discount_percentage' => 10,
                'discount_type' => 'percentage',
                'conditions' => 'Aplica en compras mayores a S/ 50. No incluye productos en oferta.',
                'max_uses_per_user' => 10,
                'is_active' => 1,
            ),
            array(
                'merchant_name' => 'Farmacia SaludAgro',
                'category' => 'health',
                'title' => '2x1 en protector solar',
                'description' => 'Lleva 2 protectores solares por el precio de 1',
                'discount_percentage' => 50,
                'discount_type' => '2x1',
                'conditions' => 'Solo en protectores solares SPF 50+. Stock limitado.',
                'max_uses_per_user' => 2,
                'is_active' => 1,
            ),
            array(
                'merchant_name' => 'Transportes RutaVerde',
                'category' => 'transport',
                'title' => 'S/ 5 de descuento',
                'description' => 'S/ 5 de descuento en pasajes interurbanos',
                'discount_percentage' => 0,
                'discount_type' => 'fixed',
                'discount_value' => 'S/ 5',
                'conditions' => 'Valido en rutas interurbanas. Un uso por viaje.',
                'max_uses_per_user' => 10,
                'is_active' => 1,
            ),
        );

        foreach ($sample_discounts as $discount) {
            $wpdb->insert($table_name, $discount);
        }
    }
    add_action('admin_init', 'agrochamba_seed_sample_discounts', 20);
}

// ==========================================
// HELPER: Obtener label de categoria
// ==========================================
if (!function_exists('agrochamba_discount_category_label')) {
    function agrochamba_discount_category_label($category) {
        $labels = array(
            'restaurant' => 'Restaurantes',
            'hotel' => 'Hoteles',
            'store' => 'Tiendas',
            'transport' => 'Transporte',
            'health' => 'Salud',
            'entertainment' => 'Entretenimiento',
        );
        return isset($labels[$category]) ? $labels[$category] : ucfirst($category);
    }
}

// ==========================================
// HELPER: Formatear descuento para respuesta API
// ==========================================
if (!function_exists('agrochamba_format_discount')) {
    function agrochamba_format_discount($discount) {
        global $wpdb;
        $redemptions_table = $wpdb->prefix . 'agrochamba_redemptions';

        $times_redeemed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $redemptions_table WHERE discount_id = %d",
            $discount->id
        ));

        return array(
            'id' => intval($discount->id),
            'merchant_name' => $discount->merchant_name,
            'merchant_logo' => $discount->merchant_logo,
            'merchant_address' => $discount->merchant_address,
            'merchant_phone' => $discount->merchant_phone,
            'category' => $discount->category,
            'category_label' => agrochamba_discount_category_label($discount->category),
            'title' => $discount->title,
            'description' => $discount->description,
            'discount_percentage' => intval($discount->discount_percentage),
            'discount_type' => $discount->discount_type,
            'discount_value' => $discount->discount_value,
            'conditions' => $discount->conditions,
            'valid_from' => $discount->valid_from,
            'valid_until' => $discount->valid_until,
            'is_active' => (bool) $discount->is_active,
            'max_uses_per_user' => intval($discount->max_uses_per_user),
            'times_redeemed' => intval($times_redeemed),
            'image_url' => $discount->image_url,
        );
    }
}

// ==========================================
// 1. LISTAR DESCUENTOS DISPONIBLES
// ==========================================
if (!function_exists('agrochamba_get_discounts')) {
    function agrochamba_get_discounts($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesion.', array('status' => 401));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'agrochamba_discounts';

        $category = $request->get_param('category');
        $page = max(1, intval($request->get_param('page') ?: 1));
        $per_page = min(50, max(1, intval($request->get_param('per_page') ?: 20)));
        $offset = ($page - 1) * $per_page;

        $where = "WHERE is_active = 1 AND (valid_until IS NULL OR valid_until >= CURDATE())";
        $params = array();

        if (!empty($category) && $category !== 'all') {
            $where .= " AND category = %s";
            $params[] = sanitize_text_field($category);
        }

        // Contar total
        $count_sql = "SELECT COUNT(*) FROM $table_name $where";
        if (!empty($params)) {
            $total = $wpdb->get_var($wpdb->prepare($count_sql, $params));
        } else {
            $total = $wpdb->get_var($count_sql);
        }

        // Obtener descuentos
        $query = "SELECT * FROM $table_name $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
        $discounts = $wpdb->get_results($wpdb->prepare($query, $params));

        $data = array();
        foreach ($discounts as $discount) {
            $data[] = agrochamba_format_discount($discount);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $data,
            'total' => intval($total),
        ), 200);
    }
}

// ==========================================
// 2. DETALLE DE UN DESCUENTO
// ==========================================
if (!function_exists('agrochamba_get_discount_detail')) {
    function agrochamba_get_discount_detail($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesion.', array('status' => 401));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'agrochamba_discounts';
        $discount_id = intval($request->get_param('id'));

        $discount = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND is_active = 1",
            $discount_id
        ));

        if (!$discount) {
            return new WP_Error('not_found', 'Descuento no encontrado.', array('status' => 404));
        }

        return new WP_REST_Response(agrochamba_format_discount($discount), 200);
    }
}

// ==========================================
// 3. VALIDAR USUARIO PARA DESCUENTO
// ==========================================
if (!function_exists('agrochamba_validate_discount')) {
    function agrochamba_validate_discount($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesion.', array('status' => 401));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'agrochamba_discounts';
        $redemptions_table = $wpdb->prefix . 'agrochamba_redemptions';

        $discount_id = intval($request->get_param('id'));
        $params = $request->get_json_params();
        $user_dni = isset($params['user_dni']) ? sanitize_text_field($params['user_dni']) : '';

        if (empty($user_dni)) {
            return new WP_Error('invalid_dni', 'DNI del usuario requerido.', array('status' => 400));
        }

        // Verificar que el descuento existe y esta activo
        $discount = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND is_active = 1",
            $discount_id
        ));

        if (!$discount) {
            return new WP_REST_Response(array(
                'success' => true,
                'can_redeem' => false,
                'message' => 'Descuento no encontrado o inactivo.',
            ), 200);
        }

        // Verificar validez temporal
        if ($discount->valid_until && strtotime($discount->valid_until) < time()) {
            return new WP_REST_Response(array(
                'success' => true,
                'can_redeem' => false,
                'message' => 'Este descuento ha expirado.',
            ), 200);
        }

        // Buscar usuario por DNI
        $users = get_users(array(
            'meta_key' => 'dni',
            'meta_value' => $user_dni,
            'number' => 1,
        ));

        if (empty($users)) {
            return new WP_REST_Response(array(
                'success' => true,
                'can_redeem' => false,
                'message' => 'No se encontro un usuario con este DNI en Agrochamba.',
            ), 200);
        }

        $user = $users[0];

        // Contar cuantas veces ya ha canjeado este descuento
        $times_used = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $redemptions_table WHERE discount_id = %d AND user_id = %d",
            $discount_id,
            $user->ID
        ));

        $can_redeem = intval($times_used) < intval($discount->max_uses_per_user);

        // Obtener foto de perfil
        $user_photo = get_user_meta($user->ID, 'profile_photo_url', true);

        return new WP_REST_Response(array(
            'success' => true,
            'can_redeem' => $can_redeem,
            'user_name' => $user->display_name,
            'user_dni' => $user_dni,
            'user_photo' => $user_photo ?: null,
            'message' => $can_redeem ? 'Usuario valido para canjear este descuento.' : 'Este usuario ya ha usado el maximo de canjes para este descuento.',
            'times_used' => intval($times_used),
            'max_uses' => intval($discount->max_uses_per_user),
        ), 200);
    }
}

// ==========================================
// 4. CANJEAR/REDIMIR DESCUENTO
// ==========================================
if (!function_exists('agrochamba_redeem_discount')) {
    function agrochamba_redeem_discount($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesion.', array('status' => 401));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'agrochamba_discounts';
        $redemptions_table = $wpdb->prefix . 'agrochamba_redemptions';

        $discount_id = intval($request->get_param('id'));
        $params = $request->get_json_params();
        $user_dni = isset($params['user_dni']) ? sanitize_text_field($params['user_dni']) : '';
        $redeemed_by = get_current_user_id();

        if (empty($user_dni)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'DNI del usuario requerido.',
            ), 200);
        }

        // Verificar que el descuento existe y esta activo
        $discount = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND is_active = 1",
            $discount_id
        ));

        if (!$discount) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Descuento no encontrado o inactivo.',
            ), 200);
        }

        // Buscar usuario por DNI
        $users = get_users(array(
            'meta_key' => 'dni',
            'meta_value' => $user_dni,
            'number' => 1,
        ));

        if (empty($users)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ), 200);
        }

        $user = $users[0];

        // Verificar limite de canjes
        $times_used = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $redemptions_table WHERE discount_id = %d AND user_id = %d",
            $discount_id,
            $user->ID
        ));

        if (intval($times_used) >= intval($discount->max_uses_per_user)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Este usuario ya alcanzo el limite de canjes para este descuento.',
            ), 200);
        }

        // Registrar el canje
        $inserted = $wpdb->insert($redemptions_table, array(
            'discount_id' => $discount_id,
            'user_id' => $user->ID,
            'user_dni' => $user_dni,
            'redeemed_by' => $redeemed_by,
            'redeemed_at' => current_time('mysql'),
        ));

        if (!$inserted) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Error al registrar el canje.',
            ), 200);
        }

        $redemption_id = $wpdb->insert_id;

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Descuento canjeado exitosamente.',
            'data' => array(
                'redemption_id' => $redemption_id,
                'discount_id' => $discount_id,
                'user_id' => $user->ID,
                'user_name' => $user->display_name,
                'user_dni' => $user_dni,
                'discount_title' => $discount->title,
                'merchant_name' => $discount->merchant_name,
                'redeemed_at' => current_time('mysql'),
            ),
        ), 200);
    }
}

// ==========================================
// 5. HISTORIAL DE CANJES DEL USUARIO
// ==========================================
if (!function_exists('agrochamba_get_my_redemptions')) {
    function agrochamba_get_my_redemptions($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Debes iniciar sesion.', array('status' => 401));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'agrochamba_discounts';
        $redemptions_table = $wpdb->prefix . 'agrochamba_redemptions';

        $user_id = get_current_user_id();

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT r.id, r.discount_id, r.redeemed_at,
                    d.title as discount_title, d.merchant_name, d.merchant_logo,
                    d.discount_percentage, d.discount_type, d.discount_value
             FROM $redemptions_table r
             JOIN $table_name d ON r.discount_id = d.id
             WHERE r.user_id = %d
             ORDER BY r.redeemed_at DESC
             LIMIT 50",
            $user_id
        ));

        $data = array();
        foreach ($results as $row) {
            $data[] = array(
                'id' => intval($row->id),
                'discount_id' => intval($row->discount_id),
                'discount_title' => $row->discount_title,
                'merchant_name' => $row->merchant_name,
                'merchant_logo' => $row->merchant_logo,
                'discount_percentage' => intval($row->discount_percentage),
                'redeemed_at' => $row->redeemed_at,
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $data,
            'total' => count($data),
        ), 200);
    }
}

// ==========================================
// REGISTRAR ENDPOINTS REST API
// ==========================================
add_action('rest_api_init', function () {
    $routes = rest_get_server()->get_routes();

    // Listar descuentos
    if (!isset($routes['/agrochamba/v1/discounts'])) {
        register_rest_route('agrochamba/v1', '/discounts', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_discounts',
            'permission_callback' => '__return_true',
            'args' => array(
                'category' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'page' => array(
                    'required' => false,
                    'default' => 1,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                ),
                'per_page' => array(
                    'required' => false,
                    'default' => 20,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));
    }

    // Detalle de descuento
    if (!isset($routes['/agrochamba/v1/discounts/(?P<id>\\d+)'])) {
        register_rest_route('agrochamba/v1', '/discounts/(?P<id>\\d+)', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_discount_detail',
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));
    }

    // Validar usuario para descuento
    if (!isset($routes['/agrochamba/v1/discounts/(?P<id>\\d+)/validate'])) {
        register_rest_route('agrochamba/v1', '/discounts/(?P<id>\\d+)/validate', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_validate_discount',
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));
    }

    // Canjear descuento
    if (!isset($routes['/agrochamba/v1/discounts/(?P<id>\\d+)/redeem'])) {
        register_rest_route('agrochamba/v1', '/discounts/(?P<id>\\d+)/redeem', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_redeem_discount',
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));
    }

    // Historial de canjes del usuario
    if (!isset($routes['/agrochamba/v1/discounts/my-redemptions'])) {
        register_rest_route('agrochamba/v1', '/discounts/my-redemptions', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_my_redemptions',
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ));
    }
}, 20);

// ==========================================
// PAGINA DE VERIFICACION PUBLICA
// ==========================================
// Cuando alguien escanea el QR con cualquier lector,
// se abre: https://agrochamba.com/verificar/72345678
// y ve una pagina con los datos basicos del trabajador.

// Registrar rewrite rule: /verificar/{dni}
if (!function_exists('agrochamba_verificar_rewrite_rules')) {
    function agrochamba_verificar_rewrite_rules() {
        add_rewrite_rule(
            '^verificar/([0-9]{8,12})/?$',
            'index.php?agrochamba_verificar_dni=$matches[1]',
            'top'
        );
    }
    add_action('init', 'agrochamba_verificar_rewrite_rules');
}

// Registrar query var
if (!function_exists('agrochamba_verificar_query_vars')) {
    function agrochamba_verificar_query_vars($vars) {
        $vars[] = 'agrochamba_verificar_dni';
        return $vars;
    }
    add_filter('query_vars', 'agrochamba_verificar_query_vars');
}

// Renderizar la pagina de verificacion
if (!function_exists('agrochamba_verificar_template')) {
    function agrochamba_verificar_template($template) {
        $dni = get_query_var('agrochamba_verificar_dni');
        if (empty($dni)) {
            return $template;
        }

        $dni = sanitize_text_field($dni);

        // Buscar usuario por DNI
        $users = get_users(array(
            'meta_key' => 'dni',
            'meta_value' => $dni,
            'number' => 1,
        ));

        $found = !empty($users);
        $user = $found ? $users[0] : null;
        $display_name = $found ? esc_html($user->display_name) : '';
        $profile_photo = $found ? esc_url(get_user_meta($user->ID, 'profile_photo_url', true)) : '';
        $member_since = $found ? date('Y', strtotime($user->user_registered)) : '';

        // No exponer datos sensibles; solo confirmar que es usuario verificado
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Usuario - AgroChamba</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0fdf4; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); max-width: 400px; width: 100%; overflow: hidden; }
        .header { background: linear-gradient(135deg, #166534, #16a34a); padding: 24px; text-align: center; color: white; }
        .header h1 { font-size: 14px; letter-spacing: 3px; opacity: 0.8; margin-bottom: 4px; }
        .header h2 { font-size: 20px; }
        .body { padding: 32px 24px; text-align: center; }
        .avatar { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 16px; background: #dcfce7; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 3px solid #16a34a; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar .initials { font-size: 32px; color: #16a34a; font-weight: bold; }
        .badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; margin: 12px 0; }
        .badge.verified { background: #dcfce7; color: #166534; }
        .badge.not-found { background: #fef2f2; color: #991b1b; }
        .name { font-size: 22px; font-weight: 700; color: #1a1a1a; margin: 8px 0 4px; }
        .dni { font-size: 16px; color: #666; letter-spacing: 2px; }
        .info { font-size: 13px; color: #999; margin-top: 16px; }
        .footer { padding: 16px; text-align: center; border-top: 1px solid #f0f0f0; }
        .footer a { color: #16a34a; text-decoration: none; font-weight: 600; font-size: 14px; }
        .check-icon { font-size: 24px; margin-right: 6px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>AGROCHAMBA</h1>
            <h2>Verificacion de Usuario</h2>
        </div>
        <div class="body">';

        if ($found) {
            $initials = mb_strtoupper(mb_substr($display_name, 0, 1));
            echo '<div class="avatar">';
            if (!empty($profile_photo)) {
                echo '<img src="' . $profile_photo . '" alt="Foto">';
            } else {
                echo '<span class="initials">' . $initials . '</span>';
            }
            echo '</div>';
            echo '<span class="badge verified"><span class="check-icon">&#10003;</span> Usuario Verificado</span>';
            echo '<div class="name">' . $display_name . '</div>';
            echo '<div class="dni">DNI: ' . esc_html($dni) . '</div>';
            echo '<div class="info">Miembro de AgroChamba desde ' . $member_since . '</div>';
        } else {
            echo '<div class="avatar"><span class="initials">?</span></div>';
            echo '<span class="badge not-found">Usuario no encontrado</span>';
            echo '<div class="dni">DNI: ' . esc_html($dni) . '</div>';
            echo '<div class="info">Este DNI no esta registrado en AgroChamba</div>';
        }

        echo '
        </div>
        <div class="footer">
            <a href="https://agrochamba.com">agrochamba.com</a>
        </div>
    </div>
</body>
</html>';
        exit;
    }
    add_action('template_redirect', 'agrochamba_verificar_template');
}

// Flush rewrite rules al activar (necesario para que /verificar/ funcione)
if (!function_exists('agrochamba_discounts_flush_rewrite')) {
    function agrochamba_discounts_flush_rewrite() {
        agrochamba_verificar_rewrite_rules();
        flush_rewrite_rules();
    }
    register_activation_hook(AGROCHAMBA_PLUGIN_DIR . '/agrochamba-core.php', 'agrochamba_discounts_flush_rewrite');
}
