<?php
/**
 * Funciones helper para empresas
 * 
 * Funciones para mostrar cards de empresa, obtener datos, etc.
 *
 * @package AgroChamba
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtener datos completos de una empresa
 *
 * @param int $empresa_id ID del CPT Empresa
 * @return array|false Datos de la empresa o false si no existe
 */
function agrochamba_get_empresa_data($empresa_id) {
    $empresa = get_post($empresa_id);
    
    if (!$empresa || $empresa->post_type !== 'empresa') {
        return false;
    }
    
    $logo_id = get_post_thumbnail_id($empresa_id);
    $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'agrochamba_profile') : null;
    
    return [
        'id' => $empresa_id,
        'nombre_comercial' => get_post_meta($empresa_id, '_empresa_nombre_comercial', true) ?: $empresa->post_title,
        'razon_social' => get_post_meta($empresa_id, '_empresa_razon_social', true),
        'ruc' => get_post_meta($empresa_id, '_empresa_ruc', true),
        'logo_url' => $logo_url,
        'sector' => get_post_meta($empresa_id, '_empresa_sector', true),
        'verificada' => get_post_meta($empresa_id, '_empresa_verificada', true) === '1',
        'ciudad' => get_post_meta($empresa_id, '_empresa_ciudad', true),
        'url' => get_permalink($empresa_id),
        'ofertas_count' => agrochamba_get_empresa_ofertas_count($empresa_id),
    ];
}

/**
 * Mostrar card de empresa (para usar en empleos)
 *
 * @param int $empresa_id ID del CPT Empresa
 * @param array $args Argumentos adicionales
 */
function agrochamba_render_empresa_card($empresa_id, $args = []) {
    $empresa_data = agrochamba_get_empresa_data($empresa_id);
    
    if (!$empresa_data) {
        return;
    }
    
    $defaults = [
        'show_ofertas_count' => true,
        'show_verificada' => true,
        'class' => 'empresa-card',
    ];
    
    $args = wp_parse_args($args, $defaults);
    
    ?>
    <div class="<?php echo esc_attr($args['class']); ?>">
        <a href="<?php echo esc_url($empresa_data['url']); ?>" class="empresa-card-link">
            <?php if ($empresa_data['logo_url']): ?>
                <div class="empresa-card-logo">
                    <img src="<?php echo esc_url($empresa_data['logo_url']); ?>" 
                         alt="<?php echo esc_attr($empresa_data['nombre_comercial']); ?>" />
                </div>
            <?php endif; ?>
            
            <div class="empresa-card-content">
                <h3 class="empresa-card-nombre">
                    <?php echo esc_html($empresa_data['nombre_comercial']); ?>
                    <?php if ($args['show_verificada'] && $empresa_data['verificada']): ?>
                        <span class="badge-verificado-small" title="Empresa verificada">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </span>
                    <?php endif; ?>
                </h3>
                
                <?php if ($empresa_data['sector']): ?>
                    <p class="empresa-card-sector"><?php echo esc_html($empresa_data['sector']); ?></p>
                <?php endif; ?>
                
                <?php if ($empresa_data['ciudad']): ?>
                    <p class="empresa-card-ubicacion">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($empresa_data['ciudad']); ?>
                    </p>
                <?php endif; ?>
                
                <?php if ($args['show_ofertas_count']): ?>
                    <p class="empresa-card-ofertas">
                        <span class="dashicons dashicons-portfolio"></span>
                        <?php echo esc_html($empresa_data['ofertas_count']); ?> oferta(s) activa(s)
                    </p>
                <?php endif; ?>
            </div>
        </a>
    </div>
    
    <style>
    .empresa-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
        margin: 20px 0;
        transition: box-shadow 0.2s;
    }
    
    .empresa-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .empresa-card-link {
        display: flex;
        gap: 20px;
        padding: 20px;
        text-decoration: none;
        color: inherit;
    }
    
    .empresa-card-logo {
        flex-shrink: 0;
        width: 80px;
        height: 80px;
    }
    
    .empresa-card-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .empresa-card-content {
        flex: 1;
    }
    
    .empresa-card-nombre {
        margin: 0 0 8px 0;
        font-size: 18px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .badge-verificado-small {
        display: inline-flex;
        align-items: center;
        background: #00a32a;
        color: #fff;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 12px;
    }
    
    .empresa-card-sector {
        margin: 5px 0;
        color: #646970;
        font-size: 14px;
    }
    
    .empresa-card-ubicacion,
    .empresa-card-ofertas {
        margin: 5px 0;
        color: #646970;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    @media (max-width: 600px) {
        .empresa-card-link {
            flex-direction: column;
            text-align: center;
        }
        
        .empresa-card-logo {
            margin: 0 auto;
        }
    }
    </style>
    <?php
}

/**
 * Agregar card de empresa a los empleos (hook)
 */
function agrochamba_add_empresa_card_to_empleo($content) {
    if (get_post_type() !== 'trabajo' || !is_single()) {
        return $content;
    }
    
    $empresa_id = get_post_meta(get_the_ID(), 'empresa_id', true);
    
    if (!$empresa_id) {
        return $content;
    }
    
    ob_start();
    agrochamba_render_empresa_card($empresa_id);
    $empresa_card = ob_get_clean();
    
    // Agregar la card después del contenido
    return $content . $empresa_card;
}
add_filter('the_content', 'agrochamba_add_empresa_card_to_empleo', 20);

