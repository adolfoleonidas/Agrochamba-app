<?php
/**
 * Template para mostrar el archivo/listado de trabajos
 * Diseño moderno similar a la app móvil
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Obtener información de la consulta
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$posts_per_page = get_option('posts_per_page', 12);

// Obtener filtros de URL
// Si estamos en una página de taxonomía, obtener el término desde la taxonomía
$ubicacion_filter = isset($_GET['ubicacion']) ? sanitize_text_field($_GET['ubicacion']) : '';
$cultivo_filter = isset($_GET['cultivo']) ? sanitize_text_field($_GET['cultivo']) : '';
$empresa_filter = isset($_GET['empresa']) ? sanitize_text_field($_GET['empresa']) : '';
$orderby_filter = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date'; // Por defecto: más recientes

// Si estamos en una página de taxonomía, obtener el slug del término
if (is_tax('ubicacion')) {
    $term = get_queried_object();
    if ($term && isset($term->slug)) {
        $ubicacion_filter = $term->slug;
    }
} elseif (is_tax('cultivo')) {
    $term = get_queried_object();
    if ($term && isset($term->slug)) {
        $cultivo_filter = $term->slug;
    }
} elseif (is_tax('empresa')) {
    $term = get_queried_object();
    if ($term && isset($term->slug)) {
        $empresa_filter = $term->slug;
    }
}

// Verificar si es un nuevo usuario
$show_welcome = isset($_GET['welcome']) && $_GET['welcome'] === '1';

// Verificar si hay filtros aplicados
$has_filters = !empty($ubicacion_filter) || !empty($cultivo_filter) || !empty($empresa_filter) || 
               (isset($_GET['s']) && !empty($_GET['s'])) || is_tax('ubicacion') || 
               is_tax('cultivo') || is_tax('empresa');

// Si estamos en /trabajos/ sin filtros, mostrar los últimos 3 trabajos
$show_recent_posts = false;
if (!$has_filters && is_post_type_archive('trabajo') && !is_tax()) {
    $show_recent_posts = true;
}
?>
<div class="trabajos-archive-wrapper">
    <?php if ($show_welcome && is_user_logged_in()): ?>
    <div class="welcome-message-banner">
        <div class="welcome-message-content">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <div>
                <strong>¡Bienvenido a AgroChamba!</strong>
                <span>Tu cuenta ha sido creada exitosamente. Explora las ofertas de trabajo disponibles.</span>
            </div>
            <button onclick="this.parentElement.parentElement.style.display='none'" class="welcome-close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    </div>
    <?php endif; ?>
    <!-- Header del Archivo -->
    <div class="trabajos-archive-header">
        <div class="archive-header-content">
            <!-- Botón de categoría -->
            <div class="archive-category-badge">
                <span>Trabajos</span>
            </div>
            
            <!-- Título principal -->
            <h1 class="archive-title">Encuentra tu próxima oportunidad</h1>
            <p class="archive-subtitle">Explora nuestro directorio completo de ofertas en el sector agroindustrial</p>
            
            <!-- Barra de búsqueda -->
            <form class="archive-search-form" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <input type="hidden" name="post_type" value="trabajo">
                <div class="search-input-group">
                    <div class="search-input-wrapper">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input type="text" 
                               name="s" 
                               id="search-input-field"
                               class="search-input" 
                               placeholder="Buscar por empresa, puesto o palabra clave"
                               value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
                        <button type="button" 
                                class="search-clear-btn" 
                                id="search-clear-btn"
                                onclick="clearSearchInput()"
                                style="<?php echo isset($_GET['s']) && $_GET['s'] !== '' ? 'display: flex;' : 'display: none;'; ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="search-input-wrapper location-wrapper">
                        <svg class="location-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <select name="ubicacion" id="ubicacion-select" class="search-input search-select" onchange="handleUbicacionChange(this)">
                            <option value="">Todas las ubicaciones</option>
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
                                        data-term-link="<?php echo esc_url(get_term_link($ubicacion)); ?>"
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
            
            <!-- Selector de Ordenamiento -->
            <div class="archive-sorting-controls">
                <form method="get" action="<?php echo esc_url(home_url('/')); ?>" class="sorting-form">
                    <input type="hidden" name="post_type" value="trabajo">
                    <?php if (!empty($ubicacion_filter)): ?>
                        <input type="hidden" name="ubicacion" value="<?php echo esc_attr($ubicacion_filter); ?>">
                    <?php endif; ?>
                    <?php if (!empty($_GET['s'])): ?>
                        <input type="hidden" name="s" value="<?php echo esc_attr($_GET['s']); ?>">
                    <?php endif; ?>
                    <div class="sorting-select-wrapper">
                        <svg class="sort-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 6h18M7 12h10M11 18h2"/>
                        </svg>
                        <select name="orderby" class="sorting-select" onchange="this.form.submit()" title="Ordenar trabajos">
                            <option value="smart" <?php selected($orderby_filter, 'smart'); ?>>⭐ Recomendado</option>
                            <option value="date" <?php selected($orderby_filter, 'date'); ?>>🕐 Más recientes</option>
                            <option value="relevance" <?php selected($orderby_filter, 'relevance'); ?>>🔥 Más relevantes</option>
                        </select>
                        <svg class="dropdown-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Grid de Trabajos -->
    <div class="trabajos-archive-content">
        <?php if ($show_recent_posts): ?>
            <!-- Landing page: Estadísticas, Trabajos recientes, luego pantalla de búsqueda -->
            
            <!-- Estadísticas Generales -->
            <?php
            // Obtener estadísticas
            $total_jobs = wp_count_posts('trabajo');
            $jobs_count = intval($total_jobs->publish ?? 0);
            
            $empresas_terms = get_terms(array(
                'taxonomy' => 'empresa',
                'hide_empty' => true,
                'fields' => 'count',
            ));
            $empresas_count = is_wp_error($empresas_terms) ? 0 : intval($empresas_terms);
            
            $ubicaciones_terms = get_terms(array(
                'taxonomy' => 'ubicacion',
                'hide_empty' => true,
                'fields' => 'count',
            ));
            $ubicaciones_count = is_wp_error($ubicaciones_terms) ? 0 : intval($ubicaciones_terms);
            
            ?>
            <div class="landing-stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($jobs_count); ?></div>
                            <div class="stat-label">Trabajos activos</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($empresas_count); ?></div>
                            <div class="stat-label">Empresas</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($ubicaciones_count); ?></div>
                            <div class="stat-label">Ubicaciones</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CTA para Empresas -->
            <?php if (!is_user_logged_in() || !current_user_can('publish_trabajos')): ?>
            <div class="landing-cta-section">
                <div class="cta-card">
                    <div class="cta-content">
                        <h3 class="cta-title">¿Eres una empresa?</h3>
                        <p class="cta-description">Publica tus ofertas de trabajo y encuentra el talento que necesitas</p>
                        <?php if (!is_user_logged_in()): ?>
                            <a href="<?php echo esc_url(home_url('/publicar-trabajo')); ?>" class="cta-button primary">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="8.5" cy="7" r="4"/>
                                    <line x1="20" y1="8" x2="20" y2="14"/>
                                    <line x1="23" y1="11" x2="17" y2="11"/>
                                </svg>
                                Regístrate como empresa
                            </a>
                        <?php else: ?>
                            <a href="<?php echo esc_url(home_url('/publicar-trabajo')); ?>" class="cta-button primary">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                Publicar trabajo
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="cta-icon">
                        <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Primero mostrar los últimos 3 trabajos -->
            <?php if (have_posts()): ?>
                <div class="recent-jobs-section">
                    <div class="recent-jobs-header">
                        <div class="recent-jobs-header-content">
                            <h2 class="recent-jobs-title">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                Trabajos recientes
                            </h2>
                            <p class="recent-jobs-subtitle">Las últimas oportunidades laborales publicadas</p>
                        </div>
                    </div>
                    <div class="trabajos-grid recent-jobs-grid">
                <?php while (have_posts()): the_post(); 
                    $trabajo_id = get_the_ID();
                    
                    // Obtener datos del trabajo
                    $salario_min = get_post_meta($trabajo_id, 'salario_min', true);
                    $salario_max = get_post_meta($trabajo_id, 'salario_max', true);
                    $vacantes = get_post_meta($trabajo_id, 'vacantes', true);
                    $alojamiento = get_post_meta($trabajo_id, 'alojamiento', true);
                    $transporte = get_post_meta($trabajo_id, 'transporte', true);
                    $alimentacion = get_post_meta($trabajo_id, 'alimentacion', true);
                    
                    // Obtener taxonomías
                    $ubicaciones = wp_get_post_terms($trabajo_id, 'ubicacion', array('fields' => 'names'));
                    $cultivos = wp_get_post_terms($trabajo_id, 'cultivo', array('fields' => 'names'));
                    $empresas = wp_get_post_terms($trabajo_id, 'empresa', array('fields' => 'names'));
                    
                    $ubicacion = !empty($ubicaciones) ? $ubicaciones[0] : '';
                    $cultivo = !empty($cultivos) ? $cultivos[0] : '';
                    $empresa = !empty($empresas) ? $empresas[0] : '';
                    
                    // Imagen destacada - usar tamaño large para mejor calidad
                    $featured_image_id = get_post_thumbnail_id($trabajo_id);
                    $featured_image_url = $featured_image_id ? wp_get_attachment_image_url($featured_image_id, 'large') : null;
                    $featured_image_srcset = $featured_image_id ? wp_get_attachment_image_srcset($featured_image_id, 'large') : null;
                    $featured_image_sizes = $featured_image_id ? wp_get_attachment_image_sizes($featured_image_id, 'large') : null;
                    
                    // Calcular salario
                    $salario_text = '';
                    if ($salario_min && $salario_max) {
                        $salario_text = 'S/ ' . number_format($salario_min, 0) . ' - S/ ' . number_format($salario_max, 0);
                    } elseif ($salario_min) {
                        $salario_text = 'S/ ' . number_format($salario_min, 0) . '+';
                    }
                    
                    // Determinar badge
                    $badge = null;
                    $badge_class = '';
                    $post_date = get_the_date('U');
                    $hours_since = (time() - $post_date) / 3600;
                    
                    if ($hours_since <= 48) {
                        $badge = 'Nuevo';
                        $badge_class = 'badge-new';
                    } elseif (($vacantes && intval($vacantes) >= 5) || ($salario_min && intval($salario_min) >= 3000)) {
                        $badge = 'Urgente';
                        $badge_class = 'badge-urgent';
                    } elseif ($alojamiento || $transporte || $alimentacion) {
                        $badge = 'Con beneficios';
                        $badge_class = 'badge-benefits';
                    } elseif ($salario_min && intval($salario_min) >= 2000) {
                        $badge = 'Buen salario';
                        $badge_class = 'badge-salary';
                    }
                    
                    // Excerpt
                    $excerpt = get_the_excerpt();
                    if (empty($excerpt)) {
                        $excerpt = wp_trim_words(get_the_content(), 20);
                    }
                    
                    // Obtener contadores
                    // Las vistas siempre deben ser el valor total almacenado en la BD, independiente de filtros
                    $views = get_post_meta($trabajo_id, '_trabajo_views', true);
                    $views_count = intval($views);
                    // Asegurar que siempre sea un número válido (mínimo 0)
                    if ($views_count < 0) {
                        $views_count = 0;
                    }
                    
                    // Contar favoritos (likes)
                    $favorites_count = 0;
                    $users = get_users(array('fields' => 'ID'));
                    foreach ($users as $user_id) {
                        $favorites = get_user_meta($user_id, 'favorite_jobs', true);
                        if (is_array($favorites) && in_array($trabajo_id, $favorites)) {
                            $favorites_count++;
                        }
                    }
                    
                    // Contar guardados
                    $saved_count = 0;
                    foreach ($users as $user_id) {
                        $saved = get_user_meta($user_id, 'saved_jobs', true);
                        if (is_array($saved) && in_array($trabajo_id, $saved)) {
                            $saved_count++;
                        }
                    }
                    
                    // Contar comentarios
                    $comments_count = get_comments_number($trabajo_id);
                    
                    // Contar compartidos
                    $shared_count = intval(get_post_meta($trabajo_id, '_trabajo_shared_count', true) ?: 0);
                    
                    // Estado del usuario actual
                    $is_favorite = false;
                    $is_saved = false;
                    if (is_user_logged_in()) {
                        $current_user_id = get_current_user_id();
                        $user_favorites = get_user_meta($current_user_id, 'favorite_jobs', true);
                        $user_saved = get_user_meta($current_user_id, 'saved_jobs', true);
                        
                        if (is_array($user_favorites) && in_array($trabajo_id, $user_favorites)) {
                            $is_favorite = true;
                        }
                        if (is_array($user_saved) && in_array($trabajo_id, $user_saved)) {
                            $is_saved = true;
                        }
                    }
                ?>
                    <article class="trabajo-card" data-job-id="<?php echo esc_attr($trabajo_id); ?>">
                        <a href="<?php the_permalink(); ?>" class="trabajo-card-link">
                            <?php if ($featured_image_url): ?>
                                <div class="trabajo-card-image">
                                    <img src="<?php echo esc_url($featured_image_url); ?>" 
                                         alt="<?php echo esc_attr(get_the_title()); ?>"
                                         <?php if ($featured_image_srcset): ?>
                                         srcset="<?php echo esc_attr($featured_image_srcset); ?>"
                                         <?php endif; ?>
                                         <?php if ($featured_image_sizes): ?>
                                         sizes="<?php echo esc_attr($featured_image_sizes); ?>"
                                         <?php endif; ?>
                                         loading="lazy">
                                    <?php if ($badge): ?>
                                        <span class="trabajo-badge <?php echo esc_attr($badge_class); ?>">
                                            <?php echo esc_html($badge); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="trabajo-card-image-placeholder">
                                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <polyline points="21 15 16 10 5 21"/>
                                    </svg>
                                    <?php if ($badge): ?>
                                        <span class="trabajo-badge <?php echo esc_attr($badge_class); ?>">
                                            <?php echo esc_html($badge); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="trabajo-card-content">
                                <h2 class="trabajo-card-title"><?php the_title(); ?></h2>
                                
                                <?php if ($empresa): ?>
                                    <div class="trabajo-card-empresa">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <span><?php echo esc_html($empresa); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="trabajo-card-info">
                                    <?php if ($ubicacion): ?>
                                        <div class="info-item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                                <circle cx="12" cy="10" r="3"/>
                                            </svg>
                                            <span><?php echo esc_html($ubicacion); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($salario_text): ?>
                                        <div class="info-item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="12" y1="1" x2="12" y2="23"/>
                                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                            </svg>
                                            <span><?php echo esc_html($salario_text); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($excerpt): ?>
                                    <p class="trabajo-card-excerpt"><?php echo esc_html(wp_trim_words($excerpt, 15)); ?></p>
                                <?php endif; ?>
                                
                                <div class="trabajo-card-footer">
                                    <span class="trabajo-card-date">
                                        <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' atrás'; ?>
                                    </span>
                                    <?php if ($vacantes && intval($vacantes) > 1): ?>
                                        <span class="trabajo-card-vacantes">
                                            <?php echo esc_html($vacantes); ?> vacantes
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Barra de interacciones estilo Facebook -->
                        <div class="trabajo-card-interactions">
                            <!-- Contadores -->
                            <div class="interaction-counters">
                                <div class="counter-group">
                                    <!-- Likes siempre visibles -->
                                    <span class="counter-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                        <span class="counter-value" data-counter="likes"><?php echo esc_html($favorites_count); ?></span>
                                    </span>
                                    <!-- Comentarios siempre visibles -->
                                    <span class="counter-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                        </svg>
                                        <span class="counter-value" data-counter="comments"><?php echo esc_html($comments_count); ?></span>
                                    </span>
                                    <!-- Compartidos siempre visibles -->
                                    <?php if ($shared_count > 0): ?>
                                    <span class="counter-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="18" cy="5" r="3"/>
                                            <circle cx="6" cy="12" r="3"/>
                                            <circle cx="18" cy="19" r="3"/>
                                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                                        </svg>
                                        <span class="counter-value" data-counter="shared"><?php echo esc_html($shared_count); ?></span>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <!-- Vistas siempre visibles (públicas) -->
                                <div class="counter-group">
                                    <span class="counter-item views-counter">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <span class="counter-value" data-counter="views"><?php echo esc_html($views_count); ?></span>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Botones de acción -->
                            <div class="interaction-buttons">
                                <?php if (is_user_logged_in()): ?>
                                    <button class="interaction-btn like-btn <?php echo $is_favorite ? 'active' : ''; ?>" 
                                            data-job-id="<?php echo esc_attr($trabajo_id); ?>"
                                            onclick="event.preventDefault(); toggleLike(<?php echo esc_js($trabajo_id); ?>, this);">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="<?php echo $is_favorite ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                        <span class="btn-text">Me gusta</span>
                                        <span class="btn-count" data-count="<?php echo esc_attr($favorites_count); ?>"><?php echo esc_html($favorites_count > 0 ? $favorites_count : ''); ?></span>
                                    </button>
                                    
                                    <a href="<?php echo esc_url(get_permalink($trabajo_id) . '#comments'); ?>" class="interaction-btn comment-btn">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                        </svg>
                                        <span class="btn-text">Comentar</span>
                                        <?php if ($comments_count > 0): ?>
                                            <span class="btn-count"><?php echo esc_html($comments_count); ?></span>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <button class="interaction-btn share-btn" 
                                            data-job-id="<?php echo esc_attr($trabajo_id); ?>"
                                            data-job-title="<?php echo esc_attr(get_the_title($trabajo_id)); ?>"
                                            data-job-url="<?php echo esc_url(get_permalink($trabajo_id)); ?>"
                                            onclick="event.preventDefault(); shareJob(<?php echo esc_js($trabajo_id); ?>, this);">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M15 14l4 -4l-4 -4" />
                                            <path d="M19 10h-11a4 4 0 1 0 0 8h1" />
                                        </svg>
                                        <span class="btn-text">Compartir</span>
                                    </button>
                                    
                                    <!-- Menú de tres puntos (solo para usuarios logueados) -->
                                    <div class="more-options-wrapper">
                                        <button class="interaction-btn more-options-btn" 
                                                onclick="event.preventDefault(); toggleMoreOptions(this);">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                                <circle cx="12" cy="5" r="2"/>
                                                <circle cx="12" cy="12" r="2"/>
                                                <circle cx="12" cy="19" r="2"/>
                                            </svg>
                                        </button>
                                        <div class="more-options-menu" style="display: none;">
                                            <button class="more-options-item save-btn-menu <?php echo $is_saved ? 'active' : ''; ?>" 
                                            data-job-id="<?php echo esc_attr($trabajo_id); ?>"
                                            onclick="event.preventDefault(); toggleSave(<?php echo esc_js($trabajo_id); ?>, this);">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $is_saved ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                                        </svg>
                                                <span><?php echo $is_saved ? 'Guardado' : 'Guardar'; ?></span>
                                    </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Usuario no logueado: redirigir a login -->
                                    <a href="<?php echo esc_url(wp_login_url(get_permalink($trabajo_id))); ?>" class="interaction-btn like-btn">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                        <span class="btn-text">Me gusta</span>
                                        <span class="btn-count" data-count="<?php echo esc_attr($favorites_count); ?>"><?php echo esc_html($favorites_count > 0 ? $favorites_count : ''); ?></span>
                                    </a>
                                    
                                    <a href="<?php echo esc_url(wp_login_url(get_permalink($trabajo_id) . '#comments')); ?>" class="interaction-btn comment-btn">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                        </svg>
                                        <span class="btn-text">Comentar</span>
                                        <?php if ($comments_count > 0): ?>
                                            <span class="btn-count"><?php echo esc_html($comments_count); ?></span>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <button class="interaction-btn share-btn" 
                                            data-job-id="<?php echo esc_attr($trabajo_id); ?>"
                                            data-job-title="<?php echo esc_attr(get_the_title($trabajo_id)); ?>"
                                            data-job-url="<?php echo esc_url(get_permalink($trabajo_id)); ?>"
                                            onclick="event.preventDefault(); shareJob(<?php echo esc_js($trabajo_id); ?>, this);">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M15 14l4 -4l-4 -4" />
                                            <path d="M19 10h-11a4 4 0 1 0 0 8h1" />
                                        </svg>
                                        <span class="btn-text">Compartir</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
                <?php else: ?>
                    <div class="trabajos-empty">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <h2>No se encontraron trabajos</h2>
                        <p>Intenta ajustar los filtros o vuelve más tarde</p>
                        <a href="<?php echo esc_url(get_post_type_archive_link('trabajo')); ?>" class="clear-filters-btn">Limpiar filtros</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Luego mostrar la pantalla de "Comienza tu búsqueda" -->
            <div class="no-filters-state">
                <div class="no-filters-content">
                    <div class="search-hero-section">
                        <div class="search-hero-icon-wrapper">
                            <svg width="140" height="140" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="no-filters-icon">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                                <line x1="11" y1="8" x2="11" y2="14"/>
                                <line x1="8" y1="11" x2="14" y2="11"/>
                            </svg>
                        </div>
                        <h2 class="no-filters-title">Comienza tu búsqueda</h2>
                        <p class="no-filters-description">
                            Selecciona una ubicación, busca por empresa o puesto, o explora nuestras ofertas disponibles.
                        </p>
                    </div>
                    
                    <div class="no-filters-suggestions">
                        <h3 class="suggestions-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            Ubicaciones populares
                        </h3>
                        <div class="popular-locations">
                            <?php
                            $popular_ubicaciones = get_terms(array(
                                'taxonomy' => 'ubicacion',
                                'hide_empty' => true,
                                'number' => 6,
                                'orderby' => 'count',
                                'order' => 'DESC',
                            ));
                            
                            if (!empty($popular_ubicaciones) && !is_wp_error($popular_ubicaciones)):
                                foreach ($popular_ubicaciones as $ubicacion):
                            ?>
                                <a href="<?php echo esc_url(get_term_link($ubicacion)); ?>" class="location-chip">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span class="location-name"><?php echo esc_html($ubicacion->name); ?></span>
                                    <span class="location-count"><?php echo esc_html($ubicacion->count); ?></span>
                                </a>
                            <?php 
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif (!$has_filters): ?>
            <!-- Estado inicial: Sin filtros aplicados (no debería aparecer ahora) -->
            <div class="no-filters-state">
                <div class="no-filters-content">
                    <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="no-filters-icon">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                        <line x1="11" y1="8" x2="11" y2="14"/>
                        <line x1="8" y1="11" x2="14" y2="11"/>
                    </svg>
                    <h2 class="no-filters-title">Comienza tu búsqueda</h2>
                    <p class="no-filters-description">
                        Selecciona una ubicación, busca por empresa o puesto, o explora nuestras ofertas disponibles.
                    </p>
                    <div class="no-filters-suggestions">
                        <h3>Ubicaciones populares:</h3>
                        <div class="popular-locations">
                            <?php
                            $popular_ubicaciones = get_terms(array(
                                'taxonomy' => 'ubicacion',
                                'hide_empty' => true,
                                'number' => 6,
                                'orderby' => 'count',
                                'order' => 'DESC',
                            ));
                            
                            if (!empty($popular_ubicaciones) && !is_wp_error($popular_ubicaciones)):
                                foreach ($popular_ubicaciones as $ubicacion):
                            ?>
                                <a href="<?php echo esc_url(get_term_link($ubicacion)); ?>" class="location-chip">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <?php echo esc_html($ubicacion->name); ?>
                                    <span class="location-count"><?php echo esc_html($ubicacion->count); ?></span>
                                </a>
                            <?php 
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Estado con filtros: Mostrar resultados -->
            <div class="trabajos-grid">
                <?php if (have_posts()): ?>
                <?php while (have_posts()): the_post(); 
                    $trabajo_id = get_the_ID();
                    
                    // Obtener datos del trabajo
                    $salario_min = get_post_meta($trabajo_id, 'salario_min', true);
                    $salario_max = get_post_meta($trabajo_id, 'salario_max', true);
                    $vacantes = get_post_meta($trabajo_id, 'vacantes', true);
                    $alojamiento = get_post_meta($trabajo_id, 'alojamiento', true);
                    $transporte = get_post_meta($trabajo_id, 'transporte', true);
                    $alimentacion = get_post_meta($trabajo_id, 'alimentacion', true);
                    
                    // Obtener taxonomías
                    $ubicaciones = wp_get_post_terms($trabajo_id, 'ubicacion', array('fields' => 'names'));
                    $cultivos = wp_get_post_terms($trabajo_id, 'cultivo', array('fields' => 'names'));
                    $empresas = wp_get_post_terms($trabajo_id, 'empresa', array('fields' => 'names'));
                    
                    $ubicacion = !empty($ubicaciones) ? $ubicaciones[0] : '';
                    $cultivo = !empty($cultivos) ? $cultivos[0] : '';
                    $empresa = !empty($empresas) ? $empresas[0] : '';
                    
                    // Imagen destacada - usar tamaño large para mejor calidad
                    $featured_image_id = get_post_thumbnail_id($trabajo_id);
                    $featured_image_url = $featured_image_id ? wp_get_attachment_image_url($featured_image_id, 'large') : null;
                    $featured_image_srcset = $featured_image_id ? wp_get_attachment_image_srcset($featured_image_id, 'large') : null;
                    $featured_image_sizes = $featured_image_id ? wp_get_attachment_image_sizes($featured_image_id, 'large') : null;
                    
                    // Calcular salario
                    $salario_text = '';
                    if ($salario_min && $salario_max) {
                        $salario_text = 'S/ ' . number_format($salario_min, 0) . ' - S/ ' . number_format($salario_max, 0);
                    } elseif ($salario_min) {
                        $salario_text = 'S/ ' . number_format($salario_min, 0) . '+';
                    }
                    
                    // Determinar badge
                    $badge = null;
                    $badge_class = '';
                    $post_date = get_the_date('U');
                    $hours_since = (time() - $post_date) / 3600;
                    
                    if ($hours_since <= 48) {
                        $badge = 'Nuevo';
                        $badge_class = 'badge-new';
                    } elseif (($vacantes && intval($vacantes) >= 5) || ($salario_min && intval($salario_min) >= 3000)) {
                        $badge = 'Urgente';
                        $badge_class = 'badge-urgent';
                    } elseif ($alojamiento || $transporte || $alimentacion) {
                        $badge = 'Con beneficios';
                        $badge_class = 'badge-benefits';
                    } elseif ($salario_min && intval($salario_min) >= 2000) {
                        $badge = 'Buen salario';
                        $badge_class = 'badge-salary';
                    }
                    
                    // Excerpt
                    $excerpt = get_the_excerpt();
                    if (empty($excerpt)) {
                        $excerpt = wp_trim_words(get_the_content(), 20);
                    }
                    
                    // Obtener contadores
                    // Las vistas siempre deben ser el valor total almacenado en la BD, independiente de filtros
                    $views = get_post_meta($trabajo_id, '_trabajo_views', true);
                    $views_count = intval($views);
                    // Asegurar que siempre sea un número válido (mínimo 0)
                    if ($views_count < 0) {
                        $views_count = 0;
                    }
                    
                    // Contar favoritos (likes)
                    $favorites_count = 0;
                    $users = get_users(array('fields' => 'ID'));
                    foreach ($users as $user_id) {
                        $favorites = get_user_meta($user_id, 'favorite_jobs', true);
                        if (is_array($favorites) && in_array($trabajo_id, $favorites)) {
                            $favorites_count++;
                        }
                    }
                    
                    // Contar guardados
                    $saved_count = 0;
                    foreach ($users as $user_id) {
                        $saved = get_user_meta($user_id, 'saved_jobs', true);
                        if (is_array($saved) && in_array($trabajo_id, $saved)) {
                            $saved_count++;
                        }
                    }
                    
                    // Contar comentarios
                    $comments_count = get_comments_number($trabajo_id);
                    
                    // Contar compartidos
                    $shared_count = intval(get_post_meta($trabajo_id, '_trabajo_shared_count', true) ?: 0);
                    
                    // Estado del usuario actual
                    $is_favorite = false;
                    $is_saved = false;
                    if (is_user_logged_in()) {
                        $current_user_id = get_current_user_id();
                        $user_favorites = get_user_meta($current_user_id, 'favorite_jobs', true);
                        $user_saved = get_user_meta($current_user_id, 'saved_jobs', true);
                        
                        if (is_array($user_favorites) && in_array($trabajo_id, $user_favorites)) {
                            $is_favorite = true;
                        }
                        if (is_array($user_saved) && in_array($trabajo_id, $user_saved)) {
                            $is_saved = true;
                        }
                    }
                ?>
                    <article class="trabajo-card" data-job-id="<?php echo esc_attr($trabajo_id); ?>">
                        <a href="<?php the_permalink(); ?>" class="trabajo-card-link">
                            <?php if ($featured_image_url): ?>
                                <div class="trabajo-card-image">
                                    <img src="<?php echo esc_url($featured_image_url); ?>" 
                                         alt="<?php echo esc_attr(get_the_title()); ?>"
                                         <?php if ($featured_image_srcset): ?>
                                         srcset="<?php echo esc_attr($featured_image_srcset); ?>"
                                         <?php endif; ?>
                                         <?php if ($featured_image_sizes): ?>
                                         sizes="<?php echo esc_attr($featured_image_sizes); ?>"
                                         <?php endif; ?>
                                         loading="lazy">
                                    <?php if ($badge): ?>
                                        <span class="trabajo-badge <?php echo esc_attr($badge_class); ?>">
                                            <?php echo esc_html($badge); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="trabajo-card-image-placeholder">
                                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <polyline points="21 15 16 10 5 21"/>
                                    </svg>
                                    <?php if ($badge): ?>
                                        <span class="trabajo-badge <?php echo esc_attr($badge_class); ?>">
                                            <?php echo esc_html($badge); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="trabajo-card-content">
                                <h2 class="trabajo-card-title"><?php the_title(); ?></h2>
                                
                                <?php if ($empresa): ?>
                                    <div class="trabajo-card-empresa">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <span><?php echo esc_html($empresa); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="trabajo-card-info">
                                    <?php if ($ubicacion): ?>
                                        <div class="info-item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                                <circle cx="12" cy="10" r="3"/>
                                            </svg>
                                            <span><?php echo esc_html($ubicacion); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($salario_text): ?>
                                        <div class="info-item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="12" y1="1" x2="12" y2="23"/>
                                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                            </svg>
                                            <span><?php echo esc_html($salario_text); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($excerpt): ?>
                                    <p class="trabajo-card-excerpt"><?php echo esc_html(wp_trim_words($excerpt, 15)); ?></p>
                                <?php endif; ?>
                                
                                <div class="trabajo-card-footer">
                                    <span class="trabajo-card-date">
                                        <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' atrás'; ?>
                                    </span>
                                    <?php if ($vacantes && intval($vacantes) > 1): ?>
                                        <span class="trabajo-card-vacantes">
                                            <?php echo esc_html($vacantes); ?> vacantes
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Barra de interacciones estilo Facebook -->
                        <div class="trabajo-card-interactions">
                            <!-- Contadores -->
                            <div class="interaction-counters">
                                <div class="counter-group">
                                    <!-- Likes siempre visibles -->
                                    <span class="counter-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                        <span class="counter-value" data-counter="likes"><?php echo esc_html($favorites_count); ?></span>
                                    </span>
                                    <!-- Comentarios siempre visibles -->
                                    <span class="counter-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                        </svg>
                                        <span class="counter-value" data-counter="comments"><?php echo esc_html($comments_count); ?></span>
                                    </span>
                                    <!-- Compartidos siempre visibles -->
                                    <?php if ($shared_count > 0): ?>
                                    <span class="counter-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="18" cy="5" r="3"/>
                                            <circle cx="6" cy="12" r="3"/>
                                            <circle cx="18" cy="19" r="3"/>
                                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                                        </svg>
                                        <span class="counter-value" data-counter="shared"><?php echo esc_html($shared_count); ?></span>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <!-- Vistas siempre visibles (públicas) -->
                                <div class="counter-group">
                                    <span class="counter-item views-counter">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <span class="counter-value" data-counter="views"><?php echo esc_html($views_count); ?></span>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Botones de acción -->
                            <div class="interaction-buttons">
                                <?php if (is_user_logged_in()): ?>
                                    <button class="interaction-btn like-btn <?php echo $is_favorite ? 'active' : ''; ?>" 
                                            data-job-id="<?php echo esc_attr($trabajo_id); ?>"
                                            onclick="event.preventDefault(); toggleLike(<?php echo esc_js($trabajo_id); ?>, this);">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="<?php echo $is_favorite ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                        <span class="btn-text">Me gusta</span>
                                        <span class="btn-count" data-count="<?php echo esc_attr($favorites_count); ?>"><?php echo esc_html($favorites_count > 0 ? $favorites_count : ''); ?></span>
                                    </button>
                                    
                                    <a href="<?php echo esc_url(get_permalink($trabajo_id) . '#comments'); ?>" class="interaction-btn comment-btn">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                        </svg>
                                        <span class="btn-text">Comentar</span>
                                        <?php if ($comments_count > 0): ?>
                                            <span class="btn-count"><?php echo esc_html($comments_count); ?></span>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <button class="interaction-btn share-btn" 
                                            data-job-id="<?php echo esc_attr($trabajo_id); ?>"
                                            data-job-title="<?php echo esc_attr(get_the_title($trabajo_id)); ?>"
                                            data-job-url="<?php echo esc_url(get_permalink($trabajo_id)); ?>"
                                            onclick="event.preventDefault(); shareJob(<?php echo esc_js($trabajo_id); ?>, this);">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M15 14l4 -4l-4 -4" />
                                            <path d="M19 10h-11a4 4 0 1 0 0 8h1" />
                                        </svg>
                                        <span class="btn-text">Compartir</span>
                                    </button>
                                    
                                    <!-- Menú de tres puntos (solo para usuarios logueados) -->
                                    <div class="more-options-wrapper">
                                        <button class="interaction-btn more-options-btn" 
                                                onclick="event.preventDefault(); toggleMoreOptions(this);">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                                <circle cx="12" cy="5" r="2"/>
                                                <circle cx="12" cy="12" r="2"/>
                                                <circle cx="12" cy="19" r="2"/>
                                            </svg>
                                        </button>
                                        <div class="more-options-menu" style="display: none;">
                                            <button class="more-options-item save-btn-menu <?php echo $is_saved ? 'active' : ''; ?>" 
                                            data-job-id="<?php echo esc_attr($trabajo_id); ?>"
                                            onclick="event.preventDefault(); toggleSave(<?php echo esc_js($trabajo_id); ?>, this);">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $is_saved ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                                        </svg>
                                                <span><?php echo $is_saved ? 'Guardado' : 'Guardar'; ?></span>
                                    </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Usuario no logueado: redirigir a login -->
                                    <a href="<?php echo esc_url(wp_login_url(get_permalink($trabajo_id))); ?>" class="interaction-btn like-btn">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                        <span class="btn-text">Me gusta</span>
                                        <span class="btn-count" data-count="<?php echo esc_attr($favorites_count); ?>"><?php echo esc_html($favorites_count > 0 ? $favorites_count : ''); ?></span>
                                    </a>
                                    
                                    <a href="<?php echo esc_url(wp_login_url(get_permalink($trabajo_id) . '#comments')); ?>" class="interaction-btn comment-btn">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                        </svg>
                                        <span class="btn-text">Comentar</span>
                                        <?php if ($comments_count > 0): ?>
                                            <span class="btn-count"><?php echo esc_html($comments_count); ?></span>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <button class="interaction-btn share-btn" 
                                            data-job-id="<?php echo esc_attr($trabajo_id); ?>"
                                            data-job-title="<?php echo esc_attr(get_the_title($trabajo_id)); ?>"
                                            data-job-url="<?php echo esc_url(get_permalink($trabajo_id)); ?>"
                                            onclick="event.preventDefault(); shareJob(<?php echo esc_js($trabajo_id); ?>, this);">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M15 14l4 -4l-4 -4" />
                                            <path d="M19 10h-11a4 4 0 1 0 0 8h1" />
                                        </svg>
                                        <span class="btn-text">Compartir</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="trabajos-empty">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <h2>No se encontraron trabajos</h2>
                    <p>Intenta ajustar los filtros o vuelve más tarde</p>
                        <a href="<?php echo esc_url(get_post_type_archive_link('trabajo')); ?>" class="clear-filters-btn">
                            Ver todos los trabajos
                        </a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Paginación -->
                <?php
        // Preservar parámetros GET en la paginación
        $pagination_args = array(
                    'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> Anterior',
                    'next_text' => 'Siguiente <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>',
                    'type' => 'list',
        );
        
        // Detectar si estamos en una página de taxonomía (ubicación, cultivo, empresa)
        $is_taxonomy_page = is_tax('ubicacion') || is_tax('cultivo') || is_tax('empresa');
        
        // Agregar parámetros GET actuales a la paginación
        // Esto asegura que los filtros se preserven al navegar entre páginas
        $current_url_params = array();
        
        // Solo incluir post_type si NO estamos en una página de taxonomía
        // Las páginas de taxonomía ya tienen la estructura correcta de URL
        if (!$is_taxonomy_page) {
            $current_url_params['post_type'] = 'trabajo';
        }
        
        // Incluir parámetros solo si tienen valor (no vacíos) y NO son parte de la estructura de URL de taxonomía
        if (isset($_GET['ubicacion']) && $_GET['ubicacion'] !== '' && !is_tax('ubicacion')) {
            $current_url_params['ubicacion'] = sanitize_text_field($_GET['ubicacion']);
        }
        if (isset($_GET['cultivo']) && $_GET['cultivo'] !== '' && !is_tax('cultivo')) {
            $current_url_params['cultivo'] = sanitize_text_field($_GET['cultivo']);
        }
        if (isset($_GET['empresa']) && $_GET['empresa'] !== '' && !is_tax('empresa')) {
            $current_url_params['empresa'] = sanitize_text_field($_GET['empresa']);
        }
        if (isset($_GET['s']) && $_GET['s'] !== '') {
            $current_url_params['s'] = sanitize_text_field($_GET['s']);
        }
        if (isset($_GET['orderby']) && $_GET['orderby'] !== '') {
            $current_url_params['orderby'] = sanitize_text_field($_GET['orderby']);
        }
        
        // Usar base URL correcta para la paginación
        if (is_post_type_archive('trabajo')) {
            // Archivo principal de trabajos
            $pagination_base = get_post_type_archive_link('trabajo');
            // Remover parámetros de página si existen en la URL base
            $pagination_base = remove_query_arg('paged', $pagination_base);
            $pagination_args['base'] = $pagination_base . '%_%';
        } elseif ($is_taxonomy_page) {
            // Página de taxonomía: usar la URL de la taxonomía directamente
            $term = get_queried_object();
            if ($term && isset($term->taxonomy)) {
                $taxonomy_link = get_term_link($term);
                if (!is_wp_error($taxonomy_link)) {
                    // Remover parámetros de página si existen
                    $taxonomy_link = remove_query_arg('paged', $taxonomy_link);
                    // Usar la estructura de paginación de WordPress para taxonomías
                    $pagination_args['base'] = $taxonomy_link . '%_%';
                    // NO agregar post_type como parámetro GET en taxonomías
                    $pagination_args['add_args'] = array();
                } else {
                    // Fallback: usar URL actual
                    $current_url = remove_query_arg('paged');
                    $pagination_args['base'] = $current_url . '%_%';
                    $pagination_args['add_args'] = array();
                }
            } else {
                // Fallback: usar URL actual
                $current_url = remove_query_arg('paged');
                $pagination_args['base'] = $current_url . '%_%';
                $pagination_args['add_args'] = array();
            }
        } else {
            // Para búsquedas, usar la URL actual sin paged
            $current_url = remove_query_arg('paged');
            $pagination_args['base'] = $current_url . '%_%';
            // Agregar parámetros a la paginación
            $pagination_args['add_args'] = $current_url_params;
        }
        
        // Solo agregar parámetros si no es una página de taxonomía
        if (!$is_taxonomy_page && !isset($pagination_args['add_args'])) {
            $pagination_args['add_args'] = $current_url_params;
        }
        
        // Obtener información de paginación para el botón "Cargar más"
        global $wp_query;
        $max_pages = $wp_query->max_num_pages;
        $current_page = max(1, get_query_var('paged'));
        
        // La paginación y el botón "Cargar más" se muestran después del grid, solo si hay filtros
        if ($has_filters):
            if ($current_page < $max_pages): ?>
                <div class="load-more-wrapper" style="text-align: center; margin-top: 30px;">
                    <button id="load-more-btn" class="load-more-btn" 
                            data-current-page="<?php echo esc_attr($current_page); ?>"
                            data-max-pages="<?php echo esc_attr($max_pages); ?>"
                            data-ubicacion="<?php echo esc_attr($ubicacion_filter); ?>"
                            data-cultivo="<?php echo esc_attr($cultivo_filter); ?>"
                            data-empresa="<?php echo esc_attr($empresa_filter); ?>"
                            data-search="<?php echo esc_attr(isset($_GET['s']) ? sanitize_text_field($_GET['s']) : ''); ?>"
                            data-orderby="<?php echo esc_attr($orderby_filter); ?>">
                        <span class="load-more-text">Cargar más trabajos</span>
                        <span class="load-more-spinner" style="display: none;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                            </svg>
                        </span>
                    </button>
            </div>
        <?php endif; ?>
            
            <!-- Paginación (solo si hay filtros) -->
            <?php if (paginate_links($pagination_args)): ?>
                <div class="trabajos-pagination">
                    <?php echo paginate_links($pagination_args); ?>
                </div>
            <?php endif;
        endif; ?>
    </div>
</div>

<style>
/* Mensaje de Bienvenida */
.welcome-message-banner {
    background: linear-gradient(135deg, #2d5016 0%, #3d6b1f 100%);
    padding: 0;
    position: relative;
    animation: slideDown 0.5s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.welcome-message-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    color: #fff;
}

.welcome-message-content > svg {
    flex-shrink: 0;
    color: #a5d6a7;
}

.welcome-message-content > div {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.welcome-message-content strong {
    font-size: 16px;
    font-weight: 700;
    display: block;
}

.welcome-message-content span {
    font-size: 14px;
    opacity: 0.95;
}

.welcome-close {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    color: #fff;
    transition: all 0.2s;
    flex-shrink: 0;
}

.welcome-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

@media (max-width: 768px) {
    .welcome-message-content {
        padding: 16px;
        gap: 12px;
    }
    
    .welcome-message-content strong {
        font-size: 14px;
    }
    
    .welcome-message-content span {
        font-size: 13px;
    }
}

.trabajos-archive-wrapper {
    background: #f5f5f5;
    min-height: 100vh;
    padding-bottom: 40px;
}

.trabajos-archive-header {
    background: #fff;
    padding: 60px 0 80px;
    margin-bottom: 40px;
    border-bottom: 1px solid #f0f0f0;
    position: relative;
    overflow: hidden;
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
    background: #E8F5E9;
    color: #2E7D32;
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
    padding: 16px 40px 16px 48px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 16px;
    background: #fff;
    color: #333;
    transition: all 0.3s;
    outline: none;
}

.search-clear-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    transition: color 0.2s;
    z-index: 2;
    border-radius: 50%;
    width: 24px;
    height: 24px;
}

.search-clear-btn:hover {
    background: #f0f0f0;
    color: #333;
}

.search-clear-btn svg {
    width: 16px;
    height: 16px;
}

.search-input:focus {
    border-color: #4CAF50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
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
    background: #4CAF50;
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
    background: #45a049;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.search-submit-btn svg {
    flex-shrink: 0;
}

/* Selector de Ordenamiento */
.archive-sorting-controls {
    margin-top: 24px;
    display: flex;
    justify-content: center;
}

.sorting-form {
    margin: 0;
}

.sorting-select-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 0;
    transition: all 0.3s;
}

