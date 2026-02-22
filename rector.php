<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/www',
    ])
//    ->withPhpSets()
    ->withRules([
        ReadOnlyPropertyRector::class,
        ExplicitNullableParamTypeRector::class,
    ])
    ->withTypeCoverageLevel(0);
