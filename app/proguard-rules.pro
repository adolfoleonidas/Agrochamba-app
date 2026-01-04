# Add project specific ProGuard rules here.
# You can control the set of applied configuration files using the
# proguardFiles setting in build.gradle.
#
# For more details, see
#   http://developer.android.com/guide/developing/tools/proguard.html

# If your project uses WebView with JS, uncomment the following
# and specify the fully qualified class name to the JavaScript interface
# class:
#-keepclassmembers class fqcn.of.javascript.interface.for.webview {
#   public *;
#}

# Uncomment this to preserve the line number information for
# debugging stack traces.
#-keepattributes SourceFile,LineNumberTable

# If you keep the line number information, uncomment this to
# hide the original source file name.
#-renamesourcefileattribute SourceFile

# ==== Ajustes para una app de producción =====

# Mantener anotaciones, firmas genéricas y metadatos de runtime útiles
-keepattributes Signature, *Annotation*, InnerClasses, EnclosingMethod
-keepattributes RuntimeVisibleAnnotations, RuntimeVisibleParameterAnnotations
-keepattributes *Annotation*

# Retrofit y OkHttp/Okio
-dontwarn retrofit2.**
-dontwarn okio.**
-dontwarn okhttp3.**
-keep class retrofit2.** { *; }
-keep interface retrofit2.** { *; }

# Moshi (reflexión y código generado)
-dontwarn com.squareup.moshi.**
-keep class com.squareup.moshi.** { *; }
-keep class kotlin.Metadata { *; }
-keep @com.squareup.moshi.JsonClass class * { *; }

# Preservar clases de datos de Kotlin para Moshi (KotlinJsonAdapterFactory usa reflexión)
# Esto es crítico: Moshi necesita acceso a constructores y propiedades en tiempo de ejecución
-keep class agrochamba.com.data.** { *; }
-keepclassmembers class agrochamba.com.data.** {
    <init>(...);
}

# Preservar constructores y propiedades de clases de datos
-keepclassmembers class agrochamba.com.data.** {
    <fields>;
}

# Preservar clases de respuesta de Retrofit con anotaciones @Json
-keepclasseswithmembers class agrochamba.com.data.** {
    @com.squareup.moshi.Json <fields>;
}

# Preservar clases genéricas usadas por Retrofit
-keep class agrochamba.com.data.PaginatedResponse { *; }

# Preservar metadatos de Kotlin necesarios para reflexión
-keepclassmembers class kotlin.reflect.** { *; }
-keep class kotlin.reflect.** { *; }

# Coroutines
-dontwarn kotlinx.coroutines.**

# Jetpack Compose (generalmente no requiere reglas adicionales, pero preservamos anotaciones)
-keep class androidx.compose.** { *; }

# WebView JS interfaces si se usan (placeholder)
# -keepclassmembers class agrochamba.com.** {
#     @android.webkit.JavascriptInterface <methods>;
# }