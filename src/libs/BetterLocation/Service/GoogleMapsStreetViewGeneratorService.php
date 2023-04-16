<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\Factory;
use Nette\Http\Url;

final class GoogleMapsStreetViewGeneratorService extends AbstractService
{
	const ID = 43;
	const NAME = 'Google Maps Street view';
	const NAME_SHORT = 'Street view';
	const LINK = 'https://www.google.com/maps';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_ONLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		}

		if (!Config::isGoogleStreetViewStaticApi()) {
			return null;
		}

		$api = Factory::googleStreetViewApi();
		$panoramaMetadata = $api->loadPanoaramaMetadataByCoords($lat, $lon);
		if ($panoramaMetadata === null) {
			return null;
		}

		// URL generator based on https://developers.google.com/maps/documentation/urls/get-started#street-view-action
		$url = new Url('https://www.google.com/maps/@');
		$url->setQueryParameter('api', 1);
		$url->setQueryParameter('map_action', 'pano');
		$url->setQueryParameter('pano', $panoramaMetadata->pano_id);
		$url->setQueryParameter('viewpoint', $lat . ',' . $lon);
		return (string)$url;
	}
}
