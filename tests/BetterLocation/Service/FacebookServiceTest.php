<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\FacebookService;
use Tests\HttpTestClients;

final class FacebookServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return FacebookService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function isValidUrlProvider(): array
	{
		return [
			[true, 'https://facebook.com/burgerzelva'],
			[true, 'http://facebook.com/burgerzelva'],
			[true, 'http://www.facebook.com/burgerzelva'],
			[true, 'https://www.facebook.com/burgerzelva'],
			[true, 'https://facebook.com/burgerzelva/'],
			[true, 'https://facebook.com/burgerzelva/menu'],
			[true, 'https://facebook.com/burgerzelva/menu/?ref=page_internal'],
			[true, 'https://facebook.com/burgerzelva?ref=page_internal'],
			[true, 'https://m.facebook.com/burgerzelva'],
			[true, 'https://pt-br.facebook.com/burgerzelva'],
			[true, 'https://m.facebook.com/gentlegiantcafex/'],
			[true, 'https://pt-br.facebook.com/fantaziecafe/'],
			[true, 'https://www.facebook.com/FlotaVacaDiezSCZ/'],
			[true, 'https://www.facebook.com/Bodegas-Alfaro-730504807012751/'],
			[true, 'https://www.facebook.com/Biggie-Express-251025431718109/about/?ref=page_internal'],

			[false, 'https://facebook.com/'],
			[false, 'https://facebook.com'],
			[false, 'https://facebook.com?foo=bar'],

			[false, 'some invalid url'],
		];
	}

	public static function processUrlProvider(): array
	{
		return [
			[50.087244, 14.469230, 'https://pt-br.facebook.com/burgerzelva/menu/?ref=page_internal'],
			[50.061790, 14.437030, 'https://pt-br.facebook.com/fantaziecafe/'],
			[40.411600, -3.700390, 'https://www.facebook.com/Bodegas-Alfaro-730504807012751/'],
			[-43.538899, 172.652603, 'https://m.facebook.com/gentlegiantcafex/'],
			[-25.285736, -57.559743, 'https://www.facebook.com/Biggie-Express-251025431718109/about/?ref=page_internal'],
			[-17.792721, -63.155202, 'https://www.facebook.com/FlotaVacaDiezSCZ/'],
		];
	}

	public static function processUrlNoLocationProvider(): array
	{
		return [
			['https://www.facebook.com/ThePokeHaus'],
		];
	}

	/**
	 * @dataProvider isValidUrlProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new FacebookService($this->httpTestClients->mockedRequestor);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 *
	 * @dataProvider processUrlProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $input): void
	{
		$this->markTestSkipped('Scraping is currently not working.');

		$service = new FacebookService($this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @dataProvider processUrlProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $input): void
	{
		$this->markTestSkipped('Data for offline tests are not available, fix $this->>testProcessReal() first.');

		$service = new FacebookService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group request
	 *
	 * @dataProvider processUrlNoLocationProvider
	 */
	public function testProcessNoLocationReal(string $input): void
	{
		$this->markTestSkipped('Scraping is currently not working.');

		$service = new FacebookService($this->httpTestClients->realRequestor);
		$this->assertServiceNoLocation($service, $input);
	}

	/**
	 * @group request
	 *
	 * @dataProvider processUrlNoLocationProvider
	 */
	public function testProcessNoLocationOffline(string $input): void
	{
		$this->markTestSkipped('Data for offline tests are not available, fix $this->>testProcessNoLocationReal() first.');

		$service = new FacebookService($this->httpTestClients->offlineRequestor);
		$this->assertServiceNoLocation($service, $input);
	}
}
