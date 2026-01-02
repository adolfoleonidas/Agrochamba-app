<?php
/**
 * =============================================================
 * MÓDULO 23: SINCRONIZACIÓN SUPABASE ↔ WORDPRESS
 * =============================================================
 * 
 * Maneja la sincronización de usuarios entre Supabase y WordPress
 * Permite que la nueva app web use Supabase Auth mientras WordPress
 * sigue siendo el backend de contenido.
 * 
 * Endpoints:
 * - POST /agrochamba/v1/sync/user - Sincronizar usuario Supabase → WordPress
 * - POST /agrochamba/v1/auth/validate - Validar token Supabase
 * 
 * Funciones:
 * - agrochamba_validate_supabase_token() - Valida token con Supabase API
 * - agrochamba_sync_supabase_user() - Crea/vincula usuario en WordPress
 * - agrochamba_validate_auth() - Middleware para endpoints protegidos
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// CONFIGURACIÓN
// ==========================================
if (!defined('AGROCHAMBA_SUPABASE_URL')) {
    define('AGROCHAMBA_SUPABASE_URL', get_option('agrochamba_supabase_url', ''));
}

if (!defined('AGROCHAMBA_SUPABASE_ANON_KEY')) {
    define('AGROCHAMBA_SUPABASE_ANON_KEY', get_option('agrochamba_supabase_anon_key', ''));
}

// TTL para cache de validación de tokens (5 minutos)
if (!defined('AGROCHAMBA_SUPABASE_TOKEN_CACHE_TTL')) {
    define('AGROCHAMBA_SUPABASE_TOKEN_CACHE_TTL', 5 * MINUTE_IN_SECONDS);
}

// ==========================================
// 1. VALIDAR TOKEN SUPABASE
// ==========================================
if (!function_exists('agrochamba_validate_supabase_token')) {
    /**
     * Valida un token JWT de Supabase
     * 
     * @param string $auth_header Header Authorization completo ("Bearer {token}")
     * @return object|false Usuario de Supabase o false si inválido
     */
    function agrochamba_validate_supabase_token($auth_header) {
        if (empty($auth_header)) {
            return false;
        }
        
        // Extraer token del header "Bearer {token}"
        $token = str_replace('Bearer ', '', trim($auth_header));
        
        if (empty($token)) {
            return false;
        }
        
        // Validar configuración
        $supabase_url = AGROCHAMBA_SUPABASE_URL;
        $supabase_anon_key = AGROCHAMBA_SUPABASE_ANON_KEY;
        
        if (empty($supabase_url) || empty($supabase_anon_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AgroChamba: Supabase URL o Anon Key no configurados');
            }
            return false;
        }
        
        // Cachear validación para evitar llamadas excesivas
        $cache_key = 'agrochamba_supabase_token_' . md5($token);
        $cached_user = get_transient($cache_key);
        
        if ($cached_user !== false) {
            return is_object($cached_user) ? $cached_user : (object)$cached_user;
        }
        
        // Validar token con Supabase API
        $response = wp_remote_get("{$supabase_url}/auth/v1/user", array(
            'headers' => array(
                'Authorization' => "Bearer {$token}",
                'apikey' => $supabase_anon_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AgroChamba: Error validando token Supabase: ' . $response->get_error_message());
            }
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['id'])) {
            return false;
        }
        
        $user = (object)$body;
        
        // Guardar en cache
        set_transient($cache_key, $user, AGROCHAMBA_SUPABASE_TOKEN_CACHE_TTL);
        
        return $user;
    }
}

