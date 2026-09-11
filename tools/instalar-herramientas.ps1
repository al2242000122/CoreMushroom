param(
    [switch]$Indexar
)

$ErrorActionPreference = 'Stop'

$repoRoot = (& git rev-parse --show-toplevel 2>$null).Trim()
if (-not $repoRoot) {
    throw 'No se encontró la raíz Git. Ejecuta este script dentro del repositorio.'
}
$repoRoot = [IO.Path]::GetFullPath($repoRoot)
$currentRoot = [IO.Path]::GetFullPath((Get-Location).Path)
if ($repoRoot -ne $currentRoot) {
    throw "Ejecuta el script desde la raíz del repositorio: $repoRoot"
}

# Context Mode se instala de forma global porque es un servidor MCP local,
# no un archivo que deba desplegarse junto con el tema de WordPress.
$contextModeVersion = '1.0.169'
$contextMode = Get-Command context-mode -ErrorAction SilentlyContinue
$contextModePackage = npm list --global context-mode --depth=0 2>$null | Select-String "context-mode@$contextModeVersion"
if (-not $contextMode -or -not $contextModePackage) {
    npm install --global --ignore-scripts "context-mode@$contextModeVersion"
}

# v1.0.6 es la última versión publicada con binario x86_64 para Windows.
# El release posterior v1.0.7 no incluye ese artefacto.
$tgrepVersion = 'v1.0.6'
$tgrepAsset = "tgrep-$tgrepVersion-x86_64-pc-windows-msvc.zip"
$tgrepSha256 = '10d926a88feeac0f33d3517be26e0a293b02fb7d99ca333d711c60a4607742df'
$installDir = Join-Path $env:LOCALAPPDATA 'Programs\tgrep'
$tempRoot = Join-Path ([IO.Path]::GetTempPath()) ("coremushroom-tgrep-" + [guid]::NewGuid().ToString('N'))
$archivePath = Join-Path $tempRoot $tgrepAsset
$extractPath = Join-Path $tempRoot 'extract'
$downloadUrl = "https://github.com/microsoft/tgrep/releases/download/$tgrepVersion/$tgrepAsset"

New-Item -ItemType Directory -Path $tempRoot, $extractPath, $installDir -Force | Out-Null
Invoke-WebRequest -Uri $downloadUrl -OutFile $archivePath

$actualSha256 = (Get-FileHash -LiteralPath $archivePath -Algorithm SHA256).Hash.ToLowerInvariant()
if ($actualSha256 -ne $tgrepSha256) {
    throw "La suma SHA-256 de tgrep no coincide: $actualSha256"
}

Expand-Archive -LiteralPath $archivePath -DestinationPath $extractPath -Force
$binary = Get-ChildItem -LiteralPath $extractPath -Filter 'tgrep.exe' -File -Recurse | Select-Object -First 1
if (-not $binary) {
    throw 'El archivo de tgrep no contiene tgrep.exe.'
}
Copy-Item -LiteralPath $binary.FullName -Destination (Join-Path $installDir 'tgrep.exe') -Force

# El binario se instala en una ruta de usuario. Este wrapper permite usarlo
# en la terminal actual, cuyo PATH no se refresca después de SetEnvironmentVariable.
$npmBin = Join-Path $env:APPDATA 'npm'
$wrapperPath = Join-Path $npmBin 'tgrep.cmd'
New-Item -ItemType Directory -Path $npmBin -Force | Out-Null
Set-Content -LiteralPath $wrapperPath -Encoding ascii -Value @(
    '@echo off'
    "`"$installDir\tgrep.exe`" %*"
)

# Añade tgrep al PATH del usuario sin tocar el PATH del sistema.
$userPath = [Environment]::GetEnvironmentVariable('Path', 'User')
$pathEntries = @()
if ($userPath) {
    $pathEntries = $userPath -split ';' | Where-Object { $_ }
}
if ($pathEntries -notcontains $installDir) {
    [Environment]::SetEnvironmentVariable('Path', (($pathEntries + $installDir) -join ';'), 'User')
}

Write-Output "Context Mode: $((npm list --global context-mode --depth=0 2>&1 | Select-String 'context-mode@' | ForEach-Object { $_.ToString().Trim() }))"
Write-Output "tgrep: $(& (Join-Path $installDir 'tgrep.exe') --version 2>&1)"
Write-Output "Ruta instalada: $installDir"

if ($Indexar) {
    & (Join-Path $installDir 'tgrep.exe') index $repoRoot --exclude .codex-remote-attachments --exclude .tgrep
}
