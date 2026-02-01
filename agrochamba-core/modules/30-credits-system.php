<?php
/**
 * =============================================================
 * MÓDULO 30: SISTEMA DE CRÉDITOS
 * =============================================================
 *
 * Sistema de créditos para monetización de la plataforma.
 *
 * Estructura de precios:
 *   Publicar trabajo       = 5 créditos
 *   IA - Mejorar texto     = 1 crédito
 *   IA - Generar título    = 1 crédito
 *   IA - OCR (imagen)      = 2 créditos
 *
 * Paquetes de créditos:
 *   10 créditos  → S/ 9.90
 *   30 créditos  → S/ 24.90
 *   60 créditos  → S/ 39.90
 *   150 créditos → S/ 79.90
 *
 * Créditos gratis:
 *   Nuevas empresas reciben 5 créditos al registrarse
 *   Administradores tienen uso ilimitado (sin créditos)
 *
 * Endpoints:
 *   GET  /agrochamba/v1/credits/balance    - Saldo actual
 *   GET  /agrochamba/v1/credits/packages   - Paquetes disponibles
 *   POST /agrochamba/v1/credits/purchase   - Comprar paquete (genera preferencia MP)
 *   GET  /agrochamba/v1/credits/history    - Historial de transacciones
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// COSTOS EN CRÉDITOS
// ==========================================

define('AGROCHAMBA_CREDIT_COST_PUBLISH_JOB', 5);
define('AGROCHAMBA_CREDIT_COST_AI_ENHANCE',  1);
define('AGROCHAMBA_CREDIT_COST_AI_TITLE',    1);
define('AGROCHAMBA_CREDIT_COST_AI_OCR',      2);
define('AGROCHAMBA_CREDIT_WELCOME_BONUS',    5);

// ==========================================
// PAQUETES DE CRÉDITOS
// ==========================================

function agrochamba_credits_get_packages() {
    return array(
        array(
            'id'          => 'pack_10',
            'credits'     => 10,
            'price'       => 9.90,
            'currency'    => 'PEN',
            'label'       => '10 créditos',
            'description' => '2 publicaciones o 10 usos de IA',
            'popular'     => false,
        ),
        array(
            'id'          => 'pack_30',
            'credits'     => 30,
            'price'       => 24.90,
            'currency'    => 'PEN',
            'label'       => '30 créditos',
            'description' => '6 publicaciones o 30 usos de IA',
            'popular'     => true,
        ),
        array(
            'id'          => 'pack_60',
            'credits'     => 60,
            'price'       => 39.90,
            'currency'    => 'PEN',
            'label'       => '60 créditos',
            'description' => '12 publicaciones o 60 usos de IA',
            'popular'     => false,
        ),
        array(
            'id'          => 'pack_150',
            'credits'     => 150,
            'price'       => 79.90,
            'currency'    => 'PEN',
            'label'       => '150 créditos',
            'description' => '30 publicaciones o 150 usos de IA',
            'popular'     => false,
        ),
    );
}

// ==========================================
// FUNCIONES DE GESTIÓN DE CRÉDITOS
// ==========================================

/**
 * Obtener saldo de créditos de un usuario.
 */
function agrochamba_credits_get_balance($user_id) {
    $balance = get_user_meta($user_id, '_agrochamba_credits', true);
    return $balance !== '' ? intval($balance) : 0;
}

/**
 * Verificar si el usuario es admin (créditos ilimitados).
 */
function agrochamba_credits_is_unlimited($user_id) {
    $user = get_userdata($user_id);
    return $user && in_array('administrator', $user->roles);
}

/**
 * Verificar si el usuario tiene suficientes créditos.
 */
function agrochamba_credits_has_enough($user_id, $amount) {
    if (agrochamba_credits_is_unlimited($user_id)) {
        return true;
    }
    return agrochamba_credits_get_balance($user_id) >= $amount;
}

/**
 * Añadir créditos a un usuario.
 */
function agrochamba_credits_add($user_id, $amount, $reason = '', $reference_id = '') {
    if ($amount <= 0) {
        return false;
    }

    $current = agrochamba_credits_get_balance($user_id);
    $new_balance = $current + $amount;
    update_user_meta($user_id, '_agrochamba_credits', $new_balance);

    // Registrar transacción
    agrochamba_credits_log_transaction($user_id, 'credit', $amount, $new_balance, $reason, $reference_id);

    return $new_balance;
}

/**
 * Descontar créditos de un usuario.
 * Retorna el nuevo saldo o false si no tiene suficientes.
 */
