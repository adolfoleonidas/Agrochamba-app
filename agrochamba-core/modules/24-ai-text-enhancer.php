<?php
/**
 * Módulo de Mejora de Texto con IA
 *
 * Proporciona funcionalidades para:
 * - Mejorar automáticamente la redacción de ofertas laborales
 * - Generar títulos optimizados para SEO
 * - Extraer texto de imágenes (OCR) y convertirlo en redacción profesional
 *
 * Sistema de créditos:
 * - IA Mejorar texto: 1 crédito
 * - IA Generar título: 1 crédito
 * - IA OCR imagen: 2 créditos
 * - Administradores: Sin límites (no consumen créditos)
 *
 * @package AgroChamba
 * @subpackage Modules
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// FUNCIONES DE GESTIÓN DE LÍMITES (BASADAS EN CRÉDITOS)
// ==========================================

/**
 * Obtener el número de usos de IA del usuario (legacy, mantener por compatibilidad)
 */
if (!function_exists('agrochamba_get_ai_usage_count')) {
    function agrochamba_get_ai_usage_count($user_id) {
        $count = get_user_meta($user_id, '_agrochamba_ai_usage_count', true);
        return intval($count) ?: 0;
    }
}

/**
 * Descontar créditos por uso de IA e incrementar contador.
 *
 * @param int    $user_id    ID del usuario
 * @param string $ai_action  Tipo de acción: 'enhance', 'title', 'ocr'
 * @return bool  true si se pudo descontar, false si no hay créditos
 */
if (!function_exists('agrochamba_increment_ai_usage')) {
    function agrochamba_increment_ai_usage($user_id, $ai_action = 'enhance') {
        // Incrementar contador legacy
        $current = agrochamba_get_ai_usage_count($user_id);
        update_user_meta($user_id, '_agrochamba_ai_usage_count', $current + 1);
        update_user_meta($user_id, '_agrochamba_ai_last_usage', current_time('mysql'));

        // Descontar créditos si el sistema está activo
        if (function_exists('agrochamba_credits_deduct')) {
            $cost_map = array(
                'enhance' => defined('AGROCHAMBA_CREDIT_COST_AI_ENHANCE') ? AGROCHAMBA_CREDIT_COST_AI_ENHANCE : 1,
                'title'   => defined('AGROCHAMBA_CREDIT_COST_AI_TITLE') ? AGROCHAMBA_CREDIT_COST_AI_TITLE : 1,
                'ocr'     => defined('AGROCHAMBA_CREDIT_COST_AI_OCR') ? AGROCHAMBA_CREDIT_COST_AI_OCR : 2,
            );
            $cost = isset($cost_map[$ai_action]) ? $cost_map[$ai_action] : 1;

            agrochamba_credits_deduct($user_id, $cost, 'IA - ' . $ai_action, 'ai_' . $ai_action);
        }

        return $current + 1;
    }
}

/**
 * Verificar si el usuario puede usar IA (basado en créditos).
 *
 * @param int    $user_id    ID del usuario
 * @param string $ai_action  Tipo de acción: 'enhance', 'title', 'ocr'
 * @return array Estado de uso
 */
