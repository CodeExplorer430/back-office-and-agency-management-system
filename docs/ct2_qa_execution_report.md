# CT2 QA Execution Report

## Scope
This report records the first live execution of the CT2 manual QA pack against the integrated `develop` branch after local MySQL import and seeded demo data setup.

## Environment
- Date: March 10, 2026
- Branch: `develop`
- App entrypoint: local PHP server on `http://127.0.0.1:8092/`
- Database: local MySQL `ct2_back_office`
- Seeded users exercised:
  - `ct2admin`
  - `ct2desk`

## Baseline Checks
- `bash ct2_back_office/scripts/ct2_lint.sh`: pass
- `php ct2_back_office/scripts/ct2_smoke_check.php`: pass
- `php ct2_back_office/scripts/ct2_db_smoke_check.php`: pass

## Browser Scenario Results
| Scenario | Actor | Result | Evidence |
| --- | --- | --- | --- |
| Sign in to CT2 dashboard | `ct2admin` | Pass | Login form accepted seeded admin credentials and redirected to dashboard. |
| Dashboard route load | `ct2admin` | Pass | `module=dashboard` returned `200` and rendered Back-Office Dashboard. |
| Agents module load | `ct2admin` | Pass | `module=agents` returned `200`. |
| Suppliers module load | `ct2admin` | Pass | `module=suppliers` returned `200`. |
| Availability module load | `ct2admin` | Pass | `module=availability` returned `200`. |
| Marketing module load | `ct2admin` | Pass | `module=marketing` returned `200`. |
| Financial module load | `ct2admin` | Pass | `module=financial` returned `200`. |
| Visa module load | `ct2admin` | Pass | `module=visa` returned `200`. |
| Staff module load | `ct2admin` | Pass | `module=staff` returned `200`. |
| Approval queue load | `ct2admin` | Pass | `module=approvals` returned `200`. |
| Update seeded agent | `ct2admin` | Pass | Agent `AGT-CT2-002` saved successfully and rendered as `approved` / `active`. |
| Approve seeded workflow item | `ct2admin` | Pass | Approval workflow `#8` saved with success flash and decision note. |
| Reject disallowed visa upload type | `ct2admin` | Pass | `text/plain` upload blocked with `Uploaded document type is not allowed.` |
| Upload allowed visa document | `ct2admin` | Pass | PDF upload succeeded and updated checklist/document view with `ct2_qa_upload.pdf`. |
| Export financial CSV | `ct2admin` | Pass | Export route returned `Content-Type: text/csv` and attachment filename for report run `#1`. |
| Financial page access denied for front desk user | `ct2desk` | Pass | `module=financial` returned `403 Forbidden`. |
| Search/filter on agents page | `ct2admin` | Fail | `module=agents&search=AGT-CT2-001` returned `CT2 application error: SQLSTATE[HY093]: Invalid parameter number`. |
| Search/filter on suppliers page | `ct2admin` | Fail | `module=suppliers&search=SUP-CT2-001` returned `CT2 application error: SQLSTATE[HY093]: Invalid parameter number`. |
| Search/filter on availability page | `ct2admin` | Fail | `module=availability&search=Skyline` returned `CT2 application error: SQLSTATE[HY093]: Invalid parameter number`. |
| Search/filter on marketing page | `ct2admin` | Fail | `module=marketing&search=CT2-MKT-001` returned `CT2 application error: SQLSTATE[HY093]: Invalid parameter number`. |
| Search/filter on visa page | `ct2admin` | Fail | `module=visa&search=VISA-APP-001` returned `CT2 application error: SQLSTATE[HY093]: Invalid parameter number`. |
| Search/filter on staff page | `ct2admin` | Fail | `module=staff&search=STF-CT2-001` returned `CT2 application error: SQLSTATE[HY093]: Invalid parameter number`. |

