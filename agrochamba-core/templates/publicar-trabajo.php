<?php
/**
 * Template Name: Publicar Trabajo - Empresas
 * 
 * Página personalizada para que empresas publiquen trabajos con todas las opciones de la app móvil
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Verificar si el usuario está logueado
$is_logged_in = is_user_logged_in();
$current_user_id = $is_logged_in ? get_current_user_id() : null;
$current_user = $is_logged_in ? wp_get_current_user() : null;
$is_admin = $is_logged_in && current_user_can('administrator');
// Verificar si es employer usando el rol directamente
$is_employer = $is_logged_in && ($is_admin || (isset($current_user->roles) && in_array('employer', $current_user->roles)));

// Obtener empresa del usuario si es employer
$user_company_id = null;
$user_company_term_id = null;
if ($is_employer && $current_user_id) {
    $user_company_term_id = get_user_meta($current_user_id, 'empresa_term_id', true);
    $empresa_cpt_id = get_user_meta($current_user_id, 'empresa_cpt_id', true);
    if ($empresa_cpt_id) {
        $user_company_id = $empresa_cpt_id;
    }
}

// Obtener taxonomías para los selectores
$ubicaciones = get_terms(array(
    'taxonomy' => 'ubicacion',
    'hide_empty' => false,
    'number' => 100,
    'orderby' => 'name',
    'order' => 'ASC',
));

$empresas = get_terms(array(
    'taxonomy' => 'empresa',
    'hide_empty' => false,
    'number' => 100,
    'orderby' => 'name',
    'order' => 'ASC',
));

$cultivos = get_terms(array(
    'taxonomy' => 'cultivo',
    'hide_empty' => false,
    'number' => 100,
    'orderby' => 'name',
    'order' => 'ASC',
));

$tipos_puesto = get_terms(array(
    'taxonomy' => 'tipo_puesto',
    'hide_empty' => false,
    'number' => 100,
    'orderby' => 'name',
    'order' => 'ASC',
));

$categorias = get_categories(array(
    'hide_empty' => false,
    'number' => 100,
    'orderby' => 'name',
    'order' => 'ASC',
));

// Obtener nonce para AJAX
$rest_nonce = wp_create_nonce('wp_rest');
$rest_url = rest_url('agrochamba/v1/');
?>

<div class="publicar-trabajo-wrapper">
    <div class="publicar-trabajo-container">
        <?php if (!$is_logged_in): ?>
            <!-- Mensaje para usuarios no logueados -->
            <div class="login-required-section">
                <div class="login-required-header">
                    <div class="login-required-icon">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h1 class="login-required-title">Acceso Requerido</h1>
                    <p class="login-required-subtitle">Para publicar trabajos necesitas tener una cuenta. Puedes registrarte como empresa o como usuario normal.</p>
                </div>
                
                <div class="login-required-actions">
                    <a href="<?php echo esc_url(home_url('/registro')); ?>" class="btn-primary btn-large">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <line x1="20" y1="8" x2="20" y2="14"/>
                            <line x1="23" y1="11" x2="17" y2="11"/>
                        </svg>
                        <span>Registrarse</span>
                    </a>
                    
                    <a href="<?php echo esc_url(wp_login_url(home_url('/publicar-trabajo'))); ?>" class="btn-secondary btn-large">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                        <span>Iniciar Sesión</span>
                    </a>
                </div>
                
                <div class="login-required-info">
                    <div class="info-card">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                        <div>
                            <h3>¿Ya tienes cuenta?</h3>
                            <p>Si ya estás registrado, solo necesitas iniciar sesión para acceder al panel de empresas.</p>
                        </div>
                    </div>
                    <div class="info-card">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <div>
                            <h3>Registro de Empresa</h3>
                            <p>Al registrarte, puedes elegir crear una cuenta de empresa para publicar trabajos, o una cuenta normal para comentar y buscar empleo.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif (!$is_employer): ?>
            <!-- Mensaje para usuarios normales (no empresas) -->
            <div class="login-required-section">
                <div class="login-required-header">
                    <div class="login-required-icon">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h1 class="login-required-title">Acceso Restringido</h1>
                    <p class="login-required-subtitle">Esta página es exclusiva para empresas. Solo las empresas pueden publicar y gestionar trabajos.</p>
                </div>
                
                <div class="login-required-actions">
                    <a href="<?php echo esc_url(home_url('/trabajos')); ?>" class="btn-primary btn-large">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                        </svg>
                        <span>Ver Trabajos Disponibles</span>
                    </a>
                    
                    <a href="<?php echo esc_url(home_url('/registro?role=employer')); ?>" class="btn-secondary btn-large">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="3" x2="9" y2="21"/>
                        </svg>
                        <span>Registrarse como Empresa</span>
                    </a>
                </div>
                
                <div class="login-required-info">
                    <div class="info-card">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                        <div>
                            <h3>¿Eres una empresa?</h3>
                            <p>Si quieres publicar trabajos, necesitas registrarte como empresa. Puedes hacerlo desde la página de registro.</p>
                        </div>
                    </div>
                    <div class="info-card">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <div>
                            <h3>¿Buscas trabajo?</h3>
                            <p>Si eres un trabajador buscando empleo, puedes explorar las ofertas disponibles en nuestra plataforma.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Dashboard de Empresa -->
            <div class="empresa-dashboard">
                <!-- Navegación por Tabs -->
                <div class="dashboard-nav">
                    <button class="nav-tab active" data-tab="dashboard">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                        </svg>
                        <span>Dashboard</span>
                    </button>
                    <button class="nav-tab" data-tab="mis-trabajos">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                        </svg>
                        <span>Mis Trabajos</span>
                    </button>
                    <button class="nav-tab" data-tab="crear-trabajo">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <span>Crear Trabajo</span>
                    </button>
                    <button class="nav-tab" data-tab="mi-empresa">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>Mi Empresa</span>
                    </button>
                </div>
                
                <!-- Contenido de Tabs -->
                <div class="dashboard-content">
                    <!-- Tab: Dashboard -->
                    <div class="tab-content active" id="tab-dashboard">
                        <div class="dashboard-header">
                            <h1 class="dashboard-title">Panel de Control</h1>
                            <p class="dashboard-subtitle">Bienvenido, <?php echo esc_html($current_user->display_name); ?></p>
                        </div>
                        
                        <div class="dashboard-stats" id="dashboard-stats">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                    </svg>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number" id="stat-total-jobs">-</div>
                                    <div class="stat-label">Total Trabajos</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number" id="stat-published-jobs">-</div>
                                    <div class="stat-label">Publicados</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number" id="stat-pending-jobs">-</div>
                                    <div class="stat-label">Pendientes</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number" id="stat-total-views">-</div>
                                    <div class="stat-label">Total Vistas</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="dashboard-actions">
                            <a href="#" class="action-card" data-tab="crear-trabajo">
                                <div class="action-icon">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"/>
                                        <line x1="5" y1="12" x2="19" y2="12"/>
                                    </svg>
                                </div>
                                <div class="action-content">
                                    <h3>Crear Nuevo Trabajo</h3>
                                    <p>Publica una nueva oferta de trabajo</p>
                                </div>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"/>
                                </svg>
                            </a>
                            <a href="#" class="action-card" data-tab="mis-trabajos">
                                <div class="action-icon">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                    </svg>
                                </div>
                                <div class="action-content">
                                    <h3>Gestionar Trabajos</h3>
                                    <p>Ver y editar tus trabajos publicados</p>
                                </div>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"/>
                                </svg>
                            </a>
                            <a href="#" class="action-card" data-tab="mi-empresa">
                                <div class="action-icon">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div class="action-content">
                                    <h3>Mi Perfil de Empresa</h3>
                                    <p>Actualiza la información de tu empresa</p>
                                </div>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Tab: Mis Trabajos -->
                    <div class="tab-content" id="tab-mis-trabajos">
                        <div class="dashboard-header">
                            <h1 class="dashboard-title">Mis Trabajos Publicados</h1>
                            <button class="btn-primary" data-tab="crear-trabajo">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                Crear Nuevo Trabajo
                            </button>
                        </div>
                        
                        <div id="mis-trabajos-list" class="mis-trabajos-list">
                            <div class="loading-spinner">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                                </svg>
                                <p>Cargando trabajos...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Mi Empresa -->
                    <div class="tab-content" id="tab-mi-empresa">
                        <div class="dashboard-header">
                            <h1 class="dashboard-title">Información de Mi Empresa</h1>
                            <p class="dashboard-subtitle">Actualiza los datos de tu empresa</p>
                        </div>
                        
                        <form id="empresa-profile-form" class="empresa-profile-form">
                            <div class="form-section">
                                <label for="company-name" class="section-label">Nombre de la Empresa</label>
                                <input type="text" id="company-name" name="company_name" value="<?php echo esc_attr($current_user->display_name); ?>" readonly>
                                <p class="field-hint">El nombre de la empresa no se puede cambiar</p>
                            </div>
                            
                            <div class="form-section">
                                <label for="company-email" class="section-label">Correo Electrónico</label>
                                <input type="email" id="company-email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" required>
                            </div>
                            
                            <div class="form-section">
                                <label for="company-description" class="section-label">Descripción</label>
                                <textarea id="company-description" name="description" rows="6" placeholder="Describe tu empresa, su historia, valores y lo que la hace especial..."></textarea>
                            </div>
                            
                            <div class="form-section">
                                <label for="company-phone" class="section-label">Teléfono</label>
                                <input type="tel" id="company-phone" name="phone" placeholder="Ej: +51 999 999 999">
                            </div>
                            
                            <div class="form-section">
                                <label for="company-address" class="section-label">Dirección</label>
                                <input type="text" id="company-address" name="address" placeholder="Dirección completa de la empresa">
                            </div>
                            
                            <div class="form-section">
                                <label for="company-website" class="section-label">Sitio Web</label>
                                <input type="url" id="company-website" name="website" placeholder="https://ejemplo.com">
                            </div>
                            
                            <div class="form-section">
                                <label class="section-label">Redes Sociales</label>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="company-facebook">Facebook</label>
                                        <input type="url" id="company-facebook" name="facebook" placeholder="https://facebook.com/...">
                                    </div>
                                    <div class="form-group">
                                        <label for="company-instagram">Instagram</label>
                                        <input type="url" id="company-instagram" name="instagram" placeholder="https://instagram.com/...">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="company-linkedin">LinkedIn</label>
                                        <input type="url" id="company-linkedin" name="linkedin" placeholder="https://linkedin.com/...">
                                    </div>
                                    <div class="form-group">
                                        <label for="company-twitter">Twitter</label>
                                        <input type="url" id="company-twitter" name="twitter" placeholder="https://twitter.com/...">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary" id="save-profile-btn">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                        <polyline points="17 21 17 13 7 13 7 21"/>
                                        <polyline points="7 3 7 8 15 8"/>
                                    </svg>
                                    <span class="btn-text">Guardar Cambios</span>
                                    <span class="btn-spinner" style="display: none;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                            
                            <div id="profile-error-message" class="error-message" style="display: none;"></div>
                            <div id="profile-success-message" class="success-message" style="display: none;"></div>
                        </form>
                    </div>
                    
                    <!-- Tab: Crear Trabajo -->
                    <div class="tab-content" id="tab-crear-trabajo">
                        <div class="form-header">
                            <h1 class="form-title">Publicar Nuevo Trabajo</h1>
                            <p class="form-subtitle">Completa todos los campos para publicar tu oferta de trabajo</p>
                        </div>
                
                <form id="publicar-trabajo-form" class="publicar-trabajo-form" enctype="multipart/form-data">
                    <!-- Galería de Imágenes -->
                    <div class="form-section">
                        <label class="section-label">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                            </svg>
                            Imágenes (máximo 10)
                        </label>
                        <div class="image-gallery-container">
                            <div class="image-gallery" id="image-gallery">
                                <!-- Las imágenes se agregarán aquí dinámicamente -->
                            </div>
                            <label for="image-upload" class="image-upload-btn" id="image-upload-label">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                <span>Agregar imagen</span>
                            </label>
                            <input type="file" id="image-upload" name="images[]" accept="image/*" multiple style="display: none;">
                        </div>
                        <p class="field-hint">La primera imagen será la portada</p>
                    </div>
                    
                    <!-- Título -->
                    <div class="form-section">
                        <label for="job-title" class="section-label">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                            </svg>
                            Título del Trabajo *
                        </label>
                        <input type="text" id="job-title" name="title" required maxlength="200" placeholder="Ej: Se busca personal para cosecha de uva en Ica">
                        <div class="title-counter">
                            <span id="title-length">0</span>/200 caracteres
                            <span id="title-seo-hint" class="seo-hint"></span>
                        </div>
                    </div>
                    
                    <!-- Descripción -->
                    <div class="form-section">
                        <label for="job-description" class="section-label">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10 9 9 9 8 9"/>
                            </svg>
                            Descripción del Trabajo *
                        </label>
                        <div class="tiptap-editor-wrapper">
                            <div id="tiptap-toolbar" class="tiptap-toolbar">
                                <button type="button" class="tiptap-btn" data-action="bold" title="Negrita">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/>
                                        <path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/>
                                    </svg>
                                </button>
                                <button type="button" class="tiptap-btn" data-action="italic" title="Cursiva">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="19" y1="4" x2="10" y2="4"/>
                                        <line x1="14" y1="20" x2="5" y2="20"/>
                                        <line x1="15" y1="4" x2="9" y2="20"/>
                                    </svg>
                                </button>
                                <button type="button" class="tiptap-btn" data-action="underline" title="Subrayado">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 3v7a6 6 0 0 0 6 6 6 6 0 0 0 6-6V3"/>
                                        <line x1="4" y1="21" x2="20" y2="21"/>
                                    </svg>
                                </button>
                                <div class="tiptap-divider"></div>
                                <button type="button" class="tiptap-btn" data-action="bulletList" title="Lista con viñetas">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="8" y1="6" x2="21" y2="6"/>
                                        <line x1="8" y1="12" x2="21" y2="12"/>
                                        <line x1="8" y1="18" x2="21" y2="18"/>
                                        <line x1="3" y1="6" x2="3.01" y2="6"/>
                                        <line x1="3" y1="12" x2="3.01" y2="12"/>
                                        <line x1="3" y1="18" x2="3.01" y2="18"/>
                                    </svg>
                                </button>
                                <button type="button" class="tiptap-btn" data-action="orderedList" title="Lista numerada">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="10" y1="6" x2="21" y2="6"/>
                                        <line x1="10" y1="12" x2="21" y2="12"/>
                                        <line x1="10" y1="18" x2="21" y2="18"/>
                                        <line x1="4" y1="6" x2="4" y2="6"/>
                                        <line x1="4" y1="12" x2="4" y2="12"/>
                                        <line x1="4" y1="18" x2="4" y2="18"/>
                                    </svg>
                                </button>
                            </div>
                            <div id="tiptap-editor" class="tiptap-editor"></div>
                            <textarea id="job-description" name="description" required style="display: none;"></textarea>
                        </div>
                        <p class="field-hint">Usa los botones de formato para resaltar texto importante</p>
                    </div>
                    
                    <!-- Tipo de Publicación (solo para admins) -->
                    <?php if ($is_admin): ?>
                    <div class="form-section">
                        <label class="section-label">Tipo de Publicación</label>
                        <div class="post-type-selector">
                            <label class="radio-option">
                                <input type="radio" name="post_type" value="trabajo" checked>
                                <span>📋 Trabajo</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="post_type" value="post">
                                <span>📝 Blog</span>
                            </label>
                        </div>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="post_type" value="trabajo">
                    <?php endif; ?>
                    
                    <!-- Ubicación (solo para trabajos) -->
                    <div class="form-section job-fields" id="ubicacion-section">
                        <label for="job-ubicacion" class="section-label">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            Ubicación *
                        </label>
                        <select id="job-ubicacion" name="ubicacion_id" required>
                            <option value="">Selecciona una ubicación</option>
                            <?php if (!empty($ubicaciones) && !is_wp_error($ubicaciones)): ?>
                                <?php foreach ($ubicaciones as $ubicacion): ?>
                                    <option value="<?php echo esc_attr($ubicacion->term_id); ?>">
                                        <?php echo esc_html($ubicacion->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <!-- Empresa (opcional, debajo de ubicación) -->
                    <div class="form-section job-fields" id="empresa-section">
                        <label for="job-empresa" class="section-label">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Empresa
                        </label>
                        <select id="job-empresa" name="empresa_id">
                            <option value="">Selecciona una empresa (opcional)</option>
                            <?php if (!empty($empresas) && !is_wp_error($empresas)): ?>
                                <?php foreach ($empresas as $empresa): ?>
                                    <option value="<?php echo esc_attr($empresa->term_id); ?>" 
                                            <?php echo ($user_company_term_id && $empresa->term_id == $user_company_term_id) ? 'selected' : ''; ?>>
                                        <?php echo esc_html($empresa->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <!-- Categoría (solo para blogs) -->
                    <div class="form-section blog-fields" id="categoria-section" style="display: none;">
                        <label for="job-categoria" class="section-label">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                            </svg>
                            Categoría
                        </label>
                        <select id="job-categoria" name="categoria_id">
                            <option value="">Selecciona una categoría (opcional)</option>
                            <?php if (!empty($categorias) && !is_wp_error($categorias)): ?>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo esc_attr($categoria->term_id); ?>">
                                        <?php echo esc_html($categoria->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <!-- Botón Más Detalles -->
                    <div class="form-section job-fields">
                        <button type="button" class="toggle-details-btn" id="toggle-details-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                            <span>Más Detalles</span>
                        </button>
                    </div>
                    
                    <!-- Campos Avanzados (colapsables) -->
                    <div class="advanced-fields job-fields" id="advanced-fields" style="display: none;">
                        <!-- Salario -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="job-salario-min">Salario Mínimo (S/)</label>
                                <input type="number" id="job-salario-min" name="salario_min" min="0" step="100">
                            </div>
                            <div class="form-group">
                                <label for="job-salario-max">Salario Máximo (S/)</label>
                                <input type="number" id="job-salario-max" name="salario_max" min="0" step="100">
                            </div>
                        </div>
                        
                        <!-- Vacantes -->
                        <div class="form-group">
                            <label for="job-vacantes">Número de Vacantes</label>
                            <input type="number" id="job-vacantes" name="vacantes" min="1" value="1">
                        </div>
                        
                        <!-- Cultivo -->
                        <div class="form-group">
                            <label for="job-cultivo">Cultivo</label>
                            <select id="job-cultivo" name="cultivo_id">
                                <option value="">Selecciona un cultivo (opcional)</option>
                                <?php if (!empty($cultivos) && !is_wp_error($cultivos)): ?>
                                    <?php foreach ($cultivos as $cultivo): ?>
                                        <option value="<?php echo esc_attr($cultivo->term_id); ?>">
                                            <?php echo esc_html($cultivo->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <!-- Tipo de Puesto -->
                        <div class="form-group">
                            <label for="job-tipo-puesto">Tipo de Puesto</label>
                            <select id="job-tipo-puesto" name="tipo_puesto_id">
                                <option value="">Selecciona un tipo de puesto (opcional)</option>
                                <?php if (!empty($tipos_puesto) && !is_wp_error($tipos_puesto)): ?>
                                    <?php foreach ($tipos_puesto as $tipo): ?>
                                        <option value="<?php echo esc_attr($tipo->term_id); ?>">
                                            <?php echo esc_html($tipo->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <!-- Beneficios -->
                        <div class="form-group">
                            <label class="section-label">Beneficios Incluidos</label>
                            <div class="benefits-checkboxes">
                                <label class="checkbox-option">
                                    <input type="checkbox" name="alojamiento" value="1">
                                    <span>🏠 Alojamiento</span>
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="transporte" value="1">
                                    <span>🚗 Transporte</span>
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="alimentacion" value="1">
                                    <span>🍽️ Alimentación</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Opciones Adicionales -->
                    <div class="form-section">
                        <div class="form-group">
                            <label class="checkbox-option">
                                <input type="checkbox" name="comentarios_habilitados" value="1" checked>
                                <span>💬 Permitir comentarios</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Botones de Acción -->
                    <div class="form-actions">
                        <button type="submit" class="btn-primary" id="publish-submit-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                            <span class="btn-text">Publicar Trabajo</span>
                            <span class="btn-spinner" style="display: none;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                                </svg>
                            </span>
                        </button>
                    </div>
                    
                    <div id="publish-error-message" class="error-message" style="display: none;"></div>
                    <div id="publish-success-message" class="success-message" style="display: none;"></div>
                </form>
                    </div>
                    
                    <!-- Tab: Editar Trabajo (se muestra dinámicamente) -->
                    <div class="tab-content" id="tab-editar-trabajo">
                        <div class="form-header">
                            <h1 class="form-title">Editar Trabajo</h1>
                            <button class="btn-secondary" onclick="showTab('mis-trabajos')">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15 18 9 12 15 6"/>
                                </svg>
                                Volver a Mis Trabajos
                            </button>
                        </div>
                        
                        <form id="editar-trabajo-form" class="publicar-trabajo-form" enctype="multipart/form-data">
                            <input type="hidden" id="edit-job-id" name="job_id">
                            <!-- El mismo formulario que crear trabajo, se llenará dinámicamente -->
                            <div id="edit-job-form-content">
                                <!-- Se llenará con JavaScript -->
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary" id="update-submit-btn">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                        <polyline points="17 21 17 13 7 13 7 21"/>
                                        <polyline points="7 3 7 8 15 8"/>
                                    </svg>
                                    <span class="btn-text">Actualizar Trabajo</span>
                                    <span class="btn-spinner" style="display: none;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                            
                            <div id="update-error-message" class="error-message" style="display: none;"></div>
                            <div id="update-success-message" class="success-message" style="display: none;"></div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.publicar-trabajo-wrapper {
    min-height: 100vh;
    background: #f5f5f5;
    padding: 40px 20px;
}

.publicar-trabajo-container {
    max-width: 900px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.form-header {
    background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
    padding: 40px;
    text-align: center;
    color: #fff;
}

.form-title {
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 8px 0;
}

.form-subtitle {
    font-size: 16px;
    opacity: 0.9;
    margin: 0;
}

.publicar-trabajo-form {
    padding: 40px;
}

/* Sección de Login Requerido */
.login-required-section {
    padding: 60px 40px;
    text-align: center;
}

