# Guía de Testing - Agrochamba App

## 📋 Estructura de Tests

```
app/src/
├── test/                    # Unit Tests (JVM)
│   └── java/agrochamba/com/
│       ├── domain/
│       │   └── usecase/     # Tests de casos de uso
│       ├── data/
│       │   └── repository/  # Tests de repositorios
│       ├── ui/
│       │   └── auth/        # Tests de ViewModels
│       └── util/            # Tests de utilidades
│
└── androidTest/             # Instrumented Tests (Android)
    └── java/agrochamba/com/
        └── ui/              # Tests de UI con Compose
```

## 🧪 Tipos de Tests

### 1. Unit Tests (test/)

**Ubicación**: `app/src/test/`

**Para qué**: Tests rápidos que no requieren Android SDK

**Ejemplos**:
- Tests de UseCases
- Tests de ViewModels (con StateFlow)
- Tests de Repositories
- Tests de utilidades

**Ejecutar**:
```bash
./gradlew test
```

### 2. Instrumented Tests (androidTest/)

**Ubicación**: `app/src/androidTest/`

**Para qué**: Tests que requieren Android SDK o UI

**Ejemplos**:
- Tests de UI con Compose
- Tests de integración
- Tests que requieren contexto de Android

**Ejecutar**:
```bash
./gradlew connectedAndroidTest
```

## 🛠️ Dependencias de Testing

### Unit Testing
- **JUnit 4**: Framework de testing
- **MockK**: Mocking library para Kotlin
- **Turbine**: Testing de StateFlow y Flows
- **Coroutines Test**: Testing de coroutines
- **Hilt Testing**: Testing con inyección de dependencias

### UI Testing
- **Compose Testing**: Testing de componentes Compose
- **Espresso**: Testing de UI tradicional
- **Hilt Android Testing**: Testing con Hilt en Android

## 📝 Ejemplos de Tests

### Test de UseCase

```kotlin
class LoginUseCaseTest {
    @Test
    fun `login with valid credentials should succeed`() = runTest {
        // Given
        val loginUseCase = LoginUseCase(mockRepository)
        
        // When
        val result = loginUseCase("test@example.com", "password")
        
        // Then
        assertTrue(result is Result.Success)
    }
}
```

### Test de ViewModel con StateFlow

```kotlin
class LoginViewModelTest {
    @Test
    fun `login should update state correctly`() = runTest {
        val viewModel = LoginViewModel(mockUseCase)
        
        viewModel.uiState.test {
            viewModel.login("test@example.com", "password")
            
            val loadingState = awaitItem()
            assertTrue(loadingState.isLoading)
            
            val successState = awaitItem()
            assertTrue(successState.loginSuccess)
        }
    }
}
```

### Test de UI Component

```kotlin
class LoadingIndicatorTest {
    @get:Rule
    val composeTestRule = createComposeRule()
    
    @Test
    fun loadingIndicator_shouldBeDisplayed() {
        composeTestRule.setContent {
            LoadingIndicator()
        }
        
        composeTestRule.onNodeWithContentDescription("Loading")
            .assertIsDisplayed()
    }
}
```

## 🎯 Mejores Prácticas

### 1. Naming de Tests
- Usar nombres descriptivos: `login_withValidCredentials_shouldSucceed`
- Usar backticks para nombres legibles: `` `login with valid credentials should succeed` ``

### 2. Estructura AAA
- **Arrange**: Preparar datos y mocks
- **Act**: Ejecutar la acción a testear
- **Assert**: Verificar el resultado

### 3. Mocking
- Usar MockK para mocks
- Mockear dependencias externas (Firebase, APIs)
- No mockear el código que estás testeando

### 4. StateFlow Testing
- Usar Turbine para testing de Flows
- Testear estados intermedios (loading, success, error)
- Verificar que los estados se emiten en el orden correcto

### 5. Cobertura
- Apuntar a >70% de cobertura
- Priorizar lógica de negocio (UseCases)
- Testear casos edge y errores

## 🚀 Ejecutar Tests

### Todos los tests
```bash
./gradlew test connectedAndroidTest
```

### Solo unit tests
```bash
./gradlew test
```

### Solo instrumented tests
```bash
./gradlew connectedAndroidTest
```

### Tests específicos
```bash
./gradlew test --tests "LoginUseCaseTest"
```

### Con cobertura
```bash
./gradlew test jacocoTestReport
```

## 📊 Cobertura de Código

Para generar reporte de cobertura:

1. Agregar plugin JaCoCo en `build.gradle.kts`
2. Ejecutar: `./gradlew test jacocoTestReport`
3. Ver reporte en: `app/build/reports/jacoco/test/html/index.html`

## 🔍 Debugging Tests

### En Android Studio
1. Click derecho en el test
2. "Run 'TestName'"
3. Ver resultados en la ventana "Run"

### Con logs
```kotlin
@Test
fun testExample() {
    println("Debug message")
    // ...
}
```

## 📚 Recursos

- [Android Testing Guide](https://developer.android.com/training/testing)
- [Compose Testing](https://developer.android.com/jetpack/compose/testing)
- [MockK Documentation](https://mockk.io/)
- [Turbine Documentation](https://github.com/cashapp/turbine)

