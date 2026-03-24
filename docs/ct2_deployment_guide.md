# CT2 Deployment Guide

## Purpose
This guide is the client-facing handoff for deploying `CORE TRANSACTION 2: Back-Office and Agency Management System` on a standard PHP/MySQL stack. CT2 is now primarily released to cPanel through a validated artifact flow, while still documenting Linux LAMP and Windows XAMPP compatibility without introducing framework tooling, Composer, or container requirements.

## Current Quality Gate
- The current deployment blocker is the strict full-suite policy in `docs/ct2_quality_gate.md`.
- Deployment is not ready if any formatter, linter, smoke check, browser/runtime/security validator, warning, notice, or deprecation remains unresolved.
- The full suite now includes a UI regression pass for the shared sidebar, modal, tab, pagination, toast, and split date/time contracts.

## Related Guides
- Setup and first launch: `docs/ct2_setup_guide.md`
- Integration boundaries and hosting integration: `docs/ct2_integration_guide.md`
- Troubleshooting and recovery: `docs/ct2_troubleshooting_guide.md`
- cPanel artifact deployment: `docs/ct2_cpanel_release_flow.md`
- Quality-gate policy: `docs/ct2_quality_gate.md`

## What Is Being Deployed
- Application root: `ct2_back_office/`
- Entry point: `ct2_back_office/ct2_index.php`
- Database schema and seed data: `ct2_back_office/ct2_setup.sql`
- Runtime config template: `ct2_back_office/config/ct2_local.php.example`
- Release artifact builder: `ct2_back_office/scripts/ct2_release_artifact.sh`
- cPanel deployment automation: `ct2_back_office/scripts/ct2_cpanel_deploy.sh`
- cPanel post-deploy verification: `ct2_back_office/scripts/ct2_cpanel_post_deploy_check.sh`
- Approved release summary: `docs/ct2_release_summary_2026-03-24-r1.md`
- Post-install validation: `docs/ct2_manual_qa_pack.md`, `docs/ct2_api_validation.md`, and `docs/ct2_windows_xampp_validation_pack.md`

## Supported Runtime
- PHP with `PDO` and `pdo_mysql` enabled
- MySQL with InnoDB support
- Apache or a PHP-capable local web server
- Writable CT2 storage directories:
  `ct2_back_office/storage/sessions/`
  `ct2_back_office/storage/uploads/`

## Pre-Install Checklist
1. Use the validated release artifact produced by `.github/workflows/ct2_cpanel_release.yml` or build one locally with `bash ct2_back_office/scripts/ct2_release_artifact.sh`.
2. Create a MySQL database named `ct2_back_office`.
3. Confirm the target host can reach MySQL over TCP, typically `127.0.0.1:3306`.
4. Confirm PHP can write to `ct2_back_office/storage/`.
5. Confirm `pdo_mysql` is enabled before importing the schema.

## Installation Steps
### 1. Configure the application
1. Copy `ct2_back_office/config/ct2_local.php.example` to `ct2_back_office/config/ct2_local.php`.
2. Set `host`, `port`, `name`, `username`, `password`, and `charset`.
3. Use explicit TCP settings for both LAMP and XAMPP. Do not rely on Unix socket-only behavior.
4. If environment variables are also set, they override `ct2_local.php`.

### 2. Import the CT2 database
1. Import `ct2_back_office/ct2_setup.sql` into the clean `ct2_back_office` database.
2. The import creates the schema, permissions, QA demo users, seeded walkthrough data, and the financial baseline run.
3. Do not skip this import step; the seeded roles and validation flow assume it.

### 3. Verify writable storage
1. Confirm `ct2_back_office/storage/sessions/` exists and is writable by PHP.
2. Confirm `ct2_back_office/storage/uploads/` exists and is writable by PHP.
3. Keep uploaded files and session data outside version control.

### 4. Run the built-in checks
Run the canonical strict suite from the repo root:

`bash ct2_back_office/scripts/ct2_validation_suite.sh`

On Windows XAMPP, run the PowerShell equivalent:

`powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_validation_suite.ps1`

### 5. cPanel release path
1. Keep the cPanel document root pointed at `<deploy-path>/current/ct2_back_office`.
2. Store local config at `<deploy-path>/shared/config/ct2_local.php`.
3. Store writable runtime data at:
   `shared/storage/uploads/`
   `shared/storage/sessions/`
4. Upload the validated artifact and deploy over SSH with `bash ct2_back_office/scripts/ct2_cpanel_deploy.sh`.
5. Require both the pre-live cPanel verification and the live HTTP health check to pass before considering the release complete.
6. Use `docs/ct2_cpanel_release_flow.md` as the source of truth for the cPanel directory and rollback contract.

### 6. Open the application
1. Serve `ct2_back_office/` through Apache, the PHP built-in server, or XAMPP.
2. Open `ct2_back_office/ct2_index.php`.
3. Sign in with the seeded admin account:
   `ct2admin` / `ChangeMe123!`

## Platform Notes
### LAMP
- Point Apache or your vhost document root to `ct2_back_office/`.
- Ensure the Apache/PHP user owns or can write to `storage/`.
- Keep MySQL host as TCP to match the CT2 config contract.

### Windows XAMPP
- Place the repo where Apache and PHP can read it, for example under `htdocs`.
- Confirm `php_pdo_mysql` is enabled in the active XAMPP `php.ini`.
- Keep upload and session folders writable by the Apache service account.
- Use the same `ct2_local.php` contract as LAMP; no Windows-specific code path is required.
- Use the PowerShell entrypoints under `ct2_back_office/scripts/*.ps1` and `docs/ct2_windows_xampp_validation_pack.md` as the execution and evidence template for Windows validation.

## Post-Install Acceptance
Run the acceptance flow in this order:
1. Login and open the dashboard.
2. Navigate to agents, suppliers, availability, marketing, financial, visa, staff, and approvals.
3. Approve one pending seeded item.
4. Upload one sample file through the visa checklist form.
5. Export one seeded financial CSV.
6. Follow `docs/ct2_manual_qa_pack.md` for the full browser walkthrough.
7. Follow `docs/ct2_api_validation.md` for representative JSON endpoint checks.
8. On Linux or LAMP targets, record the browser accessibility, repeated load, and role-UAT results in the current QA and NFR docs if you are refreshing repo evidence.
9. On Windows XAMPP targets, complete `docs/ct2_windows_xampp_result_template.md` after following `docs/ct2_windows_xampp_validation_pack.md`.
10. Do not waive warnings, notices, or deprecations; treat them as deployment blockers.

## Initial Credentials And Security
- Seeded accounts exist only to support first-time validation and UAT.
- Change the default passwords before production use.
- Keep `ct2_local.php` local to the deployment target and out of version control.
- Review role permissions before exposing the system to non-test users.

## Release Baseline
- `develop` remains the integration baseline for ongoing CT2 work.
- `main` is the approved release branch.
- The current validated CT2 release is summarized in `docs/ct2_release_summary_2026-03-24-r1.md`.
- For cPanel, deploy the artifact built from the approved release state rather than pulling a branch directly on the server.
