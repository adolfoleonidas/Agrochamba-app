# 👤 Agregar Ícono de Perfil al Header

## Objetivo

Agregar un ícono de perfil en el header que permita a los usuarios acceder a `/mi-perfil` y cerrar sesión, similar a la app móvil.

---

## Opción 1: Código HTML/PHP (Para Bricks Builder)

### Paso 1: Ir al Header en Bricks

1. Ve a **Bricks** → **Templates**
2. Edita el template de **Header**
3. Agrega un elemento **"Code"** en la esquina superior derecha

### Paso 2: Agregar el Código

Pega este código en el elemento Code:

```php
<?php if (is_user_logged_in()): 
    $current_user = wp_get_current_user();
    $profile_url = home_url('/mi-perfil');
    $logout_url = wp_logout_url(home_url('/trabajos'));
    $user_avatar = get_avatar_url($current_user->ID, array('size' => 80));
?>
<div class="agrochamba-user-menu">
    <button class="user-menu-toggle" onclick="toggleUserMenu(event)">
        <img src="<?php echo esc_url($user_avatar); ?>" alt="Perfil" class="user-avatar">
        <span class="user-name"><?php echo esc_html($current_user->display_name); ?></span>
        <svg class="dropdown-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"/>
        </svg>
    </button>
    <div class="user-menu-dropdown" id="userMenuDropdown">
        <a href="<?php echo esc_url($profile_url); ?>" class="menu-item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>Mi Perfil</span>
        </a>
        <div class="menu-divider"></div>
        <a href="<?php echo esc_url($logout_url); ?>" class="menu-item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</div>
<?php else: 
    $login_url = home_url('/login');
    $register_url = home_url('/registro');
?>
<div class="agrochamba-auth-buttons">
    <a href="<?php echo esc_url($login_url); ?>" class="btn-login">Iniciar Sesión</a>
    <a href="<?php echo esc_url($register_url); ?>" class="btn-register">Registrarse</a>
</div>
<?php endif; ?>
```

### Paso 3: Agregar Estilos CSS

En **Bricks** → **Theme Styles** → **CSS**, agrega:

```css
/* ==========================================
   MENÚ DE USUARIO EN HEADER
   ========================================== */
.agrochamba-user-menu {
    position: relative;
    display: inline-block;
}

.user-menu-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 16px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
    font-size: 14px;
}

.user-menu-toggle:hover {
    border-color: #2d5016;
    box-shadow: 0 2px 8px rgba(45, 80, 22, 0.1);
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.user-name {
    color: #333;
    font-weight: 500;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.dropdown-icon {
    color: #666;
    transition: transform 0.2s;
}

.user-menu-toggle:hover .dropdown-icon {
    color: #2d5016;
}

.user-menu-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    min-width: 200px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s;
    z-index: 1000;
}

.user-menu-dropdown.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: #333;
    text-decoration: none;
    transition: all 0.2s;
}

.menu-item:hover {
    background: #f5f5f5;
    color: #2d5016;
}

.menu-item:first-child {
    border-radius: 12px 12px 0 0;
}

.menu-item:last-child {
    border-radius: 0 0 12px 12px;
}

.menu-item svg {
    color: #666;
    flex-shrink: 0;
}

.menu-item:hover svg {
    color: #2d5016;
}

.menu-divider {
    height: 1px;
    background: #e0e0e0;
    margin: 4px 0;
}

/* Botones de Login/Registro (cuando no está logueado) */
.agrochamba-auth-buttons {
    display: flex;
    gap: 12px;
    align-items: center;
}

.btn-login,
.btn-register {
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
    display: inline-block;
}

.btn-login {
    color: #2d5016;
    background: transparent;
    border: 1px solid #2d5016;
}

.btn-login:hover {
    background: #2d5016;
    color: #fff;
}

.btn-register {
    background: #2d5016;
    color: #fff;
    border: 1px solid #2d5016;
}

.btn-register:hover {
    background: #1f350d;
    border-color: #1f350d;
}

/* Responsive */
@media (max-width: 768px) {
    .user-name {
        display: none;
    }
    
    .user-menu-toggle {
        padding: 8px;
        border-radius: 50%;
    }
    
    .user-menu-dropdown {
        right: -8px;
    }
    
    .agrochamba-auth-buttons {
        gap: 8px;
    }
    
    .btn-login,
    .btn-register {
        padding: 8px 16px;
        font-size: 13px;
    }
}
```

### Paso 4: Agregar JavaScript

En **Bricks** → **Theme Styles** → **Scripts**, agrega:

```javascript
// Función para toggle del menú de usuario
function toggleUserMenu(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('userMenuDropdown');
    dropdown.classList.toggle('active');
}

// Cerrar menú al hacer clic fuera
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userMenuDropdown');
    if (dropdown && dropdown.classList.contains('active')) {
        dropdown.classList.remove('active');
    }
});

// Prevenir que el dropdown se cierre al hacer clic dentro
document.addEventListener('DOMContentLoaded', function() {
    const dropdown = document.getElementById('userMenuDropdown');
    if (dropdown) {
        dropdown.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    }
});
```

---

## Opción 2: Menu de WordPress (Más Simple)

### Paso 1: Crear Menú en WordPress

