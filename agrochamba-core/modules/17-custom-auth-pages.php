<?php
/**
 * =============================================================
 * MÓDULO 17: PÁGINAS DE AUTENTICACIÓN PERSONALIZADAS
 * =============================================================
 * 
 * Redirige las páginas de login y registro de WordPress por defecto
 * a nuestras páginas personalizadas con diseño similar a la app móvil
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 0. DESACTIVAR BRICKS BUILDER EN PÁGINAS DE AUTENTICACIÓN
// ==========================================
if (!function_exists('agrochamba_disable_bricks_for_auth_pages')) {
    function agrochamba_disable_bricks_for_auth_pages() {
        // Verificar si estamos en una página de autenticación
        $page_slug = '';
        if (is_page()) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->post_name)) {
                $page_slug = $queried_object->post_name;
            }
        }
        
        if (empty($page_slug)) {
            $page_slug = get_query_var('pagename');
        }
        
        $auth_pages = array('login', 'registro', 'recuperar-contrasena');
        
        if (in_array($page_slug, $auth_pages)) {
            // Desactivar Bricks Builder
            add_filter('bricks/builder/is_frontend', '__return_false', 999);
            add_filter('bricks/frontend/render_content', '__return_false', 999);
            add_filter('bricks/builder/is_active', '__return_false', 999);
            
            // Desactivar scripts y estilos de Bricks
            add_action('wp_enqueue_scripts', function() {
                wp_dequeue_style('bricks-frontend');
                wp_dequeue_script('bricks-frontend');
                wp_dequeue_style('bricks-builder');
                wp_dequeue_script('bricks-builder');
            }, 999);
        }
    }
    add_action('template_redirect', 'agrochamba_disable_bricks_for_auth_pages', 1);
}

// ==========================================
// 1. REDIRIGIR LOGIN Y REGISTRO DE WORDPRESS
// ==========================================
if (!function_exists('agrochamba_redirect_default_login')) {
    function agrochamba_redirect_default_login() {
        // Solo redirigir si no estamos en admin y no es una petición AJAX
        if (!is_admin() && !wp_doing_ajax()) {
            // Redirigir wp-login.php a nuestra página personalizada
            if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
                $action = isset($_GET['action']) ? $_GET['action'] : 'login';
                
                // SEGURIDAD: Validar token secreto o palabra personalizada para acceso de administradores
                $admin_token = get_option('agrochamba_admin_login_token', '');
                $admin_keyword = get_option('agrochamba_admin_keyword', ''); // Palabra personalizada
                $token_created = get_option('agrochamba_admin_login_token_created', 0);
                
                // Si no existe token, generar uno automáticamente
                // NOTA: El token NO expira automáticamente para evitar bloquear administradores
                // que no han ingresado en mucho tiempo. Se puede regenerar manualmente desde el admin.
                if (empty($admin_token)) {
                    $admin_token = wp_generate_password(32, false);
                    update_option('agrochamba_admin_login_token', $admin_token);
                    update_option('agrochamba_admin_login_token_created', time());
                    
                    // Log de seguridad
                    agrochamba_log_security_event('admin_token_generated', array(
                        'reason' => 'initial_generation',
                        'ip' => agrochamba_get_client_ip()
                    ));
                }
                
                // PERMITIR acceso a wp-login.php SOLO si:
                // 1. Tiene el token secreto válido (para administradores)
                // 2. Tiene la palabra clave personalizada válida (más fácil de recordar)
                // 3. Ya está autenticado como administrador
                // 4. Es una acción de reset de contraseña (necesaria para seguridad)
                // 5. Tiene un token de emergencia válido (para casos donde el token principal se perdió)
                $has_valid_token = isset($_GET['token']) && hash_equals($admin_token, $_GET['token']);
                
                // Validar palabra clave personalizada (comparar hash)
                $has_valid_keyword = false;
                if (!empty($admin_keyword) && isset($_GET['key'])) {
                    $provided_key = sanitize_text_field($_GET['key']);
                    // Verificar usando wp_check_password para comparar con el hash
                    $has_valid_keyword = wp_check_password($provided_key, $admin_keyword);
                }
                $is_authenticated_admin = is_user_logged_in() && current_user_can('manage_options');
                $is_password_reset = in_array($action, array('rp', 'resetpass'));
                
                // Token de emergencia (solo para casos críticos, se genera manualmente desde la base de datos)
                $emergency_token = get_option('agrochamba_admin_emergency_token', '');
                $has_emergency_token = !empty($emergency_token) && isset($_GET['emergency']) && hash_equals($emergency_token, $_GET['emergency']);
                
                if ($has_valid_token || $has_valid_keyword || $is_authenticated_admin || $is_password_reset || $has_emergency_token) {
                    // Permitir acceso directo a wp-login.php para administradores
                    // Registrar acceso de admin para auditoría
                    if ($has_valid_token || $has_valid_keyword || $is_authenticated_admin || $has_emergency_token) {
                        $client_ip = agrochamba_get_client_ip();
                        $access_method = 'unknown';
                        if ($has_emergency_token) {
                            $access_method = 'emergency_token';
                        } elseif ($has_valid_keyword) {
                            $access_method = 'custom_keyword';
                        } elseif ($has_valid_token) {
                            $access_method = 'regular_token';
                        } elseif ($is_authenticated_admin) {
                            $access_method = 'authenticated';
                        }
                        
                        agrochamba_log_security_event('admin_login_access', array(
                            'ip' => $client_ip,
                            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                            'timestamp' => current_time('mysql'),
                            'method' => $access_method
                        ));
                    }
                    return;
                }
                
                // Para todos los demás casos, redirigir a página personalizada
                if ($action === 'register') {
                    // Redirigir a página de registro personalizada
                    $register_page = get_page_by_path('registro');
                    if ($register_page) {
                        wp_redirect(get_permalink($register_page->ID));
                        exit;
                    }
                } elseif ($action === 'lostpassword') {
                    // Redirigir a página de recuperación de contraseña personalizada
                    $lostpassword_page = get_page_by_path('recuperar-contrasena');
                    if ($lostpassword_page) {
                        wp_redirect(get_permalink($lostpassword_page->ID));
                        exit;
                    }
                } else {
                    // Redirigir a página de login personalizada (SOLO para usuarios finales)
                    $login_page = get_page_by_path('login');
                    if ($login_page) {
                        wp_redirect(get_permalink($login_page->ID));
                        exit;
                    }
                }
            }
        }
    }
    add_action('init', 'agrochamba_redirect_default_login', 1);
}

// ==========================================
// 2. FILTROS PARA URLS DE LOGIN Y REGISTRO
// ==========================================
if (!function_exists('agrochamba_custom_login_url')) {
    function agrochamba_custom_login_url($login_url, $redirect) {
        $login_page = get_page_by_path('login');
        if ($login_page) {
            $url = get_permalink($login_page->ID);
            if ($redirect) {
                $url = add_query_arg('redirect_to', urlencode($redirect), $url);
            }
            return $url;
        }
        return $login_url;
    }
    add_filter('login_url', 'agrochamba_custom_login_url', 10, 2);
}

// ==========================================
// FILTRO PARA REDIRECCIÓN DESPUÉS DEL LOGIN
// ==========================================
if (!function_exists('agrochamba_login_redirect')) {
    /**
     * Redirigir usuarios al listado de trabajos después del login (como en la app móvil)
     */
    function agrochamba_login_redirect($redirect_to, $request_redirect_to, $user) {
        // Si hay un error en el login, no hacer nada
        if (is_wp_error($user)) {
            return $redirect_to;
        }
        
        // Obtener URL del archivo de trabajos
        $trabajos_url = get_post_type_archive_link('trabajo');
        $default_url = $trabajos_url ? $trabajos_url : home_url();
        
        // Si el usuario es administrador y específicamente quiere ir al admin, permitirlo
        $is_admin = in_array('administrator', $user->roles);
        if ($is_admin && !empty($request_redirect_to) && strpos($request_redirect_to, admin_url()) !== false) {
            return $request_redirect_to;
        }
        
        // Para todos los demás casos, ir al listado de trabajos
        // Si redirect_to es el home o está vacío, usar el listado de trabajos
        if (empty($redirect_to) || $redirect_to === home_url() || $redirect_to === admin_url()) {
            return $default_url;
        }
        
        // Si hay un redirect_to específico que no es home ni admin, respetarlo
        // (por ejemplo, si el usuario intentó acceder a una página protegida)
        if (!empty($request_redirect_to) && $request_redirect_to !== home_url() && $request_redirect_to !== admin_url()) {
            return $request_redirect_to;
        }
        
        // Por defecto, ir al listado de trabajos
        return $default_url;
    }
    add_filter('login_redirect', 'agrochamba_login_redirect', 10, 3);
}

