<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use \BetterLocation\Service\HereWeGoService;

require_once __DIR__ . '/../src/config.php';


final class HereWeGoServiceTest extends TestCase
{
	public function testGenerateShareLink(): void {
		$this->assertEquals('https://share.here.com/l/50.087451,14.420671?p=yes', HereWeGoService::getLink(50.087451, 14.420671));
		$this->assertEquals('https://share.here.com/l/50.100000,14.500000?p=yes', HereWeGoService::getLink(50.1, 14.5));
		$this->assertEquals('https://share.here.com/l/-50.200000,14.600000?p=yes', HereWeGoService::getLink(-50.2, 14.6000001)); // round down
		$this->assertEquals('https://share.here.com/l/50.300000,-14.700001?p=yes', HereWeGoService::getLink(50.3, -14.7000009)); // round up
		$this->assertEquals('https://share.here.com/l/-50.400000,-14.800008?p=yes', HereWeGoService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void {
		$this->assertEquals('https://share.here.com/r/50.087451,14.420671', HereWeGoService::getLink(50.087451, 14.420671, true));
		$this->assertEquals('https://share.here.com/r/50.100000,14.500000', HereWeGoService::getLink(50.1, 14.5, true));
		$this->assertEquals('https://share.here.com/r/-50.200000,14.600000', HereWeGoService::getLink(-50.2, 14.6000001, true)); // round down
		$this->assertEquals('https://share.here.com/r/50.300000,-14.700001', HereWeGoService::getLink(50.3, -14.7000009, true)); // round up
		$this->assertEquals('https://share.here.com/r/-50.400000,-14.800008', HereWeGoService::getLink(-50.4, -14.800008, true));
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testNormalUrl(): void {
		$this->assertEquals('50.075130, 14.454530', HereWeGoService::parseCoordsMultiple('https://wego.here.com/?map=50.07513,14.45453,15,normal')[0]->__toString()); // browser

		$this->assertEquals('50.086290, 14.428080', HereWeGoService::parseCoordsMultiple('https://wego.here.com/?x=ep&map=50.08629,14.42808,15,satellite_traffic')[0]->__toString());

		$this->assertEquals('50.052150, 14.452560', HereWeGoService::parseCoordsMultiple('https://share.here.com/l/50.05215,14.45256,Ohradn%C3%AD?z=16&t=normal&ref=android')[0]->__toString());

		$locations = HereWeGoService::parseCoordsMultiple('https://wego.here.com/public-transport/Station%2520des%2520Bus%2520Ankazomanga/-18.895111/47.516448?map=-18.89511,47.51645,16,normal');
		$this->assertEquals('-18.895111, 47.516448', $locations[0]->__toString());
		$this->assertEquals('-18.895110, 47.516450', $locations[1]->__toString());

		$locations = HereWeGoService::parseCoordsMultiple('https://wego.here.com/%C4%8Desk%C3%A1-republika/praha/public-transport/staromestska/50.088043/14.4183?map=50.08829,14.41779,17,satellite_traffic');
		$this->assertEquals('50.088043, 14.418300', $locations[0]->__toString());
		$this->assertEquals('50.088290, 14.417790', $locations[1]->__toString());

		$location = HereWeGoService::parseCoordsMultiple('https://wego.here.com/public-transport/Staromestska/50.088183/14.415371?map=50.08818,14.41537,17,normal');  // browser, selected place before redirect
		$this->assertEquals('50.088183, 14.415371', $location[0]->__toString());
		$this->assertEquals('50.088180, 14.415370', $location[1]->__toString());
		$location = HereWeGoService::parseCoordsMultiple('https://wego.here.com/%C4%8Desk%C3%A1-republika/praha/public-transport/staromestska/50.088183/14.415371?map=50.08818,14.41537,17,normal');  // browser, selected place after redirect from URL above
		$this->assertEquals('50.088183, 14.415371', $location[0]->__toString());
		$this->assertEquals('50.088180, 14.415370', $location[1]->__toString());

		$location = HereWeGoService::parseCoordsMultiple('https://wego.here.com/public-transport/Esta%25C3%25A7%25C3%25A3o%2520102%2520Sul%2520Metro/-15.805571/-47.889866?map=-15.80557,-47.88987,16,terrain');  // browser, selected place before redirect
		$this->assertEquals('-15.805571, -47.889866', $location[0]->__toString());
		$this->assertEquals('-15.805570, -47.889870', $location[1]->__toString());
		$location = HereWeGoService::parseCoordsMultiple('https://wego.here.com/brasil/bras%C3%ADlia/public-transport/esta%C3%A7%C3%A3o-102-sul-metro/-15.805571/-47.889866?map=-15.80557,-47.88987,16,terrain');  // browser, selected place after redirect from URL above
		$this->assertEquals('-15.805571, -47.889866', $location[0]->__toString());
		$this->assertEquals('-15.805570, -47.889870', $location[1]->__toString());
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testStartPointUrl(): void {
		$location = HereWeGoService::parseCoordsMultiple('https://wego.here.com/directions/mix/Bottomwoods,-Longwood,-Saint-Helena:-15.95104,-5.67743/?map=-15.9508,-5.67904,18,terrain&msg=Bottomwoods');
		$this->assertEquals('-15.951040, -5.677430', $location[0]->__toString());
		$this->assertEquals('-15.950800, -5.679040', $location[1]->__toString());
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testRequestLocUrl(): void {
		$location = HereWeGoService::parseCoordsMultiple('https://wego.here.com/czech-republic/prague/street-square/m%C3%A1nes%C5%AFv-most--loc-dmVyc2lvbj0xO3RpdGxlPU0lQzMlQTFuZXMlQzUlQUZ2K21vc3Q7bGF0PTUwLjA4OTM0O2xvbj0xNC40MTM2NDtzdHJlZXQ9TSVDMyVBMW5lcyVDNSVBRnYrbW9zdDtjaXR5PVByYWd1ZTtwb3N0YWxDb2RlPTExOCswMDtjb3VudHJ5PUNaRTtkaXN0cmljdD1QcmFoYSsxO3N0YXRlQ29kZT1QcmFndWU7Y291bnR5PVByYWd1ZTtjYXRlZ29yeUlkPXN0cmVldC1zcXVhcmU7c291cmNlU3lzdGVtPWludGVybmFs?map=50.08963,14.41276,16,satellite_traffic&msg=M%C3%A1nes%C5%AFv%20most');
		$this->assertEquals('50.089340, 14.413640', $location[0]->__toString());
		$this->assertEquals('50.089630, 14.412760', $location[1]->__toString());

		$location = HereWeGoService::parseCoordsMultiple('https://wego.here.com/czech-republic/prague/street-square/na-hr%C3%A1zi-17825--loc-dmVyc2lvbj0xO3RpdGxlPU5hK0hyJUMzJUExemkrMTc4JTJGMjU7bGF0PTUwLjEwNTU0O2xvbj0xNC40NzU5O3N0cmVldD1OYStIciVDMyVBMXppO2hvdXNlPTE3OCUyRjI1O2NpdHk9UHJhZ3VlO3Bvc3RhbENvZGU9MTgwKzAwO2NvdW50cnk9Q1pFO2Rpc3RyaWN0PVByYWhhKzg7c3RhdGVDb2RlPVByYWd1ZTtjb3VudHk9UHJhZ3VlO2NhdGVnb3J5SWQ9YnVpbGRpbmc7c291cmNlU3lzdGVtPWludGVybmFs?map=50.10554,14.4759,15,normal&msg=Na%20Hr%C3%A1zi%20178%2F25');
		$this->assertEquals('50.105540, 14.475900', $location[0]->__toString());
		$this->assertEquals('50.105540, 14.475900', $location[1]->__toString());

		// Negative coordinates, map center is on different location than selected place
		$location = HereWeGoService::parseCoordsMultiple('https://wego.here.com/saint-helena/sandy-bay/city-town-village/sandy-bay--loc-dmVyc2lvbj0xO3RpdGxlPVNhbmR5K0JheTtsYXQ9LTE1Ljk3ODE2O2xvbj0tNS43MTIwNTtjaXR5PVNhbmR5K0JheTtjb3VudHJ5PVNITjtjb3VudHk9U2FuZHkrQmF5O2NhdGVnb3J5SWQ9Y2l0eS10b3duLXZpbGxhZ2U7c291cmNlU3lzdGVtPWludGVybmFs?map=-15.99429,-5.75681,15,normal&msg=Sandy%20Bay');
		$this->assertEquals('-15.978160, -5.712050', $location[0]->__toString());
		$this->assertEquals('-15.994290, -5.756810', $location[1]->__toString());
	}
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testShortUrl(): void {
		$location = HereWeGoService::parseCoordsMultiple('https://her.is/3lZVXD3'); // map center was few kilometers off the shared point when creating share link
		// -> https://share.here.com/p/s-Yz1wb3N0YWwtYXJlYTtsYXQ9NTAuMTA5NTc7bG9uPTE0LjQ0MTIyO249UHJhaGErNztoPTc1NWM3OQ?ref=here_com
		// -> https://wego.here.com/p/s-Yz1wb3N0YWwtYXJlYTtsYXQ9NTAuMTA5NTc7bG9uPTE0LjQ0MTIyO249UHJhaGErNztoPTc1NWM3OQ?map=50.10957%2C14.44122%2C15%2Cnormal&ref=here_com
		$this->assertEquals('50.109570, 14.441220', $location[0]->__toString());

		$location = HereWeGoService::parseCoordsMultiple('https://her.is/3bEopFZ');
		// -> https://share.here.com/p/s-Yz1wb3N0YWwtYXJlYTtsYXQ9NTAuMDk2Njc7bG9uPTE0LjQ0NTEzO249UHJhaGErNztoPTczNTY3Nw?ref=here_com
		// -> https://wego.here.com/p/s-Yz1wb3N0YWwtYXJlYTtsYXQ9NTAuMDk2Njc7bG9uPTE0LjQ0NTEzO249UHJhaGErNztoPTczNTY3Nw?map=50.09667%2C14.44513%2C15%2Cnormal&ref=here_com
		$this->assertEquals('50.096670, 14.445130', $location[0]->__toString());
	}

	public function testIsInvalidUrl(): void {
		$this->assertFalse(HereWeGoService::isValid('Novinky.cz'));
	}
}
