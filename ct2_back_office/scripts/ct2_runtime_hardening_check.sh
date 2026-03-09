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
CT2_TOMORROW="$(php -r 'echo date("Y-m-d", strtotime("+1 day"));')"
CT2_NEXT_WEEK="$(php -r 'echo date("Y-m-d", strtotime("+7 day"));')"
CT2_TWO_WEEKS="$(php -r 'echo date("Y-m-d", strtotime("+14 day"));')"
CT2_THIRTY_DAYS="$(php -r 'echo date("Y-m-d", strtotime("+30 day"));')"
CT2_NEXT_YEAR="$(php -r 'echo date("Y-m-d", strtotime("+365 day"));')"
CT2_NOW_LOCAL="$(php -r 'echo date("Y-m-d\\TH:i");')"
CT2_NEXT_DAY_LOCAL="$(php -r 'echo date("Y-m-d\\TH:i", strtotime("+1 day"));')"
CT2_FOUR_DAYS_LOCAL="$(php -r 'echo date("Y-m-d\\TH:i", strtotime("+4 day"));')"

CT2_RESOURCE_NAME="CT2 Hardening Resource ${CT2_RUN_ID}"
CT2_PACKAGE_NAME="CT2 Hardening Package ${CT2_RUN_ID}"
CT2_ALLOCATION_BOOKING_ID="CT2-HARD-ALLOC-${CT2_RUN_ID}"
CT2_BLOCK_REASON="CT2 Hardening Block ${CT2_RUN_ID}"
CT2_PLATE_NUMBER="CT2H-${CT2_RUN_ID: -4}"
CT2_DRIVER_NAME="CT2 Hardening Driver ${CT2_RUN_ID}"
CT2_SERVICE_TYPE="CT2 Hardening Service ${CT2_RUN_ID}"

CT2_CONTRACT_CODE="CTR-HARD-${CT2_RUN_ID}"
CT2_CONTRACT_TITLE="CT2 Hardening Contract ${CT2_RUN_ID}"
CT2_SUPPLIER_NOTE_TITLE="CT2 Hardening Supplier Note ${CT2_RUN_ID}"
CT2_SUPPLIER_NOTE_BODY="Supplier regression note ${CT2_RUN_ID}"

CT2_CAMPAIGN_NAME="North Luzon Coach Summer Push Hardening ${CT2_RUN_ID}"
CT2_PROMOTION_NAME="North Luzon Early Bird Hardening ${CT2_RUN_ID}"
CT2_VOUCHER_CODE="VOUCH-HARD-${CT2_RUN_ID}"
CT2_VOUCHER_NAME="CT2 Hardening Voucher ${CT2_RUN_ID}"
CT2_AFFILIATE_NAME="Biyahe Deals Network Hardening ${CT2_RUN_ID}"
CT2_REFERRAL_CODE="HREF-${CT2_RUN_ID}"
CT2_REDEMPTION_BOOKING_ID="CT2-HARD-BOOK-${CT2_RUN_ID}"
CT2_REVIEW_BATCH_ID="REV-HARD-${CT2_RUN_ID}"
CT2_MARKETING_NOTE_TITLE="CT2 Hardening Marketing Note ${CT2_RUN_ID}"

CT2_PAYMENT_REFERENCE="PAY-HARD-${CT2_RUN_ID}"
CT2_NOTIFICATION_RECIPIENT="hardening-${CT2_RUN_ID}@example.com"
CT2_VISA_NOTE_NEXT_ACTION="$(php -r 'echo date("Y-m-d", strtotime("+3 day"));')"

CT2_FILTER_KEY="hardening_key_${CT2_RUN_ID}"
CT2_FILTER_LABEL="Hardening Filter ${CT2_RUN_ID}"
CT2_RUN_LABEL="CT2 Hardening Run ${CT2_RUN_ID}"

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

log "Verifying dashboard and seeded read paths."
CT2_DASHBOARD_PAGE="$CT2_TMP_DIR/dashboard.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=dashboard&action=index" "$CT2_DASHBOARD_PAGE")" "Dashboard route did not return 200"
assert_file_contains "$CT2_DASHBOARD_PAGE" "Back-Office Dashboard" "Dashboard content did not render"

CT2_AVAILABILITY_PAGE="$CT2_TMP_DIR/availability_read.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=availability&action=index&search=Skyline" "$CT2_AVAILABILITY_PAGE")" "Availability search route did not return 200"
assert_file_contains "$CT2_AVAILABILITY_PAGE" "Skyline Coaster 18-Seater" "Availability search did not render the seeded resource"
assert_file_contains "$CT2_AVAILABILITY_PAGE" "CT1-BKG-1001" "Availability search did not render the seeded booking reference"
assert_file_contains "$CT2_AVAILABILITY_PAGE" "NAA-4581" "Availability page did not render the seeded dispatch vehicle"

CT2_AGENT_ID="$(probe agent-id AGT-CT2-002)"
CT2_SUPPLIER_ID="$(probe supplier-id SUP-CT2-002)"
CT2_CAMPAIGN_ID="$(probe campaign-id CT2-MKT-001)"
CT2_PROMOTION_ID="$(probe promotion-id PROMO-CT2-001)"
CT2_VOUCHER_ID="$(probe voucher-id VOUCH-CT2-001)"
CT2_AFFILIATE_ID="$(probe affiliate-id AFF-CT2-001)"
CT2_VISA_TYPE_ID="$(probe visa-type-id VISA-SG-TOUR)"
CT2_VISA_APPLICATION_ID="$(probe visa-application-id VISA-APP-001)"
CT2_APPROVAL_ID="$(probe approval-id supplier SUP-CT2-002)"
CT2_CHECKLIST_ID="$(probe checklist-id VISA-APP-001 "Passport bio page")"
CT2_REPORT_RUN_ID="$(probe report-run-id "QA Baseline Cross-Module Run")"
CT2_FINANCIAL_REPORT_ID="$(probe financial-report-id CT2-OPS-001)"
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
assert_equals "$((CT2_AGENT_AUDIT_BEFORE + 1))" "$(probe audit-count agents.update agent "$CT2_AGENT_ID")" "Agent update audit log did not increment"

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