if (!function_exists('agrochamba_check_ai_usage_limit')) {
    function agrochamba_check_ai_usage_limit($user_id, $ai_action = 'enhance') {
        $user = get_userdata($user_id);

        if (!$user) {
            return array(
                'allowed' => false,
                'remaining' => 0,
                'limit' => 0,
                'is_premium' => false,
                'reason' => 'Usuario no válido'
            );
        }

        // Los administradores tienen uso ilimitado
        if (in_array('administrator', $user->roles)) {
            return array(
                'allowed' => true,
                'remaining' => -1,
                'limit' => -1,
                'is_premium' => true,
                'reason' => 'Acceso ilimitado de administrador'
            );
        }

        // Sistema de créditos
        if (function_exists('agrochamba_credits_get_balance')) {
            $balance = agrochamba_credits_get_balance($user_id);

            $cost_map = array(
                'enhance' => defined('AGROCHAMBA_CREDIT_COST_AI_ENHANCE') ? AGROCHAMBA_CREDIT_COST_AI_ENHANCE : 1,
                'title'   => defined('AGROCHAMBA_CREDIT_COST_AI_TITLE') ? AGROCHAMBA_CREDIT_COST_AI_TITLE : 1,
                'ocr'     => defined('AGROCHAMBA_CREDIT_COST_AI_OCR') ? AGROCHAMBA_CREDIT_COST_AI_OCR : 2,
            );
            $cost = isset($cost_map[$ai_action]) ? $cost_map[$ai_action] : 1;
            $can_afford = $balance >= $cost;

            return array(
                'allowed'   => $can_afford,
                'remaining' => $balance,
                'used'      => agrochamba_get_ai_usage_count($user_id),
                'limit'     => -1,
                'is_premium' => false,
                'cost'      => $cost,
                'balance'   => $balance,
                'reason'    => $can_afford
                    ? "Tienes $balance créditos disponibles (costo: $cost)"
                    : "Créditos insuficientes. Necesitas $cost, tienes $balance. Compra más créditos."
            );
        }

        // Fallback: sin sistema de créditos, permitir
        return array(
            'allowed' => true,
            'remaining' => -1,
            'limit' => -1,
            'is_premium' => false,
            'reason' => 'Sistema de créditos no configurado'
        );
    }
}

/**
 * Resetear el contador de usos de IA (para admins)
 */
if (!function_exists('agrochamba_reset_ai_usage')) {
    function agrochamba_reset_ai_usage($user_id) {
        delete_user_meta($user_id, '_agrochamba_ai_usage_count');
        delete_user_meta($user_id, '_agrochamba_ai_last_usage');
        return true;
    }
}

/**
 * Otorgar acceso premium a un usuario (legacy, mantener compatibilidad)
 */
if (!function_exists('agrochamba_grant_ai_premium')) {
    function agrochamba_grant_ai_premium($user_id, $expiration = null) {
        update_user_meta($user_id, '_agrochamba_ai_premium', true);
        if ($expiration) {
            update_user_meta($user_id, '_agrochamba_ai_premium_expires', $expiration);
        }
        return true;
    }
}

// ==========================================
// REGISTRO DE ENDPOINTS REST API
// ==========================================

add_action('rest_api_init', function () {
    // Endpoint: Obtener estado de usos de IA
    register_rest_route('agrochamba/v1', '/ai/usage', array(
        'methods' => 'GET',
        'callback' => 'agrochamba_get_ai_usage_status',
        'permission_callback' => 'agrochamba_ai_permission_check'
    ));
    // Endpoint: Mejorar texto de oferta laboral
    register_rest_route('agrochamba/v1', '/ai/enhance-text', array(
        'methods' => 'POST',
        'callback' => 'agrochamba_ai_enhance_text',
        'permission_callback' => 'agrochamba_ai_permission_check',
        'args' => array(
            'text' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Texto a mejorar',
                'sanitize_callback' => 'sanitize_textarea_field'
            ),
            'type' => array(
                'required' => false,
                'type' => 'string',
                'default' => 'job',
                'description' => 'Tipo de contenido: job, blog',
                'enum' => array('job', 'blog')
            )
        )
    ));

    // Endpoint: Generar título SEO
    register_rest_route('agrochamba/v1', '/ai/generate-title', array(
        'methods' => 'POST',
        'callback' => 'agrochamba_ai_generate_title',
        'permission_callback' => 'agrochamba_ai_permission_check',
        'args' => array(
            'description' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Descripción del trabajo para generar título',
                'sanitize_callback' => 'sanitize_textarea_field'
            ),
            'location' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Ubicación del trabajo',
                'sanitize_callback' => 'sanitize_text_field'
            )
        )
    ));

    // Endpoint: OCR - Extraer texto de imagen y mejorar
    register_rest_route('agrochamba/v1', '/ai/ocr', array(
        'methods' => 'POST',
        'callback' => 'agrochamba_ai_ocr_enhance',
        'permission_callback' => 'agrochamba_ai_permission_check',
        'args' => array(
            'image_url' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'URL de la imagen a procesar',
                'sanitize_callback' => 'esc_url_raw'
            ),
            'image_id' => array(
                'required' => false,
                'type' => 'integer',
                'description' => 'ID del media en WordPress'
            ),
            'enhance' => array(
                'required' => false,
                'type' => 'boolean',
                'default' => true,
                'description' => 'Si debe mejorar el texto extraído'
            )
        )
    ));
});

