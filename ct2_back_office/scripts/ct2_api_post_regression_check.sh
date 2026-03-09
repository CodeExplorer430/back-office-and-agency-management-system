#!/usr/bin/env bash

set -euo pipefail

CT2_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CT2_APP_DIR="$CT2_ROOT_DIR/ct2_back_office"
CT2_PROBE_SCRIPT="$CT2_APP_DIR/scripts/ct2_regression_probe.php"
CT2_API_BASE_URL="http://127.0.0.1:8094/api"
CT2_COOKIE_ADMIN="$(mktemp)"
CT2_COOKIE_DESK="$(mktemp)"
CT2_TMP_DIR="$(mktemp -d)"
CT2_SERVER_LOG="$CT2_TMP_DIR/ct2_php_server.log"
CT2_SERVER_PID=""

CT2_RUN_ID="$(date +%s)"
CT2_TODAY="$(php -r 'echo date("Y-m-d");')"
CT2_TOMORROW="$(php -r 'echo date("Y-m-d", strtotime("+1 day"));')"
CT2_NEXT_WEEK="$(php -r 'echo date("Y-m-d", strtotime("+7 day"));')"
CT2_NEXT_MONTH="$(php -r 'echo date("Y-m-d", strtotime("+30 day"));')"
CT2_NOW_LOCAL="$(php -r 'echo date("Y-m-d\\TH:i");')"

CT2_AGENT_CODE="AGT-API-${CT2_RUN_ID}"
CT2_STAFF_CODE="STF-API-${CT2_RUN_ID}"
CT2_SUPPLIER_CODE="SUP-API-${CT2_RUN_ID}"
CT2_RESOURCE_NAME="CT2 API Resource ${CT2_RUN_ID}"
CT2_ALLOCATION_BOOKING_ID="CT2-API-BKG-${CT2_RUN_ID}"
CT2_CONTRACT_CODE="CTR-API-${CT2_RUN_ID}"
CT2_KPI_NOTE="CT2 API KPI ${CT2_RUN_ID}"
CT2_CAMPAIGN_CODE="CT2-API-MKT-${CT2_RUN_ID}"
CT2_PROMOTION_CODE="PROMO-API-${CT2_RUN_ID}"
CT2_VOUCHER_CODE="VOUCHAPI${CT2_RUN_ID}"
CT2_AFFILIATE_CODE="AFF-API-${CT2_RUN_ID}"
CT2_AFFILIATE_REFERRAL="AFFREF-${CT2_RUN_ID}"
CT2_APP_REFERENCE="VISA-API-${CT2_RUN_ID}"
CT2_PAYMENT_REFERENCE="PAY-API-${CT2_RUN_ID}"
CT2_DOC_NAME="api_doc_${CT2_RUN_ID}.pdf"
CT2_DOC_PATH="storage/uploads/api/visa_${CT2_RUN_ID}.pdf"
CT2_REPORT_CODE="FIN-API-${CT2_RUN_ID}"
CT2_REPORT_NAME="CT2 API Report ${CT2_RUN_ID}"
CT2_FLAG_NOTE="CT2 API flag note ${CT2_RUN_ID}"

cleanup() {
    if [[ -n "$CT2_SERVER_PID" ]] && kill -0 "$CT2_SERVER_PID" >/dev/null 2>&1; then
        kill "$CT2_SERVER_PID" >/dev/null 2>&1 || true
        wait "$CT2_SERVER_PID" 2>/dev/null || true
    fi

    rm -f "$CT2_COOKIE_ADMIN" "$CT2_COOKIE_DESK"
    rm -rf "$CT2_TMP_DIR"
}

trap cleanup EXIT

log() {
    printf '[ct2-api-post] %s\n' "$1"
}

fail() {
    printf '[ct2-api-post] ERROR: %s\n' "$1" >&2
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

assert_header_contains() {
    local file_path="$1"
    local expected_text="$2"
    local message="$3"

    if ! grep -Fiq "$expected_text" "$file_path"; then
        fail "$message"
    fi
}

probe() {
    php "$CT2_PROBE_SCRIPT" "$@"
}

json_value() {
    php -r '
        $data = json_decode((string) file_get_contents($argv[1]), true);
        if (!is_array($data)) {
            exit(1);
        }
        $value = $data;
        foreach (explode(".", $argv[2]) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                exit(2);
            }
            $value = $value[$segment];
        }
        if (is_bool($value)) {
            echo $value ? "true" : "false";
            exit(0);
        }
        if ($value === null) {
            echo "null";
            exit(0);
        }
        if (is_scalar($value)) {
            echo (string) $value;
            exit(0);
        }
        echo json_encode($value);
    ' "$1" "$2"
}

api_log_count() {
    probe api-log-count "$1" POST "$2"
}

http_post_json() {
    local cookie_jar="$1"
    local url="$2"
    local payload="$3"
    local header_file="$4"
    local body_file="$5"

    curl -sS -b "$cookie_jar" -c "$cookie_jar" \
        -H 'Content-Type: application/json' \
        -D "$header_file" \
        -o "$body_file" \
        -w '%{http_code}' \
        -X POST \
        --data "$payload" \
        "$url"
}

http_get_json() {
    local cookie_jar="$1"
    local url="$2"
    local header_file="$3"
    local body_file="$4"

    curl -sS -b "$cookie_jar" -c "$cookie_jar" \
        -D "$header_file" \
        -o "$body_file" \
        -w '%{http_code}' \
        "$url"
}

