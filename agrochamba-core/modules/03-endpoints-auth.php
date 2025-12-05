<?php
/**
 * =============================================================
 * MÓDULO 3: ENDPOINTS DE AUTENTICACIÓN Y REGISTRO
 * =============================================================
 * 
 * Endpoints:
 * - POST /agrochamba/v1/register-company - Registro de empresas
 * - POST /agrochamba/v1/register-user - Registro de usuarios trabajadores
 * - POST /agrochamba/v1/lost-password - Solicitar código de recuperación
 * - POST /agrochamba/v1/reset-password - Resetear contraseña con código
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================
// SHIM DE COMPATIBILIDAD → Delegar a controlador namespaced
// =============================================================
if (!defined('AGROCHAMBA_AUTH_CONTROLLER_NAMESPACE_INITIALIZED')) {
    define('AGROCHAMBA_AUTH_CONTROLLER_NAMESPACE_INITIALIZED', true);

    // Si existe el controlador moderno, delegar y salir para evitar duplicidad
    if (class_exists('AgroChamba\\API\\Auth\\AuthController')) {
        if (function_exists('error_log')) {
            error_log('AgroChamba: Delegando endpoints de autenticación a AgroChamba\\API\\Auth\\AuthController (migración namespaces).');
        }
        \AgroChamba\API\Auth\AuthController::init();
        return; // Evitar registrar endpoints legacy duplicados
    } else {
        if (function_exists('error_log')) {
            error_log('AgroChamba: No se encontró AgroChamba\\API\\Auth\\AuthController. Usando implementación procedural legacy.');
        }
    }
}

// ==========================================
// 1. REGISTRO DE EMPRESAS
// ==========================================
if (!function_exists('agrochamba_register_company')) {
    function agrochamba_register_company($request) {
        $params = $request->get_json_params();

        // Validar campos requeridos
        if (empty($params['username']) || empty($params['email']) || empty($params['password'])) {
            return new WP_Error('rest_invalid_param', 'Username, email y password son requeridos.', array('status' => 400));
        }

        if (empty($params['ruc']) || empty($params['razon_social'])) {
            return new WP_Error('rest_invalid_param', 'RUC y Razón Social son requeridos para empresas.', array('status' => 400));
        }

        $username = sanitize_user($params['username']);
        $email = sanitize_email($params['email']);
        $password = $params['password'];
        $ruc = sanitize_text_field($params['ruc']);
        $razon_social = sanitize_text_field($params['razon_social']);

        // Validar email
        if (!is_email($email)) {
            return new WP_Error('rest_invalid_param', 'El email proporcionado no es válido.', array('status' => 400));
        }

        // Verificar si el usuario ya existe
        if (username_exists($username)) {
            return new WP_Error('rest_user_exists', 'El nombre de usuario ya está en uso.', array('status' => 400));
        }

        if (email_exists($email)) {
            return new WP_Error('rest_email_exists', 'El email ya está registrado.', array('status' => 400));
        }

        // Crear el usuario con rol 'employer'
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return new WP_Error('rest_user_creation_failed', 'Error al crear el usuario: ' . $user_id->get_error_message(), array('status' => 500));
        }

        // Asignar rol de empresa
        $user = new WP_User($user_id);
        $user->set_role('employer');

        // Establecer display_name como razón social
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $razon_social
        ));

        // Guardar RUC y razón social en meta
        update_user_meta($user_id, 'ruc', $ruc);
        update_user_meta($user_id, 'razon_social', $razon_social);

        // ==========================================
        // CREAR TAXONOMÍA DE EMPRESA AUTOMÁTICAMENTE
        // ==========================================
        if (!empty($razon_social)) {
            $empresa_term = get_term_by('name', $razon_social, 'empresa');
            
            if (!$empresa_term) {
                $term_result = wp_insert_term(
                    $razon_social,
                    'empresa',
                    array(
                        'description' => 'Empresa: ' . $razon_social,
                        'slug' => sanitize_title($razon_social)
                    )
                );
                
                // Si se creó correctamente, guardar el ID en user meta para referencia
                if (!is_wp_error($term_result) && isset($term_result['term_id'])) {
                    update_user_meta($user_id, 'empresa_term_id', $term_result['term_id']);
                }
            } else {
                // Si ya existe, guardar el ID en user meta
                update_user_meta($user_id, 'empresa_term_id', $empresa_term->term_id);
            }
        }

        // Generar token JWT
        $jwt_token = agrochamba_generate_jwt_token($username, $password);

        if (!$jwt_token) {
            return new WP_Error('rest_token_error', 'Usuario creado pero no se pudo generar el token. Por favor, inicia sesión manualmente.', array('status' => 500));
        }

        // Asegurar que roles sea un array
        $roles = !empty($user->roles) ? array_values($user->roles) : array();
        
        return new WP_REST_Response(array(
            'token' => $jwt_token,
            'user_display_name' => $razon_social,
            'user_email' => $email,
            'user_nicename' => $user->user_nicename,
            'roles' => $roles
        ), 201);
    }
}

// ==========================================
// 2. REGISTRO DE USUARIOS TRABAJADORES
// ==========================================
if (!function_exists('agrochamba_register_user')) {
    function agrochamba_register_user($request) {
        $params = $request->get_json_params();

        // Validar campos requeridos
        if (empty($params['username']) || empty($params['email']) || empty($params['password'])) {
            return new WP_Error('rest_invalid_param', 'Username, email y password son requeridos.', array('status' => 400));
        }

        $username = sanitize_user($params['username']);
        $email = sanitize_email($params['email']);
        $password = $params['password'];

        // Validar email
        if (!is_email($email)) {
            return new WP_Error('rest_invalid_param', 'El email proporcionado no es válido.', array('status' => 400));
        }

        // Verificar si el usuario ya existe
        if (username_exists($username)) {
            return new WP_Error('rest_user_exists', 'El nombre de usuario ya está en uso.', array('status' => 400));
        }

        if (email_exists($email)) {
            return new WP_Error('rest_email_exists', 'El email ya está registrado.', array('status' => 400));
        }

        // Crear el usuario con rol 'subscriber' (trabajador)
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return new WP_Error('rest_user_creation_failed', 'Error al crear el usuario: ' . $user_id->get_error_message(), array('status' => 500));
        }

        $user = new WP_User($user_id);

        // Generar token JWT
        $jwt_token = agrochamba_generate_jwt_token($username, $password);

        if (!$jwt_token) {
            return new WP_Error('rest_token_error', 'Usuario creado pero no se pudo generar el token. Por favor, inicia sesión manualmente.', array('status' => 500));
        }

        // Asegurar que roles sea un array
        $roles = !empty($user->roles) ? array_values($user->roles) : array();
        
        return new WP_REST_Response(array(
            'token' => $jwt_token,
            'user_display_name' => $user->display_name,
            'user_email' => $email,
            'user_nicename' => $user->user_nicename,
            'roles' => $roles
        ), 201);
    }
}

// ==========================================
// 3. FUNCIÓN AUXILIAR: GENERAR TOKEN JWT
// ==========================================
if (!function_exists('agrochamba_generate_jwt_token')) {
    function agrochamba_generate_jwt_token($username, $password) {
        $login_credentials = array(
            'username' => $username,
            'password' => $password
        );
        
        // Usar el endpoint de JWT Auth
        $token_url = rest_url('jwt-auth/v1/token');
        $token_response = wp_remote_post($token_url, array(
            'body' => json_encode($login_credentials),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 10
        ));

        if (!is_wp_error($token_response)) {
            $response_code = wp_remote_retrieve_response_code($token_response);
            if ($response_code === 200) {
                $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
                if (isset($token_body['token'])) {
                    return $token_body['token'];
                }
            }
        }
        
        return null;
    }
}

// ==========================================
// 4. LOGIN PERSONALIZADO (ACEPTA USERNAME O EMAIL)
// ==========================================
if (!function_exists('agrochamba_custom_login')) {
    function agrochamba_custom_login($request) {
        $params = $request->get_json_params();
        
        // Si no hay parámetros JSON, intentar obtenerlos del body raw
        if (empty($params)) {
            $body = $request->get_body();
            if (!empty($body)) {
                $params = json_decode($body, true);
            }
        }
        
        if (empty($params['username']) || empty($params['password'])) {
            return new WP_Error('rest_invalid_param', 'Username/email y password son requeridos.', array('status' => 400));
        }
        
        $user_login = sanitize_text_field($params['username']);
        $password = $params['password'];
        
        // Intentar obtener el usuario por email primero, luego por username
        $user = get_user_by('email', $user_login);
        $username_for_auth = $user_login;
        
        if (!$user) {
            $user = get_user_by('login', $user_login);
        } else {
            // Si encontramos por email, usar el username real para autenticación
            $username_for_auth = $user->user_login;
        }
        
        if (!$user) {
            return new WP_Error('rest_invalid_credentials', 'Usuario o contraseña incorrectos.', array('status' => 401));
        }
        
        // Verificar la contraseña directamente (más confiable que wp_authenticate en este contexto)
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            return new WP_Error('rest_invalid_credentials', 'Usuario o contraseña incorrectos.', array('status' => 401));
        }
        
        // Si la autenticación es exitosa, generar el token usando el username real
        $jwt_token = agrochamba_generate_jwt_token($username_for_auth, $password);
        
        if (!$jwt_token) {
            // Si no se puede generar el token, intentar obtener más información del error
            $error_details = 'Error al generar el token de sesión.';
            if (function_exists('wp_remote_retrieve_response_message')) {
                $token_url = rest_url('jwt-auth/v1/token');
                $test_response = wp_remote_post($token_url, array(
                    'body' => json_encode(array('username' => $username_for_auth, 'password' => $password)),
                    'headers' => array('Content-Type' => 'application/json'),
                    'timeout' => 10
                ));
                if (is_wp_error($test_response)) {
                    $error_details .= ' ' . $test_response->get_error_message();
                } else {
                    $response_body = wp_remote_retrieve_body($test_response);
                    $error_details .= ' Respuesta: ' . $response_body;
                }
            }
            return new WP_Error('rest_token_error', $error_details, array('status' => 500));
        }
        
        // Obtener roles del usuario
        $user_obj = new WP_User($user->ID);
        
        // Asegurar que roles sea un array
        $roles = !empty($user_obj->roles) ? array_values($user_obj->roles) : array();
        
        return new WP_REST_Response(array(
            'token' => $jwt_token,
            'user_display_name' => $user->display_name,
            'user_email' => $user->user_email,
            'user_nicename' => $user->user_nicename,
            'roles' => $roles
        ), 200);
    }
}

// ==========================================
// 5. RECUPERACIÓN DE CONTRASEÑA - SOLICITAR CÓDIGO
// ==========================================
if (!function_exists('agrochamba_handle_lost_password_request')) {
    function agrochamba_handle_lost_password_request($request) {
        $user_login = $request->get_param('user_login');
        
        if (empty($user_login)) {
            return new WP_Error('empty_field', 'Usuario o correo no puede estar vacío.', array('status' => 400));
        }

        $user_data = get_user_by('email', $user_login);
        if (!$user_data) {
            $user_data = get_user_by('login', $user_login);
        }

        if ($user_data) {
            // Generar un código de 6 dígitos
            $reset_code = str_pad(wp_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Guardar el código y una marca de tiempo (expira en 15 minutos)
            update_user_meta($user_data->ID, 'agrochamba_reset_code', $reset_code);
            update_user_meta($user_data->ID, 'agrochamba_reset_timestamp', time());

            // Enviar correo
            $subject = 'Tu código de recuperación para Agrochamba';
            $message = "Hola " . $user_data->display_name . ",\n\n";
            $message .= "Tu código para restablecer tu contraseña es: " . $reset_code . "\n\n";
            $message .= "Este código expirará en 15 minutos.\n\n";
            $message .= "Si no solicitaste esto, puedes ignorar este correo.\n";
            
            wp_mail($user_data->user_email, $subject, $message);
        }
        
        // Por seguridad, siempre enviamos una respuesta exitosa
        return new WP_REST_Response(array(
            'message' => 'Si el usuario existe, se han enviado las instrucciones a tu correo.'
        ), 200);
    }
}

// ==========================================
// 6. RECUPERACIÓN DE CONTRASEÑA - RESETEAR
// ==========================================
if (!function_exists('agrochamba_handle_reset_password_request')) {
    function agrochamba_handle_reset_password_request($request) {
        $user_login = $request->get_param('user_login');
        $code = $request->get_param('code');
        $new_password = $request->get_param('password');

        if (empty($user_login) || empty($code) || empty($new_password)) {
            return new WP_Error('empty_fields', 'Todos los campos son obligatorios.', array('status' => 400));
        }

        $user_data = get_user_by('email', $user_login);
        if (!$user_data) {
            $user_data = get_user_by('login', $user_login);
        }

        if (!$user_data) {
            return new WP_Error('invalid_user', 'Usuario no válido.', array('status' => 404));
        }

        $saved_code = get_user_meta($user_data->ID, 'agrochamba_reset_code', true);
        $timestamp = get_user_meta($user_data->ID, 'agrochamba_reset_timestamp', true);

        // Verificar si el código es correcto y no ha expirado (15 minutos)
        if ($saved_code !== $code || (time() - $timestamp) > (15 * 60)) {
            return new WP_Error('invalid_code', 'El código es incorrecto o ha expirado.', array('status' => 403));
        }

        // Cambiar la contraseña
        reset_password($user_data, $new_password);

        // Borrar los códigos
        delete_user_meta($user_data->ID, 'agrochamba_reset_code');
        delete_user_meta($user_data->ID, 'agrochamba_reset_timestamp');

        return new WP_REST_Response(array(
            'message' => 'Contraseña actualizada con éxito.'
        ), 200);
    }
}

// ==========================================
// REGISTRAR ENDPOINTS
// ==========================================
add_action('rest_api_init', function () {
    $routes = rest_get_server()->get_routes();
    
    // Registro de empresas
    if (!isset($routes['/agrochamba/v1/register-company'])) {
        register_rest_route('agrochamba/v1', '/register-company', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_register_company',
            'permission_callback' => '__return_true', // Público
        ));
    }

    // Registro de usuarios trabajadores
    if (!isset($routes['/agrochamba/v1/register-user'])) {
        register_rest_route('agrochamba/v1', '/register-user', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_register_user',
            'permission_callback' => '__return_true', // Público
        ));
    }

    // Login personalizado (acepta username o email) - OPCIONAL
    if (!isset($routes['/agrochamba/v1/login'])) {
        register_rest_route('agrochamba/v1', '/login', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_custom_login',
            'permission_callback' => '__return_true', // Público
        ));
    }

    // Solicitar código de recuperación
    if (!isset($routes['/agrochamba/v1/lost-password'])) {
        register_rest_route('agrochamba/v1', '/lost-password', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_handle_lost_password_request',
            'permission_callback' => '__return_true', // Público
        ));
    }

    // Resetear contraseña
    if (!isset($routes['/agrochamba/v1/reset-password'])) {
        register_rest_route('agrochamba/v1', '/reset-password', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_handle_reset_password_request',
            'permission_callback' => '__return_true', // Público
        ));
    }
}, 20);

