<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\FevGamesService;
use App\BetterLocation\Service\IngressIntelService;

final class FevGamesServiceTest extends AbstractServiceTestCase
{
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

		$service = new FevGamesService($ingressClient, $ingressIntelService);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcess(float $expectedLat, float $expectedLon, string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client();
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group request
	 * @dataProvider processNotValidProvider
	 */
	public function testNoIntelLink(string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client();
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService);
		$this->assertServiceNoLocation($service, $input);
	}
}
