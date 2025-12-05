<?php
/**
 * =============================================================
 * MÓDULO 11: OPTIMIZACIÓN DE IMÁGENES
 * =============================================================
 * 
 * Optimizaciones para mejorar el rendimiento de carga de imágenes:
 * - Tamaños de imagen personalizados para la app móvil
 * - Compresión automática de imágenes
 * - Generación de thumbnails optimizados
 */

if (!defined('ABSPATH')) {
    exit;
}

// Shim de compatibilidad: delegar a clase namespaced si está disponible
if (!defined('AGROCHAMBA_IMAGE_OPTIMIZER_NAMESPACE_INITIALIZED')) {
    define('AGROCHAMBA_IMAGE_OPTIMIZER_NAMESPACE_INITIALIZED', true);
    if (class_exists('AgroChamba\\Media\\ImageOptimizer')) {
        // Registrar en log para trazar la migración (opcional)
        if (function_exists('error_log')) {
            error_log('AgroChamba: Cargando optimización de imágenes mediante AgroChamba\\Media\\ImageOptimizer (migración namespaces).');
        }
        \AgroChamba\Media\ImageOptimizer::init();
        // Evitar definir nuevamente hooks procedurales de este módulo
        return;
    } else {
        if (function_exists('error_log')) {
            error_log('AgroChamba: No se encontró AgroChamba\\Media\\ImageOptimizer. Usando implementación procedural legacy.');
        }
    }
}

// ==========================================
// 1. REGISTRAR TAMAÑOS DE IMAGEN PERSONALIZADOS
// ==========================================
if (!function_exists('agrochamba_register_custom_image_sizes')) {
    function agrochamba_register_custom_image_sizes() {
        // Tamaño para cards de trabajos (lista)
        // 400x300px manteniendo proporción
        add_image_size('agrochamba_card', 400, 300, true);
        
        // Tamaño para detalle de trabajo (slider)
        // 800x600px manteniendo proporción
        add_image_size('agrochamba_detail', 800, 600, true);
        
        // Tamaño para miniaturas (thumbnails pequeños)
        // 150x150px cuadradas
        add_image_size('agrochamba_thumb', 150, 150, true);
        
        // Tamaño para perfil de empresa
        // 300x300px cuadradas
        add_image_size('agrochamba_profile', 300, 300, true);
    }
    add_action('after_setup_theme', 'agrochamba_register_custom_image_sizes');
}

// ==========================================
// 2. AÑADIR TAMAÑOS PERSONALIZADOS AL SELECTOR DE MEDIOS
// ==========================================
if (!function_exists('agrochamba_add_custom_image_sizes_to_media')) {
    function agrochamba_add_custom_image_sizes_to_media($sizes) {
        return array_merge($sizes, array(
            'agrochamba_card' => __('Card AgroChamba (400x300)', 'agrochamba'),
            'agrochamba_detail' => __('Detalle AgroChamba (800x600)', 'agrochamba'),
            'agrochamba_thumb' => __('Miniatura AgroChamba (150x150)', 'agrochamba'),
            'agrochamba_profile' => __('Perfil AgroChamba (300x300)', 'agrochamba'),
        ));
    }
    add_filter('image_size_names_choose', 'agrochamba_add_custom_image_sizes_to_media');
}

// ==========================================
// 3. COMPRESIÓN AUTOMÁTICA DE IMÁGENES AL SUBIR
// ==========================================
// IMPORTANTE: Esta función está desactivada porque causaba bucles infinitos
// La compresión ahora se maneja en la clase ImageOptimizer con mejor manejo de hooks
// Si necesitas compresión, usa el filtro 'agrochamba_image_compression_quality'
/*
if (!function_exists('agrochamba_compress_image_on_upload')) {
    function agrochamba_compress_image_on_upload($metadata, $attachment_id, $context) {
        // Solo procesar si es una imagen y es una nueva subida
        if ($context !== 'create' || !wp_attachment_is_image($attachment_id)) {
            return $metadata;
        }
        
        // Obtener la ruta del archivo
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return $metadata;
        }
        
        // Solo comprimir JPEG y PNG
        $mime_type = get_post_mime_type($attachment_id);
        if (!in_array($mime_type, array('image/jpeg', 'image/png'))) {
            return $metadata;
        }
        
        // Calidad de compresión (0-100, 85 es un buen balance)
        $quality = apply_filters('agrochamba_image_compression_quality', 85);
        
        // Intentar comprimir usando WordPress Image Editor
        $image_editor = wp_get_image_editor($file_path);
        if (!is_wp_error($image_editor)) {
            // Guardar con compresión
            $image_editor->set_quality($quality);
            $saved = $image_editor->save($file_path);
            
            // NO regenerar metadata aquí para evitar bucles infinitos
            // WordPress ya generó los thumbnails, solo comprimimos el archivo original
        }
        
        return $metadata;
    }
    add_filter('wp_generate_attachment_metadata', 'agrochamba_compress_image_on_upload', 10, 3);
}
*/