// ==========================================
// 2. SINCRONIZAR USUARIO SUPABASE → WORDPRESS
// ==========================================
if (!function_exists('agrochamba_sync_supabase_user')) {
    /**
     * Sincroniza un usuario de Supabase a WordPress
     * 
     * @param string $supabase_user_id ID del usuario en Supabase
     * @param string $email Email del usuario
     * @param array $metadata Metadata del usuario (username, role, ruc, razon_social, etc.)
     * @return WP_User|WP_Error Usuario de WordPress o error
     */
    function agrochamba_sync_supabase_user($supabase_user_id, $email, $metadata = array()) {
        // 1. Buscar usuario existente por supabase_user_id
        $wp_users = get_users(array(
            'meta_key' => 'supabase_user_id',
            'meta_value' => $supabase_user_id,
            'number' => 1
        ));
        
        if (!empty($wp_users)) {
            $wp_user = $wp_users[0];
            // Actualizar metadata si es necesario
            agrochamba_update_user_metadata_from_supabase($wp_user->ID, $metadata);
            return $wp_user;
        }
        
        // 2. Buscar por email
        $wp_user = get_user_by('email', $email);
        
        if ($wp_user) {
            // Vincular con Supabase
            update_user_meta($wp_user->ID, 'supabase_user_id', $supabase_user_id);
            agrochamba_update_user_metadata_from_supabase($wp_user->ID, $metadata);
            return $wp_user;
        }
        
        // 3. Crear nuevo usuario
        $username = isset($metadata['username']) 
            ? sanitize_user($metadata['username']) 
            : sanitize_user($email);
        
        // Asegurar username único
        $original_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        // Generar password aleatorio (no se usa para login, solo para cumplir requisito WP)
        $password = wp_generate_password(20);
        
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        $wp_user = get_user_by('id', $user_id);
        
        if (!$wp_user) {
            return new WP_Error('user_creation_failed', 'Error al obtener usuario creado');
        }
        
        // Guardar supabase_user_id
        update_user_meta($wp_user->ID, 'supabase_user_id', $supabase_user_id);
        
        // Actualizar metadata
        agrochamba_update_user_metadata_from_supabase($wp_user->ID, $metadata);
        
        return $wp_user;
    }
}

// ==========================================
// 3. ACTUALIZAR METADATA DE USUARIO
// ==========================================
if (!function_exists('agrochamba_update_user_metadata_from_supabase')) {
    /**
     * Actualiza metadata de usuario desde Supabase
     * 
     * @param int $user_id ID del usuario en WordPress
     * @param array $metadata Metadata del usuario
     */
    function agrochamba_update_user_metadata_from_supabase($user_id, $metadata) {
        $role = isset($metadata['role']) ? $metadata['role'] : 'subscriber';
        
        // Asignar rol
        $wp_user = new WP_User($user_id);
        if ($role === 'employer') {
            $wp_user->set_role('employer');
        } else {
            $wp_user->set_role('subscriber');
        }
        
        // Actualizar display_name
        if (isset($metadata['razon_social']) && !empty($metadata['razon_social'])) {
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => sanitize_text_field($metadata['razon_social'])
            ));
            update_user_meta($user_id, 'razon_social', sanitize_text_field($metadata['razon_social']));
        }
        
        // Campos adicionales para empresas
        if ($role === 'employer') {
            if (isset($metadata['ruc'])) {
                update_user_meta($user_id, 'ruc', sanitize_text_field($metadata['ruc']));
            }
            
            // Crear/actualizar taxonomía de empresa si aplica
            if (isset($metadata['razon_social']) && !empty($metadata['razon_social'])) {
                $razon_social = sanitize_text_field($metadata['razon_social']);
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
        }
        
        // Campos generales
        if (isset($metadata['phone'])) {
            update_user_meta($user_id, 'phone', sanitize_text_field($metadata['phone']));
        }
        
        if (isset($metadata['bio'])) {
            update_user_meta($user_id, 'bio', sanitize_textarea_field($metadata['bio']));
        }
        
        if (isset($metadata['first_name'])) {
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => sanitize_text_field($metadata['first_name'])
            ));
        }
        
        if (isset($metadata['last_name'])) {
            wp_update_user(array(
                'ID' => $user_id,
                'last_name' => sanitize_text_field($metadata['last_name'])
            ));
        }
    }
}

