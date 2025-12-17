<?php
/**
 * Template Name: Perfil Personalizado AgroChamba
 * 
 * Panel de usuario personalizado con diseño similar a la app móvil
 * Reemplaza completamente el panel nativo de WordPress
 */

if (!defined('ABSPATH')) {
    exit;
}

// Si el usuario no está logueado, redirigir al login
if (!is_user_logged_in()) {
    $login_page = get_page_by_path('login');
    if ($login_page) {
        $login_url = get_permalink($login_page->ID);
        $redirect_url = add_query_arg('redirect_to', urlencode(get_permalink()), $login_url);
        wp_redirect($redirect_url);
        exit;
    } else {
        wp_redirect(wp_login_url(get_permalink()));
        exit;
    }
}

$current_user = wp_get_current_user();
$is_enterprise = in_array('employer', $current_user->roles) || in_array('administrator', $current_user->roles);

get_header();
?>

<style>
/* Ocultar completamente header, footer y contenido del tema y Bricks Builder */
header,
footer,
nav,
.site-header,
.site-footer,
.main-navigation,
#header,
#footer,
.page-header,
.entry-header,
.page .entry-content,
.page .post-content,
.page .content-area .entry-content,
.page .site-content .entry-content,
.content-area > *:not(.agrochamba-profile-wrapper),
.site-content > *:not(.agrochamba-profile-wrapper),
body > *:not(.agrochamba-profile-wrapper),
main,
article,
section:not(.agrochamba-profile-wrapper),
.brxe-container,
.brxe-wrapper,
.brxe-section,
.brxe-div,
.brxe-text,
.brxe-heading,
.brxe-image,
.brxe-button,
.brxe-icon,
.bricks-layout-wrapper,
.bricks-layout-content,
.bricks-layout-header,
.bricks-layout-footer,
.bricks-layout-main,
.bricks-layout-sidebar,
[class*="brxe-"],
[data-bricks-id],
[data-bricks-type],
.bricks-is-frontend > *:not(.agrochamba-profile-wrapper),
.bricks-body > *:not(.agrochamba-profile-wrapper) {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    height: 0 !important;
    overflow: hidden !important;
    position: absolute !important;
    left: -9999px !important;
}

/* Resetear body y html - Sobrescribir Bricks Builder */
body,
body.bricks-is-frontend,
body.bricks-body {
    margin: 0 !important;
    padding: 0 !important;
    background: #f5f5f5 !important;
    overflow-x: hidden !important;
    position: relative !important;
}

html,
html.bricks-html {
    background: #f5f5f5 !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Asegurar que nuestro contenido se muestre y ocupe toda la pantalla */
.agrochamba-profile-wrapper {
    display: flex !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    z-index: 99999 !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    -webkit-overflow-scrolling: touch !important;
}

/* ==========================================
   ESTILOS DE PERFIL - DISEÑO APP MÓVIL
   ========================================== */
.agrochamba-profile-wrapper {
    min-height: 100vh;
    display: flex !important;
    flex-direction: column;
    background: #f5f5f5 !important;
    padding: 0;
    box-sizing: border-box;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 999999 !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
}

.profile-header {
    background: #fff;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 0;
    z-index: 100;
}

.profile-header-content {
    max-width: 600px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.profile-header-title {
    font-size: 20px;
    font-weight: 700;
    color: #000;
    margin: 0;
}

.profile-header-actions {
    display: flex;
    gap: 12px;
}

.btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    border-radius: 8px;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: #f0f0f0;
    color: #2d5016;
}

.btn-icon svg {
    width: 24px;
    height: 24px;
}

.profile-container {
    flex: 1;
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
    box-sizing: border-box;
}

.profile-section {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.profile-photo-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 32px 24px;
}

.profile-photo-wrapper {
    position: relative;
    margin-bottom: 20px;
}

.profile-photo {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    background: #e0e0e0;
}

.profile-photo-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.profile-photo-placeholder svg {
    width: 60px;
    height: 60px;
    color: #999;
}

.photo-upload-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #2d5016;
    border: 3px solid #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    transition: all 0.2s;
}