log "Running supplier workflow coverage."
CT2_SUPPLIER_PAGE="$CT2_TMP_DIR/suppliers.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=suppliers&action=index&supplier_id=$CT2_SUPPLIER_ID" "$CT2_SUPPLIER_PAGE")" "Suppliers page did not load"
CT2_CSRF_TOKEN="$(extract_csrf "$CT2_SUPPLIER_PAGE")"

CT2_SUPPLIER_ONBOARDING_AUDIT_BEFORE="$(probe audit-count suppliers.onboarding_update supplier "$CT2_SUPPLIER_ID")"
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
    --data-urlencode "target_go_live_date=$CT2_TWO_WEEKS")" "Supplier onboarding update did not complete"
assert_file_contains "$CT2_SUPPLIER_RESULT" "Supplier onboarding record updated." "Supplier onboarding success flash was not rendered"
assert_equals "$((CT2_SUPPLIER_ONBOARDING_AUDIT_BEFORE + 1))" "$(probe audit-count suppliers.onboarding_update supplier "$CT2_SUPPLIER_ID")" "Supplier onboarding audit log did not increment"
assert_equals "$CT2_SUPPLIER_REVIEW_NOTE" "$(probe supplier-onboarding-field SUP-CT2-002 review_notes)" "Supplier onboarding review notes did not persist"

CT2_SUPPLIER_CONTRACT_AUDIT_BEFORE="$(probe audit-count suppliers.contract_create)"
CT2_SUPPLIER_CONTRACT_RESULT="$CT2_TMP_DIR/supplier_contract.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=suppliers&action=saveContract" "$CT2_SUPPLIER_CONTRACT_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_supplier_id=$CT2_SUPPLIER_ID" \
    --data-urlencode "contract_code=$CT2_CONTRACT_CODE" \
    --data-urlencode "contract_title=$CT2_CONTRACT_TITLE" \
    --data-urlencode "effective_date=$CT2_TODAY" \
    --data-urlencode "expiry_date=$CT2_THIRTY_DAYS" \
    --data-urlencode "renewal_status=not_started" \
    --data-urlencode "contract_status=draft" \
    --data-urlencode "clause_summary=Hardening contract summary ${CT2_RUN_ID}" \
    --data-urlencode "mock_signature_status=sent" \
    --data-urlencode "finance_handoff_status=shared")" "Supplier contract save did not complete"
assert_file_contains "$CT2_SUPPLIER_CONTRACT_RESULT" "Supplier contract registered." "Supplier contract success flash was not rendered"
assert_file_contains "$CT2_SUPPLIER_CONTRACT_RESULT" "$CT2_CONTRACT_TITLE" "Supplier contract row was not rendered"
assert_equals "$((CT2_SUPPLIER_CONTRACT_AUDIT_BEFORE + 1))" "$(probe audit-count suppliers.contract_create)" "Supplier contract audit log did not increment"

CT2_SUPPLIER_KPI_AUDIT_BEFORE="$(probe audit-count suppliers.kpi_create)"
CT2_SUPPLIER_KPI_RESULT="$CT2_TMP_DIR/supplier_kpi.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=suppliers&action=saveKpi" "$CT2_SUPPLIER_KPI_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_supplier_id=$CT2_SUPPLIER_ID" \
    --data-urlencode "measurement_date=$CT2_TODAY" \
    --data-urlencode "service_score=87.00" \
    --data-urlencode "delivery_score=85.00" \
    --data-urlencode "compliance_score=88.00" \
    --data-urlencode "responsiveness_score=86.00" \
    --data-urlencode "risk_flag=watch" \
    --data-urlencode "notes=Hardening KPI entry ${CT2_RUN_ID}")" "Supplier KPI save did not complete"
assert_file_contains "$CT2_SUPPLIER_KPI_RESULT" "Supplier KPI measurement saved." "Supplier KPI success flash was not rendered"
assert_file_contains "$CT2_SUPPLIER_KPI_RESULT" "$CT2_TODAY" "Supplier KPI measurement date was not rendered"
assert_equals "$((CT2_SUPPLIER_KPI_AUDIT_BEFORE + 1))" "$(probe audit-count suppliers.kpi_create)" "Supplier KPI audit log did not increment"

CT2_SUPPLIER_NOTE_AUDIT_BEFORE="$(probe audit-count suppliers.note_create)"
CT2_SUPPLIER_NOTE_RESULT="$CT2_TMP_DIR/supplier_note.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=suppliers&action=saveNote" "$CT2_SUPPLIER_NOTE_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_supplier_id=$CT2_SUPPLIER_ID" \
    --data-urlencode "note_type=review" \
    --data-urlencode "note_title=$CT2_SUPPLIER_NOTE_TITLE" \
    --data-urlencode "note_body=$CT2_SUPPLIER_NOTE_BODY" \
    --data-urlencode "next_action_date=$CT2_NEXT_WEEK")" "Supplier note save did not complete"
assert_file_contains "$CT2_SUPPLIER_NOTE_RESULT" "Supplier relationship note recorded." "Supplier note success flash was not rendered"
assert_file_contains "$CT2_SUPPLIER_NOTE_RESULT" "$CT2_SUPPLIER_NOTE_TITLE" "Supplier note row was not rendered"
assert_equals "$((CT2_SUPPLIER_NOTE_AUDIT_BEFORE + 1))" "$(probe audit-count suppliers.note_create)" "Supplier note audit log did not increment"

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

log "Running availability workflow coverage."
CT2_AVAILABILITY_PAGE="$CT2_TMP_DIR/availability_forms.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=availability&action=index" "$CT2_AVAILABILITY_PAGE")" "Availability page did not load"
CT2_CSRF_TOKEN="$(extract_csrf "$CT2_AVAILABILITY_PAGE")"

