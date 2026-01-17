# 🚀 Guía Paso a Paso: Implementación de Supabase Auth en Agrochamba

## 📋 Decisión: ¿Plugin Separado o Integrado?

### ✅ Recomendación: Mantener Integrado

**Razones:**
1. ✅ Ya está implementado en el módulo `23-supabase-sync.php`
2. ✅ Es parte del ecosistema Agrochamba (no tiene sentido separarlo)
3. ✅ Más simple de mantener (todo en un solo lugar)
4. ✅ Se carga automáticamente con el plugin principal
5. ✅ No necesita activarse/desactivarse independientemente

**Si fuera un plugin separado:**
- ❌ Más complejidad de instalación
- ❌ Dependencia entre plugins
- ❌ Más archivos que mantener
- ❌ No aporta beneficios reales

**Conclusión:** Mantenerlo integrado es la mejor opción.

---

## 📝 Paso a Paso Completo

### FASE 1: Preparación (15 minutos)

#### Paso 1.1: Verificar que el módulo existe

**Ubicación:** `agrochamba-core/modules/23-supabase-sync.php`

**Verificación:**
- [ ] El archivo existe
- [ ] Está en la lista de módulos en `agrochamba-core.php` (línea ~69)

**Si no existe:**
- Ya está creado, solo verifica que esté presente

#### Paso 1.2: Verificar carga del módulo

**Archivo:** `agrochamba-core/agrochamba-core.php`

**Verificar línea ~69:**
```php
'23-supabase-sync.php',            // Sincronización Supabase ↔ WordPress
```

**Si falta:**
- Ya está agregado, solo verifica

---

### FASE 2: Configurar Supabase (30 minutos)

#### Paso 2.1: Crear proyecto en Supabase

