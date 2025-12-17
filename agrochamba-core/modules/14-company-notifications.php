<?php
/**
 * =============================================================
 * MÓDULO 14: NOTIFICACIONES DE EMPRESAS SEGUIDAS
 * =============================================================
 * 
 * Funcionalidad para notificar a los usuarios cuando una empresa
 * que siguen publica nuevas ofertas de trabajo.
 * 
 * Funciones:
 * - Enviar notificaciones por email cuando se publica un trabajo nuevo
 * - Permitir a los usuarios activar/desactivar notificaciones
 * - Endpoint para gestionar preferencias de notificaciones
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. ENVIAR NOTIFICACIONES AL PUBLICAR TRABAJO
// ==========================================
if (!function_exists('agrochamba_notify_followers_new_job')) {
    /**
     * Notificar a los seguidores cuando se publica un trabajo nuevo
     */
    function agrochamba_notify_followers_new_job($post_id, $post, $update) {
        // Solo procesar si es un nuevo trabajo publicado (no actualizaciones)
        if ($update) {
            return;
        }

        // Solo procesar trabajos publicados
        if ($post->post_type !== 'trabajo' || $post->post_status !== 'publish') {
            return;
        }

        // Obtener la empresa asociada al trabajo
        $empresa_terms = wp_get_post_terms($post_id, 'empresa');
        if (empty($empresa_terms) || is_wp_error($empresa_terms)) {
            return;
        }

        $empresa_term = $empresa_terms[0];
        
        // Buscar el post de empresa asociado a este término
        $empresa_posts = get_posts(array(
            'post_type' => 'empresa',
            'posts_per_page' => 1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'empresa',
                    'field' => 'term_id',
                    'terms' => $empresa_term->term_id,
                ),
            ),
        ));

        if (empty($empresa_posts)) {
            return;
        }

        $empresa_id = $empresa_posts[0]->ID;
        $empresa_name = get_the_title($empresa_id);
        $empresa_razon_social = get_post_meta($empresa_id, 'razon_social', true);
        $empresa_display_name = !empty($empresa_razon_social) ? $empresa_razon_social : $empresa_name;

        // Obtener seguidores de la empresa
        $followers = get_post_meta($empresa_id, '_empresa_followers', true);
        if (!is_array($followers) || empty($followers)) {
            return;
        }

        // Obtener información del trabajo
        $job_title = get_the_title($post_id);
        $job_url = get_permalink($post_id);
        $job_excerpt = get_the_excerpt($post_id);
        if (empty($job_excerpt)) {
            $job_excerpt = wp_trim_words(strip_tags($post->post_content), 30);
        }

        // Ubicación del trabajo
        $ubicacion_terms = wp_get_post_terms($post_id, 'ubicacion');
        $ubicacion_name = '';
        if (!empty($ubicacion_terms) && !is_wp_error($ubicacion_terms)) {
            $ubicacion_name = $ubicacion_terms[0]->name;
        }

        // Salario
        $salario_min = get_post_meta($post_id, 'salario_min', true);
        $salario_max = get_post_meta($post_id, 'salario_max', true);
        $salario_text = '';
        if (!empty($salario_min) || !empty($salario_max)) {
            if (!empty($salario_min) && !empty($salario_max)) {
                $salario_text = 'S/ ' . number_format($salario_min, 0) . ' - S/ ' . number_format($salario_max, 0);
            } elseif (!empty($salario_min)) {
                $salario_text = 'Desde S/ ' . number_format($salario_min, 0);
            } elseif (!empty($salario_max)) {
                $salario_text = 'Hasta S/ ' . number_format($salario_max, 0);
            }
        }

        // Enviar notificaciones a cada seguidor que tenga las notificaciones activadas
        foreach ($followers as $follower_id) {
            $follower = get_userdata($follower_id);
            if (!$follower) {
                continue;
            }

            // Verificar si el usuario tiene las notificaciones activadas
            $notifications_enabled = get_user_meta($follower_id, 'agrochamba_company_notifications', true);
            if ($notifications_enabled === '0' || $notifications_enabled === false) {
                continue; // Usuario tiene notificaciones desactivadas
            }

            // Por defecto, las notificaciones están activadas si no se ha configurado
            // Enviar email
            $subject = sprintf('Nueva oferta de trabajo de %s', $empresa_display_name);
            
            $message = sprintf("Hola %s,\n\n", $follower->display_name);
            $message .= sprintf("¡%s ha publicado una nueva oferta de trabajo!\n\n", $empresa_display_name);
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= sprintf("📋 Puesto: %s\n", $job_title);
            
            if (!empty($ubicacion_name)) {
                $message .= sprintf("📍 Ubicación: %s\n", $ubicacion_name);
            }
            
            if (!empty($salario_text)) {
                $message .= sprintf("💰 Salario: %s\n", $salario_text);
            }
            
            $message .= "\n";
            $message .= sprintf("📝 Descripción:\n%s\n\n", $job_excerpt);
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= sprintf("👉 Ver más detalles: %s\n\n", $job_url);
            $message .= "Si ya no deseas recibir estas notificaciones, puedes desactivarlas en tu perfil de AgroChamba.\n\n";
            $message .= "¡Buena suerte en tu búsqueda!\n";
            $message .= "El equipo de AgroChamba\n";

            // Enviar email
            $email_sent = wp_mail(
                $follower->user_email,
                $subject,
                $message,
                array(
                    'Content-Type: text/plain; charset=UTF-8',
                    'From: AgroChamba <' . get_option('admin_email') . '>'
                )
            );

            if ($email_sent) {
                error_log(sprintf('AgroChamba Notifications: Email enviado a %s (%d) sobre nuevo trabajo de %s', $follower->user_email, $follower_id, $empresa_display_name));
            } else {
                error_log(sprintf('AgroChamba Notifications: Error al enviar email a %s (%d)', $follower->user_email, $follower_id));
            }
        }
    }
    
    // Hook para cuando se publica un trabajo nuevo
    add_action('wp_insert_post', 'agrochamba_notify_followers_new_job', 30, 3);
}

