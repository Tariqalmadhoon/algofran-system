param(
    [string] $ServerAddress,
    [int] $Port = 8000
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$mobileDirectory = Join-Path $projectRoot 'mobile'
$sourceApk = Join-Path $mobileDirectory 'build\app\outputs\flutter-apk\app-debug.apk'
$downloadDirectory = Join-Path $projectRoot 'storage\app\public\downloads'
$targetApk = Join-Path $downloadDirectory 'gofran-mobile-1.2.0-test.apk'
$buildInfoPath = Join-Path $downloadDirectory 'gofran-mobile-1.2.0-test.build.json'

if ([string]::IsNullOrWhiteSpace($ServerAddress)) {
    $defaultRoute = Get-NetRoute -DestinationPrefix '0.0.0.0/0' |
        Sort-Object RouteMetric, InterfaceMetric |
        Select-Object -First 1
    $ServerAddress = Get-NetIPAddress -AddressFamily IPv4 -InterfaceIndex $defaultRoute.InterfaceIndex |
        Where-Object { $_.AddressState -eq 'Preferred' } |
        Select-Object -ExpandProperty IPAddress -First 1
}

if ([string]::IsNullOrWhiteSpace($ServerAddress)) {
    throw 'Unable to detect the LAN address. Pass it with -ServerAddress.'
}

$apiUrl = "http://${ServerAddress}:$Port/api/v1"
Write-Host "Building the Android test app for $apiUrl" -ForegroundColor Cyan

Push-Location $mobileDirectory
try {
    & flutter pub get
    if ($LASTEXITCODE -ne 0) { throw 'flutter pub get failed.' }

    & flutter build apk --debug "--dart-define=API_BASE_URL=$apiUrl"
    if ($LASTEXITCODE -ne 0) { throw 'Flutter APK build failed.' }
} finally {
    Pop-Location
}

New-Item -ItemType Directory -Path $downloadDirectory -Force | Out-Null
Copy-Item -LiteralPath $sourceApk -Destination $targetApk -Force
$checksum = (Get-FileHash -Algorithm SHA256 -LiteralPath $targetApk).Hash.ToLowerInvariant()
Set-Content -LiteralPath ($targetApk + '.sha256') -Value $checksum -Encoding ascii -NoNewline
$buildInfo = [ordered]@{
    app_version = '1.2.0'
    api_base_url = $apiUrl
    server_address = $ServerAddress
    port = $Port
    sha256 = $checksum
} | ConvertTo-Json
Set-Content -LiteralPath $buildInfoPath -Value $buildInfo -Encoding utf8

Write-Host "APK ready: $targetApk" -ForegroundColor Green
Write-Host "Download URL: http://${ServerAddress}:$Port/storage/downloads/gofran-mobile-1.2.0-test.apk"
Write-Host "SHA-256: $checksum"