1. Ve a [https://supabase.com](https://supabase.com)
2. Crea una cuenta o inicia sesión
3. Haz clic en **"New Project"**
4. Completa:
   - **Name:** `agrochamba` (o el nombre que prefieras)
   - **Database Password:** (guarda esta contraseña)
   - **Region:** Elige la más cercana (ej: South America)
5. Haz clic en **"Create new project"**
6. Espera 2-3 minutos a que se cree el proyecto

#### Paso 2.2: Obtener credenciales

1. En el Dashboard de Supabase, ve a **Settings → API**
2. Copia estos valores:
   - **Project URL:** `https://xxxxx.supabase.co`
   - **anon/public key:** `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...`

**⚠️ IMPORTANTE:** Usa la clave `anon/public`, NO la `service_role`

#### Paso 2.3: Configurar Auth en Supabase

1. Ve a **Authentication → Providers**
2. Asegúrate de que **Email** esté habilitado
3. (Opcional) Configura otros proveedores si los necesitas (Google, Facebook, etc.)

---

### FASE 3: Configurar WordPress (20 minutos)

#### Paso 3.1: Opción A - Configurar desde Admin (Recomendado)

1. Ve a **WordPress Admin → Configuración → Supabase**
2. Ingresa:
   - **Supabase Project URL:** `https://xxxxx.supabase.co`
   - **Supabase Anon Key:** `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...`
3. Haz clic en **"Guardar configuración"**
4. Verifica que aparezca: "✓ Configuración de Supabase completa"

#### Paso 3.2: Opción B - Configurar en wp-config.php (Alternativa)

Si prefieres usar constantes en `wp-config.php`:

1. Abre `wp-config.php` en tu servidor
2. Agrega ANTES de `/* That's all, stop editing! Happy publishing. */`:

```php
// Configuración Supabase para Agrochamba
define('AGROCHAMBA_SUPABASE_URL', 'https://xxxxx.supabase.co');
define('AGROCHAMBA_SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...');
```

3. Guarda el archivo

**Nota:** Las constantes en `wp-config.php` tienen prioridad sobre la configuración del admin.

#### Paso 3.3: Verificar que el módulo se cargó

1. Activa el modo debug en WordPress (si no está activo):
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. Revisa `wp-content/debug.log`
3. Busca esta línea:
   ```
   AgroChamba: Módulo 23 (Supabase Sync) cargado correctamente
   ```

**Si no aparece:**
- Verifica que el archivo `23-supabase-sync.php` existe
- Verifica que está en la lista de módulos en `agrochamba-core.php`
- Desactiva y reactiva el plugin

---

### FASE 4: Configurar Webhook (Opcional - 15 minutos)

**Nota:** El webhook es opcional porque la app web puede sincronizar manualmente. Pero es recomendable para automatización.

#### Paso 4.1: Crear webhook en Supabase

1. Ve a **Supabase Dashboard → Database → Webhooks**
2. Haz clic en **"Create a new webhook"**
3. Configura:
   - **Name:** `sync-user-to-wordpress`
   - **Table:** `auth.users`
   - **Events:** Marca solo **INSERT** (cuando se crea un usuario)
   - **Type:** `HTTP Request`
   - **Method:** `POST`
   - **URL:** `https://agrochamba.com/wp-json/agrochamba/v1/sync/user`
   - **Headers:**
     ```
     Content-Type: application/json
     ```
   - **Body (JSON):**
     ```json
     {
       "supabase_user_id": "{{ record.id }}",
       "email": "{{ record.email }}",
       "metadata": {{ record.raw_user_meta_data }}
     }
     ```

4. Haz clic en **"Save"**

#### Paso 4.2: Probar webhook

1. Crea un usuario de prueba en Supabase (desde la app web o dashboard)
2. Verifica que se creó automáticamente en WordPress:
   - Ve a **WordPress Admin → Usuarios**
   - Busca el usuario por email
   - Verifica que existe el meta `supabase_user_id`

---

### FASE 5: Probar Endpoints (20 minutos)

#### Paso 5.1: Obtener token de Supabase

**Opción A: Desde la app web (cuando esté lista)**
- Login normal con Supabase

**Opción B: Desde Supabase Dashboard (para pruebas)**
1. Ve a **Authentication → Users**
2. Crea un usuario de prueba
3. Obtén el token usando la API de Supabase (más complejo)

**Opción C: Usar curl con credenciales**
```bash
curl -X POST 'https://xxxxx.supabase.co/auth/v1/token?grant_type=password' \
  -H "apikey: TU_ANON_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@ejemplo.com",
    "password": "password123"
  }'
```

#### Paso 5.2: Probar endpoint de validación

```bash
curl -X POST https://agrochamba.com/wp-json/agrochamba/v1/auth/validate \
  -H "Authorization: Bearer TU_TOKEN_SUPABASE" \
  -H "Content-Type: application/json"
```

**Respuesta esperada (éxito):**
```json
{
  "valid": true,
  "user_id": 123,
  "email": "test@ejemplo.com",
  "roles": ["subscriber"]
}
```

**Respuesta esperada (error):**
```json
{
  "code": "invalid_token",
  "message": "Token inválido o expirado",
  "data": {
    "status": 401
  }
}
```

#### Paso 5.3: Probar sincronización de usuario

```bash
curl -X POST https://agrochamba.com/wp-json/agrochamba/v1/sync/user \
  -H "Authorization: Bearer TU_TOKEN_SUPABASE" \
  -H "Content-Type: application/json" \
  -d '{
    "supabase_user_id": "uuid-del-usuario",
    "email": "test@ejemplo.com",
    "metadata": {
      "username": "testuser",
      "role": "subscriber"
    }
  }'
```

**Respuesta esperada:**
```json
{
  "success": true,
  "user_id": 123,
  "email": "test@ejemplo.com",
  "username": "testuser",
  "display_name": "testuser",
  "roles": ["subscriber"],
  "created": true
}
```

#### Paso 5.4: Probar endpoint protegido (crear trabajo)

```bash
curl -X POST https://agrochamba.com/wp-json/agrochamba/v1/jobs \
  -H "Authorization: Bearer TU_TOKEN_SUPABASE" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Trabajo de Prueba",
    "content": "Descripción del trabajo",
    "ubicacion_id": 1
  }'
```

**Respuesta esperada:**
```json
{
  "id": 456,
  "title": "Trabajo de Prueba",
  "status": "pending",
  "author": 123,
  ...
}
```

---

### FASE 6: Verificar Funcionamiento (15 minutos)

#### Paso 6.1: Verificar sincronización automática

1. Crea un usuario nuevo desde la app web (cuando esté lista) o desde Supabase Dashboard
2. Verifica en WordPress:
   - Ve a **WordPress Admin → Usuarios**
   - Busca el usuario por email
   - Abre el usuario
   - Ve a la sección "Información Personal"
   - Verifica que existe el campo `supabase_user_id` en los meta

#### Paso 6.2: Verificar que endpoints aceptan tokens Supabase

1. Haz login desde la app web con Supabase
2. Intenta crear un trabajo
3. Verifica que funciona correctamente

#### Paso 6.3: Verificar compatibilidad con sistema actual

1. Haz login con el sistema antiguo de WordPress (si existe)
2. Verifica que sigue funcionando
3. Los dos sistemas deben coexistir sin problemas

---

## ✅ Checklist Final

### Configuración Supabase
- [ ] Proyecto creado en Supabase
- [ ] Auth habilitado (Email/Password)
- [ ] Credenciales obtenidas (URL y Anon Key)
- [ ] Webhook configurado (opcional)

### Configuración WordPress
- [ ] Módulo `23-supabase-sync.php` existe y está cargado
- [ ] Configuración guardada (Admin o wp-config.php)
- [ ] Log muestra que el módulo se cargó correctamente

### Pruebas
- [ ] Endpoint `/auth/validate` funciona
- [ ] Endpoint `/sync/user` funciona
- [ ] Endpoint protegido (ej: `/jobs`) acepta tokens Supabase
- [ ] Usuarios se sincronizan automáticamente
- [ ] Sistema antiguo sigue funcionando

---

## 🐛 Troubleshooting

### Error: "Supabase URL o Anon Key no configurados"

**Solución:**
1. Verifica que guardaste la configuración en Admin o wp-config.php
2. Limpia la caché de WordPress si usas algún plugin de caché
3. Verifica que los valores no tengan espacios extra

### Error: "Token inválido o expirado"

**Solución:**
1. Verifica que el token sea de Supabase (no de WordPress)
2. Verifica que el proyecto Supabase sea el correcto
3. Verifica que la Anon Key sea correcta
4. El token puede haber expirado (haz login nuevamente)

### Error: "Usuario no encontrado en WordPress"

**Solución:**
1. El usuario debe sincronizarse primero llamando a `/sync/user`
2. Verifica que el webhook esté configurado correctamente
3. Puedes sincronizar manualmente desde la app web

### El middleware no funciona

**Solución:**
1. Verifica que el módulo se cargó (revisa debug.log)
2. Verifica que el hook `determine_current_user` está activo
3. Verifica que los endpoints usan `permission_callback` correctamente
4. Revisa los logs de WordPress para errores

---

## 📚 Documentación Relacionada

- [Guía Técnica Completa](./GUIA-TECNICA-APP-WEB-SUPABASE.md)
- [Configuración de Supabase](./CONFIGURACION-SUPABASE.md)
- [Valores para Desarrollador](./VALORES-PARA-DESARROLLADOR.md)
- [Ejemplos de Código](./CODIGO-EJEMPLOS-APP-WEB.md)

---

## 🎯 Próximos Pasos

Una vez completados estos pasos:

1. **App Web:** El desarrollador puede empezar a construir la app web
2. **Testing:** Prueba todos los flujos (registro, login, creación de trabajos)
3. **Migración:** Planifica la migración progresiva de usuarios
4. **Monitoreo:** Revisa logs y errores durante las primeras semanas

---

**Última actualización:** 2025-01-XX

