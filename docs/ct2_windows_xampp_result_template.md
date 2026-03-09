# CT2 Windows XAMPP Result Template

## Purpose
Use this template to return the executed Windows XAMPP evidence in a form that can be copied directly into the CT2 repo docs. Fill this out after completing `docs/ct2_windows_xampp_validation_pack.md`.

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
- Git Bash used: `yes / no`

## Native Validation Results
| Check | Result | Notes |
| --- | --- | --- |
| `ct2_lint.sh` | `pass / fail` |  |
| `ct2_smoke_check.php` | `pass / fail` |  |
| `ct2_db_smoke_check.php` | `pass / fail` |  |
| `ct2_browser_accessibility_check.sh` or manual keyboard walkthrough | `pass / fail` |  |
| `ct2_load_profile_check.sh` | `pass / fail` |  |
| `ct2_route_matrix_check.sh` | `pass / fail` |  |
| `ct2_runtime_hardening_check.sh` | `pass / fail` |  |
| `ct2_api_post_regression_check.sh` | `pass / fail` |  |
| `ct2_nfr_sanity_check.sh` | `pass / fail` |  |
| `ct2_role_uat_check.sh` | `pass / fail` |  |

## Browser And Operator Results
| Check | Result | Notes |
| --- | --- | --- |
| Dashboard and module navigation | `pass / fail` |  |
| Visa upload | `pass / fail` |  |
| Financial CSV export | `pass / fail` |  |
| Manual keyboard walkthrough, if needed | `pass / fail` | Note the controls reached and any missing focus indicator. |
| Warnings/notices in Apache or PHP output | `none / present` |  |

## Timing Summary
- Include the repeated-load summary from `ct2_load_profile_check.sh` here:

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
