#!/usr/bin/env bash

set -euo pipefail

CT2_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CT2_APP_DIR="$CT2_ROOT_DIR/ct2_back_office"
CT2_PROBE_SCRIPT="$CT2_APP_DIR/scripts/ct2_regression_probe.php"
CT2_BASE_URL="http://127.0.0.1:8093/ct2_index.php"
CT2_API_BASE_URL="http://127.0.0.1:8093/api"
CT2_COOKIE_JAR="$(mktemp)"
CT2_TMP_DIR="$(mktemp -d)"
CT2_SERVER_LOG="$CT2_TMP_DIR/ct2_php_server.log"
CT2_SERVER_PID=""

cleanup() {
    if [[ -n "$CT2_SERVER_PID" ]] && kill -0 "$CT2_SERVER_PID" >/dev/null 2>&1; then
        kill "$CT2_SERVER_PID" >/dev/null 2>&1 || true
        wait "$CT2_SERVER_PID" 2>/dev/null || true
    fi

    rm -f "$CT2_COOKIE_JAR"
    rm -rf "$CT2_TMP_DIR"
}

trap cleanup EXIT

log() {
    printf '[ct2-route-matrix] %s\n' "$1"
}

fail() {
    printf '[ct2-route-matrix] ERROR: %s\n' "$1" >&2
    exit 1
}

assert_equals() {
    local expected="$1"
    local actual="$2"
    local message="$3"

    if [[ "$expected" != "$actual" ]]; then
        fail "$message (expected '$expected', got '$actual')"
    fi
}

assert_file_contains() {
    local file_path="$1"
    local expected_text="$2"
    local message="$3"

    if ! grep -Fq "$expected_text" "$file_path"; then
        fail "$message"
    fi
}

assert_html_clean() {
    local file_path="$1"
    local message="$2"

    if grep -Eqi 'CT2 application error|Fatal error|Warning:|Notice:|Deprecated:' "$file_path"; then
        fail "$message"
    fi
}

probe() {
    php "$CT2_PROBE_SCRIPT" "$@"
}

extract_csrf() {
    php -r '$html = file_get_contents($argv[1]); if ($html === false) { exit(1); } if (!preg_match("/name=\"ct2_csrf_token\" value=\"([^\"]+)\"/", $html, $m)) { exit(2); } echo $m[1];' "$1"
}

http_get() {
    local url="$1"
    local output_file="$2"
    curl -sS -b "$CT2_COOKIE_JAR" -c "$CT2_COOKIE_JAR" -o "$output_file" -w '%{http_code}' "$url"
}

http_get_headers() {
    local url="$1"
    local header_file="$2"
    local output_file="$3"
    curl -sS -b "$CT2_COOKIE_JAR" -c "$CT2_COOKIE_JAR" -D "$header_file" -o "$output_file" -w '%{http_code}' "$url"
}

http_post_form_follow() {
    local url="$1"
    local output_file="$2"
    shift 2
    curl -sS -L -b "$CT2_COOKIE_JAR" -c "$CT2_COOKIE_JAR" -o "$output_file" -w '%{http_code}' "$url" "$@"
}

assert_json_success() {
    local body_file="$1"
    local message="$2"

    if ! grep -Fq '"success":true' "$body_file"; then
        fail "$message"
    fi

    if grep -Eqi '<html|Fatal error|Warning:|Notice:|Deprecated:' "$body_file"; then
        fail "$message"
    fi
}

assert_header_contains() {
    local header_file="$1"
    local expected_text="$2"
    local message="$3"

    if ! grep -Fiq "$expected_text" "$header_file"; then
        fail "$message"
    fi
}

start_server() {
    php -S 127.0.0.1:8093 -t "$CT2_APP_DIR" >"$CT2_SERVER_LOG" 2>&1 &
    CT2_SERVER_PID="$!"

    for _ in $(seq 1 30); do
        if curl -sS -o /dev/null "$CT2_BASE_URL?module=auth&action=login"; then
            return 0
        fi
        sleep 1
    done

    fail "Unable to start the local CT2 PHP server. See $CT2_SERVER_LOG."
}