// ==========================================
// VERIFICACIÓN DE PERMISOS
// ==========================================

/**
 * Verificar permisos para usar los endpoints de IA
 */
if (!function_exists('agrochamba_ai_permission_check')) {
    function agrochamba_ai_permission_check($request) {
        // Permitir a usuarios autenticados
        if (is_user_logged_in()) {
            return true;
        }

        // Intentar validar token si existe
        if (function_exists('agrochamba_validate_auth')) {
            return agrochamba_validate_auth($request);
        }

        return new WP_Error(
            'rest_forbidden',
            'Debes iniciar sesión para usar esta función',
            array('status' => 401)
        );
    }
}

// ==========================================
// ENDPOINT: OBTENER ESTADO DE USOS DE IA
// ==========================================

/**
 * Obtener estado de usos de IA del usuario actual
 */
if (!function_exists('agrochamba_get_ai_usage_status')) {
    function agrochamba_get_ai_usage_status($request) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error(
                'not_authenticated',
                'Debes iniciar sesión',
                array('status' => 401)
            );
        }
        
        $status = agrochamba_check_ai_usage_limit($user_id);
        
        return rest_ensure_response(array(
            'success' => true,
            'allowed' => $status['allowed'],
            'remaining' => $status['remaining'],
            'used' => $status['used'] ?? 0,
            'limit' => $status['limit'],
            'is_premium' => $status['is_premium'],
            'message' => $status['reason']
        ));
    }
}

// ==========================================
// ENDPOINT: MEJORAR TEXTO
// ==========================================

/**
 * Mejorar texto de oferta laboral usando IA
 */
if (!function_exists('agrochamba_ai_enhance_text')) {
    function agrochamba_ai_enhance_text($request) {
        $user_id = get_current_user_id();
        
        // Verificar límite de usos
        $usage_status = agrochamba_check_ai_usage_limit($user_id);
        if (!$usage_status['allowed']) {
            return new WP_Error(
                'ai_limit_reached',
                $usage_status['reason'],
                array(
                    'status' => 403,
                    'code' => 'limit_reached',
                    'remaining' => 0,
                    'limit' => $usage_status['limit'],
                    'is_premium' => false
                )
            );
        }
        
        $text = $request->get_param('text');
        $type = $request->get_param('type') ?: 'job';

        if (empty($text)) {
            return new WP_Error(
                'missing_text',
                'El texto a mejorar es requerido',
                array('status' => 400)
            );
        }

        // Limitar longitud del texto (máximo 2000 caracteres)
        if (strlen($text) > 2000) {
            $text = substr($text, 0, 2000);
        }

        $api_key = agrochamba_get_ai_api_key();
        if (empty($api_key)) {
            return new WP_Error(
                'ai_not_configured',
                'El servicio de IA no está configurado',
                array('status' => 503)
            );
        }

        // Construir prompt según el tipo de contenido
        if ($type === 'job') {
            $system_prompt = "Eres un experto redactor de ofertas de empleo agrícola en Perú. Tu tarea es mejorar y profesionalizar textos de ofertas laborales.

INSTRUCCIONES:
1. Transforma el texto informal en una oferta profesional, clara y atractiva
2. Mantén TODA la información original (ubicación, fechas, tipo de trabajo, beneficios, requisitos)
3. Usa un tono profesional pero accesible para trabajadores del campo
4. Organiza la información en secciones claras (si es necesario)
5. Destaca los beneficios y oportunidades
6. NO inventes información que no esté en el texto original
7. Máximo 500 palabras

Responde SOLO con el texto mejorado, sin explicaciones adicionales.";
        } else {
            $system_prompt = "Eres un experto redactor de contenido para un blog sobre agricultura y empleos agrícolas en Perú.

INSTRUCCIONES:
1. Transforma el texto en un artículo profesional y bien estructurado
2. Mantén la información original
3. Usa un tono informativo y amigable
4. NO inventes información
5. Máximo 500 palabras

Responde SOLO con el texto mejorado, sin explicaciones adicionales.";
        }

        $user_prompt = "Mejora el siguiente texto:\n\n" . $text;

        $result = agrochamba_call_openai_api($system_prompt, $user_prompt);

        if (is_wp_error($result)) {
            return $result;
        }

        // Incrementar contador de usos (solo si no es premium/admin)
        if (!$usage_status['is_premium']) {
            agrochamba_increment_ai_usage($user_id);
        }
        
        // Obtener estado actualizado
        $updated_status = agrochamba_check_ai_usage_limit($user_id);

        return rest_ensure_response(array(
            'success' => true,
            'original_text' => $text,
            'enhanced_text' => $result['content'],
            'tokens_used' => $result['tokens_used'] ?? 0,
            'usage' => array(
                'remaining' => $updated_status['remaining'],
                'used' => $updated_status['used'] ?? 0,
                'limit' => $updated_status['limit'],
                'is_premium' => $updated_status['is_premium']
            )
        ));
    }
}