if (!function_exists('agrochamba_custom_registration_url')) {
    function agrochamba_custom_registration_url($register_url) {
        $register_page = get_page_by_path('registro');
        if ($register_page) {
            return get_permalink($register_page->ID);
        }
        return $register_url;
    }
    add_filter('register_url', 'agrochamba_custom_registration_url');
}

if (!function_exists('agrochamba_custom_lostpassword_url')) {
    function agrochamba_custom_lostpassword_url($lostpassword_url, $redirect) {
        $lostpassword_page = get_page_by_path('recuperar-contrasena');
        if ($lostpassword_page) {
            $url = get_permalink($lostpassword_page->ID);
            if ($redirect) {
                $url = add_query_arg('redirect_to', urlencode($redirect), $url);
            }
            return $url;
        }
        return $lostpassword_url;
    }
    add_filter('lostpassword_url', 'agrochamba_custom_lostpassword_url', 10, 2);
}

// ==========================================
// 3. MANEJAR REGISTRO CON ROLES PERSONALIZADOS
// ==========================================
if (!function_exists('agrochamba_handle_custom_registration')) {
    function agrochamba_handle_custom_registration() {
        // Solo procesar si es POST y viene de nuestro formulario
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['agrochamba-register-nonce'])) {
            return;
        }

        // Verificar nonce
        if (!isset($_POST['agrochamba-register-nonce']) || !wp_verify_nonce($_POST['agrochamba-register-nonce'], 'agrochamba-register')) {
            wp_die('Error de seguridad. Por favor, intenta nuevamente.');
        }

        $user_login = isset($_POST['user_login']) ? sanitize_user($_POST['user_login']) : '';
        $user_email = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
        $user_pass = isset($_POST['user_pass']) ? $_POST['user_pass'] : '';
        $user_role = isset($_POST['user_role']) ? sanitize_text_field($_POST['user_role']) : 'subscriber';

        // Validaciones
        if (empty($user_login) || empty($user_email) || empty($user_pass)) {
            wp_redirect(add_query_arg('registration', 'failed', wp_registration_url()));
            exit;
        }

        if (!is_email($user_email)) {
            wp_redirect(add_query_arg('registration', 'failed', wp_registration_url()));
            exit;
        }

        if (strlen($user_pass) < 8) {
            wp_redirect(add_query_arg('registration', 'failed', wp_registration_url()));
            exit;
        }

        // Verificar si el usuario ya existe
        if (username_exists($user_login)) {
            wp_redirect(add_query_arg('registration', 'failed', $register_url));
            exit;
        }

        if (email_exists($user_email)) {
            wp_redirect(add_query_arg('registration', 'failed', $register_url));
            exit;
        }

        // Validar rol (solo subscriber o employer)
        $allowed_roles = array('subscriber', 'employer');
        if (!in_array($user_role, $allowed_roles)) {
            $user_role = 'subscriber'; // Por defecto
        }

        // Crear usuario
        $user_id = wp_create_user($user_login, $user_pass, $user_email);

        if (is_wp_error($user_id)) {
            wp_redirect(add_query_arg('registration', 'failed', $register_url));
            exit;
        }

        // Asignar rol
        $user = new WP_User($user_id);
        $user->set_role($user_role);

        // Si es employer, crear perfil de empresa automáticamente
        if ($user_role === 'employer') {
            // Crear CPT Empresa asociado
            $empresa_post = array(
                'post_title' => $user_login,
                'post_type' => 'empresa',
                'post_status' => 'publish',
                'post_author' => $user_id,
            );
            
            $empresa_id = wp_insert_post($empresa_post);
            
            if ($empresa_id && !is_wp_error($empresa_id)) {
                // Guardar relación usuario-empresa
                update_post_meta($empresa_id, '_empresa_user_id', $user_id);
                update_user_meta($user_id, 'empresa_cpt_id', $empresa_id);
                
                // Crear término de taxonomía empresa
                $empresa_term = wp_insert_term(
                    $user_login,
                    'empresa',
                    array(
                        'description' => 'Empresa: ' . $user_login,
                        'slug' => sanitize_title($user_login)
                    )
                );
                
                if (!is_wp_error($empresa_term)) {
                    $empresa_term_id = $empresa_term['term_id'];
                    wp_set_object_terms($empresa_id, array($empresa_term_id), 'empresa', false);
                    update_user_meta($user_id, 'empresa_term_id', $empresa_term_id);
                }
            }
        }

        // Auto-login después del registro
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        // Redirigir al listado de trabajos (como en la app móvil)
        $trabajos_url = get_post_type_archive_link('trabajo');
        if ($trabajos_url) {
            // Agregar parámetro para mostrar mensaje de bienvenida
            $redirect_url = add_query_arg('welcome', '1', $trabajos_url);
            wp_redirect($redirect_url);
        } else {
            // Fallback: si no existe el archivo de trabajos, ir al home
            wp_redirect(home_url());
        }
        exit;
    }
    add_action('init', 'agrochamba_handle_custom_registration', 1);
}

