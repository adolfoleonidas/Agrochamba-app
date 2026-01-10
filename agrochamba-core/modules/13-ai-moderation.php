<?php
/**
 * Módulo de Moderación con IA
 * 
 * Revisa automáticamente los trabajos publicados por empresas usando IA
 * y los aprueba o rechaza según criterios definidos.
 * 
 * @package AgroChamba
 * @subpackage Modules
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// CONFIGURACIÓN
// ==========================================

/**
 * Configuración del servicio de IA
 * Se puede configurar desde wp-config.php o desde la página de administración
 * 
 * Opciones disponibles:
 * - 'openai': OpenAI GPT-3.5/GPT-4/GPT-4 Vision
 * - 'gemini': Google Gemini Pro/Gemini Pro Vision
 * - 'custom': Servicio personalizado (futuro)
 */
if (!defined('AGROCHAMBA_AI_SERVICE')) {
    $saved_service = get_option('agrochamba_ai_service', 'openai');
    // También verificar si está definida en wp-config.php (tiene prioridad)
    if (defined('AGROCHAMBA_AI_SERVICE_WP_CONFIG')) {
        define('AGROCHAMBA_AI_SERVICE', AGROCHAMBA_AI_SERVICE_WP_CONFIG);
    } else {
        define('AGROCHAMBA_AI_SERVICE', $saved_service);
    }
}

/**
 * API Key del servicio de IA (OpenAI)
 * Se puede configurar desde wp-config.php o desde la página de administración
 */
if (!defined('AGROCHAMBA_AI_API_KEY')) {
    $saved_api_key = get_option('agrochamba_ai_api_key', '');
    // También verificar si está definida en wp-config.php (tiene prioridad)
    if (defined('AGROCHAMBA_AI_API_KEY_WP_CONFIG')) {
        define('AGROCHAMBA_AI_API_KEY', AGROCHAMBA_AI_API_KEY_WP_CONFIG);
    } else {
        define('AGROCHAMBA_AI_API_KEY', $saved_api_key);
    }
}

/**
 * API Key de Google Gemini
 * Se puede configurar desde wp-config.php o desde la página de administración
 */
if (!defined('AGROCHAMBA_AI_GEMINI_API_KEY')) {
    $saved_gemini_key = get_option('agrochamba_ai_gemini_api_key', '');
    // También verificar si está definida en wp-config.php (tiene prioridad)
    if (defined('AGROCHAMBA_AI_GEMINI_API_KEY_WP_CONFIG')) {
        define('AGROCHAMBA_AI_GEMINI_API_KEY', AGROCHAMBA_AI_GEMINI_API_KEY_WP_CONFIG);
    } else {
        define('AGROCHAMBA_AI_GEMINI_API_KEY', $saved_gemini_key);
    }
}

/**
 * Habilitar moderación automática
 * Se puede configurar desde wp-config.php o desde la página de administración
 */
if (!defined('AGROCHAMBA_AI_MODERATION_ENABLED')) {
    $saved_enabled = get_option('agrochamba_ai_moderation_enabled', true);
    // También verificar si está definida en wp-config.php (tiene prioridad)
    if (defined('AGROCHAMBA_AI_MODERATION_ENABLED_WP_CONFIG')) {
        define('AGROCHAMBA_AI_MODERATION_ENABLED', AGROCHAMBA_AI_MODERATION_ENABLED_WP_CONFIG);
    } else {
        define('AGROCHAMBA_AI_MODERATION_ENABLED', $saved_enabled);
    }
}

/**
 * Obtener la API Key del servicio actual configurado
 * 
 * @return string API Key del servicio seleccionado
 */
if (!function_exists('agrochamba_get_ai_api_key')) {
    function agrochamba_get_ai_api_key() {
        $service = AGROCHAMBA_AI_SERVICE;
        
        if ($service === 'gemini') {
            return AGROCHAMBA_AI_GEMINI_API_KEY;
        } else {
            // Por defecto OpenAI
            return AGROCHAMBA_AI_API_KEY;
        }
    }
}

// ==========================================
// FUNCIÓN PRINCIPAL DE MODERACIÓN
// ==========================================

/**
 * Moderar trabajo con IA
 * 
 * @param int $post_id ID del trabajo
 * @return array Resultado de la moderación con 'approved' (bool) y 'reason' (string)
 */
