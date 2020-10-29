<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\ZniceneKostelyCzService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../src/bootstrap.php';

final class ZniceneKostelyCzServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Share link is not implemented.');
		ZniceneKostelyCzService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		ZniceneKostelyCzService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValid(): void
	{
		$this->assertTrue(ZniceneKostelyCzService::isValid('http://www.znicenekostely.cz/?load=detail&id=18231'));
		$this->assertTrue(ZniceneKostelyCzService::isValid('http://znicenekostely.cz/?load=detail&id=18231'));
		$this->assertTrue(ZniceneKostelyCzService::isValid('http://www.znicenekostely.cz/?load=detail&id=18231#obsah'));
		$this->assertTrue(ZniceneKostelyCzService::isValid('http://znicenekostely.cz/?load=detail&id=18231#obsah'));
		$this->assertTrue(ZniceneKostelyCzService::isValid('http://www.znicenekostely.cz/index.php?load=detail&id=18231#obsah'));
		$this->assertTrue(ZniceneKostelyCzService::isValid('http://znicenekostely.cz/index.php?load=detail&id=18231#obsah'));
		$this->assertTrue(ZniceneKostelyCzService::isValid('http://www.znicenekostely.cz/index.php?load=detail&id=4233&search_result_index=0&nej=1#obsah'));
		$this->assertTrue(ZniceneKostelyCzService::isValid('http://znicenekostely.cz/index.php?load=detail&id=4233&search_result_index=0&nej=1#obsah'));

		$this->assertFalse(ZniceneKostelyCzService::isValid('http://znicenekostely.cz/index.php?load=detail&id='));
		$this->assertFalse(ZniceneKostelyCzService::isValid('http://znicenekostely.cz/?load=detail&id='));
		$this->assertFalse(ZniceneKostelyCzService::isValid('http://www.znicenekostely.cz/?load=detail&id='));
		$this->assertFalse(ZniceneKostelyCzService::isValid('http://znicenekostely.cz/?load=blabla&id=18231'));
		$this->assertFalse(ZniceneKostelyCzService::isValid('http://znicenekostely.cz/?load=detail'));
		$this->assertFalse(ZniceneKostelyCzService::isValid('http://znicenekostely.cz/?load=blabla&ID=18231'));
		$this->assertFalse(ZniceneKostelyCzService::isValid('http://znicenekostely.cz/'));
		$this->assertFalse(ZniceneKostelyCzService::isValid('http://znicenekostely.cz/index.php'));

		$this->assertFalse(ZniceneKostelyCzService::isValid('some invalid url'));
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testUrl(): void
	{
		$this->assertEquals('49.885611,14.044380', ZniceneKostelyCzService::parseCoords('http://www.znicenekostely.cz/?load=detail&id=18231#obsah')->__toString());
		$this->assertEquals('48.944638,15.697070', ZniceneKostelyCzService::parseCoords('http://www.znicenekostely.cz/index.php?load=detail&id=13727')->__toString());
		$this->assertEquals('50.636144,14.337469', ZniceneKostelyCzService::parseCoords('http://www.znicenekostely.cz/index.php?load=detail&id=4233&search_result_index=0&nej=1#obsah')->__toString());
		$this->assertEquals('50.042461,14.375072', ZniceneKostelyCzService::parseCoords('http://www.znicenekostely.cz/index.php?load=detail&id=14039&search_result_index=17&stav[]=Z&stav[]=T&stav[]=R&stav[]=O&stav[]=k&stav[]=n&stav[]=e&znamka[]=500&znamka[]=600&znamka_old[]=501&znamka_old[]=601&zanik=5&subtyp[]=kostely#obsah')->__toString());
		$this->assertEquals('50.782953,14.368479', ZniceneKostelyCzService::parseCoords('http://www.znicenekostely.cz/index.php?load=detail&id=6656&search_result_index=12&nej=3#obsah')->__toString());
	}

	public function testMissingCoordinates(): void
	{
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Coordinates on ZniceneKostely.cz page are missing.');
		ZniceneKostelyCzService::parseCoords('http://znicenekostely.cz/index.php?load=detail&id=99999999');
	}
}
