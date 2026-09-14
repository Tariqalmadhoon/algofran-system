package com.gofran.gofran_mobile

import android.content.Intent
import android.content.pm.ApplicationInfo
import android.content.pm.PackageInfo
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Build
import android.provider.Settings
import androidx.core.content.FileProvider
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel
import java.io.File

class MainActivity : FlutterActivity() {
    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, "gofran/app_update")
            .setMethodCallHandler { call, result ->
                if (call.method != "install") {
                    result.notImplemented()
                    return@setMethodCallHandler
                }
                try {
                    val path = call.argument<String>("path")
                    val versionCode = call.argument<Number>("versionCode")?.toLong()
                    if (path == null || versionCode == null) {
                        result.error("invalid_update", "بيانات ملف التحديث غير صالحة.", null)
                        return@setMethodCallHandler
                    }
                    val apk = File(path).canonicalFile
                    val updateDirectory = File(cacheDir, "updates").canonicalFile
                    if (apk.parentFile != updateDirectory || !apk.isFile || apk.extension != "apk") {
                        result.error("invalid_update_path", "ملف التحديث غير موجود في المسار المعتمد.", null)
                        return@setMethodCallHandler
                    }

                    @Suppress("DEPRECATION")
                    val flags = if (Build.VERSION.SDK_INT >= 28) PackageManager.GET_SIGNING_CERTIFICATES
                        else PackageManager.GET_SIGNATURES
                    @Suppress("DEPRECATION")
                    val candidate = packageManager.getPackageArchiveInfo(apk.path, flags)
                    @Suppress("DEPRECATION")
                    val installed = packageManager.getPackageInfo(packageName, flags)
                    if (candidate == null || candidate.packageName != packageName ||
                        buildNumber(candidate) != versionCode || versionCode <= buildNumber(installed) ||
                        ((candidate.applicationInfo?.flags ?: 0) and ApplicationInfo.FLAG_DEBUGGABLE) != 0 ||
                        signatures(candidate).isEmpty() || signatures(candidate) != signatures(installed)) {
                        result.error("invalid_update_package", "التحديث لا يطابق النسخة الرسمية المثبتة أو توقيعها.", null)
                        return@setMethodCallHandler
                    }
                    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O && !packageManager.canRequestPackageInstalls()) {
                        startActivity(Intent(Settings.ACTION_MANAGE_UNKNOWN_APP_SOURCES,
                            Uri.parse("package:$packageName")))
                        result.error("install_permission_required",
                            "فعّل السماح بالتثبيت من هذا المصدر، ثم عد واضغط تنزيل التحديث مرة أخرى. لن يعاد تنزيل الملف.", null)
                        return@setMethodCallHandler
                    }
                    val uri = FileProvider.getUriForFile(this, "$packageName.updateProvider", apk)
                    startActivity(Intent(Intent.ACTION_VIEW).apply {
                        setDataAndType(uri, "application/vnd.android.package-archive")
                        addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                    })
                    // Opening Android's installer is not evidence that installation completed.
                    result.success(true)
                } catch (_: Exception) {
                    result.error("installer_unavailable", "تعذر فتح شاشة تثبيت التحديث. حاول مرة أخرى.", null)
                }
            }
    }

    @Suppress("DEPRECATION")
    private fun buildNumber(info: PackageInfo): Long =
        if (Build.VERSION.SDK_INT >= 28) info.longVersionCode else info.versionCode.toLong()

    @Suppress("DEPRECATION")
    private fun signatures(info: PackageInfo): Set<String> {
        val signatures = if (Build.VERSION.SDK_INT >= 28) info.signingInfo?.apkContentsSigners
            else info.signatures
        return signatures?.map { it.toCharsString() }?.toSet() ?: emptySet()
    }
}
