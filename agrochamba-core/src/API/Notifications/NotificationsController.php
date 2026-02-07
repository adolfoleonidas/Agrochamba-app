<?php
/**
 * Controlador de Notificaciones Push
 *
 * Maneja el registro de tokens FCM y el envío de notificaciones.
 *
 * @package AgroChamba
 * @subpackage API\Notifications
 * @since 2.1.0
 */

namespace AgroChamba\API\Notifications;

use WP_Error;
use WP_REST_Response;
use WP_REST_Request;

class NotificationsController {

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'agrochamba/v1';

    /**
     * Meta key para tokens FCM del usuario
     */
    const USER_FCM_TOKENS_KEY = '_fcm_tokens';

    /**
     * Meta key para configuración de notificaciones
     */
    const USER_NOTIFICATION_SETTINGS_KEY = '_notification_settings';

    /**
     * Opción para la Server Key de FCM
     */
    const FCM_SERVER_KEY_OPTION = 'agrochamba_fcm_server_key';

    /**
     * Tipos de notificación soportados
     */
    const NOTIFICATION_TYPES = [
        'APPLICATION_STATUS_CHANGED',
        'NEW_APPLICANT',
        'NEW_JOB_IN_ZONE',
        'JOB_EXPIRING',
        'FAVORITE_JOB_UPDATED',
        'FAVORITE_JOB_EXPIRING',
        'NEW_MESSAGE',
        'SYSTEM_ANNOUNCEMENT',
        'PROMOTION',
        'CREDITS_LOW',
        'CREDITS_PURCHASED',
        'GENERAL'
    ];