CT2_RESOURCE_AUDIT_BEFORE="$(probe audit-count availability.resource_create)"
CT2_AVAILABILITY_NEGATIVE_RESULT="$CT2_TMP_DIR/availability_invalid_csrf.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=availability&action=saveResource" "$CT2_AVAILABILITY_NEGATIVE_RESULT" \
    --data-urlencode "ct2_csrf_token=invalid-ct2-token" \
    --data-urlencode "ct2_supplier_id=$CT2_SUPPLIER_ID" \
    --data-urlencode "resource_name=$CT2_RESOURCE_NAME" \
    --data-urlencode "resource_type=transport" \
    --data-urlencode "capacity=18" \
    --data-urlencode "base_cost=4200.00" \
    --data-urlencode "status=available" \
    --data-urlencode "notes=Invalid token should not persist")" "Invalid-CSRF availability resource post did not complete"
assert_file_contains "$CT2_AVAILABILITY_NEGATIVE_RESULT" "Invalid request token." "Availability invalid-CSRF flash was not rendered"
assert_equals "$CT2_RESOURCE_AUDIT_BEFORE" "$(probe audit-count availability.resource_create)" "Availability invalid-CSRF request wrote an audit log"

CT2_RESOURCE_RESULT="$CT2_TMP_DIR/availability_resource.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=availability&action=saveResource" "$CT2_RESOURCE_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_supplier_id=$CT2_SUPPLIER_ID" \
    --data-urlencode "resource_name=$CT2_RESOURCE_NAME" \
    --data-urlencode "resource_type=transport" \
    --data-urlencode "capacity=24" \
    --data-urlencode "base_cost=4200.00" \
    --data-urlencode "status=available" \
    --data-urlencode "notes=CT2 hardening resource ${CT2_RUN_ID}")" "Availability resource save did not complete"
assert_file_contains "$CT2_RESOURCE_RESULT" "Inventory resource saved." "Availability resource success flash was not rendered"
assert_file_contains "$CT2_RESOURCE_RESULT" "$CT2_RESOURCE_NAME" "Availability resource row was not rendered"
assert_equals "$((CT2_RESOURCE_AUDIT_BEFORE + 1))" "$(probe audit-count availability.resource_create)" "Availability resource audit log did not increment"
CT2_RESOURCE_ID="$(probe resource-id "$CT2_RESOURCE_NAME")"

CT2_PACKAGE_AUDIT_BEFORE="$(probe audit-count availability.package_create)"
CT2_PACKAGE_RESULT="$CT2_TMP_DIR/availability_package.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=availability&action=savePackage" "$CT2_PACKAGE_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "package_name=$CT2_PACKAGE_NAME" \
    --data-urlencode "base_price=18999.00" \
    --data-urlencode "margin_percentage=18.00" \
    --data-urlencode "ct2_resource_id=$CT2_RESOURCE_ID" \
    --data-urlencode "units_required=1" \
    --data-urlencode "is_active=on")" "Availability package save did not complete"
assert_file_contains "$CT2_PACKAGE_RESULT" "Tour package saved." "Availability package success flash was not rendered"
assert_file_contains "$CT2_PACKAGE_RESULT" "$CT2_PACKAGE_NAME" "Availability package row was not rendered"
assert_equals "$((CT2_PACKAGE_AUDIT_BEFORE + 1))" "$(probe audit-count availability.package_create)" "Availability package audit log did not increment"
CT2_PACKAGE_ID="$(probe package-id "$CT2_PACKAGE_NAME")"

CT2_ALLOCATION_AUDIT_BEFORE="$(probe audit-count availability.allocation_create)"
CT2_ALLOCATION_RESULT="$CT2_TMP_DIR/availability_allocation.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=availability&action=saveAllocation" "$CT2_ALLOCATION_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_resource_id=$CT2_RESOURCE_ID" \
    --data-urlencode "ct2_package_id=$CT2_PACKAGE_ID" \
    --data-urlencode "external_booking_id=$CT2_ALLOCATION_BOOKING_ID" \
    --data-urlencode "allocation_date=$CT2_TODAY" \
    --data-urlencode "pax_count=12" \
    --data-urlencode "reserved_units=1" \
    --data-urlencode "notes=CT2 hardening allocation ${CT2_RUN_ID}")" "Availability allocation save did not complete"
assert_file_contains "$CT2_ALLOCATION_RESULT" "$CT2_ALLOCATION_BOOKING_ID" "Availability allocation row was not rendered"
assert_equals "$((CT2_ALLOCATION_AUDIT_BEFORE + 1))" "$(probe audit-count availability.allocation_create)" "Availability allocation audit log did not increment"

CT2_BLOCK_AUDIT_BEFORE="$(probe audit-count availability.block_create)"
CT2_BLOCK_RESULT="$CT2_TMP_DIR/availability_block.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=availability&action=saveBlock" "$CT2_BLOCK_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_resource_id=$CT2_RESOURCE_ID" \
    --data-urlencode "start_date=$CT2_NEXT_WEEK" \
    --data-urlencode "end_date=$CT2_TWO_WEEKS" \
    --data-urlencode "reason=$CT2_BLOCK_REASON" \
    --data-urlencode "block_type=manual_soft_block")" "Availability block save did not complete"
assert_file_contains "$CT2_BLOCK_RESULT" "Seasonal block created." "Availability block success flash was not rendered"
assert_file_contains "$CT2_BLOCK_RESULT" "$CT2_BLOCK_REASON" "Availability block row was not rendered"
assert_equals "$((CT2_BLOCK_AUDIT_BEFORE + 1))" "$(probe audit-count availability.block_create)" "Availability block audit log did not increment"

CT2_VEHICLE_AUDIT_BEFORE="$(probe audit-count availability.vehicle_create)"
CT2_VEHICLE_RESULT="$CT2_TMP_DIR/availability_vehicle.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=availability&action=saveVehicle" "$CT2_VEHICLE_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "plate_number=$CT2_PLATE_NUMBER" \
    --data-urlencode "model_name=CT2 Hardening Transporter" \
    --data-urlencode "vehicle_capacity=24" \
    --data-urlencode "current_mileage=12000" \
    --data-urlencode "vehicle_status=available")" "Availability vehicle save did not complete"
