<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\BaladIrService;
use Tests\HttpTestClients;

final class BaladIrServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return BaladIrService::class;
	}

	public function getShareLinks(): array
	{
		return [
			'https://balad.ir/location?latitude=50.087451&longitude=14.420671',
			'https://balad.ir/location?latitude=50.1&longitude=14.5',
			'https://balad.ir/location?latitude=-50.2&longitude=14.6000001',
			'https://balad.ir/location?latitude=50.3&longitude=-14.7000009',
			'https://balad.ir/location?latitude=-50.4&longitude=-14.800008',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	/**
	 * @return array<array{bool, string}>
	 */
	public static function isValidProvider(): array
	{
		return [
			[true, 'https://balad.ir/location?latitude=50.087451&longitude=14.420671'],
			[true, 'https://balad.ir/location?latitude=50&longitude=14'],
			[true, 'https://balad.ir/location?latitude=-50.087451&longitude=-14.420671'],
			[true, 'https://balad.ir/location?latitude=35.826644&longitude=50.968268&zoom=16.500000#15/35.83347/50.95417'],
			[true, 'https://balad.ir/location?latitude=9999.826644&longitude=50.968268&zoom=16.500000#15/35.83347/50.95417'],
			[true, 'https://balad.ir/location?latitude=35.826644&longitude=50.968268&zoom=16.500000#15/999.83347/50.95417'],
			[true, 'https://balad.ir/#6.04/29.513/53.574'],
			[true, 'https://balad.ir/#6.04/29/53'],
			[true, 'https://balad.ir/#6/29.513/53.574'],
			// Place ID
			[true, 'https://balad.ir/p/405uvqx6JfALrs'],
			[true, 'https://balad.ir/p/3j08MFNHbCGvnu?preview=true'],
			[true, 'https://balad.ir/p/%DA%A9%D9%88%DB%8C-%D8%A2%DB%8C%D8%AA-%D8%A7%D9%84%D9%84%D9%87-%D8%BA%D9%81%D8%A7%D8%B1%DB%8C-bandar-abbas_residential-complex-3j08MFNHbCGvnu?preview=true'],

			[false, 'https://balad.ir/location?longitude=-14.420671'],
			[false, 'https://balad.ir/location?latitude=-99.087451&longitude=-14.420671'],
			[false, 'https://balad.ir/#6/99.513/53.574'],
			[false, 'https://different-domain.ir/location?latitude=-99.087451&longitude=-14.420671'],
			[false, 'https://balad.ir/location?latitude=99.826644&longitude=50.968268&zoom=16.500000#15/999.83347/50.95417'],
			[false, 'non url'],

			// @TODO add support for processing areas (/maps/xyz)
			[false, 'https://balad.ir/maps/qazvin?preview=true'],
			[false, 'https://balad.ir/maps/mashhad?preview=true'],
			[true, 'https://balad.ir/maps/mashhad?preview=true#11.03/36.3084/59.6476'], // map center is valid
		];
	}

	public static function processProvider(): array
	{
		return [
			// Coordinates in URL
			[35.826644, 50.968268, 'https://balad.ir/location?latitude=35.826644&longitude=50.968268&zoom=16.500000#15/35.83347/50.95417', BaladIrService::TYPE_PLACE_COORDS],
			[35.826644, 50.968268, 'https://balad.ir/location?latitude=35.826644&longitude=50.968268&zoom=16.5', BaladIrService::TYPE_PLACE_COORDS],

			// Map center
			[35.833470, 50.954170, 'https://balad.ir/location?latitude=35.826644&longitude=999.968268&zoom=16.500000#15/35.83347/50.95417', BaladIrService::TYPE_MAP_CENTER],
			[35.826644, 50.968268, 'https://balad.ir/#15/35.826644/50.968268', BaladIrService::TYPE_MAP_CENTER],

			// Place IDs
			[27.192955, 56.290765, 'https://balad.ir/p/3j08MFNHbCGvnu?preview=true', BaladIrService::TYPE_PLACE],
			[27.192955, 56.290765, 'https://balad.ir/p/%DA%A9%D9%88%DB%8C-%D8%A2%DB%8C%D8%AA-%D8%A7%D9%84%D9%84%D9%87-%D8%BA%D9%81%D8%A7%D8%B1%DB%8C-bandar-abbas_residential-complex-3j08MFNHbCGvnu?preview=true', BaladIrService::TYPE_PLACE],
			// Place ID is available, do not load map coordinates
			[27.192955, 56.290765, 'https://balad.ir/p/%DA%A9%D9%88%DB%8C-%D8%A2%DB%8C%D8%AA-%D8%A7%D9%84%D9%84%D9%87-%D8%BA%D9%81%D8%A7%D8%B1%DB%8C-bandar-abbas_residential-complex-3j08MFNHbCGvnu?preview=true#16.01/27.19771/56.287317', BaladIrService::TYPE_PLACE],
			// Place ID is not valid, load map coordinates instead
			[27.197710, 56.287317, 'https://balad.ir/p/blah-blah-abcd?preview=true#16.01/27.19771/56.287317', BaladIrService::TYPE_MAP_CENTER],
			[35.699738, 51.338060, 'https://balad.ir/p/405uvqx6JfALrs?preview=true', BaladIrService::TYPE_PLACE],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new BaladIrService($this->httpTestClients->mockedRequestor);
		$service->setInput($input);
		$isValid = $service->validate();
		$this->assertSame($expectedIsValid, $isValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $input, string $expectedSourceType): void
	{
		$service = new BaladIrService($this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon, $expectedSourceType);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $input, string $expectedSourceType): void
	{
		$service = new BaladIrService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon, $expectedSourceType);
	}
}
