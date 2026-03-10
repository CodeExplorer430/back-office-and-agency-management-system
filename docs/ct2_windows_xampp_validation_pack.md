# CT2 Windows XAMPP Validation Pack

## Purpose
Use this packet to collect the missing Windows XAMPP runtime evidence for CT2 without introducing a separate Windows-only code path. The application contract stays the same as LAMP: TCP MySQL, local `ct2_local.php`, seeded SQL import, native scripts, and browser/API verification.
The next promotion from `develop` to `main` is gated on this packet being executed and the evidence being copied back into the repo docs.
Use `docs/ct2_windows_xampp_result_template.md` as the required return format for the executed run.

## Target Environment
- Windows machine with XAMPP installed
- Apache and MySQL running
- PHP with `pdo_mysql` enabled
- Windows PowerShell
- PowerShell for the native CT2 validation entrypoints

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
Run these from Windows PowerShell in the repo root:

```powershell
powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_format_check.ps1
powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_lint.ps1
powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_smoke_check.ps1
powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_db_smoke_check.ps1
powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_browser_accessibility_check.ps1
powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_load_profile_check.ps1
powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_route_matrix_check.ps1
powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_runtime_hardening_check.ps1
powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_api_post_regression_check.ps1
powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_nfr_sanity_check.ps1
powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_role_uat_check.ps1
```

You can also run the aggregate suite:

```powershell
powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_validation_suite.ps1
```

If Chrome automation is not available on the Windows host, execute the keyboard walkthrough manually and record the result in the evidence table instead of skipping the evidence.

## Browser And Operator Checks
- Sign in as `ct2admin` / `ChangeMe123!`
- Verify dashboard load and navigation to:
  `agents`, `suppliers`, `availability`, `marketing`, `financial`, `visa`, `staff`, `approvals`
- Perform one real visa upload through the browser workflow
- Trigger one financial CSV export
- Complete one keyboard-only walkthrough for login, dashboard nav, approvals, visa upload, and the financial export trigger if the browser accessibility script is not executed directly
- Confirm no PHP warnings, notices, or broken downloads appear in Apache/PHP output

## Evidence Capture
- Fill out `docs/ct2_windows_xampp_result_template.md` after the Windows run.
- Do not return a partial narrative or screenshots alone; the completed template is the required intake format.
- Copy the finished template back into the repo docs listed in its `Repo Copy-Back Targets` section.

## Windows-Specific Watch Items
- PowerShell is the primary operator shell.
- PowerShell is sufficient for the full scripted validation path.
- Apache/PHP file permissions must still allow writes under `storage/`.
- Use TCP MySQL values such as `127.0.0.1` and `3306`; do not switch CT2 to a socket-only config path.
- If upload paths fail, confirm the Apache user can write to `ct2_back_office/storage/uploads/`.

## Expected Handoff Outcome
- The completed `docs/ct2_windows_xampp_result_template.md` is copied back into the repo docs named there so Windows execution becomes direct evidence rather than a pending packet.
- Any Windows-only defect found is fixed in the repo rather than documented as an accepted divergence.