assert_file_contains "$CT2_VEHICLE_RESULT" "Dispatch vehicle saved." "Availability vehicle success flash was not rendered"
assert_file_contains "$CT2_VEHICLE_RESULT" "$CT2_PLATE_NUMBER" "Availability vehicle row was not rendered"
assert_equals "$((CT2_VEHICLE_AUDIT_BEFORE + 1))" "$(probe audit-count availability.vehicle_create)" "Availability vehicle audit log did not increment"
CT2_VEHICLE_ID="$(probe vehicle-id "$CT2_PLATE_NUMBER")"

CT2_DRIVER_AUDIT_BEFORE="$(probe audit-count availability.driver_create)"
CT2_DRIVER_RESULT="$CT2_TMP_DIR/availability_driver.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=availability&action=saveDriver" "$CT2_DRIVER_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "driver_name=$CT2_DRIVER_NAME" \
    --data-urlencode "license_expiry=$CT2_NEXT_YEAR" \
    --data-urlencode "driver_status=available")" "Availability driver save did not complete"
assert_file_contains "$CT2_DRIVER_RESULT" "Dispatch driver saved." "Availability driver success flash was not rendered"
assert_file_contains "$CT2_DRIVER_RESULT" "$CT2_DRIVER_NAME" "Availability driver row was not rendered"
assert_equals "$((CT2_DRIVER_AUDIT_BEFORE + 1))" "$(probe audit-count availability.driver_create)" "Availability driver audit log did not increment"
CT2_DRIVER_ID="$(probe driver-id "$CT2_DRIVER_NAME")"

CT2_DISPATCH_AUDIT_BEFORE="$(probe audit-count availability.dispatch_create)"
CT2_DISPATCH_RESULT="$CT2_TMP_DIR/availability_dispatch.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=availability&action=saveDispatch" "$CT2_DISPATCH_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_allocation_id=0" \
    --data-urlencode "ct2_vehicle_id=$CT2_VEHICLE_ID" \
    --data-urlencode "ct2_driver_id=$CT2_DRIVER_ID" \
    --data-urlencode "dispatch_date=$CT2_TOMORROW" \
    --data-urlencode "dispatch_time=$CT2_NEXT_DAY_LOCAL" \
    --data-urlencode "return_time=$CT2_FOUR_DAYS_LOCAL" \
    --data-urlencode "start_mileage=12000" \
    --data-urlencode "end_mileage=12120" \
    --data-urlencode "dispatch_status=scheduled")" "Availability dispatch save did not complete"
assert_file_contains "$CT2_DISPATCH_RESULT" "Dispatch order saved." "Availability dispatch success flash was not rendered"
assert_file_contains "$CT2_DISPATCH_RESULT" "$CT2_PLATE_NUMBER" "Availability dispatch row was not rendered"
assert_file_contains "$CT2_DISPATCH_RESULT" "$CT2_DRIVER_NAME" "Availability dispatch driver was not rendered"
assert_equals "$((CT2_DISPATCH_AUDIT_BEFORE + 1))" "$(probe audit-count availability.dispatch_create)" "Availability dispatch audit log did not increment"

CT2_MAINTENANCE_AUDIT_BEFORE="$(probe audit-count availability.maintenance_create)"
CT2_MAINTENANCE_RESULT="$CT2_TMP_DIR/availability_maintenance.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=availability&action=saveMaintenance" "$CT2_MAINTENANCE_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "maintenance_vehicle_id=$CT2_VEHICLE_ID" \
    --data-urlencode "service_date=$CT2_TOMORROW" \
    --data-urlencode "service_type=$CT2_SERVICE_TYPE" \
    --data-urlencode "maintenance_cost=6500.00" \
    --data-urlencode "mechanic_notes=CT2 hardening maintenance ${CT2_RUN_ID}")" "Availability maintenance save did not complete"
assert_file_contains "$CT2_MAINTENANCE_RESULT" "Maintenance log saved." "Availability maintenance success flash was not rendered"
assert_file_contains "$CT2_MAINTENANCE_RESULT" "$CT2_SERVICE_TYPE" "Availability maintenance row was not rendered"
assert_equals "$((CT2_MAINTENANCE_AUDIT_BEFORE + 1))" "$(probe audit-count availability.maintenance_create)" "Availability maintenance audit log did not increment"

log "Running marketing workflow coverage."
CT2_MARKETING_PAGE="$CT2_TMP_DIR/marketing.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=marketing&action=index" "$CT2_MARKETING_PAGE")" "Marketing page did not load"
CT2_CSRF_TOKEN="$(extract_csrf "$CT2_MARKETING_PAGE")"

CT2_CAMPAIGN_AUDIT_BEFORE="$(probe audit-count marketing.campaign_update)"
CT2_MARKETING_NEGATIVE_RESULT="$CT2_TMP_DIR/marketing_invalid_csrf.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=marketing&action=saveCampaign" "$CT2_MARKETING_NEGATIVE_RESULT" \
    --data-urlencode "ct2_csrf_token=invalid-ct2-token" \
    --data-urlencode "ct2_campaign_id=$CT2_CAMPAIGN_ID" \
    --data-urlencode "campaign_code=CT2-MKT-001" \
    --data-urlencode "campaign_name=$CT2_CAMPAIGN_NAME" \
    --data-urlencode "campaign_type=seasonal" \
    --data-urlencode "channel_type=hybrid" \
    --data-urlencode "start_date=$CT2_TODAY" \
    --data-urlencode "end_date=$CT2_THIRTY_DAYS" \
    --data-urlencode "budget_amount=150000.00" \
    --data-urlencode "status=active" \
    --data-urlencode "approval_status=approved" \
    --data-urlencode "target_audience=Invalid token should not persist" \
    --data-urlencode "external_customer_segment_id=SEG-HARD-INVALID" \
    --data-urlencode "source_system=crm")" "Invalid-CSRF marketing campaign post did not complete"
assert_file_contains "$CT2_MARKETING_NEGATIVE_RESULT" "Invalid request token." "Marketing invalid-CSRF flash was not rendered"
assert_equals "$CT2_CAMPAIGN_AUDIT_BEFORE" "$(probe audit-count marketing.campaign_update)" "Marketing invalid-CSRF request wrote an audit log"

