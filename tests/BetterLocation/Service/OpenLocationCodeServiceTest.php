<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\OpenLocationCodeService;
use PHPUnit\Framework\TestCase;

final class OpenLocationCodeServiceTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateShareLink(): void
	{
		$this->assertEquals('https://plus.codes/9F2P3CPC+X7M3', OpenLocationCodeService::getLink(50.087451, 14.420671));
		$this->assertEquals('https://plus.codes/9F2P3GX2+X2XX', OpenLocationCodeService::getLink(50.1, 14.5));
		$this->assertEquals('https://plus.codes/3FXPQJX2+X2RR', OpenLocationCodeService::getLink(-50.2, 14.6000001)); // round down
		$this->assertEquals('https://plus.codes/9C27872X+2X55', OpenLocationCodeService::getLink(50.3, -14.7000009)); // round up
		$this->assertEquals('https://plus.codes/3CX7J52X+2X54', OpenLocationCodeService::getLink(-50.4, -14.800008));
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		OpenLocationCodeService::getLink(50.087451, 14.420671, true);
	}

}
