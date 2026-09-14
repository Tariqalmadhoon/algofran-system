import java.io.FileInputStream
import java.net.URI
import java.util.Base64
import java.util.Properties

plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

val releaseKeystoreProperties = Properties()
val releaseKeystorePropertiesFile = rootProject.file("key.properties")
val hasReleaseSigning = releaseKeystorePropertiesFile.exists()

if (hasReleaseSigning) {
    FileInputStream(releaseKeystorePropertiesFile).use {
        releaseKeystoreProperties.load(it)
    }
    listOf("keyAlias", "keyPassword", "storeFile", "storePassword").forEach { key ->
        require(!releaseKeystoreProperties.getProperty(key).isNullOrBlank()) {
            "Missing release signing property: $key"
        }
    }
}

android {
    namespace = "com.gofran.gofran_mobile"
    compileSdk = 37
    ndkVersion = flutter.ndkVersion

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = JavaVersion.VERSION_17.toString()
    }

    defaultConfig {
        applicationId = "com.gofran.gofran_mobile"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    if (hasReleaseSigning) {
        signingConfigs {
            create("release") {
                keyAlias = releaseKeystoreProperties.getProperty("keyAlias")
                keyPassword = releaseKeystoreProperties.getProperty("keyPassword")
                storeFile = file(releaseKeystoreProperties.getProperty("storeFile"))
                storePassword = releaseKeystoreProperties.getProperty("storePassword")
            }
        }
    }

    buildTypes {
        release {
            if (hasReleaseSigning) {
                signingConfig = signingConfigs.getByName("release")
            }
        }
    }
}

gradle.taskGraph.whenReady {
    val buildingRelease = allTasks.any {
        it.name.contains("release", ignoreCase = true) &&
            (it.name.contains("assemble", ignoreCase = true) ||
                it.name.contains("bundle", ignoreCase = true))
    }

    if (buildingRelease && !hasReleaseSigning) {
        throw GradleException(
            "Missing android/key.properties. Release builds must use the permanent production signing key.",
        )
    }
    if (buildingRelease) {
        val defines = (project.findProperty("dart-defines") as String?)
            ?.split(",")?.map { String(Base64.getDecoder().decode(it)) } ?: emptyList()
        val apiUrl = defines.firstOrNull { it.startsWith("API_BASE_URL=") }
            ?.removePrefix("API_BASE_URL=")
        val uri = apiUrl?.let { URI(it) }
        require(uri?.scheme == "https" && !uri.host.isNullOrBlank() &&
            uri.userInfo == null && uri.query == null && uri.fragment == null &&
            uri.path.trimEnd('/') == "/api/v1" &&
            uri.host != "localhost" && !uri.host.endsWith(".local") &&
            !uri.host.matches(Regex("[0-9.]+")) && !uri.host.contains(":")) {
            "Release builds require API_BASE_URL=https://your-production-domain/api/v1."
        }
    }
}

flutter {
    source = "../.."
}