## API Scenario Results
| Scenario | Actor | Result | Evidence |
| --- | --- | --- | --- |
| Module status endpoint | `ct2admin` | Pass | `/api/ct2_module_status.php` returned JSON with all six modules marked `implemented`. |
| Login endpoint rejects wrong method | anonymous | Pass | `GET /api/ct2_auth_login.php` returned `405` with JSON `Method not allowed.` |
| Agents endpoint denies anonymous access | anonymous | Pass | `GET /api/ct2_agents.php` returned `403` with JSON `Forbidden.` |
| Financial reports endpoint denies front desk role | `ct2desk` | Pass | `GET /api/ct2_financial_reports.php` returned `403` with JSON `Forbidden.` |
| Financial export metadata | `ct2admin` | Pass | `/api/ct2_financial_exports.php?ct2_report_run_id=1` returned JSON with `row_count` and `download_url`. |
| Agents search endpoint | `ct2admin` | Fail | `/api/ct2_agents.php?search=AGT-CT2-001` emitted HTML fatal with `SQLSTATE[HY093]`. |
| Suppliers search endpoint | `ct2admin` | Fail | `/api/ct2_suppliers.php?search=SUP-CT2-001` emitted HTML fatal with `SQLSTATE[HY093]`. |
| Promotions search endpoint | `ct2admin` | Fail | `/api/ct2_promotions.php?search=SPRING` emitted HTML fatal with `SQLSTATE[HY093]`. |
| Affiliates search endpoint | `ct2admin` | Fail | `/api/ct2_affiliates.php?search=AFF-CT2-001` emitted HTML fatal with `SQLSTATE[HY093]`. |
| Marketing campaigns search endpoint | `ct2admin` | Fail | `/api/ct2_marketing_campaigns.php?search=CT2-MKT-001` emitted HTML fatal with `SQLSTATE[HY093]`. |
| Visa applications search endpoint | `ct2admin` | Fail | `/api/ct2_visa_applications.php?search=VISA-APP-001` emitted HTML fatal with `SQLSTATE[HY093]`. |

## Findings Summary
### Confirmed defect
- Search and filter flows are release-blocking across multiple CT2 modules. When a `search` parameter is supplied, several list queries crash with `SQLSTATE[HY093]: Invalid parameter number` instead of returning filtered HTML or JSON.

### Confirmed correct behavior
- Auth, session handling, role boundaries, approval decision flow, visa upload allowlist enforcement, and financial CSV export all behaved correctly in the executed scenarios.
- The failed `text/plain` visa upload is expected behavior and not a defect.

## Release Recommendation
- Do not promote `develop` to `main` until the search/filter defect is fixed and the QA pass is rerun for the affected modules and endpoints.

## Rerun After Blocker Fix
- Date: March 10, 2026
- Branch: `develop`
- Validation rerun completed after repairing multi-column search bindings, adding API exception wrapping, and extending DB-backed search regression checks.

### Browser search rerun
| Scenario | Actor | Result | Evidence |
| --- | --- | --- | --- |
| Search/filter on agents page | `ct2admin` | Pass | `module=agents&search=AGT-CT2-001` returned `200`, rendered the seeded match, and no longer emitted `CT2 application error`. |
| Search/filter on suppliers page | `ct2admin` | Pass | `module=suppliers&search=SUP-CT2-001` returned `200` and rendered the seeded match. |
| Search/filter on availability page | `ct2admin` | Pass | `module=availability&search=Skyline` returned `200` and rendered the seeded resource match. |
| Search/filter on marketing page | `ct2admin` | Pass | `module=marketing&search=CT2-MKT-001` returned `200` and rendered the seeded campaign match. |
| Search/filter on visa page | `ct2admin` | Pass | `module=visa&search=VISA-APP-001` returned `200` and rendered the seeded application match. |
| Search/filter on staff page | `ct2admin` | Pass | `module=staff&search=STF-CT2-001` returned `200` and rendered the seeded staff match. |