assert_json_response() {
    local expected_status="$1"
    local header_file="$2"
    local body_file="$3"
    local expected_success="$4"
    local message="$5"

    assert_equals "$expected_status" "$(grep -m1 '^HTTP/' "$header_file" | awk '{print $2}')" "$message"
    assert_header_contains "$header_file" 'Content-Type: application/json' "$message"
    assert_equals "$expected_success" "$(json_value "$body_file" success)" "$message"

    if grep -Eqi '<html|Fatal error|Warning:|Notice:|Deprecated:' "$body_file"; then
        fail "$message"
    fi
}

start_server() {
    php -S 127.0.0.1:8094 -t "$CT2_APP_DIR" >"$CT2_SERVER_LOG" 2>&1 &
    CT2_SERVER_PID="$!"

    for _ in $(seq 1 30); do
        if curl -sS -o /dev/null "$CT2_API_BASE_URL/ct2_module_status.php"; then
            return 0
        fi
        sleep 1
    done

    fail "Unable to start the local CT2 PHP server. See $CT2_SERVER_LOG."
}

login_api() {
    local cookie_jar="$1"
    local username="$2"
    local password="$3"
    local expected_status="$4"
    local expected_success="$5"
    local body_file="$6"
    local header_file="$7"

    local payload
    payload=$(printf '{"username":"%s","password":"%s"}' "$username" "$password")
    http_post_json "$cookie_jar" "$CT2_API_BASE_URL/ct2_auth_login.php" "$payload" "$header_file" "$body_file" >/dev/null
    assert_json_response "$expected_status" "$header_file" "$body_file" "$expected_success" "API login response was invalid"
}

log "Starting local CT2 PHP server."
start_server

log "Validating API authentication."
CT2_HEADERS="$CT2_TMP_DIR/headers.txt"
CT2_BODY="$CT2_TMP_DIR/body.json"

CT2_AUTH_401_BEFORE="$(api_log_count ct2_auth_login 401)"
login_api "$CT2_COOKIE_ADMIN" 'ct2admin' 'WrongPassword!' '401' 'false' "$CT2_BODY" "$CT2_HEADERS"
assert_file_contains "$CT2_BODY" '"error":"Invalid credentials."' "API login invalid-credential error was not returned"
assert_equals "$((CT2_AUTH_401_BEFORE + 1))" "$(api_log_count ct2_auth_login 401)" "API login 401 log count did not increment"

CT2_AUTH_200_BEFORE="$(api_log_count ct2_auth_login 200)"
login_api "$CT2_COOKIE_ADMIN" 'ct2admin' 'ChangeMe123!' '200' 'true' "$CT2_BODY" "$CT2_HEADERS"
assert_file_contains "$CT2_BODY" '"username":"ct2admin"' "API login success did not return the admin user"
assert_equals "$((CT2_AUTH_200_BEFORE + 1))" "$(api_log_count ct2_auth_login 200)" "API login 200 log count did not increment"

login_api "$CT2_COOKIE_DESK" 'ct2desk' 'ChangeMe123!' '200' 'true' "$CT2_BODY" "$CT2_HEADERS"

log "Checking anonymous API denial."
CT2_ANON_COOKIE="$CT2_TMP_DIR/anon.cookie"
touch "$CT2_ANON_COOKIE"
CT2_ANON_403_BEFORE="$(api_log_count ct2_agents 403)"
http_post_json "$CT2_ANON_COOKIE" "$CT2_API_BASE_URL/ct2_agents.php" '{"agent_code":"ANON"}' "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '403' "$CT2_HEADERS" "$CT2_BODY" 'false' "Anonymous agent POST did not return JSON 403"
assert_equals "$((CT2_ANON_403_BEFORE + 1))" "$(api_log_count ct2_agents 403)" "Anonymous agent POST log count did not increment"

log "Creating agent through API."
CT2_AGENTS_422_BEFORE="$(api_log_count ct2_agents 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_agents.php" "{\"agent_code\":\"$CT2_AGENT_CODE\"}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Agent API invalid payload did not return JSON 422"
assert_file_contains "$CT2_BODY" '"error":"Missing field: agency_name"' "Agent API invalid payload error was not correct"
assert_equals "$((CT2_AGENTS_422_BEFORE + 1))" "$(api_log_count ct2_agents 422)" "Agent API 422 log count did not increment"

CT2_AGENTS_200_BEFORE="$(api_log_count ct2_agents 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_agents.php" "{
  \"agent_code\":\"$CT2_AGENT_CODE\",
  \"agency_name\":\"CT2 API Agency $CT2_RUN_ID\",
  \"contact_person\":\"API Agent Contact\",
  \"email\":\"agent-api-$CT2_RUN_ID@example.com\",
  \"phone\":\"+63-917-510-$((CT2_RUN_ID % 10000))\",
  \"region\":\"API Region\",
  \"commission_rate\":\"9.50\",
  \"support_level\":\"priority\",
  \"approval_status\":\"pending\",
  \"active_status\":\"active\",
  \"external_booking_id\":\"API-BKG-$CT2_RUN_ID\",
  \"external_customer_id\":\"API-CUST-$CT2_RUN_ID\",
  \"external_payment_id\":\"API-PAY-$CT2_RUN_ID\",
  \"source_system\":\"ct1\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Agent API create did not return JSON 200"
CT2_AGENT_ID="$(json_value "$CT2_BODY" data.ct2_agent_id)"
assert_equals "$((CT2_AGENTS_200_BEFORE + 1))" "$(api_log_count ct2_agents 200)" "Agent API 200 log count did not increment"
assert_equals "1" "$(probe audit-count agents.api_create agent "$CT2_AGENT_ID")" "Agent API create audit row was not recorded"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_agents.php?search=$CT2_AGENT_CODE" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Agent API search did not return JSON 200"
assert_file_contains "$CT2_BODY" "\"agent_code\":\"$CT2_AGENT_CODE\"" "Agent API search did not return the created agent"

