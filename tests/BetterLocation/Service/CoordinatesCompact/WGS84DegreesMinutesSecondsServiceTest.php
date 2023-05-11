<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\CoordinatesCompact;

use App\BetterLocation\Service\CoordinatesRender\WGS84DegreeMinutesSecondsCompactService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

final class WGS84DegreesMinutesSecondsServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		WGS84DegreeMinutesSecondsCompactService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		WGS84DegreeMinutesSecondsCompactService::getLink(50.087451, 14.420671, true);
	}

	public function testGenerateDriveLinkV2(): void
	{
		$this->expectException(NotSupportedException::class);
		WGS84DegreeMinutesSecondsCompactService::getDriveLink(50.087451, 14.420671);
	}

	public function testNothingIsValid(): void
	{
		$this->assertFalse(WGS84DegreeMinutesSecondsCompactService::isValidStatic('any input'));
	}

	public function testNotProcessing(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Input is not valid.');
		WGS84DegreeMinutesSecondsCompactService::processStatic('any input');
	}

	public function testRender(): void
	{
		$this->assertSame('50°5\'14.824,14°25\'14.416', WGS84DegreeMinutesSecondsCompactService::getShareText(50.087451, 14.420671));
		$this->assertSame('-50°5\'14.824,14°25\'14.416', WGS84DegreeMinutesSecondsCompactService::getShareText(-50.087451, 14.420671));
		$this->assertSame('-50°5\'14.824,-14°25\'14.416', WGS84DegreeMinutesSecondsCompactService::getShareText(-50.087451, -14.420671));
		$this->assertSame('50°5\'14.824,-14°25\'14.416', WGS84DegreeMinutesSecondsCompactService::getShareText(50.087451, -14.420671));
		$this->assertSame('50°0\'0.000,1°0\'0.000', WGS84DegreeMinutesSecondsCompactService::getShareText(50, 1));
	}
}
