<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\ServicesManager;
use Nette\Http\Url;

/**
 * Deep links are documented at https://documenter.getpostman.com/view/7396339/TWDTNeds
 */
final class ABetterRoutePlannerService extends AbstractService
{
	const int ID = 66;
	const string NAME = 'A Better Routeplanner';
	const string NAME_SHORT = 'ABRP';

	const string LINK = 'https://abetterrouteplanner.com';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
		ServicesManager::TAG_GENERATE_LINK_DRIVE,
	];

	#[\Override]
	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		$url = new Url(self::LINK . '/');
		// Drive link is disabled, share link is used instead. Plan requires at least two destinations, so it must
		// start from user's current location ("My position"), but when opened via link, planning fails with
		// "We could not calculate your plan" until user manually clicks on "My position".
		// if ($drive) {
		// 	$url->setQueryParameter('destinations', \Nette\Utils\Json::encode([
		// 		[
		// 			'is_my_pos' => true,
		// 		],
		// 		[
		// 			'address' => sprintf('%F,%F', $lat, $lon),
		// 			'lat' => round($lat, 6),
		// 			'lon' => round($lon, 6),
		// 		],
		// 	]));
		// 	return (string)$url;
		// }
		$url->setQueryParameter('lat', sprintf('%F', $lat));
		$url->setQueryParameter('lon', sprintf('%F', $lon));
		return (string)$url;
	}
}