.login-required-header {
    margin-bottom: 40px;
}

.login-required-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 24px;
    background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
    border-radius: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4CAF50;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
}

.login-required-title {
    font-size: 36px;
    font-weight: 800;
    color: #1a237e;
    margin: 0 0 12px 0;
}

.login-required-subtitle {
    font-size: 18px;
    color: #666;
    margin: 0;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}

.login-required-actions {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-width: 400px;
    margin: 0 auto 40px;
}

.btn-large {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 18px 32px;
    font-size: 18px;
    font-weight: 700;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-large:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

.btn-large svg {
    flex-shrink: 0;
}

.login-required-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    max-width: 800px;
    margin: 0 auto;
}

.info-card {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 24px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    text-align: left;
}

.info-card svg {
    flex-shrink: 0;
    color: #4CAF50;
    margin-top: 4px;
}

.info-card h3 {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin: 0 0 8px 0;
}

.info-card p {
    font-size: 14px;
    color: #666;
    margin: 0;
    line-height: 1.6;
}

.form-section {
    margin-bottom: 32px;
}

.section-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 12px;
}

.section-label svg {
    color: #4CAF50;
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #666;
    margin-bottom: 8px;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group input[type="number"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 150px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.title-counter {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
    font-size: 13px;
    color: #666;
}

.seo-hint {
    font-weight: 600;
}

.seo-hint.optimal {
    color: #4CAF50;
}

.seo-hint.warning {
    color: #FF9800;
}

.seo-hint.error {
    color: #D32F2F;
}

/* Galería de Imágenes */
.image-gallery-container {
    margin-bottom: 16px;
}

.image-gallery {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
}

.image-preview {
    position: relative;
    width: 100px;
    height: 100px;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e0e0e0;
}

.image-preview.featured {
    border-color: #4CAF50;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-preview .remove-image {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    background: rgba(0, 0, 0, 0.7);
    border: none;
    border-radius: 50%;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.image-preview .featured-badge {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(76, 175, 80, 0.9);
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    text-align: center;
    padding: 4px;
}

.image-upload-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: #f0f0f0;
    border: 2px dashed #ccc;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
    color: #666;
    font-weight: 500;
}

.image-upload-btn:hover {
    background: #e8f5e9;
    border-color: #4CAF50;
    color: #4CAF50;
}

.field-hint {
    font-size: 13px;
    color: #999;
    margin-top: 8px;
}

/* TipTap Editor */
.tiptap-editor-wrapper {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s;
}

.tiptap-editor-wrapper:focus-within {
    border-color: #4CAF50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.tiptap-toolbar {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 8px 12px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.tiptap-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
    background: transparent;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    color: #666;
    transition: all 0.2s;
}

.tiptap-btn:hover {
    background: #e8f5e9;
    color: #4CAF50;
}

.tiptap-btn.is-active {
    background: #4CAF50;
    color: #fff;
}

.tiptap-btn svg {
    flex-shrink: 0;
}

.tiptap-divider {
    width: 1px;
    height: 20px;
    background: #e0e0e0;
    margin: 0 4px;
}

.tiptap-editor {
    min-height: 200px;
    max-height: 400px;
    padding: 16px;
    overflow-y: auto;
    font-size: 16px;
    line-height: 1.6;
    color: #333;
    background: #fff;
}

.tiptap-editor:focus {
    outline: none;
}

.tiptap-editor p {
    margin: 0 0 12px 0;
}

.tiptap-editor p:last-child {
    margin-bottom: 0;
}

.tiptap-editor p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #999;
    pointer-events: none;
    height: 0;
}

.tiptap-editor ul,
.tiptap-editor ol {
    padding-left: 24px;
    margin: 12px 0;
}

.tiptap-editor li {
    margin: 4px 0;
}

.tiptap-editor strong {
    font-weight: 700;
}

.tiptap-editor em {
    font-style: italic;
}

.tiptap-editor u {
    text-decoration: underline;
}

/* Selector de Tipo de Publicación */
.post-type-selector {
    display: flex;
    gap: 12px;
}

.radio-option {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 500;
}

.radio-option input[type="radio"] {
    display: none;
}

.radio-option input[type="radio"]:checked + span {
    color: #4CAF50;
}

.radio-option:has(input[type="radio"]:checked) {
    border-color: #4CAF50;
    background: #e8f5e9;
}

/* Botón Más Detalles */
.toggle-details-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    background: #f0f0f0;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    color: #666;
    transition: all 0.3s;
}

