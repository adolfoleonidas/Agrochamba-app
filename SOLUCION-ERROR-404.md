# Solución: Error 404 al Registrar Usuario

## ⚠️ IMPORTANTE: Verificar Wordfence

**Si tienes Wordfence instalado**, es muy probable que esté bloqueando las peticiones de la app. 

Ver: `CONFIGURAR-WORDFENCE.md` para instrucciones detalladas.

## 🔍 Diagnóstico

Si recibes un error 404 al intentar registrarte, puede ser por:
1. **Wordfence bloqueando las peticiones** (más común)
2. El endpoint `/wp-json/agrochamba/v1/register-user` no está disponible
3. El plugin no está activo

## ✅ Pasos para Verificar y Solucionar

### 1. Verificar que el Plugin esté Activo

1. Ve a tu panel de administración de WordPress
2. Ve a **Plugins** → **Plugins Instalados**
3. Busca **"AgroChamba Core"**
4. Asegúrate de que esté **Activado** (debe decir "Desactivar" en lugar de "Activar")

### 2. Verificar que los Endpoints estén Registrados

Abre tu navegador y visita:
```
https://agrochamba.com/wp-json/agrochamba/v1/
```

Deberías ver una lista de todos los endpoints disponibles. Busca:
- `/agrochamba/v1/register-user`
- `/agrochamba/v1/register-company`

Si no aparecen, el plugin no está cargando correctamente.

### 3. Actualizar Permalinks (Rewrite Rules)

1. Ve a **Configuración** → **Enlaces permanentes**
2. **No cambies nada**, solo haz clic en **"Guardar cambios"**
3. Esto actualiza las reglas de reescritura de WordPress

### 4. Verificar Logs de WordPress

Si tienes acceso a los logs de WordPress, busca errores relacionados con:
- `agrochamba`
- `register-user`
- `rest_api_init`

### 5. Verificar que el Plugin se Cargue Correctamente

Asegúrate de que:
- El plugin esté en: `/wp-content/plugins/agrochamba-core/`
- El archivo principal sea: `agrochamba-core.php`
- La carpeta `modules/` exista y tenga los 9 archivos PHP

### 6. Probar el Endpoint Directamente

Puedes probar el endpoint con curl o Postman:

```bash
curl -X POST https://agrochamba.com/wp-json/agrochamba/v1/register-user \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "password": "testpass123"
  }'
```

Si esto también da 404, el problema está en el servidor/plugin.
Si funciona, el problema está en la app Android.

## 🔧 Soluciones Comunes

### Solución 1: Reactivar el Plugin
1. Desactiva el plugin
2. Actívalo nuevamente
3. Ve a **Configuración** → **Enlaces permanentes** → **Guardar**

### Solución 2: Verificar Versión de WordPress
El plugin requiere WordPress 5.0 o superior. Verifica tu versión en **Escritorio** → **Actualizaciones**.

### Solución 3: Verificar Permisos de Archivos
Asegúrate de que los archivos del plugin tengan los permisos correctos (644 para archivos, 755 para directorios).

### Solución 4: Desactivar Otros Plugins
A veces otros plugins pueden causar conflictos. Prueba desactivando otros plugins temporalmente.

## 📱 Verificar desde la App

La app ahora muestra mensajes de error más descriptivos. Si ves:
- "Error 404: El endpoint no está disponible" → El plugin no está activo o no se cargó
- "El nombre de usuario ya está en uso" → El usuario existe, intenta con otro
- "El email ya está registrado" → El email existe, intenta con otro

## 📝 Logs en Android Studio

Revisa los logs en Android Studio (Logcat) buscando:
- `RegisterViewModel` - Verás los intentos de registro y errores
- `CompanyRegisterViewModel` - Para registro de empresas

Los logs mostrarán:
- La URL que se está intentando
- El código de error HTTP
- El cuerpo de la respuesta de error

## 🆘 Si Nada Funciona

1. Verifica que el plugin esté en la versión correcta
2. Revisa los logs del servidor WordPress
3. Verifica que no haya errores de PHP en el plugin
4. Asegúrate de que la URL base en la app sea correcta: `https://agrochamba.com/wp-json/`

