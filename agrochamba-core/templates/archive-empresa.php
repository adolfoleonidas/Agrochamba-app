<?php
/**
 * Template para mostrar el archivo/listado de empresas
 * Diseño moderno similar al archive de trabajos
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Obtener información de la consulta
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$posts_per_page = get_option('posts_per_page', 12);

// Obtener filtros de URL
$sector_filter = isset($_GET['sector']) ? sanitize_text_field($_GET['sector']) : '';
$ubicacion_filter = isset($_GET['ubicacion']) ? sanitize_text_field($_GET['ubicacion']) : '';

?>
<div class="empresas-archive-wrapper">
    <!-- Header del Archivo -->
    <div class="empresas-archive-header">
        <div class="archive-header-content">
            <!-- Botón de categoría -->
            <div class="archive-category-badge">
                <span>Empresas</span>
            </div>
            
            <!-- Título principal -->
            <h1 class="archive-title">Explora nuestras empresas</h1>
            <p class="archive-subtitle">Conoce las empresas que están buscando talento en el sector agroindustrial</p>
            
            <!-- Barra de búsqueda -->
            <form class="archive-search-form" method="get" action="<?php echo esc_url(get_post_type_archive_link('empresa')); ?>">
                <div class="search-input-group">
                    <div class="search-input-wrapper">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input type="text" 
                               name="s" 
                               class="search-input" 
                               placeholder="Buscar empresa..."
                               value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
                    </div>
                    
                    <div class="search-input-wrapper location-wrapper">
                        <svg class="location-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <select name="ubicacion" class="search-input search-select" onchange="this.form.submit()">
                            <option value="">Ubicación</option>
                            <?php
                            $ubicaciones = get_terms(array(
                                'taxonomy' => 'ubicacion',
                                'hide_empty' => true,
                                'number' => 50,
                            ));
                            
                            if (!empty($ubicaciones) && !is_wp_error($ubicaciones)):
                                foreach ($ubicaciones as $ubicacion):
                            ?>
                                <option value="<?php echo esc_attr($ubicacion->slug); ?>" 
                                        <?php selected($ubicacion_filter, $ubicacion->slug); ?>>
                                    <?php echo esc_html($ubicacion->name); ?>
                                </option>
                            <?php 
                                endforeach;
                            endif;
                            ?>
                        </select>
                        <svg class="dropdown-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                    
                    <button type="submit" class="search-submit-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Grid de Empresas -->
    <div class="empresas-archive-content">
        <div class="empresas-grid">
            <?php if (have_posts()): ?>
                <?php while (have_posts()): the_post(); 
                    $empresa_id = get_the_ID();
                    $empresa_data = agrochamba_get_empresa_data($empresa_id);
                    
                    if (!$empresa_data) {
                        continue;
                    }
                    
                    $logo_url = $empresa_data['logo_url'];
                    $nombre_comercial = $empresa_data['nombre_comercial'];
                    $sector = $empresa_data['sector'];
                    $ciudad = $empresa_data['ciudad'];
                    $verificada = $empresa_data['verificada'];
                    $ofertas_count = $empresa_data['ofertas_count'];
                    
                    // Determinar badge
                    $badge = null;
                    $badge_class = '';
                    $post_date = get_the_date('U');
                    $hours_since = (time() - $post_date) / 3600;
                    
                    if ($hours_since <= 168) { // 7 días
                        $badge = 'Nueva';
                        $badge_class = 'badge-new';
                    } elseif ($verificada) {
                        $badge = 'Verificada';
                        $badge_class = 'badge-verified';
                    } elseif ($ofertas_count >= 10) {
                        $badge = 'Activa';
                        $badge_class = 'badge-active';
                    }
                ?>
                    <article class="empresa-card">
                        <a href="<?php echo esc_url($empresa_data['url']); ?>" class="empresa-card-link">
                            <div class="empresa-card-header">
                                <?php if ($logo_url): ?>
                                    <div class="empresa-card-logo">
                                        <img src="<?php echo esc_url($logo_url); ?>" 
                                             alt="<?php echo esc_attr($nombre_comercial); ?>"
                                             loading="lazy">
                                    </div>
                                <?php else: ?>
                                    <div class="empresa-card-logo-placeholder">
                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($badge): ?>
                                    <span class="empresa-badge <?php echo esc_attr($badge_class); ?>">
                                        <?php echo esc_html($badge); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="empresa-card-content">
                                <h2 class="empresa-card-title"><?php echo esc_html($nombre_comercial); ?></h2>
                                
                                <?php if ($sector): ?>
                                    <div class="empresa-card-sector">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                            <path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
                                        </svg>
                                        <span><?php echo esc_html($sector); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="empresa-card-info">
                                    <?php if ($ciudad): ?>
                                        <div class="info-item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                                <circle cx="12" cy="10" r="3"/>
                                            </svg>
                                            <span><?php echo esc_html($ciudad); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($ofertas_count > 0): ?>
                                        <div class="info-item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                            </svg>
                                            <span><?php echo esc_html($ofertas_count); ?> <?php echo $ofertas_count === 1 ? 'oferta' : 'ofertas'; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($verificada): ?>
                                    <div class="empresa-card-verified">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                                        </svg>
                                        <span>Empresa verificada</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empresas-empty">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <h2>No se encontraron empresas</h2>
                    <p>Intenta ajustar los filtros o vuelve más tarde</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Paginación -->
        <?php if (paginate_links()): ?>
            <div class="empresas-pagination">
                <?php
                echo paginate_links(array(
                    'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> Anterior',
                    'next_text' => 'Siguiente <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>',
                    'type' => 'list',
                ));
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.empresas-archive-wrapper {
    background: #f5f5f5;
    min-height: 100vh;
    padding-bottom: 40px;
}

.empresas-archive-header {
    background: #fff;
    padding: 60px 0 80px;
    margin-bottom: 40px;
    border-bottom: 1px solid #f0f0f0;
}

.archive-header-content {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px;
    text-align: center;
}

.archive-category-badge {
    display: inline-block;
    margin-bottom: 24px;
}

.archive-category-badge span {
    display: inline-block;
    padding: 8px 20px;
    background: #E3F2FD;
    color: #1976D2;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.archive-title {
    font-size: 48px;
    font-weight: 700;
    margin: 0 0 16px 0;
    color: #1a237e;
    line-height: 1.2;
}

.archive-subtitle {
    font-size: 18px;
    margin: 0 0 40px 0;
    color: #666;
    line-height: 1.6;
}

.archive-search-form {
    margin-top: 40px;
}

.search-input-group {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
}

.search-input-wrapper {
    position: relative;
    flex: 1;
    min-width: 280px;
    max-width: 400px;
}

.search-input-wrapper .search-icon,
.search-input-wrapper .location-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    pointer-events: none;
    z-index: 1;
}

.search-input-wrapper .dropdown-icon {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    pointer-events: none;
    z-index: 1;
}

.search-input {
    width: 100%;
    padding: 16px 16px 16px 48px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 16px;
    background: #fff;
    color: #333;
    transition: all 0.3s;
    outline: none;
}

.search-input:focus {
    border-color: #1976D2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
}

.search-input::placeholder {
    color: #999;
}

.search-select {
    appearance: none;
    padding-right: 48px;
    cursor: pointer;
}

.location-wrapper {
    position: relative;
}

.search-submit-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 16px 32px;
    background: #1976D2;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
}

.search-submit-btn:hover {
    background: #1565C0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
}

.search-submit-btn svg {
    flex-shrink: 0;
}

.empresas-archive-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.empresas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.empresa-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.empresa-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.empresa-card-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.empresa-card-header {
    position: relative;
    padding: 30px 20px 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    text-align: center;
}

.empresa-card-logo {
    width: 100px;
    height: 100px;
    margin: 0 auto 15px;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.empresa-card-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 10px;
}

.empresa-card-logo-placeholder {
    width: 100px;
    height: 100px;
    margin: 0 auto 15px;
    border-radius: 12px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.empresa-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-new {
    background: #E3F2FD;
    color: #1976D2;
}

.badge-verified {
    background: #E8F5E9;
    color: #2E7D32;
}

.badge-active {
    background: #FFF9C4;
    color: #F57F17;
}

.empresa-card-content {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.empresa-card-title {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 12px 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.empresa-card-sector {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #0066cc;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 12px;
}

.empresa-card-sector svg {
    flex-shrink: 0;
}

.empresa-card-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 12px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 14px;
}

.info-item svg {
    flex-shrink: 0;
    color: #0066cc;
}

.empresa-card-verified {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #2E7D32;
    font-size: 13px;
    font-weight: 500;
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}

.empresa-card-verified svg {
    flex-shrink: 0;
}

.empresas-empty {
    text-align: center;
    padding: 80px 20px;
    color: #999;
}

.empresas-empty svg {
    margin-bottom: 20px;
    opacity: 0.5;
}

.empresas-empty h2 {
    font-size: 24px;
    margin: 0 0 10px 0;
    color: #666;
}

.empresas-empty p {
    font-size: 16px;
    margin: 0;
}

.empresas-pagination {
    margin-top: 40px;
}

.empresas-pagination .page-numbers {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    list-style: none;
    padding: 0;
    margin: 0;
    flex-wrap: wrap;
}

.empresas-pagination .page-numbers li {
    margin: 0;
}

.empresas-pagination .page-numbers a,
.empresas-pagination .page-numbers span {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
    color: #666;
    background: #fff;
    border: 1px solid #e0e0e0;
    transition: all 0.3s;
    font-weight: 500;
}

.empresas-pagination .page-numbers a:hover {
    background: #1976D2;
    color: #fff;
    border-color: #1976D2;
}

.empresas-pagination .page-numbers .current {
    background: #1976D2;
    color: #fff;
    border-color: #1976D2;
}

@media (max-width: 768px) {
    .archive-title {
        font-size: 36px;
    }
    
    .archive-subtitle {
        font-size: 16px;
    }
    
    .search-input-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-input-wrapper {
        max-width: 100%;
    }
    
    .search-submit-btn {
        width: 100%;
        justify-content: center;
    }
    
    .empresas-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}

@media (max-width: 480px) {
    .archive-title {
        font-size: 28px;
    }
    
    .archive-subtitle {
        font-size: 15px;
    }
    
    .empresas-archive-header {
        padding: 40px 0 50px;
    }
    
    .search-input {
        padding: 14px 14px 14px 44px;
        font-size: 15px;
    }
    
    .search-input-wrapper .search-icon,
    .search-input-wrapper .location-icon {
        left: 14px;
        width: 18px;
        height: 18px;
    }
}
</style>

<?php
get_footer();