.sorting-select-wrapper:hover {
    border-color: #4CAF50;
}

.sorting-select-wrapper:focus-within {
    border-color: #4CAF50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.sort-icon {
    margin-left: 12px;
    color: #666;
    flex-shrink: 0;
}

.sorting-select {
    appearance: none;
    border: none;
    background: transparent;
    padding: 12px 40px 12px 8px;
    font-size: 15px;
    font-weight: 500;
    color: #333;
    cursor: pointer;
    outline: none;
    min-width: 180px;
}

.sorting-select-wrapper .dropdown-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    pointer-events: none;
    z-index: 1;
}

@media (max-width: 768px) {
    .archive-sorting-controls {
        margin-top: 20px;
    }
    
    .sorting-select {
        min-width: 160px;
        font-size: 14px;
        padding: 10px 36px 10px 8px;
    }
    
    .sort-icon {
        margin-left: 10px;
        width: 16px;
        height: 16px;
    }
}

.trabajos-archive-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.trabajos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.trabajo-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.trabajo-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.trabajo-card-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.trabajo-card-image {
    position: relative;
    width: 100%;
    height: 200px;
    overflow: hidden;
    background: #f0f0f0;
}

.trabajo-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.trabajo-card:hover .trabajo-card-image img {
    transform: scale(1.05);
}

