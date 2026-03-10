<?php

declare(strict_types=1);

$ct2RootDir = realpath(__DIR__ . '/../..');

if ($ct2RootDir === false) {
    fwrite(STDERR, "Unable to resolve CT2 repository root.\n");
    exit(1);
}

$ct2AllowedExtensions = [
    'php',
    'css',
    'md',
    'sql',
    'sh',
    'ps1',
];

$ct2Violations = [];

$ct2Iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $ct2RootDir,
        FilesystemIterator::SKIP_DOTS
    )
);

/** @var SplFileInfo $ct2File */
foreach ($ct2Iterator as $ct2File) {
    if (!$ct2File->isFile()) {
        continue;
    }

    $ct2Path = $ct2File->getPathname();

    if (str_contains($ct2Path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR)) {
        continue;
    }

    $ct2Extension = strtolower(pathinfo($ct2Path, PATHINFO_EXTENSION));

    if (!in_array($ct2Extension, $ct2AllowedExtensions, true)) {
        continue;
    }

    $ct2Contents = file_get_contents($ct2Path);
    if ($ct2Contents === false) {
        $ct2Violations[] = $ct2Path . ': unreadable file';
        continue;
    }

    if (str_starts_with($ct2Contents, "\xEF\xBB\xBF")) {
        $ct2Violations[] = $ct2Path . ': UTF-8 BOM is not allowed';
    }

    if ($ct2Contents !== '' && !preg_match('/\R\z/', $ct2Contents)) {
        $ct2Violations[] = $ct2Path . ': missing trailing newline at EOF';
    }

    $ct2Lines = preg_split('/\R/', $ct2Contents);
    if ($ct2Lines === false) {
        $ct2Violations[] = $ct2Path . ': unable to inspect file lines';
        continue;
    }

    foreach ($ct2Lines as $ct2Index => $ct2Line) {
        if (preg_match('/[ \t]+$/', $ct2Line) === 1) {
            $ct2Violations[] = sprintf('%s:%d: trailing whitespace', $ct2Path, $ct2Index + 1);
        }
    }
}

if ($ct2Violations !== []) {
    fwrite(STDERR, "CT2 format check failed:\n" . implode("\n", $ct2Violations) . "\n");
    exit(1);
}

echo "CT2 format check passed.\n";
