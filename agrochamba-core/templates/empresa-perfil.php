<?php
/**
 * Template Name: Perfil de Empresa
 * 
 * Template para mostrar el perfil público de una empresa
 * estilo Computrabajo con tabs, evaluaciones y ofertas
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$empresa_id = get_the_ID();
$empresa = get_post($empresa_id);

if (!$empresa || $empresa->post_type !== 'empresa') {
    get_template_part('404');
    get_footer();
    return;
}

// Obtener datos de la empresa
$nombre_comercial = get_post_meta($empresa_id, '_empresa_nombre_comercial', true) ?: $empresa->post_title;
$razon_social = get_post_meta($empresa_id, '_empresa_razon_social', true);
$ruc = get_post_meta($empresa_id, '_empresa_ruc', true);
$sector = get_post_meta($empresa_id, '_empresa_sector', true);
$verificada = get_post_meta($empresa_id, '_empresa_verificada', true) === '1';
$empleados = get_post_meta($empresa_id, '_empresa_empleados', true);
$ciudad = get_post_meta($empresa_id, '_empresa_ciudad', true);
$provincia = get_post_meta($empresa_id, '_empresa_provincia', true);
$direccion = get_post_meta($empresa_id, '_empresa_direccion', true);
$telefono = get_post_meta($empresa_id, '_empresa_telefono', true);
$celular = get_post_meta($empresa_id, '_empresa_celular', true);
$email_contacto = get_post_meta($empresa_id, '_empresa_email_contacto', true);
$logo_id = get_post_thumbnail_id($empresa_id);
$logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : null;

// Obtener término de empresa asociado al CPT empresa
$empresa_terms = wp_get_post_terms($empresa_id, 'empresa');
$empresa_term_id = !empty($empresa_terms) && !is_wp_error($empresa_terms) ? $empresa_terms[0]->term_id : null;

// Obtener ofertas activas - filtrar SOLO por esta empresa específica
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$ofertas_args = array(
    'post_type' => 'trabajo',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC',
);

// Filtrar por empresa: usar empresa_id como método principal
// Si hay taxonomía y no hay resultados, usar taxonomía como respaldo

$meta_query = array(
    'relation' => 'AND',
    // Estado activo
    array(
        'relation' => 'OR',
        array(
            'key' => 'estado',
            'value' => 'activa',
            'compare' => '=',
        ),
        array(
            'key' => 'estado',
            'compare' => 'NOT EXISTS',
        ),
    ),
    // Empresa: DEBE tener empresa_id igual al ID de esta empresa
    array(
        'key' => 'empresa_id',
        'value' => $empresa_id,
        'compare' => '=',
        'type' => 'NUMERIC',
    ),
);

$ofertas_args['meta_query'] = $meta_query;

// Ejecutar query principal
$ofertas_query_temp = new WP_Query($ofertas_args);

// Si no hay resultados y hay taxonomía, intentar con taxonomía como respaldo
if (!$ofertas_query_temp->have_posts() && $empresa_term_id) {
    // Query alternativa usando solo taxonomía
    $ofertas_args_alt = array(
        'post_type' => 'trabajo',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'paged' => $paged,
        'orderby' => 'date',
        'order' => 'DESC',
        'tax_query' => array(
            array(
                'taxonomy' => 'empresa',
                'field' => 'term_id',
                'terms' => $empresa_term_id,
            ),
        ),
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'estado',
                'value' => 'activa',
                'compare' => '=',
            ),
            array(
                'key' => 'estado',
                'compare' => 'NOT EXISTS',
            ),
        ),
    );
    
    $ofertas_query = new WP_Query($ofertas_args_alt);
} else {
    // Usar la query principal
    $ofertas_query = $ofertas_query_temp;
}

$ofertas_count = $ofertas_query->found_posts;

// Obtener contador de seguidores
$followers_count = get_post_meta($empresa_id, '_empresa_followers_count', true);
if ($followers_count === '' || $followers_count === false) {
    $followers = get_post_meta($empresa_id, '_empresa_followers', true);
    $followers_count = is_array($followers) ? count($followers) : 0;
    // Actualizar el meta para futuras consultas
    if ($followers_count > 0) {
        update_post_meta($empresa_id, '_empresa_followers_count', $followers_count);
    }
}
$followers_count = intval($followers_count);

// Verificar si el usuario actual sigue esta empresa
$is_following = false;
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $following = get_user_meta($user_id, 'following_companies', true);
    if (is_array($following) && in_array($empresa_id, $following)) {
        $is_following = true;
    }
}

// Tabs activos
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'empresa';

?>
<div class="empresa-perfil-wrapper">
    <!-- Header Principal -->
    <div class="empresa-header-main">
        <div class="empresa-header-content">
            <div class="empresa-logo-section">
                <?php if ($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($nombre_comercial); ?>" class="empresa-logo-img" />
                <?php else: ?>
                    <div class="empresa-logo-placeholder">
                        <span class="logo-icon"><?php echo esc_html(substr($nombre_comercial, 0, 1)); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="empresa-header-info">
                <div class="empresa-title-row">
                    <h1 class="empresa-nombre"><?php echo esc_html($nombre_comercial); ?></h1>
                    <?php if ($verificada): ?>
                        <span class="badge-verificado-header" title="Empresa verificada">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 0L10.5 5.5L16 6L12 10L13 16L8 13L3 16L4 10L0 6L5.5 5.5L8 0Z"/>
                            </svg>
                            Verificada
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($razon_social): ?>
                    <p class="empresa-razon-social"><?php echo esc_html($razon_social); ?></p>
                <?php endif; ?>
                <div class="empresa-stats">
                    <span class="empresa-seguidores">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                        </svg>
                        <span id="seguidores-count"><?php echo esc_html($followers_count); ?></span> seguidores
                    </span>
                </div>
                <div class="empresa-header-actions">
                    <?php if (is_user_logged_in()): ?>
                        <button class="btn-seguir <?php echo $is_following ? 'siguiendo' : ''; ?>" onclick="toggleSeguir()" id="btn-seguir">
                            <span id="seguir-text"><?php echo $is_following ? 'Siguiendo' : '+ Seguir'; ?></span>
                        </button>
                    <?php else: ?>
                        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn-seguir">
                            <span>+ Seguir</span>
                        </a>
                    <?php endif; ?>
                    
                    <!-- Botón de compartir -->
                    <button class="btn-compartir" onclick="compartirEmpresa()" title="Compartir empresa">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="18" cy="5" r="3"/>
                            <circle cx="6" cy="12" r="3"/>
                            <circle cx="18" cy="19" r="3"/>
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                        </svg>
                        Compartir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs de Navegación -->
    <div class="empresa-tabs-nav">
        <a href="?tab=empresa" class="empresa-tab <?php echo $active_tab === 'empresa' ? 'active' : ''; ?>">
            La empresa
        </a>
        <a href="?tab=ofertas" class="empresa-tab <?php echo $active_tab === 'ofertas' ? 'active' : ''; ?>">
            Ofertas <span class="tab-count"><?php echo esc_html($ofertas_count); ?></span>
        </a>
        <a href="?tab=evaluaciones" class="empresa-tab <?php echo $active_tab === 'evaluaciones' ? 'active' : ''; ?>">
            Evaluaciones <span class="tab-count">0</span>
        </a>
        <a href="?tab=salarios" class="empresa-tab <?php echo $active_tab === 'salarios' ? 'active' : ''; ?>">
            Salarios <span class="tab-count">0</span>
        </a>
        <a href="?tab=entrevistas" class="empresa-tab <?php echo $active_tab === 'entrevistas' ? 'active' : ''; ?>">
            Entrevistas <span class="tab-count">0</span>
        </a>
        <a href="?tab=beneficios" class="empresa-tab <?php echo $active_tab === 'beneficios' ? 'active' : ''; ?>">
            Beneficios <span class="tab-count">0</span>
        </a>
        <a href="?tab=fotos" class="empresa-tab <?php echo $active_tab === 'fotos' ? 'active' : ''; ?>">
            Fotos
        </a>
    </div>

    <!-- Contenido Principal -->
    <div class="empresa-content-main">
        <?php if ($active_tab === 'empresa'): ?>
            <!-- Tab: La Empresa -->
            <div class="empresa-tab-content">
                <div class="empresa-content-left">
                    <!-- Acerca de -->
                    <div class="empresa-section">
                        <h2 class="section-title">Acerca de <?php echo esc_html($nombre_comercial); ?></h2>
                        <div class="empresa-descripcion-text">
                            <?php if ($empresa->post_content): ?>
                                <?php echo wp_kses_post(wpautop($empresa->post_content)); ?>
                            <?php else: ?>
                                <p>No hay descripción disponible.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="empresa-info-grid">
                            <?php if ($sector): ?>
                                <div class="info-item">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 2L3 7v11h4v-6h6v6h4V7l-7-5z"/>
                                    </svg>
                                    <div>
                                        <strong>Industria</strong>
                                        <p><?php echo esc_html($sector); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($empleados): ?>
                                <div class="info-item">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM17 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 0 0-1.5-4.33A5 5 0 0 1 19 16v1h-6.07zM6 11a5 5 0 0 1 5 5v1H1v-1a5 5 0 0 1 5-5z"/>
                                    </svg>
                                    <div>
                                        <strong>Tamaño</strong>
                                        <p><?php echo esc_html($empleados); ?> empleados</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($ruc): ?>
                                <div class="info-item">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M4 4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4zm2 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H6zm5 0a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-1z"/>
                                    </svg>
                                    <div>
                                        <strong>RUC</strong>
                                        <p><?php echo esc_html($ruc); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($ciudad || $provincia): ?>
                                <div class="info-item">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 2a5 5 0 0 0-5 5c0 4.5 5 10 5 10s5-5.5 5-10a5 5 0 0 0-5-5zm0 7a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/>
                                    </svg>
                                    <div>
                                        <strong>Ubicación</strong>
                                        <p><?php 
                                            $ubicacion_completa = trim(($ciudad ? $ciudad : '') . ($provincia ? ', ' . $provincia : ''));
                                            echo esc_html($ubicacion_completa ?: 'No especificada');
                                        ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($direccion): ?>
                                <div class="info-item">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10.707 2.293a1 1 0 0 0-1.414 0l-7 7a1 1 0 0 0 1.414 1.414L4 10.414V17a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2a1 1 0 0 1 1-1h2a1 1 0 0 1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-6.586l.293.293a1 1 0 0 0 1.414-1.414l-7-7z"/>
                                    </svg>
                                    <div>
                                        <strong>Dirección</strong>
                                        <p><?php echo esc_html($direccion); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($telefono || $celular): ?>
                                <div class="info-item">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2 3a1 1 0 0 1 1-1h2.153a1 1 0 0 1 .986.836l.74 4.435a1 1 0 0 1-.54 1.06l-1.548.773a11.037 11.037 0 0 0 6.105 6.105l.774-1.548a1 1 0 0 1 1.06-.54l4.435.74a1 1 0 0 1 .836.986V17a1 1 0 0 1-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                    </svg>
                                    <div>
                                        <strong>Contacto</strong>
                                        <p>
                                            <?php if ($telefono): ?>
                                                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $telefono)); ?>" class="contact-link"><?php echo esc_html($telefono); ?></a>
                                            <?php endif; ?>
                                            <?php if ($celular): ?>
                                                <?php if ($telefono): ?> / <?php endif; ?>
                                                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $celular)); ?>" class="contact-link"><?php echo esc_html($celular); ?></a>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($email_contacto): ?>
                                <div class="info-item">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0 0 16 4H4a2 2 0 0 0-1.997 1.884z"/>
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8.118z"/>
                                    </svg>
                                    <div>
                                        <strong>Email</strong>
                                        <p><a href="mailto:<?php echo esc_attr($email_contacto); ?>" class="contact-link"><?php echo esc_html($email_contacto); ?></a></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="empresa-content-right">
                    <!-- Evaluación General -->
                    <div class="empresa-section rating-section">
                        <h3 class="section-title">Evaluación general</h3>
                        <div class="rating-main">
                            <div class="rating-score">4.2</div>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="<?php echo $i <= 4 ? '#FFB800' : '#E0E0E0'; ?>">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <p class="rating-count">0 evaluaciones</p>
                        </div>
                        
                        <div class="rating-distribution">
                            <?php 
                            $distributions = [
                                ['stars' => 5, 'percent' => 0],
                                ['stars' => 4, 'percent' => 0],
                                ['stars' => 3, 'percent' => 0],
                                ['stars' => 2, 'percent' => 0],
                                ['stars' => 1, 'percent' => 0],
                            ];
                            foreach ($distributions as $dist): 
                            ?>
                                <div class="rating-bar-item">
                                    <span class="bar-label"><?php echo esc_html($dist['stars']); ?> estrellas</span>
                                    <div class="bar-container">
                                        <div class="bar-fill" style="width: <?php echo esc_attr($dist['percent']); ?>%"></div>
                                    </div>
                                    <span class="bar-percent"><?php echo esc_html($dist['percent']); ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Tu opinión cuenta -->
                    <div class="empresa-section opinion-section">
                        <h3 class="section-title">Tu opinión cuenta</h3>
                        <p class="opinion-text">Tu opinión le importa a millones de personas! Evalúa esta empresa de forma anónima.</p>
                        <div class="rating-input">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#FFB800" stroke-width="2" class="star-input" data-rating="<?php echo esc_attr($i); ?>">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>
                        <button class="btn-evaluar">Evaluar empresa</button>
                    </div>
                </div>
            </div>

        <?php elseif ($active_tab === 'ofertas'): ?>
            <!-- Tab: Ofertas -->
            <div class="empresa-tab-content ofertas-tab-full">
                <h2 class="section-title">Ofertas de Trabajo (<?php echo esc_html($ofertas_count); ?>)</h2>
                <?php if ($ofertas_query->have_posts()): ?>
                    <div class="ofertas-grid">
                        <?php while ($ofertas_query->have_posts()): $ofertas_query->the_post(); 
                            $trabajo_id = get_the_ID();
                            $salario_min = get_post_meta($trabajo_id, 'salario_min', true);
                            $salario_max = get_post_meta($trabajo_id, 'salario_max', true);
                            $vacantes = get_post_meta($trabajo_id, 'vacantes', true);
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
                            
                            $post_date = get_the_date('U');
                            $hours_since = (time() - $post_date) / 3600;
                            $badge = null;
                            $badge_class = '';
                            
                            if ($hours_since <= 48) {
                                $badge = 'Nuevo';
                                $badge_class = 'badge-new';
                            } elseif (($vacantes && intval($vacantes) >= 5) || ($salario_min && intval($salario_min) >= 3000)) {
                                $badge = 'Urgente';
                                $badge_class = 'badge-urgent';
                            }
                            
                            $excerpt = get_the_excerpt();
                            if (empty($excerpt)) {
                                $excerpt = wp_trim_words(get_the_content(), 20);
                            }
                        ?>
                            <article class="oferta-card">
                                <a href="<?php the_permalink(); ?>" class="oferta-card-link">
                                    <?php if ($featured_image_url): ?>
                                        <div class="oferta-card-image">
                                            <img src="<?php echo esc_url($featured_image_url); ?>" 
                                                 alt="<?php echo esc_attr(get_the_title()); ?>"
                                                 loading="lazy">
                                            <?php if ($badge): ?>
                                                <span class="oferta-badge <?php echo esc_attr($badge_class); ?>">
                                                    <?php echo esc_html($badge); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="oferta-card-image-placeholder">
                                            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                                <polyline points="21 15 16 10 5 21"/>
                                            </svg>
                                            <?php if ($badge): ?>
                                                <span class="oferta-badge <?php echo esc_attr($badge_class); ?>">
                                                    <?php echo esc_html($badge); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="oferta-card-content">
                                        <h3 class="oferta-card-title"><?php the_title(); ?></h3>
                                        
                                        <div class="oferta-card-info">
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
                                            <p class="oferta-card-excerpt"><?php echo esc_html(wp_trim_words($excerpt, 15)); ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="oferta-card-footer">
                                            <span class="oferta-card-date">
                                                <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' atrás'; ?>
                                            </span>
                                            <?php if ($vacantes && intval($vacantes) > 1): ?>
                                                <span class="oferta-card-vacantes">
                                                    <?php echo esc_html($vacantes); ?> vacantes
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </article>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Paginación -->
                    <?php if ($ofertas_query->max_num_pages > 1): ?>
                        <div class="ofertas-pagination">
                            <?php
                            echo paginate_links(array(
                                'total' => $ofertas_query->max_num_pages,
                                'current' => max(1, $paged),
                                'format' => '?tab=ofertas&paged=%#%',
                                'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> Anterior',
                                'next_text' => 'Siguiente <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>',
                                'type' => 'list',
                            ));
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php wp_reset_postdata(); ?>
                <?php else: ?>
                    <div class="ofertas-empty">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                        </svg>
                        <h3>No hay ofertas activas</h3>
                        <p>Esta empresa no tiene ofertas de trabajo disponibles en este momento.</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- Otros tabs (placeholder) -->
            <div class="empresa-tab-content">
                <p>Esta sección estará disponible próximamente.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.empresa-perfil-wrapper {
    background: #f5f5f5;
    min-height: 100vh;
}

.empresa-header-main {
    background: #fff;
    border-bottom: 1px solid #e0e0e0;
    padding: 30px 0;
}

.empresa-header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    gap: 30px;
    align-items: flex-start;
}

.empresa-logo-section {
    flex-shrink: 0;
}

.empresa-logo-img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
}

.empresa-logo-placeholder {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 48px;
    font-weight: bold;
}

.empresa-header-info {
    flex: 1;
}

.empresa-title-row {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
}

.empresa-nombre {
    margin: 0;
    font-size: 32px;
    font-weight: 600;
    color: #1a1a1a;
}

.badge-verificado-header {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #00a32a;
    color: #fff;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 500;
}

.empresa-razon-social {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 16px;
}

.empresa-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.empresa-seguidores {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
    font-size: 14px;
}

.empresa-header-actions {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: 15px;
}

.btn-seguir {
    background: #0066cc;
    color: #fff;
    border: none;
    padding: 10px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
    display: inline-block;
}

.btn-seguir:hover {
    background: #0052a3;
}

.btn-seguir.siguiendo {
    background: #666;
}

.btn-compartir {
    background: #fff;
    color: #0066cc;
    border: 2px solid #0066cc;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-compartir:hover {
    background: #0066cc;
    color: #fff;
}

.btn-compartir svg {
    flex-shrink: 0;
}

.empresa-tabs-nav {
    background: #fff;
    border-bottom: 1px solid #e0e0e0;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    gap: 0;
    overflow-x: auto;
}

.empresa-tab {
    padding: 15px 20px;
    text-decoration: none;
    color: #666;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    white-space: nowrap;
}

.empresa-tab:hover {
    color: #0066cc;
}

.empresa-tab.active {
    color: #0066cc;
    border-bottom-color: #0066cc;
}

.tab-count {
    color: #999;
    font-weight: normal;
}

.empresa-content-main {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
}

.empresa-tab-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.empresa-section {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.section-title {
    margin: 0 0 20px 0;
    font-size: 24px;
    font-weight: 600;
    color: #1a1a1a;
}

.empresa-descripcion-text {
    line-height: 1.8;
    color: #333;
    margin-bottom: 30px;
}

.empresa-info-grid {
    display: grid;
    gap: 20px;
}

.info-item {
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.info-item svg {
    color: #0066cc;
    flex-shrink: 0;
    margin-top: 2px;
}

.info-item strong {
    display: block;
    color: #1a1a1a;
    margin-bottom: 5px;
}

.info-item p {
    margin: 0;
    color: #666;
}

.contact-link {
    color: #0066cc;
    text-decoration: none;
    transition: color 0.2s ease;
}

.contact-link:hover {
    color: #0052a3;
    text-decoration: underline;
}

.rating-section {
    text-align: center;
}

.rating-main {
    margin-bottom: 30px;
}

.rating-score {
    font-size: 48px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 10px;
}

.rating-stars {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-bottom: 10px;
}

.rating-count {
    color: #666;
    font-size: 14px;
    margin: 0;
}

.rating-distribution {
    text-align: left;
    margin-top: 20px;
}

.rating-bar-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.bar-label {
    width: 80px;
    font-size: 13px;
    color: #666;
}

.bar-container {
    flex: 1;
    height: 8px;
    background: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: #00a32a;
    transition: width 0.3s;
}

.bar-percent {
    width: 40px;
    text-align: right;
    font-size: 13px;
    color: #666;
}

.opinion-section {
    text-align: center;
}

.opinion-text {
    color: #666;
    margin-bottom: 20px;
    line-height: 1.6;
}

.rating-input {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-bottom: 20px;
}

.star-input {
    cursor: pointer;
    transition: fill 0.2s;
}

.star-input:hover {
    fill: #FFB800;
}

.btn-evaluar {
    width: 100%;
    background: #0066cc;
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-evaluar:hover {
    background: #0052a3;
}

.ofertas-tab-full {
    grid-template-columns: 1fr !important;
}

.ofertas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.ofertas-pagination {
    margin-top: 40px;
    display: flex;
    justify-content: center;
}

.ofertas-pagination .page-numbers {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}

.ofertas-pagination .page-numbers li {
    margin: 0;
}

.ofertas-pagination .page-numbers a,
.ofertas-pagination .page-numbers span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    background: #fff;
    border: 1px solid #e0e0e0;
    transition: all 0.2s;
    font-weight: 500;
}

.ofertas-pagination .page-numbers a:hover {
    background: #0066cc;
    color: #fff;
    border-color: #0066cc;
}

.ofertas-pagination .page-numbers .current {
    background: #0066cc;
    color: #fff;
    border-color: #0066cc;
}

.ofertas-pagination .page-numbers .prev,
.ofertas-pagination .page-numbers .next {
    font-weight: 600;
}

.ofertas-pagination .page-numbers svg {
    flex-shrink: 0;
}

.oferta-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.oferta-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.oferta-card-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.oferta-card-image {
    position: relative;
    width: 100%;
    height: 200px;
    overflow: hidden;
    background: #f0f0f0;
}

.oferta-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.oferta-card:hover .oferta-card-image img {
    transform: scale(1.05);
}

.oferta-card-image-placeholder {
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    position: relative;
}

.oferta-badge {
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

.oferta-card-content {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.oferta-card-title {
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

.oferta-card-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 12px;
}

.oferta-card-info .info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 14px;
}

.oferta-card-info .info-item svg {
    flex-shrink: 0;
    color: #0066cc;
}

.oferta-card-excerpt {
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

.oferta-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
    font-size: 12px;
    color: #999;
}

.oferta-card-vacantes {
    background: #f0f0f0;
    padding: 4px 10px;
    border-radius: 12px;
    font-weight: 500;
    color: #666;
}

.ofertas-empty {
    text-align: center;
    padding: 80px 20px;
    color: #999;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.ofertas-empty svg {
    margin-bottom: 20px;
    opacity: 0.5;
}

.ofertas-empty h3 {
    font-size: 24px;
    margin: 0 0 10px 0;
    color: #666;
}

.ofertas-empty p {
    font-size: 16px;
    margin: 0;
}

@media (max-width: 968px) {
    .empresa-tab-content {
        grid-template-columns: 1fr;
    }
    
    .empresa-header-content {
        flex-direction: column;
    }
    
    .empresa-logo-section {
        align-self: center;
    }
}

@media (max-width: 968px) {
    .empresa-tab-content {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .empresa-nombre {
        font-size: 24px;
    }
    
    .empresa-tabs-nav {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .ofertas-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .oferta-card-image,
    .oferta-card-image-placeholder {
        height: 180px;
    }
}
</style>

<script>
const empresaId = <?php echo esc_js($empresa_id); ?>;
const isFollowing = <?php echo $is_following ? 'true' : 'false'; ?>;

async function toggleSeguir() {
    const btn = document.getElementById('btn-seguir');
    const text = document.getElementById('seguir-text');
    const countEl = document.getElementById('seguidores-count');
    
    if (!btn) return;
    
    // Deshabilitar botón mientras se procesa
    btn.disabled = true;
    const originalText = text.textContent;
    text.textContent = '...';
    
    try {
        const response = await fetch('<?php echo esc_url(rest_url('agrochamba/v1/companies/' . $empresa_id . '/follow')); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            },
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Actualizar estado visual
            if (data.is_following) {
                btn.classList.add('siguiendo');
                text.textContent = 'Siguiendo';
            } else {
                btn.classList.remove('siguiendo');
                text.textContent = '+ Seguir';
            }
            
            // Actualizar contador
            if (countEl) {
                countEl.textContent = data.followers_count || 0;
            }
        } else {
            // Error
            alert(data.message || 'Error al actualizar el seguimiento');
            text.textContent = originalText;
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión. Por favor, intenta nuevamente.');
        text.textContent = originalText;
    } finally {
        btn.disabled = false;
    }
}

// Cargar estado inicial al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // El estado ya está cargado desde PHP, pero podemos verificar si hay cambios
    <?php if (is_user_logged_in()): ?>
    // Opcional: verificar estado desde el servidor para asegurar sincronización
    fetch('<?php echo esc_url(rest_url('agrochamba/v1/companies/' . $empresa_id . '/follow-status')); ?>', {
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.is_following !== undefined) {
            const btn = document.getElementById('btn-seguir');
            const text = document.getElementById('seguir-text');
            const countEl = document.getElementById('seguidores-count');
            
            if (btn && text) {
                if (data.is_following) {
                    btn.classList.add('siguiendo');
                    text.textContent = 'Siguiendo';
                } else {
                    btn.classList.remove('siguiendo');
                    text.textContent = '+ Seguir';
                }
            }
            
            if (countEl && data.followers_count !== undefined) {
                countEl.textContent = data.followers_count;
            }
        }
    })
    .catch(error => {
        console.error('Error al verificar estado:', error);
    });
    <?php endif; ?>
});

// Rating stars interaction
document.querySelectorAll('.star-input').forEach(star => {
    star.addEventListener('click', function() {
        const rating = parseInt(this.dataset.rating);
        // Aquí puedes agregar lógica para guardar la evaluación
        console.log('Rating seleccionado:', rating);
    });
});

// Función para compartir empresa
function compartirEmpresa() {
    const empresaNombre = '<?php echo esc_js($nombre_comercial); ?>';
    const empresaUrl = '<?php echo esc_url(get_permalink($empresa_id)); ?>';
    const empresaDescripcion = '<?php echo esc_js(wp_trim_words(get_the_excerpt($empresa_id), 20)); ?>';
    
    // Verificar si el navegador soporta Web Share API
    if (navigator.share) {
        navigator.share({
            title: empresaNombre + ' - AgroChamba',
            text: empresaDescripcion,
            url: empresaUrl
        }).catch(err => {
            console.log('Error al compartir:', err);
            // Fallback: copiar al portapapeles
            copiarEnlace(empresaUrl);
        });
    } else {
        // Fallback: mostrar opciones de compartir
        mostrarOpcionesCompartir(empresaNombre, empresaUrl);
    }
}

// Función para mostrar opciones de compartir
function mostrarOpcionesCompartir(nombre, url) {
    // Crear modal de opciones de compartir
    const modal = document.createElement('div');
    modal.className = 'share-modal';
    modal.innerHTML = `
        <div class="share-modal-content">
            <div class="share-modal-header">
                <h3>Compartir empresa</h3>
                <button class="share-modal-close" onclick="this.closest('.share-modal').remove()">×</button>
            </div>
            <div class="share-modal-body">
                <div class="share-options">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}" 
                       target="_blank" 
                       class="share-option facebook"
                       onclick="this.closest('.share-modal').remove()">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?text=${encodeURIComponent(nombre)}&url=${encodeURIComponent(url)}" 
                       target="_blank" 
                       class="share-option twitter"
                       onclick="this.closest('.share-modal').remove()">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                        </svg>
                        Twitter
                    </a>
                    <a href="https://wa.me/?text=${encodeURIComponent(nombre + ' - ' + url)}" 
                       target="_blank" 
                       class="share-option whatsapp"
                       onclick="this.closest('.share-modal').remove()">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                        WhatsApp
                    </a>
                    <button class="share-option copy-link" onclick="copiarEnlace('${url}'); this.closest('.share-modal').remove();">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                        </svg>
                        Copiar enlace
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Cerrar al hacer clic fuera del modal
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// Función para copiar enlace al portapapeles
function copiarEnlace(url) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(() => {
            mostrarMensajeExito('¡Enlace copiado!');
        }).catch(() => {
            fallbackCopiar(url);
        });
    } else {
        fallbackCopiar(url);
    }
}

// Fallback para copiar (navegadores antiguos)
function fallbackCopiar(url) {
    const textarea = document.createElement('textarea');
    textarea.value = url;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand('copy');
        mostrarMensajeExito('¡Enlace copiado!');
    } catch (err) {
        alert('No se pudo copiar el enlace. Por favor, cópialo manualmente: ' + url);
    }
    document.body.removeChild(textarea);
}

// Mostrar mensaje de éxito
function mostrarMensajeExito(mensaje) {
    const toast = document.createElement('div');
    toast.className = 'share-toast';
    toast.textContent = mensaje;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}
</script>

<style>
.share-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    animation: fadeIn 0.2s;
}

.share-modal-content {
    background: white;
    border-radius: 12px;
    max-width: 400px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    animation: slideUp 0.3s;
}

.share-modal-header {
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.share-modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.share-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    line-height: 1;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s;
}

.share-modal-close:hover {
    background: #f0f0f0;
}

.share-modal-body {
    padding: 20px;
}

.share-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.share-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    font-weight: 500;
    transition: all 0.2s;
    border: 2px solid transparent;
    background: #f8f9fa;
}

.share-option:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

.share-option.facebook {
    color: #1877f2;
}

.share-option.twitter {
    color: #1da1f2;
}

.share-option.whatsapp {
    color: #25d366;
}

.share-option.copy-link {
    background: #0066cc;
    color: white;
    border: none;
    cursor: pointer;
    width: 100%;
}

.share-option.copy-link:hover {
    background: #0052a3;
}

.share-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #00a32a;
    color: white;
    padding: 14px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10001;
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.3s;
}

.share-toast.show {
    opacity: 1;
    transform: translateY(0);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .empresa-header-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-seguir,
    .btn-compartir {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php
get_footer();

