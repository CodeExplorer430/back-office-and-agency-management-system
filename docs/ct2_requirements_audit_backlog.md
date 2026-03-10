# CT2 Requirements Audit Backlog

## Purpose
This backlog captures the remaining repo-owned actions surfaced by `docs/ct2_requirements_traceability_matrix.md`. It excludes upstream documentation work that belongs to other teams and focuses on the validation debt that can still be closed inside this repository.

## Priority 1
### Add executed Windows XAMPP runtime evidence
- Type: Validation gap
- Impact: The CT2 runtime contract is cross-platform by design, but in-repo runtime proof is still strongest on the local Linux LAMP environment.
- Expected work:
  execute the deployment guide, DB smoke, runtime hardening script, and a short browser/API pass on a Windows XAMPP setup;
  record the result in the deployment or NFR docs.
- Acceptance criteria:
  the repo contains explicit Windows XAMPP execution evidence,
  and cross-platform compatibility is no longer only code-aligned and documented.

### Remove Bash delegation from advanced PowerShell validation entrypoints
- Type: Tooling debt
- Impact: Windows operators can launch validation from PowerShell, but full native parity is not complete because several advanced `.ps1` scripts still call the Bash implementations.
- Expected work:
  port route-matrix, runtime-hardening, API-post, accessibility, load, NFR sanity, and role-UAT logic to native PowerShell or retire the Bash-free parity expectation explicitly.
- Acceptance criteria:
  Windows validation can run from PowerShell without requiring Bash for the advanced checks,
  or the project scope explicitly removes that requirement from the repo contract.

## Resolved In Repo
- Broaden automated regression coverage beyond the earlier representative smoke checks.
- Add a broader warning-free route sweep for major module routes, seeded filters, representative JSON GET endpoints, and financial export breadth.
- Add broader scripted coverage for stable API POST mutations across CT2.
- Add local performance sanity samples and structural accessibility checks.
- Add real browser-driven keyboard and focus evidence for login, nav, approvals, visa upload, and financial export.
- Add broader repeated load sampling beyond the earlier single-pass timing run.
- Add seeded role-specific UAT evidence for manager, front desk, and accounting workflows.

## External Dependency Note
- Upstream CT2 module diagrams and requirements packs are still absent from `docs/diagrams/`, which currently contains only the organizational chart image. That source-of-truth gap is owned by the documentation teams rather than this CT2 implementation workstream.

## Current Recommendation
- Treat CT2 as strongly hardened for the repo-defined scope.
- Treat the remaining repo-owned debt as executed Windows XAMPP evidence plus full native PowerShell parity rather than missing core module implementation or major Linux-side validation depth.
