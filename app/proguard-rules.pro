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

# Coroutines
-dontwarn kotlinx.coroutines.**

# Jetpack Compose (generalmente no requiere reglas adicionales, pero preservamos anotaciones)
-keep class androidx.compose.** { *; }

# WebView JS interfaces si se usan (placeholder)
# -keepclassmembers class agrochamba.com.** {
#     @android.webkit.JavascriptInterface <methods>;
# }