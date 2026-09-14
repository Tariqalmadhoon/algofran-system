param(
    [int] $Port = 8000
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$apkPath = Join-Path $projectRoot 'storage\app\public\downloads\gofran-mobile-1.2.0-test.apk'
$buildInfoPath = Join-Path $projectRoot 'storage\app\public\downloads\gofran-mobile-1.2.0-test.build.json'
$buildScript = Join-Path $PSScriptRoot 'build-mobile-test-apk.ps1'
$defaultRoute = Get-NetRoute -DestinationPrefix '0.0.0.0/0' |
    Sort-Object RouteMetric, InterfaceMetric |
    Select-Object -First 1
$serverAddress = Get-NetIPAddress -AddressFamily IPv4 -InterfaceIndex $defaultRoute.InterfaceIndex |
    Where-Object { $_.AddressState -eq 'Preferred' } |
    Select-Object -ExpandProperty IPAddress -First 1

if ([string]::IsNullOrWhiteSpace($serverAddress)) {
    throw 'Unable to detect the computer LAN address.'
}

$requestedPort = $Port
$lastCandidatePort = $requestedPort + 20
while (Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue) {
    $Port++
    if ($Port -gt $lastCandidatePort) {
        throw "Unable to find a free test port between $requestedPort and $lastCandidatePort."
    }
}
if ($Port -ne $requestedPort) {
    Write-Host "Port $requestedPort is already in use. The mobile demo will use port $Port instead." -ForegroundColor Yellow
}

$currentApiUrl = "http://${serverAddress}:$Port/api/v1"
$needsBuild = -not (Test-Path -LiteralPath $apkPath) -or -not (Test-Path -LiteralPath $buildInfoPath)
if (-not $needsBuild) {
    $buildInfo = Get-Content -LiteralPath $buildInfoPath -Raw | ConvertFrom-Json
    $needsBuild = $buildInfo.api_base_url -ne $currentApiUrl
}

if (-not $needsBuild) {
    $apkTimestamp = (Get-Item -LiteralPath $apkPath).LastWriteTimeUtc
    $latestMobileSource = Get-ChildItem -LiteralPath (Join-Path $projectRoot 'mobile') -Recurse -File |
        Where-Object {
            $_.FullName -notlike '*\build\*' -and
            $_.FullName -notlike '*\.dart_tool\*' -and
            $_.FullName -notlike '*\.gradle\*'
        } |
        Sort-Object LastWriteTimeUtc -Descending |
        Select-Object -First 1
    $needsBuild = $latestMobileSource -and $latestMobileSource.LastWriteTimeUtc -gt $apkTimestamp
}

if ($needsBuild) {
    Write-Host "The network address changed or the APK is missing. Building a compatible APK for $currentApiUrl..." -ForegroundColor Yellow
    & $buildScript -ServerAddress $serverAddress -Port $Port
    if ($LASTEXITCODE -ne 0) { throw 'Unable to build a compatible APK.' }
}

Push-Location $projectRoot
try {
    if (-not (Test-Path -LiteralPath (Join-Path $projectRoot 'public\storage'))) {
        & php artisan storage:link
        if ($LASTEXITCODE -ne 0) { throw 'Unable to create the public storage link.' }
    }

    Write-Host 'Mobile test server is ready.' -ForegroundColor Green
    Write-Host "Website: http://${serverAddress}:$Port"
    Write-Host "APK: http://${serverAddress}:$Port/storage/downloads/gofran-mobile-1.2.0-test.apk"
    Write-Host 'Keep this window open while testing. Press Ctrl+C to stop.' -ForegroundColor Yellow
    & php artisan serve --host=0.0.0.0 --port=$Port --no-reload
} finally {
    Pop-Location
}
