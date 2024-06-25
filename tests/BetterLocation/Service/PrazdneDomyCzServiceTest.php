<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\MapyCzService;
use App\BetterLocation\Service\PrazdneDomyCzService;
use Tests\HttpTestClients;

final class PrazdneDomyCzServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return PrazdneDomyCzService::class;
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
			[true, 'https://prazdnedomy.cz/domy/objekty/detail/2732-kasarna-u-sloupu'],
			[true, 'https://prazdnedomy.cz/domy/objekty/detail/96-dum-u-tri-bilych-lilii'],
			[true, 'https://prazdnedomy.cz/domy/objekty/detail/96'],
			[true, 'https://www.prazdnedomy.cz/domy/objekty/detail/96'],
			[true, 'http://www.prazdnedomy.cz/domy/objekty/detail/96'],

			[false, 'some invalid url'],
			[false, 'https://prazdnedomy.cz/domy/objekty/detail/kasarna-u-sloupu'],
			[false, 'https://prazdnedomy.cz/clanky/'],
			[false, 'https://prazdnedomy.cz/clanky/prazdne-domy-na-vedlejsi-koleji-aneb-prazdna-nadrazi-jako-prilezitost/'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[49.060201, 13.736212, 'https://prazdnedomy.cz/domy/objekty/detail/2732-kasarna-u-sloupu'],
			[50.087720, 14.398980, 'https://prazdnedomy.cz/domy/objekty/detail/96-dum-u-tri-bilych-lilii'],
		];
	}

	public static function processInvalidIdProvider(): array
	{
		return [
			['https://prazdnedomy.cz/domy/objekty/detail/999999999'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new PrazdneDomyCzService($this->httpTestClients->mockedRequestor, new MapyCzService());
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new PrazdneDomyCzService($this->httpTestClients->realRequestor, new MapyCzService());
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new PrazdneDomyCzService($this->httpTestClients->offlineRequestor, new MapyCzService());
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group request
	 * @dataProvider processInvalidIdProvider
	 */
	public function testInvalidIdReal(string $input): void
	{
		$service = new PrazdneDomyCzService($this->httpTestClients->realRequestor, new MapyCzService());
		$this->assertServiceNoLocation($service, $input);
	}

	/**
	 * @dataProvider processInvalidIdProvider
	 */
	public function testInvalidIdOffline(string $input): void
	{
		$service = new PrazdneDomyCzService($this->httpTestClients->offlineRequestor, new MapyCzService());
		$this->assertServiceNoLocation($service, $input);
	}
}
