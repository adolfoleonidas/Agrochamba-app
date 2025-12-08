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
        error_log('AgroChamba Facebook: Iniciando publicación para post ID: ' . $post_id);
        
        $facebook_enabled = get_option('agrochamba_facebook_enabled', false);
        if (!$facebook_enabled) {
            error_log('AgroChamba Facebook Error: La publicación está deshabilitada');
            return new WP_Error('facebook_disabled', 'La publicación en Facebook está deshabilitada.');
        }

        // Verificar si se usa n8n o método directo
        $use_n8n = get_option('agrochamba_use_n8n', false);
        $n8n_webhook_url = get_option('agrochamba_n8n_webhook_url', '');

        if ($use_n8n && !empty($n8n_webhook_url)) {
            // Usar n8n para publicar
            return agrochamba_post_to_facebook_via_n8n($post_id, $job_data, $n8n_webhook_url);
        }

        // Método directo (legacy)
        $page_access_token = get_option('agrochamba_facebook_page_token', '');
        $page_id = get_option('agrochamba_facebook_page_id', '');

        error_log('AgroChamba Facebook Debug - Page ID: ' . ($page_id ?: 'VACÍO'));
        error_log('AgroChamba Facebook Debug - Page Token: ' . ($page_access_token ? 'EXISTE (' . strlen($page_access_token) . ' caracteres)' : 'VACÍO'));

        if (empty($page_access_token) || empty($page_id)) {
            error_log('AgroChamba Facebook Error: Configuración incompleta. Token: ' . (empty($page_access_token) ? 'FALTA' : 'OK') . ', Page ID: ' . (empty($page_id) ? 'FALTA' : 'OK'));
            return new WP_Error('facebook_config', 'Configuración de Facebook incompleta. Verifica el Page Access Token y Page ID.');
        }

        $message = agrochamba_build_facebook_message($post_id, $job_data);

        // Obtener todas las imágenes (featured + gallery)
        $image_urls = array();
        
        // Imagen destacada (prioridad)
        if (isset($job_data['featured_media']) && !empty($job_data['featured_media'])) {
            $featured_url = wp_get_attachment_image_url($job_data['featured_media'], 'large');
            if ($featured_url) {
                // Asegurar URL absoluta
                $featured_url = (strpos($featured_url, 'http') === 0) ? $featured_url : site_url($featured_url);
                $image_urls[] = $featured_url;
                error_log('AgroChamba Facebook: Imagen destacada encontrada: ' . $featured_url);
            }
        } elseif (has_post_thumbnail($post_id)) {
            $featured_url = get_the_post_thumbnail_url($post_id, 'large');
            if ($featured_url) {
                // Asegurar URL absoluta
                $featured_url = (strpos($featured_url, 'http') === 0) ? $featured_url : site_url($featured_url);
                $image_urls[] = $featured_url;
                error_log('AgroChamba Facebook: Imagen destacada (thumbnail) encontrada: ' . $featured_url);
            }
        }
        
        // Imágenes de la galería (si no están ya incluidas)
        if (isset($job_data['gallery_ids']) && is_array($job_data['gallery_ids']) && !empty($job_data['gallery_ids'])) {
            foreach ($job_data['gallery_ids'] as $gallery_id) {
                $gallery_url = wp_get_attachment_image_url(intval($gallery_id), 'large');
                if ($gallery_url) {
                    // Asegurar URL absoluta
                    $gallery_url = (strpos($gallery_url, 'http') === 0) ? $gallery_url : site_url($gallery_url);
                    if (!in_array($gallery_url, $image_urls)) {
                        $image_urls[] = $gallery_url;
                        error_log('AgroChamba Facebook: Imagen de galería encontrada: ' . $gallery_url);
                    }
                }
            }
        } else {
            // Fallback: obtener gallery_ids del post meta
            $gallery_ids = get_post_meta($post_id, 'gallery_ids', true);
            if (is_array($gallery_ids) && !empty($gallery_ids)) {
                foreach ($gallery_ids as $gallery_id) {
                    $gallery_url = wp_get_attachment_image_url(intval($gallery_id), 'large');
                    if ($gallery_url) {
                        // Asegurar URL absoluta
                        $gallery_url = (strpos($gallery_url, 'http') === 0) ? $gallery_url : site_url($gallery_url);
                        if (!in_array($gallery_url, $image_urls)) {
                            $image_urls[] = $gallery_url;
                            error_log('AgroChamba Facebook: Imagen de galería (meta) encontrada: ' . $gallery_url);
                        }
                    }
                }
            }
        }

        $job_url = get_permalink($post_id);
        $photo_ids = array();

        // Si hay imágenes, subirlas nativamente a Facebook primero (todas las imágenes)
        if (!empty($image_urls)) {
            error_log('AgroChamba Facebook: Subiendo ' . count($image_urls) . ' imagen(es) nativa(s) a Facebook');
            
            foreach ($image_urls as $index => $image_url) {
                error_log('AgroChamba Facebook: Subiendo imagen ' . ($index + 1) . ' de ' . count($image_urls) . ': ' . $image_url);
                
                // Subir la imagen a Facebook usando el endpoint /photos
                $photo_url = "https://graph.facebook.com/v18.0/{$page_id}/photos";
                
                $photo_response = wp_remote_post($photo_url, array(
                    'method' => 'POST',
                    'timeout' => 30,
                    'headers' => array(
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ),
                    'body' => array(
                        'access_token' => $page_access_token,
                        'url' => $image_url, // Facebook descargará la imagen desde la URL
                        'published' => false, // No publicar aún, solo subir
                    ),
                ));
                
                if (!is_wp_error($photo_response)) {
                    $photo_response_code = wp_remote_retrieve_response_code($photo_response);
                    $photo_response_body = json_decode(wp_remote_retrieve_body($photo_response), true);
                    
                    if ($photo_response_code === 200 && isset($photo_response_body['id'])) {
                        $photo_ids[] = $photo_response_body['id'];
                        error_log('AgroChamba Facebook: Imagen ' . ($index + 1) . ' subida exitosamente. Photo ID: ' . $photo_response_body['id']);
                    } else {
                        error_log('AgroChamba Facebook Error al subir imagen ' . ($index + 1) . ': ' . json_encode($photo_response_body));
                    }
                } else {
                    error_log('AgroChamba Facebook Error al conectar para subir imagen ' . ($index + 1) . ': ' . $photo_response->get_error_message());
                }
                
                // Pequeña pausa entre subidas para evitar rate limiting
                if ($index < count($image_urls) - 1) {
                    usleep(500000); // 0.5 segundos
                }
            }
        } else {
            error_log('AgroChamba Facebook: No se encontraron imágenes para el post ID: ' . $post_id);
        }

        // Publicar el post en Facebook
        $post_data = array(
            'message' => $message,
        );

        // Si tenemos photo_ids, adjuntarlos al post (múltiples imágenes)
        if (!empty($photo_ids)) {
            // Construir el array de attached_media en el formato correcto para Facebook
            $attached_media = array();
            foreach ($photo_ids as $photo_id) {
                $attached_media[] = array('media_fbid' => $photo_id);
            }
            // Facebook requiere attached_media como JSON string en el body
            $post_data['attached_media'] = json_encode($attached_media);
            error_log('AgroChamba Facebook: Adjuntando ' . count($photo_ids) . ' foto(s) nativa(s) al post. Photo IDs: ' . implode(', ', $photo_ids));
            error_log('AgroChamba Facebook: attached_media JSON: ' . json_encode($attached_media));
            // NO incluir 'link' cuando hay imágenes adjuntas, ya que Facebook prioriza el link preview sobre las imágenes
            // El link ya está incluido en el mensaje de texto al final
        } else {
            // Solo incluir link si NO hay imágenes adjuntas para mostrar preview del link
            $post_data['link'] = $job_url;
            error_log('AgroChamba Facebook: No hay imágenes, usando link preview');
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

        error_log('AgroChamba Facebook Response Code: ' . $response_code);
        error_log('AgroChamba Facebook Response Body: ' . json_encode($response_body));

        if ($response_code !== 200) {
            $error_message = isset($response_body['error']['message']) 
                ? $response_body['error']['message'] 
                : 'Error desconocido al publicar en Facebook';
            error_log('AgroChamba Facebook Error: ' . $error_message);
            error_log('AgroChamba Facebook Error Details: ' . json_encode($response_body));
            return new WP_Error('facebook_api_error', $error_message, $response_body);
        }

        if (isset($response_body['id'])) {
            update_post_meta($post_id, 'facebook_post_id', $response_body['id']);
            error_log('AgroChamba Facebook Success: Post publicado con ID: ' . $response_body['id']);
        } else {
            error_log('AgroChamba Facebook Warning: Respuesta exitosa pero sin ID de Facebook');
        }

        return array(
            'success' => true,
            'facebook_post_id' => isset($response_body['id']) ? $response_body['id'] : null,
            'message' => 'Trabajo publicado en Facebook correctamente.',
        );
    }
}

