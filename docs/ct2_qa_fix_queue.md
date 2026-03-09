# CT2 QA Fix Queue

## Priority 1
### Search and filter flows crash with `SQLSTATE[HY093]`
- Severity: Blocker
- Impact: Browser list pages and JSON list endpoints fail when a `search` parameter is provided, breaking routine operator filtering and exposing raw fatal output from API endpoints.
- Confirmed affected browser routes:
  - `module=agents`
  - `module=suppliers`
  - `module=availability`
  - `module=marketing`
  - `module=visa`
  - `module=staff`
- Confirmed affected API endpoints:
  - `/api/ct2_agents.php`
  - `/api/ct2_suppliers.php`
  - `/api/ct2_marketing_campaigns.php`
  - `/api/ct2_promotions.php`
  - `/api/ct2_affiliates.php`
  - `/api/ct2_visa_applications.php`
- Likely affected model layer:
  - [ct2_AgentModel.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/models/ct2_AgentModel.php#L1)
  - [ct2_StaffModel.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/models/ct2_StaffModel.php#L1)
  - [ct2_SupplierModel.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/models/ct2_SupplierModel.php#L1)
  - [ct2_ResourceModel.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/models/ct2_ResourceModel.php#L1)
  - [ct2_MarketingCampaignModel.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/models/ct2_MarketingCampaignModel.php#L1)
  - [ct2_PromotionModel.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/models/ct2_PromotionModel.php#L1)
  - [ct2_AffiliateModel.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/models/ct2_AffiliateModel.php#L1)
  - [ct2_VisaApplicationModel.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/models/ct2_VisaApplicationModel.php#L1)
- Likely root cause:
  repeated named placeholders such as `LIKE :search` are reused several times in one SQL statement, but only one bound value is supplied. This runtime rejects that statement shape and throws `SQLSTATE[HY093]: Invalid parameter number`.
- Fix direction:
  replace repeated `:search` placeholders with unique names per clause, or switch to positional placeholders consistently inside each affected query.
- Acceptance criteria:
  - Filtered HTML routes render `200` without `CT2 application error`.
  - Filtered JSON endpoints return valid JSON envelopes instead of HTML fatal output.
  - Search results still honor the intended multi-column matching behavior.
  - Manual QA scenarios in `docs/ct2_manual_qa_pack.md` pass for every affected module.

## Priority 2
### API fatal output bypasses the JSON error contract when server-side exceptions occur
- Severity: High
- Impact: A database/runtime exception currently leaks HTML/PHP fatal output from API endpoints instead of a JSON envelope, which breaks clients and exposes stack details.
- Evidence:
  every failing search API endpoint in the QA run returned HTML fatal output rather than `{ success, data, error, meta }`.
- Likely affected layer:
  API entrypoints under `ct2_back_office/api/` and any shared bootstrap/error handling used by those scripts.
- Fix direction:
  add centralized exception handling for API scripts so unexpected failures are converted into JSON error responses with appropriate HTTP status codes and no stack trace leakage in production-style runs.
- Acceptance criteria:
  - Any unhandled exception inside `/api/*` returns JSON, not HTML.
  - Error status codes remain accurate.
  - Debug details are not exposed in the response body during normal local runtime validation.

## Priority 3
### Expand regression coverage for search-enabled lists after the blocker fix
- Severity: Medium
- Impact: The current smoke suite verifies route presence and DB readiness, but not search-enabled list behavior. The blocker reached runtime only during manual QA.
- Fix direction:
  extend the DB-backed validation path to exercise at least one seeded search scenario for each major search-enabled module and one API search endpoint.
- Acceptance criteria:
  - The regression suite fails if a filtered list query throws `HY093` or emits non-JSON output from an API endpoint.
  - Seeded demo data used by the tests stays documented in the QA pack.
