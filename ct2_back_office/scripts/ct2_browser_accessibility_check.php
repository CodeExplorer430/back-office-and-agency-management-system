<?php

declare(strict_types=1);

require_once __DIR__ . '/ct2_validation_common.php';

ct2AssertCli();

$ct2Prefix = 'ct2-browser-a11y';
$ct2TempDir = ct2CreateTempDir($ct2Prefix);
$ct2Server = null;
$ct2Chrome = null;

try {
    $ct2Node = ct2FindCommand(['node']);
    $ct2ChromeBinary = ct2FindCommand(['google-chrome', 'google-chrome-stable', 'chrome', 'chromium', 'chromium-browser']);

    if ($ct2Node === null || $ct2ChromeBinary === null) {
        ct2Log($ct2Prefix, 'Manual fallback required: Chrome automation dependencies are unavailable.');
        exit(0);
    }

    ct2Log($ct2Prefix, 'Starting local CT2 PHP server.');
    $ct2Server = ct2StartPhpServer(8097, $ct2TempDir);

    $ct2ChromePort = 9224;
    $ct2ChromeLog = $ct2TempDir . DIRECTORY_SEPARATOR . 'ct2_chrome.log';
    $ct2ChromeProfile = $ct2TempDir . DIRECTORY_SEPARATOR . 'chrome-profile';
    if (!mkdir($ct2ChromeProfile, 0777, true) && !is_dir($ct2ChromeProfile)) {
        throw new RuntimeException('Unable to create the Chrome profile directory.');
    }

    ct2Log($ct2Prefix, 'Starting headless Chrome for keyboard focus validation.');
    $ct2ChromeCommand = sprintf(
        '%s --headless=new --no-sandbox --disable-gpu --remote-debugging-port=%d --user-data-dir=%s about:blank',
        escapeshellarg($ct2ChromeBinary),
        $ct2ChromePort,
        escapeshellarg($ct2ChromeProfile)
    );
    $ct2Chrome = ct2StartBackgroundProcess($ct2ChromeCommand, $ct2ChromeLog);

    $ct2ChromeJsonList = $ct2TempDir . DIRECTORY_SEPARATOR . 'chrome_targets.json';
    $ct2ChromeReadyUrl = sprintf('http://127.0.0.1:%d/json/list', $ct2ChromePort);
    $ct2Deadline = microtime(true) + 30;
    while (microtime(true) < $ct2Deadline) {
        try {
            $ct2ChromeTargets = ct2RawHttpGet($ct2ChromeReadyUrl);
            if (($ct2ChromeTargets['status'] ?? 0) === 200) {
                file_put_contents($ct2ChromeJsonList, $ct2ChromeTargets['body']);
                break;
            }
        } catch (Throwable) {
        }

        usleep(250000);
    }

    if (!is_file($ct2ChromeJsonList)) {
        ct2Fail($ct2Prefix, 'Unable to start headless Chrome. See ' . $ct2ChromeLog . '.');
    }

    $ct2Environment = array_merge(
        $_ENV,
        [
            'CT2_BASE_URL' => 'http://127.0.0.1:8097/ct2_index.php',
            'CT2_CHROME_JSON_LIST' => $ct2ChromeJsonList,
        ]
    );

    $ct2NodeCommand = escapeshellarg($ct2Node) . ' ' . escapeshellarg(__DIR__ . '/ct2_browser_accessibility_check.js');
    $ct2Output = [];
    $ct2ExitCode = 0;
    $ct2Process = proc_open(
        $ct2NodeCommand,
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $ct2Pipes,
        ct2RepoRoot(),
        $ct2Environment
    );

    if (!is_resource($ct2Process)) {
        ct2Fail($ct2Prefix, 'Unable to start the shared browser accessibility runner.');
    }

    fclose($ct2Pipes[0]);
    $ct2Stdout = stream_get_contents($ct2Pipes[1]) ?: '';
    fclose($ct2Pipes[1]);
    $ct2Stderr = stream_get_contents($ct2Pipes[2]) ?: '';
    fclose($ct2Pipes[2]);
    $ct2ExitCode = proc_close($ct2Process);

    if ($ct2Stdout !== '') {
        fwrite(STDOUT, $ct2Stdout);
    }

    if ($ct2ExitCode !== 0) {
        if ($ct2Stderr !== '') {
            fwrite(STDERR, $ct2Stderr);
        }
        ct2Fail($ct2Prefix, 'The shared browser accessibility runner failed.');
    }
} finally {
    if (is_array($ct2Chrome)) {
        ct2StopProcess($ct2Chrome['proc']);
    }
    if (is_array($ct2Server)) {
        ct2StopProcess($ct2Server['proc']);
    }
    ct2RemoveDir($ct2TempDir);
}
