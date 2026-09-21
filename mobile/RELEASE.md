# Android direct release

The Android application keeps the permanent package ID
`com.gofran.gofran_mobile`. Every published update must use the same signing
keystore and a strictly greater build number (`versionCode`).

## One-time signing setup

1. Create a permanent PKCS12/JKS key on an offline trusted machine.
2. Keep at least two encrypted backups of the keystore and passwords.
3. Copy `android/key.properties.example` to `android/key.properties` and fill
   its values. Both the real properties file and all keystores are ignored by
   Git.
4. Never place signing passwords in source code, `.env`, CI logs, or chat.

`MOBILE_APP_RELEASE_ENABLED` is disabled by default, so the existing Debug/LAN
APK cannot accidentally appear as the official release on the website or update
API. Enable it only after uploading a production-signed APK and its matching
checksum.

Example key creation (run locally and replace the distinguished name values):

```powershell
New-Item -ItemType Directory -Path android\keystore -Force
keytool -genkeypair -v -keystore android\keystore\gofran-release.jks -storetype PKCS12 -keyalg RSA -keysize 4096 -validity 10000 -alias gofran-release
```

Losing this key means future APKs cannot update installations already present
on teachers' phones.

## Build a production APK

Use the HTTPS production API URL; the script refuses local/HTTP addresses and
creates an APK plus its SHA-256 sidecar under the ignored `build/releases`
directory.

```powershell
powershell -ExecutionPolicy Bypass -File scripts\build_release_apk.ps1 `
  -ApiBaseUrl https://example.org/api/v1 `
  -BuildName 1.3.0 `
  -BuildNumber 5
```

Upload the three generated files (`.apk`, `.apk.sha256`, and `.apk.json`) to
the server's private release directory (`storage/app/private/releases`). Never
place the APK in `public/`. Then set these production variables to matching
values:

```dotenv
MOBILE_APP_VERSION=1.3.1
MOBILE_APP_RELEASE_ENABLED=true
MOBILE_APP_VERSION_CODE=5
MOBILE_APP_MINIMUM_VERSION_CODE=1
MOBILE_ANDROID_APK_PATH=releases/gofran-mobile-1.3.1-5.apk
MOBILE_APP_RELEASE_NOTES="Release notes shown inside the app"
MOBILE_APP_PUBLISHED_AT=2026-09-09T00:00:00+03:00
```

Keep `MOBILE_APP_MINIMUM_VERSION_CODE` low for an optional update. Raise it only
when an old client is genuinely incompatible. The app still refuses to start an
update while local attendance, recitation, or student changes are unresolved.

After upload, clear Laravel's configuration cache and verify:

```text
GET /api/v1/mobile/releases/latest?platform=android&current_version_code=3
```

The production app accepts APK downloads only over HTTPS and verifies SHA-256
before opening Android's installer. Android still asks the teacher to approve
installation and may require enabling "install unknown apps" for this app.