function agrochamba_credits_deduct($user_id, $amount, $reason = '', $reference_id = '') {
    // Admins no gastan créditos
    if (agrochamba_credits_is_unlimited($user_id)) {
        agrochamba_credits_log_transaction($user_id, 'admin_free', 0, -1, $reason, $reference_id);
        return -1; // -1 = ilimitado
    }

    if ($amount <= 0) {
        return false;
    }

    $current = agrochamba_credits_get_balance($user_id);
    if ($current < $amount) {
        return false;
    }

    $new_balance = $current - $amount;
    update_user_meta($user_id, '_agrochamba_credits', $new_balance);

    // Registrar transacción
    agrochamba_credits_log_transaction($user_id, 'debit', $amount, $new_balance, $reason, $reference_id);

    return $new_balance;
}

/**
 * Registrar una transacción de créditos.
 * Usa la tabla wp_options como almacenamiento simple (sin crear tablas custom).
 */
function agrochamba_credits_log_transaction($user_id, $type, $amount, $balance, $reason = '', $reference_id = '') {
    $transaction = array(
        'user_id'      => $user_id,
        'type'         => $type,     // credit, debit, admin_free
        'amount'       => $amount,
        'balance'      => $balance,
        'reason'       => $reason,
        'reference_id' => $reference_id,
        'date'         => current_time('mysql'),
        'timestamp'    => time(),
    );

    // Guardar en user_meta como array de transacciones (últimas 100)
    $history = get_user_meta($user_id, '_agrochamba_credit_history', true);
    if (!is_array($history)) {
        $history = array();
    }

    // Agregar al inicio y limitar a 100 registros
    array_unshift($history, $transaction);
    $history = array_slice($history, 0, 100);

    update_user_meta($user_id, '_agrochamba_credit_history', $history);

    // Log para debugging
    error_log(sprintf(
        'AgroChamba Credits: user=%d type=%s amount=%d balance=%d reason=%s',
        $user_id, $type, $amount, $balance, $reason
    ));
}

// ==========================================
// BONO DE BIENVENIDA PARA NUEVAS EMPRESAS
// ==========================================

add_action('user_register', function ($user_id) {
    // Se ejecuta después de que el usuario es creado
    // Verificar en un hook posterior cuando los roles ya estén asignados
    add_action('set_user_role', function ($set_user_id, $role) use ($user_id) {
        if ($set_user_id !== $user_id) return;
        if ($role === 'employer') {
            $already_given = get_user_meta($user_id, '_agrochamba_welcome_credits_given', true);
            if (!$already_given) {
                agrochamba_credits_add(
                    $user_id,
                    AGROCHAMBA_CREDIT_WELCOME_BONUS,
                    'Bono de bienvenida',
                    'welcome_bonus'
                );
                update_user_meta($user_id, '_agrochamba_welcome_credits_given', true);
            }
        }
    }, 10, 2);
});

// ==========================================
// ENDPOINTS REST API
// ==========================================

add_action('rest_api_init', function () {

    // Saldo actual
    register_rest_route('agrochamba/v1', '/credits/balance', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_credits_api_balance',
        'permission_callback' => function () { return is_user_logged_in(); },
    ));

    // Paquetes disponibles
    register_rest_route('agrochamba/v1', '/credits/packages', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_credits_api_packages',
        'permission_callback' => function () { return is_user_logged_in(); },
    ));

    // Comprar paquete (genera preferencia MP)
    register_rest_route('agrochamba/v1', '/credits/purchase', array(
        'methods'             => 'POST',
        'callback'            => 'agrochamba_credits_api_purchase',
        'permission_callback' => function () { return is_user_logged_in(); },
    ));

    // Historial de transacciones
    register_rest_route('agrochamba/v1', '/credits/history', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_credits_api_history',
        'permission_callback' => function () { return is_user_logged_in(); },
    ));

    // Tabla de costos (pública para info)
    register_rest_route('agrochamba/v1', '/credits/costs', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_credits_api_costs',
        'permission_callback' => function () { return is_user_logged_in(); },
    ));
});

// ==========================================
// IMPLEMENTACIÓN DE ENDPOINTS
// ==========================================

function agrochamba_credits_api_balance($request) {
    $user_id = get_current_user_id();
    $is_unlimited = agrochamba_credits_is_unlimited($user_id);

    return new WP_REST_Response(array(
        'success'     => true,
        'balance'     => $is_unlimited ? -1 : agrochamba_credits_get_balance($user_id),
        'is_unlimited' => $is_unlimited,
        'costs'       => array(
            'publish_job' => AGROCHAMBA_CREDIT_COST_PUBLISH_JOB,
            'ai_enhance'  => AGROCHAMBA_CREDIT_COST_AI_ENHANCE,
            'ai_title'    => AGROCHAMBA_CREDIT_COST_AI_TITLE,
            'ai_ocr'      => AGROCHAMBA_CREDIT_COST_AI_OCR,
        ),
    ), 200);
}

