<?php
/**
 * PHPUnit Bootstrap file
 * Carga un entorno mínimo para ejecutar tests unitarios sin WordPress completo.
 */

// 1) Cargar autoloader de Composer si existe
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

// 2) Definir constantes básicas simuladas de WP
if (!defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wordpress' . DIRECTORY_SEPARATOR);
}
if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

// 3) Definir stubs mínimos de funciones de WordPress usadas por el plugin
if (!function_exists('plugins_url')) {
    function plugins_url($path = '', $plugin = '') {
        $base = 'http://example.com/wp-content/plugins/agrochamba-core';
        return rtrim($base, '/') . ($path ? '/' . ltrim($path, '/') : '');
    }
}
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        // No-op en tests unitarios sin WP
    }
}
if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        // No-op en tests unitarios sin WP
    }
}
if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value) {
        return $value;
    }
}
// Common WP utility callbacks
if (!function_exists('__return_true')) {
    function __return_true() { return true; }
}
if (!function_exists('__return_false')) {
    function __return_false() { return false; }
}
if (!function_exists('wp_get_attachment_image_url')) {
    function wp_get_attachment_image_url($attachment_id, $size = 'full') {
        return 'http://example.com/media/' . intval($attachment_id) . '/' . (is_string($size) ? $size : 'full');
    }
}
// Minimal REST API stubs for route registration and inspection
if (!function_exists('rest_get_server')) {
    class AC_Test_REST_Server_Stub {
        private $routes = [];
        public function get_routes() {
            return $this->routes;
        }
        public function add_route($namespace, $route, $args) {
            $full = '/' . trim($namespace, '/') . '/' . ltrim($route, '/');
            // Normalizar expresiones regex de parámetros para parecerse a WP
            $full = preg_replace('#/{2,}#', '/', $full);
            if (!isset($this->routes[$full])) {
                $this->routes[$full] = [];
            }
            // En WP, register_rest_route puede recibir un array de endpoints o un endpoint simple
            if (is_array($args) && isset($args[0])) {
                foreach ($args as $endpoint) {
                    $this->routes[$full][] = $endpoint;
                }
            } else {
                $this->routes[$full][] = $args;
            }
        }
        public function reset() {
            $this->routes = [];
        }
    }
    $GLOBALS['__ac_rest_server'] = new AC_Test_REST_Server_Stub();
    function rest_get_server() {
        return $GLOBALS['__ac_rest_server'];
    }
}
if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args) {
        rest_get_server()->add_route($namespace, $route, $args);
        return true;
    }
}
// Basic hooks system for tests
if (!function_exists('add_action')) {
    $GLOBALS['__ac_actions'] = [];
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['__ac_actions'][$hook][$priority][] = [$callback, $accepted_args];
    }
    function do_action($hook, ...$args) {
        if (empty($GLOBALS['__ac_actions'][$hook])) return;
        ksort($GLOBALS['__ac_actions'][$hook]);
        foreach ($GLOBALS['__ac_actions'][$hook] as $priority => $callbacks) {
            foreach ($callbacks as $cb) {
                $callback = $cb[0];
                $accepted = $cb[1];
                $callArgs = array_slice($args, 0, $accepted);
                call_user_func_array($callback, $callArgs);
            }
        }
    }
}
// Transients API stubs (memoria en proceso)
if (!function_exists('get_transient')) {
    $GLOBALS['__ac_transients'] = [];
    function get_transient($transient) {
        $store = $GLOBALS['__ac_transients'] ?? [];
        if (!isset($store[$transient])) {
            return false;
        }
        $data = $store[$transient];
        if ($data['expires'] !== 0 && $data['expires'] < time()) {
            unset($GLOBALS['__ac_transients'][$transient]);
            return false;
        }
        return $data['value'];
    }
}
if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration) {
        $expires = $expiration > 0 ? (time() + (int)$expiration) : 0;
        if (!isset($GLOBALS['__ac_transients'])) {
            $GLOBALS['__ac_transients'] = [];
        }
        $GLOBALS['__ac_transients'][$transient] = [
            'value' => $value,
            'expires' => $expires,
        ];
        return true;
    }
}
if (!function_exists('delete_transient')) {
    function delete_transient($transient) {
        if (isset($GLOBALS['__ac_transients'][$transient])) {
            unset($GLOBALS['__ac_transients'][$transient]);
            return true;
        }
        return false;
    }
}
if (!function_exists('is_admin')) {
    function is_admin() { return false; }
}