.photo-upload-btn:hover {
    background: #1f350d;
    transform: scale(1.05);
}

.photo-upload-btn svg {
    width: 20px;
    height: 20px;
    color: #fff;
}

.profile-name {
    font-size: 24px;
    font-weight: 700;
    color: #000;
    margin: 0 0 8px 0;
    text-align: center;
}

.profile-username {
    font-size: 16px;
    color: #666;
    margin: 0 0 20px 0;
    text-align: center;
}

.profile-actions {
    display: flex;
    gap: 12px;
    width: 100%;
}

.btn-primary,
.btn-secondary {
    flex: 1;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary {
    background: #2d5016;
    color: #fff;
}

.btn-primary:hover {
    background: #1f350d;
}

.btn-secondary {
    background: #f0f0f0;
    color: #333;
}

.btn-secondary:hover {
    background: #e0e0e0;
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #000;
    margin: 0 0 20px 0;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.form-input,
.form-textarea {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    font-size: 16px;
    background: #fff;
    transition: all 0.2s;
    box-sizing: border-box;
    color: #000;
    font-family: inherit;
}

.form-textarea {
    min-height: 100px;
    resize: vertical;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: #2d5016;
    box-shadow: 0 0 0 2px rgba(45, 80, 22, 0.1);
}

.form-input:disabled {
    background: #f5f5f5;
    color: #999;
    cursor: not-allowed;
}

.message {
    padding: 14px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.message-success {
    background: #e8f5e9;
    border: 1px solid #c8e6c9;
    color: #2e7d32;
}

.message-error {
    background: #ffebee;
    border: 1px solid #ffcdd2;
    color: #c62828;
}

.message svg {
    flex-shrink: 0;
}

.loading {
    opacity: 0.6;
    pointer-events: none;
}

.hidden {
    display: none !important;
}

/* Responsive para móvil */
@media (max-width: 768px) {
    .profile-container {
        padding: 16px;
    }
    
    .profile-section {
        padding: 20px;
    }
    
    .profile-header {
        padding: 16px;
    }
    
    .profile-header-title {
        font-size: 18px;
    }
}

/* Ocultar elementos de Bricks Builder dinámicamente */
</style>

<div class="agrochamba-profile-wrapper">
    <div class="profile-header">
        <div class="profile-header-content">
            <h1 class="profile-header-title">Mi Perfil</h1>
            <div class="profile-header-actions">
                <button class="btn-icon" onclick="window.location.href='<?php echo esc_url(home_url()); ?>'" title="Ir al inicio">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </button>
                <button class="btn-icon" onclick="logout()" title="Cerrar sesión">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="profile-container">
        <div id="messages"></div>
        
        <?php if (isset($_GET['welcome']) && $_GET['welcome'] === '1'): ?>
        <div class="message message-success" style="margin-bottom: 20px;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>¡Bienvenido a AgroChamba! Tu cuenta ha sido creada exitosamente. Completa tu perfil para comenzar.</span>
        </div>
        <?php endif; ?>

        <!-- Sección de Foto de Perfil -->
        <div class="profile-section profile-photo-section">
            <div class="profile-photo-wrapper">
                <div id="profile-photo-container">
                    <img id="profile-photo" class="profile-photo" src="" alt="Foto de perfil" style="display: none;">
                    <div id="profile-photo-placeholder" class="profile-photo-placeholder">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>
                <label for="photo-upload" class="photo-upload-btn" title="Cambiar foto">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <input type="file" id="photo-upload" accept="image/*" style="display: none;">
                </label>
            </div>
            <h2 class="profile-name" id="profile-display-name"><?php echo esc_html($current_user->display_name); ?></h2>
            <p class="profile-username" id="profile-username">@<?php echo esc_html($current_user->user_login); ?></p>
            <div class="profile-actions">
                <button type="button" class="btn-secondary" onclick="toggleEditMode()" id="edit-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Editar Perfil
                </button>
            </div>
            
            <!-- Botones de foto en modo edición -->
            <div id="photo-actions-edit" style="display: none; margin-top: 16px; display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn-secondary" onclick="document.getElementById('photo-upload').click()" id="change-photo-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Cambiar Foto
                </button>
                <button type="button" class="btn-secondary" onclick="deletePhoto()" id="delete-photo-btn-edit" style="display: none; color: #c62828;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Eliminar
                </button>
            </div>
        </div>

        <!-- Formulario de Edición -->
        <form id="profile-form" class="profile-section" style="display: none;">
            <div class="form-group">
                <label class="form-label" for="display_name">Nombre a mostrar</label>
                <input type="text" id="display_name" name="display_name" class="form-input" required>
            </div>

            <div class="form-group" style="display: flex; gap: 12px;">
                <div style="flex: 1;">
                    <label class="form-label" for="first_name">Nombre</label>
                    <input type="text" id="first_name" name="first_name" class="form-input">
                </div>
                <div style="flex: 1;">
                    <label class="form-label" for="last_name">Apellido</label>
                    <input type="text" id="last_name" name="last_name" class="form-input">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Teléfono</label>
                <input type="tel" id="phone" name="phone" class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label" for="bio">Biografía</label>
                <textarea id="bio" name="bio" class="form-textarea" rows="5" style="min-height: 120px;"></textarea>
            </div>

            <?php if ($is_enterprise): ?>
            <div style="margin-top: 32px; margin-bottom: 20px;">
                <h3 class="section-title" style="margin: 0 0 8px 0; font-size: 16px;">Descripción de la Empresa</h3>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="company_description">Acerca de tu empresa</label>
                <textarea id="company_description" name="company_description" class="form-textarea" rows="8" style="min-height: 150px;"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="company_address">Dirección</label>
                <input type="text" id="company_address" name="company_address" class="form-input" placeholder="Ej: Av. Principal 123, Lima, Perú">
            </div>

            <div class="form-group">
                <label class="form-label" for="company_phone">Teléfono de la empresa</label>
                <input type="tel" id="company_phone" name="company_phone" class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label" for="company_website">Sitio web</label>
                <input type="url" id="company_website" name="company_website" class="form-input" placeholder="https://www.ejemplo.com">
            </div>

            <div style="margin-top: 24px; margin-bottom: 12px;">
                <h3 class="section-title" style="margin: 0; font-size: 14px; font-weight: 700;">Redes Sociales</h3>
            </div>

            <div class="form-group">
                <label class="form-label" for="company_facebook">Facebook</label>
                <input type="url" id="company_facebook" name="company_facebook" class="form-input" placeholder="https://facebook.com/empresa">
            </div>

            <div class="form-group">
                <label class="form-label" for="company_instagram">Instagram</label>
                <input type="url" id="company_instagram" name="company_instagram" class="form-input" placeholder="https://instagram.com/empresa">
            </div>

            <div class="form-group">
                <label class="form-label" for="company_linkedin">LinkedIn</label>
                <input type="url" id="company_linkedin" name="company_linkedin" class="form-input" placeholder="https://linkedin.com/company/empresa">
            </div>

            <div class="form-group">
                <label class="form-label" for="company_twitter">Twitter</label>
                <input type="url" id="company_twitter" name="company_twitter" class="form-input" placeholder="https://twitter.com/empresa">
            </div>
            <?php endif; ?>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn-primary" style="flex: 1;">
                    Guardar Cambios
                </button>
                <button type="button" class="btn-secondary" onclick="cancelEdit()" style="flex: 1;">
                    Cancelar
                </button>
            </div>
        </form>

        <!-- Vista de Solo Lectura -->
        <div id="profile-view" class="profile-section">
            <div class="form-group">
                <label class="form-label">Nombre a mostrar</label>
                <div id="view-display_name" class="form-input" style="background: #f5f5f5;">-</div>
            </div>

            <div class="form-group">
                <label class="form-label">Correo electrónico</label>
                <div id="view-email" class="form-input" style="background: #f5f5f5;">-</div>
            </div>

            <div class="form-group">
                <label class="form-label">Teléfono</label>
                <div id="view-phone" class="form-input" style="background: #f5f5f5;">-</div>
            </div>

            <div class="form-group">
                <label class="form-label">Biografía</label>
                <div id="view-bio" class="form-input" style="background: #f5f5f5; min-height: 60px; white-space: pre-wrap;">-</div>
            </div>

            <?php if ($is_enterprise): ?>
            <div style="margin-top: 32px; margin-bottom: 20px;">
                <h3 class="section-title" style="margin: 0 0 8px 0; font-size: 16px;">Descripción de la Empresa</h3>
            </div>
            
            <div class="form-group">
                <label class="form-label">Acerca de tu empresa</label>
                <div id="view-company_description" class="form-input" style="background: #f5f5f5; min-height: 60px; white-space: pre-wrap;">-</div>
            </div>

            <div class="form-group">
                <label class="form-label">Dirección</label>
                <div id="view-company_address" class="form-input" style="background: #f5f5f5;">-</div>
            </div>

            <div class="form-group">
                <label class="form-label">Teléfono de la empresa</label>
                <div id="view-company_phone" class="form-input" style="background: #f5f5f5;">-</div>
            </div>

            <div class="form-group">
                <label class="form-label">Sitio web</label>
                <div id="view-company_website" class="form-input" style="background: #f5f5f5;">-</div>
            </div>

            <div style="margin-top: 24px; margin-bottom: 12px;">
                <h3 class="section-title" style="margin: 0; font-size: 14px; font-weight: 700;">Redes Sociales</h3>
            </div>

            <div class="form-group">
                <label class="form-label">Facebook</label>
                <div id="view-company_facebook" class="form-input" style="background: #f5f5f5;">-</div>
            </div>

            <div class="form-group">
                <label class="form-label">Instagram</label>
                <div id="view-company_instagram" class="form-input" style="background: #f5f5f5;">-</div>
            </div>

            <div class="form-group">
                <label class="form-label">LinkedIn</label>
                <div id="view-company_linkedin" class="form-input" style="background: #f5f5f5;">-</div>
            </div>

            <div class="form-group">
                <label class="form-label">Twitter</label>
                <div id="view-company_twitter" class="form-input" style="background: #f5f5f5;">-</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Variables globales
let profileData = null;
let isEditMode = false;
const apiBase = '<?php echo esc_url(rest_url('agrochamba/v1')); ?>';
const nonce = '<?php echo wp_create_nonce('wp_rest'); ?>';

// Cargar perfil al iniciar
document.addEventListener('DOMContentLoaded', function() {
    loadProfile();
    
    // Manejar subida de foto
    document.getElementById('photo-upload').addEventListener('change', handlePhotoUpload);
});

// Cargar datos del perfil
async function loadProfile() {
    try {
        const response = await fetch(`${apiBase}/me/profile`, {
            headers: {
                'X-WP-Nonce': nonce
            }
        });
        
        if (!response.ok) {
            throw new Error('Error al cargar el perfil');
        }
        
        profileData = await response.json();
        displayProfile(profileData);
    } catch (error) {
        showMessage('Error al cargar el perfil: ' + error.message, 'error');
    }
}

// Mostrar datos del perfil
function displayProfile(data) {
    // Foto de perfil
    if (data.profile_photo_url) {
        document.getElementById('profile-photo').src = data.profile_photo_url;
        document.getElementById('profile-photo').style.display = 'block';
        document.getElementById('profile-photo-placeholder').style.display = 'none';
        document.getElementById('delete-photo-btn-edit').style.display = 'flex';
    } else {
        document.getElementById('profile-photo').style.display = 'none';
        document.getElementById('profile-photo-placeholder').style.display = 'flex';
        document.getElementById('delete-photo-btn-edit').style.display = 'none';
    }
    
    // Nombre y usuario
    document.getElementById('profile-display-name').textContent = data.display_name || '-';
    document.getElementById('profile-username').textContent = '@' + (data.username || '');
    
    // Llenar formulario
    document.getElementById('display_name').value = data.display_name || '';
    document.getElementById('first_name').value = data.first_name || '';
    document.getElementById('last_name').value = data.last_name || '';
    document.getElementById('email').value = data.email || '';
    document.getElementById('phone').value = data.phone || '';
    document.getElementById('bio').value = data.bio || '';
    
    <?php if ($is_enterprise): ?>
    document.getElementById('company_description').value = data.company_description || '';
    document.getElementById('company_address').value = data.company_address || '';
    document.getElementById('company_phone').value = data.company_phone || '';
    document.getElementById('company_website').value = data.company_website || '';
    document.getElementById('company_facebook').value = data.company_facebook || '';
    document.getElementById('company_instagram').value = data.company_instagram || '';
    document.getElementById('company_linkedin').value = data.company_linkedin || '';
    document.getElementById('company_twitter').value = data.company_twitter || '';
    <?php endif; ?>
    
    // Llenar vista de solo lectura
    document.getElementById('view-display_name').textContent = data.display_name || '-';
    document.getElementById('view-email').textContent = data.email || '-';
    document.getElementById('view-phone').textContent = data.phone || '-';
    document.getElementById('view-bio').textContent = data.bio || '-';
    
    <?php if ($is_enterprise): ?>
    document.getElementById('view-company_description').textContent = data.company_description || '-';
    document.getElementById('view-company_address').textContent = data.company_address || '-';
    document.getElementById('view-company_phone').textContent = data.company_phone || '-';
    document.getElementById('view-company_website').textContent = data.company_website || '-';
    document.getElementById('view-company_facebook').textContent = data.company_facebook || '-';
    document.getElementById('view-company_instagram').textContent = data.company_instagram || '-';
    document.getElementById('view-company_linkedin').textContent = data.company_linkedin || '-';
    document.getElementById('view-company_twitter').textContent = data.company_twitter || '-';
    <?php endif; ?>
}

// Alternar modo de edición
function toggleEditMode() {
    isEditMode = !isEditMode;
    document.getElementById('profile-form').style.display = isEditMode ? 'block' : 'none';
    document.getElementById('profile-view').style.display = isEditMode ? 'none' : 'block';
    document.getElementById('photo-actions-edit').style.display = isEditMode ? 'flex' : 'none';
    
    const editBtn = document.getElementById('edit-btn');
    editBtn.innerHTML = isEditMode 
        ? '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>Cancelar'
        : '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>Editar Perfil';
    
    // Mostrar/ocultar botón de eliminar foto según si hay foto
    if (isEditMode) {
        const hasPhoto = profileData && profileData.profile_photo_url;
        document.getElementById('delete-photo-btn-edit').style.display = hasPhoto ? 'flex' : 'none';
    }
}

function cancelEdit() {
    toggleEditMode();
    if (profileData) {
        displayProfile(profileData);
    }
}

// Manejar envío del formulario
document.getElementById('profile-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    try {
        const response = await fetch(`${apiBase}/me/profile`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error al actualizar el perfil');
        }
        
        const result = await response.json();
        showMessage('Perfil actualizado correctamente', 'success');
        
        // Recargar perfil
        await loadProfile();
        toggleEditMode();
    } catch (error) {
        showMessage('Error al actualizar el perfil: ' + error.message, 'error');
    }
});

// Manejar subida de foto
async function handlePhotoUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    if (!file.type.startsWith('image/')) {
        showMessage('Por favor, selecciona una imagen válida', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', file);
    
    try {
        const response = await fetch(`${apiBase}/me/profile/photo`, {
            method: 'POST',
            headers: {
                'X-WP-Nonce': nonce
            },
            body: formData
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error al subir la foto');
        }
        
        const result = await response.json();
        showMessage('Foto de perfil actualizada correctamente', 'success');
        
        // Recargar perfil
        await loadProfile();
    } catch (error) {
        showMessage('Error al subir la foto: ' + error.message, 'error');
    }
    
    // Limpiar input
    e.target.value = '';
}

// Eliminar foto
async function deletePhoto() {
    if (!confirm('¿Estás seguro de que quieres eliminar tu foto de perfil?')) {
        return;
    }
    
    try {
        const response = await fetch(`${apiBase}/me/profile/photo`, {
            method: 'DELETE',
            headers: {
                'X-WP-Nonce': nonce
            }
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error al eliminar la foto');
        }
        
        showMessage('Foto de perfil eliminada correctamente', 'success');
        
        // Recargar perfil
        await loadProfile();
        
        // Actualizar visibilidad del botón de eliminar
        if (isEditMode) {
            document.getElementById('delete-photo-btn-edit').style.display = 'none';
        }
    } catch (error) {
        showMessage('Error al eliminar la foto: ' + error.message, 'error');
    }
}

// Cerrar sesión
function logout() {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
        window.location.href = '<?php echo esc_url(wp_logout_url(home_url())); ?>';
    }
}

// Mostrar mensajes
function showMessage(message, type) {
    const messagesContainer = document.getElementById('messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type}`;
    
    const icon = type === 'success' 
        ? '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
        : '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
    
    messageDiv.innerHTML = icon + ' ' + message;
    messagesContainer.appendChild(messageDiv);
    
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

// Ocultar elementos de Bricks Builder dinámicamente
function hideBricksElements() {
    // Seleccionar todos los elementos posibles de Bricks
    const selectors = [
        '[class*="brxe-"]',
        '[data-bricks-id]',
        '[data-bricks-type]',
        '.bricks-layout-wrapper',
        '.bricks-layout-content',
        '.bricks-layout-header',
        '.bricks-layout-footer',
        '.bricks-layout-main',
        '.bricks-layout-sidebar',
        'header:not(.profile-header)',
        'footer',
        'nav',
        '.site-header',
        '.site-footer',
        '.main-navigation',
        '#header',
        '#footer',
        '.page-header',
        '.entry-header',
        'main:not(.agrochamba-profile-wrapper)',
        'article:not(.agrochamba-profile-wrapper)',
        'section:not(.agrochamba-profile-wrapper)'
    ];
    
    selectors.forEach(selector => {
        try {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                // No ocultar nuestro wrapper
                if (!el.closest('.agrochamba-profile-wrapper')) {
                    el.style.display = 'none';
                    el.style.visibility = 'hidden';
                    el.style.opacity = '0';
                    el.style.height = '0';
                    el.style.overflow = 'hidden';
                    el.style.position = 'absolute';
                    el.style.left = '-9999px';
                }
            });
        } catch (e) {
            console.warn('Error hiding Bricks elements:', e);
        }
    });
}

// Ejecutar inmediatamente y después de que cargue el DOM
hideBricksElements();
document.addEventListener('DOMContentLoaded', hideBricksElements);
document.addEventListener('load', hideBricksElements);

// Observar cambios en el DOM para ocultar elementos nuevos que Bricks pueda agregar
if (typeof MutationObserver !== 'undefined') {
    const observer = new MutationObserver(function(mutations) {
        hideBricksElements();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}
</script>

<?php
get_footer();
?>

