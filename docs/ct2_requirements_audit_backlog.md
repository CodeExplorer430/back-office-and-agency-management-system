# CT2 Requirements Audit Backlog

## Purpose
This backlog captures the remaining repo-owned actions surfaced by `docs/ct2_requirements_traceability_matrix.md`. It excludes upstream documentation work that belongs to other teams and focuses on the validation debt that can still be closed inside this repository.

## Priority 1
### Add formal performance and accessibility evidence
- Type: Validation gap
- Impact: Security, audit, and representative runtime hardening now have direct evidence, but performance and accessibility still rely on code inspection and manual usability impressions.
- Expected work:
  define lightweight but real accessibility checks such as keyboard navigation and heading/form-label review;
  add a reproducible performance sanity check for the seeded local environment;
  record those results in `docs/ct2_nfr_evidence.md`.
- Acceptance criteria:
  the audit matrix can upgrade performance/accessibility from `Partially Implemented` to `Implemented`,
  and the repo contains a repeatable method to demonstrate those NFRs.

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
### Add broader scripted coverage for API POST mutations and role-specific UAT paths
- Type: Validation gap
- Impact: Core browser routes, seeded filters, exports, and major mutation flows are now script-covered, but API write paths and role-specific business walkthroughs still rely more heavily on manual execution.
- Expected work:
  add targeted scripted checks for stable API POST mutation endpoints and preserve a shorter manual pack for role-driven UAT steps that require human judgment.
- Acceptance criteria:
  representative browser and API mutation flows are both repeatable under the seeded environment,
  and the remaining manual QA surface is explicitly limited to usability or business-judgment checks.

## Resolved In Repo
- Broaden automated regression coverage beyond the earlier representative smoke checks.
- Add a broader warning-free route sweep for major module routes, seeded filters, representative JSON GET endpoints, and financial export breadth.

## External Dependency Note
- Upstream CT2 diagrams and requirements packs are still absent from `docs/diagrams/`, but that source-of-truth gap is owned by the documentation teams rather than this CT2 implementation workstream.

## Current Recommendation
- Treat CT2 as strongly hardened for the repo-defined scope.
- Treat the remaining debt as performance/accessibility evidence, Windows XAMPP execution evidence, and narrower API/UAT validation depth rather than missing core module implementation.