log "Deciding approval through API."
CT2_APPROVAL_ID="$(probe approval-id agent "$CT2_AGENT_CODE")"
CT2_APPROVALS_422_BEFORE="$(api_log_count ct2_approvals 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_approvals.php" '{"approval_status":"approved"}' "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Approvals API invalid payload did not return JSON 422"
assert_equals "$((CT2_APPROVALS_422_BEFORE + 1))" "$(api_log_count ct2_approvals 422)" "Approvals API 422 log count did not increment"

CT2_APPROVALS_200_BEFORE="$(api_log_count ct2_approvals 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_approvals.php" "{
  \"ct2_approval_workflow_id\":$CT2_APPROVAL_ID,
  \"approval_status\":\"approved\",
  \"decision_notes\":\"API approval $CT2_RUN_ID\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Approvals API success did not return JSON 200"
assert_equals "$((CT2_APPROVALS_200_BEFORE + 1))" "$(api_log_count ct2_approvals 200)" "Approvals API 200 log count did not increment"
assert_equals "approved" "$(probe approval-status agent "$CT2_AGENT_CODE")" "Approvals API did not persist the approved status"
assert_equals "1" "$(probe audit-count approvals.api_decide agent "$CT2_AGENT_ID")" "Approvals API audit row was not recorded"

log "Creating staff through API."
CT2_STAFF_422_BEFORE="$(api_log_count ct2_staff 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_staff.php" "{\"staff_code\":\"$CT2_STAFF_CODE\"}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Staff API invalid payload did not return JSON 422"
assert_equals "$((CT2_STAFF_422_BEFORE + 1))" "$(api_log_count ct2_staff 422)" "Staff API 422 log count did not increment"

CT2_STAFF_200_BEFORE="$(api_log_count ct2_staff 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_staff.php" "{
  \"staff_code\":\"$CT2_STAFF_CODE\",
  \"full_name\":\"CT2 API Staff $CT2_RUN_ID\",
  \"email\":\"staff-api-$CT2_RUN_ID@example.com\",
  \"phone\":\"+63-917-520-$((CT2_RUN_ID % 10000))\",
  \"department\":\"API Ops\",
  \"position_title\":\"API Coordinator\",
  \"team_name\":\"API Team\",
  \"employment_status\":\"active\",
  \"availability_status\":\"available\",
  \"notes\":\"API staff record $CT2_RUN_ID\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Staff API create did not return JSON 200"
CT2_STAFF_ID="$(json_value "$CT2_BODY" data.ct2_staff_id)"
assert_equals "$((CT2_STAFF_200_BEFORE + 1))" "$(api_log_count ct2_staff 200)" "Staff API 200 log count did not increment"
assert_equals "1" "$(probe audit-count staff.api_create staff "$CT2_STAFF_ID")" "Staff API create audit row was not recorded"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_staff.php?search=$CT2_STAFF_CODE" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"staff_code\":\"$CT2_STAFF_CODE\"" "Staff API search did not return the created staff record"

log "Creating supplier and supplier auxiliary records through API."
CT2_SUPPLIERS_422_BEFORE="$(api_log_count ct2_suppliers 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_suppliers.php" "{\"supplier_code\":\"$CT2_SUPPLIER_CODE\"}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Suppliers API invalid payload did not return JSON 422"
assert_equals "$((CT2_SUPPLIERS_422_BEFORE + 1))" "$(api_log_count ct2_suppliers 422)" "Suppliers API 422 log count did not increment"

CT2_SUPPLIERS_200_BEFORE="$(api_log_count ct2_suppliers 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_suppliers.php" "{
  \"supplier_code\":\"$CT2_SUPPLIER_CODE\",
  \"supplier_name\":\"CT2 API Supplier $CT2_RUN_ID\",
  \"primary_contact_name\":\"API Supplier Contact\",
  \"email\":\"supplier-api-$CT2_RUN_ID@example.com\",
  \"phone\":\"+63-917-530-$((CT2_RUN_ID % 10000))\",
  \"service_category\":\"Transport\",
  \"support_tier\":\"priority\",
  \"approval_status\":\"pending\",
  \"onboarding_status\":\"draft\",
  \"active_status\":\"active\",
  \"risk_level\":\"low\",
  \"source_system\":\"financials\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Suppliers API create did not return JSON 200"
CT2_SUPPLIER_ID="$(json_value "$CT2_BODY" data.ct2_supplier_id)"
assert_equals "$((CT2_SUPPLIERS_200_BEFORE + 1))" "$(api_log_count ct2_suppliers 200)" "Suppliers API 200 log count did not increment"
assert_equals "1" "$(probe audit-count suppliers.api_create supplier "$CT2_SUPPLIER_ID")" "Suppliers API create audit row was not recorded"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_suppliers.php?search=$CT2_SUPPLIER_CODE" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"supplier_code\":\"$CT2_SUPPLIER_CODE\"" "Suppliers API search did not return the created supplier"

CT2_ONBOARDING_422_BEFORE="$(api_log_count ct2_supplier_onboarding 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_supplier_onboarding.php" '{}' "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Supplier onboarding API invalid payload did not return JSON 422"
assert_equals "$((CT2_ONBOARDING_422_BEFORE + 1))" "$(api_log_count ct2_supplier_onboarding 422)" "Supplier onboarding API 422 log count did not increment"

