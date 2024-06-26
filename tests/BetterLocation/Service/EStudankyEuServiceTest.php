<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\EStudankyEuService;
use App\BetterLocation\Service\MapyCzService;
use DJTommek\MapyCzApi\MapyCzApi;
use Tests\HttpTestClients;

final class EStudankyEuServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;
	private readonly MapyCzService $mapyCzServiceMocked;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
		$this->mapyCzServiceMocked = new MapyCzService(
			$this->httpTestClients->mockedRequestor,
			(new MapyCzApi)->setClient($this->httpTestClients->mockedHttpClient),
		);
	}

	protected function getServiceClass(): string
	{
		return EStudankyEuService::class;
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
			[true, 'https://estudanky.eu/3762-studanka-kinska'],
			[true, 'http://estudanky.eu/3762-studanka-kinska'],
			[true, 'https://www.estudanky.eu/3762-studanka-kinska'],
			[true, 'https://www.estudanky.eu/3762'],
			[true, 'https://www.estudanky.eu/3762-'],

			// Invalid
			[false, 'some invalid url'],
			[false, 'https://estudanky.eu/nepristupne-cislo-zpet-strana-1'],
			[false, 'https://estudanky.eu/kraj-B-cislo-strana-1'],
			[false, 'https://estudanky.eu/zachranme-studanky'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[50.078999, 14.400600, 'https://estudanky.eu/3762-studanka-kinska'],
			[50.068591, 14.420468, 'https://estudanky.eu/10596-studna-bez-jmena'],
			[49.517083, 18.729550, 'https://estudanky.eu/4848'],
		];
	}

	public static function processInvalidIdProvider(): array
	{
		return [
			['https://estudanky.eu/999999999'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new EStudankyEuService($this->httpTestClients->mockedRequestor, $this->mapyCzServiceMocked);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new EStudankyEuService($this->httpTestClients->realRequestor, $this->mapyCzServiceMocked);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new EStudankyEuService($this->httpTestClients->offlineRequestor, $this->mapyCzServiceMocked);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group request
	 * @dataProvider processInvalidIdProvider
	 */
	public function testInvalidIdReal(string $input): void
	{
		$service = new EStudankyEuService($this->httpTestClients->realRequestor, $this->mapyCzServiceMocked);
		$this->assertServiceNoLocation($service, $input);
	}

	/**
	 * @dataProvider processInvalidIdProvider
	 */
	public function testInvalidIdOffline(string $input): void
	{
		$service = new EStudankyEuService($this->httpTestClients->offlineRequestor, $this->mapyCzServiceMocked);
		$this->assertServiceNoLocation($service, $input);
	}
}
