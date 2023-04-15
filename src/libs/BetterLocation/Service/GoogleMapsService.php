<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\Coordinates;
use App\Utils\Strict;

/**
 * Google has nice documentations related to generating link to their apps.
 * @link https://developers.google.com/maps/documentation/urls/get-started
 * @link https://gearside.com/easily-link-to-locations-and-directions-using-the-new-google-maps/
 */
final class GoogleMapsService extends AbstractService
{
	const ID = 2;
	const NAME = 'Google';

	const LINK = 'https://www.google.com/maps/place/%1$F,%2$F?q=%1$F,%2$F';
	const LINK_DRIVE = 'https://www.google.com/maps/dir/?api=1&destination=%1$F%%2C%2$F&travelmode=driving&dir_action=navigate';

	const TYPE_UNKNOWN = 'unknown';
	const TYPE_MAP = 'Map center';
	const TYPE_PLACE = 'Place';
	const TYPE_STREET_VIEW = 'Street view';
	const TYPE_SEARCH = 'search';
	const TYPE_INLINE_SEARCH = 'inline search';
	const TYPE_HIDDEN = 'hidden';
	const TYPE_DRIVE = 'drive';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
		ServicesManager::TAG_GENERATE_LINK_DRIVE,
	];

	public static function getConstants(): array
	{
		return [
			self::TYPE_INLINE_SEARCH,
			self::TYPE_STREET_VIEW,
			self::TYPE_PLACE,
			self::TYPE_HIDDEN,
			self::TYPE_SEARCH,
			self::TYPE_DRIVE,
			self::TYPE_UNKNOWN,
			self::TYPE_MAP,
		];
	}

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		return sprintf($drive ? self::LINK_DRIVE : self::LINK, $lat, $lon);
	}

	public function isValid(): bool
	{
		if ($this->url === null) {
			return false;
		}

		return $this->isShortUrl() || $this->isNormalUrl();
	}

	public function isShortUrl(): bool
	{

		if (
			($this->url->getDomain(0) === 'goo.gl' && str_starts_with($this->url->getPath(), '/maps/'))
			|| ($this->url->getDomain(0) === 'maps.app.goo.gl')
		) {
			$this->data->isShort = true;
			return true;
		}
		return false;
	}

	public function isNormalUrl(): bool
	{
		if ($this->url === null) {
			return false;
		}

		if (str_starts_with($this->url->getDomain(3), 'maps.google.')) {
			return true;
		}

		// maps.google.com
		// www.maps.google.com
		// maps.google.cz
		// www.maps.google.cz
		if (str_starts_with($this->url->getDomain(2), 'google.') && str_starts_with($this->url->getPath(), '/maps')) {
			return true;
		}

		return false;
	}

	public static function getScreenshotLink(float $lat, float $lon, array $options = []): ?string
	{
		if (is_null(Config::GOOGLE_MAPS_STATIC_API_KEY)) {
			throw new NotSupportedException('Google Maps Static API key is not defined.');
		}
		$params = [
			'center' => '',
			'zoom' => '13',
			'size' => '600x600',
			'maptype' => 'roadmap',
			'markers' => sprintf('color:red|label:|%1$s,%2$s', $lat, $lon),
			'key' => Config::GOOGLE_MAPS_STATIC_API_KEY,
		];
		return 'https://maps.googleapis.com/maps/api/staticmap?' . http_build_query($params);
	}

	public function process(): void
	{
		if ($this->data->isShort ?? false) {
			$urlToRequest = $this->url->setScheme('https'); // Optimalization by skipping one extra redirecting from http to https
			$this->url = Strict::url(MiniCurl::loadRedirectUrl($urlToRequest->getAbsoluteUrl()));
			if ($this->isValid() === false) {
				throw new InvalidLocationException(sprintf('Invalid redirect for short Google maps link "%s".', $this->inputUrl));
			}
		}

		if ($this->isNormalUrl()) {
			$this->processUrl();
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords for normal Google maps link "%s".', $this->url));
		}
	}

	private function processUrl(): void
	{
		// https://www.google.com/maps/place/50%C2%B006'04.6%22N+14%C2%B031'44.0%22E/@50.101271,14.5281082,18z/data=!3m1!4b1!4m6!3m5!1s0x0:0x0!7e2!8m2!3d50.1012711!4d14.5288824?shorturl=1
		// Regex is matching "!3d50.1012711!4d14.5288824"
		if (preg_match_all('/!3d(-?[0-9]{1,3}\.[0-9]+)!4d(-?[0-9]{1,3}\.[0-9]+)/', $this->url->getPath(), $matches)) {
			/**
			 * There might be more than just one parameter to match, example:
			 * https://www.google.com/maps/place/49%C2%B050'19.5%22N+18%C2%B023'29.9%22E/@49.8387187,18.3912988,88m/data=!3m1!1e3!4m14!1m7!3m6!1s0x4713fdb643f28f71:0xcbeec5757ed37704!2zT2Rib3LFrywgNzM1IDQxIFBldMWZdmFsZA!3b1!8m2!3d49.8386455!4d18.39618!3m5!1s0x0:0x0!7e2!8m2!3d49.8387596!4d18.3916417
			 * In this case correct is the last one. If used "share button", it will generate this link https://goo.gl/maps/aTQGPSpepT2EDCrT8 which leads to:
			 * https://www.google.com/maps/place/49%C2%B050'19.5%22N+18%C2%B023'29.9%22E/@49.8387187,18.3912988,88m/data=!3m1!1e3!4m6!3m5!1s0x0:0x0!7e2!8m2!3d49.8387596!4d18.3916417?shorturl=1
			 * In this URL is only one parameter to match. Strange...
			 */
			if (Coordinates::isLat(end($matches[1])) && Coordinates::isLon(end($matches[2]))) {
				$this->collection->add(new BetterLocation($this->inputUrl, Strict::floatval(end($matches[1])), Strict::floatval(end($matches[2])), self::class, self::TYPE_PLACE));
			}
		}

		// https://www.google.cz/maps/place/50.02261,14.525433
		if (preg_match('/\/maps\/place\/(-?[0-9.]+),(-?[0-9.]+)/', urldecode($this->url->getPath()), $matches)) {
			if (Coordinates::isLat($matches[1]) && Coordinates::isLon($matches[2])) {
				$this->collection->add(new BetterLocation($this->inputUrl, Strict::floatval($matches[1]), Strict::floatval($matches[2]), self::class, self::TYPE_PLACE));
			}
		}

		if ($this->url->getQueryParameter('ll')) {
			$coords = explode(',', $this->url->getQueryParameter('ll'));
			if (count($coords) === 2 && Coordinates::isLat($coords[0]) && Coordinates::isLon($coords[1])) {
				$this->collection->add(new BetterLocation($this->inputUrl, Strict::floatval($coords[0]), Strict::floatval($coords[1]), self::class, self::TYPE_UNKNOWN));
			}
		}

		if ($this->url->getQueryParameter('daddr')) {
			$coords = explode(',', $this->url->getQueryParameter('daddr'));
			if (count($coords) === 2 && Coordinates::isLat($coords[0]) && Coordinates::isLon($coords[1])) {
				$this->collection->add(new BetterLocation($this->inputUrl, Strict::floatval($coords[0]), Strict::floatval($coords[1]), self::class, self::TYPE_DRIVE));
			}
		}

		if ($this->url->getQueryParameter('q')) {
			$coords = explode(',', $this->url->getQueryParameter('q'));
			if (count($coords) === 2 && Coordinates::isLat($coords[0]) && Coordinates::isLon($coords[1])) {
				$this->collection->add(new BetterLocation($this->inputUrl, Strict::floatval($coords[0]), Strict::floatval($coords[1]), self::class, self::TYPE_SEARCH));
				// Warning: coordinates in URL in format "@50.00,15.00" is position of the map, not selected/shared point.
			}
		}

		// https://www.google.com/maps/@50.0873231,14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656
		//                              \__lat___/ \__lon___/ \/ \_/ \_____/ \_/
		//		                         \___coordinates___/   \_street_view__/
		if (preg_match('/@(-?[0-9.]+),(-?[0-9.]+)/', $this->url->getPath(), $matches)) {
			if (
				Coordinates::isLat($matches[1]) &&
				Coordinates::isLon($matches[2]) &&
				preg_match('/,[0-9.]+a/', $this->url->getPath()) &&
				preg_match('/,[0-9.]+y/', $this->url->getPath()) &&
				preg_match('/,[0-9.]+h/', $this->url->getPath()) &&
				preg_match('/,[0-9.]+t/', $this->url->getPath())
			) {
				$type = self::TYPE_STREET_VIEW;
			} else {
				$type = self::TYPE_MAP;
			}
			$this->collection->add(new BetterLocation($this->inputUrl, Strict::floatval($matches[1]), Strict::floatval($matches[2]), self::class, $type));
		}

		// To prevent doing unnecessary request, this is done only if there is no other location detected
		// This might happen if clicked on "Share" button from phone app Google maps:
		// https://maps.app.goo.gl/X5bZDTSFfdRzchGY6
		// -> https://www.google.com/maps/place/bauMax,+Chodovsk%C3%A1+1549%2F18,+101+00+Praha+10/data=!4m2!3m1!1s0x470b93a27e4781c5:0xeca4ac5483aa4dd2?utm_source=mstt_1&entry=gps
		//                                                                                                      \__@TODO this might be some place ID__/
		if ($this->collection->count() === 0) {
			// URL don't have any coordinates or place-id to translate so load content and there are some coordinates hidden in page in some of brutal multi-array
			$content = (new MiniCurl($this->url->getAbsoluteUrl()))->allowCache(Config::CACHE_TTL_GOOGLE_MAPS)->run()->getBody();
			$coords = null;
			if (preg_match('/",null,\[null,null,(-?[0-9]{1,3}\.[0-9]+),(-?[0-9]{1,3}\.[0-9]+)]/', $content, $matches)) {
				// Example: ',"",null,[null,null,50.0641584,14.468139599999999]';
				$coords = Coordinates::safe($matches[1], $matches[2]);
			} else if (preg_match('/window\.APP_INITIALIZATION_STATE=\[\[\[[0-9.+]+,([-0-9.+]+),([-0-9.+]+)],\[/', $content, $matches)) {
				// example: '...wvRvJsOMw"]];window.APP_INITIALIZATION_STATE=[[[2564.4475005591294,14.569239800000005,50.002965700000004],[0,0,0],[1024,768],13.1],[[["m...'
				$coords = Coordinates::safe($matches[2], $matches[1]);
			}

			if ($coords) {
				$location = new BetterLocation($this->inputUrl, $coords->getLat(), $coords->getLon(), self::class, self::TYPE_HIDDEN);
				$this->collection->add($location);
			}
		}
	}
}