CT2_ONBOARDING_200_BEFORE="$(api_log_count ct2_supplier_onboarding 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_supplier_onboarding.php" "{
  \"ct2_supplier_id\":$CT2_SUPPLIER_ID,
  \"checklist_status\":\"review_ready\",
  \"documents_status\":\"complete\",
  \"compliance_status\":\"cleared\",
  \"review_notes\":\"API onboarding $CT2_RUN_ID\",
  \"target_go_live_date\":\"$CT2_NEXT_WEEK\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Supplier onboarding API update did not return JSON 200"
assert_equals "$((CT2_ONBOARDING_200_BEFORE + 1))" "$(api_log_count ct2_supplier_onboarding 200)" "Supplier onboarding API 200 log count did not increment"
assert_equals "1" "$(probe audit-count suppliers.api_onboarding_update supplier "$CT2_SUPPLIER_ID")" "Supplier onboarding API audit row was not recorded"
assert_equals "API onboarding $CT2_RUN_ID" "$(probe supplier-onboarding-field "$CT2_SUPPLIER_CODE" review_notes)" "Supplier onboarding API did not persist review notes"

CT2_CONTRACTS_422_BEFORE="$(api_log_count ct2_supplier_contracts 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_supplier_contracts.php" "{\"ct2_supplier_id\":$CT2_SUPPLIER_ID}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Supplier contracts API invalid payload did not return JSON 422"
assert_equals "$((CT2_CONTRACTS_422_BEFORE + 1))" "$(api_log_count ct2_supplier_contracts 422)" "Supplier contracts API 422 log count did not increment"

CT2_CONTRACTS_200_BEFORE="$(api_log_count ct2_supplier_contracts 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_supplier_contracts.php" "{
  \"ct2_supplier_id\":$CT2_SUPPLIER_ID,
  \"contract_code\":\"$CT2_CONTRACT_CODE\",
  \"contract_title\":\"CT2 API Contract $CT2_RUN_ID\",
  \"effective_date\":\"$CT2_TODAY\",
  \"expiry_date\":\"$CT2_NEXT_MONTH\",
  \"renewal_status\":\"not_started\",
  \"contract_status\":\"active\",
  \"clause_summary\":\"API supplier contract $CT2_RUN_ID\",
  \"mock_signature_status\":\"signed\",
  \"finance_handoff_status\":\"confirmed\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Supplier contracts API create did not return JSON 200"
CT2_CONTRACT_ID="$(json_value "$CT2_BODY" data.ct2_supplier_contract_id)"
assert_equals "$((CT2_CONTRACTS_200_BEFORE + 1))" "$(api_log_count ct2_supplier_contracts 200)" "Supplier contracts API 200 log count did not increment"
assert_equals "1" "$(probe audit-count suppliers.api_contract_create supplier_contract "$CT2_CONTRACT_ID")" "Supplier contract API audit row was not recorded"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_supplier_contracts.php" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"contract_code\":\"$CT2_CONTRACT_CODE\"" "Supplier contracts API GET did not return the created contract"

CT2_KPIS_422_BEFORE="$(api_log_count ct2_supplier_kpis 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_supplier_kpis.php" "{\"ct2_supplier_id\":$CT2_SUPPLIER_ID}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Supplier KPI API invalid payload did not return JSON 422"
assert_equals "$((CT2_KPIS_422_BEFORE + 1))" "$(api_log_count ct2_supplier_kpis 422)" "Supplier KPI API 422 log count did not increment"

CT2_KPIS_200_BEFORE="$(api_log_count ct2_supplier_kpis 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_supplier_kpis.php" "{
  \"ct2_supplier_id\":$CT2_SUPPLIER_ID,
  \"measurement_date\":\"$CT2_TODAY\",
  \"service_score\":91,
  \"delivery_score\":89,
  \"compliance_score\":94,
  \"responsiveness_score\":90,
  \"risk_flag\":\"watch\",
  \"notes\":\"$CT2_KPI_NOTE\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Supplier KPI API create did not return JSON 200"
CT2_KPI_ID="$(json_value "$CT2_BODY" data.ct2_supplier_kpi_id)"
assert_equals "$((CT2_KPIS_200_BEFORE + 1))" "$(api_log_count ct2_supplier_kpis 200)" "Supplier KPI API 200 log count did not increment"
assert_equals "1" "$(probe audit-count suppliers.api_kpi_create supplier_kpi "$CT2_KPI_ID")" "Supplier KPI API audit row was not recorded"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_supplier_kpis.php" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"notes\":\"$CT2_KPI_NOTE\"" "Supplier KPI API GET did not return the created KPI"

log "Creating availability records through API."
CT2_RESOURCES_422_BEFORE="$(api_log_count ct2_resources 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_resources.php" "{\"resource_name\":\"$CT2_RESOURCE_NAME\"}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Resources API invalid payload did not return JSON 422"
assert_equals "$((CT2_RESOURCES_422_BEFORE + 1))" "$(api_log_count ct2_resources 422)" "Resources API 422 log count did not increment"

CT2_RESOURCES_200_BEFORE="$(api_log_count ct2_resources 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_resources.php" "{
  \"ct2_supplier_id\":$CT2_SUPPLIER_ID,
  \"resource_name\":\"$CT2_RESOURCE_NAME\",
  \"resource_type\":\"transport\",
  \"capacity\":12,
  \"base_cost\":\"8500.00\",
  \"status\":\"available\",
  \"notes\":\"API resource $CT2_RUN_ID\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Resources API create did not return JSON 200"
CT2_RESOURCE_ID="$(json_value "$CT2_BODY" data.ct2_resource_id)"
assert_equals "$((CT2_RESOURCES_200_BEFORE + 1))" "$(api_log_count ct2_resources 200)" "Resources API 200 log count did not increment"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_resources.php?search=$(printf '%s' "$CT2_RESOURCE_NAME" | sed 's/ /%20/g')" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"resource_name\":\"$CT2_RESOURCE_NAME\"" "Resources API GET did not return the created resource"