// ==========================================
// 4. ENDPOINT: SINCRONIZAR USUARIO
// ==========================================
if (!function_exists('agrochamba_rest_sync_user')) {
    /**
     * Endpoint REST para sincronizar usuario Supabase → WordPress
     */
    function agrochamba_rest_sync_user($request) {
        // Validar token Supabase
        $auth_header = $request->get_header('Authorization');
        $supabase_user = agrochamba_validate_supabase_token($auth_header);
        
        if (!$supabase_user) {
            return new WP_Error(
                'invalid_token',
                'Token de Supabase inválido o expirado',
                array('status' => 401)
            );
        }
        
        $params = $request->get_json_params();
        $supabase_user_id = isset($params['supabase_user_id']) 
            ? sanitize_text_field($params['supabase_user_id']) 
            : $supabase_user->id;
        
        $email = isset($params['email']) 
            ? sanitize_email($params['email']) 
            : $supabase_user->email;
        
        $metadata = isset($params['metadata']) 
            ? $params['metadata'] 
            : (isset($supabase_user->user_metadata) ? $supabase_user->user_metadata : array());
        
        // Sincronizar usuario
        $wp_user = agrochamba_sync_supabase_user($supabase_user_id, $email, $metadata);
        
        if (is_wp_error($wp_user)) {
            return $wp_user;
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'user_id' => $wp_user->ID,
            'email' => $wp_user->user_email,
            'username' => $wp_user->user_login,
            'display_name' => $wp_user->display_name,
            'roles' => $wp_user->roles,
            'created' => get_user_meta($wp_user->ID, 'supabase_user_id', true) === $supabase_user_id
        ), 200);
    }
}

// ==========================================
// 5. ENDPOINT: VALIDAR TOKEN
// ==========================================
if (!function_exists('agrochamba_rest_validate_token')) {
    /**
     * Endpoint REST para validar token Supabase
     */
    function agrochamba_rest_validate_token($request) {
        $auth_header = $request->get_header('Authorization');
        $supabase_user = agrochamba_validate_supabase_token($auth_header);
        
        if (!$supabase_user) {
            return new WP_Error(
                'invalid_token',
                'Token inválido o expirado',
                array('status' => 401)
            );
        }
        
        // Buscar usuario WordPress vinculado
        $wp_users = get_users(array(
            'meta_key' => 'supabase_user_id',
            'meta_value' => $supabase_user->id,
            'number' => 1
        ));
        
        if (empty($wp_users)) {
            return new WP_Error(
                'user_not_found',
                'Usuario no encontrado en WordPress. Debe sincronizarse primero.',
                array('status' => 404)
            );
        }
        
        $wp_user = $wp_users[0];
        
        return new WP_REST_Response(array(
            'valid' => true,
            'user_id' => $wp_user->ID,
            'email' => $wp_user->user_email,
            'username' => $wp_user->user_login,
            'display_name' => $wp_user->display_name,
            'roles' => $wp_user->roles
        ), 200);
    }
}

// ==========================================
// 6. MIDDLEWARE: VALIDAR AUTENTICACIÓN
// ==========================================
if (!function_exists('agrochamba_validate_auth')) {
    /**
     * Middleware para validar autenticación (Supabase o WordPress tradicional)
     * 
     * @param WP_REST_Request $request Request object
     * @return bool True si autenticado, false si no
     */
    function agrochamba_validate_auth($request) {
        $auth_header = $request->get_header('Authorization');
        
        // Intentar validar token Supabase primero
        if (!empty($auth_header)) {
            $supabase_user = agrochamba_validate_supabase_token($auth_header);
            
            if ($supabase_user) {
                // Buscar usuario WordPress vinculado
                $wp_users = get_users(array(
                    'meta_key' => 'supabase_user_id',
                    'meta_value' => $supabase_user->id,
                    'number' => 1
                ));
                
                if (!empty($wp_users)) {
                    $wp_user = $wp_users[0];
                    wp_set_current_user($wp_user->ID);
                    return true;
                }
            }
        }
        
        // Fallback: validar sesión WordPress tradicional
        if (is_user_logged_in()) {
            return true;
        }
        
        return false;
    }
}

// ==========================================
// 7. REGISTRAR ENDPOINTS
// ==========================================
add_action('rest_api_init', function() {
    $routes = rest_get_server()->get_routes();
    
    // Sincronizar usuario
    if (!isset($routes['/agrochamba/v1/sync/user'])) {
        register_rest_route('agrochamba/v1', '/sync/user', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_rest_sync_user',
            'permission_callback' => '__return_true', // Validado por token en el callback
        ));
    }
    
    // Validar token
    if (!isset($routes['/agrochamba/v1/auth/validate'])) {
        register_rest_route('agrochamba/v1', '/auth/validate', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_rest_validate_token',
            'permission_callback' => '__return_true', // Validado por token en el callback
        ));
    }
}, 20);

