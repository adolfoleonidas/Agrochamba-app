<?php
/**
 * =============================================================
 * MÓDULO 12: REGENERACIÓN DE THUMBNAILS
 * =============================================================
 * 
 * Funcionalidad para regenerar thumbnails de imágenes existentes
 * y generar los nuevos tamaños personalizados de AgroChamba.
 * 
 * USO:
 * - Acceder a: /wp-admin/admin.php?page=agrochamba-regenerate-thumbnails
 * - O usar WP-CLI: wp agrochamba regenerate-thumbnails
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. PÁGINA DE ADMIN PARA REGENERAR THUMBNAILS
// ==========================================
if (!function_exists('agrochamba_add_regenerate_thumbnails_page')) {
    function agrochamba_add_regenerate_thumbnails_page() {
        add_management_page(
            'Regenerar Thumbnails AgroChamba',
            'Regenerar Thumbnails',
            'manage_options',
            'agrochamba-regenerate-thumbnails',
            'agrochamba_regenerate_thumbnails_page'
        );
    }
    add_action('admin_menu', 'agrochamba_add_regenerate_thumbnails_page');
}

// ==========================================
// 2. FUNCIÓN PARA REGENERAR THUMBNAILS
// ==========================================
if (!function_exists('agrochamba_regenerate_thumbnails')) {
    /**
     * Regenera thumbnails para una imagen específica o todas las imágenes
     * 
     * @param int|null $attachment_id ID del attachment (null para todas)
     * @return array Resultado de la operación
     */
    function agrochamba_regenerate_thumbnails($attachment_id = null) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $results = array(
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        if ($attachment_id) {
            // Regenerar una imagen específica
            $attachment = get_post($attachment_id);
            if ($attachment && wp_attachment_is_image($attachment_id)) {
                $file_path = get_attached_file($attachment_id);
                if ($file_path && file_exists($file_path)) {
                    $metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
                    if (!is_wp_error($metadata)) {
                        wp_update_attachment_metadata($attachment_id, $metadata);
                        $results['success'] = 1;
                    } else {
                        $results['failed'] = 1;
                        $results['errors'][] = "Error regenerando imagen ID $attachment_id: " . $metadata->get_error_message();
                    }
                } else {
                    $results['failed'] = 1;
                    $results['errors'][] = "Archivo no encontrado para imagen ID $attachment_id";
                }
            } else {
                $results['failed'] = 1;
                $results['errors'][] = "Imagen ID $attachment_id no encontrada o no es una imagen";
            }
        } else {
            // Regenerar todas las imágenes
            $attachments = get_posts(array(
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));
            
            foreach ($attachments as $id) {
                $file_path = get_attached_file($id);
                if ($file_path && file_exists($file_path)) {
                    $metadata = wp_generate_attachment_metadata($id, $file_path);
                    if (!is_wp_error($metadata)) {
                        wp_update_attachment_metadata($id, $metadata);
                        $results['success']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Error regenerando imagen ID $id: " . $metadata->get_error_message();
                    }
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Archivo no encontrado para imagen ID $id";
                }
            }
        }
        
        return $results;
    }
}

