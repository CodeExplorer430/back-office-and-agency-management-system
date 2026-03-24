# CT2 Performance And Accessibility Evidence

## Purpose
This document records the lightweight, repeatable evidence currently available for CT2 performance sanity and accessibility structure. It is not a formal audit. It captures what was actually checked in-repo, what passed on the seeded local environment, and what still requires human-operated follow-up.

## Current Quality Gate
- The browser, load, and NFR checks documented here are part of the blocking strict suite in `docs/ct2_quality_gate.md`.
- A clean accessibility or load result does not override failures elsewhere in the suite.

## Evidence Sources
- `bash ct2_back_office/scripts/ct2_validation_suite.sh`
- `bash ct2_back_office/scripts/ct2_browser_accessibility_check.sh`
- `bash ct2_back_office/scripts/ct2_ui_regression_check.sh`
- `bash ct2_back_office/scripts/ct2_load_profile_check.sh`
- `bash ct2_back_office/scripts/ct2_nfr_sanity_check.sh`
- `docs/ct2_nfr_evidence.md`
- `docs/ct2_manual_qa_pack.md`
- `docs/ct2_windows_xampp_validation_pack.md`
- `docs/ct2_quality_gate.md`

## Local Performance Sanity Run
- Date: March 10, 2026
- Environment: local Linux LAMP-style PHP/MySQL stack with the seeded `ct2_back_office` database
- Entry point: `bash ct2_back_office/scripts/ct2_nfr_sanity_check.sh`
- Sanity limit used by the script: `5.00s` per sampled request

| Request | Measured Time |
| --- | --- |
| Login page GET | `0.002305s` |
| Login form POST to dashboard | `0.394561s` |
| Dashboard GET | `0.014834s` |
| Agents filtered GET | `0.006942s` |
| Module status API GET | `0.009459s` |

### Performance interpretation
- The seeded local sanity run passed cleanly and none of the sampled routes came close to the current `5.00s` ceiling.
- This is evidence of basic runtime health on the local seeded environment, not a concurrency benchmark and not a production-capacity claim.

## Repeated Load Profile Run
- Date: March 10, 2026
- Environment: local Linux LAMP-style PHP/MySQL stack with the seeded `ct2_back_office` database
- Entry point: `bash ct2_back_office/scripts/ct2_load_profile_check.sh`
- Iterations per request family: `5`
- Per-request ceiling: `5.00s`

| Request Family | Count | Average | Minimum | Maximum |
| --- | --- | --- | --- | --- |
| Login page GET | `5` | `0.002594s` | `0.002174s` | `0.003047s` |
| Login form POST | `5` | `0.294092s` | `0.274644s` | `0.312372s` |
| Dashboard GET | `5` | `0.012770s` | `0.009779s` | `0.016579s` |
| Agents filtered GET | `5` | `0.006920s` | `0.004964s` | `0.009535s` |
| Module status API GET | `5` | `0.022098s` | `0.009248s` | `0.028918s` |
| Financial export metadata GET | `5` | `0.022938s` | `0.019305s` | `0.026010s` |

### Load interpretation
- The repeated seeded run stayed well below the current `5.00s` ceiling for every sampled request family.
- This is stronger than the earlier one-pass sanity sample because it exercises repeated authenticated and unauthenticated requests, but it is still not a concurrency or production-capacity benchmark.

## Accessibility Structure Review
- The NFR sanity script statically verifies that core CT2 views contain:
  main section headings on auth, dashboard, agents, staff, suppliers, availability, marketing, financial, visa, and approvals;
  form-label markup on the main form-heavy views.
- The purpose of this check is to prevent regressions where pages lose their primary heading or obvious label structure.

### Accessibility interpretation
- Structural heading and label coverage is now directly checked in-repo.