// ==========================================
// 4. FORZAR REGENERACIÓN DE THUMBNAILS PARA TAMAÑOS PERSONALIZADOS
// ==========================================
if (!function_exists('agrochamba_regenerate_thumbnails_for_existing_images')) {
    function agrochamba_regenerate_thumbnails_for_existing_images($attachment_id) {
        // Solo para imágenes
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }
        
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return;
        }
        
        // Regenerar metadata para incluir los nuevos tamaños
        $metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $metadata);
    }
    
    // Hook para regenerar cuando se actualiza un attachment
    add_action('edit_attachment', 'agrochamba_regenerate_thumbnails_for_existing_images');
}

// ==========================================
// 5. AÑADIR TAMAÑOS PERSONALIZADOS A LAS RESPUESTAS DE LA API REST
// ==========================================
if (!function_exists('agrochamba_add_custom_sizes_to_rest_response')) {
    function agrochamba_add_custom_sizes_to_rest_response($response, $post, $request) {
        // Solo para attachments de tipo imagen
        if ($post->post_type !== 'attachment' || !wp_attachment_is_image($post->ID)) {
            return $response;
        }
        
        $data = $response->get_data();
        
        // Agregar URLs de tamaños personalizados
        if (!isset($data['media_details']['sizes'])) {
            $data['media_details']['sizes'] = array();
        }
        
        $custom_sizes = array('agrochamba_card', 'agrochamba_detail', 'agrochamba_thumb', 'agrochamba_profile');
        
        foreach ($custom_sizes as $size) {
            $image_url = wp_get_attachment_image_url($post->ID, $size);
            if ($image_url) {
                $image_meta = wp_get_attachment_image_src($post->ID, $size);
                $data['media_details']['sizes'][$size] = array(
                    'file' => basename($image_url),
                    'width' => $image_meta[1],
                    'height' => $image_meta[2],
                    'mime-type' => get_post_mime_type($post->ID),
                    'source_url' => $image_url
                );
            }
        }
        
        $response->set_data($data);
        return $response;
    }
    add_filter('rest_prepare_attachment', 'agrochamba_add_custom_sizes_to_rest_response', 10, 3);
}

// ==========================================
// 6. FUNCIÓN HELPER PARA OBTENER URL DE IMAGEN OPTIMIZADA
// ==========================================
if (!function_exists('agrochamba_get_optimized_image_url')) {
    /**
     * Obtener URL de imagen optimizada según el contexto
     * 
     * @param int $attachment_id ID del attachment
     * @param string $context Contexto: 'card', 'detail', 'thumb', 'profile', 'full'
     * @return string URL de la imagen
     */
    function agrochamba_get_optimized_image_url($attachment_id, $context = 'card') {
        $size_map = array(
            'card' => 'agrochamba_card',
            'detail' => 'agrochamba_detail',
            'thumb' => 'agrochamba_thumb',
            'profile' => 'agrochamba_profile',
            'full' => 'full'
        );
        
        $size = isset($size_map[$context]) ? $size_map[$context] : 'agrochamba_card';
        
        $url = wp_get_attachment_image_url($attachment_id, $size);
        
        // Si no existe el tamaño personalizado, usar el tamaño más cercano
        if (!$url) {
            switch ($context) {
                case 'card':
                    $url = wp_get_attachment_image_url($attachment_id, 'medium');
                    break;
                case 'detail':
                    $url = wp_get_attachment_image_url($attachment_id, 'large');
                    break;
                case 'thumb':
                case 'profile':
                    $url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
                    break;
                default:
                    $url = wp_get_attachment_image_url($attachment_id, 'full');
            }
        }
        
        return $url ?: wp_get_attachment_image_url($attachment_id, 'full');
    }
}

