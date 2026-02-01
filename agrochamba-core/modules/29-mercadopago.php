<?php
/**
 * =============================================================
 * MÓDULO 29: INTEGRACIÓN MERCADO PAGO
 * =============================================================
 *
 * Endpoints:
 * - POST /agrochamba/v1/payments/create-preference - Crear preferencia de pago para publicar trabajo
 * - POST /agrochamba/v1/payments/webhook          - Webhook IPN de Mercado Pago
 * - GET  /agrochamba/v1/payments/status/{id}       - Consultar estado de pago por job_id
 *
 * Flujo:
 * 1. Empresa crea trabajo → estado "pending_payment"
 * 2. App solicita preferencia de pago → backend crea preference en MP
 * 3. App abre Checkout Pro (Custom Tab)
 * 4. MP envía webhook → backend cambia estado del trabajo a "pending" (moderación)
 * 5. App verifica estado al regresar
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// CONSTANTES DE CONFIGURACIÓN
// ==========================================

// Obtener credenciales de MP desde wp_options (configurables desde admin)
function agrochamba_mp_get_access_token() {
    return get_option('agrochamba_mp_access_token', '');
}

function agrochamba_mp_get_public_key() {
    return get_option('agrochamba_mp_public_key', '');
}

function agrochamba_mp_is_sandbox() {
    return (bool) get_option('agrochamba_mp_sandbox', true);
}

// Precio de publicación de trabajo (en PEN - Soles peruanos)
function agrochamba_mp_get_job_price() {
    return (float) get_option('agrochamba_mp_job_price', 5.00);
}

// Deep link scheme para la app Android
define('AGROCHAMBA_MP_DEEP_LINK_SCHEME', 'agrochamba');

// ==========================================
// REGISTRAR ENDPOINTS REST API
// ==========================================

add_action('rest_api_init', function () {

    // Crear preferencia de pago
    register_rest_route('agrochamba/v1', '/payments/create-preference', array(
        'methods'             => 'POST',
        'callback'            => 'agrochamba_mp_create_preference',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    // Webhook de Mercado Pago (público, sin autenticación)
    register_rest_route('agrochamba/v1', '/payments/webhook', array(
        'methods'             => 'POST',
        'callback'            => 'agrochamba_mp_webhook',
        'permission_callback' => '__return_true',
    ));

    // Consultar estado de pago por job_id
    register_rest_route('agrochamba/v1', '/payments/status/(?P<job_id>\d+)', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_mp_payment_status',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
        'args' => array(
            'job_id' => array(
                'required'          => true,
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                },
            ),
        ),
    ));

    // Obtener configuración pública de MP (public key, precio)
    register_rest_route('agrochamba/v1', '/payments/config', array(
        'methods'             => 'GET',
        'callback'            => 'agrochamba_mp_get_config',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
});

// ==========================================
// 1. CREAR PREFERENCIA DE PAGO
// ==========================================

function agrochamba_mp_create_preference($request) {
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $params = $request->get_json_params();

    // Validar que sea empresa o admin
    if (!in_array('employer', $user->roles) && !in_array('administrator', $user->roles)) {
        return new WP_Error(
            'rest_forbidden',
            'Solo las empresas pueden publicar trabajos pagos.',
            array('status' => 403)
        );
    }

    // Admins no necesitan pagar
    if (in_array('administrator', $user->roles)) {
        return new WP_REST_Response(array(
            'success'      => true,
            'payment_free' => true,
            'message'      => 'Los administradores no requieren pago.',
        ), 200);
    }

    // Validar job_id
    $job_id = isset($params['job_id']) ? intval($params['job_id']) : 0;
    if ($job_id <= 0) {
        return new WP_Error(
            'rest_invalid_param',
            'Se requiere un job_id válido.',
            array('status' => 400)
        );
    }

    // Verificar que el trabajo existe y pertenece al usuario
    $post = get_post($job_id);
    if (!$post || $post->post_author != $user_id) {
        return new WP_Error(
            'rest_forbidden',
            'No tienes permiso para este trabajo.',
            array('status' => 403)
        );
    }

    // Verificar que el trabajo está pendiente de pago
    $payment_status = get_post_meta($job_id, '_payment_status', true);
    if ($payment_status === 'approved') {
        return new WP_REST_Response(array(
            'success'        => true,
            'already_paid'   => true,
            'message'        => 'Este trabajo ya fue pagado.',
        ), 200);
    }

    // Obtener credenciales de MP
    $access_token = agrochamba_mp_get_access_token();
    if (empty($access_token)) {
        error_log('AgroChamba MP: Access token no configurado');
        return new WP_Error(
            'mp_not_configured',
            'El sistema de pagos no está configurado. Contacta al administrador.',
            array('status' => 500)
        );
    }

    $job_price = agrochamba_mp_get_job_price();
    $job_title = $post->post_title;

    // Construir la preferencia de pago
    $site_url = home_url();
    $preference_data = array(
        'items' => array(
            array(
                'id'          => 'job_publish_' . $job_id,
                'title'       => 'Publicar trabajo: ' . mb_substr($job_title, 0, 60),
                'description' => 'Publicación de oferta de trabajo en AgroChamba',
                'quantity'    => 1,
                'unit_price'  => $job_price,
                'currency_id' => 'PEN',
            ),
        ),
        'payer' => array(
            'email' => $user->user_email,
        ),
        'back_urls' => array(
            'success' => AGROCHAMBA_MP_DEEP_LINK_SCHEME . '://payment/success?job_id=' . $job_id,
            'failure' => AGROCHAMBA_MP_DEEP_LINK_SCHEME . '://payment/failure?job_id=' . $job_id,
            'pending' => AGROCHAMBA_MP_DEEP_LINK_SCHEME . '://payment/pending?job_id=' . $job_id,
        ),
        'auto_return'  => 'approved',
        'external_reference' => 'job_' . $job_id . '_user_' . $user_id,
        'notification_url'   => rest_url('agrochamba/v1/payments/webhook'),
        'statement_descriptor' => 'AgroChamba',
        'metadata' => array(
            'job_id'  => $job_id,
            'user_id' => $user_id,
        ),
    );

    // Llamar a la API de Mercado Pago
    $api_url = 'https://api.mercadopago.com/checkout/preferences';
    $response = wp_remote_post($api_url, array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
        ),
        'body'    => wp_json_encode($preference_data),
        'timeout' => 30,
    ));

    if (is_wp_error($response)) {
        error_log('AgroChamba MP: Error creando preferencia - ' . $response->get_error_message());
        return new WP_Error(
            'mp_api_error',
            'Error al conectar con Mercado Pago. Intenta nuevamente.',
            array('status' => 500)
        );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($status_code !== 200 && $status_code !== 201) {
        error_log('AgroChamba MP: Error de API - Status: ' . $status_code . ' Body: ' . wp_json_encode($body));
        return new WP_Error(
            'mp_api_error',
            'Error al crear la preferencia de pago.',
            array('status' => 500)
        );
    }

    // Guardar ID de preferencia en el post meta
    update_post_meta($job_id, '_mp_preference_id', $body['id']);
    update_post_meta($job_id, '_payment_status', 'pending');
    update_post_meta($job_id, '_payment_amount', $job_price);

    // Determinar init_point según modo sandbox/producción
    $is_sandbox = agrochamba_mp_is_sandbox();
    $init_point = $is_sandbox
        ? ($body['sandbox_init_point'] ?? $body['init_point'])
        : $body['init_point'];

    return new WP_REST_Response(array(
        'success'       => true,
        'init_point'    => $init_point,
        'preference_id' => $body['id'],
        'job_id'        => $job_id,
        'amount'        => $job_price,
        'currency'      => 'PEN',
    ), 200);
}

// ==========================================
// 2. WEBHOOK DE MERCADO PAGO
// ==========================================

function agrochamba_mp_webhook($request) {
    $params = $request->get_json_params();

    // MP envía notificaciones con distintos tipos
    $type = isset($params['type']) ? $params['type'] : '';
    $action = isset($params['action']) ? $params['action'] : '';

    // También puede venir como query parameter (IPN legacy)
    if (empty($type)) {
        $type = $request->get_param('type');
    }

    error_log('AgroChamba MP Webhook: type=' . $type . ' action=' . $action);

    // Solo procesar notificaciones de pago
    if ($type === 'payment') {
        $payment_id = isset($params['data']['id']) ? $params['data']['id'] : null;
        if ($payment_id) {
            agrochamba_mp_process_payment($payment_id);
        }
    }

    // Responder 200 OK para que MP no reintente
    return new WP_REST_Response(array('status' => 'ok'), 200);
}

/**
 * Procesar un pago verificando con la API de MP
 */
