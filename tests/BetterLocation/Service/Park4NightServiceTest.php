<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Park4NightService;
use Tests\HttpTestClients;

final class Park4NightServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return Park4NightService::class;
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
			[true, 'https://park4night.com/en/place/12960'], // Typical web link
			[true, 'https://park4night.com/en/lieu/12960/open/'], // Typical share link from Android application
			[true, 'https://park4night.com/en/lieu/12960'],

			// Invalid
			[false, 'some invalid url'],
			[false, 'https://park4night.com/en/place/'],
			[false, 'https://park4night.com/en/place/ab'],
			[false, 'https://park4night.com/en/abcd/12960'],
			[false, 'https://park4night.com/en/lie/12960'],
			[false, 'https://park4night.com/en/plac/12960'],
			[false, 'https://park4night.com/en/lace/12960'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[
				50.092859,
				14.429902,
				'<a href="https://park4night.com/en/place/536148">park4night</a> - <a href="https://park4night.com/en/place/536148">(110 00) Petrská čtvrť - 7 Novomlýnská</a>',
				[
					'Quiet and safe place',
				],
				'https://park4night.com/en/place/536148',
			],
			[
				46.664101,
				11.1591,
				'<a href="https://park4night.com/en/place/12960">park4night</a> - <a href="https://park4night.com/en/place/12960">(39012) Campeggio di Merano | Live Merano Camping ****</a>',
				[
					'Well located campsite, very large pitches, mostly shaded. New and clean toilets. Very friendly welcome. Reopened after renovation on September 14, 2020. Open all year round.',
				],
				'https://park4night.com/en/place/12960',
			],
			[
				46.664101,
				11.1591,
				'<a href="https://park4night.com/en/lieu/12960/open/">park4night</a> - <a href="https://park4night.com/en/place/12960">(39012) Campeggio di Merano | Live Merano Camping ****</a>',
				[
					'Well located campsite, very large pitches, mostly shaded. New and clean toilets. Very friendly welcome. Reopened after renovation on September 14, 2020. Open all year round.',
				],
				'https://park4night.com/en/lieu/12960/open/',
			],
			[
				50.05665,
				14.41376,
				'<a href="https://park4night.com/en/place/539465">park4night</a> - <a href="https://park4night.com/en/place/539465">(150 00)  - 30 Císařská louka</a>',
				[
					// English language is not available
					'Parkeerplaats waar camperen gedoogd wordt. 30 kronen per uur maar er is geen betaalmogelijkheid of instructie hoe of waar dat te voldoen. En geen controle. Camping ernaast heeft leuk restaurant 2 lounge met uitzicht op de Moldau en zwemsteigertje.',
				],
				'https://park4night.com/en/place/539465',
			],
			[
				-32.880737,
				-54.448637,
				'<a href="https://park4night.com/en/place/525875">park4night</a> - <a href="https://park4night.com/en/place/525875">(33000) Posada El Capricho</a>',
				[
					'Camping of the Posada El Capricho, next to the entrance to the Quebrada de los Cuervos. It is a private, wild campsite, with little mobile signal. All the services. They allow pets, and that is what d&#8230',
				],
				'https://park4night.com/en/place/525875',
			],
			[
				42.555554,
				27.426904,
				'<a href="https://park4night.com/en/place/327177">park4night</a> - <a href="https://park4night.com/en/place/327177">() Burgas - E871</a>',
				[
					'Rompetrol station with a fountain that has a water tap. We filled a tank; no idea if it&#039;s good for drinking though.',
				],
				'https://park4night.com/en/place/327177',
			],
			[
				42.5322,
				27.46926,
				'<a href="https://park4night.com/en/place/434138">park4night</a> - <a href="https://park4night.com/en/place/434138">(8008) Burgas - 8000 булевард „Транспортна“</a>',
				[
					'Gas station with water tap. I do not know if you can drink it. Ask workers where to find the tap. It is behind by the building.',
				],
				'https://park4night.com/en/place/434138',
			],
			[ // Extra long description including newlines
				50.924343,
				13.855146,
				'<a href="https://park4night.com/en/place/186610">park4night</a> - <a href="https://park4night.com/en/place/186610">(01809) Stellplatz Abendruhe</a>',
				[
					'***We are open and look forward to the 2024 camping season***Please register by phone | SMS | WhatsApp.Please arrive by 9 p.m., departure is possible at any time.Our pitch is on private property - sec&#8230',
				],
				'https://park4night.com/en/place/186610',
			],
		];
	}

	/**
	 * Valid but does not exists.
	 */
	public static function processInvalidProvider(): array
	{
		return [
			['https://park4night.com/en/lieu/99999999'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new Park4NightService($this->httpTestClients->mockedRequestor);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
		if ($expectedIsValid === true) {
			$this->assertTrue(isset($service->getData()->placeId));
		}
	}

	/**
	 * @dataProvider processProvider
	 * @group request
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $expectedPrefix, array $expectedDescriptions, string $input): void
	{
		$service = new Park4NightService($this->httpTestClients->realRequestor);
		$this->testProcess($service, $expectedLat, $expectedLon, $expectedPrefix, $expectedDescriptions, $input);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $expectedPrefix, array $expectedDescriptions, string $input): void
	{
		$service = new Park4NightService($this->httpTestClients->offlineRequestor);
		$this->testProcess($service, $expectedLat, $expectedLon, $expectedPrefix, $expectedDescriptions, $input);
	}

	/**
	 * @dataProvider processInvalidProvider
	 * @group request
	 */
	public function testProcessInvalidReal(string $input): void
	{
		$service = new Park4NightService($this->httpTestClients->realRequestor);
		$this->assertServiceNoLocation($service, $input);
	}

	/**
	 * @dataProvider processInvalidProvider
	 */
	public function testProcessInvalidOffline(string $input): void
	{
		$service = new Park4NightService($this->httpTestClients->offlineRequestor);
		$this->assertServiceNoLocation($service, $input);
	}

	private function testProcess(
		Park4NightService $service,
		float $expectedLat,
		float $expectedLon,
		string $expectedPrefix,
		array $expectedDescriptions,
		string $input,
	): void {
		$location = $this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
		$descriptions = $location->getDescriptions();
		$this->assertSame($expectedPrefix, $location->getPrefixMessage());

		$this->assertCount(count($expectedDescriptions), $descriptions);

		foreach ($expectedDescriptions as $key => $expectedDescriptionText) {
			$this->assertSame($expectedDescriptionText, $descriptions[$key]->content);
		}
	}
}
