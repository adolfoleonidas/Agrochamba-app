# Android + Firebase: Guía de Mejores Prácticas (Agrochamba)

Este documento es una “caja de pases” para recordar y aplicar buenas prácticas al construir la app con Firebase como backend principal y con posibilidad de publicar/opcionalmente replicar a WordPress. Úsalo como checklist continuo.

---

## 0) Objetivos
- Escalable y extensible (future‑proof) con capas y contratos claros.
- Seguridad por defecto (principio de mínimo privilegio).
- Observabilidad (crashes, rendimiento, logs) desde el día 1.
- Preparado para multi‑ambiente (dev/stage/prod) y feature flags.
- Pruebas locales con Emulator Suite y CI.

---

## 1) Estructura y Arquitectura
- Patrón: MVVM + Repositorios + UseCases (opcional) + DI (Hilt/Koin).
- Dependencia hacia interfaces, no implementaciones:
  - `AuthRepository`, `JobsRepository`, `MediaRepository`, `FavoritesRepository`.
  - Implementaciones enchufables (Firebase hoy; otros destinos mañana).
- UI con Jetpack Compose:
  - State hoisting; modelos inmutables; `remember` y `derivedStateOf` para costos altos.
  - Navegación con `Navigation-Compose` y rutas tipadas.
  - Evitar trabajo pesado en Composables; delegar a ViewModel.
- Flujo de datos:
  - `Flow`/`StateFlow` para streams (FireStore listeners -> mapeados a `Flow`).
  - `WorkManager` para tareas diferidas/retries (subidas, sincronizaciones).
- Errores/Resultados:
  - Usar `Result<T>` o selladas (`sealed class`) para estados: Loading/Success/Error/Empty.

---

## 2) Configuración Firebase (Gradle y Proyecto)
- Un proyecto por ambiente: `agrochamba-dev`, `agrochamba-prod`.
- Flavors en Android: `dev`, `prod` con `applicationIdSuffix` y `versionNameSuffix`.
- `google-services.json` por flavor (colocar en `app/src/dev/` y `app/src/prod/`).
- Gestionar dependencias con BoM:
  ```kotlin
  dependencies {
      implementation(platform("com.google.firebase:firebase-bom:33.6.0"))
      implementation("com.google.firebase:firebase-auth-ktx")
      implementation("com.google.firebase:firebase-firestore-ktx")
      implementation("com.google.firebase:firebase-storage-ktx")
      implementation("com.google.firebase:firebase-config-ktx")
      implementation("com.google.firebase:firebase-analytics-ktx")
      implementation("com.google.firebase:firebase-crashlytics-ktx")
      implementation("com.google.firebase:firebase-perf-ktx")
  }
  ```
- Aplicar plugins: `com.google.gms.google-services`, `com.google.firebase.crashlytics`, `com.google.firebase.firebase-perf` solo en `release`/`beta` según convenga.

---

## 3) Firestore: Modelo y Acceso
- Diseñar colecciones explícitas (ejemplo sugerido):
  - `users/{uid}` perfiles y roles.
  - `companies/{companyId}`.
  - `jobs/{jobId}` con `publishToWp`, `status`, `ownerUid`, `createdAt`, `updatedAt`.
  - `jobs_meta/{jobId}`: `wp_post_id`, `syncStatus`, `lastSyncedAt`, `lastError` (solo Functions/Admin).
  - `favorites/{uid}/items/{jobId}`.
- Indexación:
  - Consultas deben estar respaldadas por índices compuestos si requieren `where` + `orderBy`.
  - Paginación con cursores (`startAfter`) y límites (`limit`).
- Lectores eficientes:
  - Evitar escuchar colecciones enormes; segmentar por filtros (`where status == 'published'`).
  - Limitar campos con `select` cuando sea posible (en Cloud Functions/REST). En SDK móvil, mapear modelos ligeros.
- Escrituras seguras:
  - Preferir `runTransaction`/`writeBatch` para consistencia.
  - Timestamps con `FieldValue.serverTimestamp()`.

---

## 4) Reglas de Seguridad (borrador de intención)
- Firestore (idea base):
  - `jobs` lectura pública solo cuando `status == 'published'`.
  - Escritura de `jobs` restringida al dueño o rol `company_admin`.
  - `jobs_meta` y `config/*` accesibles solo por admin/Functions.
- Storage:
  - Carpetas por usuario/empresa: validar `request.auth.uid` y MIME type.
  - Redimensionar/optimizar imágenes en backend (Cloud Functions) si se requiere.
- Mantener las reglas versionadas junto al código y testearlas con Emulator Suite.

---

## 5) Feature Flags y Multi‑ambiente
- Flags globales con Remote Config (por ambiente): p. ej. `publishToWp`.
- Flags por ítem en Firestore: campo boolean `publishToWp` en `jobs`.
- Build Variants: `dev` usa Emulators y logging detallado; `prod` minimiza logs.

---

