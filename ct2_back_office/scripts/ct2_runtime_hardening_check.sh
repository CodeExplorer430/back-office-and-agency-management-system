#!/usr/bin/env bash

set -euo pipefail

CT2_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CT2_APP_DIR="$CT2_ROOT_DIR/ct2_back_office"
CT2_PROBE_SCRIPT="$CT2_APP_DIR/scripts/ct2_regression_probe.php"
CT2_BASE_URL="http://127.0.0.1:8092/ct2_index.php"
CT2_API_BASE_URL="http://127.0.0.1:8092/api"
CT2_COOKIE_JAR="$(mktemp)"
CT2_TMP_DIR="$(mktemp -d)"
CT2_SERVER_LOG="$CT2_TMP_DIR/ct2_php_server.log"
CT2_SERVER_PID=""
CT2_RUN_ID="$(date +%s)"
CT2_TODAY="$(php -r 'echo date("Y-m-d");')"
CT2_TARGET_GO_LIVE="$(php -r 'echo date("Y-m-d", strtotime("+12 day"));')"

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
    printf '[ct2-hardening] %s\n' "$1"
}

fail() {
    printf '[ct2-hardening] ERROR: %s\n' "$1" >&2
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

http_post_form_headers() {
    local url="$1"
    local header_file="$2"
    local output_file="$3"
    shift 3
    curl -sS -b "$CT2_COOKIE_JAR" -c "$CT2_COOKIE_JAR" -D "$header_file" -o "$output_file" -w '%{http_code}' "$url" "$@"
}

build_sample_upload() {
    local upload_path="$1"
    printf '%s' 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WnL1hQAAAAASUVORK5CYII=' | base64 --decode > "$upload_path"
}

start_server() {
    php -S 127.0.0.1:8092 -t "$CT2_APP_DIR" >"$CT2_SERVER_LOG" 2>&1 &
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

log "Preparing upload fixture."
CT2_UPLOAD_FILE="$CT2_TMP_DIR/ct2_hardening_upload.png"
build_sample_upload "$CT2_UPLOAD_FILE"

log "Signing in as seeded CT2 administrator."
CT2_LOGIN_PAGE="$CT2_TMP_DIR/login.html"
CT2_LOGIN_STATUS="$(http_get "$CT2_BASE_URL?module=auth&action=login" "$CT2_LOGIN_PAGE")"
assert_equals "200" "$CT2_LOGIN_STATUS" "Login page did not load"
CT2_CSRF_TOKEN="$(extract_csrf "$CT2_LOGIN_PAGE")"
CT2_LOGIN_RESULT="$CT2_TMP_DIR/login_result.html"
CT2_LOGIN_POST_STATUS="$(http_post_form_follow "$CT2_BASE_URL?module=auth&action=login" "$CT2_LOGIN_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "username=ct2admin" \
    --data-urlencode "password=ChangeMe123!")"
assert_equals "200" "$CT2_LOGIN_POST_STATUS" "Login submission did not complete"
assert_file_contains "$CT2_LOGIN_RESULT" "Back-Office Dashboard" "Login did not land on the dashboard"

log "Verifying dashboard and availability read paths."
CT2_DASHBOARD_PAGE="$CT2_TMP_DIR/dashboard.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=dashboard&action=index" "$CT2_DASHBOARD_PAGE")" "Dashboard route did not return 200"
assert_file_contains "$CT2_DASHBOARD_PAGE" "Back-Office Dashboard" "Dashboard content did not render"
CT2_AVAILABILITY_PAGE="$CT2_TMP_DIR/availability.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=availability&action=index&search=Skyline" "$CT2_AVAILABILITY_PAGE")" "Availability search route did not return 200"
assert_file_contains "$CT2_AVAILABILITY_PAGE" "Skyline Coaster 18-Seater" "Availability search did not render the seeded resource"
assert_file_contains "$CT2_AVAILABILITY_PAGE" "CT1-BKG-1001" "Availability search did not render the seeded booking reference"
assert_file_contains "$CT2_AVAILABILITY_PAGE" "NAA-4581" "Availability page did not render the seeded dispatch vehicle"

