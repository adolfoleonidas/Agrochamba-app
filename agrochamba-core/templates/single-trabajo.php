<?php
/**
 * Template para mostrar el detalle de un trabajo/empleo
 * Estilo Computrabajo con información de empresa embebida
 */

if (!defined('ABSPATH')) {
    exit;
}

// Filtrar wp_kses para permitir elementos HTML básicos
add_filter('wp_kses_allowed_html', function($allowed, $context) {
    if ($context === 'post' || $context === 'agrochamba_content') {
        // Permitir span con atributos data
        if (!isset($allowed['span'])) {
            $allowed['span'] = array();
        }
        $allowed['span']['class'] = true;
        $allowed['span']['data-phone'] = true;
        $allowed['span']['data-phone-display'] = true;
        $allowed['span']['id'] = true;
        
        // Permitir button con onclick
        $allowed['button'] = array(
            'type' => true,
            'class' => true,
            'onclick' => true,
            'title' => true,
        );
        
        // Permitir SVG completo
        $allowed['svg'] = array(
            'width' => true,
            'height' => true,
            'viewbox' => true,
            'viewBox' => true,
            'fill' => true,
            'xmlns' => true,
            'class' => true,
        );
        
        $allowed['path'] = array(
            'd' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
        );
    }
    return $allowed;
}, 10, 2);

get_header();

$trabajo_id = get_the_ID();
$trabajo = get_post($trabajo_id);

if (!$trabajo || $trabajo->post_type !== 'trabajo') {
    get_template_part('404');
    get_footer();
    return;
}

// Obtener datos del trabajo
$salario_min = get_post_meta($trabajo_id, 'salario_min', true);
$salario_max = get_post_meta($trabajo_id, 'salario_max', true);
$vacantes = get_post_meta($trabajo_id, 'vacantes', true);
$tipo_contrato = get_post_meta($trabajo_id, 'tipo_contrato', true);
$jornada = get_post_meta($trabajo_id, 'jornada', true);
$requisitos = get_post_meta($trabajo_id, 'requisitos', true);
$beneficios = get_post_meta($trabajo_id, 'beneficios', true);
$fecha_inicio = get_post_meta($trabajo_id, 'fecha_inicio', true);
$fecha_fin = get_post_meta($trabajo_id, 'fecha_fin', true);
$contacto_whatsapp = get_post_meta($trabajo_id, 'contacto_whatsapp', true);
$contacto_email = get_post_meta($trabajo_id, 'contacto_email', true);
$google_maps_url = get_post_meta($trabajo_id, 'google_maps_url', true);
$alojamiento = get_post_meta($trabajo_id, 'alojamiento', true);
$transporte = get_post_meta($trabajo_id, 'transporte', true);
$alimentacion = get_post_meta($trabajo_id, 'alimentacion', true);
$experiencia = get_post_meta($trabajo_id, 'experiencia', true);
$estado = get_post_meta($trabajo_id, 'estado', true) ?: 'activa';

// Obtener ubicación
$ubicaciones = wp_get_post_terms($trabajo_id, 'ubicacion');
$ubicacion = !empty($ubicaciones) ? $ubicaciones[0] : null;

// Obtener empresa (CPT)
$empresa_id = get_post_meta($trabajo_id, 'empresa_id', true);
$empresa_data = null;