// ==========================================
// 3. PÁGINA DE ADMIN (UI)
// ==========================================
if (!function_exists('agrochamba_regenerate_thumbnails_page')) {
    function agrochamba_regenerate_thumbnails_page() {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para acceder a esta página.');
        }
        
        $message = '';
        $message_type = '';
        
        // Procesar acción
        if (isset($_POST['regenerate_thumbnails']) && check_admin_referer('agrochamba_regenerate_thumbnails')) {
            $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : null;
            
            // Ejecutar en segundo plano para no bloquear
            if (function_exists('agrochamba_regenerate_thumbnails')) {
                $results = agrochamba_regenerate_thumbnails($attachment_id);
                
                if ($results['success'] > 0) {
                    $message = sprintf(
                        'Se regeneraron exitosamente %d imagen(es).',
                        $results['success']
                    );
                    $message_type = 'success';
                }
                
                if ($results['failed'] > 0) {
                    $error_msg = sprintf(
                        'Falló la regeneración de %d imagen(es).',
                        $results['failed']
                    );
                    $message = $message ? $message . ' ' . $error_msg : $error_msg;
                    $message_type = $message_type === 'success' ? 'warning' : 'error';
                }
                
                if (!empty($results['errors'])) {
                    $message .= '<br><strong>Errores:</strong><ul>';
                    foreach ($results['errors'] as $error) {
                        $message .= '<li>' . esc_html($error) . '</li>';
                    }
                    $message .= '</ul>';
                }
            }
        }
        
        // Contar imágenes
        $total_images = wp_count_posts('attachment');
        $total_image_count = isset($total_images->inherit) ? $total_images->inherit : 0;
        ?>
        <div class="wrap">
            <h1>Regenerar Thumbnails AgroChamba</h1>
            
            <?php if ($message): ?>
                <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
                    <p><?php echo $message; ?></p>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Información</h2>
                <p>
                    Esta herramienta regenera todos los tamaños de imagen (thumbnails) para las imágenes existentes,
                    incluyendo los nuevos tamaños personalizados de AgroChamba:
                </p>
                <ul>
                    <li><strong>agrochamba_card</strong> (400x300px) - Para cards en lista</li>
                    <li><strong>agrochamba_detail</strong> (800x600px) - Para slider de detalle</li>
                    <li><strong>agrochamba_thumb</strong> (150x150px) - Para miniaturas</li>
                    <li><strong>agrochamba_profile</strong> (300x300px) - Para perfiles de empresa</li>
                </ul>
                <p>
                    <strong>Total de imágenes:</strong> <?php echo number_format($total_image_count); ?>
                </p>
                <p class="description">
                    <strong>Nota:</strong> Este proceso puede tardar varios minutos dependiendo de la cantidad de imágenes.
                    Se recomienda ejecutarlo durante horas de bajo tráfico.
                </p>
            </div>
            
            <div class="card">
                <h2>Regenerar Thumbnails</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('agrochamba_regenerate_thumbnails'); ?>
                    <p>
                        <label>
                            <input type="radio" name="regenerate_type" value="all" checked>
                            Regenerar todas las imágenes (<?php echo number_format($total_image_count); ?> imágenes)
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="radio" name="regenerate_type" value="specific">
                            Regenerar una imagen específica:
                            <input type="number" name="attachment_id" min="1" placeholder="ID de imagen" style="width: 150px; margin-left: 10px;">
                        </label>
                    </p>
                    <p class="submit">
                        <input type="submit" name="regenerate_thumbnails" class="button button-primary" value="Regenerar Thumbnails" onclick="return confirm('¿Estás seguro? Este proceso puede tardar varios minutos.');">
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
}

// ==========================================
// 4. COMANDO WP-CLI (OPCIONAL)
// ==========================================
if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
    /**
     * Comando WP-CLI para regenerar thumbnails
     * 
     * Uso: wp agrochamba regenerate-thumbnails [--attachment-id=<id>]
     */
    WP_CLI::add_command('agrochamba regenerate-thumbnails', function($args, $assoc_args) {
        $attachment_id = isset($assoc_args['attachment-id']) ? intval($assoc_args['attachment-id']) : null;
        
        WP_CLI::line('Regenerando thumbnails...');
        
        if (function_exists('agrochamba_regenerate_thumbnails')) {
            $results = agrochamba_regenerate_thumbnails($attachment_id);
            
            if ($results['success'] > 0) {
                WP_CLI::success(sprintf('Se regeneraron exitosamente %d imagen(es).', $results['success']));
            }
            
            if ($results['failed'] > 0) {
                WP_CLI::warning(sprintf('Falló la regeneración de %d imagen(es).', $results['failed']));
            }
            
            if (!empty($results['errors'])) {
                foreach ($results['errors'] as $error) {
                    WP_CLI::error($error, false);
                }
            }
        } else {
            WP_CLI::error('La función agrochamba_regenerate_thumbnails no está disponible.');
        }
    });
}

