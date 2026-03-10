# CT2 Requirements Traceability Matrix

## Purpose
This matrix traces the current `CORE TRANSACTION 2: Back-Office and Agency Management System` implementation against the source of truth that actually exists in this repository today. It does not claim strict conformance to a module-level CT2 diagram pack, because `docs/diagrams/` currently contains the intentionally separated organizational chart but not the CT2 workflow or requirements diagrams.

## Source Of Truth Used For This Matrix
- `ct2_back_office/ct2_setup.sql`
- `ct2_back_office/controllers/*`
- `ct2_back_office/models/*`
- `ct2_back_office/views/*`
- `ct2_back_office/api/*`
- `docs/ct2_manual_qa_pack.md`
- `docs/ct2_api_validation.md`
- `docs/ct2_qa_execution_report.md`
- `docs/ct2_nfr_evidence.md`
- `docs/ct2_release_summary_2026-03-10.md`
- `docs/ct2_deployment_guide.md`
- `docs/ct2_operator_runbook.md`
- `docs/ct2_technical_debt_register.md`

## Status Legend
- `Implemented`: present in code and supported by direct validation evidence.
- `Partially Implemented`: present in code, but coverage is narrower than the intended capability or only partially validated.
- `Unverified`: plausible or documented in repo standards, but not proven against a direct validation artifact.
- `Source Gap`: cannot be strictly closed because the upstream diagrams/requirements pack is not present in the repo.

## Functional Requirements
| ID | Requirement | Source Artifact | Implementation Evidence | Validation Evidence | Status | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| FR-01 | Users authenticate into CT2 and are restricted by role and permission. | `docs/ct2_manual_qa_pack.md`, `docs/ct2_operator_runbook.md` | `ct2_back_office/config/ct2_bootstrap.php`, `ct2_back_office/controllers/ct2_AuthController.php`, `ct2_back_office/models/ct2_UserModel.php` | `docs/ct2_qa_execution_report.md` login, `403`, and `405` results | Implemented | Seeded roles cover admin, manager, team lead, front desk, and finance. |
| FR-02 | Dashboard shows cross-module CT2 operational summaries. | `docs/ct2_manual_qa_pack.md` | `ct2_back_office/controllers/ct2_DashboardController.php`, `ct2_back_office/views/dashboard/ct2_home.php` | Dashboard checks in `docs/ct2_manual_qa_pack.md` and `docs/ct2_qa_execution_report.md` | Implemented | Includes summary cards and operational tables. |
| FR-03 | Travel agent and staff records can be viewed, updated, and linked. | `docs/ct2_manual_qa_pack.md`, release summary | `ct2_back_office/controllers/ct2_AgentController.php`, `ct2_back_office/controllers/ct2_StaffController.php`, `ct2_back_office/models/ct2_AssignmentModel.php` | Agent/staff workflow in `docs/ct2_manual_qa_pack.md`; agent update in `docs/ct2_qa_execution_report.md` | Implemented | Includes assignment visibility and approval status handling. |
| FR-04 | Approval workflows can be reviewed and decided from a shared queue. | `docs/ct2_manual_qa_pack.md`, `docs/ct2_api_validation.md` | `ct2_back_office/controllers/ct2_ApprovalController.php`, `ct2_back_office/models/ct2_ApprovalModel.php`, `ct2_back_office/api/ct2_approvals.php` | Approval queue scenario in `docs/ct2_manual_qa_pack.md`; approval decision pass in `docs/ct2_qa_execution_report.md` | Implemented | Shared approval flow is reused across modules. |
| FR-05 | Suppliers support onboarding, contracts, KPIs, and relationship notes. | `docs/ct2_manual_qa_pack.md`, `docs/ct2_api_validation.md` | `ct2_back_office/controllers/ct2_SupplierController.php`, supplier models, supplier APIs | Supplier walkthrough in QA pack; expanded scripted regression section in `docs/ct2_qa_execution_report.md` | Implemented | Onboarding plus contract/KPI/note mutation paths now have direct scripted evidence and audit assertions. |
| FR-06 | Availability planning supports packages, resources, allocations, vehicles, drivers, and dispatch orders. | `docs/ct2_manual_qa_pack.md`, release summary | `ct2_back_office/controllers/ct2_AvailabilityController.php`, resource/allocation/dispatch models and APIs | Availability walkthrough in QA pack; expanded scripted regression section in `docs/ct2_qa_execution_report.md` | Implemented | Resource, package, allocation, block, vehicle, driver, dispatch, and maintenance flows now have direct scripted evidence and audit assertions. |
| FR-07 | Marketing supports campaigns, promotions, vouchers, affiliates, clicks, redemptions, and metrics. | `docs/ct2_manual_qa_pack.md`, `docs/ct2_api_validation.md` | `ct2_back_office/controllers/ct2_MarketingController.php`, campaign/promotion/affiliate models and APIs | Marketing walkthrough in QA pack; expanded scripted regression and route-matrix sections in `docs/ct2_qa_execution_report.md` | Implemented | Campaign, promotion, voucher, affiliate, referral, redemption, metric, and note flows now have direct scripted evidence. |
| FR-08 | Visa operations support applications, checklist verification, payments, notifications, notes, and uploads. | `docs/ct2_manual_qa_pack.md`, release summary | `ct2_back_office/controllers/ct2_VisaController.php`, visa models, `ct2_UploadService.php`, visa APIs | Visa upload and checklist verification in QA pack and execution report | Implemented | Browser upload path is proven; JSON upload is intentionally not part of the API contract. |
| FR-09 | Financial reporting supports report catalog, snapshots, reconciliation, and CSV export. | `docs/ct2_manual_qa_pack.md`, `docs/ct2_api_validation.md`, release summary | `ct2_back_office/controllers/ct2_FinancialController.php`, financial models, financial APIs | Financial route, export, and metadata checks in QA execution report; release smoke verified export after PHP 8.4 fix | Implemented | CSV export warning was fixed before release promotion. |
| FR-10 | Search/filter behavior works across major CT2 module lists. | QA execution report and fix queue | Search-capable module models and controllers, shared search helper in `ct2_back_office/models/ct2_BaseModel.php` | Search rerun section in `docs/ct2_qa_execution_report.md` | Implemented | Blocker was fixed and rerun across browser and API paths. |
| FR-11 | JSON APIs expose representative read/write CT2 operations. | `docs/ct2_api_validation.md` | `ct2_back_office/api/*`, `ct2_back_office/scripts/ct2_api_post_regression_check.sh` | API validation guide and API POST regression section in `docs/ct2_qa_execution_report.md` | Implemented | Read and stable write coverage are now both directly evidenced under the seeded environment. |
| FR-12 | CT2 preserves upstream system ownership for bookings, customers, suppliers, and payments where applicable. | Operator runbook, release summary | External ID fields in schema and models, module payloads, release docs | Operator runbook and deployment docs | Implemented | This is an intentional architecture boundary, not a missing feature. |