if (!function_exists('agrochamba_moderate_job_with_ai')) {
    function agrochamba_moderate_job_with_ai($post_id) {
        // Verificar si la moderación está habilitada
        if (!AGROCHAMBA_AI_MODERATION_ENABLED || empty(AGROCHAMBA_AI_API_KEY)) {
            error_log('AgroChamba AI: Moderación deshabilitada o API key no configurada');
            return array(
                'approved' => false,
                'reason' => 'Moderación con IA no configurada',
                'auto_approved' => false
            );
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'trabajo') {
            return array(
                'approved' => false,
                'reason' => 'No es un trabajo válido',
                'auto_approved' => false
            );
        }

        // PASO 1: Extraer y revisar links/URLs del contenido PRIMERO
        $links_violence_result = agrochamba_check_links_for_violence($post_id, $post);
        if ($links_violence_result['violence_detected']) {
            // Si la violencia está confirmada en links, eliminar contenido y rechazar
            if (isset($links_violence_result['confirmed']) && $links_violence_result['confirmed']) {
                error_log("AgroChamba AI: Contenido violento CONFIRMADO en links del trabajo $post_id. Eliminando contenido.");
                
                // Eliminar imágenes también por seguridad
                $gallery_ids = get_post_meta($post_id, 'gallery_ids', true);
                $featured_id = get_post_thumbnail_id($post_id);
                $all_image_ids = array();
                if ($featured_id) $all_image_ids[] = $featured_id;
                if (is_array($gallery_ids)) {
                    $all_image_ids = array_merge($all_image_ids, $gallery_ids);
                }
                if (!empty($all_image_ids)) {
                    agrochamba_delete_job_images($post_id, array_unique($all_image_ids));
                }
                
                // Limpiar contenido del post (remover links y contenido sospechoso)
                agrochamba_clean_violent_content($post_id, $links_violence_result['violent_links'] ?? array());
                
                update_post_meta($post_id, '_ai_moderation_result', array(
                    'approved' => false,
                    'reason' => 'Contenido violento confirmado en links. El contenido ha sido limpiado.',
                    'violence_detected' => true,
                    'violence_confirmed' => true,
                    'content_cleaned' => true,
                    'source' => 'link_moderation'
                ));
                return array(
                    'approved' => false,
                    'reason' => 'Contenido violento confirmado en links. El contenido ha sido limpiado y el trabajo ha sido rechazado.',
                    'violence_detected' => true,
                    'violence_confirmed' => true,
                    'content_cleaned' => true,
                    'auto_approved' => false
                );
            }
            
            // Si requiere revisión manual (baja confianza), dejar pendiente
            if (isset($links_violence_result['requires_manual_review']) && $links_violence_result['requires_manual_review']) {
                error_log("AgroChamba AI: Posible contenido violento en links del trabajo $post_id. Requiere revisión manual.");
                update_post_meta($post_id, '_ai_moderation_result', array(
                    'approved' => false,
                    'reason' => 'Posible contenido inapropiado detectado en links. Requiere revisión manual.',
                    'violence_detected' => true,
                    'violence_confirmed' => false,
                    'requires_manual_review' => true,
                    'source' => 'link_moderation',
                    'suspicious_links' => $links_violence_result['suspicious_links'] ?? array()
                ));
                return array(
                    'approved' => false,
                    'reason' => 'Posible contenido inapropiado en links. Requiere revisión manual.',
                    'violence_detected' => true,
                    'violence_confirmed' => false,
                    'requires_manual_review' => true,
                    'auto_approved' => false
                );
            }
        }

        // PASO 2: Revisar imágenes (si hay)
        $image_violence_result = agrochamba_check_images_for_violence($post_id);
        if ($image_violence_result['violence_detected']) {
            // Si la violencia está confirmada (alta confianza), las imágenes ya fueron eliminadas
            if (isset($image_violence_result['confirmed']) && $image_violence_result['confirmed']) {
                error_log("AgroChamba AI: Contenido violento CONFIRMADO en imágenes del trabajo $post_id. Imágenes eliminadas.");
                update_post_meta($post_id, '_ai_moderation_result', array(
                    'approved' => false,
                    'reason' => 'Contenido violento confirmado en imágenes. Las imágenes han sido eliminadas del sistema.',
                    'violence_detected' => true,
                    'violence_confirmed' => true,
                    'images_deleted' => true,
                    'source' => 'image_moderation'
                ));
                return array(
                    'approved' => false,
                    'reason' => 'Contenido violento confirmado en imágenes. Las imágenes han sido eliminadas y el trabajo ha sido rechazado.',
                    'violence_detected' => true,
                    'violence_confirmed' => true,
                    'images_deleted' => true,
                    'auto_approved' => false
                );
            }
            
            // Si requiere revisión manual (baja confianza), dejar pendiente
            if (isset($image_violence_result['requires_manual_review']) && $image_violence_result['requires_manual_review']) {
                error_log("AgroChamba AI: Posible contenido violento en imágenes del trabajo $post_id. Requiere revisión manual.");
                update_post_meta($post_id, '_ai_moderation_result', array(
                    'approved' => false,
                    'reason' => 'Posible contenido inapropiado detectado en imágenes. Requiere revisión manual.',
                    'violence_detected' => true,
                    'violence_confirmed' => false,
                    'requires_manual_review' => true,
                    'source' => 'image_moderation',
                    'violence_images' => $image_violence_result['violence_images'] ?? array()
                ));
                return array(
                    'approved' => false,
                    'reason' => 'Posible contenido inapropiado en imágenes. Requiere revisión manual.',
                    'violence_detected' => true,
                    'violence_confirmed' => false,
                    'requires_manual_review' => true,
                    'auto_approved' => false
                );
            }
        }

        // PASO 2: Preparar contenido de texto para revisión
        $title = $post->post_title;
        $content = strip_tags($post->post_content);
        $excerpt = $post->post_excerpt;
        
        // Obtener meta fields relevantes
        $salario_min = get_post_meta($post_id, 'salario_min', true);
        $salario_max = get_post_meta($post_id, 'salario_max', true);
        $vacantes = get_post_meta($post_id, 'vacantes', true);
        $ubicacion = wp_get_post_terms($post_id, 'ubicacion', array('fields' => 'names'));
        $ubicacion_name = !empty($ubicacion) ? $ubicacion[0] : '';
        
        // Construir texto completo para revisión
        $full_text = "Título: $title\n\n";
        if (!empty($excerpt)) {
            $full_text .= "Resumen: $excerpt\n\n";
        }
        $full_text .= "Descripción: $content\n\n";
        if (!empty($salario_min) || !empty($salario_max)) {
            $full_text .= "Salario: " . ($salario_min ?: 'No especificado') . " - " . ($salario_max ?: 'No especificado') . "\n";
        }
        if (!empty($vacantes)) {
            $full_text .= "Vacantes: $vacantes\n";
        }
        if (!empty($ubicacion_name)) {
            $full_text .= "Ubicación: $ubicacion_name\n";
        }

        // PASO 3: Revisar texto con API de moderación de OpenAI (más rápida y específica para violencia)
        $text_moderation_result = agrochamba_moderate_text_with_openai_api($full_text);
        if ($text_moderation_result['violence_detected']) {
            // Verificar confianza de la detección
            $violence_score = isset($text_moderation_result['category_scores']['violence']) 
                ? floatval($text_moderation_result['category_scores']['violence']) 
                : 0.0;
            $graphic_score = isset($text_moderation_result['category_scores']['violence/graphic']) 
                ? floatval($text_moderation_result['category_scores']['violence/graphic']) 
                : 0.0;
            
            $max_violence_score = max($violence_score, $graphic_score);
            $high_confidence = $max_violence_score >= 0.8; // Umbral de alta confianza
            
            if ($high_confidence) {
                // Violencia confirmada - eliminar imágenes y limpiar contenido violento
                $gallery_ids = get_post_meta($post_id, 'gallery_ids', true);
                $featured_id = get_post_thumbnail_id($post_id);
                $all_image_ids = array();
                if ($featured_id) $all_image_ids[] = $featured_id;
                if (is_array($gallery_ids)) {
                    $all_image_ids = array_merge($all_image_ids, $gallery_ids);
                }
                if (!empty($all_image_ids)) {
                    agrochamba_delete_job_images($post_id, array_unique($all_image_ids));
                }
                
                // Limpiar contenido violento del texto
                $post = get_post($post_id);
                if ($post) {
                    agrochamba_clean_violent_text_content($post_id, $post, $text_moderation_result);
                }
                
                error_log("AgroChamba AI: Contenido violento CONFIRMADO en texto del trabajo $post_id (score: $max_violence_score). Contenido limpiado.");
                update_post_meta($post_id, '_ai_moderation_result', array(
                    'approved' => false,
                    'reason' => 'Contenido violento confirmado en el texto. El contenido ha sido limpiado.',
                    'violence_detected' => true,
                    'violence_confirmed' => true,
                    'violence_score' => $max_violence_score,
                    'images_deleted' => !empty($all_image_ids),
                    'content_cleaned' => true,
                    'source' => 'text_moderation',
                    'flags' => $text_moderation_result['flags'] ?? array()
                ));
                return array(
                    'approved' => false,
                    'reason' => 'Contenido violento confirmado en el texto. El contenido ha sido limpiado y el trabajo ha sido rechazado.',
                    'violence_detected' => true,
                    'violence_confirmed' => true,
                    'images_deleted' => !empty($all_image_ids),
                    'content_cleaned' => true,
                    'auto_approved' => false
                );
            } else {
                // Baja confianza - requiere revisión manual
                error_log("AgroChamba AI: Posible contenido violento en texto del trabajo $post_id (score: $max_violence_score). Requiere revisión manual.");
                update_post_meta($post_id, '_ai_moderation_result', array(
                    'approved' => false,
                    'reason' => 'Posible contenido inapropiado detectado en el texto. Requiere revisión manual.',
                    'violence_detected' => true,
                    'violence_confirmed' => false,
                    'violence_score' => $max_violence_score,
                    'requires_manual_review' => true,
                    'source' => 'text_moderation',
                    'flags' => $text_moderation_result['flags'] ?? array()
                ));
                return array(
                    'approved' => false,
                    'reason' => 'Posible contenido inapropiado en el texto. Requiere revisión manual.',
                    'violence_detected' => true,
                    'violence_confirmed' => false,
                    'requires_manual_review' => true,
                    'auto_approved' => false
                );
            }
        }

        // PASO 4: Si pasó las revisiones de violencia, hacer revisión completa con GPT
        // Llamar al servicio de IA según configuración para revisión completa
        $service = AGROCHAMBA_AI_SERVICE;
        
        switch ($service) {
            case 'openai':
                $full_moderation_result = agrochamba_moderate_with_openai($full_text, $post_id);
                // Si la revisión completa también detecta violencia, rechazar
                if (isset($full_moderation_result['violence_detected']) && $full_moderation_result['violence_detected']) {
                    return array(
                        'approved' => false,
                        'reason' => 'Contenido violento detectado durante la revisión completa',
                        'violence_detected' => true,
                        'auto_approved' => false
                    );
                }
                return $full_moderation_result;
            
            case 'gemini':
                return agrochamba_moderate_with_gemini($full_text, $post_id);
            
            default:
                error_log("AgroChamba AI: Servicio '$service' no implementado");
                return array(
                    'approved' => false,
                    'reason' => 'Servicio de IA no configurado correctamente',
                    'auto_approved' => false
                );
        }
    }
}

