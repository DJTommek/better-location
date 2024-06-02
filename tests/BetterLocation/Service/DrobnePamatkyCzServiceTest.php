<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\DrobnePamatkyCzService;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use Tests\HttpTestClients;

final class DrobnePamatkyCzServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return DrobnePamatkyCzService::class;
	}

	protected function getShareLinks(): array
	{
		$this->revalidateGeneratedShareLink = false;

		return [
			'https://www.drobnepamatky.cz/blizko?km[latitude]=50.087451&km[longitude]=14.420671&km[search_distance]=5&km[search_units]=km',
			'https://www.drobnepamatky.cz/blizko?km[latitude]=50.100000&km[longitude]=14.500000&km[search_distance]=5&km[search_units]=km',
			'https://www.drobnepamatky.cz/blizko?km[latitude]=-50.200000&km[longitude]=14.600000&km[search_distance]=5&km[search_units]=km',
			'https://www.drobnepamatky.cz/blizko?km[latitude]=50.300000&km[longitude]=-14.700001&km[search_distance]=5&km[search_units]=km',
			'https://www.drobnepamatky.cz/blizko?km[latitude]=-50.400000&km[longitude]=-14.800008&km[search_distance]=5&km[search_units]=km',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function isValidProvider(): array
	{
		return [
			[true, 'https://www.drobnepamatky.cz/node/36966'],
			[true, 'http://www.drobnepamatky.cz/node/36966'],
			[true, 'https://drobnepamatky.cz/node/36966'],
			[true, 'http://drobnepamatky.cz/node/36966'],

			[false, 'some invalid url'],
			[false, 'https://www.drobnepamatky.cz/'],
			[false, 'https://www.drobnepamatky.cz/node/'],
			[false, 'https://www.drobnepamatky.cz/node/abc'],
			[false, 'https://www.drobnepamatky.cz/node/123abc'],
			[false, 'https://www.drobnepamatky.cz/node/abc123'],
			[false, 'https://www.drobnepamatky.cz/node/123aaa456'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[50.067698, 14.401455, 'https://www.drobnepamatky.cz/node/36966'],
			[49.854263, 18.542156, 'https://www.drobnepamatky.cz/node/9279'],
			[49.805000, 18.449748, 'https://www.drobnepamatky.cz/node/9282'],
			// Oborané památky (https://www.drobnepamatky.cz/oborane)
			[49.687425, 14.712345, 'https://www.drobnepamatky.cz/node/10646'],
			[48.974158, 14.612296, 'https://www.drobnepamatky.cz/node/2892'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new DrobnePamatkyCzService($this->httpTestClients->mockedRequestor);
		$service->setInput($input);
		$isValid = $service->validate();
		$this->assertSame($expectedIsValid, $isValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new DrobnePamatkyCzService($this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new DrobnePamatkyCzService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group request
	 */
	public function testMissingCoordinatesReal(): void
	{
		$service = new DrobnePamatkyCzService($this->httpTestClients->realRequestor);
		$service->setInput('https://www.drobnepamatky.cz/node/9999999');
		$this->assertTrue($service->validate());

		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Coordinates on DrobnePamatky.cz page are missing.');

		$service->process();
	}

	/**
	 * @group request
	 */
	public function testMissingCoordinatesOffline(): void
	{
		$service = new DrobnePamatkyCzService($this->httpTestClients->offlineRequestor);
		$service->setInput('https://www.drobnepamatky.cz/node/9999999');
		$this->assertTrue($service->validate());

		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Coordinates on DrobnePamatky.cz page are missing.');

		$service->process();
	}
}
