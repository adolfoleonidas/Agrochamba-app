# 🔑 Valores para Configurar en la App Web

## Valores que necesita el desarrollador

### 1. WORDPRESS_URL

**Valor:**
```
https://agrochamba.com
```

**O si estás en desarrollo local:**
```
http://localhost
```

**Explicación:**
- Es la URL base de tu sitio WordPress
- Sin `/wp-json` al final
- Sin barra final `/`

**Dónde encontrarlo:**
- Es la URL de tu sitio WordPress
- Ejemplo: `https://agrochamba.com`

---

### 2. WORDPRESS_JWT_SECRET

**⚠️ IMPORTANTE:** Con Supabase Auth, este valor **NO es necesario** para la nueva app web porque estamos usando tokens de Supabase, no JWT de WordPress.

**Sin embargo, si el desarrollador lo necesita para compatibilidad o desarrollo:**

**Valor real (de wp-config.php):**
```
miuqzY##yXQYK0_NM_rp=9L{<hI]dUt1yu0N${h-
```

**Dónde encontrarlo:**

1. **En `wp-config.php` de WordPress:**
   ```php
   define('JWT_AUTH_SECRET_KEY', 'miuqzY##yXQYK0_NM_rp=9L{<hI]dUt1yu0N${h-');
   ```

2. **O generar una nueva:**
   - Ve a WordPress Admin
   - Instala el plugin "JWT Authentication for WP REST API"
   - Ve a Settings → JWT Auth
   - Genera una nueva clave secreta

**Nota para el desarrollador:**
- Este valor solo es necesario si van a usar el sistema de autenticación antiguo de WordPress
- Para la nueva app web con Supabase, **NO es necesario**
- Pueden usar el valor real si necesitan compatibilidad con sistema antiguo: `miuqzY##yXQYK0_NM_rp=9L{<hI]dUt1yu0N${h-`

---

## 📋 Valores Completos para la App Web

### Variables de Entorno Necesarias

```env
# WordPress
WORDPRESS_URL=https://agrochamba.com
WORDPRESS_API_URL=https://agrochamba.com/wp-json/agrochamba/v1

# Supabase (OBLIGATORIO para nueva app web)
NEXT_PUBLIC_SUPABASE_URL=https://tu-proyecto.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=tu-anon-key-aqui

# JWT Secret (SOLO si usan sistema antiguo)
WORDPRESS_JWT_SECRET=tu-jwt-secret-aqui
```

---

## 🎯 Valores para Modo Desarrollo/Mock

Si están en modo desarrollo y aún no tienen WordPress configurado:

```env
# WordPress (Mock/Desarrollo)
WORDPRESS_URL=http://localhost:8080
WORDPRESS_API_URL=http://localhost:8080/wp-json/agrochamba/v1
WORDPRESS_JWT_SECRET=dev-mock-jwt-secret-12345

# Supabase (Necesitan crear proyecto primero)
NEXT_PUBLIC_SUPABASE_URL=https://tu-proyecto-dev.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=tu-anon-key-dev
```

---

## ✅ Checklist para el Desarrollador

- [ ] **WORDPRESS_URL**: URL base de WordPress (ej: `https://agrochamba.com`)
- [ ] **WORDPRESS_JWT_SECRET**: Solo si usan sistema antiguo (puede ser mock para desarrollo)
- [ ] **NEXT_PUBLIC_SUPABASE_URL**: URL del proyecto Supabase
- [ ] **NEXT_PUBLIC_SUPABASE_ANON_KEY**: Clave anon de Supabase

---

## 🔍 Cómo Obtener los Valores Reales

### WORDPRESS_URL
1. Abre tu navegador
2. Ve a tu sitio WordPress
3. Copia la URL (sin `/wp-json` ni `/wp-admin`)

### WORDPRESS_JWT_SECRET
1. Ve a WordPress Admin
2. Settings → JWT Auth
3. Copia la clave secreta
4. O revisa `wp-config.php`:
   ```php
   define('JWT_AUTH_SECRET_KEY', 'miuqzY##yXQYK0_NM_rp=9L{<hI]dUt1yu0N${h-');
   ```

**Valor actual configurado:**
```
miuqzY##yXQYK0_NM_rp=9L{<hI]dUt1yu0N${h-
```

### SUPABASE_URL y SUPABASE_ANON_KEY
1. Ve a [Supabase Dashboard](https://app.supabase.com)
2. Selecciona tu proyecto
3. Ve a **Settings → API**
4. Copia:
   - **Project URL** → `NEXT_PUBLIC_SUPABASE_URL`
   - **anon/public key** → `NEXT_PUBLIC_SUPABASE_ANON_KEY`

---

## 📝 Ejemplo Completo de Configuración

### Para Producción:
```env
WORDPRESS_URL=https://agrochamba.com
WORDPRESS_JWT_SECRET=miuqzY##yXQYK0_NM_rp=9L{<hI]dUt1yu0N${h-
NEXT_PUBLIC_SUPABASE_URL=https://abc123.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Para Desarrollo:
```env
WORDPRESS_URL=http://localhost
WORDPRESS_JWT_SECRET=dev-secret-12345
NEXT_PUBLIC_SUPABASE_URL=https://dev-project.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## ⚠️ Notas Importantes

1. **WORDPRESS_JWT_SECRET** solo es necesario si:
   - Van a mantener compatibilidad con el sistema antiguo
   - Están haciendo pruebas con el sistema antiguo
   - Para la nueva app web con Supabase, **NO es necesario**

2. **Para la nueva app web**, los valores críticos son:
   - `WORDPRESS_URL` (para hacer requests a la API)
   - `NEXT_PUBLIC_SUPABASE_URL` (para autenticación)
   - `NEXT_PUBLIC_SUPABASE_ANON_KEY` (para autenticación)

3. **WORDPRESS_JWT_SECRET** puede ser un valor mock/placeholder si solo van a usar Supabase Auth.

---

## 🆘 Si el Desarrollador Tiene Dudas

Dile que:
- **WORDPRESS_URL**: Es simplemente la URL de tu sitio WordPress
- **WORDPRESS_JWT_SECRET**: Puede usar un valor mock como `dev-jwt-secret-12345` si solo van a usar Supabase
- Los valores reales de Supabase los necesita obtener del Dashboard de Supabase

---

**Última actualización:** 2025-01-XX

