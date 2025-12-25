<?php
/**
 * Template Name: Registro Personalizado AgroChamba
 * 
 * Página de registro personalizada con diseño similar a la app móvil
 */

if (!defined('ABSPATH')) {
    exit;
}

// Si el usuario ya está logueado, redirigir
if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

// Manejar errores de registro
$register_error = '';
$register_success = false;

if (isset($_GET['registration']) && $_GET['registration'] === 'disabled') {
    $register_error = 'El registro está deshabilitado.';
} elseif (isset($_GET['registration']) && $_GET['registration'] === 'failed') {
    $register_error = 'Error al crear la cuenta. Por favor, intenta nuevamente.';
}

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
.content-area > *:not(.agrochamba-auth-wrapper),
.site-content > *:not(.agrochamba-auth-wrapper),
body > *:not(.agrochamba-auth-wrapper),
.brxe-container,
.brxe-wrapper,
.brxe-section,
.brxe-div,
.bricks-layout-wrapper,
.bricks-layout-content,
.bricks-layout-header,
.bricks-layout-footer,
[class*="brxe-"],
[data-bricks-id] {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    height: 0 !important;
    overflow: hidden !important;
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
.agrochamba-auth-wrapper {
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
   ESTILOS DE AUTENTICACIÓN - DISEÑO APP MÓVIL
   ========================================== */
.agrochamba-auth-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5 !important;
    padding: 20px;
    box-sizing: border-box;
}

.auth-container {
    width: 100%;
    max-width: 420px;
    background: transparent;
    padding: 0;
    box-sizing: border-box;
    margin: auto;
}

.auth-logo-container {
    display: flex;
    justify-content: center;
    margin-bottom: 32px;
    padding-top: 20px;
}

.auth-logo {
    width: 90px;
    height: 90px;
    background: #fff;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
}

.auth-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.auth-title {
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 12px 0;
    color: #000;
    text-align: center;
    line-height: 1.2;
    padding: 0 10px;
    box-sizing: border-box;
}

.auth-subtitle {
    font-size: 16px;
    margin: 0 0 32px 0;
    color: #666;
    text-align: center;
    line-height: 1.5;
    padding: 0 10px;
    box-sizing: border-box;
}

.auth-form-wrapper {
    padding: 0;
    width: 100%;
    box-sizing: border-box;
}

.auth-error-message {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: #fee;
    border: 1px solid #fcc;
    border-radius: 12px;
    color: #c33;
    margin-bottom: 24px;
    font-size: 14px;
}

.auth-error-message svg {
    flex-shrink: 0;
}

.form-group {
    margin-bottom: 20px;
    position: relative;
    width: 100%;
    box-sizing: border-box;
}

.form-input {
    width: 100%;
    padding: 16px 16px 16px 50px;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    font-size: 16px;
    background: #fff;
    transition: all 0.2s;
    box-sizing: border-box;
    color: #000;
    -webkit-appearance: none;
    appearance: none;
    max-width: 100%;
}

.form-input::placeholder {
    color: #999;
}

.form-input:focus {
    outline: none;
    border-color: #2d5016;
    box-shadow: 0 0 0 2px rgba(45, 80, 22, 0.1);
}

.form-input-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    color: #666;
    pointer-events: none;
}

.password-input-wrapper {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #666;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.password-toggle:hover {
    color: #2d5016;
}

.password-toggle svg {
    width: 20px;
    height: 20px;
}

.form-help {
    display: block;
    margin-top: 6px;
    font-size: 12px;
    color: #999;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    color: #000;
    font-size: 14px;
    line-height: 1.5;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #2d5016;
    margin-top: 2px;
    flex-shrink: 0;
}

.checkbox-label a {
    color: #2d5016;
    text-decoration: none;
    font-weight: 500;
}

.checkbox-label a:hover {
    text-decoration: underline;
}

.auth-submit-btn {
    width: 100%;
    padding: 16px;
    background: #2d5016;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.auth-submit-btn:hover {
    background: #1a5d1a;
}

.auth-submit-btn:active {
    transform: scale(0.98);
}

.auth-submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.auth-footer {
    text-align: center;
    padding-top: 0;
}

