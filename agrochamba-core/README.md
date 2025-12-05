# 🌾 AgroChamba Core

**Sistema completo de gestión de trabajos agrícolas con API REST personalizada para WordPress**

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/agrochamba/agrochamba-core)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## 📋 Descripción

AgroChamba Core es un plugin de WordPress que proporciona una API REST completa para gestionar ofertas de trabajo en el sector agrícola. Diseñado específicamente para conectar empresas agrícolas con trabajadores del campo.

### ✨ Características Principales

- 🔐 **Autenticación JWT** - Sistema seguro de autenticación con tokens
- 👥 **Gestión de Usuarios** - Registro de empresas y trabajadores
- 💼 **Gestión de Trabajos** - CRUD completo de ofertas laborales
- 📸 **Galería de Imágenes** - Sistema optimizado de manejo de imágenes
- ⭐ **Favoritos y Guardados** - Sistema de marcadores para usuarios
- 🏢 **Perfiles de Empresa** - Perfiles completos con redes sociales
- 📱 **Facebook Integration** - Publicación automática en Facebook
- 🚀 **Sistema de Caché** - Optimización de rendimiento
- 🔒 **Seguridad Avanzada** - CORS, Rate Limiting, Sanitización

---

## 📦 Instalación

### Requisitos

- WordPress 5.8 o superior
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Plugin JWT Authentication for WP REST API

### Pasos de Instalación

1. **Descargar el plugin**
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/agrochamba/agrochamba-core.git
   ```

2. **Activar el plugin**
   - Ve a `WP Admin > Plugins`
   - Activa "AgroChamba Core"

3. **Configurar JWT Authentication**
   - Instala y activa el plugin "JWT Authentication for WP REST API"
   - Configura el secret key en `wp-config.php`:
   ```php
   define('JWT_AUTH_SECRET_KEY', 'tu-clave-secreta-aqui');
   define('JWT_AUTH_CORS_ENABLE', true);
   ```

4. **Configurar Facebook (Opcional)**
   - Ve a `Ajustes > Facebook Integration`
   - Añade tu Page Access Token y Page ID

---

## 🚀 Uso Rápido

### Ejemplo de Registro de Empresa

```bash
POST /wp-json/agrochamba/v1/register-company
Content-Type: application/json

{
  "username": "empresa123",
  "email": "contacto@empresa.com",
  "password": "password123",
  "ruc": "20123456789",
  "razon_social": "Agrícola Los Andes SAC"
}
```

### Ejemplo de Crear Trabajo

```bash
POST /wp-json/agrochamba/v1/jobs
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Cosechadores de Café - Temporada Alta",
  "content": "Buscamos cosechadores con experiencia...",
  "ubicacion_id": 5,
  "cultivo_id": 3,
  "salario_min": 1200,
  "salario_max": 1500,
  "vacantes": 20
}
```

---

## 📚 Documentación Completa

La documentación completa de la API está disponible en:
- **[API Endpoints](docs/API-ENDPOINTS.md)** - Lista completa de endpoints
- **[Guía de Desarrollo](docs/DEVELOPMENT.md)** - Guía para desarrolladores
- **[Plan de Reorganización](REORGANIZATION_PLAN.md)** - Arquitectura del proyecto

---

## ✅ Guía rápida para Producción

Sigue esta checklist antes de subir a producción (Hostinger/WordPress):

1) Backup y entorno
- Realiza backup completo de base de datos y carpeta wp-content/uploads.
- Verifica que PHP 7.4+ y WordPress 5.8+ estén activos.

2) Configuración segura
- En wp-config.php, define un secreto robusto para JWT Authentication (32+ caracteres):
  ```php
  define('JWT_AUTH_SECRET_KEY', 'coloca_un_valor_largo_y_unico_generado_para_prod');
  define('JWT_AUTH_CORS_ENABLE', true);
  ```
- Asegúrate de que WP_DEBUG y WP_DEBUG_LOG estén desactivados en producción:
  ```php
  define('WP_DEBUG', false);
  define('WP_DEBUG_LOG', false);
  ```

3) CORS (dominios permitidos)
- Por defecto se permite:
  - https://agrochamba.com y https://www.agrochamba.com
  - localhost/127.0.0.1 para desarrollo
- Para añadir más orígenes (por ejemplo app.agrochamba.com), agrega este filtro en functions.php de tu theme o en un mu-plugin:
  ```php
  add_filter('agrochamba_allowed_origins', function(array $origins) {
      $origins[] = 'https://app.agrochamba.com';
      return array_values(array_unique($origins));
  });
  ```

4) Despliegue y activación
- Sube el plugin a wp-content/plugins/agrochamba-core o actualiza desde Git.
- Activa el plugin en WP Admin > Plugins.
- Ve a Ajustes > Enlaces permanentes y guarda para forzar un flush de permalinks.

5) Pruebas de humo (API)
- GET /wp-json para ver el índice y confirmar que no hay rutas duplicadas.
- Auth: login y obtén token JWT.
- Perfil: GET /agrochamba/v1/me/profile (con token) y PUT para actualizar un campo.
- Foto de perfil: DELETE /agrochamba/v1/me/profile/photo (con token) y verifica meta.
- Empresa: GET /agrochamba/v1/companies/{user_id}/profile (público) si corresponde.

6) Bandera del cargador moderno (opcional)
- Por defecto AGROCHAMBA_USE_MODULE_LOADER=false (modo híbrido/legacy seguro).
- En staging puedes encenderlo a true y validar; si todo está OK, planifica activarlo en producción.

7) Monitoreo post-deploy
- Revisa errores PHP del servidor y respuestas 429 (rate limit) o CORS bloqueadas durante 24–48h.

---

## 🏗️ Estructura del Proyecto

```
agrochamba-core/
├── agrochamba-core.php       # Plugin principal
├── config/                    # Configuración
│   ├── constants.php
│   └── bootstrap.php
├── src/                       # Código fuente (futuro)
│   └── Core/
│       ├── Autoloader.php
│       ├── ModuleLoader.php
│       └── PluginActivator.php
├── modules/                   # Módulos funcionales
│   ├── 00-security-cors.php
│   ├── 01-cpt-taxonomies.php
│   ├── 03-endpoints-auth.php
│   ├── 04-endpoints-user-profile.php
│   ├── 06-endpoints-jobs.php
│   └── ... (12 módulos totales)
├── includes/                  # Helpers y funciones
│   ├── functions.php
│   └── hooks.php
├── docs/                      # Documentación
│   └── API-ENDPOINTS.md
├── assets/                    # Recursos estáticos
├── templates/                 # Plantillas
└── tests/                     # Tests (futuro)
```

---

## 🔧 Configuración Avanzada

### Constantes Disponibles

```php
// Cache TTL
define('AGROCHAMBA_CACHE_JOBS_LIST_TTL', 5 * MINUTE_IN_SECONDS);
define('AGROCHAMBA_CACHE_SINGLE_JOB_TTL', 15 * MINUTE_IN_SECONDS);

