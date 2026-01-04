# 📱 Permisos de la App AgroChamba Android

Este documento detalla todos los permisos que utiliza la aplicación AgroChamba Android y su propósito.

## 🔐 Permisos Declarados en AndroidManifest.xml

### 1. INTERNET y RED
```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
```

**Propósito:**
- Conectarse a la API de WordPress (`https://agrochamba.com/wp-json/`)
- Cargar imágenes y contenido desde servidores remotos
- Acceder a WebViews (Rutas, Fechas, Cuartos)
- Comunicación con el backend para todas las funcionalidades
- Detectar estado de la conexión de red

**Tipo:** Permiso normal (se otorga automáticamente al instalar)
**Requisito:** ✅ Obligatorio - La app no funciona sin conexión a internet

---

### 2. GEOLOCALIZACIÓN (GPS)
```xml
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
```

**Propósito:**
- **AgroBus:** Mostrar ubicación del usuario en el mapa de rutas
- **AgroBus:** Calcular distancia al bus en tiempo real
- **Cuartos:** Mostrar alojamientos cercanos
- Permitir que las WebViews accedan a la ubicación del dispositivo

**Tipo:** Permiso peligroso (se solicita en tiempo de ejecución)
**Requisito:** ⚠️ Opcional - La app funciona sin ubicación, pero algunas funciones de mapas estarán limitadas

**Cuándo se solicita:**
- Cuando una WebView (como AgroBus) solicita acceso a `navigator.geolocation`
- El usuario puede aceptar o rechazar

---

### 3. CÁMARA
```xml
<uses-permission android:name="android.permission.CAMERA" />
<uses-feature android:name="android.hardware.camera" android:required="false" />
<uses-feature android:name="android.hardware.camera.autofocus" android:required="false" />
```

**Propósito:**
- Capturar fotos directamente para subir a formularios web
- Permitir que las WebViews usen `<input type="file" capture="camera">`
- Subir imágenes a perfiles o publicaciones de trabajo

**Tipo:** Permiso peligroso (se solicita en tiempo de ejecución)
**Requisito:** ⚠️ Opcional - Se puede usar el selector de galería en su lugar

**Nota:** `android:required="false"` significa que la app puede instalarse en dispositivos sin cámara.

---

### 4. MICRÓFONO (Audio)
```xml
<uses-permission android:name="android.permission.RECORD_AUDIO" />
<uses-feature android:name="android.hardware.microphone" android:required="false" />
```

**Propósito:**
- Soporte para WebRTC (videollamadas futuras)
- Permitir que las WebViews accedan al micrófono si es necesario
- Mensajes de voz (funcionalidad futura)

**Tipo:** Permiso peligroso (se solicita en tiempo de ejecución)
**Requisito:** ⚠️ Opcional - No se usa actualmente, preparado para futuro

---

### 5. ALMACENAMIENTO (Solo Android 9 y anteriores)
```xml
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" 
    android:maxSdkVersion="28"
    tools:ignore="ScopedStorage" />
```

**Propósito:**
- Descargar archivos (PDFs, documentos) en Android 9 y anteriores
- En Android 10+ se usa Scoped Storage y no se necesita este permiso

**Tipo:** Permiso peligroso (solo en Android 9 y anteriores)
**Requisito:** ⚠️ Opcional - Solo para funcionalidad de descargas en dispositivos antiguos

---

## 📸 Permisos para Selección de Imágenes

### Android 13+ (API 33+)
**No se requieren permisos adicionales**

La app utiliza `ActivityResultContracts.GetMultipleContents()` que aprovecha el **Photo Picker del sistema** introducido en Android 13. Este sistema:
- ✅ No requiere permisos de almacenamiento
- ✅ Proporciona una interfaz segura y moderna
- ✅ Respeta la privacidad del usuario
- ✅ Funciona con `contentResolver.openInputStream()` sin permisos adicionales

### Android 12 y anteriores (API 24-32)
El sistema puede solicitar permisos automáticamente cuando se usa el selector de archivos.

---

## 📋 Resumen de Permisos por Funcionalidad

| Funcionalidad | Permisos Requeridos | Versión Android | Estado |
|--------------|---------------------|-----------------|--------|
| Conexión a Internet | `INTERNET` | Todas | ✅ Implementado |
| Estado de red | `ACCESS_NETWORK_STATE` | Todas | ✅ Implementado |
| Mapas en AgroBus | `ACCESS_FINE_LOCATION` | Todas | ✅ Implementado |
| Subir fotos con cámara | `CAMERA` | Todas | ✅ Implementado |
| WebRTC (futuro) | `RECORD_AUDIO` | Todas | ✅ Implementado |
| Descargar archivos | `WRITE_EXTERNAL_STORAGE` | Android 9- | ✅ Implementado |
| Seleccionar imágenes | Ninguno (Photo Picker) | Android 13+ | ✅ Implementado |

---

## 🌐 Funcionalidades del WebView Completo

El WebView de la app ahora soporta:

### ✅ JavaScript y DOM
- `javaScriptEnabled = true` - Ejecutar JavaScript
- `domStorageEnabled = true` - localStorage y sessionStorage (necesario para Supabase)
- `databaseEnabled = true` - Web SQL Database

### ✅ Geolocalización
- `setGeolocationEnabled(true)` - Soporte para GPS
- Manejo de `onGeolocationPermissionsShowPrompt()` con solicitud de permisos

