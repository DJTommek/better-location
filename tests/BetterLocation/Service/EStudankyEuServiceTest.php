<?php declare(strict_types=1);

use App\BetterLocation\Service\EStudankyEuService;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\MiniCurl\Exceptions\InvalidResponseException;
use PHPUnit\Framework\TestCase;

final class EStudankyEuServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotImplementedException::class);
		$this->expectExceptionMessage('Share link is not implemented.');
		EStudankyEuService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotImplementedException::class);
		$this->expectExceptionMessage('Drive link is not implemented.');
		EStudankyEuService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValid(): void
	{
		// Place
		$this->assertTrue(EStudankyEuService::isValidStatic('https://estudanky.eu/3762-studanka-kinska'));
		$this->assertTrue(EStudankyEuService::isValidStatic('http://estudanky.eu/3762-studanka-kinska'));
		$this->assertTrue(EStudankyEuService::isValidStatic('https://www.estudanky.eu/3762-studanka-kinska'));
		$this->assertTrue(EStudankyEuService::isValidStatic('https://www.estudanky.eu/3762'));
		$this->assertTrue(EStudankyEuService::isValidStatic('https://www.estudanky.eu/3762-'));

		// Invalid
		$this->assertFalse(EStudankyEuService::isValidStatic('some invalid url'));
		$this->assertFalse(EStudankyEuService::isValidStatic('https://estudanky.eu/nepristupne-cislo-zpet-strana-1'));
		$this->assertFalse(EStudankyEuService::isValidStatic('https://estudanky.eu/kraj-B-cislo-strana-1'));
		$this->assertFalse(EStudankyEuService::isValidStatic('https://estudanky.eu/zachranme-studanky'));
	}

	public function testProcessPlace(): void
	{
		$collection = EStudankyEuService::processStatic('https://estudanky.eu/3762-studanka-kinska')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.078999,14.400600', $collection[0]->__toString());

		$collection = EStudankyEuService::processStatic('https://estudanky.eu/10596-studna-bez-jmena')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.068591,14.420468', $collection[0]->__toString());

		$collection = EStudankyEuService::processStatic('https://estudanky.eu/4848')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.517083,18.729550', $collection[0]->__toString());
	}

	public function testInvalidId(): void
	{
		$this->expectException(InvalidResponseException::class);
		$this->expectExceptionCode(404);
		$this->expectExceptionMessage('Invalid response code "404" but required "200" for URL "https://estudanky.eu/999999999"');
		EStudankyEuService::processStatic('https://estudanky.eu/999999999');
	}
}
