# 🚀 Guía de Lanzamiento Público - AgroChamba Android

## 📋 Checklist Pre-Lanzamiento

### ✅ 1. Configuración de Firma (Keystore)

**IMPORTANTE:** Necesitas crear un keystore para firmar tu aplicación. Este archivo es CRÍTICO y debes guardarlo de forma segura.

#### Crear el Keystore:

```bash
# En la raíz del proyecto Android
keytool -genkey -v -keystore agrochamba-release.jks -keyalg RSA -keysize 2048 -validity 10000 -alias agrochamba

# Te pedirá:
# - Contraseña del keystore (GUÁRDALA BIEN)
# - Información personal (nombre, organización, etc.)
# - Contraseña de la clave (puede ser la misma)
```

#### Configurar la firma en el proyecto:

1. Crear archivo `app/key.properties` (NO subir a Git):
```properties
storePassword=TU_CONTRASEÑA_DEL_KEYSTORE
keyPassword=TU_CONTRASEÑA_DE_LA_CLAVE
keyAlias=agrochamba
storeFile=../agrochamba-release.jks
```

2. Agregar al `.gitignore`:
```
app/key.properties
app/*.jks
*.jks
```

### ✅ 2. Actualizar build.gradle.kts para Firma

Agregar configuración de firma en `app/build.gradle.kts`:

```kotlin
android {
    // ... código existente ...
    
    signingConfigs {
        create("release") {
            val keystorePropertiesFile = rootProject.file("app/key.properties")
            if (keystorePropertiesFile.exists()) {
                val keystoreProperties = java.util.Properties()
                keystoreProperties.load(java.io.FileInputStream(keystorePropertiesFile))
                
                storeFile = file(keystoreProperties["storeFile"] as String)
                storePassword = keystoreProperties["storePassword"] as String
                keyAlias = keystoreProperties["keyAlias"] as String
                keyPassword = keystoreProperties["keyPassword"] as String
            }
        }
    }
    
    buildTypes {
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            signingConfig = signingConfigs.getByName("release")
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
        // ... resto del código ...
    }
}
```

### ✅ 3. Verificar Configuración de Producción

#### URLs y Endpoints:
- ✅ BASE_URL: `https://agrochamba.com/wp-json/` (correcto)
- ✅ WEB_ROUTES_URL: `https://agrobus.agrochamba.com/`
- ✅ PRIVACY_URL: `https://agrochamba.com/politica-de-privacidad/`

#### Versión de la App:
- **Version Code:** 1 (incrementar en cada release)
- **Version Name:** 1.0.0

### ✅ 4. Verificaciones Finales

#### AndroidManifest.xml:
- ✅ Permisos correctos
- ✅ `usesCleartextTraffic="false"` (solo HTTPS)
- ✅ Network Security Config configurado

#### ProGuard:
- ✅ Reglas configuradas para Retrofit, Moshi, Coroutines
- ✅ Minificación y shrink resources habilitados

### ✅ 5. Generar AAB (Android App Bundle) para Google Play

```bash
# Desde la raíz del proyecto
./gradlew bundleProdRelease

# El archivo estará en:
# app/build/outputs/bundle/prodRelease/app-prod-release.aab
```

### ✅ 6. Generar APK para Testing Interno

```bash
# Generar APK firmado
./gradlew assembleProdRelease

# El archivo estará en:
# app/build/outputs/apk/prod/release/app-prod-release.apk
```

### ✅ 7. Preparación para Google Play Store

#### Información Necesaria:

1. **Título de la App:** AgroChamba
2. **Descripción Corta:** Conecta trabajadores agrícolas con oportunidades laborales
3. **Descripción Completa:** (Preparar descripción detallada)
4. **Categoría:** Empleo / Trabajo
5. **Clasificación de Contenido:** PEGI 3 / Everyone
6. **Icono:** 512x512 px (PNG)
7. **Capturas de Pantalla:**
   - Mínimo 2, máximo 8
   - Teléfono: 16:9 o 9:16, mínimo 320px
   - Tablet: 16:9 o 9:16, mínimo 320px