// ==========================================
// REVISIÓN DE LINKS/URLs PARA CONTENIDO VIOLENTO
// ==========================================

/**
 * Extraer y revisar links/URLs del contenido del trabajo
 * 
 * @param int $post_id ID del trabajo
 * @param WP_Post $post Objeto del post
 * @return array Resultado de la revisión
 */
if (!function_exists('agrochamba_check_links_for_violence')) {
    function agrochamba_check_links_for_violence($post_id, $post) {
        // Extraer todas las URLs del contenido
        $content = $post->post_content . ' ' . $post->post_excerpt;
        
        // Patrón para detectar URLs
        $url_pattern = '/https?:\/\/[^\s<>"{}|\\^`\[\]]+/i';
        preg_match_all($url_pattern, $content, $matches);
        
        $urls = !empty($matches[0]) ? array_unique($matches[0]) : array();
        
        if (empty($urls)) {
            return array('violence_detected' => false);
        }

        $api_key = agrochamba_get_ai_api_key();
        if (empty($api_key)) {
            return array('violence_detected' => false, 'error' => 'API key no configurada');
        }

        $violence_detected_links = array();
        $suspicious_links = array();
        $high_confidence_violence = false;

        // Revisar cada URL
        foreach ($urls as $url) {
            // Limpiar URL
            $url = trim($url);
            if (empty($url) || strlen($url) > 2048) {
                continue; // URLs muy largas pueden ser sospechosas pero las saltamos
            }

            // Revisar el dominio y la URL en sí
            $link_analysis = agrochamba_analyze_link_content($url);
            
            if ($link_analysis['violence_detected']) {
                $confidence = isset($link_analysis['confidence']) ? floatval($link_analysis['confidence']) : 0.5;
                
                $link_data = array(
                    'url' => $url,
                    'confidence' => $confidence,
                    'reason' => $link_analysis['reason'] ?? '',
                    'flags' => $link_analysis['flags'] ?? array()
                );
                
                if ($confidence >= 0.8) {
                    // Alta confianza - violencia confirmada
                    $violence_detected_links[] = $link_data;
                    $high_confidence_violence = true;
                    error_log("AgroChamba AI: Contenido violento CONFIRMADO en link: $url (confianza: $confidence)");
                } else {
                    // Baja confianza - sospechoso pero requiere revisión
                    $suspicious_links[] = $link_data;
                    error_log("AgroChamba AI: Link sospechoso detectado: $url (confianza: $confidence)");
                }
            }
        }

        // Si hay violencia confirmada (alta confianza), eliminar contenido
        if ($high_confidence_violence) {
            return array(
                'violence_detected' => true,
                'confirmed' => true,
                'violent_links' => $violence_detected_links,
                'suspicious_links' => $suspicious_links
            );
        }

        // Si hay links sospechosos pero con baja confianza, requerir revisión manual
        if (!empty($suspicious_links)) {
            return array(
                'violence_detected' => true,
                'confirmed' => false,
                'requires_manual_review' => true,
                'suspicious_links' => $suspicious_links
            );
        }

        return array('violence_detected' => false);
    }
}

