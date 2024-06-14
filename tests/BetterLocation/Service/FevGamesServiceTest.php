<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\FevGamesService;
use App\BetterLocation\Service\IngressIntelService;
use Tests\HttpTestClients;

final class FevGamesServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return FevGamesService::class;
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
			[true, 'https://fevgames.net/ifs/event/?e=15677'],
			[true, 'https://FEVgaMEs.net/ifs/event/?e=15677'],
			[true, 'http://fevgames.net/ifs/event/?e=15677'],
			[true, 'http://www.fevgames.net/ifs/event/?e=15677'],
			[true, 'https://www.fevgames.net/ifs/event/?e=15677'],
			[true, 'https://fevgames.net/ifs/event/?e=12342'],

			[false, 'non url'],
			[false, 'https://blabla.net/ifs/event/?e=15677'],
			[false, 'https://fevgames.net/ifs/event/'],
			[false, 'https://fevgames.net/ifs/event/?e=-15677'],
			[false, 'https://fevgames.net/ifs/event/?e=0'],
			[false, 'https://fevgames.cz/ifs/event/?e=15677'],
			[false, 'https://fevgames.net?e=15677'],
			[false, 'https://fevgames.net/ifs/event/?event=15677'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[-37.815226, 144.963781, 'https://fevgames.net/ifs/event/?e=23448'],
		];
	}

	public static function processNotValidProvider(): array
	{
		return [
			['https://fevgames.net/ifs/event/?e=12342'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client();
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->mockedRequestor);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client();
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client();
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->offlineRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group request
	 * @dataProvider processNotValidProvider
	 */
	public function testNoIntelLinkReal(string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client();
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->realRequestor);
		$this->assertServiceNoLocation($service, $input);
	}

	/**
	 * @dataProvider processNotValidProvider
	 */
	public function testNoIntelLinkOffline(string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client();
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->offlineRequestor);
		$this->assertServiceNoLocation($service, $input);
	}
}