CT2_ALLOC_422_BEFORE="$(api_log_count ct2_tour_availability 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_tour_availability.php" "{\"ct2_resource_id\":$CT2_RESOURCE_ID}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Tour availability API invalid payload did not return JSON 422"
assert_equals "$((CT2_ALLOC_422_BEFORE + 1))" "$(api_log_count ct2_tour_availability 422)" "Tour availability API 422 log count did not increment"

CT2_ALLOC_200_BEFORE="$(api_log_count ct2_tour_availability 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_tour_availability.php" "{
  \"ct2_resource_id\":$CT2_RESOURCE_ID,
  \"external_booking_id\":\"$CT2_ALLOCATION_BOOKING_ID\",
  \"allocation_date\":\"$CT2_TOMORROW\",
  \"pax_count\":8,
  \"reserved_units\":1,
  \"notes\":\"API allocation $CT2_RUN_ID\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Tour availability API create did not return JSON 200"
assert_equals "$((CT2_ALLOC_200_BEFORE + 1))" "$(api_log_count ct2_tour_availability 200)" "Tour availability API 200 log count did not increment"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_tour_availability.php" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"external_booking_id\":\"$CT2_ALLOCATION_BOOKING_ID\"" "Tour availability API GET did not return the created allocation"

log "Creating dispatch order through API."
CT2_VEHICLE_ID="$(probe vehicle-id NAA-4581)"
CT2_DRIVER_ID="$(probe driver-id "Aris Navarro")"
CT2_DISPATCH_422_BEFORE="$(api_log_count ct2_dispatch_orders 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_dispatch_orders.php" "{\"ct2_driver_id\":$CT2_DRIVER_ID}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Dispatch orders API invalid payload did not return JSON 422"
assert_equals "$((CT2_DISPATCH_422_BEFORE + 1))" "$(api_log_count ct2_dispatch_orders 422)" "Dispatch orders API 422 log count did not increment"

CT2_DISPATCH_200_BEFORE="$(api_log_count ct2_dispatch_orders 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_dispatch_orders.php" "{
  \"ct2_vehicle_id\":$CT2_VEHICLE_ID,
  \"ct2_driver_id\":$CT2_DRIVER_ID,
  \"dispatch_date\":\"$CT2_TOMORROW\",
  \"dispatch_time\":\"$CT2_NOW_LOCAL\",
  \"dispatch_status\":\"scheduled\",
  \"start_mileage\":$((CT2_RUN_ID % 100000))
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Dispatch orders API create did not return JSON 200"
CT2_DISPATCH_ID="$(json_value "$CT2_BODY" data.ct2_dispatch_order_id)"
assert_equals "$((CT2_DISPATCH_200_BEFORE + 1))" "$(api_log_count ct2_dispatch_orders 200)" "Dispatch orders API 200 log count did not increment"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_dispatch_orders.php" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"ct2_dispatch_order_id\":$CT2_DISPATCH_ID" "Dispatch orders API GET did not return the created dispatch order"

log "Creating marketing records through API."
CT2_CAMPAIGNS_422_BEFORE="$(api_log_count ct2_marketing_campaigns 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_marketing_campaigns.php" "{\"campaign_name\":\"API Campaign\"}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Marketing campaigns API invalid payload did not return JSON 422"
assert_equals "$((CT2_CAMPAIGNS_422_BEFORE + 1))" "$(api_log_count ct2_marketing_campaigns 422)" "Marketing campaigns API 422 log count did not increment"

CT2_CAMPAIGNS_200_BEFORE="$(api_log_count ct2_marketing_campaigns 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_marketing_campaigns.php" "{
  \"campaign_code\":\"$CT2_CAMPAIGN_CODE\",
  \"campaign_name\":\"CT2 API Campaign $CT2_RUN_ID\",
  \"campaign_type\":\"seasonal\",
  \"channel_type\":\"hybrid\",
  \"start_date\":\"$CT2_TODAY\",
  \"end_date\":\"$CT2_NEXT_MONTH\",
  \"budget_amount\":100000,
  \"status\":\"pending_approval\",
  \"approval_status\":\"pending\",
  \"target_audience\":\"API travelers\",
  \"source_system\":\"crm\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Marketing campaigns API create did not return JSON 200"
CT2_CAMPAIGN_ID="$(json_value "$CT2_BODY" data.ct2_campaign_id)"
assert_equals "$((CT2_CAMPAIGNS_200_BEFORE + 1))" "$(api_log_count ct2_marketing_campaigns 200)" "Marketing campaigns API 200 log count did not increment"
assert_equals "1" "$(probe audit-count marketing.api_campaign_create campaign "$CT2_CAMPAIGN_ID")" "Marketing campaign API audit row was not recorded"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_marketing_campaigns.php?search=$CT2_CAMPAIGN_CODE" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"campaign_code\":\"$CT2_CAMPAIGN_CODE\"" "Marketing campaigns API GET did not return the created campaign"

CT2_PROMOTIONS_422_BEFORE="$(api_log_count ct2_promotions 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_promotions.php" "{\"promotion_code\":\"$CT2_PROMOTION_CODE\"}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Promotions API invalid payload did not return JSON 422"
assert_equals "$((CT2_PROMOTIONS_422_BEFORE + 1))" "$(api_log_count ct2_promotions 422)" "Promotions API 422 log count did not increment"

