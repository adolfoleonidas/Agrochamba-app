# Ecosistema Agro - Arquitectura y Plan de Migración

## Visión General

Dos aplicaciones complementarias para trabajadores del sector agroindustrial:

| App | Propósito | Estado |
|-----|-----------|--------|
| **AgroChamba** | Encontrar trabajos agroindustriales | 39 usuarios |
| **AgroBus** | Rutas y transporte en tiempo real | En desarrollo |

```
👤 Trabajador agrícola
        │
        ├──► AgroChamba (encuentra trabajo)
        │
        └──► AgroBus (llega al trabajo)

= Mismo usuario, misma cuenta
```

---

## Infraestructura Actual

| Recurso | Estado | Costo |
|---------|--------|-------|
| WordPress | Hosting compartido Hostinger | ~$3-5/mes |
| VPS | Vacío (sin usar) | Ya pagado |
| Supabase | Usado en AgroBus | Free tier |

### Problemas actuales
- VPS desperdiciado
- Hosting compartido = lento y limitado
- Auth separada entre apps
- Usuarios deben registrarse 2 veces

---

## Arquitectura Objetivo

```
┌─────────────────┐     ┌─────────────────┐
│   AgroChamba    │     │    AgroBus      │
│   (Android)     │     │   (Android)     │
└────────┬────────┘     └────────┬────────┘
         │                       │
         └───────────┬───────────┘
                     │
                     ▼
            ┌─────────────────┐
            │    SUPABASE     │
            │   ───────────   │
            │   • Auth        │  ← Login unificado
            │   • Users       │  ← Perfil compartido
            │   • Storage     │  ← Imágenes CDN
            │   • Realtime    │  ← GPS buses
            └────────┬────────┘
                     │
         ┌───────────┴───────────┐
         │                       │
         ▼                       ▼
┌─────────────────┐     ┌─────────────────┐
│   WordPress     │     │   PostgreSQL    │
│   (Tu VPS)      │     │   (Supabase)    │
│   ───────────   │     │   ───────────   │
│   • Trabajos    │     │   • Rutas       │
│   • Empresas    │     │   • Ubicaciones │
│   • Sedes       │     │   • Horarios    │
│   • Admin Panel │     │   • Tracking    │
└─────────────────┘     └─────────────────┘
```

### Beneficios
- ✅ Un solo registro para ambas apps
- ✅ Datos cruzados (trabajo + ruta disponible)
- ✅ VPS aprovechado
- ✅ Imágenes en CDN global
- ✅ Escalable a futuro

---

## Plan de Migración

### Fase 1: Mover WordPress al VPS
**Tiempo estimado:** 1-2 días
**Riesgo:** Bajo
**Impacto:** Mayor velocidad inmediata

