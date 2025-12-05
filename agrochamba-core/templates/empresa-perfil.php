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
$logo_id = get_post_thumbnail_id($empresa_id);
$logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : null;

// Obtener ofertas activas
$ofertas_query = agrochamba_get_empresa_ofertas($empresa_id, [
    'posts_per_page' => 20,
    'meta_query' => [
        [
            'key' => 'estado',
            'value' => 'activa',
            'compare' => '=',
        ],
    ],
]);

$ofertas_count = $ofertas_query->found_posts;

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
                        <span id="seguidores-count">0</span> seguidores
                    </span>
                </div>
                <button class="btn-seguir" onclick="toggleSeguir()">
                    <span id="seguir-text">+ Seguir</span>
                </button>
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
            <div class="empresa-tab-content">
                <h2 class="section-title">Ofertas de Trabajo (<?php echo esc_html($ofertas_count); ?>)</h2>
                <?php if ($ofertas_query->have_posts()): ?>
                    <div class="ofertas-list">
                        <?php while ($ofertas_query->have_posts()): $ofertas_query->the_post(); ?>
                            <div class="oferta-item">
                                <h3><a href="<?php echo esc_url(get_permalink()); ?>"><?php the_title(); ?></a></h3>
                                <div class="oferta-meta">
                                    <?php 
                                    $salario_min = get_post_meta(get_the_ID(), 'salario_min', true);
                                    $salario_max = get_post_meta(get_the_ID(), 'salario_max', true);
                                    if ($salario_min || $salario_max):
                                    ?>
                                        <span class="oferta-salario">
                                            <?php 
                                            if ($salario_min && $salario_max) {
                                                echo esc_html('S/ ' . $salario_min . ' - S/ ' . $salario_max);
                                            } elseif ($salario_min) {
                                                echo esc_html('Desde S/ ' . $salario_min);
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $ubicaciones = wp_get_post_terms(get_the_ID(), 'ubicacion');
                                    if (!empty($ubicaciones)):
                                    ?>
                                        <span class="oferta-ubicacion">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                                <path d="M8 0a5 5 0 0 0-5 5c0 4.5 5 10 5 10s5-5.5 5-10a5 5 0 0 0-5-5zm0 7a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/>
                                            </svg>
                                            <?php echo esc_html($ubicaciones[0]->name); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="oferta-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?></p>
                                <a href="<?php echo esc_url(get_permalink()); ?>" class="btn-ver-oferta">Ver oferta</a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <?php wp_reset_postdata(); ?>
                <?php else: ?>
                    <p>Esta empresa no tiene ofertas activas en este momento.</p>
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
}

.btn-seguir:hover {
    background: #0052a3;
}

.btn-seguir.siguiendo {
    background: #666;
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

.ofertas-list {
    display: grid;
    gap: 20px;
}

.oferta-item {
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.oferta-item h3 {
    margin: 0 0 15px 0;
    font-size: 20px;
}

.oferta-item h3 a {
    color: #0066cc;
    text-decoration: none;
}

.oferta-item h3 a:hover {
    text-decoration: underline;
}

.oferta-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.oferta-salario {
    color: #00a32a;
    font-weight: 600;
}

.oferta-ubicacion {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
}

.oferta-excerpt {
    color: #666;
    line-height: 1.6;
    margin-bottom: 15px;
}

.btn-ver-oferta {
    display: inline-block;
    background: #0066cc;
    color: #fff;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: background 0.2s;
}

.btn-ver-oferta:hover {
    background: #0052a3;
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

@media (max-width: 768px) {
    .empresa-nombre {
        font-size: 24px;
    }
    
    .empresa-tabs-nav {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}
</style>

<script>
function toggleSeguir() {
    const btn = document.querySelector('.btn-seguir');
    const text = document.getElementById('seguir-text');
    const countEl = document.getElementById('seguidores-count');
    
    if (btn.classList.contains('siguiendo')) {
        btn.classList.remove('siguiendo');
        text.textContent = '+ Seguir';
        countEl.textContent = parseInt(countEl.textContent) - 1;
    } else {
        btn.classList.add('siguiendo');
        text.textContent = 'Siguiendo';
        countEl.textContent = parseInt(countEl.textContent) + 1;
    }
}

// Rating stars interaction
document.querySelectorAll('.star-input').forEach(star => {
    star.addEventListener('click', function() {
        const rating = parseInt(this.dataset.rating);
        // Aquí puedes agregar lógica para guardar la evaluación
        console.log('Rating seleccionado:', rating);
    });
});
</script>

<?php
get_footer();