.auth-footer p {
    margin: 0;
    color: #000;
    font-size: 16px;
}

.auth-link {
    color: #2d5016;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}

.auth-link:hover {
    color: #1a5d1a;
    text-decoration: underline;
}

/* Optimización para móviles */
@media (max-width: 768px) {
    .agrochamba-auth-wrapper {
        padding: 24px 20px !important;
        align-items: flex-start !important;
        padding-top: 40px !important;
        padding-bottom: 40px !important;
    }
    
    .auth-container {
        max-width: 100%;
        width: 100%;
        padding: 0;
        margin: 0 auto;
    }
    
    .auth-logo-container {
        margin-bottom: 28px;
        padding-top: 10px;
    }
    
    .auth-logo {
        width: 80px;
        height: 80px;
    }
    
    .auth-logo svg {
        width: 50px;
        height: 50px;
    }
    
    .auth-title {
        font-size: 26px;
        margin-bottom: 10px;
        padding: 0 5px;
    }
    
    .auth-subtitle {
        font-size: 15px;
        margin-bottom: 28px;
        padding: 0 5px;
    }
    
    .auth-form-wrapper {
        padding: 0;
    }
    
    .form-group {
        margin-bottom: 18px;
    }
    
    .form-input {
        padding: 14px 14px 14px 48px;
        font-size: 16px; /* Evitar zoom en iOS */
    }
    
    .form-input-icon {
        left: 14px;
        width: 18px;
        height: 18px;
    }
    
    .password-toggle {
        right: 14px;
        padding: 6px;
    }
    
    .auth-submit-btn {
        padding: 15px;
        font-size: 16px;
        margin-bottom: 20px;
    }
    
    .auth-footer {
        padding-top: 0;
    }
    
    .auth-footer p {
        font-size: 15px;
        padding: 0 5px;
    }
    
    .checkbox-label {
        font-size: 13px;
    }
}

/* Optimización para móviles pequeños */
@media (max-width: 480px) {
    .agrochamba-auth-wrapper {
        padding: 20px 16px !important;
        padding-top: 30px !important;
        padding-bottom: 30px !important;
    }
    
    .auth-logo-container {
        margin-bottom: 24px;
        padding-top: 5px;
    }
    
    .auth-logo {
        width: 75px;
        height: 75px;
    }
    
    .auth-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    
    .auth-title {
        font-size: 24px;
        margin-bottom: 8px;
        padding: 0;
    }
    
    .auth-subtitle {
        font-size: 14px;
        margin-bottom: 24px;
        padding: 0;
    }
    
    .form-group {
        margin-bottom: 16px;
    }
    
    .form-input {
        padding: 13px 13px 13px 45px;
        font-size: 16px;
        border-radius: 10px;
    }
    
    .form-input-icon {
        left: 13px;
        width: 16px;
        height: 16px;
    }
    
    .password-toggle {
        right: 13px;
    }
    
    .auth-submit-btn {
        padding: 14px;
        font-size: 16px;
        margin-bottom: 18px;
        border-radius: 10px;
    }
    
    .auth-error-message {
        padding: 12px 14px;
        font-size: 13px;
        margin-bottom: 20px;
        border-radius: 10px;
    }
    
    .form-help {
        font-size: 11px;
    }
    
    .checkbox-label {
        font-size: 12px;
    }
}

/* Asegurar que el contenido no se salga en pantallas muy pequeñas */
@media (max-width: 360px) {
    .agrochamba-auth-wrapper {
        padding: 16px 12px !important;
    }
    
    .auth-title {
        font-size: 22px;
    }
    
    .auth-subtitle {
        font-size: 13px;
    }
    
    .form-input {
        padding: 12px 12px 12px 42px;
    }
}
</style>

<div class="agrochamba-auth-wrapper">
    <div class="auth-container">
        <div class="auth-logo-container">
            <div class="auth-logo">
                <img src="https://agrochamba.com/wp-content/uploads/2025/09/image-removebg-preview-5.png" alt="AgroChamba Logo" />
            </div>
        </div>

        <!-- Modal de selección de tipo de cuenta -->
        <div id="account-type-modal" class="account-type-modal">
            <div class="modal-overlay" onclick="closeAccountTypeModal()"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Únete a AgroChamba</h2>
                    <p class="modal-subtitle">Elige tu tipo de cuenta para empezar</p>
                </div>
                <div class="modal-options">
                    <button class="modal-option-btn" onclick="selectAccountType('subscriber')">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <div class="option-content">
                            <span class="option-title">Busco Chamba</span>
                            <small class="option-subtitle">Soy trabajador</small>
                        </div>
                    </button>
                    <button class="modal-option-btn" onclick="selectAccountType('employer')">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="3" x2="9" y2="21"/>
                        </svg>
                        <div class="option-content">
                            <span class="option-title">Busco Talentos</span>
                            <small class="option-subtitle">Soy empresa</small>
                        </div>
                    </button>
                </div>
                <button class="modal-close-btn" onclick="closeAccountTypeModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Formulario de registro (oculto inicialmente) -->
        <div id="register-form-container" class="register-form-container" style="display: none;">
            <h1 class="auth-title" id="register-title">Crea tu cuenta</h1>
            <p class="auth-subtitle" id="register-subtitle">Únete a AgroChamba y encuentra tu próximo trabajo</p>

            <div class="auth-form-wrapper">
            <?php if ($register_error): ?>
                <div class="auth-error-message">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span><?php echo esc_html($register_error); ?></span>
                </div>
            <?php endif; ?>

            <form name="registerform" id="registerform" action="<?php echo esc_url(home_url('/registro')); ?>" method="post" class="auth-form" novalidate>
                <div class="form-group">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <input 
                        type="text" 
                        name="user_login" 
                        id="user_login" 
                        class="form-input" 
                        placeholder="Usuario"
                        required
                        autocomplete="username"
                        pattern="[a-zA-Z0-9_]+"
                        title="Solo letras, números y guiones bajos">
                </div>

                <div class="form-group">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <input 
                        type="email" 
                        name="user_email" 
                        id="user_email" 
                        class="form-input" 
                        placeholder="Correo Electrónico"
                        required
                        autocomplete="email">
                </div>

                <div class="form-group password-input-wrapper">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input 
                        type="password" 
                        name="user_pass" 
                        id="user_pass" 
                        class="form-input" 
                        placeholder="Contraseña"
                        required
                        autocomplete="new-password"
                        minlength="8">
                    <button type="button" class="password-toggle" onclick="togglePassword('user_pass', this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="eye-icon">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="eye-off-icon" style="display: none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>

                <!-- Campo oculto para el rol seleccionado -->
                <input type="hidden" name="user_role" id="user_role" value="">

                <!-- Campos adicionales para empresas (simplificados como en la app móvil) -->
                <div id="company-fields" class="company-fields" style="display: none;">
                    <div class="form-group">
                        <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="3" x2="9" y2="21"/>
                        </svg>
                        <input 
                            type="text" 
                            name="ruc" 
                            id="ruc" 
                            class="form-input" 
                            placeholder="RUC"
                            autocomplete="off"
                            pattern="[0-9]{11}"
                            maxlength="11">
                    </div>

                    <div class="form-group">
                        <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="3" x2="9" y2="21"/>
                        </svg>
                        <input 
                            type="text" 
                            name="company_name" 
                            id="company_name" 
                            class="form-input" 
                            placeholder="Razón Social"
                            autocomplete="organization">
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" id="terms" required>
                        <span>Acepto los <a href="#" target="_blank">términos y condiciones</a> y la <a href="#" target="_blank">política de privacidad</a></span>
                    </label>
                </div>

                <?php wp_nonce_field('agrochamba-register', 'agrochamba-register-nonce'); ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url()); ?>">

                <button type="submit" class="auth-submit-btn">
                    Crear cuenta
                </button>
            </form>

            <div class="auth-footer">
                <p>¿Ya tienes una cuenta? <a href="<?php echo esc_url(wp_login_url()); ?>" class="auth-link">Inicia sesión</a></p>
            </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos adicionales para registro */
