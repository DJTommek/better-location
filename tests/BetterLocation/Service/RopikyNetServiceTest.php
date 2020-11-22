<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\RopikyNetService;
use PHPUnit\Framework\TestCase;

final class RopikyNetServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Share link is not implemented.');
		RopikyNetService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		RopikyNetService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValid(): void
	{
		$this->assertTrue(RopikyNetService::isValid('https://www.ropiky.net/dbase_objekt.php?id=1183840757'));
		$this->assertTrue(RopikyNetService::isValid('https://ropiky.net/dbase_objekt.php?id=1183840757'));
		$this->assertTrue(RopikyNetService::isValid('http://www.ropiky.net/dbase_objekt.php?id=1183840757'));
		$this->assertTrue(RopikyNetService::isValid('http://ropiky.net/dbase_objekt.php?id=1183840757'));

		$this->assertTrue(RopikyNetService::isValid('https://www.ropiky.net/nerop_objekt.php?id=1397407312'));
		$this->assertTrue(RopikyNetService::isValid('https://ropiky.net/nerop_objekt.php?id=1397407312'));
		$this->assertTrue(RopikyNetService::isValid('http://www.ropiky.net/nerop_objekt.php?id=1397407312'));
		$this->assertTrue(RopikyNetService::isValid('http://ropiky.net/nerop_objekt.php?id=1397407312'));

		$this->assertFalse(RopikyNetService::isValid('https://www.ropiky.net/dbase_objekt.php?id=abcd'));
		$this->assertFalse(RopikyNetService::isValid('https://www.ropiky.net/dbase_objekt.php?id='));
		$this->assertFalse(RopikyNetService::isValid('https://www.ropiky.net/dbase_objekt.blabla?id=1183840757'));
		$this->assertFalse(RopikyNetService::isValid('https://www.ropiky.net/nerop_objekt.php?id=abcd'));
		$this->assertFalse(RopikyNetService::isValid('https://www.ropiky.net/nerop_objekt.php?id='));
		$this->assertFalse(RopikyNetService::isValid('https://www.ropiky.net/nerop_objekt.blabla?id=1183840757'));
		$this->assertFalse(RopikyNetService::isValid('https://www.ropiky.net/aaaaa.php?id=1183840757'));
		$this->assertFalse(RopikyNetService::isValid('https://www.ropiky.net'));

		$this->assertFalse(RopikyNetService::isValid('some invalid url'));
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testUrl(): void
	{
		$this->assertEquals('48.325750,20.233450', RopikyNetService::parseCoords('https://ropiky.net/dbase_objekt.php?id=1183840757')->__toString());
		$this->assertEquals('48.331710,20.240140', RopikyNetService::parseCoords('https://ropiky.net/dbase_objekt.php?id=1183840760')->__toString());
		$this->assertEquals('50.127520,16.601080', RopikyNetService::parseCoords('https://ropiky.net/dbase_objekt.php?id=1075717726')->__toString());
		$this->assertEquals('49.346390,16.974210', RopikyNetService::parseCoords('https://ropiky.net/dbase_objekt.php?id=1075718529')->__toString());
		$this->assertEquals('47.999410,18.780630', RopikyNetService::parseCoords('https://ropiky.net/dbase_objekt.php?id=1075728128')->__toString());

		$this->assertEquals('49.728630,13.558510', RopikyNetService::parseCoords('http://www.ropiky.net/nerop_objekt.php?id=1296479566')->__toString());
		$this->assertEquals('49.182180,13.470280', RopikyNetService::parseCoords('http://www.ropiky.net/nerop_objekt.php?id=1397407312')->__toString());
		$this->assertEquals('50.599950,13.889120', RopikyNetService::parseCoords('http://www.ropiky.net/nerop_objekt.php?id=1396538830')->__toString());
	}

	public function testMissingCoordinates1(): void
	{
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Coordinates on Ropiky.net page are missing.');
		RopikyNetService::parseCoords('https://ropiky.net/dbase_objekt.php?id=1121190136');

	}

	public function testMissingCoordinates2(): void
	{
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Coordinates on Ropiky.net page are missing.');
		RopikyNetService::parseCoords('https://ropiky.net/dbase_objekt.php?id=1121190152');
	}

	public function testMissingCoordinates3(): void
	{
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Coordinates on Ropiky.net page are missing.');
		RopikyNetService::parseCoords('http://www.ropiky.net/nerop_objekt.php?id=1249996776');
	}

	public function testInvalidId(): void
	{
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Unable to get coords from Ropiky.net link https://ropiky.net/dbase_objekt.php?id=123.');
		RopikyNetService::parseCoords('https://ropiky.net/dbase_objekt.php?id=123');
	}
}
