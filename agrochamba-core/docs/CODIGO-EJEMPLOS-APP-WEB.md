# 💻 Ejemplos de Código: App Web Agrochamba con Supabase

Este documento contiene ejemplos de código prácticos y específicos para implementar la nueva app web de Agrochamba.

---

## 📋 Tabla de Contenidos

1. [Backend WordPress](#backend-wordpress)
2. [Frontend - Configuración](#frontend---configuración)
3. [Frontend - Autenticación](#frontend---autenticación)
4. [Frontend - Sincronización](#frontend---sincronización)
5. [Frontend - Trabajos](#frontend---trabajos)
6. [Frontend - Perfil](#frontend---perfil)
7. [Hooks Personalizados](#hooks-personalizados)
8. [Componentes UI](#componentes-ui)

---

## 🔧 Backend WordPress

### 1. Módulo de Sincronización Supabase

**Archivo:** `agrochamba-core/modules/23-supabase-sync.php`

```php
<?php
/**
 * MÓDULO 23: SINCRONIZACIÓN SUPABASE ↔ WORDPRESS
 * 
 * Maneja la sincronización de usuarios entre Supabase y WordPress
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// CONFIGURACIÓN
// ==========================================
if (!defined('AGROCHAMBA_SUPABASE_URL')) {
    define('AGROCHAMBA_SUPABASE_URL', get_option('agrochamba_supabase_url', ''));
}

if (!defined('AGROCHAMBA_SUPABASE_ANON_KEY')) {
    define('AGROCHAMBA_SUPABASE_ANON_KEY', get_option('agrochamba_supabase_anon_key', ''));
}

// ==========================================
// 1. VALIDAR TOKEN SUPABASE
// ==========================================
if (!function_exists('agrochamba_validate_supabase_token')) {
    /**
     * Valida un token JWT de Supabase
     * 
     * @param string $auth_header Header Authorization completo
     * @return object|false Usuario de Supabase o false si inválido
     */
    function agrochamba_validate_supabase_token($auth_header) {
        if (empty($auth_header)) {
            return false;
        }
        
        // Extraer token del header "Bearer {token}"
        $token = str_replace('Bearer ', '', trim($auth_header));
        
        if (empty($token)) {
            return false;
        }
        
        // Validar token con Supabase API
        $supabase_url = AGROCHAMBA_SUPABASE_URL;
        $supabase_anon_key = AGROCHAMBA_SUPABASE_ANON_KEY;
        
        if (empty($supabase_url) || empty($supabase_anon_key)) {
            error_log('AgroChamba: Supabase URL o Anon Key no configurados');
            return false;
        }
        
        // Cachear validación (5 minutos)
        $cache_key = 'agrochamba_supabase_token_' . md5($token);
        $cached_user = get_transient($cache_key);
        
        if ($cached_user !== false) {
            return $cached_user;
        }
        
        // Validar con Supabase
        $response = wp_remote_get("{$supabase_url}/auth/v1/user", array(
            'headers' => array(
                'Authorization' => "Bearer {$token}",
                'apikey' => $supabase_anon_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            error_log('AgroChamba: Error validando token Supabase: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['id'])) {
            return false;
        }
        
        $user = (object)$body;
        
        // Guardar en cache
        set_transient($cache_key, $user, 5 * MINUTE_IN_SECONDS);
        
        return $user;
    }
}

// ==========================================
// 2. SINCRONIZAR USUARIO SUPABASE → WORDPRESS
// ==========================================
if (!function_exists('agrochamba_sync_supabase_user')) {
    /**
     * Sincroniza un usuario de Supabase a WordPress
     * 
     * @param string $supabase_user_id ID del usuario en Supabase
     * @param string $email Email del usuario
     * @param array $metadata Metadata del usuario
     * @return WP_User|WP_Error Usuario de WordPress o error
     */
    function agrochamba_sync_supabase_user($supabase_user_id, $email, $metadata = array()) {
        // 1. Buscar usuario existente por supabase_user_id
        $wp_users = get_users(array(
            'meta_key' => 'supabase_user_id',
            'meta_value' => $supabase_user_id,
            'number' => 1
        ));
        
        if (!empty($wp_users)) {
            $wp_user = $wp_users[0];
            // Actualizar metadata si es necesario
            agrochamba_update_user_metadata($wp_user->ID, $metadata);
            return $wp_user;
        }
        
        // 2. Buscar por email
        $wp_user = get_user_by('email', $email);
        
        if ($wp_user) {
            // Vincular con Supabase
            update_user_meta($wp_user->ID, 'supabase_user_id', $supabase_user_id);
            agrochamba_update_user_metadata($wp_user->ID, $metadata);
            return $wp_user;
        }
        
        // 3. Crear nuevo usuario
        $username = isset($metadata['username']) 
            ? sanitize_user($metadata['username']) 
            : sanitize_user($email);
        
        // Asegurar username único
        $original_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        // Generar password aleatorio (no se usa para login)
        $password = wp_generate_password(20);
        
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        $wp_user = get_user_by('id', $user_id);
        
        // Guardar supabase_user_id
        update_user_meta($wp_user->ID, 'supabase_user_id', $supabase_user_id);
        
        // Actualizar metadata
        agrochamba_update_user_metadata($wp_user->ID, $metadata);
        
        return $wp_user;
    }
}

// ==========================================
// 3. ACTUALIZAR METADATA DE USUARIO
// ==========================================
if (!function_exists('agrochamba_update_user_metadata')) {
    /**
     * Actualiza metadata de usuario desde Supabase
     */
    function agrochamba_update_user_metadata($user_id, $metadata) {
        $role = isset($metadata['role']) ? $metadata['role'] : 'subscriber';
        
        // Asignar rol
        $wp_user = new WP_User($user_id);
        if ($role === 'employer') {
            $wp_user->set_role('employer');
        } else {
            $wp_user->set_role('subscriber');
        }
        
        // Actualizar display_name
        if (isset($metadata['razon_social']) && !empty($metadata['razon_social'])) {
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => sanitize_text_field($metadata['razon_social'])
            ));
            update_user_meta($user_id, 'razon_social', sanitize_text_field($metadata['razon_social']));
        }
        
        // Campos adicionales para empresas
        if ($role === 'employer') {
            if (isset($metadata['ruc'])) {
                update_user_meta($user_id, 'ruc', sanitize_text_field($metadata['ruc']));
            }
        }
        
        // Campos generales
        if (isset($metadata['phone'])) {
            update_user_meta($user_id, 'phone', sanitize_text_field($metadata['phone']));
        }
        
        if (isset($metadata['bio'])) {
            update_user_meta($user_id, 'bio', sanitize_textarea_field($metadata['bio']));
        }
    }
}

// ==========================================
// 4. ENDPOINT: SINCRONIZAR USUARIO
// ==========================================
if (!function_exists('agrochamba_rest_sync_user')) {
    function agrochamba_rest_sync_user($request) {
        // Validar token Supabase
        $auth_header = $request->get_header('Authorization');
        $supabase_user = agrochamba_validate_supabase_token($auth_header);
        
        if (!$supabase_user) {
            return new WP_Error(
                'invalid_token',
                'Token de Supabase inválido o expirado',
                array('status' => 401)
            );
        }
        
        $params = $request->get_json_params();
        $supabase_user_id = isset($params['supabase_user_id']) 
            ? sanitize_text_field($params['supabase_user_id']) 
            : $supabase_user->id;
        
        $email = isset($params['email']) 
            ? sanitize_email($params['email']) 
            : $supabase_user->email;
        
        $metadata = isset($params['metadata']) 
            ? $params['metadata'] 
            : (isset($supabase_user->user_metadata) ? $supabase_user->user_metadata : array());
        
        // Sincronizar usuario
        $wp_user = agrochamba_sync_supabase_user($supabase_user_id, $email, $metadata);
        
        if (is_wp_error($wp_user)) {
            return $wp_user;
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'user_id' => $wp_user->ID,
            'email' => $wp_user->user_email,
            'username' => $wp_user->user_login,
            'display_name' => $wp_user->display_name,
            'roles' => $wp_user->roles
        ), 200);
    }
}

// ==========================================
// 5. ENDPOINT: VALIDAR TOKEN
// ==========================================
if (!function_exists('agrochamba_rest_validate_token')) {
    function agrochamba_rest_validate_token($request) {
        $auth_header = $request->get_header('Authorization');
        $supabase_user = agrochamba_validate_supabase_token($auth_header);
        
        if (!$supabase_user) {
            return new WP_Error(
                'invalid_token',
                'Token inválido o expirado',
                array('status' => 401)
            );
        }
        
        // Buscar usuario WordPress vinculado
        $wp_users = get_users(array(
            'meta_key' => 'supabase_user_id',
            'meta_value' => $supabase_user->id,
            'number' => 1
        ));
        
        if (empty($wp_users)) {
            return new WP_Error(
                'user_not_found',
                'Usuario no encontrado en WordPress',
                array('status' => 404)
            );
        }
        
        $wp_user = $wp_users[0];
        
        return new WP_REST_Response(array(
            'valid' => true,
            'user_id' => $wp_user->ID,
            'email' => $wp_user->user_email,
            'roles' => $wp_user->roles
        ), 200);
    }
}

// ==========================================
// 6. MIDDLEWARE: VALIDAR AUTENTICACIÓN
// ==========================================
if (!function_exists('agrochamba_validate_auth')) {
    /**
     * Middleware para validar autenticación (Supabase o WordPress)
     */
    function agrochamba_validate_auth($request) {
        $auth_header = $request->get_header('Authorization');
        
        // Intentar validar token Supabase
        if (!empty($auth_header)) {
            $supabase_user = agrochamba_validate_supabase_token($auth_header);
            
            if ($supabase_user) {
                // Buscar usuario WordPress vinculado
                $wp_users = get_users(array(
                    'meta_key' => 'supabase_user_id',
                    'meta_value' => $supabase_user->id,
                    'number' => 1
                ));
                
                if (!empty($wp_users)) {
                    $wp_user = $wp_users[0];
                    wp_set_current_user($wp_user->ID);
                    return true;
                }
            }
        }
        
        // Fallback: validar sesión WordPress tradicional
        if (is_user_logged_in()) {
            return true;
        }
        
        return false;
    }
}

// ==========================================
// 7. REGISTRAR ENDPOINTS
// ==========================================
add_action('rest_api_init', function() {
    // Sincronizar usuario
    register_rest_route('agrochamba/v1', '/sync/user', array(
        'methods' => 'POST',
        'callback' => 'agrochamba_rest_sync_user',
        'permission_callback' => '__return_true', // Validado por token
    ));
    
    // Validar token
    register_rest_route('agrochamba/v1', '/auth/validate', array(
        'methods' => 'POST',
        'callback' => 'agrochamba_rest_validate_token',
        'permission_callback' => '__return_true', // Validado por token
    ));
}, 20);

// ==========================================
// 8. APLICAR MIDDLEWARE A ENDPOINTS EXISTENTES
// ==========================================
add_filter('rest_pre_dispatch', function($result, $server, $request) {
    $route = $request->get_route();
    
    // Rutas protegidas
    $protected_routes = array(
        '/agrochamba/v1/jobs',
        '/agrochamba/v1/me/',
    );
    
    foreach ($protected_routes as $protected_route) {
        if (strpos($route, $protected_route) === 0) {
            if (!agrochamba_validate_auth($request)) {
                return new WP_Error(
                    'rest_forbidden',
                    'Debes iniciar sesión para acceder a este recurso',
                    array('status' => 401)
                );
            }
        }
    }
    
    return $result;
}, 10, 3);
```

---

## ⚙️ Frontend - Configuración

### 1. Configuración Supabase

**Archivo:** `src/lib/supabase.ts`

```typescript
import { createClient } from '@supabase/supabase-js'

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!

if (!supabaseUrl || !supabaseAnonKey) {
  throw new Error('Missing Supabase environment variables')
}

export const supabase = createClient(supabaseUrl, supabaseAnonKey, {
  auth: {
    autoRefreshToken: true,
    persistSession: true,
    detectSessionInUrl: true,
    storage: typeof window !== 'undefined' ? window.localStorage : undefined
  }
})
```

### 2. Cliente WordPress API

**Archivo:** `src/lib/wordpress.ts`

```typescript
import { supabase } from './supabase'

const WORDPRESS_API = process.env.NEXT_PUBLIC_WORDPRESS_API_URL || 'https://agrochamba.com/wp-json/agrochamba/v1'

export interface WordPressError {
  code: string
  message: string
  data?: {
    status: number
  }
}

export async function wordpressRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  // Obtener token de Supabase
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
    const error: WordPressError = await response.json()
    throw new Error(error.message || `Error ${response.status}`)
  }
  
  return response.json()
}

// Helper para GET requests
export async function wordpressGet<T>(endpoint: string): Promise<T> {
  return wordpressRequest<T>(endpoint, { method: 'GET' })
}

// Helper para POST requests
export async function wordpressPost<T>(
  endpoint: string,
  body: unknown
): Promise<T> {
  return wordpressRequest<T>(endpoint, {
    method: 'POST',
    body: JSON.stringify(body)
  })
}

// Helper para PUT requests
export async function wordpressPut<T>(
  endpoint: string,
  body: unknown
): Promise<T> {
  return wordpressRequest<T>(endpoint, {
    method: 'PUT',
    body: JSON.stringify(body)
  })
}

// Helper para DELETE requests
export async function wordpressDelete<T>(endpoint: string): Promise<T> {
  return wordpressRequest<T>(endpoint, { method: 'DELETE' })
}
```

---

## 🔐 Frontend - Autenticación

### 1. Hook useAuth

**Archivo:** `src/hooks/useAuth.ts`

```typescript
import { useState, useEffect } from 'react'
import { User, Session } from '@supabase/supabase-js'
import { supabase } from '@/lib/supabase'
import { wordpressGet } from '@/lib/wordpress'

interface WordPressUser {
  user_id: number
  email: string
  username: string
  display_name: string
  roles: string[]
}

interface AuthState {
  user: User | null
  wpUser: WordPressUser | null
  session: Session | null
  loading: boolean
  error: Error | null
}

export function useAuth() {
  const [state, setState] = useState<AuthState>({
    user: null,
    wpUser: null,
    session: null,
    loading: true,
    error: null
  })

  // Sincronizar usuario con WordPress
  const syncWithWordPress = async (supabaseUser: User) => {
    try {
      const wpUser = await wordpressGet<WordPressUser>('/me/profile')
      setState(prev => ({ ...prev, wpUser }))
    } catch (error) {
      // Si no existe, sincronizar
      try {
        const response = await fetch(`${process.env.NEXT_PUBLIC_WORDPRESS_API_URL}/sync/user`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${(await supabase.auth.getSession()).data.session?.access_token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            supabase_user_id: supabaseUser.id,
            email: supabaseUser.email,
            metadata: supabaseUser.user_metadata
          })
        })
        
        if (response.ok) {
          const wpUser = await response.json()
          setState(prev => ({ ...prev, wpUser }))
        }
      } catch (syncError) {
        console.error('Error sincronizando usuario:', syncError)
      }
    }
  }

  // Inicializar sesión
  useEffect(() => {
    supabase.auth.getSession().then(({ data: { session } }) => {
      setState(prev => ({
        ...prev,
        session,
        user: session?.user ?? null,
        loading: false
      }))

      if (session?.user) {
        syncWithWordPress(session.user)
      }
    })

    // Escuchar cambios de autenticación
    const {
      data: { subscription }
    } = supabase.auth.onAuthStateChange((_event, session) => {
      setState(prev => ({
        ...prev,
        session,
        user: session?.user ?? null,
        loading: false
      }))

      if (session?.user) {
        syncWithWordPress(session.user)
      } else {
        setState(prev => ({ ...prev, wpUser: null }))
      }
    })

    return () => subscription.unsubscribe()
  }, [])

  // Login
  const login = async (email: string, password: string) => {
    setState(prev => ({ ...prev, loading: true, error: null }))
    
    try {
      const { data, error } = await supabase.auth.signInWithPassword({
        email,
        password
      })

      if (error) throw error

      if (data.user) {
        await syncWithWordPress(data.user)
      }

      return { user: data.user, session: data.session }
    } catch (error) {
      setState(prev => ({ ...prev, error: error as Error }))
      throw error
    } finally {
      setState(prev => ({ ...prev, loading: false }))
    }
  }

  // Registro
  const register = async (
    email: string,
    password: string,
    metadata: {
      username: string
      role: 'employer' | 'subscriber'
      ruc?: string
      razon_social?: string
    }
  ) => {
    setState(prev => ({ ...prev, loading: true, error: null }))

    try {
      const { data, error } = await supabase.auth.signUp({
        email,
        password,
        options: {
          data: metadata
        }
      })

      if (error) throw error

      return { user: data.user, session: data.session }
    } catch (error) {
      setState(prev => ({ ...prev, error: error as Error }))
      throw error
    } finally {
      setState(prev => ({ ...prev, loading: false }))
    }
  }

  // Logout
  const logout = async () => {
    setState(prev => ({ ...prev, loading: true }))
    
    await supabase.auth.signOut()
    
    setState({
      user: null,
      wpUser: null,
      session: null,
      loading: false,
      error: null
    })
  }

  return {
    ...state,
    login,
    register,
    logout,
    isAuthenticated: !!state.user,
    isEmployer: state.wpUser?.roles.includes('employer') ?? false
  }
}
```

### 2. Componente AuthGuard

**Archivo:** `src/components/auth/AuthGuard.tsx`

```typescript
import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { useAuth } from '@/hooks/useAuth'

interface AuthGuardProps {
  children: React.ReactNode
  requireEmployer?: boolean
}

export function AuthGuard({ children, requireEmployer = false }: AuthGuardProps) {
  const { isAuthenticated, isEmployer, loading } = useAuth()
  const router = useRouter()

  useEffect(() => {
    if (!loading) {
      if (!isAuthenticated) {
        router.push('/login')
      } else if (requireEmployer && !isEmployer) {
        router.push('/dashboard')
      }
    }
  }, [isAuthenticated, isEmployer, loading, router, requireEmployer])

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    )
  }

  if (!isAuthenticated) {
    return null
  }

  if (requireEmployer && !isEmployer) {
    return null
  }

  return <>{children}</>
}
```

---

## 🔄 Frontend - Sincronización

### Hook useSync

**Archivo:** `src/hooks/useSync.ts`

```typescript
import { useState } from 'react'
import { supabase } from '@/lib/supabase'

export function useSync() {
  const [syncing, setSyncing] = useState(false)
  const [error, setError] = useState<Error | null>(null)

  const syncUser = async () => {
    setSyncing(true)
    setError(null)

    try {
      const { data: { session } } = await supabase.auth.getSession()
      
      if (!session?.user) {
        throw new Error('No hay sesión activa')
      }

      const response = await fetch(`${process.env.NEXT_PUBLIC_WORDPRESS_API_URL}/sync/user`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${session.access_token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          supabase_user_id: session.user.id,
          email: session.user.email,
          metadata: session.user.user_metadata
        })
      })

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.message || 'Error sincronizando usuario')
      }

      const wpUser = await response.json()
      return wpUser
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Error desconocido')
      setError(error)
      throw error
    } finally {
      setSyncing(false)
    }
  }

  return {
    syncUser,
    syncing,
    error
  }
}
```

---

## 📝 Frontend - Trabajos

### Hook useJobs

**Archivo:** `src/hooks/useJobs.ts`

```typescript
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { wordpressGet, wordpressPost, wordpressPut, wordpressDelete } from '@/lib/wordpress'

export interface Job {
  id: number
  title: string
  content: string
  status: string
  author: number
  date: string
  link: string
  meta?: {
    ubicacion_id?: number
    empresa_id?: number
    salario_min?: number
    salario_max?: number
    vacantes?: number
  }
}

export interface CreateJobData {
  title: string
  content: string
  ubicacion_id: number
  empresa_id?: number
  salario_min?: number
  salario_max?: number
  vacantes?: number
  comentarios_habilitados?: boolean
}

export function useJobs() {
  const queryClient = useQueryClient()

  // Obtener mis trabajos
  const { data: myJobs, isLoading: loadingMyJobs } = useQuery<Job[]>({
    queryKey: ['jobs', 'my'],
    queryFn: () => wordpressGet<Job[]>('/me/jobs')
  })

  // Crear trabajo
  const createJob = useMutation({
    mutationFn: (data: CreateJobData) => 
      wordpressPost<Job>('/jobs', data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['jobs'] })
    }
  })

  // Actualizar trabajo
  const updateJob = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<CreateJobData> }) =>
      wordpressPut<Job>(`/jobs/${id}`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['jobs'] })
    }
  })

  // Eliminar trabajo
  const deleteJob = useMutation({
    mutationFn: (id: number) =>
      wordpressDelete(`/jobs/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['jobs'] })
    }
  })

  return {
    myJobs: myJobs ?? [],
    loadingMyJobs,
    createJob: createJob.mutate,
    updateJob: updateJob.mutate,
    deleteJob: deleteJob.mutate,
    isCreating: createJob.isPending,
    isUpdating: updateJob.isPending,
    isDeleting: deleteJob.isPending,
    error: createJob.error || updateJob.error || deleteJob.error
  }
}
```

### Componente JobForm

**Archivo:** `src/components/jobs/JobForm.tsx`

```typescript
'use client'

import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useJobs, CreateJobData } from '@/hooks/useJobs'

const jobSchema = z.object({
  title: z.string().min(10).max(200),
  content: z.string().min(50).max(10000),
  ubicacion_id: z.number().min(1),
  empresa_id: z.number().optional(),
  salario_min: z.number().optional(),
  salario_max: z.number().optional(),
  vacantes: z.number().min(1).optional(),
  comentarios_habilitados: z.boolean().default(true)
})

type JobFormData = z.infer<typeof jobSchema>

export function JobForm() {
  const { createJob, isCreating } = useJobs()
  const [success, setSuccess] = useState(false)

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset
  } = useForm<JobFormData>({
    resolver: zodResolver(jobSchema)
  })

  const onSubmit = async (data: JobFormData) => {
    try {
      await createJob(data as CreateJobData)
      setSuccess(true)
      reset()
      setTimeout(() => setSuccess(false), 3000)
    } catch (error) {
      console.error('Error creando trabajo:', error)
    }
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      {success && (
        <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
          Trabajo creado exitosamente
        </div>
      )}

      <div>
        <label htmlFor="title" className="block text-sm font-medium text-gray-700">
          Título *
        </label>
        <input
          {...register('title')}
          type="text"
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
        />
        {errors.title && (
          <p className="mt-1 text-sm text-red-600">{errors.title.message}</p>
        )}
      </div>

      <div>
        <label htmlFor="content" className="block text-sm font-medium text-gray-700">
          Descripción *
        </label>
        <textarea
          {...register('content')}
          rows={10}
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
        />
        {errors.content && (
          <p className="mt-1 text-sm text-red-600">{errors.content.message}</p>
        )}
      </div>

      <div>
        <label htmlFor="ubicacion_id" className="block text-sm font-medium text-gray-700">
          Ubicación *
        </label>
        <input
          {...register('ubicacion_id', { valueAsNumber: true })}
          type="number"
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
        />
        {errors.ubicacion_id && (
          <p className="mt-1 text-sm text-red-600">{errors.ubicacion_id.message}</p>
        )}
      </div>

      <button
        type="submit"
        disabled={isCreating}
        className="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50"
      >
        {isCreating ? 'Creando...' : 'Crear Trabajo'}
      </button>
    </form>
  )
}
```

---

## 👤 Frontend - Perfil

### Hook useProfile

**Archivo:** `src/hooks/useProfile.ts`

```typescript
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { wordpressGet, wordpressPut } from '@/lib/wordpress'

