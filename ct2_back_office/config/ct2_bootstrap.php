<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', PHP_SAPI === 'cli' ? '1' : '0');

define('CT2_BASE_PATH', dirname(__DIR__));
define('CT2_CONFIG_PATH', CT2_BASE_PATH . '/config');
define('CT2_MODEL_PATH', CT2_BASE_PATH . '/models');
define('CT2_CONTROLLER_PATH', CT2_BASE_PATH . '/controllers');
define('CT2_VIEW_PATH', CT2_BASE_PATH . '/views');
define('CT2_ASSET_PATH', CT2_BASE_PATH . '/assets');
define('CT2_API_PATH', CT2_BASE_PATH . '/api');
define('CT2_APP_NAME', 'CORE TRANSACTION 2');

require_once CT2_CONFIG_PATH . '/ct2_database.php';

$ct2SessionSavePath = CT2_BASE_PATH . '/storage/sessions';
if (!is_dir($ct2SessionSavePath)) {
    mkdir($ct2SessionSavePath, 0775, true);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $ct2IsHttps = (
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
    );

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(
        [
            'lifetime' => 0,
            'path' => '/',
            'secure' => $ct2IsHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
    session_name('ct2_session');
    session_save_path($ct2SessionSavePath);
    session_start();
}

spl_autoload_register(
    static function (string $ct2ClassName): void {
        if (strpos($ct2ClassName, 'CT2_') !== 0) {
            return;
        }

        $ct2RelativeFile = 'ct2_' . substr($ct2ClassName, 4) . '.php';
        $ct2Directories = [
            CT2_MODEL_PATH,
            CT2_CONTROLLER_PATH,
            CT2_CONFIG_PATH,
        ];

        foreach ($ct2Directories as $ct2Directory) {
            $ct2Candidate = $ct2Directory . '/' . $ct2RelativeFile;
            if (is_file($ct2Candidate)) {
                require_once $ct2Candidate;
                return;
            }
        }
    }
);

function ct2_asset_url(string $ct2RelativePath): string
{
    return 'assets/' . ltrim($ct2RelativePath, '/');
}

function ct2_url(array $ct2Parameters = []): string
{
    $ct2Script = basename($_SERVER['SCRIPT_NAME'] ?? 'ct2_index.php');
    $ct2Query = http_build_query($ct2Parameters);

    return $ct2Query === ''
        ? $ct2Script
        : $ct2Script . '?' . $ct2Query;
}

function ct2_app_url(array $ct2Parameters = []): string
{
    $ct2Query = http_build_query($ct2Parameters);
    return $ct2Query === ''
        ? '../ct2_index.php'
        : '../ct2_index.php?' . $ct2Query;
}

function ct2_redirect(array $ct2Parameters = []): void
{
    header('Location: ' . ct2_url($ct2Parameters));
    exit;
}

function ct2_flash(string $ct2Key, ?string $ct2Message = null): ?string
{
    if ($ct2Message !== null) {
        $_SESSION['ct2_flash'][$ct2Key] = $ct2Message;
        return null;
    }

    if (!isset($_SESSION['ct2_flash'][$ct2Key])) {
        return null;
    }

    $ct2Value = $_SESSION['ct2_flash'][$ct2Key];
    unset($_SESSION['ct2_flash'][$ct2Key]);

    return is_string($ct2Value) ? $ct2Value : null;
}

function ct2_csrf_token(): string
{
    if (!isset($_SESSION['ct2_csrf_token'])) {
        return ct2_regenerate_csrf_token();
    }

    return (string) $_SESSION['ct2_csrf_token'];
}

function ct2_regenerate_csrf_token(): string
{
    $_SESSION['ct2_csrf_token'] = bin2hex(random_bytes(32));
    return (string) $_SESSION['ct2_csrf_token'];
}

function ct2_verify_csrf(?string $ct2Token): bool
{
    return is_string($ct2Token)
        && isset($_SESSION['ct2_csrf_token'])
        && hash_equals((string) $_SESSION['ct2_csrf_token'], $ct2Token);
}

function ct2_is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function ct2_current_user(): ?array
{
    return isset($_SESSION['ct2_user']) && is_array($_SESSION['ct2_user'])
        ? $_SESSION['ct2_user']
        : null;
}

function ct2_current_user_id(): ?int
{
    $ct2User = ct2_current_user();
    return $ct2User !== null ? (int) $ct2User['ct2_user_id'] : null;
}

function ct2_store_user_session(array $ct2User): void
{
    $_SESSION['ct2_user'] = $ct2User;
}

function ct2_clear_user_session(): void
{
    unset($_SESSION['ct2_user']);
}

function ct2_rotate_session_for_auth(): string
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    ct2_regenerate_csrf_token();

    return session_id();
}

function ct2_has_permission(string $ct2PermissionKey): bool
{
    $ct2User = ct2_current_user();
    if ($ct2User === null) {
        return false;
    }

    $ct2Permissions = $ct2User['permissions'] ?? [];
    return is_array($ct2Permissions) && in_array($ct2PermissionKey, $ct2Permissions, true);
}

function ct2_require_auth(): void
{
    if (ct2_current_user() === null) {
        ct2_flash('error', 'Please sign in to continue.');
        ct2_redirect(['module' => 'auth', 'action' => 'login']);
    }
}

function ct2_require_permission(string $ct2PermissionKey): void
{
    ct2_require_auth();

    if (!ct2_has_permission($ct2PermissionKey)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function ct2_render(string $ct2View, array $ct2Data = []): void
{
    $ct2ViewFile = CT2_VIEW_PATH . '/' . $ct2View . '.php';
    if (!is_file($ct2ViewFile)) {
        throw new RuntimeException('CT2 view not found: ' . $ct2View);
    }

    extract($ct2Data, EXTR_SKIP);

    require CT2_VIEW_PATH . '/layouts/ct2_header.php';
    require $ct2ViewFile;
    require CT2_VIEW_PATH . '/layouts/ct2_footer.php';
}

function ct2_json_input(): array
{
    $ct2RawInput = file_get_contents('php://input');
    if ($ct2RawInput === false || trim($ct2RawInput) === '') {
        return [];
    }

    $ct2Decoded = json_decode($ct2RawInput, true);
    return is_array($ct2Decoded) ? $ct2Decoded : [];
}

function ct2_json_response(bool $ct2Success, array $ct2Data = [], ?string $ct2Error = null, int $ct2StatusCode = 200): void
{
    http_response_code($ct2StatusCode);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        [
            'success' => $ct2Success,
            'data' => $ct2Data,
            'error' => $ct2Error,
            'meta' => [
                'timestamp' => date(DATE_ATOM),
                'module' => 'ct2',
            ],
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    exit;
}

function ct2_api_forbidden(string $ct2EndpointName): never
{
    ct2_record_api_log($ct2EndpointName, strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), 403);
    ct2_json_response(false, [], 'Forbidden.', 403);
}

function ct2_require_api_permission(string $ct2EndpointName, string $ct2ReadPermission, ?string $ct2WritePermission = null): void
{
    if (ct2_current_user() === null || !ct2_has_permission('api.access')) {
        ct2_api_forbidden($ct2EndpointName);
    }

    $ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $ct2RequiredPermission = in_array($ct2Method, ['GET', 'HEAD'], true)
        ? $ct2ReadPermission
        : ($ct2WritePermission ?? $ct2ReadPermission);

    if (!ct2_has_permission($ct2RequiredPermission)) {
        ct2_api_forbidden($ct2EndpointName);
    }
}

function ct2_is_api_request(): bool
{
    $ct2ScriptFilename = realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $ct2ApiPath = realpath(CT2_API_PATH);

    return $ct2ScriptFilename !== false
        && $ct2ApiPath !== false
        && strpos($ct2ScriptFilename, $ct2ApiPath . DIRECTORY_SEPARATOR) === 0;
}

function ct2_is_validation_mode(): bool
{
    if (getenv('CT2_VALIDATION_MODE') === '1') {
        return true;
    }

    return PHP_SAPI === 'cli-server'
        && (string) ($_SERVER['HTTP_X_CT2_VALIDATION_MODE'] ?? '') === '1';
}

function ct2_render_error_page(int $ct2StatusCode, string $ct2Message): never
{
    http_response_code($ct2StatusCode);
    header('Content-Type: text/html; charset=utf-8');

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>CORE TRANSACTION 2</title></head><body>';
    echo '<h1>CORE TRANSACTION 2</h1>';
    echo '<p>' . htmlspecialchars($ct2Message, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</body></html>';
    exit;
}

function ct2_current_api_endpoint_name(): string
{
    $ct2ScriptName = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'ct2_api.php'));
    $ct2Endpoint = pathinfo($ct2ScriptName, PATHINFO_FILENAME);

    return $ct2Endpoint !== '' ? $ct2Endpoint : 'ct2_api';
}

function ct2_record_api_log(string $ct2EndpointName, string $ct2Method, int $ct2StatusCode, array $ct2RequestSummary = [], array $ct2ResponseSummary = []): void
{
    if (!class_exists('CT2_AuditLogModel')) {
        return;
    }

    $ct2AuditLogModel = new CT2_AuditLogModel();
    $ct2AuditLogModel->recordApi(
        ct2_current_user_id(),
        $ct2EndpointName,
        $ct2Method,
        $ct2StatusCode,
        $ct2RequestSummary,
        $ct2ResponseSummary
    );
}

if (ct2_is_api_request()) {
    ini_set('display_errors', '0');

    set_exception_handler(
        static function (Throwable $ct2Exception): void {
            $ct2EndpointName = ct2_current_api_endpoint_name();
            $ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

            error_log(
                sprintf(
                    '[CT2 API ERROR] %s %s: %s',
                    $ct2EndpointName,
                    get_class($ct2Exception),
                    $ct2Exception->getMessage()
                )
            );

            ct2_record_api_log(
                $ct2EndpointName,
                $ct2Method,
                500,
                [],
                ['exception' => get_class($ct2Exception)]
            );

            ct2_json_response(false, [], 'Internal server error.', 500);
        }
    );
}