.trabajo-card-image-placeholder {
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    position: relative;
}

.trabajo-badge {
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

.badge-urgent {
    background: #FFEBEE;
    color: #D32F2F;
}

.badge-benefits {
    background: #E8F5E9;
    color: #2E7D32;
}

.badge-salary {
    background: #FFF9C4;
    color: #F57F17;
}

.trabajo-card-content {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.trabajo-card-title {
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

.trabajo-card-empresa {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #0066cc;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 12px;
}

.trabajo-card-empresa svg {
    flex-shrink: 0;
}

.trabajo-card-info {
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

.trabajo-card-excerpt {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
    margin: 0 0 16px 0;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.trabajo-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
    font-size: 12px;
    color: #999;
}

.trabajo-card-vacantes {
    background: #f0f0f0;
    padding: 4px 10px;
    border-radius: 12px;
    font-weight: 500;
    color: #666;
}

.trabajos-empty {
    text-align: center;
    padding: 80px 20px;
    color: #999;
}

.trabajos-empty svg {
    margin-bottom: 20px;
    opacity: 0.5;
}

.trabajos-empty h2 {
    font-size: 24px;
    margin: 0 0 10px 0;
    color: #666;
}

.trabajos-empty p {
    font-size: 16px;
    margin: 0;
}

.clear-filters-btn {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 24px;
    background: #4CAF50;
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
}

.clear-filters-btn:hover {
    background: #45a049;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

/* Estadísticas Generales */
.landing-stats-section {
    margin-bottom: 60px;
    padding: 0 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 24px;
    max-width: 1200px;
    margin: 0 auto;
}

.stat-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 16px;
    padding: 32px 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(76, 175, 80, 0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, #4CAF50 0%, #45a049 100%);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(76, 175, 80, 0.15);
}

.stat-icon {
    flex-shrink: 0;
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4CAF50;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 36px;
    font-weight: 800;
    color: #1a237e;
    line-height: 1;
    margin-bottom: 4px;
    background: linear-gradient(135deg, #1a237e 0%, #4CAF50 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label {
    font-size: 14px;
    color: #666;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* CTA para Empresas */
.landing-cta-section {
    margin-bottom: 60px;
    padding: 0 20px;
}

.cta-card {
    max-width: 1000px;
    margin: 0 auto;
    background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
    border-radius: 20px;
    padding: 50px 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 40px;
    box-shadow: 0 8px 32px rgba(26, 35, 126, 0.3);
    position: relative;
    overflow: hidden;
}

.cta-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(76, 175, 80, 0.1) 0%, transparent 70%);
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.cta-content {
    flex: 1;
    position: relative;
    z-index: 1;
}

.cta-title {
    font-size: 32px;
    font-weight: 800;
    color: #fff;
    margin: 0 0 12px 0;
    line-height: 1.2;
}

.cta-description {
    font-size: 18px;
    color: rgba(255, 255, 255, 0.9);
    margin: 0 0 24px 0;
    line-height: 1.6;
}

.cta-button {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 32px;
    background: #4CAF50;
    color: #fff;
    text-decoration: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
    border: none;
    cursor: pointer;
}

.cta-button:hover {
    background: #45a049;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(76, 175, 80, 0.5);
    color: #fff;
}

.cta-button svg {
    flex-shrink: 0;
}

.cta-icon {
    flex-shrink: 0;
    color: rgba(255, 255, 255, 0.1);
    position: relative;
    z-index: 1;
}

/* Filtros Rápidos */
.quick-filters-section {
    margin-top: 50px;
    padding-top: 50px;
    border-top: 2px solid #e8f5e9;
}

.quick-filters-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    justify-content: center;
    align-items: center;
}

.quick-filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 24px;
    background: #fff;
    border: 2px solid #e0e0e0;
    border-radius: 30px;
    text-decoration: none;
    color: #333;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    position: relative;
    overflow: hidden;
}

.quick-filter-chip::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.quick-filter-chip:hover::before {
    left: 100%;
}

.quick-filter-chip svg {
    flex-shrink: 0;
    transition: transform 0.3s;
}

.quick-filter-chip:hover svg {
    transform: scale(1.1);
}

.quick-filter-chip.benefits-filter:hover {
    background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
    border-color: #4CAF50;
    color: #2E7D32;
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.3);
}

.quick-filter-chip.salary-filter:hover {
    background: linear-gradient(135deg, #FFF9C4 0%, #FFF59D 100%);
    border-color: #F57F17;
    color: #F57F17;
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(245, 127, 23, 0.3);
}

.quick-filter-chip.urgent-filter:hover {
    background: linear-gradient(135deg, #FFEBEE 0%, #FFCDD2 100%);
    border-color: #D32F2F;
    color: #D32F2F;
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(211, 47, 47, 0.3);
}

.quick-filter-chip.new-filter:hover {
    background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
    border-color: #1976D2;
    color: #1976D2;
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(25, 118, 210, 0.3);
}

.cultivo-chip {
    background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%);
    border-color: #FF9800;
}

.cultivo-chip:hover {
    background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
    border-color: #FF9800;
    color: #fff;
}

/* Sección de Trabajos Recientes */
.recent-jobs-section {
    margin-bottom: 80px;
}

.recent-jobs-header {
    margin-bottom: 40px;
    text-align: center;
}

.recent-jobs-header-content {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.recent-jobs-title {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    font-size: 42px;
    font-weight: 800;
    color: #1a237e;
    margin: 0 0 12px 0;
    line-height: 1.2;
}

.recent-jobs-title svg {
    color: #4CAF50;
    flex-shrink: 0;
}

.recent-jobs-subtitle {
    font-size: 18px;
    color: #666;
    margin: 0;
    font-weight: 400;
}

.recent-jobs-grid {
    margin-top: 40px;
}

/* Estado inicial: Sin filtros - Landing Page Mejorada */
.no-filters-state {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 20px 80px;
}

.no-filters-content {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 24px;
    padding: 80px 60px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(76, 175, 80, 0.1);
    position: relative;
    overflow: hidden;
}

.no-filters-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #4CAF50 0%, #45a049 50%, #4CAF50 100%);
}

.search-hero-section {
    text-align: center;
    margin-bottom: 60px;
}

.search-hero-icon-wrapper {
    margin-bottom: 32px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.no-filters-icon {
    color: #4CAF50;
    opacity: 1;
    filter: drop-shadow(0 4px 12px rgba(76, 175, 80, 0.2));
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.9;
    }
}

.no-filters-title {
    font-size: 42px;
    font-weight: 800;
    color: #1a237e;
    margin: 0 0 20px 0;
    line-height: 1.2;
    background: linear-gradient(135deg, #1a237e 0%, #4CAF50 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.no-filters-description {
    font-size: 20px;
    color: #555;
    line-height: 1.7;
    margin: 0;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
    font-weight: 400;
}

.no-filters-suggestions {
    margin-top: 60px;
    padding-top: 50px;
    border-top: 2px solid #e8f5e9;
}

.suggestions-title {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 24px;
    font-weight: 700;
    color: #1a237e;
    margin: 0 0 32px 0;
    text-align: center;
}

.suggestions-title svg {
    color: #4CAF50;
    flex-shrink: 0;
}

.popular-locations {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    justify-content: center;
    align-items: center;
}

.location-chip {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 24px;
    background: #fff;
    border: 2px solid #e0e0e0;
    border-radius: 30px;
    text-decoration: none;
    color: #333;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    position: relative;
    overflow: hidden;
}

.location-chip::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.location-chip:hover::before {
    left: 100%;
}

.location-chip:hover {
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    border-color: #4CAF50;
    color: #fff;
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 8px 24px rgba(76, 175, 80, 0.4);
}

.location-chip svg {
    flex-shrink: 0;
    transition: transform 0.3s;
}

.location-chip:hover svg {
    transform: scale(1.1);
}

.location-name {
    font-weight: 600;
}

.location-count {
    background: rgba(0, 0, 0, 0.08);
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 700;
    min-width: 32px;
    text-align: center;
    transition: all 0.3s;
}

.location-chip:hover .location-count {
    background: rgba(255, 255, 255, 0.25);
    color: #fff;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    
    .stat-card {
        padding: 24px 20px;
        flex-direction: column;
        text-align: center;
        gap: 16px;
    }
    
    .stat-icon {
        width: 56px;
        height: 56px;
    }
    
    .stat-number {
        font-size: 28px;
    }
    
    .stat-label {
        font-size: 12px;
    }
    
    .cta-card {
        flex-direction: column;
        padding: 40px 30px;
        text-align: center;
    }
    
    .cta-title {
        font-size: 24px;
    }
    
    .cta-description {
        font-size: 16px;
    }
    
    .cta-icon {
        width: 80px;
        height: 80px;
    }
    
    .quick-filters-grid {
        gap: 12px;
    }
    
    .quick-filter-chip {
        font-size: 14px;
        padding: 12px 20px;
    }
    
    .recent-jobs-title {
        font-size: 32px;
        flex-direction: column;
        gap: 8px;
    }
    
    .recent-jobs-title svg {
        width: 28px;
        height: 28px;
    }
    
    .recent-jobs-subtitle {
        font-size: 16px;
    }
    
    .recent-jobs-section {
        margin-bottom: 60px;
    }
    
    .no-filters-content {
        padding: 50px 30px;
        border-radius: 20px;
    }
    
    .no-filters-title {
        font-size: 32px;
    }
    
    .no-filters-description {
        font-size: 17px;
    }
    
    .no-filters-icon {
        width: 100px;
        height: 100px;
    }
    
    .suggestions-title {
        font-size: 20px;
        flex-direction: column;
        gap: 8px;
    }
    
    .location-chip {
        font-size: 15px;
        padding: 14px 20px;
    }
    
    .popular-locations {
        gap: 12px;
    }
    
    .no-filters-suggestions {
        margin-top: 50px;
        padding-top: 40px;
    }
}

.trabajos-pagination {
    margin-top: 40px;
}

.trabajos-pagination .page-numbers {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    list-style: none;
    padding: 0;
    margin: 0;
    flex-wrap: wrap;
}

.trabajos-pagination .page-numbers li {
    margin: 0;
}

.trabajos-pagination .page-numbers a,
.trabajos-pagination .page-numbers span {
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

.trabajos-pagination .page-numbers a:hover {
    background: #0066cc;
    color: #fff;
    border-color: #0066cc;
}

.trabajos-pagination .page-numbers .current {
    background: #0066cc;
    color: #fff;
    border-color: #0066cc;
}

/* Botón Cargar Más */
.load-more-wrapper {
    text-align: center;
    margin-top: 40px;
    margin-bottom: 40px;
}

.load-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 32px;
    background: #4CAF50;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.load-more-btn:hover:not(:disabled) {
    background: #45a049;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(76, 175, 80, 0.4);
}

.load-more-btn:active:not(:disabled) {
    transform: translateY(0);
}

.load-more-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.load-more-text {
    display: inline;
}

.load-more-spinner {
    display: none;
    animation: spin 1s linear infinite;
}

.load-more-spinner svg {
    width: 20px;
    height: 20px;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 768px) {
    .archive-title {
        font-size: 36px;
    }
    
    .load-more-btn {
        padding: 12px 24px;
        font-size: 15px;
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
    
    .trabajos-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .trabajo-card-image,
    .trabajo-card-image-placeholder {
        height: 180px;
    }
}

@media (max-width: 480px) {
    .archive-title {
        font-size: 28px;
    }
    
    .archive-subtitle {
        font-size: 15px;
    }
    
    .trabajos-archive-header {
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

/* ==========================================
   BARRAS DE INTERACCIÓN ESTILO FACEBOOK
   ========================================== */
.trabajo-card-interactions {
    border-top: 1px solid #e0e0e0;
    padding: 8px 16px;
    background: #fff;
}

.interaction-counters {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    margin-bottom: 4px;
    font-size: 13px;
    color: #65676b;
}

.counter-group {
    display: flex;
    align-items: center;
    gap: 16px;
}

.counter-item {
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    transition: color 0.2s;
}

.counter-item:hover {
    color: #1877f2;
}

.counter-item svg {
    flex-shrink: 0;
    color: #1877f2;
}

.views-counter svg {
    color: #65676b;
}

.counter-value {
    font-weight: 500;
}

.interaction-buttons {
    display: flex !important;
    justify-content: space-around;
    align-items: center;
    border-top: 1px solid #e0e0e0;
    padding-top: 4px;
    visibility: visible !important;
    opacity: 1 !important;
}

.interaction-btn {
    flex: 1;
    display: flex !important;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 4px;
    border: none;
    background: transparent;
    color: #65676b;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.2s;
    text-decoration: none;
    visibility: visible !important;
    opacity: 1 !important;
}

.interaction-btn:hover {
    background: #f0f2f5;
    color: #1877f2;
}

.interaction-btn.active {
    color: #1877f2;
}

.interaction-btn.active svg {
    fill: #1877f2;
}

.interaction-btn svg {
    flex-shrink: 0;
    transition: all 0.2s;
}

.interaction-btn .btn-text {
    flex: 1;
    text-align: left;
}

.interaction-btn .btn-count {
    font-weight: 600;
    color: #65676b;
    margin-left: 4px;
}

.interaction-btn.active .btn-count {
    color: #1877f2;
}

.like-btn.active {
    color: #1877f2;
}

.like-btn.active svg {
    fill: #1877f2;
}

.save-btn.active {
    color: #1877f2;
}

.save-btn.active svg {
    fill: #1877f2;
}

.comment-btn {
    color: #65676b;
}

.comment-btn:hover {
    color: #1877f2;
}

/* Menú de tres puntos */
.more-options-wrapper {
    position: relative;
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.more-options-btn {
    flex: 0 0 auto !important;
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.more-options-menu {
    position: absolute;
    bottom: 100%;
    right: 0;
    margin-bottom: 8px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    min-width: 160px;
    z-index: 1000;
    overflow: hidden;
    border: 1px solid #e0e0e0;
}

.more-options-item {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 12px 16px;
    border: none;
    background: transparent;
    color: #333;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
    text-align: left;
}

.more-options-item:hover {
    background: #f0f2f5;
}

.more-options-item.active {
    color: #1877f2;
}

.more-options-item svg {
    flex-shrink: 0;
}

.more-options-item span {
    flex: 1;
}

/* Botón de compartir */
.share-btn {
    color: #65676b;
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.share-btn:hover {
    color: #1877f2;
}

/* Menú de compartir */
.share-menu {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    margin-bottom: 8px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    min-width: 280px;
    z-index: 1001;
    overflow: hidden;
    border: 1px solid #e0e0e0;
    padding: 8px;
}

.share-menu-header {
    padding: 12px 16px;
    border-bottom: 1px solid #e0e0e0;
    font-weight: 600;
    font-size: 16px;
    color: #1a1a1a;
}

.share-options {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    padding: 8px;
}

.share-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 12px 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.2s;
    text-decoration: none;
    color: #333;
}

.share-option:hover {
    background: #f0f2f5;
}

.share-option-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.share-option-icon.facebook {
    background: #1877f2;
    color: #fff;
}

.share-option-icon.whatsapp {
    background: #25d366;
    color: #fff;
}

.share-option-icon.twitter {
    background: #1da1f2;
    color: #fff;
}

.share-option-icon.link {
    background: #f0f2f5;
    color: #65676b;
}

.share-option-label {
    font-size: 12px;
    font-weight: 500;
    text-align: center;
}

@media (max-width: 768px) {
    .interaction-buttons {
        gap: 4px;
        display: flex !important;
        visibility: visible !important;
    }
    
    .interaction-btn {
        font-size: 12px;
        padding: 6px 2px;
        display: flex !important;
        visibility: visible !important;
    }
    
    .interaction-btn .btn-text {
        display: none;
    }
    
    .interaction-btn .btn-count {
        margin-left: 0;
    }
    
    .counter-group {
        gap: 8px;
        font-size: 12px;
    }
    
    .share-btn,
    .more-options-btn {
        display: flex !important;
        visibility: visible !important;
    }
    
    .share-menu {
        min-width: 240px;
        left: auto;
        right: 0;
        transform: none;
    }
    
    .share-options {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<script>
// Función para manejar el cambio de ubicación
function handleUbicacionChange(select) {
    const selectedOption = select.options[select.selectedIndex];
    const selectedValue = select.value;
    
    // Si se selecciona "Todas las ubicaciones" (valor vacío)
    if (!selectedValue) {
        // Redirigir a /trabajos/ (archivo principal sin filtros)
        window.location.href = '<?php echo esc_url(get_post_type_archive_link('trabajo')); ?>';
        return;
    }
    
    // Si hay un link de término (taxonomía), usar ese
    const termLink = selectedOption.getAttribute('data-term-link');
    if (termLink) {
        // Preservar parámetros de búsqueda si existen
        const searchInput = document.getElementById('search-input-field');
        const searchValue = searchInput ? searchInput.value.trim() : '';
        
        if (searchValue) {
            // Si hay búsqueda, usar parámetros GET en lugar de URL de taxonomía
            const url = new URL(termLink, window.location.origin);
            url.searchParams.set('s', searchValue);
            window.location.href = url.toString();
        } else {
            // Si no hay búsqueda, usar URL de taxonomía limpia
            window.location.href = termLink;
        }
    } else {
        // Fallback: usar formulario con parámetros GET
        select.form.submit();
    }
}

// Funciones para interacciones estilo Facebook
function toggleLike(jobId, button) {
    <?php if (!is_user_logged_in()): ?>
        alert('Debes iniciar sesión para dar me gusta');
        window.location.href = '<?php echo esc_url(wp_login_url(get_permalink())); ?>';
        return;
    <?php endif; ?>
    
    const btn = button;
    const btnText = btn.querySelector('.btn-text');
    const btnCount = btn.querySelector('.btn-count');
    const isActive = btn.classList.contains('active');
    
    // Deshabilitar botón temporalmente
    btn.disabled = true;
    
    fetch('<?php echo esc_url(rest_url('agrochamba/v1/favorites')); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            job_id: jobId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar estado visual
            if (data.is_favorite) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
            
            // Actualizar contador
            const currentCount = parseInt(btnCount.getAttribute('data-count') || 0);
            const newCount = data.is_favorite ? currentCount + 1 : Math.max(0, currentCount - 1);
            btnCount.setAttribute('data-count', newCount);
            
            if (newCount > 0) {
                btnCount.textContent = newCount;
            } else {
                btnCount.textContent = '';
            }
            
            // Actualizar contador en la sección de counters
            const card = btn.closest('.trabajo-card');
            const likesCounter = card.querySelector('[data-counter="likes"]');
            if (likesCounter) {
                likesCounter.textContent = newCount;
            }
        } else {
            alert(data.message || 'Error al actualizar me gusta');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Por favor, intenta nuevamente.');
    })
    .finally(() => {
        btn.disabled = false;
    });
}

// Variable global para almacenar listeners de menús
const menuClickListeners = new Map();

function toggleSave(jobId, button) {
    <?php if (!is_user_logged_in()): ?>
        alert('Debes iniciar sesión para guardar trabajos');
        window.location.href = '<?php echo esc_url(wp_login_url(get_permalink())); ?>';
        return;
    <?php endif; ?>
    
    const btn = button;
    const isMenuButton = btn.classList.contains('save-btn-menu');
    const btnText = btn.querySelector('.btn-text') || btn.querySelector('span');
    const btnCount = btn.querySelector('.btn-count');
    const isActive = btn.classList.contains('active');
    
    // Deshabilitar botón temporalmente
    btn.disabled = true;
    
    fetch('<?php echo esc_url(rest_url('agrochamba/v1/saved')); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            job_id: jobId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar estado visual del botón actual
            if (data.is_saved) {
                btn.classList.add('active');
                const svg = btn.querySelector('svg');
                if (svg) {
                    svg.setAttribute('fill', 'currentColor');
                }
                if (btnText && btnText.tagName === 'SPAN') {
                    btnText.textContent = 'Guardado';
                }
            } else {
                btn.classList.remove('active');
                const svg = btn.querySelector('svg');
                if (svg) {
                    svg.setAttribute('fill', 'none');
                }
                if (btnText && btnText.tagName === 'SPAN') {
                    btnText.textContent = 'Guardar';
                }
            }
            
            // Actualizar contador si existe
            if (btnCount) {
            const currentCount = parseInt(btnCount.getAttribute('data-count') || 0);
            const newCount = data.is_saved ? currentCount + 1 : Math.max(0, currentCount - 1);
            btnCount.setAttribute('data-count', newCount);
            
            if (newCount > 0) {
                btnCount.textContent = newCount;
            } else {
                btnCount.textContent = '';
                }
            }
            
            // Cerrar menú de tres puntos si se usó desde ahí
            if (isMenuButton) {
                const menu = btn.closest('.more-options-menu');
                if (menu) {
                    menu.style.display = 'none';
                    // Eliminar listener cuando se cierra desde toggleSave
                    if (menuClickListeners.has(menu)) {
                        const listener = menuClickListeners.get(menu);
                        document.removeEventListener('click', listener);
                        menuClickListeners.delete(menu);
                    }
                }
            }
        } else {
            alert(data.message || 'Error al guardar trabajo');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Por favor, intenta nuevamente.');
    })
    .finally(() => {
        btn.disabled = false;
    });
}

// Sistema de actualización en tiempo real de todos los contadores (SOLO lectura, NO cuenta vistas)
// Las vistas solo se cuentan cuando se abre la página individual del trabajo
(function() {
    const jobIdsOnPage = new Set();
    
    // Función para actualizar todos los contadores visualmente
    function updateAllCounters(jobId, data) {
        // Actualizar todas las cards con este jobId en la página
        const cards = document.querySelectorAll(`[data-job-id="${jobId}"]`);
        cards.forEach(function(card) {
            // Actualizar contador de vistas
            // IMPORTANTE: Solo actualizar si el valor del servidor es mayor o igual al actual
            // Esto evita que filtros o caché incorrecto sobrescriban el valor correcto
            if (data.views !== undefined) {
                const viewsCounter = card.querySelector('[data-counter="views"]');
                if (viewsCounter) {
                    const currentViews = parseInt(viewsCounter.textContent) || 0;
                    const serverViews = parseInt(data.views) || 0;
                    // Solo actualizar si el valor del servidor es válido y mayor o igual al actual
                    // Esto previene que valores incorrectos sobrescriban el contador correcto
                    if (serverViews >= currentViews && serverViews !== currentViews) {
                        viewsCounter.textContent = serverViews;
                        // Animación sutil solo si cambió
                        viewsCounter.style.transition = 'all 0.3s';
                        viewsCounter.style.transform = 'scale(1.1)';
                        setTimeout(function() {
                            viewsCounter.style.transform = 'scale(1)';
                        }, 300);
                    }
                }
            }
            
            // Actualizar contador de likes y estado del botón
            if (data.likes !== undefined) {
                const likesCounter = card.querySelector('[data-counter="likes"]');
                if (likesCounter) {
                    const currentLikes = parseInt(likesCounter.textContent) || 0;
                    if (data.likes !== currentLikes) {
                        likesCounter.textContent = data.likes;
                    }
                }
                
                // Actualizar estado del botón de like (si el usuario está logueado)
                <?php if (is_user_logged_in()): ?>
                const likeBtn = card.querySelector('.like-btn');
                if (likeBtn && data.is_favorite !== undefined) {
                    // Actualizar clase active según el estado
                    if (data.is_favorite) {
                        likeBtn.classList.add('active');
                        // Actualizar SVG para que se llene
                        const svg = likeBtn.querySelector('svg');
                        if (svg) {
                            svg.setAttribute('fill', 'currentColor');
                        }
                    } else {
                        likeBtn.classList.remove('active');
                        // Actualizar SVG para que esté vacío
                        const svg = likeBtn.querySelector('svg');
                        if (svg) {
                            svg.setAttribute('fill', 'none');
                        }
                    }
                    
                    // Actualizar contador en el botón
                    const btnCount = likeBtn.querySelector('.btn-count');
                    if (btnCount) {
                        btnCount.setAttribute('data-count', data.likes);
                        if (data.likes > 0) {
                            btnCount.textContent = data.likes;
                        } else {
                            btnCount.textContent = '';
                        }
                    }
                }
                <?php endif; ?>
            }
            
            // Actualizar contador de guardados y estado del botón
            if (data.saved !== undefined) {
                <?php if (is_user_logged_in()): ?>
                const saveBtn = card.querySelector('.save-btn');
                if (saveBtn && data.is_saved !== undefined) {
                    // Actualizar clase active según el estado
                    if (data.is_saved) {
                        saveBtn.classList.add('active');
                        const svg = saveBtn.querySelector('svg');
                        if (svg) {
                            svg.setAttribute('fill', 'currentColor');
                        }
                    } else {
                        saveBtn.classList.remove('active');
                        const svg = saveBtn.querySelector('svg');
                        if (svg) {
                            svg.setAttribute('fill', 'none');
                        }
                    }
                    
                    // Actualizar contador en el botón
                    const btnCount = saveBtn.querySelector('.btn-count');
                    if (btnCount) {
                        btnCount.setAttribute('data-count', data.saved);
                        if (data.saved > 0) {
                            btnCount.textContent = data.saved;
                        } else {
                            btnCount.textContent = '';
                        }
                    }
                }
                <?php endif; ?>
            }
            
            // Actualizar contador de comentarios
            if (data.comments !== undefined) {
                const commentsCounter = card.querySelector('[data-counter="comments"]');
                if (commentsCounter) {
                    const currentComments = parseInt(commentsCounter.textContent) || 0;
                    if (data.comments !== currentComments) {
                        commentsCounter.textContent = data.comments;
                    }
                }
            }
            
            // Actualizar contador de compartidos
            if (data.shared !== undefined) {
                const sharedCounter = card.querySelector('[data-counter="shared"]');
                const shareBtn = card.querySelector('.share-btn');
                const shareBtnCount = shareBtn ? shareBtn.querySelector('.btn-count') : null;
                
                if (sharedCounter) {
                    sharedCounter.textContent = data.shared;
                }
                
                if (shareBtnCount) {
                    shareBtnCount.setAttribute('data-count', data.shared);
                    if (data.shared > 0) {
                        shareBtnCount.textContent = data.shared;
                    } else {
                        shareBtnCount.textContent = '';
                    }
                } else if (shareBtn && data.shared > 0) {
                    // Crear contador en el botón si no existe
                    const span = document.createElement('span');
                    span.className = 'btn-count';
                    span.setAttribute('data-count', data.shared);
                    span.textContent = data.shared;
                    shareBtn.appendChild(span);
                }
            }
        });
    }
    
    // Función para obtener y actualizar todos los contadores (SOLO lectura)
    function refreshAllCounters(jobId) {
        fetch('<?php echo esc_url(rest_url('agrochamba/v1/jobs/')); ?>' + jobId + '/counters', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.likes !== undefined || data.comments !== undefined || data.views !== undefined || data.shared !== undefined) {
                updateAllCounters(jobId, data);
            }
        })
        .catch(error => {
            // Silenciar errores
        });
    }
    
    // Recopilar todos los IDs de trabajos en la página
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.trabajo-card[data-job-id]');
        cards.forEach(function(card) {
            const jobId = card.getAttribute('data-job-id');
            if (jobId) {
                jobIdsOnPage.add(jobId);
            }
        });
        
        // Actualizar contadores cada 30 segundos (polling para ver cambios de otros usuarios)
        // IMPORTANTE: Esto solo LEE los contadores, NO los cuenta
        if (jobIdsOnPage.size > 0) {
            // Actualizar inmediatamente al cargar
            jobIdsOnPage.forEach(function(jobId) {
                refreshAllCounters(jobId);
            });
            
            // Actualizar cada 15 segundos para sincronización más rápida con la app
            setInterval(function() {
                jobIdsOnPage.forEach(function(jobId) {
                    refreshAllCounters(jobId);
                });
            }, 15000); // Actualizar cada 15 segundos para mejor sincronización
        }
    });
})();

// La búsqueda ahora se maneja con el formulario HTML estándar
// No necesitamos funciones JavaScript adicionales para los filtros

// Función para limpiar el campo de búsqueda
function clearSearchInput() {
    const searchInput = document.getElementById('search-input-field');
    const clearBtn = document.getElementById('search-clear-btn');
    
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
        
        // Ocultar botón de limpiar
        if (clearBtn) {
            clearBtn.style.display = 'none';
        }
        
        // Enviar formulario para limpiar resultados
        const form = searchInput.closest('form');
        if (form) {
            form.submit();
        }
    }
}

// Función para mostrar/ocultar botón de limpiar según el contenido
function toggleClearButton() {
    const searchInput = document.getElementById('search-input-field');
    const clearBtn = document.getElementById('search-clear-btn');
    
    if (searchInput && clearBtn) {
        if (searchInput.value.trim() !== '') {
            clearBtn.style.display = 'flex';
        } else {
            clearBtn.style.display = 'none';
        }
    }
}

// Asegurar que los botones de compartir y menú de tres puntos siempre sean visibles
document.addEventListener('DOMContentLoaded', function() {
    // Configurar campo de búsqueda
    const searchInput = document.getElementById('search-input-field');
    const clearBtn = document.getElementById('search-clear-btn');
    
    if (searchInput && clearBtn) {
        // Mostrar/ocultar botón según contenido inicial
        toggleClearButton();
        
        // Escuchar cambios en el campo de búsqueda
        searchInput.addEventListener('input', toggleClearButton);
        searchInput.addEventListener('keyup', toggleClearButton);
    }
    
    // Forzar visibilidad de botones de interacción
    const interactionButtons = document.querySelectorAll('.interaction-buttons');
    interactionButtons.forEach(function(container) {
        container.style.display = 'flex';
        container.style.visibility = 'visible';
        container.style.opacity = '1';
    });
    
    // Forzar visibilidad de botones de compartir
    const shareButtons = document.querySelectorAll('.share-btn');
    shareButtons.forEach(function(btn) {
        btn.style.display = 'flex';
        btn.style.visibility = 'visible';
        btn.style.opacity = '1';
    });
    
    // Forzar visibilidad de menú de tres puntos (solo para usuarios logueados)
    <?php if (is_user_logged_in()): ?>
    const moreOptionsWrappers = document.querySelectorAll('.more-options-wrapper');
    moreOptionsWrappers.forEach(function(wrapper) {
        wrapper.style.display = 'flex';
        wrapper.style.visibility = 'visible';
        wrapper.style.opacity = '1';
    });
    
    const moreOptionsButtons = document.querySelectorAll('.more-options-btn');
    moreOptionsButtons.forEach(function(btn) {
        btn.style.display = 'flex';
        btn.style.visibility = 'visible';
        btn.style.opacity = '1';
    });
    <?php endif; ?>
});

// Función para compartir trabajo
function shareJob(jobId, button) {
    const jobTitle = button.getAttribute('data-job-title');
    
    // Obtener la URL directamente del enlace del permalink (URL limpia como en la barra de direcciones)
    const card = button.closest('.trabajo-card');
    const permalinkLink = card ? card.querySelector('.trabajo-card-link') : null;
    let jobUrl = permalinkLink ? permalinkLink.getAttribute('href') : button.getAttribute('data-job-url');
    
    // Si la URL está codificada, decodificarla para que sea igual a la de la barra de direcciones
    try {
        // Decodificar solo si está codificada
        if (jobUrl && jobUrl.includes('%')) {
            jobUrl = decodeURIComponent(jobUrl);
        }
    } catch (e) {
        // Si falla la decodificación, usar la URL original
        console.log('No se pudo decodificar la URL:', e);
    }
    
    const jobText = 'Mira esta oportunidad de trabajo: ' + jobTitle;
    
    // Función para registrar el compartido en el servidor
    const trackShare = function() {
        fetch('<?php echo esc_url(rest_url('agrochamba/v1/jobs/')); ?>' + jobId + '/share', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar contador en el botón
                const btnCount = button.querySelector('.btn-count');
                if (btnCount) {
                    const currentCount = parseInt(btnCount.getAttribute('data-count') || 0);
                    const newCount = currentCount + 1;
                    btnCount.setAttribute('data-count', newCount);
                    btnCount.textContent = newCount;
                } else {
                    // Crear contador si no existe
                    const span = document.createElement('span');
                    span.className = 'btn-count';
                    span.setAttribute('data-count', data.shared_count);
                    span.textContent = data.shared_count;
                    button.appendChild(span);
                }
                
                // Actualizar contador en la sección de contadores
                const card = button.closest('.trabajo-card');
                const sharedCounter = card.querySelector('[data-counter="shared"]');
                if (sharedCounter) {
                    sharedCounter.textContent = data.shared_count;
                } else {
                    // Crear contador si no existe
                    const counterGroup = card.querySelector('.counter-group');
                    if (counterGroup) {
                        const counterItem = document.createElement('span');
                        counterItem.className = 'counter-item';
                        counterItem.innerHTML = `
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="18" cy="5" r="3"/>
                                <circle cx="6" cy="12" r="3"/>
                                <circle cx="18" cy="19" r="3"/>
                                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                            </svg>
                            <span class="counter-value" data-counter="shared">${data.shared_count}</span>
                        `;
                        counterGroup.appendChild(counterItem);
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error al registrar compartido:', error);
        });
    };
    
    // Intentar usar Web Share API (similar a Facebook)
    if (navigator.share) {
        navigator.share({
            title: jobTitle,
            text: jobText,
            url: jobUrl
        })
        .then(() => {
            console.log('Compartido exitosamente');
            trackShare(); // Registrar el compartido
            // NO mostrar el modal del plugin si el sistema nativo funcionó
        })
        .catch((error) => {
            // Solo mostrar el modal si el usuario canceló explícitamente (error.name === 'AbortError')
            // o si hay un error real (no es solo cancelación)
            if (error.name !== 'AbortError') {
                console.log('Error al compartir:', error);
                // Si falla por un error real (no cancelación), mostrar menú de opciones
                showShareMenu(button, jobTitle, jobUrl, jobText, trackShare);
            } else {
                // Si el usuario canceló, solo registrar silenciosamente
                console.log('Usuario canceló el compartido');
            }
        });
    } else {
        // Fallback: mostrar menú de opciones de compartir solo si Web Share API no está disponible
        showShareMenu(button, jobTitle, jobUrl, jobText, trackShare);
    }
}

// Función para mostrar menú de compartir
function showShareMenu(button, jobTitle, jobUrl, jobText, trackShareCallback) {
    // Cerrar otros menús abiertos
    closeAllMenus();
    
    const card = button.closest('.trabajo-card');
    const shareMenu = document.createElement('div');
    shareMenu.className = 'share-menu';
    shareMenu.innerHTML = `
        <div class="share-menu-header">Compartir en</div>
        <div class="share-options">
            <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(jobUrl)}" 
               target="_blank" 
               rel="noopener noreferrer"
               class="share-option"
               onclick="closeAllMenus();">
                <div class="share-option-icon facebook">f</div>
                <div class="share-option-label">Facebook</div>
            </a>
            <a href="https://wa.me/?text=${encodeURIComponent(jobText + ' ' + jobUrl)}" 
               target="_blank" 
               rel="noopener noreferrer"
               class="share-option"
               onclick="closeAllMenus();">
                <div class="share-option-icon whatsapp">W</div>
                <div class="share-option-label">WhatsApp</div>
            </a>
            <a href="https://twitter.com/intent/tweet?text=${encodeURIComponent(jobText)}&url=${encodeURIComponent(jobUrl)}" 
               target="_blank" 
               rel="noopener noreferrer"
               class="share-option"
               onclick="closeAllMenus();">
                <div class="share-option-icon twitter">t</div>
                <div class="share-option-label">Twitter</div>
            </a>
            <button class="share-option" 
                    onclick="copyToClipboard('${jobUrl.replace(/'/g, "\\'")}'); closeAllMenus();">
                <div class="share-option-icon link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                </div>
                <div class="share-option-label">Copiar enlace</div>
            </button>
        </div>
    `;
    
    const buttonWrapper = button.closest('.interaction-btn');
    buttonWrapper.style.position = 'relative';
    buttonWrapper.appendChild(shareMenu);
    
    // Registrar compartido cuando se hace clic en cualquier opción
    if (trackShareCallback) {
        shareMenu.querySelectorAll('.share-option').forEach(option => {
            option.addEventListener('click', function() {
                setTimeout(trackShareCallback, 500); // Pequeño delay para asegurar que se abrió la ventana
            });
        });
    }
    
    // Cerrar menú al hacer clic fuera
    setTimeout(() => {
        document.addEventListener('click', function closeMenuOnClickOutside(e) {
            if (!shareMenu.contains(e.target) && e.target !== button) {
                shareMenu.remove();
                document.removeEventListener('click', closeMenuOnClickOutside);
            }
        });
    }, 100);
}

// Función para copiar al portapapeles
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            // Mostrar notificación temporal
            const notification = document.createElement('div');
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #4CAF50; color: white; padding: 12px 20px; border-radius: 8px; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
            notification.textContent = 'Enlace copiado al portapapeles';
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.remove();
            }, 2000);
        }).catch(err => {
            console.error('Error al copiar:', err);
            alert('Error al copiar el enlace');
        });
    } else {
        // Fallback para navegadores antiguos
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            alert('Enlace copiado al portapapeles');
        } catch (err) {
            alert('Error al copiar el enlace');
        }
        document.body.removeChild(textArea);
    }
}

