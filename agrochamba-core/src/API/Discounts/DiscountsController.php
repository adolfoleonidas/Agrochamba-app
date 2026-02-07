<?php
/**
 * Controlador de Descuentos con Comercios Aliados
 *
 * Sistema estilo Yape para que usuarios de Agrochamba obtengan
 * descuentos en comercios aliados mostrando su QR.
 *
 * @package AgroChamba
 * @subpackage API\Discounts
 * @since 2.1.0
 */

namespace AgroChamba\API\Discounts;

use WP_Error;
use WP_REST_Response;
use WP_REST_Request;

class DiscountsController {

    const API_NAMESPACE = 'agrochamba/v1';

    /**
     * Inicializar el controlador
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'), 20);
        add_action('admin_init', array(__CLASS__, 'ensure_tables_exist'));
    }

    /**
     * Crear tablas si no existen
     */
    public static function ensure_tables_exist() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'agrochamba_discounts';
        $redemptions_table = $wpdb->prefix . 'agrochamba_redemptions';
        $charset_collate = $wpdb->get_charset_collate();

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

    /**
     * Registrar rutas REST API
     */
    public static function register_routes() {
        $routes = rest_get_server()->get_routes();

        if (!isset($routes['/' . self::API_NAMESPACE . '/discounts'])) {
            register_rest_route(self::API_NAMESPACE, '/discounts', array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_discounts'),
                'permission_callback' => '__return_true',
            ));
        }

        if (!isset($routes['/' . self::API_NAMESPACE . '/discounts/(?P<id>\\d+)'])) {
            register_rest_route(self::API_NAMESPACE, '/discounts/(?P<id>\\d+)', array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_discount_detail'),
                'permission_callback' => '__return_true',
            ));
        }

        if (!isset($routes['/' . self::API_NAMESPACE . '/discounts/(?P<id>\\d+)/validate'])) {
            register_rest_route(self::API_NAMESPACE, '/discounts/(?P<id>\\d+)/validate', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'validate_discount'),
                'permission_callback' => '__return_true',
            ));
        }

        if (!isset($routes['/' . self::API_NAMESPACE . '/discounts/(?P<id>\\d+)/redeem'])) {
            register_rest_route(self::API_NAMESPACE, '/discounts/(?P<id>\\d+)/redeem', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'redeem_discount'),
                'permission_callback' => '__return_true',
            ));
        }

        if (!isset($routes['/' . self::API_NAMESPACE . '/discounts/my-redemptions'])) {
            register_rest_route(self::API_NAMESPACE, '/discounts/my-redemptions', array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_my_redemptions'),
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            ));
        }
    }

    /**
     * Listar descuentos disponibles
     */
    public static function get_discounts($request) {
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

        $count_sql = "SELECT COUNT(*) FROM $table_name $where";
        $total = !empty($params)
            ? $wpdb->get_var($wpdb->prepare($count_sql, $params))
            : $wpdb->get_var($count_sql);

        $query = "SELECT * FROM $table_name $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
        $discounts = $wpdb->get_results($wpdb->prepare($query, $params));

        $data = array_map(array(__CLASS__, 'format_discount'), $discounts);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $data,
            'total' => intval($total),
        ), 200);
    }

    /**
     * Detalle de un descuento
     */
    public static function get_discount_detail($request) {
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

        return new WP_REST_Response(self::format_discount($discount), 200);
    }

    /**
     * Validar usuario para un descuento (comercio escanea QR)
     */
    public static function validate_discount($request) {
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

        $times_used = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $redemptions_table WHERE discount_id = %d AND user_id = %d",
            $discount_id,
            $user->ID
        ));

        $can_redeem = intval($times_used) < intval($discount->max_uses_per_user);
        $user_photo = get_user_meta($user->ID, 'profile_photo_url', true);

        return new WP_REST_Response(array(
            'success' => true,
            'can_redeem' => $can_redeem,
            'user_name' => $user->display_name,
            'user_dni' => $user_dni,
            'user_photo' => $user_photo ?: null,
            'message' => $can_redeem
                ? 'Usuario valido para canjear este descuento.'
                : 'Este usuario ya ha usado el maximo de canjes.',
            'times_used' => intval($times_used),
            'max_uses' => intval($discount->max_uses_per_user),
        ), 200);
    }

    /**
     * Canjear/confirmar descuento
     */
    public static function redeem_discount($request) {
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
            return new WP_REST_Response(array('success' => false, 'message' => 'DNI requerido.'), 200);
        }

        $discount = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND is_active = 1",
            $discount_id
        ));

        if (!$discount) {
            return new WP_REST_Response(array('success' => false, 'message' => 'Descuento no encontrado.'), 200);
        }

        $users = get_users(array('meta_key' => 'dni', 'meta_value' => $user_dni, 'number' => 1));
        if (empty($users)) {
            return new WP_REST_Response(array('success' => false, 'message' => 'Usuario no encontrado.'), 200);
        }

        $user = $users[0];

        $times_used = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $redemptions_table WHERE discount_id = %d AND user_id = %d",
            $discount_id, $user->ID
        ));

        if (intval($times_used) >= intval($discount->max_uses_per_user)) {
            return new WP_REST_Response(array('success' => false, 'message' => 'Limite de canjes alcanzado.'), 200);
        }

        $inserted = $wpdb->insert($redemptions_table, array(
            'discount_id' => $discount_id,
            'user_id' => $user->ID,
            'user_dni' => $user_dni,
            'redeemed_by' => $redeemed_by,
            'redeemed_at' => current_time('mysql'),
        ));

        if (!$inserted) {
            return new WP_REST_Response(array('success' => false, 'message' => 'Error al registrar.'), 200);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Descuento canjeado exitosamente.',
            'data' => array(
                'redemption_id' => $wpdb->insert_id,
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

    /**
     * Historial de canjes del usuario
     */
    public static function get_my_redemptions($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'agrochamba_discounts';
        $redemptions_table = $wpdb->prefix . 'agrochamba_redemptions';
        $user_id = get_current_user_id();

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT r.id, r.discount_id, r.redeemed_at,
                    d.title as discount_title, d.merchant_name, d.merchant_logo,
                    d.discount_percentage
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

    /**
     * Formatear descuento para respuesta API
     */
    private static function format_discount($discount) {
        global $wpdb;
        $redemptions_table = $wpdb->prefix . 'agrochamba_redemptions';

        $times_redeemed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $redemptions_table WHERE discount_id = %d",
            $discount->id
        ));

        $labels = array(
            'restaurant' => 'Restaurantes',
            'hotel' => 'Hoteles',
            'store' => 'Tiendas',
            'transport' => 'Transporte',
            'health' => 'Salud',
            'entertainment' => 'Entretenimiento',
        );

        return array(
            'id' => intval($discount->id),
            'merchant_name' => $discount->merchant_name,
            'merchant_logo' => $discount->merchant_logo,
            'merchant_address' => $discount->merchant_address,
            'merchant_phone' => $discount->merchant_phone,
            'category' => $discount->category,
            'category_label' => isset($labels[$discount->category]) ? $labels[$discount->category] : ucfirst($discount->category),
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