CT2_AGENT_ID="$(probe agent-id AGT-CT2-002)"
CT2_SUPPLIER_ID="$(probe supplier-id SUP-CT2-002)"
CT2_VISA_APPLICATION_ID="$(probe visa-application-id VISA-APP-001)"
CT2_APPROVAL_ID="$(probe approval-id supplier SUP-CT2-002)"
CT2_CHECKLIST_ID="$(probe checklist-id VISA-APP-001 "Passport bio page")"
CT2_REPORT_RUN_ID="$(probe report-run-id "QA Baseline Cross-Module Run")"
CT2_FLAG_ID="$(probe flag-id suppliers SUP-CT2-002)"

log "Running positive agent update with audit verification."
CT2_AGENT_AUDIT_BEFORE="$(probe audit-count agents.update agent "$CT2_AGENT_ID")"
CT2_AGENT_PAGE="$CT2_TMP_DIR/agents.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=agents&action=index&edit_id=$CT2_AGENT_ID" "$CT2_AGENT_PAGE")" "Agents page did not load"
CT2_CSRF_TOKEN="$(extract_csrf "$CT2_AGENT_PAGE")"
CT2_AGENT_RESULT="$CT2_TMP_DIR/agents_save.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=agents&action=save" "$CT2_AGENT_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_agent_id=$CT2_AGENT_ID" \
    --data-urlencode "agent_code=AGT-CT2-002" \
    --data-urlencode "agency_name=Island Connect Tours" \
    --data-urlencode "contact_person=Ramon Aquino" \
    --data-urlencode "email=ramon@islandconnect.example.com" \
    --data-urlencode "phone=+63-917-200-0002" \
    --data-urlencode "region=Visayas" \
    --data-urlencode "commission_rate=10.00" \
    --data-urlencode "support_level=priority" \
    --data-urlencode "approval_status=approved" \
    --data-urlencode "active_status=active" \
    --data-urlencode "external_booking_id=CT1-BKG-1002" \
    --data-urlencode "external_customer_id=CT1-CUST-8802" \
    --data-urlencode "external_payment_id=FIN-PAY-4402" \
    --data-urlencode "source_system=ct1")" "Agent update post did not complete"
assert_file_contains "$CT2_AGENT_RESULT" "Agent profile saved successfully." "Agent update success flash was not rendered"
CT2_AGENT_AUDIT_AFTER="$(probe audit-count agents.update agent "$CT2_AGENT_ID")"
assert_equals "$((CT2_AGENT_AUDIT_BEFORE + 1))" "$CT2_AGENT_AUDIT_AFTER" "Agent update audit log did not increment"

log "Running invalid-CSRF supplier onboarding negative check."
CT2_SUPPLIER_ONBOARDING_AUDIT_BEFORE="$(probe audit-count suppliers.onboarding_update supplier "$CT2_SUPPLIER_ID")"
CT2_SUPPLIER_DOCUMENTS_BEFORE="$(probe supplier-onboarding-field SUP-CT2-002 documents_status)"
CT2_SUPPLIER_REVIEW_BEFORE="$(probe supplier-onboarding-field SUP-CT2-002 review_notes)"
CT2_SUPPLIER_NEGATIVE_RESULT="$CT2_TMP_DIR/supplier_invalid_csrf.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=suppliers&action=saveOnboarding" "$CT2_SUPPLIER_NEGATIVE_RESULT" \
    --data-urlencode "ct2_csrf_token=invalid-ct2-token" \
    --data-urlencode "ct2_supplier_id=$CT2_SUPPLIER_ID" \
    --data-urlencode "checklist_status=review_ready" \
    --data-urlencode "documents_status=complete" \
    --data-urlencode "compliance_status=cleared" \
    --data-urlencode "review_notes=Invalid CSRF should not persist" \
    --data-urlencode "blocked_reason=Invalid token test" \
    --data-urlencode "target_go_live_date=$CT2_TODAY")" "Invalid-CSRF supplier post did not complete"
