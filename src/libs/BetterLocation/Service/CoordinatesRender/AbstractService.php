<?php declare(strict_types=1);

namespace App\BetterLocation\Service\CoordinatesRender;

use App\BetterLocation\ServicesManager;

/**
 * Only text location renderers
 */
abstract class AbstractService extends \App\BetterLocation\Service\AbstractService
{
	const TAGS = [
		ServicesManager::TAG_GENERATE_TEXT,
		ServicesManager::TAG_GENERATE_TEXT_OFFLINE,
	];
}