// ==========================================
// ENDPOINT: GENERAR TÍTULO SEO
// ==========================================

/**
 * Generar título optimizado para SEO
 */
if (!function_exists('agrochamba_ai_generate_title')) {
    function agrochamba_ai_generate_title($request) {
        $user_id = get_current_user_id();
        
        // Verificar límite de usos
        $usage_status = agrochamba_check_ai_usage_limit($user_id);
        if (!$usage_status['allowed']) {
            return new WP_Error(
                'ai_limit_reached',
                $usage_status['reason'],
                array(
                    'status' => 403,
                    'code' => 'limit_reached',
                    'remaining' => 0,
                    'limit' => $usage_status['limit'],
                    'is_premium' => false
                )
            );
        }
        
        $description = $request->get_param('description');
        $location = $request->get_param('location');

        if (empty($description)) {
            return new WP_Error(
                'missing_description',
                'La descripción es requerida para generar el título',
                array('status' => 400)
            );
        }

        // Limitar longitud de la descripción
        if (strlen($description) > 1000) {
            $description = substr($description, 0, 1000);
        }

        $api_key = agrochamba_get_ai_api_key();
        if (empty($api_key)) {
            return new WP_Error(
                'ai_not_configured',
                'El servicio de IA no está configurado',
                array('status' => 503)
            );
        }

        $system_prompt = "Eres un experto en SEO para ofertas de empleo agrícola en Perú.

INSTRUCCIONES para generar el título:
1. Crea un título claro, conciso y atractivo (máximo 60 caracteres para SEO óptimo)
2. Incluye el tipo de trabajo o puesto
3. Si hay ubicación disponible, inclúyela de forma natural
4. Usa palabras clave relevantes (empleo, trabajo, cosecha, agrícola, etc.)
5. El título debe ser llamativo para buscadores y candidatos
6. NO uses signos de exclamación ni emojis
7. Formato: simple, sin comillas ni decoraciones

Responde SOLO con el título, nada más.";

        $location_info = !empty($location) ? " La ubicación es: $location." : "";
        $user_prompt = "Genera un título SEO para esta oferta de empleo agrícola.$location_info\n\nDescripción:\n" . $description;

        $result = agrochamba_call_openai_api($system_prompt, $user_prompt, 80);

        if (is_wp_error($result)) {
            return $result;
        }

        // Limpiar título (quitar comillas si las hay)
        $title = trim($result['content'], ' "\'');
        
        // Asegurar que no exceda 200 caracteres (límite de la app)
        if (strlen($title) > 200) {
            $title = substr($title, 0, 197) . '...';
        }

        // Incrementar contador de usos (solo si no es premium/admin)
        if (!$usage_status['is_premium']) {
            agrochamba_increment_ai_usage($user_id);
        }
        
        // Obtener estado actualizado
        $updated_status = agrochamba_check_ai_usage_limit($user_id);

        return rest_ensure_response(array(
            'success' => true,
            'title' => $title,
            'character_count' => strlen($title),
            'seo_optimal' => strlen($title) >= 50 && strlen($title) <= 60,
            'usage' => array(
                'remaining' => $updated_status['remaining'],
                'used' => $updated_status['used'] ?? 0,
                'limit' => $updated_status['limit'],
                'is_premium' => $updated_status['is_premium']
            )
        ));
    }
}

