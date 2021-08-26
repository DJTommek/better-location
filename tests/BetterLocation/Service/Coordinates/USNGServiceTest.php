<?php declare(strict_types=1);

use App\BetterLocation\Service\Coordinates\USNGService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

final class USNGServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Share link is not supported.');
		USNGService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		USNGService::getLink(50.087451, 14.420671, true);
	}

	public function testValidLocation(): void
	{
		$this->assertSame('50.083718,14.400509', USNGService::processStatic('33 N 457111 5548111')->getFirst()->__toString()); // Prague
		$this->assertSame('50.083718,14.400509', USNGService::processStatic('33N 457111 5548111')->getFirst()->__toString()); // Prague
		$this->assertSame('50.083718,14.400509', USNGService::processStatic('33N457111 5548111')->getFirst()->__toString()); // Prague
	}


	public function testNothingInText(): void
	{
		$this->assertSame([], USNGService::findInText('Nothing valid')->getAll());
	}
}
