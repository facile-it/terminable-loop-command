<?php

declare(strict_types=1);

$fileToCheck = dirname(__DIR__) . '/src/AbstractTerminableCommandAfterSymfony7_3.php';

if (PHP_VERSION_ID < 8_02_00) {
    unlink($fileToCheck);
}

require_once dirname(__DIR__) . '/vendor/autoload.php';