// ==========================================
// ENDPOINT: OCR Y MEJORA DE TEXTO
// ==========================================

/**
 * Extraer texto de imagen y mejorarlo
 */
if (!function_exists('agrochamba_ai_ocr_enhance')) {
    function agrochamba_ai_ocr_enhance($request) {
        $user_id = get_current_user_id();
        
        // Verificar límite de usos
        $usage_status = agrochamba_check_ai_usage_limit($user_id);
        if (!$usage_status['allowed']) {
            return new WP_Error(
                'ai_limit_reached',
                $usage_status['reason'],
                array(
                    'status' => 403,
                    'code' => 'limit_reached',
                    'remaining' => 0,
                    'limit' => $usage_status['limit'],
                    'is_premium' => false
                )
            );
        }
        
        $image_url = $request->get_param('image_url');
        $image_id = $request->get_param('image_id');
        $enhance = $request->get_param('enhance') !== false;

        // Obtener URL de imagen desde ID si no se proporciona URL
        if (empty($image_url) && !empty($image_id)) {
            $image_url = wp_get_attachment_image_url($image_id, 'large');
        }

        if (empty($image_url)) {
            return new WP_Error(
                'missing_image',
                'Se requiere una imagen (image_url o image_id)',
                array('status' => 400)
            );
        }

        $api_key = agrochamba_get_ai_api_key();
        if (empty($api_key)) {
            return new WP_Error(
                'ai_not_configured',
                'El servicio de IA no está configurado',
                array('status' => 503)
            );
        }

        // Paso 1: Extraer texto de la imagen con GPT-4 Vision
        $ocr_result = agrochamba_extract_text_from_image($image_url);
        
        if (is_wp_error($ocr_result)) {
            return $ocr_result;
        }

        $extracted_text = $ocr_result['text'];

        if (empty($extracted_text) || strlen(trim($extracted_text)) < 10) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'No se pudo extraer texto legible de la imagen',
                'extracted_text' => $extracted_text
            ));
        }

        // Paso 2: Mejorar el texto si se solicita
        if ($enhance) {
            $enhance_request = new WP_REST_Request('POST', '/agrochamba/v1/ai/enhance-text');
            $enhance_request->set_param('text', $extracted_text);
            $enhance_request->set_param('type', 'job');
            
            $enhance_result = agrochamba_ai_enhance_text($enhance_request);
            
            if (is_wp_error($enhance_result)) {
                // Si falla la mejora, devolver texto extraído sin mejorar
                return rest_ensure_response(array(
                    'success' => true,
                    'extracted_text' => $extracted_text,
                    'enhanced_text' => null,
                    'message' => 'Texto extraído correctamente, pero no se pudo mejorar'
                ));
            }

            $enhanced_data = $enhance_result->get_data();

            // Incrementar contador de usos (solo si no es premium/admin)
            // Nota: enhance-text ya incrementó si se usó, así que no duplicamos
            if (!$usage_status['is_premium']) {
                agrochamba_increment_ai_usage($user_id);
            }
            
            // Obtener estado actualizado
            $updated_status = agrochamba_check_ai_usage_limit($user_id);

            return rest_ensure_response(array(
                'success' => true,
                'extracted_text' => $extracted_text,
                'enhanced_text' => $enhanced_data['enhanced_text'] ?? $extracted_text,
                'usage' => array(
                    'remaining' => $updated_status['remaining'],
                    'used' => $updated_status['used'] ?? 0,
                    'limit' => $updated_status['limit'],
                    'is_premium' => $updated_status['is_premium']
                )
            ));
        }

        // Incrementar contador de usos (solo si no es premium/admin)
        if (!$usage_status['is_premium']) {
            agrochamba_increment_ai_usage($user_id);
        }
        
        // Obtener estado actualizado
        $updated_status = agrochamba_check_ai_usage_limit($user_id);

        return rest_ensure_response(array(
            'success' => true,
            'extracted_text' => $extracted_text,
            'enhanced_text' => null,
            'usage' => array(
                'remaining' => $updated_status['remaining'],
                'used' => $updated_status['used'] ?? 0,
                'limit' => $updated_status['limit'],
                'is_premium' => $updated_status['is_premium']
            )
        ));
    }
}

