<?php
/**
 * ImageOptimizer
 *
 * Servicio de optimización y tamaños de imagen para AgroChamba.
 */

namespace AgroChamba\Media;

if (!defined('ABSPATH')) {
    exit;
}

class ImageOptimizer
{
    /**
     * Inicializa hooks necesarios.
     */
    public static function init(): void
    {
        add_action('after_setup_theme', [self::class, 'register_sizes']);
        add_filter('image_size_names_choose', [self::class, 'add_sizes_to_media']);
        
        // Compresión segura: usar filtro que se ejecuta DESPUÉS de que WordPress termine de procesar
        // IMPORTANTE: wp_update_attachment_metadata es un FILTRO, no una acción
        add_filter('wp_update_attachment_metadata', [self::class, 'compress_after_metadata_saved'], 20, 2);
    }

    /**
     * Registrar tamaños personalizados.
     */
    public static function register_sizes(): void
    {
        add_image_size('agrochamba_card', (int) (defined('AGROCHAMBA_IMAGE_CARD_WIDTH') ? AGROCHAMBA_IMAGE_CARD_WIDTH : 400), (int) (defined('AGROCHAMBA_IMAGE_CARD_HEIGHT') ? AGROCHAMBA_IMAGE_CARD_HEIGHT : 300), true);
        add_image_size('agrochamba_detail', (int) (defined('AGROCHAMBA_IMAGE_DETAIL_WIDTH') ? AGROCHAMBA_IMAGE_DETAIL_WIDTH : 800), (int) (defined('AGROCHAMBA_IMAGE_DETAIL_HEIGHT') ? AGROCHAMBA_IMAGE_DETAIL_HEIGHT : 600), true);
        add_image_size('agrochamba_thumb', (int) (defined('AGROCHAMBA_IMAGE_THUMB_WIDTH') ? AGROCHAMBA_IMAGE_THUMB_WIDTH : 150), (int) (defined('AGROCHAMBA_IMAGE_THUMB_HEIGHT') ? AGROCHAMBA_IMAGE_THUMB_HEIGHT : 150), true);
        add_image_size('agrochamba_profile', 300, 300, true);
    }

    /**
     * Mostrar tamaños personalizados en el selector de medios.
     */
    public static function add_sizes_to_media(array $sizes): array
    {
        return array_merge($sizes, [
            'agrochamba_card' => __('Card AgroChamba (400x300)', 'agrochamba'),
            'agrochamba_detail' => __('Detalle AgroChamba (800x600)', 'agrochamba'),
            'agrochamba_thumb' => __('Miniatura AgroChamba (150x150)', 'agrochamba'),
            'agrochamba_profile' => __('Perfil AgroChamba (300x300)', 'agrochamba'),
        ]);
    }

    /**
     * Comprimir imágenes DESPUÉS de que WordPress haya terminado de generar metadata.
     * Este filtro se ejecuta cuando se guarda la metadata, evitando bucles infinitos.
     * 
     * IMPORTANTE: Este es un FILTRO, no una acción, por lo que DEBE retornar $metadata.
     * Si retorna null o void, rompe la cadena de filtros y los metadatos no se guardan.
     * 
     * @param array $metadata Metadata del attachment
     * @param int $attachment_id ID del attachment
     * @return array Metadata del attachment (debe retornarse siempre)
     */
    public static function compress_after_metadata_saved($metadata, $attachment_id): array
    {
        // Validar que $metadata sea un array válido
        if (!is_array($metadata)) {
            return $metadata;
        }

        // Solo procesar imágenes
        if (!wp_attachment_is_image($attachment_id)) {
            return $metadata;
        }

        // Evitar procesar múltiples veces en la misma petición
        static $processed = [];
        if (isset($processed[$attachment_id])) {
            return $metadata;
        }
        $processed[$attachment_id] = true;

        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return $metadata;
        }

        $mime_type = get_post_mime_type($attachment_id);
        if (!in_array($mime_type, ['image/jpeg', 'image/png'], true)) {
            return $metadata;
        }

        // Calidad de compresión (configurable, por defecto 85)
        $quality = (int) apply_filters('agrochamba_image_compression_quality', 85);
        
        // Comprimir el archivo original
        $image_editor = wp_get_image_editor($file_path);
        if (!is_wp_error($image_editor)) {
            $image_editor->set_quality($quality);
            $saved = $image_editor->save($file_path);
            
            // Si se guardó correctamente, también comprimir los thumbnails generados
            if (!is_wp_error($saved) && !empty($metadata['sizes'])) {
                $upload_dir = wp_upload_dir();
                $base_dir = dirname($file_path);
                
                foreach ($metadata['sizes'] as $size_name => $size_data) {
                    if (empty($size_data['file'])) {
                        continue;
                    }
                    
                    $thumbnail_path = $base_dir . '/' . $size_data['file'];
                    
                    if (file_exists($thumbnail_path)) {
                        $thumb_editor = wp_get_image_editor($thumbnail_path);
                        if (!is_wp_error($thumb_editor)) {
                            $thumb_editor->set_quality($quality);
                            $thumb_editor->save($thumbnail_path);
                        }
                    }
                }
            }
        }

        // SIEMPRE retornar $metadata para mantener la cadena de filtros
        return $metadata;
    }

    /**
     * Regenerar thumbnails para imágenes existentes.
     * NOTA: Esta función solo debe usarse manualmente o mediante WP-CLI,
     * NO automáticamente al subir imágenes para evitar bucles infinitos.
     * 
     * @param int $attachment_id ID del attachment
     */
    public static function regenerate_thumbnails_for_existing(int $attachment_id): void
    {
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }
        
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return;
        }
        
        // Regenerar metadata (esto generará los nuevos tamaños personalizados)
        $metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
        if (!is_wp_error($metadata) && !empty($metadata)) {
            wp_update_attachment_metadata($attachment_id, $metadata);
            // La compresión se aplicará automáticamente mediante compress_after_metadata_saved
        }
    }
}
