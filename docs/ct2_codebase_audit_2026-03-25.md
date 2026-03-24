# CT2 Codebase Audit 2026-03-25

## Purpose
This document records the March 25, 2026 deep codebase audit of the CT2 repository. It is findings-first and scoped to repo-owned implementation, validation, release process, and documentation concerns. It does not replace the release summaries or the strict quality gate; it complements them with the unresolved risks that still need remediation planning.

## Audit Scope
- `ct2_back_office/config/*`
- `ct2_back_office/controllers/*`
- `ct2_back_office/models/*`
- `ct2_back_office/api/*`
- `ct2_back_office/scripts/*`
- `docs/*`
- `.github/workflows/*`

## Resolution Status
- This document remains the historical findings snapshot from the March 25, 2026 audit pass.
- The auth/session rotation, inactive-user API login handling, browser-safe 500 handling, and API RBAC parity findings recorded below were remediated in the subsequent auth/API hardening batch on the same branch.
- The remaining repo-owned gaps after that batch are the executed Windows XAMPP evidence run and the external upstream diagram source gap.

## Findings
### P0: Session rotation is missing on successful browser and API login
- Severity: Security
- Evidence:
  - [ct2_AuthController.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/controllers/ct2_AuthController.php#L41) records the current session and stores the authenticated user without calling `session_regenerate_id(true)`.
  - [ct2_auth_login.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/api/ct2_auth_login.php#L25) does the same for API login.
  - [ct2_bootstrap.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/config/ct2_bootstrap.php#L24) starts sessions, but no cookie hardening or post-auth session rotation is applied centrally.
- Impact:
  authenticated sessions can continue on a pre-login session identifier, which is a classic session fixation weakness and also keeps the CSRF token tied to the old session lifecycle.
- Coverage gap:
  the strict suite validates login success and stale-session rejection, but it does not currently assert session ID rotation on successful authentication.

### P0: API write authorization is flatter than the browser permission model
- Severity: Security
- Evidence:
  - Browser routes require module-specific permissions such as [ct2_AgentController.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/controllers/ct2_AgentController.php#L24) and [ct2_SupplierController.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/controllers/ct2_SupplierController.php#L67).
  - Representative API write endpoints such as [ct2_agents.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/api/ct2_agents.php#L7), [ct2_suppliers.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/api/ct2_suppliers.php#L7), and [ct2_visa_applications.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/api/ct2_visa_applications.php#L7) permit writes based on `api.access` alone.
  - Seeded accounting users are granted `api.access` in [ct2_setup.sql](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/ct2_setup.sql#L1057), but they do not have equivalent browser-side manage permissions for agents, suppliers, marketing, or visa.
- Impact:
  users with broad API access can potentially mutate module data that their browser role is not intended to manage, creating RBAC drift between UI and API channels.
- Coverage gap:
  [ct2_api_post_regression_check.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/scripts/ct2_api_post_regression_check.php#L79) proves anonymous denial and [ct2_api_post_regression_check.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/scripts/ct2_api_post_regression_check.php#L528) proves a financial-role denial, but it does not assert module-by-module API permission parity.

### P1: Browser exception handling still discloses raw internal error messages
- Severity: Security / Robustness
- Evidence:
  - [ct2_index.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/ct2_index.php#L45) catches `Throwable` and echoes the exception message to the browser.
  - [ct2_bootstrap.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/config/ct2_bootstrap.php#L5) globally enables `display_errors=1` for non-API execution.
- Impact:
  production browser failures can expose implementation details, SQL-derived exception text, or local path hints instead of a safe user-facing error response with server-side logging only.
- Coverage gap:
  the strict suite enforces no warning leakage on validated flows, but it does not intentionally trigger an uncaught browser exception and assert the sanitized failure contract.

### P1: API login handles inactive accounts inconsistently with browser login
- Severity: Correctness / Security hygiene
- Evidence:
  - Browser login rejects inactive users in [ct2_AuthController.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/controllers/ct2_AuthController.php#L32).
  - API login in [ct2_auth_login.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/api/ct2_auth_login.php#L20) does not check `is_active` before updating `last_login_at` and recording a session log entry.
  - Hydration later fails because [ct2_UserModel.php](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/ct2_back_office/models/ct2_UserModel.php#L21) only returns active users, which makes the API respond with a `500` instead of an auth failure.
- Impact:
  inactive credentials can mutate login metadata and produce the wrong failure class, which is inconsistent with the browser contract and complicates operational interpretation.
- Coverage gap:
  the strict suite covers valid admin login and invalid-password login only; it does not exercise inactive-user auth behavior.

### P2: Documentation overstates current auth and API hardening
- Severity: Documentation accuracy
- Evidence:
  - [ct2_requirements_traceability_matrix.md](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/docs/ct2_requirements_traceability_matrix.md#L32) currently marks role-restricted authentication as fully implemented.
  - [ct2_nfr_evidence.md](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/docs/ct2_nfr_evidence.md#L31) lists role-based route and API restrictions as directly proven without calling out the remaining API-RBAC parity gap.
  - [ct2_integration_guide.md](/home/apollo/Projects/Freelance/travel-and-tours-bpm/core-transaction-2/back-office-and-agency-management-system/docs/ct2_integration_guide.md#L46) simplifies protected API permissions to `api.access` in a way that can be read as sufficient rather than minimal.
- Impact:
  the repo’s source-of-truth docs can mislead operators or reviewers about the current security posture and the remaining remediation scope.

## Coverage Notes
- The validation suite is materially broad and does cover browser accessibility, UI regression, route breadth, representative API writes, runtime hardening, load profile, and role UAT.
- The main uncovered areas from this audit are:
  - session ID rotation on successful login
  - inactive-user login handling in the API
  - module-specific API authorization parity for non-admin roles
  - browser-safe handling of unexpected uncaught exceptions

## Recommended Remediation Order
1. Harden session lifecycle and auth consistency:
   add post-auth `session_regenerate_id(true)` to browser and API login, rotate or reset the CSRF token after auth state changes, and align inactive-user handling across both login paths.
2. Align API authorization with browser RBAC:
   require module-specific permissions on each API write endpoint and add regression coverage for at least accounting and front-desk denial on non-financial module writes.
3. Sanitize browser error handling:
   disable raw browser error display for normal runtime, log server-side, and return a safe generic error page or redirect contract.
4. Extend the strict suite for the above cases:
   add session-rotation, inactive-user, and API-RBAC parity assertions so the hardening does not regress.