CT2_CAMPAIGN_RESULT="$CT2_TMP_DIR/marketing_campaign.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=marketing&action=saveCampaign" "$CT2_CAMPAIGN_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_campaign_id=$CT2_CAMPAIGN_ID" \
    --data-urlencode "campaign_code=CT2-MKT-001" \
    --data-urlencode "campaign_name=$CT2_CAMPAIGN_NAME" \
    --data-urlencode "campaign_type=seasonal" \
    --data-urlencode "channel_type=hybrid" \
    --data-urlencode "start_date=$CT2_TODAY" \
    --data-urlencode "end_date=$CT2_THIRTY_DAYS" \
    --data-urlencode "budget_amount=155000.00" \
    --data-urlencode "status=active" \
    --data-urlencode "approval_status=approved" \
    --data-urlencode "target_audience=CT2 hardening audience ${CT2_RUN_ID}" \
    --data-urlencode "external_customer_segment_id=SEG-HARD-${CT2_RUN_ID}" \
    --data-urlencode "source_system=crm")" "Marketing campaign save did not complete"
assert_file_contains "$CT2_CAMPAIGN_RESULT" "Marketing campaign saved successfully." "Marketing campaign success flash was not rendered"
assert_file_contains "$CT2_CAMPAIGN_RESULT" "$CT2_CAMPAIGN_NAME" "Marketing campaign row was not rendered"
assert_equals "$((CT2_CAMPAIGN_AUDIT_BEFORE + 1))" "$(probe audit-count marketing.campaign_update)" "Marketing campaign audit log did not increment"

CT2_PROMOTION_AUDIT_BEFORE="$(probe audit-count marketing.promotion_update)"
CT2_PROMOTION_RESULT="$CT2_TMP_DIR/marketing_promotion.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=marketing&action=savePromotion" "$CT2_PROMOTION_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_promotion_id=$CT2_PROMOTION_ID" \
    --data-urlencode "ct2_campaign_id=$CT2_CAMPAIGN_ID" \
    --data-urlencode "promotion_code=PROMO-CT2-001" \
    --data-urlencode "promotion_name=$CT2_PROMOTION_NAME" \
    --data-urlencode "promotion_type=percentage" \
    --data-urlencode "discount_value=14.50" \
    --data-urlencode "eligibility_rule=CT2 hardening promotion rule ${CT2_RUN_ID}" \
    --data-urlencode "valid_from=$CT2_TODAY" \
    --data-urlencode "valid_until=$CT2_TWO_WEEKS" \
    --data-urlencode "usage_limit=150" \
    --data-urlencode "promotion_status=active" \
    --data-urlencode "approval_status=approved" \
    --data-urlencode "external_booking_scope=hardening_scope_${CT2_RUN_ID}" \
    --data-urlencode "source_system=ct1")" "Marketing promotion save did not complete"
assert_file_contains "$CT2_PROMOTION_RESULT" "Promotion saved successfully." "Marketing promotion success flash was not rendered"
assert_file_contains "$CT2_PROMOTION_RESULT" "$CT2_PROMOTION_NAME" "Marketing promotion row was not rendered"
assert_equals "$((CT2_PROMOTION_AUDIT_BEFORE + 1))" "$(probe audit-count marketing.promotion_update)" "Marketing promotion audit log did not increment"

CT2_VOUCHER_AUDIT_BEFORE="$(probe audit-count marketing.voucher_create)"
CT2_VOUCHER_RESULT="$CT2_TMP_DIR/marketing_voucher.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=marketing&action=saveVoucher" "$CT2_VOUCHER_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_promotion_id=$CT2_PROMOTION_ID" \
    --data-urlencode "voucher_code=$CT2_VOUCHER_CODE" \
    --data-urlencode "voucher_name=$CT2_VOUCHER_NAME" \
    --data-urlencode "customer_scope=multi_use" \
    --data-urlencode "max_redemptions=3" \
    --data-urlencode "voucher_status=active" \
    --data-urlencode "valid_from=$CT2_TODAY" \
    --data-urlencode "valid_until=$CT2_TWO_WEEKS" \
    --data-urlencode "external_customer_id=CT1-CUST-HARD-${CT2_RUN_ID}" \
    --data-urlencode "source_system=crm")" "Marketing voucher save did not complete"
assert_file_contains "$CT2_VOUCHER_RESULT" "Voucher saved successfully." "Marketing voucher success flash was not rendered"
assert_file_contains "$CT2_VOUCHER_RESULT" "$CT2_VOUCHER_CODE" "Marketing voucher row was not rendered"
assert_equals "$((CT2_VOUCHER_AUDIT_BEFORE + 1))" "$(probe audit-count marketing.voucher_create)" "Marketing voucher audit log did not increment"
CT2_VOUCHER_ID="$(probe voucher-id "$CT2_VOUCHER_CODE")"

CT2_AFFILIATE_AUDIT_BEFORE="$(probe audit-count marketing.affiliate_update)"
CT2_AFFILIATE_RESULT="$CT2_TMP_DIR/marketing_affiliate.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=marketing&action=saveAffiliate" "$CT2_AFFILIATE_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_affiliate_id=$CT2_AFFILIATE_ID" \
    --data-urlencode "affiliate_code=AFF-CT2-001" \
    --data-urlencode "affiliate_name=$CT2_AFFILIATE_NAME" \
    --data-urlencode "contact_name=Cris Villanueva" \
    --data-urlencode "email=cris@biyahenetwork.example.com" \
    --data-urlencode "phone=+63-917-400-0001" \
    --data-urlencode "affiliate_status=active" \
    --data-urlencode "commission_rate=8.50" \
    --data-urlencode "payout_status=ready" \
    --data-urlencode "referral_code=BIYAHE-QA" \
    --data-urlencode "external_partner_id=PARTNER-7701" \
    --data-urlencode "source_system=partner_portal")" "Marketing affiliate save did not complete"
assert_file_contains "$CT2_AFFILIATE_RESULT" "Affiliate profile saved successfully." "Marketing affiliate success flash was not rendered"
assert_file_contains "$CT2_AFFILIATE_RESULT" "$CT2_AFFILIATE_NAME" "Marketing affiliate row was not rendered"
assert_equals "$((CT2_AFFILIATE_AUDIT_BEFORE + 1))" "$(probe audit-count marketing.affiliate_update)" "Marketing affiliate audit log did not increment"