.toggle-details-btn:hover {
    background: #e8f5e9;
    border-color: #4CAF50;
    color: #4CAF50;
}

.toggle-details-btn svg {
    transition: transform 0.3s;
}

.toggle-details-btn.expanded svg {
    transform: rotate(180deg);
}

/* Campos Avanzados */
.advanced-fields {
    background: #f8f9fa;
    padding: 24px;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    margin-top: 16px;
}

.benefits-checkboxes {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.checkbox-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #fff;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.checkbox-option:hover {
    border-color: #4CAF50;
    background: #f0f4f0;
}

.checkbox-option input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.checkbox-option input[type="checkbox"]:checked + span {
    color: #4CAF50;
    font-weight: 600;
}

.checkbox-option:has(input[type="checkbox"]:checked) {
    border-color: #4CAF50;
    background: #e8f5e9;
}

/* Botones */
.form-actions {
    margin-top: 40px;
    padding-top: 32px;
    border-top: 2px solid #f0f0f0;
}

.btn-primary {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 32px;
    background: #4CAF50;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.btn-primary:hover:not(:disabled) {
    background: #45a049;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(76, 175, 80, 0.4);
}

.btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.form-footer {
    text-align: center;
    margin-top: 20px;
    color: #666;
    font-size: 14px;
}

