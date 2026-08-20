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

Invoke-DeploymentStep $ComposerCommand @('install', '--no-dev', '--prefer-dist', '--optimize-autoloader', '--no-interaction')
Invoke-DeploymentStep $NpmCommand @('ci')
Invoke-DeploymentStep $NpmCommand @('run', 'build')

$maintenanceEnabled = $false
try {
    Invoke-DeploymentStep $PhpCommand @('artisan', 'down', '--retry=60', '--refresh=15')
    $maintenanceEnabled = $true
    Invoke-DeploymentStep $PhpCommand @('artisan', 'migrate', '--force')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'storage:link')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'optimize')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'system:production-check')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'queue:restart')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'reverb:restart')
    Invoke-DeploymentStep $PhpCommand @('artisan', 'up')
    $maintenanceEnabled = $false
}
finally {
    if ($maintenanceEnabled) {
        & $PhpCommand artisan up
    }
}

Write-Host 'Deployment completed successfully.'