// ==========================================
// 4. SISTEMA DE SEGURIDAD PARA LOGIN
// ==========================================
if (!function_exists('agrochamba_get_client_ip')) {
    /**
     * Obtener la IP real del cliente
     */
    function agrochamba_get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP',        // Nginx proxy
            'HTTP_X_FORWARDED_FOR',  // Proxy/Load balancer
            'REMOTE_ADDR'            // IP directa
        );
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Si es X-Forwarded-For, tomar la primera IP
                if ($key === 'HTTP_X_FORWARDED_FOR') {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                // Validar IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
}

if (!function_exists('agrochamba_check_login_rate_limit')) {
    /**
     * Verificar rate limiting para login
     * Bloquea después de 5 intentos fallidos en 15 minutos
     */
    function agrochamba_check_login_rate_limit($ip_address) {
        $transient_key = 'agrochamba_login_attempts_' . md5($ip_address);
        $attempts = get_transient($transient_key);
        
        if ($attempts === false) {
            $attempts = 0;
        }
        
        // Límite: 5 intentos en 15 minutos
        $max_attempts = 5;
        $lockout_time = 15 * MINUTE_IN_SECONDS; // 15 minutos
        
        if ($attempts >= $max_attempts) {
            // Bloquear por 15 minutos
            set_transient($transient_key, $attempts, $lockout_time);
            return array(
                'blocked' => true,
                'remaining_time' => $lockout_time,
                'attempts' => $attempts
            );
        }
        
        return array(
            'blocked' => false,
            'attempts' => $attempts,
            'remaining' => $max_attempts - $attempts
        );
    }
}

if (!function_exists('agrochamba_increment_login_attempts')) {
    /**
     * Incrementar contador de intentos fallidos
     */
    function agrochamba_increment_login_attempts($ip_address, $username = '') {
        $transient_key = 'agrochamba_login_attempts_' . md5($ip_address);
        $attempts = get_transient($transient_key);
        
        if ($attempts === false) {
            $attempts = 0;
        }
        
        $attempts++;
        $lockout_time = 15 * MINUTE_IN_SECONDS;
        
        set_transient($transient_key, $attempts, $lockout_time);
        
        // Registrar intento fallido en logs
        agrochamba_log_failed_login($ip_address, $username);
        
        return $attempts;
    }
}

if (!function_exists('agrochamba_reset_login_attempts')) {
    /**
     * Resetear contador de intentos después de login exitoso
     */
    function agrochamba_reset_login_attempts($ip_address) {
        $transient_key = 'agrochamba_login_attempts_' . md5($ip_address);
        delete_transient($transient_key);
    }
}

if (!function_exists('agrochamba_log_failed_login')) {
    /**
     * Registrar intentos de login fallidos para auditoría
     */
    function agrochamba_log_failed_login($ip_address, $username = '') {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'ip' => $ip_address,
            'username' => sanitize_text_field($username),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'referer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : ''
        );
        
        // Guardar en opción de WordPress (últimos 100 intentos)
        $logs = get_option('agrochamba_failed_login_logs', array());
        $logs[] = $log_entry;
        
        // Mantener solo los últimos 100 registros
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option('agrochamba_failed_login_logs', $logs);
    }
}

if (!function_exists('agrochamba_validate_login_input')) {
    /**
     * Validar entrada del formulario de login
     */
    function agrochamba_validate_login_input($username, $password) {
        $errors = array();
        
        // Validar username (email o nombre de usuario)
        if (empty($username)) {
            $errors[] = 'El usuario o correo electrónico es requerido.';
        } elseif (strlen($username) > 100) {
            $errors[] = 'El usuario o correo electrónico es demasiado largo.';
        } elseif (preg_match('/[<>"\']/', $username)) {
            $errors[] = 'Caracteres no permitidos en el usuario.';
        }
        
        // Validar password
        if (empty($password)) {
            $errors[] = 'La contraseña es requerida.';
        } elseif (strlen($password) > 128) {
            $errors[] = 'La contraseña es demasiado larga.';
        }
        
        // Validar formato de email si se proporciona como email
        if (!empty($username) && is_email($username) && !filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El formato del correo electrónico no es válido.';
        }
        
        return $errors;
    }
}

// ==========================================
// LOGGING DE SEGURIDAD PARA AUDITORÍA
// ==========================================
if (!function_exists('agrochamba_log_security_event')) {
    /**
     * Registrar eventos de seguridad para auditoría
     */
    function agrochamba_log_security_event($event_type, $data = array()) {
        // Limitar tamaño del log (últimos 100 eventos)
        $log_key = 'agrochamba_security_log';
        $log = get_option($log_key, array());
        
        // Agregar nuevo evento
        $log[] = array(
            'type' => $event_type,
            'data' => $data,
            'timestamp' => current_time('mysql')
        );
        
        // Mantener solo los últimos 100 eventos
        if (count($log) > 100) {
            $log = array_slice($log, -100);
        }
        
        update_option($log_key, $log);
    }
}

// ==========================================
// VALIDACIÓN ADICIONAL DE SEGURIDAD
// ==========================================
if (!function_exists('agrochamba_validate_request_security')) {
    /**
     * Validar seguridad de la petición
     */
    function agrochamba_validate_request_security() {
        // Validar User-Agent (debe existir)
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            agrochamba_log_security_event('suspicious_request', array(
                'reason' => 'missing_user_agent',
                'ip' => agrochamba_get_client_ip()
            ));
            return false;
        }
        
        // Validar que no sea un bot común (excepto Googlebot)
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $bot_patterns = array('bot', 'crawler', 'spider', 'scraper');
        foreach ($bot_patterns as $pattern) {
            if (strpos($user_agent, $pattern) !== false && strpos($user_agent, 'googlebot') === false) {
                // Permitir Googlebot pero bloquear otros bots en login
                return false;
            }
        }
        
        return true;
    }
}