.form-footer a {
    color: #4CAF50;
    font-weight: 600;
    text-decoration: none;
}

.form-footer a:hover {
    text-decoration: underline;
}

.error-message,
.success-message {
    padding: 16px;
    border-radius: 12px;
    margin-top: 20px;
    font-weight: 500;
}

.error-message {
    background: #FFEBEE;
    color: #D32F2F;
    border: 1px solid #FFCDD2;
}

.success-message {
    background: #E8F5E9;
    color: #2E7D32;
    border: 1px solid #C8E6C9;
}

.field-error {
    display: block;
    color: #D32F2F;
    font-size: 13px;
    margin-top: 4px;
}

/* Dashboard de Empresa */
.empresa-dashboard {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.dashboard-nav {
    display: flex;
    background: #fff;
    border-bottom: 2px solid #e0e0e0;
    overflow-x: auto;
    gap: 0;
}

.nav-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 16px 24px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    color: #666;
    transition: all 0.3s;
    white-space: nowrap;
    flex-shrink: 0;
}

.nav-tab:hover {
    background: #f5f5f5;
    color: #4CAF50;
}

.nav-tab.active {
    color: #4CAF50;
    border-bottom-color: #4CAF50;
    background: #f0f4f0;
}

.nav-tab svg {
    flex-shrink: 0;
}

.dashboard-content {
    flex: 1;
    background: #f5f5f5;
    padding: 40px;
}

.tab-content {
    display: none;
    max-width: 1200px;
    margin: 0 auto;
}

.tab-content.active {
    display: block;
}

.dashboard-header {
    margin-bottom: 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.dashboard-title {
    font-size: 32px;
    font-weight: 800;
    color: #1a237e;
    margin: 0;
}

.dashboard-subtitle {
    font-size: 16px;
    color: #666;
    margin: 8px 0 0 0;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.dashboard-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
}

.action-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 24px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    text-decoration: none;
    color: inherit;
    transition: all 0.3s;
    border: 2px solid transparent;
}

.action-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(76, 175, 80, 0.15);
    border-color: #4CAF50;
    color: inherit;
}

.action-icon {
    flex-shrink: 0;
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4CAF50;
}

.action-content {
    flex: 1;
}

.action-content h3 {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin: 0 0 4px 0;
}

.action-content p {
    font-size: 14px;
    color: #666;
    margin: 0;
}

.action-card svg:last-child {
    flex-shrink: 0;
    color: #999;
    transition: transform 0.3s;
}

.action-card:hover svg:last-child {
    transform: translateX(4px);
    color: #4CAF50;
}

/* Lista de Trabajos */
.mis-trabajos-list {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: #666;
}

.loading-spinner svg {
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

.job-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    transition: background 0.2s;
}

.job-item:last-child {
    border-bottom: none;
}

.job-item:hover {
    background: #f8f9fa;
}

.job-item-image {
    flex-shrink: 0;
    width: 100px;
    height: 100px;
    border-radius: 12px;
    overflow: hidden;
    background: #f0f0f0;
}

.job-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.job-item-content {
    flex: 1;
    min-width: 0;
}

.job-item-title {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin: 0 0 8px 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.job-item-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 14px;
    color: #666;
    margin-bottom: 8px;
}

.job-item-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.job-item-status.published {
    background: #E8F5E9;
    color: #2E7D32;
}

.job-item-status.pending {
    background: #FFF3E0;
    color: #E65100;
}

.job-item-status.draft {
    background: #F5F5F5;
    color: #666;
}

.job-item-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.btn-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    padding: 0;
    background: transparent;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    color: #666;
}

.btn-icon:hover {
    border-color: #4CAF50;
    color: #4CAF50;
    background: #f0f4f0;
}

.btn-icon.delete:hover {
    border-color: #D32F2F;
    color: #D32F2F;
    background: #FFEBEE;
}

.btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: #fff;
    color: #666;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-secondary:hover {
    border-color: #4CAF50;
    color: #4CAF50;
    background: #f0f4f0;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state svg {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 20px;
    color: #666;
    margin: 0 0 8px 0;
}

.empty-state p {
    font-size: 14px;
    margin: 0;
}

