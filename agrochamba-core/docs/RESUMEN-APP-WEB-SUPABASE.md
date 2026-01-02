# 📋 Resumen Ejecutivo: App Web Agrochamba con Supabase

## 🎯 Objetivo

Crear app web moderna que use **Supabase Auth** como login único, sincronizada completamente con WordPress existente.

---

## ✅ Checklist Rápido

### Backend WordPress
- [ ] Instalar módulo `23-supabase-sync.php`
- [ ] Configurar variables: `agrochamba_supabase_url` y `agrochamba_supabase_anon_key`
- [ ] Endpoint `/sync/user` funcionando
- [ ] Endpoint `/auth/validate` funcionando
- [ ] Middleware aplicado a endpoints protegidos

### Supabase
- [ ] Proyecto creado
- [ ] Auth habilitado (Email/Password)
- [ ] Webhook `user.created` configurado → WordPress `/sync/user`
- [ ] Variables de entorno guardadas

### Frontend App Web
- [ ] Proyecto Next.js/React inicializado
- [ ] Supabase client configurado
- [ ] WordPress API client configurado
- [ ] Login/Register implementado
- [ ] Sincronización automática funcionando
- [ ] Creación de trabajos funcionando

---

## 🔑 Flujos Principales

### 1. Registro
```
Usuario → Supabase Auth → Webhook → WordPress → Usuario creado
```

### 2. Login
```
Usuario → Supabase Auth → Token JWT → WordPress valida → Sesión activa
```

### 3. Crear Trabajo
```
Usuario autenticado → POST /jobs con token Supabase → WordPress valida → Trabajo creado
```

---

## 📁 Archivos Clave

### Backend
- `agrochamba-core/modules/23-supabase-sync.php` - Sincronización
- `agrochamba-core/modules/06-endpoints-jobs.php` - Endpoints trabajos (modificar)

### Frontend
- `src/lib/supabase.ts` - Cliente Supabase
- `src/lib/wordpress.ts` - Cliente WordPress API
- `src/hooks/useAuth.ts` - Hook autenticación
- `src/hooks/useJobs.ts` - Hook trabajos

---

## 🔧 Configuración Mínima

### WordPress (wp-config.php o plugin)
```php
define('AGROCHAMBA_SUPABASE_URL', 'https://xxx.supabase.co');
define('AGROCHAMBA_SUPABASE_ANON_KEY', 'xxx');
```

### Frontend (.env.local)
```env
NEXT_PUBLIC_SUPABASE_URL=https://xxx.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=xxx
NEXT_PUBLIC_WORDPRESS_API_URL=https://agrochamba.com/wp-json/agrochamba/v1
```

---

## 🚨 Puntos Críticos

1. **Validación de tokens:** Cachear validaciones (5 min) para performance
2. **Sincronización:** Usuario debe existir en ambos sistemas
3. **Roles:** Mantener en WordPress, Supabase solo metadata
4. **Compatibilidad:** Sistema actual sigue funcionando durante migración

---

## 📚 Documentación Completa

- **Guía Técnica:** `GUIA-TECNICA-APP-WEB-SUPABASE.md`
- **Prompt Desarrollador:** `PROMPT-DESARROLLADOR-APP-WEB.md`
- **Ejemplos de Código:** `CODIGO-EJEMPLOS-APP-WEB.md`

---

## 🆘 Troubleshooting

**Error: "Token inválido"**
- Verificar Supabase URL y Anon Key
- Verificar que token no haya expirado
- Revisar logs de WordPress

**Error: "Usuario no encontrado"**
- Verificar sincronización automática
- Llamar manualmente `/sync/user`
- Revisar webhook de Supabase

**Error: "CORS"**
- Configurar CORS en WordPress
- Verificar dominio en configuración

---

**Versión:** 1.0.0 | **Última actualización:** 2025-01-XX

