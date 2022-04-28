<?php declare(strict_types=1);

use App\BetterLocation\Service\CoordinatesRender\WGS84DegreeMinutesCompactService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

final class WGS84DegreesMinutesServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('WGS84 DM Compact (ID 37) does not support share link.');
		WGS84DegreeMinutesCompactService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('WGS84 DM Compact (ID 37) does not support drive link.');
		WGS84DegreeMinutesCompactService::getLink(50.087451, 14.420671, true);
	}

	public function testGenerateDriveLinkV2(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('WGS84 DM Compact (ID 37) does not support drive link.');
		WGS84DegreeMinutesCompactService::getDriveLink(50.087451, 14.420671);
	}

	public function testNothingIsValid(): void
	{
		$this->assertFalse(WGS84DegreeMinutesCompactService::isValidStatic('any input'));
	}

	public function testNotProcessing(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Input is not valid.');
		WGS84DegreeMinutesCompactService::processStatic('any input');
	}

	public function testRender() {
		$this->assertSame('50°5.247,14°25.240', WGS84DegreeMinutesCompactService::getShareText(50.087451, 14.420671));
		$this->assertSame('-50°5.247,14°25.240', WGS84DegreeMinutesCompactService::getShareText(-50.087451, 14.420671));
		$this->assertSame('-50°5.247,-14°25.240', WGS84DegreeMinutesCompactService::getShareText(-50.087451, -14.420671));
		$this->assertSame('50°5.247,-14°25.240', WGS84DegreeMinutesCompactService::getShareText(50.087451, -14.420671));
		$this->assertSame('50°0.000,1°0.000', WGS84DegreeMinutesCompactService::getShareText(50, 1));
	}
}
