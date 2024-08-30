<?php declare(strict_types=1);

namespace Tests\BetterLocation;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\ProcessExample;
use App\BetterLocation\Service\WazeService;
use PHPUnit\Framework\TestCase;
use Tests\HttpTestClients;
use unreal4u\TelegramAPI\Telegram;

final class ProcessExampleTest extends TestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	public function testBasic(): void
	{
		$wazeService = new WazeService($this->httpTestClients->mockedRequestor);
		$processExample = new ProcessExample($wazeService);
		$this->assertInstanceOf(BetterLocation::class, $processExample->getExampleLocation());
		$this->assertSame(ProcessExample::LAT, $processExample->getLat());
		$this->assertSame(ProcessExample::LON, $processExample->getLon());
	}
}
