<?php
/**
 * Template para mostrar resultados de búsqueda de trabajos
 * Reutiliza el diseño de archive-trabajo.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Obtener término de búsqueda
$search_query = get_search_query();
$ubicacion_filter = isset($_GET['ubicacion']) ? sanitize_text_field($_GET['ubicacion']) : '';
$orderby_filter = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date'; // Por defecto: más recientes

?>
<div class="trabajos-archive-wrapper">
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
                               placeholder="Busca por empresa, puesto o palabra clave"
                               value="<?php echo esc_attr($search_query); ?>">
                        <button type="button" 
                                class="search-clear-btn" 
                                id="search-clear-btn"
                                onclick="clearSearchInput()"
                                style="<?php echo !empty($search_query) ? 'display: flex;' : 'display: none;'; ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="search-input-wrapper location-wrapper">
                        <input type="hidden" name="ubicacion" id="ubicacion-hidden-search" value="<?php echo esc_attr($ubicacion_filter); ?>">
                        <?php
                        // Usar solo los 25 departamentos de Perú
                        $departamentos = function_exists('agrochamba_get_departamentos')
                            ? agrochamba_get_departamentos()
                            : array();

                        // Obtener datos completos para búsqueda
                        $locations_for_search = function_exists('agrochamba_get_locations_for_js')
                            ? agrochamba_get_locations_for_js()
                            : array();

                        // Obtener nombre del departamento seleccionado
                        $selected_name = 'Todas las ubicaciones';
                        foreach ($departamentos as $departamento):
                            $term = get_term_by('name', $departamento, 'ubicacion');
                            $slug = $term ? $term->slug : sanitize_title($departamento);
                            if ($slug === $ubicacion_filter) {
                                $selected_name = $departamento;
                                break;
                            }
                        endforeach;
                        ?>
                        <div class="searchable-select" id="ubicacion-searchable-search" data-locations='<?php echo json_encode($locations_for_search, JSON_UNESCAPED_UNICODE); ?>'>
                            <button type="button" class="searchable-select-trigger" onclick="toggleSearchableSelect('ubicacion-searchable-search')">
                                <svg class="location-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <span class="searchable-select-text"><?php echo esc_html($selected_name); ?></span>
                                <svg class="dropdown-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </button>
                            <div class="searchable-select-dropdown">
                                <div class="searchable-select-search">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"/>
                                        <path d="m21 21-4.35-4.35"/>
                                    </svg>
                                    <input type="text" class="searchable-select-input" placeholder="Buscar ubicación..." oninput="filterLocationOptions(this, 'ubicacion-searchable-search')">
                                </div>
                                <div class="searchable-select-options">
                                    <div class="searchable-select-option <?php echo empty($ubicacion_filter) ? 'selected' : ''; ?>"
                                         data-value=""
                                         data-departamento=""
                                         onclick="selectSearchableOptionSearch(this, 'ubicacion-searchable-search')">
                                        Todas las ubicaciones
                                    </div>
                                    <?php foreach ($departamentos as $departamento):
                                        $term = get_term_by('name', $departamento, 'ubicacion');
                                        $slug = $term ? $term->slug : sanitize_title($departamento);
                                    ?>
                                    <div class="searchable-select-option <?php echo ($slug === $ubicacion_filter) ? 'selected' : ''; ?>"
                                         data-value="<?php echo esc_attr($slug); ?>"
                                         data-departamento="<?php echo esc_attr($departamento); ?>"
                                         onclick="selectSearchableOptionSearch(this, 'ubicacion-searchable-search')">
                                        <?php echo esc_html($departamento); ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
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
                    <?php if (!empty($search_query)): ?>
                        <input type="hidden" name="s" value="<?php echo esc_attr($search_query); ?>">
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
            
            <!-- Mostrar término de búsqueda si existe -->
            <?php if ($search_query): ?>
                <div class="search-results-info">
                    <p>
                        <?php 
                        $found_posts = $wp_query->found_posts;
                        printf(
                            _n(
                                'Se encontró %d resultado para "%s"',
                                'Se encontraron %d resultados para "%s"',
                                $found_posts,
                                'agrochamba'
                            ),
                            $found_posts,
                            '<strong>' . esc_html($search_query) . '</strong>'
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Grid de Trabajos -->
    <div class="trabajos-archive-content">
        <div class="trabajos-grid">
            <?php if (have_posts()): ?>
                <?php while (have_posts()): the_post(); 
                    $trabajo_id = get_the_ID();
                    
                    // Verificar que sea un trabajo
                    if (get_post_type($trabajo_id) !== 'trabajo') {
                        continue;
                    }
                    
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
                    <p><?php echo $search_query ? 'Intenta con otros términos de búsqueda o ajusta los filtros' : 'Intenta ajustar los filtros o vuelve más tarde'; ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Paginación -->
        <?php 
        global $wp_query;
        $max_pages = $wp_query->max_num_pages;
        $current_page = max(1, get_query_var('paged'));
        
        // Obtener filtros actuales
        $ubicacion_filter = isset($_GET['ubicacion']) ? sanitize_text_field($_GET['ubicacion']) : '';
        $cultivo_filter = isset($_GET['cultivo']) ? sanitize_text_field($_GET['cultivo']) : '';
        $empresa_filter = isset($_GET['empresa']) ? sanitize_text_field($_GET['empresa']) : '';
        $orderby_filter = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
        
        if (paginate_links()): ?>
            <div class="trabajos-pagination">
                <?php
                echo paginate_links(array(
                    'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> Anterior',
                    'next_text' => 'Siguiente <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>',
                    'type' => 'list',
                ));
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Botón Cargar Más -->
        <?php if ($current_page < $max_pages): ?>
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
    </div>
</div>

<?php
// Incluir los mismos estilos que archive-trabajo.php
// Para evitar duplicación, podríamos mover los estilos a un archivo CSS separado
// Por ahora, los incluimos aquí
?>
<style>
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

/* Searchable Select Component */
.searchable-select {
    position: relative;
    width: 100%;
}

