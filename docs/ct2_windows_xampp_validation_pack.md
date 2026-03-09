# CT2 Windows XAMPP Validation Pack

## Purpose
Use this packet to collect the missing Windows XAMPP runtime evidence for CT2 without introducing a separate Windows-only code path. The application contract stays the same as LAMP: TCP MySQL, local `ct2_local.php`, seeded SQL import, native scripts, and browser/API verification.

## Target Environment
- Windows machine with XAMPP installed
- Apache and MySQL running
- PHP with `pdo_mysql` enabled
- Git Bash recommended for the repo shell scripts

## Configuration Steps
1. Check out the validated CT2 branch you intend to verify.
2. Copy `ct2_back_office/config/ct2_local.php.example` to `ct2_back_office/config/ct2_local.php`.
3. Set TCP database values for the XAMPP MySQL instance:
   `host`, `port`, `name`, `username`, `password`, `charset`
4. Create a clean `ct2_back_office` database and import `ct2_back_office/ct2_setup.sql`.
5. Confirm these paths are writable:
   `ct2_back_office/storage/sessions/`
   `ct2_back_office/storage/uploads/`

## Native Validation Sequence
Run these from Git Bash in the repo root:

```bash
bash ct2_back_office/scripts/ct2_lint.sh
php ct2_back_office/scripts/ct2_smoke_check.php
php ct2_back_office/scripts/ct2_db_smoke_check.php
bash ct2_back_office/scripts/ct2_route_matrix_check.sh
bash ct2_back_office/scripts/ct2_runtime_hardening_check.sh
bash ct2_back_office/scripts/ct2_api_post_regression_check.sh
bash ct2_back_office/scripts/ct2_nfr_sanity_check.sh
```

## Browser And Operator Checks
- Sign in as `ct2admin` / `ChangeMe123!`
- Verify dashboard load and navigation to:
  `agents`, `suppliers`, `availability`, `marketing`, `financial`, `visa`, `staff`, `approvals`
- Perform one real visa upload through the browser workflow
- Trigger one financial CSV export
- Confirm no PHP warnings, notices, or broken downloads appear in Apache/PHP output

## Evidence Capture Template
Fill this in after the Windows run and copy the results into the repo docs:

| Check | Result | Notes |
| --- | --- | --- |
| `ct2_lint.sh` | `pass / fail` |  |
| `ct2_smoke_check.php` | `pass / fail` |  |
| `ct2_db_smoke_check.php` | `pass / fail` |  |
| `ct2_route_matrix_check.sh` | `pass / fail` |  |
| `ct2_runtime_hardening_check.sh` | `pass / fail` |  |
| `ct2_api_post_regression_check.sh` | `pass / fail` |  |
| `ct2_nfr_sanity_check.sh` | `pass / fail` |  |
| Dashboard/browser navigation | `pass / fail` |  |
| Visa upload | `pass / fail` |  |
| Financial CSV export | `pass / fail` |  |

## Windows-Specific Watch Items
- Git Bash is needed for the `bash` scripts unless you translate them manually.
- Apache/PHP file permissions must still allow writes under `storage/`.
- Use TCP MySQL values such as `127.0.0.1` and `3306`; do not switch CT2 to a socket-only config path.
- If upload paths fail, confirm the Apache user can write to `ct2_back_office/storage/uploads/`.

## Expected Handoff Outcome
- The completed table above is copied into `docs/ct2_nfr_evidence.md` or the QA execution report.
- Any Windows-only defect found is fixed in the repo rather than documented as an accepted divergence.
