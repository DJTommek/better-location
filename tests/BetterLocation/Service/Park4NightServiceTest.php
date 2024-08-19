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
				[],
				'https://park4night.com/en/place/536148',
			],
			[
				46.664101,
				11.1591,
				'<a href="https://park4night.com/en/place/12960">park4night</a> - <a href="https://park4night.com/en/place/12960">(39012) Campeggio di Merano | Live Merano Camping ****</a>',
				[],
				'https://park4night.com/en/place/12960',
			],
			[
				46.664101,
				11.1591,
				'<a href="https://park4night.com/en/lieu/12960/open/">park4night</a> - <a href="https://park4night.com/en/place/12960">(39012) Campeggio di Merano | Live Merano Camping ****</a>',
				[],
				'https://park4night.com/en/lieu/12960/open/',
			],
			[
				50.05665,
				14.41376,
				'<a href="https://park4night.com/en/place/539465">park4night</a> - <a href="https://park4night.com/en/place/539465">(150 00)  - 30 Císařská louka</a>',
				[],
				'https://park4night.com/en/place/539465',
			],
			[
				-32.880737,
				-54.448637,
				'<a href="https://park4night.com/en/place/525875">park4night</a> - <a href="https://park4night.com/en/place/525875">(33000) Posada El Capricho</a>',
				[],
				'https://park4night.com/en/place/525875',
			],
			[
				42.555554,
				27.426904,
				'<a href="https://park4night.com/en/place/327177">park4night</a> - <a href="https://park4night.com/en/place/327177">() Burgas - E871</a>',
				[],
				'https://park4night.com/en/place/327177',
			],
			[
				42.5322,
				27.46926,
				'<a href="https://park4night.com/en/place/434138">park4night</a> - <a href="https://park4night.com/en/place/434138">(8008) Burgas - 8000 булевард „Транспортна“</a>',
				[],
				'https://park4night.com/en/place/434138',
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