// ==========================================
// 5. MANEJAR LOGIN PERSONALIZADO CON SEGURIDAD
// ==========================================
if (!function_exists('agrochamba_handle_custom_login')) {
    function agrochamba_handle_custom_login() {
        // Solo procesar si es POST y viene de nuestro formulario
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['agrochamba-login-nonce'])) {
            return;
        }

        // Validar seguridad de la petición
        if (!agrochamba_validate_request_security()) {
            wp_die('Solicitud inválida.', 'Error de Seguridad', array('response' => 403));
        }

        // Honeypot field: si está lleno, es un bot
        if (!empty($_POST['website'])) {
            // Bot detectado, no procesar
            agrochamba_log_security_event('bot_detected', array(
                'ip' => agrochamba_get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
            ));
            wp_die('Acceso denegado.', 'Error de Seguridad', array('response' => 403));
        }

        // Obtener IP del cliente
        $client_ip = agrochamba_get_client_ip();
        
        // Verificar rate limiting ANTES de procesar
        $rate_limit = agrochamba_check_login_rate_limit($client_ip);
        if ($rate_limit['blocked']) {
            // Usuario bloqueado por demasiados intentos
            agrochamba_log_security_event('rate_limit_exceeded', array(
                'ip' => $client_ip,
                'attempts' => $rate_limit['attempts']
            ));
            
            $login_page = get_page_by_path('login');
            $login_url = $login_page ? get_permalink($login_page->ID) : wp_login_url();
            $redirect_url = add_query_arg('login', 'blocked', $login_url);
            wp_redirect($redirect_url);
            exit;
        }

        // Verificar nonce CSRF
        if (!isset($_POST['agrochamba-login-nonce']) || !wp_verify_nonce($_POST['agrochamba-login-nonce'], 'agrochamba-login')) {
            // Incrementar intentos por nonce inválido
            agrochamba_increment_login_attempts($client_ip, '');
            
            agrochamba_log_security_event('csrf_attack', array(
                'ip' => $client_ip,
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
            ));
            
            wp_die('Error de seguridad. Por favor, recarga la página e intenta nuevamente.', 'Error de Seguridad', array('response' => 403));
        }

        // Sanitizar y validar entrada
        $user_login = isset($_POST['log']) ? sanitize_text_field($_POST['log']) : '';
        $user_pass = isset($_POST['pwd']) ? $_POST['pwd'] : '';
        $rememberme = isset($_POST['rememberme']) ? true : false;
        
        // Por defecto, redirigir al listado de trabajos (como en la app móvil)
        $trabajos_url = get_post_type_archive_link('trabajo');
        $default_redirect = $trabajos_url ? $trabajos_url : home_url();
        $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : $default_redirect;
        
        // Validar que redirect_to sea del mismo dominio
        $redirect_host = parse_url($redirect_to, PHP_URL_HOST);
        $site_host = parse_url(home_url(), PHP_URL_HOST);
        if ($redirect_host !== $site_host) {
            $redirect_to = $default_redirect;
        }

        // Obtener URL de login personalizada
        $login_page = get_page_by_path('login');
        $login_url = $login_page ? get_permalink($login_page->ID) : wp_login_url();

        // Validar entrada
        $validation_errors = agrochamba_validate_login_input($user_login, $user_pass);
        if (!empty($validation_errors)) {
            agrochamba_increment_login_attempts($client_ip, $user_login);
            $redirect_url = add_query_arg('login', 'invalid', $login_url);
            if ($redirect_to !== home_url()) {
                $redirect_url = add_query_arg('redirect_to', urlencode($redirect_to), $redirect_url);
            }
            wp_redirect($redirect_url);
            exit;
        }

        // Verificar que los campos no estén vacíos después de sanitizar
        if (empty($user_login) || empty($user_pass)) {
            agrochamba_increment_login_attempts($client_ip, $user_login);
            $redirect_url = add_query_arg('login', 'empty', $login_url);
            if ($redirect_to !== home_url()) {
                $redirect_url = add_query_arg('redirect_to', urlencode($redirect_to), $redirect_url);
            }
            wp_redirect($redirect_url);
            exit;
        }

        // Intentar login
        $user = wp_authenticate($user_login, $user_pass);

        if (is_wp_error($user)) {
            // Login fallido - incrementar contador
            agrochamba_increment_login_attempts($client_ip, $user_login);
            
            // Registrar intento fallido
            agrochamba_log_security_event('failed_login', array(
                'ip' => $client_ip,
                'username' => substr($user_login, 0, 3) . '***', // Ocultar username completo
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
            ));
            
            // No revelar si el usuario existe o no (prevenir enumeración)
            $redirect_url = add_query_arg('login', 'failed', $login_url);
            if ($redirect_to !== home_url()) {
                $redirect_url = add_query_arg('redirect_to', urlencode($redirect_to), $redirect_url);
            }
            wp_redirect($redirect_url);
            exit;
        }

        // Login exitoso - resetear contador de intentos
        agrochamba_reset_login_attempts($client_ip);
        
        // Verificar que el usuario no esté bloqueado
        if (get_user_meta($user->ID, 'agrochamba_account_locked', true)) {
            wp_die('Tu cuenta ha sido bloqueada. Por favor, contacta al administrador.', 'Cuenta Bloqueada', array('response' => 403));
        }
        
        // Verificar que el usuario esté activo (user_status 0 = activo en WordPress)
        if ($user->user_status != 0) {
            wp_die('Tu cuenta no está activa. Por favor, contacta al administrador.', 'Cuenta Inactiva', array('response' => 403));
        }

        // Login exitoso
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $rememberme);
        
        // Registrar login exitoso (para auditoría)
        update_user_meta($user->ID, 'agrochamba_last_login', current_time('mysql'));
        update_user_meta($user->ID, 'agrochamba_last_login_ip', $client_ip);
        
        // Log de seguridad
        agrochamba_log_security_event('successful_login', array(
            'user_id' => $user->ID,
            'username' => $user->user_login,
            'ip' => $client_ip,
            'is_admin' => in_array('administrator', $user->roles)
        ));
        
        // Determinar redirección final
        $is_admin = in_array('administrator', $user->roles);
        $trabajos_url = get_post_type_archive_link('trabajo');
        $default_url = $trabajos_url ? $trabajos_url : home_url();
        
        // Si el redirect_to no está especificado o es el home, usar el listado de trabajos
        if (empty($redirect_to) || $redirect_to === home_url()) {
            $redirect_to = $default_url;
        }
        
        // Para administradores: solo redirigir al admin si explícitamente se solicita
        // De lo contrario, también van al listado de trabajos
        if ($is_admin && strpos($redirect_to, admin_url()) !== false) {
            // Administrador quiere ir al admin, permitirlo
        } else {
            // Todos los demás (incluidos admins sin redirect_to específico) van al listado de trabajos
            if ($redirect_to === home_url()) {
                $redirect_to = $default_url;
            }
        }
        
        // Redirigir de forma segura
        wp_safe_redirect($redirect_to);
        exit;
    }
    add_action('init', 'agrochamba_handle_custom_login', 1);
}

