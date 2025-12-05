=== AgroChamba Core ===
Contributors: agrochamba
Tags: jobs, agriculture, api, rest, custom-post-type, mobile-app
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sistema completo de gestión de trabajos agrícolas con API REST personalizada para WordPress y aplicación móvil Android.

== Description ==

AgroChamba Core es un plugin completo y optimizado que proporciona:

**Funcionalidades Principales:**
* Custom Post Type para trabajos agrícolas con editor mejorado estilo Computrabajo
* Taxonomías personalizadas (ubicación, empresa, cultivo, tipo de puesto)
* Sistema de moderación de trabajos (aprobación/rechazo por administradores)
* API REST personalizada con endpoints para:
  * Autenticación y registro de usuarios y empresas (con JWT)
  * Gestión de perfiles de usuario y empresa
  * Creación y gestión de trabajos con galería de imágenes
  * Sistema de favoritos y trabajos guardados
  * Gestión avanzada de imágenes con múltiples tamaños optimizados
  * Paginación en todos los endpoints de listados
* Integración con Facebook para publicación automática
* Sistema de recuperación de contraseña

**Optimizaciones y Rendimiento:**
* Sistema de caché inteligente para taxonomías, listados y perfiles
* Optimización automática de imágenes con compresión
* Tamaños de imagen personalizados para móviles (card, detail, thumb, profile)
* Regeneración de thumbnails para imágenes existentes
* Rate limiting para proteger la API
* CORS configurado para aplicaciones móviles

**Seguridad:**
* Validación de tamaño de archivos (configurable, por defecto 5MB)
* Rate limiting por usuario/IP
* CORS configurado para orígenes específicos
* Validación robusta de datos de entrada

**Editor de WordPress:**
* Meta boxes mejorados con diseño moderno
* Campos para beneficios (alojamiento, transporte, alimentación)
* Campo para URL de Google Maps o dirección
* Sincronización automática de imágenes del editor con gallery_ids
* Mantenimiento del orden de imágenes

== Installation ==

1. Sube la carpeta 'agrochamba-core' a '/wp-content/plugins/'
2. Activa el plugin desde el menú 'Plugins' en WordPress
3. Ve a Configuración → Enlaces permanentes y guarda (sin cambiar nada)
4. (Opcional) Ve a Herramientas → Regenerar Thumbnails para generar tamaños optimizados de imágenes existentes
5. ¡Listo! El plugin está funcionando

== Frequently Asked Questions ==

= ¿Necesito instalar algo más? =

Solo necesitas el plugin "JWT Authentication for WP REST API" si quieres usar autenticación JWT. El plugin funciona sin él, pero la autenticación será limitada.

= ¿Cómo configuro Facebook? =

Ve a Configuración → Facebook Integration en el panel de WordPress y configura tu Page Access Token y Page ID.

= ¿Los endpoints funcionan inmediatamente? =

Sí, todos los endpoints están disponibles en /wp-json/agrochamba/v1/ inmediatamente después de activar el plugin.

= ¿Cómo regenero los thumbnails de imágenes existentes? =

Ve a Herramientas → Regenerar Thumbnails en el panel de WordPress. Esto generará los nuevos tamaños optimizados (agrochamba_card, agrochamba_detail, etc.) para todas tus imágenes.

= ¿Puedo cambiar el tamaño máximo de archivos? =

Sí, puedes usar el filtro 'agrochamba_max_upload_size' en tu tema o plugin personalizado para cambiar el límite (por defecto 5MB).

= ¿Cómo funciona el sistema de caché? =

El plugin cachea automáticamente:
- Taxonomías (1 hora)
- Listados de trabajos públicos (5 minutos)
- Perfiles de empresa (30 minutos)

El caché se invalida automáticamente cuando se crean/actualizan/eliminan trabajos, términos o perfiles.

== Changelog ==

= 1.0.0 =
* Versión inicial completa
* Sistema completo de gestión de trabajos
* API REST personalizada con paginación
* Integración con Facebook
* Sistema de moderación de trabajos
* Sistema de caché inteligente
* Optimización de imágenes con tamaños personalizados
* Compresión automática de imágenes
* Regeneración de thumbnails
* Rate limiting y seguridad mejorada
* Editor de WordPress mejorado estilo Computrabajo
* Sincronización automática de gallery_ids
* Validación robusta de datos
* CORS configurado para aplicaciones móviles

== Upgrade Notice ==

= 1.0.0 =
Versión inicial completa del plugin con todas las funcionalidades y optimizaciones.