export interface UserProfile {
  user_id: number
  username: string
  display_name: string
  email: string
  roles: string[]
  is_enterprise: boolean
  profile_photo_url?: string
  phone?: string
  bio?: string
  company_description?: string
  company_address?: string
  company_phone?: string
  company_website?: string
}

export interface UpdateProfileData {
  display_name?: string
  first_name?: string
  last_name?: string
  email?: string
  phone?: string
  bio?: string
  company_description?: string
  company_address?: string
  company_phone?: string
  company_website?: string
}

export function useProfile() {
  const queryClient = useQueryClient()

  // Obtener perfil
  const { data: profile, isLoading: loading } = useQuery<UserProfile>({
    queryKey: ['profile', 'me'],
    queryFn: () => wordpressGet<UserProfile>('/me/profile')
  })

  // Actualizar perfil
  const updateProfile = useMutation({
    mutationFn: (data: UpdateProfileData) =>
      wordpressPut<{ success: boolean; message: string }>('/me/profile', data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['profile'] })
    }
  })

  return {
    profile,
    loading,
    updateProfile: updateProfile.mutate,
    isUpdating: updateProfile.isPending,
    error: updateProfile.error
  }
}
```

---

## 🎨 Componentes UI

### Componente LoginForm

**Archivo:** `src/components/auth/LoginForm.tsx`

```typescript
'use client'

