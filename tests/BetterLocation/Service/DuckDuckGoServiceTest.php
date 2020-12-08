<?php declare(strict_types=1);

use App\BetterLocation\Service\DuckDuckGoService;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use PHPUnit\Framework\TestCase;

final class DuckDuckGoServiceTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://duckduckgo.com/?q=50.087451,14.420671&iaxm=maps', DuckDuckGoService::getLink(50.087451, 14.420671));
		$this->assertSame('https://duckduckgo.com/?q=50.100000,14.500000&iaxm=maps', DuckDuckGoService::getLink(50.1, 14.5));
		$this->assertSame('https://duckduckgo.com/?q=-50.200000,14.600000&iaxm=maps', DuckDuckGoService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://duckduckgo.com/?q=50.300000,-14.700001&iaxm=maps', DuckDuckGoService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://duckduckgo.com/?q=-50.400000,-14.800008&iaxm=maps', DuckDuckGoService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotImplementedException::class);
		$this->expectExceptionMessage('Drive link is not implemented.');
		DuckDuckGoService::getLink(50.087451, 14.420671, true);
	}

}
