<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\AirbnbService;
use Tests\HttpTestClients;

final class AirbnbServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return AirbnbService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
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
			[true, 'https://www.airbnb.cz/rooms/16958918'],
			[true, 'https://airbnb.com/rooms/16958918'],
			[true, 'https://www.airbnb.com/rooms/123'],
			[true, 'https://www.airbnb.com/rooms/123/abc'],
			[true, 'https://www.airbnb.com/rooms/123?something'],
			// Real example
			[true, 'https://www.airbnb.com/rooms/16958918?adults=8&check_in=2024-03-14&check_out=2024-03-17&source_impression_id=p3_1707752790_J1JOXEiZQ5zIywtZ&previous_page_section_name=1000&federated_search_id=db0802cc-b8b1-4e05-8df3-4874a286c728'],
			// Various domains
			[true, 'https://www.airbnb.cz/rooms/16958918'],
			[true, 'https://www.airbnb.si/rooms/16958918'],
			[true, 'https://www.airbnb.com.py/rooms/16958918'],
			[true, 'https://www.airbnb.co.nz/rooms/16958918'],
			[true, 'https://www.airbnb.co.za/rooms/16958918'],

			[false, 'non url'],
			[false, 'https://www.airbnb.com/'],
			[false, 'https://www.airbnb.com/rooms'],
			[false, 'https://www.airbnb.com/rooms/123abcd'],
			[false, 'https://www.airbnb.com/rooms/abcd123'],
			[false, 'https://www.airbnb.com/rooms/abcd'],
			[false, 'https://www.airbnb.com/rooms/-5693'],
			// Invalid domains
			[false, 'https://www.airbnb.bla.co/rooms/5693'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[37.36178, -122.40566, 'https://www.airbnb.com/rooms/731059780954437660'],
			[-8.51964, 115.27551, 'https://www.airbnb.com/rooms/47721565'],
			[47.127026, 10.96707, 'https://www.airbnb.cz/rooms/16958918'],
			[52.237109251604, 14.029602373175, 'https://www.airbnb.com/rooms/53318530'],
			[52.237109251604, 14.029602373175, 'https://www.airbnb.com/rooms/53318530?adults=1&category_tag=Tag%3A8522&children=0&enable_m3_private_room=true&infants=0&pets=0&photo_id=1314700868&search_mode=flex_destinations_search&check_in=2024-03-17&check_out=2024-03-22&source_impression_id=p3_1707928709_kJTXrpfuz%2FWpl67h&previous_page_section_name=1000'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedValid, string $link): void
	{
		$service = new AirbnbService($this->httpTestClients->mockedRequestor);
		$realValid = $service->setInput($link)->validate();
		$this->assertSame($expectedValid, $realValid);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new AirbnbService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new AirbnbService($this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}
}
