param(
    [Parameter(Mandatory = $true)]
    [string] $ApiBaseUrl,

    [Parameter(Mandatory = $true)]
    [ValidatePattern('^\d+\.\d+\.\d+$')]
    [string] $BuildName,

    [Parameter(Mandatory = $true)]
    [ValidateRange(1, 2100000000)]
    [int] $BuildNumber,

    [string] $SdkRoot,

    [ValidatePattern('^[a-fA-F0-9]{64}$')]
    [string] $ExpectedSigningSha256
)

$ErrorActionPreference = 'Stop'
$mobileRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$keyProperties = Join-Path $mobileRoot 'android/key.properties'
$releaseDirectory = Join-Path $mobileRoot 'build/releases'
$releaseApk = Join-Path $releaseDirectory "gofran-mobile-$BuildName-$BuildNumber.apk"
if ((Test-Path -LiteralPath $releaseApk) -or (Test-Path -LiteralPath "$releaseApk.json") -or
    (Test-Path -LiteralPath "$releaseApk.sha256")) {
    throw 'This immutable release already exists; increment the build number.'
}
$apiUri = $null
if (-not [Uri]::TryCreate($ApiBaseUrl, [UriKind]::Absolute, [ref] $apiUri) -or
    $apiUri.Scheme -ne 'https' -or $apiUri.UserInfo -or $apiUri.Query -or $apiUri.Fragment -or
    $apiUri.AbsolutePath.TrimEnd('/') -ne '/api/v1' -or
    $apiUri.HostNameType -ne [UriHostNameType]::Dns -or $apiUri.Host -eq 'localhost' -or
    $apiUri.Host.EndsWith('.local')) {
    throw 'API URL must be a public HTTPS domain ending in /api/v1 with no credentials, query or fragment.'
}
$ApiBaseUrl = $ApiBaseUrl.TrimEnd('/')

