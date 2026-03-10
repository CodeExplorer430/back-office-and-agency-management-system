. $PSScriptRoot\ct2_powershell_common.ps1

Write-Host '[ct2-suite] Running format check.'
& (Join-Path $PSScriptRoot 'ct2_format_check.ps1')

Write-Host '[ct2-suite] Running PHP lint.'
& (Join-Path $PSScriptRoot 'ct2_lint.ps1')

Write-Host '[ct2-suite] Running smoke checks.'
& (Join-Path $PSScriptRoot 'ct2_smoke_check.ps1')
& (Join-Path $PSScriptRoot 'ct2_db_smoke_check.ps1')

Write-Host '[ct2-suite] Running route and runtime validation.'
& (Join-Path $PSScriptRoot 'ct2_route_matrix_check.ps1')
& (Join-Path $PSScriptRoot 'ct2_runtime_hardening_check.ps1')
& (Join-Path $PSScriptRoot 'ct2_api_post_regression_check.ps1')
& (Join-Path $PSScriptRoot 'ct2_nfr_sanity_check.ps1')
& (Join-Path $PSScriptRoot 'ct2_load_profile_check.ps1')
& (Join-Path $PSScriptRoot 'ct2_browser_accessibility_check.ps1')
& (Join-Path $PSScriptRoot 'ct2_role_uat_check.ps1')

Write-Host '[ct2-suite] CT2 validation suite passed.'
