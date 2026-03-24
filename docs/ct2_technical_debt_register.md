# CT2 Technical Debt Register

## Purpose
This register captures repo-owned CT2 debt that is still relevant after the current release and hardening work. It separates actionable debt from accepted architecture boundaries and from future enhancement ideas.

## Current Quality Gate
- The active repo policy is `docs/ct2_quality_gate.md`.
- Debt tracking does not weaken the zero-warning, zero-skipped-gate requirement for current work.

## Active Technical Debt
### Windows validation execution still pending
- Type: Validation debt
- Impact: Cross-platform support is code-aligned and documented, but the repo still lacks imported runtime evidence from a real Windows XAMPP execution.
- Current state: `docs/ct2_windows_xampp_validation_pack.md` and `docs/ct2_windows_xampp_result_template.md` are ready for operator execution.
- Exit criteria: Execute the Windows run, import the results into the NFR and QA docs, and update the audit backlog.

### Traceability to upstream module diagrams is blocked by missing source material
- Type: Source-of-truth debt
- Impact: The repo cannot prove strict CT2 module conformance against external process diagrams because `docs/diagrams/` currently contains the intentionally separated organizational chart, but not the module workflow diagrams.
- Current state: The requirements matrix correctly treats the organizational chart as actor/context material and the missing workflow diagrams as the actual source gap.
- Exit criteria: The external documentation team adds the actual CT2 module diagrams to the repo, then CT2 refreshes the traceability matrix and validation mapping.

## Accepted Limitations
### Shared upload transport is only adopted in the visa workflow
- Reason: The current client-approved scope only required browser upload support where the visa module needed document intake.
- Impact: Other document-heavy CT2 flows still rely on metadata or linked references instead of direct browser upload.

### CT2 preserves external ownership boundaries
- Reason: Customer, booking, payment, and wider financial system ownership belong to adjacent ERP domains.
- Impact: CT2 models those records through external IDs and reference metadata rather than becoming a new system of record.

## Enhancement Opportunities
### Extend the shared upload service to other document-heavy CT2 flows
- Candidate areas: supplier compliance documents, contract attachments, and operational archive support.

### Increase route and browser automation depth
- Candidate areas: broader route/action fuzz coverage, more mobile viewport assertions, and screenshot-backed UI regressions.

### Expand export and reporting ergonomics
- Candidate areas: scheduled exports, additional export formats, and richer operational drill-down filters.

### Remove duplicated validation configuration between Bash and PowerShell entrypoints
- Candidate areas: shared parameter loading, shared host/port handling, and a common validation manifest.
- Current mitigation: keep both `ct2_validation_suite.sh` and `ct2_validation_suite.ps1` aligned to `docs/ct2_quality_gate.md`.
