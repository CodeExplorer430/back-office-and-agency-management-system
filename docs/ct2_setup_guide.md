# CT2 Setup Guide

## Purpose
Use this guide to stand up CT2 for the first time in a local, staging, or production-like environment. It covers prerequisites, database setup, runtime configuration, first launch, and the minimum validation steps required before handing the environment to users or to the deployment team.

## Supported Setup Targets
- Local developer machine
- Linux LAMP host
- cPanel host with SSH access
- Windows XAMPP validation environment

## Prerequisites
- PHP CLI and web runtime with `PDO` and `pdo_mysql`
- MySQL reachable over TCP, usually `127.0.0.1:3306`
- Writable CT2 storage paths:
  - `ct2_back_office/storage/sessions/`
  - `ct2_back_office/storage/uploads/`
- Browser access for the manual verification flow
- Chrome or Chromium plus `node` if the full browser/runtime suite will run on the target

## Setup Steps
### 1. Place the code or release artifact
- For cPanel or release environments, use the validated artifact built by `bash ct2_back_office/scripts/ct2_release_artifact.sh`.
- For local and developer setups, use the checked-out repo state that has already passed the strict suite.

### 2. Configure the database
1. Create a MySQL database named `ct2_back_office`.
2. Import `ct2_back_office/ct2_setup.sql`.
3. Confirm the seeded baseline data was created, including demo users, workflow records, and the financial baseline run.

### 3. Configure runtime settings
1. Copy `ct2_back_office/config/ct2_local.php.example` to `ct2_back_office/config/ct2_local.php` for local or non-artifact setups.
2. Set:
   - `host`
   - `port`
   - `name`
   - `username`
   - `password`
   - `charset`
3. Prefer explicit TCP values even on Linux.
4. If environment variables such as `CT2_DB_HOST` and `CT2_DB_PASS` are present, they override the local config file.

### 4. Prepare writable storage
1. Confirm `storage/sessions/` exists and is writable by PHP.
2. Confirm `storage/uploads/` exists and is writable by PHP.
3. Keep both outside version control and preserve them across releases.

### 5. Start the application
- Local PHP server:
  `php -S 127.0.0.1:8000 -t ct2_back_office`
- Apache or cPanel:
  point the document root to the deployed `ct2_back_office/` directory
- Open:
  `ct2_back_office/ct2_index.php`

## First Login And Baseline Validation
- Seeded administrator:
  `ct2admin` / `ChangeMe123!`
- Change seeded passwords before production use.
- Run the blocking validation suite:
  - `bash ct2_back_office/scripts/ct2_validation_suite.sh`
  - `powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_validation_suite.ps1`
- Then run the human walkthroughs as needed:
  - `docs/ct2_manual_qa_pack.md`
  - `docs/ct2_api_validation.md`

## cPanel-Specific Setup Notes
- Keep the release tree immutable and place local config in the shared path described in `docs/ct2_cpanel_release_flow.md`.
- The cPanel document root should resolve to the active release’s `ct2_back_office/` directory.
- Use:
  - `bash ct2_back_office/scripts/ct2_cpanel_post_deploy_check.sh`
  - `bash ct2_back_office/scripts/ct2_live_http_health_check.sh`
  after each deployment.

## Related Guides
- Deployment and release flow: `docs/ct2_deployment_guide.md`
- cPanel artifact deployment: `docs/ct2_cpanel_release_flow.md`
- Integration details: `docs/ct2_integration_guide.md`
- Troubleshooting and recovery: `docs/ct2_troubleshooting_guide.md`
