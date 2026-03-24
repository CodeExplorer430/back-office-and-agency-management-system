<?php

declare(strict_types=1);

if (!defined('CT2_VALIDATION_COMMON_LOADED')) {
    define('CT2_VALIDATION_COMMON_LOADED', true);

    function ct2AssertCli(): void
    {
        if (PHP_SAPI !== 'cli') {
            fwrite(STDERR, "CT2 validation scripts must run from the CLI.\n");
            exit(1);
        }
    }

    function ct2RepoRoot(): string
    {
        $ct2Root = realpath(__DIR__ . '/../..');
        if ($ct2Root === false) {
            throw new RuntimeException('Unable to resolve the CT2 repository root.');
        }

        return $ct2Root;
    }

    function ct2AppRoot(): string
    {
        $ct2Root = realpath(__DIR__ . '/..');
        if ($ct2Root === false) {
            throw new RuntimeException('Unable to resolve the CT2 application root.');
        }

        return $ct2Root;
    }

    function ct2Fail(string $prefix, string $message): never
    {
        fwrite(STDERR, sprintf('[%s] ERROR: %s' . PHP_EOL, $prefix, $message));
        exit(1);
    }

    function ct2Log(string $prefix, string $message): void
    {
        fwrite(STDOUT, sprintf('[%s] %s' . PHP_EOL, $prefix, $message));
    }

    function ct2CreateTempDir(string $prefix): string
    {
        $ct2BaseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . '_' . uniqid('', true);
        if (!mkdir($ct2BaseDir, 0777, true) && !is_dir($ct2BaseDir)) {
            throw new RuntimeException('Unable to create temporary directory: ' . $ct2BaseDir);
        }

        return $ct2BaseDir;
    }

    function ct2SelectPort(int $preferredPort, int $maxAttempts = 25): int
    {
        $ct2Offset = abs((int) getmypid()) % max(1, $maxAttempts);
        return $preferredPort + $ct2Offset;
    }

    function ct2RemoveDir(string $path): void
    {
        if (!is_dir($path)) {
            if (is_file($path)) {
                @unlink($path);
            }
            return;
        }

        $ct2Entries = scandir($path);
        if ($ct2Entries === false) {
            return;
        }

        foreach ($ct2Entries as $ct2Entry) {
            if ($ct2Entry === '.' || $ct2Entry === '..') {
                continue;
            }

            $ct2ChildPath = $path . DIRECTORY_SEPARATOR . $ct2Entry;
            if (is_dir($ct2ChildPath)) {
                ct2RemoveDir($ct2ChildPath);
                continue;
            }

            @unlink($ct2ChildPath);
        }

        @rmdir($path);
    }

    /**
     * @param array<string, string> $env
     * @return array{proc: resource, log_file: string}
     */
    function ct2StartPhpServer(int $port, string $tempDir, string $readinessPath = '?module=auth&action=login', array $env = []): array
    {
        $ct2LogFile = $tempDir . DIRECTORY_SEPARATOR . 'ct2_php_server.log';
        $ct2LogHandle = fopen($ct2LogFile, 'ab');
        if ($ct2LogHandle === false) {
            throw new RuntimeException('Unable to open PHP server log file.');
        }

        $ct2Command = sprintf(
            '%s -S 127.0.0.1:%d -t %s',
            escapeshellarg(PHP_BINARY),
            $port,
            escapeshellarg(ct2AppRoot())
        );

        $ct2Proc = proc_open(
            $ct2Command,
            [
                0 => ['pipe', 'r'],
                1 => $ct2LogHandle,
                2 => $ct2LogHandle,
            ],
            $ct2Pipes,
            ct2RepoRoot(),
            $env === [] ? null : array_merge($_ENV, $env)
        );

        if (!is_resource($ct2Proc)) {
            fclose($ct2LogHandle);
            throw new RuntimeException('Unable to start the local CT2 PHP server.');
        }

        if (isset($ct2Pipes[0]) && is_resource($ct2Pipes[0])) {
            fclose($ct2Pipes[0]);
        }

        $ct2ReadyUrl = sprintf('http://127.0.0.1:%d/ct2_index.php%s', $port, $readinessPath);
        $ct2Deadline = microtime(true) + 30;
        while (microtime(true) < $ct2Deadline) {
            try {
                $ct2Response = ct2RawHttpGet($ct2ReadyUrl);
                if (($ct2Response['status'] ?? 0) >= 200) {
                    return [
                        'proc' => $ct2Proc,
                        'log_file' => $ct2LogFile,
                    ];
                }
            } catch (Throwable) {
            }

            usleep(250000);
        }

        ct2StopProcess($ct2Proc);
        throw new RuntimeException('Unable to start the local CT2 PHP server. See ' . $ct2LogFile . '.');
    }

    /**
     * @return array{proc: resource, log_file: string}
     */
    function ct2StartBackgroundProcess(string $command, string $logFile, array $env = []): array
    {
        $ct2LogHandle = fopen($logFile, 'ab');
        if ($ct2LogHandle === false) {
            throw new RuntimeException('Unable to open process log file: ' . $logFile);
        }

        $ct2Proc = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => $ct2LogHandle,
                2 => $ct2LogHandle,
            ],
            $ct2Pipes,
            ct2RepoRoot(),
            $env === [] ? null : array_merge($_ENV, $env)
        );

        if (!is_resource($ct2Proc)) {
            fclose($ct2LogHandle);
            throw new RuntimeException('Unable to start background process: ' . $command);
        }

        if (isset($ct2Pipes[0]) && is_resource($ct2Pipes[0])) {
            fclose($ct2Pipes[0]);
        }

        return [
            'proc' => $ct2Proc,
            'log_file' => $logFile,
        ];
    }

    function ct2StopProcess($process): void
    {
        if (!is_resource($process)) {
            return;
        }

        $ct2Status = proc_get_status($process);
        if (($ct2Status['running'] ?? false) === true && isset($ct2Status['pid'])) {
            @proc_terminate($process);
            usleep(250000);
        }

        @proc_close($process);
    }

    /**
     * @return array{cookie_file: string}
     */
    function ct2CreateHttpSession(string $tempDir): array
    {
        $ct2CookieFile = $tempDir . DIRECTORY_SEPARATOR . 'ct2_cookie_' . uniqid('', true) . '.txt';
        if (file_put_contents($ct2CookieFile, '') === false) {
            throw new RuntimeException('Unable to initialize cookie jar.');
        }

        return [
            'cookie_file' => $ct2CookieFile,
        ];
    }

    /**
     * @param array{cookie_file: string}|null $session
     * @param array<string, string> $headers
     * @param array<string, scalar|null> $formParams
     * @return array{status:int,body:string,headers_raw:string,headers:array<string,string>,time_total:float}
     */
    function ct2HttpRequest(
        string $method,
        string $url,
        ?array $session = null,
        array $headers = [],
        array $formParams = [],
        ?string $jsonPayload = null,
        bool $followRedirects = true
    ): array {
        $ct2Curl = curl_init($url);
        if ($ct2Curl === false) {
            throw new RuntimeException('Unable to initialize cURL.');
        }

        $ct2HeaderLines = [];
        foreach ($headers as $ct2HeaderName => $ct2HeaderValue) {
            $ct2HeaderLines[] = $ct2HeaderName . ': ' . $ct2HeaderValue;
        }

        $ct2Options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => $followRedirects,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $ct2HeaderLines,
        ];

        if ($session !== null) {
            $ct2Options[CURLOPT_COOKIEFILE] = $session['cookie_file'];
            $ct2Options[CURLOPT_COOKIEJAR] = $session['cookie_file'];
        }

        if ($formParams !== []) {
            $ct2Options[CURLOPT_POSTFIELDS] = http_build_query($formParams);
            $ct2Options[CURLOPT_HTTPHEADER] = array_merge(
                $ct2HeaderLines,
                ['Content-Type: application/x-www-form-urlencoded']
            );
        }

        if ($jsonPayload !== null) {
            $ct2Options[CURLOPT_POSTFIELDS] = $jsonPayload;
            $ct2Options[CURLOPT_HTTPHEADER] = array_merge(
                $ct2HeaderLines,
                ['Content-Type: application/json']
            );
        }

        curl_setopt_array($ct2Curl, $ct2Options);
        $ct2Response = curl_exec($ct2Curl);
        if ($ct2Response === false) {
            $ct2Error = curl_error($ct2Curl);
            curl_close($ct2Curl);
            throw new RuntimeException('HTTP request failed: ' . $ct2Error);
        }

        $ct2HeaderSize = (int) curl_getinfo($ct2Curl, CURLINFO_HEADER_SIZE);
        $ct2Status = (int) curl_getinfo($ct2Curl, CURLINFO_RESPONSE_CODE);
        $ct2TimeTotal = (float) curl_getinfo($ct2Curl, CURLINFO_TOTAL_TIME);
        curl_close($ct2Curl);

        $ct2HeaderChunk = substr($ct2Response, 0, $ct2HeaderSize);
        $ct2Body = (string) substr($ct2Response, $ct2HeaderSize);
        $ct2FinalHeadersRaw = ct2ExtractFinalHeaderBlock((string) $ct2HeaderChunk);

        return [
            'status' => $ct2Status,
            'body' => $ct2Body,
            'headers_raw' => $ct2FinalHeadersRaw,
            'headers' => ct2ParseHeaders($ct2FinalHeadersRaw),
            'time_total' => $ct2TimeTotal,
        ];
    }

    /**
     * @return array{status:int,body:string}
     */
    function ct2RawHttpGet(string $url): array
    {
        $ct2Curl = curl_init($url);
        if ($ct2Curl === false) {
            throw new RuntimeException('Unable to initialize cURL.');
        }

        curl_setopt_array(
            $ct2Curl,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
            ]
        );

        $ct2Body = curl_exec($ct2Curl);
        if ($ct2Body === false) {
            $ct2Error = curl_error($ct2Curl);
            curl_close($ct2Curl);
            throw new RuntimeException('HTTP readiness check failed: ' . $ct2Error);
        }

        $ct2Status = (int) curl_getinfo($ct2Curl, CURLINFO_RESPONSE_CODE);
        curl_close($ct2Curl);

        return [
            'status' => $ct2Status,
            'body' => $ct2Body,
        ];
    }

    function ct2ExtractFinalHeaderBlock(string $headersRaw): string
    {
        $ct2Blocks = preg_split("/\r\n\r\n|\n\n|\r\r/", trim($headersRaw));
        if ($ct2Blocks === false || $ct2Blocks === []) {
            return trim($headersRaw);
        }

        $ct2Blocks = array_values(array_filter($ct2Blocks, static fn ($ct2Block): bool => trim((string) $ct2Block) !== ''));
        return $ct2Blocks === [] ? trim($headersRaw) : trim((string) end($ct2Blocks));
    }

    /**
     * @return array<string, string>
     */
    function ct2ParseHeaders(string $headersRaw): array
    {
        $ct2Headers = [];
        foreach (preg_split('/\R/', trim($headersRaw)) ?: [] as $ct2Line) {
            if (str_contains($ct2Line, ':') === false) {
                continue;
            }

            [$ct2Name, $ct2Value] = explode(':', $ct2Line, 2);
            $ct2Headers[strtolower(trim($ct2Name))] = trim($ct2Value);
        }

        return $ct2Headers;
    }

    function ct2ExtractCsrf(string $html): string
    {
        if (!preg_match('/name="ct2_csrf_token" value="([^"]+)"/', $html, $ct2Matches)) {
            throw new RuntimeException('Unable to extract the CT2 CSRF token.');
        }

        return $ct2Matches[1];
    }

    function ct2AssertStatus(int $expected, array $response, string $message, string $prefix): void
    {
        if (($response['status'] ?? 0) !== $expected) {
            ct2Fail($prefix, sprintf('%s (expected %d, got %d)', $message, $expected, (int) ($response['status'] ?? 0)));
        }
    }

    function ct2AssertContains(string $haystack, string $needle, string $message, string $prefix): void
    {
        if (!str_contains($haystack, $needle)) {
            ct2Fail($prefix, $message);
        }
    }

    function ct2AssertNotContains(string $haystack, string $needle, string $message, string $prefix): void
    {
        if (str_contains($haystack, $needle)) {
            ct2Fail($prefix, $message);
        }
    }

    function ct2AssertEquals(string $expected, string $actual, string $message, string $prefix): void
    {
        if ($expected !== $actual) {
            ct2Fail($prefix, sprintf('%s (expected %s, got %s)', $message, $expected, $actual));
        }
    }

    function ct2AssertNotEquals(string $unexpected, string $actual, string $message, string $prefix): void
    {
        if ($unexpected === $actual) {
            ct2Fail($prefix, sprintf('%s (unexpected %s)', $message, $actual));
        }
    }

    function ct2AssertHeaderContains(array $response, string $headerName, string $needle, string $message, string $prefix): void
    {
        $ct2HeaderValue = $response['headers'][strtolower($headerName)] ?? '';
        if (!str_contains(strtolower($ct2HeaderValue), strtolower($needle))) {
            ct2Fail($prefix, $message);
        }
    }

    function ct2AssertHtmlClean(string $html, string $message, string $prefix): void
    {
        if (preg_match('/CT2 application error|Fatal error|Warning:|Notice:|Deprecated:/i', $html) === 1) {
            ct2Fail($prefix, $message);
        }
    }

    function ct2AssertJsonSuccess(array $response, string $message, string $prefix): array
    {
        ct2AssertHeaderContains($response, 'Content-Type', 'application/json', $message, $prefix);
        if (preg_match('/<html|Fatal error|Warning:|Notice:|Deprecated:/i', $response['body']) === 1) {
            ct2Fail($prefix, $message);
        }

        $ct2Decoded = json_decode($response['body'], true);
        if (!is_array($ct2Decoded) || ($ct2Decoded['success'] ?? null) !== true) {
            ct2Fail($prefix, $message);
        }

        return $ct2Decoded;
    }

    function ct2AssertJsonEnvelope(array $response, bool $expectedSuccess, string $message, string $prefix): array
    {
        ct2AssertHeaderContains($response, 'Content-Type', 'application/json', $message, $prefix);
        if (preg_match('/<html|Fatal error|Warning:|Notice:|Deprecated:/i', $response['body']) === 1) {
            ct2Fail($prefix, $message);
        }

        $ct2Decoded = json_decode($response['body'], true);
        if (!is_array($ct2Decoded) || ($ct2Decoded['success'] ?? null) !== $expectedSuccess) {
            ct2Fail($prefix, $message);
        }

        return $ct2Decoded;
    }

    function ct2AssertSecondsWithinLimit(float $measured, float $limit, string $label, string $prefix): void
    {
        if ($measured > $limit) {
            ct2Fail($prefix, sprintf('%s exceeded %.2fs (measured %.6fs)', $label, $limit, $measured));
        }
    }

    function ct2Probe(string ...$args): string
    {
        $ct2Command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/ct2_regression_probe.php');
        foreach ($args as $ct2Arg) {
            $ct2Command .= ' ' . escapeshellarg($ct2Arg);
        }

        $ct2Output = [];
        $ct2ExitCode = 0;
        exec($ct2Command . ' 2>&1', $ct2Output, $ct2ExitCode);
        if ($ct2ExitCode !== 0) {
            throw new RuntimeException('CT2 regression probe failed: ' . implode(PHP_EOL, $ct2Output));
        }

        return trim(implode(PHP_EOL, $ct2Output));
    }

    function ct2ApiLogCount(string $endpoint, int $statusCode, string $method = 'POST'): int
    {
        return (int) ct2Probe('api-log-count', $endpoint, strtoupper($method), (string) $statusCode);
    }

    function ct2JsonValue(array $data, string $path): string
    {
        $ct2Value = $data;
        foreach (explode('.', $path) as $ct2Segment) {
            if (!is_array($ct2Value) || !array_key_exists($ct2Segment, $ct2Value)) {
                throw new RuntimeException('Missing JSON path: ' . $path);
            }

            $ct2Value = $ct2Value[$ct2Segment];
        }

        if (is_bool($ct2Value)) {
            return $ct2Value ? 'true' : 'false';
        }

        if ($ct2Value === null) {
            return 'null';
        }

        if (is_scalar($ct2Value)) {
            return (string) $ct2Value;
        }

        return (string) json_encode($ct2Value, JSON_UNESCAPED_SLASHES);
    }

    function ct2HttpMultipartRequest(
        string $method,
        string $url,
        ?array $session,
        array $fields,
        array $files,
        bool $followRedirects = true
    ): array {
        $ct2Payload = $fields;
        foreach ($files as $ct2FieldName => $ct2FileSpec) {
            if (!isset($ct2FileSpec['path'])) {
                throw new InvalidArgumentException('Missing file path for multipart request: ' . $ct2FieldName);
            }

            $ct2Payload[$ct2FieldName] = curl_file_create(
                $ct2FileSpec['path'],
                $ct2FileSpec['mime_type'] ?? 'application/octet-stream',
                $ct2FileSpec['file_name'] ?? basename($ct2FileSpec['path'])
            );
        }

        $ct2Curl = curl_init($url);
        if ($ct2Curl === false) {
            throw new RuntimeException('Unable to initialize cURL.');
        }

        $ct2Options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => $followRedirects,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_POSTFIELDS => $ct2Payload,
        ];

        if ($session !== null) {
            $ct2Options[CURLOPT_COOKIEFILE] = $session['cookie_file'];
            $ct2Options[CURLOPT_COOKIEJAR] = $session['cookie_file'];
        }

        curl_setopt_array($ct2Curl, $ct2Options);
        $ct2Response = curl_exec($ct2Curl);
        if ($ct2Response === false) {
            $ct2Error = curl_error($ct2Curl);
            curl_close($ct2Curl);
            throw new RuntimeException('Multipart HTTP request failed: ' . $ct2Error);
        }

        $ct2HeaderSize = (int) curl_getinfo($ct2Curl, CURLINFO_HEADER_SIZE);
        $ct2Status = (int) curl_getinfo($ct2Curl, CURLINFO_RESPONSE_CODE);
        $ct2TimeTotal = (float) curl_getinfo($ct2Curl, CURLINFO_TOTAL_TIME);
        curl_close($ct2Curl);

        $ct2HeaderChunk = substr($ct2Response, 0, $ct2HeaderSize);
        $ct2Body = (string) substr($ct2Response, $ct2HeaderSize);
        $ct2FinalHeadersRaw = ct2ExtractFinalHeaderBlock((string) $ct2HeaderChunk);

        return [
            'status' => $ct2Status,
            'body' => $ct2Body,
            'headers_raw' => $ct2FinalHeadersRaw,
            'headers' => ct2ParseHeaders($ct2FinalHeadersRaw),
            'time_total' => $ct2TimeTotal,
        ];
    }

    function ct2FindCommand(array $candidates): ?string
    {
        foreach ($candidates as $ct2Candidate) {
            $ct2Resolved = ct2ResolveCommand($ct2Candidate);
            if ($ct2Resolved !== null) {
                return $ct2Resolved;
            }
        }

        return null;
    }

    function ct2ResolveCommand(string $command): ?string
    {
        $ct2Command = DIRECTORY_SEPARATOR === '\\'
            ? 'where ' . escapeshellarg($command)
            : 'command -v ' . escapeshellarg($command);

        $ct2Output = [];
        $ct2ExitCode = 0;
        exec($ct2Command . ' 2>&1', $ct2Output, $ct2ExitCode);
        if ($ct2ExitCode !== 0 || $ct2Output === []) {
            return null;
        }

        return trim($ct2Output[0]);
    }

    function ct2SummarizeSamples(string $label, array $samples): string
    {
        if ($samples === []) {
            throw new InvalidArgumentException('No timing samples found for ' . $label);
        }

        sort($samples);
        $ct2Count = count($samples);
        $ct2Average = array_sum($samples) / $ct2Count;

        return sprintf(
            '%s count=%d avg=%.6fs min=%.6fs max=%.6fs',
            $label,
            $ct2Count,
            $ct2Average,
            (float) $samples[0],
            (float) $samples[$ct2Count - 1]
        );
    }

    function ct2BuildUploadFixture(string $targetPath): void
    {
        $ct2PngData = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WnL1hQAAAAASUVORK5CYII=',
            true
        );

        if ($ct2PngData === false || file_put_contents($targetPath, $ct2PngData) === false) {
            throw new RuntimeException('Unable to build the CT2 upload fixture.');
        }
    }

    function ct2SessionCookieValue(array $session, string $cookieName = 'ct2_session'): string
    {
        $ct2CookieFile = $session['cookie_file'] ?? '';
        if ($ct2CookieFile === '' || !is_file($ct2CookieFile)) {
            throw new RuntimeException('Unable to resolve the CT2 cookie jar.');
        }

        $ct2Lines = file($ct2CookieFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($ct2Lines)) {
            throw new RuntimeException('Unable to read the CT2 cookie jar.');
        }

        $ct2CookieValue = null;
        foreach ($ct2Lines as $ct2Line) {
            if ($ct2Line === '') {
                continue;
            }

            if (str_starts_with($ct2Line, '#HttpOnly_')) {
                $ct2Line = substr($ct2Line, 10);
            } elseif (str_starts_with($ct2Line, '#')) {
                continue;
            }

            $ct2Parts = explode("\t", $ct2Line);
            if (count($ct2Parts) < 7) {
                continue;
            }

            if ($ct2Parts[5] === $cookieName) {
                $ct2CookieValue = $ct2Parts[6];
            }
        }

        if (!is_string($ct2CookieValue) || $ct2CookieValue === '') {
            throw new RuntimeException('Unable to resolve cookie value for ' . $cookieName . '.');
        }

        return $ct2CookieValue;
    }
}
