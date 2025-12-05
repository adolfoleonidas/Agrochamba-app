# AgroChamba

Sistema completo de gestión de trabajos agrícolas con aplicación Android y plugin WordPress.

## 📁 Estructura del Proyecto

```
Agrochamba/
├── app/                    # ⭐ Aplicación Android (Kotlin + Jetpack Compose)
│   └── src/main/java/...  # Código fuente de la app
│
├── agrochamba-core/        # Plugin WordPress (completo y funcional)
│   ├── agrochamba-core.php # Archivo principal del plugin
│   ├── modules/            # 9 módulos organizados
│   ├── README.txt          # Documentación del plugin
│   └── ESTADO-ACTUAL.md    # Estado y mejoras futuras
│
└── README.md               # Este archivo
```

## 🎯 Enfoque Actual

**Estamos enfocados en el desarrollo y mejora de la App Android.**

El plugin WordPress está completo, funcional y listo para uso. Se mantiene en el proyecto para futuras mejoras o ajustes cuando sea necesario.

## 🚀 Inicio Rápido

### App Android (Enfoque Principal)

1. Abre el proyecto en Android Studio
2. Sincroniza Gradle
3. Ejecuta la app en un dispositivo o emulador

Ver `app/README.md` para más detalles sobre la app.

### Plugin WordPress (Referencia)

El plugin está completo y funcional. Para instalarlo:

1. Copia la carpeta `agrochamba-core` a `wp-content/plugins/`
2. Activa el plugin desde el panel de administración de WordPress
3. Requiere: Plugin **JWT Authentication for WP REST API**

Ver `agrochamba-core/README.txt` para documentación completa.

## 📚 Documentación

- **App Android**: `app/README.md` - Arquitectura y funcionalidades
- **Plugin WordPress**: `agrochamba-core/README.txt` - Instalación y uso
- **Estado del Plugin**: `agrochamba-core/ESTADO-ACTUAL.md` - Estado actual y mejoras futuras

## 🔧 Tecnologías

### App Android
- **Lenguaje**: Kotlin
- **UI**: Jetpack Compose
- **Arquitectura**: MVVM
- **Networking**: Retrofit + Moshi
- **Autenticación**: JWT

### Plugin WordPress
- **Lenguaje**: PHP
- **API**: WordPress REST API
- **Autenticación**: JWT (plugin externo)

## 📝 Licencia

GPL v2 or later
