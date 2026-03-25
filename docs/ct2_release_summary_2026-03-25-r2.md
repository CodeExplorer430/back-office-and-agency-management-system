# CT2 Release Summary - March 25, 2026 (R2)

## Historical Snapshot
- This summary captures the promoted `main` state as of March 25, 2026.
- The March 10, March 24, and March 25 R1 summaries remain historical snapshots; this document is the current approved release reference.

## Release Marker
- Branch: `main`
- Release version: `2026-03-25-r2`
- Artifact name: `ct2-2026-03-25-r2.tar.gz`
- Promotion source: validated `develop` tip merged into `main` after the auth/API hardening batch, audit/doc refresh, and shared cPanel public-path deployment support
- Git tag: not issued in this workflow-dispatch release path

## Delivered Scope
- Complete `CORE TRANSACTION 2: Back-Office and Agency Management System`
- Release delta beyond `2026-03-25-r1`:
  - successful-login session rotation and CSRF refresh for browser and API auth
  - inactive-user API login denial without side effects
  - representative API read/write permission parity with the browser RBAC model
  - browser-safe generic 500 handling without raw exception leakage
  - stricter auth/runtime/API validation coverage proving the hardening behavior directly
  - codebase audit register and refreshed NFR, QA, traceability, and debt docs
  - shared cPanel path deployment support via `CT2_CPANEL_PUBLIC_PATH`
  - encrypted SSH deploy-key support in the cPanel release workflow through `CT2_CPANEL_SSH_PASSPHRASE`

## Release Validation
- The promoted release candidate passed the repo strict suite locally with zero tolerated warnings via `bash ct2_back_office/scripts/ct2_validation_suite.sh`.
- The promoted release candidate passed the repo strict suite locally with zero tolerated warnings via `powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_validation_suite.ps1`.
- The promoted release candidate is expected to pass the GitHub `CT2 Quality Gate` workflow for both `Strict Suite (Linux)` and `Strict Suite (PowerShell)` before or during promotion.
- The cPanel target layout was prepared for shared hosting at `/home/icarusim/apps/ct2` with the public path `/home/icarusim/public_html/ct2`.
- Windows XAMPP evidence remains a documented follow-up packet rather than a blocker for this approved `main` release.

## Deployment Notes
- Deploy the validated artifact built from `main`, not a feature branch checkout.
- Use `.github/workflows/ct2_cpanel_release_pipeline.yml` or `bash ct2_back_office/scripts/ct2_release_artifact.sh` to produce the release artifact.
- Keep runtime credentials in shared config or environment variables, not in the release tree.
- Use the shared cPanel contract:
  - deploy root: `/home/icarusim/apps/ct2`
  - shared config: `/home/icarusim/apps/ct2/shared/config/ct2_local.php`
  - shared storage: `/home/icarusim/apps/ct2/shared/storage/{uploads,sessions}`
  - public path: `/home/icarusim/public_html/ct2`
- Treat `bash ct2_back_office/scripts/ct2_cpanel_post_deploy_check.sh` and `bash ct2_back_office/scripts/ct2_live_http_health_check.sh` as blocking for cPanel completion.

## Acceptance References
- Browser/UAT walkthrough: `docs/ct2_manual_qa_pack.md`
- API checks: `docs/ct2_api_validation.md`
- QA evidence: `docs/ct2_qa_execution_report.md`
- Performance and accessibility evidence: `docs/ct2_performance_accessibility_evidence.md`
- NFR evidence: `docs/ct2_nfr_evidence.md`
- Deployment/runbook: `docs/ct2_deployment_guide.md`, `docs/ct2_operator_runbook.md`