    /**
     * Inicializar el controlador
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'), 20);

        // Hooks para enviar notificaciones automáticamente
        add_action('agrochamba_application_status_changed', array(__CLASS__, 'on_application_status_changed'), 10, 4);
        add_action('agrochamba_new_application', array(__CLASS__, 'on_new_application'), 10, 3);
    }

    /**
     * Registrar rutas de la API
     */
    public static function register_routes() {
        // POST /notifications/register-token - Registrar token FCM
        register_rest_route(self::API_NAMESPACE, '/notifications/register-token', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'register_fcm_token'),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ));

        // DELETE /notifications/unregister-token - Eliminar token FCM
        register_rest_route(self::API_NAMESPACE, '/notifications/unregister-token', array(
            'methods' => 'DELETE',
            'callback' => array(__CLASS__, 'unregister_fcm_token'),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ));

        // GET/PUT /notifications/settings - Configuración de notificaciones
        register_rest_route(self::API_NAMESPACE, '/notifications/settings', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_notification_settings'),
                'permission_callback' => function() {
                    return is_user_logged_in();
                },
            ),
            array(
                'methods' => 'PUT',
                'callback' => array(__CLASS__, 'update_notification_settings'),
                'permission_callback' => function() {
                    return is_user_logged_in();
                },
            ),
        ));

        // POST /notifications/send - Enviar notificación (solo admin)
        register_rest_route(self::API_NAMESPACE, '/notifications/send', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'send_notification_admin'),
            'permission_callback' => function() {
                return current_user_can('administrator');
            },
        ));
    }

    /**
     * Registrar token FCM
     */
    public static function register_fcm_token($request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        $fcm_token = isset($params['fcm_token']) ? sanitize_text_field($params['fcm_token']) : '';

        if (empty($fcm_token)) {
            return new WP_Error('missing_token', 'Token FCM requerido.', array('status' => 400));
        }

        // Obtener tokens existentes
        $tokens = get_user_meta($user_id, self::USER_FCM_TOKENS_KEY, true);
        if (!is_array($tokens)) {
            $tokens = array();
        }

        // Agregar nuevo token si no existe
        $token_data = array(
            'token' => $fcm_token,
            'device' => isset($params['device_info']) ? sanitize_text_field($params['device_info']) : 'unknown',
            'registered_at' => current_time('mysql'),
            'last_used' => current_time('mysql')
        );

        // Buscar si ya existe este token
        $found = false;
        foreach ($tokens as $key => $existing) {
            if ($existing['token'] === $fcm_token) {
                $tokens[$key]['last_used'] = current_time('mysql');
                $found = true;
                break;
            }
        }

        if (!$found) {
            // Limitar a máximo 5 dispositivos por usuario
            if (count($tokens) >= 5) {
                // Eliminar el más antiguo
                usort($tokens, function($a, $b) {
                    return strtotime($a['last_used']) - strtotime($b['last_used']);
                });
                array_shift($tokens);
            }
            $tokens[] = $token_data;
        }

        update_user_meta($user_id, self::USER_FCM_TOKENS_KEY, $tokens);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Token registrado correctamente.',
            'device_registered' => true
        ), 200);
    }

    /**
     * Eliminar token FCM (para logout)
     */
    public static function unregister_fcm_token($request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        $fcm_token = isset($params['fcm_token']) ? sanitize_text_field($params['fcm_token']) : '';

        $tokens = get_user_meta($user_id, self::USER_FCM_TOKENS_KEY, true);
        if (is_array($tokens)) {
            $tokens = array_filter($tokens, function($t) use ($fcm_token) {
                return $t['token'] !== $fcm_token;
            });
            update_user_meta($user_id, self::USER_FCM_TOKENS_KEY, array_values($tokens));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Token eliminado.'
        ), 200);
    }

    /**
     * Obtener configuración de notificaciones
     */
    public static function get_notification_settings($request) {
        $user_id = get_current_user_id();
        $settings = get_user_meta($user_id, self::USER_NOTIFICATION_SETTINGS_KEY, true);

        if (!is_array($settings)) {
            $settings = self::get_default_settings();
        }

        return new WP_REST_Response(array(
            'success' => true,
            'settings' => $settings
        ), 200);
    }

    /**
     * Actualizar configuración de notificaciones
     */
    public static function update_notification_settings($request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();

        $current_settings = get_user_meta($user_id, self::USER_NOTIFICATION_SETTINGS_KEY, true);
        if (!is_array($current_settings)) {
            $current_settings = self::get_default_settings();
        }

        // Actualizar solo los campos enviados
        $allowed_keys = array('applications', 'new_jobs', 'favorites', 'messages', 'promotions', 'system');
        foreach ($allowed_keys as $key) {
            if (isset($params[$key])) {
                $current_settings[$key] = (bool)$params[$key];
            }
        }

        update_user_meta($user_id, self::USER_NOTIFICATION_SETTINGS_KEY, $current_settings);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Configuración actualizada.',
            'settings' => $current_settings
        ), 200);
    }

    /**
     * Configuración por defecto
     */
    private static function get_default_settings() {
        return array(
            'applications' => true,
            'new_jobs' => true,
            'favorites' => true,
            'messages' => true,
            'promotions' => false,
            'system' => true
        );
    }

    /**
     * Enviar notificación a un usuario
     *
     * @param int $user_id ID del usuario
     * @param string $type Tipo de notificación
     * @param string $title Título
     * @param string $body Cuerpo del mensaje
     * @param array $data Datos adicionales
     * @return bool|WP_Error
     */
    public static function send_to_user($user_id, $type, $title, $body, $data = array()) {
        // Verificar configuración del usuario
        $settings = get_user_meta($user_id, self::USER_NOTIFICATION_SETTINGS_KEY, true);
        if (!is_array($settings)) {
            $settings = self::get_default_settings();
        }

        // Verificar si el usuario quiere recibir este tipo de notificación
        $setting_key = self::get_setting_key_for_type($type);
        if ($setting_key && isset($settings[$setting_key]) && !$settings[$setting_key]) {
            return false; // Usuario desactivó este tipo de notificación
        }

        // Obtener tokens del usuario
        $tokens = get_user_meta($user_id, self::USER_FCM_TOKENS_KEY, true);
        if (!is_array($tokens) || empty($tokens)) {
            return false; // Usuario sin tokens registrados
        }

        // Obtener server key
        $server_key = get_option(self::FCM_SERVER_KEY_OPTION);
        if (empty($server_key)) {
            error_log('AgroChamba: FCM Server Key no configurada');
            return new WP_Error('fcm_not_configured', 'FCM no configurado');
        }

        // Preparar payload
        $notification_data = array_merge($data, array(
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'timestamp' => current_time('timestamp')
        ));

        // Enviar a todos los dispositivos del usuario
        $success_count = 0;
        $invalid_tokens = array();

        foreach ($tokens as $token_info) {
            $result = self::send_fcm_message(
                $server_key,
                $token_info['token'],
                $title,
                $body,
                $notification_data
            );

            if ($result === true) {
                $success_count++;
            } elseif ($result === 'invalid_token') {
                $invalid_tokens[] = $token_info['token'];
            }
        }

        // Limpiar tokens inválidos
        if (!empty($invalid_tokens)) {
            $tokens = array_filter($tokens, function($t) use ($invalid_tokens) {
                return !in_array($t['token'], $invalid_tokens);
            });
            update_user_meta($user_id, self::USER_FCM_TOKENS_KEY, array_values($tokens));
        }

        return $success_count > 0;
    }

    /**
     * Enviar mensaje FCM
     */
    private static function send_fcm_message($server_key, $token, $title, $body, $data) {
        $url = 'https://fcm.googleapis.com/fcm/send';

        $payload = array(
            'to' => $token,
            'notification' => array(
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ),
            'data' => $data,
            'priority' => 'high'
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'key=' . $server_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($payload),
            'timeout' => 10
        ));

        if (is_wp_error($response)) {
            error_log('AgroChamba FCM Error: ' . $response->get_error_message());
            return false;
        }

        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($response_body['results'][0]['error'])) {
            $error = $response_body['results'][0]['error'];
            if (in_array($error, array('NotRegistered', 'InvalidRegistration'))) {
                return 'invalid_token';
            }
            error_log('AgroChamba FCM Error: ' . $error);
            return false;
        }

        return isset($response_body['success']) && $response_body['success'] > 0;
    }

    /**
     * Obtener key de configuración para un tipo de notificación
     */
    private static function get_setting_key_for_type($type) {
        $map = array(
            'APPLICATION_STATUS_CHANGED' => 'applications',
            'NEW_APPLICANT' => 'applications',
            'NEW_JOB_IN_ZONE' => 'new_jobs',
            'JOB_EXPIRING' => 'new_jobs',
            'FAVORITE_JOB_UPDATED' => 'favorites',
            'FAVORITE_JOB_EXPIRING' => 'favorites',
            'NEW_MESSAGE' => 'messages',
            'PROMOTION' => 'promotions',
            'SYSTEM_ANNOUNCEMENT' => 'system',
            'CREDITS_LOW' => 'system',
            'CREDITS_PURCHASED' => 'system'
        );
        return isset($map[$type]) ? $map[$type] : null;
    }

    /**
     * Hook: Cuando cambia el estado de una postulación
     */
    public static function on_application_status_changed($user_id, $job_id, $old_status, $new_status) {
        $job = get_post($job_id);
        if (!$job) return;

        // Títulos especiales por estado
        $special_titles = array(
            'entrevista' => '¡Te invitaron a entrevista!',
            'finalista'  => '¡Eres finalista!',
            'aceptado'   => '¡Has sido contratado!',
        );

        $status_labels = array(
            'visto'      => 'vista por la empresa',
            'en_proceso' => 'puesta en proceso de selección',
            'entrevista' => 'seleccionada para entrevista',
            'finalista'  => 'seleccionada como finalista',
            'aceptado'   => 'aceptada. ¡Felicidades!',
            'rechazado'  => 'no seleccionada',
        );

        $title = isset($special_titles[$new_status])
            ? $special_titles[$new_status]
            : '¡Actualización de postulación!';

        $status_label = isset($status_labels[$new_status]) ? $status_labels[$new_status] : $new_status;

        self::send_to_user(
            $user_id,
            'APPLICATION_STATUS_CHANGED',
            $title,
            "Tu postulación a \"{$job->post_title}\" ha sido {$status_label}.",
            array(
                'job_id' => $job_id,
                'new_status' => $new_status,
                'action' => 'open_applications'
            )
        );
    }

    /**
     * Hook: Cuando llega una nueva postulación
     */
    public static function on_new_application($job_id, $applicant_id, $message) {
        $job = get_post($job_id);
        if (!$job) return;

        $applicant = get_userdata($applicant_id);
        $applicant_name = $applicant ? $applicant->display_name : 'Un usuario';

        // Notificar al dueño del trabajo
        self::send_to_user(
            $job->post_author,
            'NEW_APPLICANT',
            '¡Nuevo postulante!',
            "{$applicant_name} se ha postulado a \"{$job->post_title}\".",
            array(
                'job_id' => $job_id,
                'applicant_id' => $applicant_id,
                'action' => 'open_job_applicants'
            )
        );
    }

    /**
     * Endpoint admin para enviar notificaciones masivas
     */
    public static function send_notification_admin($request) {
        $params = $request->get_json_params();

        $user_ids = isset($params['user_ids']) ? (array)$params['user_ids'] : array();
        $type = isset($params['type']) ? sanitize_text_field($params['type']) : 'GENERAL';
        $title = isset($params['title']) ? sanitize_text_field($params['title']) : '';
        $body = isset($params['body']) ? sanitize_text_field($params['body']) : '';
        $data = isset($params['data']) ? (array)$params['data'] : array();

        if (empty($title) || empty($body)) {
            return new WP_Error('missing_params', 'Título y cuerpo requeridos.', array('status' => 400));
        }

        $success_count = 0;
        $failed_count = 0;

        foreach ($user_ids as $user_id) {
            $result = self::send_to_user($user_id, $type, $title, $body, $data);
            if ($result) {
                $success_count++;
            } else {
                $failed_count++;
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'sent' => $success_count,
            'failed' => $failed_count
        ), 200);
    }
}
