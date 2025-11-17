# Configurar Wordfence para Permitir la App Android

## 🔴 Problema

Wordfence está bloqueando las peticiones de la app Android porque:
1. Detecta demasiados intentos de acceso
2. Puede estar bloqueando peticiones REST API
3. Está bloqueando tu IP por 4 horas

## ✅ Solución: Configurar Wordfence

### Paso 1: Desbloquear tu IP Temporalmente

1. Ve a tu panel de WordPress: `https://agrochamba.com/wp-admin/`
2. Ve a **Wordfence** → **Bloqueos**
3. Busca tu IP: `2800:200:fb90:82e:edce:bdd4:a837:b5bb`
4. Haz clic en **"Desbloquear"** o **"Eliminar bloqueo"**

### Paso 2: Permitir Endpoints REST API

1. Ve a **Wordfence** → **Firewall** → **Reglas de Firewall**
2. Busca la sección **"Allowlisted URLs"** o **"URLs Permitidas"**
3. Agrega estas URLs a la lista blanca:

```
/wp-json/agrochamba/v1/register-user
/wp-json/agrochamba/v1/register-company
/wp-json/agrochamba/v1/login
/wp-json/agrochamba/v1/*
/wp-json/jwt-auth/v1/token
```

### Paso 3: Configurar Rate Limiting

1. Ve a **Wordfence** → **Firewall** → **Rate Limiting**
2. Ajusta los límites para **REST API**:
   - **Requests**: Aumenta a 120 por minuto (o más)
   - **404s**: Aumenta a 60 por minuto
   - O desactiva temporalmente el rate limiting para REST API

### Paso 4: Permitir tu IP Permanente (Opcional)

1. Ve a **Wordfence** → **Bloqueos**
2. Haz clic en **"Whitelist"** o **"Lista Blanca"**
3. Agrega tu IP: `2800:200:fb90:82e:edce:bdd4:a837:b5bb`
4. Marca como **"Permanente"**

### Paso 5: Configurar Excepciones para REST API

1. Ve a **Wordfence** → **Firewall** → **Opciones de Firewall**
2. Busca **"Allowlisted Services"** o **"Servicios Permitidos"**
3. Asegúrate de que **"REST API"** esté permitido

### Paso 6: Desactivar Protección de Login Temporalmente (Solo para Probar)

⚠️ **Solo para pruebas**: Si necesitas probar rápidamente, puedes desactivar temporalmente la protección de login:

1. Ve a **Wordfence** → **Login Security**
2. Desactiva **"Enable brute force protection"** temporalmente
3. Prueba la app
4. **Vuelve a activarlo** después

## 🔧 Configuración Recomendada para Apps Móviles

### Opción 1: Permitir todas las peticiones REST API

En **Wordfence** → **Firewall** → **Opciones**:
- Marca **"Allow REST API requests"** o **"Permitir peticiones REST API"**

### Opción 2: Crear una Regla Personalizada

1. Ve a **Wordfence** → **Firewall** → **Reglas Personalizadas**
2. Crea una regla que permita:
   - **URL Pattern**: `/wp-json/agrochamba/v1/*`
   - **Action**: Allow
   - **Bypass Rate Limiting**: Sí

### Opción 3: Usar User-Agent Whitelist

Si la app envía un User-Agent específico, puedes agregarlo a la lista blanca.

## 📱 Verificar desde la App

Después de configurar Wordfence:

1. Espera 5-10 minutos para que los cambios se apliquen
2. Intenta registrar nuevamente desde la app
3. Si sigue fallando, revisa los logs de Wordfence:
   - **Wordfence** → **Tools** → **Live Traffic**
   - Busca peticiones bloqueadas

## 🚨 Si el Problema Persiste

### Desactivar Wordfence Temporalmente (Solo para Diagnóstico)

1. Ve a **Plugins** → **Plugins Instalados**
2. Desactiva **Wordfence** temporalmente
3. Prueba la app
4. Si funciona, el problema es Wordfence
5. **Vuelve a activarlo** y configura correctamente

### Alternativa: Usar Otro Plugin de Seguridad

Si Wordfence causa muchos problemas, considera:
- **iThemes Security** (más flexible con REST API)
- **Sucuri Security** (mejor para apps móviles)
- O configurar reglas de firewall a nivel de servidor

## 📝 Notas Importantes

- **No desactives Wordfence permanentemente** - Es importante para la seguridad
- **Configura correctamente** los permisos para REST API
- **Monitorea los logs** para ver qué está bloqueando
- **Considera usar HTTPS** si no lo estás usando ya

## 🔍 Verificar que Funciona

Después de configurar, prueba estos endpoints directamente:

```bash
# Probar registro
curl -X POST https://agrochamba.com/wp-json/agrochamba/v1/register-user \
  -H "Content-Type: application/json" \
  -d '{"username":"test","email":"test@test.com","password":"test123"}'

# Probar login
curl -X POST https://agrochamba.com/wp-json/agrochamba/v1/login \
  -H "Content-Type: application/json" \
  -d '{"username":"test","password":"test123"}'
```

Si estos funcionan, la app también debería funcionar.