/**
 * Analizar contenido de un link/URL
 * 
 * @param string $url URL a analizar
 * @return array Resultado del análisis
 */
if (!function_exists('agrochamba_analyze_link_content')) {
    function agrochamba_analyze_link_content($url) {
        $api_key = agrochamba_get_ai_api_key();
        
        // Primero, analizar el dominio y la URL en sí
        $domain = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);
        
        $url_text = $domain . ' ' . $path . ' ' . $query;
        
        // Revisar con API de moderación de OpenAI
        $moderation_result = agrochamba_moderate_text_with_openai_api($url_text);
        
        if ($moderation_result['violence_detected']) {
            $violence_score = isset($moderation_result['category_scores']['violence']) 
                ? floatval($moderation_result['category_scores']['violence']) 
                : 0.0;
            $graphic_score = isset($moderation_result['category_scores']['violence/graphic']) 
                ? floatval($moderation_result['category_scores']['violence/graphic']) 
                : 0.0;
            
            $max_score = max($violence_score, $graphic_score);
            
            return array(
                'violence_detected' => true,
                'confidence' => $max_score,
                'reason' => 'Contenido violento detectado en la URL',
                'flags' => $moderation_result['flags'] ?? array()
            );
        }
        
        // También revisar palabras clave sospechosas en la URL
        $violence_keywords = array(
            'violence', 'violencia', 'weapon', 'arma', 'gun', 'pistola', 
            'blood', 'sangre', 'kill', 'matar', 'death', 'muerte',
            'gore', 'sangriento', 'attack', 'ataque', 'fight', 'pelea'
        );
        
        $url_lower = strtolower($url);
        foreach ($violence_keywords as $keyword) {
            if (stripos($url_lower, $keyword) !== false) {
                return array(
                    'violence_detected' => true,
                    'confidence' => 0.75, // Confianza media-alta para keywords
                    'reason' => "Palabra clave relacionada con violencia detectada en URL: $keyword",
                    'flags' => array('violence_keyword')
                );
            }
        }
        
        return array('violence_detected' => false);
    }
}

/**
 * Limpiar contenido violento del post
 * 
 * @param int $post_id ID del trabajo
 * @param array $violent_links Array de links violentos a eliminar
 */
if (!function_exists('agrochamba_clean_violent_content')) {
    function agrochamba_clean_violent_content($post_id, $violent_links) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }
        
        $content = $post->post_content;
        $excerpt = $post->post_excerpt;
        
        // Eliminar links violentos del contenido
        foreach ($violent_links as $link_data) {
            $url = $link_data['url'] ?? '';
            if (!empty($url)) {
                // Remover el link del contenido (con y sin tags HTML)
                $content = str_replace($url, '', $content);
                $content = preg_replace('/<a[^>]*href=["\']?' . preg_quote($url, '/') . '["\']?[^>]*>.*?<\/a>/i', '', $content);
                
                if (!empty($excerpt)) {
                    $excerpt = str_replace($url, '', $excerpt);
                    $excerpt = preg_replace('/<a[^>]*href=["\']?' . preg_quote($url, '/') . '["\']?[^>]*>.*?<\/a>/i', '', $excerpt);
                }
            }
        }
        
        // Actualizar el post con contenido limpio
        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content,
            'post_excerpt' => $excerpt
        ));
        
        // Guardar registro de limpieza
        update_post_meta($post_id, '_content_cleaned_date', current_time('mysql'));
        update_post_meta($post_id, '_content_cleaned_reason', 'Contenido violento detectado en links');
        update_post_meta($post_id, '_removed_links', array_map(function($link) {
            return $link['url'] ?? '';
        }, $violent_links));
        
        error_log("AgroChamba AI: Contenido limpiado del trabajo $post_id. Links eliminados: " . count($violent_links));
    }
}

/**
 * Limpiar contenido violento del texto del post
 * 
 * @param int $post_id ID del trabajo
 * @param WP_Post $post Objeto del post
 * @param array $moderation_result Resultado de la moderación
 */
