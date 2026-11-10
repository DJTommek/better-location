<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\ServicesManager;
use App\Utils\S2Cell;

/**
 * Google S2 geometry cell token, eg. "470b94e9fd45a86d". Only generating is supported: tokens are plain hexadecimal
 * strings, so detecting them in messages would cause too many false positives (numbers, words, H3 codes...).
 *
 * @link https://s2geometry.io/
 * @link https://s2geometry.io/devguide/s2cell_hierarchy
 */
final class S2CellService extends AbstractService
{
	const int ID = 72;
	const string NAME = 'S2 cell';
	const string NAME_SHORT = 'S2';

	/**
	 * Level 30 is the most precise level (cell area ~1 cm²), token has always 16 characters.
	 */
	const int DEFAULT_LEVEL = S2Cell::MAX_LEVEL;

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_TEXT,
		ServicesManager::TAG_GENERATE_TEXT_OFFLINE,
	];

	#[\Override]
	public static function getShareText(float $lat, float $lon): string
	{
		return S2Cell::fromLatLon($lat, $lon, self::DEFAULT_LEVEL)->getToken();
	}
}
