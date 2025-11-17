<?php
/**
 * =============================================================
 * MÓDULO 0: SEGURIDAD, CORS Y RATE LIMITING
 * =============================================================
 * 
 * Este módulo debe cargarse PRIMERO para aplicar seguridad
 * a todas las peticiones de la API REST.
 * 
 * Funcionalidades:
 * - Configuración de CORS para permitir peticiones desde la app móvil
 * - Rate limiting para prevenir abuso de API
 * - Headers de seguridad adicionales
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. CONFIGURACIÓN DE CORS
// ==========================================
if (!function_exists('agrochamba_cors_headers')) {
    function agrochamba_cors_headers() {
        // Solo aplicar a endpoints de nuestra API
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, '/wp-json/agrochamba/v1/') === false) {
            return;
        }
        
        // Obtener origen de la petición
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        // Lista de orígenes permitidos (configurable)
        $allowed_origins = apply_filters('agrochamba_allowed_origins', array(
            'https://agrochamba.com',
            'http://localhost',
            'http://localhost:8080',
            'http://127.0.0.1',
            'http://127.0.0.1:8080',
            // Agregar más orígenes según sea necesario
        ));
        
        // Si el origen está en la lista o es una app móvil (sin origen), permitir
        if (in_array($origin, $allowed_origins) || empty($origin)) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400'); // 24 horas
        }
        
        // Responder a preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            status_header(200);
            exit;
        }
    }
    add_action('rest_api_init', 'agrochamba_cors_headers', 1);
    add_action('init', 'agrochamba_cors_headers', 1);
}

// ==========================================
// 2. RATE LIMITING
// ==========================================
if (!function_exists('agrochamba_rate_limit_check')) {
    function agrochamba_rate_limit_check($request) {
        // Solo aplicar a endpoints de nuestra API
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, '/wp-json/agrochamba/v1/') === false) {
            return true;
        }
        
        // Obtener identificador del cliente
        $client_id = agrochamba_get_client_id();
        
        // Configuración de rate limiting (configurable)
        $rate_limit_config = apply_filters('agrochamba_rate_limit', array(
            'requests_per_minute' => 60,  // 60 peticiones por minuto
            'requests_per_hour' => 1000,  // 1000 peticiones por hora
            'requests_per_day' => 10000,  // 10000 peticiones por día
        ));
        
        // Verificar límites
        $transient_minute = 'agrochamba_rate_limit_minute_' . $client_id;
        $transient_hour = 'agrochamba_rate_limit_hour_' . $client_id;
        $transient_day = 'agrochamba_rate_limit_day_' . $client_id;
        
        // Contador por minuto
        $count_minute = get_transient($transient_minute);
        if ($count_minute === false) {
            set_transient($transient_minute, 1, 60); // 60 segundos
        } else {
            if ($count_minute >= $rate_limit_config['requests_per_minute']) {
                return new WP_Error(
                    'rate_limit_exceeded',
                    'Has excedido el límite de peticiones por minuto. Por favor, espera un momento.',
                    array('status' => 429)
                );
            }
            set_transient($transient_minute, $count_minute + 1, 60);
        }
        
        // Contador por hora
        $count_hour = get_transient($transient_hour);
        if ($count_hour === false) {
            set_transient($transient_hour, 1, 3600); // 1 hora
        } else {
            if ($count_hour >= $rate_limit_config['requests_per_hour']) {
                return new WP_Error(
                    'rate_limit_exceeded',
                    'Has excedido el límite de peticiones por hora. Por favor, intenta más tarde.',
                    array('status' => 429)
                );
            }
            set_transient($transient_hour, $count_hour + 1, 3600);
        }
        
        // Contador por día
        $count_day = get_transient($transient_day);
        if ($count_day === false) {
            set_transient($transient_day, 1, 86400); // 24 horas
        } else {
            if ($count_day >= $rate_limit_config['requests_per_day']) {
                return new WP_Error(
                    'rate_limit_exceeded',
                    'Has excedido el límite de peticiones por día. Por favor, intenta mañana.',
                    array('status' => 429)
                );
            }
            set_transient($transient_day, $count_day + 1, 86400);
        }
        
        return true;
    }
    
    // Aplicar rate limiting a todos los endpoints de la API
    add_filter('rest_pre_dispatch', function($result, $server, $request) {
        if (strpos($request->get_route(), '/agrochamba/v1/') !== false) {
            $rate_check = agrochamba_rate_limit_check($request);
            if (is_wp_error($rate_check)) {
                return $rate_check;
            }
        }
        return $result;
    }, 10, 3);
}

// ==========================================
// 3. OBTENER IDENTIFICADOR DEL CLIENTE
// ==========================================
if (!function_exists('agrochamba_get_client_id')) {
    function agrochamba_get_client_id() {
        // Si el usuario está autenticado, usar su ID
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }
        
        // Si no, usar IP + User Agent
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return 'ip_' . md5($ip . $user_agent);
    }
}

// ==========================================
// 4. HEADERS DE SEGURIDAD ADICIONALES
// ==========================================
if (!function_exists('agrochamba_security_headers')) {
    function agrochamba_security_headers() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, '/wp-json/agrochamba/v1/') === false) {
            return;
        }
        
        // Headers de seguridad
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        
        // No cachear respuestas de API por defecto
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    add_action('rest_api_init', 'agrochamba_security_headers', 1);
    add_action('init', 'agrochamba_security_headers', 1);
}

