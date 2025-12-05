# Arquitectura de la App Agrochamba

## 📐 Estructura del Proyecto

La app sigue **Clean Architecture** con **MVVM** y separación por **features**:

```
app/src/main/java/agrochamba/com/
├── data/                          # Capa de Datos
│   ├── remote/
│   │   └── firebase/              # Servicios Firebase (AuthService, FirestoreService, etc.)
│   ├── repository/                # Implementaciones de repositorios
│   │   ├── UserRepositoryImpl.kt
│   │   └── JobsRepositoryImpl.kt
│   └── [models]                   # Modelos de datos (JobPost, UserProfile, etc.)
│
├── domain/                        # Capa de Dominio (Lógica de Negocio)
│   ├── repository/                # Interfaces de repositorios
│   │   ├── UserRepository.kt
│   │   └── JobsRepository.kt
│   └── usecase/                   # Casos de uso
│       ├── auth/
│       │   ├── LoginUseCase.kt
│       │   ├── RegisterUserUseCase.kt
│       │   ├── RegisterCompanyUseCase.kt
│       │   └── SendPasswordResetUseCase.kt
│       └── jobs/
│           ├── CreateJobUseCase.kt
│           ├── UpdateJobUseCase.kt
│           └── DeleteJobUseCase.kt
│
├── ui/                            # Capa de Presentación
│   ├── auth/                      # Feature: Autenticación
│   │   ├── LoginScreen.kt
│   │   ├── LoginViewModel.kt
│   │   ├── RegisterScreen.kt
│   │   ├── RegisterViewModel.kt
│   │   └── ...
│   ├── jobs/                      # Feature: Trabajos
│   │   ├── JobsScreen.kt
│   │   ├── JobsViewModel.kt
│   │   ├── CreateJobScreen.kt
│   │   └── ...
│   └── common/                    # Componentes compartidos (futuro)
│
├── core/                          # Funcionalidades Core
│   ├── AuthManager.kt             # Gestión de sesión
│   └── navigation/                # Navegación
│
└── util/                          # Utilidades
    ├── Results.kt                 # Result wrapper
    └── FirebaseErrorMapper.kt     # Mapeo de errores
```

## 🏗️ Principios de Arquitectura

### 1. **Separación por Capas**

- **UI (Presentación)**: Solo muestra datos y captura eventos del usuario
- **Domain (Lógica de Negocio)**: Casos de uso y reglas de negocio
- **Data (Datos)**: Acceso a Firebase, APIs, almacenamiento local

### 2. **Flujo de Datos**

```
UI (Screen)
    ↓ llama a
ViewModel
    ↓ usa
UseCase (lógica de negocio)
    ↓ usa
Repository (interfaz)
    ↓ implementado por
RepositoryImpl (Firebase/API)
    ↓ accede a
Firebase/API
```

### 3. **Ventajas de esta Arquitectura**

✅ **Testeable**: Cada capa se puede probar independientemente
✅ **Mantenible**: Cambios en una capa no afectan otras
✅ **Escalable**: Fácil agregar nuevas features
✅ **Reutilizable**: Casos de uso se pueden usar en múltiples ViewModels
✅ **Desacoplado**: UI no conoce Firebase directamente

## 📦 Componentes Principales

### Repositorios

**Interfaz** (`domain/repository/`):
- Define qué operaciones se pueden hacer
- No depende de implementación específica

**Implementación** (`data/repository/`):
- Implementa la interfaz usando Firebase/API
- Maneja la conversión de datos

### Casos de Uso

Contienen la **lógica de negocio**:
- Validaciones
- Reglas de negocio
- Orquestación de operaciones

Ejemplo: `CreateJobUseCase` valida que el usuario sea empresa antes de crear un trabajo.

### ViewModels

- Preparan datos para la UI
- Manejan el estado de la pantalla
- Usan casos de uso (no servicios directamente)

## 🔐 Sistema de Roles

- **`worker`**: Usuario normal (trabajador)
- **`employer`**: Empresa
- **`administrator`**: Administrador

Los roles se almacenan en Firestore (`users/{uid}/roles`) y se cargan al hacer login.

## 🚀 Cómo Agregar una Nueva Feature

1. **Crear caso de uso** en `domain/usecase/[feature]/`
2. **Crear/actualizar repositorio** si es necesario
3. **Crear ViewModel** en `ui/[feature]/`
4. **Crear Screen** en `ui/[feature]/`
5. **Agregar ruta** en `MainActivity.kt`

## 📝 Notas Importantes

- Los ViewModels **NO** deben acceder directamente a Firebase
- La lógica de negocio va en **UseCases**, no en ViewModels
- Los repositorios abstraen la fuente de datos (Firebase, API, etc.)

## 🔌 Inyección de Dependencias (Hilt)

La app usa **Hilt** para inyección de dependencias, eliminando instanciaciones manuales:

### Módulos de Hilt

- **`FirebaseModule`**: Proporciona instancias de Firebase (Auth, Firestore, Storage)
- **`ServiceModule`**: Proporciona servicios de Firebase (AuthService, FirestoreService, etc.)
- **`RepositoryModule`**: Proporciona implementaciones de repositorios
- **`UseCaseModule`**: Proporciona casos de uso

### Uso en ViewModels

Los ViewModels usan `@HiltViewModel` y reciben dependencias por constructor:

```kotlin
@HiltViewModel
class LoginViewModel @Inject constructor(
    private val loginUseCase: LoginUseCase
) : ViewModel()
```

### Uso en Activities

Las Activities usan `@AndroidEntryPoint`:

```kotlin
@AndroidEntryPoint
class MainActivity : ComponentActivity()
```

### Application Class

La Application class usa `@HiltAndroidApp`:

```kotlin
@HiltAndroidApp
class AgrochambaApp : Application()
```