assert_file_contains "$CT2_SUPPLIER_NEGATIVE_RESULT" "Invalid request token." "Supplier invalid-CSRF flash was not rendered"
assert_equals "$CT2_SUPPLIER_ONBOARDING_AUDIT_BEFORE" "$(probe audit-count suppliers.onboarding_update supplier "$CT2_SUPPLIER_ID")" "Supplier invalid-CSRF request wrote an audit log"
assert_equals "$CT2_SUPPLIER_DOCUMENTS_BEFORE" "$(probe supplier-onboarding-field SUP-CT2-002 documents_status)" "Supplier invalid-CSRF request changed onboarding documents status"
assert_equals "$CT2_SUPPLIER_REVIEW_BEFORE" "$(probe supplier-onboarding-field SUP-CT2-002 review_notes)" "Supplier invalid-CSRF request changed onboarding review notes"

log "Running positive supplier onboarding update with audit verification."
CT2_SUPPLIER_PAGE="$CT2_TMP_DIR/suppliers.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=suppliers&action=index&supplier_id=$CT2_SUPPLIER_ID" "$CT2_SUPPLIER_PAGE")" "Suppliers page did not load"
CT2_CSRF_TOKEN="$(extract_csrf "$CT2_SUPPLIER_PAGE")"
CT2_SUPPLIER_REVIEW_NOTE="Hardening supplier update $CT2_RUN_ID"
CT2_SUPPLIER_RESULT="$CT2_TMP_DIR/supplier_save.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=suppliers&action=saveOnboarding" "$CT2_SUPPLIER_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_supplier_id=$CT2_SUPPLIER_ID" \
    --data-urlencode "checklist_status=review_ready" \
    --data-urlencode "documents_status=complete" \
    --data-urlencode "compliance_status=cleared" \
    --data-urlencode "review_notes=$CT2_SUPPLIER_REVIEW_NOTE" \
    --data-urlencode "blocked_reason=" \
    --data-urlencode "target_go_live_date=$CT2_TARGET_GO_LIVE")" "Supplier onboarding update did not complete"
assert_file_contains "$CT2_SUPPLIER_RESULT" "Supplier onboarding record updated." "Supplier onboarding success flash was not rendered"
assert_equals "$((CT2_SUPPLIER_ONBOARDING_AUDIT_BEFORE + 1))" "$(probe audit-count suppliers.onboarding_update supplier "$CT2_SUPPLIER_ID")" "Supplier onboarding audit log did not increment"
assert_equals "$CT2_SUPPLIER_REVIEW_NOTE" "$(probe supplier-onboarding-field SUP-CT2-002 review_notes)" "Supplier onboarding review notes did not persist"

log "Running invalid-CSRF approval negative check."
CT2_APPROVAL_AUDIT_BEFORE="$(probe audit-count approvals.decide supplier "$CT2_SUPPLIER_ID")"
CT2_APPROVAL_STATUS_BEFORE="$(probe approval-status supplier SUP-CT2-002)"
CT2_APPROVAL_NEGATIVE_RESULT="$CT2_TMP_DIR/approval_invalid_csrf.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=approvals&action=decide" "$CT2_APPROVAL_NEGATIVE_RESULT" \
    --data-urlencode "ct2_csrf_token=invalid-ct2-token" \
    --data-urlencode "ct2_approval_workflow_id=$CT2_APPROVAL_ID" \
    --data-urlencode "approval_status=rejected" \
    --data-urlencode "decision_notes=Invalid token should not persist")" "Invalid-CSRF approval post did not complete"