8. **Imagen Promocional:** 1024x500 px (opcional)
9. **Política de Privacidad:** URL requerida

#### Requisitos de Contenido:

- ✅ Política de Privacidad publicada
- ✅ Términos de Servicio (recomendado)
- ✅ Información de contacto del desarrollador

### ✅ 8. Proceso de Publicación en Google Play

1. **Crear cuenta de Desarrollador:**
   - Ir a https://play.google.com/console
   - Pagar tarifa única de $25 USD

2. **Crear Nueva App:**
   - Nombre: AgroChamba
   - Idioma predeterminado: Español
   - Tipo de app: App
   - Gratis o de pago: Gratis

3. **Completar Información de la Tienda:**
   - Título, descripción, icono, capturas
   - Categoría y clasificación
   - Política de privacidad

4. **Subir AAB:**
   - Ir a "Producción" > "Crear nueva versión"
   - Subir el archivo `.aab` generado
   - Agregar notas de la versión

5. **Revisar y Publicar:**
   - Revisar toda la información
   - Enviar para revisión
   - Tiempo de revisión: 1-7 días

### ✅ 9. Testing Pre-Lanzamiento

#### Checklist de Testing:

- [ ] Login funciona correctamente
- [ ] Registro de usuarios funciona
- [ ] Registro de empresas funciona
- [ ] Recuperación de contraseña funciona
- [ ] Listado de trabajos carga correctamente
- [ ] Búsqueda y filtros funcionan
- [ ] Detalles de trabajo se muestran correctamente
- [ ] Compartir trabajos funciona
- [ ] Favoritos y guardados funcionan
- [ ] Perfil de usuario funciona
- [ ] Crear/editar trabajos (para empresas) funciona
- [ ] Imágenes se cargan correctamente
- [ ] Sin crashes en diferentes dispositivos
- [ ] Performance aceptable

### ✅ 10. Monitoreo Post-Lanzamiento

#### Herramientas Recomendadas:

1. **Firebase Crashlytics** (opcional pero recomendado)
2. **Google Play Console Analytics**
3. **Monitoreo de errores del servidor**

## 🔒 Seguridad

### Archivos que NO deben subirse a Git:

```
app/key.properties
*.jks
*.keystore
app/keystore/
```

### Backup del Keystore:

**CRÍTICO:** Guarda el keystore en múltiples lugares seguros:
- Disco duro externo
- Servicio de almacenamiento en la nube (encriptado)
- Impresión física guardada en lugar seguro

**Si pierdes el keystore, NO podrás actualizar tu app en Google Play.**

## 📝 Notas Importantes

1. **Version Code:** Debe incrementarse en cada release (1, 2, 3, ...)
2. **Version Name:** Puede ser semántica (1.0.0, 1.0.1, 1.1.0, etc.)
3. **ProGuard:** Ya está configurado, pero revisa los logs por advertencias
4. **Testing:** Prueba en diferentes dispositivos y versiones de Android
5. **Política de Privacidad:** Debe estar accesible públicamente

## 🆘 Troubleshooting

### Error: "Keystore file not found"
- Verifica que `key.properties` existe y tiene la ruta correcta
- Verifica que el archivo `.jks` existe en la ubicación especificada

### Error: "Failed to read key"
- Verifica las contraseñas en `key.properties`
- Verifica que el alias es correcto

### Error al subir AAB: "App not signed"
- Verifica que el build type `release` tiene `signingConfig` configurado
- Verifica que el keystore está correctamente configurado

## 📞 Soporte

Para problemas durante el proceso de lanzamiento, revisa:
- Documentación oficial de Android: https://developer.android.com/studio/publish
- Google Play Console Help: https://support.google.com/googleplay/android-developer