CT2_REFERRAL_AUDIT_BEFORE="$(probe audit-count marketing.referral_create)"
CT2_REFERRAL_RESULT="$CT2_TMP_DIR/marketing_referral.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=marketing&action=saveReferral" "$CT2_REFERRAL_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_affiliate_id=$CT2_AFFILIATE_ID" \
    --data-urlencode "ct2_campaign_id=$CT2_CAMPAIGN_ID" \
    --data-urlencode "referral_code=$CT2_REFERRAL_CODE" \
    --data-urlencode "click_date=$CT2_NOW_LOCAL" \
    --data-urlencode "landing_page=/ct2-hardening/${CT2_RUN_ID}" \
    --data-urlencode "external_customer_id=CT1-CUST-HARD-${CT2_RUN_ID}" \
    --data-urlencode "external_booking_id=$CT2_REDEMPTION_BOOKING_ID" \
    --data-urlencode "attribution_status=booked" \
    --data-urlencode "source_system=web")" "Marketing referral save did not complete"
assert_file_contains "$CT2_REFERRAL_RESULT" "Referral click recorded." "Marketing referral success flash was not rendered"
assert_file_contains "$CT2_REFERRAL_RESULT" "$CT2_REDEMPTION_BOOKING_ID" "Marketing referral row was not rendered"
assert_equals "$((CT2_REFERRAL_AUDIT_BEFORE + 1))" "$(probe audit-count marketing.referral_create)" "Marketing referral audit log did not increment"

CT2_REDEMPTION_AUDIT_BEFORE="$(probe audit-count marketing.redemption_create)"
CT2_REDEMPTION_RESULT="$CT2_TMP_DIR/marketing_redemption.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=marketing&action=saveRedemption" "$CT2_REDEMPTION_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_campaign_id=$CT2_CAMPAIGN_ID" \
    --data-urlencode "ct2_promotion_id=$CT2_PROMOTION_ID" \
    --data-urlencode "ct2_voucher_id=$CT2_VOUCHER_ID" \
    --data-urlencode "redemption_date=$CT2_NOW_LOCAL" \
    --data-urlencode "external_customer_id=CT1-CUST-HARD-${CT2_RUN_ID}" \
    --data-urlencode "external_booking_id=$CT2_REDEMPTION_BOOKING_ID" \
    --data-urlencode "redeemed_amount=1800.00" \
    --data-urlencode "redemption_status=redeemed" \
    --data-urlencode "source_system=ct1")" "Marketing redemption save did not complete"
assert_file_contains "$CT2_REDEMPTION_RESULT" "Redemption recorded successfully." "Marketing redemption success flash was not rendered"
assert_file_contains "$CT2_REDEMPTION_RESULT" "$CT2_VOUCHER_CODE" "Marketing redemption row was not rendered"
assert_equals "$((CT2_REDEMPTION_AUDIT_BEFORE + 1))" "$(probe audit-count marketing.redemption_create)" "Marketing redemption audit log did not increment"

CT2_METRIC_AUDIT_BEFORE="$(probe audit-count marketing.metric_create)"
CT2_METRIC_RESULT="$CT2_TMP_DIR/marketing_metric.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=marketing&action=saveMetric" "$CT2_METRIC_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_campaign_id=$CT2_CAMPAIGN_ID" \
    --data-urlencode "report_date=$CT2_TODAY" \
    --data-urlencode "impressions_count=10000" \
    --data-urlencode "click_count=500" \
    --data-urlencode "lead_count=60" \
    --data-urlencode "conversion_count=12" \
    --data-urlencode "attributed_revenue=125000.00" \
    --data-urlencode "positive_reviews=11" \
    --data-urlencode "neutral_reviews=2" \
    --data-urlencode "negative_reviews=1" \
    --data-urlencode "external_review_batch_id=$CT2_REVIEW_BATCH_ID" \
    --data-urlencode "source_system=analytics")" "Marketing metric save did not complete"
assert_file_contains "$CT2_METRIC_RESULT" "Campaign metrics saved." "Marketing metric success flash was not rendered"
assert_file_contains "$CT2_METRIC_RESULT" "$CT2_TODAY" "Marketing metric date was not rendered"
assert_equals "$((CT2_METRIC_AUDIT_BEFORE + 1))" "$(probe audit-count marketing.metric_create)" "Marketing metric audit log did not increment"

CT2_MARKETING_NOTE_AUDIT_BEFORE="$(probe audit-count marketing.note_create)"
CT2_MARKETING_NOTE_RESULT="$CT2_TMP_DIR/marketing_note.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=marketing&action=saveNote" "$CT2_MARKETING_NOTE_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_campaign_id=$CT2_CAMPAIGN_ID" \
    --data-urlencode "ct2_affiliate_id=$CT2_AFFILIATE_ID" \
    --data-urlencode "note_type=performance" \
    --data-urlencode "note_title=$CT2_MARKETING_NOTE_TITLE" \
    --data-urlencode "note_body=Marketing regression note ${CT2_RUN_ID}" \
    --data-urlencode "next_action_date=$CT2_NEXT_WEEK")" "Marketing note save did not complete"
assert_file_contains "$CT2_MARKETING_NOTE_RESULT" "Marketing note recorded." "Marketing note success flash was not rendered"
assert_file_contains "$CT2_MARKETING_NOTE_RESULT" "$CT2_MARKETING_NOTE_TITLE" "Marketing note row was not rendered"
assert_equals "$((CT2_MARKETING_NOTE_AUDIT_BEFORE + 1))" "$(probe audit-count marketing.note_create)" "Marketing note audit log did not increment"

log "Running visa checklist and auxiliary workflow coverage."
CT2_VISA_PAGE="$CT2_TMP_DIR/visa.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=visa&action=index" "$CT2_VISA_PAGE")" "Visa page did not load"
CT2_CSRF_TOKEN="$(extract_csrf "$CT2_VISA_PAGE")"

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

