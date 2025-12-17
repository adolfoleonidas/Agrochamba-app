<?php
/**
 * Template Name: Login Personalizado AgroChamba
 * 
 * Página de inicio de sesión personalizada con diseño similar a la app móvil
 */

if (!defined('ABSPATH')) {
    exit;
}

// Si el usuario ya está logueado, redirigir según su rol
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $is_admin = in_array('administrator', $current_user->roles);
    
    // Si es administrador y quiere ir al admin, permitirlo
    if ($is_admin && isset($_GET['redirect_to']) && strpos($_GET['redirect_to'], admin_url()) !== false) {
        wp_redirect(admin_url());
        exit;
    }
    
    // Para otros usuarios, ir al listado de trabajos (como en la app móvil)
    $trabajos_url = get_post_type_archive_link('trabajo');
    $redirect_url = $trabajos_url ? $trabajos_url : home_url();
    wp_redirect($redirect_url);
    exit;
}

// Manejar errores de login
$login_error = '';
$login_warning = '';

// Por defecto, redirigir al listado de trabajos (como en la app móvil)
$trabajos_url = get_post_type_archive_link('trabajo');
$default_redirect = $trabajos_url ? $trabajos_url : home_url();
$redirect_to = isset($_GET['redirect_to']) ? esc_url_raw($_GET['redirect_to']) : $default_redirect;

// Validar que redirect_to sea del mismo dominio
$redirect_host = parse_url($redirect_to, PHP_URL_HOST);
$site_host = parse_url(home_url(), PHP_URL_HOST);
if ($redirect_host !== $site_host) {
    $redirect_to = $default_redirect;
}

if (isset($_GET['login'])) {
    $login_status = sanitize_text_field($_GET['login']);
    
    switch ($login_status) {
        case 'failed':
            $login_error = 'Usuario o contraseña incorrectos.';
            break;
        case 'empty':
            $login_error = 'Por favor, completa todos los campos.';
            break;
        case 'invalid':
            $login_error = 'Los datos ingresados no son válidos.';
            break;
        case 'blocked':
            $login_error = 'Has excedido el número máximo de intentos. Por favor, espera 15 minutos antes de intentar nuevamente.';
            break;
        case 'false':
            $login_warning = 'Sesión cerrada correctamente.';
            break;
    }
}

// Verificar rate limiting para mostrar advertencia
if (function_exists('agrochamba_get_client_ip') && function_exists('agrochamba_check_login_rate_limit')) {
    $client_ip = agrochamba_get_client_ip();
    $rate_limit = agrochamba_check_login_rate_limit($client_ip);
    
    if ($rate_limit['attempts'] > 0 && $rate_limit['attempts'] < 5) {
        $remaining = 5 - $rate_limit['attempts'];
        $login_warning = "Atención: Te quedan {$remaining} intento(s) antes de ser bloqueado temporalmente.";
    }
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
    color: #000;
    text-align: center;
    line-height: 1.5;
    font-weight: 400;
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

.form-options {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-bottom: 32px;
    font-size: 14px;
}

.forgot-password-link {
    color: #2d5016;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.forgot-password-link:hover {
    color: #1a5d1a;
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
    
    .auth-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
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
    
    .form-options {
        margin-bottom: 28px;
        font-size: 14px;
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
    
    .form-options {
        margin-bottom: 24px;
        font-size: 13px;
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

        <h1 class="auth-title">Bienvenido a Agrochamba</h1>
        <p class="auth-subtitle">La plataforma n°1 para encontrar trabajos en el sector agro.</p>

        <div class="auth-form-wrapper">
            <?php if ($login_error): ?>
                <div class="auth-error-message">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span><?php echo esc_html($login_error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($login_warning && !$login_error): ?>
                <div class="auth-error-message" style="background: #fff3cd; border-color: #ffc107; color: #856404;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <span><?php echo esc_html($login_warning); ?></span>
                </div>
            <?php endif; ?>

            <form name="loginform" id="loginform" action="<?php echo esc_url(home_url('/login')); ?>" method="post" class="auth-form">
                <div class="form-group">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <input 
                        type="text" 
                        name="log" 
                        id="user_login" 
                        class="form-input" 
                        placeholder="Usuario o Correo Electrónico"
                        required
                        autocomplete="username"
                        value="<?php echo isset($_GET['user']) ? esc_attr($_GET['user']) : ''; ?>">
                </div>

                <div class="form-group password-input-wrapper">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input 
                        type="password" 
                        name="pwd" 
                        id="user_pass" 
                        class="form-input" 
                        placeholder="Contraseña"
                        required
                        autocomplete="current-password">
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

                <div class="form-options">
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="forgot-password-link">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                <!-- Honeypot field (oculto para humanos, visible para bots) -->
                <input type="text" name="website" value="" style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;" tabindex="-1" autocomplete="off">

                <input type="hidden" name="redirect_to" id="redirect_to_input" value="<?php echo esc_url($redirect_to); ?>">
                <?php wp_nonce_field('agrochamba-login', 'agrochamba-login-nonce'); ?>

                <button type="submit" class="auth-submit-btn">
                    Ingresar
                </button>
            </form>

            <div class="auth-footer">
                <p>¿No tienes una cuenta? <a href="<?php echo esc_url(wp_registration_url()); ?>" class="auth-link">Regístrate</a></p>
            </div>
        </div>
    </div>
</div>

<script>
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

// Ocultar elementos de Bricks Builder que puedan aparecer
function hideBricksElements() {
    // Ocultar todos los elementos de Bricks
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

// Ejecutar inmediatamente y después de que cargue el DOM
hideBricksElements();
document.addEventListener('DOMContentLoaded', hideBricksElements);
document.addEventListener('load', hideBricksElements);

// Observar cambios en el DOM para ocultar elementos nuevos de Bricks
const observer = new MutationObserver(hideBricksElements);
observer.observe(document.body, {
    childList: true,
    subtree: true
});

// Manejar envío del formulario
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginform');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('.auth-submit-btn');
            const userLogin = document.getElementById('user_login').value.trim();
            const userPass = document.getElementById('user_pass').value.trim();
            
            if (!userLogin || !userPass) {
                e.preventDefault();
                alert('Por favor, completa todos los campos.');
                return false;
            }
            
            // Mostrar estado de carga
            submitBtn.disabled = true;
            submitBtn.textContent = 'Ingresando...';
        });
    }
});
</script>

<?php
get_footer();
