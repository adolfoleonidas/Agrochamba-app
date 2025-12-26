# 🚀 Comandos Rápidos para Lanzamiento

## 1️⃣ Crear Keystore (Solo la primera vez)

```bash
# Desde la raíz del proyecto
keytool -genkey -v -keystore agrochamba-release.jks -keyalg RSA -keysize 2048 -validity 10000 -alias agrochamba
```

**Guarda las contraseñas de forma segura.**

## 2️⃣ Configurar key.properties

```bash
# Copiar el template
cp app/key.properties.template app/key.properties

# Editar con tus datos reales
# (Usa un editor de texto, NO subas este archivo a Git)
```

## 3️⃣ Generar AAB para Google Play Store

```bash
# Build de producción
./gradlew bundleProdRelease

# El archivo estará en:
# app/build/outputs/bundle/prodRelease/app-prod-release.aab
```

## 4️⃣ Generar APK para Testing

```bash
# APK firmado de producción
./gradlew assembleProdRelease

# El archivo estará en:
# app/build/outputs/apk/prod/release/app-prod-release.apk
```

## 5️⃣ Limpiar Builds Anteriores

```bash
./gradlew clean
```

## 6️⃣ Verificar Firma del AAB/APK

```bash
# Verificar AAB
jarsigner -verify -verbose -certs app/build/outputs/bundle/prodRelease/app-prod-release.aab

# Verificar APK
jarsigner -verify -verbose -certs app/build/outputs/apk/prod/release/app-prod-release.apk
```

## 7️⃣ Instalar APK en Dispositivo

```bash
# Conecta tu dispositivo y ejecuta:
adb install app/build/outputs/apk/prod/release/app-prod-release.apk
```

## 📝 Incrementar Versión

Antes de cada release, actualiza en `app/build.gradle.kts`:

```kotlin
versionCode = 2  // Incrementar en 1
versionName = "1.0.1"  // Versión semántica
```

## ⚠️ Troubleshooting

### Error: "Keystore file not found"
- Verifica que `agrochamba-release.jks` existe en la raíz del proyecto
- Verifica la ruta en `app/key.properties`

### Error: "Failed to read key"
- Verifica las contraseñas en `app/key.properties`
- Verifica que el alias es "agrochamba"

### Build falla sin keystore
- Esto es normal si no has creado el keystore aún
- Crea el keystore siguiendo el paso 1

