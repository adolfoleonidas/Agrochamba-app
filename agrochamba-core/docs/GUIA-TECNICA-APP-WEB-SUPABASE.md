# 📘 Guía Técnica: Nueva App Web de Agrochamba con Supabase Auth

## 🎯 Objetivo Principal

Crear una aplicación web moderna de Agrochamba que:
- ✅ Use **Supabase Auth** como sistema de autenticación único
- ✅ Se sincronice completamente con el backend WordPress existente
- ✅ Mantenga compatibilidad total con usuarios, publicaciones y permisos actuales
- ✅ Permita migración progresiva sin romper el sistema existente
- ✅ Funcione como evolución del sistema, no como app paralela

---

## 📋 Tabla de Contenidos

1. [Contexto del Sistema Actual](#contexto-del-sistema-actual)
2. [Arquitectura General](#arquitectura-general)
3. [Flujos Detallados](#flujos-detallados)
4. [Endpoints Necesarios](#endpoints-necesarios)
5. [Estructura de la App Web](#estructura-de-la-app-web)
6. [Implementación Técnica](#implementación-técnica)
7. [Consideraciones de Seguridad](#consideraciones-de-seguridad)
8. [Plan de Migración](#plan-de-migración)
9. [Checklist de Implementación](#checklist-de-implementación)

---

## 🧱 Contexto del Sistema Actual

### Backend WordPress

**Base URL:** `https://agrochamba.com/wp-json/agrochamba/v1/`

**Estructura actual:**
- **Custom Post Type:** `trabajo` (trabajos agrícolas)
- **Custom Post Type:** `empresa` (perfiles de empresas)
- **Taxonomías:** `ubicacion`, `empresa`, `tipo_puesto`, `cultivo`
- **Roles:** `employer` (empresas), `subscriber` (trabajadores), `administrator`

**Endpoints principales existentes:**

```
POST   /agrochamba/v1/register-company    # Registro de empresas
POST   /agrochamba/v1/register-user        # Registro de trabajadores
POST   /agrochamba/v1/login                # Login (devuelve JWT)
POST   /agrochamba/v1/jobs                 # Crear trabajo
GET    /agrochamba/v1/me/jobs              # Mis trabajos
GET    /agrochamba/v1/me/profile            # Mi perfil
PUT    /agrochamba/v1/me/profile            # Actualizar perfil
GET    /agrochamba/v1/companies/{id}/profile # Perfil de empresa
GET    /wp/v2/trabajos                     # Listar trabajos (REST API nativa)
GET    /wp/v2/empresa                      # Listar empresas (taxonomía)
```

**Autenticación actual:**
- Plugin JWT Auth (`jwt-auth/v1/token`)
- Tokens JWT almacenados en cliente
- Validación mediante `is_user_logged_in()` en WordPress

**Datos de usuario almacenados:**
- `user_meta`: `ruc`, `razon_social`, `phone`, `bio`, `profile_photo_id`, `empresa_term_id`
- Roles WordPress: `employer`, `subscriber`, `administrator`

---

## 🏗️ Arquitectura General

### Diagrama de Arquitectura

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENTE (App Web)                        │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  React/Next.js/Vue.js + Supabase Client SDK              │  │
│  │  - Autenticación: Supabase Auth                          │  │
│  │  - Estado: React Query / Zustand                        │  │
│  │  - UI: Componentes modernos                              │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ Token JWT (Supabase)
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      SUPABASE (Auth Layer)                       │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Supabase Auth                                           │  │
│  │  - Registro / Login                                      │  │
│  │  - JWT Tokens                                             │  │
│  │  - User Management                                        │  │
│  │  - Webhooks (user.created, user.updated)                 │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ Webhook / API Call
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              WORDPRESS (Backend + Content Layer)                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  WordPress REST API                                       │  │
│  │  - Endpoints existentes                                  │  │
│  │  - Middleware de validación Supabase                     │  │
│  │  - Sincronización de usuarios                           │  │
│  │  - Custom Post Types (trabajo, empresa)                 │  │
│  │  - Taxonomías (ubicacion, empresa, tipo_puesto)         │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### Principios de Diseño

1. **Supabase = Source of Truth para Autenticación**
   - Todos los logins pasan por Supabase
   - WordPress valida tokens pero no crea usuarios directamente

2. **WordPress = Source of Truth para Contenido**
   - Todas las publicaciones se almacenan en WordPress
   - Los datos de usuario se sincronizan a WordPress

3. **Sincronización Bidireccional**
   - Usuario creado en Supabase → se crea en WordPress
   - Usuario existente en WordPress → se vincula con Supabase

4. **Migración Progresiva**
   - Sistema actual sigue funcionando
   - Nuevos usuarios van a Supabase
   - Usuarios antiguos migran gradualmente

---

## 🔄 Flujos Detallados

### 1. Flujo de Autenticación (Login)

```
┌─────────┐
│ Usuario │
└────┬────┘
     │ 1. Ingresa email/password
     ▼
┌─────────────────┐
│  App Web        │
│  (Supabase SDK) │
└────┬────────────┘
     │ 2. auth.signInWithPassword()
     ▼
┌─────────────────┐
│  Supabase Auth  │
│  - Valida creds │
│  - Genera JWT   │
└────┬────────────┘
     │ 3. Devuelve { access_token, user }
     ▼
┌─────────────────┐
│  App Web        │
│  - Guarda token │
│  - Sincroniza   │
└────┬────────────┘
     │ 4. Verifica usuario en WordPress
     ▼
┌─────────────────┐
│  WordPress API  │
│  GET /sync/user │
└────┬────────────┘
     │ 5. Devuelve user_id WP vinculado
     ▼
┌─────────────────┐
│  App Web        │
│  - Usuario listo│
└─────────────────┘
```

**Código de ejemplo:**

```typescript
// App Web - Login
import { supabase } from '@/lib/supabase'

async function login(email: string, password: string) {
  // 1. Autenticar con Supabase
  const { data, error } = await supabase.auth.signInWithPassword({
    email,
    password
  })
  
  if (error) throw error
  
  // 2. Sincronizar con WordPress
  const wpUser = await syncUserToWordPress(data.user.id, data.session.access_token)
  
  return {
    supabaseUser: data.user,
    wpUser,
    token: data.session.access_token
  }
}

async function syncUserToWordPress(supabaseUserId: string, token: string) {
  const response = await fetch(`${WORDPRESS_API}/agrochamba/v1/sync/user`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      supabase_user_id: supabaseUserId
    })
  })
  
  return response.json()
}
```

---

### 2. Flujo de Registro

```
┌─────────┐
│ Usuario │
└────┬────┘
     │ 1. Completa formulario
     ▼
┌─────────────────┐
│  App Web        │
│  - Valida datos │
└────┬────────────┘
     │ 2. auth.signUp()
     ▼
┌─────────────────┐
│  Supabase Auth  │
│  - Crea usuario │
│  - Envía email  │
└────┬────────────┘
     │ 3. Webhook: user.created
     ▼
┌─────────────────┐
│  WordPress API  │
│  POST /sync/user │
│  - Crea usuario │
│  - Guarda meta  │
└────┬────────────┘
     │ 4. Devuelve user_id WP
     ▼
┌─────────────────┐
│  App Web        │
│  - Usuario listo│
└─────────────────┘
```

**Código de ejemplo:**

```typescript
// App Web - Registro
async function register(userData: {
  email: string
  password: string
  username: string
  role: 'employer' | 'subscriber'
  // Campos adicionales para empresas
  ruc?: string
  razon_social?: string
}) {
  // 1. Registrar en Supabase
  const { data, error } = await supabase.auth.signUp({
    email: userData.email,
    password: userData.password,
    options: {
      data: {
        username: userData.username,
        role: userData.role,
        ruc: userData.ruc,
        razon_social: userData.razon_social
      }
    }
  })
  
  if (error) throw error
  
  // 2. El webhook de Supabase creará automáticamente el usuario en WordPress
  // Pero podemos hacer una llamada directa también para asegurar sincronización
  
  return {
    supabaseUser: data.user,
    message: 'Usuario registrado. Verifica tu email.'
  }
}
```

---

### 3. Flujo de Sincronización Supabase → WordPress

**Opción A: Webhook (Recomendado)**

```php
// WordPress - Nuevo endpoint para sincronización
// agrochamba-core/modules/23-supabase-sync.php

add_action('rest_api_init', function() {
    register_rest_route('agrochamba/v1', '/sync/user', array(
        'methods' => 'POST',
        'callback' => 'agrochamba_sync_supabase_user',
        'permission_callback' => '__return_true', // Validado por token
    ));
});

function agrochamba_sync_supabase_user($request) {
    // 1. Validar token de Supabase
    $token = $request->get_header('Authorization');
    $supabase_user = validate_supabase_token($token);
    
    if (!$supabase_user) {
        return new WP_Error('invalid_token', 'Token inválido', array('status' => 401));
    }
    
    $supabase_user_id = $supabase_user->id;
    $email = $supabase_user->email;
    $metadata = $supabase_user->user_metadata ?? array();
    
    // 2. Buscar usuario existente por email o supabase_user_id
    $wp_user = get_users(array(
        'meta_key' => 'supabase_user_id',
        'meta_value' => $supabase_user_id,
        'number' => 1
    ));
    
    if (empty($wp_user)) {
        // Buscar por email
        $wp_user = get_user_by('email', $email);
    }
    
    // 3. Si no existe, crear usuario
    if (!$wp_user) {
        $username = $metadata['username'] ?? sanitize_user($email);
        $password = wp_generate_password(20); // Password aleatorio (no se usa)
        
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            return new WP_Error('user_creation_failed', $user_id->get_error_message(), array('status' => 500));
        }
        
        $wp_user = get_user_by('id', $user_id);
    } else {
        $wp_user = is_array($wp_user) ? $wp_user[0] : $wp_user;
    }
    
    // 4. Actualizar metadata
    update_user_meta($wp_user->ID, 'supabase_user_id', $supabase_user_id);
    
    // 5. Sincronizar roles
    $role = $metadata['role'] ?? 'subscriber';
    if ($role === 'employer') {
        $wp_user->set_role('employer');
    } else {
        $wp_user->set_role('subscriber');
    }
    
    // 6. Sincronizar campos adicionales (para empresas)
    if ($role === 'employer') {
        if (!empty($metadata['ruc'])) {
            update_user_meta($wp_user->ID, 'ruc', sanitize_text_field($metadata['ruc']));
        }
        if (!empty($metadata['razon_social'])) {
            wp_update_user(array(
                'ID' => $wp_user->ID,
                'display_name' => sanitize_text_field($metadata['razon_social'])
            ));
            update_user_meta($wp_user->ID, 'razon_social', sanitize_text_field($metadata['razon_social']));
        }
    }
    
    return new WP_REST_Response(array(
        'success' => true,
        'user_id' => $wp_user->ID,
        'email' => $wp_user->user_email,
        'roles' => $wp_user->roles
    ), 200);
}

function validate_supabase_token($auth_header) {
    // Extraer token del header
    if (empty($auth_header)) {
        return false;
    }
    
    $token = str_replace('Bearer ', '', $auth_header);
    
    // Validar token con Supabase
    $supabase_url = get_option('agrochamba_supabase_url');
    $supabase_anon_key = get_option('agrochamba_supabase_anon_key');
    
    $response = wp_remote_get("{$supabase_url}/auth/v1/user", array(
        'headers' => array(
            'Authorization' => "Bearer {$token}",
            'apikey' => $supabase_anon_key
        ),
        'timeout' => 10
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    return isset($body['id']) ? (object)$body : false;
}
```

**Opción B: Edge Function (Alternativa)**

```typescript
// Supabase Edge Function - sync-user-to-wordpress
import { serve } from "https://deno.land/std@0.168.0/http/server.ts"

serve(async (req) => {
  const { user } = await req.json()
  
  // Llamar a WordPress API
  const wpResponse = await fetch(`${WORDPRESS_URL}/wp-json/agrochamba/v1/sync/user`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Supabase-Webhook': 'true'
    },
    body: JSON.stringify({
      supabase_user_id: user.id,
      email: user.email,
      metadata: user.user_metadata
    })
  })
  
  return new Response(JSON.stringify({ success: true }), {
    headers: { 'Content-Type': 'application/json' }
  })
})
```

---

### 4. Flujo de Publicación de Trabajo

```
┌─────────┐
│ Usuario │
└────┬────┘
     │ 1. Completa formulario
     ▼
┌─────────────────┐
│  App Web        │
│  - Valida datos │
└────┬────────────┘
     │ 2. POST /agrochamba/v1/jobs
     │    Header: Authorization: Bearer {supabase_token}
     ▼
┌─────────────────┐
│  WordPress API  │
│  Middleware:    │
│  - Valida token │
│  - Obtiene user │
└────┬────────────┘
     │ 3. Crea trabajo
     ▼
┌─────────────────┐
│  WordPress      │
│  - Guarda post  │
│  - Asigna autor │
└────┬────────────┘
     │ 4. Devuelve trabajo creado
     ▼
┌─────────────────┐
│  App Web        │
│  - Muestra éxito│
└─────────────────┘
```

**Código de ejemplo:**

```typescript
// App Web - Crear trabajo
async function createJob(jobData: {
  title: string
  content: string
  ubicacion_id: number
  empresa_id?: number
  salario_min?: number
  salario_max?: number
  vacantes?: number
}) {
  const { data: { session } } = await supabase.auth.getSession()
  
  if (!session) {
    throw new Error('No autenticado')
  }
  
  const response = await fetch(`${WORDPRESS_API}/agrochamba/v1/jobs`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${session.access_token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(jobData)
  })
  
  if (!response.ok) {
    const error = await response.json()
    throw new Error(error.message)
  }
  
  return response.json()
}
```

**WordPress - Middleware de validación:**

```php
// Modificar permission_callback de endpoints existentes
// agrochamba-core/modules/23-supabase-auth-middleware.php

function agrochamba_validate_supabase_auth($request) {
    // 1. Intentar validar token Supabase
    $auth_header = $request->get_header('Authorization');
    
    if (!empty($auth_header)) {
        $supabase_user = validate_supabase_token($auth_header);
        
        if ($supabase_user) {
            // Buscar usuario WordPress vinculado
            $wp_user = get_users(array(
                'meta_key' => 'supabase_user_id',
                'meta_value' => $supabase_user->id,
                'number' => 1
            ));
            
            if (!empty($wp_user)) {
                $wp_user = is_array($wp_user) ? $wp_user[0] : $wp_user;
                wp_set_current_user($wp_user->ID);
                return true;
            }
        }
    }
    
    // 2. Fallback: validar token JWT tradicional (para compatibilidad)
    if (is_user_logged_in()) {
        return true;
    }
    
    return false;
}

// Aplicar a endpoints existentes
add_filter('rest_pre_dispatch', function($result, $server, $request) {
    $route = $request->get_route();
    
    // Rutas que requieren autenticación
    $protected_routes = array(
        '/agrochamba/v1/jobs',
        '/agrochamba/v1/me/',
    );
    
    foreach ($protected_routes as $protected_route) {
        if (strpos($route, $protected_route) === 0) {
            if (!agrochamba_validate_supabase_auth($request)) {
                return new WP_Error('rest_forbidden', 'Debes iniciar sesión', array('status' => 401));
            }
        }
    }
    
    return $result;
}, 10, 3);
```

---

## 🔌 Endpoints Necesarios

### Nuevos Endpoints WordPress

#### 1. Sincronización de Usuario

**POST** `/agrochamba/v1/sync/user`

**Headers:**
```
Authorization: Bearer {supabase_token}
Content-Type: application/json
```

**Body:**
```json
{
  "supabase_user_id": "uuid-del-usuario-supabase",
  "email": "usuario@ejemplo.com",
  "metadata": {
    "username": "nombre_usuario",
    "role": "employer",
    "ruc": "12345678901",
    "razon_social": "Empresa S.A.C."
  }
}
```

**Response 200:**
```json
{
  "success": true,
  "user_id": 123,
  "email": "usuario@ejemplo.com",
  "roles": ["employer"],
  "created": true
}
```

#### 2. Validar Token Supabase

**POST** `/agrochamba/v1/auth/validate`

**Headers:**
```
Authorization: Bearer {supabase_token}
```

**Response 200:**
```json
{
  "valid": true,
  "user_id": 123,
  "email": "usuario@ejemplo.com",
  "roles": ["employer"]
}
```

**Response 401:**
```json
{
  "valid": false,
  "error": "Token inválido o expirado"
}
```

### Endpoints Modificados

Los endpoints existentes deben aceptar tokens Supabase además de JWT tradicionales:

- `POST /agrochamba/v1/jobs` - Ya existe, agregar validación Supabase
- `GET /agrochamba/v1/me/jobs` - Ya existe, agregar validación Supabase
- `GET /agrochamba/v1/me/profile` - Ya existe, agregar validación Supabase
- `PUT /agrochamba/v1/me/profile` - Ya existe, agregar validación Supabase

---

## 🏛️ Estructura de la App Web

### Stack Tecnológico Recomendado

**Frontend Framework:**
- **Next.js 14+** (App Router) o **React 18+** con Vite
- **TypeScript** (obligatorio)
- **Tailwind CSS** o **Chakra UI** para estilos

**Estado y Datos:**
- **React Query** (TanStack Query) para cache y sincronización
- **Zustand** o **Jotai** para estado global
- **Supabase JS Client** para autenticación

**Formularios:**
- **React Hook Form** + **Zod** para validación

**Routing:**
- **Next.js Router** (si Next.js) o **React Router** (si React puro)

### Estructura de Carpetas

```
app-web/
├── src/
│   ├── app/                    # Next.js App Router (si Next.js)
│   │   ├── (auth)/
│   │   │   ├── login/
│   │   │   ├── register/
│   │   │   └── layout.tsx
│   │   ├── (dashboard)/
│   │   │   ├── jobs/
│   │   │   │   ├── new/
│   │   │   │   └── [id]/
│   │   │   ├── profile/
│   │   │   └── layout.tsx
│   │   ├── api/                 # API Routes (si Next.js)
│   │   │   └── sync/
│   │   └── layout.tsx
│   │
│   ├── components/              # Componentes reutilizables
│   │   ├── auth/
│   │   │   ├── LoginForm.tsx
│   │   │   ├── RegisterForm.tsx
│   │   │   └── AuthGuard.tsx
│   │   ├── jobs/
│   │   │   ├── JobCard.tsx
│   │   │   ├── JobForm.tsx
│   │   │   └── JobList.tsx
│   │   ├── layout/
│   │   │   ├── Header.tsx
│   │   │   ├── Footer.tsx
│   │   │   └── Sidebar.tsx
│   │   └── ui/                  # Componentes UI base
│   │
│   ├── lib/                     # Utilidades y configuraciones
│   │   ├── supabase.ts          # Cliente Supabase
│   │   ├── wordpress.ts         # Cliente WordPress API
│   │   ├── auth.ts              # Helpers de autenticación
│   │   └── utils.ts
│   │
│   ├── hooks/                   # Custom hooks
│   │   ├── useAuth.ts
│   │   ├── useJobs.ts
│   │   └── useSync.ts
│   │
│   ├── store/                   # Estado global (Zustand)
│   │   ├── authStore.ts
│   │   └── userStore.ts
│   │
│   ├── types/                   # TypeScript types
│   │   ├── user.ts
│   │   ├── job.ts
│   │   └── api.ts
│   │
│   └── styles/                  # Estilos globales
│       └── globals.css
│
├── public/                      # Archivos estáticos
├── .env.local                   # Variables de entorno
├── .env.example
├── package.json
├── tsconfig.json
└── README.md
```

### Configuración Inicial

**`.env.local`:**

```env
# Supabase
NEXT_PUBLIC_SUPABASE_URL=https://tu-proyecto.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=tu-anon-key

# WordPress
NEXT_PUBLIC_WORDPRESS_URL=https://agrochamba.com
NEXT_PUBLIC_WORDPRESS_API_URL=https://agrochamba.com/wp-json/agrochamba/v1

# App
NEXT_PUBLIC_APP_URL=http://localhost:3000
```

**`src/lib/supabase.ts`:**

```typescript
import { createClient } from '@supabase/supabase-js'

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!

export const supabase = createClient(supabaseUrl, supabaseAnonKey, {
  auth: {
    autoRefreshToken: true,
    persistSession: true,
    detectSessionInUrl: true
  }
})
```

**`src/lib/wordpress.ts`:**

```typescript
const WORDPRESS_API = process.env.NEXT_PUBLIC_WORDPRESS_API_URL!

export async function wordpressRequest(
  endpoint: string,
  options: RequestInit = {}
) {
  const { data: { session } } = await supabase.auth.getSession()
  
  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...options.headers
  }
  
  if (session?.access_token) {
    headers['Authorization'] = `Bearer ${session.access_token}`
  }
  
  const response = await fetch(`${WORDPRESS_API}${endpoint}`, {
    ...options,
    headers
  })
  
  if (!response.ok) {
    const error = await response.json()
    throw new Error(error.message || 'Error en la petición')
  }
  
  return response.json()
}
```

---

## 🔒 Consideraciones de Seguridad

### 1. Validación de Tokens

- ✅ Validar tokens Supabase en cada request protegido
- ✅ Cachear validaciones (5-10 minutos) para performance
- ✅ Verificar expiración de tokens
- ✅ Implementar refresh token automático

### 2. Rate Limiting

- ✅ Limitar requests por IP/usuario
- ✅ Implementar en WordPress y Supabase
- ✅ Usar middleware de rate limiting

### 3. CORS

- ✅ Configurar CORS en WordPress para dominio de la app web
- ✅ Solo permitir métodos necesarios (GET, POST, PUT, DELETE)
- ✅ Validar origen en cada request

### 4. Sanitización

- ✅ Sanitizar todos los inputs en WordPress
- ✅ Validar tipos de datos en frontend (Zod)
- ✅ Escapar outputs en frontend

### 5. HTTPS

- ✅ Usar HTTPS en producción (obligatorio)
- ✅ Configurar HSTS headers
- ✅ Validar certificados SSL

### 6. Secrets Management

- ✅ Nunca exponer Supabase service_role key
- ✅ Usar variables de entorno para configuración
- ✅ Rotar keys periódicamente

---

## 📅 Plan de Migración

### Fase 1: Preparación (Semana 1-2)

**Objetivos:**
- Configurar Supabase Auth
- Crear middleware de validación en WordPress
- Implementar endpoints de sincronización
- Testing en desarrollo

**Tareas:**
- [ ] Crear proyecto Supabase
- [ ] Configurar Supabase Auth (email/password)
- [ ] Implementar `validate_supabase_token()` en WordPress
- [ ] Crear endpoint `/sync/user`
- [ ] Crear endpoint `/auth/validate`
- [ ] Configurar webhooks de Supabase
- [ ] Testing de sincronización básica

**Criterios de éxito:**
- Usuario creado en Supabase se sincroniza automáticamente a WordPress
- Token Supabase valida correctamente en WordPress
- No se rompe funcionalidad existente

---

### Fase 2: Desarrollo App Web (Semana 3-5)

**Objetivos:**
- Crear estructura base de la app web
- Implementar autenticación con Supabase
- Implementar sincronización con WordPress
- Crear UI básica

**Tareas:**
- [ ] Setup proyecto (Next.js/React)
- [ ] Configurar Supabase client
- [ ] Crear componentes de autenticación (Login/Register)
- [ ] Implementar `useAuth` hook
- [ ] Crear middleware de sincronización
- [ ] Implementar páginas principales (Dashboard, Jobs, Profile)
- [ ] Crear formulario de publicación de trabajos
- [ ] Testing de flujos completos

**Criterios de éxito:**
- Usuario puede registrarse y loguearse con Supabase
- Usuario se sincroniza automáticamente con WordPress
- Usuario puede crear trabajos desde la app web
- UI es responsive y funcional

---

### Fase 3: Coexistencia (Semana 6-7)

**Objetivos:**
- App web funcionando en paralelo con sistema actual
- Nuevos usuarios van a Supabase
- Usuarios antiguos siguen funcionando

**Tareas:**
- [ ] Deploy app web a staging
- [ ] Configurar dominio/subdominio
- [ ] Habilitar registro/login Supabase para nuevos usuarios
- [ ] Mantener login WordPress para usuarios existentes
- [ ] Implementar migración lazy (al primer login)
- [ ] Monitorear errores y performance
- [ ] Documentar proceso

**Criterios de éxito:**
- App web accesible públicamente
- Nuevos usuarios se registran solo en Supabase
- Usuarios antiguos pueden seguir usando sistema actual
- No hay interrupciones en producción

---

### Fase 4: Migración de Usuarios Activos (Semana 8-9)

**Objetivos:**
- Migrar usuarios activos a Supabase
- Deshabilitar registro directo en WordPress
- Mantener compatibilidad con sistema antiguo

**Tareas:**
- [ ] Identificar usuarios activos (últimos 90 días)
- [ ] Crear script de migración batch
- [ ] Migrar usuarios activos a Supabase
- [ ] Vincular cuentas Supabase con WordPress
- [ ] Deshabilitar endpoints de registro WordPress (opcional)
- [ ] Mantener login legacy para casos especiales
- [ ] Notificar usuarios migrados

**Criterios de éxito:**
- 80%+ usuarios activos migrados
- Usuarios migrados pueden usar ambos sistemas
- No hay pérdida de datos

---

### Fase 5: Consolidación (Semana 10-11)

**Objetivos:**
- WordPress solo acepta tokens Supabase
- Eliminar código legacy de autenticación
- Optimizar y limpiar código

**Tareas:**
- [ ] Actualizar todos los endpoints para solo aceptar Supabase
- [ ] Eliminar endpoints de registro/login WordPress (opcional)
- [ ] Limpiar código no utilizado
- [ ] Optimizar queries y cache
- [ ] Actualizar documentación
- [ ] Testing final completo
- [ ] Deploy a producción

**Criterios de éxito:**
- Sistema funciona completamente con Supabase
- Código limpio y optimizado
- Documentación actualizada
- Sin errores en producción

---

## ✅ Checklist de Implementación

### Configuración Supabase

- [ ] Proyecto Supabase creado
- [ ] Auth habilitado (Email/Password)
- [ ] Webhooks configurados (`user.created`, `user.updated`)
- [ ] Edge Functions creadas (si aplica)
- [ ] Variables de entorno configuradas

### Backend WordPress

- [ ] Plugin de sincronización instalado
- [ ] Endpoint `/sync/user` implementado
- [ ] Endpoint `/auth/validate` implementado
- [ ] Middleware de validación Supabase implementado
- [ ] Modificados endpoints existentes para aceptar tokens Supabase
- [ ] Webhooks configurados y funcionando
- [ ] Testing de sincronización completo

### Frontend App Web

- [ ] Proyecto inicializado (Next.js/React)
- [ ] Supabase client configurado
- [ ] WordPress API client configurado
- [ ] Componentes de autenticación creados
- [ ] Hooks personalizados implementados
- [ ] Páginas principales creadas
- [ ] Formularios implementados
- [ ] Manejo de errores implementado
- [ ] Loading states implementados
- [ ] Responsive design implementado

### Testing

- [ ] Testing de registro de usuario
- [ ] Testing de login
- [ ] Testing de sincronización Supabase → WordPress
- [ ] Testing de creación de trabajos
- [ ] Testing de actualización de perfil
- [ ] Testing de permisos y roles
- [ ] Testing de errores y edge cases
- [ ] Testing de performance
- [ ] Testing de seguridad

### Deployment

- [ ] App web deployada (staging)
- [ ] Variables de entorno configuradas
- [ ] CORS configurado correctamente
- [ ] HTTPS configurado
- [ ] Monitoring configurado
- [ ] Error tracking configurado
- [ ] Documentación actualizada

---

## 📚 Recursos Adicionales

### Documentación de Referencia

- [Supabase Auth Documentation](https://supabase.com/docs/guides/auth)
- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [Next.js Documentation](https://nextjs.org/docs)
- [React Query Documentation](https://tanstack.com/query/latest)

### Código de Ejemplo

Ver archivos de ejemplo en:
- `agrochamba-core/modules/23-supabase-sync.php` (Backend)
- `app-web/src/lib/supabase.ts` (Frontend)
- `app-web/src/hooks/useAuth.ts` (Hooks)

---

## 🎯 Resultado Esperado

Al finalizar la implementación, tendrás:

✅ **App Web moderna** con Supabase Auth
✅ **Sincronización completa** con WordPress
✅ **Compatibilidad total** con sistema existente
✅ **Migración progresiva** sin interrupciones
✅ **Base sólida** para futuros servicios (AgroBus, Ranking, AgroSafe)

---

## 📞 Soporte

Para dudas o problemas durante la implementación:
1. Revisar logs de WordPress (`debug.log`)
2. Revisar logs de Supabase (Dashboard → Logs)
3. Verificar configuración de webhooks
4. Validar tokens en Supabase Dashboard → Auth → Users

---

**Última actualización:** 2025-01-XX
**Versión:** 1.0.0