// Función para abrir/cerrar menú de tres puntos
function toggleMoreOptions(button) {
    const wrapper = button.closest('.more-options-wrapper');
    const menu = wrapper.querySelector('.more-options-menu');
    
    // Cerrar otros menús abiertos
    closeAllMenus();
    
    if (menu.style.display === 'none' || !menu.style.display) {
        menu.style.display = 'block';
        
        // Eliminar listener anterior si existe
        if (menuClickListeners.has(menu)) {
            const oldListener = menuClickListeners.get(menu);
            document.removeEventListener('click', oldListener);
            menuClickListeners.delete(menu);
        }
        
        // Cerrar menú al hacer clic fuera
        const closeMenuOnClickOutside = function(e) {
            // No cerrar si el clic es dentro del menú o en el botón
            if (!menu.contains(e.target) && e.target !== button && !button.contains(e.target)) {
                menu.style.display = 'none';
                document.removeEventListener('click', closeMenuOnClickOutside);
                menuClickListeners.delete(menu);
            }
        };
        
        // Guardar referencia al listener
        menuClickListeners.set(menu, closeMenuOnClickOutside);
        
        setTimeout(() => {
            document.addEventListener('click', closeMenuOnClickOutside);
        }, 100);
    } else {
        menu.style.display = 'none';
        // Eliminar listener cuando se cierra manualmente
        if (menuClickListeners.has(menu)) {
            const listener = menuClickListeners.get(menu);
            document.removeEventListener('click', listener);
            menuClickListeners.delete(menu);
        }
    }
}