// ==========================================
// 8. ESTABLECER USUARIO ANTES DE PERMISSION_CALLBACK
// ==========================================
// Este hook se ejecuta ANTES de que WordPress evalúe los permission_callbacks
// Es crítico para que is_user_logged_in() funcione correctamente
add_filter('determine_current_user', function($user_id) {
    // Solo procesar si no hay usuario ya establecido
    if ($user_id) {
        return $user_id;
    }
    
    // Solo procesar requests a la API REST de Agrochamba
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($request_uri, '/wp-json/agrochamba/v1/') === false) {
        return $user_id;
    }
    
    // Obtener header Authorization
    $auth_header = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    
    if (empty($auth_header)) {
        return $user_id;
    }
    
    // Validar token Supabase
    $supabase_user = agrochamba_validate_supabase_token($auth_header);
    
    if ($supabase_user) {
        // Buscar usuario WordPress vinculado
        $wp_users = get_users(array(
            'meta_key' => 'supabase_user_id',
            'meta_value' => $supabase_user->id,
            'number' => 1
        ));
        
        if (!empty($wp_users)) {
            $wp_user = $wp_users[0];
            // Establecer usuario actual
            wp_set_current_user($wp_user->ID);
            return $wp_user->ID;
        }
    }
    
    return $user_id;
}, 20);

// ==========================================
// 9. APLICAR MIDDLEWARE ADICIONAL EN REST_PRE_DISPATCH
// ==========================================
// Este hook se ejecuta DESPUÉS de permission_callback pero puede ayudar
// con endpoints que usan '__return_true' y verifican dentro del callback
add_filter('rest_pre_dispatch', function($result, $server, $request) {
    // Solo aplicar si no hay resultado previo
    if ($result !== null) {
        return $result;
    }
    
    $route = $request->get_route();
    
    // Rutas protegidas que deben aceptar tokens Supabase
    $protected_routes = array(
        '/agrochamba/v1/jobs',
        '/agrochamba/v1/me/',
        '/agrochamba/v1/favorites',
        '/agrochamba/v1/saved',
    );
    
    foreach ($protected_routes as $protected_route) {
        if (strpos($route, $protected_route) === 0) {
            // Verificar método HTTP (solo aplicar a métodos que requieren auth)
            $method = $request->get_method();
            $methods_requiring_auth = array('POST', 'PUT', 'DELETE', 'PATCH');
            
            if (in_array($method, $methods_requiring_auth)) {
                // Si el usuario no está logueado, intentar validar token Supabase
                if (!is_user_logged_in()) {
                    if (!agrochamba_validate_auth($request)) {
                        return new WP_Error(
                            'rest_forbidden',
                            'Debes iniciar sesión para acceder a este recurso',
                            array('status' => 401)
                        );
                    }
                }
            } else {
                // Para GET, intentar establecer usuario si hay token válido
                if (!is_user_logged_in()) {
                    agrochamba_validate_auth($request);
                }
            }
        }
    }
    
    return $result;
}, 10, 3);

// ==========================================
// 10. PÁGINA DE CONFIGURACIÓN EN ADMIN (Opcional)
// ==========================================
if (!function_exists('agrochamba_add_supabase_settings_page')) {
    function agrochamba_add_supabase_settings_page() {
        add_options_page(
            'Configuración Supabase',
            'Supabase',
            'manage_options',
            'agrochamba-supabase',
            'agrochamba_supabase_settings_page'
        );
    }
    add_action('admin_menu', 'agrochamba_add_supabase_settings_page');
}

