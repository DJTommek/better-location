<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\MapyCzService;
use App\BetterLocation\Service\VojenskoCzService;
use Tests\HttpTestClients;

final class VojenskoCzServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return VojenskoCzService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function isValidProvider(): array
	{
		return [
			[true, 'https://www.vojensko.cz/objekty-csla/sekce-00031-kasarna-a-objekty-csla/polozka-03180-vu-5849-jachymov-vrsek'],
			[true, 'http://www.vojensko.cz/objekty-csla/sekce-00031-kasarna-a-objekty-csla/polozka-03180-vu-5849-jachymov-vrsek'],
			[true, 'http://vojensko.cz/objekty-csla/sekce-00031-kasarna-a-objekty-csla/polozka-03180-vu-5849-jachymov-vrsek'],
			[true, 'https://vojensko.cz/objekty-csla/sekce-00031-kasarna-a-objekty-csla/polozka-03180-vu-5849-jachymov-vrsek'],
			[true, 'https://www.vojensko.cz/objekty-csla/sekce-00031-kasarna-a-objekty-csla/polozka-03180-vu-5849-jachymov-vrsek?image=7#detail-foto'],
			[true, 'https://www.vojensko.cz/objekty-ps/sekce-00061-zanikle-roty-5-bps/polozka-00107-pavlova-hut?image=5#detail-foto'],
			[true, 'https://www.vojensko.cz/objekty-csla/sekce-00031-kasarna-a-objekty-csla/polozka-03747-vu-beroun'],
			[true, 'https://www.vojensko.cz/objekty-pvos/sekce-00044-pozorovaci-hlasky/polozka-03260-ph-457-novosedly'],
			[true, 'https://www.vojensko.cz/objekty-pvos/sekce-00129-2-rtb-brno/polozka-02266-621-rtr-chropyne-souhrn-fotografii-utvaru'],
			[true, 'https://www.vojensko.cz/objekty-csla/sekce-00051-objekty-elektronicke-valky/polozka-00476-stanoviste-tisina'],
			[true, 'https://www.vojensko.cz/ruzne/sekce-00058-pristroje-nastroje-zbrane/polozka-04356-zavora-ippen-pavluv-studenec'],
			[true, 'https://www.vojensko.cz/dobove-foto/sekce-00057-dobove-foto-sla/polozka-05123-vu-1732-beroun-r-1973-75'],
			[true, 'https://www.vojensko.cz/objekty-pvos/sekce-00044-pozorovaci-hlasky/polozka-05500-demontaz-veze-vidove-hlasky-ph-254-kaplicky'],
			[true, 'https://www.vojensko.cz/objekty-csla/sekce-00031/polozka-03180'], // Valid, page will load

			// Valid, but no locations
			[true, 'https://www.vojensko.cz/dobove-foto/sekce-00055-dobove-foto-ps/polozka-01895-artolec-r-1980-82'],
			[true, 'https://www.vojensko.cz/dobove-foto/sekce-00057-dobove-foto-sla/polozka-05100-vu-1535-kromeriz-r-1965-67'],
			[true, 'https://www.vojensko.cz/ruzne/sekce-00149-knihy-o-ps-a-csla/polozka-04293-sumava-hranici-prechazejte-po-pulnoci'],

			[false, 'not url'],
			[false, 'https://www.vojensko.cz'],
			[false, 'https://www.vojensko.cz/'],
			[false, 'https://www.vojensko.cz/uvod'],
			[false, 'https://www.vojensko.cz/objekty-pvos/sekce-00044-pozorovaci-hlasky'], // Category
			[false, 'https://www.vojensko.cz/some-random-invalid-path'],
			[false, 'https://www.vojensko.cz/objekty-pvos'],
			[false, 'https://www.vojensko.cz/dobove-foto'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[50.375738, 12.863950, 'https://www.vojensko.cz/objekty-csla/sekce-00031-kasarna-a-objekty-csla/polozka-03180-vu-5849-jachymov-vrsek'],
			[50.375738, 12.863950, 'http://vojensko.cz/objekty-csla/sekce-00031-kasarna-a-objekty-csla/polozka-03180-vu-5849-jachymov-vrsek'],
			[50.375738, 12.863950, 'https://www.vojensko.cz/objekty-csla/sekce-00031/polozka-03180'],
			[50.375738, 12.863950, 'https://www.vojensko.cz/objekty-csla/sekce-00031-kasarna-a-objekty-csla/polozka-03180-vu-5849-jachymov-vrsek?image=7#detail-foto'],
			[49.869869, 12.534102, 'https://www.vojensko.cz/objekty-csla/sekce-00051-objekty-elektronicke-valky/polozka-00476-stanoviste-tisina'],
			[49.652981, 13.300206, 'https://www.vojensko.cz/objekty-pvos/sekce-00128-3-rtb-chomutov/polozka-03646-52-rtpr-dobrany-stod-vu-8060'],
		];
	}

	public static function processNoLocationProvider(): array
	{
		return [
			['https://www.vojensko.cz/objekty-pvos/sekce-00129-2-rtb-brno/polozka-02266-621-rtr-chropyne-souhrn-fotografii-utvaru'],
			// @TODO Valid, no location on this page, but it has linked page 'VÚ Beroun' that has location 49.967211,14.068781 (https://www.vojensko.cz/objekty-csla/sekce-00031-kasarna-a-objekty-csla/polozka-03747-vu-beroun)
			['https://www.vojensko.cz/dobove-foto/sekce-00057-dobove-foto-sla/polozka-05123-vu-1732-beroun-r-1973-75'],
			// @TODO Valid, no location on this page, but it has linked page 'PH 254 - Kapličky' that has location 49.967211,14.068781 (https://www.vojensko.cz/objekty-pvos/sekce-00044-pozorovaci-hlasky/polozka-00767-ph-254-kaplicky)
			['https://www.vojensko.cz/objekty-pvos/sekce-00044-pozorovaci-hlasky/polozka-05500-demontaz-veze-vidove-hlasky-ph-254-kaplicky'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new VojenskoCzService($this->httpTestClients->mockedRequestor, new MapyCzService());
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new VojenskoCzService($this->httpTestClients->realRequestor, new MapyCzService());
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new VojenskoCzService($this->httpTestClients->offlineRequestor, new MapyCzService());
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group request
	 * @dataProvider processNoLocationProvider
	 */
	public function testProcessNoLocationReal(string $input): void
	{
		$service = new VojenskoCzService($this->httpTestClients->realRequestor, new MapyCzService());
		$this->assertServiceNoLocation($service, $input);
	}

	/**
	 * @dataProvider processNoLocationProvider
	 */
	public function testProcessNoLocationOffline(string $input): void
	{
		$service = new VojenskoCzService($this->httpTestClients->offlineRequestor, new MapyCzService());
		$this->assertServiceNoLocation($service, $input);
	}
}