// ==========================================
// 6. HEADERS DE SEGURIDAD PARA PÁGINAS DE AUTENTICACIÓN
// ==========================================
if (!function_exists('agrochamba_add_security_headers')) {
    function agrochamba_add_security_headers() {
        $page_slug = '';
        if (is_page()) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->post_name)) {
                $page_slug = $queried_object->post_name;
            }
        }
        
        if (empty($page_slug)) {
            $page_slug = get_query_var('pagename');
        }
        
        $auth_pages = array('login', 'registro', 'recuperar-contrasena');
        
        if (in_array($page_slug, $auth_pages)) {
            // Prevenir clickjacking
            header('X-Frame-Options: DENY');
            
            // Prevenir MIME type sniffing
            header('X-Content-Type-Options: nosniff');
            
            // XSS Protection
            header('X-XSS-Protection: 1; mode=block');
            
            // Referrer Policy
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            // Content Security Policy básico
            $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self';";
            header("Content-Security-Policy: {$csp}");
        }
    }
    add_action('template_redirect', 'agrochamba_add_security_headers', 1);
}

// ==========================================
// 7. LIMITAR INTENTOS DE LOGIN POR USUARIO
// ==========================================
if (!function_exists('agrochamba_limit_login_by_username')) {
    add_filter('authenticate', function($user, $username, $password) {
        if (empty($username)) {
            return $user;
        }
        
        // Verificar si el usuario existe y está bloqueado
        $user_obj = get_user_by('login', $username);
        if (!$user_obj) {
            $user_obj = get_user_by('email', $username);
        }
        
        if ($user_obj && get_user_meta($user_obj->ID, 'agrochamba_account_locked', true)) {
            return new WP_Error('account_locked', 'Tu cuenta ha sido bloqueada. Por favor, contacta al administrador.');
        }
        
        return $user;
    }, 30, 3);
}

// ==========================================
// 8. DESHABILITAR XML-RPC (común vector de ataque)
// ==========================================
if (!function_exists('agrochamba_disable_xmlrpc')) {
    add_filter('xmlrpc_enabled', '__return_false');
    add_filter('wp_headers', function($headers) {
        unset($headers['X-Pingback']);
        return $headers;
    });
}

// ==========================================
// 9. CREAR PÁGINAS AUTOMÁTICAMENTE AL ACTIVAR
// ==========================================
if (!function_exists('agrochamba_create_auth_pages')) {
    function agrochamba_create_auth_pages() {
        // Crear página de login
        $login_page = get_page_by_path('login');
        if (!$login_page) {
            $login_page_id = wp_insert_post(array(
                'post_title' => 'Iniciar Sesión',
                'post_name' => 'login',
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'page'
            ));
            
            if ($login_page_id && !is_wp_error($login_page_id)) {
                // Asignar template después de crear la página
                update_post_meta($login_page_id, '_wp_page_template', 'login.php');
                update_option('agrochamba_login_page_id', $login_page_id);
            }
        } else {
            // Si la página ya existe, asegurar que tenga el template correcto
            $current_template = get_page_template_slug($login_page->ID);
            if ($current_template !== 'login.php') {
                update_post_meta($login_page->ID, '_wp_page_template', 'login.php');
            }
        }

        // Crear página de publicar trabajo
        $publicar_trabajo_page = get_page_by_path('publicar-trabajo');
        if (!$publicar_trabajo_page) {
            $publicar_trabajo_page_id = wp_insert_post(array(
                'post_title' => 'Publicar Trabajo',
                'post_name' => 'publicar-trabajo',
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'page'
            ));
            
            if ($publicar_trabajo_page_id && !is_wp_error($publicar_trabajo_page_id)) {
                // Asignar template después de crear la página
                update_post_meta($publicar_trabajo_page_id, '_wp_page_template', 'publicar-trabajo.php');
                update_option('agrochamba_publicar_trabajo_page_id', $publicar_trabajo_page_id);
            }
        } else {
            // Si la página ya existe, asegurar que tenga el template correcto
            $current_template = get_page_template_slug($publicar_trabajo_page->ID);
            if ($current_template !== 'publicar-trabajo.php') {
                update_post_meta($publicar_trabajo_page->ID, '_wp_page_template', 'publicar-trabajo.php');
            }
        }

        // Crear página de registro
        $register_page = get_page_by_path('registro');
        if (!$register_page) {
            $register_page_id = wp_insert_post(array(
                'post_title' => 'Registro',
                'post_name' => 'registro',
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'page'
            ));
            
            if ($register_page_id && !is_wp_error($register_page_id)) {
                // Asignar template después de crear la página
                update_post_meta($register_page_id, '_wp_page_template', 'register.php');
                update_option('agrochamba_register_page_id', $register_page_id);
            }
        } else {
            // Si la página ya existe, asegurar que tenga el template correcto
            $current_template = get_page_template_slug($register_page->ID);
            if ($current_template !== 'register.php') {
                update_post_meta($register_page->ID, '_wp_page_template', 'register.php');
            }
        }

        // Crear página de recuperación de contraseña
        $lostpassword_page = get_page_by_path('recuperar-contrasena');
        if (!$lostpassword_page) {
            $lostpassword_page_id = wp_insert_post(array(
                'post_title' => 'Recuperar Contraseña',
                'post_name' => 'recuperar-contrasena',
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'page'
            ));
            
            if ($lostpassword_page_id && !is_wp_error($lostpassword_page_id)) {
                // Asignar template después de crear la página
                update_post_meta($lostpassword_page_id, '_wp_page_template', 'lostpassword.php');
                update_option('agrochamba_lostpassword_page_id', $lostpassword_page_id);
            }
        } else {
            // Si la página ya existe, asegurar que tenga el template correcto
            $current_template = get_page_template_slug($lostpassword_page->ID);
            if ($current_template !== 'lostpassword.php') {
                update_post_meta($lostpassword_page->ID, '_wp_page_template', 'lostpassword.php');
            }
        }
    }
    add_action('after_switch_theme', 'agrochamba_create_auth_pages');
    add_action('agrochamba_plugin_activated', 'agrochamba_create_auth_pages');
    
    // Crear páginas al cargar el módulo si no existen
    add_action('init', function() {
        if (!get_option('agrochamba_auth_pages_created')) {
            agrochamba_create_auth_pages();
            update_option('agrochamba_auth_pages_created', true);
        }
    }, 20);
    
    // Forzar recreación de páginas si se solicita (para diagnóstico)
    add_action('admin_init', function() {
        if (isset($_GET['agrochamba_recreate_auth_pages']) && current_user_can('manage_options')) {
            delete_option('agrochamba_auth_pages_created');
            agrochamba_create_auth_pages();
            update_option('agrochamba_auth_pages_created', true);
            wp_redirect(admin_url('edit.php?post_type=page&agrochamba_pages_created=1'));
            exit;
        }
    });
}

