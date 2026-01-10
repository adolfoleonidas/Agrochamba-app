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
            // Usar n8n para publicar (soporta múltiples páginas internamente)
            return agrochamba_post_to_facebook_via_n8n($post_id, $job_data, $n8n_webhook_url);
        }

        // ==========================================
        // PUBLICACIÓN EN MÚLTIPLES PÁGINAS
        // ==========================================
        
        // Obtener páginas habilitadas del nuevo sistema
        $pages = array();
        if (function_exists('agrochamba_get_enabled_facebook_pages')) {
            $pages = agrochamba_get_enabled_facebook_pages();
        }
        
        // Si hay páginas configuradas en el nuevo sistema, usarlas
        if (!empty($pages)) {
            error_log('AgroChamba Facebook: Publicando en ' . count($pages) . ' página(s) configurada(s)');
            return agrochamba_post_to_multiple_facebook_pages($post_id, $job_data, $pages);
        }
        
        // Fallback: Método directo legacy (una sola página)
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
        
        // Verificar si el usuario prefiere usar link preview en lugar de imágenes adjuntas
        $use_link_preview = isset($job_data['facebook_use_link_preview']) && filter_var($job_data['facebook_use_link_preview'], FILTER_VALIDATE_BOOLEAN);
        
        if ($use_link_preview) {
            error_log('AgroChamba Facebook: Usuario prefiere usar link preview en lugar de imágenes adjuntas');
        }

        // Si hay imágenes Y el usuario NO prefiere link preview, subirlas nativamente a Facebook
        if (!empty($image_urls) && !$use_link_preview) {
            error_log('AgroChamba Facebook: Subiendo ' . count($image_urls) . ' imagen(es) nativa(s) a Facebook (usuario prefiere imágenes adjuntas)');
            
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
            if ($use_link_preview && !empty($image_urls)) {
                error_log('AgroChamba Facebook: Usuario prefiere link preview, no se subirán imágenes nativas aunque estén disponibles');
            } elseif (empty($image_urls)) {
                error_log('AgroChamba Facebook: No se encontraron imágenes para el post ID: ' . $post_id);
            }
        }

        // Publicar el post en Facebook
        $post_data = array(
            'message' => $message,
        );

        // Si tenemos photo_ids Y el usuario NO prefiere link preview, adjuntarlos al post (múltiples imágenes)
        if (!empty($photo_ids) && !$use_link_preview) {
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
            // Usar link preview si:
            // 1. El usuario prefiere link preview, O
            // 2. No hay imágenes disponibles
            $post_data['link'] = $job_url;
            if ($use_link_preview) {
                error_log('AgroChamba Facebook: Usando link preview (preferencia del usuario)');
            } else {
                error_log('AgroChamba Facebook: No hay imágenes, usando link preview');
            }
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
// 1.1. PUBLICAR EN MÚLTIPLES PÁGINAS DE FACEBOOK
// ==========================================
if (!function_exists('agrochamba_post_to_multiple_facebook_pages')) {
    function agrochamba_post_to_multiple_facebook_pages($post_id, $job_data, $pages) {
        $message = agrochamba_build_facebook_message($post_id, $job_data);
        $image_urls = agrochamba_get_facebook_images($post_id, $job_data);
        $job_url = get_permalink($post_id);
        $use_link_preview = isset($job_data['facebook_use_link_preview']) && filter_var($job_data['facebook_use_link_preview'], FILTER_VALIDATE_BOOLEAN);
        
        $results = array();
        $success_count = 0;
        $error_count = 0;
        $all_post_ids = array();
        
        foreach ($pages as $page) {
            $page_id = $page['page_id'];
            $page_token = $page['page_token'];
            $page_name = $page['page_name'] ?? 'Página';
            
            error_log("AgroChamba Facebook: Publicando en página '{$page_name}' (ID: {$page_id})");
            
            // Subir imágenes a esta página si es necesario
            $photo_ids = array();
            if (!empty($image_urls) && !$use_link_preview) {
                foreach ($image_urls as $index => $image_url) {
                    $photo_url = "https://graph.facebook.com/v18.0/{$page_id}/photos";
                    
                    $photo_response = wp_remote_post($photo_url, array(
                        'method' => 'POST',
                        'timeout' => 30,
                        'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
                        'body' => array(
                            'access_token' => $page_token,
                            'url' => $image_url,
                            'published' => false,
                        ),
                    ));
                    
                    if (!is_wp_error($photo_response)) {
                        $photo_response_code = wp_remote_retrieve_response_code($photo_response);
                        $photo_response_body = json_decode(wp_remote_retrieve_body($photo_response), true);
                        
                        if ($photo_response_code === 200 && isset($photo_response_body['id'])) {
                            $photo_ids[] = $photo_response_body['id'];
                        }
                    }
                    
                    // Pausa entre subidas
                    if ($index < count($image_urls) - 1) {
                        usleep(300000);
                    }
                }
            }
            
            // Preparar datos del post
            $post_data = array('message' => $message);
            
            if (!empty($photo_ids) && !$use_link_preview) {
                $attached_media = array();
                foreach ($photo_ids as $photo_id) {
                    $attached_media[] = array('media_fbid' => $photo_id);
                }
                $post_data['attached_media'] = json_encode($attached_media);
            } else {
                $post_data['link'] = $job_url;
            }
            
            // Publicar en el feed de la página
            $graph_url = "https://graph.facebook.com/v18.0/{$page_id}/feed";
            
            $response = wp_remote_post($graph_url, array(
                'method' => 'POST',
                'timeout' => 30,
                'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
                'body' => array_merge($post_data, array('access_token' => $page_token)),
            ));
            
            if (is_wp_error($response)) {
                $error_count++;
                $results[] = array(
                    'page_name' => $page_name,
                    'page_id' => $page_id,
                    'success' => false,
                    'error' => $response->get_error_message()
                );
                error_log("AgroChamba Facebook Error en página '{$page_name}': " . $response->get_error_message());
                continue;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($response_code === 200 && isset($response_body['id'])) {
                $success_count++;
                $all_post_ids[] = $response_body['id'];
                $results[] = array(
                    'page_name' => $page_name,
                    'page_id' => $page_id,
                    'success' => true,
                    'facebook_post_id' => $response_body['id']
                );
                error_log("AgroChamba Facebook: Publicado en '{$page_name}' con ID: " . $response_body['id']);
            } else {
                $error_count++;
                $error_message = isset($response_body['error']['message']) 
                    ? $response_body['error']['message'] 
                    : 'Error desconocido';
                $results[] = array(
                    'page_name' => $page_name,
                    'page_id' => $page_id,
                    'success' => false,
                    'error' => $error_message
                );
                error_log("AgroChamba Facebook Error en página '{$page_name}': {$error_message}");
            }
            
            // Pausa entre publicaciones en diferentes páginas
            usleep(500000);
        }
        
        // Guardar todos los IDs de posts de Facebook
        if (!empty($all_post_ids)) {
            update_post_meta($post_id, 'facebook_post_id', $all_post_ids[0]); // Principal
            update_post_meta($post_id, 'facebook_post_ids', $all_post_ids); // Todos
        }
        
        // Retornar resultado consolidado
        return array(
            'success' => $success_count > 0,
            'message' => "Publicado en {$success_count} de " . count($pages) . " páginas",
            'facebook_post_id' => !empty($all_post_ids) ? $all_post_ids[0] : null,
            'facebook_post_ids' => $all_post_ids,
            'pages_results' => $results,
            'success_count' => $success_count,
            'error_count' => $error_count
        );
    }
}

// ==========================================
// 1.2. OBTENER IMÁGENES PARA FACEBOOK
// ==========================================
if (!function_exists('agrochamba_get_facebook_images')) {
    function agrochamba_get_facebook_images($post_id, $job_data) {
        $image_urls = array();
        
        // Imagen destacada
        if (isset($job_data['featured_media']) && !empty($job_data['featured_media'])) {
            $featured_url = wp_get_attachment_image_url($job_data['featured_media'], 'large');
            if ($featured_url) {
                $featured_url = (strpos($featured_url, 'http') === 0) ? $featured_url : site_url($featured_url);
                $image_urls[] = $featured_url;
            }
        } elseif (has_post_thumbnail($post_id)) {
            $featured_url = get_the_post_thumbnail_url($post_id, 'large');
            if ($featured_url) {
                $featured_url = (strpos($featured_url, 'http') === 0) ? $featured_url : site_url($featured_url);
                $image_urls[] = $featured_url;
            }
        }
        
        // Galería
        $gallery_ids = isset($job_data['gallery_ids']) && is_array($job_data['gallery_ids']) 
            ? $job_data['gallery_ids'] 
            : get_post_meta($post_id, 'gallery_ids', true);
            
        if (is_array($gallery_ids) && !empty($gallery_ids)) {
            foreach ($gallery_ids as $gallery_id) {
                $gallery_url = wp_get_attachment_image_url(intval($gallery_id), 'large');
                if ($gallery_url) {
                    $gallery_url = (strpos($gallery_url, 'http') === 0) ? $gallery_url : site_url($gallery_url);
                    if (!in_array($gallery_url, $image_urls)) {
                        $image_urls[] = $gallery_url;
                    }
                }
            }
        }
        
        return $image_urls;
    }
}

// ==========================================
// 1.3. PUBLICAR TRABAJO EN FACEBOOK VÍA N8N
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
        
        // Verificar si el usuario prefiere usar link preview en lugar de imágenes adjuntas
        $use_link_preview = isset($job_data['facebook_use_link_preview']) && filter_var($job_data['facebook_use_link_preview'], FILTER_VALIDATE_BOOLEAN);
        
        // Preparar payload para n8n
        $title = get_the_title($post_id);
        // Decodificar entidades HTML del título (ej: &#8211; -> –)
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        $payload = array(
            'post_id' => $post_id,
            'title' => $title,
            'message' => $message,
            'link' => $job_url,
            'image_url' => !empty($image_urls) ? $image_urls[0] : null, // Primera imagen para compatibilidad
            'image_urls' => $image_urls, // Todas las imágenes para n8n
            'use_link_preview' => $use_link_preview, // Preferencia del usuario
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

/**
 * Convierte HTML a texto formateado para Facebook.
 * Facebook no soporta HTML, pero podemos usar emojis y estructura para simular formato.
 * 
 * Conversiones:
 * - <strong>/<b> → Texto en MAYÚSCULAS o con emoji 📌
 * - <ul><li> → ✅ item (viñetas con emoji)
 * - <ol><li> → 1️⃣ item (números con emoji)
 * - <p> → Doble salto de línea
 * - <br> → Salto de línea simple
 */
if (!function_exists('agrochamba_html_to_facebook_text')) {
    function agrochamba_html_to_facebook_text($html) {
        if (empty($html)) {
            return '';
        }
        
        $text = $html;
        
        // Paso 0: Normalizar saltos de línea y espacios en el HTML
        $text = str_replace(array("\r\n", "\r"), "\n", $text);
        
        // Paso 1: Convertir listas PRIMERO (antes de procesar negrita)
        // Esto asegura que las listas se conviertan correctamente
        
        // Convertir listas numeradas <ol> a números con emoji
        $text = preg_replace_callback(
            '/<ol[^>]*>(.*?)<\/ol>/is',
            function($matches) {
                $list_content = $matches[1];
                $counter = 1;
                $number_emojis = array('1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟');
                $items = array();
                
                preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $list_content, $li_matches);
                foreach ($li_matches[1] as $li_content) {
                    $item_content = trim(strip_tags($li_content));
                    if (!empty($item_content)) {
                        $emoji = isset($number_emojis[$counter - 1]) ? $number_emojis[$counter - 1] : $counter . '.';
                        $items[] = $emoji . ' ' . $item_content;
                        $counter++;
                    }
                }
                
                return "\n" . implode("\n", $items) . "\n";
            },
            $text
        );
        
        // Convertir listas con viñetas <ul> a emojis
        $text = preg_replace_callback(
            '/<ul[^>]*>(.*?)<\/ul>/is',
            function($matches) {
                $list_content = $matches[1];
                $items = array();
                
                preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $list_content, $li_matches);
                foreach ($li_matches[1] as $li_content) {
                    $item_content = trim(strip_tags($li_content));
                    if (!empty($item_content)) {
                        $items[] = '✅ ' . $item_content;
                    }
                }
                
                return "\n" . implode("\n", $items) . "\n";
            },
            $text
        );
        
        // Paso 2: Convertir palabras clave en negrita a formato destacado
        $keyword_patterns = array(
            'requisitos' => '📋',
            'beneficios' => '🎁',
            'funciones' => '📝',
            'responsabilidades' => '📝',
            'contacto' => '📞',
            'informes' => 'ℹ️',
            'consultas' => '❓',
            'importante' => '⚠️',
            'nota' => '📌',
            'ubicación' => '📍',
            'ubicacion' => '📍',
            'horario' => '🕐',
            'salario' => '💰',
            'experiencia' => '💼',
            'detalles' => '📄',
            'oportunidades' => '💼',
            'fechas' => '📅',
        );
        
        // Convertir <strong>Palabra:</strong> a EMOJI PALABRA: (con salto de línea después)
        $text = preg_replace_callback(
            '/<(strong|b)>([^<]+)<\/(strong|b)>/i',
            function($matches) use ($keyword_patterns) {
                $content = trim($matches[2]);
                $content_lower = mb_strtolower($content, 'UTF-8');
                
                // Buscar si es una palabra clave
                foreach ($keyword_patterns as $keyword => $emoji) {
                    if (strpos($content_lower, $keyword) !== false) {
                        // Es una palabra clave, agregar emoji y poner en mayúsculas
                        $clean_content = preg_replace('/[:\s]+$/', '', $content);
                        // Agregar salto de línea antes Y después para que se vea separado
                        return "\n\n" . $emoji . ' ' . mb_strtoupper($clean_content, 'UTF-8') . ":\n";
                    }
                }
                
                // No es palabra clave, solo poner en mayúsculas
                return mb_strtoupper($content, 'UTF-8');
            },
            $text
        );
        
        // Paso 3: Convertir elementos de bloque a saltos de línea
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<\/p>/i', "\n", $text);
        $text = preg_replace('/<p[^>]*>/i', "", $text);
        $text = preg_replace('/<\/div>/i', "\n", $text);
        $text = preg_replace('/<div[^>]*>/i', "", $text);
        
        // Paso 4: Convertir <em>/<i> a _texto_ (simular cursiva)
        $text = preg_replace('/<(em|i)>([^<]+)<\/(em|i)>/i', '_$2_', $text);
        
        // Paso 5: Remover cualquier HTML restante
        $text = wp_strip_all_tags($text);
        
        // Paso 6: Decodificar entidades HTML
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Paso 7: Limpiar espacios horizontales (NO tocar saltos de línea aún)
        $text = preg_replace('/[ \t]+/', ' ', $text); // Múltiples espacios a uno solo
        
        // Paso 8: Procesar línea por línea para limpiar sin perder estructura
        $lines = explode("\n", $text);
        $cleaned_lines = array();
        $prev_was_empty = false;
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            if (empty($trimmed)) {
                // Línea vacía - solo agregar si la anterior no era vacía (evitar múltiples líneas vacías)
                if (!$prev_was_empty && count($cleaned_lines) > 0) {
                    $cleaned_lines[] = '';
                    $prev_was_empty = true;
                }
            } else {
                $cleaned_lines[] = $trimmed;
                $prev_was_empty = false;
            }
        }
        
        $text = implode("\n", $cleaned_lines);
        $text = trim($text);
        
        return $text;
    }
}

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
        // Decodificar entidades HTML del título (ej: &#8211; -> –)
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
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
        
        // Convertir HTML a texto formateado para Facebook (no soporta HTML nativo)
        $content = agrochamba_html_to_facebook_text($content);
        
        // Verificar si se debe acortar el contenido
        // Prioridad: 1) Preferencia del usuario desde la app, 2) Configuración global del admin
        $shorten_content = false;
        if (isset($job_data['facebook_shorten_content'])) {
            // Preferencia específica del usuario desde la app
            $shorten_content = filter_var($job_data['facebook_shorten_content'], FILTER_VALIDATE_BOOLEAN);
        } else {
            // Usar configuración global del admin
            $shorten_content = get_option('agrochamba_facebook_shorten_content', false);
        }
        
        // Verificar si se debe usar link preview
        // Prioridad: 1) Preferencia del usuario desde la app, 2) Configuración global del admin
        $use_link_preview = false;
        if (isset($job_data['facebook_use_link_preview'])) {
            // Preferencia específica del usuario desde la app
            $use_link_preview = filter_var($job_data['facebook_use_link_preview'], FILTER_VALIDATE_BOOLEAN);
        } else {
            // Usar configuración global del admin
            $use_link_preview = get_option('agrochamba_facebook_use_link_preview', false);
        }
        
        $original_content = $content;
        
        if ($shorten_content && !empty($content)) {
            // Truncar contenido a aproximadamente 300 caracteres (ajustable)
            // Intentar cortar en un punto lógico (punto, salto de línea, etc.)
            $max_length = 300;
            if (strlen($content) > $max_length) {
                // Buscar el último punto, signo de exclamación o interrogación antes del límite
                $truncate_pos = $max_length;
                $punctuation = array('. ', '.\n', '! ', '!\n', '? ', '?\n', '.\n\n', '!\n\n', '?\n\n');
                $best_pos = 0;
                
                foreach ($punctuation as $punct) {
                    $pos = strrpos(substr($content, 0, $max_length), $punct);
                    if ($pos !== false && $pos > $best_pos) {
                        $best_pos = $pos + strlen($punct);
                    }
                }
                
                // Si encontramos un punto lógico, usar ese; si no, cortar en el límite
                if ($best_pos > 100) { // Solo usar si encontramos algo razonable (al menos 100 caracteres)
                    $truncate_pos = $best_pos;
                }
                
                $content = substr($content, 0, $truncate_pos);
                $content = trim($content);
                
                // No agregar texto aquí, se agregará al final de forma consistente
            }
        }
        
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
        
        // Agregar mensaje corto y llamativo al final para motivar a visitar
        $job_url = get_permalink($post_id);
        
        // Lógica para agregar el link:
        // - Si use_link_preview está ACTIVO: NO agregar link (Facebook lo genera automáticamente con preview)
        // - Si use_link_preview está INACTIVO: SÍ agregar link (para que sea clickeable)
        if ($use_link_preview) {
            // Preview activo: Facebook genera el link automáticamente, solo mensaje
            $message .= "\n\n👉 Ver más detalles";
        } else {
            // Preview inactivo: Necesitamos agregar el link para que sea clickeable
            $message .= "\n\n👉 Ver más detalles: " . $job_url;
        }
        
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
            'Configuración de Integraciones',
            'AgroChamba Integraciones',
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
            
            // Configuración de Facebook
            update_option('agrochamba_facebook_enabled', isset($_POST['facebook_enabled']));
            update_option('agrochamba_use_n8n', isset($_POST['use_n8n']));
            update_option('agrochamba_n8n_webhook_url', sanitize_text_field($_POST['n8n_webhook_url']));
            update_option('agrochamba_facebook_page_token', sanitize_text_field($_POST['facebook_page_token']));
            update_option('agrochamba_facebook_page_id', sanitize_text_field($_POST['facebook_page_id']));
            update_option('agrochamba_facebook_shorten_content', isset($_POST['facebook_shorten_content']));
            
            // Configuración de Moderación por IA
            update_option('agrochamba_ai_moderation_enabled', isset($_POST['ai_moderation_enabled']));
            update_option('agrochamba_ai_service', sanitize_text_field($_POST['ai_service'] ?? 'openai'));
            update_option('agrochamba_ai_api_key', sanitize_text_field($_POST['ai_api_key']));
            update_option('agrochamba_ai_gemini_api_key', sanitize_text_field($_POST['ai_gemini_api_key'] ?? ''));
            
            echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
        }
        
        // Valores de Facebook
        $facebook_enabled = get_option('agrochamba_facebook_enabled', false);
        $use_n8n = get_option('agrochamba_use_n8n', false);
        $n8n_webhook_url = get_option('agrochamba_n8n_webhook_url', '');
        $page_token = get_option('agrochamba_facebook_page_token', '');
        $page_id = get_option('agrochamba_facebook_page_id', '');
        $facebook_shorten_content = get_option('agrochamba_facebook_shorten_content', false);
        
        // Valores de Moderación por IA
        $ai_moderation_enabled = get_option('agrochamba_ai_moderation_enabled', true);
        $ai_service = get_option('agrochamba_ai_service', 'openai');
        $ai_api_key = get_option('agrochamba_ai_api_key', '');
        $ai_gemini_api_key = get_option('agrochamba_ai_gemini_api_key', '');
        ?>
        <div class="wrap">
            <h1>Configuración de Integraciones</h1>
            <p>Configura las integraciones de AgroChamba: Facebook y Moderación por IA.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('agrochamba_facebook_settings'); ?>
                
                <h2 class="title">Facebook Integration</h2>
                <p>Configura la integración con Facebook para publicar trabajos automáticamente.</p>
                
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
                        <th scope="row">Páginas Configuradas</th>
                        <td>
                            <?php
                            $pages = function_exists('agrochamba_get_facebook_pages') ? agrochamba_get_facebook_pages() : array();
                            if (!empty($pages)) {
                                echo '<div style="background: #f0f0f1; padding: 10px; border-radius: 4px; margin-bottom: 10px;">';
                                echo '<strong>' . count($pages) . ' página(s) configurada(s):</strong><ul style="margin: 10px 0 0 20px;">';
                                foreach ($pages as $page) {
                                    $status = $page['enabled'] ? '✅' : '⏸️';
                                    $primary = ($page['is_primary'] ?? false) ? ' (Principal)' : '';
                                    echo '<li>' . $status . ' ' . esc_html($page['page_name']) . $primary . '</li>';
                                }
                                echo '</ul></div>';
                            } else {
                                echo '<p style="color: #d63638;">⚠️ No hay páginas configuradas.</p>';
                            }
                            ?>
                            <p class="description">
                                Las páginas de Facebook se gestionan desde la app Android en <strong>Configuración → Gestionar Páginas de Facebook</strong>.<br>
                                También puedes usar la <a href="<?php echo rest_url('agrochamba/v1/facebook/pages'); ?>" target="_blank">API REST</a> para gestionarlas.
                            </p>
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
                        <th scope="row">Page Access Token (Legacy)</th>
                        <td>
                            <input type="text" name="facebook_page_token" value="<?php echo esc_attr($page_token); ?>" class="regular-text" />
                            <p class="description">
                                <em>Configuración legacy para una sola página.</em> Usa la app Android para configurar múltiples páginas.<br>
                                Token de acceso de larga duración de tu página de Facebook.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Page ID (Legacy)</th>
                        <td>
                            <input type="text" name="facebook_page_id" value="<?php echo esc_attr($page_id); ?>" class="regular-text" />
                            <p class="description">
                                <em>Configuración legacy para una sola página.</em> Usa la app Android para configurar múltiples páginas.<br>
                                ID de tu página de Facebook.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Acortar contenido en Facebook</th>
                        <td>
                            <label>
                                <input type="checkbox" name="facebook_shorten_content" value="1" <?php checked($facebook_shorten_content, true); ?>>
                                Mostrar solo una parte del contenido y dirigir tráfico al sitio web
                            </label>
                            <p class="description">
                                Cuando está activado, solo se mostrará una parte del contenido en Facebook (aproximadamente 300 caracteres).<br>
                                Al final se agregará: "📌 Mayor información en el link adjunto de AgroChamba" para dirigir más tráfico a tu sitio web.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <h2 class="title" style="margin-top: 30px;">Moderación por IA</h2>
                <p>Configura la moderación automática de trabajos usando inteligencia artificial para detectar contenido violento, inapropiado o fraudulento.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Habilitar moderación por IA</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ai_moderation_enabled" value="1" <?php checked($ai_moderation_enabled, true); ?>>
                                Activar moderación automática con IA
                            </label>
                            <p class="description">
                                Cuando está activado, todos los trabajos nuevos serán revisados automáticamente por IA antes de publicarse.<br>
                                La IA detecta y elimina contenido violento, links sospechosos, imágenes inapropiadas y texto fraudulento.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Servicio de IA</th>
                        <td>
                            <select name="ai_service" id="ai_service_select" class="regular-text">
                                <option value="openai" <?php selected($ai_service, 'openai'); ?>>OpenAI (GPT-3.5/GPT-4/GPT-4 Vision)</option>
                                <option value="gemini" <?php selected($ai_service, 'gemini'); ?>>Google Gemini</option>
                            </select>
                            <p class="description">
                                Selecciona el servicio de IA que deseas usar para la moderación.<br>
                                Cada servicio requiere su propia API Key.
                            </p>
                        </td>
                    </tr>
                    <tr id="openai_config" style="display: <?php echo $ai_service === 'openai' ? 'table-row' : 'none'; ?>;">
                        <th scope="row">OpenAI API Key</th>
                        <td>
                            <input type="password" name="ai_api_key" value="<?php echo esc_attr($ai_api_key); ?>" class="regular-text" placeholder="sk-..." />
                            <p class="description">
                                API Key de OpenAI para usar GPT-3.5/GPT-4 y GPT-4 Vision.<br>
                                Puedes obtenerla desde: <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>
                            </p>
                        </td>
                    </tr>
                    <tr id="gemini_config" style="display: <?php echo $ai_service === 'gemini' ? 'table-row' : 'none'; ?>;">
                        <th scope="row">Google Gemini API Key</th>
                        <td>
                            <input type="password" name="ai_gemini_api_key" value="<?php echo esc_attr($ai_gemini_api_key); ?>" class="regular-text" placeholder="AIza..." />
                            <p class="description">
                                API Key de Google Gemini para usar Gemini Pro y Gemini Pro Vision.<br>
                                Puedes obtenerla desde: <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Estado de la moderación</th>
                        <td>
                            <?php
                            $current_api_key = '';
                            if ($ai_service === 'openai') {
                                $current_api_key = $ai_api_key;
                                $service_name = 'OpenAI';
                            } elseif ($ai_service === 'gemini') {
                                $current_api_key = $ai_gemini_api_key;
                                $service_name = 'Google Gemini';
                            }
                            
                            if (!$ai_moderation_enabled) {
                                echo '<span style="color: #d63638;">❌ Moderación por IA deshabilitada manualmente</span>';
                            } elseif (empty($current_api_key)) {
                                echo '<span style="color: #d63638;">⚠️ API Key de ' . esc_html($service_name) . ' no configurada - La moderación por IA está deshabilitada</span>';
                            } else {
                                echo '<span style="color: #00a32a;">✅ Moderación por IA activa usando ' . esc_html($service_name) . '</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                
                <script>
                jQuery(document).ready(function($) {
                    $('#ai_service_select').on('change', function() {
                        var selected = $(this).val();
                        if (selected === 'openai') {
                            $('#openai_config').show();
                            $('#gemini_config').hide();
                        } else if (selected === 'gemini') {
                            $('#openai_config').hide();
                            $('#gemini_config').show();
                        }
                    });
                });
                </script>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}


