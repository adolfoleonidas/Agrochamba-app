# 📋 Plan de Reorganización AgroChamba Core

## ⚠️ Estado Actual vs. Objetivo

### Problemas Actuales:
1. ✅ **Archivos sueltos en raíz** - Necesitan organización
2. ✅ **Sin autoloader moderno** - Requires manual
3. ✅ **Nomenclatura inconsistente** - Dificulta mantenimiento
4. ✅ **Sin separación clara** - Todo mezclado

### Solución Implementada:
He creado la estructura base profesional con:
- ✅ Carpetas organizadas (`src/`, `config/`, `includes/`, etc.)
- ✅ Autoloader PSR-4 funcional
- ✅ ModuleLoader centralizado
- ✅ Constantes globales
- ✅ Bootstrap system

## 🎯 Siguiente Fase: Migración Gradual

### Opción Recomendada: **Migración Híbrida**

En lugar de reescribir todo ahora (riesgo alto), propongo:

1. **Mantener módulos actuales funcionando**
2. **Cargarlos desde nueva estructura**
3. **Migrar gradualmente uno por uno**

### Implementación:

```php
// agrochamba-core.php (nuevo)
require_once __DIR__ . '/config/bootstrap.php';

// Cargar módulos actuales (compatibilidad)
require_once __DIR__ . '/modules/00-security-cors.php';
require_once __DIR__ . '/modules/01-cpt-taxonomies.php';
// ... etc

// Nuevos módulos (cuando estén listos)
// \AgroChamba\Core\ModuleLoader::init();
```

## 📦 Estructura Creada:

```
agrochamba-core/
├── config/
│   ├── bootstrap.php          ✅ Creado
│   └── constants.php           ✅ Creado
├── src/
│   └── Core/
│       ├── Autoloader.php     ✅ Creado
│       ├── ModuleLoader.php   ✅ Creado
│       └── PluginActivator.php ✅ Creado
├── includes/                   ✅ Carpeta creada
├── templates/                  ✅ Carpeta creada
├── assets/                     ✅ Carpeta creada
├── tests/                      ✅ Carpeta creada
└── docs/                       ✅ Carpeta creada
```

## 🔄 Próximos Pasos:

### Fase 1: Limpieza (AHORA)
- [x] Mover archivos sueltos a carpetas correctas
- [x] Eliminar archivos obsoletos (el módulo 02 fue reemplazado por `includes/hooks.php`)
- [x] Mover documentación a `/docs`
- [x] Actualizar archivo principal (usa `config/bootstrap.php` y mantiene compatibilidad con `/modules`)

### Fase 2: Migración Gradual (FUTURO)
- [x] Migrar módulo de seguridad a namespace
- [x] Migrar servicios (cache, logger, etc.)
- [x] Migrar endpoints API (Jobs e Images)
- [x] Migrar Auth a namespace (shim en modules/03)
- [x] Migrar Favoritos a namespace (shim en modules/08)
- [x] Migrar Perfil (usuario/empresa) a namespace (shims en modules/04 y /05)
- [x] Actualizar tests

### Fase 3: Optimización (FUTURO)
- [x] Implementar Composer
- [x] Tests automatizados
- [x] CI/CD (GitHub Actions básico con PHP 7.4/8.0/8.1)

## ✅ Recomendación Final:

**Completar Fase 1 ahora:** Organizar archivos sin romper funcionalidad.
**Fase 2 y 3:** Implementar gradualmente en siguientes sprints.

¿Procedo con Fase 1 (limpieza y organización)?