if (-not (Test-Path -LiteralPath $keyProperties -PathType Leaf)) {
    throw 'android/key.properties is missing. Create it from key.properties.example and keep it outside Git.'
}
if (-not $SdkRoot) { $SdkRoot = $env:ANDROID_HOME }
if (-not $SdkRoot) { $SdkRoot = $env:ANDROID_SDK_ROOT }
if (-not $SdkRoot) {
    $localProperties = Join-Path $mobileRoot 'android/local.properties'
    if (Test-Path -LiteralPath $localProperties) {
        $sdkLine = Get-Content -LiteralPath $localProperties | Where-Object { $_ -match '^sdk\.dir=' } | Select-Object -First 1
        if ($sdkLine) { $SdkRoot = $sdkLine.Substring(8).Replace('\:', ':').Replace('\\', '\') }
    }
}
if (-not $SdkRoot -or -not (Test-Path -LiteralPath $SdkRoot -PathType Container)) {
    throw 'Set ANDROID_HOME or pass -SdkRoot to the installed Android SDK.'
}
$isWindowsPlatform = [System.IO.Path]::DirectorySeparatorChar -eq '\'
$signerName = if ($isWindowsPlatform) { 'apksigner.bat' } else { 'apksigner' }
$aaptName = if ($isWindowsPlatform) { 'aapt.exe' } else { 'aapt' }
$buildTools = Get-ChildItem -LiteralPath (Join-Path $SdkRoot 'build-tools') -Directory |
    Where-Object { $_.Name -match '^\d+\.\d+\.\d+$' } |
    Sort-Object { [Version] $_.Name } -Descending | Select-Object -First 1
if (-not $buildTools) { throw 'Android SDK build-tools are required to verify the signed APK.' }
$apkSigner = Join-Path $buildTools.FullName $signerName
$aapt = Join-Path $buildTools.FullName $aaptName
if (-not (Test-Path -LiteralPath $apkSigner) -or -not (Test-Path -LiteralPath $aapt)) {
    throw 'The Android SDK is missing apksigner or aapt.'
}

Push-Location $mobileRoot
try {
    flutter pub get
    if ($LASTEXITCODE -ne 0) { throw 'flutter pub get failed.' }
    flutter analyze --no-pub
    if ($LASTEXITCODE -ne 0) { throw 'Flutter analysis failed.' }
    flutter test --no-pub
    if ($LASTEXITCODE -ne 0) { throw 'Flutter tests failed.' }

    flutter build apk --release `
        --build-name=$BuildName `
        --build-number=$BuildNumber `
        --dart-define=API_BASE_URL=$ApiBaseUrl `
        --dart-define=APP_VERSION=$BuildName
    if ($LASTEXITCODE -ne 0) { throw 'Flutter release build failed.' }

    $sourceApk = Join-Path $mobileRoot 'build/app/outputs/flutter-apk/app-release.apk'
    if (-not (Test-Path -LiteralPath $sourceApk -PathType Leaf)) {
        throw 'The signed APK was not produced.'
    }

    $signature = (& $apkSigner verify --verbose --print-certs $sourceApk 2>&1 | Out-String)
    if ($LASTEXITCODE -ne 0) { throw 'APK signature verification failed.' }
    if ($signature -match 'CN=Android Debug') { throw 'Debug certificates cannot publish an official release.' }
    # Android build-tools 37 changed the prefix from "Signer #1" to "V2 Signer".
    $certificateMatch = [regex]::Match($signature, 'certificate SHA-256 digest:\s*([a-fA-F0-9]{64})')
    if (-not $certificateMatch.Success) { throw 'The APK signing certificate fingerprint was not found.' }
    if ($ExpectedSigningSha256 -and $certificateMatch.Groups[1].Value -ne $ExpectedSigningSha256) {
        throw 'APK signing certificate differs from the pinned production certificate.'
    }
    $badging = (& $aapt dump badging $sourceApk 2>&1 | Out-String)
    if ($LASTEXITCODE -ne 0) { throw 'Could not inspect APK version and package metadata.' }
    $packageMatch = [regex]::Match($badging, "package: name='([^']+)' versionCode='(\d+)' versionName='([^']+)'")
    if (-not $packageMatch.Success -or $packageMatch.Groups[1].Value -ne 'com.gofran.gofran_mobile' -or
        [int] $packageMatch.Groups[2].Value -ne $BuildNumber -or $packageMatch.Groups[3].Value -ne $BuildName -or
        $badging -match 'application-debuggable') {
        throw 'The built APK does not match the requested official package, version and release mode.'
    }

    New-Item -ItemType Directory -Path $releaseDirectory -Force | Out-Null
    if (Test-Path -LiteralPath $releaseApk) { throw 'This immutable release already exists; increment the build number.' }
    Copy-Item -LiteralPath $sourceApk -Destination $releaseApk

    $checksum = (Get-FileHash -LiteralPath $releaseApk -Algorithm SHA256).Hash.ToLowerInvariant()
    Set-Content -LiteralPath "$releaseApk.sha256" -Value $checksum -Encoding ascii
    $metadata = [ordered] @{
        application_id = $packageMatch.Groups[1].Value
        version_name = $BuildName
        version_code = $BuildNumber
        debuggable = $false
        api_base_url = $ApiBaseUrl
        signing_certificate_sha256 = $certificateMatch.Groups[1].Value.ToLowerInvariant()
        size_bytes = (Get-Item -LiteralPath $releaseApk).Length
        sha256 = $checksum
        built_at = [DateTime]::UtcNow.ToString('o')
    } | ConvertTo-Json
    [System.IO.File]::WriteAllText("$releaseApk.json", $metadata, [System.Text.UTF8Encoding]::new($false))

    Write-Host "Verified signed APK: $releaseApk"
    Write-Host 'Upload APK, .sha256 and .json to storage/app/private/releases; activate only after all three are present.'
    Write-Host "SHA-256: $checksum"
} finally {
    Pop-Location
}