// ==========================================
// 2. ENDPOINTS PARA GESTIONAR NOTIFICACIONES
// ==========================================
if (!function_exists('agrochamba_register_notification_routes')) {
    function agrochamba_register_notification_routes() {
        // Obtener estado de notificaciones del usuario
        register_rest_route('agrochamba/v1', '/notifications/company', array(
            'methods' => 'GET',
            'callback' => 'agrochamba_get_notification_preferences',
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ));

        // Actualizar preferencias de notificaciones
        register_rest_route('agrochamba/v1', '/notifications/company', array(
            'methods' => 'POST',
            'callback' => 'agrochamba_update_notification_preferences',
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ));
    }
    add_action('rest_api_init', 'agrochamba_register_notification_routes');
}

/**
 * Obtener preferencias de notificaciones del usuario
 */
if (!function_exists('agrochamba_get_notification_preferences')) {
    function agrochamba_get_notification_preferences($request) {
        $user_id = get_current_user_id();
        $notifications_enabled = get_user_meta($user_id, 'agrochamba_company_notifications', true);
        
        // Por defecto, las notificaciones están activadas
        if ($notifications_enabled === '' || $notifications_enabled === null) {
            $notifications_enabled = '1';
        }

        return new WP_REST_Response(array(
            'notifications_enabled' => $notifications_enabled === '1',
        ), 200);
    }
}

/**
 * Actualizar preferencias de notificaciones del usuario
 */
if (!function_exists('agrochamba_update_notification_preferences')) {
    function agrochamba_update_notification_preferences($request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        
        $enabled = isset($params['enabled']) ? filter_var($params['enabled'], FILTER_VALIDATE_BOOLEAN) : true;
        
        update_user_meta($user_id, 'agrochamba_company_notifications', $enabled ? '1' : '0');

        return new WP_REST_Response(array(
            'success' => true,
            'notifications_enabled' => $enabled,
            'message' => $enabled ? 'Notificaciones activadas' : 'Notificaciones desactivadas',
        ), 200);
    }
}