// ==========================================
// PÁGINA DE CONFIGURACIÓN DE ACCESO DE ADMINISTRADOR
// ==========================================
if (!function_exists('agrochamba_add_admin_access_settings_page')) {
    // Agregar página de configuración en el menú de Ajustes
    add_action('admin_menu', function() {
        add_options_page(
            'Acceso de Administrador',
            'Acceso de Admin',
            'manage_options',
            'agrochamba-admin-access',
            'agrochamba_render_admin_access_settings_page'
        );
    });
    
    // Procesar formularios de la página de configuración
    add_action('admin_init', function() {
        // Solo procesar para administradores
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Verificar que estamos en nuestra página de configuración
        if (!isset($_GET['page']) || $_GET['page'] !== 'agrochamba-admin-access') {
            return;
        }
        
        // Manejar configuración de palabra clave personalizada
        if (isset($_POST['set_admin_keyword']) && wp_verify_nonce($_POST['_wpnonce'], 'set_admin_keyword')) {
            $new_keyword = isset($_POST['admin_keyword']) ? sanitize_text_field($_POST['admin_keyword']) : '';
            
            if (!empty($new_keyword) && strlen($new_keyword) >= 6) {
                // Hash de la palabra clave para mayor seguridad usando wp_hash_password
                $hashed_keyword = wp_hash_password($new_keyword);
                update_option('agrochamba_admin_keyword', $hashed_keyword);
                
                agrochamba_log_security_event('admin_keyword_set', array(
                    'user_id' => get_current_user_id(),
                    'ip' => agrochamba_get_client_ip()
                ));
                
                wp_redirect(admin_url('options-general.php?page=agrochamba-admin-access&keyword_set=1'));
                exit;
            } elseif (!empty($new_keyword) && strlen($new_keyword) < 6) {
                wp_redirect(admin_url('options-general.php?page=agrochamba-admin-access&keyword_error=short'));
                exit;
            }
        }
        
        // Manejar eliminación de palabra clave
        if (isset($_POST['remove_keyword']) && wp_verify_nonce($_POST['_wpnonce'], 'set_admin_keyword')) {
            delete_option('agrochamba_admin_keyword');
            agrochamba_log_security_event('admin_keyword_removed', array(
                'user_id' => get_current_user_id(),
                'ip' => agrochamba_get_client_ip()
            ));
            wp_redirect(admin_url('options-general.php?page=agrochamba-admin-access&keyword_removed=1'));
            exit;
        }
        
        // Manejar regeneración manual del token
        if (isset($_GET['regenerate_admin_token']) && wp_verify_nonce($_GET['_wpnonce'], 'regenerate_admin_token')) {
            $new_token = wp_generate_password(32, false);
            update_option('agrochamba_admin_login_token', $new_token);
            update_option('agrochamba_admin_login_token_created', time());
            
            agrochamba_log_security_event('admin_token_regenerated', array(
                'reason' => 'manual_regeneration',
                'user_id' => get_current_user_id(),
                'ip' => agrochamba_get_client_ip()
            ));
            
            wp_redirect(admin_url('options-general.php?page=agrochamba-admin-access&token_regenerated=1'));
            exit;
        }
        
        // Manejar generación de token de emergencia
        if (isset($_GET['generate_emergency_token']) && wp_verify_nonce($_GET['_wpnonce'], 'generate_emergency_token')) {
            $new_emergency_token = wp_generate_password(32, false);
            update_option('agrochamba_admin_emergency_token', $new_emergency_token);
            agrochamba_log_security_event('emergency_token_generated', array(
                'user_id' => get_current_user_id(),
                'ip' => agrochamba_get_client_ip()
            ));
            wp_redirect(admin_url('options-general.php?page=agrochamba-admin-access&emergency_token_generated=1'));
            exit;
        }
    });
    
    // Función para renderizar la página de configuración
    function agrochamba_render_admin_access_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Obtener valores actuales
        $admin_token = get_option('agrochamba_admin_login_token', '');
        $admin_keyword = get_option('agrochamba_admin_keyword', '');
        $emergency_token = get_option('agrochamba_admin_emergency_token', '');
        $token_created = get_option('agrochamba_admin_login_token_created', 0);
        $has_keyword = !empty($admin_keyword);
        
        // Generar token si no existe
        if (empty($admin_token)) {
            $admin_token = wp_generate_password(32, false);
            update_option('agrochamba_admin_login_token', $admin_token);
            update_option('agrochamba_admin_login_token_created', time());
            $token_created = time();
        }
        
        // Calcular información del token
        $token_info = '';
        if ($token_created > 0) {
            $days_since_creation = floor((time() - $token_created) / DAY_IN_SECONDS);
            if ($days_since_creation == 0) {
                $token_info = 'Creado hoy';
            } elseif ($days_since_creation == 1) {
                $token_info = 'Creado hace 1 día';
            } else {
                $token_info = sprintf('Creado hace %d días', $days_since_creation);
            }
        }
        
        $login_url = home_url('/wp-login.php?token=' . $admin_token);
        $regenerate_url = wp_nonce_url(admin_url('options-general.php?page=agrochamba-admin-access&regenerate_admin_token=1'), 'regenerate_admin_token');
        
        ?>
        <div class="wrap">
            <h1>🔐 Acceso de Administrador</h1>
            
            <?php if (isset($_GET['token_regenerated'])): ?>
                <div class="notice notice-success is-dismissible"><p>✅ Token regenerado exitosamente</p></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['keyword_set'])): ?>
                <div class="notice notice-success is-dismissible"><p>✅ Palabra clave configurada exitosamente</p></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['keyword_removed'])): ?>
                <div class="notice notice-success is-dismissible"><p>✅ Palabra clave eliminada</p></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['keyword_error']) && $_GET['keyword_error'] === 'short'): ?>
                <div class="notice notice-error is-dismissible"><p>❌ La palabra clave debe tener al menos 6 caracteres</p></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['emergency_token_generated'])): ?>
                <div class="notice notice-success is-dismissible"><p>✅ Token de emergencia generado exitosamente</p></div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 900px;">
                <h2>Token de Acceso Principal</h2>
                <p>Usa este token para acceder al panel de administración de WordPress desde la página de login personalizada.</p>
                
                <p><strong>URL de acceso:</strong></p>
                <p>
                    <code style="background: #f0f0f0; padding: 10px 15px; border-radius: 4px; display: inline-block; word-break: break-all; max-width: 100%; font-size: 13px;"><?php echo esc_html($login_url); ?></code>
                </p>
                
                <p>
                    <a href="<?php echo esc_url($login_url); ?>" class="button button-primary" target="_blank">Abrir Panel de Admin</a>
                    <button type="button" class="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($login_url); ?>').then(() => alert('URL copiada al portapapeles'))">Copiar URL</button>
                    <a href="<?php echo esc_url($regenerate_url); ?>" class="button button-secondary" onclick="return confirm('¿Estás seguro de regenerar el token? El token actual dejará de funcionar.')">🔄 Regenerar Token</a>
                </p>
                
                <?php if ($token_info): ?>
                    <p style="font-size: 13px; color: #666;">📅 <?php echo esc_html($token_info); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="card" style="max-width: 900px; margin-top: 20px;">
                <h2>🔑 Palabra Clave Personalizada</h2>
                <p>Configura una palabra fácil de recordar para acceder al admin. Debe tener al menos 6 caracteres.</p>
                
                <?php if ($has_keyword): ?>
                    <div class="notice notice-info inline" style="margin: 15px 0;">
                        <p>✅ Palabra clave configurada. Puedes actualizarla o eliminarla.</p>
                        <p style="font-size: 12px; color: #666;">Usa: <code>/wp-login.php?key=TU_PALABRA</code></p>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo esc_url(admin_url('options-general.php?page=agrochamba-admin-access')); ?>">
                    <?php wp_nonce_field('set_admin_keyword'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="admin_keyword">Palabra Clave</label>
                            </th>
                            <td>
                                <input type="text" id="admin_keyword" name="admin_keyword" class="regular-text" placeholder="<?php echo $has_keyword ? 'Ingresa nueva palabra clave' : 'Ej: miClave2024'; ?>" minlength="6" required>
                                <p class="description">Mínimo 6 caracteres. Por seguridad, no se muestra la palabra clave actual.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <?php if ($has_keyword): ?>
                            <button type="submit" name="set_admin_keyword" class="button button-secondary">Actualizar Palabra Clave</button>
                            <button type="submit" name="remove_keyword" class="button" onclick="return confirm('¿Eliminar la palabra clave? Esta acción no se puede deshacer.')" style="background: #d63638; color: #fff; border-color: #d63638;">Eliminar Palabra Clave</button>
                        <?php else: ?>
                            <button type="submit" name="set_admin_keyword" class="button button-primary">Configurar Palabra Clave</button>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
            
            <div class="card" style="max-width: 900px; margin-top: 20px;">
                <h2>🚨 Token de Emergencia</h2>
                <p>Token de respaldo para casos críticos donde pierdas acceso al token principal.</p>
                
                <?php if (!empty($emergency_token)): ?>
                    <?php $emergency_url = home_url('/wp-login.php?emergency=' . $emergency_token); ?>
                    <p><strong>URL de emergencia:</strong></p>
                    <p>
                        <code style="background: #fff3cd; padding: 10px 15px; border-radius: 4px; display: inline-block; word-break: break-all; max-width: 100%; font-size: 13px;"><?php echo esc_html($emergency_url); ?></code>
                    </p>
                    <p>
                        <a href="<?php echo esc_url($emergency_url); ?>" class="button" target="_blank">Probar Token de Emergencia</a>
                        <button type="button" class="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($emergency_url); ?>').then(() => alert('URL copiada al portapapeles'))">Copiar URL</button>
                    </p>
                    <p style="font-size: 12px; color: #d63638;">⚠️ <strong>Importante:</strong> Guarda este token en un lugar seguro. Solo úsalo si pierdes acceso al token principal.</p>
                <?php else: ?>
                    <p>No hay token de emergencia configurado.</p>
                    <p>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('options-general.php?page=agrochamba-admin-access&generate_emergency_token=1'), 'generate_emergency_token')); ?>" class="button button-primary">Generar Token de Emergencia</a>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="card" style="max-width: 900px; margin-top: 20px; background: #f0f6fc; border-left: 4px solid #2271b1;">
                <h3>ℹ️ Información Importante</h3>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li>El token principal <strong>NO expira automáticamente</strong>, pero puedes regenerarlo manualmente cuando lo necesites.</li>
                    <li>La palabra clave personalizada es más fácil de recordar que el token largo.</li>
                    <li>Guarda estos enlaces en un lugar seguro (gestor de contraseñas recomendado).</li>
                    <li>Todos los accesos se registran en los logs de seguridad para auditoría.</li>
                </ul>
            </div>
        </div>
        <?php
    }
}

