#!/usr/bin/env bash

set -euo pipefail

CT2_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CT2_APP_DIR="$CT2_ROOT_DIR/ct2_back_office"
CT2_BASE_URL="http://127.0.0.1:8099/ct2_index.php"
CT2_TMP_DIR="$(mktemp -d)"
CT2_SERVER_LOG="$CT2_TMP_DIR/ct2_php_server.log"
CT2_SERVER_PID=""
CT2_RUN_ID="ROLE-UAT-$(date +%s)"

cleanup() {
    if [[ -n "$CT2_SERVER_PID" ]] && kill -0 "$CT2_SERVER_PID" >/dev/null 2>&1; then
        kill "$CT2_SERVER_PID" >/dev/null 2>&1 || true
        wait "$CT2_SERVER_PID" 2>/dev/null || true
    fi

    rm -rf "$CT2_TMP_DIR"
}

trap cleanup EXIT

log() {
    printf '[ct2-role-uat] %s\n' "$1"
}

fail() {
    printf '[ct2-role-uat] ERROR: %s\n' "$1" >&2
    exit 1
}

assert_file_contains() {
    local file_path="$1"
    local expected_text="$2"
    local message="$3"

    if ! grep -Fq "$expected_text" "$file_path"; then
        fail "$message"
    fi
}

assert_status() {
    local expected="$1"
    local actual="$2"
    local message="$3"

    if [[ "$expected" != "$actual" ]]; then
        fail "$message (expected $expected, got $actual)"
    fi
}

extract_csrf() {
    php -r '$html = file_get_contents($argv[1]); if ($html === false) { exit(1); } if (!preg_match("/name=\"ct2_csrf_token\" value=\"([^\"]+)\"/", $html, $m)) { exit(2); } echo $m[1];' "$1"
}

extract_first_approval_id() {
    php -r '$html = file_get_contents($argv[1]); if ($html === false) { exit(1); } if (!preg_match("/name=\"ct2_approval_workflow_id\" value=\"([0-9]+)\"/", $html, $m)) { exit(2); } echo $m[1];' "$1"
}

start_server() {
    php -S 127.0.0.1:8099 -t "$CT2_APP_DIR" >"$CT2_SERVER_LOG" 2>&1 &
    CT2_SERVER_PID="$!"

    for _ in $(seq 1 30); do
        if curl -sS -o /dev/null "$CT2_BASE_URL?module=auth&action=login"; then
            return 0
        fi
        sleep 1
    done

    fail "Unable to start the local CT2 PHP server. See $CT2_SERVER_LOG."
}

login_user() {
    local username="$1"
    local password="$2"
    local cookie_jar="$3"
    local prefix="$4"
    local body_file="$CT2_TMP_DIR/${prefix}_login.body"
    local headers_file="$CT2_TMP_DIR/${prefix}_login.headers"
    local csrf_token=""

    curl -sS -b "$cookie_jar" -c "$cookie_jar" -D "$headers_file" -o "$body_file" "$CT2_BASE_URL?module=auth&action=login" >/dev/null
    csrf_token="$(extract_csrf "$body_file")"

    curl -sS -L -b "$cookie_jar" -c "$cookie_jar" -D "$headers_file" -o "$body_file" \
        "$CT2_BASE_URL?module=auth&action=login" \
        --data-urlencode "ct2_csrf_token=$csrf_token" \
        --data-urlencode "username=$username" \
        --data-urlencode "password=$password" >/dev/null

    assert_file_contains "$body_file" "CORE TRANSACTION 2" "Login failed for ${username}"
}

get_route() {
    local cookie_jar="$1"
    local url="$2"
    local body_file="$3"
    local headers_file="$4"

    curl -sS -b "$cookie_jar" -c "$cookie_jar" -D "$headers_file" -o "$body_file" -w '%{http_code}' "$url"
}

post_form_follow() {
    local cookie_jar="$1"
    local url="$2"
    local body_file="$3"
    local headers_file="$4"
    shift 4

    curl -sS -L -b "$cookie_jar" -c "$cookie_jar" -D "$headers_file" -o "$body_file" -w '%{http_code}' "$url" "$@"
}

