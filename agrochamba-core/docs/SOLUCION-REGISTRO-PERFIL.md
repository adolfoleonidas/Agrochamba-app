# Solución: Página de Perfil después del Registro

## Problema Resuelto

Al crear una cuenta en WordPress, los usuarios no eran redirigidos a la página de perfil personalizada, sino al admin de WordPress (para empresas) o al home (para trabajadores). Esto evidenciaba el uso de WordPress y no permitía a los usuarios completar su perfil fácilmente.

## Cambios Implementados

### 1. **Redirección después del Registro** (`17-custom-auth-pages.php`)

**Antes:**
```php
// Redirigir según el rol
if ($user_role === 'employer') {
    wp_redirect(admin_url('edit.php?post_type=trabajo'));
} else {
    wp_redirect(home_url());
}
```

**Después:**
```php
// Redirigir a la página de perfil personalizada
$profile_page = get_page_by_path('mi-perfil');
if ($profile_page) {
    // Agregar parámetro para mostrar mensaje de bienvenida
    $redirect_url = add_query_arg('welcome', '1', get_permalink($profile_page->ID));
    wp_redirect($redirect_url);
} else {
    // Fallback: si no existe la página de perfil, ir al home
    wp_redirect(home_url());
}
```

### 2. **Mensaje de Bienvenida** (`templates/profile.php`)

Se agregó un mensaje de bienvenida que aparece cuando el usuario recién se registra:

```php
<?php if (isset($_GET['welcome']) && $_GET['welcome'] === '1'): ?>
<div class="message message-success">
    <svg>...</svg>
    <span>¡Bienvenido a AgroChamba! Tu cuenta ha sido creada exitosamente. Completa tu perfil para comenzar.</span>
</div>
<?php endif; ?>
```

### 3. **Carga del Template de Perfil** (`18-custom-user-panel.php`)

Se agregó una función para cargar el template personalizado de perfil (`profile.php`) cuando se accede a la página `/mi-perfil`:

```php
function agrochamba_load_profile_template($template) {
    // Detecta si es la página de perfil y carga el template personalizado
    // ...
}
add_filter('page_template', 'agrochamba_load_profile_template', 10);
add_filter('template_include', 'agrochamba_load_profile_template', 99);
```

### 4. **Redirección desde Home a Perfil**

Se agregó una redirección automática para usuarios logueados que accedan al home:

```php
function agrochamba_redirect_home_to_profile() {
    // Usuarios normales son redirigidos automáticamente a /mi-perfil
    // Administradores pueden ver el home si lo desean
}
```

### 5. **Filtro de Login Redirect**

Se implementó un filtro `login_redirect` para asegurar que todos los usuarios vayan al perfil después del login:

```php
add_filter('login_redirect', 'agrochamba_login_redirect', 10, 3);
```

### 6. **Opciones de Diagnóstico**

Se agregó la capacidad de recrear la página de perfil manualmente si hay problemas:

```
/wp-admin/edit.php?post_type=page&agrochamba_recreate_profile_page=1
```

## Flujo de Registro Actualizado

1. **Usuario llega a `/registro`** → Ve formulario personalizado sin evidenciar WordPress
2. **Completa el formulario** → Selecciona tipo de cuenta (Trabajador o Empresa)
3. **Envía el formulario** → Se crea la cuenta y hace auto-login
4. **Es redirigido a `/mi-perfil?welcome=1`** → Ve página de perfil con mensaje de bienvenida
5. **Puede editar su perfil** → Todos los campos disponibles (nombre, teléfono, bio, etc.)
   - **Si es Empresa:** También puede editar descripción, dirección, redes sociales, etc.

## Flujo de Login Actualizado

1. **Usuario llega a `/login`** → Ve formulario personalizado
2. **Ingresa credenciales** → Envía el formulario
3. **Es redirigido a `/mi-perfil`** → Siempre ve su perfil personalizado
   - **Administradores:** Pueden ir al admin si acceden específicamente a `/wp-admin`
   - **Usuarios normales:** Siempre van al perfil

## Redirección Automática desde Home

Si un usuario logueado accede a `agrochamba.com` (home):
- **Usuarios normales:** Son redirigidos automáticamente a `/mi-perfil`
- **Administradores:** Pueden ver el home si lo desean

Esto asegura que los usuarios siempre vean interfaces personalizadas y nunca el contenido por defecto de WordPress.

## Páginas Personalizadas Creadas

El sistema crea automáticamente estas páginas:

1. **`/login`** → Página de inicio de sesión personalizada
2. **`/registro`** → Página de registro personalizada
3. **`/recuperar-contrasena`** → Página de recuperación de contraseña
4. **`/mi-perfil`** → Página de perfil de usuario personalizada (**NUEVA**)

Todas estas páginas:
- ✅ No evidencian el uso de WordPress
- ✅ Tienen diseño similar a la app móvil
- ✅ Desactivan Bricks Builder automáticamente
- ✅ Tienen headers de seguridad apropiados

## Verificar que Todo Funciona

### 1. Verificar que las Páginas Existen

En el admin de WordPress, ve a **Páginas** y verifica que existan:

