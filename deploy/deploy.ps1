param(
    [string]$ProjectDirectory = (Split-Path -Parent $PSScriptRoot),
    [string]$PhpCommand = 'php',
    [string]$ComposerCommand = 'composer',
    [string]$NpmCommand = 'npm'
)

$ErrorActionPreference = 'Stop'

function Invoke-DeploymentStep {
    param(
        [string]$Executable,
        [string[]]$StepArguments
    )

    & $Executable @StepArguments
    if ($LASTEXITCODE -ne 0) {
        throw "Deployment command failed: $Executable"
    }
}

Set-Location -LiteralPath (Resolve-Path -LiteralPath $ProjectDirectory)

$deploymentLock = [System.IO.File]::Open(
    (Join-Path (Get-Location).Path 'storage/framework/deploy.lock'),
    [System.IO.FileMode]::OpenOrCreate,
    [System.IO.FileAccess]::ReadWrite,
    [System.IO.FileShare]::None
)
try {
    Invoke-DeploymentStep $PhpCommand @('artisan', 'down', '--retry=60', '--refresh=15')
    Invoke-DeploymentStep $ComposerCommand @('install', '--no-dev', '--prefer-dist', '--optimize-autoloader', '--no-interaction')
    Invoke-DeploymentStep $NpmCommand @('ci')
    Invoke-DeploymentStep $NpmCommand @('run', 'build')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'config:clear')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'migrate', '--force')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'db:seed', '--class=Database\Seeders\QuranReferenceSeeder', '--force')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'storage:link')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'optimize')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'system:production-check')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'queue:restart')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'reverb:restart')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'up')
}
catch {
    Write-Warning 'Deployment failed; maintenance remains enabled. Complete or recover the release before running artisan up.'
    throw
}
finally {
    $deploymentLock.Dispose()
}

Write-Host 'Deployment completed successfully.'
