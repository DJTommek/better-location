<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/www',
    ])
//    ->withPhpSets()
    ->withRules([
        \Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class,
    ])
    ->withTypeCoverageLevel(0);