#### Pasos:
1. Instalar en VPS:
   - Ubuntu 22.04
   - Nginx
   - PHP 8.1+
   - MySQL 8
   - SSL (Let's Encrypt)

2. Migrar WordPress:
   - Exportar DB de Hostinger
   - Copiar wp-content
   - Importar en VPS
   - Actualizar DNS

3. Verificar:
   - API REST funciona
   - App Android conecta
   - Admin accesible

#### Comandos VPS:
```bash
# Instalar LEMP stack
sudo apt update
sudo apt install nginx mysql-server php-fpm php-mysql php-curl php-xml php-mbstring

# Configurar MySQL
sudo mysql_secure_installation

# Crear base de datos
sudo mysql -e "CREATE DATABASE agrochamba;"
sudo mysql -e "CREATE USER 'agrochamba'@'localhost' IDENTIFIED BY 'PASSWORD_SEGURO';"
sudo mysql -e "GRANT ALL ON agrochamba.* TO 'agrochamba'@'localhost';"

# SSL con Certbot
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d tudominio.com
```

---

### Fase 2: Integrar Supabase Auth
**Tiempo estimado:** 1 semana
**Riesgo:** Medio
**Impacto:** Login unificado

#### 2.1 Configurar Supabase
```sql
-- Tabla de perfiles extendida
CREATE TABLE public.profiles (
  id UUID REFERENCES auth.users PRIMARY KEY,
  nombre TEXT,
  telefono TEXT,
  tipo TEXT CHECK (tipo IN ('trabajador', 'empresa')),
  wordpress_user_id INTEGER, -- Referencia a WordPress
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- RLS (Row Level Security)
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view own profile"
  ON public.profiles FOR SELECT
  USING (auth.uid() = id);

CREATE POLICY "Users can update own profile"
  ON public.profiles FOR UPDATE
  USING (auth.uid() = id);
```

#### 2.2 Modificar Android (AgroChamba)
```kotlin
// AuthManager.kt - Nuevo con Supabase
object AuthManager {
    private val supabase = createSupabaseClient(
        supabaseUrl = "https://xxx.supabase.co",
        supabaseKey = "tu-anon-key"
    ) {
        install(Auth)
        install(Postgrest)
        install(Storage)
    }

    suspend fun signIn(email: String, password: String): Result<User> {
        return try {
            val result = supabase.auth.signInWith(Email) {
                this.email = email
                this.password = password
            }
            Result.success(result)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun signUp(email: String, password: String, nombre: String): Result<User> {
        return try {
            val result = supabase.auth.signUpWith(Email) {
                this.email = email
                this.password = password
            }
            // Crear perfil
            supabase.postgrest["profiles"].insert(
                mapOf(
                    "id" to result.id,
                    "nombre" to nombre
                )
            )
            Result.success(result)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    fun getToken(): String? = supabase.auth.currentSessionOrNull()?.accessToken
}
```

#### 2.3 Modificar WordPress (validar token Supabase)
```php
// functions.php o plugin personalizado
add_filter('rest_authentication_errors', function($result) {
    // Si ya está autenticado, continuar
    if (!empty($result)) return $result;

    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $token);

    if (empty($token)) return $result;

    // Validar JWT de Supabase
    $supabase_jwt_secret = 'tu-jwt-secret';

    try {
        $decoded = firebase_jwt_decode($token, $supabase_jwt_secret);
        $supabase_user_id = $decoded->sub;

        // Buscar o crear usuario WordPress asociado
        $wp_user = get_users(['meta_key' => 'supabase_id', 'meta_value' => $supabase_user_id]);

        if (!empty($wp_user)) {
            wp_set_current_user($wp_user[0]->ID);
        }
    } catch (Exception $e) {
        return new WP_Error('invalid_token', 'Token inválido', ['status' => 401]);
    }

    return $result;
});
```

#### 2.4 Migrar usuarios existentes
```php
// Script de migración (ejecutar una vez)
function migrar_usuarios_a_supabase() {
    $usuarios = get_users(['role__in' => ['subscriber', 'empresa']]);

    foreach ($usuarios as $user) {
        // Crear en Supabase via API
        $response = wp_remote_post('https://xxx.supabase.co/auth/v1/admin/users', [
            'headers' => [
                'Authorization' => 'Bearer SERVICE_ROLE_KEY',
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'email' => $user->user_email,
                'password' => wp_generate_password(16), // Temporal
                'email_confirm' => true,
                'user_metadata' => [
                    'nombre' => $user->display_name,
                    'wordpress_id' => $user->ID
                ]
            ])
        ]);

        if (!is_wp_error($response)) {
            $supabase_user = json_decode($response['body']);
            update_user_meta($user->ID, 'supabase_id', $supabase_user->id);
        }
    }
}
```

---

### Fase 3: Supabase Storage para imágenes
**Tiempo estimado:** 2-3 días
**Riesgo:** Bajo
**Impacto:** Imágenes más rápidas (CDN)

#### 3.1 Crear bucket en Supabase
```sql
-- Bucket para imágenes de trabajos
INSERT INTO storage.buckets (id, name, public)
VALUES ('job-images', 'job-images', true);

-- Políticas
CREATE POLICY "Public read access"
  ON storage.objects FOR SELECT
  USING (bucket_id = 'job-images');

CREATE POLICY "Authenticated upload"
  ON storage.objects FOR INSERT
  WITH CHECK (bucket_id = 'job-images' AND auth.role() = 'authenticated');
```

#### 3.2 Subir imágenes desde Android
```kotlin
suspend fun uploadImage(file: File): String {
    val bucket = supabase.storage["job-images"]
    val path = "jobs/${UUID.randomUUID()}.jpg"

    bucket.upload(path, file.readBytes())

    return bucket.publicUrl(path)
}
```

---

## Costos Estimados

| Servicio | Actual | Después |
|----------|--------|---------|
| Hostinger compartido | ~$4/mes | $0 (cancelar) |
| VPS | ~$5-10/mes | ~$5-10/mes |
| Supabase Free | $0 | $0 |
| **Total** | ~$9-14/mes | ~$5-10/mes |

### Límites Supabase Free
- 50,000 usuarios auth
- 1GB storage
- 2GB bandwidth
- 500MB database

**Suficiente hasta ~5,000 usuarios activos**

---

## Escalabilidad Futura

### Con 1,000+ usuarios
- [ ] Activar Supabase Pro ($25/mes) si necesitas más storage
- [ ] Redis cache en VPS para WordPress
- [ ] CDN (Cloudflare gratis) delante del VPS

### Con 10,000+ usuarios
- [ ] Separar WordPress a servidor dedicado
- [ ] PostgreSQL dedicado o Supabase Pro
- [ ] Considerar migrar trabajos de WordPress a Supabase

### Expansión del ecosistema
```
Posibles apps futuras (mismo auth):
├── AgroMarket - Compra/venta de productos
├── AgroCredito - Microcréditos para trabajadores
├── AgroCapacita - Cursos y certificaciones
└── AgroSeguro - Seguros para trabajadores
```

---

## Checklist de Migración

### Fase 1 - WordPress a VPS
- [ ] Backup completo de Hostinger
- [ ] Configurar VPS (Nginx + PHP + MySQL)
- [ ] Instalar WordPress
- [ ] Importar base de datos
- [ ] Copiar wp-content
- [ ] Configurar SSL
- [ ] Actualizar DNS
- [ ] Probar API REST
- [ ] Probar app Android
- [ ] Cancelar Hostinger (después de verificar)

### Fase 2 - Supabase Auth
- [ ] Crear proyecto Supabase (o usar existente)
- [ ] Configurar tabla profiles
- [ ] Agregar dependencia Supabase a Android
- [ ] Implementar nuevo AuthManager
- [ ] Modificar WordPress para validar JWT
- [ ] Migrar 39 usuarios existentes
- [ ] Notificar usuarios del cambio
- [ ] Probar login en ambas apps

### Fase 3 - Storage
- [ ] Crear bucket en Supabase
- [ ] Implementar upload en Android
- [ ] Migrar imágenes existentes
- [ ] Actualizar URLs en base de datos

---

## Contacto y Soporte

Documentación oficial:
- [Supabase Docs](https://supabase.com/docs)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)

---

*Documento creado: Enero 2026*
*Última actualización: Enero 2026*
