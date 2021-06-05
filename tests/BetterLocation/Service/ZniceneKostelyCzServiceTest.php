<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\ZniceneKostelyCzService;
use PHPUnit\Framework\TestCase;

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
		$this->assertTrue(ZniceneKostelyCzService::isValidStatic('http://www.znicenekostely.cz/?load=detail&id=18231'));
		$this->assertTrue(ZniceneKostelyCzService::isValidStatic('http://www.znicenekostely.cz/?id=18231&load=detail'));
		$this->assertTrue(ZniceneKostelyCzService::isValidStatic('http://znicenekostely.cz/?load=detail&id=18231'));
		$this->assertTrue(ZniceneKostelyCzService::isValidStatic('http://www.znicenekostely.cz/?load=detail&id=18231#obsah'));
		$this->assertTrue(ZniceneKostelyCzService::isValidStatic('http://znicenekostely.cz/?load=detail&id=18231#obsah'));
		$this->assertTrue(ZniceneKostelyCzService::isValidStatic('http://www.znicenekostely.cz/index.php?load=detail&id=18231#obsah'));
		$this->assertTrue(ZniceneKostelyCzService::isValidStatic('http://znicenekostely.cz/index.php?load=detail&id=18231#obsah'));
		$this->assertTrue(ZniceneKostelyCzService::isValidStatic('http://www.znicenekostely.cz/index.php?load=detail&id=4233&search_result_index=0&nej=1#obsah'));
		$this->assertTrue(ZniceneKostelyCzService::isValidStatic('http://znicenekostely.cz/index.php?load=detail&id=4233&search_result_index=0&nej=1#obsah'));

		$this->assertFalse(ZniceneKostelyCzService::isValidStatic('http://www.znicenekostely.cz/?load=detail&id=18231aaaa'));
		$this->assertFalse(ZniceneKostelyCzService::isValidStatic('http://www.znicenekostely.cz/?load=detail&id=aaaa'));
		$this->assertFalse(ZniceneKostelyCzService::isValidStatic('http://znicenekostely.cz/index.php?load=detail&id='));
		$this->assertFalse(ZniceneKostelyCzService::isValidStatic('http://znicenekostely.cz/?load=detail&id='));
		$this->assertFalse(ZniceneKostelyCzService::isValidStatic('http://www.znicenekostely.cz/?load=detail&id='));
		$this->assertFalse(ZniceneKostelyCzService::isValidStatic('http://znicenekostely.cz/?load=blabla&id=18231'));
		$this->assertFalse(ZniceneKostelyCzService::isValidStatic('http://znicenekostely.cz/?load=detail'));
		$this->assertFalse(ZniceneKostelyCzService::isValidStatic('http://znicenekostely.cz/?load=blabla&ID=18231'));
		$this->assertFalse(ZniceneKostelyCzService::isValidStatic('http://znicenekostely.cz/'));
		$this->assertFalse(ZniceneKostelyCzService::isValidStatic('http://znicenekostely.cz/index.php'));

		$this->assertFalse(ZniceneKostelyCzService::isValidStatic('some invalid url'));
	}

	public function testUrl(): void
	{
		$collection = ZniceneKostelyCzService::processStatic('http://www.znicenekostely.cz/?load=detail&id=18231#obsah')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.885617,14.044381', $collection[0]->__toString());

		$collection = ZniceneKostelyCzService::processStatic('http://www.znicenekostely.cz/index.php?load=detail&id=13727')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.944638,15.697070', $collection[0]->__toString());

		$collection = ZniceneKostelyCzService::processStatic('http://www.znicenekostely.cz/index.php?load=detail&id=4233&search_result_index=0&nej=1#obsah')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.636144,14.337469', $collection[0]->__toString());

		$collection = ZniceneKostelyCzService::processStatic('http://www.znicenekostely.cz/index.php?load=detail&id=14039&search_result_index=17&stav[]=Z&stav[]=T&stav[]=R&stav[]=O&stav[]=k&stav[]=n&stav[]=e&znamka[]=500&znamka[]=600&znamka_old[]=501&znamka_old[]=601&zanik=5&subtyp[]=kostely#obsah')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.042461,14.375072', $collection[0]->__toString());

		$collection = ZniceneKostelyCzService::processStatic('http://www.znicenekostely.cz/index.php?load=detail&id=6656&search_result_index=12&nej=3#obsah')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.782953,14.368479', $collection[0]->__toString());

	}

	public function testMissingCoordinates(): void
	{
		$collection = ZniceneKostelyCzService::processStatic('http://znicenekostely.cz/index.php?load=detail&id=99999999')->getCollection();
		$this->assertCount(0, $collection);
	}
}