CT2_PROMOTIONS_200_BEFORE="$(api_log_count ct2_promotions 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_promotions.php" "{
  \"ct2_campaign_id\":$CT2_CAMPAIGN_ID,
  \"promotion_code\":\"$CT2_PROMOTION_CODE\",
  \"promotion_name\":\"CT2 API Promotion $CT2_RUN_ID\",
  \"promotion_type\":\"percentage\",
  \"discount_value\":15,
  \"valid_from\":\"$CT2_TODAY\",
  \"valid_until\":\"$CT2_NEXT_MONTH\",
  \"usage_limit\":25,
  \"promotion_status\":\"pending_approval\",
  \"approval_status\":\"pending\",
  \"eligibility_rule\":\"API rule\",
  \"source_system\":\"ct1\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Promotions API create did not return JSON 200"
CT2_PROMOTION_ID="$(json_value "$CT2_BODY" data.ct2_promotion_id)"
assert_equals "$((CT2_PROMOTIONS_200_BEFORE + 1))" "$(api_log_count ct2_promotions 200)" "Promotions API 200 log count did not increment"
assert_equals "1" "$(probe audit-count marketing.api_promotion_create promotion "$CT2_PROMOTION_ID")" "Promotion API audit row was not recorded"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_promotions.php?search=$CT2_PROMOTION_CODE" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"promotion_code\":\"$CT2_PROMOTION_CODE\"" "Promotions API GET did not return the created promotion"

CT2_VOUCHERS_422_BEFORE="$(api_log_count ct2_vouchers 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_vouchers.php" "{\"voucher_name\":\"Missing code\"}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Vouchers API invalid payload did not return JSON 422"
assert_equals "$((CT2_VOUCHERS_422_BEFORE + 1))" "$(api_log_count ct2_vouchers 422)" "Vouchers API 422 log count did not increment"

CT2_VOUCHERS_200_BEFORE="$(api_log_count ct2_vouchers 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_vouchers.php" "{
  \"ct2_promotion_id\":$CT2_PROMOTION_ID,
  \"voucher_code\":\"$CT2_VOUCHER_CODE\",
  \"voucher_name\":\"CT2 API Voucher $CT2_RUN_ID\",
  \"customer_scope\":\"single_use\",
  \"max_redemptions\":1,
  \"voucher_status\":\"issued\",
  \"valid_from\":\"$CT2_TODAY\",
  \"valid_until\":\"$CT2_NEXT_MONTH\",
  \"external_customer_id\":\"API-CUST-$CT2_RUN_ID\",
  \"source_system\":\"ct1\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Vouchers API create did not return JSON 200"
CT2_VOUCHER_ID="$(json_value "$CT2_BODY" data.ct2_voucher_id)"
assert_equals "$((CT2_VOUCHERS_200_BEFORE + 1))" "$(api_log_count ct2_vouchers 200)" "Vouchers API 200 log count did not increment"
assert_equals "1" "$(probe audit-count marketing.api_voucher_create voucher "$CT2_VOUCHER_ID")" "Voucher API audit row was not recorded"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_vouchers.php" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"voucher_code\":\"$CT2_VOUCHER_CODE\"" "Vouchers API GET did not return the created voucher"

CT2_AFFILIATES_422_BEFORE="$(api_log_count ct2_affiliates 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_affiliates.php" "{\"affiliate_code\":\"$CT2_AFFILIATE_CODE\"}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Affiliates API invalid payload did not return JSON 422"
assert_equals "$((CT2_AFFILIATES_422_BEFORE + 1))" "$(api_log_count ct2_affiliates 422)" "Affiliates API 422 log count did not increment"

CT2_AFFILIATES_200_BEFORE="$(api_log_count ct2_affiliates 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_affiliates.php" "{
  \"affiliate_code\":\"$CT2_AFFILIATE_CODE\",
  \"affiliate_name\":\"CT2 API Affiliate $CT2_RUN_ID\",
  \"contact_name\":\"API Affiliate Contact\",
  \"email\":\"affiliate-api-$CT2_RUN_ID@example.com\",
  \"phone\":\"+63-917-540-$((CT2_RUN_ID % 10000))\",
  \"affiliate_status\":\"active\",
  \"commission_rate\":8.5,
  \"payout_status\":\"ready\",
  \"referral_code\":\"$CT2_AFFILIATE_REFERRAL\",
  \"source_system\":\"partner_portal\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Affiliates API create did not return JSON 200"
CT2_AFFILIATE_ID="$(json_value "$CT2_BODY" data.ct2_affiliate_id)"
assert_equals "$((CT2_AFFILIATES_200_BEFORE + 1))" "$(api_log_count ct2_affiliates 200)" "Affiliates API 200 log count did not increment"
assert_equals "1" "$(probe audit-count marketing.api_affiliate_create affiliate "$CT2_AFFILIATE_ID")" "Affiliate API audit row was not recorded"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_affiliates.php?search=$CT2_AFFILIATE_CODE" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"affiliate_code\":\"$CT2_AFFILIATE_CODE\"" "Affiliates API GET did not return the created affiliate"

log "Creating visa records through API."
CT2_VISA_TYPE_ID="$(probe visa-type-id VISA-SG-TOUR)"
CT2_VISA_APPS_422_BEFORE="$(api_log_count ct2_visa_applications 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_visa_applications.php" "{\"application_reference\":\"$CT2_APP_REFERENCE\"}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Visa applications API invalid payload did not return JSON 422"
assert_equals "$((CT2_VISA_APPS_422_BEFORE + 1))" "$(api_log_count ct2_visa_applications 422)" "Visa applications API 422 log count did not increment"

