param([string] $KeytoolCommand = 'keytool')

$ErrorActionPreference = 'Stop'
$mobileRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$keyDirectory = Join-Path $mobileRoot 'android/keystore'
$keyFile = Join-Path $keyDirectory 'gofran-release.jks'
$propertiesFile = Join-Path $mobileRoot 'android/key.properties'
if ((Test-Path -LiteralPath $keyFile) -or (Test-Path -LiteralPath $propertiesFile)) {
    throw 'Signing material already exists. Never replace a published app signing key. Restore the original backup if needed.'
}
Get-Command $KeytoolCommand -ErrorAction Stop | Out-Null

function Protect-SigningPath([string] $Path, [bool] $Directory) {
    if ([System.IO.Path]::DirectorySeparatorChar -eq '\') {
        $owner = [System.Security.Principal.WindowsIdentity]::GetCurrent().User
        $system = [System.Security.Principal.SecurityIdentifier]::new('S-1-5-18')
        $acl = Get-Acl -LiteralPath $Path
        $acl.SetAccessRuleProtection($true, $false)
        $inheritance = if ($Directory) { 'ContainerInherit, ObjectInherit' } else { 'None' }
        foreach ($identity in @($owner, $system)) {
            $acl.AddAccessRule([System.Security.AccessControl.FileSystemAccessRule]::new(
                $identity, 'FullControl', $inheritance, 'None', 'Allow'))
        }
        Set-Acl -LiteralPath $Path -AclObject $acl
    } else {
        $mode = if ($Directory) { '700' } else { '600' }
        & chmod $mode $Path
        if ($LASTEXITCODE -ne 0) { throw 'Unable to restrict signing file permissions.' }
    }
}

New-Item -ItemType Directory -Path $keyDirectory -Force | Out-Null
Protect-SigningPath $keyDirectory $true
$randomBytes = New-Object byte[] 48
$generator = [System.Security.Cryptography.RandomNumberGenerator]::Create()
try {
    $generator.GetBytes($randomBytes)
    $signingPassword = [Convert]::ToBase64String($randomBytes)
    # Passwords are not command-line arguments and are never printed.
    $previousPassword = $env:GOFRAN_SIGNING_PASSWORD
    $env:GOFRAN_SIGNING_PASSWORD = $signingPassword
    & $KeytoolCommand -genkeypair -noprompt -storetype JKS -keystore $keyFile `
        -alias gofran-release -keyalg RSA -keysize 3072 -validity 10000 `
        -dname 'CN=algofran-center.tech, OU=Mobile, O=Gofran Quran Center' `
        -storepass:env GOFRAN_SIGNING_PASSWORD -keypass:env GOFRAN_SIGNING_PASSWORD
    if ($LASTEXITCODE -ne 0) { throw 'Signing key generation failed. Inspect existing files before retrying; never overwrite a key.' }
    Protect-SigningPath $keyFile $false

    # Create an empty protected file before storing the password.
    $stream = [System.IO.File]::Open($propertiesFile, [System.IO.FileMode]::CreateNew)
    $stream.Dispose()
    Protect-SigningPath $propertiesFile $false
    $properties = "storePassword=$signingPassword`nkeyPassword=$signingPassword`nkeyAlias=gofran-release`nstoreFile=../keystore/gofran-release.jks`n"
    [System.IO.File]::WriteAllText($propertiesFile, $properties, [System.Text.UTF8Encoding]::new($false))
    Write-Host 'Permanent signing key created. Back up android/keystore/gofran-release.jks AND android/key.properties in encrypted offline storage before distribution.'
    Write-Host 'These files are excluded from Git. Never upload them to the website or include them in a release artifact.'
} finally {
    $env:GOFRAN_SIGNING_PASSWORD = $previousPassword
    $signingPassword = $null
    $properties = $null
    [Array]::Clear($randomBytes, 0, $randomBytes.Length)
    $generator.Dispose()
}