## Non-Functional Requirements
| ID | Requirement | Source Artifact | Implementation Evidence | Validation Evidence | Status | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| NFR-01 | Use Vanilla OOP PHP only; no frameworks, Composer, or NPM. | `docs/ct2_deployment_guide.md` | Repo structure and direct PHP implementation | Repository inspection | Implemented | No framework tooling is present. |
| NFR-02 | Use PDO prepared statements with MySQL only. | `docs/ct2_operator_runbook.md` | `ct2_back_office/config/ct2_database.php`, model query patterns | Code inspection and runtime DB smoke | Implemented | Query style is consistently PDO-based. |
| NFR-03 | Prefix CT2 artifacts with `ct2_`. | `ct2_back_office/ct2_setup.sql`, `docs/ct2_deployment_guide.md` | Files, tables, APIs, classes, CSS, and routes are prefixed | Codebase inspection and smoke checks | Implemented | Minor non-prefixed PHP language constructs are outside CT2 naming scope. |
| NFR-04 | Enforce CSRF and role checks on state-changing browser flows. | QA pack, NFR evidence | `ct2_bootstrap.php`, controller assertions, permission checks, `ct2_back_office/scripts/ct2_runtime_hardening_check.sh` | `docs/ct2_qa_execution_report.md`, `docs/ct2_nfr_evidence.md` | Implemented | Invalid-CSRF and stale-session negative checks now have direct scripted evidence on representative protected browser writes. |
| NFR-05 | APIs return JSON with stable envelope and no HTML leakage. | API validation guide | `ct2_bootstrap.php`, API entrypoints, centralized API exception handler | API checks and post-fix rerun in `docs/ct2_qa_execution_report.md` | Implemented | Search-related HTML leakage was fixed before release. |
| NFR-06 | Audit logging exists for state-changing back-office actions. | QA pack, NFR evidence | `ct2_back_office/models/ct2_AuditLogModel.php`, controller audit calls, `ct2_back_office/scripts/ct2_regression_probe.php`, `ct2_back_office/scripts/ct2_runtime_hardening_check.sh` | `docs/ct2_nfr_evidence.md`, updated QA pack audit assertions | Implemented | Representative audit assertions now directly prove both positive writes and rejected negative paths. |
| NFR-07 | Runtime supports LAMP and Windows XAMPP through the same config contract. | Deployment guide, operator runbook | TCP-first DB config, local override template, upload/session paths, PowerShell validation entrypoints | Local LAMP validation plus deployment docs | Partially Implemented | Windows compatibility is code-aligned and PowerShell-launchable, but executed Windows evidence is still pending. |
| NFR-08 | Release and deployment process is documented and repeatable. | Release summary, deployment guide, operator runbook | Release tag, GitHub release, release docs, smoke scripts | Published GitHub release and local release verification | Implemented | Release state is now represented in repo docs and GitHub. |
| NFR-09 | No warnings, notices, or fatal errors under normal validated flows. | NFR evidence | Smoke scripts, route matrix script, runtime hardening script, recent CSV export fix | `docs/ct2_qa_execution_report.md`, `docs/ct2_nfr_evidence.md` | Implemented | Proved on major module routes, seeded filters, representative JSON GET endpoints, upload, approval, and export flows; not intended as exhaustive fuzzing evidence. |
| NFR-10 | Performance, accessibility, and load characteristics are explicitly evidenced. | NFR evidence need | `ct2_back_office/scripts/ct2_nfr_sanity_check.sh`, `ct2_back_office/scripts/ct2_browser_accessibility_check.sh`, `ct2_back_office/scripts/ct2_load_profile_check.sh`, `docs/ct2_performance_accessibility_evidence.md`, `docs/ct2_nfr_evidence.md` | NFR sanity, browser accessibility, and repeated load sections in `docs/ct2_qa_execution_report.md` | Implemented | Evidence is explicit for the supported local runtime; this is still not a formal certification claim or large-scale performance benchmark. |
| NFR-11 | Formatting checks cover repo text artifacts with zero warnings or drift. | Deployment and QA docs | `ct2_back_office/scripts/ct2_format_check.php`, `ct2_back_office/scripts/ct2_format_check.sh`, `ct2_back_office/scripts/ct2_format_check.ps1` | Repo-native format check output | Implemented | Covers PHP, CSS, Markdown, SQL, shell, and PowerShell text artifacts for BOM, trailing whitespace, and EOF newline hygiene. |

