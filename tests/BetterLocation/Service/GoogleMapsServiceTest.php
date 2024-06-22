<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\GoogleMapsService;
use Tests\HttpTestClients;

final class GoogleMapsServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return GoogleMapsService::class;
	}

	protected function getShareLinks(): array
	{
		return [
			'https://www.google.com/maps/place/50.087451,14.420671?q=50.087451,14.420671',
			'https://www.google.com/maps/place/50.100000,14.500000?q=50.100000,14.500000',
			'https://www.google.com/maps/place/-50.200000,14.600000?q=-50.200000,14.600000',
			'https://www.google.com/maps/place/50.300000,-14.700001?q=50.300000,-14.700001',
			'https://www.google.com/maps/place/-50.400000,-14.800008?q=-50.400000,-14.800008',
		];
	}

	protected function getDriveLinks(): array
	{
		return [
			'https://www.google.com/maps/dir/?api=1&destination=50.087451%2C14.420671&travelmode=driving&dir_action=navigate',
			'https://www.google.com/maps/dir/?api=1&destination=50.100000%2C14.500000&travelmode=driving&dir_action=navigate',
			'https://www.google.com/maps/dir/?api=1&destination=-50.200000%2C14.600000&travelmode=driving&dir_action=navigate',
			'https://www.google.com/maps/dir/?api=1&destination=50.300000%2C-14.700001&travelmode=driving&dir_action=navigate',
			'https://www.google.com/maps/dir/?api=1&destination=-50.400000%2C-14.800008&travelmode=driving&dir_action=navigate',
		];
	}

	public static function isValidBasicProvider(): array
	{
		return [
			[true, 'https://www.google.com/maps/place/Velk%C3%BD+Meheln%C3%ADk,+397+01+Pisek/@49.2941662,14.2258333,14z/data=!4m2!3m1!1s0x470b5087ca84a6e9:0xfeb1428d8c8334da'],
			[true, 'https://www.google.com/maps/place/Zelend%C3%A1rky/@49.2069545,14.2495123,15z/data=!4m5!3m4!1s0x0:0x3ad3965c4ecb9e51!8m2!3d49.2113282!4d14.2553488'],
			[true, 'https://www.google.cz/maps/@36.8264601,22.5287146,9.33z'],
			[true, 'https://www.google.cz/maps/place/49%C2%B020\'00.6%22N+14%C2%B017\'46.2%22E/@49.3339819,14.2956352,18.4z/data=!4m5!3m4!1s0x0:0x0!8m2!3d49.333511!4d14.296174'],
			[true, 'https://www.google.cz/maps/place/Hrad+P%C3%ADsek/@49.3088543,14.1454615,391m/data=!3m1!1e3!4m12!1m6!3m5!1s0x470b4ff494c201db:0x4f78e2a2eaa0955b!2sHrad+P%C3%ADsek!8m2!3d49.3088525!4d14.1465894!3m4!1s0x470b4ff494c201db:0x4f78e2a2eaa0955b!8m2!3d49.3088525!4d14.1465894'],
			[true, 'https://www.google.com/maps/place/50%C2%B006\'04.6%22N+14%C2%B031\'44.0%22E/@50.101271,14.5281082,18z/data=!3m1!4b1!4m6!3m5!1s0x0:0x0!7e2!8m2!3d50.1012711!4d14.5288824?shorturl=1'],
			[true, 'https://maps.google.com/maps?ll=49.367523,14.514022&q=49.367523,14.514022'],
			[true, 'https://maps.google.com/maps?ll=-49.367523,-14.514022&q=-49.367523,-14.514022'],
			[true, 'http://maps.google.com/?q=49.417361,14.652640'], // http link from @ingressportalbot
			[true, 'http://maps.google.com/?q=-49.417361,-14.652640'], // http link from @ingressportalbot
			[true, 'https://maps.google.com/?q=49.417361,14.652640'], // same as above, just https
			[true, 'https://maps.google.com/?q=-49.417361,-14.652640'], // same as above, just https
			[true, 'http://maps.google.com/?daddr=50.052098,14.451968'], // http drive link from @ingressportalbot
			[true, 'http://maps.google.com/?daddr=-50.052098,-14.451968'], // http drive link from @ingressportalbot
			[true, 'https://maps.google.com/?daddr=50.052098,14.451968'], // same as above, just https
			[true, 'https://maps.google.com/?daddr=-50.052098,-14.451968'], // same as above, just https
			[true, 'https://www.google.cz/maps/place/50.02261,14.525433'],
			[true, 'https://www.google.cz/maps/place/-50.02261,-14.525433'],
			[true, 'https://www.google.com/maps/place/50.0821019,14.4494197'],
			[true, 'https://www.google.com/maps/place/50.0821019,14.4494197'],
			[true, 'https://google.com/maps/place/50.0821019,14.4494197'],
			[true, 'https://www.google.cz/maps/place/50.02261,14.525433'],
			[true, 'https://google.cz/maps/place/50.02261,14.525433'],
			[true, 'https://google.com/maps?q=49.417361,14.652640'],
			[true, 'https://maps.google.com/?q=49.417361,14.652640'],
			[true, 'https://maps.google.cz/?q=49.417361,14.652640'],
			[true, 'https://www.maps.google.cz/?q=49.417361,14.652640'],
		];
	}

	public static function isValidShortProvider(): array
	{
		return [
			[true, 'https://goo.gl/maps/rgZZt125tpvf2rnCA'],
			[true, 'http://goo.gl/maps/rgZZt125tpvf2rnCA'],
			[true, 'https://goo.gl/maps/eUYMwABdpv9NNSDX7'],
			[true, 'https://GoO.GL/maps/hEbUKxSuMjA2'],
			[true, 'https://goo.gL/maps/pPZ91TfW2edvejbb6'],
			[true, 'https://maps.app.goo.gl/W5wPRJ5FMJxgaisf9'],
			[true, 'http://maps.app.goo.gl/W5wPRJ5FMJxgaisf9'],
			[true, 'https://maps.app.goo.gl/nJqTbFow1HtofApTA'],
			[false, 'https://mapsapp.goo.gl/nJqTbFow1HtofApTA'],
			[false, 'https://maps.app.goo.gl.com/nJqTbFow1HtofApTA'],
			[false, 'https://maps.app.googl/nJqTbFow1HtofApTA'],
			[false, 'https://maps.appgoo.gl/nJqTbFow1HtofApTA'],
		];
	}

	public static function isValidStreetViewProvider(): array
	{
		return [
			[true, 'https://www.google.com/maps/@50.0873231,14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'],
			[true, 'https://www.google.com/maps/@50.0873231,-14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'],
			[true, 'https://www.google.com/maps/@-50.0873231,14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'],
			[true, 'https://www.google.com/maps/@-50.0873231,-14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'],
			// valid but not street view, missing "3a" parameter
			[true, 'https://www.google.com/maps/@50.0873231,14.4208835,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[[[49.294166, 14.225833, GoogleMapsService::TYPE_MAP]], 'https://www.google.com/maps/place/Velk%C3%BD+Meheln%C3%ADk,+397+01+Pisek/@49.2941662,14.2258333,14z/data=!4m2!3m1!1s0x470b5087ca84a6e9:0xfeb1428d8c8334da'],
			[[[49.211328, 14.255349, GoogleMapsService::TYPE_PLACE]], 'https://www.google.com/maps/place/Zelend%C3%A1rky/@49.2069545,14.2495123,15z/data=!4m5!3m4!1s0x0:0x3ad3965c4ecb9e51!8m2!3d49.2113282!4d14.2553488'],
			[[[36.826460, 22.528715, GoogleMapsService::TYPE_MAP]], 'https://www.google.cz/maps/@36.8264601,22.5287146,9.33z'],
			[[[49.333511, 14.296174, GoogleMapsService::TYPE_PLACE]], 'https://www.google.cz/maps/place/49%C2%B020\'00.6%22N+14%C2%B017\'46.2%22E/@49.3339819,14.2956352,18.4z/data=!4m5!3m4!1s0x0:0x0!8m2!3d49.333511!4d14.296174'],
			[[[49.308853, 14.146589, GoogleMapsService::TYPE_PLACE]], 'https://www.google.cz/maps/place/Hrad+P%C3%ADsek/@49.3088543,14.1454615,391m/data=!3m1!1e3!4m12!1m6!3m5!1s0x470b4ff494c201db:0x4f78e2a2eaa0955b!2sHrad+P%C3%ADsek!8m2!3d49.3088525!4d14.1465894!3m4!1s0x470b4ff494c201db:0x4f78e2a2eaa0955b!8m2!3d49.3088525!4d14.1465894'],
			[[[50.101271, 14.528882, GoogleMapsService::TYPE_PLACE]], 'https://www.google.com/maps/place/50%C2%B006\'04.6%22N+14%C2%B031\'44.0%22E/@50.101271,14.5281082,18z/data=!3m1!4b1!4m6!3m5!1s0x0:0x0!7e2!8m2!3d50.1012711!4d14.5288824?shorturl=1'],
		];
	}

	public static function processCoordsInUrlProvider(): array
	{
		return [
			[[[36.826460, 22.528715, 'Map center']], 'https://www.google.cz/maps/@36.8264601,22.5287146,9.33z'],
			[[[49.417361, 14.652640, 'search']], 'http://maps.google.com/?q=49.417361,14.652640'], // http link from @ingressportalbot
			[[[-49.417361, -14.652640, 'search']], 'http://maps.google.com/?q=-49.417361,-14.652640'], // http link from @ingressportalbot
			[[[50.052098, 14.451968, 'drive']], 'http://maps.google.com/?daddr=50.052098,14.451968'], // http drive link from @ingressportalbot
			[[[50.0821019, 14.4494197, 'Place']], 'https://www.google.com/maps/place/50.0821019,14.4494197'],
			[[[50.0821019, 14.4494197, 'Place']], 'https://google.com/maps/place/50.0821019,14.4494197'],
			[[[50.02261, 14.525433, 'Place']], 'https://www.google.cz/maps/place/50.02261,14.525433'],
			[[[50.02261, 14.525433, 'Place']], 'https://google.cz/maps/place/50.02261,14.525433'],
			[[[49.417361, 14.652640, 'search']], 'https://google.com/maps?q=49.417361,14.652640'],
			[[[49.417361, 14.652640, 'search']], 'https://maps.google.com/?q=49.417361,14.652640'],
			[[[49.417361, 14.652640, 'search']], 'https://maps.google.cz/?q=49.417361,14.652640'],
			[[[49.417361, 14.652640, 'search']], 'https://www.maps.google.cz/?q=49.417361,14.652640'],
		];
	}

	public static function processStreetViewUrlProvider(): array
	{
		// Gathering coordinates from street view is not precise - it assumes that "@lat,lon" is location of currently loaded street view
		// It means, that opening links below, with different lat or lon, still opens location like in first URL in this test.

		return [
			[[[50.0873231, 14.4208835, GoogleMapsService::TYPE_STREET_VIEW]], 'https://www.google.com/maps/@50.0873231,14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'],
			[[[5.0873231, -14.4208835, GoogleMapsService::TYPE_STREET_VIEW]], 'https://www.google.com/maps/@5.0873231,-14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'],
			[[[-50.0873231, 144.4208835, GoogleMapsService::TYPE_STREET_VIEW]], 'https://www.google.com/maps/@-50.0873231,144.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'],
			[[[-50.0873231, -14.4208835, GoogleMapsService::TYPE_STREET_VIEW]], 'https://www.google.com/maps/@-50.0873231,-14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'],
			// Valid location, but not street view - missing "3a" parameter so it fallback to general Map center
			[[[50.0873231, 14.4208835, GoogleMapsService::TYPE_MAP]], 'https://www.google.com/maps/@50.0873231,14.4208835,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'],
		];
	}

	public static function processShortUrlProvider(): array
	{
		return [
			[[[49.982825, 14.571417, GoogleMapsService::TYPE_PLACE]], 'https://goo.gl/maps/rgZZt125tpvf2rnCA'],
			[[[49.982825, 14.571417, GoogleMapsService::TYPE_PLACE]], 'http://gOo.gl/maps/rgZZt125tpvf2rnCA'],
			[[[49.3066028, 14.1467086, GoogleMapsService::TYPE_STREET_VIEW]], 'https://goo.gl/maps/eUYMwABdpv9NNSDX7'],
			[[[49.3062328, 14.1466707, GoogleMapsService::TYPE_STREET_VIEW]], 'https://goo.GL/maps/hEbUKxSuMjA2'],
			[[[49.2702263, 14.0462163, GoogleMapsService::TYPE_PLACE]], 'https://goo.gl/maps/pPZ91TfW2edvejbb6'],
			[[[49.296449499999994, 14.4803612, GoogleMapsService::TYPE_HIDDEN]], 'https://maps.app.goo.gl/W5wPRJ5FMJxgaisf9'],
			[[[49.296449499999994, 14.4803612, GoogleMapsService::TYPE_HIDDEN]], 'http://maps.app.goo.gl/W5wPRJ5FMJxgaisf9'],
			[[[49.2677196, 14.0031687, GoogleMapsService::TYPE_HIDDEN]], 'https://maps.app.goo.gl/nJqTbFow1HtofApTA'],
		];
	}

	/**
	 * Links generated in browser on Google app by clicking on "share" button
	 * Opened URL before opening place: https://www.google.com/maps/@50.0543547,14.4763896,16.75z
	 * Opened URL after opening place: https://www.google.com/maps/place/bauMax/@50.0543547,14.4763896,16.75z/data=!4m5!3m4!1s0x470b93a27e4781c5:0xeca4ac5483aa4dd2!8m2!3d50.0560684!4d14.4729532
	 */
	public static function processShareNormalUrlPCBrowser(): array
	{
		return [
			__FUNCTION__ . ' Baumax Michle (normal)' => [[[50.056068, 14.472953, GoogleMapsService::TYPE_PLACE]], 'https://www.google.com/maps/place/bauMax/@50.0543547,14.4763896,16.75z/data=!4m5!3m4!1s0x470b93a27e4781c5:0xeca4ac5483aa4dd2!8m2!3d50.0560684!4d14.4729532?shorturl=1'],
		];
	}

	/**
	 * Links generated in browser on Google app by clicking on "share" button
	 * Opened URL before opening place: https://www.google.com/maps/@50.0543547,14.4763896,16.75z
	 * Opened URL after opening place: https://www.google.com/maps/place/bauMax/@50.0543547,14.4763896,16.75z/data=!4m5!3m4!1s0x470b93a27e4781c5:0xeca4ac5483aa4dd2!8m2!3d50.0560684!4d14.4729532
	 */
	public static function processShareShortUrlPCBrowser(): array
	{
		return [
			__FUNCTION__ . ' Baumax Michle (short)' => [[[50.056068, 14.472953, GoogleMapsService::TYPE_PLACE]], 'https://goo.gl/maps/AK13hVJLjnveWZqJA'],
		];
	}

	/**
	 * Links generated on phone in Google maps app by clicking on "share" button while opened place
	 */
	public static function processShareNormalUrlPhoneProvider(): array
	{
		$datasets = [
			__FUNCTION__ . ' Baumax' => [
				[[50.056156, 14.472952, GoogleMapsService::TYPE_PLACE, '<a href="https://www.baumax.cz/">bauMax</a>']],
				'https://www.google.com/maps/place/bauMax,+Chodovsk%C3%A1+1549%2F18,+101+00+Praha+10/data=!4m2!3m1!1s0x470b93a27e4781c5:0xeca4ac5483aa4dd2?utm_source=mstt_1&entry=gps',
			],
			__FUNCTION__ . ' Lemour Sušice' => [
				[[49.231830, 13.521600, GoogleMapsService::TYPE_PLACE, '<a href="https://www.facebook.com/pages/Café-LAmour/632972443431373?fref=ts">Café L\'Amour</a>']],
				'https://www.google.com/maps/place/Caf%C3%A9+Lamour,+n%C3%A1b%C5%99.+Karla+Houry+180,+342+01+Su%C5%A1ice/data=!4m2!3m1!1s0x470b2b2fad7dd1c3:0x6c66c5beca8a4117?utm_source=mstt_1&entry=gps',
			],
			__FUNCTION__ . ' Dacia Průhonice' => [
				[[50.002966, 14.569240, GoogleMapsService::TYPE_PLACE, '<a href="https://www.daciapruhonice.cz/">Dacia Průhonice - Pyramida Průhonice</a>']],
				'https://www.google.com/maps/place/Dacia+Pr%C5%AFhonice+-+Pyramida+Pr%C5%AFhonice,+u+Prahy,+U+Pyramidy+721,+252+43+Pr%C5%AFhonice/data=!4m2!3m1!1s0x470b8f7265f22517:0xd2786b5c9cd599cd?utm_source=mstt_1&entry=gps&g_ep=CAESCTExLjYzLjcwNBgAIIgnKgBCAkNa',
			],
		];

		// Append input URL into prefix
		foreach ($datasets as &$dataset) {
			$expectedResults = &$dataset[0];
			$inputUrl = $dataset[1];
			foreach ($expectedResults as &$expectedResult) {
				$expectedResult[3] = sprintf('<a href="%s">Google</a>: %s', $inputUrl, $expectedResult[3]);
			}
		}
		return $datasets;
	}

	/**
	 * Links generated on phone in Google maps app by clicking on "share" button while opened place
	 */
	public static function processShareShortUrlPhoneProvider(): array
	{
		$datasets = [
			__FUNCTION__ . ' Baumax' => [ // same as previous but short URL
				[[50.056156, 14.472952, GoogleMapsService::TYPE_PLACE, '<a href="https://www.baumax.cz/">bauMax</a>']],
				'https://maps.app.goo.gl/X5bZDTSFfdRzchGY6',
			],
			__FUNCTION__ . ' Lemour Sušice' => [ // same as previous but short URL
				[[49.231830, 13.521600, GoogleMapsService::TYPE_PLACE, '<a href="https://www.facebook.com/pages/Café-LAmour/632972443431373?fref=ts">Café L\'Amour</a>']],
				'https://maps.app.goo.gl/C4FjaU9CXsHuMrobA',
			],
			__FUNCTION__ . ' Dacia Průhonice' => [
				[[50.002966, 14.569240, GoogleMapsService::TYPE_PLACE, '<a href="https://www.daciapruhonice.cz/">Dacia Průhonice - Pyramida Průhonice</a>']],
				'https://maps.app.goo.gl/NM78pUenb1hVA3nX8',
			],
			__FUNCTION__ . ' Mount Victoria Lookout' => [
				[[-41.296057, 174.794310, GoogleMapsService::TYPE_PLACE, '<a href="http://www.wellingtonnz.com/discover/things-to-do/sights-activities/mount-victoria-lookout/">Mount Victoria Lookout</a>']],
				'https://maps.app.goo.gl/PRwZr2cHQLfqxbNw9',
			],
		];

		// Append input URL into prefix
		foreach ($datasets as &$dataset) {
			$expectedResults = &$dataset[0];
			$inputUrl = $dataset[1];
			foreach ($expectedResults as &$expectedResult) {
				$expectedResult[3] = sprintf('<a href="%s">Google</a>: %s', $inputUrl, $expectedResult[3]);
			}
		}
		return $datasets;
	}

	/**
	 * @dataProvider isValidBasicProvider
	 * @dataProvider isValidShortProvider
	 * @dataProvider isValidStreetViewProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new GoogleMapsService($this->httpTestClients->mockedRequestor);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processShortUrlProvider
	 * @dataProvider processShareShortUrlPCBrowser
	 * @dataProvider processShareNormalUrlPhoneProvider
	 * @dataProvider processShareShortUrlPhoneProvider
	 */
	public function testProcessReal(array $expectedResults, string $input): void
	{
		$service = new GoogleMapsService($this->httpTestClients->realRequestor);
		$this->testProcess($service, $expectedResults, $input);
	}

	/**
	 * @dataProvider processProvider
	 * @dataProvider processCoordsInUrlProvider
	 * @dataProvider processStreetViewUrlProvider
	 * @dataProvider processShareNormalUrlPCBrowser
	 */
	public function testProcessWithoutRequest(array $expectedResults, string $input): void
	{
		$service = new GoogleMapsService($this->httpTestClients->mockedRequestor);
		$this->testProcess($service, $expectedResults, $input);
	}

	/**
	 * @dataProvider processShortUrlProvider
	 * @dataProvider processShareNormalUrlPCBrowser
	 * @dataProvider processShareShortUrlPCBrowser
	 * @dataProvider processShareNormalUrlPhoneProvider
	 * @dataProvider processShareShortUrlPhoneProvider
	 */
	public function testProcessOffline(array $expectedResults, string $input): void
	{
		$service = new GoogleMapsService($this->httpTestClients->offlineRequestor);
		$this->testProcess($service, $expectedResults, $input);
	}

	private function testProcess(GoogleMapsService $service, array $expectedResults, string $input): void
	{
		$service->setInput($input);
		$this->assertTrue($service->validate());
		$service->process();

		$collection = $service->getCollection();
		$this->assertCount(count($expectedResults), $collection);

		foreach ($expectedResults as $key => $expectedResult) {
			[$expectedLat, $expectedLon, $expectedSourceType, $expectedPrefix] = $expectedResult;
			$location = $collection[$key];
			$this->assertCoordsWithDelta($expectedLat, $expectedLon, $location);
			$this->assertSame($expectedSourceType, $location->getSourceType());
		}
	}
}
