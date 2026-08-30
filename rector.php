<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    ->withImportNames(importShortClasses: false)
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true
    )
    ->withAttributesSets(all: true)
    ->withSets([
        \Rector\PHPUnit\Set\PHPUnitSetList::COMPOSER_BASED,
    ])
;