// Minimal user and auth stubs
if (!function_exists('is_user_logged_in')) {
    $GLOBALS['__ac_current_user_logged_in'] = false;
    function is_user_logged_in() { return (bool)$GLOBALS['__ac_current_user_logged_in']; }
}
if (!function_exists('get_current_user_id')) {
    $GLOBALS['__ac_current_user_id'] = 0;
    function get_current_user_id() { return (int)$GLOBALS['__ac_current_user_id']; }
}
if (!function_exists('get_userdata')) {
    $GLOBALS['__ac_users'] = [];
    function get_userdata($user_id) {
        $users = $GLOBALS['__ac_users'] ?? [];
        return $users[$user_id] ?? null;
    }
}
if (!function_exists('wp_update_user')) {
    function wp_update_user($userdata) {
        $id = $userdata['ID'] ?? 0;
        if (!$id) return 0;
        if (!isset($GLOBALS['__ac_users'][$id])) return 0;
        foreach ($userdata as $k => $v) {
            if ($k === 'ID') continue;
            $GLOBALS['__ac_users'][$id]->{$k} = $v;
        }
        return $id;
    }
}
if (!function_exists('get_user_meta')) {
    $GLOBALS['__ac_user_meta'] = [];
    function get_user_meta($user_id, $key, $single = true) {
        if (!isset($GLOBALS['__ac_user_meta'][$user_id])) return $single ? '' : [];
        if ($key === '') return $GLOBALS['__ac_user_meta'][$user_id];
        if (!array_key_exists($key, $GLOBALS['__ac_user_meta'][$user_id])) return $single ? '' : [];
        return $single ? $GLOBALS['__ac_user_meta'][$user_id][$key] : [$GLOBALS['__ac_user_meta'][$user_id][$key]];
    }
}
if (!function_exists('update_user_meta')) {
    function update_user_meta($user_id, $key, $value) {
        if (!isset($GLOBALS['__ac_user_meta'][$user_id])) $GLOBALS['__ac_user_meta'][$user_id] = [];
        $GLOBALS['__ac_user_meta'][$user_id][$key] = $value;
        return true;
    }
}
if (!function_exists('delete_user_meta')) {
    function delete_user_meta($user_id, $key, $value = null) {
        if (isset($GLOBALS['__ac_user_meta'][$user_id][$key])) {
            unset($GLOBALS['__ac_user_meta'][$user_id][$key]);
            return true;
        }
        return false;
    }
}

// Sanitizers
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        $str = (string)$str;
        $str = wp_strip_all_tags($str);
        $str = preg_replace('/[\r\n\t]+/', ' ', $str);
        return trim($str);
    }
}
if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) {
        $url = trim((string)$url);
        // Permitir solo http/https
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        return '';
    }
}
if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($str) {
        return trim(strip_tags((string)$str));
    }
}
if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        // Similar a sanitize_text_field pero preservando saltos de línea simples
        $str = (string)$str;
        $str = strip_tags($str);
        // Normalizar saltos múltiples a uno
        $str = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $str);
        return trim($str);
    }
}

// Minimal REST response and error stubs
if (!class_exists('WP_Error')) {
    class WP_Error {
        public $errors = [];
        public $error_data = [];
        public function __construct($code = '', $message = '', $data = null) {
            if ($code) {
                $this->errors[$code] = [$message];
            }
            if ($data !== null) {
                $this->error_data[$code] = $data;
            }
        }
        public function get_error_code() { return key($this->errors); }
        public function get_error_message() { $c = key($this->errors); return $this->errors[$c][0] ?? ''; }
        public function get_error_data($code = '') { $c = $code ?: key($this->errors); return $this->error_data[$c] ?? null; }
    }
}
if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public $data;
        public $status;
        public function __construct($data = null, $status = 200) {
            $this->data = $data;
            $this->status = $status;
        }
        public function get_data() { return $this->data; }
        public function get_status() { return $this->status; }
    }
}
if (!function_exists('error_log')) {
    // En algunos entornos de CI puede estar limitado, nos aseguramos de que exista
    function error_log($message) { /* no-op */ }
}

// Minimal user lookup and sanitization helpers used by CompanyProfile
if (!function_exists('get_user_by')) {
    function get_user_by($field, $value) {
        foreach (($GLOBALS['__ac_users'] ?? []) as $user) {
            if ($field === 'login' && isset($user->user_login) && $user->user_login === $value) return $user;
            if ($field === 'slug' && isset($user->user_nicename) && $user->user_nicename === $value) return $user;
        }
        return false;
    }
}
if (!function_exists('sanitize_title')) {
    function sanitize_title($title) {
        $title = strtolower(trim((string)$title));
        $title = preg_replace('/[^a-z0-9\-\s]+/', '', $title);
        $title = preg_replace('/\s+/', '-', $title);
        return trim($title, '-');
    }
}

// 4) Definir constantes del plugin si no están definidas (algunas rutas)
if (!defined('AGROCHAMBA_PLUGIN_DIR')) {
    define('AGROCHAMBA_PLUGIN_DIR', dirname(__DIR__));
}

// 5) Cargar bootstrap del plugin para registrar autoloader y helpers
require_once AGROCHAMBA_PLUGIN_DIR . '/config/bootstrap.php';