CT2_VISA_APPS_200_BEFORE="$(api_log_count ct2_visa_applications 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_visa_applications.php" "{
  \"ct2_visa_type_id\":$CT2_VISA_TYPE_ID,
  \"application_reference\":\"$CT2_APP_REFERENCE\",
  \"external_customer_id\":\"API-CUST-$CT2_RUN_ID\",
  \"external_agent_id\":\"$CT2_AGENT_CODE\",
  \"source_system\":\"ct1\",
  \"status\":\"submitted\",
  \"submission_date\":\"$CT2_TODAY\",
  \"approval_status\":\"not_required\",
  \"remarks\":\"API visa application $CT2_RUN_ID\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Visa applications API create did not return JSON 200"
CT2_VISA_APPLICATION_ID="$(json_value "$CT2_BODY" data.ct2_visa_application_id)"
assert_equals "$((CT2_VISA_APPS_200_BEFORE + 1))" "$(api_log_count ct2_visa_applications 200)" "Visa applications API 200 log count did not increment"
assert_equals "1" "$(probe audit-count visa.api_application_create visa_application "$CT2_VISA_APPLICATION_ID")" "Visa applications API audit row was not recorded"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_visa_applications.php?search=$CT2_APP_REFERENCE" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"application_reference\":\"$CT2_APP_REFERENCE\"" "Visa applications API GET did not return the created application"

CT2_CHECKLIST_ID="$(probe checklist-id "$CT2_APP_REFERENCE" "Passport bio page")"
CT2_VISA_CHECKLISTS_422_BEFORE="$(api_log_count ct2_visa_checklists 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_visa_checklists.php" "{\"ct2_visa_application_id\":$CT2_VISA_APPLICATION_ID}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Visa checklists API invalid payload did not return JSON 422"
assert_equals "$((CT2_VISA_CHECKLISTS_422_BEFORE + 1))" "$(api_log_count ct2_visa_checklists 422)" "Visa checklists API 422 log count did not increment"

CT2_VISA_CHECKLISTS_200_BEFORE="$(api_log_count ct2_visa_checklists 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_visa_checklists.php" "{
  \"ct2_visa_application_id\":$CT2_VISA_APPLICATION_ID,
  \"ct2_application_checklist_id\":$CT2_CHECKLIST_ID,
  \"checklist_status\":\"verified\",
  \"verification_notes\":\"API checklist $CT2_RUN_ID\",
  \"file_name\":\"$CT2_DOC_NAME\",
  \"file_path\":\"$CT2_DOC_PATH\",
  \"mime_type\":\"application/pdf\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Visa checklists API update did not return JSON 200"
assert_equals "$((CT2_VISA_CHECKLISTS_200_BEFORE + 1))" "$(api_log_count ct2_visa_checklists 200)" "Visa checklists API 200 log count did not increment"
assert_equals "1" "$(probe audit-count visa.api_checklist_update visa_application "$CT2_VISA_APPLICATION_ID")" "Visa checklists API audit row was not recorded"
assert_equals "verified" "$(probe checklist-status "$CT2_APP_REFERENCE" "Passport bio page")" "Visa checklists API did not persist the checklist status"
assert_equals "$CT2_DOC_NAME" "$(probe latest-document-name "$CT2_APP_REFERENCE")" "Visa checklists API did not persist the document metadata"

CT2_VISA_PAYMENTS_422_BEFORE="$(api_log_count ct2_visa_payments 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_visa_payments.php" "{\"payment_reference\":\"$CT2_PAYMENT_REFERENCE\"}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Visa payments API invalid payload did not return JSON 422"
assert_equals "$((CT2_VISA_PAYMENTS_422_BEFORE + 1))" "$(api_log_count ct2_visa_payments 422)" "Visa payments API 422 log count did not increment"

CT2_VISA_PAYMENTS_200_BEFORE="$(api_log_count ct2_visa_payments 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_visa_payments.php" "{
  \"ct2_visa_application_id\":$CT2_VISA_APPLICATION_ID,
  \"payment_reference\":\"$CT2_PAYMENT_REFERENCE\",
  \"amount\":\"4250.00\",
  \"currency\":\"PHP\",
  \"payment_method\":\"Manual\",
  \"payment_status\":\"completed\",
  \"source_system\":\"cashier\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Visa payments API create did not return JSON 200"
CT2_VISA_PAYMENT_ID="$(json_value "$CT2_BODY" data.ct2_visa_payment_id)"
assert_equals "$((CT2_VISA_PAYMENTS_200_BEFORE + 1))" "$(api_log_count ct2_visa_payments 200)" "Visa payments API 200 log count did not increment"
assert_equals "1" "$(probe audit-count visa.api_payment_create visa_payment "$CT2_VISA_PAYMENT_ID")" "Visa payments API audit row was not recorded"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_visa_payments.php" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"payment_reference\":\"$CT2_PAYMENT_REFERENCE\"" "Visa payments API GET did not return the created payment"

CT2_VISA_STATUS_422_BEFORE="$(api_log_count ct2_visa_status 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_visa_status.php" "{\"status\":\"completed\"}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Visa status API invalid payload did not return JSON 422"
assert_equals "$((CT2_VISA_STATUS_422_BEFORE + 1))" "$(api_log_count ct2_visa_status 422)" "Visa status API 422 log count did not increment"

CT2_VISA_STATUS_200_BEFORE="$(api_log_count ct2_visa_status 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_visa_status.php" "{
  \"ct2_visa_application_id\":$CT2_VISA_APPLICATION_ID,
  \"status\":\"appointment_scheduled\",
  \"appointment_date\":\"$CT2_NEXT_WEEK 09:00:00\",
  \"embassy_reference\":\"EMB-API-$CT2_RUN_ID\",
  \"approval_status\":\"not_required\",
  \"remarks\":\"API status update $CT2_RUN_ID\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Visa status API update did not return JSON 200"