function agrochamba_mp_process_payment($payment_id) {
    $access_token = agrochamba_mp_get_access_token();
    if (empty($access_token)) {
        error_log('AgroChamba MP: No se puede procesar pago - access token vacío');
        return false;
    }

    // Consultar el pago en la API de MP
    $api_url = 'https://api.mercadopago.com/v1/payments/' . intval($payment_id);
    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
        ),
        'timeout' => 30,
    ));

    if (is_wp_error($response)) {
        error_log('AgroChamba MP: Error consultando pago ' . $payment_id . ' - ' . $response->get_error_message());
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body) || !isset($body['status'])) {
        error_log('AgroChamba MP: Respuesta inválida para pago ' . $payment_id);
        return false;
    }

    $payment_status = $body['status']; // approved, pending, rejected, etc.
    $external_reference = isset($body['external_reference']) ? $body['external_reference'] : '';
    $metadata = isset($body['metadata']) ? $body['metadata'] : array();

    // ==========================================
    // DETECTAR TIPO DE COMPRA: créditos o trabajo
    // ==========================================
    $purchase_type = isset($metadata['type']) ? $metadata['type'] : 'job';

    // Compra de créditos (formato: credits_{pack_id}_user_{id}_{timestamp})
    if ($purchase_type === 'credits' || strpos($external_reference, 'credits_') === 0) {
        return agrochamba_mp_process_credits_payment($payment_id, $payment_status, $body, $metadata, $external_reference);
    }

    // Compra de publicación de trabajo
    $job_id = 0;
    if (preg_match('/^job_(\d+)_user_(\d+)$/', $external_reference, $matches)) {
        $job_id = intval($matches[1]);
    } elseif (isset($metadata['job_id'])) {
        $job_id = intval($metadata['job_id']);
    }

    if ($job_id <= 0) {
        error_log('AgroChamba MP: No se pudo extraer job_id del pago ' . $payment_id);
        return false;
    }

    error_log('AgroChamba MP: Pago ' . $payment_id . ' para job ' . $job_id . ' - Estado: ' . $payment_status);

    // Guardar info del pago
    update_post_meta($job_id, '_mp_payment_id', $payment_id);
    update_post_meta($job_id, '_payment_status', $payment_status);
    update_post_meta($job_id, '_payment_date', current_time('mysql'));
    update_post_meta($job_id, '_mp_payment_data', wp_json_encode(array(
        'id'                 => $body['id'],
        'status'             => $body['status'],
        'status_detail'      => $body['status_detail'] ?? '',
        'payment_method_id'  => $body['payment_method_id'] ?? '',
        'payment_type_id'    => $body['payment_type_id'] ?? '',
        'transaction_amount' => $body['transaction_amount'] ?? 0,
        'currency_id'        => $body['currency_id'] ?? 'PEN',
        'date_approved'      => $body['date_approved'] ?? '',
    )));

    // Si el pago fue aprobado, cambiar estado del trabajo
    if ($payment_status === 'approved') {
        $post = get_post($job_id);
        if ($post) {
            $user = get_userdata($post->post_author);
            // Admins publican directo, empresas van a moderación
            $new_status = 'pending';
            if ($user && in_array('administrator', $user->roles)) {
                $new_status = 'publish';
            }

            wp_update_post(array(
                'ID'          => $job_id,
                'post_status' => $new_status,
            ));

            error_log('AgroChamba MP: Job ' . $job_id . ' actualizado a estado: ' . $new_status);
        }
    }

    return true;
}

