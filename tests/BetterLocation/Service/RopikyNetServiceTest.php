<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\RopikyNetService;
use PHPUnit\Framework\TestCase;

final class RopikyNetServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		RopikyNetService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		RopikyNetService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValid(): void
	{
		$this->assertTrue(RopikyNetService::isValidStatic('https://www.ropiky.net/dbase_objekt.php?id=1183840757'));
		$this->assertTrue(RopikyNetService::isValidStatic('https://ropiky.net/dbase_objekt.php?id=1183840757'));
		$this->assertTrue(RopikyNetService::isValidStatic('http://www.ropiky.net/dbase_objekt.php?id=1183840757'));
		$this->assertTrue(RopikyNetService::isValidStatic('http://ropiky.net/dbase_objekt.php?id=1183840757'));

		$this->assertTrue(RopikyNetService::isValidStatic('https://www.ropiky.net/nerop_objekt.php?id=1397407312'));
		$this->assertTrue(RopikyNetService::isValidStatic('https://ropiky.net/nerop_objekt.php?id=1397407312'));
		$this->assertTrue(RopikyNetService::isValidStatic('http://www.ropiky.net/nerop_objekt.php?id=1397407312'));
		$this->assertTrue(RopikyNetService::isValidStatic('http://ropiky.net/nerop_objekt.php?id=1397407312'));

		$this->assertFalse(RopikyNetService::isValidStatic('https://www.ropiky.net/dbase_objekt.php?id=abcd'));
		$this->assertFalse(RopikyNetService::isValidStatic('https://www.ropiky.net/dbase_objekt.php?id='));
		$this->assertFalse(RopikyNetService::isValidStatic('https://www.ropiky.net/dbase_objekt.blabla?id=1183840757'));
		$this->assertFalse(RopikyNetService::isValidStatic('https://www.ropiky.net/nerop_objekt.php?id=abcd'));
		$this->assertFalse(RopikyNetService::isValidStatic('https://www.ropiky.net/nerop_objekt.php?id='));
		$this->assertFalse(RopikyNetService::isValidStatic('https://www.ropiky.net/nerop_objekt.blabla?id=1183840757'));
		$this->assertFalse(RopikyNetService::isValidStatic('https://www.ropiky.net/aaaaa.php?id=1183840757'));
		$this->assertFalse(RopikyNetService::isValidStatic('https://www.ropiky.net'));

		$this->assertFalse(RopikyNetService::isValidStatic('some invalid url'));
	}

	/**
	 * @group request
	 */
	public function testProcessDBaseObjekt(): void
	{
		$collection = RopikyNetService::processStatic('https://ropiky.net/dbase_objekt.php?id=1183840757')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.325750,20.233450', $collection[0]->__toString());

		$collection = RopikyNetService::processStatic('https://ropiky.net/dbase_objekt.php?id=1183840760')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.331710,20.240140', $collection[0]->__toString());

		$collection = RopikyNetService::processStatic('https://ropiky.net/dbase_objekt.php?id=1075717726')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.127520,16.601080', $collection[0]->__toString());

		$collection = RopikyNetService::processStatic('https://ropiky.net/dbase_objekt.php?id=1075718529')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.346390,16.974210', $collection[0]->__toString());

		$collection = RopikyNetService::processStatic('https://ropiky.net/dbase_objekt.php?id=1075728128')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('47.999410,18.780630', $collection[0]->__toString());
	}

	/**
	 * @group request
	 */
	public function testMissingCoordinates(): void
	{
		$this->assertCount(0, RopikyNetService::processStatic('https://ropiky.net/dbase_objekt.php?id=1121190136')->getCollection());
		$this->assertCount(0, RopikyNetService::processStatic('https://ropiky.net/dbase_objekt.php?id=1121190152')->getCollection());
	}

	/**
	 * @group request
	 */
	public function testInvalidId(): void
	{
		$this->assertCount(0, RopikyNetService::processStatic('https://ropiky.net/dbase_objekt.php?id=123')->getCollection());
	}
}
