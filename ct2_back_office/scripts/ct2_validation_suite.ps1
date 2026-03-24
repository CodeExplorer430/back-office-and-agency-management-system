. $PSScriptRoot\ct2_powershell_common.ps1

$ct2SuiteSteps = @(
    @{ Label = 'format check'; Script = 'ct2_format_check.ps1' },
    @{ Label = 'PHP lint'; Script = 'ct2_lint.ps1' },
    @{ Label = 'structural smoke'; Script = 'ct2_smoke_check.ps1' },
    @{ Label = 'DB smoke'; Script = 'ct2_db_smoke_check.ps1' },
    @{ Label = 'browser accessibility'; Script = 'ct2_browser_accessibility_check.ps1' },
    @{ Label = 'UI regression'; Script = 'ct2_ui_regression_check.ps1' },
    @{ Label = 'load profile'; Script = 'ct2_load_profile_check.ps1' },
    @{ Label = 'route matrix'; Script = 'ct2_route_matrix_check.ps1' },
    @{ Label = 'runtime hardening'; Script = 'ct2_runtime_hardening_check.ps1' },
    @{ Label = 'API POST regression'; Script = 'ct2_api_post_regression_check.ps1' },
    @{ Label = 'NFR sanity'; Script = 'ct2_nfr_sanity_check.ps1' },
    @{ Label = 'role UAT'; Script = 'ct2_role_uat_check.ps1' }
)

foreach ($ct2Step in $ct2SuiteSteps) {
    Write-Host "[ct2-suite] Running $($ct2Step.Label)."
    & (Join-Path $PSScriptRoot $ct2Step.Script)
    if ($LASTEXITCODE -ne 0) {
        throw "[ct2-suite] FAILED: $($ct2Step.Label)"
    }
    Write-Host "[ct2-suite] Passed: $($ct2Step.Label)"
}

Write-Host '[ct2-suite] CT2 strict validation suite passed with zero tolerated warnings.'
