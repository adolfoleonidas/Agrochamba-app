<?php
/**
 * AgroChamba Seguridad: CORS, Rate Limiting y Headers
 *
 * Implementación con namespace que centraliza la lógica de seguridad
 * del antiguo módulo procedural modules/00-security-cors.php.
 */

namespace AgroChamba\Security;

if (!defined('ABSPATH')) {
    exit;
}

class CORSManager
{
    /**
     * Inicializa todos los hooks de seguridad
     */
    public static function init(): void
    {
        // CORS y headers de seguridad - solo en rest_api_init para evitar interferir con subidas de WordPress
        add_action('rest_api_init', [self::class, 'send_cors_headers'], 1);
        add_action('rest_api_init', [self::class, 'send_security_headers'], 1);

        // Rate limiting - solo para endpoints de agrochamba
        add_filter('rest_pre_dispatch', [self::class, 'rate_limit_middleware'], 10, 3);
    }

    /**
     * Envía headers CORS
     */
    public static function send_cors_headers(): void
    {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        // Solo aplicar a endpoints de agrochamba, NO a /wp/v2/media u otros endpoints de WordPress
        if (strpos($request_uri, '/wp-json/agrochamba/v1/') === false) {
            return;
        }

        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        $allowed_origins = apply_filters('agrochamba_allowed_origins', [
            'https://agrochamba.com',
            'https://www.agrochamba.com',
            'http://localhost',
            'http://localhost:8080',
            'http://127.0.0.1',
            'http://127.0.0.1:8080',
        ]);

        if (in_array($origin, $allowed_origins, true) || empty($origin)) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }

        // Solo manejar OPTIONS para endpoints de agrochamba
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS' && strpos($request_uri, '/wp-json/agrochamba/v1/') !== false) {
            status_header(200);
            exit;
        }
    }

    /**
     * Middleware de rate limiting
     *
     * @param mixed $result
     * @param \WP_REST_Server $server
     * @param \WP_REST_Request $request
     * @return mixed
     */
    public static function rate_limit_middleware($result, $server, $request)
    {
        if (strpos($request->get_route(), '/agrochamba/v1/') !== false) {
            $rate_check = self::rate_limit_check($request);
            if (is_wp_error($rate_check)) {
                return $rate_check;
            }
        }
        return $result;
    }

    /**
     * Verifica los límites de petición
     *
     * @param \WP_REST_Request $request
     * @return true|\WP_Error
     */
    private static function rate_limit_check($request)
    {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, '/wp-json/agrochamba/v1/') === false) {
            return true;
        }

        $client_id = self::get_client_id();
        $rate_limit_config = apply_filters('agrochamba_rate_limit', [
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
            'requests_per_day' => 10000,
        ]);

        $transient_minute = 'agrochamba_rate_limit_minute_' . $client_id;
        $transient_hour = 'agrochamba_rate_limit_hour_' . $client_id;
        $transient_day = 'agrochamba_rate_limit_day_' . $client_id;

        $count_minute = get_transient($transient_minute);
        if ($count_minute === false) {
            set_transient($transient_minute, 1, 60);
        } else {
            if ($count_minute >= $rate_limit_config['requests_per_minute']) {
                return new \WP_Error(
                    'rate_limit_exceeded',
                    'Has excedido el límite de peticiones por minuto. Por favor, espera un momento.',
                    ['status' => 429]
                );
            }
            set_transient($transient_minute, $count_minute + 1, 60);
        }

        $count_hour = get_transient($transient_hour);
        if ($count_hour === false) {
            set_transient($transient_hour, 1, 3600);
        } else {
            if ($count_hour >= $rate_limit_config['requests_per_hour']) {
                return new \WP_Error(
                    'rate_limit_exceeded',
                    'Has excedido el límite de peticiones por hora. Por favor, intenta más tarde.',
                    ['status' => 429]
                );
            }
            set_transient($transient_hour, $count_hour + 1, 3600);
        }

        $count_day = get_transient($transient_day);
        if ($count_day === false) {
            set_transient($transient_day, 1, 86400);
        } else {
            if ($count_day >= $rate_limit_config['requests_per_day']) {
                return new \WP_Error(
                    'rate_limit_exceeded',
                    'Has excedido el límite de peticiones por día. Por favor, intenta mañana.',
                    ['status' => 429]
                );
            }
            set_transient($transient_day, $count_day + 1, 86400);
        }

        return true;
    }

    /**
     * Identificador único para el cliente
     */
    private static function get_client_id(): string
    {
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return 'ip_' . md5($ip . $user_agent);
    }

    /**
     * Headers de seguridad adicionales
     * IMPORTANTE: Solo aplicar a endpoints de agrochamba para no interferir con subidas de WordPress
     */
    public static function send_security_headers(): void
    {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        // Solo aplicar a endpoints de agrochamba, NO a /wp/v2/media u otros endpoints de WordPress
        if (strpos($request_uri, '/wp-json/agrochamba/v1/') === false) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}