if (!function_exists('agrochamba_clean_violent_text_content')) {
    function agrochamba_clean_violent_text_content($post_id, $post, $moderation_result) {
        $content = $post->post_content;
        $excerpt = $post->post_excerpt;
        
        // Lista de palabras/frases violentas comunes a eliminar o reemplazar
        $violent_phrases = array(
            // Violencia física
            '/\b(matar|kill|asesinar|assassinate)\b/i' => '[contenido removido]',
            '/\b(arma|weapon|gun|pistola|rifle)\b/i' => '[contenido removido]',
            '/\b(sangre|blood|sangriento|bloody)\b/i' => '[contenido removido]',
            '/\b(agresión|aggression|atacar|attack)\b/i' => '[contenido removido]',
            '/\b(violencia|violence|violento|violent)\b/i' => '[contenido removido]',
            '/\b(pelea|fight|combate|combat)\b/i' => '[contenido removido]',
            // Amenazas
            '/\b(amenazar|threat|amenaza|threaten)\b/i' => '[contenido removido]',
            '/\b(intimidar|intimidate|intimidación)\b/i' => '[contenido removido]',
        );
        
        // Remover frases violentas del contenido
        foreach ($violent_phrases as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
            if (!empty($excerpt)) {
                $excerpt = preg_replace($pattern, $replacement, $excerpt);
            }
        }
        
        // Si el contenido queda muy corto o vacío después de limpiar, usar mensaje genérico
        $cleaned_content = strip_tags($content);
        if (strlen(trim($cleaned_content)) < 50 || substr_count(strtolower($cleaned_content), '[contenido removido]') > strlen($cleaned_content) * 0.3) {
            $content = '<p>Este contenido ha sido removido por contener material inapropiado.</p>';
            $excerpt = 'Contenido removido por material inapropiado.';
        }
        
        // Actualizar el post con contenido limpio
        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content,
            'post_excerpt' => $excerpt
        ));
        
        // Guardar registro de limpieza
        update_post_meta($post_id, '_text_content_cleaned_date', current_time('mysql'));
        update_post_meta($post_id, '_text_content_cleaned_reason', 'Contenido violento detectado en texto');
        update_post_meta($post_id, '_text_content_cleaned_flags', $moderation_result['flags'] ?? array());
        
        error_log("AgroChamba AI: Contenido de texto limpiado del trabajo $post_id");
    }
}

// ==========================================
// REVISIÓN DE IMÁGENES PARA CONTENIDO VIOLENTO
// ==========================================

/**
 * Revisar imágenes del trabajo para detectar contenido violento
 * 
 * @param int $post_id ID del trabajo
 * @return array Resultado de la revisión
 */
if (!function_exists('agrochamba_check_images_for_violence')) {
    function agrochamba_check_images_for_violence($post_id) {
        // Obtener todas las imágenes del trabajo
        $gallery_ids = get_post_meta($post_id, 'gallery_ids', true);
        $featured_id = get_post_thumbnail_id($post_id);
        
        $image_ids = array();
        if ($featured_id) {
            $image_ids[] = $featured_id;
        }
        if (is_array($gallery_ids)) {
            $image_ids = array_merge($image_ids, $gallery_ids);
        }
        $image_ids = array_unique($image_ids);
        
        if (empty($image_ids)) {
            return array('violence_detected' => false);
        }

        $api_key = agrochamba_get_ai_api_key();
        if (empty($api_key)) {
            return array('violence_detected' => false, 'error' => 'API key no configurada');
        }

        $violence_detected_images = array();
        $high_confidence_violence = false;

        // Revisar cada imagen usando la API de moderación de imágenes de OpenAI
        foreach ($image_ids as $image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            if (!$image_url) {
                continue;
            }

            // Usar la API de moderación de OpenAI para imágenes
            $moderation_result = agrochamba_moderate_image_with_openai($image_url);
            
            // Solo considerar violencia confirmada si la confianza es alta (>= 0.8)
            $confidence = isset($moderation_result['confidence']) ? floatval($moderation_result['confidence']) : 0.5;
            
            if ($moderation_result['violence_detected']) {
                error_log("AgroChamba AI: Contenido violento detectado en imagen ID: $image_id (confianza: $confidence)");
                
                $violence_detected_images[] = array(
                    'image_id' => $image_id,
                    'confidence' => $confidence,
                    'flags' => $moderation_result['flags'] ?? array(),
                    'reason' => $moderation_result['reason'] ?? ''
                );
                
                // Si la confianza es alta (>= 0.8), marcar como violencia confirmada
                if ($confidence >= 0.8) {
                    $high_confidence_violence = true;
                }
            }
        }

        // Si hay violencia confirmada (alta confianza), eliminar todas las imágenes
        if ($high_confidence_violence) {
            error_log("AgroChamba AI: Violencia confirmada detectada. Eliminando todas las imágenes del trabajo $post_id");
            agrochamba_delete_job_images($post_id, $image_ids);
            
            return array(
                'violence_detected' => true,
                'confirmed' => true,
                'images_deleted' => true,
                'violence_images' => $violence_detected_images
            );
        }

        // Si hay violencia detectada pero con baja confianza, dejar para revisión manual
        if (!empty($violence_detected_images)) {
            error_log("AgroChamba AI: Posible contenido violento detectado (baja confianza). Requiere revisión manual para trabajo $post_id");
            return array(
                'violence_detected' => true,
                'confirmed' => false,
                'requires_manual_review' => true,
                'violence_images' => $violence_detected_images
            );
        }

        return array('violence_detected' => false);
    }
}

/**
 * Eliminar todas las imágenes de un trabajo del sistema
 * 
 * @param int $post_id ID del trabajo
 * @param array $image_ids IDs de las imágenes a eliminar
 */
