<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\SumavaCzService;
use PHPUnit\Framework\TestCase;

final class SumavaCzServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		SumavaCzService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		SumavaCzService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValid(): void
	{
		// Place
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/objekt_az/765-stezka-v-korunch-d/'));
		$this->assertTrue(SumavaCzService::isValidStatic('https://www.sumava.cz/objekt_az/765-stezka-v-korunch-d/'));
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/objekt_az/765-stezka-v-korunch-d'));
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/objekt_az/765'));
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/objekt_az/146-infocentrum-albtn/'));

		// Accomodation
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/objekt/2/'));
		$this->assertTrue(SumavaCzService::isValidStatic('https://www.sumava.cz/objekt/2/'));
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/objekt/2'));

		// Companies
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/firma/565-aldi-sd-bodenmais-d/'));
		$this->assertTrue(SumavaCzService::isValidStatic('https://www.sumava.cz/firma/565-aldi-sd-bodenmais-d'));
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/firma/565'));

		// Gallery
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/galerie_sekce/4711-zmeck-park-hrdek-u-suice/'));
		$this->assertTrue(SumavaCzService::isValidStatic('https://www.sumava.cz/galerie_sekce/4711-zmeck-park-hrdek-u-suice/'));
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/galerie_sekce/4711-zmeck-park-hrdek-u-suice'));
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/galerie_sekce/4711'));

		// Invalid
		$this->assertFalse(SumavaCzService::isValidStatic('some invalid url'));
		$this->assertFalse(SumavaCzService::isValidStatic('http://www.sumava.cz/mapa-stranek/'));
		$this->assertFalse(SumavaCzService::isValidStatic('http://www.sumava.cz/rozcestnik-kategorie/3-infocentra/'));
		$this->assertFalse(SumavaCzService::isValidStatic('http://www.sumava.cz/galerie/'));
		$this->assertFalse(SumavaCzService::isValidStatic('http://www.sumava.cz/blabla/objekt_az/765-stezka-v-korunch-d/'));
		$this->assertFalse(SumavaCzService::isValidStatic('http://www.sumava.cz/foooo/objekt/2/'));
		$this->assertFalse(SumavaCzService::isValidStatic('http://www.sumava.cz/palider/firma/565-aldi-sd-bodenmais-d/'));
		$this->assertFalse(SumavaCzService::isValidStatic('http://www.sumava.cz/tomas/galerie_sekce/4711-zmeck-park-hrdek-u-suice/'));
	}

	/**
	 * @group request
	 */
	public function testProcessPlace(): void
	{
		$collection = SumavaCzService::processStatic('http://www.sumava.cz/objekt_az/765-stezka-v-korunch-d/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.890900,13.485400', $collection[0]->__toString());
		$this->assertSame('Place', $collection[0]->getName());

		$collection = SumavaCzService::processStatic('http://www.sumava.cz/objekt_az/146-infocentrum-albtn/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.121800,13.209300', $collection[0]->__toString());
	}

	/**
	 * @group request
	 */
	public function testProcessAccomodation(): void
	{
		$collection = SumavaCzService::processStatic('http://www.sumava.cz/objekt/2/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.170100,13.454600', $collection[0]->__toString());
		$this->assertSame('Accomodation', $collection[0]->getName());

		// no coordinates in description, but available in map
		$collection = SumavaCzService::processStatic('http://www.sumava.cz/objekt/39')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.670000,14.162900', $collection[0]->__toString());
		$this->assertSame('Accomodation', $collection[0]->getName());
	}

	/**
	 * @group request
	 */
	public function testProcessCompanies(): void
	{
		$collection = SumavaCzService::processStatic('http://www.sumava.cz/firma/565-aldi-sd-bodenmais-d/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.071600,13.092100', $collection[0]->__toString());
		$this->assertSame('Company', $collection[0]->getName());

		$collection = SumavaCzService::processStatic('http://www.sumava.cz/firma/805-erpac-stanice-shell-bodenmais-d/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.063400,13.104300', $collection[0]->__toString());
		$this->assertSame('Company', $collection[0]->getName());
	}

	/**
	 * Type is place because gallery is just original source
	 * @group request
	 */
	public function testProcessGallery(): void
	{
		$collection = SumavaCzService::processStatic('http://www.sumava.cz/galerie_sekce/4710-tedraice/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.265100,13.520600', $collection[0]->__toString());
		$this->assertSame('Place', $collection[0]->getName());

		$collection = SumavaCzService::processStatic('http://www.sumava.cz/galerie_sekce/4711-zmeck-park-hrdek-u-suice/')->getCollection();
		$this->assertCount(3, $collection);
		$this->assertSame('49.260500,13.497900', $collection[0]->__toString());
		$this->assertSame('Place', $collection[0]->getName());
		$this->assertSame('49.261100,13.498100', $collection[1]->__toString());
		$this->assertSame('Place', $collection[1]->getName());
		$this->assertSame('49.261300,13.498500', $collection[2]->__toString());
		$this->assertSame('Place', $collection[2]->getName());
	}

	/**
	 * Gallery is not linked to any specific place
	 * @group request
	 */
	public function testGalleryNotRelated()
	{
		$this->assertCount(0, SumavaCzService::processStatic('http://www.sumava.cz/galerie_sekce/4688-strovsk-skotsk-horalsk-hry-2020/')->getCollection());
	}

	/**
	 * @group request
	 */
	public function testInvalidId(): void
	{
		$this->assertCount(0, SumavaCzService::processStatic('https://www.sumava.cz/objekt_az/99999999')->getCollection());
		$this->assertCount(0, SumavaCzService::processStatic('https://www.sumava.cz/objekt/99999999')->getCollection());
		$this->assertCount(0, SumavaCzService::processStatic('https://www.sumava.cz/galerie_sekce/9999999')->getCollection());
	}
}