CT2_VISA_PAYMENT_AUDIT_BEFORE="$(probe audit-count visa.payment_create)"
CT2_VISA_PAYMENT_RESULT="$CT2_TMP_DIR/visa_payment.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=visa&action=savePayment" "$CT2_VISA_PAYMENT_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_visa_application_id=$CT2_VISA_APPLICATION_ID" \
    --data-urlencode "payment_reference=$CT2_PAYMENT_REFERENCE" \
    --data-urlencode "external_payment_id=FIN-HARD-${CT2_RUN_ID}" \
    --data-urlencode "amount=3500.00" \
    --data-urlencode "currency=PHP" \
    --data-urlencode "payment_method=Manual" \
    --data-urlencode "payment_status=completed" \
    --data-urlencode "paid_at=$CT2_NOW_LOCAL" \
    --data-urlencode "source_system=cashier")" "Visa payment save did not complete"
assert_file_contains "$CT2_VISA_PAYMENT_RESULT" "Visa payment recorded." "Visa payment success flash was not rendered"
assert_file_contains "$CT2_VISA_PAYMENT_RESULT" "$CT2_PAYMENT_REFERENCE" "Visa payment row was not rendered"
assert_equals "$((CT2_VISA_PAYMENT_AUDIT_BEFORE + 1))" "$(probe audit-count visa.payment_create)" "Visa payment audit log did not increment"

CT2_VISA_NOTIFICATION_AUDIT_BEFORE="$(probe audit-count visa.notification_create)"
CT2_VISA_NOTIFICATION_RESULT="$CT2_TMP_DIR/visa_notification.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=visa&action=saveNotification" "$CT2_VISA_NOTIFICATION_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_visa_application_id=$CT2_VISA_APPLICATION_ID" \
    --data-urlencode "notification_channel=email" \
    --data-urlencode "recipient_reference=$CT2_NOTIFICATION_RECIPIENT" \
    --data-urlencode "notification_subject=CT2 Hardening Notification ${CT2_RUN_ID}" \
    --data-urlencode "notification_message=Visa regression notification ${CT2_RUN_ID}" \
    --data-urlencode "delivery_status=sent")" "Visa notification save did not complete"
assert_file_contains "$CT2_VISA_NOTIFICATION_RESULT" "Notification log saved." "Visa notification success flash was not rendered"
assert_file_contains "$CT2_VISA_NOTIFICATION_RESULT" "$CT2_NOTIFICATION_RECIPIENT" "Visa notification row was not rendered"
assert_equals "$((CT2_VISA_NOTIFICATION_AUDIT_BEFORE + 1))" "$(probe audit-count visa.notification_create)" "Visa notification audit log did not increment"

CT2_VISA_NOTE_AUDIT_BEFORE="$(probe audit-count visa.note_create)"
CT2_VISA_NOTE_RESULT="$CT2_TMP_DIR/visa_note.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=visa&action=saveNote" "$CT2_VISA_NOTE_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_visa_application_id=$CT2_VISA_APPLICATION_ID" \
    --data-urlencode "note_type=review" \
    --data-urlencode "note_body=CT2 hardening visa note ${CT2_RUN_ID}" \
    --data-urlencode "next_action_date=$CT2_VISA_NOTE_NEXT_ACTION")" "Visa note save did not complete"
assert_file_contains "$CT2_VISA_NOTE_RESULT" "Visa case note recorded." "Visa note success flash was not rendered"
assert_file_contains "$CT2_VISA_NOTE_RESULT" "$CT2_VISA_NOTE_NEXT_ACTION" "Visa note row was not rendered"
assert_equals "$((CT2_VISA_NOTE_AUDIT_BEFORE + 1))" "$(probe audit-count visa.note_create)" "Visa note audit log did not increment"

log "Running financial workflow coverage."
CT2_FINANCIAL_PAGE="$CT2_TMP_DIR/financial.html"
assert_equals "200" "$(http_get "$CT2_BASE_URL?module=financial&action=index&ct2_report_run_id=$CT2_REPORT_RUN_ID&source_module=suppliers&ct2_financial_report_id=$CT2_FINANCIAL_REPORT_ID" "$CT2_FINANCIAL_PAGE")" "Financial page did not load"
CT2_CSRF_TOKEN="$(extract_csrf "$CT2_FINANCIAL_PAGE")"

CT2_FINANCIAL_RUN_AUDIT_BEFORE="$(probe audit-count financial.run_generate)"
CT2_FINANCIAL_NEGATIVE_RESULT="$CT2_TMP_DIR/financial_invalid_csrf.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=financial&action=runReport" "$CT2_FINANCIAL_NEGATIVE_RESULT" \
    --data-urlencode "ct2_csrf_token=invalid-ct2-token" \
    --data-urlencode "ct2_financial_report_id=$CT2_FINANCIAL_REPORT_ID" \
    --data-urlencode "run_label=$CT2_RUN_LABEL" \
    --data-urlencode "date_from=$CT2_TODAY" \
    --data-urlencode "date_to=$CT2_THIRTY_DAYS" \
    --data-urlencode "module_key=all" \
    --data-urlencode "source_system=ct2")" "Invalid-CSRF financial run post did not complete"
assert_file_contains "$CT2_FINANCIAL_NEGATIVE_RESULT" "Invalid request token." "Financial invalid-CSRF flash was not rendered"
assert_equals "$CT2_FINANCIAL_RUN_AUDIT_BEFORE" "$(probe audit-count financial.run_generate)" "Financial invalid-CSRF request wrote an audit log"

CT2_FINANCIAL_FILTER_AUDIT_BEFORE="$(probe audit-count financial.filter_create)"
CT2_FINANCIAL_FILTER_RESULT="$CT2_TMP_DIR/financial_filter.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=financial&action=saveFilter" "$CT2_FINANCIAL_FILTER_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_financial_report_id=$CT2_FINANCIAL_REPORT_ID" \
    --data-urlencode "filter_key=$CT2_FILTER_KEY" \
    --data-urlencode "filter_label=$CT2_FILTER_LABEL" \
    --data-urlencode "filter_type=text" \
    --data-urlencode "default_value=" \
    --data-urlencode "sort_order=99")" "Financial filter save did not complete"
