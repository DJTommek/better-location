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
}