## Shared UI Regression Review
- Environment: local Linux LAMP-style PHP/MySQL stack plus headless Chrome
- Entry point: `bash ct2_back_office/scripts/ct2_ui_regression_check.sh`
- Method: live browser assertions against the shared authenticated shell and representative module modals after seeded admin login

| Scenario Family | Result | Evidence |
| --- | --- | --- |
| Sidebar geometry | Pass | Expanded and collapsed sidebar states are asserted against the content shell so overlap regressions fail the gate. |
| Collapsed alignment | Pass | Logo, toggle, and active nav icon centering are checked in the collapsed rail. |
| Modal geometry and footer safety | Pass | Representative supplier modal is required to mount at the top-level modal host, center in the viewport, remain clickable, and keep the last field clear of the footer actions. |
| Toast and modal layering | Pass | The toast stack is required to stay below the modal layer and must not intercept submit-button clicks while a modal is open. |
| Tabs and pagination state | Pass | Financial tab links and supplier pagination links are required to preserve relevant query state such as active tab, source module, and page/filter params. |
| Split date/time controls | Pass | Visa application, visa payment, and availability dispatch modals are required to render date/time fields as separate controls with no legacy `datetime-local` inputs. |

### UI regression interpretation
- CT2 now has repeatable browser-driven proof for the exact shared UI contracts that previously caused layout and modal regressions.
- The gate is targeted rather than exhaustive, but it is designed to fail early on shell, modal, tab, pagination, and split date/time regressions that materially affect operator use.

## Browser Keyboard And Focus Walkthrough
- Date: March 10, 2026
- Environment: local Linux LAMP-style PHP/MySQL stack plus headless Chrome
- Entry point: `bash ct2_back_office/scripts/ct2_browser_accessibility_check.sh`
- Method: real `Tab` traversal through headless Chrome DevTools on the live CT2 pages after seeded admin login

| Scenario | Result | Evidence |
| --- | --- | --- |
| Login form | Pass | Keyboard traversal reached `username`, `password`, then `Sign In` in order, and each target retained a visible focus indicator. |
| Dashboard navigation | Pass | Tabbing reached `Sign Out`, then `Dashboard`, `Agents`, and `Suppliers` nav links in the expected top-level order with visible focus. |
| Agents data-entry form | Pass | Tabbing moved from search/filter into `agent_code`, `agency_name`, and the `Save Agent` button without skipping the primary form controls. |
| Approval decision form | Pass | Tabbing reached `approval_status`, `decision_notes`, and `Save` directly on the approval queue rows with visible focus indicators. |
| Visa upload workflow | Pass | Tabbing reached the checklist upload controls, including `ct2_document_file` and `Save Checklist Update`, and they retained visible focus indicators. |
| Financial export trigger | Pass | Tabbing reached the filter controls and the `Export CSV` link on the seeded report run page with visible focus indicators. |

### Keyboard and focus interpretation
- CT2 now has direct browser-driven evidence that the core auth, navigation, approval, visa upload, and financial export surfaces are keyboard-reachable on the supported local runtime.
- The primary interactive controls exercised in the walkthrough retained visible focus indicators under the tested Chrome runtime.
- Native date and datetime-local inputs expose multiple sub-focus states during tab traversal; the script records those states, but the acceptance gate is tied to the reachability and visible focus of the primary control set rather than every internal browser-managed subfield.

## Remaining Manual Follow-Up
- Windows XAMPP execution of the same sanity flow using `docs/ct2_windows_xampp_validation_pack.md`.
- Optional screen-reader review if the client later requests a stronger accessibility claim than keyboard/focus reachability.

## Current Conclusion
- Performance sanity is directly evidenced for the seeded local environment through both one-pass and repeated seeded samples.
- Accessibility evidence is materially stronger because CT2 now has script-checked structural markers and a real browser-driven keyboard/focus walkthrough for the primary operator surfaces.
- The main remaining cross-platform evidence gap is still the unexecuted Windows XAMPP run, not the Linux-side keyboard or load proof.