assert_file_contains "$CT2_FINANCIAL_FILTER_RESULT" "Report filter saved successfully." "Financial filter success flash was not rendered"
assert_file_contains "$CT2_FINANCIAL_FILTER_RESULT" "$CT2_FILTER_KEY" "Financial filter row was not rendered"
assert_equals "$((CT2_FINANCIAL_FILTER_AUDIT_BEFORE + 1))" "$(probe audit-count financial.filter_create)" "Financial filter audit log did not increment"

CT2_FINANCIAL_RUN_RESULT="$CT2_TMP_DIR/financial_run.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=financial&action=runReport" "$CT2_FINANCIAL_RUN_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_financial_report_id=$CT2_FINANCIAL_REPORT_ID" \
    --data-urlencode "run_label=$CT2_RUN_LABEL" \
    --data-urlencode "date_from=$CT2_TODAY" \
    --data-urlencode "date_to=$CT2_THIRTY_DAYS" \
    --data-urlencode "module_key=all" \
    --data-urlencode "source_system=ct2")" "Financial run generation did not complete"
assert_file_contains "$CT2_FINANCIAL_RUN_RESULT" "Financial report run generated" "Financial run success flash was not rendered"
assert_equals "$((CT2_FINANCIAL_RUN_AUDIT_BEFORE + 1))" "$(probe audit-count financial.run_generate)" "Financial run audit log did not increment"
CT2_HARDENING_RUN_ID="$(probe report-run-id "$CT2_RUN_LABEL")"

CT2_FINANCIAL_FLAG_AUDIT_BEFORE="$(probe audit-count financial.flag_update reconciliation_flag "$CT2_FLAG_ID")"
CT2_FINANCIAL_FLAG_RESULT="$CT2_TMP_DIR/financial_flag.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=financial&action=resolveFlag" "$CT2_FINANCIAL_FLAG_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_CSRF_TOKEN" \
    --data-urlencode "ct2_reconciliation_flag_id=$CT2_FLAG_ID" \
    --data-urlencode "flag_status=resolved" \
    --data-urlencode "resolution_notes=Hardening reconciliation update $CT2_RUN_ID" \
    --data-urlencode "ct2_report_run_id=$CT2_REPORT_RUN_ID" \
    --data-urlencode "source_module=suppliers" \
    --data-urlencode "flag_filter_status=open" \
    --data-urlencode "ct2_financial_report_id=$CT2_FINANCIAL_REPORT_ID")" "Financial flag update did not complete"
assert_file_contains "$CT2_FINANCIAL_FLAG_RESULT" "Reconciliation flag updated successfully." "Financial flag success flash was not rendered"
assert_equals "$((CT2_FINANCIAL_FLAG_AUDIT_BEFORE + 1))" "$(probe audit-count financial.flag_update reconciliation_flag "$CT2_FLAG_ID")" "Financial flag audit log did not increment"
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
CT2_STALE_TOKEN="$(extract_csrf "$CT2_LOGOUT_PAGE")"
CT2_LOGOUT_RESULT="$CT2_TMP_DIR/logout_result.html"
assert_equals "200" "$(http_post_form_follow "$CT2_BASE_URL?module=auth&action=logout" "$CT2_LOGOUT_RESULT" \
    --data-urlencode "ct2_csrf_token=$CT2_STALE_TOKEN")" "Logout did not complete"
assert_file_contains "$CT2_LOGOUT_RESULT" "CT2 Back-Office Login" "Logout did not return to the login page"

CT2_STALE_AGENT_AUDIT_BEFORE="$(probe audit-count agents.update agent "$CT2_AGENT_ID")"
CT2_STALE_AGENT_HEADERS="$CT2_TMP_DIR/stale_agent.headers"
CT2_STALE_AGENT_BODY="$CT2_TMP_DIR/stale_agent.body"
CT2_STALE_AGENT_STATUS="$(http_post_form_headers "$CT2_BASE_URL?module=agents&action=save" "$CT2_STALE_AGENT_HEADERS" "$CT2_STALE_AGENT_BODY" \
    --data-urlencode "ct2_csrf_token=$CT2_STALE_TOKEN" \
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
assert_equals "302" "$CT2_STALE_AGENT_STATUS" "Stale-session agent post did not redirect"
assert_file_contains "$CT2_STALE_AGENT_HEADERS" "Location: ct2_index.php?module=auth&action=login" "Stale-session agent post did not redirect to login"
assert_equals "$CT2_STALE_AGENT_AUDIT_BEFORE" "$(probe audit-count agents.update agent "$CT2_AGENT_ID")" "Stale-session agent post wrote an audit log"

CT2_STALE_FINANCIAL_AUDIT_BEFORE="$(probe audit-count financial.filter_create)"
CT2_STALE_FINANCIAL_HEADERS="$CT2_TMP_DIR/stale_financial.headers"
CT2_STALE_FINANCIAL_BODY="$CT2_TMP_DIR/stale_financial.body"
CT2_STALE_FINANCIAL_STATUS="$(http_post_form_headers "$CT2_BASE_URL?module=financial&action=saveFilter" "$CT2_STALE_FINANCIAL_HEADERS" "$CT2_STALE_FINANCIAL_BODY" \
    --data-urlencode "ct2_csrf_token=$CT2_STALE_TOKEN" \
    --data-urlencode "ct2_financial_report_id=$CT2_FINANCIAL_REPORT_ID" \
    --data-urlencode "filter_key=stale_filter_${CT2_RUN_ID}" \
    --data-urlencode "filter_label=Stale Filter ${CT2_RUN_ID}" \
    --data-urlencode "filter_type=text" \
    --data-urlencode "default_value=" \
    --data-urlencode "sort_order=100")"
assert_equals "302" "$CT2_STALE_FINANCIAL_STATUS" "Stale-session financial post did not redirect"
assert_file_contains "$CT2_STALE_FINANCIAL_HEADERS" "Location: ct2_index.php?module=auth&action=login" "Stale-session financial post did not redirect to login"
assert_equals "$CT2_STALE_FINANCIAL_AUDIT_BEFORE" "$(probe audit-count financial.filter_create)" "Stale-session financial post wrote an audit log"

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
