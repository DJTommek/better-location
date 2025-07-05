<?php declare(strict_types=1);

namespace Tests\TelegramCustomWrapper;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\MessageGeneratorInterface;
use App\BetterLocation\Service\BetterLocationService;
use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use App\BetterLocation\Service\MapyCzService;
use App\BetterLocation\Service\OpenLocationCodeService;
use App\BetterLocation\Service\WazeService;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\Factory;
use App\Google\Geocoding\StaticApi;
use App\IngressLanchedRu\Client;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHtmlMessageGenerator;
use PHPUnit\Framework\TestCase;
use Tests\HttpTestClients;

final class ProcessedMessageResultTest extends TestCase
{
	private readonly HttpTestClients $httpTestClients;
	private readonly MessageGeneratorInterface $messageGenerator;

	private static ?StaticApi $googleGeocodeApi = null;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		if (Config::isGooglePlaceApi()) {
			self::$googleGeocodeApi = Factory::googleGeocodingApi();
		}

	}


	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
		$this->messageGenerator = new TelegramHtmlMessageGenerator(new ServicesManager());
	}

	public static function defaultNoAddressProvider(): array
	{
		return [
			__FUNCTION__ . ' - Default settings with one item' => [
				'<a href="">WGS84</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D49.000000%26x%3D14.000000%26source%3Dcoor%26id%3D14.000000%252C49.000000%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>49.000000,14.000000</code>
<a href="https://better-location.palider.cz/49.000000,14.000000" target="_blank">BetterLocation</a> | <a href="https://www.google.com/maps/place/49.000000,14.000000?q=49.000000,14.000000" target="_blank">Google</a> | <a href="https://mapy.cz/zakladni?y=49.000000&x=14.000000&source=coor&id=14.000000%2C49.000000" target="_blank">Mapy.com</a> | <a href="https://duckduckgo.com/?q=49.000000,14.000000&iaxm=maps" target="_blank">DDG</a> | <a href="https://www.waze.com/ul?ll=49.000000,14.000000" target="_blank">Waze</a> | <a href="https://share.here.com/l/49.000000,14.000000?p=yes" target="_blank">HERE</a> | <a href="https://www.openstreetmap.org/search?whereami=1&query=49.000000,14.000000&mlat=49.000000&mlon=14.000000#map=17/49.000000/14.000000" target="_blank">OSM</a>

',
				[
					[
						[
							'text' => 'Google 🚗',
							'url' => 'https://www.google.com/maps/dir/?api=1&destination=49.000000%2C14.000000&travelmode=driving&dir_action=navigate',
						],
						[
							'text' => 'Waze 🚗',
							'url' => 'https://www.waze.com/ul?ll=49.000000,14.000000&navigate=yes',
						],
						[
							'text' => 'HERE 🚗',
							'url' => 'https://share.here.com/r/49.000000,14.000000',
						],
						[
							'text' => 'OsmAnd 🚗',
							'url' => 'https://osmand.net/go.html?lat=49.000000&lon=14.000000',
						],
					],
				],
				(new BetterLocationCollection())->add(new BetterLocation('abcd', 49, 14, WGS84DegreesService::class)),
				new BetterLocationMessageSettings(address: false),
			],

			__FUNCTION__ . ' - Default settings with multiple items' => [
				'2 locations: <a href="https://better-location.palider.cz/50.087451,14.420671;36.826460,22.528715" target="_blank">BetterLocation</a> | <a href="https://mapy.cz/zakladni?vlastni-body&uc=9hAK0xXxOKu02Lcw61El" target="_blank">Mapy.com</a>

<a href="https://www.waze.com/ul?ll=50.087451123456789%2C14.420671123456789">Waze</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D50.087451%26x%3D14.420671%26source%3Dcoor%26id%3D14.420671%252C50.087451%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>50.087451,14.420671</code>
<a href="https://better-location.palider.cz/50.087451,14.420671" target="_blank">BetterLocation</a> | <a href="https://www.google.com/maps/place/50.087451,14.420671?q=50.087451,14.420671" target="_blank">Google</a> | <a href="https://mapy.cz/zakladni?y=50.087451&x=14.420671&source=coor&id=14.420671%2C50.087451" target="_blank">Mapy.com</a> | <a href="https://duckduckgo.com/?q=50.087451,14.420671&iaxm=maps" target="_blank">DDG</a> | <a href="https://www.waze.com/ul?ll=50.087451,14.420671" target="_blank">Waze</a> | <a href="https://share.here.com/l/50.087451,14.420671?p=yes" target="_blank">HERE</a> | <a href="https://www.openstreetmap.org/search?whereami=1&query=50.087451,14.420671&mlat=50.087451&mlon=14.420671#map=17/50.087451/14.420671" target="_blank">OSM</a>

<a href="https://www.google.cz/maps/@36.8264601,22.5287146,9.33z">Waze</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D36.826460%26x%3D22.528715%26source%3Dcoor%26id%3D22.528715%252C36.826460%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>36.826460,22.528715</code>
<a href="https://better-location.palider.cz/36.826460,22.528715" target="_blank">BetterLocation</a> | <a href="https://www.google.com/maps/place/36.826460,22.528715?q=36.826460,22.528715" target="_blank">Google</a> | <a href="https://mapy.cz/zakladni?y=36.826460&x=22.528715&source=coor&id=22.528715%2C36.826460" target="_blank">Mapy.com</a> | <a href="https://duckduckgo.com/?q=36.826460,22.528715&iaxm=maps" target="_blank">DDG</a> | <a href="https://www.waze.com/ul?ll=36.826460,22.528715" target="_blank">Waze</a> | <a href="https://share.here.com/l/36.826460,22.528715?p=yes" target="_blank">HERE</a> | <a href="https://www.openstreetmap.org/search?whereami=1&query=36.826460,22.528715&mlat=36.826460&mlon=22.528715#map=17/36.826460/22.528715" target="_blank">OSM</a>

',
				[
					[
						[
							'text' => 'Google 🚗',
							'url' => 'https://www.google.com/maps/dir/?api=1&destination=50.087451%2C14.420671&travelmode=driving&dir_action=navigate',
						],
						[
							'text' => 'Waze 🚗',
							'url' => 'https://www.waze.com/ul?ll=50.087451,14.420671&navigate=yes',
						],
						[
							'text' => 'HERE 🚗',
							'url' => 'https://share.here.com/r/50.087451,14.420671',
						],
						[
							'text' => 'OsmAnd 🚗',
							'url' => 'https://osmand.net/go.html?lat=50.087451&lon=14.420671',
						],
					],
				],
				(new BetterLocationCollection())
					->add(new BetterLocation('https://www.waze.com/ul?ll=50.087451123456789,14.420671123456789', 50.087451123456789, 14.420671123456789, WazeService::class))
					->add(new BetterLocation('https://www.google.cz/maps/@36.8264601,22.5287146,9.33z', 36.826460, 22.528715, WazeService::class)),
				new BetterLocationMessageSettings(address: false),
			],

			__FUNCTION__ . ' - Default settings with multiple items but max location count limits result only to first location' => [
				'2 locations: <a href="https://better-location.palider.cz/50.087451,14.420671;36.826460,22.528715" target="_blank">BetterLocation</a> | <a href="https://mapy.cz/zakladni?vlastni-body&uc=9hAK0xXxOKu02Lcw61El" target="_blank">Mapy.com</a>

<a href="https://www.waze.com/ul?ll=50.087451123456789%2C14.420671123456789">Waze</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D50.087451%26x%3D14.420671%26source%3Dcoor%26id%3D14.420671%252C50.087451%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>50.087451,14.420671</code>
<a href="https://better-location.palider.cz/50.087451,14.420671" target="_blank">BetterLocation</a> | <a href="https://www.google.com/maps/place/50.087451,14.420671?q=50.087451,14.420671" target="_blank">Google</a> | <a href="https://mapy.cz/zakladni?y=50.087451&x=14.420671&source=coor&id=14.420671%2C50.087451" target="_blank">Mapy.com</a> | <a href="https://duckduckgo.com/?q=50.087451,14.420671&iaxm=maps" target="_blank">DDG</a> | <a href="https://www.waze.com/ul?ll=50.087451,14.420671" target="_blank">Waze</a> | <a href="https://share.here.com/l/50.087451,14.420671?p=yes" target="_blank">HERE</a> | <a href="https://www.openstreetmap.org/search?whereami=1&query=50.087451,14.420671&mlat=50.087451&mlon=14.420671#map=17/50.087451/14.420671" target="_blank">OSM</a>

Showing only first 1 of 2 detected locations. All at once can be opened with links on top of the message.',
				[
					[
						[
							'text' => 'Google 🚗',
							'url' => 'https://www.google.com/maps/dir/?api=1&destination=50.087451%2C14.420671&travelmode=driving&dir_action=navigate',
						],
						[
							'text' => 'Waze 🚗',
							'url' => 'https://www.waze.com/ul?ll=50.087451,14.420671&navigate=yes',
						],
						[
							'text' => 'HERE 🚗',
							'url' => 'https://share.here.com/r/50.087451,14.420671',
						],
						[
							'text' => 'OsmAnd 🚗',
							'url' => 'https://osmand.net/go.html?lat=50.087451&lon=14.420671',
						],
					],
				],
				(new BetterLocationCollection())
					->add(new BetterLocation('https://www.waze.com/ul?ll=50.087451123456789,14.420671123456789', 50.087451123456789, 14.420671123456789, WazeService::class))
					->add(new BetterLocation('https://www.google.cz/maps/@36.8264601,22.5287146,9.33z', 36.826460, 22.528715, WazeService::class)),
				new BetterLocationMessageSettings(address: false),
				1,
			],

			__FUNCTION__ . ' - Default settings with multiple items but maximum text length forcing only first location' => [
				'2 locations: <a href="https://better-location.palider.cz/50.087451,14.420671;36.826460,22.528715" target="_blank">BetterLocation</a> | <a href="https://mapy.cz/zakladni?vlastni-body&uc=9hAK0xXxOKu02Lcw61El" target="_blank">Mapy.com</a>

<a href="https://www.waze.com/ul?ll=50.087451123456789%2C14.420671123456789">Waze</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D50.087451%26x%3D14.420671%26source%3Dcoor%26id%3D14.420671%252C50.087451%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>50.087451,14.420671</code>
<a href="https://better-location.palider.cz/50.087451,14.420671" target="_blank">BetterLocation</a> | <a href="https://www.google.com/maps/place/50.087451,14.420671?q=50.087451,14.420671" target="_blank">Google</a> | <a href="https://mapy.cz/zakladni?y=50.087451&x=14.420671&source=coor&id=14.420671%2C50.087451" target="_blank">Mapy.com</a> | <a href="https://duckduckgo.com/?q=50.087451,14.420671&iaxm=maps" target="_blank">DDG</a> | <a href="https://www.waze.com/ul?ll=50.087451,14.420671" target="_blank">Waze</a> | <a href="https://share.here.com/l/50.087451,14.420671?p=yes" target="_blank">HERE</a> | <a href="https://www.openstreetmap.org/search?whereami=1&query=50.087451,14.420671&mlat=50.087451&mlon=14.420671#map=17/50.087451/14.420671" target="_blank">OSM</a>

Showing only first 1 of 2 detected locations. All at once can be opened with links on top of the message.',
				[
					[
						[
							'text' => 'Google 🚗',
							'url' => 'https://www.google.com/maps/dir/?api=1&destination=50.087451%2C14.420671&travelmode=driving&dir_action=navigate',
						],
						[
							'text' => 'Waze 🚗',
							'url' => 'https://www.waze.com/ul?ll=50.087451,14.420671&navigate=yes',
						],
						[
							'text' => 'HERE 🚗',
							'url' => 'https://share.here.com/r/50.087451,14.420671',
						],
						[
							'text' => 'OsmAnd 🚗',
							'url' => 'https://osmand.net/go.html?lat=50.087451&lon=14.420671',
						],
					],
				],
				(new BetterLocationCollection())
					->add(new BetterLocation('https://www.waze.com/ul?ll=50.087451123456789,14.420671123456789', 50.087451123456789, 14.420671123456789, WazeService::class))
					->add(new BetterLocation('https://www.google.cz/maps/@36.8264601,22.5287146,9.33z', 36.826460, 22.528715, WazeService::class)),
				new BetterLocationMessageSettings(address: false),
				null,
				100,
			],

			__FUNCTION__ . ' - Empty collection' => [
				'',
				[],
				new BetterLocationCollection(),
				new BetterLocationMessageSettings(address: false),
			],
		];
	}

	public static function minimalNoAddressProvider(): array
	{
		return [

			__FUNCTION__ . ' - One item, one button' => [
				'<a href="">WGS84</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D49.000000%26x%3D14.000000%26source%3Dcoor%26id%3D14.000000%252C49.000000%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>8FXP2222+22XX</code>
<a href="https://better-location.palider.cz/49.000000,14.000000" target="_blank">BetterLocation</a>

',
				[
					[
						[
							'text' => 'Mapy.com 🚗',
							'url' => 'https://mapy.cz/zakladni?y=49.000000&x=14.000000&source=coor&id=14.000000%2C49.000000',
						],
					],
				],
				(new BetterLocationCollection())->add(new BetterLocation('abcd', 49, 14, WGS84DegreesService::class)),
				new BetterLocationMessageSettings(
					shareServices: [BetterLocationService::class],
					buttonServices: [MapyCzService::class],
					textServices: [OpenLocationCodeService::class],
					address: false,
				),
			],

			__FUNCTION__ . ' - One item, no buttons' => [
				'<a href="">WGS84</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D49.000000%26x%3D14.000000%26source%3Dcoor%26id%3D14.000000%252C49.000000%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>49.000000,14.000000</code>
<a href="https://better-location.palider.cz/49.000000,14.000000" target="_blank">BetterLocation</a>

',
				[
					[],
				],
				(new BetterLocationCollection())->add(new BetterLocation('abcd', 49, 14, WGS84DegreesService::class)),
				new BetterLocationMessageSettings(shareServices: [BetterLocationService::class], buttonServices: [], address: false),
			],
		];
	}

	public static function minimalWithAddressProvider(): array
	{
		return [
			__FUNCTION__ . ' - One item, one button' => [
				'<a href="">WGS84</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D50.087451%26x%3D14.420671%26source%3Dcoor%26id%3D14.420671%252C50.087451%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>9F2P3CPC+X7M3</code>
<a href="https://better-location.palider.cz/50.087451,14.420671" target="_blank">BetterLocation</a>
🇨🇿 Mikulášská 22, 110 00 Praha 1-Staré Město, Czechia

',
				[
					[
						[
							'text' => 'Mapy.com 🚗',
							'url' => 'https://mapy.cz/zakladni?y=50.087451&x=14.420671&source=coor&id=14.420671%2C50.087451',
						],
					],
				],
				(new BetterLocationCollection())->add(new BetterLocation('abcd', 50.087451, 14.420671, WGS84DegreesService::class)),
				new BetterLocationMessageSettings(
					shareServices: [BetterLocationService::class],
					buttonServices: [MapyCzService::class],
					textServices: [OpenLocationCodeService::class],
					address: true,
				),
			],

			__FUNCTION__ . ' - Multiple items, no buttons' => [
				'2 locations: <a href="https://better-location.palider.cz/49.000000,14.000000;-53.163196,-70.892391" target="_blank">BetterLocation</a> | <a href="https://mapy.cz/zakladni?vlastni-body&uc=9fqfrxSnSnqsSWop6ctn" target="_blank">Mapy.com</a>

<a href="https://mapy.cz/turisticka?source=coor&id=16.60807216711808%2C49.19523769907402&x=16.6078214&y=49.1951089&z=19">Mapy.com Place coords</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D49.000000%26x%3D14.000000%26source%3Dcoor%26id%3D14.000000%252C49.000000%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>49.000000,14.000000</code>
<a href="https://better-location.palider.cz/49.000000,14.000000" target="_blank">BetterLocation</a>
🇨🇿 Lázně 1129, 383 01 Prachatice-Prachatice II, Czechia

<a href="">WGS84</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D-53.163196%26x%3D-70.892391%26source%3Dcoor%26id%3D-70.892391%252C-53.163196%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>-53.163196,-70.892391</code>
<a href="https://better-location.palider.cz/-53.163196,-70.892391" target="_blank">BetterLocation</a>
🇨🇱 Mejicana 1480, 6213229 Punta Arenas, Magallanes y la Antártica Chilena, Chile

',
				[
					[],
				],
				(new BetterLocationCollection())
					->add(new BetterLocation('https://mapy.cz/turisticka?source=coor&id=16.60807216711808%2C49.19523769907402&x=16.6078214&y=49.1951089&z=19', 49, 14, MapyCzService::class, MapyCzService::TYPE_PLACE_COORDS))
					->add(new BetterLocation('abcd', -53.1631958, -70.8923906, WGS84DegreesService::class)),
				new BetterLocationMessageSettings(shareServices: [BetterLocationService::class], buttonServices: []),
			],
		];
	}

	public static function oneLocationNoAddressProvider(): array
	{
		$collection = (new BetterLocationCollection())
			->add(new BetterLocation('First location', 49, 14, WGS84DegreesService::class))
			->add(new BetterLocation('Second location', 50, 13, WGS84DegreesService::class))
			->add(new BetterLocation('Third', -51, -13, WGS84DegreesService::class));
		$minimalSettings = new BetterLocationMessageSettings(
			shareServices: [BetterLocationService::class],
			buttonServices: [MapyCzService::class],
			textServices: [OpenLocationCodeService::class],
			address: false,
		);


		return [

			__FUNCTION__ . ' - One item, one button' => [
				'<a href="">WGS84</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D49.000000%26x%3D14.000000%26source%3Dcoor%26id%3D14.000000%252C49.000000%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>8FXP2222+22XX</code>
<a href="https://better-location.palider.cz/49.000000,14.000000" target="_blank">BetterLocation</a>

',
				[
					[
						[
							'text' => 'Mapy.com 🚗',
							'url' => 'https://mapy.cz/zakladni?y=49.000000&x=14.000000&source=coor&id=14.000000%2C49.000000',
						],
					],
				],
				(new BetterLocationCollection())->add(new BetterLocation('abcd', 49, 14, WGS84DegreesService::class)),
				$minimalSettings,
				0,
			],

			__FUNCTION__ . ' - One item, no buttons' => [
				'<a href="">WGS84</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D49.000000%26x%3D14.000000%26source%3Dcoor%26id%3D14.000000%252C49.000000%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>49.000000,14.000000</code>
<a href="https://better-location.palider.cz/49.000000,14.000000" target="_blank">BetterLocation</a>

',
				[
					[],
				],
				(new BetterLocationCollection())->add(new BetterLocation('abcd', 49, 14, WGS84DegreesService::class)),
				new BetterLocationMessageSettings(shareServices: [BetterLocationService::class], buttonServices: [], address: false),
				0,
			],

			__FUNCTION__ . ' - Multiple locations (first location)' => [
				'<a href="">WGS84</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D49.000000%26x%3D14.000000%26source%3Dcoor%26id%3D14.000000%252C49.000000%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>8FXP2222+22XX</code>
<a href="https://better-location.palider.cz/49.000000,14.000000" target="_blank">BetterLocation</a>

',
				[
					[
						[
							'text' => 'Mapy.com 🚗',
							'url' => 'https://mapy.cz/zakladni?y=49.000000&x=14.000000&source=coor&id=14.000000%2C49.000000',
						],
					],
				],
				$collection,
				$minimalSettings,
				0,
			],

			__FUNCTION__ . ' - Multiple locations (second location)' => [
				'<a href="">WGS84</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D50.000000%26x%3D13.000000%26source%3Dcoor%26id%3D13.000000%252C50.000000%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>9F2M2222+22XX</code>
<a href="https://better-location.palider.cz/50.000000,13.000000" target="_blank">BetterLocation</a>

',
				[
					[
						[
							'text' => 'Mapy.com 🚗',
							'url' => 'https://mapy.cz/zakladni?y=50.000000&x=13.000000&source=coor&id=13.000000%2C50.000000',
						],
					],
				],
				$collection,
				$minimalSettings,
				1,
			],

			__FUNCTION__ . ' - Multiple locations (third location)' => [
				'<a href="">WGS84</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D-51.000000%26x%3D-13.000000%26source%3Dcoor%26id%3D-13.000000%252C-51.000000%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>3CX92222+22XX</code>
<a href="https://better-location.palider.cz/-51.000000,-13.000000" target="_blank">BetterLocation</a>

',
				[
					[
						[
							'text' => 'Mapy.com 🚗',
							'url' => 'https://mapy.cz/zakladni?y=-51.000000&x=-13.000000&source=coor&id=-13.000000%2C-51.000000',
						],
					],
				],
				$collection,
				$minimalSettings,
				2,
			],
		];
	}

	public static function tryLoadIngressPortalProvider(): array
	{
		$collection = (new BetterLocationCollection())
			->add(new BetterLocation('Some portal', 50.087805, 14.42116, WGS84DegreesService::class))
			->add(new BetterLocation('Another portal', 8.437575, 98.235749, WGS84DegreesService::class))
			->add(new BetterLocation('No portal', -51, -13, WGS84DegreesService::class));
		$minimalSettings = new BetterLocationMessageSettings(
			shareServices: [BetterLocationService::class],
			address: false,
			tryLoadIngressPortal: true,
		);


		return [
			__FUNCTION__ . ' - Multiple locations (first location, portal yes)' => [
				'<a href="">WGS84</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D50.087805%26x%3D14.421160%26source%3Dcoor%26id%3D14.421160%252C50.087805%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>50.087805,14.421160</code>
<a href="https://better-location.palider.cz/50.087805,14.421160" target="_blank">BetterLocation</a>
Ingress portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F3f45fb115df8449686cf6826073ec1f0.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087805%2C14.421160">Jan Hus Monument 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087805,14.421160">🖥</a> <a href="https://lh3.googleusercontent.com/FZXlrGIcPc1tKr5KeudSrAO7NQBZxGv4hJzLZuhR3ysx2YvfEwjLA485u8V2p3Ecg-47y1yjKneEyXUi1qyAl7T9v50=s10000">🖼</a>

',
				$collection,
				$minimalSettings,
				0,
			],
			__FUNCTION__ . ' - Multiple locations (second location, portal yes)' => [
				'<a href="">WGS84</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D8.437575%26x%3D98.235749%26source%3Dcoor%26id%3D98.235749%252C8.437575%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>8.437575,98.235749</code>
<a href="https://better-location.palider.cz/8.437575,98.235749" target="_blank">BetterLocation</a>
Ingress portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F9dfcf50cad0e39638e8c6a0eca10fdae.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D8.437575%2C98.235749">อุทยานแห่งชาติเขาลำปี-หาดท้ายเหมือง 📱</a> <a href="https://intel.ingress.com/intel?pll=8.437575,98.235749">🖥</a> <a href="https://lh3.googleusercontent.com/maROY6EbR4HWX5CVfw4q6ZAZyxXMNt0iFsBdob_ZWE5l7f09_FjHmzjzfBRLgJDpfxNiBqOSdQ2bzmlB-_jzQKav9fC45JAb152vKmQ=s10000">🖼</a>

',
				$collection,
				$minimalSettings,
				1,
			],
			__FUNCTION__ . ' - Multiple locations (third location, portal no)' => [
				'<a href="">WGS84</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D-51.000000%26x%3D-13.000000%26source%3Dcoor%26id%3D-13.000000%252C-51.000000%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>-51.000000,-13.000000</code>
<a href="https://better-location.palider.cz/-51.000000,-13.000000" target="_blank">BetterLocation</a>

',
				$collection,
				$minimalSettings,
				2,
			],
		];
	}

	/**
	 * @dataProvider defaultNoAddressProvider
	 * @dataProvider minimalNoAddressProvider
	 */
	public function testBasic(
		string $expectedText,
		array $expectedButtons,
		BetterLocationCollection $collection,
		BetterLocationMessageSettings $settings,
		?int $maxLocationsCount = null,
		?int $maxTextLength = null,
	): void {
		$processedCollection = new ProcessedMessageResult(
			collection: $collection,
			messageSettings: $settings,
			messageGenerator: $this->messageGenerator,
		);
		$maxLocationsCount ??= Config::TELEGRAM_MAXIMUM_LOCATIONS;
		$maxTextLength ??= Config::TELEGRAM_BETTER_LOCATION_MESSAGE_LIMIT;
		$processedCollection->process();

		$realText = $processedCollection->getText(
			includeStaticMapUrl: false,
			maxTextLength: $maxTextLength,
			maxLocationsCount: $maxLocationsCount,
		);
		$this->assertResult(
			$expectedText,
			$expectedButtons,
			$realText,
			$processedCollection->getButtons(),
		);
	}

	/**
	 * @group request
	 * @dataProvider minimalWithAddressProvider
	 */
	public function testBasicWithAddress(
		string $expectedText,
		array $expectedButtons,
		BetterLocationCollection $collection,
		BetterLocationMessageSettings $settings,
		?int $maxLocationsCount = null,
		?int $maxTextLength = null,
	): void {
		if (self::$googleGeocodeApi === null) {
			self::markTestSkipped('Missing Google API key');
		}

		$processedCollection = new ProcessedMessageResult(
			collection: $collection,
			messageSettings: $settings,
			messageGenerator: $this->messageGenerator,
			addressProvider: self::$googleGeocodeApi,
		);
		$maxLocationsCount ??= Config::TELEGRAM_MAXIMUM_LOCATIONS;
		$maxTextLength ??= Config::TELEGRAM_BETTER_LOCATION_MESSAGE_LIMIT;
		$processedCollection->process();

		$realText = $processedCollection->getText(
			includeStaticMapUrl: false,
			maxTextLength: $maxTextLength,
			maxLocationsCount: $maxLocationsCount,
		);
		$this->assertResult(
			$expectedText,
			$expectedButtons,
			$realText,
			$processedCollection->getButtons(),
		);
	}

	/**
	 * @dataProvider oneLocationNoAddressProvider
	 */
	public function testSelectOneLocation(
		string $expectedText,
		array $expectedButtons,
		BetterLocationCollection $collection,
		BetterLocationMessageSettings $settings,
		int $locationIndex,
	): void {
		$processedCollection = new ProcessedMessageResult(
			collection: $collection,
			messageSettings: $settings,
			messageGenerator: $this->messageGenerator,
		);
		$processedCollection->process();

		$this->assertResult(
			$expectedText,
			$expectedButtons,
			$processedCollection->getOneLocationText($locationIndex, false),
			$processedCollection->getOneLocationButtonRow($locationIndex),
		);
	}

	/**
	 * @dataProvider tryLoadIngressPortalProvider
	 * @group request
	 */
	public function testTryIngressReal(
		string $expectedText,
		BetterLocationCollection $collection,
		BetterLocationMessageSettings $settings,
		int $locationIndex,
	): void {
		$lanchedRuClient = new Client($this->httpTestClients->realRequestor);
		$this->testTryIngress($expectedText, $collection, $settings, $locationIndex, $lanchedRuClient);
	}

	/**
	 * @dataProvider tryLoadIngressPortalProvider
	 * @group request
	 */
	public function testTryIngressOffline(
		string $expectedText,
		BetterLocationCollection $collection,
		BetterLocationMessageSettings $settings,
		int $locationIndex,
	): void {
		$lanchedRuClient = new Client($this->httpTestClients->offlineRequestor);
		$this->testTryIngress($expectedText, $collection, $settings, $locationIndex, $lanchedRuClient);
	}

	private function testTryIngress(
		string $expectedText,
		BetterLocationCollection $collection,
		BetterLocationMessageSettings $settings,
		int $locationIndex,
		Client $lanchedRuClient,
	): void {
		$processedCollection = new ProcessedMessageResult(
			collection: $collection,
			messageSettings: $settings,
			messageGenerator: $this->messageGenerator,
			lanchedRuClient: $lanchedRuClient,
		);
		$processedCollection->process();

		$this->assertResult(
			$expectedText,
			[],
			$processedCollection->getOneLocationText($locationIndex, false),
			[],
		);
	}

	private function assertResult(string $expectedText, array $expectedButtons, string $realText, array $realButtons): void
	{
		$realText = preg_replace("/\R/u", PHP_EOL, $realText);

		$this->assertSame($expectedText, $realText);
		$this->assertButtons($expectedButtons, $realButtons);
	}

	private function assertButtons(array $expectedButtons, array $realButtons): void
	{
		$this->assertCount(count($expectedButtons), $realButtons);
		foreach ($expectedButtons as $rowKey => $expectedButtonRow) {
			$realButtonRow = $realButtons[$rowKey];
			$this->assertCount(count($expectedButtonRow), $realButtonRow);
			foreach ($expectedButtonRow as $colKey => $expectedButton) {
				$realButton = $realButtonRow[$colKey];
				assert($realButton instanceof \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button);
				$this->assertSame($expectedButton['text'], $realButton->text);
				$this->assertSame($expectedButton['url'], $realButton->url);
			}
		}
	}
}
