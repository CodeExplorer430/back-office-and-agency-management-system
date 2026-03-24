# CT2 Windows XAMPP Result Template

## Purpose
Use this template to return the executed Windows XAMPP evidence in a form that can be copied directly into the CT2 repo docs. Fill this out after completing `docs/ct2_windows_xampp_validation_pack.md`.

## Current Quality Gate
- The Windows run is expected to satisfy the same strict policy defined in `docs/ct2_quality_gate.md`.
- Record any warning, notice, deprecation, or skipped validation step as a failure, not as informational noise.

## Environment
- Date:
- Operator:
- Branch or tag tested:
- Windows version:
- XAMPP version:
- PHP version:
- MySQL version:
- Browser used:
- Chrome automation script executed directly: `yes / no`
- Additional shell used beyond PowerShell, if any:

## Native Validation Results
| Check | Result | Notes |
| --- | --- | --- |
| `ct2_validation_suite.ps1` | `pass / fail` | Required top-level gate for Windows evidence. |
| `ct2_format_check.ps1` | `pass / fail` |  |
| `ct2_lint.ps1` | `pass / fail` |  |
| `ct2_smoke_check.ps1` | `pass / fail` |  |
| `ct2_db_smoke_check.ps1` | `pass / fail` |  |
| `ct2_browser_accessibility_check.ps1` or manual keyboard walkthrough | `pass / fail` |  |
| `ct2_ui_regression_check.ps1` | `pass / fail` | Sidebar, modal, tabs, pagination, toast, and split date/time UI contract. |
| `ct2_load_profile_check.ps1` | `pass / fail` |  |
| `ct2_route_matrix_check.ps1` | `pass / fail` |  |
| `ct2_runtime_hardening_check.ps1` | `pass / fail` |  |
| `ct2_api_post_regression_check.ps1` | `pass / fail` |  |
| `ct2_nfr_sanity_check.ps1` | `pass / fail` |  |
| `ct2_role_uat_check.ps1` | `pass / fail` |  |

## Browser And Operator Results
| Check | Result | Notes |
| --- | --- | --- |
| Dashboard and module navigation | `pass / fail` |  |
| Visa upload | `pass / fail` |  |
| Financial CSV export | `pass / fail` |  |
| Manual keyboard walkthrough, if needed | `pass / fail` | Note the controls reached and any missing focus indicator. |
| Warnings/notices in Apache or PHP output | `none / present` |  |

## Timing Summary
- Include the repeated-load summary from `ct2_load_profile_check.ps1` or `ct2_validation_suite.ps1` here:

```text
paste the timing summary output
```

## Defects Or Deviations
- List every Windows-only issue or deviation from Linux/LAMP behavior.
- Include:
  route or script name,
  exact failure text,
  reproducibility,
  and whether it blocks promotion to `main`.

## Repo Copy-Back Targets
- Copy cross-platform and release-gate conclusions into:
  `docs/ct2_nfr_evidence.md`
- Copy keyboard/focus and timing observations into:
  `docs/ct2_performance_accessibility_evidence.md`
- Copy route, role, upload, export, and failure results into:
  `docs/ct2_qa_execution_report.md`
- If the run closes the final validation gap, update:
  `docs/ct2_requirements_traceability_matrix.md`
  and
  `docs/ct2_requirements_audit_backlog.md`

## Promotion Recommendation
- Ready to promote `develop` to `main`: `yes / no`
- Reason:
- Confirm the recommendation is `no` if any strict-gate item failed or was skipped.
