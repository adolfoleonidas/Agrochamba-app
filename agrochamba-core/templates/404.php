<?php
/**
 * Template para página 404 (No encontrado)
 * Diseño moderno y útil para ayudar a los usuarios
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Obtener algunos trabajos recientes para mostrar
$recent_jobs = new WP_Query(array(
    'post_type' => 'trabajo',
    'posts_per_page' => 6,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
));

?>
<div class="error-404-wrapper">
    <div class="error-404-content">
        <!-- Ilustración y mensaje principal -->
        <div class="error-404-main">
            <div class="error-404-icon">
                <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            
            <h1 class="error-404-title">404</h1>
            <h2 class="error-404-subtitle">Página no encontrada</h2>
            <p class="error-404-description">
                Lo sentimos, la página que buscas no existe o ha sido movida. 
                Pero no te preocupes, aquí tienes algunas opciones para continuar.
            </p>
        </div>

        <!-- Acciones rápidas -->
        <div class="error-404-actions">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="error-action-btn error-action-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Ir al inicio
            </a>
            
            <a href="<?php echo esc_url(get_post_type_archive_link('trabajo')); ?>" class="error-action-btn error-action-secondary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                </svg>
                Ver trabajos
            </a>
            
            <a href="<?php echo esc_url(get_post_type_archive_link('empresa')); ?>" class="error-action-btn error-action-secondary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Ver empresas
            </a>
        </div>

        <!-- Búsqueda -->
        <div class="error-404-search">
            <h3 class="error-search-title">¿Buscas algo específico?</h3>
            <form class="error-search-form" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <input type="hidden" name="post_type" value="trabajo">
                <div class="error-search-input-wrapper">
                    <svg class="error-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input type="text" 
                           name="s" 
                           class="error-search-input" 
                           placeholder="Buscar trabajos, empresas..."
                           value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
                    <button type="submit" class="error-search-submit">
                        Buscar
                    </button>
                </div>
            </form>
        </div>

        <!-- Trabajos recientes -->
        <?php if ($recent_jobs->have_posts()): ?>
            <div class="error-404-recent">
                <h3 class="error-recent-title">Trabajos recientes</h3>
                <div class="error-recent-grid">
                    <?php while ($recent_jobs->have_posts()): $recent_jobs->the_post(); 
                        $trabajo_id = get_the_ID();
                        $salario_min = get_post_meta($trabajo_id, 'salario_min', true);
                        $salario_max = get_post_meta($trabajo_id, 'salario_max', true);
                        $ubicaciones = wp_get_post_terms($trabajo_id, 'ubicacion', array('fields' => 'names'));
                        $ubicacion = !empty($ubicaciones) ? $ubicaciones[0] : '';
                        
                        $salario_text = '';
                        if ($salario_min && $salario_max) {
                            $salario_text = 'S/ ' . number_format($salario_min, 0) . ' - S/ ' . number_format($salario_max, 0);
                        } elseif ($salario_min) {
                            $salario_text = 'S/ ' . number_format($salario_min, 0) . '+';
                        }
                        
                        $featured_image_id = get_post_thumbnail_id($trabajo_id);
                        $featured_image_url = $featured_image_id ? wp_get_attachment_image_url($featured_image_id, 'large') : null;
                    ?>
                        <article class="error-recent-card">
                            <a href="<?php the_permalink(); ?>" class="error-recent-link">
                                <?php if ($featured_image_url): ?>
                                    <div class="error-recent-image">
                                        <img src="<?php echo esc_url($featured_image_url); ?>" 
                                             alt="<?php echo esc_attr(get_the_title()); ?>"
                                             loading="lazy">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="error-recent-content">
                                    <h4 class="error-recent-title-card"><?php the_title(); ?></h4>
                                    
                                    <div class="error-recent-info">
                                        <?php if ($ubicacion): ?>
                                            <div class="error-recent-item">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                                    <circle cx="12" cy="10" r="3"/>
                                                </svg>
                                                <span><?php echo esc_html($ubicacion); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($salario_text): ?>
                                            <div class="error-recent-item">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                                </svg>
                                                <span><?php echo esc_html($salario_text); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.error-404-wrapper {
    background: #f5f5f5;
    min-height: 100vh;
    padding: 60px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.error-404-content {
    max-width: 900px;
    width: 100%;
    text-align: center;
}

.error-404-main {
    margin-bottom: 50px;
}

.error-404-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 30px;
    color: #1976D2;
    opacity: 0.8;
}

.error-404-title {
    font-size: 120px;
    font-weight: 700;
    margin: 0;
    color: #1976D2;
    line-height: 1;
    text-shadow: 0 4px 12px rgba(25, 118, 210, 0.2);
}

.error-404-subtitle {
    font-size: 36px;
    font-weight: 600;
    margin: 20px 0 16px;
    color: #1a1a1a;
}

.error-404-description {
    font-size: 18px;
    color: #666;
    line-height: 1.6;
    max-width: 600px;
    margin: 0 auto;
}

.error-404-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 50px;
}

.error-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    border: 2px solid transparent;
}

.error-action-primary {
    background: #1976D2;
    color: #fff;
}

.error-action-primary:hover {
    background: #1565C0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
}

.error-action-secondary {
    background: #fff;
    color: #1976D2;
    border-color: #1976D2;
}

.error-action-secondary:hover {
    background: #1976D2;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
}

.error-action-btn svg {
    flex-shrink: 0;
}

.error-404-search {
    margin-bottom: 50px;
    padding: 30px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.error-search-title {
    font-size: 24px;
    font-weight: 600;
    margin: 0 0 20px;
    color: #1a1a1a;
}

.error-search-form {
    max-width: 600px;
    margin: 0 auto;
}

.error-search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
}

.error-search-icon {
    position: absolute;
    left: 16px;
    color: #999;
    pointer-events: none;
    z-index: 1;
}

.error-search-input {
    flex: 1;
    padding: 16px 16px 16px 48px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 16px;
    background: #fff;
    color: #333;
    transition: all 0.3s;
    outline: none;
}

.error-search-input:focus {
    border-color: #1976D2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
}

.error-search-submit {
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

.error-search-submit:hover {
    background: #1565C0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
}

.error-404-recent {
    margin-top: 50px;
}

.error-recent-title {
    font-size: 28px;
    font-weight: 600;
    margin: 0 0 30px;
    color: #1a1a1a;
}

.error-recent-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.error-recent-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.error-recent-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.error-recent-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.error-recent-image {
    width: 100%;
    height: 150px;
    overflow: hidden;
    background: #f0f0f0;
}

.error-recent-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.error-recent-content {
    padding: 16px;
}

.error-recent-title-card {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 12px;
    color: #1a1a1a;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.error-recent-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.error-recent-item {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #666;
    font-size: 13px;
}

.error-recent-item svg {
    flex-shrink: 0;
    color: #1976D2;
}

@media (max-width: 768px) {
    .error-404-title {
        font-size: 80px;
    }
    
    .error-404-subtitle {
        font-size: 28px;
    }
    
    .error-404-description {
        font-size: 16px;
    }
    
    .error-404-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .error-action-btn {
        width: 100%;
        justify-content: center;
    }
    
    .error-search-input-wrapper {
        flex-direction: column;
    }
    
    .error-search-submit {
        width: 100%;
    }
    
    .error-recent-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .error-404-wrapper {
        padding: 40px 15px;
    }
    
    .error-404-title {
        font-size: 60px;
    }
    
    .error-404-subtitle {
        font-size: 24px;
    }
    
    .error-404-icon {
        width: 80px;
        height: 80px;
    }
}
</style>

<?php
get_footer();

