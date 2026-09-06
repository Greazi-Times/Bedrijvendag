<?php

$versionFile = base_path('VERSION');

return [
    'display' => env(
        'APP_VERSION',
        is_file($versionFile) ? trim((string) file_get_contents($versionFile)) : 'development'
    ),
];
