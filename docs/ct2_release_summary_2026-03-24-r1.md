# CT2 Release Summary - March 24, 2026 (R1)

## Historical Snapshot
- This summary captures the promoted `main` state as of March 24, 2026.
- The March 10 release summaries remain historical snapshots; this document is the current approved release reference.

## Release Marker
- Branch: `main`
- Release version: `2026-03-24-r1`
- Artifact name: `ct2-2026-03-24-r1.tar.gz`
- Promotion source: validated `develop` tip merged into `main` after the CT2 UI, validation, release automation, and documentation hardening pass
- Git tag: not issued in this workflow-dispatch release path

## Delivered Scope
- Complete `CORE TRANSACTION 2: Back-Office and Agency Management System`
- Implemented modules:
  - Travel Agent and Staff Management
  - Supplier and Partner Management
  - Tour Availability and Resource Planning
  - Marketing and Promotions Management
  - Financial Reporting and Analytics
  - Document and Visa Assistance Module
- Shared CT2 capabilities:
  - auth and RBAC
  - approval workflows
  - audit and API logging
  - JSON APIs
  - seeded QA/demo data
  - browser-driven document uploads
  - financial CSV export
  - refined shared UI shell, sidebar, modal, tab, pagination, toast, and split date/time behavior
  - strict Bash and PowerShell validation entrypoints
  - browser accessibility, UI regression, load, route, runtime hardening, API POST, NFR, and role-UAT automation
  - cPanel artifact packaging and deployment automation
  - expanded setup, integration, troubleshooting, and release guidance

## Release Validation
- The promoted release state passed the repo strict suite locally with zero tolerated warnings via `bash ct2_back_office/scripts/ct2_validation_suite.sh`.
- The promoted release state passed the GitHub `CT2 Quality Gate` workflow for both `Strict Suite (Linux)` and `Strict Suite (PowerShell)` before promotion.
- Browser accessibility and UI regression are now part of the blocking release evidence, not optional follow-up checks.
- Windows XAMPP evidence remains a documented follow-up packet rather than a blocker for this approved `main` release.

## Deployment Notes
- Deploy the validated artifact built from `main`, not a feature branch checkout.
- Use `.github/workflows/ct2_cpanel_release.yml` or `bash ct2_back_office/scripts/ct2_release_artifact.sh` to produce the release artifact.
- Import `ct2_back_office/ct2_setup.sql` into a clean `ct2_back_office` database for first-time setup.
- Keep runtime credentials in shared config or environment variables, not in the release tree.
- Treat `bash ct2_back_office/scripts/ct2_cpanel_post_deploy_check.sh` and `bash ct2_back_office/scripts/ct2_live_http_health_check.sh` as blocking for cPanel completion.

## Acceptance References
- Browser/UAT walkthrough: `docs/ct2_manual_qa_pack.md`
- API checks: `docs/ct2_api_validation.md`
- QA evidence: `docs/ct2_qa_execution_report.md`
- Performance and accessibility evidence: `docs/ct2_performance_accessibility_evidence.md`
- NFR evidence: `docs/ct2_nfr_evidence.md`
- Deployment/runbook: `docs/ct2_deployment_guide.md`, `docs/ct2_operator_runbook.md`