// ==========================================
// 3. CONSULTAR ESTADO DE PAGO
// ==========================================

function agrochamba_mp_payment_status($request) {
    $job_id = intval($request->get_param('job_id'));
    $user_id = get_current_user_id();

    // Verificar que el trabajo existe
    $post = get_post($job_id);
    if (!$post) {
        return new WP_Error('not_found', 'Trabajo no encontrado.', array('status' => 404));
    }

    // Verificar que pertenece al usuario (o es admin)
    $user = get_userdata($user_id);
    if ($post->post_author != $user_id && !in_array('administrator', $user->roles)) {
        return new WP_Error('rest_forbidden', 'No tienes permiso.', array('status' => 403));
    }

    $payment_status = get_post_meta($job_id, '_payment_status', true);
    $payment_id = get_post_meta($job_id, '_mp_payment_id', true);
    $payment_amount = get_post_meta($job_id, '_payment_amount', true);
    $payment_date = get_post_meta($job_id, '_payment_date', true);

    // Si hay un payment_id y el estado no es definitivo, re-verificar con MP
    if (!empty($payment_id) && !in_array($payment_status, array('approved', 'rejected', 'cancelled'))) {
        $access_token = agrochamba_mp_get_access_token();
        if (!empty($access_token)) {
            $api_url = 'https://api.mercadopago.com/v1/payments/' . intval($payment_id);
            $response = wp_remote_get($api_url, array(
                'headers' => array('Authorization' => 'Bearer ' . $access_token),
                'timeout' => 15,
            ));

            if (!is_wp_error($response)) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($body['status'])) {
                    $payment_status = $body['status'];
                    update_post_meta($job_id, '_payment_status', $payment_status);

                    // Procesar cambio de estado si fue aprobado
                    if ($payment_status === 'approved') {
                        agrochamba_mp_process_payment($payment_id);
                    }
                }
            }
        }
    }

    return new WP_REST_Response(array(
        'success'        => true,
        'job_id'         => $job_id,
        'payment_status' => $payment_status ?: 'none',
        'payment_id'     => $payment_id ?: null,
        'amount'         => $payment_amount ? (float) $payment_amount : null,
        'payment_date'   => $payment_date ?: null,
        'post_status'    => $post->post_status,
    ), 200);
}