// Si no hay empresa_id en meta, intentar obtener desde taxonomía empresa
if (!$empresa_id) {
    $empresa_terms = wp_get_post_terms($trabajo_id, 'empresa');
    if (!empty($empresa_terms) && !is_wp_error($empresa_terms)) {
        $empresa_term = $empresa_terms[0];
        // Buscar CPT Empresa por nombre
        $empresa_posts = get_posts(array(
            'post_type' => 'empresa',
            'name' => $empresa_term->slug,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
        if (!empty($empresa_posts)) {
            $empresa_id = $empresa_posts[0]->ID;
        }
    }
}

if ($empresa_id) {
    $empresa_data = agrochamba_get_empresa_data($empresa_id);
}

// Si aún no hay empresa_data pero hay taxonomía empresa, crear datos básicos desde la taxonomía
if (!$empresa_data && !empty($empresa_terms) && !is_wp_error($empresa_terms)) {
    $empresa_term = $empresa_terms[0];
    $empresa_data = array(
        'nombre_comercial' => $empresa_term->name,
        'url' => get_term_link($empresa_term),
        'logo_url' => null,
        'verificada' => false,
    );
}

// Obtener todas las imágenes para el slider
$all_images = array();

// 1. Imagen destacada (primera prioridad)
$featured_image_id = get_post_thumbnail_id($trabajo_id);
if ($featured_image_id) {
    $featured_image_url = wp_get_attachment_image_url($featured_image_id, 'full');
    $featured_image_thumb = wp_get_attachment_image_url($featured_image_id, 'large');
    if ($featured_image_url) {
        $all_images[] = array(
            'id' => $featured_image_id,
            'url' => $featured_image_url,
            'thumb' => $featured_image_thumb
        );
    }
}

// 2. Imágenes de la galería (gallery_ids)
$gallery_ids = get_post_meta($trabajo_id, 'gallery_ids', true);
if (!empty($gallery_ids) && is_array($gallery_ids)) {
    foreach ($gallery_ids as $gallery_id) {
        $gallery_id = intval($gallery_id);
        // Evitar duplicar la imagen destacada
        if ($gallery_id != $featured_image_id) {
            $gallery_url = wp_get_attachment_image_url($gallery_id, 'full');
            $gallery_thumb = wp_get_attachment_image_url($gallery_id, 'large');
            if ($gallery_url) {
                $all_images[] = array(
                    'id' => $gallery_id,
                    'url' => $gallery_url,
                    'thumb' => $gallery_thumb
                );
            }
        }
    }
}

// Si no hay imágenes, usar la imagen destacada como fallback
if (empty($all_images) && $featured_image_id) {
    $featured_image_url = wp_get_attachment_image_url($featured_image_id, 'full');
    $featured_image_thumb = wp_get_attachment_image_url($featured_image_id, 'large');
    if ($featured_image_url) {
        $all_images[] = array(
            'id' => $featured_image_id,
            'url' => $featured_image_url,
            'thumb' => $featured_image_thumb
        );
    }
}

// Obtener IDs de todas las imágenes que están en el slider
$slider_image_ids = array();
foreach ($all_images as $image) {
    if (isset($image['id'])) {
        $slider_image_ids[] = $image['id'];
    }
}

// Función para filtrar imágenes del contenido que ya están en el slider
function agrochamba_remove_slider_images_from_content($content, $image_ids_to_remove) {
    if (empty($image_ids_to_remove) || empty($content)) {
        return $content;
    }
    
    // Crear un patrón regex para encontrar todas las imágenes con esos IDs
    foreach ($image_ids_to_remove as $image_id) {
        // Patrón para encontrar <img> tags con el attachment_id en la URL o en atributos
        // Buscar por ID en la URL de la imagen (wp-image-{id})
        $pattern = '/<img[^>]*class="[^"]*wp-image-' . preg_quote($image_id, '/') . '[^"]*"[^>]*>.*?<\/img>|<img[^>]*class="[^"]*wp-image-' . preg_quote($image_id, '/') . '[^"]*"[^>]*\/>/i';
        $content = preg_replace($pattern, '', $content);
        
        // También buscar por URL de la imagen (puede tener diferentes tamaños)
        $image_url = wp_get_attachment_url($image_id);
        if ($image_url) {
            // Extraer el nombre del archivo sin extensión
            $image_filename = basename($image_url);
            $image_filename_no_ext = preg_replace('/\.[^.]+$/', '', $image_filename);
            
            // Buscar imágenes con este nombre de archivo
            $pattern = '/<img[^>]*src="[^"]*' . preg_quote($image_filename_no_ext, '/') . '[^"]*"[^>]*>.*?<\/img>|<img[^>]*src="[^"]*' . preg_quote($image_filename_no_ext, '/') . '[^"]*"[^>]*\/>/i';
            $content = preg_replace($pattern, '', $content);
        }
        
        // Buscar por data-id o attachment_id
        $pattern = '/<img[^>]*(data-id|attachment-id)="' . preg_quote($image_id, '/') . '"[^>]*>.*?<\/img>|<img[^>]*(data-id|attachment-id)="' . preg_quote($image_id, '/') . '"[^>]*\/>/i';
        $content = preg_replace($pattern, '', $content);
    }
    
    // Limpiar párrafos, divs y figures vacíos que puedan quedar
    $content = preg_replace('/<p[^>]*>\s*<\/p>/i', '', $content);
    $content = preg_replace('/<div[^>]*>\s*<\/div>/i', '', $content);
    $content = preg_replace('/<figure[^>]*>\s*<\/figure>/i', '', $content);
    
    return $content;
}

// Filtrar el contenido para remover imágenes del slider
$filtered_content = $trabajo->post_content;
if (!empty($slider_image_ids)) {
    $filtered_content = agrochamba_remove_slider_images_from_content($filtered_content, $slider_image_ids);
}

// Asegurarse de que el contenido no esté doblemente escapado
// WordPress puede escapar el contenido, así que lo decodificamos
$filtered_content = wp_specialchars_decode($filtered_content, ENT_QUOTES);
$filtered_content = html_entity_decode($filtered_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

// Función helper para obtener permisos HTML personalizados que permiten botones de teléfono
function agrochamba_get_allowed_html_for_content() {
    $allowed_html = wp_kses_allowed_html('post');
    
    // Permitir atributos adicionales en span
    $allowed_html['span'] = array_merge(
        isset($allowed_html['span']) ? $allowed_html['span'] : array(),
        array(
            'class' => true,
            'data-phone' => true,
            'data-phone-display' => true,
            'id' => true,
        )
    );
    
    // Permitir atributos adicionales en enlaces
    $allowed_html['a'] = array_merge(
        isset($allowed_html['a']) ? $allowed_html['a'] : array(),
        array(
            'class' => true,
            'href' => true,
            'target' => true,
            'rel' => true,
            'title' => true,
        )
    );
    
    // Permitir botones con onclick
    $allowed_html['button'] = array(
        'type' => true,
        'class' => true,
        'onclick' => true,
        'title' => true,
    );
    
    // Permitir SVG e iconos
    $allowed_html['svg'] = array(
        'width' => true,
        'height' => true,
        'viewbox' => true,
        'viewBox' => true,
        'fill' => true,
        'xmlns' => true,
        'class' => true,
    );
    
    $allowed_html['path'] = array(
        'd' => true,
        'fill' => true,
        'stroke' => true,
        'stroke-width' => true,
        'stroke-linecap' => true,
        'stroke-linejoin' => true,
    );
    
    return $allowed_html;
}

// Función para hacer clicables correos, teléfonos y URLs en el contenido
function agrochamba_make_content_clickable($content) {
    if (empty($content)) {
        return $content;
    }
    
    // Primero, proteger los enlaces HTML existentes usando marcadores temporales
    $link_placeholders = array();
    $placeholder_index = 0;
    
    // Reemplazar enlaces existentes con placeholders
    $content = preg_replace_callback('/<a\s[^>]*>.*?<\/a>/is', function($matches) use (&$link_placeholders, &$placeholder_index) {
        $placeholder = '___LINK_PLACEHOLDER_' . $placeholder_index . '___';
        $link_placeholders[$placeholder] = $matches[0];
        $placeholder_index++;
        return $placeholder;
    }, $content);
    
    // Patrón para detectar teléfonos (peruano: 9 dígitos, puede empezar con +51 o 0)
    // Formato: +51 999 999 999, 999 999 999, (01) 999-9999, etc.
    // Evitar números dentro de placeholders (enlaces existentes)
    $phone_pattern = '/(?<![\d+])(?<!___LINK_PLACEHOLDER_)(\+?51\s?)?(0?\d{1,2}[\s\-]?)?(\d{3}[\s\-]?\d{3}[\s\-]?\d{3,4})(?![\d])/';
    $content = preg_replace_callback($phone_pattern, function($matches) {
        $full_phone = $matches[0];
        $phone_clean = preg_replace('/[^0-9]/', '', $full_phone);
        // Crear enlace simple para teléfono
        $phone_display = esc_html($full_phone);
        return '<a href="tel:' . esc_attr($phone_clean) . '" class="clickable-phone">' . $phone_display . '</a>';
    }, $content);
    
    // Patrón para detectar correos electrónicos (evitar los que ya están en enlaces)
    $email_pattern = '/(?<!href=["\']mailto:)(?<!>)([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})(?![^<]*<\/a>)/';
    $content = preg_replace($email_pattern, '<a href="mailto:$1" class="clickable-email">$1</a>', $content);
    
    // Patrón para detectar URLs (http/https/www) - evitar las que ya están en enlaces
    $url_pattern = '/(?<!href=["\'])(?<!src=["\'])(?<!>)(https?:\/\/[^\s<>"\'{}|\\^`\[\]]+|[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]?\.[a-zA-Z]{2,}[^\s<>"\'{}|\\^`\[\]]*)(?![^<]*<\/a>)/i';
    $content = preg_replace_callback($url_pattern, function($matches) {
        $url = $matches[0];
        // Si no tiene protocolo, agregar https://
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }
        return '<a href="' . esc_url($url) . '" class="clickable-url" target="_blank" rel="noopener">' . esc_html($matches[0]) . '</a>';
    }, $content);
    
    // Restaurar los enlaces originales
    foreach ($link_placeholders as $placeholder => $original_link) {
        $content = str_replace($placeholder, $original_link, $content);
    }
    
    return $content;
}

?>
<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<div class="trabajo-detalle-wrapper">
    <!-- Slider de Imágenes -->
    <?php if (!empty($all_images)): ?>
        <div class="trabajo-image-slider-wrapper">
            <div class="swiper trabajo-image-slider">
                <div class="swiper-wrapper">
                    <?php foreach ($all_images as $index => $image): ?>
                        <div class="swiper-slide">
                            <img src="<?php echo esc_url($image['thumb']); ?>" 
                                 data-full="<?php echo esc_url($image['url']); ?>"
                                 alt="<?php echo esc_attr($trabajo->post_title . ' - Imagen ' . ($index + 1)); ?>"
                                 class="slider-image"
                                 onclick="openFullscreenSlider(<?php echo $index; ?>)">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($all_images) > 1): ?>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Placeholder si no hay imágenes -->
        <div class="trabajo-image-placeholder">
            <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
            </svg>
        </div>
    <?php endif; ?>

    <!-- Header del Trabajo -->
    <div class="trabajo-header-section">
        <div class="trabajo-header-content">
            <div class="trabajo-header-left">
                <h1 class="trabajo-titulo">
                    <?php echo esc_html($trabajo->post_title); ?>
                </h1>
                
                <!-- Nombre de la empresa y ubicación (similar a la app) -->
                <?php if ($empresa_data || $ubicacion): ?>
                    <div class="empresa-meta-header">
                        <?php if ($empresa_data): ?>
                            <a href="<?php echo esc_url($empresa_data['url']); ?>" class="empresa-nombre-link-destacado">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/>
                                </svg>
                                <span><?php echo esc_html($empresa_data['nombre_comercial']); ?></span>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($ubicacion): ?>
                            <?php if ($empresa_data): ?>
                                <span class="meta-separator">•</span>
                            <?php endif; ?>
                            <div class="trabajo-location-header">
                                <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 0a5 5 0 0 0-5 5c0 4.5 5 10 5 10s5-5.5 5-10a5 5 0 0 0-5-5zm0 7a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/>
                                </svg>
                                <span><?php echo esc_html($ubicacion->name); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($empresa_data): ?>
                    <div class="empresa-info-header">
                        <div class="empresa-rating-header">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#FFB800">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            <span class="rating-value">4.2</span>
                            <span class="evaluaciones-count">0 evaluaciones</span>
                        </div>
                        
                        <?php if ($empresa_data['verificada']): ?>
                            <div class="empresa-verificada-badge-header">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.99-4.01z"/>
                                </svg>
                                Empresa verificada
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="trabajo-actions-section">
        <div class="trabajo-actions-content">
            <button class="btn-postularme" onclick="postularme()">
                Postularme
            </button>
            <div class="trabajo-action-icons">
                <button class="action-icon" title="Guardar" onclick="toggleFavorito()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                    </svg>
                </button>
                <button class="action-icon" title="Compartir" onclick="compartir()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M15 14l4 -4l-4 -4" />
                        <path d="M19 10h-11a4 4 0 1 0 0 8h1" />
                    </svg>
                </button>
                <button class="action-icon" title="Ocultar" onclick="ocultar()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
                <button class="action-icon" title="Más opciones" onclick="masOpciones()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="12" cy="5" r="2"/>
                        <circle cx="12" cy="12" r="2"/>
                        <circle cx="12" cy="19" r="2"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Detalles del Trabajo -->
    <div class="trabajo-details-section">
        <div class="trabajo-details-content">
            <div class="trabajo-details-grid">
                <?php if ($tipo_contrato): ?>
                    <div class="detail-item">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M4 4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4zm2 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H6zm5 0a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-1z"/>
                        </svg>
                        <span><?php echo esc_html($tipo_contrato); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($jornada): ?>
                    <div class="detail-item">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16zm1-12a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l2.828 2.829a1 1 0 1 0 1.415-1.415L11 9.586V6z"/>
                        </svg>
                        <span><?php echo esc_html($jornada); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="detail-item">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.394 2.08a1 1 0 0 0-.788 0l-7 3a1 1 0 0 0 0 1.84L5.25 8.051a.999.999 0 0 1 .356-.257l4-1.714a1 1 0 1 1 .788 1.838L7.667 9.088l1.94.831a1 1 0 0 1 .557 1.04l1.185 4.838a1 1 0 0 0 1.928-.518l-1.185-4.838a1 1 0 0 1 .557-1.04l5.554-2.38a1 1 0 1 1 .788 1.838l-7.653 3.282a1 1 0 0 1-1.84 0l-7-3a1 1 0 1 1 .788-1.838l7.653 3.282a1 1 0 0 1 .356-.257l4-1.714a1 1 0 1 1 .788 1.838l-4 1.714a1 1 0 0 1-.356.257l-7.653 3.281a1 1 0 1 1-.788-1.838l7-3z"/>
                    </svg>
                    <span>Presencial</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="trabajo-content-main">
        <div class="trabajo-content-left">
            <!-- Descripción del Trabajo -->
            <div class="trabajo-section">
                <h2 class="section-title">Descripción del trabajo</h2>
                <div class="trabajo-descripcion-text">
                    <?php 
                    // Hacer clicables los elementos (teléfonos, correos, URLs)
                    $clickable_content = agrochamba_make_content_clickable($filtered_content);
                    // Aplicar wpautop y wp_kses_post normalmente
                    echo wp_kses_post(wpautop($clickable_content)); 
                    ?>
                </div>
            </div>

            <!-- Responsabilidades -->
            <?php if ($requisitos): ?>
                <div class="trabajo-section">
                    <h2 class="section-title">Responsabilidades</h2>
                    <div class="responsabilidades-list">
                        <?php 
                        $responsabilidades = explode("\n", $requisitos);
                        foreach ($responsabilidades as $responsabilidad):
                            $responsabilidad = trim($responsabilidad);
                            if (!empty($responsabilidad)):
                        ?>
                            <div class="responsabilidad-item">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.99-4.01z"/>
                                </svg>
                                <span><?php 
                                    $clickable_responsabilidad = agrochamba_make_content_clickable($responsabilidad);
                                    echo wp_kses_post($clickable_responsabilidad); 
                                ?></span>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Requisitos -->
            <?php if ($experiencia): ?>
                <div class="trabajo-section">
                    <h2 class="section-title">Requisitos</h2>
                    <div class="requisitos-list">
                        <div class="requisito-item">
                            <strong>Experiencia:</strong> <?php 
                                $clickable_experiencia = agrochamba_make_content_clickable($experiencia);
                                echo wp_kses_post($clickable_experiencia); 
                            ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Beneficios -->
            <?php if ($beneficios || $alojamiento || $transporte || $alimentacion): ?>
                <div class="trabajo-section">
                    <h2 class="section-title">Beneficios</h2>
                    <div class="beneficios-list">
                        <?php if ($alojamiento): ?>
                            <div class="beneficio-item">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 0 0-1.414 0l-7 7a1 1 0 0 0 1.414 1.414L4 10.414V17a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2a1 1 0 0 1 1-1h2a1 1 0 0 1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-6.586l.293.293a1 1 0 0 0 1.414-1.414l-7-7z"/>
                                </svg>
                                <span>Alojamiento incluido</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($transporte): ?>
                            <div class="beneficio-item">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M8 16.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM15 16.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/>
                                    <path d="M3 4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h1.05a2.5 2.5 0 0 1 4.9 0H10a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1H3zM3 5h7v10H3V5zm11 1.5V14a1 1 0 0 0 1 1h1.05a2.5 2.5 0 0 1 4.9 0H20v-7.5h-6z"/>
                                </svg>
                                <span>Transporte incluido</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($alimentacion): ?>
                            <div class="beneficio-item">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M3 1a1 1 0 0 0 0 2h1.22l.305 1.222a.997.997 0 0 0 .01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 0 0 0-2H6.414l1-1H14a1 1 0 0 0 .894-.553l3-6A1 1 0 0 0 17 3H6.28l-.31-1.243A1 1 0 0 0 5 1H3zM16 16.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zM6.5 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/>
                                </svg>
                                <span>Alimentación incluida</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($beneficios): ?>
                            <div class="beneficio-texto">
                                <?php 
                                $clickable_beneficios = agrochamba_make_content_clickable($beneficios);
                                echo wp_kses_post(wpautop($clickable_beneficios)); 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Información de Contacto -->
            <?php if ($contacto_whatsapp || $contacto_email): ?>
                <div class="trabajo-section">
                    <h2 class="section-title">Información de contacto</h2>
                    <div class="contacto-info">
                        <?php if ($contacto_whatsapp): ?>
                            <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $contacto_whatsapp)); ?>" 
                               class="contacto-item" target="_blank">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2.05 2.05a2 2 0 0 1 2.9-.01L6 3.5l-.95.95a7.5 7.5 0 0 0 0 10.6l.95.95L4.95 18a2 2 0 0 1-2.9-2.9l.95-.95a7.5 7.5 0 0 0 0-10.6l-.95-.95zm11.9 0a2 2 0 0 1 2.9 2.9l-.95.95a7.5 7.5 0 0 0 0 10.6l.95.95a2 2 0 0 1-2.9 2.9L14 16.5l.95-.95a7.5 7.5 0 0 0 0-10.6L14 4.05l-.05-.95z"/>
                                </svg>
                                WhatsApp: <?php echo esc_html($contacto_whatsapp); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($contacto_email): ?>
                            <a href="mailto:<?php echo esc_attr($contacto_email); ?>" class="contacto-item">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0 0 16 4H4a2 2 0 0 0-1.997 1.884z"/>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8.118z"/>
                                </svg>
                                Email: <?php echo esc_html($contacto_email); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Derecha -->
        <div class="trabajo-content-right">
            <!-- Información de la Empresa -->
            <?php if ($empresa_data): ?>
                <div class="empresa-card-sidebar">
                    <h3 class="sidebar-title">
                        Acerca de <a href="<?php echo esc_url($empresa_data['url']); ?>" class="empresa-nombre-sidebar-link"><?php echo esc_html($empresa_data['nombre_comercial']); ?></a>
                    </h3>
                    
                    <div class="empresa-card-header">
                        <?php if ($empresa_data['logo_url']): ?>
                            <a href="<?php echo esc_url($empresa_data['url']); ?>" class="empresa-logo-sidebar-link">
                                <img src="<?php echo esc_url($empresa_data['logo_url']); ?>" 
                                     alt="<?php echo esc_attr($empresa_data['nombre_comercial']); ?>" 
                                     class="empresa-logo-sidebar" />
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url($empresa_data['url']); ?>" class="btn-seguir-sidebar">
                            + Seguir
                        </a>
                    </div>
                    
                    <?php 
                    $empresa_post = get_post($empresa_id);
                    if ($empresa_post && $empresa_post->post_content):
                    ?>
                        <div class="empresa-descripcion-sidebar">
                            <?php echo wp_kses_post(wp_trim_words($empresa_post->post_content, 50)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url($empresa_data['url']); ?>" class="ver-empresa-link">
                        Ver perfil completo →
                    </a>
                </div>
            <?php endif; ?>

            <!-- Información del Puesto -->
            <div class="trabajo-info-sidebar">
                <h3 class="sidebar-title">Información del puesto</h3>
                <div class="info-sidebar-list">
                    <?php if ($salario_min || $salario_max): ?>
                        <div class="info-sidebar-item">
                            <strong>Salario:</strong>
                            <span>
                                <?php 
                                if ($salario_min && $salario_max) {
                                    echo esc_html('S/ ' . $salario_min . ' - S/ ' . $salario_max);
                                } elseif ($salario_min) {
                                    echo esc_html('Desde S/ ' . $salario_min);
                                } elseif ($salario_max) {
                                    echo esc_html('Hasta S/ ' . $salario_max);
                                }
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($vacantes): ?>
                        <div class="info-sidebar-item">
                            <strong>Vacantes:</strong>
                            <span><?php echo esc_html($vacantes); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($fecha_inicio): ?>
                        <div class="info-sidebar-item">
                            <strong>Fecha de inicio:</strong>
                            <span><?php echo esc_html(date_i18n('d/m/Y', strtotime($fecha_inicio))); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($fecha_fin): ?>
                        <div class="info-sidebar-item">
                            <strong>Fecha de fin:</strong>
                            <span><?php echo esc_html(date_i18n('d/m/Y', strtotime($fecha_fin))); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.trabajo-detalle-wrapper {
    background: #f5f5f5;
    min-height: 100vh;
}

.trabajo-header-section {
    background: #fff;
    border-bottom: 1px solid #e0e0e0;
    padding: 30px 0;
}

.trabajo-header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.trabajo-header-left {
    flex: 1;
}

.trabajo-titulo {
    font-size: 32px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 15px 0;
    line-height: 1.2;
}

.trabajo-ubicacion-header {
    font-weight: 400;
    color: #666;
}

.empresa-info-header {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
}

.empresa-rating-header {
    display: flex;
    align-items: center;
    gap: 5px;
}

.rating-value {
    font-weight: 600;
    color: #1a1a1a;
}

.empresa-meta-header {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin: 16px 0 12px 0;
}

.empresa-nombre-link-destacado {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #0066cc;
    text-decoration: none;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.2s ease;
}

.meta-separator {
    color: #999;
    font-size: 18px;
    line-height: 1;
}

.trabajo-location-header {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #666;
    font-size: 16px;
}

.trabajo-location-header svg {
    flex-shrink: 0;
    color: #666;
}

.empresa-nombre-link-destacado:hover {
    color: #0052a3;
    text-decoration: underline;
}

.empresa-nombre-link-destacado svg {
    flex-shrink: 0;
    color: #0066cc;
}

.empresa-nombre-link-destacado:hover svg {
    color: #0052a3;
}

.empresa-nombre-link {
    color: #0066cc;
    text-decoration: none;
    font-weight: 500;
}

.empresa-nombre-link:hover {
    text-decoration: underline;
}

.evaluaciones-count {
    color: #666;
    font-size: 14px;
}

.trabajo-location-header {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
    font-size: 14px;
}

.empresa-verificada-badge-header {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #00a32a;
    font-size: 14px;
    font-weight: 500;
}


.trabajo-actions-section {
    background: #fff;
    border-bottom: 1px solid #e0e0e0;
    padding: 20px 0;
}

.trabajo-actions-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.btn-postularme {
    background: #0066cc;
    color: #fff;
    border: none;
    padding: 14px 40px;
    border-radius: 6px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-postularme:hover {
    background: #0052a3;
}

.trabajo-action-icons {
    display: flex;
    gap: 10px;
}

.action-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 1px solid #e0e0e0;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    color: #666;
}

.action-icon:hover {
    background: #f5f5f5;
    border-color: #ccc;
}

.trabajo-details-section {
    background: #fff;
    border-bottom: 1px solid #e0e0e0;
    padding: 20px 0;
}

.trabajo-details-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.trabajo-details-grid {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 14px;
}

.detail-item svg {
    color: #0066cc;
}

.trabajo-content-main {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.trabajo-content-left {
    min-width: 0;
}

.trabajo-section {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.section-title {
    font-size: 24px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 20px 0;
}

.trabajo-descripcion-text {
    line-height: 1.8;
    color: #333;
}

.responsabilidades-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.responsabilidad-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.responsabilidad-item svg {
    color: #00a32a;
    flex-shrink: 0;
    margin-top: 3px;
}

.beneficios-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.beneficio-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #333;
}

.beneficio-item svg {
    color: #0066cc;
}

.beneficio-texto {
    line-height: 1.8;
    color: #333;
}

/* Estilos para enlaces clicables en el contenido */
.trabajo-descripcion-text a,
.responsabilidad-item a,
.requisito-item a,
.beneficio-texto a {
    color: #0066cc;
    text-decoration: none;
    border-bottom: 1px solid transparent;
    transition: all 0.2s ease;
    cursor: pointer;
}

.trabajo-descripcion-text a:hover,
.responsabilidad-item a:hover,
.requisito-item a:hover,
.beneficio-texto a:hover {
    color: #0052a3;
    border-bottom-color: #0052a3;
    text-decoration: none;
}

/* Estilos para contenedor de teléfonos con botones */
/* Estilos para enlaces clicables de teléfonos */
.clickable-phone {
    color: #0066cc;
    text-decoration: none;
    font-weight: 500;
    border-bottom: 1px solid transparent;
    transition: all 0.2s ease;
}

.clickable-phone:hover {
    color: #0052a3;
    border-bottom-color: #0052a3;
    text-decoration: none;
}

/* Estilos específicos para correos */
.clickable-email {
    color: #0066cc !important;
}

.clickable-email:hover {
    color: #0052a3 !important;
    border-bottom-color: #0052a3 !important;
}

/* Estilos específicos para URLs */
.clickable-url {
    color: #0066cc !important;
    word-break: break-all;
}

.clickable-url:hover {
    color: #0052a3 !important;
    border-bottom-color: #0052a3 !important;
}

.contacto-info {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.contacto-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #0066cc;
    text-decoration: none;
    font-size: 16px;
}

.contacto-item:hover {
    text-decoration: underline;
}

.empresa-card-sidebar {
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.sidebar-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 20px 0;
}

.empresa-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.empresa-logo-sidebar-link {
    display: block;
    transition: transform 0.3s ease;
    text-decoration: none;
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
}

.empresa-logo-sidebar-link:hover {
    transform: scale(1.05);
}

.empresa-logo-sidebar {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
    display: block;
}

.empresa-nombre-sidebar-link {
    color: #0066cc;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.empresa-nombre-sidebar-link:hover {
    color: #0052a3;
    text-decoration: underline;
}

.btn-seguir-sidebar {
    padding: 8px 16px;
    border: 1px solid #0066cc;
    color: #0066cc;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-seguir-sidebar:hover {
    background: #0066cc;
    color: #fff;
}

.empresa-descripcion-sidebar {
    line-height: 1.6;
    color: #666;
    margin-bottom: 15px;
}

.ver-empresa-link {
    color: #0066cc;
    text-decoration: none;
    font-weight: 500;
}

.ver-empresa-link:hover {
    text-decoration: underline;
}

.trabajo-info-sidebar {
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.info-sidebar-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* ==========================================
   ESTILOS DE POPUP DE ENCUESTA
   ========================================== */
.poll-popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.poll-popup-overlay.show {
    display: flex;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.poll-popup-container {
    position: relative;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 20px;
    padding: 40px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.4s ease;
    text-align: center;
    border: 2px solid #e0e0e0;
}

.poll-popup-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(0, 0, 0, 0.05);
    border: 2px solid rgba(0, 0, 0, 0.1);
    cursor: pointer;
    padding: 8px;
    color: #666;
    transition: all 0.3s ease;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.poll-popup-close:hover {
    background: rgba(0, 0, 0, 0.1);
    border-color: rgba(0, 0, 0, 0.2);
    color: #333;
    transform: rotate(90deg) scale(1.1);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
}

.poll-popup-close:active {
    transform: rotate(90deg) scale(0.95);
}

.poll-popup-icon {
    margin: 0 auto 20px;
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #1877f2 0%, #0066cc 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 8px 20px rgba(24, 119, 242, 0.3);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.poll-popup-title {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 15px 0;
    line-height: 1.2;
}

.poll-popup-description {
    font-size: 16px;
    color: #666;
    line-height: 1.6;
    margin: 0 0 30px 0;
}

.poll-popup-button {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 32px;
    background: linear-gradient(135deg, #1877f2 0%, #0066cc 100%);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-size: 18px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(24, 119, 242, 0.4);
    margin-bottom: 15px;
}

.poll-popup-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(24, 119, 242, 0.5);
    background: linear-gradient(135deg, #1565c0 0%, #0052a3 100%);
}

.poll-popup-button svg {
    transition: transform 0.3s ease;
}

.poll-popup-button:hover svg {
    transform: translateX(4px);
}

.poll-popup-skip {
    background: transparent;
    border: none;
    color: #999;
    font-size: 14px;
    cursor: pointer;
    padding: 8px 16px;
    transition: color 0.2s;
    text-decoration: underline;
}

.poll-popup-skip:hover {
    color: #666;
}

@media (max-width: 768px) {
    .poll-popup-container {
        padding: 30px 20px;
        max-width: 90%;
    }
    
    .poll-popup-title {
        font-size: 24px;
    }
    
    .poll-popup-description {
        font-size: 14px;
    }
    
    .poll-popup-button {
        padding: 14px 24px;
        font-size: 16px;
    }
    
    .poll-popup-icon {
        width: 64px;
        height: 64px;
    }
}

.info-sidebar-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.info-sidebar-item strong {
    color: #1a1a1a;
    font-size: 14px;
}

.info-sidebar-item span {
    color: #666;
    font-size: 14px;
}

@media (max-width: 968px) {
    .trabajo-content-main {
        grid-template-columns: 1fr;
    }
    
    .trabajo-header-content {
        flex-direction: column;
    }
}

@media (max-width: 768px) {
    .trabajo-titulo {
        font-size: 24px;
    }
    
    .trabajo-actions-content {
        flex-direction: column;
        gap: 15px;
    }
    
    .btn-postularme {
        width: 100%;
    }
}

/* Estilos del Slider de Imágenes */
.trabajo-image-slider-wrapper {
    width: 100%;
    position: relative;
    background: #000;
}

.trabajo-image-slider {
    width: 100%;
    height: 500px;
}

.trabajo-image-slider .swiper-slide {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000;
}

.trabajo-image-slider .slider-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.trabajo-image-slider .slider-image:hover {
    transform: scale(1.02);
}

.trabajo-image-slider .swiper-pagination {
    bottom: 20px;
}

.trabajo-image-slider .swiper-pagination-bullet {
    background: #fff;
    opacity: 0.5;
    width: 10px;
    height: 10px;
}

.trabajo-image-slider .swiper-pagination-bullet-active {
    opacity: 1;
    background: #0066cc;
}

.trabajo-image-slider .swiper-button-next,
.trabajo-image-slider .swiper-button-prev {
    color: #fff;
    background: rgba(0, 0, 0, 0.5);
    width: 44px;
    height: 44px;
    border-radius: 50%;
    transition: background 0.3s;
}

.trabajo-image-slider .swiper-button-next:hover,
.trabajo-image-slider .swiper-button-prev:hover {
    background: rgba(0, 0, 0, 0.8);
}

.trabajo-image-slider .swiper-button-next::after,
.trabajo-image-slider .swiper-button-prev::after {
    font-size: 20px;
    font-weight: bold;
}

.trabajo-image-placeholder {
    width: 100%;
    height: 400px;
    background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
}

.trabajo-image-placeholder svg {
    opacity: 0.3;
}

/* Vista en pantalla completa */
.fullscreen-slider-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.95);
    z-index: 9999;
    cursor: pointer;
}

.fullscreen-slider-overlay.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.fullscreen-slider-container {
    width: 100%;
    height: 100%;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fullscreen-swiper {
    width: 100%;
    height: 100%;
}

.fullscreen-swiper .swiper-slide {
    display: flex;
    align-items: center;
    justify-content: center;
}

.fullscreen-slider-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    margin: auto;
    display: block;
    width: auto;
    height: auto;
}

.fullscreen-pagination {
    position: absolute;
    bottom: 60px;
    left: 50%;
    transform: translateX(-50%);
    color: #fff;
    font-size: 16px;
    z-index: 10;
}

.fullscreen-button-next,
.fullscreen-button-prev {
    color: #fff;
    background: rgba(255, 255, 255, 0.2);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    transition: background 0.3s;
}

.fullscreen-button-next:hover,
.fullscreen-button-prev:hover {
    background: rgba(255, 255, 255, 0.3);
}

.fullscreen-button-next::after,
.fullscreen-button-prev::after {
    font-size: 20px;
    font-weight: bold;
}

.fullscreen-slider-close {
    position: absolute;
    top: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    border: none;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s;
    z-index: 10000;
}

.fullscreen-slider-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

.fullscreen-slider-counter {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    color: #fff;
    background: rgba(0, 0, 0, 0.5);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
}

@media (max-width: 768px) {
    .trabajo-image-slider {
        height: 350px;
    }
    
    .trabajo-image-slider .swiper-button-next,
    .trabajo-image-slider .swiper-button-prev {
        width: 36px;
        height: 36px;
    }
    
    .trabajo-image-slider .swiper-button-next::after,
    .trabajo-image-slider .swiper-button-prev::after {
        font-size: 16px;
    }
}
</style>

<script>
function postularme() {
    // Aquí puedes agregar la lógica para postularse
    alert('Función de postulación próximamente disponible');
}


function toggleFavorito() {
    // Lógica para guardar en favoritos
    console.log('Toggle favorito');
}

function compartir() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo esc_js($trabajo->post_title); ?>',
            text: '<?php echo esc_js(wp_trim_words($trabajo->post_excerpt, 20)); ?>',
            url: window.location.href
        });
    } else {
        // Fallback: copiar al portapapeles
        navigator.clipboard.writeText(window.location.href);
        alert('Enlace copiado al portapapeles');
    }
}