if (!function_exists('agrochamba_delete_job_images')) {
    function agrochamba_delete_job_images($post_id, $image_ids) {
        $deleted_count = 0;
        
        foreach ($image_ids as $image_id) {
            // Eliminar el archivo físico de la imagen
            $file_path = get_attached_file($image_id);
            if ($file_path && file_exists($file_path)) {
                wp_delete_file($file_path);
            }
            
            // Eliminar todos los tamaños de la imagen (thumbnails)
            $metadata = wp_get_attachment_metadata($image_id);
            if ($metadata && isset($metadata['sizes'])) {
                $upload_dir = wp_upload_dir();
                $base_dir = $upload_dir['basedir'];
                $file_dir = dirname($file_path);
                
                foreach ($metadata['sizes'] as $size => $size_data) {
                    $size_file = $file_dir . '/' . $size_data['file'];
                    if (file_exists($size_file)) {
                        wp_delete_file($size_file);
                    }
                }
            }
            
            // Eliminar el attachment de la base de datos
            $deleted = wp_delete_attachment($image_id, true); // true = fuerza eliminación
            
            if ($deleted) {
                $deleted_count++;
                error_log("AgroChamba AI: Imagen ID $image_id eliminada completamente del sistema");
            } else {
                error_log("AgroChamba AI: Error al eliminar imagen ID $image_id");
            }
        }
        
        // Limpiar meta del trabajo
        delete_post_thumbnail($post_id);
        update_post_meta($post_id, 'gallery_ids', array());
        
        // Guardar registro de eliminación
        update_post_meta($post_id, '_images_deleted_reason', 'Contenido violento detectado por IA');
        update_post_meta($post_id, '_images_deleted_date', current_time('mysql'));
        update_post_meta($post_id, '_images_deleted_count', $deleted_count);
        
        error_log("AgroChamba AI: Total de imágenes eliminadas del trabajo $post_id: $deleted_count");
        
        return $deleted_count;
    }
}

/**
 * Moderar imagen individual con OpenAI
 * 
 * @param string $image_url URL de la imagen
 * @return array Resultado de la moderación
 */
if (!function_exists('agrochamba_moderate_image_with_openai')) {
    function agrochamba_moderate_image_with_openai($image_url) {
        $api_key = agrochamba_get_ai_api_key();
        
        // Usar GPT-4 Vision para analizar la imagen
        $api_url = 'https://api.openai.com/v1/chat/completions';
        
        $request_body = array(
            'model' => 'gpt-4o-mini', // Modelo eficiente con visión
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array(
                            'type' => 'text',
                            'text' => 'Analiza esta imagen y determina si contiene contenido violento, armas, agresiones físicas, sangre, o cualquier forma de violencia. Responde SOLO con JSON: {"violence_detected": true/false, "reason": "breve explicación", "confidence": 0.0-1.0}'
                        ),
                        array(
                            'type' => 'image_url',
                            'image_url' => array('url' => $image_url)
                        )
                    )
                )
            ),
            'max_tokens' => 150
        );

        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode($request_body)
        ));

        if (is_wp_error($response)) {
            error_log('AgroChamba AI Error (imagen): ' . $response->get_error_message());
            return array('violence_detected' => false, 'error' => 'Error al conectar');
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            error_log('AgroChamba AI Error (imagen): ' . json_encode($response_body));
            return array('violence_detected' => false, 'error' => 'Error en respuesta');
        }

        $ai_message = $response_body['choices'][0]['message']['content'] ?? '';
        $result = json_decode($ai_message, true);

        if (!$result || !isset($result['violence_detected'])) {
            // Fallback: buscar palabras clave en la respuesta
            $violence_keywords = array('violence', 'violencia', 'weapon', 'arma', 'blood', 'sangre', 'aggression', 'agresión');
            $violence_detected = false;
            foreach ($violence_keywords as $keyword) {
                if (stripos($ai_message, $keyword) !== false) {
                    $violence_detected = true;
                    break;
                }
            }
            return array('violence_detected' => $violence_detected);
        }

        return array(
            'violence_detected' => (bool) $result['violence_detected'],
            'reason' => $result['reason'] ?? '',
            'confidence' => isset($result['confidence']) ? floatval($result['confidence']) : 0.5,
            'flags' => $result['flags'] ?? array()
        );
    }
}

// ==========================================
// REVISIÓN DE TEXTO CON API DE MODERACIÓN DE OPENAI
// ==========================================

/**
 * Moderar texto usando la API de moderación específica de OpenAI
 * Esta API es más rápida y específica para detectar contenido violento
 * 
 * @param string $text Texto a moderar
 * @return array Resultado de la moderación
 */
if (!function_exists('agrochamba_moderate_text_with_openai_api')) {
    function agrochamba_moderate_text_with_openai_api($text) {
        $api_key = agrochamba_get_ai_api_key();
        $api_url = 'https://api.openai.com/v1/moderations';
        
        $request_body = array(
            'input' => $text
        );

        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode($request_body)
        ));

        if (is_wp_error($response)) {
            error_log('AgroChamba AI Error (moderación texto): ' . $response->get_error_message());
            return array('violence_detected' => false, 'error' => 'Error al conectar');
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            error_log('AgroChamba AI Error (moderación texto): ' . json_encode($response_body));
            return array('violence_detected' => false, 'error' => 'Error en respuesta');
        }

        $results = $response_body['results'][0] ?? array();
        $flagged = $results['flagged'] ?? false;
        $categories = $results['categories'] ?? array();
        $category_scores = $results['category_scores'] ?? array();

        // Detectar específicamente violencia
        $violence_detected = false;
        $flags = array();

        if ($flagged) {
            // Revisar categorías específicas de violencia
            if (isset($categories['violence']) && $categories['violence']) {
                $violence_detected = true;
                $flags[] = 'violence';
            }
            if (isset($categories['violence/graphic']) && $categories['violence/graphic']) {
                $violence_detected = true;
                $flags[] = 'violence/graphic';
            }
            if (isset($categories['hate']) && $categories['hate']) {
                $flags[] = 'hate';
            }
            if (isset($categories['harassment']) && $categories['harassment']) {
                $flags[] = 'harassment';
            }
            if (isset($categories['self-harm']) && $categories['self-harm']) {
                $flags[] = 'self-harm';
            }
        }

        return array(
            'violence_detected' => $violence_detected,
            'flagged' => $flagged,
            'flags' => $flags,
            'category_scores' => $category_scores
        );
    }
}