assert_file_contains "$CT2_APPROVAL_NEGATIVE_RESULT" "Invalid request token for approval decision." "Approval invalid-CSRF flash was not rendered"
assert_equals "$CT2_APPROVAL_AUDIT_BEFORE" "$(probe audit-count approvals.decide supplier "$CT2_SUPPLIER_ID")" "Approval invalid-CSRF request wrote an audit log"
assert_equals "$CT2_APPROVAL_STATUS_BEFORE" "$(probe approval-status supplier SUP-CT2-002)" "Approval invalid-CSRF request changed workflow status"

log "Running positive approval decision with audit verification."
CT2_APPROVAL_PAGE="$CT2_TMP_DIR/approvals.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=approvals&action=index" "$CT2_APPROVAL_PAGE")" "Approvals page did not load"
CT2_CSRF_TOKEN="$(extract_csrf "$CT2_APPROVAL_PAGE")"
CT2_APPROVAL_RESULT="$CT2_TMP_DIR/approval_save.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=approvals&action=decide" "$CT2_APPROVAL_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_approval_workflow_id=$CT2_APPROVAL_ID" \
    --data-urlencode "approval_status=approved" \
    --data-urlencode "decision_notes=Hardening approval decision $CT2_RUN_ID")" "Approval decision did not complete"
assert_file_contains "$CT2_APPROVAL_RESULT" "Approval decision recorded." "Approval success flash was not rendered"
assert_equals "$((CT2_APPROVAL_AUDIT_BEFORE + 1))" "$(probe audit-count approvals.decide supplier "$CT2_SUPPLIER_ID")" "Approval decision audit log did not increment"
assert_equals "approved" "$(probe approval-status supplier SUP-CT2-002)" "Approval decision did not update workflow status to approved"

log "Running invalid-CSRF visa checklist negative check."
CT2_VISA_AUDIT_BEFORE="$(probe audit-count visa.document_checklist_update visa_application "$CT2_VISA_APPLICATION_ID")"
CT2_CHECKLIST_STATUS_BEFORE="$(probe checklist-status VISA-APP-001 "Passport bio page")"
CT2_VISA_NEGATIVE_RESULT="$CT2_TMP_DIR/visa_invalid_csrf.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=visa&action=saveDocumentChecklist" "$CT2_VISA_NEGATIVE_RESULT" \
    --data-urlencode "ct2_csrf_token=invalid-ct2-token" \
    --data-urlencode "ct2_visa_application_id=$CT2_VISA_APPLICATION_ID" \
    --data-urlencode "ct2_application_checklist_id=$CT2_CHECKLIST_ID" \
    --data-urlencode "checklist_status=rejected" \
    --data-urlencode "verification_notes=Invalid token should not persist")" "Invalid-CSRF visa checklist post did not complete"
assert_file_contains "$CT2_VISA_NEGATIVE_RESULT" "Invalid request token." "Visa invalid-CSRF flash was not rendered"
assert_equals "$CT2_VISA_AUDIT_BEFORE" "$(probe audit-count visa.document_checklist_update visa_application "$CT2_VISA_APPLICATION_ID")" "Visa invalid-CSRF request wrote an audit log"
assert_equals "$CT2_CHECKLIST_STATUS_BEFORE" "$(probe checklist-status VISA-APP-001 "Passport bio page")" "Visa invalid-CSRF request changed checklist status"

log "Running positive visa checklist upload with audit verification."
CT2_VISA_PAGE="$CT2_TMP_DIR/visa.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=visa&action=index" "$CT2_VISA_PAGE")" "Visa page did not load"
CT2_CSRF_TOKEN="$(extract_csrf "$CT2_VISA_PAGE")"
CT2_VISA_RESULT="$CT2_TMP_DIR/visa_save.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=visa&action=saveDocumentChecklist" "$CT2_VISA_RESULT" \
    -F "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    -F "ct2_visa_application_id=$CT2_VISA_APPLICATION_ID" \
    -F "ct2_application_checklist_id=$CT2_CHECKLIST_ID" \
    -F "checklist_status=verified" \
    -F "verification_notes=Hardening checklist upload $CT2_RUN_ID" \
    -F "ct2_document_file=@$CT2_UPLOAD_FILE;type=image/png")" "Visa upload did not complete"
