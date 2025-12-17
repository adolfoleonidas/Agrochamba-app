<?php
/**
 * Template para mostrar las empresas que sigue el usuario
 * URL: /mis-empresas/
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar si el usuario está logueado
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$user_id = get_current_user_id();
$following = get_user_meta($user_id, 'following_companies', true);
if (!is_array($following)) {
    $following = array();
}

?>
<div class="mis-empresas-wrapper">
    <!-- Header -->
    <div class="mis-empresas-header">
        <div class="header-content">
            <div class="page-badge">
                <span>Mis Empresas</span>
            </div>
            <h1 class="page-title">Empresas que sigo</h1>
            <p class="page-subtitle">Gestiona las empresas que sigues para recibir notificaciones de nuevas ofertas</p>
        </div>
    </div>

    <!-- Contenido -->
    <div class="mis-empresas-content">
        <?php if (empty($following)): ?>
            <!-- Estado vacío -->
            <div class="empty-state">
                <div class="empty-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <h2>No sigues ninguna empresa aún</h2>
                <p>Cuando sigas empresas, aparecerán aquí y recibirás notificaciones cuando publiquen nuevas ofertas.</p>
                <a href="<?php echo esc_url(get_post_type_archive_link('empresa')); ?>" class="btn-primary">
                    Explorar empresas
                </a>
            </div>
        <?php else: ?>
            <!-- Grid de empresas -->
            <div class="empresas-grid">
                <?php foreach ($following as $empresa_id): 
                    $empresa = get_post($empresa_id);
                    if (!$empresa || $empresa->post_type !== 'empresa' || $empresa->post_status !== 'publish') {
                        continue;
                    }

                    $logo_id = get_post_thumbnail_id($empresa_id);
                    $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
                    
                    $razon_social = get_post_meta($empresa_id, 'razon_social', true);
                    $empresa_name = get_the_title($empresa_id);
                    $display_name = !empty($razon_social) ? $razon_social : $empresa_name;

                    // Contar ofertas activas
                    $empresa_terms = wp_get_post_terms($empresa_id, 'empresa');
                    $ofertas_count = 0;
                    if (!empty($empresa_terms) && !is_wp_error($empresa_terms)) {
                        $empresa_term = $empresa_terms[0];
                        $ofertas_args = array(
                            'post_type' => 'trabajo',
                            'posts_per_page' => 1,
                            'post_status' => 'publish',
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'empresa',
                                    'field' => 'term_id',
                                    'terms' => $empresa_term->term_id,
                                ),
                            ),
                        );
                        $ofertas_query = new WP_Query($ofertas_args);
                        $ofertas_count = $ofertas_query->found_posts;
                    }

                    // Obtener seguidores
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

                    $empresa_permalink = get_permalink($empresa_id);
                ?>
                    <div class="empresa-card">
                        <a href="<?php echo esc_url($empresa_permalink); ?>" class="empresa-card-link">
                            <div class="empresa-card-image">
                                <?php if ($logo_url): ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($display_name); ?>">
                                <?php else: ?>
                                    <div class="empresa-card-placeholder">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <polyline points="21 15 16 10 5 21"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="empresa-card-content">
                                <h3 class="empresa-card-title"><?php echo esc_html($display_name); ?></h3>
                                <div class="empresa-card-stats">
                                    <span class="empresa-stat">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                        <?php echo esc_html($ofertas_count); ?> ofertas
                                    </span>
                                    <span class="empresa-stat">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                                        </svg>
                                        <?php echo esc_html($followers_count); ?> seguidores
                                    </span>
                                </div>
                            </div>
                        </a>
                        <div class="empresa-card-actions">
                            <a href="<?php echo esc_url($empresa_permalink); ?>" class="btn-view-empresa">
                                Ver perfil
                            </a>
                            <button class="btn-unfollow" onclick="toggleSeguirEmpresa(<?php echo esc_js($empresa_id); ?>, this)">
                                Dejar de seguir
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.mis-empresas-wrapper {
    min-height: 100vh;
    background: #f8f9fa;
    padding: 2rem 0;
}

.mis-empresas-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4rem 1rem;
    margin-bottom: 2rem;
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    text-align: center;
}

.page-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem;
}

.page-subtitle {
    font-size: 1.125rem;
    opacity: 0.9;
    margin: 0;
}

.mis-empresas-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.empty-icon {
    color: #667eea;
    margin-bottom: 1.5rem;
}

.empty-state h2 {
    font-size: 1.5rem;
    margin: 0 0 1rem;
    color: #333;
}

.empty-state p {
    color: #666;
    margin-bottom: 2rem;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.btn-primary {
    display: inline-block;
    background: #667eea;
    color: white;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-primary:hover {
    background: #5568d3;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.empresas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.empresa-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s;
}

.empresa-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.empresa-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.empresa-card-image {
    width: 100%;
    height: 200px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.empresa-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.empresa-card-placeholder {
    color: #999;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.empresa-card-content {
    padding: 1.5rem;
}

.empresa-card-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 1rem;
    color: #333;
}

.empresa-card-stats {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.empresa-stat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
    font-size: 0.875rem;
}

.empresa-stat svg {
    flex-shrink: 0;
}

.empresa-card-actions {
    padding: 0 1.5rem 1.5rem;
    display: flex;
    gap: 0.75rem;
}

.btn-view-empresa {
    flex: 1;
    display: inline-block;
    text-align: center;
    background: #667eea;
    color: white;
    padding: 0.75rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-view-empresa:hover {
    background: #5568d3;
}

.btn-unfollow {
    flex: 1;
    background: transparent;
    color: #dc3545;
    border: 2px solid #dc3545;
    padding: 0.75rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-unfollow:hover {
    background: #dc3545;
    color: white;
}

@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .empresas-grid {
        grid-template-columns: 1fr;
    }
    
    .empresa-card-actions {
        flex-direction: column;
    }
}
</style>

<script>
function toggleSeguirEmpresa(empresaId, button) {
    if (!confirm('¿Estás seguro de que quieres dejar de seguir esta empresa?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'toggle_follow_company');
    formData.append('empresa_id', empresaId);

    fetch('<?php echo esc_url(rest_url('agrochamba/v1/companies/')); ?>' + empresaId + '/follow', {
        method: 'POST',
        headers: {
            'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recargar la página para actualizar la lista
            window.location.reload();
        } else {
            alert('Error al dejar de seguir la empresa');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al dejar de seguir la empresa');
    });
}
</script>

<?php
get_footer();