## 6) Sincronización con WordPress (opcional y desacoplada)
- Replicación vía Cloud Functions `onWrite(jobs/{jobId})`.
- Guardar mapeo `wp_post_id` en `jobs_meta` y estados `syncStatus`.
- Autenticación con WP mediante Secret Manager (`WP_BASE_URL`, `WP_JWT` o App Passwords).
- Retries con backoff exponencial; idempotencia.

---

## 7) Observabilidad y Trazabilidad
- Crashlytics: agregar claves (userId, companyId) con cuidado de privacidad.
- Analytics: eventos clave (login, create_job, publish_toggle, upload_image, search_job).
- Performance Monitoring: habilitar en `release`; usar trazas personalizadas para operaciones críticas.
- Logging:
  - Nivel `DEBUG` en dev; redactar tokens (Authorization). 
  - Evitar logs sensibles en `release`.

---

## 8) Calidad: Testing y Emuladores
- Unit tests: ViewModels, UseCases, mapeos (DTO ↔ dominio).
- Instrumentation tests: navegación y estados UI críticos.
- Firebase Emulator Suite: Auth, Firestore, Functions, Storage para pruebas locales end‑to‑end.
- Fakes/Mocks: usar `MockK`/`Turbine` para `Flow`.

---

## 9) Rendimiento, Tamaño y UX
- Imágenes: compresión, tamaños adecuados; `coil`/`Glide` con caching.
- Evitar recomposiciones: claves estables, `@Stable`/`@Immutable` cuando aplique.
- WorkManager para cargas de fondo y reintentos.
- StrictMode en debug; LeakCanary para fugas.
- Accesibilidad: contrastes, tamaños, `contentDescription`, navegación con lector de pantalla.

---

## 10) Seguridad y Privacidad
- No exponer claves/tokens en cliente. 
- Mínimos scopes de lectura/escritura en reglas.
- Pantalla y política de privacidad alineadas con Play Store (archivo `Data safety`).
- Borrar/anonimizar datos cuando el usuario lo solicite.

---

## 11) CI/CD (sugerido)
- Lint/format: `ktlint`/`detekt` en CI.
- Build matrix: `dev` (debuggable, emulators) / `release` (signing, shrinker, R8).
- Tests unit e instrumentación (instrumentación puede ser por lotes o en Firebase Test Lab).
- Distribución interna (Firebase App Distribution) para QA.

---

## 12) Checklist operativo (rápido)
- [ ] Flavors `dev`/`prod` y `google-services.json` correctos.
- [ ] BoM Firebase y plugins aplicados (Auth, Firestore, Storage, Remote Config, Crashlytics, Perf).
- [ ] Reglas Firestore/Storage mínimas y probadas con Emulator.
- [ ] Interfaces de repositorios definidas y enlazadas por DI.
- [ ] `WorkManager` configurado para tareas de subida/sync.
- [ ] Remote Config: flag `publishToWp` definido por ambiente.
- [ ] Analytics eventos mínimos y Crashlytics habilitado.
- [ ] Estrategia de logs segura (redacción de credenciales) y niveles por build type.
- [ ] Pruebas unitarias clave y al menos 1 flujo E2E con Emulator.
- [ ] Política de privacidad y ficha “Data safety” actualizadas.

---

## 13) Snippets útiles

### Inyección de Repos (Hilt, ejemplo mínimo)
```kotlin
@Module
@InstallIn(SingletonComponent::class)
object RepositoryModule {
    @Provides @Singleton
    fun provideJobsRepository(): JobsRepository = FirebaseJobsRepository()
}
```

### Remote Config (leer flag global)
```kotlin
val rc = Firebase.remoteConfig
rc.setConfigSettingsAsync(remoteConfigSettings { minimumFetchIntervalInSeconds = 3600 })
rc.setDefaultsAsync(mapOf("publishToWp" to false))
rc.fetchAndActivate().await()
val publishToWp = rc.getBoolean("publishToWp")
```

### Firestore (timestamps y owner)
```kotlin
val data = mapOf(
    "title" to job.title,
    "status" to job.status,
    "ownerUid" to uid,
    "createdAt" to FieldValue.serverTimestamp(),
    "updatedAt" to FieldValue.serverTimestamp(),
    "publishToWp" to job.publishToWp
)
db.collection("jobs").document(jobId).set(data, SetOptions.merge()).await()
```

---

## 14) Referencias
- Guías oficiales Firebase Android: https://firebase.google.com/docs/android/setup
- Reglas de seguridad: https://firebase.google.com/docs/rules
- Emulator Suite: https://firebase.google.com/docs/emulator-suite
- Arquitectura Android (MAD): https://developer.android.com/jetpack/guide
- Compose performance: https://developer.android.com/jetpack/compose/performance

---

Mantén este documento vivo: ajusta colecciones, reglas y módulos a medida que crezca el proyecto. La meta es poder “activar nuevas líneas” sin volver a cablear todo.
