<?php

declare(strict_types=1);

/**
 * Load application configuration from an INI-style environment file
 * stored outside the public web root.
 */

$environmentFile = '/var/www/paintchip.env';

if (!is_readable($environmentFile)) {
    throw new RuntimeException(
        'The Paint Chip environment file is missing or unreadable.'
    );
}

$environment = parse_ini_file(
    $environmentFile,
    false,
    INI_SCANNER_RAW
);

if ($environment === false) {
    throw new RuntimeException(
        'The Paint Chip environment file could not be parsed.'
    );
}

$requiredVariables = [
    'DB_HOST',
    'DB_NAME',
    'DB_USER',
    'DB_PASSWORD',
];

foreach ($requiredVariables as $variable) {
    if (
        !array_key_exists($variable, $environment) ||
        trim((string) $environment[$variable]) === ''
    ) {
        throw new RuntimeException(
            sprintf('Required environment variable "%s" is missing.', $variable)
        );
    }
}

foreach ($environment as $name => $value) {
    if (!is_string($name) || !is_scalar($value)) {
        continue;
    }

    $stringValue = (string) $value;

    $_ENV[$name] = $stringValue;
    $_SERVER[$name] = $stringValue;
    putenv($name . '=' . $stringValue);
}