function ocultar() {
    // Lógica para ocultar oferta
    console.log('Ocultar oferta');
}

function masOpciones() {
    // Lógica para más opciones
    console.log('Más opciones');
}

// Inicializar Swiper para el slider de imágenes
<?php if (!empty($all_images) && count($all_images) > 1): ?>
document.addEventListener('DOMContentLoaded', function() {
    const swiper = new Swiper('.trabajo-image-slider', {
        slidesPerView: 1,
        spaceBetween: 0,
        loop: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        keyboard: {
            enabled: true,
        },
    });
});
<?php endif; ?>

// Funcionalidad de vista en pantalla completa con soporte de swipe
const allImagesData = <?php echo json_encode($all_images); ?>;

let fullscreenSwiper = null;
let currentFullscreenIndex = 0;

function openFullscreenSlider(initialIndex) {
    if (!allImagesData || allImagesData.length === 0) return;
    
    currentFullscreenIndex = initialIndex || 0;
    
    // Crear overlay si no existe
    let overlay = document.getElementById('fullscreen-slider-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'fullscreen-slider-overlay';
        overlay.className = 'fullscreen-slider-overlay';
        overlay.innerHTML = `
            <div class="fullscreen-slider-container">
                <button class="fullscreen-slider-close" onclick="closeFullscreenSlider()">×</button>
                <div class="swiper fullscreen-swiper">
                    <div class="swiper-wrapper" id="fullscreen-swiper-wrapper">
                    </div>
                    <div class="swiper-pagination fullscreen-pagination"></div>
                    <div class="swiper-button-next fullscreen-button-next"></div>
                    <div class="swiper-button-prev fullscreen-button-prev"></div>
                </div>
                <div class="fullscreen-slider-counter">
                    <span id="current-image-index">1</span> / <span id="total-images">${allImagesData.length}</span>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        
        // Crear slides
        const wrapper = overlay.querySelector('#fullscreen-swiper-wrapper');
        allImagesData.forEach(function(image, index) {
            const slide = document.createElement('div');
            slide.className = 'swiper-slide';
            slide.innerHTML = `<img src="${image.url}" class="fullscreen-slider-image" alt="Imagen ${index + 1}" />`;
            wrapper.appendChild(slide);
        });
        
        // Inicializar Swiper para pantalla completa
        fullscreenSwiper = new Swiper('.fullscreen-swiper', {
            slidesPerView: 1,
            spaceBetween: 0,
            initialSlide: currentFullscreenIndex,
            pagination: {
                el: '.fullscreen-pagination',
                type: 'fraction',
            },
            navigation: {
                nextEl: '.fullscreen-button-next',
                prevEl: '.fullscreen-button-prev',
            },
            keyboard: {
                enabled: true,
            },
            touchEventsTarget: 'container',
            allowTouchMove: true,
            on: {
                slideChange: function() {
                    const index = this.activeIndex;
                    document.getElementById('current-image-index').textContent = index + 1;
                    currentFullscreenIndex = index;
                }
            }
        });
    } else {
        // Si ya existe, solo cambiar al slide inicial
        if (fullscreenSwiper) {
            fullscreenSwiper.slideTo(currentFullscreenIndex);
        }
    }
    
    // Navegación con teclado
    function handleKeyPress(e) {
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
            if (fullscreenSwiper) {
                fullscreenSwiper.slideNext();
            }
        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
            if (fullscreenSwiper) {
                fullscreenSwiper.slidePrev();
            }
        } else if (e.key === 'Escape') {
            closeFullscreenSlider();
        }
    }
    
    // Guardar referencia al handler para poder removerlo después
    overlay._keyHandler = handleKeyPress;
    document.addEventListener('keydown', handleKeyPress);
    
    // Mostrar overlay
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Actualizar contador inicial
    document.getElementById('current-image-index').textContent = currentFullscreenIndex + 1;
    document.getElementById('total-images').textContent = allImagesData.length;
}

function closeFullscreenSlider() {
    const overlay = document.getElementById('fullscreen-slider-overlay');
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        
        // Remover handler de teclado
        if (overlay._keyHandler) {
            document.removeEventListener('keydown', overlay._keyHandler);
            overlay._keyHandler = null;
        }
    }
}
</script>

<!-- Popup de Encuesta -->
<div id="poll-popup-overlay" class="poll-popup-overlay" style="display: none;">
    <div class="poll-popup-container">
        <button class="poll-popup-close" onclick="closePollPopup(true)" aria-label="Cerrar" title="Cerrar">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
        <div class="poll-popup-content">
            <!-- Contenido para votar (antes del 30 de diciembre) -->
            <div id="poll-content-vote" style="display: none;">
                <div class="poll-popup-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                </div>
                <h2 class="poll-popup-title">¡Tu opinión es importante!</h2>
                <p class="poll-popup-description">
                    ¿En qué empresa agroindustrial te sentiste mejor trabajando en Ica?
                </p>
                <a href="https://agrochamba.com/blog/encuesta-de-la-mejor-empresa-agroindustrial-iquena/" 
                   class="poll-popup-button" 
                   target="_blank"
                   onclick="trackPollClick()">
                    Participar en la encuesta
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
                <button class="poll-popup-skip" onclick="closePollPopup(true)">
                    Tal vez después
                </button>
            </div>
            
            <!-- Contenido para ver resultados (después del 30 de diciembre) -->
            <div id="poll-content-results" style="display: none;">
                <div class="poll-popup-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 17A7 7 0 1 1 16 10"/>
                        <polyline points="15 8 9 8 9 14"/>
                    </svg>
                </div>
                <h2 class="poll-popup-title">¡Los resultados están disponibles!</h2>
                <p class="poll-popup-description">
                    La encuesta ha finalizado. Puedes ver los resultados de: ¿En qué empresa agroindustrial te sentiste mejor trabajando en Ica?
                </p>
                <a href="https://agrochamba.com/blog/encuesta-de-la-mejor-empresa-agroindustrial-iquena/?results=true&form_id=2375&render_id=0" 
                   class="poll-popup-button" 
                   target="_blank"
                   onclick="trackPollClick()">
                    Ver resultados
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
                <button class="poll-popup-skip" onclick="closePollPopup(true)">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sección de Comentarios -->
<div class="trabajo-comments-section" id="comments">
    <div class="comments-container">
        <h2 class="comments-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            Comentarios
            <span class="comments-count" id="comments-total-count">
                <?php echo get_comments_number($trabajo_id); ?>
            </span>
        </h2>
        
        <?php if (comments_open($trabajo_id)): ?>
            <!-- Formulario de comentario -->
            <?php if (is_user_logged_in()): ?>
                <div class="comment-form-wrapper">
                    <?php
                    $current_user = wp_get_current_user();
                    $avatar_url = get_avatar_url($current_user->ID, array('size' => 48));
                    ?>
                    <div class="comment-form-avatar">
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>">
                    </div>
                    <div class="comment-form-content">
                        <form id="comment-form" class="comment-form">
                            <textarea 
                                id="comment-content" 
                                name="comment" 
                                placeholder="Escribe un comentario..." 
                                rows="3"
                                required></textarea>
                            <div class="comment-form-actions">
                                <button type="submit" class="btn-comment-submit">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="22" y1="2" x2="11" y2="13"/>
                                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                    </svg>
                                    Publicar comentario
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="comment-login-prompt">
                    <p>Debes <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">iniciar sesión</a> para comentar.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="comments-closed">
                <p>Los comentarios están cerrados para este trabajo.</p>
            </div>
        <?php endif; ?>
        
        <!-- Lista de comentarios -->
        <div class="comments-list" id="comments-list">
            <div class="comments-loading" id="comments-loading">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
                    <path d="M12 2 A10 10 0 0 1 22 12" stroke-linecap="round"/>
                </svg>
                <span>Cargando comentarios...</span>
            </div>
        </div>
        
        <!-- Botón para cargar más comentarios -->
        <div class="comments-load-more-wrapper" id="comments-load-more-wrapper" style="display: none;">
            <button class="btn-load-more-comments" id="btn-load-more-comments">
                Cargar más comentarios
            </button>
        </div>
    </div>
</div>

<style>
/* ==========================================
   ESTILOS DE COMENTARIOS
   ========================================== */
.trabajo-comments-section {
    margin-top: 60px;
    padding: 40px 0;
    border-top: 2px solid #e0e0e0;
}

.comments-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px;
}

.comments-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 28px;
    font-weight: 700;
    color: #1a237e;
    margin-bottom: 30px;
}

.comments-title svg {
    color: #1877f2;
}

.comments-count {
    font-size: 20px;
    font-weight: 500;
    color: #666;
    margin-left: 8px;
}

.comment-form-wrapper {
    display: flex;
    gap: 16px;
    margin-bottom: 40px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 12px;
}

.comment-form-avatar {
    flex-shrink: 0;
}

.comment-form-avatar img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.comment-form-content {
    flex: 1;
}

.comment-form textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    font-family: inherit;
    resize: vertical;
    min-height: 80px;
    transition: border-color 0.2s;
}

.comment-form textarea:focus {
    outline: none;
    border-color: #1877f2;
}

.comment-form-actions {
    margin-top: 12px;
    display: flex;
    justify-content: flex-end;
}

.btn-comment-submit {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #1877f2;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-comment-submit:hover {
    background: #1565c0;
}

.btn-comment-submit:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.comment-login-prompt,
.comments-closed {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
    color: #666;
    margin-bottom: 30px;
}

.comment-login-prompt a {
    color: #1877f2;
    text-decoration: none;
    font-weight: 600;
}

.comment-login-prompt a:hover {
    text-decoration: underline;
}

.comments-list {
    margin-top: 30px;
}

.comments-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    padding: 40px;
    color: #999;
}

.comments-loading svg {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.comment-item {
    display: flex;
    gap: 16px;
    padding: 20px 0;
    border-bottom: 1px solid #e0e0e0;
}

.comment-item:last-child {
    border-bottom: none;
}

.comment-avatar {
    flex-shrink: 0;
}

.comment-avatar img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.comment-content-wrapper {
    flex: 1;
}

.comment-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.comment-author-name {
    font-weight: 600;
    color: #1a237e;
    font-size: 15px;
}

.comment-date {
    font-size: 13px;
    color: #999;
}

.comment-text {
    color: #333;
    line-height: 1.6;
    margin-bottom: 8px;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.comment-actions {
    display: flex;
    gap: 16px;
    margin-top: 8px;
}

.comment-action-btn {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    background: transparent;
    border: none;
    color: #65676b;
    font-size: 13px;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.comment-action-btn:hover {
    background: #f0f2f5;
    color: #1877f2;
}

.comment-action-btn.delete-btn:hover {
    color: #dc3545;
}

.comments-load-more-wrapper {
    text-align: center;
    margin-top: 30px;
}

.btn-load-more-comments {
    padding: 12px 24px;
    background: #f0f2f5;
    color: #1877f2;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-load-more-comments:hover {
    background: #e4e6eb;
}

.comment-empty {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.comment-empty svg {
    margin-bottom: 16px;
    opacity: 0.5;
}

.comment-reply-form {
    margin-top: 16px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
    display: none;
}

.comment-reply-form.active {
    display: block;
}

@media (max-width: 768px) {
    .comment-form-wrapper {
        flex-direction: column;
    }
    
    .comment-form-avatar {
        align-self: flex-start;
    }
    
    .comments-title {
        font-size: 24px;
    }
}
</style>

<script>
// Sistema de comentarios
(function() {
    const jobId = <?php echo esc_js($trabajo_id); ?>;
    let currentPage = 1;
    let totalPages = 1;
    let isLoading = false;
    
    // Cargar comentarios al iniciar
    document.addEventListener('DOMContentLoaded', function() {
        loadComments();
        
        // Manejar envío de formulario
        const commentForm = document.getElementById('comment-form');
        if (commentForm) {
            commentForm.addEventListener('submit', handleCommentSubmit);
        }
        
        // Manejar botón de cargar más
        const loadMoreBtn = document.getElementById('btn-load-more-comments');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', loadMoreComments);
        }
    });
    
    function loadComments(page = 1) {
        if (isLoading) return;
        
        isLoading = true;
        const commentsList = document.getElementById('comments-list');
        const loadingEl = document.getElementById('comments-loading');
        
        if (page === 1) {
            commentsList.innerHTML = '<div class="comments-loading" id="comments-loading"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2 A10 10 0 0 1 22 12" stroke-linecap="round"/></svg><span>Cargando comentarios...</span></div>';
        }
        
        fetch(`<?php echo esc_url(rest_url('agrochamba/v1/jobs/')); ?>${jobId}/comments?page=${page}&per_page=20`)
            .then(response => response.json())
            .then(data => {
                isLoading = false;
                currentPage = data.page;
                totalPages = data.total_pages;
                
                if (page === 1) {
                    renderComments(data.comments);
                } else {
                    appendComments(data.comments);
                }
                
                updateLoadMoreButton();
                updateCommentsCount(data.total);
            })
            .catch(error => {
                isLoading = false;
                console.error('Error:', error);
                commentsList.innerHTML = '<div class="comment-empty"><p>Error al cargar comentarios. Por favor, recarga la página.</p></div>';
            });
    }
    
    function renderComments(comments) {
        const commentsList = document.getElementById('comments-list');
        
        if (comments.length === 0) {
            commentsList.innerHTML = '<div class="comment-empty"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><p>Aún no hay comentarios. ¡Sé el primero en comentar!</p></div>';
            return;
        }
        
        commentsList.innerHTML = comments.map(comment => renderComment(comment)).join('');
    }
    
    function appendComments(comments) {
        const commentsList = document.getElementById('comments-list');
        const emptyDiv = commentsList.querySelector('.comment-empty');
        if (emptyDiv) {
            emptyDiv.remove();
        }
        
        comments.forEach(comment => {
            const commentEl = document.createElement('div');
            commentEl.innerHTML = renderComment(comment);
            commentsList.appendChild(commentEl.firstElementChild);
        });
    }
    
    function renderComment(comment) {
        const date = new Date(comment.date);
        const dateStr = date.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        
        return `
            <div class="comment-item" data-comment-id="${comment.id}">
                <div class="comment-avatar">
                    <img src="${escapeHtml(comment.author.avatar)}" alt="${escapeHtml(comment.author.name)}">
                </div>
                <div class="comment-content-wrapper">
                    <div class="comment-header">
                        <span class="comment-author-name">${escapeHtml(comment.author.name)}</span>
                        <span class="comment-date">${dateStr}</span>
                    </div>
                    <div class="comment-text">${comment.content}</div>
                    ${comment.can_edit || comment.can_delete ? `
                        <div class="comment-actions">
                            ${comment.can_edit ? `<button class="comment-action-btn edit-btn" onclick="editComment(${comment.id})">Editar</button>` : ''}
                            ${comment.can_delete ? `<button class="comment-action-btn delete-btn" onclick="deleteComment(${comment.id})">Eliminar</button>` : ''}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    function handleCommentSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const textarea = form.querySelector('#comment-content');
        const content = textarea.value.trim();
        
        if (!content) {
            alert('Por favor, escribe un comentario.');
            return;
        }
        
        const submitBtn = form.querySelector('.btn-comment-submit');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Publicando...';
        
        fetch(`<?php echo esc_url(rest_url('agrochamba/v1/jobs/')); ?>${jobId}/comments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                content: content
            })
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Publicar comentario';
            
            if (data.success) {
                textarea.value = '';
                // Recargar comentarios
                loadComments(1);
            } else {
                alert(data.message || 'Error al publicar el comentario.');
            }
        })
        .catch(error => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Publicar comentario';
            console.error('Error:', error);
            alert('Error de conexión. Por favor, intenta nuevamente.');
        });
    }
    
    function loadMoreComments() {
        if (currentPage < totalPages) {
            loadComments(currentPage + 1);
        }
    }
    
    function updateLoadMoreButton() {
        const wrapper = document.getElementById('comments-load-more-wrapper');
        if (currentPage < totalPages) {
            wrapper.style.display = 'block';
        } else {
            wrapper.style.display = 'none';
        }
    }
    
    function updateCommentsCount(total) {
        const countEl = document.getElementById('comments-total-count');
        if (countEl) {
            countEl.textContent = total;
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Funciones globales para editar y eliminar
    window.editComment = function(commentId) {
        const commentItem = document.querySelector(`[data-comment-id="${commentId}"]`);
        const commentText = commentItem.querySelector('.comment-text');
        const currentText = commentText.textContent;
        
        const newText = prompt('Editar comentario:', currentText);
        if (newText === null || newText.trim() === currentText.trim()) {
            return;
        }
        
        fetch(`<?php echo esc_url(rest_url('agrochamba/v1/comments/')); ?>${commentId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                content: newText.trim()
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadComments(1);
            } else {
                alert(data.message || 'Error al editar el comentario.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión. Por favor, intenta nuevamente.');
        });
    };
    
    window.deleteComment = function(commentId) {
        if (!confirm('¿Estás seguro de que quieres eliminar este comentario?')) {
            return;
        }
        
        fetch(`<?php echo esc_url(rest_url('agrochamba/v1/comments/')); ?>${commentId}`, {
            method: 'DELETE',
            headers: {
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadComments(1);
            } else {
                alert(data.message || 'Error al eliminar el comentario.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión. Por favor, intenta nuevamente.');
        });
    };
})();

// Contar vista cuando se carga la página individual (respaldo del método PHP)
// Este script asegura que se cuente la vista incluso si el método PHP falla
(function() {
    const trabajoId = <?php echo intval($trabajo_id); ?>;
    
    if (trabajoId > 0) {
        // Usar sessionStorage para evitar contar múltiples veces en la misma sesión del navegador
        // pero permitir contar en diferentes sesiones o después de cerrar el navegador
        const viewKey = 'agrochamba_viewed_' + trabajoId;
        const alreadyViewed = sessionStorage.getItem(viewKey);
        
        if (!alreadyViewed) {
            // Esperar 1 segundo para dar tiempo al método PHP de ejecutarse primero
            setTimeout(function() {
                // Contar vista usando el endpoint POST (respaldo si PHP falló)
                // Esto se ejecuta cada vez que alguien visita la URL específica del trabajo
                fetch('<?php echo esc_url(rest_url('agrochamba/v1/jobs/')); ?>' + trabajoId + '/views', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    // Actualizar contador en la página si se devuelve
                    if (data.views !== undefined) {
                        const viewsElements = document.querySelectorAll('[data-counter="views"]');
                        viewsElements.forEach(function(el) {
                            el.textContent = data.views;
                        });
                        // Marcar como contado en esta sesión del navegador
                        sessionStorage.setItem(viewKey, '1');
                    }
                })
                .catch(error => {
                    // Silenciar errores - el método PHP debería haber contado ya
                });
            }, 1000); // Esperar 1 segundo después de cargar
        } else {
            // Ya se contó en esta sesión, solo actualizar el display con el valor actual
            fetch('<?php echo esc_url(rest_url('agrochamba/v1/jobs/')); ?>' + trabajoId + '/views', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.views !== undefined) {
                    const viewsElements = document.querySelectorAll('[data-counter="views"]');
                    viewsElements.forEach(function(el) {
                        el.textContent = data.views;
                    });
                }
            })
            .catch(error => {
                // Silenciar errores
            });
        }
    }
})();

// Sistema de Popup de Encuesta
(function() {
    const POLL_POPUP_KEY = 'agrochamba_poll_popup_closed';
    const POLL_POPUP_DELAY = 3000; // Mostrar después de 3 segundos
    const POLL_POPUP_SCROLL_TRIGGER = 0.5; // Mostrar cuando el usuario haya scrolleado 50% de la página
    const POLL_EXPIRY_DATE = new Date('2025-12-31T00:00:00'); // 30 de diciembre de 2025 (incluye todo el día 30)
    
    let popupShown = false;
    let scrollTriggered = false;
    
    // Verificar si la encuesta ya venció
    function isPollExpired() {
        const now = new Date();
        // Comparar solo la fecha (sin hora) para incluir todo el día 30
        const expiryDateOnly = new Date(POLL_EXPIRY_DATE.getFullYear(), POLL_EXPIRY_DATE.getMonth(), POLL_EXPIRY_DATE.getDate());
        const nowDateOnly = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        return nowDateOnly > expiryDateOnly;
    }
    
    // Configurar el contenido del popup según si la encuesta venció o no
    function setupPollContent() {
        const voteContent = document.getElementById('poll-content-vote');
        const resultsContent = document.getElementById('poll-content-results');
        
        if (isPollExpired()) {
            // Mostrar contenido de resultados
            if (voteContent) voteContent.style.display = 'none';
            if (resultsContent) resultsContent.style.display = 'block';
        } else {
            // Mostrar contenido para votar
            if (voteContent) voteContent.style.display = 'block';
            if (resultsContent) resultsContent.style.display = 'none';
        }
    }
    
    function shouldShowPopup() {
        // Verificar si el usuario ya cerró el popup en esta sesión
        if (sessionStorage.getItem(POLL_POPUP_KEY)) {
            return false;
        }
        return true;
    }
    
    function showPollPopup() {
        if (popupShown || !shouldShowPopup()) {
            return;
        }
        
        // Configurar el contenido antes de mostrar
        setupPollContent();
        
        const overlay = document.getElementById('poll-popup-overlay');
        if (overlay) {
            overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            popupShown = true;
            
            // Agregar animación de entrada
            setTimeout(() => {
                overlay.classList.add('show');
            }, 10);
        }
    }
    
    function closePollPopup(skip = false) {
        const overlay = document.getElementById('poll-popup-overlay');
        if (overlay) {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
            
            // Guardar en sessionStorage para no mostrar de nuevo en esta sesión
            if (skip) {
                sessionStorage.setItem(POLL_POPUP_KEY, '1');
            }
        }
    }
    
    function trackPollClick() {
        // Opcional: rastrear clics en el botón de encuesta
        // Aquí podrías agregar analytics si lo deseas
        console.log('Usuario hizo clic en participar en la encuesta');
    }
    
    // Hacer funciones globales para que puedan ser llamadas desde el HTML
    window.closePollPopup = closePollPopup;
    window.trackPollClick = trackPollClick;
    
    // Mostrar popup después de un delay
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar el contenido al cargar la página
        setupPollContent();
        
        setTimeout(function() {
            if (shouldShowPopup()) {
                showPollPopup();
            }
        }, POLL_POPUP_DELAY);
        
        // Mostrar popup cuando el usuario haga scroll (solo una vez)
        window.addEventListener('scroll', function() {
            if (!scrollTriggered && shouldShowPopup()) {
                const scrollPercent = (window.scrollY + window.innerHeight) / document.documentElement.scrollHeight;
                if (scrollPercent >= POLL_POPUP_SCROLL_TRIGGER) {
                    scrollTriggered = true;
                    if (!popupShown) {
                        showPollPopup();
                    }
                }
            }
        }, { once: false });
        
        // Cerrar popup al hacer clic fuera del contenedor
        const overlay = document.getElementById('poll-popup-overlay');
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closePollPopup(true);
                }
            });
        }
        
        // Cerrar popup con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const overlay = document.getElementById('poll-popup-overlay');
                if (overlay && overlay.style.display === 'flex') {
                    closePollPopup(true);
                }
            }
        });
    });
})();
</script>

<?php
get_footer();