## Feature And Source Coverage Notes
| ID | Requirement | Source Artifact | Implementation Evidence | Validation Evidence | Status | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| FT-01 | Seeded demo data supports cross-module UAT and technical validation. | QA pack, setup SQL | `ct2_back_office/ct2_setup.sql`, DB smoke checks | QA pack and DB smoke | Implemented | Seed coverage spans all six modules plus approvals. |
| FT-02 | Shared browser upload flow exists for CT2 documents. | Release summary, QA pack | `ct2_back_office/config/ct2_UploadService.php`, visa controller and view | Upload path verified in QA execution report and release smoke | Implemented | Shared upload transport is only adopted by visa in current release. |
| FT-03 | Strict traceability to a CT2 module diagram pack can be proven. | `docs/diagrams/` | Organizational chart present; workflow diagrams absent | Repo inspection | Source Gap | The organizational chart is valid actor/context documentation, but the CT2 module process/requirements diagrams are not present in the repo. |
| FT-04 | Windows operators can launch the validation suite from native PowerShell. | Windows validation pack, operator runbook | `ct2_back_office/scripts/*.ps1`, `ct2_back_office/scripts/ct2_validation_suite.ps1`, shared PHP and JS validators for route, runtime, API, NFR, load, role, and browser checks | Linux `pwsh` execution plus repo smoke checks | Implemented | Native PowerShell entrypoints now exist across the full CT2 validation surface. Executed Windows XAMPP evidence is tracked separately as runtime proof, not as an implementation gap. |

## Overall Assessment
- CT2 is strongly implemented and release-validated for the repo-defined scope.
- The main remaining repo-owned gap is executed Windows XAMPP runtime evidence.
- Upstream diagram traceability remains an external dependency rather than an active CT2 implementation gap in this repository.
