# CT2 Troubleshooting Guide

## Purpose
Use this guide to diagnose and recover from the most common CT2 setup, deployment, integration, and runtime failures. It complements the operator runbook by expanding the practical recovery steps.

## Setup And Installation Problems
### Database connection failure
- Confirm `ct2_local.php` or the `CT2_DB_*` environment variables are correct.
- Confirm MySQL is reachable over TCP.
- Confirm `pdo_mysql` is enabled.
- Run:
  `php ct2_back_office/scripts/ct2_db_smoke_check.php`

### Missing seeded users or baseline data
- Re-import `ct2_back_office/ct2_setup.sql`.
- Confirm the import finished without SQL errors.
- Verify seeded users such as `ct2admin` exist before assuming the login flow is broken.

### Storage or permission errors
- Check `storage/sessions/` and `storage/uploads/`.
- Confirm the active PHP user can read and write them.
- On cPanel, confirm the release relinks to shared writable storage instead of a stale release-local folder.

## Deployment And cPanel Problems
### Artifact deployment fails before go-live
- Confirm the release artifact exists and the SHA256 file matches.
- Confirm SSH credentials, deploy path, and shared directories are correct.
- Run:
  `bash ct2_back_office/scripts/ct2_cpanel_post_deploy_check.sh`

### Live health check fails after deployment
- Confirm `CT2_BASE_URL` points to the active cPanel URL.
- Confirm the document root resolves to the active release’s `ct2_back_office/`.
- Confirm the health-check credentials are valid if authenticated health verification is enabled.
- Roll back to the previous `current` symlink if the environment cannot be restored quickly.

## Runtime And Browser Problems
### Login or session issues
- Confirm `storage/sessions/` is writable.
- Clear stale session files only under an approved support process.
- Confirm the user is active and has the expected role.

### Upload failures
- Confirm upload directories and PHP upload limits.
- Confirm the file type matches the visa workflow’s accepted formats.
- Re-run the runtime hardening suite if the failure followed a code or deployment change.

### Export or API failures
- Confirm the user has the required permission such as `financial.export` or `api.access`.
- Confirm the endpoint returns JSON or CSV with the correct headers.
- Check for HTML leakage, PHP notices, or warning text in the response body.
- Re-run:
  - `bash ct2_back_office/scripts/ct2_api_post_regression_check.sh`
  - `bash ct2_back_office/scripts/ct2_route_matrix_check.sh`

### Sidebar, modal, or layout regressions
- Run the UI and browser validators:
  - `bash ct2_back_office/scripts/ct2_browser_accessibility_check.sh`
  - `bash ct2_back_office/scripts/ct2_ui_regression_check.sh`
- Hard-refresh the browser to eliminate stale CSS/JS cache before concluding the deployed build is bad.

## Validation And Quality-Gate Problems
### Strict suite failure
- Read the first failing gate in `ct2_validation_suite.sh`; do not treat later gates as actionable until the first one is fixed.
- Warnings, notices, and deprecations are blockers, not informational output.

### GitHub Actions passes but cPanel fails
- Compare runtime config, writable paths, and PHP extension state between CI and cPanel.
- Prefer the cPanel post-deploy verification output over assumptions from CI when diagnosing environment-only failures.

## Recovery Rules
- Restore the previous working release before attempting risky live debugging.
- Restore database and upload backups if the failed deployment changed data or write paths.
- Re-run the post-deploy verification path after rollback before reopening the system.

## Related Guides
- Setup: `docs/ct2_setup_guide.md`
- Integration model: `docs/ct2_integration_guide.md`
- Deployment: `docs/ct2_deployment_guide.md`
- Operator procedure: `docs/ct2_operator_runbook.md`
- cPanel release flow: `docs/ct2_cpanel_release_flow.md`