function agrochamba_credits_api_packages($request) {
    $packages = agrochamba_credits_get_packages();
    return new WP_REST_Response(array(
        'success'  => true,
        'packages' => $packages,
    ), 200);
}

function agrochamba_credits_api_purchase($request) {
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $params = $request->get_json_params();

    $package_id = isset($params['package_id']) ? sanitize_text_field($params['package_id']) : '';
    if (empty($package_id)) {
        return new WP_Error('invalid_param', 'Debes seleccionar un paquete.', array('status' => 400));
    }

    // Buscar el paquete
    $packages = agrochamba_credits_get_packages();
    $selected = null;
    foreach ($packages as $pkg) {
        if ($pkg['id'] === $package_id) {
            $selected = $pkg;
            break;
        }
    }

    if (!$selected) {
        return new WP_Error('invalid_package', 'Paquete no válido.', array('status' => 400));
    }

    // Crear preferencia de pago en Mercado Pago
    $access_token = function_exists('agrochamba_mp_get_access_token') ? agrochamba_mp_get_access_token() : '';
    if (empty($access_token)) {
        return new WP_Error('mp_not_configured', 'Sistema de pagos no configurado.', array('status' => 500));
    }

    $preference_data = array(
        'items' => array(
            array(
                'id'          => $selected['id'],
                'title'       => $selected['label'] . ' - AgroChamba',
                'description' => $selected['description'],
                'quantity'    => 1,
                'unit_price'  => $selected['price'],
                'currency_id' => 'PEN',
            ),
        ),
        'payer' => array(
            'email' => $user->user_email,
        ),
        'back_urls' => array(
            'success' => 'agrochamba://credits/success?package_id=' . $selected['id'],
            'failure' => 'agrochamba://credits/failure?package_id=' . $selected['id'],
            'pending' => 'agrochamba://credits/pending?package_id=' . $selected['id'],
        ),
        'auto_return'          => 'approved',
        'external_reference'   => 'credits_' . $selected['id'] . '_user_' . $user_id . '_' . time(),
        'notification_url'     => rest_url('agrochamba/v1/payments/webhook'),
        'statement_descriptor' => 'AgroChamba',
        'metadata' => array(
            'type'       => 'credits',
            'package_id' => $selected['id'],
            'credits'    => $selected['credits'],
            'user_id'    => $user_id,
        ),
    );

    $response = wp_remote_post('https://api.mercadopago.com/checkout/preferences', array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
        ),
        'body'    => wp_json_encode($preference_data),
        'timeout' => 30,
    ));

    if (is_wp_error($response)) {
        error_log('AgroChamba Credits: Error MP - ' . $response->get_error_message());
        return new WP_Error('mp_error', 'Error al conectar con Mercado Pago.', array('status' => 500));
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $status_code = wp_remote_retrieve_response_code($response);

    if ($status_code !== 200 && $status_code !== 201) {
        error_log('AgroChamba Credits: MP Error ' . $status_code . ' - ' . wp_json_encode($body));
        return new WP_Error('mp_error', 'Error al crear el pago.', array('status' => 500));
    }

    $is_sandbox = function_exists('agrochamba_mp_is_sandbox') ? agrochamba_mp_is_sandbox() : true;
    $init_point = $is_sandbox
        ? ($body['sandbox_init_point'] ?? $body['init_point'])
        : $body['init_point'];

    return new WP_REST_Response(array(
        'success'       => true,
        'init_point'    => $init_point,
        'preference_id' => $body['id'],
        'package'       => $selected,
    ), 200);
}

function agrochamba_credits_api_history($request) {
    $user_id = get_current_user_id();
    $history = get_user_meta($user_id, '_agrochamba_credit_history', true);

    if (!is_array($history)) {
        $history = array();
    }

    // Limitar a los últimos 50 para la API
    $history = array_slice($history, 0, 50);

    return new WP_REST_Response(array(
        'success' => true,
        'history' => $history,
        'balance' => agrochamba_credits_get_balance($user_id),
    ), 200);
}

function agrochamba_credits_api_costs($request) {
    return new WP_REST_Response(array(
        'success' => true,
        'costs' => array(
            array('action' => 'publish_job', 'label' => 'Publicar trabajo',   'credits' => AGROCHAMBA_CREDIT_COST_PUBLISH_JOB),
            array('action' => 'ai_enhance',  'label' => 'IA - Mejorar texto', 'credits' => AGROCHAMBA_CREDIT_COST_AI_ENHANCE),
            array('action' => 'ai_title',    'label' => 'IA - Generar titulo','credits' => AGROCHAMBA_CREDIT_COST_AI_TITLE),
            array('action' => 'ai_ocr',      'label' => 'IA - OCR imagen',    'credits' => AGROCHAMBA_CREDIT_COST_AI_OCR),
        ),
    ), 200);
}
