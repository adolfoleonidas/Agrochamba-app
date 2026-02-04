import java.util.Properties
import java.io.FileInputStream

plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.kotlin.compose)
    alias(libs.plugins.hilt)
    alias(libs.plugins.kotlin.kapt)
}

android {
    // Mantener el namespace alineado con los paquetes de código existentes
    // (el applicationId de producción puede ser distinto)
    namespace = "agrochamba.com"
    // Establecer compileSdk con el valor numérico directamente en Kotlin DSL
    compileSdk = 35

    defaultConfig {
        applicationId = "com.agrochamba"
        minSdk = 24
        targetSdk = 35
        versionCode = 5
        versionName = "1.0.4"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"

        // Constantes de configuración para evitar URLs hardcodeadas en el código
        buildConfigField("String", "WEB_ROUTES_URL", "\"https://agrobus.agrochamba.com/\"")
        buildConfigField("String", "WEB_DATES_URL", "\"https://agrochamba.com/wp-content/fechas.html\"")
        buildConfigField("String", "WEB_ROOMS_URL", "\"https://cuartos.agrochamba.com/\"")
        buildConfigField("String", "PRIVACY_URL", "\"https://agrochamba.com/politica-de-privacidad/\"")
        // Eliminado WP_BASE_URL: la app ya no consume WordPress directamente
    }

    signingConfigs {
        create("release") {
            val keystorePropertiesFile = rootProject.file("app/key.properties")
            if (keystorePropertiesFile.exists()) {
                val keystoreProperties = Properties().apply {
                    load(FileInputStream(keystorePropertiesFile))
                }
                
                val storeFileProp = keystoreProperties.getProperty("storeFile")
                if (storeFileProp != null && rootProject.file(storeFileProp).exists()) {
                    storeFile = file(storeFileProp)
                    storePassword = keystoreProperties.getProperty("storePassword") ?: ""
                    keyAlias = keystoreProperties.getProperty("keyAlias") ?: ""
                    keyPassword = keystoreProperties.getProperty("keyPassword") ?: ""
                }
            }
        }
    }

    buildTypes {
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            // Solo usar signingConfig si el keystore está configurado
            val keystorePropertiesFile = rootProject.file("app/key.properties")
            val keystoreFile = if (keystorePropertiesFile.exists()) {
                val keystoreProperties = Properties().apply {
                    load(FileInputStream(keystorePropertiesFile))
                }
                val storeFileProp = keystoreProperties.getProperty("storeFile")
                if (storeFileProp != null) rootProject.file(storeFileProp) else null
            } else null
            
            if (keystoreFile != null && keystoreFile.exists()) {
                signingConfig = signingConfigs.getByName("release")
            }
            
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
        debug {
            // Facilita distinguir builds internas
            applicationIdSuffix = ".debug"
            versionNameSuffix = "-debug"
        }
    }
    // Definición de dimensiones de sabores
    flavorDimensions += "env"
    productFlavors {
        register("dev") {
            dimension = "env"
            // Para seguir un esquema estándar (dominio invertido) en desarrollo
            // el applicationId de dev será com.agrochamba.dev
            applicationId = "com.agrochamba.dev"
            versionNameSuffix = "-dev"
        }
        register("prod") {
            dimension = "env"
        }
    }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
    kotlinOptions {
        jvmTarget = "17"
    }
    buildFeatures {
        compose = true
        // Habilita la generación de BuildConfig porque usamos buildConfigField en defaultConfig
        buildConfig = true
    }
}

dependencies {
    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.lifecycle.runtime.ktx)
    implementation(libs.androidx.activity.compose)
    implementation(platform(libs.androidx.compose.bom))
    implementation(libs.androidx.compose.ui)
    implementation(libs.androidx.compose.ui.graphics)
    implementation(libs.androidx.compose.ui.tooling.preview)
    implementation(libs.androidx.compose.material3)

    // Location + Maps (GPS + Map view)
    implementation("com.google.android.gms:play-services-location:21.2.0")
    // TODO: Configurar MAPBOX_DOWNLOADS_TOKEN en gradle.properties con un secret token
    // Obtenerlo desde: https://account.mapbox.com/access-tokens/ (crear un secret token)
    // implementation("com.mapbox.maps:android:11.5.0")
    // implementation("com.mapbox.maps:plugin-annotation:11.5.0")
    implementation("com.google.accompanist:accompanist-webview:0.36.0")
    implementation("com.google.accompanist:accompanist-swiperefresh:0.36.0")
    implementation("androidx.navigation:navigation-compose:2.9.5")
    implementation("androidx.compose.material:material-icons-extended:1.7.8")

    // Nuevas dependencias para la v2.0
    implementation("com.squareup.retrofit2:retrofit:3.0.0")
    implementation("com.squareup.retrofit2:converter-moshi:3.0.0")
    implementation("com.squareup.moshi:moshi-kotlin:1.15.2")
    implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")
    implementation("io.coil-kt:coil-compose:2.7.0")

    // Firebase eliminado - migrado completamente a WordPress

    // CameraX + ML Kit para QR Scanner
    implementation("androidx.camera:camera-core:1.4.1")
    implementation("androidx.camera:camera-camera2:1.4.1")
    implementation("androidx.camera:camera-lifecycle:1.4.1")
    implementation("androidx.camera:camera-view:1.4.1")
    implementation("com.google.android.gms:play-services-mlkit-barcode-scanning:18.3.1")

    // ZXing para generar códigos QR y de barras (Fotocheck Virtual)
    implementation("com.google.zxing:core:3.5.3")

    // Mercado Pago - Custom Tabs para Checkout Pro
    implementation("androidx.browser:browser:1.8.0")

    // Hilt Dependency Injection
    implementation("com.google.dagger:hilt-android:2.51.1")
    kapt("com.google.dagger:hilt-android-compiler:2.51.1")
    implementation("androidx.hilt:hilt-navigation-compose:1.2.0")

    // Unit Testing
    testImplementation(libs.junit)
    testImplementation("org.jetbrains.kotlinx:kotlinx-coroutines-test:1.7.3")
    testImplementation("io.mockk:mockk:1.13.8")
    testImplementation("app.cash.turbine:turbine:1.0.0") // Para testing de StateFlow
    testImplementation("com.google.dagger:hilt-android-testing:2.51.1")
    kaptTest("com.google.dagger:hilt-android-compiler:2.51.1")
    
    // Android Testing
    androidTestImplementation(libs.androidx.junit)
    androidTestImplementation(libs.androidx.espresso.core)
    androidTestImplementation(platform(libs.androidx.compose.bom))
    androidTestImplementation(libs.androidx.compose.ui.test.junit4)
    androidTestImplementation("com.google.dagger:hilt-android-testing:2.51.1")
    kaptAndroidTest("com.google.dagger:hilt-android-compiler:2.51.1")
    androidTestImplementation("androidx.test:runner:1.5.2")
    androidTestImplementation("androidx.test:rules:1.5.0")
    
    debugImplementation(libs.androidx.compose.ui.tooling)
    debugImplementation(libs.androidx.compose.ui.test.manifest)
}