### ✅ Subida de Archivos
- `onShowFileChooser()` - Selector de archivos del sistema
- Soporte para captura de cámara (`capture="camera"`)
- Múltiples tipos de archivos (imágenes, videos, PDFs)

### ✅ Descargas
- `DownloadListener` - Descargar PDFs, documentos, etc.
- Notificación cuando la descarga completa

### ✅ Mixed Content
- `MIXED_CONTENT_COMPATIBILITY_MODE` - Permitir recursos HTTP en páginas HTTPS

### ✅ Zoom y Accesibilidad
- `setSupportZoom(true)` - Zoom con gestos
- `builtInZoomControls = true` - Controles de zoom
- `loadWithOverviewMode = true` - Ajustar contenido al ancho

### ✅ Cookies
- `setAcceptCookie(true)` - Cookies propias
- `setAcceptThirdPartyCookies(true)` - Cookies de terceros

### ✅ Hardware Acceleration
- `LAYER_TYPE_HARDWARE` - Renderizado por GPU
- `hardwareAccelerated="true"` en Manifest - Mejor rendimiento

### ✅ Múltiples Ventanas
- `setSupportMultipleWindows(true)` - Popups y target="_blank"
- `onCreateWindow()` - Manejar nuevas ventanas

### ✅ Enlaces Externos
- Abrir `tel:`, `mailto:`, `whatsapp:` en apps nativas
- Google Maps links en la app de mapas

### ✅ User Agent Personalizado
- Identifica la app: `"... AgroChambaApp/1.0"`

---

## 📋 Matriz de Permisos por WebView

| WebView | Ubicación | Cámara | Micrófono | Descargas |
|---------|-----------|--------|-----------|-----------|
| Rutas (agrobus) | ✅ Sí | ❌ No | ❌ No | ❌ No |
| Fechas | ❌ No | ❌ No | ❌ No | ❌ No |
| Cuartos | ✅ Posible | ✅ Posible | ❌ No | ✅ Posible |
| Política | ❌ No | ❌ No | ❌ No | ✅ Posible |

---

## 🔒 Privacidad y Seguridad

### Buenas Prácticas Implementadas

1. **Permisos bajo demanda**
   - Los permisos peligrosos se solicitan SOLO cuando se necesitan
   - El usuario puede rechazar sin afectar otras funcionalidades

2. **Photo Picker moderno**
   - Uso del sistema Photo Picker en Android 13+
   - No requiere acceso completo al almacenamiento
   - El usuario controla qué imágenes compartir

3. **Comunicación segura**
   - `usesCleartextTraffic="false"` - Solo conexiones HTTPS
   - `networkSecurityConfig` configurado para seguridad

4. **Features opcionales**
   - Cámara y micrófono marcados como `required="false"`
   - La app puede instalarse en dispositivos sin estas características

5. **Debugging seguro**
   - `WebContentsDebuggingEnabled` solo en modo desarrollo

---

## 📝 Declaración para Google Play Store

Al publicar en Google Play Store, deberás declarar:

### Permisos Declarados:
- ✅ **INTERNET** - Para conectarse a internet
- ✅ **ACCESS_NETWORK_STATE** - Para detectar estado de conexión
- ✅ **ACCESS_FINE_LOCATION** - Para funciones de mapas y rutas
- ✅ **ACCESS_COARSE_LOCATION** - Para ubicación aproximada
- ✅ **CAMERA** - Para subir fotos desde cámara
- ✅ **RECORD_AUDIO** - Para funciones de audio (preparado para futuro)
- ✅ **WRITE_EXTERNAL_STORAGE** - Solo Android 9 y anteriores, para descargas

### Justificación de Permisos:
| Permiso | Justificación |
|---------|--------------|
| Ubicación | AgroBus muestra la ubicación del usuario y buses en tiempo real |
| Cámara | Permite subir fotos desde la cámara en formularios web |
| Micrófono | Preparado para funciones futuras de audio/videollamadas |
| Almacenamiento | Descargar documentos en dispositivos Android 9 y anteriores |

---

## 🔄 Flujo de Solicitud de Permisos

```
1. Usuario abre AgroBus (WebView)
   ↓
2. Sitio web solicita geolocalización
   ↓
3. WebChromeClient detecta onGeolocationPermissionsShowPrompt()
   ↓
4. App verifica si tiene permiso ACCESS_FINE_LOCATION
   ↓
5a. SI tiene permiso → Otorga acceso al WebView
5b. NO tiene permiso → Muestra diálogo del sistema para solicitar
   ↓
6. Usuario acepta/rechaza
   ↓
7. Callback al WebView con resultado
```

---

## ✅ Conclusión

La app AgroChamba Android utiliza permisos de forma responsable:

- **2 permisos obligatorios:** `INTERNET`, `ACCESS_NETWORK_STATE`
- **4 permisos opcionales:** Ubicación, Cámara, Micrófono, Almacenamiento
- **Solicitud bajo demanda:** Los permisos se piden solo cuando son necesarios
- **Máxima privacidad:** El usuario siempre tiene control

Esto hace que la app sea:
- ✅ Segura y confiable
- ✅ Transparente en el uso de permisos
- ✅ Compatible con políticas de privacidad de Google Play
- ✅ Funcional incluso si el usuario rechaza permisos opcionales