log "Starting local CT2 PHP server."
start_server

log "Signing in as seeded CT2 administrator."
CT2_LOGIN_PAGE="$CT2_TMP_DIR/login.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=auth&action=login" "$CT2_LOGIN_PAGE")" "Login page did not load"
CT2_CSRF_TOKEN="$(extract_csrf "$CT2_LOGIN_PAGE")"
CT2_LOGIN_RESULT="$CT2_TMP_DIR/login_result.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=auth&action=login" "$CT2_LOGIN_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "username=ct2admin" \
    --data-urlencode "password=ChangeMe123!")" "Login submission did not complete"
assert_file_contains "$CT2_LOGIN_RESULT" "Back-Office Dashboard" "Login did not reach the dashboard"

CT2_REPORT_RUN_ID="$(probe report-run-id "QA Baseline Cross-Module Run")"
CT2_FINANCIAL_REPORT_ID="$(probe financial-report-id CT2-OPS-001)"

log "Running route breadth checks."

CT2_DASHBOARD_PAGE="$CT2_TMP_DIR/dashboard.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=dashboard&action=index" "$CT2_DASHBOARD_PAGE")" "Dashboard route did not return 200"
assert_file_contains "$CT2_DASHBOARD_PAGE" "Back-Office Dashboard" "Dashboard heading did not render"
assert_html_clean "$CT2_DASHBOARD_PAGE" "Dashboard route emitted a warning or error"

CT2_AGENTS_PAGE="$CT2_TMP_DIR/agents.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=agents&action=index&search=AGT-CT2-001" "$CT2_AGENTS_PAGE")" "Agents filtered route did not return 200"
assert_file_contains "$CT2_AGENTS_PAGE" "AGT-CT2-001" "Agents filtered route did not render the seeded agent"
assert_html_clean "$CT2_AGENTS_PAGE" "Agents filtered route emitted a warning or error"

CT2_STAFF_PAGE="$CT2_TMP_DIR/staff.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=staff&action=index&search=STF-CT2-001" "$CT2_STAFF_PAGE")" "Staff filtered route did not return 200"
assert_file_contains "$CT2_STAFF_PAGE" "STF-CT2-001" "Staff filtered route did not render the seeded staff record"
assert_html_clean "$CT2_STAFF_PAGE" "Staff filtered route emitted a warning or error"

CT2_SUPPLIERS_PAGE="$CT2_TMP_DIR/suppliers.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=suppliers&action=index&search=SUP-CT2-001" "$CT2_SUPPLIERS_PAGE")" "Suppliers filtered route did not return 200"
assert_file_contains "$CT2_SUPPLIERS_PAGE" "SUP-CT2-001" "Suppliers filtered route did not render the seeded supplier"
assert_html_clean "$CT2_SUPPLIERS_PAGE" "Suppliers filtered route emitted a warning or error"

CT2_AVAILABILITY_PAGE="$CT2_TMP_DIR/availability.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=availability&action=index&search=Skyline" "$CT2_AVAILABILITY_PAGE")" "Availability filtered route did not return 200"
assert_file_contains "$CT2_AVAILABILITY_PAGE" "Skyline Coaster 18-Seater" "Availability filtered route did not render the seeded resource"
assert_html_clean "$CT2_AVAILABILITY_PAGE" "Availability filtered route emitted a warning or error"

CT2_MARKETING_PAGE="$CT2_TMP_DIR/marketing.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=marketing&action=index&search=North%20Luzon" "$CT2_MARKETING_PAGE")" "Marketing filtered route did not return 200"
assert_file_contains "$CT2_MARKETING_PAGE" "North Luzon Coach Summer Push" "Marketing filtered route did not render the seeded campaign"
assert_html_clean "$CT2_MARKETING_PAGE" "Marketing filtered route emitted a warning or error"