// ==========================================
// 4. CONFIGURACIÓN PÚBLICA
// ==========================================

function agrochamba_mp_get_config($request) {
    $public_key = agrochamba_mp_get_public_key();
    $job_price = agrochamba_mp_get_job_price();
    $is_sandbox = agrochamba_mp_is_sandbox();

    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $is_admin = $user && in_array('administrator', $user->roles);

    return new WP_REST_Response(array(
        'success'       => true,
        'public_key'    => $public_key,
        'job_price'     => $job_price,
        'currency'      => 'PEN',
        'currency_symbol' => 'S/',
        'is_sandbox'    => $is_sandbox,
        'payment_required' => !$is_admin, // Admins no pagan
    ), 200);
}

// ==========================================
// 5. PÁGINA DE CONFIGURACIÓN EN ADMIN
// ==========================================

add_action('admin_menu', function () {
    add_submenu_page(
        'options-general.php',
        'Mercado Pago - AgroChamba',
        'Mercado Pago',
        'manage_options',
        'agrochamba-mercadopago',
        'agrochamba_mp_admin_page'
    );
});

function agrochamba_mp_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permiso para acceder a esta página.');
    }

    // Guardar configuración
    if (isset($_POST['agrochamba_mp_save']) && check_admin_referer('agrochamba_mp_settings')) {
        update_option('agrochamba_mp_access_token', sanitize_text_field($_POST['mp_access_token'] ?? ''));
        update_option('agrochamba_mp_public_key', sanitize_text_field($_POST['mp_public_key'] ?? ''));
        update_option('agrochamba_mp_sandbox', isset($_POST['mp_sandbox']) ? 1 : 0);
        update_option('agrochamba_mp_job_price', floatval($_POST['mp_job_price'] ?? 5.00));
        echo '<div class="notice notice-success"><p>Configuración guardada.</p></div>';
    }

    $access_token = agrochamba_mp_get_access_token();
    $public_key = agrochamba_mp_get_public_key();
    $is_sandbox = agrochamba_mp_is_sandbox();
    $job_price = agrochamba_mp_get_job_price();
    ?>
    <div class="wrap">
        <h1>Configuración de Mercado Pago</h1>
        <p>Configura las credenciales de Mercado Pago para el cobro de publicación de trabajos.</p>

        <form method="post">
            <?php wp_nonce_field('agrochamba_mp_settings'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="mp_public_key">Public Key</label></th>
                    <td>
                        <input type="text" id="mp_public_key" name="mp_public_key"
                               value="<?php echo esc_attr($public_key); ?>" class="regular-text">
                        <p class="description">Clave pública de Mercado Pago.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mp_access_token">Access Token</label></th>
                    <td>
                        <input type="password" id="mp_access_token" name="mp_access_token"
                               value="<?php echo esc_attr($access_token); ?>" class="regular-text">
                        <p class="description">Token de acceso (privado). Obténlo en
                            <a href="https://www.mercadopago.com.pe/developers/panel/app" target="_blank">tu panel de desarrollador</a>.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mp_job_price">Precio por publicación (PEN)</label></th>
                    <td>
                        <input type="number" id="mp_job_price" name="mp_job_price"
                               value="<?php echo esc_attr($job_price); ?>" min="0.01" step="0.01" class="small-text">
                        <p class="description">Precio en Soles (S/) por cada trabajo publicado.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Modo Sandbox</th>
                    <td>
                        <label>
                            <input type="checkbox" name="mp_sandbox" value="1" <?php checked($is_sandbox); ?>>
                            Activar modo de pruebas (sandbox)
                        </label>
                        <p class="description">Usa credenciales de prueba para testing sin cobros reales.</p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="agrochamba_mp_save" class="button-primary" value="Guardar configuración">
            </p>
        </form>

        <hr>
        <h2>Webhook URL</h2>
        <p>Configura esta URL como Notification URL en tu panel de Mercado Pago:</p>
        <code><?php echo esc_html(rest_url('agrochamba/v1/payments/webhook')); ?></code>

        <h2>Deep Links configurados</h2>
        <ul>
            <li><strong>Pago trabajo:</strong> <code>agrochamba://payment/{success|failure|pending}</code></li>
            <li><strong>Compra créditos:</strong> <code>agrochamba://credits/{success|failure|pending}</code></li>
        </ul>
    </div>
    <?php
}