@media (max-width: 768px) {
    .publicar-trabajo-wrapper {
        padding: 20px 10px;
    }
    
    .publicar-trabajo-form {
        padding: 24px 20px;
    }
    
    .form-header {
        padding: 30px 20px;
    }
    
    .form-title {
        font-size: 24px;
    }
    
    .login-required-section {
        padding: 40px 20px;
    }
    
    .login-required-title {
        font-size: 28px;
    }
    
    .login-required-subtitle {
        font-size: 16px;
    }
    
    .login-required-icon {
        width: 100px;
        height: 100px;
    }
    
    .login-required-info {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .post-type-selector {
        flex-direction: column;
    }
    
    .dashboard-content {
        padding: 20px;
    }
    
    .dashboard-nav {
        gap: 0;
    }
    
    .nav-tab {
        padding: 12px 16px;
        font-size: 14px;
    }
    
    .nav-tab span {
        display: none;
    }
    
    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    
    .dashboard-actions {
        grid-template-columns: 1fr;
    }
    
    .job-item {
        flex-direction: column;
        align-items: stretch;
    }
    
    .job-item-image {
        width: 100%;
        height: 200px;
    }
    
    .job-item-actions {
        justify-content: flex-end;
    }
}
</style>

<script>
(function() {
    const restUrl = '<?php echo esc_url($rest_url); ?>';
    const restNonce = '<?php echo esc_js($rest_nonce); ?>';
    
    <?php if (!$is_logged_in): ?>
    // No hay JavaScript necesario para usuarios no logueados
    // Los botones redirigen directamente a las páginas de registro/login
    <?php else: ?>
    // Manejar publicación de trabajo
    const publishForm = document.getElementById('publicar-trabajo-form');
    const imageGallery = document.getElementById('image-gallery');
    const imageUpload = document.getElementById('image-upload');
    const selectedImages = [];
    
    // Manejar carga de imágenes
    if (imageUpload) {
        imageUpload.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            files.forEach(file => {
                if (selectedImages.length >= 10) {
                    alert('Máximo 10 imágenes permitidas');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    const imageData = {
                        file: file,
                        preview: event.target.result
                    };
                    selectedImages.push(imageData);
                    renderImageGallery();
                };
                reader.readAsDataURL(file);
            });
            
            // Limpiar input para permitir seleccionar el mismo archivo de nuevo
            e.target.value = '';
        });
    }
    
    function renderImageGallery() {
        if (!imageGallery) return;
        
        imageGallery.innerHTML = '';
        selectedImages.forEach((imageData, index) => {
            const imageDiv = document.createElement('div');
            imageDiv.className = 'image-preview' + (index === 0 ? ' featured' : '');
            imageDiv.innerHTML = `
                <img src="${imageData.preview}" alt="Imagen ${index + 1}">
                <button type="button" class="remove-image" onclick="removeImage(${index})">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
                ${index === 0 ? '<div class="featured-badge">Portada</div>' : ''}
            `;
            imageGallery.appendChild(imageDiv);
        });
        
        // Mostrar/ocultar botón de agregar
        const uploadLabel = document.getElementById('image-upload-label');
        if (uploadLabel) {
            uploadLabel.style.display = selectedImages.length >= 10 ? 'none' : 'inline-flex';
        }
    }
    
    window.removeImage = function(index) {
        selectedImages.splice(index, 1);
        renderImageGallery();
    };
    
    // Manejar toggle de detalles avanzados
    const toggleDetailsBtn = document.getElementById('toggle-details-btn');
    const advancedFields = document.getElementById('advanced-fields');
    if (toggleDetailsBtn && advancedFields) {
        toggleDetailsBtn.addEventListener('click', function() {
            const isExpanded = advancedFields.style.display !== 'none';
            advancedFields.style.display = isExpanded ? 'none' : 'block';
            toggleDetailsBtn.classList.toggle('expanded', !isExpanded);
            toggleDetailsBtn.querySelector('span').textContent = isExpanded ? 'Más Detalles' : 'Ocultar Detalles';
        });
    }
    
    // Manejar cambio de tipo de publicación
    const postTypeRadios = document.querySelectorAll('input[name="post_type"]');
    const jobFields = document.querySelectorAll('.job-fields');
    const blogFields = document.querySelectorAll('.blog-fields');
    
    postTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const isJob = this.value === 'trabajo';
            jobFields.forEach(field => {
                field.style.display = isJob ? 'block' : 'none';
            });
            blogFields.forEach(field => {
                field.style.display = isJob ? 'none' : 'block';
            });
            
            // Actualizar validación de ubicación
            const ubicacionSelect = document.getElementById('job-ubicacion');
            if (ubicacionSelect) {
                ubicacionSelect.required = isJob;
            }
        });
    });
    
    // Inicializar TipTap Editor
    let editor = null;
    const tiptapEditor = document.getElementById('tiptap-editor');
    const descriptionTextarea = document.getElementById('job-description');
    
    if (tiptapEditor && descriptionTextarea) {
        // Cargar TipTap desde CDN usando jsDelivr (mejor compatibilidad con UMD)
        const loadTipTap = () => {
            return new Promise((resolve, reject) => {
                // Verificar si ya está cargado
                if (window.tiptap && window.tiptap.Editor) {
                    resolve();
                    return;
                }
                
                let coreLoaded = false;
                let starterKitLoaded = false;
                let underlineLoaded = false;
                
                const checkAllLoaded = () => {
                    if (coreLoaded && starterKitLoaded && underlineLoaded) {
                        resolve();
                    }
                };
                
                // Cargar Core
                const coreScript = document.createElement('script');
                coreScript.src = 'https://cdn.jsdelivr.net/npm/@tiptap/core@2.1.13/dist/index.umd.js';
                coreScript.onload = () => {
                    coreLoaded = true;
                    checkAllLoaded();
                };
                coreScript.onerror = () => {
                    console.error('Error loading TipTap Core');
                    reject(new Error('Error loading TipTap Core'));
                };
                document.head.appendChild(coreScript);
                
                // Cargar StarterKit
                const starterKitScript = document.createElement('script');
                starterKitScript.src = 'https://cdn.jsdelivr.net/npm/@tiptap/starter-kit@2.1.13/dist/index.umd.js';
                starterKitScript.onload = () => {
                    starterKitLoaded = true;
                    checkAllLoaded();
                };
                starterKitScript.onerror = () => {
                    console.error('Error loading TipTap StarterKit');
                    reject(new Error('Error loading TipTap StarterKit'));
                };
                document.head.appendChild(starterKitScript);
                
                // Cargar Underline
                const underlineScript = document.createElement('script');
                underlineScript.src = 'https://cdn.jsdelivr.net/npm/@tiptap/extension-underline@2.1.13/dist/index.umd.js';
                underlineScript.onload = () => {
                    underlineLoaded = true;
                    checkAllLoaded();
                };
                underlineScript.onerror = () => {
                    console.error('Error loading TipTap Underline');
                    // Underline no es crítico, continuar sin él
                    underlineLoaded = true;
                    checkAllLoaded();
                };
                document.head.appendChild(underlineScript);
            });
        };
        
        loadTipTap().then(() => {
            // Acceder a TipTap desde el objeto global (los módulos UMD exponen en window)
            // Los módulos UMD de TipTap se exponen directamente en window con su nombre
            const Editor = window.tiptap?.Editor || window.Editor;
            const StarterKit = window.tiptapStarterKit?.StarterKit || window.StarterKit;
            const Underline = window.tiptapUnderline?.Underline || window.Underline;
            
            if (!Editor || !StarterKit) {
                throw new Error('TipTap no se cargó correctamente');
            }
            
            const extensions = [
                StarterKit.configure({
                    heading: false,
                    code: false,
                    codeBlock: false,
                    blockquote: false,
                    horizontalRule: false,
                })
            ];
            
            // Agregar Underline solo si está disponible
            if (Underline) {
                extensions.push(Underline);
            }
            
            editor = new Editor({
                element: tiptapEditor,
                extensions: extensions,
                content: descriptionTextarea.value || '',
                editorProps: {
                    attributes: {
                        class: 'tiptap-content',
                        'data-placeholder': 'Una descripción detallada permite obtener más visitas. Incluye información sobre el trabajo, requisitos y beneficios.',
                    },
                },
                onUpdate: ({ editor }) => {
                    // Actualizar textarea oculto con el HTML
                    descriptionTextarea.value = editor.getHTML();
                },
            });
            
            // Configurar botones de la toolbar
            const toolbarButtons = document.querySelectorAll('.tiptap-btn');
            toolbarButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const action = btn.getAttribute('data-action');
                    
                    switch (action) {
                        case 'bold':
                            editor.chain().focus().toggleBold().run();
                            break;
                        case 'italic':
                            editor.chain().focus().toggleItalic().run();
                            break;
                        case 'underline':
                            if (editor.can().toggleUnderline()) {
                                editor.chain().focus().toggleUnderline().run();
                            }
                            break;
                        case 'bulletList':
                            editor.chain().focus().toggleBulletList().run();
                            break;
                        case 'orderedList':
                            editor.chain().focus().toggleOrderedList().run();
                            break;
                    }
                    
                    // Actualizar estado de botones
                    updateToolbarButtons();
                });
            });
            
            // Función para actualizar el estado visual de los botones
            const updateToolbarButtons = () => {
                toolbarButtons.forEach(btn => {
                    const action = btn.getAttribute('data-action');
                    let isActive = false;
                    
                    switch (action) {
                        case 'bold':
                            isActive = editor.isActive('bold');
                            break;
                        case 'italic':
                            isActive = editor.isActive('italic');
                            break;
                        case 'underline':
                            isActive = editor.isActive('underline') || false;
                            break;
                        case 'bulletList':
                            isActive = editor.isActive('bulletList');
                            break;
                        case 'orderedList':
                            isActive = editor.isActive('orderedList');
                            break;
                    }
                    
                    if (isActive) {
                        btn.classList.add('is-active');
                    } else {
                        btn.classList.remove('is-active');
                    }
                });
            };
            
            // Actualizar botones cuando cambia la selección
            editor.on('selectionUpdate', updateToolbarButtons);
            editor.on('transaction', updateToolbarButtons);
            
            // Inicializar estado de botones
            updateToolbarButtons();
        }).catch(error => {
            console.error('Error loading TipTap:', error);
            // Fallback: mostrar textarea si TipTap no se carga
            tiptapEditor.style.display = 'none';
            descriptionTextarea.style.display = 'block';
            descriptionTextarea.required = true;
        });
    }
    
    // Validación de título con SEO
    const titleInput = document.getElementById('job-title');
    const titleLength = document.getElementById('title-length');
    const titleSeoHint = document.getElementById('title-seo-hint');
    
    if (titleInput && titleLength && titleSeoHint) {
        titleInput.addEventListener('input', function() {
            const length = this.value.length;
            titleLength.textContent = length;
            
            let hint = '';
            let hintClass = '';
            if (length === 0) {
                hint = '';
            } else if (length <= 50) {
                hint = '✓ Título óptimo para SEO';
                hintClass = 'optimal';
            } else if (length <= 60) {
                hint = '✓ Título dentro del rango SEO recomendado';
                hintClass = 'optimal';
            } else if (length <= 100) {
                hint = '⚠ Título largo: puede truncarse en resultados';
                hintClass = 'warning';
            } else if (length <= 200) {
                hint = '⚠ Título muy largo: se truncará significativamente';
                hintClass = 'warning';
            } else {
                hint = '⚠ Título extremadamente largo';
                hintClass = 'error';
            }
            
            titleSeoHint.textContent = hint;
            titleSeoHint.className = 'seo-hint ' + hintClass;
        });
    }
    
    // Manejar envío del formulario
    if (publishForm) {
        publishForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('publish-submit-btn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnSpinner = submitBtn.querySelector('.btn-spinner');
            const errorMsg = document.getElementById('publish-error-message');
            const successMsg = document.getElementById('publish-success-message');
            
            // Limpiar mensajes previos
            errorMsg.style.display = 'none';
            successMsg.style.display = 'none';
            
            // Validaciones básicas
            const title = document.getElementById('job-title').value.trim();
            // Obtener contenido del editor TipTap o del textarea
            let description = '';
            if (editor) {
                description = editor.getHTML().trim();
                // Si está vacío, verificar si solo tiene párrafos vacíos
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = description;
                const textContent = tempDiv.textContent || tempDiv.innerText || '';
                if (!textContent.trim()) {
                    description = '';
                }
            } else {
                description = document.getElementById('job-description').value.trim();
            }
            const postType = document.querySelector('input[name="post_type"]:checked')?.value || 'trabajo';
            const ubicacionId = document.getElementById('job-ubicacion')?.value;
            
            if (!title) {
                errorMsg.textContent = 'El título es obligatorio';
                errorMsg.style.display = 'block';
                return;
            }
            
            if (!description) {
                errorMsg.textContent = 'La descripción es obligatoria';
                errorMsg.style.display = 'block';
                return;
            }
            
            // Actualizar textarea oculto con el contenido del editor
            if (editor) {
                descriptionTextarea.value = description;
            }
            
            if (postType === 'trabajo' && !ubicacionId) {
                errorMsg.textContent = 'La ubicación es obligatoria para trabajos';
                errorMsg.style.display = 'block';
                return;
            }
            
            // Deshabilitar botón y mostrar spinner
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-block';
            
            try {
                // Preparar datos del formulario
                const formData = new FormData(publishForm);
                const jobData = {
                    post_type: postType,
                    title: title,
                    content: description,
                    ubicacion_id: ubicacionId || null,
                    empresa_id: document.getElementById('job-empresa')?.value || null,
                    salario_min: parseInt(formData.get('salario_min')) || 0,
                    salario_max: parseInt(formData.get('salario_max')) || 0,
                    vacantes: parseInt(formData.get('vacantes')) || 1,
                    cultivo_id: document.getElementById('job-cultivo')?.value || null,
                    tipo_puesto_id: document.getElementById('job-tipo-puesto')?.value || null,
                    alojamiento: formData.get('alojamiento') === '1' ? 1 : 0,
                    transporte: formData.get('transporte') === '1' ? 1 : 0,
                    alimentacion: formData.get('alimentacion') === '1' ? 1 : 0,
                    comentarios_habilitados: formData.get('comentarios_habilitados') === '1' ? 1 : 0
                };
                
                // Agregar categoría si es blog
                if (postType === 'post') {
                    const categoriaId = document.getElementById('job-categoria')?.value;
                    if (categoriaId) {
                        jobData.categories = [parseInt(categoriaId)];
                    }
                }
                
                // Subir imágenes primero si hay
                const galleryIds = [];
                if (selectedImages.length > 0) {
                    for (let i = 0; i < selectedImages.length; i++) {
                        const imageData = selectedImages[i];
                        const imageFormData = new FormData();
                        imageFormData.append('file', imageData.file);
                        
                        const imageResponse = await fetch('<?php echo esc_url(rest_url('wp/v2/media')); ?>', {
                            method: 'POST',
                            headers: {
                                'X-WP-Nonce': restNonce
                            },
                            credentials: 'same-origin',
                            body: imageFormData
                        });
                        
                        if (imageResponse.ok) {
                            const imageResult = await imageResponse.json();
                            if (imageResult.id) {
                                galleryIds.push(imageResult.id);
                            }
                        }
                    }
                    
                    if (galleryIds.length > 0) {
                        jobData.gallery_ids = galleryIds;
                        // La primera imagen será la destacada
                        jobData.featured_image_id = galleryIds[0];
                    }
                }
                
                // Crear el trabajo
                const response = await fetch(restUrl + 'jobs', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': restNonce
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(jobData)
                });
                
                const result = await response.json();
                
                if (result.success || result.id) {
                    successMsg.textContent = '¡Trabajo publicado exitosamente! Está pendiente de revisión por un administrador.';
                    successMsg.style.display = 'block';
                    
                    // Limpiar formulario
                    publishForm.reset();
                    selectedImages.length = 0;
                    renderImageGallery();
                    
                    // Limpiar editor TipTap
                    if (editor) {
                        editor.commands.clearContent();
                    }
                    
                    // Actualizar dashboard y cambiar a Mis Trabajos
                    setTimeout(() => {
                        loadDashboardStats();
                        loadMyJobs();
                        showTab('mis-trabajos');
                    }, 1000);
                } else {
                    const errorText = result.message || 'Error al publicar el trabajo';
                    errorMsg.textContent = errorText;
                    errorMsg.style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                errorMsg.textContent = 'Error de conexión. Por favor, intenta nuevamente.';
                errorMsg.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                btnSpinner.style.display = 'none';
            }
        });
    }
    
    // Sistema de navegación por tabs
    function showTab(tabName) {
        // Ocultar todos los tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.nav-tab').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Mostrar tab seleccionado
        const targetTab = document.getElementById('tab-' + tabName);
        const targetBtn = document.querySelector(`[data-tab="${tabName}"]`);
        
        if (targetTab) {
            targetTab.classList.add('active');
        }
        if (targetBtn) {
            targetBtn.classList.add('active');
        }
        
        // Cargar datos según el tab
        if (tabName === 'dashboard') {
            loadDashboardStats();
        } else if (tabName === 'mis-trabajos') {
            loadMyJobs();
        } else if (tabName === 'mi-empresa') {
            loadCompanyProfile();
        }
    }
    
    // Event listeners para tabs
    document.querySelectorAll('.nav-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            const tabName = btn.getAttribute('data-tab');
            showTab(tabName);
        });
    });
    
    // Event listeners para action cards
    document.querySelectorAll('.action-card').forEach(card => {
        card.addEventListener('click', (e) => {
            e.preventDefault();
            const tabName = card.getAttribute('data-tab');
            if (tabName) {
                showTab(tabName);
            }
        });
    });
    
    // Cargar estadísticas del dashboard
    async function loadDashboardStats() {
        try {
            const response = await fetch(restUrl + 'me/jobs?per_page=100', {
                headers: {
                    'X-WP-Nonce': restNonce
                },
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const data = await response.json();
                const jobs = data.jobs || data.data || [];
                
                const totalJobs = jobs.length;
                const publishedJobs = jobs.filter(j => j.status === 'publish' || j.post_status === 'publish').length;
                const pendingJobs = jobs.filter(j => j.status === 'pending' || j.post_status === 'pending').length;
                
                // Calcular total de vistas
                let totalViews = 0;
                jobs.forEach(job => {
                    const views = parseInt(job.views || job.views_count || 0);
                    totalViews += views;
                });
                
                // Actualizar estadísticas
                document.getElementById('stat-total-jobs').textContent = totalJobs;
                document.getElementById('stat-published-jobs').textContent = publishedJobs;
                document.getElementById('stat-pending-jobs').textContent = pendingJobs;
                document.getElementById('stat-total-views').textContent = totalViews.toLocaleString();
            }
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
        }
    }
    
    // Cargar trabajos del usuario
    async function loadMyJobs() {
        const jobsList = document.getElementById('mis-trabajos-list');
        if (!jobsList) return;
        
        jobsList.innerHTML = `
            <div class="loading-spinner">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
                <p>Cargando trabajos...</p>
            </div>
        `;
        
        try {
            const response = await fetch(restUrl + 'me/jobs?per_page=100', {
                headers: {
                    'X-WP-Nonce': restNonce
                },
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const data = await response.json();
                const jobs = data.jobs || data.data || [];
                
                if (jobs.length === 0) {
                    jobsList.innerHTML = `
                        <div class="empty-state">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                            </svg>
                            <h3>No tienes trabajos publicados</h3>
                            <p>Crea tu primera oferta de trabajo</p>
                            <button class="btn-primary" style="margin-top: 20px; width: auto;" onclick="showTab('crear-trabajo')">
                                Crear Trabajo
                            </button>
                        </div>
                    `;
                    return;
                }
                
                let html = '';
                jobs.forEach(job => {
                    const status = job.status || job.post_status || 'draft';
                    const statusText = status === 'publish' ? 'Publicado' : status === 'pending' ? 'Pendiente' : 'Borrador';
                    const statusClass = status === 'publish' ? 'published' : status === 'pending' ? 'pending' : 'draft';
                    
                    const imageUrl = job.featured_image_url || (job.featured_media ? `<?php echo esc_url(home_url()); ?>/?attachment_id=${job.featured_media}` : '');
                    const ubicacion = job.ubicacion?.name || job.ubicacion || 'Sin ubicación';
                    const fecha = job.date ? new Date(job.date).toLocaleDateString('es-PE') : '';
                    const views = job.views || job.views_count || 0;
                    
                    html += `
                        <div class="job-item" data-job-id="${job.id}">
                            <div class="job-item-image">
                                ${imageUrl ? `<img src="${imageUrl}" alt="${job.title?.rendered || job.title || ''}">` : '<div style="width:100%;height:100%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#999;">Sin imagen</div>'}
                            </div>
                            <div class="job-item-content">
                                <h3 class="job-item-title">${job.title?.rendered || job.title || 'Sin título'}</h3>
                                <div class="job-item-meta">
                                    <span>📍 ${ubicacion}</span>
                                    <span>📅 ${fecha}</span>
                                    <span>👁️ ${views} vistas</span>
                                </div>
                                <div>
                                    <span class="job-item-status ${statusClass}">${statusText}</span>
                                </div>
                            </div>
                            <div class="job-item-actions">
                                <button class="btn-icon" onclick="editJob(${job.id})" title="Editar">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <a href="${job.link || job.permalink || '#'}" class="btn-icon" target="_blank" title="Ver">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                                <button class="btn-icon delete" onclick="deleteJob(${job.id})" title="Eliminar">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    `;
                });
                
                jobsList.innerHTML = html;
            } else {
                jobsList.innerHTML = `
                    <div class="empty-state">
                        <h3>Error al cargar trabajos</h3>
                        <p>Por favor, intenta de nuevo</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading jobs:', error);
            jobsList.innerHTML = `
                <div class="empty-state">
                    <h3>Error de conexión</h3>
                    <p>No se pudieron cargar los trabajos</p>
                </div>
            `;
        }
    }
    
    // Cargar perfil de empresa
    async function loadCompanyProfile() {
        try {
            const response = await fetch(restUrl + 'me/company-profile', {
                headers: {
                    'X-WP-Nonce': restNonce
                },
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const data = await response.json();
                const profile = data.company || data;
                
                // Llenar formulario
                if (document.getElementById('company-description')) {
                    document.getElementById('company-description').value = profile.description || '';
                }
                if (document.getElementById('company-phone')) {
                    document.getElementById('company-phone').value = profile.phone || '';
                }
                if (document.getElementById('company-address')) {
                    document.getElementById('company-address').value = profile.address || '';
                }
                if (document.getElementById('company-website')) {
                    document.getElementById('company-website').value = profile.website || '';
                }
                if (document.getElementById('company-facebook')) {
                    document.getElementById('company-facebook').value = profile.facebook || '';
                }
                if (document.getElementById('company-instagram')) {
                    document.getElementById('company-instagram').value = profile.instagram || '';
                }
                if (document.getElementById('company-linkedin')) {
                    document.getElementById('company-linkedin').value = profile.linkedin || '';
                }
                if (document.getElementById('company-twitter')) {
                    document.getElementById('company-twitter').value = profile.twitter || '';
                }
            }
        } catch (error) {
            console.error('Error loading company profile:', error);
        }
    }
    
    // Guardar perfil de empresa
    const profileForm = document.getElementById('empresa-profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('save-profile-btn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnSpinner = submitBtn.querySelector('.btn-spinner');
            const errorMsg = document.getElementById('profile-error-message');
            const successMsg = document.getElementById('profile-success-message');
            
            errorMsg.style.display = 'none';
            successMsg.style.display = 'none';
            
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-block';
            
            try {
                const formData = new FormData(profileForm);
                const profileData = {
                    email: formData.get('email'),
                    description: formData.get('description'),
                    phone: formData.get('phone'),
                    address: formData.get('address'),
                    website: formData.get('website'),
                    facebook: formData.get('facebook'),
                    instagram: formData.get('instagram'),
                    linkedin: formData.get('linkedin'),
                    twitter: formData.get('twitter')
                };
                
                const response = await fetch(restUrl + 'me/company-profile', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': restNonce
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(profileData)
                });
                
                const result = await response.json();
                
                if (result.success || response.ok) {
                    successMsg.textContent = 'Perfil actualizado exitosamente';
                    successMsg.style.display = 'block';
                } else {
                    errorMsg.textContent = result.message || 'Error al actualizar el perfil';
                    errorMsg.style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                errorMsg.textContent = 'Error de conexión. Por favor, intenta nuevamente.';
                errorMsg.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                btnSpinner.style.display = 'none';
            }
        });
    }
    
    // Editar trabajo
    window.editJob = async function(jobId) {
        try {
            // Usar el endpoint personalizado para obtener el trabajo
            const response = await fetch(restUrl + 'jobs/' + jobId, {
                headers: {
                    'X-WP-Nonce': restNonce
                },
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const job = await response.json();
                
                // Cambiar a tab de editar
                showTab('editar-trabajo');
                
                // Clonar el formulario de crear trabajo para editar
                const createForm = document.getElementById('publicar-trabajo-form');
                const editFormContent = document.getElementById('edit-job-form-content');
                
                if (createForm && editFormContent) {
                    // Clonar el formulario completo
                    const formClone = createForm.cloneNode(true);
                    formClone.id = 'edit-trabajo-form-clone';
                    
                    // Llenar con datos del trabajo
                    const titleInput = formClone.querySelector('#job-title');
                    if (titleInput) {
                        titleInput.value = job.title?.rendered || job.title || '';
                        // Disparar evento para actualizar contador
                        titleInput.dispatchEvent(new Event('input'));
                    }
                    
                    // Llenar descripción en TipTap (se inicializará después)
                    const descriptionTextarea = formClone.querySelector('#job-description');
                    if (descriptionTextarea) {
                        descriptionTextarea.value = job.content?.rendered || job.content || '';
                    }
                    
                    // Llenar selectores
                    if (job.ubicacion?.id) {
                        const ubicacionSelect = formClone.querySelector('#job-ubicacion');
                        if (ubicacionSelect) ubicacionSelect.value = job.ubicacion.id;
                    }
                    
                    if (job.empresa?.id) {
                        const empresaSelect = formClone.querySelector('#job-empresa');
                        if (empresaSelect) empresaSelect.value = job.empresa.id;
                    }
                    
                    if (job.cultivo?.id) {
                        const cultivoSelect = formClone.querySelector('#job-cultivo');
                        if (cultivoSelect) cultivoSelect.value = job.cultivo.id;
                    }
                    
                    if (job.tipo_puesto?.id) {
                        const tipoPuestoSelect = formClone.querySelector('#job-tipo-puesto');
                        if (tipoPuestoSelect) tipoPuestoSelect.value = job.tipo_puesto.id;
                    }
                    
                    // Llenar campos numéricos
                    if (job.salario_min) {
                        const salarioMinInput = formClone.querySelector('#job-salario-min');
                        if (salarioMinInput) salarioMinInput.value = job.salario_min;
                    }
                    
                    if (job.salario_max) {
                        const salarioMaxInput = formClone.querySelector('#job-salario-max');
                        if (salarioMaxInput) salarioMaxInput.value = job.salario_max;
                    }
                    
                    if (job.vacantes) {
                        const vacantesInput = formClone.querySelector('#job-vacantes');
                        if (vacantesInput) vacantesInput.value = job.vacantes;
                    }
                    
                    // Llenar checkboxes de beneficios
                    if (job.alojamiento) {
                        const alojamientoCheck = formClone.querySelector('input[name="alojamiento"]');
                        if (alojamientoCheck) alojamientoCheck.checked = true;
                    }
                    
                    if (job.transporte) {
                        const transporteCheck = formClone.querySelector('input[name="transporte"]');
                        if (transporteCheck) transporteCheck.checked = true;
                    }
                    
                    if (job.alimentacion) {
                        const alimentacionCheck = formClone.querySelector('input[name="alimentacion"]');
                        if (alimentacionCheck) alimentacionCheck.checked = true;
                    }
                    
                    // Comentarios habilitados
                    const comentariosCheck = formClone.querySelector('input[name="comentarios_habilitados"]');
                    if (comentariosCheck) {
                        comentariosCheck.checked = job.comentarios_habilitados !== false;
                    }
                    
                    // Reemplazar contenido del formulario de edición
                    editFormContent.innerHTML = '';
                    editFormContent.appendChild(formClone);
                    
                    // Inicializar TipTap para el editor clonado
                    const clonedTiptapEditor = formClone.querySelector('#tiptap-editor');
                    const clonedDescriptionTextarea = formClone.querySelector('#job-description');
                    
                    if (clonedTiptapEditor && clonedDescriptionTextarea && window.tiptap) {
                        // Inicializar TipTap con el contenido existente
                        setTimeout(() => {
                            initializeTipTapEditor(clonedTiptapEditor, clonedDescriptionTextarea, job.content?.rendered || job.content || '');
                        }, 100);
                    }
                    
                    // Configurar submit del formulario de edición
                    formClone.addEventListener('submit', async function(e) {
                        e.preventDefault();
                        
                        const submitBtn = document.getElementById('update-submit-btn');
                        const btnText = submitBtn.querySelector('.btn-text');
                        const btnSpinner = submitBtn.querySelector('.btn-spinner');
                        const errorMsg = document.getElementById('update-error-message');
                        const successMsg = document.getElementById('update-success-message');
                        
                        errorMsg.style.display = 'none';
                        successMsg.style.display = 'none';
                        
                        // Obtener datos del formulario clonado
                        const title = formClone.querySelector('#job-title').value.trim();
                        let description = '';
                        
                        // Obtener contenido de TipTap si está disponible
                        const clonedEditor = window.editJobEditor;
                        if (clonedEditor) {
                            description = clonedEditor.getHTML().trim();
                        } else {
                            description = clonedDescriptionTextarea.value.trim();
                        }
                        
                        if (!title || !description) {
                            errorMsg.textContent = 'Título y descripción son obligatorios';
                            errorMsg.style.display = 'block';
                            return;
                        }
                        
                        submitBtn.disabled = true;
                        btnText.style.display = 'none';
                        btnSpinner.style.display = 'inline-block';
                        
                        try {
                            const formData = new FormData(formClone);
                            const updateData = {
                                title: title,
                                content: description,
                                ubicacion_id: formClone.querySelector('#job-ubicacion')?.value || null,
                                empresa_id: formClone.querySelector('#job-empresa')?.value || null,
                                salario_min: parseInt(formData.get('salario_min')) || 0,
                                salario_max: parseInt(formData.get('salario_max')) || 0,
                                vacantes: parseInt(formData.get('vacantes')) || 1,
                                cultivo_id: formClone.querySelector('#job-cultivo')?.value || null,
                                tipo_puesto_id: formClone.querySelector('#job-tipo-puesto')?.value || null,
                                alojamiento: formData.get('alojamiento') === '1' ? 1 : 0,
                                transporte: formData.get('transporte') === '1' ? 1 : 0,
                                alimentacion: formData.get('alimentacion') === '1' ? 1 : 0,
                                comentarios_habilitados: formData.get('comentarios_habilitados') === '1' ? 1 : 0
                            };
                            
                            const updateResponse = await fetch(restUrl + 'jobs/' + jobId, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-WP-Nonce': restNonce
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify(updateData)
                            });
                            
                            const result = await updateResponse.json();
                            
                            if (result.success || updateResponse.ok) {
                                successMsg.textContent = 'Trabajo actualizado exitosamente';
                                successMsg.style.display = 'block';
                                
                                // Recargar lista y estadísticas
                                setTimeout(() => {
                                    loadMyJobs();
                                    loadDashboardStats();
                                    showTab('mis-trabajos');
                                }, 1500);
                            } else {
                                errorMsg.textContent = result.message || 'Error al actualizar el trabajo';
                                errorMsg.style.display = 'block';
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            errorMsg.textContent = 'Error de conexión. Por favor, intenta nuevamente.';
                            errorMsg.style.display = 'block';
                        } finally {
                            submitBtn.disabled = false;
                            btnText.style.display = 'inline';
                            btnSpinner.style.display = 'none';
                        }
                    });
                }
            } else {
                alert('Error al cargar el trabajo');
            }
        } catch (error) {
            console.error('Error loading job:', error);
            alert('Error de conexión');
        }
    };
    
    // Función auxiliar para inicializar TipTap en el editor de edición
    function initializeTipTapEditor(element, textarea, initialContent) {
        if (!window.tiptap || !window.tiptap.Editor) {
            // Si TipTap no está cargado, usar textarea normal
            textarea.style.display = 'block';
            return;
        }
        
        const Editor = window.tiptap?.Editor || window.Editor;
        const StarterKit = window.tiptapStarterKit?.StarterKit || window.StarterKit;
        const Underline = window.tiptapUnderline?.Underline || window.Underline;
        
        if (!Editor || !StarterKit) return;
        
        const extensions = [StarterKit.configure({
            heading: false,
            code: false,
            codeBlock: false,
            blockquote: false,
            horizontalRule: false,
        })];
        
        if (Underline) {
            extensions.push(Underline);
        }
        
        window.editJobEditor = new Editor({
            element: element,
            extensions: extensions,
            content: initialContent,
            editorProps: {
                attributes: {
                    class: 'tiptap-content',
                    'data-placeholder': 'Una descripción detallada permite obtener más visitas...',
                },
            },
            onUpdate: ({ editor }) => {
                textarea.value = editor.getHTML();
            },
        });
        
        // Configurar toolbar del editor clonado
        const toolbar = element.parentElement.querySelector('.tiptap-toolbar');
        if (toolbar) {
            const toolbarButtons = toolbar.querySelectorAll('.tiptap-btn');
            toolbarButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const action = btn.getAttribute('data-action');
                    switch (action) {
                        case 'bold':
                            window.editJobEditor.chain().focus().toggleBold().run();
                            break;
                        case 'italic':
                            window.editJobEditor.chain().focus().toggleItalic().run();
                            break;
                        case 'underline':
                            if (window.editJobEditor.can().toggleUnderline()) {
                                window.editJobEditor.chain().focus().toggleUnderline().run();
                            }
                            break;
                        case 'bulletList':
                            window.editJobEditor.chain().focus().toggleBulletList().run();
                            break;
                        case 'orderedList':
                            window.editJobEditor.chain().focus().toggleOrderedList().run();
                            break;
                    }
                });
            });
        }
    }
    
    // Eliminar trabajo
    window.deleteJob = async function(jobId) {
        if (!confirm('¿Estás seguro de eliminar este trabajo? Esta acción no se puede deshacer.')) {
            return;
        }
        
        try {
            const response = await fetch(restUrl + 'jobs/' + jobId, {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': restNonce
                },
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                // Recargar lista de trabajos
                loadMyJobs();
                loadDashboardStats();
            } else {
                alert('Error al eliminar el trabajo');
            }
        } catch (error) {
            console.error('Error deleting job:', error);
            alert('Error de conexión');
        }
    };
    
    // Cargar datos iniciales si estamos en dashboard
    <?php if ($is_logged_in): ?>
    if (document.querySelector('.tab-content.active')?.id === 'tab-dashboard') {
        loadDashboardStats();
    }
    <?php endif; ?>
    
    <?php endif; ?>
})();
</script>

<?php
get_footer();

