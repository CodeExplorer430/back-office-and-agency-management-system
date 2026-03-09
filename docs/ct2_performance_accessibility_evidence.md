# CT2 Performance And Accessibility Evidence

## Purpose
This document records the lightweight, repeatable evidence currently available for CT2 performance sanity and accessibility structure. It is not a formal audit. It captures what was actually checked in-repo, what passed on the seeded local environment, and what still requires human-operated follow-up.

## Evidence Sources
- `bash ct2_back_office/scripts/ct2_nfr_sanity_check.sh`
- `docs/ct2_nfr_evidence.md`
- `docs/ct2_manual_qa_pack.md`
- `docs/ct2_windows_xampp_validation_pack.md`

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

## Accessibility Structure Review
- The NFR sanity script statically verifies that core CT2 views contain:
  main section headings on auth, dashboard, agents, staff, suppliers, availability, marketing, financial, visa, and approvals;
  form-label markup on the main form-heavy views.
- The purpose of this check is to prevent regressions where pages lose their primary heading or obvious label structure.

### Accessibility interpretation
- Structural heading and label coverage is now directly checked in-repo.
- Keyboard-only flow, focus order, focus visibility, and screen-reader behavior are not proven by this script and still require an operator walkthrough.

## Remaining Manual Follow-Up
- Keyboard-only walkthrough for:
  login,
  dashboard navigation,
  agents form,
  approvals decision form,
  visa upload form,
  financial export link.
- Browser-based review of focus visibility and tab order on the supported runtime targets.
- Windows XAMPP execution of the same sanity flow using `docs/ct2_windows_xampp_validation_pack.md`.

## Current Conclusion
- Performance sanity is directly evidenced for the seeded local environment.
- Accessibility evidence is stronger than before because structural heading/label regressions are now script-checked.
- Accessibility is still only partially proven until the keyboard/browser walkthrough is executed and recorded.
