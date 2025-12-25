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
$is_employer = $is_logged_in && current_user_can('publish_trabajos');

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
            <!-- Formulario de Registro de Empresa -->
            <div class="empresa-register-section">
                <div class="register-header">
                    <h1 class="register-title">Regístrate como Empresa</h1>
                    <p class="register-subtitle">Crea tu cuenta y comienza a publicar ofertas de trabajo</p>
                </div>
                
                <form id="empresa-register-form" class="empresa-register-form">
                    <div class="form-group">
                        <label for="register-username">Nombre de usuario *</label>
                        <input type="text" id="register-username" name="username" required minlength="3">
                        <span class="field-error" id="username-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-email">Correo electrónico *</label>
                        <input type="email" id="register-email" name="email" required>
                        <span class="field-error" id="email-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-password">Contraseña *</label>
                        <input type="password" id="register-password" name="password" required minlength="6">
                        <span class="field-error" id="password-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-ruc">RUC *</label>
                        <input type="text" id="register-ruc" name="ruc" required pattern="[0-9]{8,11}">
                        <span class="field-error" id="ruc-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-razon-social">Razón Social *</label>
                        <input type="text" id="register-razon-social" name="razon_social" required>
                        <span class="field-error" id="razon-social-error"></span>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary" id="register-submit-btn">
                            <span class="btn-text">Registrarse</span>
                            <span class="btn-spinner" style="display: none;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                                </svg>
                            </span>
                        </button>
                        <p class="form-footer">
                            ¿Ya tienes cuenta? <a href="<?php echo esc_url(wp_login_url(home_url('/publicar-trabajo'))); ?>">Inicia sesión</a>
                        </p>
                    </div>
                    
                    <div id="register-error-message" class="error-message" style="display: none;"></div>
                    <div id="register-success-message" class="success-message" style="display: none;"></div>
                </form>
            </div>
        <?php else: ?>
            <!-- Formulario de Publicación de Trabajo -->
            <div class="publicar-trabajo-section">
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
                        <div class="form-group">
                            <label class="checkbox-option">
                                <input type="checkbox" name="publish_to_facebook" value="1">
                                <span>📱 Publicar también en Facebook</span>
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

.form-header,
.register-header {
    background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
    padding: 40px;
    text-align: center;
    color: #fff;
}

.form-title,
.register-title {
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 8px 0;
}

.form-subtitle,
.register-subtitle {
    font-size: 16px;
    opacity: 0.9;
    margin: 0;
}

.publicar-trabajo-form,
.empresa-register-form {
    padding: 40px;
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

@media (max-width: 768px) {
    .publicar-trabajo-wrapper {
        padding: 20px 10px;
    }
    
    .publicar-trabajo-form,
    .empresa-register-form {
        padding: 24px 20px;
    }
    
    .form-header,
    .register-header {
        padding: 30px 20px;
    }
    
    .form-title,
    .register-title {
        font-size: 24px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .post-type-selector {
        flex-direction: column;
    }
}
</style>

<script>
(function() {
    const restUrl = '<?php echo esc_url($rest_url); ?>';
    const restNonce = '<?php echo esc_js($rest_nonce); ?>';
    
    <?php if (!$is_logged_in): ?>
    // Manejar registro de empresa
    const registerForm = document.getElementById('empresa-register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('register-submit-btn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnSpinner = submitBtn.querySelector('.btn-spinner');
            const errorMsg = document.getElementById('register-error-message');
            const successMsg = document.getElementById('register-success-message');
            
            // Limpiar errores previos
            document.querySelectorAll('.field-error').forEach(el => el.textContent = '');
            errorMsg.style.display = 'none';
            successMsg.style.display = 'none';
            
            // Obtener datos del formulario
            const formData = {
                username: document.getElementById('register-username').value.trim(),
                email: document.getElementById('register-email').value.trim(),
                password: document.getElementById('register-password').value,
                ruc: document.getElementById('register-ruc').value.trim(),
                razon_social: document.getElementById('register-razon-social').value.trim()
            };
            
            // Validaciones básicas
            let hasErrors = false;
            if (formData.username.length < 3) {
                document.getElementById('username-error').textContent = 'El nombre de usuario debe tener al menos 3 caracteres';
                hasErrors = true;
            }
            if (formData.password.length < 6) {
                document.getElementById('password-error').textContent = 'La contraseña debe tener al menos 6 caracteres';
                hasErrors = true;
            }
            if (!/^\d{8,11}$/.test(formData.ruc)) {
                document.getElementById('ruc-error').textContent = 'El RUC debe tener entre 8 y 11 dígitos';
                hasErrors = true;
            }
            
            if (hasErrors) return;
            
            // Deshabilitar botón y mostrar spinner
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-block';
            
            try {
                const response = await fetch(restUrl + 'register-company', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': restNonce
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.token) {
                    // Registro exitoso - hacer login automático
                    successMsg.textContent = '¡Registro exitoso! Redirigiendo...';
                    successMsg.style.display = 'block';
                    
                    // Guardar token y hacer login
                    localStorage.setItem('wp_token', data.token);
                    
                    // Recargar la página para mostrar el formulario de publicación
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Mostrar errores
                    const errorText = data.message || 'Error al registrar la empresa';
                    errorMsg.textContent = errorText;
                    errorMsg.style.display = 'block';
                    
                    // Mostrar errores específicos de campos
                    if (data.code === 'rest_user_exists') {
                        document.getElementById('username-error').textContent = 'Este nombre de usuario ya está en uso';
                    }
                    if (data.code === 'rest_email_exists') {
                        document.getElementById('email-error').textContent = 'Este correo ya está registrado';
                    }
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
                    comentarios_habilitados: formData.get('comentarios_habilitados') === '1' ? 1 : 0,
                    publish_to_facebook: formData.get('publish_to_facebook') === '1' ? 1 : 0
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
                    
                    // Redirigir después de 3 segundos
                    setTimeout(() => {
                        window.location.href = '<?php echo esc_url(get_post_type_archive_link('trabajo')); ?>';
                    }, 3000);
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
    <?php endif; ?>
})();
</script>

<?php
get_footer();