CT2_FINANCIAL_PAGE="$CT2_TMP_DIR/financial.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=financial&action=index&ct2_report_run_id=$CT2_REPORT_RUN_ID&ct2_financial_report_id=$CT2_FINANCIAL_REPORT_ID" "$CT2_FINANCIAL_PAGE")" "Financial route did not return 200"
assert_file_contains "$CT2_FINANCIAL_PAGE" "Report Catalog" "Financial route did not render the report catalog"
assert_html_clean "$CT2_FINANCIAL_PAGE" "Financial route emitted a warning or error"

CT2_VISA_PAGE="$CT2_TMP_DIR/visa.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=visa&action=index&search=VISA-APP-001" "$CT2_VISA_PAGE")" "Visa filtered route did not return 200"
assert_file_contains "$CT2_VISA_PAGE" "VISA-APP-001" "Visa filtered route did not render the seeded application"
assert_html_clean "$CT2_VISA_PAGE" "Visa filtered route emitted a warning or error"

CT2_APPROVALS_PAGE="$CT2_TMP_DIR/approvals.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=approvals&action=index" "$CT2_APPROVALS_PAGE")" "Approvals route did not return 200"
assert_file_contains "$CT2_APPROVALS_PAGE" "Approval Queue" "Approvals route did not render the queue heading"
assert_html_clean "$CT2_APPROVALS_PAGE" "Approvals route emitted a warning or error"

log "Running representative JSON route checks."

CT2_JSON_HEADERS="$CT2_TMP_DIR/json_headers.txt"
CT2_JSON_BODY="$CT2_TMP_DIR/json_body.txt"

assert_equals "200" "$(http_get_headers "$CT2_API_BASE_URL/ct2_module_status.php" "$CT2_JSON_HEADERS" "$CT2_JSON_BODY")" "Module status endpoint did not return 200"
assert_header_contains "$CT2_JSON_HEADERS" "Content-Type: application/json" "Module status endpoint did not return JSON content type"
assert_json_success "$CT2_JSON_BODY" "Module status endpoint did not return a clean success envelope"
assert_file_contains "$CT2_JSON_BODY" "\"module_key\":\"marketing-promotions-management\"" "Module status endpoint did not include the marketing module"

assert_equals "200" "$(http_get_headers "$CT2_API_BASE_URL/ct2_agents.php?search=AGT-CT2-001" "$CT2_JSON_HEADERS" "$CT2_JSON_BODY")" "Agents API route did not return 200"
assert_header_contains "$CT2_JSON_HEADERS" "Content-Type: application/json" "Agents API route did not return JSON content type"
assert_json_success "$CT2_JSON_BODY" "Agents API route did not return a clean success envelope"
assert_file_contains "$CT2_JSON_BODY" "\"agent_code\":\"AGT-CT2-001\"" "Agents API route did not return the seeded agent"

assert_equals "200" "$(http_get_headers "$CT2_API_BASE_URL/ct2_staff.php?search=STF-CT2-001" "$CT2_JSON_HEADERS" "$CT2_JSON_BODY")" "Staff API route did not return 200"
assert_header_contains "$CT2_JSON_HEADERS" "Content-Type: application/json" "Staff API route did not return JSON content type"
assert_json_success "$CT2_JSON_BODY" "Staff API route did not return a clean success envelope"
assert_file_contains "$CT2_JSON_BODY" "\"staff_code\":\"STF-CT2-001\"" "Staff API route did not return the seeded staff record"

assert_equals "200" "$(http_get_headers "$CT2_API_BASE_URL/ct2_suppliers.php?search=SUP-CT2-001" "$CT2_JSON_HEADERS" "$CT2_JSON_BODY")" "Suppliers API route did not return 200"
assert_header_contains "$CT2_JSON_HEADERS" "Content-Type: application/json" "Suppliers API route did not return JSON content type"
assert_json_success "$CT2_JSON_BODY" "Suppliers API route did not return a clean success envelope"
assert_file_contains "$CT2_JSON_BODY" "\"supplier_code\":\"SUP-CT2-001\"" "Suppliers API route did not return the seeded supplier"