// ==========================================
// 6. PROCESAR PAGO DE CRÉDITOS
// ==========================================

/**
 * Procesa un pago de compra de créditos desde el webhook de MP.
 */
function agrochamba_mp_process_credits_payment($payment_id, $payment_status, $body, $metadata, $external_reference) {
    error_log('AgroChamba MP: Procesando compra de créditos - pago ' . $payment_id . ' estado: ' . $payment_status);

    // Extraer user_id y package_id
    $user_id = isset($metadata['user_id']) ? intval($metadata['user_id']) : 0;
    $package_id = isset($metadata['package_id']) ? $metadata['package_id'] : '';
    $credits_to_add = isset($metadata['credits']) ? intval($metadata['credits']) : 0;

    // Fallback: extraer del external_reference (credits_{pack_id}_user_{id}_{timestamp})
    if ($user_id <= 0 && preg_match('/credits_(\w+)_user_(\d+)_/', $external_reference, $matches)) {
        $package_id = $matches[1];
        $user_id = intval($matches[2]);
    }

    if ($user_id <= 0) {
        error_log('AgroChamba MP Credits: No se pudo extraer user_id del pago ' . $payment_id);
        return false;
    }

    // Si no tenemos los créditos del metadata, buscar del paquete
    if ($credits_to_add <= 0 && !empty($package_id) && function_exists('agrochamba_credits_get_packages')) {
        $packages = agrochamba_credits_get_packages();
        foreach ($packages as $pkg) {
            if ($pkg['id'] === $package_id) {
                $credits_to_add = $pkg['credits'];
                break;
            }
        }
    }

    if ($credits_to_add <= 0) {
        error_log('AgroChamba MP Credits: No se pudo determinar créditos para pago ' . $payment_id);
        return false;
    }

    // Evitar procesar pagos duplicados
    $processed = get_user_meta($user_id, '_mp_processed_payments', true);
    if (!is_array($processed)) {
        $processed = array();
    }
    if (in_array($payment_id, $processed)) {
        error_log('AgroChamba MP Credits: Pago ' . $payment_id . ' ya procesado, ignorando');
        return true;
    }

    // Si el pago fue aprobado, acreditar los créditos
    if ($payment_status === 'approved' && function_exists('agrochamba_credits_add')) {
        agrochamba_credits_add(
            $user_id,
            $credits_to_add,
            'Compra de ' . $credits_to_add . ' créditos (Pago MP #' . $payment_id . ')',
            'mp_payment_' . $payment_id
        );

        // Marcar como procesado
        $processed[] = $payment_id;
        update_user_meta($user_id, '_mp_processed_payments', $processed);

        error_log('AgroChamba MP Credits: ' . $credits_to_add . ' créditos acreditados al usuario ' . $user_id);
    }

    return true;
}
