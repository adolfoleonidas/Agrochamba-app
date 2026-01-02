# 🚀 Prompt para Desarrollador: Nueva App Web Agrochamba

## 📋 Instrucciones de Uso

Este documento contiene un prompt completo que puedes copiar y pegar directamente a:
- Un desarrollador humano
- Una IA de desarrollo (Claude, GPT-4, Cursor, etc.)
- Un equipo de desarrollo

**IMPORTANTE:** Lee primero `GUIA-TECNICA-APP-WEB-SUPABASE.md` para contexto completo.

---

## 🎯 PROMPT COMPLETO

```
Actúa como arquitecto de software senior y desarrollador full-stack experto en:

- Supabase (Auth, Database, Edge Functions)
- WordPress (REST API, Plugins, Custom Post Types)
- Next.js 14+ / React 18+ con TypeScript
- Arquitecturas híbridas y migraciones progresivas
- Sistemas de autenticación unificados (SSO)

---

## 🎯 OBJETIVO PRINCIPAL

Diseñar y construir la nueva aplicación web de Agrochamba que:

✅ Use Supabase Auth como sistema de autenticación único
✅ Se sincronice completamente con el backend WordPress existente
✅ Mantenga compatibilidad total con usuarios, publicaciones y permisos actuales
✅ Permita migración progresiva sin romper el sistema existente
✅ Funcione como evolución del sistema, NO como app paralela

---

## 🧱 CONTEXTO DEL SISTEMA ACTUAL

### Backend WordPress

**Base URL:** https://agrochamba.com/wp-json/agrochamba/v1/

**Estructura:**
- Custom Post Type: `trabajo` (trabajos agrícolas)
- Custom Post Type: `empresa` (perfiles de empresas)
- Taxonomías: `ubicacion`, `empresa`, `tipo_puesto`, `cultivo`
- Roles: `employer` (empresas), `subscriber` (trabajadores), `administrator`

**Endpoints principales existentes:**

```
POST   /agrochamba/v1/register-company    # Registro de empresas
POST   /agrochamba/v1/register-user        # Registro de trabajadores
POST   /agrochamba/v1/login                # Login (devuelve JWT WordPress)
POST   /agrochamba/v1/jobs                 # Crear trabajo (requiere auth)
GET    /agrochamba/v1/me/jobs              # Mis trabajos (requiere auth)
GET    /agrochamba/v1/me/profile           # Mi perfil (requiere auth)
PUT    /agrochamba/v1/me/profile           # Actualizar perfil (requiere auth)
GET    /agrochamba/v1/companies/{id}/profile # Perfil de empresa
GET    /wp/v2/trabajos                     # Listar trabajos (REST API nativa)
GET    /wp/v2/empresa                      # Listar empresas (taxonomía)
```

**Autenticación actual:**
- Plugin JWT Auth (`jwt-auth/v1/token`)
- Tokens JWT almacenados en cliente
- Validación mediante `is_user_logged_in()` en WordPress

**Datos de usuario:**
- `user_meta`: `ruc`, `razon_social`, `phone`, `bio`, `profile_photo_id`, `empresa_term_id`
- Roles WordPress: `employer`, `subscriber`, `administrator`

---

## 🔐 REQUISITOS DE AUTENTICACIÓN

### Regla de Oro

**Supabase Auth será el ÚNICO sistema de login para la nueva app web.**

La app web debe:
- ✅ Registrar usuarios SOLO en Supabase
- ✅ Iniciar sesión SOLO con Supabase
- ✅ Usar tokens JWT de Supabase para todas las peticiones
- ❌ NO usar el sistema de login WordPress directamente

---

## 🔁 SINCRONIZACIÓN SUPABASE ↔ WORDPRESS

### Regla Obligatoria

**Todo usuario creado en Supabase DEBE existir también en WordPress.**

### Comportamiento Esperado

**Al registrarse un usuario en Supabase:**

1. Supabase crea el usuario y genera JWT token
2. Webhook de Supabase (`user.created`) notifica a WordPress
3. WordPress crea automáticamente el usuario correspondiente
4. WordPress guarda `supabase_user_id` en `user_meta`
5. WordPress sincroniza roles y metadata

**Si el usuario ya existe en WordPress:**

1. Buscar por email o `supabase_user_id`
2. Vincular (no duplicar)
3. Actualizar metadata si es necesario

### Implementación Requerida

**Backend WordPress - Nuevo Endpoint:**

```php
POST /agrochamba/v1/sync/user

Headers:
  Authorization: Bearer {supabase_token}
  Content-Type: application/json

Body:
{
  "supabase_user_id": "uuid-del-usuario",
  "email": "usuario@ejemplo.com",
  "metadata": {
    "username": "nombre_usuario",
    "role": "employer",
    "ruc": "12345678901",
    "razon_social": "Empresa S.A.C."
  }
}