// ==========================================
// IMPLEMENTACIÓN PARA OPENAI
// ==========================================

/**
 * Moderar con OpenAI
 * 
 * @param string $content Contenido completo del trabajo
 * @param int $post_id ID del trabajo
 * @return array Resultado de la moderación
 */
if (!function_exists('agrochamba_moderate_with_openai')) {
    function agrochamba_moderate_with_openai($content, $post_id) {
        $api_key = agrochamba_get_ai_api_key();
        $api_url = 'https://api.openai.com/v1/chat/completions';
        
        // Prompt para la moderación
        $system_prompt = "Eres un moderador de contenido para una plataforma de empleos agrícolas en Perú. 
Tu tarea es revisar anuncios de trabajo y APROBAR automáticamente los que cumplan con todos los requisitos.

CRITERIOS DE APROBACIÓN AUTOMÁTICA (APRUEBA SI CUMPLE TODO):

✅ El trabajo DEBE tener:
1. Título claro y descriptivo del puesto
2. Descripción detallada del trabajo a realizar
3. Ubicación especificada
4. Información sobre salario o beneficios (al menos uno)
5. Lenguaje profesional y respetuoso
6. Cumplimiento con leyes laborales peruanas
7. Sin discriminación por género, edad, raza, etc.

CRITERIOS DE RECHAZO OBLIGATORIO (RECHAZA SI ENCUENTRAS CUALQUIERA DE ESTOS):

❌ CONTENIDO VIOLENTO (RECHAZO AUTOMÁTICO):
   - Cualquier referencia a violencia física, armas, agresiones
   - Lenguaje agresivo, amenazante o intimidatorio
   - Contenido que promueva o normalice la violencia
   - Descripciones de situaciones peligrosas o violentas

❌ CONTENIDO INAPROPIADO:
   - Spam, contenido ofensivo o inapropiado
   - Lenguaje no profesional o irrespetuoso
   - Información falsa o engañosa
   - Contenido sexual o explícito
   - Lenguaje discriminatorio o de odio

❌ INFORMACIÓN INCOMPLETA (solo rechaza si falta información CRÍTICA):
   - Falta título o descripción completamente
   - No especifica ubicación (es obligatorio)
   - Información muy vaga o insuficiente

IMPORTANTE: 
- Si el trabajo cumple con los requisitos básicos y NO tiene contenido violento o inapropiado, APRUEBA automáticamente.
- Si detectas CUALQUIER contenido violento, RECHAZA inmediatamente.
- Si falta información menor (como salario exacto), pero el trabajo es válido, APRUEBA.

Responde SOLO con un JSON válido en este formato:
{
  \"approved\": true/false,
  \"reason\": \"Razón breve y clara de la decisión\",
  \"confidence\": 0.0-1.0,
  \"violence_detected\": true/false
}

Si apruebas (approved: true), el trabajo se publicará automáticamente. Si rechazas (approved: false), quedará pendiente de revisión manual.";

        $user_prompt = "Revisa este anuncio de trabajo:\n\n" . $content;

        $request_body = array(
            'model' => 'gpt-4o-mini', // Modelo eficiente y económico
            'messages' => array(
                array('role' => 'system', 'content' => $system_prompt),
                array('role' => 'user', 'content' => $user_prompt)
            ),
            'temperature' => 0.3, // Baja temperatura para respuestas más consistentes
            'max_tokens' => 200
        );

        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode($request_body)
        ));

        if (is_wp_error($response)) {
            error_log('AgroChamba AI Error: ' . $response->get_error_message());
            return array(
                'approved' => false,
                'reason' => 'Error al conectar con el servicio de IA',
                'auto_approved' => false
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            error_log('AgroChamba AI Error: ' . json_encode($response_body));
            return array(
                'approved' => false,
                'reason' => 'Error en la respuesta del servicio de IA',
                'auto_approved' => false
            );
        }

        // Extraer respuesta de la IA
        $ai_message = $response_body['choices'][0]['message']['content'] ?? '';
        
        // Intentar parsear JSON de la respuesta
        $moderation_result = json_decode($ai_message, true);
        
        if (!$moderation_result || !isset($moderation_result['approved'])) {
            // Si no es JSON válido, intentar extraer información del texto
            $approved = (stripos($ai_message, 'approved') !== false && stripos($ai_message, 'true') !== false) ||
                       (stripos($ai_message, 'aprobado') !== false);
            
            // Verificar si menciona violencia (rechazo automático)
            $violence_keywords = array('violence', 'violencia', 'violent', 'violento', 'arma', 'weapon', 'agresión', 'aggression');
            $violence_detected = false;
            foreach ($violence_keywords as $keyword) {
                if (stripos($ai_message, $keyword) !== false) {
                    $violence_detected = true;
                    $approved = false;
                    break;
                }
            }
            
            $moderation_result = array(
                'approved' => $approved && !$violence_detected,
                'reason' => $ai_message ?: 'Revisión completada',
                'confidence' => 0.7,
                'violence_detected' => $violence_detected
            );
        }

        // Si detecta violencia, rechazar automáticamente
        if (isset($moderation_result['violence_detected']) && $moderation_result['violence_detected']) {
            $moderation_result['approved'] = false;
        }

        // Determinar si se puede aprobar automáticamente
        // Solo aprobar si: está aprobado, no tiene violencia, tiene buena confianza (>= 0.7)
        $is_approved = (bool) $moderation_result['approved'];
        $has_violence = isset($moderation_result['violence_detected']) && $moderation_result['violence_detected'];
        $confidence = isset($moderation_result['confidence']) ? floatval($moderation_result['confidence']) : 0.7;
        
        // Auto-aprobar solo si: está aprobado, sin violencia, y confianza >= 0.7
        $can_auto_approve = $is_approved && !$has_violence && $confidence >= 0.7;

        // Guardar resultado en meta del post
        update_post_meta($post_id, '_ai_moderation_result', $moderation_result);
        update_post_meta($post_id, '_ai_moderation_date', current_time('mysql'));

        return array(
            'approved' => $is_approved,
            'reason' => $moderation_result['reason'] ?? ($is_approved ? 'Cumple con todos los requisitos' : 'Requiere revisión manual'),
            'confidence' => $confidence,
            'violence_detected' => $has_violence,
            'auto_approved' => $can_auto_approve // Solo auto-aprobar si cumple todos los requisitos
        );
    }
}

