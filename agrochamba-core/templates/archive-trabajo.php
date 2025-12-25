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
$ubicacion_filter = isset($_GET['ubicacion']) ? sanitize_text_field($_GET['ubicacion']) : '';
$cultivo_filter = isset($_GET['cultivo']) ? sanitize_text_field($_GET['cultivo']) : '';
$empresa_filter = isset($_GET['empresa']) ? sanitize_text_field($_GET['empresa']) : '';

// Verificar si es un nuevo usuario
$show_welcome = isset($_GET['welcome']) && $_GET['welcome'] === '1';
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
                               class="search-input" 
                               placeholder="Buscar por empresa, puesto o palabra clave"
                               value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
                    </div>
                    
                    <div class="search-input-wrapper location-wrapper">
                        <svg class="location-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <select name="ubicacion" class="search-input search-select" onchange="this.form.submit()">
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

    <!-- Grid de Trabajos -->
    <div class="trabajos-archive-content">
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
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="18" cy="5" r="3"/>
                                            <circle cx="6" cy="12" r="3"/>
                                            <circle cx="18" cy="19" r="3"/>
                                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                                        </svg>
                                        <span class="btn-text">Compartir</span>
                                    </button>
                                    
                                    <!-- Menú de tres puntos -->
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
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="18" cy="5" r="3"/>
                                            <circle cx="6" cy="12" r="3"/>
                                            <circle cx="18" cy="19" r="3"/>
                                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                                        </svg>
                                        <span class="btn-text">Compartir</span>
                                    </button>
                                    
                                    <!-- Menú de tres puntos -->
                                    <div class="more-options-wrapper">
                                        <a href="<?php echo esc_url(wp_login_url(get_permalink($trabajo_id))); ?>" class="interaction-btn more-options-btn">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                                <circle cx="12" cy="5" r="2"/>
                                                <circle cx="12" cy="12" r="2"/>
                                                <circle cx="12" cy="19" r="2"/>
                                            </svg>
                                        </a>
                                    </div>
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
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Paginación -->
        <?php 
        // Preservar parámetros GET en la paginación
        $pagination_args = array(
            'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> Anterior',
            'next_text' => 'Siguiente <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>',
            'type' => 'list',
        );
        
        // Agregar parámetros GET actuales a la paginación
        // Esto asegura que los filtros se preserven al navegar entre páginas
        $current_url_params = array();
        
        // Siempre incluir post_type para mantener la consistencia
        $current_url_params['post_type'] = 'trabajo';
        
        // Incluir parámetros solo si tienen valor (no vacíos)
        if (isset($_GET['ubicacion']) && $_GET['ubicacion'] !== '') {
            $current_url_params['ubicacion'] = sanitize_text_field($_GET['ubicacion']);
        }
        if (isset($_GET['cultivo']) && $_GET['cultivo'] !== '') {
            $current_url_params['cultivo'] = sanitize_text_field($_GET['cultivo']);
        }
        if (isset($_GET['empresa']) && $_GET['empresa'] !== '') {
            $current_url_params['empresa'] = sanitize_text_field($_GET['empresa']);
        }
        if (isset($_GET['s']) && $_GET['s'] !== '') {
            $current_url_params['s'] = sanitize_text_field($_GET['s']);
        }
        
        // Usar base URL correcta para la paginación
        // Si estamos en el archivo de trabajos, usar su URL base
        if (is_post_type_archive('trabajo')) {
            $pagination_base = get_post_type_archive_link('trabajo');
            // Remover parámetros de página si existen en la URL base
            $pagination_base = remove_query_arg('paged', $pagination_base);
            $pagination_args['base'] = $pagination_base . '%_%';
        } else {
            // Para búsquedas o taxonomías, usar la URL actual sin paged
            $current_url = remove_query_arg('paged');
            $pagination_args['base'] = $current_url . '%_%';
        }
        
        // Agregar parámetros a la paginación
        $pagination_args['add_args'] = $current_url_params;
        
        if (paginate_links($pagination_args)): ?>
            <div class="trabajos-pagination">
                <?php echo paginate_links($pagination_args); ?>
            </div>
        <?php endif; ?>
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
            if (data.likes !== undefined || data.comments !== undefined || data.views !== undefined) {
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

// Función para compartir trabajo
function shareJob(jobId, button) {
    const jobTitle = button.getAttribute('data-job-title');
    const jobUrl = button.getAttribute('data-job-url');
    const jobText = 'Mira esta oportunidad de trabajo: ' + jobTitle;
    
    // Intentar usar Web Share API (similar a Facebook)
    if (navigator.share) {
        navigator.share({
            title: jobTitle,
            text: jobText,
            url: jobUrl
        })
        .then(() => {
            console.log('Compartido exitosamente');
        })
        .catch((error) => {
            console.log('Error al compartir:', error);
            // Si falla, mostrar menú de opciones
            showShareMenu(button, jobTitle, jobUrl, jobText);
        });
    } else {
        // Fallback: mostrar menú de opciones de compartir
        showShareMenu(button, jobTitle, jobUrl, jobText);
    }
}

// Función para mostrar menú de compartir
function showShareMenu(button, jobTitle, jobUrl, jobText) {
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
        
        // Cerrar menú al hacer clic fuera
        setTimeout(() => {
            document.addEventListener('click', function closeMenuOnClickOutside(e) {
                if (!menu.contains(e.target) && e.target !== button) {
                    menu.style.display = 'none';
                    document.removeEventListener('click', closeMenuOnClickOutside);
                }
            });
        }, 100);
    } else {
        menu.style.display = 'none';
    }
}

// Función para cerrar todos los menús abiertos
function closeAllMenus() {
    // Cerrar menús de compartir
    document.querySelectorAll('.share-menu').forEach(menu => {
        menu.remove();
    });
    
    // Cerrar menús de tres puntos
    document.querySelectorAll('.more-options-menu').forEach(menu => {
        menu.style.display = 'none';
    });
}
</script>

<?php
get_footer();