- **Login** (slug: `login`)
- **Registro** (slug: `registro`)
- **Recuperar Contraseña** (slug: `recuperar-contrasena`)
- **Mi Perfil** (slug: `mi-perfil`)

Todas deben tener asignado un template personalizado:
- `login.php`
- `register.php`
- `lostpassword.php`
- `profile.php`

### 2. Probar el Flujo de Registro

1. Abre una ventana de incógnito/privada
2. Ve a `https://tudominio.com/registro`
3. Completa el formulario con un nuevo usuario de prueba
4. Envía el formulario
5. **Verifica que seas redirigido a `/mi-perfil`** con un mensaje de bienvenida
6. Verifica que puedas editar tu perfil (nombre, teléfono, bio, etc.)
7. Guarda los cambios y verifica que se guarden correctamente

### 3. Probar con Usuario Empresa

Repite el flujo anterior pero selecciona **"Empresa"** como tipo de cuenta. Verifica que:

- Seas redirigido a `/mi-perfil`
- Veas campos adicionales: descripción de empresa, dirección, redes sociales, etc.
- Puedas guardar todos los campos correctamente

### 4. Verificar que Bricks Builder No Aparezca

En la página `/mi-perfil`, verifica que:

- No aparezca el header/footer de Bricks Builder
- No aparezcan elementos del tema
- Solo se vea la interfaz personalizada de perfil
- El diseño se vea limpio y similar a la app móvil

## Solución de Problemas

### Problema: La página `/mi-perfil` no existe

**Solución:**

1. Ve al admin de WordPress
2. Accede a esta URL (reemplaza `tudominio.com`):
   ```
   https://tudominio.com/wp-admin/edit.php?post_type=page&agrochamba_recreate_profile_page=1
   ```
3. Esto recreará la página automáticamente

### Problema: Después del registro sigo siendo redirigido al home o admin

**Posibles causas:**

1. **La página `/mi-perfil` no existe**
   - Usa la solución anterior para recrearla

2. **El tema o plugin está interfiriendo**
   - Desactiva otros plugins temporalmente
   - Verifica que el módulo 17 esté cargándose correctamente

3. **Cache del navegador**
   - Limpia el cache del navegador
   - Intenta en una ventana de incógnito

### Problema: El template de perfil no se carga

**Solución:**

1. Verifica que el archivo existe:
   ```
   agrochamba-core/templates/profile.php
   ```

2. Verifica que la página tenga el template asignado:
   - Ve a **Páginas** → **Mi Perfil** → **Editar**
   - En la barra lateral derecha, busca **"Atributos de página"**
   - Verifica que el template sea `profile.php`

3. Regenera los permalinks:
   - Ve a **Ajustes** → **Enlaces permanentes**
   - Haz clic en **"Guardar cambios"** sin modificar nada

### Problema: Bricks Builder sigue apareciendo

**Solución:**

1. Verifica que el módulo 18 esté activo
2. Limpia el cache de Bricks Builder:
   - Ve a **Bricks** → **Settings** → **Performance**
   - Haz clic en **"Clear cache"**
3. Limpia el cache del servidor/CDN si usas uno

## API Endpoints Disponibles

La página de perfil usa estos endpoints para funcionar:

- **GET** `/wp-json/agrochamba/v1/me/profile` - Obtener datos del perfil
- **PUT** `/wp-json/agrochamba/v1/me/profile` - Actualizar perfil
- **POST** `/wp-json/agrochamba/v1/me/profile/photo` - Subir foto de perfil
- **DELETE** `/wp-json/agrochamba/v1/me/profile/photo` - Eliminar foto de perfil

Si hay problemas con la API, verifica:

1. Los permalinks estén configurados correctamente
2. El módulo 04 (`04-endpoints-user-profile.php`) esté activo
3. No haya errores en los logs de PHP

## Campos Disponibles en el Perfil

### Usuarios Trabajadores (subscriber)
- Nombre a mostrar
- Nombre
- Apellido
- Correo electrónico
- Teléfono
- Biografía
- Foto de perfil

### Usuarios Empresa (employer)
Todos los campos anteriores más:
- Descripción de la empresa
- Dirección
- Teléfono de la empresa
- Sitio web
- Facebook
- Instagram
- LinkedIn
- Twitter

## Seguridad

El sistema incluye:

- ✅ Validación de nonce para prevenir CSRF
- ✅ Sanitización de todos los datos de entrada
- ✅ Headers de seguridad apropiados
- ✅ Validación de tipos de archivo para fotos
- ✅ Límite de tamaño de archivos (5MB por defecto)
- ✅ Rate limiting para prevenir abuso

## Próximos Pasos Recomendados

1. **Personalizar el mensaje de bienvenida** si lo deseas
2. **Agregar validaciones adicionales** según tus necesidades
3. **Configurar emails de bienvenida** (opcional)
4. **Personalizar los campos** si necesitas campos adicionales
5. **Implementar notificaciones** cuando se complete el perfil

## Soporte

Si tienes problemas o necesitas ayuda adicional:

1. Verifica los logs de errores de PHP
2. Revisa la consola del navegador para errores de JavaScript
3. Verifica que todos los módulos estén activos
4. Contacta al equipo de desarrollo con los detalles del error