assert_equals "$((CT2_VISA_STATUS_200_BEFORE + 1))" "$(api_log_count ct2_visa_status 200)" "Visa status API 200 log count did not increment"
assert_equals "1" "$(probe audit-count visa.api_status_update visa_application "$CT2_VISA_APPLICATION_ID")" "Visa status API audit row was not recorded"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_visa_applications.php?search=$CT2_APP_REFERENCE" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"status\":\"appointment_scheduled\"" "Visa applications API GET did not reflect the status update"

log "Creating financial records through API."
CT2_FINANCIAL_403_BEFORE="$(api_log_count ct2_financial_reports 403)"
http_post_json "$CT2_COOKIE_DESK" "$CT2_API_BASE_URL/ct2_financial_reports.php" '{"report_code":"DENIED","report_name":"Denied"}' "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '403' "$CT2_HEADERS" "$CT2_BODY" 'false' "Financial reports API desk POST did not return JSON 403"
assert_equals "$((CT2_FINANCIAL_403_BEFORE + 1))" "$(api_log_count ct2_financial_reports 403)" "Financial reports API 403 log count did not increment"

CT2_FINANCIAL_422_BEFORE="$(api_log_count ct2_financial_reports 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_financial_reports.php" "{
  \"report_code\":\"$CT2_REPORT_CODE\",
  \"report_name\":\"$CT2_REPORT_NAME\",
  \"report_scope\":\"invalid_scope\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Financial reports API invalid payload did not return JSON 422"
assert_equals "$((CT2_FINANCIAL_422_BEFORE + 1))" "$(api_log_count ct2_financial_reports 422)" "Financial reports API 422 log count did not increment"

CT2_FINANCIAL_200_BEFORE="$(api_log_count ct2_financial_reports 200)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_financial_reports.php" "{
  \"report_code\":\"$CT2_REPORT_CODE\",
  \"report_name\":\"$CT2_REPORT_NAME\",
  \"report_scope\":\"cross_module\",
  \"report_status\":\"active\",
  \"default_date_range\":\"30d\",
  \"definition_notes\":\"API report $CT2_RUN_ID\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Financial reports API create did not return JSON 200"
CT2_REPORT_ID="$(json_value "$CT2_BODY" data.ct2_financial_report_id)"
assert_equals "$((CT2_FINANCIAL_200_BEFORE + 1))" "$(api_log_count ct2_financial_reports 200)" "Financial reports API 200 log count did not increment"
assert_equals "1" "$(probe audit-count financial.api_report_create financial_report "$CT2_REPORT_ID")" "Financial reports API audit row was not recorded"
http_get_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_financial_reports.php" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_file_contains "$CT2_BODY" "\"report_code\":\"$CT2_REPORT_CODE\"" "Financial reports API GET did not return the created report"

CT2_FLAG_ID="$(probe flag-id suppliers SUP-CT2-002)"
CT2_FLAGS_403_BEFORE="$(api_log_count ct2_financial_snapshots 403)"
http_post_json "$CT2_COOKIE_DESK" "$CT2_API_BASE_URL/ct2_financial_snapshots.php" "{\"ct2_reconciliation_flag_id\":$CT2_FLAG_ID}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '403' "$CT2_HEADERS" "$CT2_BODY" 'false' "Financial snapshots API desk POST did not return JSON 403"
assert_equals "$((CT2_FLAGS_403_BEFORE + 1))" "$(api_log_count ct2_financial_snapshots 403)" "Financial snapshots API 403 log count did not increment"

CT2_FLAGS_422_BEFORE="$(api_log_count ct2_financial_snapshots 422)"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_financial_snapshots.php" "{
  \"ct2_reconciliation_flag_id\":$CT2_FLAG_ID,
  \"flag_status\":\"bad_status\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '422' "$CT2_HEADERS" "$CT2_BODY" 'false' "Financial snapshots API invalid payload did not return JSON 422"
assert_equals "$((CT2_FLAGS_422_BEFORE + 1))" "$(api_log_count ct2_financial_snapshots 422)" "Financial snapshots API 422 log count did not increment"

CT2_FLAGS_200_BEFORE="$(api_log_count ct2_financial_snapshots 200)"
CT2_FLAG_AUDIT_BEFORE="$(probe audit-count financial.api_flag_update reconciliation_flag "$CT2_FLAG_ID")"
http_post_json "$CT2_COOKIE_ADMIN" "$CT2_API_BASE_URL/ct2_financial_snapshots.php" "{
  \"ct2_reconciliation_flag_id\":$CT2_FLAG_ID,
  \"flag_status\":\"acknowledged\",
  \"resolution_notes\":\"$CT2_FLAG_NOTE\"
}" "$CT2_HEADERS" "$CT2_BODY" >/dev/null
assert_json_response '200' "$CT2_HEADERS" "$CT2_BODY" 'true' "Financial snapshots API update did not return JSON 200"
assert_equals "$((CT2_FLAGS_200_BEFORE + 1))" "$(api_log_count ct2_financial_snapshots 200)" "Financial snapshots API 200 log count did not increment"
assert_equals "$((CT2_FLAG_AUDIT_BEFORE + 1))" "$(probe audit-count financial.api_flag_update reconciliation_flag "$CT2_FLAG_ID")" "Financial snapshots API audit row did not increment"
assert_equals "acknowledged" "$(probe flag-field suppliers SUP-CT2-002 flag_status)" "Financial snapshots API did not persist the flag status"
assert_equals "$CT2_FLAG_NOTE" "$(probe flag-field suppliers SUP-CT2-002 resolution_notes)" "Financial snapshots API did not persist the resolution note"

log "CT2 API POST regression checks passed."
