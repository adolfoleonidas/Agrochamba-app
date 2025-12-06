<?php
/**
 * Script de migración: Embebir imágenes de gallery_ids en el contenido de trabajos existentes
 * 
 * Este script actualiza todos los trabajos existentes para que sus imágenes
 * estén embebidas en el contenido HTML, mejorando el SEO y la visibilidad en Google.
 * 
 * USO:
 * 1. Ejecutar desde WP-CLI: wp eval-file agrochamba-core/includes/migrate-images-to-content.php
 * 2. O agregar temporalmente en functions.php y visitar cualquier página del admin
 */

if (!defined('ABSPATH')) {
    // Si se ejecuta desde WP-CLI, cargar WordPress
    require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');
}

function agrochamba_migrate_images_to_content() {
    // Obtener todos los trabajos
    $args = array(
        'post_type' => 'trabajo',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'pending', 'draft'),
    );
    
    $query = new WP_Query($args);
    $updated = 0;
    $skipped = 0;
    $errors = 0;
    
    echo "Iniciando migración de imágenes al contenido...\n";
    echo "Total de trabajos a procesar: " . $query->found_posts . "\n\n";
    
    foreach ($query->posts as $post) {
        $post_id = $post->ID;
        $gallery_ids = get_post_meta($post_id, 'gallery_ids', true);
        
        // Si no hay gallery_ids, saltar
        if (empty($gallery_ids) || !is_array($gallery_ids)) {
            $skipped++;
            continue;
        }
        
        // Verificar si las imágenes ya están en el contenido
        $first_img_id = $gallery_ids[0];
        $first_img_url = wp_get_attachment_image_url($first_img_id, 'large');
        
        if ($first_img_url && strpos($post->post_content, $first_img_url) !== false) {
            // Las imágenes ya están embebidas, saltar
            $skipped++;
            continue;
        }
        
        // Preparar HTML de imágenes
        $images_html = '';
        $job_title = $post->post_title;
        
        foreach ($gallery_ids as $img_id) {
            $img_url = wp_get_attachment_image_url($img_id, 'large');
            $img_alt = get_post_meta($img_id, '_wp_attachment_image_alt', true);
            
            // Si no hay alt text, usar el título del trabajo
            if (empty($img_alt)) {
                $img_alt = $job_title;
            }
            
            if ($img_url) {
                // Crear HTML de imagen con atributos SEO-friendly
                $images_html .= '<figure class="wp-block-image size-large">' . "\n";
                $images_html .= '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt) . '" class="wp-image-' . $img_id . ' aligncenter size-large" />' . "\n";
                $images_html .= '</figure>' . "\n\n";
            }
        }
        
        if (!empty($images_html)) {
            // Agregar imágenes al contenido
            $new_content = trim($post->post_content);
            $new_content .= "\n\n" . trim($images_html);
            
            // Actualizar el post
            $result = wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $new_content,
            ));
            
            if (is_wp_error($result)) {
                echo "ERROR en post ID $post_id: " . $result->get_error_message() . "\n";
                $errors++;
            } else {
                echo "✓ Actualizado post ID $post_id: " . count($gallery_ids) . " imágenes embebidas\n";
                $updated++;
            }
        }
    }
    
    echo "\n";
    echo "========================================\n";
    echo "Migración completada:\n";
    echo "- Actualizados: $updated\n";
    echo "- Omitidos (ya tenían imágenes): $skipped\n";
    echo "- Errores: $errors\n";
    echo "========================================\n";
}

// Ejecutar la migración
if (php_sapi_name() === 'cli' || (defined('WP_CLI') && WP_CLI)) {
    // Ejecutar desde WP-CLI
    agrochamba_migrate_images_to_content();
} elseif (is_admin() && isset($_GET['migrate_images']) && current_user_can('manage_options')) {
    // Ejecutar desde el admin (temporal, para testing)
    agrochamba_migrate_images_to_content();
    echo "<br><br><a href='" . admin_url() . "'>Volver al admin</a>";
}

