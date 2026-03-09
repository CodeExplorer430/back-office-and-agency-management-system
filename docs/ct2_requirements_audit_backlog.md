# CT2 Requirements Audit Backlog

## Purpose
This backlog captures the remaining repo-owned actions surfaced by `docs/ct2_requirements_traceability_matrix.md`. It excludes upstream documentation work that belongs to other teams and focuses on the validation debt that can still be closed inside this repository.

## Priority 1
### Add executed keyboard/focus accessibility evidence and broader load sampling
- Type: Validation gap
- Impact: Local performance sanity and structural form/heading checks are now directly evidenced, but keyboard navigation, focus order, focus visibility, and broader load behavior still rely on manual or absent validation.
- Expected work:
  execute a real browser-based keyboard walkthrough on the supported runtime targets;
  add a slightly broader reproducible load or repeated-request sample beyond the current single-user sanity timing pass;
  record the results in `docs/ct2_performance_accessibility_evidence.md` and `docs/ct2_nfr_evidence.md`.
- Acceptance criteria:
  the repo contains direct keyboard/focus evidence rather than only structural markup checks,
  and the remaining performance note is stronger than a single-seeded sanity sample.

## Priority 2
### Add executed Windows XAMPP runtime evidence
- Type: Validation gap
- Impact: The CT2 runtime contract is cross-platform by design, but in-repo runtime proof is still strongest on the local Linux LAMP environment.
- Expected work:
  execute the deployment guide, DB smoke, runtime hardening script, and a short browser/API pass on a Windows XAMPP setup;
  record the result in the deployment or NFR docs.
- Acceptance criteria:
  the repo contains explicit Windows XAMPP execution evidence,
  and cross-platform compatibility is no longer only code-aligned and documented.

## Priority 3
### Add narrower role-specific UAT evidence for business judgment flows
- Type: Validation gap
- Impact: Stable browser and API mutation flows are now script-covered, but role-specific browser walkthroughs still require human judgment and documentation.
- Expected work:
  keep the current scripted baseline and tighten the manual QA pack around role-driven UAT scenarios such as approvals judgment, visa operator handling, and accounting/operator signoff.
- Acceptance criteria:
  the remaining manual QA surface is explicitly limited to usability or business-judgment checks,
  and each role-specific scenario has a clear evidence trail in the QA report.

## Resolved In Repo
- Broaden automated regression coverage beyond the earlier representative smoke checks.
- Add a broader warning-free route sweep for major module routes, seeded filters, representative JSON GET endpoints, and financial export breadth.
- Add broader scripted coverage for stable API POST mutations across CT2.
- Add local performance sanity samples and structural accessibility checks.

## External Dependency Note
- Upstream CT2 diagrams and requirements packs are still absent from `docs/diagrams/`, but that source-of-truth gap is owned by the documentation teams rather than this CT2 implementation workstream.

## Current Recommendation
- Treat CT2 as strongly hardened for the repo-defined scope.
- Treat the remaining debt as performance/accessibility evidence, Windows XAMPP execution evidence, and narrower API/UAT validation depth rather than missing core module implementation.