.role-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 8px;
}

.role-option {
    cursor: pointer;
    margin: 0;
}

.role-option input[type="radio"] {
    display: none;
}

.role-card {
    padding: 16px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    text-align: center;
    transition: all 0.2s;
    background: #fff;
}

.role-card svg {
    color: #2d5016;
    margin-bottom: 8px;
}

.role-card span {
    display: block;
    font-weight: 600;
    color: #000;
    margin-bottom: 4px;
}

.role-card small {
    display: block;
    font-size: 12px;
    color: #666;
}

.role-option input[type="radio"]:checked + .role-card {
    border-color: #2d5016;
    background: rgba(45, 80, 22, 0.05);
}

.role-option:hover .role-card {
    border-color: #2d5016;
}

.company-fields {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid #e0e0e0;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.company-fields .form-group {
    margin-bottom: 18px;
}

.company-fields textarea.form-input {
    padding-top: 16px;
    padding-bottom: 16px;
    font-family: inherit;
    line-height: 1.5;
}

/* Modal de selección de tipo de cuenta */
.account-type-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 100000;
    align-items: center;
    justify-content: center;
}

.account-type-modal.active {
    display: flex;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.modal-content {
    position: relative;
    background: #fff;
    border-radius: 20px;
    padding: 32px;
    max-width: 420px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideUp 0.3s ease-out;
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    text-align: center;
    margin-bottom: 32px;
}

.modal-title {
    font-size: 28px;
    font-weight: 700;
    color: #000;
    margin: 0 0 8px 0;
}

.modal-subtitle {
    font-size: 16px;
    color: #666;
    margin: 0;
}

.modal-options {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.modal-option-btn {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    border: 2px solid #e0e0e0;
    border-radius: 16px;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    width: 100%;
}

.modal-option-btn:hover {
    border-color: #2d5016;
    background: rgba(45, 80, 22, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(45, 80, 22, 0.15);
}

.modal-option-btn svg {
    color: #2d5016;
    flex-shrink: 0;
}

.option-content {
    flex: 1;
}

.option-title {
    display: block;
    font-size: 18px;
    font-weight: 600;
    color: #000;
    margin-bottom: 4px;
}

.option-subtitle {
    display: block;
    font-size: 14px;
    color: #666;
}

.modal-close-btn {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 36px;
    height: 36px;
    border: none;
    background: #f5f5f5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    color: #666;
}

.modal-close-btn:hover {
    background: #e0e0e0;
    color: #000;
}

.register-form-container {
    width: 100%;
}

@media (max-width: 480px) {
    .modal-content {
        padding: 24px;
        border-radius: 16px;
    }
    
    .modal-title {
        font-size: 24px;
    }
    
    .modal-subtitle {
        font-size: 14px;
    }
    
    .modal-option-btn {
        padding: 16px;
    }
    
    .option-title {
        font-size: 16px;
    }
    
    .company-fields {
        margin-top: 16px;
        padding-top: 16px;
    }
}
</style>

<script>
// Ocultar elementos de Bricks Builder que puedan aparecer
function hideBricksElements() {
    const bricksSelectors = [
        'header',
        'footer',
        'nav',
        '.site-header',
        '.site-footer',
        '.brxe-container',
        '.brxe-wrapper',
        '.brxe-section',
        '[class*="brxe-"]',
        '[data-bricks-id]',
        '.bricks-layout-wrapper',
        '.bricks-layout-content',
        '.bricks-layout-header',
        '.bricks-layout-footer'
    ];
    
    bricksSelectors.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(el => {
            if (!el.closest('.agrochamba-auth-wrapper')) {
                el.style.display = 'none';
                el.style.visibility = 'hidden';
                el.style.opacity = '0';
                el.style.height = '0';
                el.style.overflow = 'hidden';
            }
        });
    });
}

hideBricksElements();
document.addEventListener('DOMContentLoaded', hideBricksElements);
document.addEventListener('load', hideBricksElements);

const observer = new MutationObserver(hideBricksElements);
observer.observe(document.body, {
    childList: true,
    subtree: true
});

function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const eyeIcon = button.querySelector('.eye-icon');
    const eyeOffIcon = button.querySelector('.eye-off-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        eyeIcon.style.display = 'none';
        eyeOffIcon.style.display = 'block';
    } else {
        input.type = 'password';
        eyeIcon.style.display = 'block';
        eyeOffIcon.style.display = 'none';
    }
}

