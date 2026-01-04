# 🔧 Solución de Errores de Build

## Error: "Keystore file not found" o "Failed to read key"

### Solución 1: Build sin Keystore (para testing)

Si aún no has creado el keystore, puedes construir un APK sin firma para testing:

```bash
# Build debug (no requiere keystore)
./gradlew assembleProdDebug

# O build release sin firma (si el keystore no está configurado, se construirá sin firma)
./gradlew assembleProdRelease
```

**Nota:** El APK sin firma NO puede subirse a Google Play Store, pero sirve para testing interno.

### Solución 2: Configurar Keystore

1. **Crear el keystore:**
```bash
keytool -genkey -v -keystore agrochamba-release.jks -keyalg RSA -keysize 2048 -validity 10000 -alias agrochamba
```

2. **Crear key.properties:**
```bash
# Copiar template
cp app/key.properties.template app/key.properties

# Editar app/key.properties con tus datos:
storePassword=TU_CONTRASEÑA
keyPassword=TU_CONTRASEÑA
keyAlias=agrochamba
storeFile=../agrochamba-release.jks
```

3. **Verificar que el archivo existe:**
- `agrochamba-release.jks` debe estar en la raíz del proyecto
- `app/key.properties` debe existir

## Error: "SDK 36 not found" o "compileSdk 36"

Si no tienes Android SDK 36 instalado:

1. **Opción A:** Instalar SDK 36 desde Android Studio
   - Tools > SDK Manager > SDK Platforms > Android 15.0 (API 36)

2. **Opción B:** Cambiar a SDK 35 (más común)
   - Editar `app/build.gradle.kts`:
   ```kotlin
   compileSdk = 35
   targetSdk = 35
   ```

## Error: "Gradle sync failed"

1. **Limpiar proyecto:**
```bash
./gradlew clean
```

2. **Invalidar caché en Android Studio:**
   - File > Invalidate Caches / Restart

3. **Sincronizar proyecto:**
   - File > Sync Project with Gradle Files

## Error: "Build failed" sin mensaje claro

1. **Ver logs detallados:**
```bash
./gradlew assembleProdRelease --stacktrace --info
```

2. **Revisar en Android Studio:**
   - Build > View > Build Output
   - Buscar líneas rojas con "ERROR"

## Error: "Duplicate class" o conflictos de dependencias

1. **Verificar dependencias duplicadas:**
```bash
./gradlew app:dependencies > dependencies.txt
```

2. **Limpiar y reconstruir:**
```bash
./gradlew clean
./gradlew assembleProdRelease
```

## Error: ProGuard/R8 "Cannot find symbol"

1. **Verificar reglas de ProGuard:**
   - Revisar `app/proguard-rules.pro`
   - Agregar reglas para clases faltantes

2. **Deshabilitar minificación temporalmente (solo para debug):**
   - En `build.gradle.kts`, cambiar:
   ```kotlin
   release {
       isMinifyEnabled = false  // Temporalmente
       // ...
   }
   ```

## Build exitoso pero APK no funciona

1. **Verificar que el APK está firmado:**
```bash
jarsigner -verify -verbose -certs app/build/outputs/apk/prod/release/app-prod-release.apk
```

2. **Instalar en dispositivo:**
```bash
adb install app/build/outputs/apk/prod/release/app-prod-release.apk
```

## Comandos útiles de diagnóstico

```bash
# Ver versión de Gradle
./gradlew --version

# Ver configuración del proyecto
./gradlew tasks

# Limpiar todo
./gradlew clean

# Build con información detallada
./gradlew assembleProdRelease --info --stacktrace

# Ver dependencias
./gradlew app:dependencies
```

## Verificar configuración

### Checklist rápido:

- [ ] `compileSdk` está instalado (SDK Manager)
- [ ] `key.properties` existe (si quieres firmar)
- [ ] `agrochamba-release.jks` existe (si quieres firmar)
- [ ] Rutas en `key.properties` son correctas
- [ ] Contraseñas en `key.properties` son correctas
- [ ] Internet conectado (para descargar dependencias)
- [ ] Proyecto sincronizado (Sync Project)

## Si nada funciona

1. **Backup del proyecto**
2. **Clonar proyecto limpio desde Git**
3. **Sincronizar dependencias:**
   ```bash
   ./gradlew --refresh-dependencies
   ```
4. **Limpiar y reconstruir:**
   ```bash
   ./gradlew clean build
   ```

