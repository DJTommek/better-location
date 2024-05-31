<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
	$rectorConfig->paths([
//		__DIR__ . '/libs',
		__DIR__ . '/../tests',
		__DIR__ . '/../www',
	]);

	// define sets of rules
	$rectorConfig->sets([
		LevelSetList::UP_TO_PHP_82,
	]);

	$rectorConfig->skip([
		\Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector::class,
	]);
};
