<?php
/**
 * =============================================================
 * MÓDULO 9: INTEGRACIÓN CON FACEBOOK
 * =============================================================
 * 
 * Funcionalidad para publicar trabajos automáticamente en Facebook
 * cuando se crean en la aplicación.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. PUBLICAR TRABAJO EN FACEBOOK
// ==========================================
if (!function_exists('agrochamba_post_to_facebook')) {
    function agrochamba_post_to_facebook($post_id, $job_data) {
        $facebook_enabled = get_option('agrochamba_facebook_enabled', false);
        if (!$facebook_enabled) {
            return new WP_Error('facebook_disabled', 'La publicación en Facebook está deshabilitada.');
        }

        $page_access_token = get_option('agrochamba_facebook_page_token', '');
        $page_id = get_option('agrochamba_facebook_page_id', '');

        if (empty($page_access_token) || empty($page_id)) {
            return new WP_Error('facebook_config', 'Configuración de Facebook incompleta. Verifica el Page Access Token y Page ID.');
        }

        $message = agrochamba_build_facebook_message($post_id, $job_data);

        $image_url = null;
        if (isset($job_data['featured_media']) && !empty($job_data['featured_media'])) {
            $image_url = wp_get_attachment_image_url($job_data['featured_media'], 'large');
        } elseif (has_post_thumbnail($post_id)) {
            $image_url = get_the_post_thumbnail_url($post_id, 'large');
        }

        $job_url = get_permalink($post_id);

        $post_data = array(
            'message' => $message,
            'link' => $job_url,
        );

        if ($image_url) {
            $post_data['picture'] = $image_url;
        }

        $graph_url = "https://graph.facebook.com/v18.0/{$page_id}/feed";

        $response = wp_remote_post($graph_url, array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => array_merge($post_data, array(
                'access_token' => $page_access_token,
            )),
        ));

        if (is_wp_error($response)) {
            return new WP_Error('facebook_request_error', 'Error al conectar con Facebook: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            $error_message = isset($response_body['error']['message']) 
                ? $response_body['error']['message'] 
                : 'Error desconocido al publicar en Facebook';
            return new WP_Error('facebook_api_error', $error_message, $response_body);
        }

        if (isset($response_body['id'])) {
            update_post_meta($post_id, 'facebook_post_id', $response_body['id']);
        }

        return array(
            'success' => true,
            'facebook_post_id' => isset($response_body['id']) ? $response_body['id'] : null,
            'message' => 'Trabajo publicado en Facebook correctamente.',
        );
    }
}

// ==========================================
// 2. CONSTRUIR MENSAJE PARA FACEBOOK
// ==========================================
if (!function_exists('agrochamba_build_facebook_message')) {
    function agrochamba_build_facebook_message($post_id, $job_data) {
        $title = get_the_title($post_id);
        $content = get_post_field('post_content', $post_id);
        
        $content = wp_strip_all_tags($content);
        $content = wp_trim_words($content, 50, '...');
        
        $ubicaciones = wp_get_post_terms($post_id, 'ubicacion', array('fields' => 'names'));
        $ubicacion = !empty($ubicaciones) ? $ubicaciones[0] : '';
        
        $salario_min = get_post_meta($post_id, 'salario_min', true);
        $salario_max = get_post_meta($post_id, 'salario_max', true);
        $vacantes = get_post_meta($post_id, 'vacantes', true);
        
        $message = "🌾 NUEVA OFERTA DE TRABAJO AGRÍCOLA 🌾\n\n";
        $message .= "📋 " . $title . "\n\n";
        
        if (!empty($content)) {
            $message .= $content . "\n\n";
        }
        
        if (!empty($ubicacion)) {
            $message .= "📍 Ubicación: " . $ubicacion . "\n";
        }
        
        if (!empty($salario_min) || !empty($salario_max)) {
            $salario_text = '';
            if (!empty($salario_min) && !empty($salario_max)) {
                $salario_text = "S/ " . number_format($salario_min, 0) . " - S/ " . number_format($salario_max, 0);
            } elseif (!empty($salario_min)) {
                $salario_text = "Desde S/ " . number_format($salario_min, 0);
            } elseif (!empty($salario_max)) {
                $salario_text = "Hasta S/ " . number_format($salario_max, 0);
            }
            if (!empty($salario_text)) {
                $message .= "💰 Salario: " . $salario_text . "\n";
            }
        }
        
        if (!empty($vacantes)) {
            $message .= "👥 Vacantes: " . $vacantes . "\n";
        }
        
        $message .= "\n👉 Ver más detalles en AgroChamba";
        
        return $message;
    }
}

// ==========================================
// 3. HOOK PARA PUBLICAR AUTOMÁTICAMENTE
// ==========================================
if (!function_exists('agrochamba_auto_post_to_facebook')) {
    function agrochamba_auto_post_to_facebook($post_id, $post, $update) {
        if ($update) {
            return;
        }
        
        if ($post->post_type !== 'trabajo') {
            return;
        }
        
        if ($post->post_status !== 'publish') {
            return;
        }
        
        $already_posted = get_post_meta($post_id, 'facebook_post_id', true);
        if (!empty($already_posted)) {
            return;
        }
        
        $job_data = array(
            'title' => $post->post_title,
            'content' => $post->post_content,
            'featured_media' => get_post_thumbnail_id($post_id),
        );
        
        wp_schedule_single_event(time() + 5, 'agrochamba_publish_to_facebook', array($post_id, $job_data));
    }
    add_action('wp_insert_post', 'agrochamba_auto_post_to_facebook', 10, 3);
}

// ==========================================
// 4. EJECUTAR PUBLICACIÓN EN FACEBOOK
// ==========================================
if (!function_exists('agrochamba_execute_facebook_post')) {
    function agrochamba_execute_facebook_post($post_id, $job_data) {
        $result = agrochamba_post_to_facebook($post_id, $job_data);
        
        if (is_wp_error($result)) {
            error_log('Error al publicar en Facebook: ' . $result->get_error_message());
            update_post_meta($post_id, 'facebook_post_error', $result->get_error_message());
        } else {
            error_log('Trabajo publicado en Facebook correctamente. Post ID: ' . $post_id);
        }
    }
    add_action('agrochamba_publish_to_facebook', 'agrochamba_execute_facebook_post', 10, 2);
}

// ==========================================
// 5. PÁGINA DE CONFIGURACIÓN EN ADMIN
// ==========================================
if (!function_exists('agrochamba_facebook_settings_menu')) {
    function agrochamba_facebook_settings_menu() {
        add_options_page(
            'Configuración de Facebook',
            'Facebook Integration',
            'manage_options',
            'agrochamba-facebook',
            'agrochamba_facebook_settings_page'
        );
    }
    add_action('admin_menu', 'agrochamba_facebook_settings_menu');
}

if (!function_exists('agrochamba_facebook_settings_page')) {
    function agrochamba_facebook_settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('agrochamba_facebook_settings');
            
            update_option('agrochamba_facebook_enabled', isset($_POST['facebook_enabled']));
            update_option('agrochamba_facebook_page_token', sanitize_text_field($_POST['facebook_page_token']));
            update_option('agrochamba_facebook_page_id', sanitize_text_field($_POST['facebook_page_id']));
            
            echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
        }
        
        $facebook_enabled = get_option('agrochamba_facebook_enabled', false);
        $page_token = get_option('agrochamba_facebook_page_token', '');
        $page_id = get_option('agrochamba_facebook_page_id', '');
        ?>
        <div class="wrap">
            <h1>Configuración de Facebook</h1>
            <p>Configura la integración con Facebook para publicar trabajos automáticamente.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('agrochamba_facebook_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Habilitar publicación en Facebook</th>
                        <td>
                            <label>
                                <input type="checkbox" name="facebook_enabled" value="1" <?php checked($facebook_enabled, true); ?>>
                                Publicar trabajos automáticamente en Facebook
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Page Access Token</th>
                        <td>
                            <input type="text" name="facebook_page_token" value="<?php echo esc_attr($page_token); ?>" class="regular-text" />
                            <p class="description">
                                Token de acceso de larga duración de tu página de Facebook.<br>
                                Puedes obtenerlo desde: <a href="https://developers.facebook.com/tools/explorer/" target="_blank">Graph API Explorer</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Page ID</th>
                        <td>
                            <input type="text" name="facebook_page_id" value="<?php echo esc_attr($page_id); ?>" class="regular-text" />
                            <p class="description">
                                ID de tu página de Facebook (puedes encontrarlo en la configuración de tu página).
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