import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { useAuth } from '@/hooks/useAuth'
import { useRouter } from 'next/navigation'

interface LoginFormData {
  email: string
  password: string
}

export function LoginForm() {
  const { login, loading, error } = useAuth()
  const router = useRouter()
  const [submitError, setSubmitError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    formState: { errors }
  } = useForm<LoginFormData>()

  const onSubmit = async (data: LoginFormData) => {
    try {
      setSubmitError(null)
      await login(data.email, data.password)
      router.push('/dashboard')
    } catch (err) {
      setSubmitError(err instanceof Error ? err.message : 'Error al iniciar sesión')
    }
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      {(error || submitError) && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {submitError || error?.message}
        </div>
      )}

      <div>
        <label htmlFor="email" className="block text-sm font-medium text-gray-700">
          Email
        </label>
        <input
          {...register('email', { required: 'Email es requerido' })}
          type="email"
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
        />
        {errors.email && (
          <p className="mt-1 text-sm text-red-600">{errors.email.message}</p>
        )}
      </div>

      <div>
        <label htmlFor="password" className="block text-sm font-medium text-gray-700">
          Contraseña
        </label>
        <input
          {...register('password', { required: 'Contraseña es requerida' })}
          type="password"
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
        />
        {errors.password && (
          <p className="mt-1 text-sm text-red-600">{errors.password.message}</p>
        )}
      </div>

      <button
        type="submit"
        disabled={loading}
        className="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50"
      >
        {loading ? 'Iniciando sesión...' : 'Iniciar Sesión'}
      </button>
    </form>
  )
}
```

---

## 📦 Variables de Entorno

**Archivo:** `.env.local`

```env
# Supabase
NEXT_PUBLIC_SUPABASE_URL=https://tu-proyecto.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=tu-anon-key-aqui

# WordPress
NEXT_PUBLIC_WORDPRESS_API_URL=https://agrochamba.com/wp-json/agrochamba/v1

# App
NEXT_PUBLIC_APP_URL=http://localhost:3000
```

---

## 🚀 Próximos Pasos

1. Implementar estos ejemplos en tu proyecto
2. Adaptar según tus necesidades específicas
3. Agregar manejo de errores más robusto
4. Implementar tests unitarios e integración
5. Optimizar performance y cache

---

**Última actualización:** 2025-01-XX

