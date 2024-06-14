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
			[[[-37.815226, 144.963781]], 'https://fevgames.net/ifs/event/?e=23448'],

			[
				[
					[40.696302, 14.481354],
					[40.699493, 14.482077],
				],
				'https://fevgames.net/ifs/event/?e=27094',
			],
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
		$ingressClient = new \App\IngressLanchedRu\Client($this->httpTestClients->mockedRequestor);
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->mockedRequestor);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessReal(array $expectedResults, string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client($this->httpTestClients->realRequestor);
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->realRequestor);
		$this->testProcess($service, $expectedResults, $input);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(array $expectedResults, string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client($this->httpTestClients->offlineRequestor);
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->offlineRequestor);
		$this->testProcess($service, $expectedResults, $input);
	}

	private function testProcess(FevGamesService $service, array $expectedResults, string $input): void
	{
		$service->setInput($input);
		$this->assertTrue($service->validate());
		$service->process();

		$collection = $service->getCollection();
		$this->assertCount(count($expectedResults), $collection);
		foreach ($expectedResults as $key => $expectedResult) {
			[$expectedLat, $expectedLon] = $expectedResult;
			$location = $collection[$key];
			$this->assertSame($expectedLat, $location->getLat());
			$this->assertSame($expectedLon, $location->getLon());
		}
	}

	/**
	 * @group request
	 * @dataProvider processNotValidProvider
	 */
	public function testNoIntelLinkReal(string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client($this->httpTestClients->mockedRequestor);
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->realRequestor);
		$this->assertServiceNoLocation($service, $input);
	}

	/**
	 * @dataProvider processNotValidProvider
	 */
	public function testNoIntelLinkOffline(string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client($this->httpTestClients->mockedRequestor);
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->offlineRequestor);
		$this->assertServiceNoLocation($service, $input);
	}
}
