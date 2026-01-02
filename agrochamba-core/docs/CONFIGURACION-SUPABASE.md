# ⚙️ Configuración de Supabase para Agrochamba

## 📋 Requisitos Previos

1. Tener un proyecto creado en [Supabase](https://supabase.com)
2. Tener acceso al Dashboard de Supabase
3. Tener permisos de administrador en WordPress

---

## 🔧 Configuración en WordPress

### Opción 1: Usando wp-config.php (Recomendado)

Agrega estas líneas en tu archivo `wp-config.php`:

```php
// Configuración Supabase para Agrochamba
define('AGROCHAMBA_SUPABASE_URL', 'https://tu-proyecto.supabase.co');
define('AGROCHAMBA_SUPABASE_ANON_KEY', 'tu-anon-key-aqui');
```

**Ubicación:** Antes de la línea `/* That's all, stop editing! Happy publishing. */`

**Nota:** Las constantes en `wp-config.php` tienen prioridad sobre la configuración del admin.

### Opción 2: Usando Panel de Administración (Más Fácil)

1. Ve a **WordPress Admin → Configuración → Supabase**
2. Ingresa tu **Supabase Project URL**
3. Ingresa tu **Supabase Anon Key**
4. Haz clic en **Guardar configuración**

**Ventaja:** No necesitas editar archivos del servidor.

### Opción 3: Usando Options API (Programáticamente)

Si prefieres configurarlo desde el código:

```php
update_option('agrochamba_supabase_url', 'https://tu-proyecto.supabase.co');
update_option('agrochamba_supabase_anon_key', 'tu-anon-key-aqui');
```

---

## 🔑 Obtener Credenciales de Supabase

1. **Ve a tu proyecto en Supabase Dashboard**
2. **Settings → API**
3. **Copia los siguientes valores:**
   - **Project URL** → Usa este valor para `AGROCHAMBA_SUPABASE_URL`
   - **anon/public key** → Usa este valor para `AGROCHAMBA_SUPABASE_ANON_KEY`

**⚠️ IMPORTANTE:** Usa la clave `anon/public`, NO la `service_role` (es más segura y tiene las restricciones correctas).

---

## 📝 Configurar Webhook (Opcional pero Recomendado)

Para sincronización automática cuando se crea un usuario en Supabase:

1. **Ve a Supabase Dashboard → Database → Webhooks**
2. **Crea un nuevo webhook:**
   - **Name:** `sync-user-to-wordpress`
   - **Table:** `auth.users`
   - **Events:** `INSERT` (cuando se crea un usuario)
   - **HTTP Request:**
     - **URL:** `https://agrochamba.com/wp-json/agrochamba/v1/sync/user`
     - **Method:** `POST`
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

**Nota:** El webhook es opcional porque la app web puede llamar directamente al endpoint `/sync/user` después del registro.

---

## ✅ Verificar Configuración

### 1. Verificar que el módulo se cargó

Revisa los logs de WordPress (`wp-content/debug.log` si `WP_DEBUG` está activo). Deberías ver:

```
AgroChamba: Módulo 23 (Supabase Sync) cargado correctamente
```

### 2. Probar endpoint de validación

```bash
curl -X POST https://agrochamba.com/wp-json/agrochamba/v1/auth/validate \
  -H "Authorization: Bearer TU_TOKEN_SUPABASE_AQUI" \
  -H "Content-Type: application/json"
```

**Respuesta esperada (si token válido):**
```json
{
  "valid": true,
  "user_id": 123,
  "email": "usuario@ejemplo.com",
  "roles": ["subscriber"]
}
```

**Respuesta esperada (si token inválido):**
```json
{
  "code": "invalid_token",
  "message": "Token inválido o expirado",
  "data": {
    "status": 401
  }
}
```

### 3. Probar sincronización de usuario

```bash
curl -X POST https://agrochamba.com/wp-json/agrochamba/v1/sync/user \
  -H "Authorization: Bearer TU_TOKEN_SUPABASE_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "supabase_user_id": "uuid-del-usuario",
    "email": "usuario@ejemplo.com",
    "metadata": {
      "username": "nombre_usuario",
      "role": "subscriber"
    }
  }'
```

---

## 🔒 Seguridad

### Variables de Entorno

- ✅ **NUNCA** commits las credenciales en Git
- ✅ Usa `wp-config.php` o variables de entorno del servidor
- ✅ Rota las claves periódicamente
- ✅ Usa la clave `anon/public`, NO `service_role`

### Rate Limiting

El módulo usa el sistema de rate limiting existente de Agrochamba. Los tokens se cachean por 5 minutos para evitar llamadas excesivas a Supabase.

---

## 🐛 Troubleshooting

### Error: "Supabase URL o Anon Key no configurados"

**Solución:**
1. Verifica que las constantes estén definidas en `wp-config.php`
2. Verifica que los valores sean correctos (sin espacios extra)
3. Limpia la caché de WordPress si usas algún plugin de caché

### Error: "Token inválido o expirado"

**Posibles causas:**
1. Token expirado → El usuario debe hacer login nuevamente
2. Token de otro proyecto Supabase → Verifica que uses el proyecto correcto
3. Clave anon incorrecta → Verifica que copiaste la clave correcta

### Error: "Usuario no encontrado en WordPress"

**Solución:**
1. El usuario debe sincronizarse primero llamando a `/sync/user`
2. Verifica que el webhook esté configurado correctamente
3. Puedes sincronizar manualmente desde la app web

### El middleware no funciona en endpoints existentes

**Solución:**
1. Verifica que el módulo 23 se cargó correctamente
2. Verifica que los endpoints usen `permission_callback` correctamente
3. Revisa los logs de WordPress para errores

---

## 📚 Documentación Relacionada

- [Guía Técnica App Web](./GUIA-TECNICA-APP-WEB-SUPABASE.md)
- [Ejemplos de Código](./CODIGO-EJEMPLOS-APP-WEB.md)
- [API Endpoints](./API-ENDPOINTS.md)

---

## 🆘 Soporte

Si tienes problemas con la configuración:

1. Revisa los logs de WordPress (`wp-content/debug.log`)
2. Verifica la configuración de Supabase en el Dashboard
3. Prueba los endpoints manualmente con `curl` o Postman
4. Consulta la documentación de Supabase: https://supabase.com/docs

---

**Última actualización:** 2025-01-XX