assert_file_contains "$CT2_VISA_RESULT" "Document and checklist status updated." "Visa upload success flash was not rendered"
assert_equals "$((CT2_VISA_AUDIT_BEFORE + 1))" "$(probe audit-count visa.document_checklist_update visa_application "$CT2_VISA_APPLICATION_ID")" "Visa checklist audit log did not increment"
assert_equals "verified" "$(probe checklist-status VISA-APP-001 "Passport bio page")" "Visa checklist status did not update to verified"
CT2_STORED_DOCUMENT_PATH="$(probe latest-document-path VISA-APP-001)"
if [[ ! -f "$CT2_APP_DIR/$CT2_STORED_DOCUMENT_PATH" ]]; then
    fail "Stored visa document does not exist at $CT2_STORED_DOCUMENT_PATH"
fi

log "Running financial flag update and CSV export verification."
CT2_FINANCIAL_AUDIT_BEFORE="$(probe audit-count financial.flag_update reconciliation_flag "$CT2_FLAG_ID")"
CT2_FINANCIAL_PAGE="$CT2_TMP_DIR/financial.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=financial&action=index&ct2_report_run_id=$CT2_REPORT_RUN_ID&source_module=suppliers" "$CT2_FINANCIAL_PAGE")" "Financial page did not load"
CT2_CSRF_TOKEN="$(extract_csrf "$CT2_FINANCIAL_PAGE")"
CT2_FINANCIAL_RESULT="$CT2_TMP_DIR/financial_save.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=financial&action=resolveFlag" "$CT2_FINANCIAL_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_reconciliation_flag_id=$CT2_FLAG_ID" \
    --data-urlencode "flag_status=resolved" \
    --data-urlencode "resolution_notes=Hardening reconciliation update $CT2_RUN_ID" \
    --data-urlencode "ct2_report_run_id=$CT2_REPORT_RUN_ID" \
    --data-urlencode "source_module=suppliers" \
    --data-urlencode "flag_filter_status=open" \
    --data-urlencode "ct2_financial_report_id=0")" "Financial flag update did not complete"
assert_file_contains "$CT2_FINANCIAL_RESULT" "Reconciliation flag updated successfully." "Financial flag success flash was not rendered"
assert_equals "$((CT2_FINANCIAL_AUDIT_BEFORE + 1))" "$(probe audit-count financial.flag_update reconciliation_flag "$CT2_FLAG_ID")" "Financial flag audit log did not increment"
assert_equals "resolved" "$(probe flag-field suppliers SUP-CT2-002 flag_status)" "Financial flag status did not update to resolved"

CT2_EXPORT_HEADERS="$CT2_TMP_DIR/financial_export.headers"
CT2_EXPORT_FILE="$CT2_TMP_DIR/financial_export.csv"
assert_equals "200" "$(http_get_headers "$CT2_BASE_URL?module=financial&action=exportCsv&ct2_report_run_id=$CT2_REPORT_RUN_ID&source_module=suppliers" "$CT2_EXPORT_HEADERS" "$CT2_EXPORT_FILE")" "Financial export did not return 200"
assert_file_contains "$CT2_EXPORT_HEADERS" "Content-Type: text/csv; charset=utf-8" "Financial export did not return CSV content type"
assert_file_contains "$CT2_EXPORT_FILE" "report_run_id,report_name,run_label,source_module" "Financial export header row is missing"
assert_file_contains "$CT2_EXPORT_FILE" "SUP-CT2-002" "Financial export did not include the seeded supplier flag reference"

log "Signing out and proving stale-session writes are rejected."
CT2_LOGOUT_PAGE="$CT2_TMP_DIR/logout_source.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=dashboard&action=index" "$CT2_LOGOUT_PAGE")" "Dashboard page did not load before logout"
CT2_LOGOUT_TOKEN="$(extract_csrf "$CT2_LOGOUT_PAGE")"
CT2_LOGOUT_RESULT="$CT2_TMP_DIR/logout_result.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=auth&action=logout" "$CT2_LOGOUT_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_LOGOUT_TOKEN")" "Logout did not complete"
assert_file_contains "$CT2_LOGOUT_RESULT" "CT2 Back-Office Login" "Logout did not return to the login page"

