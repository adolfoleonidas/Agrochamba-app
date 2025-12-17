<?php
/**
 * =============================================================
 * MÓDULO 18: PANEL DE USUARIO PERSONALIZADO
 * =============================================================
 * 
 * Reemplaza el panel nativo de WordPress con un panel personalizado
 * que oculta completamente WordPress y ofrece una experiencia similar a la app móvil
 * 
 * Funcionalidades:
 * - Redirige wp-admin/profile.php y otras URLs de WordPress relacionadas con el perfil
 * - Crea automáticamente la página de perfil personalizada
 * - Desactiva Bricks Builder en la página de perfil
 * - Bloquea acceso al admin de WordPress para usuarios no administradores
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. DESACTIVAR BRICKS BUILDER EN PÁGINA DE PERFIL
// ==========================================
if (!function_exists('agrochamba_disable_bricks_for_profile_page')) {
    function agrochamba_disable_bricks_for_profile_page() {
        // Verificar si estamos en la página de perfil
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
        
        // También verificar por URL
        $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw($_SERVER['REQUEST_URI']) : '';
        $is_profile_page = ($page_slug === 'mi-perfil') || (strpos($request_uri, '/mi-perfil') !== false);
        
        if ($is_profile_page) {
            // Desactivar Bricks Builder completamente
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
    add_action('template_redirect', 'agrochamba_disable_bricks_for_profile_page', 1);
}

// ==========================================
// 2. REDIRIGIR URLs DE WORDPRESS RELACIONADAS CON PERFIL
// ==========================================
if (!function_exists('agrochamba_redirect_wp_profile_urls')) {
    function agrochamba_redirect_wp_profile_urls() {
        // Solo redirigir si no estamos en admin y no es una petición AJAX
        if (!is_admin() && !wp_doing_ajax()) {
            $request_uri = $_SERVER['REQUEST_URI'];
            
            // Redirigir wp-admin/profile.php a nuestro panel personalizado
            if (strpos($request_uri, 'wp-admin/profile.php') !== false) {
                $profile_page = get_page_by_path('mi-perfil');
                if ($profile_page) {
                    wp_redirect(get_permalink($profile_page->ID));
                    exit;
                }
            }
            
            // Redirigir wp-admin/user-edit.php (si el usuario intenta editar su propio perfil)
            if (strpos($request_uri, 'wp-admin/user-edit.php') !== false) {
                $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
                $current_user_id = get_current_user_id();
                
                // Solo redirigir si el usuario intenta editar su propio perfil
                if ($user_id > 0 && $user_id === $current_user_id) {
                    $profile_page = get_page_by_path('mi-perfil');
                    if ($profile_page) {
                        wp_redirect(get_permalink($profile_page->ID));
                        exit;
                    }
                }
            }
        }
    }
    add_action('init', 'agrochamba_redirect_wp_profile_urls', 1);
}

// ==========================================
// NOTA: Ya no redirigimos el home al perfil
// Los usuarios deben ver el contenido principal (trabajos) en el home
// El perfil solo se accede cuando el usuario lo solicite desde el menú
// ==========================================

// ==========================================
// 3. BLOQUEAR ACCESO AL ADMIN DE WORDPRESS PARA USUARIOS NO ADMINISTRADORES
// ==========================================
if (!function_exists('agrochamba_block_wp_admin_for_non_admins')) {
    function agrochamba_block_wp_admin_for_non_admins() {
        // Solo aplicar si estamos en el admin y el usuario está logueado
        if (is_admin() && is_user_logged_in() && !wp_doing_ajax()) {
            $user = wp_get_current_user();
            
            // Permitir acceso solo a administradores
            if (!in_array('administrator', $user->roles)) {
                // Redirigir a la página de perfil personalizada
                $profile_page = get_page_by_path('mi-perfil');
                if ($profile_page) {
                    wp_redirect(get_permalink($profile_page->ID));
                    exit;
                } else {
                    // Si no existe la página de perfil, redirigir al inicio
                    wp_redirect(home_url());
                    exit;
                }
            }
        }
    }
    add_action('admin_init', 'agrochamba_block_wp_admin_for_non_admins', 1);
}

// ==========================================
// 4. FILTROS PARA URLS DE PERFIL
// ==========================================
if (!function_exists('agrochamba_custom_profile_url')) {
    function agrochamba_custom_profile_url($url, $user_id, $scheme) {
        // Solo cambiar la URL si es el usuario actual
        if ($user_id == get_current_user_id()) {
            $profile_page = get_page_by_path('mi-perfil');
            if ($profile_page) {
                return get_permalink($profile_page->ID);
            }
        }
        return $url;
    }
    add_filter('edit_profile_url', 'agrochamba_custom_profile_url', 10, 3);
}

// ==========================================
// 5. CREAR PÁGINA DE PERFIL AUTOMÁTICAMENTE
// ==========================================
if (!function_exists('agrochamba_create_profile_page')) {
    function agrochamba_create_profile_page() {
        // Crear página de perfil
        $profile_page = get_page_by_path('mi-perfil');
        if (!$profile_page) {
            $profile_page_id = wp_insert_post(array(
                'post_title' => 'Mi Perfil',
                'post_name' => 'mi-perfil',
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'page'
            ));
            
            if ($profile_page_id && !is_wp_error($profile_page_id)) {
                // Asignar template después de crear la página
                update_post_meta($profile_page_id, '_wp_page_template', 'profile.php');
                update_option('agrochamba_profile_page_id', $profile_page_id);
            }
        } else {
            // Si la página ya existe, asegurar que tenga el template correcto
            $current_template = get_page_template_slug($profile_page->ID);
            if ($current_template !== 'profile.php') {
                update_post_meta($profile_page->ID, '_wp_page_template', 'profile.php');
            }
        }
    }
    add_action('after_switch_theme', 'agrochamba_create_profile_page');
    add_action('agrochamba_plugin_activated', 'agrochamba_create_profile_page');
    
    // Crear página al cargar el módulo si no existe
    add_action('init', function() {
        if (!get_option('agrochamba_profile_page_created')) {
            agrochamba_create_profile_page();
            update_option('agrochamba_profile_page_created', true);
        }
    }, 20);
    
    // Forzar recreación de página de perfil si se solicita (para diagnóstico)
    add_action('admin_init', function() {
        if (isset($_GET['agrochamba_recreate_profile_page']) && current_user_can('manage_options')) {
            delete_option('agrochamba_profile_page_created');
            agrochamba_create_profile_page();
            update_option('agrochamba_profile_page_created', true);
            wp_redirect(admin_url('edit.php?post_type=page&agrochamba_profile_created=1'));
            exit;
        }
    });
    
    // Mensaje de admin si se creó la página
    add_action('admin_notices', function() {
        if (current_user_can('manage_options') && isset($_GET['agrochamba_profile_created'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Página de perfil creada/actualizada correctamente.</p></div>';
        }
    });
}

// ==========================================
// 9. PÁGINA DE DIAGNÓSTICO DE PERFIL
// ==========================================
if (!function_exists('agrochamba_add_profile_diagnostics_page')) {
    // Agregar página de diagnóstico en el menú de Herramientas
    add_action('admin_menu', function() {
        add_management_page(
            'Diagnóstico de Perfil',
            'Diagnóstico de Perfil',
            'manage_options',
            'agrochamba-profile-diagnostics',
            'agrochamba_render_profile_diagnostics_page'
        );
    });
    
    // Función para renderizar la página de diagnóstico
    function agrochamba_render_profile_diagnostics_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Verificar todas las páginas de autenticación y perfil
        $pages_to_check = array(
            'login' => array('name' => 'Login', 'template' => 'login.php'),
            'registro' => array('name' => 'Registro', 'template' => 'register.php'),
            'recuperar-contrasena' => array('name' => 'Recuperar Contraseña', 'template' => 'lostpassword.php'),
            'mi-perfil' => array('name' => 'Mi Perfil', 'template' => 'profile.php')
        );
        
        $diagnostics = array();
        $has_errors = false;
        
        foreach ($pages_to_check as $slug => $info) {
            $page = get_page_by_path($slug);
            $result = array(
                'exists' => false,
                'id' => null,
                'url' => null,
                'template_assigned' => false,
                'template_file_exists' => false,
                'status' => 'error'
            );
            
            if ($page) {
                $result['exists'] = true;
                $result['id'] = $page->ID;
                $result['url'] = get_permalink($page->ID);
                
                $assigned_template = get_page_template_slug($page->ID);
                $result['template_assigned'] = ($assigned_template === $info['template']);
                
                $template_paths = array(
                    AGROCHAMBA_TEMPLATES_DIR . '/' . $info['template'],
                    AGROCHAMBA_PLUGIN_DIR . '/templates/' . $info['template']
                );
                
                foreach ($template_paths as $path) {
                    if (file_exists($path)) {
                        $result['template_file_exists'] = true;
                        $result['template_path'] = $path;
                        break;
                    }
                }
                
                if ($result['template_assigned'] && $result['template_file_exists']) {
                    $result['status'] = 'success';
                } else {
                    $result['status'] = 'warning';
                    $has_errors = true;
                }
            } else {
                $has_errors = true;
            }
            
            $diagnostics[$slug] = array_merge($info, $result);
        }
        
        // Verificar endpoints de API
        $api_endpoints = array(
            '/agrochamba/v1/me/profile' => 'Obtener/Actualizar Perfil',
            '/agrochamba/v1/me/profile/photo' => 'Foto de Perfil'
        );
        
        $api_status = array();
        foreach ($api_endpoints as $endpoint => $description) {
            $routes = rest_get_server()->get_routes();
            $api_status[$endpoint] = array(
                'description' => $description,
                'registered' => isset($routes[$endpoint])
            );
        }
        
        ?>
        <div class="wrap">
            <h1>🔍 Diagnóstico de Páginas de Perfil y Autenticación</h1>
            
            <?php if ($has_errors): ?>
                <div class="notice notice-error">
                    <p><strong>⚠️ Se encontraron problemas.</strong> Revisa la tabla abajo y usa los botones de solución.</p>
                </div>
            <?php else: ?>
                <div class="notice notice-success">
                    <p><strong>✅ Todo está configurado correctamente.</strong></p>
                </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: none; margin-top: 20px;">
                <h2>Estado de las Páginas</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Página</th>
                            <th style="width: 10%;">Estado</th>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 15%;">Template Asignado</th>
                            <th style="width: 15%;">Archivo Existe</th>
                            <th style="width: 17%;">URL</th>
                            <th style="width: 15%;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diagnostics as $slug => $result): ?>
                        <tr>
                            <td><strong><?php echo esc_html($result['name']); ?></strong><br><small>Slug: <?php echo esc_html($slug); ?></small></td>
                            <td>
                                <?php if ($result['status'] === 'success'): ?>
                                    <span style="color: #46b450; font-size: 16px;">✅</span> OK
                                <?php elseif ($result['status'] === 'warning'): ?>
                                    <span style="color: #ffb900; font-size: 16px;">⚠️</span> Advertencia
                                <?php else: ?>
                                    <span style="color: #dc3232; font-size: 16px;">❌</span> Error
                                <?php endif; ?>
                            </td>
                            <td><?php echo $result['exists'] ? esc_html($result['id']) : '-'; ?></td>
                            <td>
                                <?php if ($result['exists']): ?>
                                    <?php echo $result['template_assigned'] ? '<span style="color: #46b450;">✅ Correcto</span>' : '<span style="color: #dc3232;">❌ Incorrecto</span>'; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($result['template_file_exists']): ?>
                                    <span style="color: #46b450;">✅ Sí</span>
                                    <?php if (isset($result['template_path'])): ?>
                                        <br><small style="color: #666;"><?php echo esc_html(basename($result['template_path'])); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #dc3232;">❌ No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($result['exists']): ?>
                                    <a href="<?php echo esc_url($result['url']); ?>" target="_blank" style="font-size: 11px;">Ver página →</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$result['exists']): ?>
                                    <?php if ($slug === 'mi-perfil'): ?>
                                        <a href="<?php echo esc_url(admin_url('tools.php?page=agrochamba-profile-diagnostics&action=create_profile')); ?>" class="button button-primary">Crear Página</a>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url(admin_url('tools.php?page=agrochamba-profile-diagnostics&action=create_auth')); ?>" class="button button-primary">Crear Todas</a>
                                    <?php endif; ?>
                                <?php elseif (!$result['template_assigned']): ?>
                                    <a href="<?php echo esc_url(admin_url("post.php?post={$result['id']}&action=edit")); ?>" class="button">Editar</a>
                                <?php else: ?>
                                    <span style="color: #46b450;">✓ OK</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card" style="max-width: none; margin-top: 20px;">
                <h2>Estado de la API REST</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Endpoint</th>
                            <th style="width: 40%;">Descripción</th>
                            <th style="width: 20%;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($api_status as $endpoint => $info): ?>
                        <tr>
                            <td><code><?php echo esc_html($endpoint); ?></code></td>
                            <td><?php echo esc_html($info['description']); ?></td>
                            <td>
                                <?php if ($info['registered']): ?>
                                    <span style="color: #46b450;">✅ Registrado</span>
                                <?php else: ?>
                                    <span style="color: #dc3232;">❌ No registrado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card" style="max-width: none; margin-top: 20px;">
                <h2>🛠️ Herramientas de Solución</h2>
                <p>Si encuentras problemas, usa estos botones para solucionarlos automáticamente:</p>
                <p>
                    <a href="<?php echo esc_url(admin_url('tools.php?page=agrochamba-profile-diagnostics&action=create_all')); ?>" class="button button-primary button-large">Crear/Actualizar Todas las Páginas</a>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=page&agrochamba_recreate_profile_page=1')); ?>" class="button button-secondary button-large">Solo Recrear Página de Perfil</a>
                    <a href="<?php echo esc_url(admin_url('options-permalink.php')); ?>" class="button button-secondary button-large">Regenerar Permalinks</a>
                </p>
            </div>
            
            <div class="card" style="max-width: none; margin-top: 20px; background: #f0f6fc; border-left: 4px solid #2271b1;">
                <h3>ℹ️ Información del Sistema</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 30%;"><strong>Directorio de templates:</strong></td>
                        <td><code><?php echo esc_html(AGROCHAMBA_TEMPLATES_DIR); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Directorio del plugin:</strong></td>
                        <td><code><?php echo esc_html(AGROCHAMBA_PLUGIN_DIR); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Tema activo:</strong></td>
                        <td><?php echo esc_html(wp_get_theme()->get('Name')); ?> (<?php echo esc_html(wp_get_theme()->get('Version')); ?>)</td>
                    </tr>
                    <tr>
                        <td><strong>WordPress versión:</strong></td>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
    
    // Manejar acciones de diagnóstico
    add_action('admin_init', function() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'agrochamba-profile-diagnostics') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'create_profile':
                    // Crear solo página de perfil
                    if (function_exists('agrochamba_create_profile_page')) {
                        delete_option('agrochamba_profile_page_created');
                        agrochamba_create_profile_page();
                        update_option('agrochamba_profile_page_created', true);
                    }
                    wp_redirect(admin_url('tools.php?page=agrochamba-profile-diagnostics&created=profile'));
                    exit;
                    
                case 'create_auth':
                    // Crear páginas de autenticación
                    if (function_exists('agrochamba_create_auth_pages')) {
                        delete_option('agrochamba_auth_pages_created');
                        agrochamba_create_auth_pages();
                        update_option('agrochamba_auth_pages_created', true);
                    }
                    wp_redirect(admin_url('tools.php?page=agrochamba-profile-diagnostics&created=auth'));
                    exit;
                    
                case 'create_all':
                    // Crear todas las páginas
                    if (function_exists('agrochamba_create_auth_pages')) {
                        delete_option('agrochamba_auth_pages_created');
                        agrochamba_create_auth_pages();
                        update_option('agrochamba_auth_pages_created', true);
                    }
                    if (function_exists('agrochamba_create_profile_page')) {
                        delete_option('agrochamba_profile_page_created');
                        agrochamba_create_profile_page();
                        update_option('agrochamba_profile_page_created', true);
                    }
                    wp_redirect(admin_url('tools.php?page=agrochamba-profile-diagnostics&created=all'));
                    exit;
            }
        }
        
        // Mostrar mensajes de éxito
        if (isset($_GET['created'])) {
            add_action('admin_notices', function() {
                $type = $_GET['created'];
                $messages = array(
                    'profile' => 'Página de perfil creada/actualizada correctamente.',
                    'auth' => 'Páginas de autenticación creadas/actualizadas correctamente.',
                    'all' => 'Todas las páginas han sido creadas/actualizadas correctamente.'
                );
                $message = isset($messages[$type]) ? $messages[$type] : 'Operación completada.';
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            });
        }
    });
}

// ==========================================
// 6. VERIFICAR QUE EL USUARIO ESTÉ LOGUEADO EN LA PÁGINA DE PERFIL
// ==========================================
if (!function_exists('agrochamba_check_profile_access')) {
    function agrochamba_check_profile_access() {
        $profile_page = get_page_by_path('mi-perfil');
        if ($profile_page && is_page($profile_page->ID)) {
            // Si el usuario no está logueado, redirigir al login
            if (!is_user_logged_in()) {
                $login_page = get_page_by_path('login');
                if ($login_page) {
                    $login_url = get_permalink($login_page->ID);
                    $redirect_url = add_query_arg('redirect_to', urlencode(get_permalink($profile_page->ID)), $login_url);
                    wp_redirect($redirect_url);
                    exit;
                } else {
                    wp_redirect(wp_login_url(get_permalink($profile_page->ID)));
                    exit;
                }
            }
        }
    }
    add_action('template_redirect', 'agrochamba_check_profile_access', 1);
}

// ==========================================
// 7. HEADERS DE SEGURIDAD PARA PÁGINA DE PERFIL
// ==========================================
if (!function_exists('agrochamba_add_profile_security_headers')) {
    function agrochamba_add_profile_security_headers() {
        $profile_page = get_page_by_path('mi-perfil');
        if ($profile_page && is_page($profile_page->ID)) {
            // Headers de seguridad
            header('X-Frame-Options: SAMEORIGIN');
            header('X-Content-Type-Options: nosniff');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            // Content Security Policy básico
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;");
        }
    }
    add_action('template_redirect', 'agrochamba_add_profile_security_headers', 1);
}

// ==========================================
// 8. CARGAR TEMPLATE PERSONALIZADO DE PERFIL
// ==========================================
if (!function_exists('agrochamba_load_profile_template')) {
    function agrochamba_load_profile_template($template) {
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
        $is_profile_page = strpos($request_uri, '/mi-perfil') !== false || strpos($request_uri, '?page_id=') !== false;
        
        // Cargar template de perfil
        if ($page_slug === 'mi-perfil' || $page_template === 'profile.php' || ($is_profile_page && empty($page_slug))) {
            // Rutas posibles para el template
            $possible_template_paths = array(
                AGROCHAMBA_TEMPLATES_DIR . '/profile.php',
                AGROCHAMBA_PLUGIN_DIR . '/templates/profile.php',
                dirname(AGROCHAMBA_PLUGIN_DIR) . '/agrochamba-core/templates/profile.php'
            );
            
            foreach ($possible_template_paths as $template_path) {
                if (file_exists($template_path)) {
                    return $template_path;
                }
            }
        }
        
        return $template;
    }
    // Usar ambos filtros para máxima compatibilidad
    add_filter('page_template', 'agrochamba_load_profile_template', 10);
    add_filter('template_include', 'agrochamba_load_profile_template', 99);
    
    // También usar single_template con prioridad alta para asegurar que se cargue
    add_filter('single_template', function($template) {
        return agrochamba_load_profile_template($template);
    }, 99);
}

