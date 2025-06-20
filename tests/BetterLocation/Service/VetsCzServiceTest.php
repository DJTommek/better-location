<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\VetsCzService;
use Tests\HttpTestClients;

final class VetsCzServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return VetsCzService::class;
	}

	protected function getShareLinks(): array
	{
		return [
			'https://www.vets.cz/vpm/mapa/?lat=50.087451&lon=14.420671',
			'https://www.vets.cz/vpm/mapa/?lat=50.100000&lon=14.500000',
			'https://www.vets.cz/vpm/mapa/?lat=-50.200000&lon=14.600000', // round down
			'https://www.vets.cz/vpm/mapa/?lat=50.300000&lon=-14.700001', // round up
			'https://www.vets.cz/vpm/mapa/?lat=-50.400000&lon=-14.800008',
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
			// Maps
			[true, 'https://www.vets.cz/vpm/mapa/?lat=50.04418&lon=14.45979'],
			[true, 'https://vets.cz/vpm/mapa?lon=14.45979&lat=50.04418'],
			[true, 'https://www.vets.cz/vpm/mapa/?lat=25.07823&lon=-77.33834'],
			[true, 'https://www.vets.cz/vpm/mapa/?lat=-26.792778&lon=-60.436111'],

			// Maps - Invalid or missing coordinates
			[false, 'https://www.vets.cz/vpm/mapa/'],
			[false, 'https://www.vets.cz/vpm/mapa/?lat=aaaa&lon=14.45979'],
			[false, 'https://www.vets.cz/vpm/mapa/?lat=50.04418&lon=aaa'],

			// Places
			[true, 'https://www.vets.cz/vpm/54842-pametni-deska-vaclav-kratochvil/#54842-pametni-deska-vaclav-kratochvil'],
			[true, 'https://www.vets.cz/vpm/54842-pametni-deska-vaclav-kratochvil/#something-random'],
			[true, 'https://www.vets.cz/vpm/54842-pametni-deska-vaclav-kratochvil'],
			[true, 'https://www.vets.cz/vpm/54842-a/'], // minimalistic version
			[true, 'https://www.vets.cz/vpm/38546-hrob-jan-dobias/#38546-hrob-jan-dobias'],
			[true, 'https://www.vets.cz/vpm/38546-hrob-jan-dobias'],
			[true, 'https://www.vets.cz/vpm/4707-pametni-deska-josef-balcar/#4707-pametni-deska-josef-balcar'],
			[true, 'https://www.vets.cz/vpm/999999-foo-bar'], // Valid but non-existing place ID

			// @TODO valid format, but not supported by this service, yet
			[false, 'https://www.vets.cz/vpm/badatelna/single/24453/'],
			[false, 'https://www.vets.cz/vpm/ostatni/single/55593/'],

			// @TODO valid format, but might contain multiple points and each point must be scraped separately
			[false, 'https://www.vets.cz/vpm/oskar-izrael-adler-1082/'], // contains only one point
			// |-> https://www.vets.cz/vpm/16063-pamatnik-2-svetove-valky/#16063-pamatnik-2-svetove-valky
			[false, 'https://www.vets.cz/vpm/ivan-nikolaj-beca-2993/'], // contains multiple points
			// |-> https://www.vets.cz/vpm/15966-hrob-obeti-2-svetove-valky/#15966-hrob-obeti-2-svetove-valky
			// |-> https://www.vets.cz/vpm/16063-pamatnik-2-svetove-valky/#16063-pamatnik-2-svetove-valky

			[false, 'non url'],
			[false, 'https://example.com/?ll=50.087451,14.420671'],
			[false, 'https://www.vets.cz'],
			[false, 'https://www.vets.cz/vpm/mista/obec/3912-roudnice-nad-labem/'],
			[false, 'https://www.vets.cz/vpm/mista/obec/1006-praha-4/'],
		];
	}

	public static function processPlaceProvider(): array
	{
		$result1 = [
			[
				50.421639,
				14.261083,
				VetsCzService::TYPE_PLACE,
				'<a href="https://www.vets.cz/vpm/54842-pametni-deska-vaclav-kratochvil/#54842-pametni-deska-vaclav-kratochvil">vets.cz Pamětní deska Václav Kratochvíl</a>',
			],
		];
		$result2 = [
			[
				50.04418,
				14.45979,
				VetsCzService::TYPE_PLACE,
				'<a href="https://www.vets.cz/vpm/4707-pametni-deska-josef-balcar/#4707-pametni-deska-josef-balcar">vets.cz Pamětní deska Josef Balcar</a>',
			],
		];

		return [
			[$result1, 'https://www.vets.cz/vpm/54842-pametni-deska-vaclav-kratochvil/#54842-pametni-deska-vaclav-kratochvil'],
			[$result1, 'https://www.vets.cz/vpm/54842-pametni-deska-vaclav-kratochvil'],
			[$result1, 'https://www.vets.cz/vpm/54842-a'],
			[$result2, 'https://www.vets.cz/vpm/4707-pametni-deska-josef-balcar/#4707-pametni-deska-josef-balcar'],
			[$result2, 'https://www.vets.cz/vpm/4707-pametni-deska-josef-balcar/#something-random'],
			[$result2, 'https://www.vets.cz/vpm/4707-pametni-deska-josef-balcar'],
			[$result2, 'https://www.vets.cz/vpm/4707-z'],
			[
				[
					[
						50.42686,
						14.24216,
						VetsCzService::TYPE_PLACE,
						'<a href="https://www.vets.cz/vpm/38546-hrob-jan-dobias/#38546-hrob-jan-dobias">vets.cz Hrob Jan Dobiáš</a>',
					],
				],
				'https://www.vets.cz/vpm/38546-hrob-jan-dobias',
			],
			[
				[
					[
						25.07823,
						-77.33834,
						VetsCzService::TYPE_PLACE,
						'<a href="https://www.vets.cz/vpm/35613-hrob-jaroslav-mares/#35613-hrob-jaroslav-mares">vets.cz Hrob Jaroslav Mareš</a>',
					],
				],
				'https://www.vets.cz/vpm/35613-hrob-jaroslav-mares/#35613-hrob-jaroslav-mares',
			],
			[
				[
					[
						-26.792778,
						-60.436111,
						VetsCzService::TYPE_PLACE,
						'<a href="https://www.vets.cz/vpm/44193-pamatnik-m-r-stefanik-a-t-g-masaryk/#44193-pamatnik-m-r-stefanik-a-t-g-masaryk">vets.cz Památník M. R. Štefánik a T. G. Masaryk</a>',
					],
				],
				'https://www.vets.cz/vpm/44193-pamatnik-m-r-stefanik-a-t-g-masaryk/#44193-pamatnik-m-r-stefanik-a-t-g-masaryk',
			],
		];
	}

	public static function processMapProvider(): array
	{
		return [
			[
				[
					[
						50.04418,
						14.45979,
						VetsCzService::TYPE_MAP,
						'<a href="https://www.vets.cz/vpm/mapa/?lat=50.04418&lon=14.45979">vets.cz map</a>',
					],
				],
				'https://www.vets.cz/vpm/mapa/?lat=50.04418&lon=14.45979',
			],
			[
				[
					[
						-26.792778,
						-60.436111,
						VetsCzService::TYPE_MAP,
						'<a href="https://www.vets.cz/vpm/mapa/?lat=-26.792778&lon=-60.436111">vets.cz map</a>',
					],
				],
				'https://www.vets.cz/vpm/mapa/?lat=-26.792778&lon=-60.436111',
			],
		];
	}

	public static function processInvalidProvider(): array
	{
		return [
			['https://www.vets.cz/vpm/999999-foo-bar'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new VetsCzService($this->httpTestClients->mockedRequestor);
		$service->setInput($input);
		$isValid = $service->validate();
		$this->assertSame($expectedIsValid, $isValid);
	}

	/**
	 * @dataProvider processPlaceProvider
	 * @group request
	 */
	public function testProcessReal(array $expectedResults, string $input): void
	{
		$service = new VetsCzService($this->httpTestClients->realRequestor);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * @dataProvider processPlaceProvider
	 */
	public function testProcessOffline(array $expectedResults, string $input): void
	{
		$service = new VetsCzService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * @dataProvider processMapProvider
	 */
	public function testProcessMocked(array $expectedResults, string $input): void
	{
		$service = new VetsCzService($this->httpTestClients->mockedRequestor);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * @dataProvider processInvalidProvider
	 * @group request
	 */
	public function testProcessInvalidReal(string $input): void
	{
		$service = new VetsCzService($this->httpTestClients->realRequestor);
		$this->assertServiceNoLocation($service, $input);
	}

	/**
	 * @dataProvider processInvalidProvider
	 */
	public function testProcessInvalidOffline(string $input): void
	{
		$service = new VetsCzService($this->httpTestClients->offlineRequestor);
		$this->assertServiceNoLocation($service, $input);
	}
}