// ==========================================
// IMPLEMENTACIÓN PARA GEMINI (PLACEHOLDER)
// ==========================================

/**
 * Moderar con Google Gemini
 * 
 * @param string $content Contenido completo del trabajo
 * @param int $post_id ID del trabajo
 * @return array Resultado de la moderación
 */
if (!function_exists('agrochamba_moderate_with_gemini')) {
    function agrochamba_moderate_with_gemini($content, $post_id) {
        // TODO: Implementar integración con Google Gemini
        error_log('AgroChamba AI: Gemini aún no implementado');
        return array(
            'approved' => false,
            'reason' => 'Servicio Gemini aún no implementado',
            'auto_approved' => false
        );
    }
}

// ==========================================
// HOOK PARA MODERACIÓN AUTOMÁTICA
// ==========================================

/**
 * Ejecutar moderación automática cuando se crea un trabajo en estado 'pending'
 */
if (!function_exists('agrochamba_auto_moderate_job')) {
    function agrochamba_auto_moderate_job($post_id, $post, $update) {
        // Solo procesar si es un nuevo trabajo (no actualización)
        if ($update) {
            return;
        }

        // Solo procesar trabajos
        if ($post->post_type !== 'trabajo') {
            return;
        }

        // Solo procesar trabajos en estado 'pending'
        if ($post->post_status !== 'pending') {
            return;
        }

        // Evitar procesar si ya fue moderado
        $already_moderated = get_post_meta($post_id, '_ai_moderation_date', true);
        if (!empty($already_moderated)) {
            return;
        }

        // Ejecutar moderación de forma asíncrona para no bloquear la respuesta
        wp_schedule_single_event(time() + 2, 'agrochamba_execute_ai_moderation', array($post_id));
    }
    add_action('wp_insert_post', 'agrochamba_auto_moderate_job', 20, 3);
}

/**
 * Ejecutar moderación con IA
 */
if (!function_exists('agrochamba_execute_ai_moderation')) {
    function agrochamba_execute_ai_moderation($post_id) {
        error_log("AgroChamba AI: Iniciando moderación para trabajo ID: $post_id");
        
        $result = agrochamba_moderate_job_with_ai($post_id);
        
        // Verificar si se puede aprobar automáticamente
        // Condiciones: aprobado, auto-aprobado, sin violencia, sin contenido inapropiado
        $can_approve = $result['approved'] && 
                      isset($result['auto_approved']) && 
                      $result['auto_approved'] &&
                      !(isset($result['violence_detected']) && $result['violence_detected']) &&
                      !(isset($result['requires_manual_review']) && $result['requires_manual_review']);
        
        if ($can_approve) {
            // Aprobar automáticamente
            $updated = wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'publish'
            ));
            
            if (!is_wp_error($updated)) {
                error_log("AgroChamba AI: ✅ Trabajo $post_id APROBADO AUTOMÁTICAMENTE. Razón: " . ($result['reason'] ?? 'Cumple con todos los requisitos'));
                
                // Actualizar meta con resultado de aprobación
                update_post_meta($post_id, '_ai_moderation_status', 'approved');
                update_post_meta($post_id, '_ai_auto_approved', true);
                update_post_meta($post_id, '_ai_approval_date', current_time('mysql'));
                
                // Opcional: Publicar en Facebook si está configurado
                // Nota: No publicar automáticamente en Facebook, solo si el usuario lo solicitó al crear el trabajo
                // Esto se maneja en el endpoint de creación
            } else {
                error_log("AgroChamba AI: Error al aprobar trabajo $post_id: " . $updated->get_error_message());
            }
        } else {
            // Determinar razón de rechazo
            $rejection_reason = $result['reason'] ?? 'Requiere revisión manual';
            
            if (isset($result['violence_detected']) && $result['violence_detected']) {
                $rejection_reason = 'Contenido violento o inapropiado detectado';
            } elseif (isset($result['requires_manual_review']) && $result['requires_manual_review']) {
                $rejection_reason = 'Requiere revisión manual por administrador';
            } elseif (isset($result['incomplete']) && $result['incomplete']) {
                $rejection_reason = 'Información incompleta';
            }
            
            error_log("AgroChamba AI: ⚠️ Trabajo $post_id requiere revisión manual. Razón: $rejection_reason");
            
            // Actualizar meta con estado de rechazo
            update_post_meta($post_id, '_ai_moderation_status', 'pending_review');
            update_post_meta($post_id, '_ai_auto_approved', false);
            update_post_meta($post_id, '_ai_rejection_reason', $rejection_reason);
            
            // El trabajo permanece en estado 'pending' para revisión manual
        }
    }
    add_action('agrochamba_execute_ai_moderation', 'agrochamba_execute_ai_moderation', 10, 1);
}