1. Ve a **Apariencia** → **Menús**
2. Crea un nuevo menú llamado "Usuario"
3. Agrega estos elementos:
   - **Página personalizada** → URL: `/mi-perfil` → Texto: "Mi Perfil"
   - **Página personalizada** → URL: `#logout` → Texto: "Cerrar Sesión"

### Paso 2: Agregar al Header de Bricks

En el header de Bricks, agrega un elemento **"Nav Menu"** y selecciona el menú "Usuario"

### Paso 3: Configurar Visibilidad

1. Selecciona el elemento Nav Menu
2. En **Conditions**, agrega:
   - **User is logged in** = Yes

### Paso 4: Agregar Funcionalidad de Logout

Agrega este código en **Theme Functions** o en un plugin:

```php
// Manejar logout desde menú
add_action('wp_footer', function() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const logoutLinks = document.querySelectorAll('a[href="#logout"]');
        logoutLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = '<?php echo wp_logout_url(home_url('/trabajos')); ?>';
            });
        });
    });
    </script>
    <?php
});
```

---

## Opción 3: Widget Personalizado (Avanzado)

Si prefieres crear un widget reutilizable, crea un archivo:

**Ubicación:** `wp-content/plugins/agrochamba-core/widgets/user-menu-widget.php`

```php
<?php
/**
 * Widget de Menú de Usuario para Bricks Builder
 */

if (!defined('ABSPATH')) {
    exit;
}

class Agrochamba_User_Menu_Widget extends \Bricks\Element {
    public $category = 'agrochamba';
    public $name = 'agrochamba-user-menu';
    public $icon = 'ti-user';

    public function get_label() {
        return 'Menú de Usuario';
    }

    public function render() {
        if (!is_user_logged_in()) {
            $login_url = home_url('/login');
            $register_url = home_url('/registro');
            
            echo '<div class="agrochamba-auth-buttons">';
            echo '<a href="' . esc_url($login_url) . '" class="btn-login">Iniciar Sesión</a>';
            echo '<a href="' . esc_url($register_url) . '" class="btn-register">Registrarse</a>';
            echo '</div>';
            return;
        }

        $current_user = wp_get_current_user();
        $profile_url = home_url('/mi-perfil');
        $logout_url = wp_logout_url(home_url('/trabajos'));
        $user_avatar = get_avatar_url($current_user->ID, array('size' => 80));

        ?>
        <div class="agrochamba-user-menu">
            <button class="user-menu-toggle" onclick="toggleUserMenu(event)">
                <img src="<?php echo esc_url($user_avatar); ?>" alt="Perfil" class="user-avatar">
                <span class="user-name"><?php echo esc_html($current_user->display_name); ?></span>
                <svg class="dropdown-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </button>
            <div class="user-menu-dropdown" id="userMenuDropdown">
                <a href="<?php echo esc_url($profile_url); ?>" class="menu-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span>Mi Perfil</span>
                </a>
                <div class="menu-divider"></div>
                <a href="<?php echo esc_url($logout_url); ?>" class="menu-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
        <?php
    }
}

// Registrar el widget
add_action('init', function() {
    if (class_exists('\Bricks\Elements')) {
        \Bricks\Elements::register_element('agrochamba-user-menu-widget.php');
    }
});
```

Luego, en el header de Bricks, simplemente arrastra el elemento "Menú de Usuario" desde la categoría "AgroChamba".

---

## 🎨 Personalización

### Cambiar Colores

Modifica las variables en el CSS:

```css
/* Color principal */
--primary-color: #2d5016;

/* Color hover */
--primary-hover: #1f350d;

/* Color de borde */
--border-color: #e0e0e0;
```

### Cambiar Tamaño del Avatar

En el código PHP, cambia:

```php
$user_avatar = get_avatar_url($current_user->ID, array('size' => 80)); // 80 = tamaño
```

Y en el CSS:

```css
.user-avatar {
    width: 32px; /* Cambia este valor */
    height: 32px; /* Y este también */
}
```

### Agregar Más Opciones al Menú

Agrega más elementos después de "Mi Perfil":

```html
<a href="/mis-favoritos" class="menu-item">
    <svg>...</svg>
    <span>Mis Favoritos</span>
</a>
```

---

## ✅ Resultado Final

Una vez implementado, el header tendrá:

- ✅ **Avatar circular del usuario** (foto de perfil)
- ✅ **Nombre del usuario** (oculto en móvil)
- ✅ **Flecha desplegable**
- ✅ **Dropdown con opciones:**
  - Mi Perfil
  - Cerrar Sesión
- ✅ **Animación suave**
- ✅ **Responsive (móvil y desktop)**
- ✅ **Cierra al hacer clic fuera**

**Para usuarios no logueados:**
- ✅ **Botón "Iniciar Sesión"**
- ✅ **Botón "Registrarse"**

---

## 🧪 Prueba

1. Guarda los cambios en Bricks
2. Recarga la página en el frontend
3. Si estás logueado, verás el menú de usuario
4. Haz clic y verifica que aparezca el dropdown
5. Haz clic en "Mi Perfil" → debe ir a `/mi-perfil`
6. Haz clic en "Cerrar Sesión" → debe cerrar sesión y redirigir a `/trabajos`

---

**Recomendación:** Usa **Opción 1** por ser la más completa y personalizable.