// ==========================================
// FUNCIÓN: EXTRAER TEXTO DE IMAGEN (OCR)
// ==========================================

/**
 * Extraer texto de una imagen usando GPT-4 Vision
 */
if (!function_exists('agrochamba_extract_text_from_image')) {
    function agrochamba_extract_text_from_image($image_url) {
        $api_key = agrochamba_get_ai_api_key();
        $api_url = 'https://api.openai.com/v1/chat/completions';

        $request_body = array(
            'model' => 'gpt-4o-mini', // Modelo eficiente con visión
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array(
                            'type' => 'text',
                            'text' => 'Extrae TODO el texto visible en esta imagen. Si hay texto manuscrito o impreso, transcríbelo exactamente. Si no hay texto legible, responde "NO_TEXT". Responde SOLO con el texto extraído, sin explicaciones.'
                        ),
                        array(
                            'type' => 'image_url',
                            'image_url' => array('url' => $image_url)
                        )
                    )
                )
            ),
            'max_tokens' => 1000
        );

        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'timeout' => 45,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode($request_body)
        ));

        if (is_wp_error($response)) {
            error_log('AgroChamba AI OCR Error: ' . $response->get_error_message());
            return new WP_Error(
                'api_error',
                'Error al procesar la imagen',
                array('status' => 500)
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            error_log('AgroChamba AI OCR Error: ' . json_encode($response_body));
            return new WP_Error(
                'api_error',
                'Error al procesar la imagen con IA',
                array('status' => 500)
            );
        }

        $extracted_text = $response_body['choices'][0]['message']['content'] ?? '';
        
        // Verificar si no hay texto
        if (strtoupper(trim($extracted_text)) === 'NO_TEXT') {
            $extracted_text = '';
        }

        return array(
            'text' => trim($extracted_text),
            'tokens_used' => $response_body['usage']['total_tokens'] ?? 0
        );
    }
}

// ==========================================
// FUNCIÓN AUXILIAR: LLAMAR API OPENAI
// ==========================================

/**
 * Llamar a la API de OpenAI para generación de texto
 */
if (!function_exists('agrochamba_call_openai_api')) {
    function agrochamba_call_openai_api($system_prompt, $user_prompt, $max_tokens = 800) {
        $api_key = agrochamba_get_ai_api_key();
        $api_url = 'https://api.openai.com/v1/chat/completions';

        $request_body = array(
            'model' => 'gpt-4o-mini', // Modelo eficiente y económico
            'messages' => array(
                array('role' => 'system', 'content' => $system_prompt),
                array('role' => 'user', 'content' => $user_prompt)
            ),
            'temperature' => 0.7,
            'max_tokens' => $max_tokens
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
            return new WP_Error(
                'api_error',
                'Error al conectar con el servicio de IA',
                array('status' => 500)
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            error_log('AgroChamba AI Error: ' . json_encode($response_body));
            $error_message = $response_body['error']['message'] ?? 'Error en el servicio de IA';
            return new WP_Error(
                'api_error',
                $error_message,
                array('status' => $response_code)
            );
        }

        $content = $response_body['choices'][0]['message']['content'] ?? '';
        
        return array(
            'content' => trim($content),
            'tokens_used' => $response_body['usage']['total_tokens'] ?? 0
        );
    }
}

// ==========================================
// REGISTRAR MÓDULO EN LA LISTA
// ==========================================

error_log('AgroChamba AI: Módulo de Mejora de Texto cargado correctamente');