.searchable-select-trigger {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 16px 20px;
    background: #fff;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 16px;
    color: #333;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
}

.searchable-select-trigger:hover {
    border-color: #4CAF50;
}

.searchable-select.open .searchable-select-trigger {
    border-color: #4CAF50;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}

.searchable-select-trigger .location-icon {
    color: #4CAF50;
    flex-shrink: 0;
}

.searchable-select-text {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.searchable-select-trigger .dropdown-icon {
    color: #999;
    flex-shrink: 0;
    transition: transform 0.2s;
}

.searchable-select.open .searchable-select-trigger .dropdown-icon {
    transform: rotate(180deg);
}

.searchable-select-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 2px solid #4CAF50;
    border-top: none;
    border-bottom-left-radius: 12px;
    border-bottom-right-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    max-height: 320px;
    overflow: hidden;
}

.searchable-select.open .searchable-select-dropdown {
    display: block;
}

.searchable-select-search {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    border-bottom: 1px solid #e0e0e0;
    background: #f9f9f9;
}

.searchable-select-search svg {
    color: #999;
    flex-shrink: 0;
}

.searchable-select-input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 14px;
    outline: none;
    color: #333;
}

.searchable-select-input::placeholder {
    color: #999;
}

.searchable-select-options {
    max-height: 250px;
    overflow-y: auto;
}

.searchable-select-option {
    padding: 12px 16px;
    cursor: pointer;
    transition: background 0.15s;
    font-size: 15px;
}

.searchable-select-option:hover {
    background: #f5f5f5;
}

.searchable-select-option.selected {
    background: #e8f5e9;
    color: #2e7d32;
    font-weight: 500;
}

.searchable-select-option.hidden {
    display: none;
}

.searchable-select-no-results {
    padding: 16px;
    text-align: center;
    color: #999;
    font-size: 14px;
}

.searchable-select-option.match-child {
    background: #fff8e1;
}

.searchable-select-option.match-child:hover {
    background: #ffecb3;
}