// Funciones para manejar el modal de selección de tipo de cuenta
function showAccountTypeModal() {
    const modal = document.getElementById('account-type-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeAccountTypeModal() {
    const modal = document.getElementById('account-type-modal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function selectAccountType(role) {
    const roleInput = document.getElementById('user_role');
    const registerFormContainer = document.getElementById('register-form-container');
    const companyFields = document.getElementById('company-fields');
    const registerTitle = document.getElementById('register-title');
    const registerSubtitle = document.getElementById('register-subtitle');
    const companyNameInput = document.getElementById('company_name');
    const rucInput = document.getElementById('ruc');
    
    if (roleInput && registerFormContainer) {
        roleInput.value = role;
        
        // Mostrar formulario
        registerFormContainer.style.display = 'block';
        
        // Cerrar modal
        closeAccountTypeModal();
        
        // Scroll al formulario
        setTimeout(() => {
            registerFormContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
        
        if (role === 'employer') {
            // Configurar para empresa
            registerTitle.textContent = 'Registro para Empresas';
            registerSubtitle.textContent = 'Completa los datos para crear tu cuenta empresarial';
            companyFields.style.display = 'block';
            if (companyNameInput) companyNameInput.required = true;
            if (rucInput) rucInput.required = true;
        } else {
            // Configurar para trabajador
            registerTitle.textContent = 'Crea tu cuenta';
            registerSubtitle.textContent = 'Únete a AgroChamba y encuentra tu próximo trabajo';
            companyFields.style.display = 'none';
            if (companyNameInput) companyNameInput.required = false;
            if (rucInput) rucInput.required = false;
        }
    }
}

// Manejar envío del formulario y mostrar modal
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si hay parámetro en URL para mostrar directamente el formulario
    const urlParams = new URLSearchParams(window.location.search);
    const roleParam = urlParams.get('role');
    
    if (roleParam === 'subscriber' || roleParam === 'employer') {
        // Mostrar formulario directamente
        selectAccountType(roleParam);
    } else {
        // Mostrar modal de selección
        showAccountTypeModal();
    }
    
    // Cerrar modal al hacer clic fuera
    const modal = document.getElementById('account-type-modal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal || e.target.classList.contains('modal-overlay')) {
                closeAccountTypeModal();
            }
        });
    }
    
    // Manejar envío del formulario
    const form = document.getElementById('registerform');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('.auth-submit-btn');
            const userLogin = document.getElementById('user_login').value.trim();
            const userEmail = document.getElementById('user_email').value.trim();
            const userPass = document.getElementById('user_pass').value.trim();
            const terms = document.getElementById('terms').checked;
            
            const userRole = document.getElementById('user_role').value;
            const companyName = document.getElementById('company_name')?.value.trim();
            const ruc = document.getElementById('ruc')?.value.trim();
            
            // Validar que se haya seleccionado un tipo de cuenta
            if (!userRole) {
                e.preventDefault();
                alert('Por favor, selecciona un tipo de cuenta.');
                showAccountTypeModal();
                return false;
            }
            
            // Validaciones básicas
            if (!userLogin || !userEmail || !userPass) {
                e.preventDefault();
                alert('Por favor, completa todos los campos obligatorios.');
                return false;
            }
            
            if (userPass.length < 8) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 8 caracteres.');
                return false;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('Debes aceptar los términos y condiciones.');
                return false;
            }
            
            // Validaciones adicionales para empresas
            if (userRole === 'employer') {
                if (!ruc || ruc.length !== 11 || !/^\d+$/.test(ruc)) {
                    e.preventDefault();
                    alert('Por favor, ingresa un RUC válido (11 dígitos).');
                    return false;
                }
                if (!companyName) {
                    e.preventDefault();
                    alert('Por favor, ingresa la razón social de la empresa.');
                    return false;
                }
            }
            
            // Mostrar estado de carga
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Creando cuenta...</span>';
        });
    }
});
</script>

<?php
get_footer();

