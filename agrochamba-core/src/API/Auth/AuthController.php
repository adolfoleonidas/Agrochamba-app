<?php
/**
 * Controlador de Autenticación
 *
 * Maneja el registro y autenticación de usuarios y empresas
 *
 * @package AgroChamba
 * @subpackage API\Auth
 * @since 2.0.0
 */

namespace AgroChamba\API\Auth;

use WP_Error;
use WP_REST_Response;
use WP_REST_Request;
use WP_User;

class AuthController {

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'agrochamba/v1';

    /**
     * Inicializar el controlador
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'), 20);
    }

    /**
     * Registrar rutas de la API
     */
    public static function register_routes() {
        $routes = rest_get_server()->get_routes();

        // Registro de empresas
        if (!isset($routes['/' . self::API_NAMESPACE . '/register-company'])) {
            register_rest_route(self::API_NAMESPACE, '/register-company', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'register_company'),
                'permission_callback' => '__return_true',
            ));
        }

        // Registro de usuarios trabajadores
        if (!isset($routes['/' . self::API_NAMESPACE . '/register-user'])) {
            register_rest_route(self::API_NAMESPACE, '/register-user', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'register_user'),
                'permission_callback' => '__return_true',
            ));
        }

        // Login personalizado
        if (!isset($routes['/' . self::API_NAMESPACE . '/login'])) {
            register_rest_route(self::API_NAMESPACE, '/login', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'custom_login'),
                'permission_callback' => '__return_true',
            ));
        }

        // Solicitar código de recuperación
        if (!isset($routes['/' . self::API_NAMESPACE . '/lost-password'])) {
            register_rest_route(self::API_NAMESPACE, '/lost-password', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'lost_password'),
                'permission_callback' => '__return_true',
            ));
        }

        // Resetear contraseña
        if (!isset($routes['/' . self::API_NAMESPACE . '/reset-password'])) {
            register_rest_route(self::API_NAMESPACE, '/reset-password', array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'reset_password'),
                'permission_callback' => '__return_true',
            ));
        }
    }

    /**
     * Registrar empresa
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function register_company($request) {
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

        // Guardar campos adicionales opcionales en user_meta (para luego sincronizar al CPT)
        $optional_fields = array(
            'company_description' => 'description',
            'company_address' => 'address',
            'company_phone' => 'phone',
            'company_website' => 'website',
            'company_facebook' => 'facebook',
            'company_instagram' => 'instagram',
            'company_linkedin' => 'linkedin',
            'company_twitter' => 'twitter',
            'company_sector' => 'sector',
            'company_ciudad' => 'ciudad'
        );

        foreach ($optional_fields as $meta_key => $param_key) {
            if (isset($params[$param_key]) && !empty($params[$param_key])) {
                update_user_meta($user_id, $meta_key, sanitize_text_field($params[$param_key]));
            }
        }

        // Crear taxonomía de empresa automáticamente
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

                if (!is_wp_error($term_result) && isset($term_result['term_id'])) {
                    update_user_meta($user_id, 'empresa_term_id', $term_result['term_id']);
                }
            } else {
                update_user_meta($user_id, 'empresa_term_id', $empresa_term->term_id);
            }
        }

        // Crear CPT de empresa automáticamente (llamar explícitamente para asegurar ejecución)
        if (function_exists('agrochamba_create_empresa_on_user_register')) {
            agrochamba_create_empresa_on_user_register($user_id);
        }

        // Generar token JWT
        $jwt_token = JWTHelper::generate_token($username, $password);

        if (!$jwt_token) {
            return new WP_Error('rest_token_error', 'Usuario creado pero no se pudo generar el token. Por favor, inicia sesión manualmente.', array('status' => 500));
        }

        $roles = !empty($user->roles) ? array_values($user->roles) : array();
        
        // Obtener empresa_cpt_id (ID del CPT empresa) para enviarlo como user_company_id
        $empresa_cpt_id = get_user_meta($user_id, 'empresa_cpt_id', true);
        $user_company_id = $empresa_cpt_id ? intval($empresa_cpt_id) : null;

        return new WP_REST_Response(array(
            'token' => $jwt_token,
            'user_display_name' => $razon_social,
            'user_email' => $email,
            'user_nicename' => $user->user_nicename,
            'roles' => $roles,
            'user_company_id' => $user_company_id
        ), 201);
    }

    /**
     * Registrar usuario trabajador
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function register_user($request) {
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
        $jwt_token = JWTHelper::generate_token($username, $password);

        if (!$jwt_token) {
            return new WP_Error('rest_token_error', 'Usuario creado pero no se pudo generar el token. Por favor, inicia sesión manualmente.', array('status' => 500));
        }

        $roles = !empty($user->roles) ? array_values($user->roles) : array();

        return new WP_REST_Response(array(
            'token' => $jwt_token,
            'user_display_name' => $user->display_name,
            'user_email' => $email,
            'user_nicename' => $user->user_nicename,
            'roles' => $roles
        ), 201);
    }

    /**
     * Login personalizado (acepta username o email)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function custom_login($request) {
        $params = $request->get_json_params();

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
            $username_for_auth = $user->user_login;
        }

        if (!$user) {
            return new WP_Error('rest_invalid_credentials', 'Usuario o contraseña incorrectos.', array('status' => 401));
        }

        // Verificar la contraseña
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            return new WP_Error('rest_invalid_credentials', 'Usuario o contraseña incorrectos.', array('status' => 401));
        }

        // Generar el token
        $jwt_token = JWTHelper::generate_token($username_for_auth, $password);

        if (!$jwt_token) {
            return new WP_Error('rest_token_error', 'Error al generar el token de sesión.', array('status' => 500));
        }

        $user_obj = new WP_User($user->ID);
        $roles = !empty($user_obj->roles) ? array_values($user_obj->roles) : array();
        
        // Obtener empresa_cpt_id (ID del CPT empresa) para enviarlo como user_company_id
        $user_company_id = null;
        if (in_array('employer', $roles) || in_array('administrator', $roles)) {
            // Primero intentar obtener el ID del CPT empresa
            $empresa_cpt_id = get_user_meta($user->ID, 'empresa_cpt_id', true);
            if ($empresa_cpt_id) {
                $user_company_id = intval($empresa_cpt_id);
            } else {
                // Fallback: buscar la empresa del usuario por autor
                $empresa_posts = get_posts(array(
                    'post_type' => 'empresa',
                    'author' => $user->ID,
                    'posts_per_page' => 1,
                    'post_status' => 'publish',
                ));
                if (!empty($empresa_posts)) {
                    $user_company_id = $empresa_posts[0]->ID;
                    // Guardar para futuras consultas
                    update_user_meta($user->ID, 'empresa_cpt_id', $user_company_id);
                }
            }
        }

        return new WP_REST_Response(array(
            'token' => $jwt_token,
            'user_display_name' => $user->display_name,
            'user_email' => $user->user_email,
            'user_nicename' => $user->user_nicename,
            'roles' => $roles,
            'user_company_id' => $user_company_id
        ), 200);
    }

    /**
     * Solicitar código de recuperación de contraseña
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function lost_password($request) {
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

        return new WP_REST_Response(array(
            'message' => 'Si el usuario existe, se han enviado las instrucciones a tu correo.'
        ), 200);
    }

    /**
     * Resetear contraseña con código
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function reset_password($request) {
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