CT2_STALE_AGENT_AUDIT_BEFORE="$(probe audit-count agents.update agent "$CT2_AGENT_ID")"
CT2_STALE_HEADERS="$CT2_TMP_DIR/stale_agent.headers"
CT2_STALE_BODY="$CT2_TMP_DIR/stale_agent.body"
CT2_STALE_STATUS="$(http_post_form_headers "$CT2_BASE_URL?module=agents&action=save" "$CT2_STALE_HEADERS" "$CT2_STALE_BODY" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_agent_id=$CT2_AGENT_ID" \
    --data-urlencode "agent_code=AGT-CT2-002" \
    --data-urlencode "agency_name=Island Connect Tours" \
    --data-urlencode "contact_person=Ramon Aquino" \
    --data-urlencode "email=ramon@islandconnect.example.com" \
    --data-urlencode "phone=+63-917-200-0002" \
    --data-urlencode "region=Visayas" \
    --data-urlencode "commission_rate=10.00" \
    --data-urlencode "support_level=priority" \
    --data-urlencode "approval_status=approved" \
    --data-urlencode "active_status=active" \
    --data-urlencode "external_booking_id=CT1-BKG-1002" \
    --data-urlencode "external_customer_id=CT1-CUST-8802" \
    --data-urlencode "external_payment_id=FIN-PAY-4402" \
    --data-urlencode "source_system=ct1")"
assert_equals "302" "$CT2_STALE_STATUS" "Stale-session agent post did not redirect"
assert_file_contains "$CT2_STALE_HEADERS" "Location: ct2_index.php?module=auth&action=login" "Stale-session agent post did not redirect to login"
assert_equals "$CT2_STALE_AGENT_AUDIT_BEFORE" "$(probe audit-count agents.update agent "$CT2_AGENT_ID")" "Stale-session agent post wrote an audit log"

log "Verifying protected API failures stay JSON-shaped."
CT2_API_ANON_HEADERS="$CT2_TMP_DIR/api_anon.headers"
CT2_API_ANON_BODY="$CT2_TMP_DIR/api_anon.json"
assert_equals "403" "$(curl -sS -D "$CT2_API_ANON_HEADERS" -o "$CT2_API_ANON_BODY" -w '%{http_code}' "$CT2_API_BASE_URL/ct2_agents.php")" "Anonymous agents API request did not return 403"
assert_file_contains "$CT2_API_ANON_HEADERS" "Content-Type: application/json; charset=utf-8" "Anonymous agents API request did not stay JSON"
assert_file_contains "$CT2_API_ANON_BODY" "\"success\":false" "Anonymous agents API response did not return the CT2 JSON envelope"
assert_file_contains "$CT2_API_ANON_BODY" "\"error\":\"Forbidden.\"" "Anonymous agents API response did not return the expected forbidden error"

CT2_API_METHOD_HEADERS="$CT2_TMP_DIR/api_method.headers"
CT2_API_METHOD_BODY="$CT2_TMP_DIR/api_method.json"
assert_equals "405" "$(curl -sS -D "$CT2_API_METHOD_HEADERS" -o "$CT2_API_METHOD_BODY" -w '%{http_code}' "$CT2_API_BASE_URL/ct2_auth_login.php")" "Wrong-method auth API request did not return 405"
assert_file_contains "$CT2_API_METHOD_HEADERS" "Content-Type: application/json; charset=utf-8" "Wrong-method auth API request did not stay JSON"
assert_file_contains "$CT2_API_METHOD_BODY" "\"error\":\"Method not allowed.\"" "Wrong-method auth API response did not return the expected error"

log "CT2 runtime hardening checks passed."
