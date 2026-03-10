Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$script:CT2RepositoryRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..'))
$script:CT2AppRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))

function Invoke-CT2Process {
    param(
        [Parameter(Mandatory = $true)]
        [string]$FilePath,

        [string[]]$Arguments = @()
    )

    & $FilePath @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Command failed: $FilePath $($Arguments -join ' ')"
    }
}

function Invoke-CT2PhpScript {
    param(
        [Parameter(Mandatory = $true)]
        [string]$RelativePath
    )

    $ct2ScriptPath = Join-Path $script:CT2AppRoot $RelativePath
    Invoke-CT2Process -FilePath 'php' -Arguments @($ct2ScriptPath)
}

function Invoke-CT2BashValidation {
    param(
        [Parameter(Mandatory = $true)]
        [string]$ScriptName
    )

    $ct2Bash = Get-Command bash -ErrorAction SilentlyContinue
    if ($null -eq $ct2Bash) {
        throw "bash is required for $ScriptName. Install Git Bash or WSL to run this PowerShell entrypoint."
    }

    $ct2ScriptPath = Join-Path $script:CT2AppRoot "scripts/$ScriptName"
    Invoke-CT2Process -FilePath $ct2Bash.Source -Arguments @($ct2ScriptPath)
}