assert_equals "200" "$(http_get_headers "$CT2_API_BASE_URL/ct2_resources.php?search=Skyline" "$CT2_JSON_HEADERS" "$CT2_JSON_BODY")" "Resources API route did not return 200"
assert_header_contains "$CT2_JSON_HEADERS" "Content-Type: application/json" "Resources API route did not return JSON content type"
assert_json_success "$CT2_JSON_BODY" "Resources API route did not return a clean success envelope"
assert_file_contains "$CT2_JSON_BODY" "\"resource_name\":\"Skyline Coaster 18-Seater\"" "Resources API route did not return the seeded resource"

assert_equals "200" "$(http_get_headers "$CT2_API_BASE_URL/ct2_marketing_campaigns.php?search=CT2-MKT-001" "$CT2_JSON_HEADERS" "$CT2_JSON_BODY")" "Marketing campaigns API route did not return 200"
assert_header_contains "$CT2_JSON_HEADERS" "Content-Type: application/json" "Marketing campaigns API route did not return JSON content type"
assert_json_success "$CT2_JSON_BODY" "Marketing campaigns API route did not return a clean success envelope"
assert_file_contains "$CT2_JSON_BODY" "\"campaign_code\":\"CT2-MKT-001\"" "Marketing campaigns API route did not return the seeded campaign"

assert_equals "200" "$(http_get_headers "$CT2_API_BASE_URL/ct2_affiliates.php?search=AFF-CT2-001" "$CT2_JSON_HEADERS" "$CT2_JSON_BODY")" "Affiliates API route did not return 200"
assert_header_contains "$CT2_JSON_HEADERS" "Content-Type: application/json" "Affiliates API route did not return JSON content type"
assert_json_success "$CT2_JSON_BODY" "Affiliates API route did not return a clean success envelope"
assert_file_contains "$CT2_JSON_BODY" "\"affiliate_code\":\"AFF-CT2-001\"" "Affiliates API route did not return the seeded affiliate"

assert_equals "200" "$(http_get_headers "$CT2_API_BASE_URL/ct2_visa_applications.php?search=VISA-APP-001" "$CT2_JSON_HEADERS" "$CT2_JSON_BODY")" "Visa applications API route did not return 200"
assert_header_contains "$CT2_JSON_HEADERS" "Content-Type: application/json" "Visa applications API route did not return JSON content type"
assert_json_success "$CT2_JSON_BODY" "Visa applications API route did not return a clean success envelope"
assert_file_contains "$CT2_JSON_BODY" "\"application_reference\":\"VISA-APP-001\"" "Visa applications API route did not return the seeded application"

assert_equals "200" "$(http_get_headers "$CT2_API_BASE_URL/ct2_financial_reports.php?ct2_financial_report_id=$CT2_FINANCIAL_REPORT_ID" "$CT2_JSON_HEADERS" "$CT2_JSON_BODY")" "Financial reports API route did not return 200"
assert_header_contains "$CT2_JSON_HEADERS" "Content-Type: application/json" "Financial reports API route did not return JSON content type"
assert_json_success "$CT2_JSON_BODY" "Financial reports API route did not return a clean success envelope"
assert_file_contains "$CT2_JSON_BODY" "\"report_code\":\"CT2-OPS-001\"" "Financial reports API route did not return the seeded report definition"

log "Running export breadth check."
CT2_EXPORT_HEADERS="$CT2_TMP_DIR/export_headers.txt"
CT2_EXPORT_BODY="$CT2_TMP_DIR/export.csv"
assert_equals "200" "$(http_get_headers "$CT2_BASE_URL?module=financial&action=exportCsv&ct2_report_run_id=$CT2_REPORT_RUN_ID" "$CT2_EXPORT_HEADERS" "$CT2_EXPORT_BODY")" "Financial export route did not return 200"
assert_header_contains "$CT2_EXPORT_HEADERS" "Content-Type: text/csv" "Financial export route did not return CSV content type"
assert_file_contains "$CT2_EXPORT_BODY" "report_run_id,report_name,run_label,source_module" "Financial export route did not return CSV headers"

log "CT2 route matrix check passed."