// Rate Limiting
define('AGROCHAMBA_RATE_LIMIT_REQUESTS', 100); // Peticiones
define('AGROCHAMBA_RATE_LIMIT_WINDOW', 60);    // Segundos

// Image Sizes
define('AGROCHAMBA_IMAGE_CARD_WIDTH', 400);
define('AGROCHAMBA_IMAGE_DETAIL_WIDTH', 800);
```

### Hooks Disponibles

```php
// Después de cargar módulos
add_action('agrochamba_modules_loaded', function() {
    // Tu código aquí
});

// Filtrar respuesta JWT
add_filter('jwt_auth_token_before_dispatch', function($data, $user) {
    $data['custom_field'] = 'value';
    return $data;
}, 10, 2);
```

---

## 🛠️ Desarrollo

### Requisitos de Desarrollo

- Node.js y npm (para assets)
- Composer (para dependencias PHP)
- Git

### Setup de Desarrollo

```bash
# Clonar repositorio
git clone https://github.com/agrochamba/agrochamba-core.git
cd agrochamba-core

# Instalar dependencias (futuro)
composer install
npm install

# Ejecutar tests (futuro)
composer test
```

---

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---

## 📝 Changelog

### [2.0.0] - 2024-11-22

#### Added
- ✨ Nueva estructura de carpetas profesional
- ✨ Sistema de autoloader PSR-4
- ✨ Helper functions globales
- ✨ Documentación completa
- ✨ Constantes de configuración centralizadas

#### Changed
- 🔄 Reorganización completa del código
- 🔄 Actualización de headers del plugin
- 🔄 Mejora en el sistema de carga de módulos

#### Removed
- ❌ Archivos obsoletos eliminados
- ❌ Código redundante removido

### [1.0.0] - 2024-11-16
- 🎉 Versión inicial

---

## 📄 Licencia

Este proyecto está licenciado bajo GPL v2 o posterior - ver el archivo [LICENSE](LICENSE) para más detalles.

---

## 👥 Autores

**AgroChamba Team**
- Website: [https://agrochamba.com](https://agrochamba.com)
- Email: contacto@agrochamba.com

---

## 🙏 Agradecimientos

- WordPress Community
- JWT Authentication for WP REST API plugin
- Todos los contribuidores del proyecto

---

## 📞 Soporte

¿Necesitas ayuda? Contáctanos:

- 📧 Email: soporte@agrochamba.com
- 💬 Issues: [GitHub Issues](https://github.com/agrochamba/agrochamba-core/issues)
- 📖 Docs: [Documentación Completa](docs/)

---

<p align="center">
  Hecho con ❤️ para el sector agrícola del Perú 🇵🇪
</p>