### API rerun
| Scenario | Actor | Result | Evidence |
| --- | --- | --- | --- |
| Agents search endpoint | `ct2admin` | Pass | `/api/ct2_agents.php?search=AGT-CT2-001` returned `200` JSON with `success: true` and the seeded match. |
| Suppliers search endpoint | `ct2admin` | Pass | `/api/ct2_suppliers.php?search=SUP-CT2-001` returned `200` JSON with no HTML fatal output. |
| Marketing campaigns search endpoint | `ct2admin` | Pass | `/api/ct2_marketing_campaigns.php?search=CT2-MKT-001` returned `200` JSON with the seeded campaign match. |
| Promotions search endpoint | `ct2admin` | Pass | `/api/ct2_promotions.php?search=PROMO-CT2-001` returned `200` JSON with the seeded promotion match. |
| Affiliates search endpoint | `ct2admin` | Pass | `/api/ct2_affiliates.php?search=AFF-CT2-001` returned `200` JSON with the seeded affiliate match. |
| Visa applications search endpoint | `ct2admin` | Pass | `/api/ct2_visa_applications.php?search=VISA-APP-001` returned `200` JSON with the seeded application match. |
| Agents endpoint denies anonymous access | anonymous | Pass | `/api/ct2_agents.php` still returned `403` after the fix. |
| Login endpoint rejects wrong method | anonymous | Pass | `GET /api/ct2_auth_login.php` still returned `405` after the fix. |

## Current Recommendation
- The search/filter blocker is resolved on `develop`.
- The current local QA evidence supports moving to broader release signoff or final promotion review, assuming no additional issues are introduced outside this rerun scope.

## Internal Hardening Validation Rerun
- Date: March 10, 2026
- Branch: `develop`
- Validation entrypoint: `bash ct2_back_office/scripts/ct2_runtime_hardening_check.sh`
- Scope: representative positive and negative browser/API security checks plus direct audit-log assertions

### Hardening scenario results
| Scenario | Actor | Result | Evidence |
| --- | --- | --- | --- |
| Dashboard and availability read path | `ct2admin` | Pass | Dashboard loaded; availability search rendered `Skyline Coaster 18-Seater`, `CT1-BKG-1001`, and `NAA-4581`. |
| Agent update audit assertion | `ct2admin` | Pass | `agents.update` audit count incremented after a seeded agent save. |
| Supplier onboarding invalid CSRF rejection | `ct2admin` | Pass | Invalid token redirected back with flash error; onboarding state and audit count did not change. |
| Supplier onboarding positive audit assertion | `ct2admin` | Pass | `suppliers.onboarding_update` audit count incremented and updated review notes persisted. |
| Approval invalid CSRF rejection | `ct2admin` | Pass | Invalid token redirected back with approval error flash; workflow status and audit count did not change. |
| Approval decision positive audit assertion | `ct2admin` | Pass | `approvals.decide` audit count incremented and the seeded supplier workflow moved to `approved`. |
| Visa checklist invalid CSRF rejection | `ct2admin` | Pass | Invalid token redirected back with error flash; checklist status and audit count did not change. |
| Visa upload positive audit assertion | `ct2admin` | Pass | PNG upload succeeded, checklist status moved to `verified`, stored file path existed, and `visa.document_checklist_update` audit count incremented. |
| Financial reconciliation positive audit assertion | `ct2admin` | Pass | Reconciliation flag updated to `resolved` and `financial.flag_update` audit count incremented. |
| Financial CSV export | `ct2admin` | Pass | Export returned `Content-Type: text/csv` and included the seeded supplier reconciliation data. |
| Stale-session browser write rejection | anonymous after logout | Pass | Protected agent save returned `302` to the login route and did not create an audit row. |
| Protected API JSON negative paths | anonymous | Pass | `GET /api/ct2_agents.php` returned JSON `403`; `GET /api/ct2_auth_login.php` returned JSON `405`; neither leaked HTML. |

### Hardening conclusion
- No new blocker was found during the internal hardening rerun.
- The repo now has direct repeatable evidence for representative CSRF rejection, stale-session rejection, protected API JSON behavior, and audit-log creation across major CT2 write paths.