Response 200:
{
  "success": true,
  "user_id": 123,
  "email": "usuario@ejemplo.com",
  "roles": ["employer"],
  "created": true
}
```

**Validación de Tokens:**

WordPress debe validar tokens Supabase en cada request protegido:

```php
function validate_supabase_token($auth_header) {
  // 1. Extraer token del header Authorization
  // 2. Validar token con Supabase API: GET {supabase_url}/auth/v1/user
  // 3. Si válido, buscar usuario WordPress vinculado por supabase_user_id
  // 4. Establecer sesión WordPress con wp_set_current_user()
  // 5. Retornar true si válido, false si inválido
}
```

---

## 📝 PUBLICACIONES (JOBS)

### Flujo de Publicación

1. Usuario autenticado en Supabase crea publicación desde app web
2. App web envía request a WordPress con token Supabase:
   ```
   POST /agrochamba/v1/jobs
   Headers: Authorization: Bearer {supabase_token}
   Body: { title, content, ubicacion_id, empresa_id, ... }
   ```
3. WordPress valida token Supabase
4. WordPress identifica usuario vinculado
5. WordPress crea trabajo con autoría correcta
6. WordPress devuelve trabajo creado

### Estructura de Datos

**Crear Trabajo:**
```json
{
  "title": "Cosecha de Café",
  "content": "Descripción detallada...",
  "ubicacion_id": 5,
  "empresa_id": 8,
  "salario_min": 50,
  "salario_max": 80,
  "vacantes": 10,
  "comentarios_habilitados": true
}
```

**Response:**
```json
{
  "id": 321,
  "title": "Cosecha de Café",
  "status": "pending",
  "author": 123,
  "date": "2025-01-15T10:00:00",
  "link": "https://agrochamba.com/trabajos/cosecha-de-cafe"
}
```

---

## 🏛️ ESTRUCTURA DE LA APP WEB

### Stack Tecnológico Requerido

**Frontend:**
- Next.js 14+ (App Router) O React 18+ con Vite
- TypeScript (obligatorio)
- Tailwind CSS o Chakra UI

**Estado y Datos:**
- React Query (TanStack Query) para cache
- Zustand o Jotai para estado global
- Supabase JS Client (@supabase/supabase-js)

**Formularios:**
- React Hook Form + Zod

**Routing:**
- Next.js Router (si Next.js) o React Router (si React)

### Estructura de Carpetas Requerida

```
app-web/
├── src/
│   ├── app/                    # Next.js App Router
│   │   ├── (auth)/             # Rutas de autenticación
│   │   │   ├── login/
│   │   │   ├── register/
│   │   │   └── layout.tsx
│   │   ├── (dashboard)/        # Rutas protegidas
│   │   │   ├── jobs/
│   │   │   │   ├── new/
│   │   │   │   └── [id]/
│   │   │   ├── profile/
│   │   │   └── layout.tsx
│   │   └── layout.tsx
│   │
│   ├── components/
│   │   ├── auth/
│   │   │   ├── LoginForm.tsx
│   │   │   ├── RegisterForm.tsx
│   │   │   └── AuthGuard.tsx
│   │   ├── jobs/
│   │   │   ├── JobCard.tsx
│   │   │   ├── JobForm.tsx
│   │   │   └── JobList.tsx
│   │   └── ui/
│   │
│   ├── lib/
│   │   ├── supabase.ts         # Cliente Supabase
│   │   ├── wordpress.ts        # Cliente WordPress API
│   │   └── auth.ts             # Helpers de auth
│   │
│   ├── hooks/
│   │   ├── useAuth.ts
│   │   ├── useJobs.ts
│   │   └── useSync.ts
│   │
│   ├── store/                  # Zustand stores
│   │   └── authStore.ts
│   │
│   └── types/
│       ├── user.ts
│       └── job.ts
│
├── .env.local
├── package.json
└── tsconfig.json
```

---

## 🔒 CONSIDERACIONES DE SEGURIDAD

1. **Validación de Tokens:**
   - Validar tokens Supabase en cada request protegido
   - Cachear validaciones (5-10 min) para performance
   - Verificar expiración de tokens

2. **Rate Limiting:**
   - Implementar límites por IP/usuario
   - Usar middleware de rate limiting

3. **CORS:**
   - Configurar CORS en WordPress para dominio de la app
   - Solo permitir métodos necesarios

4. **Sanitización:**
   - Sanitizar todos los inputs en WordPress
   - Validar tipos de datos en frontend (Zod)

5. **HTTPS:**
   - Usar HTTPS en producción (obligatorio)

---

## 📋 ENTREGABLES ESPERADOS

### 1. Arquitectura General

- [ ] Diagrama de arquitectura (texto o imagen)
- [ ] Flujo de autenticación completo
- [ ] Flujo de sincronización de usuarios
- [ ] Flujo de publicaciones

### 2. Backend WordPress

- [ ] Endpoint `/sync/user` implementado
- [ ] Endpoint `/auth/validate` implementado
- [ ] Función `validate_supabase_token()` implementada
- [ ] Middleware aplicado a endpoints existentes
- [ ] Webhooks configurados (si aplica)

### 3. Frontend App Web

- [ ] Proyecto inicializado (Next.js/React)
- [ ] Supabase client configurado
- [ ] WordPress API client configurado
- [ ] Componentes de autenticación (Login/Register)
- [ ] Hook `useAuth` implementado
- [ ] Hook `useSync` implementado
- [ ] Páginas principales (Dashboard, Jobs, Profile)
- [ ] Formulario de creación de trabajos
- [ ] Manejo de errores y loading states
- [ ] Responsive design

### 4. Documentación

- [ ] README con instrucciones de setup
- [ ] Documentación de API endpoints
- [ ] Guía de deployment
- [ ] Variables de entorno documentadas

### 5. Testing

- [ ] Testing de registro/login
- [ ] Testing de sincronización
- [ ] Testing de creación de trabajos
- [ ] Testing de permisos y roles

---

## 🚦 REGLAS NO NEGOCIABLES

1. ❌ **NO crear nuevos sistemas de login** - Solo Supabase
2. ✅ **Supabase es el login único** para la nueva app web
3. ✅ **WordPress sigue vivo** como backend de contenido
4. ❌ **NO migraciones masivas** de golpe
5. ✅ **UX del usuario es prioridad**
6. ✅ **Arquitectura flexible y evolutiva**
7. ✅ **Compatibilidad total** con sistema existente

---

## 📚 REFERENCIAS TÉCNICAS

### Documentación Supabase

- Auth: https://supabase.com/docs/guides/auth
- JS Client: https://supabase.com/docs/reference/javascript/auth-signup
- Webhooks: https://supabase.com/docs/guides/database/webhooks

### Documentación WordPress

- REST API: https://developer.wordpress.org/rest-api/
- User Meta: https://developer.wordpress.org/reference/functions/update_user_meta/
- Custom Post Types: https://developer.wordpress.org/reference/functions/register_post_type/

### Documentación Next.js

- App Router: https://nextjs.org/docs/app
- API Routes: https://nextjs.org/docs/app/building-your-application/routing/route-handlers

---

## 🎯 RESULTADO ESPERADO

Una aplicación web moderna de Agrochamba que:

✅ Permite registro/login solo con Supabase
✅ Sincroniza automáticamente usuarios con WordPress
✅ Permite crear y gestionar trabajos
✅ Mantiene compatibilidad total con sistema existente
✅ Está preparada para crecer con nuevos servicios

---

## ❓ PREGUNTAS FRECUENTES

**P: ¿Debo eliminar el sistema de login WordPress actual?**
R: NO. El sistema actual debe seguir funcionando durante la migración. Solo la nueva app web usará Supabase.

**P: ¿Qué pasa con los usuarios existentes?**
R: Los usuarios existentes pueden seguir usando el sistema actual. Se implementará migración lazy (al primer login) o batch (script de migración).

**P: ¿Puedo usar otro framework además de Next.js?**
R: Sí, pero Next.js es recomendado por su integración con Supabase y facilidad de deployment.

**P: ¿Debo crear base de datos nueva?**
R: NO. WordPress sigue siendo la base de datos principal. Solo la autenticación pasa por Supabase.

**P: ¿Cómo manejo los roles de usuario?**
R: Los roles se mantienen en WordPress. Supabase solo almacena metadata básica. WordPress es el source of truth para permisos.

---

## 🚀 COMENZAR

1. Lee `GUIA-TECNICA-APP-WEB-SUPABASE.md` para detalles completos
2. Configura proyecto Supabase
3. Implementa endpoints de sincronización en WordPress
4. Crea estructura base de la app web
5. Implementa autenticación con Supabase
6. Implementa sincronización con WordPress
7. Crea UI y funcionalidades principales
8. Testing completo
9. Deploy a staging
10. Migración progresiva

---

**¿Listo para comenzar?** 🎉

Empieza por:
1. Crear proyecto Supabase
2. Implementar endpoint `/sync/user` en WordPress
3. Crear estructura base de la app web
```

---

## 📝 Notas Adicionales

Este prompt está diseñado para ser usado directamente. Puedes:

1. **Copiarlo completo** y enviarlo a un desarrollador
2. **Adaptarlo** según necesidades específicas
3. **Dividirlo** en tareas más pequeñas
4. **Usarlo con IA** (Claude, GPT-4, Cursor, etc.)

Para más detalles técnicos, consulta `GUIA-TECNICA-APP-WEB-SUPABASE.md`.

