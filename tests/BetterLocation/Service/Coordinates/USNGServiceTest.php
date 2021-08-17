<?php declare(strict_types=1);

use App\BetterLocation\Service\Coordinates\USNGService;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use PHPUnit\Framework\TestCase;

final class USNGServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotImplementedException::class);
		$this->expectExceptionMessage('Share link is not implemented.');
		USNGService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotImplementedException::class);
		$this->expectExceptionMessage('Drive link is not implemented.');
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
