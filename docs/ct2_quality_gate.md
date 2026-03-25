# CT2 Quality Gate

## Purpose
This document defines the current mandatory validation policy for the CT2 repository. It is the source of truth for pre-merge, pre-release, and pre-deployment quality enforcement.

## Strict Policy
- Every repo change must pass the full CT2 validation suite before the task is considered complete.
- Zero tolerance applies to:
  formatting drift,
  lint failures,
  PHP warnings,
  PHP notices,
  PHP deprecations,
  fatal errors,
  HTML leakage in JSON/API flows,
  partial-pass validation output,
  and skipped security/runtime checks without an explicitly documented blocker.
- A green subset is not enough. If one gate is relevant to the repo and runnable in the target environment, it is blocking.
- If a gate cannot run because of environment or sandbox limits, the task is not complete until that limitation is documented and the full gate is run in the correct environment.

## Canonical Entry Points
### Linux, macOS, and Bash-capable environments
Run:

`bash ct2_back_office/scripts/ct2_validation_suite.sh`

If browser accessibility or UI regression will run on Node.js 20.10+, export
`NODE_OPTIONS=--experimental-websocket` first. Node.js 22+ does not require the flag.

### Windows and native PowerShell environments
Run:

`powershell -ExecutionPolicy Bypass -File .\ct2_back_office\scripts\ct2_validation_suite.ps1`

If browser accessibility or UI regression will run on Node.js 20.10+, set
`NODE_OPTIONS=--experimental-websocket` first. Node.js 22+ does not require the flag.

## CI And Deployment Enforcement
- The repository-level blocking gate is `.github/workflows/ct2_quality_gate.yml`.
- cPanel release packaging and optional SSH deployment are orchestrated by `.github/workflows/ct2_cpanel_release.yml`.
- The supported CI baseline is GitHub-hosted runners. If self-hosted GitHub Actions runners are introduced later, validate that their runner version satisfies the minimum requirements of the pinned Node 24-based marketplace actions before treating them as equivalent.
- Artifact publication is allowed only after the strict suite is green.
- Shared-domain cPanel deployments may additionally use `CT2_CPANEL_PUBLIC_PATH` so the deploy workflow can refresh a public path such as `public_html/ct2` to the active `current/ct2_back_office` release.
- If `CT2_CPANEL_SSH_KEY` is encrypted, provide `CT2_CPANEL_SSH_PASSPHRASE` so the deploy workflow can unlock the key in `ssh-agent` before upload and release activation.
- cPanel deployment is not complete until the post-deploy verification path passes:
  - `bash ct2_back_office/scripts/ct2_cpanel_post_deploy_check.sh`
  - `bash ct2_back_office/scripts/ct2_live_http_health_check.sh`

## Suite Contents
The strict suite runs these checks in order:
1. Format check
2. PHP lint
3. Structural smoke
4. DB smoke
5. Browser accessibility
6. UI regression
7. Load profile
8. Route matrix
9. Runtime hardening
10. API POST regression
11. NFR sanity
12. Role UAT

## Failure Criteria
- Any non-zero exit code fails the suite.
- Any PHP warning-like output such as `Warning:`, `Notice:`, `Deprecated:`, `Fatal error`, or `Parse error` fails the suite.
- Any browser/runtime/API validator result that reports an incomplete pass, broken contract, missing focus state, broken route, bad status code, or write-path security failure fails the suite.
- Formatting and linting are blocking. There is no warning budget.

## When To Rerun
- Always rerun the full suite after every repo change.
- Do not downscope to “only the affected scripts” as the default workflow.
- Use targeted scripts only while debugging; the task still closes on the full-suite pass.

## Related Documents
- Setup guide: `docs/ct2_setup_guide.md`
- Integration guide: `docs/ct2_integration_guide.md`
- Troubleshooting guide: `docs/ct2_troubleshooting_guide.md`
- Deployment execution: `docs/ct2_deployment_guide.md`
- cPanel release workflow: `docs/ct2_cpanel_release_flow.md`
- Operational procedure: `docs/ct2_operator_runbook.md`
- Manual walkthrough: `docs/ct2_manual_qa_pack.md`
- API-focused validation: `docs/ct2_api_validation.md`
- Non-functional evidence: `docs/ct2_nfr_evidence.md`
- Windows execution pack: `docs/ct2_windows_xampp_validation_pack.md`