log "Starting local CT2 PHP server."
start_server

CT2_MANAGER_COOKIE="$CT2_TMP_DIR/manager.cookies"
CT2_DESK_COOKIE="$CT2_TMP_DIR/desk.cookies"
CT2_FINANCE_COOKIE="$CT2_TMP_DIR/finance.cookies"
CT2_BODY="$CT2_TMP_DIR/body.txt"
CT2_HEADERS="$CT2_TMP_DIR/headers.txt"

log "Verifying back-office manager role flow."
login_user "ct2manager" "ChangeMe123!" "$CT2_MANAGER_COOKIE" "manager"
assert_status "200" "$(get_route "$CT2_MANAGER_COOKIE" "$CT2_BASE_URL?module=approvals&action=index" "$CT2_BODY" "$CT2_HEADERS")" "Manager approvals page did not load"
assert_file_contains "$CT2_BODY" "Approval Queue" "Manager approvals page did not render the approval queue"
assert_file_contains "$CT2_BODY" "Decision notes" "Manager approvals page did not expose the decision form"

CT2_MANAGER_CSRF="$(extract_csrf "$CT2_BODY")"
CT2_APPROVAL_ID="$(extract_first_approval_id "$CT2_BODY")"
assert_status "200" "$(post_form_follow "$CT2_MANAGER_COOKIE" "$CT2_BASE_URL?module=approvals&action=decide" "$CT2_BODY" "$CT2_HEADERS" \
    --data-urlencode "ct2_csrf_token=$CT2_MANAGER_CSRF" \
    --data-urlencode "ct2_approval_workflow_id=$CT2_APPROVAL_ID" \
    --data-urlencode "approval_status=approved" \
    --data-urlencode "decision_notes=Manager role walkthrough $CT2_RUN_ID")" "Manager approval decision did not complete"
assert_file_contains "$CT2_BODY" "Approval Queue" "Manager approval decision did not return to the approval queue"

assert_status "200" "$(get_route "$CT2_MANAGER_COOKIE" "$CT2_BASE_URL?module=marketing&action=index" "$CT2_BODY" "$CT2_HEADERS")" "Manager marketing page did not load"
assert_file_contains "$CT2_BODY" "Marketing and Promotions Management" "Manager marketing page did not render"

log "Verifying front desk role flow."
login_user "ct2desk" "ChangeMe123!" "$CT2_DESK_COOKIE" "desk"
assert_status "200" "$(get_route "$CT2_DESK_COOKIE" "$CT2_BASE_URL?module=visa&action=index" "$CT2_BODY" "$CT2_HEADERS")" "Front desk visa page did not load"
assert_file_contains "$CT2_BODY" "Document and Visa Assistance Module" "Front desk visa page did not render"

assert_status "403" "$(get_route "$CT2_DESK_COOKIE" "$CT2_BASE_URL?module=financial&action=index" "$CT2_BODY" "$CT2_HEADERS")" "Front desk financial page was not denied"
assert_file_contains "$CT2_BODY" "Forbidden" "Front desk financial denial did not render a forbidden response"

log "Verifying accounting staff role flow."
login_user "ct2finance" "ChangeMe123!" "$CT2_FINANCE_COOKIE" "finance"
assert_status "200" "$(get_route "$CT2_FINANCE_COOKIE" "$CT2_BASE_URL?module=financial&action=index&ct2_report_run_id=1&source_module=suppliers" "$CT2_BODY" "$CT2_HEADERS")" "Accounting financial page did not load"
assert_file_contains "$CT2_BODY" "Financial Reporting and Analytics" "Accounting financial page did not render"
assert_file_contains "$CT2_BODY" "Export CSV" "Accounting financial page did not expose the export trigger"

assert_status "200" "$(get_route "$CT2_FINANCE_COOKIE" "$CT2_BASE_URL?module=financial&action=exportCsv&ct2_report_run_id=1&source_module=suppliers" "$CT2_BODY" "$CT2_HEADERS")" "Accounting CSV export did not succeed"
assert_file_contains "$CT2_HEADERS" "Content-Type: text/csv" "Accounting CSV export did not return CSV content"

log "CT2 role-specific UAT checks passed."