// Función para cerrar todos los menús abiertos
function closeAllMenus() {
    // Cerrar menús de compartir
    document.querySelectorAll('.share-menu').forEach(menu => {
        menu.remove();
    });
    
    // Cerrar menús de tres puntos y eliminar listeners
    document.querySelectorAll('.more-options-menu').forEach(menu => {
        menu.style.display = 'none';
        // Eliminar listener si existe
        if (menuClickListeners.has(menu)) {
            const listener = menuClickListeners.get(menu);
            document.removeEventListener('click', listener);
            menuClickListeners.delete(menu);
        }
    });
}

// Función para cargar más trabajos
(function() {
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (!loadMoreBtn) return;
    
    let isLoading = false;
    
    loadMoreBtn.addEventListener('click', function() {
        if (isLoading) return;
        
        const currentPage = parseInt(this.getAttribute('data-current-page')) || 1;
        const maxPages = parseInt(this.getAttribute('data-max-pages')) || 1;
        const nextPage = currentPage + 1;
        
        if (nextPage > maxPages) {
            this.style.display = 'none';
            return;
        }
        
        isLoading = true;
        const btnText = this.querySelector('.load-more-text');
        const btnSpinner = this.querySelector('.load-more-spinner');
        
        // Mostrar spinner
        btnText.style.display = 'none';
        btnSpinner.style.display = 'inline-block';
        this.disabled = true;
        
        // Construir URL de la API
        const params = new URLSearchParams({
            page: nextPage
        });
        
        const ubicacion = this.getAttribute('data-ubicacion');
        const cultivo = this.getAttribute('data-cultivo');
        const empresa = this.getAttribute('data-empresa');
        const search = this.getAttribute('data-search');
        const orderby = this.getAttribute('data-orderby');
        
        if (ubicacion) params.append('ubicacion', ubicacion);
        if (cultivo) params.append('cultivo', cultivo);
        if (empresa) params.append('empresa', empresa);
        if (search) params.append('s', search);
        if (orderby) params.append('orderby', orderby);
        
        const apiUrl = '<?php echo esc_url(rest_url('agrochamba/v1/jobs/load-more')); ?>?' + params.toString();
        
        fetch(apiUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.html) {
                // Insertar nuevo contenido en el grid
                const grid = document.querySelector('.trabajos-grid');
                if (grid) {
                    // Crear un contenedor temporal para parsear el HTML
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.html;
                    
                    // Agregar cada card al grid
                    const cards = tempDiv.querySelectorAll('.trabajo-card');
                    cards.forEach(card => {
                        grid.appendChild(card);
                    });
                    
                    // Actualizar contadores de todas las cards nuevas
                    cards.forEach(card => {
                        const jobId = card.getAttribute('data-job-id');
                        if (jobId) {
                            updateAllCounters(parseInt(jobId), card);
                        }
                    });
                }
                
                // Actualizar página actual
                this.setAttribute('data-current-page', nextPage);
                
                // Ocultar botón si no hay más páginas
                if (!data.has_more || nextPage >= maxPages) {
                    this.style.display = 'none';
                } else {
                    // Restaurar botón
                    btnText.style.display = 'inline';
                    btnSpinner.style.display = 'none';
                    this.disabled = false;
                }
            } else {
                // Ocultar botón si no hay más contenido
                this.style.display = 'none';
            }
            
            isLoading = false;
        })
        .catch(error => {
            console.error('Error al cargar más trabajos:', error);
            btnText.style.display = 'inline';
            btnSpinner.style.display = 'none';
            this.disabled = false;
            isLoading = false;
            
            // Mostrar mensaje de error
            alert('Error al cargar más trabajos. Por favor, intenta de nuevo.');
        });
    });
})();
</script>

<?php
get_footer();

