# CT2 Development Standards

## Purpose
This repository contains the `CORE TRANSACTION 2: Back-Office and Agency Management System` for the Travel and Tours ERP platform. All contributions must keep the codebase interoperable with the other platform modules while remaining strictly framework-free.

## Core Constraints
- Use Vanilla OOP PHP only. Do not add Laravel, Symfony, Composer packages, NPM packages, or client-side frameworks.
- Use MySQL through PDO only. All SQL access must use prepared statements.
- Prefix every CT2 artifact with `ct2_`:
  tables, files, views, routes, CSS classes and IDs, form fields when practical, and integration payload identifiers.
- Keep the mini-MVC split strict:
  models for database access, controllers for request handling and business flow, views for HTML only.

## Directory Contract
- Keep the application rooted in `/ct2_back_office/`.
- Required top-level application files:
  `ct2_setup.sql`, `ct2_index.php`, `config/ct2_database.php`, `assets/css/ct2_styles.css`, `api/*`.
- Add new CT2 code only under the existing prefixed directories unless a new directory is required by the same naming convention.

## Coding Standards
- Prefer `declare(strict_types=1);` in PHP files.
- Escape user-facing output in views with `htmlspecialchars`.
- Keep HTML out of models and controllers.
- Use short, intention-revealing methods. Avoid god classes and duplicate SQL.
- Treat all cross-module identifiers as external references, not CT2-owned source-of-truth records, unless the client explicitly changes ownership.
- Use `password_hash` and `password_verify` for credentials.
- Enforce CSRF checks on state-changing form requests.
- Return JSON only from `/api/*` with `Content-Type: application/json`.
- Keep browser file uploads inside controller/form flows; do not switch `/api/*` endpoints to multipart without an explicit new contract.

## Database Standards
- All CT2 tables must be created in `ct2_setup.sql`.
- Use `InnoDB`, explicit foreign keys, indexed lookup columns, and timestamps on operational tables.
- New tables must use the `ct2_` prefix and document external ownership through `source_system` and external identifier fields when needed.
- Never use destructive schema changes without documenting migration impact in the commit message and pull request notes.

## Quality Gates
- Run `bash ct2_back_office/scripts/ct2_lint.sh` before every commit.
- Run `php ct2_back_office/scripts/ct2_smoke_check.php` before every commit.
- Run `php ct2_back_office/scripts/ct2_db_smoke_check.php` whenever local DB-backed behavior, seeds, or schema compatibility are part of the work.
- Import `ct2_back_office/ct2_setup.sql` into a clean MySQL database before claiming schema work is complete.
- Keep `docs/ct2_manual_qa_pack.md` and `docs/ct2_api_validation.md` in sync with seeded roles, demo records, and validation flows whenever QA behavior changes.
- Do not merge code that emits PHP warnings, notices, or fatal errors under `E_ALL`.

## Definition Of Done
- Routes render without PHP warnings or notices.
- New or changed APIs return valid JSON envelopes and appropriate HTTP status codes.
- Database changes are reflected in `ct2_setup.sql`.
- Navigation, permissions, and CSRF behavior are covered by manual smoke testing.
- Audit logging is updated for state-changing back-office actions.

## Branching And Commits
- Long-lived branches:
  `main` for releasable code, `develop` for integration.
- Short-lived branches:
  `feature/ct2-*`, `fix/ct2-*`, `docs/ct2-*`.
- `develop` is now the integrated CT2 baseline branch. Validate all new CT2 work there before promoting anything to `main`.
- Commit format:
  `feat(ct2-auth): add login flow`
  `feat(ct2-agents): add approval queue`
  `fix(ct2-api): harden JSON validation`
  `docs(ct2): define repo standards`
  `chore(ct2-repo): add native lint scripts`

## Local Runtime Validation
- Copy `ct2_back_office/config/ct2_local.php.example` to `ct2_back_office/config/ct2_local.php` and set explicit TCP MySQL credentials for either LAMP or Windows XAMPP.
- Environment variables must override `ct2_local.php` when both are present.
- Import `ct2_back_office/ct2_setup.sql` into a clean `ct2_back_office` database before browser testing.
- Run `php ct2_back_office/scripts/ct2_db_smoke_check.php` after import and before browser testing.
- Use the seeded administrator account for first login:
  `ct2admin` / `ChangeMe123!`
- Validate the full route set on local PHP/Apache:
  dashboard, agents, suppliers, availability, marketing, financial, visa, staff, approvals.
- Validate representative JSON endpoints under `ct2_back_office/api/` and confirm JSON envelopes, HTTP status codes, and audit/API log creation remain correct.

## Review Focus
- Security regressions first: authentication, authorization, CSRF, SQL safety, output escaping.
- Then behavior regressions: broken routes, invalid JSON, missing audit entries, and schema drift.
- Then maintainability: duplication, unclear module boundaries, or prefixing violations.
