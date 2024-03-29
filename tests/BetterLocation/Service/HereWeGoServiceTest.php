<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\HereWeGoService;
use PHPUnit\Framework\TestCase;

final class HereWeGoServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://share.here.com/l/50.087451,14.420671?p=yes', HereWeGoService::getLink(50.087451, 14.420671));
		$this->assertSame('https://share.here.com/l/50.100000,14.500000?p=yes', HereWeGoService::getLink(50.1, 14.5));
		$this->assertSame('https://share.here.com/l/-50.200000,14.600000?p=yes', HereWeGoService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://share.here.com/l/50.300000,-14.700001?p=yes', HereWeGoService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://share.here.com/l/-50.400000,-14.800008?p=yes', HereWeGoService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->assertSame('https://share.here.com/r/50.087451,14.420671', HereWeGoService::getLink(50.087451, 14.420671, true));
		$this->assertSame('https://share.here.com/r/50.100000,14.500000', HereWeGoService::getLink(50.1, 14.5, true));
		$this->assertSame('https://share.here.com/r/-50.200000,14.600000', HereWeGoService::getLink(-50.2, 14.6000001, true)); // round down
		$this->assertSame('https://share.here.com/r/50.300000,-14.700001', HereWeGoService::getLink(50.3, -14.7000009, true)); // round up
		$this->assertSame('https://share.here.com/r/-50.400000,-14.800008', HereWeGoService::getLink(-50.4, -14.800008, true));
	}

	public function testIsValidNormalUrl(): void
	{
		$this->assertTrue(HereWeGoService::isValidStatic('https://wego.here.com/?map=50.07513,14.45453,15,normal')); // browser
		$this->assertTrue(HereWeGoService::isValidStatic('https://wego.here.com/?x=ep&map=50.08629,14.42808,15,satellite_traffic'));
		$this->assertTrue(HereWeGoService::isValidStatic('https://share.here.com/l/50.05215,14.45256,Ohradn%C3%AD?z=16&t=normal&ref=android'));
		$this->assertTrue(HereWeGoService::isValidStatic('https://wego.here.com/public-transport/Station%2520des%2520Bus%2520Ankazomanga/-18.895111/47.516448?map=-18.89511,47.51645,16,normal'));
		$this->assertTrue(HereWeGoService::isValidStatic('https://wego.here.com/%C4%8Desk%C3%A1-republika/praha/public-transport/staromestska/50.088043/14.4183?map=50.08829,14.41779,17,satellite_traffic'));
		$this->assertTrue(HereWeGoService::isValidStatic('https://wego.here.com/public-transport/Staromestska/50.088183/14.415371?map=50.08818,14.41537,17,normal'));  // browser, selected place before redirect
		$this->assertTrue(HereWeGoService::isValidStatic('https://wego.here.com/%C4%8Desk%C3%A1-republika/praha/public-transport/staromestska/50.088183/14.415371?map=50.08818,14.41537,17,normal'));  // browser, selected place after redirect from URL above
		$this->assertTrue(HereWeGoService::isValidStatic('https://wego.here.com/public-transport/Esta%25C3%25A7%25C3%25A3o%2520102%2520Sul%2520Metro/-15.805571/-47.889866?map=-15.80557,-47.88987,16,terrain'));  // browser, selected place before redirect
		$this->assertTrue(HereWeGoService::isValidStatic('https://wego.here.com/brasil/bras%C3%ADlia/public-transport/esta%C3%A7%C3%A3o-102-sul-metro/-15.805571/-47.889866?map=-15.80557,-47.88987,16,terrain'));  // browser, selected place after redirect from URL above
	}

	public function testNormalUrl(): void
	{
		$collection = HereWeGoService::processStatic('https://wego.here.com/?map=50.07513,14.45453,15,normal')->getCollection(); // browser
		$this->assertCount(1, $collection);
		$this->assertSame('50.075130,14.454530', $collection[0]->__toString()); // browser
		$this->assertSame('Map center', $collection[0]->getSourceType());

		$collection = HereWeGoService::processStatic('https://wego.here.com/?x=ep&map=50.08629,14.42808,15,satellite_traffic')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.086290,14.428080', $collection[0]->__toString());
		$this->assertSame('Map center', $collection[0]->getSourceType());

		$collection = HereWeGoService::processStatic('https://share.here.com/l/50.05215,14.45256,Ohradn%C3%AD?z=16&t=normal&ref=android')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.052150,14.452560', $collection[0]->__toString());
		$this->assertSame('Place coords', $collection[0]->getSourceType());

		$collection = HereWeGoService::processStatic('https://wego.here.com/public-transport/Station%2520des%2520Bus%2520Ankazomanga/-18.895111/47.516448?map=-18.89511,47.51645,16,normal')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('-18.895111,47.516448', $collection[0]->__toString());
		$this->assertSame('Place coords', $collection[0]->getSourceType());
		$this->assertSame('-18.895110,47.516450', $collection[1]->__toString());
		$this->assertSame('Map center', $collection[1]->getSourceType());

		$collection = HereWeGoService::processStatic('https://wego.here.com/%C4%8Desk%C3%A1-republika/praha/public-transport/staromestska/50.088043/14.4183?map=50.08829,14.41779,17,satellite_traffic')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('50.088043,14.418300', $collection[0]->__toString());
		$this->assertSame('Place coords', $collection[0]->getSourceType());
		$this->assertSame('50.088290,14.417790', $collection[1]->__toString());
		$this->assertSame('Map center', $collection[1]->getSourceType());

		$collection = HereWeGoService::processStatic('https://wego.here.com/public-transport/Staromestska/50.088183/14.415371?map=50.08818,14.41537,17,normal')->getCollection();  // browser, selected place before redirect
		$this->assertCount(2, $collection);
		$this->assertSame('50.088183,14.415371', $collection[0]->__toString());
		$this->assertSame('Place coords', $collection[0]->getSourceType());
		$this->assertSame('50.088180,14.415370', $collection[1]->__toString());
		$this->assertSame('Map center', $collection[1]->getSourceType());

		$collection = HereWeGoService::processStatic('https://wego.here.com/%C4%8Desk%C3%A1-republika/praha/public-transport/staromestska/50.088183/14.415371?map=50.08818,14.41537,17,normal')->getCollection();  // browser, selected place after redirect from URL above
		$this->assertCount(2, $collection);
		$this->assertSame('50.088183,14.415371', $collection[0]->__toString());
		$this->assertSame('Place coords', $collection[0]->getSourceType());
		$this->assertSame('50.088180,14.415370', $collection[1]->__toString());
		$this->assertSame('Map center', $collection[1]->getSourceType());

		$collection = HereWeGoService::processStatic('https://wego.here.com/public-transport/Esta%25C3%25A7%25C3%25A3o%2520102%2520Sul%2520Metro/-15.805571/-47.889866?map=-15.80557,-47.88987,16,terrain')->getCollection();  // browser, selected place before redirect
		$this->assertCount(2, $collection);
		$this->assertSame('-15.805571,-47.889866', $collection[0]->__toString());
		$this->assertSame('Place coords', $collection[0]->getSourceType());
		$this->assertSame('-15.805570,-47.889870', $collection[1]->__toString());
		$this->assertSame('Map center', $collection[1]->getSourceType());

		$collection = HereWeGoService::processStatic('https://wego.here.com/brasil/bras%C3%ADlia/public-transport/esta%C3%A7%C3%A3o-102-sul-metro/-15.805571/-47.889866?map=-15.80557,-47.88987,16,terrain')->getCollection();  // browser, selected place after redirect from URL above
		$this->assertCount(2, $collection);
		$this->assertSame('-15.805571,-47.889866', $collection[0]->__toString());
		$this->assertSame('Place coords', $collection[0]->getSourceType());
		$this->assertSame('-15.805570,-47.889870', $collection[1]->__toString());
		$this->assertSame('Map center', $collection[1]->getSourceType());
	}

	public function testIsValidStartPointUrl(): void
	{
		$this->assertTrue(HereWeGoService::isValidStatic('https://wego.here.com/directions/mix/Bottomwoods,-Longwood,-Saint-Helena:-15.95104,-5.67743/?map=-15.9508,-5.67904,18,terrain&msg=Bottomwoods'));
	}

	public function testStartPointUrl(): void
	{
		$collection = HereWeGoService::processStatic('https://wego.here.com/directions/mix/Bottomwoods,-Longwood,-Saint-Helena:-15.95104,-5.67743/?map=-15.9508,-5.67904,18,terrain&msg=Bottomwoods')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('-15.951040,-5.677430', $collection[0]->__toString());
		$this->assertSame('Place coords', $collection[0]->getSourceType());
		$this->assertSame('-15.950800,-5.679040', $collection[1]->__toString());
		$this->assertSame('Map center', $collection[1]->getSourceType());
	}

	public function testIsValidRequestLocUrl(): void
	{
		$this->assertTrue(HereWeGoService::isValidStatic('https://wego.here.com/czech-republic/prague/street-square/m%C3%A1nes%C5%AFv-most--loc-dmVyc2lvbj0xO3RpdGxlPU0lQzMlQTFuZXMlQzUlQUZ2K21vc3Q7bGF0PTUwLjA4OTM0O2xvbj0xNC40MTM2NDtzdHJlZXQ9TSVDMyVBMW5lcyVDNSVBRnYrbW9zdDtjaXR5PVByYWd1ZTtwb3N0YWxDb2RlPTExOCswMDtjb3VudHJ5PUNaRTtkaXN0cmljdD1QcmFoYSsxO3N0YXRlQ29kZT1QcmFndWU7Y291bnR5PVByYWd1ZTtjYXRlZ29yeUlkPXN0cmVldC1zcXVhcmU7c291cmNlU3lzdGVtPWludGVybmFs?map=50.08963,14.41276,16,satellite_traffic&msg=M%C3%A1nes%C5%AFv%20most'));
		$this->assertTrue(HereWeGoService::isValidStatic('https://wego.here.com/czech-republic/prague/street-square/na-hr%C3%A1zi-17825--loc-dmVyc2lvbj0xO3RpdGxlPU5hK0hyJUMzJUExemkrMTc4JTJGMjU7bGF0PTUwLjEwNTU0O2xvbj0xNC40NzU5O3N0cmVldD1OYStIciVDMyVBMXppO2hvdXNlPTE3OCUyRjI1O2NpdHk9UHJhZ3VlO3Bvc3RhbENvZGU9MTgwKzAwO2NvdW50cnk9Q1pFO2Rpc3RyaWN0PVByYWhhKzg7c3RhdGVDb2RlPVByYWd1ZTtjb3VudHk9UHJhZ3VlO2NhdGVnb3J5SWQ9YnVpbGRpbmc7c291cmNlU3lzdGVtPWludGVybmFs?map=50.10554,14.4759,15,normal&msg=Na%20Hr%C3%A1zi%20178%2F25'));
		// Negative coordinates, map center is on different location than selected place
		$this->assertTrue(HereWeGoService::isValidStatic('https://wego.here.com/saint-helena/sandy-bay/city-town-village/sandy-bay--loc-dmVyc2lvbj0xO3RpdGxlPVNhbmR5K0JheTtsYXQ9LTE1Ljk3ODE2O2xvbj0tNS43MTIwNTtjaXR5PVNhbmR5K0JheTtjb3VudHJ5PVNITjtjb3VudHk9U2FuZHkrQmF5O2NhdGVnb3J5SWQ9Y2l0eS10b3duLXZpbGxhZ2U7c291cmNlU3lzdGVtPWludGVybmFs?map=-15.99429,-5.75681,15,normal&msg=Sandy%20Bay'));
	}

	/**
	 * @group request
	 */
	public function testRequestLocUrl(): void
	{
		$collection = HereWeGoService::processStatic('https://wego.here.com/czech-republic/prague/street-square/m%C3%A1nes%C5%AFv-most--loc-dmVyc2lvbj0xO3RpdGxlPU0lQzMlQTFuZXMlQzUlQUZ2K21vc3Q7bGF0PTUwLjA4OTM0O2xvbj0xNC40MTM2NDtzdHJlZXQ9TSVDMyVBMW5lcyVDNSVBRnYrbW9zdDtjaXR5PVByYWd1ZTtwb3N0YWxDb2RlPTExOCswMDtjb3VudHJ5PUNaRTtkaXN0cmljdD1QcmFoYSsxO3N0YXRlQ29kZT1QcmFndWU7Y291bnR5PVByYWd1ZTtjYXRlZ29yeUlkPXN0cmVldC1zcXVhcmU7c291cmNlU3lzdGVtPWludGVybmFs?map=50.08963,14.41276,16,satellite_traffic&msg=M%C3%A1nes%C5%AFv%20most')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('50.089340,14.413640', $collection[0]->__toString());
		$this->assertSame('Place', $collection[0]->getSourceType());
		$this->assertSame('50.089630,14.412760', $collection[1]->__toString());
		$this->assertSame('Map center', $collection[1]->getSourceType());

		$collection = HereWeGoService::processStatic('https://wego.here.com/czech-republic/prague/street-square/na-hr%C3%A1zi-17825--loc-dmVyc2lvbj0xO3RpdGxlPU5hK0hyJUMzJUExemkrMTc4JTJGMjU7bGF0PTUwLjEwNTU0O2xvbj0xNC40NzU5O3N0cmVldD1OYStIciVDMyVBMXppO2hvdXNlPTE3OCUyRjI1O2NpdHk9UHJhZ3VlO3Bvc3RhbENvZGU9MTgwKzAwO2NvdW50cnk9Q1pFO2Rpc3RyaWN0PVByYWhhKzg7c3RhdGVDb2RlPVByYWd1ZTtjb3VudHk9UHJhZ3VlO2NhdGVnb3J5SWQ9YnVpbGRpbmc7c291cmNlU3lzdGVtPWludGVybmFs?map=50.10554,14.4759,15,normal&msg=Na%20Hr%C3%A1zi%20178%2F25')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('50.105540,14.475900', $collection[0]->__toString());
		$this->assertSame('Place', $collection[0]->getSourceType());
		$this->assertSame('50.105540,14.475900', $collection[1]->__toString());
		$this->assertSame('Map center', $collection[1]->getSourceType());

		// Negative coordinates, map center is on different location than selected place
		$collection = HereWeGoService::processStatic('https://wego.here.com/saint-helena/sandy-bay/city-town-village/sandy-bay--loc-dmVyc2lvbj0xO3RpdGxlPVNhbmR5K0JheTtsYXQ9LTE1Ljk3ODE2O2xvbj0tNS43MTIwNTtjaXR5PVNhbmR5K0JheTtjb3VudHJ5PVNITjtjb3VudHk9U2FuZHkrQmF5O2NhdGVnb3J5SWQ9Y2l0eS10b3duLXZpbGxhZ2U7c291cmNlU3lzdGVtPWludGVybmFs?map=-15.99429,-5.75681,15,normal&msg=Sandy%20Bay')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('-15.978160,-5.712050', $collection[0]->__toString());
		$this->assertSame('Place', $collection[0]->getSourceType());
		$this->assertSame('-15.994290,-5.756810', $collection[1]->__toString());
		$this->assertSame('Map center', $collection[1]->getSourceType());
	}

	public function testIsValidShortUrl(): void
	{
		$this->assertTrue(HereWeGoService::isValidStatic('https://her.is/3lZVXD3'));
		$this->assertTrue(HereWeGoService::isValidStatic('https://her.is/3bEopFZ'));
	}

	/**
	 * @group request
	 */
	public function testShortUrl(): void
	{
		$collection = HereWeGoService::processStatic('https://her.is/3lZVXD3')->getCollection(); // map center was few kilometers off the shared point when creating share link
		$this->assertCount(1, $collection);
		// -> https://share.here.com/p/s-Yz1wb3N0YWwtYXJlYTtsYXQ9NTAuMTA5NTc7bG9uPTE0LjQ0MTIyO249UHJhaGErNztoPTc1NWM3OQ?ref=here_com
		// -> https://wego.here.com/p/s-Yz1wb3N0YWwtYXJlYTtsYXQ9NTAuMTA5NTc7bG9uPTE0LjQ0MTIyO249UHJhaGErNztoPTc1NWM3OQ?map=50.10957%2C14.44122%2C15%2Cnormal&ref=here_com
		$this->assertSame('50.109570,14.441220', $collection[0]->__toString());
		$this->assertSame('Place share', $collection[0]->getSourceType());

		$collection = HereWeGoService::processStatic('https://her.is/3bEopFZ')->getCollection();
		// -> https://share.here.com/p/s-Yz1wb3N0YWwtYXJlYTtsYXQ9NTAuMDk2Njc7bG9uPTE0LjQ0NTEzO249UHJhaGErNztoPTczNTY3Nw?ref=here_com
		// -> https://wego.here.com/p/s-Yz1wb3N0YWwtYXJlYTtsYXQ9NTAuMDk2Njc7bG9uPTE0LjQ0NTEzO249UHJhaGErNztoPTczNTY3Nw?map=50.09667%2C14.44513%2C15%2Cnormal&ref=here_com
		$this->assertSame('50.096670,14.445130', $collection[0]->__toString());
		$this->assertSame('Place share', $collection[0]->getSourceType());
	}

	public function testIsInvalidUrl(): void
	{
		$this->assertFalse(HereWeGoService::isValidStatic('Novinky.cz'));
	}
}
