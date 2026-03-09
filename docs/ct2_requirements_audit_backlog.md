# CT2 Requirements Audit Backlog

## Purpose
This backlog captures the open actions surfaced by `docs/ct2_requirements_traceability_matrix.md`. It prioritizes gaps that block strict requirements closure ahead of lower-risk evidence improvements.

## Priority 1
### Import the missing CT2 diagrams and requirements pack
- Type: Source-of-truth gap
- Impact: The repo cannot currently prove strict conformance to the original CT2 functional and non-functional requirements because `docs/diagrams/` contains only an organizational chart.
- Expected work:
  collect the actual CT2 workflow, process, use-case, and requirements diagrams from the documentation teams and add them under `docs/diagrams/`.
- Acceptance criteria:
  the repo contains the real CT2 diagrams pack,
  the traceability matrix gains an upstream-diagram source column,
  and every matrix row is updated from `Source Gap` or repo-derived wording to explicit upstream mapping.

## Priority 2
### Expand non-functional validation evidence
- Type: Validation gap
- Impact: Security, operational, and runtime quality claims are mostly supported, but performance, accessibility, and broad warning-free coverage are not directly evidenced in the repo.
- Expected work:
  define concrete NFR checks for performance, accessibility/usability, and warning-free runtime expectations;
  add lightweight reproducible validation steps or scripts where practical.
- Acceptance criteria:
  the audit matrix no longer marks performance/accessibility as `Unverified`,
  and the repo contains a repeatable method to demonstrate those NFRs.

## Priority 3
### Broaden automated regression coverage beyond current smoke checks
- Type: Validation gap
- Impact: Current confidence depends heavily on manual QA plus targeted smoke scripts. That is acceptable for release history, but weak for ongoing change protection.
- Expected work:
  extend the DB-backed smoke layer and/or add additional scripted HTTP checks for representative cross-module workflows and negative cases.
- Acceptance criteria:
  representative auth, approval, upload, export, and cross-module read paths are covered by repeatable scripted checks,
  and the audit matrix can point to direct automated evidence for more rows currently marked `Partially Implemented`.

## Priority 4
### Add direct audit-log verification to the QA contract
- Type: Validation gap
- Impact: Audit logging is implemented, but current QA artifacts do not explicitly verify audit rows for each major state-changing path.
- Expected work:
  add audit-log assertions to the QA pack or a DB-backed validation script for representative agent, approval, supplier, visa, and financial actions.
- Acceptance criteria:
  the matrix can upgrade audit logging from `Partially Implemented` to `Implemented`,
  and the QA docs/scripts name the expected audit evidence.

## Priority 5
### Tighten negative-path coverage for browser security controls
- Type: Validation gap
- Impact: Positive CSRF/session/permission paths are exercised, but direct invalid-CSRF and stale-session posting evidence is still mostly procedural rather than recorded.
- Expected work:
  extend QA or scripted validation with invalid token and expired-session checks on representative state-changing routes.
- Acceptance criteria:
  the matrix can point to direct negative-path evidence for CSRF/session controls,
  and security controls remain proven after future changes.

## Current Recommendation
- Treat CT2 as release-ready for the repo-defined scope.
- Treat strict “all requirements from the original diagrams are implemented” as not yet fully provable until the missing upstream CT2 diagrams are added and mapped.