.match-hint {
    display: block;
    font-size: 12px;
    color: #666;
    margin-top: 2px;
    font-weight: 400;
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

.search-results-info {
    margin-top: 24px;
    padding: 16px;
    background: #f5f5f5;
    border-radius: 8px;
    color: #666;
    font-size: 15px;
}

.search-results-info strong {
    color: #1a237e;
    font-weight: 600;
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
    grid-column: 1 / -1;
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
    display: flex;
    justify-content: space-around;
    align-items: center;
    border-top: 1px solid #e0e0e0;
    padding-top: 4px;
}

.interaction-btn {
    flex: 1;
    display: flex;
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

@media (max-width: 768px) {
    .interaction-buttons {
        gap: 4px;
    }
    
    .interaction-btn {
        font-size: 12px;
        padding: 6px 2px;
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
// ==========================================
// Searchable Select Component
// ==========================================
function toggleSearchableSelect(selectId) {
    const select = document.getElementById(selectId);
    const isOpen = select.classList.contains('open');

    // Cerrar todos los otros selects abiertos
    document.querySelectorAll('.searchable-select.open').forEach(s => {
        if (s.id !== selectId) {
            s.classList.remove('open');
        }
    });

    // Toggle el select actual
    select.classList.toggle('open');

    // Si se abre, enfocar el input de búsqueda y limpiar highlights
    if (!isOpen) {
        const input = select.querySelector('.searchable-select-input');
        if (input) {
            setTimeout(() => input.focus(), 100);
        }
        // Limpiar cualquier highlight anterior
        select.querySelectorAll('.searchable-select-option').forEach(opt => {
            opt.classList.remove('match-child');
            const hint = opt.querySelector('.match-hint');
            if (hint) hint.remove();
        });
    }
}

// Normalizar texto para búsqueda (remover acentos)
function normalizeText(text) {
    return text.toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

// Buscar en departamentos, provincias y distritos
function filterLocationOptions(input, selectId) {
    const select = document.getElementById(selectId);
    const filter = normalizeText(input.value);
    const options = select.querySelectorAll('.searchable-select-option');
    const locationsData = JSON.parse(select.getAttribute('data-locations') || '{}');
    let visibleCount = 0;

    options.forEach(option => {
        const departamento = option.getAttribute('data-departamento');
        const optionText = normalizeText(option.textContent);

        // Limpiar estado anterior
        option.classList.remove('hidden', 'match-child');
        const oldHint = option.querySelector('.match-hint');
        if (oldHint) oldHint.remove();

        // Si no hay filtro, mostrar todo
        if (!filter) {
            visibleCount++;
            return;
        }

        // Opción "Todas las ubicaciones"
        if (!departamento) {
            if (filter) {
                option.classList.add('hidden');
            } else {
                visibleCount++;
            }
            return;
        }

        // Buscar coincidencia directa en el departamento
        if (optionText.includes(filter)) {
            visibleCount++;
            return;
        }

        // Buscar en provincias y distritos de este departamento
        const provincias = locationsData[departamento];
        if (provincias) {
            let matchFound = null;

            for (const provincia in provincias) {
                if (normalizeText(provincia).includes(filter)) {
                    matchFound = { type: 'provincia', name: provincia };
                    break;
                }

                const distritos = provincias[provincia];
                if (Array.isArray(distritos)) {
                    for (const distrito of distritos) {
                        if (normalizeText(distrito).includes(filter)) {
                            matchFound = { type: 'distrito', name: distrito, provincia: provincia };
                            break;
                        }
                    }
                }
                if (matchFound) break;
            }

            if (matchFound) {
                visibleCount++;
                option.classList.add('match-child');

                const hint = document.createElement('span');
                hint.className = 'match-hint';
                if (matchFound.type === 'provincia') {
                    hint.textContent = `(${matchFound.name})`;
                } else {
                    hint.textContent = `(${matchFound.name}, ${matchFound.provincia})`;
                }
                option.appendChild(hint);
                return;
            }
        }

        option.classList.add('hidden');
    });

    // Mostrar mensaje de "sin resultados"
    let noResults = select.querySelector('.searchable-select-no-results');
    if (visibleCount === 0) {
        if (!noResults) {
            noResults = document.createElement('div');
            noResults.className = 'searchable-select-no-results';
            noResults.textContent = 'No se encontraron ubicaciones';
            select.querySelector('.searchable-select-options').appendChild(noResults);
        }
        noResults.style.display = 'block';
    } else if (noResults) {
        noResults.style.display = 'none';
    }
}

function selectSearchableOptionSearch(option, selectId) {
    const select = document.getElementById(selectId);
    const value = option.getAttribute('data-value');
    const departamento = option.getAttribute('data-departamento');
    const text = departamento || 'Todas las ubicaciones';

    // Actualizar el input hidden
    const hiddenInput = document.getElementById('ubicacion-hidden-search');
    if (hiddenInput) {
        hiddenInput.value = value;
    }

    // Actualizar el texto visible
    const trigger = select.querySelector('.searchable-select-text');
    if (trigger) {
        trigger.textContent = text;
    }

    // Limpiar hints y estados de todas las opciones
    select.querySelectorAll('.searchable-select-option').forEach(opt => {
        opt.classList.remove('selected', 'match-child', 'hidden');
        const hint = opt.querySelector('.match-hint');
        if (hint) hint.remove();
    });
    option.classList.add('selected');

    // Cerrar el dropdown
    select.classList.remove('open');

    // Limpiar el filtro
    const input = select.querySelector('.searchable-select-input');
    if (input) {
        input.value = '';
    }

    // Enviar el formulario
    const form = select.closest('form');
    if (form) {
        form.submit();
    }
}

// Cerrar el select al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.searchable-select')) {
        document.querySelectorAll('.searchable-select.open').forEach(select => {
            select.classList.remove('open');
        });
    }
});

// Cerrar el select con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.searchable-select.open').forEach(select => {
            select.classList.remove('open');
        });
    }
});

// ==========================================
// Funciones originales
// ==========================================

// Funciones para interacciones estilo Facebook (reutilizadas de archive-trabajo.php)
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
            if (data.is_favorite) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
            
            const currentCount = parseInt(btnCount.getAttribute('data-count') || 0);
            const newCount = data.is_favorite ? currentCount + 1 : Math.max(0, currentCount - 1);
            btnCount.setAttribute('data-count', newCount);
            
            if (newCount > 0) {
                btnCount.textContent = newCount;
            } else {
                btnCount.textContent = '';
            }
            
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
                            refreshAllCounters(parseInt(jobId));
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

