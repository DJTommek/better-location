<?php declare(strict_types=1);

namespace BetterLocation\Service\CoordinatesCompact;

use App\BetterLocation\Service\CoordinatesRender\WGS84DegreeCompactService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

final class WGS84DegreesServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		WGS84DegreeCompactService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		WGS84DegreeCompactService::getLink(50.087451, 14.420671, true);
	}

	public function testGenerateDriveLinkV2(): void
	{
		$this->expectException(NotSupportedException::class);
		WGS84DegreeCompactService::getDriveLink(50.087451, 14.420671);
	}

	public function testNothingIsValid(): void
	{
		$this->assertFalse(WGS84DegreeCompactService::isValidStatic('any input'));
	}

	public function testNotProcessing(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Input is not valid.');
		WGS84DegreeCompactService::processStatic('any input');
	}

	public function testRender()
	{
		$this->assertSame('50.087451,14.420671', WGS84DegreeCompactService::getShareText(50.087451, 14.420671));
		$this->assertSame('-50.087451,14.420671', WGS84DegreeCompactService::getShareText(-50.087451, 14.420671));
		$this->assertSame('-50.087451,-14.420671', WGS84DegreeCompactService::getShareText(-50.087451, -14.420671));
		$this->assertSame('50.087451,-14.420671', WGS84DegreeCompactService::getShareText(50.087451, -14.420671));
		$this->assertSame('50.000000,1.000000', WGS84DegreeCompactService::getShareText(50, 1));
	}
}
