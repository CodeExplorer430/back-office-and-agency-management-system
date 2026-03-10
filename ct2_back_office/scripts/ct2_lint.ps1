Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$ct2AppRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$ct2PhpFiles = Get-ChildItem -Path $ct2AppRoot -Recurse -File -Filter '*.php' | Sort-Object FullName

foreach ($ct2File in $ct2PhpFiles) {
    & php -l $ct2File.FullName | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "PHP lint failed: $($ct2File.FullName)"
    }

    Write-Host "lint ok: $($ct2File.FullName)"
}