// ==========================================
// 7. FUNCIÓN DE DIAGNÓSTICO Y VERIFICACIÓN
// ==========================================
if (!function_exists('agrochamba_check_auth_pages')) {
    function agrochamba_check_auth_pages() {
        $results = array();
        
        // Verificar página de login
        $login_page = get_page_by_path('login');
        if ($login_page) {
            $login_template = get_page_template_slug($login_page->ID);
            $results['login'] = array(
                'exists' => true,
                'id' => $login_page->ID,
                'url' => get_permalink($login_page->ID),
                'template' => $login_template,
                'template_correct' => $login_template === 'login.php',
                'template_file_exists' => file_exists(AGROCHAMBA_TEMPLATES_DIR . '/login.php')
            );
        } else {
            $results['login'] = array('exists' => false);
        }
        
        // Verificar página de registro
        $register_page = get_page_by_path('registro');
        if ($register_page) {
            $register_template = get_page_template_slug($register_page->ID);
            $results['register'] = array(
                'exists' => true,
                'id' => $register_page->ID,
                'url' => get_permalink($register_page->ID),
                'template' => $register_template,
                'template_correct' => $register_template === 'register.php',
                'template_file_exists' => file_exists(AGROCHAMBA_TEMPLATES_DIR . '/register.php')
            );
        } else {
            $results['register'] = array('exists' => false);
        }
        
        // Verificar página de recuperación
        $lostpassword_page = get_page_by_path('recuperar-contrasena');
        if ($lostpassword_page) {
            $lostpassword_template = get_page_template_slug($lostpassword_page->ID);
            $results['lostpassword'] = array(
                'exists' => true,
                'id' => $lostpassword_page->ID,
                'url' => get_permalink($lostpassword_page->ID),
                'template' => $lostpassword_template,
                'template_correct' => $lostpassword_template === 'lostpassword.php',
                'template_file_exists' => file_exists(AGROCHAMBA_TEMPLATES_DIR . '/lostpassword.php')
            );
        } else {
            $results['lostpassword'] = array('exists' => false);
        }
        
        return $results;
    }
    
    // Agregar mensaje de admin si hay problemas
    add_action('admin_notices', function() {
        if (current_user_can('manage_options') && isset($_GET['agrochamba_pages_created'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Páginas de autenticación creadas/actualizadas correctamente.</p></div>';
        }
        
        // Mostrar diagnóstico si se solicita
        if (current_user_can('manage_options') && isset($_GET['agrochamba_check_auth'])) {
            $check = agrochamba_check_auth_pages();
            echo '<div class="notice notice-info"><p><strong>🔍 Diagnóstico de Páginas de Autenticación:</strong></p>';
            echo '<table class="widefat" style="margin-top: 10px;">';
            echo '<thead><tr><th>Página</th><th>Estado</th><th>ID</th><th>URL</th><th>Template</th><th>Archivo existe</th></tr></thead><tbody>';
            
            foreach ($check as $page_type => $info) {
                $page_name = ucfirst($page_type);
                if ($info['exists']) {
                    $status = $info['template_correct'] && $info['template_file_exists'] ? '✅ OK' : '⚠️ Problema';
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($page_name) . '</strong></td>';
                    echo '<td>' . esc_html($status) . '</td>';
                    echo '<td>' . esc_html($info['id']) . '</td>';
                    echo '<td><a href="' . esc_url($info['url']) . '" target="_blank">Ver página</a></td>';
                    echo '<td>' . esc_html($info['template'] ?: 'Ninguno') . '</td>';
                    echo '<td>' . ($info['template_file_exists'] ? '✅ Sí' : '❌ No') . '</td>';
                    echo '</tr>';
                } else {
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($page_name) . '</strong></td>';
                    echo '<td>❌ No existe</td>';
                    echo '<td colspan="4">La página no ha sido creada</td>';
                    echo '</tr>';
                }
            }
            
            echo '</tbody></table>';
            echo '<p style="margin-top: 15px;">';
            echo '<a href="' . admin_url('edit.php?post_type=page&agrochamba_recreate_auth_pages=1') . '" class="button button-primary">Recrear/Actualizar Páginas</a> ';
            echo '<a href="' . admin_url('edit.php?post_type=page') . '" class="button">Ver todas las páginas</a>';
            echo '</p></div>';
        }
    });
}

// ==========================================
// 6. CARGAR TEMPLATES PERSONALIZADOS
// ==========================================
if (!function_exists('agrochamba_load_auth_templates')) {
    function agrochamba_load_auth_templates($template) {
        // Verificar si es una página (solo para page_template)
        if (current_filter() === 'page_template' && !is_page()) {
            return $template;
        }
        
        // Obtener el slug de la página actual
        $page_slug = '';
        
        // Intentar obtener el slug de diferentes formas
        if (is_page()) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->post_name)) {
                $page_slug = $queried_object->post_name;
            }
        }
        
        if (empty($page_slug)) {
            $page_slug = get_query_var('pagename');
        }
        
        // También verificar por template slug
        $page_template = get_page_template_slug();
        
        // Verificar por URL también (último recurso)
        $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw($_SERVER['REQUEST_URI']) : '';
        $is_login_page = strpos($request_uri, '/login') !== false || strpos($request_uri, '?page_id=') !== false;
        $is_register_page = strpos($request_uri, '/registro') !== false;
        
        // Rutas posibles para los templates
        $possible_template_paths = array(
            AGROCHAMBA_TEMPLATES_DIR . '/login.php',
            AGROCHAMBA_PLUGIN_DIR . '/templates/login.php',
            dirname(AGROCHAMBA_PLUGIN_DIR) . '/agrochamba-core/templates/login.php'
        );
        
        // Cargar template de login
        if ($page_slug === 'login' || $page_template === 'login.php' || ($is_login_page && empty($page_slug))) {
            foreach ($possible_template_paths as $template_path) {
                if (file_exists($template_path)) {
                    return $template_path;
                }
            }
            // También buscar en el directorio del plugin
            $plugin_template = AGROCHAMBA_PLUGIN_DIR . '/templates/login.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Cargar template de registro
        if ($page_slug === 'registro' || $page_template === 'register.php' || ($is_register_page && empty($page_slug))) {
            $register_paths = array(
                AGROCHAMBA_TEMPLATES_DIR . '/register.php',
                AGROCHAMBA_PLUGIN_DIR . '/templates/register.php',
                dirname(AGROCHAMBA_PLUGIN_DIR) . '/agrochamba-core/templates/register.php'
            );
            foreach ($register_paths as $template_path) {
                if (file_exists($template_path)) {
                    return $template_path;
                }
            }
        }
        
        // Verificar por URL de recuperación de contraseña
        $is_lostpassword_page = strpos($request_uri, '/recuperar-contrasena') !== false;
        
        // Cargar template de recuperación de contraseña
        if ($page_slug === 'recuperar-contrasena' || $page_template === 'lostpassword.php' || ($is_lostpassword_page && empty($page_slug))) {
            $lostpassword_paths = array(
                AGROCHAMBA_TEMPLATES_DIR . '/lostpassword.php',
                AGROCHAMBA_PLUGIN_DIR . '/templates/lostpassword.php',
                dirname(AGROCHAMBA_PLUGIN_DIR) . '/agrochamba-core/templates/lostpassword.php'
            );
            foreach ($lostpassword_paths as $template_path) {
                if (file_exists($template_path)) {
                    return $template_path;
                }
            }
        }
        
        // Verificar por URL de publicar trabajo
        $is_publicar_trabajo_page = strpos($request_uri, '/publicar-trabajo') !== false;
        
        // Cargar template de publicar trabajo
        if ($page_slug === 'publicar-trabajo' || $page_template === 'publicar-trabajo.php' || ($is_publicar_trabajo_page && empty($page_slug))) {
            $publicar_trabajo_paths = array(
                AGROCHAMBA_TEMPLATES_DIR . '/publicar-trabajo.php',
                AGROCHAMBA_PLUGIN_DIR . '/templates/publicar-trabajo.php',
                dirname(AGROCHAMBA_PLUGIN_DIR) . '/agrochamba-core/templates/publicar-trabajo.php'
            );
            foreach ($publicar_trabajo_paths as $template_path) {
                if (file_exists($template_path)) {
                    return $template_path;
                }
            }
        }
        
        return $template;
    }
    // Usar ambos filtros para máxima compatibilidad
    add_filter('page_template', 'agrochamba_load_auth_templates', 10);
    add_filter('template_include', 'agrochamba_load_auth_templates', 99);
    
    // También usar single_template con prioridad alta para asegurar que se cargue
    add_filter('single_template', function($template) {
        return agrochamba_load_auth_templates($template);
    }, 99);
}

