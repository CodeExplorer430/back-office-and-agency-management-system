# CT2 Deployment Guide

## Purpose
This guide is the client-facing handoff for deploying `CORE TRANSACTION 2: Back-Office and Agency Management System` on a standard PHP/MySQL stack. It covers both Linux LAMP and Windows XAMPP without introducing framework tooling, Composer, or container requirements.

## What Is Being Deployed
- Application root: `ct2_back_office/`
- Entry point: `ct2_back_office/ct2_index.php`
- Database schema and seed data: `ct2_back_office/ct2_setup.sql`
- Runtime config template: `ct2_back_office/config/ct2_local.php.example`
- Approved release summary: `docs/ct2_release_summary_2026-03-10.md`
- Post-install validation: `docs/ct2_manual_qa_pack.md`, `docs/ct2_api_validation.md`, and `docs/ct2_windows_xampp_validation_pack.md`

## Supported Runtime
- PHP with `PDO` and `pdo_mysql` enabled
- MySQL with InnoDB support
- Apache or a PHP-capable local web server
- Writable CT2 storage directories:
  `ct2_back_office/storage/sessions/`
  `ct2_back_office/storage/uploads/`

## Pre-Install Checklist
1. Check out the approved `main` branch or the tagged release state derived from it.
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
Run these commands from the repo root:

`bash ct2_back_office/scripts/ct2_lint.sh`

`php ct2_back_office/scripts/ct2_smoke_check.php`

`php ct2_back_office/scripts/ct2_db_smoke_check.php`

`bash ct2_back_office/scripts/ct2_browser_accessibility_check.sh`

`bash ct2_back_office/scripts/ct2_load_profile_check.sh`

`bash ct2_back_office/scripts/ct2_route_matrix_check.sh`

`bash ct2_back_office/scripts/ct2_runtime_hardening_check.sh`

`bash ct2_back_office/scripts/ct2_api_post_regression_check.sh`

`bash ct2_back_office/scripts/ct2_nfr_sanity_check.sh`

`bash ct2_back_office/scripts/ct2_role_uat_check.sh`

### 5. Open the application
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
- Use `docs/ct2_windows_xampp_validation_pack.md` as the execution and evidence template for Windows validation.

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
9. On Windows XAMPP targets, complete the evidence table in `docs/ct2_windows_xampp_validation_pack.md`.

## Initial Credentials And Security
- Seeded accounts exist only to support first-time validation and UAT.
- Change the default passwords before production use.
- Keep `ct2_local.php` local to the deployment target and out of version control.
- Review role permissions before exposing the system to non-test users.

## Release Baseline
- `develop` remains the integration baseline for ongoing CT2 work.
- `main` is the approved release branch.
- The current validated CT2 release is summarized in `docs/ct2_release_summary_2026-03-10.md`.
