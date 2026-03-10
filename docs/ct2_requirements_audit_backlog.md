# CT2 Requirements Audit Backlog

## Purpose
This backlog captures the remaining repo-owned actions surfaced by `docs/ct2_requirements_traceability_matrix.md`. It excludes upstream documentation work that belongs to other teams and focuses on the validation debt that can still be closed inside this repository.

## Priority 1
### Add executed Windows XAMPP runtime evidence
- Type: Validation gap
- Impact: The CT2 runtime contract is cross-platform by design, but in-repo runtime proof is still strongest on the local Linux LAMP environment.
- Expected work:
  execute the deployment guide, DB smoke, runtime hardening script, and a short browser/API pass on a Windows XAMPP setup;
  record the result in the Windows result template and copy the evidence back into the QA and NFR docs.
- Acceptance criteria:
  the repo contains explicit Windows XAMPP execution evidence,
  and cross-platform compatibility is no longer only code-aligned and documented.

## Resolved In Repo
- Broaden automated regression coverage beyond the earlier representative smoke checks.
- Add a broader warning-free route sweep for major module routes, seeded filters, representative JSON GET endpoints, and financial export breadth.
- Add broader scripted coverage for stable API POST mutations across CT2.
- Add local performance sanity samples and structural accessibility checks.
- Add real browser-driven keyboard and focus evidence for login, nav, approvals, visa upload, and financial export.
- Add broader repeated load sampling beyond the earlier single-pass timing run.
- Add seeded role-specific UAT evidence for manager, front desk, and accounting workflows.

## External Dependency Note
- Upstream CT2 module workflow diagrams and requirements packs are still absent from `docs/diagrams/`. The organizational chart is intentionally separate actor/context material and does not replace the missing workflow diagrams. That source-of-truth gap is owned by the documentation teams rather than this CT2 implementation workstream.

## Current Recommendation
- Treat CT2 as strongly hardened for the repo-defined scope.
- Treat the remaining repo-owned debt as executed Windows XAMPP evidence rather than missing core module implementation or major Linux-side validation depth.
