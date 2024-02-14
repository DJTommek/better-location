<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\AirbnbService;

final class AirbnbServiceTest extends AbstractServiceTestCase
{
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

	public function testIsValid(): void
	{
		$this->assertTrue(AirbnbService::isValidStatic('https://www.airbnb.cz/rooms/16958918?adults=8&check_in=2024-03-14&check_out=2024-03-17&source_impression_id=p3_1707752790_J1JOXEiZQ5zIywtZ&previous_page_section_name=1000&federated_search_id=db0802cc-b8b1-4e05-8df3-4874a286c728'));
		$this->assertTrue(AirbnbService::isValidStatic('https://www.airbnb.cz/rooms/123'));

		$this->assertFalse(AirbnbService::isValidStatic('https://www.airbnb.cz/'));
		$this->assertFalse(AirbnbService::isValidStatic('https://www.airbnb.com/'));
		$this->assertFalse(AirbnbService::isValidStatic('https://www.airbnb.cz/rooms/123abc'));
		$this->assertFalse(AirbnbService::isValidStatic('non url'));
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValidUsingProvider(bool $expectedIsValid, string $link): void
	{
		$this->assertSame($expectedIsValid, AirbnbService::isValidStatic($link));
	}

	/**
	 * @return array<array{bool, string}>
	 */
	public static function isValidProvider(): array
	{
		return [
			[true, 'https://www.airbnb.cz/rooms/16958918'],
			[true, 'https://airbnb.cz/rooms/16958918'],
			[true, 'https://www.airbnb.com/rooms/123'],
			[true, 'https://www.airbnb.com/rooms/123/abc'],
			[true, 'https://www.airbnb.com/rooms/123?something'],
			[true, 'https://www.airbnb.cz/rooms/16958918?adults=8&check_in=2024-03-14&check_out=2024-03-17&source_impression_id=p3_1707752790_J1JOXEiZQ5zIywtZ&previous_page_section_name=1000&federated_search_id=db0802cc-b8b1-4e05-8df3-4874a286c728'],

			[false, 'non url'],
			[false, 'https://www.airbnb.cz/'],
			[false, 'https://www.airbnb.cz/rooms'],
			[false, 'https://www.airbnb.cz/rooms/123abcd'],
			[false, 'https://www.airbnb.cz/rooms/abcd123'],
			[false, 'https://www.airbnb.cz/rooms/abcd'],
			[false, 'https://www.airbnb.cz/rooms/-5693'],
		];
	}

	/**
	 * @group request
	 */
	public function testProcess(): void
	{
		$this->assertLocation('https://www.airbnb.com/rooms/731059780954437660', 37.36178, -122.40566);
		$this->assertLocation('https://www.airbnb.com/rooms/47721565', -8.51964, 115.27551);
		$this->assertLocation('https://www.airbnb.cz/rooms/16958918', 47.127026, 10.96707);
		$this->assertLocation('https://www.airbnb.com/rooms/53318530', 52.21932, 14.01817);
		$this->assertLocation(
			'https://www.airbnb.com/rooms/53318530?adults=1&category_tag=Tag%3A8522&children=0&enable_m3_private_room=true&infants=0&pets=0&photo_id=1314700868&search_mode=flex_destinations_search&check_in=2024-03-17&check_out=2024-03-22&source_impression_id=p3_1707928709_kJTXrpfuz%2FWpl67h&previous_page_section_name=1000',
			52.21932,
			14.01817,
		);
	}
}
