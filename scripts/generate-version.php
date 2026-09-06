<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$versionPrefix = getenv('VERSION_PREFIX') ?: '2.16';
$buildNumber = getenv('GITHUB_RUN_NUMBER') ?: null;
$commitSha = getenv('GITHUB_SHA') ?: null;

if (! preg_match('/^[0-9A-Za-z.-]+$/', $versionPrefix)) {
    fwrite(STDERR, "VERSION_PREFIX may only contain letters, numbers, dots, and hyphens.\n");
    exit(1);
}

if ($buildNumber === null) {
    $output = [];
    $exitCode = 0;
    exec('git rev-list --count HEAD 2>/dev/null', $output, $exitCode);

    if ($exitCode === 0 && isset($output[0]) && ctype_digit($output[0])) {
        $buildNumber = $output[0];
    }
}

if ($commitSha === null) {
    $output = [];
    $exitCode = 0;
    exec('git rev-parse --short=7 HEAD 2>/dev/null', $output, $exitCode);

    if ($exitCode === 0 && isset($output[0])) {
        $commitSha = $output[0];
    }
}

$buildNumber = ctype_digit((string) $buildNumber) ? $buildNumber : 'dev';
$commitSha = preg_match('/^[0-9a-f]{7,40}$/i', (string) $commitSha)
    ? substr((string) $commitSha, 0, 7)
    : 'unknown';

$version = sprintf('%s.%s-%s', $versionPrefix, $buildNumber, $commitSha);
$versionFile = $projectRoot.DIRECTORY_SEPARATOR.'VERSION';

if (file_put_contents($versionFile, $version.PHP_EOL) === false) {
    fwrite(STDERR, "Unable to write the VERSION file.\n");
    exit(1);
}

fwrite(STDOUT, "Generated application version {$version}\n");
