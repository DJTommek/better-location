<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use DJTommek\MapyCzApi\MapyCzApi;
use Nette\Http\Url;

final class MapyCzPanoramaGeneratorService extends AbstractService
{
	const ID = 42;
	const NAME = 'Mapy.cz Panorama';
	const NAME_SHORT = 'Panorama';
	const LINK = 'https://mapy.cz';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_ONLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			$api = new MapyCzApi();
			$bestPanorama = $api->loadPanoramaGetBest($lon, $lat);
			if ($bestPanorama) {
				$link = new Url($bestPanorama->getLink());
				$link->setQueryParameter('source', MapyCzApi::SOURCE_COOR);
				$link->setQueryParameter('id', $lon . ',' . $lat);
				return (string)$link;
			}
			return null;
		}
	}
}