// ==========================================
// 1.1. PUBLICAR TRABAJO EN FACEBOOK VÍA N8N
// ==========================================
if (!function_exists('agrochamba_post_to_facebook_via_n8n')) {
    function agrochamba_post_to_facebook_via_n8n($post_id, $job_data, $webhook_url) {
        error_log('AgroChamba n8n: Enviando webhook para post ID: ' . $post_id);
        
        // Construir mensaje para Facebook
        $message = agrochamba_build_facebook_message($post_id, $job_data);
        
        // Obtener todas las imágenes (featured + gallery)
        $image_urls = array();
        
        // Imagen destacada (prioridad)
        if (isset($job_data['featured_media']) && !empty($job_data['featured_media'])) {
            $featured_url = wp_get_attachment_image_url($job_data['featured_media'], 'large');
            if ($featured_url) {
                // Asegurar URL absoluta
                $featured_url = (strpos($featured_url, 'http') === 0) ? $featured_url : site_url($featured_url);
                $image_urls[] = $featured_url;
            }
        } elseif (has_post_thumbnail($post_id)) {
            $featured_url = get_the_post_thumbnail_url($post_id, 'large');
            if ($featured_url) {
                // Asegurar URL absoluta
                $featured_url = (strpos($featured_url, 'http') === 0) ? $featured_url : site_url($featured_url);
                $image_urls[] = $featured_url;
            }
        }
        
        // Imágenes de la galería (si no están ya incluidas)
        if (isset($job_data['gallery_ids']) && is_array($job_data['gallery_ids']) && !empty($job_data['gallery_ids'])) {
            foreach ($job_data['gallery_ids'] as $gallery_id) {
                $gallery_url = wp_get_attachment_image_url(intval($gallery_id), 'large');
                if ($gallery_url) {
                    // Asegurar URL absoluta
                    $gallery_url = (strpos($gallery_url, 'http') === 0) ? $gallery_url : site_url($gallery_url);
                    if (!in_array($gallery_url, $image_urls)) {
                        $image_urls[] = $gallery_url;
                    }
                }
            }
        } else {
            // Fallback: obtener gallery_ids del post meta
            $gallery_ids = get_post_meta($post_id, 'gallery_ids', true);
            if (is_array($gallery_ids) && !empty($gallery_ids)) {
                foreach ($gallery_ids as $gallery_id) {
                    $gallery_url = wp_get_attachment_image_url(intval($gallery_id), 'large');
                    if ($gallery_url) {
                        // Asegurar URL absoluta
                        $gallery_url = (strpos($gallery_url, 'http') === 0) ? $gallery_url : site_url($gallery_url);
                        if (!in_array($gallery_url, $image_urls)) {
                            $image_urls[] = $gallery_url;
                        }
                    }
                }
            }
        }
        
        // Obtener URL del trabajo
        $job_url = get_permalink($post_id);
        
        // Preparar payload para n8n
        $payload = array(
            'post_id' => $post_id,
            'title' => get_the_title($post_id),
            'message' => $message,
            'link' => $job_url,
            'image_url' => !empty($image_urls) ? $image_urls[0] : null, // Primera imagen para compatibilidad
            'image_urls' => $image_urls, // Todas las imágenes para n8n
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
        );
        
        // Enviar webhook a n8n
        $response = wp_remote_post($webhook_url, array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($payload),
        ));
        
        if (is_wp_error($response)) {
            error_log('AgroChamba n8n Error: ' . $response->get_error_message());
            return new WP_Error('n8n_request_error', 'Error al conectar con n8n: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        error_log('AgroChamba n8n Response Code: ' . $response_code);
        error_log('AgroChamba n8n Response Body: ' . json_encode($response_body));
        
        if ($response_code >= 200 && $response_code < 300) {
            // n8n puede devolver el facebook_post_id en la respuesta
            $facebook_post_id = isset($response_body['facebook_post_id']) ? $response_body['facebook_post_id'] : null;
            
            if ($facebook_post_id) {
                update_post_meta($post_id, 'facebook_post_id', $facebook_post_id);
                error_log('AgroChamba n8n Success: Post publicado con ID: ' . $facebook_post_id);
            } else {
                error_log('AgroChamba n8n Success: Webhook enviado correctamente (ID de Facebook pendiente)');
            }
            
            return array(
                'success' => true,
                'facebook_post_id' => $facebook_post_id,
                'message' => 'Trabajo enviado a n8n para publicación en Facebook.',
            );
        } else {
            $error_message = isset($response_body['error']) 
                ? (is_array($response_body['error']) ? json_encode($response_body['error']) : $response_body['error'])
                : 'Error desconocido al enviar a n8n';
            error_log('AgroChamba n8n Error: ' . $error_message);
            return new WP_Error('n8n_api_error', $error_message, $response_body);
        }
    }
}

// ==========================================
// 2. CONSTRUIR MENSAJE PARA FACEBOOK
// ==========================================
if (!function_exists('agrochamba_get_emoji_for_crop')) {
    /**
     * Obtener emoji basado en el cultivo
     */
    function agrochamba_get_emoji_for_crop($crop_name) {
        if (empty($crop_name)) {
            return '🌱';
        }
        
        $crop_lower = strtolower($crop_name);
        
        if (strpos($crop_lower, 'uva') !== false) {
            return '🍇';
        } elseif (strpos($crop_lower, 'arándano') !== false || strpos($crop_lower, 'arandano') !== false) {
            return '🫐';
        } elseif (strpos($crop_lower, 'palta') !== false || strpos($crop_lower, 'aguacate') !== false) {
            return '🥑';
        } elseif (strpos($crop_lower, 'mango') !== false) {
            return '🥭';
        } elseif (strpos($crop_lower, 'fresa') !== false || strpos($crop_lower, 'frutilla') !== false) {
            return '🍓';
        } elseif (strpos($crop_lower, 'limón') !== false || strpos($crop_lower, 'limon') !== false) {
            return '🍋';
        } elseif (strpos($crop_lower, 'naranja') !== false) {
            return '🍊';
        } elseif (strpos($crop_lower, 'plátano') !== false || strpos($crop_lower, 'platano') !== false || strpos($crop_lower, 'banana') !== false) {
            return '🍌';
        } else {
            return '🌱';
        }
    }
}

if (!function_exists('agrochamba_build_facebook_message')) {
    function agrochamba_build_facebook_message($post_id, $job_data) {
        $title = get_the_title($post_id);
        $content = get_post_field('post_content', $post_id);
        
        // Obtener empresa y ubicación
        $empresas = wp_get_post_terms($post_id, 'empresa', array('fields' => 'names'));
        $empresa = !empty($empresas) ? $empresas[0] : '';
        
        $ubicaciones = wp_get_post_terms($post_id, 'ubicacion', array('fields' => 'names'));
        $ubicacion = !empty($ubicaciones) ? $ubicaciones[0] : '';
        
        // Obtener cultivo para determinar el emoji
        $cultivos = wp_get_post_terms($post_id, 'cultivo', array('fields' => 'names'));
        $cultivo = !empty($cultivos) ? $cultivos[0] : '';
        $emoji = agrochamba_get_emoji_for_crop($cultivo);
        
        // Construir encabezado dinámico: #UBICACION | 🍇 EMPRESA
        $header = '';
        if (!empty($ubicacion) && !empty($empresa)) {
            $header = '#' . strtoupper($ubicacion) . ' | ' . $emoji . ' ' . $empresa;
        } elseif (!empty($ubicacion)) {
            $header = '#' . strtoupper($ubicacion);
        } elseif (!empty($empresa)) {
            $header = $emoji . ' ' . $empresa;
        }
        
        // Preservar formato: convertir HTML a texto plano manteniendo saltos de línea
        // Primero convertir <br>, <p>, <div> a saltos de línea
        $content = preg_replace('/<br\s*\/?>/i', "\n", $content);
        $content = preg_replace('/<\/p>/i', "\n\n", $content);
        $content = preg_replace('/<\/div>/i', "\n", $content);
        $content = preg_replace('/<li>/i', "• ", $content);
        $content = preg_replace('/<\/li>/i', "\n", $content);
        
        // Remover otros tags HTML pero mantener el texto
        $content = wp_strip_all_tags($content);
        
        // Limpiar espacios múltiples y saltos de línea excesivos (máximo 2 saltos seguidos)
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        $content = trim($content);
        
        // NO truncar el contenido - mostrar todo lo que el usuario escribió
        
        // Obtener valores de meta fields (solo si fueron rellenados)
        $salario_min = get_post_meta($post_id, 'salario_min', true);
        $salario_max = get_post_meta($post_id, 'salario_max', true);
        $vacantes = get_post_meta($post_id, 'vacantes', true);
        
        // Construir mensaje con encabezado dinámico
        $message = '';
        
        if (!empty($header)) {
            $message .= $header . "\n\n";
        }
        
        $message .= $title . "\n\n";
        
        // El contenido del editor es lo principal - mostrarlo tal cual
        if (!empty($content)) {
            $message .= $content;
        }
        
        // Solo agregar campos adicionales si fueron rellenados (no valores por defecto)
        $additional_info = array();
        
        // Ubicación: solo si está definida
        if (!empty($ubicacion)) {
            $additional_info[] = "📍 Ubicación: " . $ubicacion;
        }
        
        // Salario: solo si tiene valores reales (mayor a 0)
        $salario_min_val = intval($salario_min);
        $salario_max_val = intval($salario_max);
        if ($salario_min_val > 0 || $salario_max_val > 0) {
            $salario_text = '';
            if ($salario_min_val > 0 && $salario_max_val > 0) {
                $salario_text = "S/ " . number_format($salario_min_val, 0) . " - S/ " . number_format($salario_max_val, 0);
            } elseif ($salario_min_val > 0) {
                $salario_text = "Desde S/ " . number_format($salario_min_val, 0);
            } elseif ($salario_max_val > 0) {
                $salario_text = "Hasta S/ " . number_format($salario_max_val, 0);
            }
            if (!empty($salario_text)) {
                $additional_info[] = "💰 Salario: " . $salario_text;
            }
        }
        
        // Vacantes: solo si tiene un valor real (mayor a 0 y no es el valor por defecto de 1)
        $vacantes_val = intval($vacantes);
        if ($vacantes_val > 1) { // No mostrar si es 0 o 1 (valor por defecto)
            $additional_info[] = "👥 Vacantes: " . $vacantes_val;
        }
        
        // Agregar información adicional solo si hay algo que mostrar
        if (!empty($additional_info)) {
            $message .= "\n\n" . implode("\n", $additional_info);
        }
        
        // Agregar link al final del mensaje (se incluirá en el texto)
        $job_url = get_permalink($post_id);
        $message .= "\n\n👉 Ver más detalles: " . $job_url;
        
        return $message;
    }
}

// ==========================================
// 3. HOOK PARA PUBLICAR AUTOMÁTICAMENTE
// ==========================================
// DESHABILITADO: Ya no publicamos automáticamente en Facebook.
// La publicación solo ocurre cuando el usuario lo solicita explícitamente mediante publish_to_facebook.
// Este hook causaba que los trabajos se publicaran sin el consentimiento del usuario.
/*
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
*/

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
            update_option('agrochamba_use_n8n', isset($_POST['use_n8n']));
            update_option('agrochamba_n8n_webhook_url', sanitize_text_field($_POST['n8n_webhook_url']));
            update_option('agrochamba_facebook_page_token', sanitize_text_field($_POST['facebook_page_token']));
            update_option('agrochamba_facebook_page_id', sanitize_text_field($_POST['facebook_page_id']));
            
            echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
        }
        
        $facebook_enabled = get_option('agrochamba_facebook_enabled', false);
        $use_n8n = get_option('agrochamba_use_n8n', false);
        $n8n_webhook_url = get_option('agrochamba_n8n_webhook_url', '');
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
                        <th scope="row">Método de publicación</th>
                        <td>
                            <label>
                                <input type="checkbox" name="use_n8n" value="1" <?php checked($use_n8n, true); ?>>
                                Usar n8n para automatización (recomendado)
                            </label>
                            <p class="description">
                                Si está marcado, WordPress enviará un webhook a n8n que se encargará de publicar en Facebook.<br>
                                Si no está marcado, WordPress publicará directamente usando las credenciales de Facebook.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">URL del Webhook de n8n</th>
                        <td>
                            <input type="url" name="n8n_webhook_url" value="<?php echo esc_attr($n8n_webhook_url); ?>" class="regular-text" placeholder="https://tu-n8n.com/webhook/facebook" />
                            <p class="description">
                                URL del webhook de n8n que recibirá los datos del trabajo para publicar en Facebook.<br>
                                Solo necesario si "Usar n8n" está habilitado. Ver documentación en <code>docs/N8N_SETUP.md</code>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Page Access Token</th>
                        <td>
                            <input type="text" name="facebook_page_token" value="<?php echo esc_attr($page_token); ?>" class="regular-text" />
                            <p class="description">
                                Token de acceso de larga duración de tu página de Facebook.<br>
                                Solo necesario si NO usas n8n. Puedes obtenerlo desde: <a href="https://developers.facebook.com/tools/explorer/" target="_blank">Graph API Explorer</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Page ID</th>
                        <td>
                            <input type="text" name="facebook_page_id" value="<?php echo esc_attr($page_id); ?>" class="regular-text" />
                            <p class="description">
                                ID de tu página de Facebook (puedes encontrarlo en la configuración de tu página).<br>
                                Solo necesario si NO usas n8n.
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