if (!function_exists('agrochamba_supabase_settings_page')) {
    function agrochamba_supabase_settings_page() {
        // Guardar configuración
        if (isset($_POST['agrochamba_supabase_save']) && check_admin_referer('agrochamba_supabase_settings')) {
            $url = sanitize_text_field($_POST['agrochamba_supabase_url'] ?? '');
            $key = sanitize_text_field($_POST['agrochamba_supabase_anon_key'] ?? '');
            
            update_option('agrochamba_supabase_url', $url);
            update_option('agrochamba_supabase_anon_key', $key);
            
            // Actualizar constantes si están definidas en wp-config.php
            // Nota: Las constantes tienen prioridad sobre las opciones
            echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
        }
        
        $current_url = get_option('agrochamba_supabase_url', '');
        $current_key = get_option('agrochamba_supabase_anon_key', '');
        
        // Verificar si hay constantes definidas (tienen prioridad)
        $config_url = defined('AGROCHAMBA_SUPABASE_URL') ? AGROCHAMBA_SUPABASE_URL : '';
        $config_key = defined('AGROCHAMBA_SUPABASE_ANON_KEY') ? '***' . substr(AGROCHAMBA_SUPABASE_ANON_KEY, -10) : '';
        
        ?>
        <div class="wrap">
            <h1>Configuración de Supabase</h1>
            
            <?php if ($config_url): ?>
                <div class="notice notice-info">
                    <p><strong>Nota:</strong> Las credenciales están definidas en <code>wp-config.php</code> y tienen prioridad sobre esta configuración.</p>
                    <p>URL configurada: <code><?php echo esc_html($config_url); ?></code></p>
                    <p>Key configurada: <code><?php echo esc_html($config_key); ?></code></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('agrochamba_supabase_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="agrochamba_supabase_url">Supabase Project URL</label>
                        </th>
                        <td>
                            <input 
                                type="url" 
                                id="agrochamba_supabase_url" 
                                name="agrochamba_supabase_url" 
                                value="<?php echo esc_attr($current_url); ?>" 
                                class="regular-text"
                                placeholder="https://tu-proyecto.supabase.co"
                                <?php echo $config_url ? 'disabled' : ''; ?>
                            />
                            <p class="description">
                                URL de tu proyecto Supabase. Se encuentra en Settings → API → Project URL
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="agrochamba_supabase_anon_key">Supabase Anon Key</label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                id="agrochamba_supabase_anon_key" 
                                name="agrochamba_supabase_anon_key" 
                                value="<?php echo esc_attr($current_key); ?>" 
                                class="regular-text"
                                placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
                                <?php echo $config_key ? 'disabled' : ''; ?>
                            />
                            <p class="description">
                                Clave pública (anon/public) de Supabase. Se encuentra en Settings → API → anon/public key
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php if (!$config_url): ?>
                    <p class="submit">
                        <input type="submit" name="agrochamba_supabase_save" class="button button-primary" value="Guardar configuración" />
                    </p>
                <?php else: ?>
                    <p class="description">
                        Para cambiar esta configuración, edita las constantes en <code>wp-config.php</code>
                    </p>
                <?php endif; ?>
            </form>
            
            <hr>
            
            <h2>Estado de la Configuración</h2>
            <?php
            $url = defined('AGROCHAMBA_SUPABASE_URL') ? AGROCHAMBA_SUPABASE_URL : get_option('agrochamba_supabase_url', '');
            $key = defined('AGROCHAMBA_SUPABASE_ANON_KEY') ? AGROCHAMBA_SUPABASE_ANON_KEY : get_option('agrochamba_supabase_anon_key', '');
            
            if (empty($url) || empty($key)) {
                echo '<div class="notice notice-error"><p><strong>Error:</strong> La configuración de Supabase no está completa. Por favor, configura la URL y la clave.</p></div>';
            } else {
                echo '<div class="notice notice-success"><p><strong>✓</strong> Configuración de Supabase completa.</p></div>';
                
                // Probar conexión
                echo '<h3>Probar Conexión</h3>';
                echo '<p>Para probar la conexión, intenta hacer login desde la app web con Supabase.</p>';
            }
            ?>
        </div>
        <?php
    }
}

// ==========================================
// 11. HOOK: SINCRONIZAR AL CREAR USUARIO EN SUPABASE (Webhook)
// ==========================================
// Este hook se puede usar si configuras un webhook de Supabase
// que llame directamente a este endpoint
// O puedes usar una Edge Function de Supabase que llame al endpoint REST

// ==========================================
// 12. FUNCIÓN HELPER: OBTENER USER_ID DE SUPABASE
// ==========================================
if (!function_exists('agrochamba_get_wp_user_by_supabase_id')) {
    /**
     * Obtiene usuario WordPress por ID de Supabase
     * 
     * @param string $supabase_user_id ID del usuario en Supabase
     * @return WP_User|false Usuario de WordPress o false si no existe
     */
    function agrochamba_get_wp_user_by_supabase_id($supabase_user_id) {
        $users = get_users(array(
            'meta_key' => 'supabase_user_id',
            'meta_value' => $supabase_user_id,
            'number' => 1
        ));
        
        return !empty($users) ? $users[0] : false;
    }
}

// ==========================================
// LOG DE ACTIVACIÓN
// ==========================================
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('AgroChamba: Módulo 23 (Supabase Sync) cargado correctamente');
}

