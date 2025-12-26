<?php
/**
 * Template Name: Recuperar Contraseña Personalizado AgroChamba
 * 
 * Página de recuperación de contraseña personalizada con diseño similar a la app móvil
 */

if (!defined('ABSPATH')) {
    exit;
}

// Si el usuario ya está logueado, redirigir
if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

// Manejar errores y mensajes
$lostpassword_error = '';
$lostpassword_success = false;

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalidkey') {
        $lostpassword_error = 'El enlace de recuperación no es válido o ha expirado.';
    } elseif ($_GET['error'] === 'expiredkey') {
        $lostpassword_error = 'El enlace de recuperación ha expirado.';
    }
}

if (isset($_GET['checkemail']) && $_GET['checkemail'] === 'confirm') {
    $lostpassword_success = true;
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
    font-size: 28px;
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

.auth-success-message {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: #efe;
    border: 1px solid #cfc;
    border-radius: 12px;
    color: #3c3;
    margin-bottom: 24px;
    font-size: 14px;
}

#lostpassword-message-container {
    margin-bottom: 0;
}

#lostpassword-message-container .auth-error-message,
#lostpassword-message-container .auth-success-message {
    margin-bottom: 24px;
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

.auth-error-message svg,
.auth-success-message svg {
    flex-shrink: 0;
}

.form-group {
    margin-bottom: 32px;
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
    padding-top: 24px;
    margin-top: 24px;
    border-top: 1px solid #e0e0e0;
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
        font-size: 24px;
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
        margin-bottom: 28px;
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
    
    .auth-submit-btn {
        padding: 15px;
        font-size: 16px;
    }
    
    .auth-footer {
        padding-top: 20px;
        margin-top: 20px;
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
        font-size: 22px;
        margin-bottom: 8px;
        padding: 0;
    }
    
    .auth-subtitle {
        font-size: 14px;
        margin-bottom: 24px;
        padding: 0;
    }
    
    .form-group {
        margin-bottom: 24px;
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
    
    .auth-submit-btn {
        padding: 14px;
        font-size: 16px;
        border-radius: 10px;
    }
    
    .auth-error-message,
    .auth-success-message {
        padding: 12px 14px;
        font-size: 13px;
        margin-bottom: 20px;
        border-radius: 10px;
    }
    
    .auth-footer {
        padding-top: 16px;
        margin-top: 16px;
    }
}

/* Asegurar que el contenido no se salga en pantallas muy pequeñas */
@media (max-width: 360px) {
    .agrochamba-auth-wrapper {
        padding: 16px 12px !important;
    }
    
    .auth-title {
        font-size: 20px;
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

        <h1 class="auth-title">Recuperar Contraseña</h1>
        <p class="auth-subtitle">Ingresa tu correo o nombre de usuario.</p>

        <div class="auth-form-wrapper">
            <?php if ($lostpassword_error): ?>
                <div class="auth-error-message">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span><?php echo esc_html($lostpassword_error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($lostpassword_success): ?>
                <div class="auth-success-message">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <span>Se ha enviado un correo con las instrucciones para restablecer tu contraseña.</span>
                </div>
            <?php else: ?>
                <div id="lostpassword-message-container"></div>
                <form name="lostpasswordform" id="lostpasswordform" class="auth-form">
                    <div class="form-group">
                        <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input 
                            type="text" 
                            name="user_login" 
                            id="user_login" 
                            class="form-input" 
                            placeholder="Usuario o Correo Electrónico"
                            required
                            autocomplete="username"
                            value="<?php echo isset($_GET['login']) ? esc_attr($_GET['login']) : ''; ?>">
                    </div>

                    <button type="submit" class="auth-submit-btn" id="lostpassword-submit-btn">
                        Enviar código de restablecimiento
                    </button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                <p><a href="<?php echo esc_url(wp_login_url()); ?>" class="auth-link">← Volver al inicio de sesión</a></p>
            </div>
        </div>
    </div>
</div>

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

// Función para mostrar mensajes
function showLostPasswordMessage(message, type) {
    const container = document.getElementById('lostpassword-message-container');
    if (!container) return;
    
    container.innerHTML = '';
    
    const messageDiv = document.createElement('div');
    messageDiv.className = type === 'error' ? 'auth-error-message' : 'auth-success-message';
    
    const iconSvg = type === 'error' 
        ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
        : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
    
    messageDiv.innerHTML = iconSvg + '<span>' + message + '</span>';
    container.appendChild(messageDiv);
    
    // Scroll al mensaje
    messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Manejar envío del formulario usando REST API
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('lostpasswordform');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('lostpassword-submit-btn');
        const userLoginInput = document.getElementById('user_login');
        const userLogin = userLoginInput.value.trim();
        
        // Limpiar mensajes anteriores
        const messageContainer = document.getElementById('lostpassword-message-container');
        if (messageContainer) {
            messageContainer.innerHTML = '';
        }
        
        // Validación básica
        if (!userLogin) {
            showLostPasswordMessage('Por favor, ingresa tu usuario o correo electrónico.', 'error');
            userLoginInput.focus();
            return false;
        }
        
        // Mostrar estado de carga
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';
        
        try {
            // Llamar al endpoint REST API
            const response = await fetch('<?php echo esc_url(rest_url('agrochamba/v1/lost-password')); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_login: userLogin
                })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                // Error del servidor
                const errorMessage = data.message || 'Error al enviar el código. Por favor, intenta nuevamente.';
                showLostPasswordMessage(errorMessage, 'error');
            } else {
                // Éxito
                showLostPasswordMessage('Si el usuario existe, se ha enviado un código de 6 dígitos a tu correo electrónico. Revisa tu bandeja de entrada y spam.', 'success');
                
                // Limpiar el campo
                userLoginInput.value = '';
                
                // Opcional: Redirigir a página de ingreso de código después de 3 segundos
                // setTimeout(() => {
                //     window.location.href = '<?php echo esc_url(add_query_arg('step', 'code', wp_lostpassword_url())); ?>';
                // }, 3000);
            }
        } catch (error) {
            console.error('Error:', error);
            showLostPasswordMessage('Error de conexión. Por favor, verifica tu conexión a internet e intenta nuevamente.', 'error');
        } finally {
            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        
        return false;
    });
});
</script>

<?php
get_footer();

