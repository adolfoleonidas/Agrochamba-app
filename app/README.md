# AgroChamba - App Android

Aplicación Android para la plataforma AgroChamba, desarrollada con Kotlin y Jetpack Compose.

## 🏗️ Arquitectura

- **Lenguaje**: Kotlin
- **UI**: Jetpack Compose
- **Arquitectura**: MVVM (Model-View-ViewModel)
- **Networking**: Retrofit + Moshi
- **Navegación**: Navigation Compose
- **Estado**: StateFlow / MutableState

## 📁 Estructura del Proyecto

```
app/src/main/java/agrochamba/com/
├── data/              # Modelos de datos y servicios API
│   ├── AuthData.kt
│   ├── AuthManager.kt
│   ├── WordPressApiService.kt
│   └── ...
├── ui/                # Interfaces de usuario
│   ├── auth/          # Pantallas de autenticación
│   ├── jobs/          # Pantallas de trabajos
│   └── theme/         # Tema de la aplicación
├── utils/             # Utilidades
└── MainActivity.kt    # Actividad principal
```

## 🚀 Funcionalidades

### Autenticación
- ✅ Login (username o email)
- ✅ Registro de trabajadores
- ✅ Registro de empresas
- ✅ Recuperación de contraseña
- ✅ Gestión de sesión (AuthManager)

### Trabajos
- ✅ Listar trabajos disponibles
- ✅ Ver detalle de trabajo
- ✅ Crear trabajo (empresas)
- ✅ Editar trabajo (empresas)
- ✅ Mis trabajos publicados
- ✅ Subir imágenes

### Perfiles
- ✅ Perfil de usuario
- ✅ Perfil de empresa
- ✅ Editar perfil
- ✅ Foto de perfil

### Otros
- ✅ Sistema de favoritos
- ✅ Trabajos guardados
- ✅ Búsqueda y filtros

## 🔧 Configuración

### Base URL
La URL base de la API está configurada en `WordPressApiService.kt`:
```kotlin
private const val BASE_URL = "https://agrochamba.com/wp-json/"
```

### Autenticación
La app usa JWT tokens para autenticación. Los tokens se almacenan en `SharedPreferences` a través de `AuthManager`.

## 📱 Requisitos

- **Min SDK**: 24 (Android 7.0)
- **Target SDK**: 36
- **Compile SDK**: 36

## 🛠️ Dependencias Principales

- Jetpack Compose
- Retrofit (API calls)
- Moshi (JSON parsing)
- Navigation Compose
- ViewModel
- Coroutines

## 📝 Notas de Desarrollo

- El proyecto usa Kotlin DSL para Gradle
- La arquitectura sigue el patrón MVVM
- El estado se maneja con `StateFlow` y `MutableState`
- Las llamadas a la API son asíncronas usando Coroutines

