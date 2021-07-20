<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\BetterLocation\Service\GoogleMapsService;

final class GoogleMapsServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://www.google.cz/maps/place/50.087451,14.420671?q=50.087451,14.420671', GoogleMapsService::getLink(50.087451, 14.420671));
		$this->assertSame('https://www.google.cz/maps/place/50.100000,14.500000?q=50.100000,14.500000', GoogleMapsService::getLink(50.1, 14.5));
		$this->assertSame('https://www.google.cz/maps/place/-50.200000,14.600000?q=-50.200000,14.600000', GoogleMapsService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://www.google.cz/maps/place/50.300000,-14.700001?q=50.300000,-14.700001', GoogleMapsService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://www.google.cz/maps/place/-50.400000,-14.800008?q=-50.400000,-14.800008', GoogleMapsService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->assertSame('https://maps.google.cz/?daddr=50.087451,14.420671&travelmode=driving', GoogleMapsService::getLink(50.087451, 14.420671, true));
		$this->assertSame('https://maps.google.cz/?daddr=50.100000,14.500000&travelmode=driving', GoogleMapsService::getLink(50.1, 14.5, true));
		$this->assertSame('https://maps.google.cz/?daddr=-50.200000,14.600000&travelmode=driving', GoogleMapsService::getLink(-50.2, 14.6000001, true)); // round down
		$this->assertSame('https://maps.google.cz/?daddr=50.300000,-14.700001&travelmode=driving', GoogleMapsService::getLink(50.3, -14.7000009, true)); // round up
		$this->assertSame('https://maps.google.cz/?daddr=-50.400000,-14.800008&travelmode=driving', GoogleMapsService::getLink(-50.4, -14.800008, true));
	}

	public function testIsValidShortUrl(): void {
		$this->assertTrue(GoogleMapsService::isValidStatic('https://goo.gl/maps/rgZZt125tpvf2rnCA'));
		$this->assertTrue(GoogleMapsService::isValidStatic('http://goo.gl/maps/rgZZt125tpvf2rnCA'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://goo.gl/maps/eUYMwABdpv9NNSDX7'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://GoO.GL/maps/hEbUKxSuMjA2'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://goo.gL/maps/pPZ91TfW2edvejbb6'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://maps.app.goo.gl/W5wPRJ5FMJxgaisf9'));
		$this->assertTrue(GoogleMapsService::isValidStatic('http://maps.app.goo.gl/W5wPRJ5FMJxgaisf9'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://maps.app.goo.gl/nJqTbFow1HtofApTA'));

		$this->assertFalse(GoogleMapsService::isValidStatic('https://mapsapp.goo.gl/nJqTbFow1HtofApTA'));
		$this->assertFalse(GoogleMapsService::isValidStatic('https://maps.app.goo.gl.com/nJqTbFow1HtofApTA'));
		$this->assertFalse(GoogleMapsService::isValidStatic('https://maps.app.googl/nJqTbFow1HtofApTA'));
		$this->assertFalse(GoogleMapsService::isValidStatic('https://maps.appgoo.gl/nJqTbFow1HtofApTA'));
	}

	public function testShortUrl(): void {
		$this->markTestSkipped('Disabled due to possibly too many requests to Google servers (recaptcha appearing...)');

		$this->assertSame('49.982825,14.571417', GoogleMapsService::processStatic('https://goo.gl/maps/rgZZt125tpvf2rnCA')->getFirst()->__toString());
		$this->assertSame('49.982825,14.571417', GoogleMapsService::processStatic('http://gOo.gl/maps/rgZZt125tpvf2rnCA')->getFirst()->__toString());
		$this->assertSame('49.306603,14.146709', GoogleMapsService::processStatic('https://goo.gl/maps/eUYMwABdpv9NNSDX7')->getFirst()->__toString());
		$this->assertSame('49.306233,14.146671', GoogleMapsService::processStatic('https://goo.GL/maps/hEbUKxSuMjA2')->getFirst()->__toString());
		$this->assertSame('49.270226,14.046216', GoogleMapsService::processStatic('https://goo.gl/maps/pPZ91TfW2edvejbb6')->getFirst()->__toString());
		$this->assertSame('49.296449,14.480361', GoogleMapsService::processStatic('https://maps.app.goo.gl/W5wPRJ5FMJxgaisf9')->getFirst()->__toString());
		$this->assertSame('49.296449,14.480361', GoogleMapsService::processStatic('http://maps.app.goo.gl/W5wPRJ5FMJxgaisf9')->getFirst()->__toString());
		$this->assertSame('49.267720,14.003169', GoogleMapsService::processStatic('https://maps.app.goo.gl/nJqTbFow1HtofApTA')->getFirst()->__toString());
	}

	public function testIsValid(): void
	{
		$this->assertTrue(GoogleMapsService::isValidStatic('https://www.google.com/maps/place/Velk%C3%BD+Meheln%C3%ADk,+397+01+Pisek/@49.2941662,14.2258333,14z/data=!4m2!3m1!1s0x470b5087ca84a6e9:0xfeb1428d8c8334da'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://www.google.com/maps/place/Zelend%C3%A1rky/@49.2069545,14.2495123,15z/data=!4m5!3m4!1s0x0:0x3ad3965c4ecb9e51!8m2!3d49.2113282!4d14.2553488'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://www.google.cz/maps/@36.8264601,22.5287146,9.33z'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://www.google.cz/maps/place/49%C2%B020\'00.6%22N+14%C2%B017\'46.2%22E/@49.3339819,14.2956352,18.4z/data=!4m5!3m4!1s0x0:0x0!8m2!3d49.333511!4d14.296174'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://www.google.cz/maps/place/Hrad+P%C3%ADsek/@49.3088543,14.1454615,391m/data=!3m1!1e3!4m12!1m6!3m5!1s0x470b4ff494c201db:0x4f78e2a2eaa0955b!2sHrad+P%C3%ADsek!8m2!3d49.3088525!4d14.1465894!3m4!1s0x470b4ff494c201db:0x4f78e2a2eaa0955b!8m2!3d49.3088525!4d14.1465894'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://www.google.com/maps/place/50%C2%B006\'04.6%22N+14%C2%B031\'44.0%22E/@50.101271,14.5281082,18z/data=!3m1!4b1!4m6!3m5!1s0x0:0x0!7e2!8m2!3d50.1012711!4d14.5288824?shorturl=1'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://maps.google.com/maps?ll=49.367523,14.514022&q=49.367523,14.514022'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://maps.google.com/maps?ll=-49.367523,-14.514022&q=-49.367523,-14.514022'));
		$this->assertTrue(GoogleMapsService::isValidStatic('http://maps.google.com/?q=49.417361,14.652640')); // http link from @ingressportalbot
		$this->assertTrue(GoogleMapsService::isValidStatic('http://maps.google.com/?q=-49.417361,-14.652640')); // http link from @ingressportalbot
		$this->assertTrue(GoogleMapsService::isValidStatic('https://maps.google.com/?q=49.417361,14.652640')); // same as above, just https
		$this->assertTrue(GoogleMapsService::isValidStatic('https://maps.google.com/?q=-49.417361,-14.652640')); // same as above, just https
		$this->assertTrue(GoogleMapsService::isValidStatic('http://maps.google.com/?daddr=50.052098,14.451968')); // http drive link from @ingressportalbot
		$this->assertTrue(GoogleMapsService::isValidStatic('http://maps.google.com/?daddr=-50.052098,-14.451968')); // http drive link from @ingressportalbot
		$this->assertTrue(GoogleMapsService::isValidStatic('https://maps.google.com/?daddr=50.052098,14.451968')); // same as above, just https
		$this->assertTrue(GoogleMapsService::isValidStatic('https://maps.google.com/?daddr=-50.052098,-14.451968')); // same as above, just https
		$this->assertTrue(GoogleMapsService::isValidStatic('https://www.google.cz/maps/place/50.02261,14.525433'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://www.google.cz/maps/place/-50.02261,-14.525433'));
	}

	public function testIsValidStreetView(): void
	{
		$this->assertTrue(GoogleMapsService::isValidStatic('https://www.google.com/maps/@50.0873231,14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://www.google.com/maps/@50.0873231,-14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://www.google.com/maps/@-50.0873231,14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'));
		$this->assertTrue(GoogleMapsService::isValidStatic('https://www.google.com/maps/@-50.0873231,-14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'));
		// valid but not street view, missing "3a" parameter
		$this->assertTrue(GoogleMapsService::isValidStatic('https://www.google.com/maps/@50.0873231,14.4208835,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656'));
	}

	public function testStreetView(): void
	{
		// Gathering coordinates from street view is not precise - it assumes that "@lat,lon" is location of currently loaded street view
		// It measns, that opening links below, with different lat or lon, still opens location like in first URL in this test.
		$service = GoogleMapsService::processStatic('https://www.google.com/maps/@50.0873231,14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656');
		$this->assertCount(1, $service->getCollection());
		$location = $service->getCollection()->getFirst();
		$this->assertEquals('Street view', $location->getName());
		$this->assertEquals('50.087323,14.420884', $location->__toString());

		$service = GoogleMapsService::processStatic('https://www.google.com/maps/@5.0873231,-14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656');
		$this->assertCount(1, $service->getCollection());
		$location = $service->getCollection()->getFirst();
		$this->assertEquals('Street view', $location->getName());
		$this->assertEquals('5.087323,-14.420884', $location->__toString());

		$service = GoogleMapsService::processStatic('https://www.google.com/maps/@-50.0873231,144.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656');
		$this->assertCount(1, $service->getCollection());
		$location = $service->getCollection()->getFirst();
		$this->assertEquals('Street view', $location->getName());
		$this->assertEquals('-50.087323,144.420884', $location->__toString());

		$service = GoogleMapsService::processStatic('https://www.google.com/maps/@-50.0873231,-14.4208835,3a,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656');
		$this->assertCount(1, $service->getCollection());
		$location = $service->getCollection()->getFirst();
		$this->assertEquals('Street view', $location->getName());
		$this->assertEquals('-50.087323,-14.420884', $location->__toString());

		// Valid location, but not street view - missing "3a" parameter so it fallback to general Map center
		$service = GoogleMapsService::processStatic('https://www.google.com/maps/@50.0873231,14.4208835,75y,254.65h,90t/data=!3m7!1e1!3m5!1sL_00EpSjrJlMCFtP8VYCZg!2e0!6s%2F%2Fgeo3.ggpht.com%2Fcbk%3Fpanoid%3DL_00EpSjrJlMCFtP8VYCZg%26output%3Dthumbnail%26cb_client%3Dmaps_sv.tactile.gps%26thumb%3D2%26w%3D203%26h%3D100%26yaw%3D246.83417%26pitch%3D0%26thumbfov%3D100!7i13312!8i6656');
		$this->assertCount(1, $service->getCollection());
		$location = $service->getCollection()->getFirst();
		$this->assertEquals('Map center', $location->getName());
		$this->assertEquals('50.087323,14.420884', $location->__toString());
	}

	public function testNormalUrl(): void
	{
		$collection = GoogleMapsService::processStatic('https://www.google.com/maps/place/Velk%C3%BD+Meheln%C3%ADk,+397+01+Pisek/@49.2941662,14.2258333,14z/data=!4m2!3m1!1s0x470b5087ca84a6e9:0xfeb1428d8c8334da')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('Map center', $collection[0]->getName());
		$this->assertSame('49.294166,14.225833', $collection[0]->__toString());

		$collection = GoogleMapsService::processStatic('https://www.google.com/maps/place/Zelend%C3%A1rky/@49.2069545,14.2495123,15z/data=!4m5!3m4!1s0x0:0x3ad3965c4ecb9e51!8m2!3d49.2113282!4d14.2553488')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('Place', $collection[0]->getName());
		$this->assertSame('49.211328,14.255349', $collection[0]->__toString());
		$this->assertSame('Map center', $collection[1]->getName());
		$this->assertSame('49.206955,14.249512', $collection[1]->__toString());

		$this->assertSame('36.826460,22.528715', GoogleMapsService::processStatic('https://www.google.cz/maps/@36.8264601,22.5287146,9.33z')->getFirst()->__toString());
		$this->assertSame('49.333511,14.296174', GoogleMapsService::processStatic('https://www.google.cz/maps/place/49%C2%B020\'00.6%22N+14%C2%B017\'46.2%22E/@49.3339819,14.2956352,18.4z/data=!4m5!3m4!1s0x0:0x0!8m2!3d49.333511!4d14.296174')->getFirst()->__toString());
		$this->assertSame('49.308853,14.146589', GoogleMapsService::processStatic('https://www.google.cz/maps/place/Hrad+P%C3%ADsek/@49.3088543,14.1454615,391m/data=!3m1!1e3!4m12!1m6!3m5!1s0x470b4ff494c201db:0x4f78e2a2eaa0955b!2sHrad+P%C3%ADsek!8m2!3d49.3088525!4d14.1465894!3m4!1s0x470b4ff494c201db:0x4f78e2a2eaa0955b!8m2!3d49.3088525!4d14.1465894')->getFirst()->__toString());

		$collection = GoogleMapsService::processStatic('https://www.google.com/maps/place/50%C2%B006\'04.6%22N+14%C2%B031\'44.0%22E/@50.101271,14.5281082,18z/data=!3m1!4b1!4m6!3m5!1s0x0:0x0!7e2!8m2!3d50.1012711!4d14.5288824?shorturl=1')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('50.101271,14.528882', $collection[0]->__toString());
		$this->assertSame('Place', $collection[0]->getName());
		$this->assertSame('50.101271,14.528108', $collection[1]->__toString());
		$this->assertSame('Map center', $collection[1]->getName());

		$this->assertSame('49.367523,14.514022', GoogleMapsService::processStatic('https://maps.google.com/maps?ll=49.367523,14.514022&q=49.367523,14.514022')->getFirst()->__toString());
		$this->assertSame('49.367523,14.514022', GoogleMapsService::processStatic('https://maps.google.com/maps?ll=49.367523,14.514022&q=49.367523,14.514022')->getFirst()->__toString());
		$this->assertSame('49.417361,14.652640', GoogleMapsService::processStatic('http://maps.google.com/?q=49.417361,14.652640')->getFirst()->__toString()); // http link from @ingressportalbot
		$this->assertSame('-49.417361,-14.652640', GoogleMapsService::processStatic('http://maps.google.com/?q=-49.417361,-14.652640')->getFirst()->__toString()); // http link from @ingressportalbot
		$this->assertSame('49.417361,14.652640', GoogleMapsService::processStatic('https://maps.google.com/?q=49.417361,14.652640')->getFirst()->__toString()); // same as above, just https
		$this->assertSame('-49.417361,-14.652640', GoogleMapsService::processStatic('https://maps.google.com/?q=-49.417361,-14.652640')->getFirst()->__toString()); // same as above, just https
		$this->assertSame('50.052098,14.451968', GoogleMapsService::processStatic('http://maps.google.com/?daddr=50.052098,14.451968')->getFirst()->__toString()); // http drive link from @ingressportalbot
		$this->assertSame('-50.052098,-14.451968', GoogleMapsService::processStatic('http://maps.google.com/?daddr=-50.052098,-14.451968')->getFirst()->__toString()); // http drive link from @ingressportalbot
		$this->assertSame('50.052098,14.451968', GoogleMapsService::processStatic('https://maps.google.com/?daddr=50.052098,14.451968')->getFirst()->__toString()); // same as above, just https
		$this->assertSame('-50.052098,-14.451968', GoogleMapsService::processStatic('https://maps.google.com/?daddr=-50.052098,-14.451968')->getFirst()->__toString()); // same as above, just https
		$this->assertSame('50.022610,14.525433', GoogleMapsService::processStatic('https://www.google.cz/maps/place/50.02261,14.525433')->getFirst()->__toString());
		$this->assertSame('-50.022610,-14.525433', GoogleMapsService::processStatic('https://www.google.cz/maps/place/-50.02261,-14.525433')->getFirst()->__toString());
	}

	/**
	 * Links generated on phone in Google maps app by clicking on "share" button while opened place
	 */
	public function testShareUrlPhone(): void
	{
		// Baumax Michle
		$collection = GoogleMapsService::processStatic('https://www.google.com/maps/place/bauMax,+Chodovsk%C3%A1+1549%2F18,+101+00+Praha+10/data=!4m2!3m1!1s0x470b93a27e4781c5:0xeca4ac5483aa4dd2?utm_source=mstt_1&entry=gps')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('hidden', $collection[0]->getName());
		$this->assertSame('50.056068,14.472953', $collection[0]->__toString());
		// same as above but short URL
		$collection = GoogleMapsService::processStatic('https://maps.app.goo.gl/X5bZDTSFfdRzchGY6')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('hidden', $collection[0]->getName());
		$this->assertSame('50.056068,14.472953', $collection[0]->__toString());

		// Lemour Sušice
		$collection = GoogleMapsService::processStatic('https://www.google.com/maps/place/Caf%C3%A9+Lamour,+n%C3%A1b%C5%99.+Karla+Houry+180,+342+01+Su%C5%A1ice/data=!4m2!3m1!1s0x470b2b2fad7dd1c3:0x6c66c5beca8a4117?utm_source=mstt_1&entry=gps')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('hidden', $collection[0]->getName());
		$this->assertSame('49.231830,13.521600', $collection[0]->__toString());
		// same as above but short URL
		$collection = GoogleMapsService::processStatic('https://maps.app.goo.gl/C4FjaU9CXsHuMrobA')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('hidden', $collection[0]->getName());
		$this->assertSame('49.231830,13.521600', $collection[0]->__toString());
	}

	/**
	 * Links generated in browser on Google app by clicking on "share" button
	 * Opened URL before opening place: https://www.google.com/maps/@50.0543547,14.4763896,16.75z
	 * Opened URL after opening place: https://www.google.com/maps/place/bauMax/@50.0543547,14.4763896,16.75z/data=!4m5!3m4!1s0x470b93a27e4781c5:0xeca4ac5483aa4dd2!8m2!3d50.0560684!4d14.4729532
	 */
	public function testShareUrlPCBrowser(): void
	{
		// Baumax Michle
		$collection = GoogleMapsService::processStatic('https://www.google.com/maps/place/bauMax/@50.0543547,14.4763896,16.75z/data=!4m5!3m4!1s0x470b93a27e4781c5:0xeca4ac5483aa4dd2!8m2!3d50.0560684!4d14.4729532?shorturl=1')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('Place', $collection[0]->getName());
		$this->assertSame('50.056068,14.472953', $collection[0]->__toString());
		$this->assertSame('Map center', $collection[1]->getName());
		$this->assertSame('50.054355,14.476390', $collection[1]->__toString());
		// same as above but short URL
		$collection = GoogleMapsService::processStatic('https://goo.gl/maps/AK13hVJLjnveWZqJA')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('Place', $collection[0]->getName());
		$this->assertSame('50.056068,14.472953', $collection[0]->__toString());
		$this->assertSame('Map center', $collection[1]->getName());
		$this->assertSame('50.054355,14.476390', $collection[1]->__toString());
	}

}
