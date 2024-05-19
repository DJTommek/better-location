<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\HradyCzService;
use App\MiniCurl\Exceptions\InvalidResponseException;
use PHPUnit\Framework\TestCase;

final class HradyCzServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		HradyCzService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		HradyCzService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValid(): void
	{
		$this->assertTrue(HradyCzService::validateStatic('https://www.hrady.cz/aaa-bbb-ccc'));
		$this->assertTrue(HradyCzService::validateStatic('https://www.hrady.cz/certovy-hlavy-zelizy'));
		$this->assertTrue(HradyCzService::validateStatic('https://hrady.cz/certovy-hlavy-zelizy'));
		$this->assertTrue(HradyCzService::validateStatic('http://hrady.cz/certovy-hlavy-zelizy'));
		$this->assertTrue(HradyCzService::validateStatic('https://www.hrady.cz/certovy-hlavy-zelizy/'));
		$this->assertTrue(HradyCzService::validateStatic('https://www.hrady.cz/certovy-hlavy-zelizy/komentare'));
		$this->assertTrue(HradyCzService::validateStatic('https://www.hrady.cz/certovy-hlavy-zelizy/komentare/new'));
		$this->assertTrue(HradyCzService::validateStatic('https://www.hrady.cz/pevnost-bunkr-lo-vz-37-a-124az1z-vaha'));

		$this->assertFalse(HradyCzService::validateStatic('some invalid url'));
		$this->assertFalse(HradyCzService::validateStatic('https://www.hrady.cz/aaa-bbb'));
		$this->assertFalse(HradyCzService::validateStatic('https://www.hrady.cz/mapa'));
		$this->assertFalse(HradyCzService::validateStatic('https://www.hrady.cz/clanky/pohadkovemu-jicinu-predchazela-jedna-z-nejvetsich-katastrof-17-stoleti'));
		$this->assertFalse(HradyCzService::validateStatic('https://www.hrady.cz/search?typ_dop=105'));
	}

	/**
	 * @group request
	 */
	public function testProcess(): void
	{
		$collection = HradyCzService::processStatic('https://www.hrady.cz/certovy-hlavy-zelizy')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.420540,14.464405', $collection[0]->__toString());

		$collection = HradyCzService::processStatic('https://www.hrady.cz/pevnost-bunkr-lo-vz-37-a-124az1z-vaha')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.306440,14.288090', $collection[0]->__toString());

		$collection = HradyCzService::processStatic('https://www.hrady.cz/kaple-nanebevzeti-panny-marie-miletice/ubytovani')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.305519,14.235415', $collection[0]->__toString());
	}

	/**
	 * @group request
	 */
	public function testInvalidId(): void
	{
		$this->expectException(InvalidResponseException::class);
		$this->expectExceptionCode(404);
		$this->expectExceptionMessage('Invalid response code "404" but required "200" for URL "https://www.hrady.cz/aaa-bbb-ccc"');
		HradyCzService::processStatic('https://www.hrady.cz/aaa-bbb-ccc');
	}
}
