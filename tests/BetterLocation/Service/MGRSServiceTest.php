<?php declare(strict_types=1);

use App\BetterLocation\Service\Coordinates\MGRSService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

final class MGRSServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Share link is not supported.');
		MGRSService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		MGRSService::getLink(50.087451, 14.420671, true);
	}

	public function testValidLocation(): void
	{
		$this->assertSame('50.086359,14.408709', MGRSService::processStatic('33UVR577484')->getFirst()->__toString()); // Prague
		$this->assertSame('21.309433,-157.916867', MGRSService::processStatic('4QFJ12345678')->getFirst()->__toString()); // https://en.wikipedia.org/wiki/Military_Grid_Reference_System
		$this->assertSame('21.309433,-157.916867', MGRSService::processStatic('04QFJ12345678')->getFirst()->__toString()); // https://en.wikipedia.org/wiki/Military_Grid_Reference_System
		$this->assertSame('38.959391,-95.265482', MGRSService::processStatic('15SUD0370514711')->getFirst()->__toString());
		$this->assertSame('38.889801,-77.036543', MGRSService::processStatic('18SUJ2337106519')->getFirst()->__toString());
		$this->assertSame('60.775935,4.693467', MGRSService::processStatic('31VEH92233902')->getFirst()->__toString()); // Edge of Norway
		$this->assertSame('-34.051387,18.462069', MGRSService::processStatic('34HBH65742924')->getFirst()->__toString()); // South Africa
		$this->assertSame('-45.892917,170.503103', MGRSService::processStatic('59GMK61451773')->getFirst()->__toString()); // New Zeland
		// examples from https://www.usna.edu/Users/oceano/pguth/md_help/html/mgrs_utm.htm
//		$this->assertSame(',', MGRSService::processStatic('18SUJ7082315291')->getFirst()->__toString());
//		$this->assertSame(',', MGRSService::processStatic('18SUJ70821529')->getFirst()->__toString());
//		$this->assertSame(',', MGRSService::processStatic('18SUJ708152')->getFirst()->__toString());
	}

	public function testNothingInText(): void
	{
		$this->assertSame([], MGRSService::findInText('Nothing valid')->getAll());
	}
}
