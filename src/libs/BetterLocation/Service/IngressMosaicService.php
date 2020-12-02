<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Config;
use App\Factory;
use App\Icons;
use App\MiniCurl\MiniCurl;
use App\Utils\General;

final class IngressMosaicService extends AbstractService
{
	const NAME = 'IngressMosaic';
	const LINK = 'https://ingressmosaic.com';
	const RE_PATH = '/^\/mosaic\/[0-9]+$/';

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not supported.');
		}
	}

	public static function isValid(string $url): bool
	{
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['host']) &&
			in_array(mb_strtolower($parsedUrl['host']), ['ingressmosaic.com', 'www.ingressmosaic.com', 'ingressmosaik.com', 'www.ingressmosaik.com'], true) &&
			isset($parsedUrl['path']) &&
			preg_match(self::RE_PATH, $parsedUrl['path'])
		);
	}

	public static function parseUrl(string $url): ?BetterLocation
	{
		if ($mosaic = self::loadMosaicPage($url)) {
			list($lat, $lon) = $mosaic[3]->latLng;
			$betterLocation = new BetterLocation($url, $lat, $lon, self::class);
			$ingressApi = Factory::IngressLanchedRu();
			$description = 'Some random info about mosaic...';
			if ($portal = $ingressApi->getPortalByCoords($lat, $lon)) {
				$betterLocation->setAddress($portal->address);
				$description .= sprintf('<br>Start portal: <a href="%s">%s</a> <a href="%s">%s</a>', $portal->getIntelLink(), htmlspecialchars($portal->name), $portal->image, Icons::PICTURE);
			}
			$betterLocation->setDescription($description);

			return $betterLocation;
		} else {
			return null;
		}
	}

	public static function parseCoords(string $url): BetterLocation
	{
		throw new NotImplementedException('Parsing coordinate is not supported.');
	}

	public static function parseCoordsMultiple(string $url): BetterLocationCollection
	{
		throw new NotImplementedException('Parsing multiple coordinate is not supported.');
	}

	private static function loadMosaicPage($url)
	{
		$response = (new MiniCurl($url))
			->allowCache(Config::CACHE_TTL_INGRESS_MOSAIC)
			->setHttpCookie('XSRF-TOKEN', Config::INGRESS_MOSAIC_COOKIE_XSRF)
			->setHttpCookie('ingressmosaik_session', Config::INGRESS_MOSAIC_COOKIE_SESSION)
			->setHttpCookie('lang', 'en')
			->run()
			->getBody();
		return self::getMosaicInfoFromResponse($response);
	}

	private static function getMosaicInfoFromResponse(string $response): ?array {
		if (preg_match('/ {8}var lang_txt_M = (\[(?:.+) {8}]);/s', $response, $matches)) {
			$langTxtM = str_replace('\'', '"', $matches[1]);
			return json_decode($langTxtM, false, 512, JSON_THROW_ON_ERROR);
		} else {
			return null;
		}
	}
}
