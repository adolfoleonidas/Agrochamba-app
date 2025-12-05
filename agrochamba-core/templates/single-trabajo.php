<?php
/**
 * Template para mostrar el detalle de un trabajo/empleo
 * Estilo Computrabajo con información de empresa embebida
 */

if (!defined('ABSPATH')) {
    exit;
}

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
if ($empresa_id) {
    $empresa_data = agrochamba_get_empresa_data($empresa_id);
}

// Imagen destacada
$featured_image_id = get_post_thumbnail_id($trabajo_id);
$featured_image_url = $featured_image_id ? wp_get_attachment_image_url($featured_image_id, 'full') : null;

?>
<div class="trabajo-detalle-wrapper">
    <!-- Header del Trabajo -->
    <div class="trabajo-header-section">
        <div class="trabajo-header-content">
            <div class="trabajo-header-left">
                <h1 class="trabajo-titulo">
                    <?php echo esc_html($trabajo->post_title); ?>
                    <?php if ($ubicacion): ?>
                        <span class="trabajo-ubicacion-header">, <?php echo esc_html($ubicacion->name); ?></span>
                    <?php endif; ?>
                </h1>
                
                <?php if ($empresa_data): ?>
                    <div class="empresa-info-header">
                        <div class="empresa-rating-header">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#FFB800">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            <span class="rating-value">4.2</span>
                            <a href="<?php echo esc_url($empresa_data['url']); ?>" class="empresa-nombre-link">
                                <?php echo esc_html($empresa_data['nombre_comercial']); ?>
                            </a>
                            <span class="evaluaciones-count">0 evaluaciones</span>
                        </div>
                        
                        <?php if ($ubicacion): ?>
                            <div class="trabajo-location-header">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 0a5 5 0 0 0-5 5c0 4.5 5 10 5 10s5-5.5 5-10a5 5 0 0 0-5-5zm0 7a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/>
                                </svg>
                                <?php echo esc_html($ubicacion->name); ?>
                            </div>
                        <?php endif; ?>
                        
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
            
            <?php if ($empresa_data && $empresa_data['logo_url']): ?>
                <div class="empresa-logo-header">
                    <img src="<?php echo esc_url($empresa_data['logo_url']); ?>" 
                         alt="<?php echo esc_attr($empresa_data['nombre_comercial']); ?>" />
                </div>
            <?php endif; ?>
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
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="18" cy="5" r="3"/>
                        <circle cx="6" cy="12" r="3"/>
                        <circle cx="18" cy="19" r="3"/>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
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
                    <?php echo wp_kses_post(wpautop($trabajo->post_content)); ?>
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
                                <span><?php echo esc_html($responsabilidad); ?></span>
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
                            <strong>Experiencia:</strong> <?php echo esc_html($experiencia); ?>
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
                                <?php echo wp_kses_post(wpautop($beneficios)); ?>
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
                    <h3 class="sidebar-title">Acerca de <?php echo esc_html($empresa_data['nombre_comercial']); ?></h3>
                    
                    <div class="empresa-card-header">
                        <?php if ($empresa_data['logo_url']): ?>
                            <img src="<?php echo esc_url($empresa_data['logo_url']); ?>" 
                                 alt="<?php echo esc_attr($empresa_data['nombre_comercial']); ?>" 
                                 class="empresa-logo-sidebar" />
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

.empresa-logo-header {
    width: 120px;
    height: 120px;
    flex-shrink: 0;
}

.empresa-logo-header img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
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

.empresa-logo-sidebar {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
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
    
    .empresa-logo-header {
        margin-top: 20px;
        align-self: center;
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
</script>

<?php
get_footer();

