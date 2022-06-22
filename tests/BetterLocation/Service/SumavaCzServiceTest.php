<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

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

	public function testIsValidOriginal(): void
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

	public function testIsValidNew(): void
	{
		// Place
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/rozcestnik/priroda/vrcholy-rozhledny/stezka-v-korunach-d/'));
		$this->assertTrue(SumavaCzService::isValidStatic('https://www.sumava.cz/rozcestnik/priroda/vrcholy-rozhledny/stezka-v-korunach-d/'));
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/rozcestnik/priroda/vrcholy-rozhledny/stezka-v-korunach-d'));
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/objekt_az/765'));
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/objekt_az/146-infocentrum-albtn/'));

		// Accomodation
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/ubytovani/sumavska-roubenka/'));

		// Companies
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/firmy/obchody/smisene/aldi-sud-bodenmais-d/'));
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/firmy/obchody/cerpaci-stanice/cerpaci-stanice-shell-freyung-d/'));

		// Gallery
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/galerie/mesta-a-obce/mesta-a-obce/tedrazice/'));
		$this->assertTrue(SumavaCzService::isValidStatic('http://www.sumava.cz/galerie/priroda/vrcholy-rozhledny/stezka-v-korunach-d/'));

		// Invalid
		$this->assertFalse(SumavaCzService::isValidStatic('some invalid url'));
	}

	/**
	 * URLs before 2022-06-20.
	 *
	 * @see testProcessPlaceNew()
	 * @group request
	 */
	public function testProcessPlaceOriginal(): void
	{
		$collectionOriginal = SumavaCzService::processStatic('http://www.sumava.cz/objekt_az/765-stezka-v-korunch-d/')->getCollection();
		$this->assertCount(1, $collectionOriginal);
		$this->assertSame('48.890900,13.485400', $collectionOriginal->getFirst()->key());
		$this->assertSame('Place', $collectionOriginal->getFirst()->getSourceType());
		// Link above is now (2022-06-20) redirected here:

		$collection = SumavaCzService::processStatic('http://www.sumava.cz/objekt_az/146-infocentrum-albtn/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.121800,13.209300', $collection->getFirst()->key());
	}

	/**
	 * URLs after 2022-06-20.
	 *
	 * @see testProcessPlaceOriginal()
	 * @group request
	 */
	public function testProcessPlaceNew(): void
	{
		$collectionNew = SumavaCzService::processStatic('http://www.sumava.cz/rozcestnik/priroda/vrcholy-rozhledny/stezka-v-korunach-d/')->getCollection();
		$this->assertCount(1, $collectionNew);
		$this->assertSame('48.890900,13.485400', $collectionNew->getFirst()->key());
		$this->assertSame('Place', $collectionNew->getFirst()->getSourceType());

		$collection = SumavaCzService::processStatic('http://www.sumava.cz/rozcestnik/instituce/infocentra/infocentrum-alzbetin/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.121800,13.209300', $collection->getFirst()->key());
	}

	/**
	 * URLs before 2022-06-20.
	 *
	 * @group request
	 */
	public function testProcessAccomodationOriginal(): void
	{
		$collection = SumavaCzService::processStatic('http://www.sumava.cz/objekt/2/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.170100,13.454600', $collection->getFirst()->key());
		$this->assertSame('Accomodation', $collection->getFirst()->getSourceType());

		// no coordinates in description, but available in map
		$collection = SumavaCzService::processStatic('http://www.sumava.cz/objekt/39')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.670000,14.162900', $collection->getFirst()->key());
		$this->assertSame('Accomodation', $collection->getFirst()->getSourceType());
	}

	/**
	 * URLs after 2022-06-20.
	 *
	 * @group request
	 */
	public function testProcessAccomodationNew(): void
	{
		$collection = SumavaCzService::processStatic('http://www.sumava.cz/ubytovani/apartmany-stara-posta-hartmanice/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.170100,13.454600', $collection->getFirst()->key());
		$this->assertSame('Accomodation', $collection->getFirst()->getSourceType());

		// no coordinates in description, but available in map
		$collection = SumavaCzService::processStatic('http://www.sumava.cz/ubytovani/rekreace-na-lipne/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.670000,14.162900', $collection->getFirst()->key());
		$this->assertSame('Accomodation', $collection->getFirst()->getSourceType());
	}

	/**
	 * URLs before 2022-06-20.
	 *
	 * @group request
	 */
	public function testProcessCompaniesOriginal(): void
	{
		$collection = SumavaCzService::processStatic('http://www.sumava.cz/firma/565-aldi-sd-bodenmais-d/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.071600,13.092100', $collection->getFirst()->key());
		$this->assertSame('Company', $collection->getFirst()->getSourceType());

		$collection = SumavaCzService::processStatic('http://www.sumava.cz/firma/805-erpac-stanice-shell-bodenmais-d/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.063400,13.104300', $collection->getFirst()->key());
		$this->assertSame('Company', $collection->getFirst()->getSourceType());
	}

	/**
	 * URLs after 2022-06-20.
	 *
	 * @group request
	 */
	public function testProcessCompaniesNew(): void
	{
		$collection = SumavaCzService::processStatic('http://www.sumava.cz/firmy/obchody/smisene/aldi-sud-bodenmais-d/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.071600,13.092100', $collection->getFirst()->key());
		$this->assertSame('Company', $collection->getFirst()->getSourceType());

		$collection = SumavaCzService::processStatic('http://www.sumava.cz/firmy/obchody/cerpaci-stanice/cerpaci-stanice-shell-bodenmais-d/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.063400,13.104300', $collection->getFirst()->key());
		$this->assertSame('Company', $collection->getFirst()->getSourceType());
	}

	/**
	 * URLs before 2022-06-20.
	 *
	 * Type is place because gallery is just original source
	 * @group request
	 */
	public function testProcessGalleryOriginal(): void
	{
		$collection = SumavaCzService::processStatic('http://www.sumava.cz/galerie_sekce/4710-tedraice/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.265100,13.520600', $collection->getFirst()->key());
		$this->assertSame('Place', $collection->getFirst()->getSourceType());

		$collection = SumavaCzService::processStatic('http://www.sumava.cz/galerie_sekce/4711-zmeck-park-hrdek-u-suice/')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('49.261300,13.498500', $collection->getFirst()->key());
		$this->assertSame('Place', $collection->getFirst()->getSourceType());
		$this->assertSame('49.261100,13.498100', $collection[1]->key());
		$this->assertSame('Place', $collection[1]->getSourceType());
//		$this->assertSame('49.261300,13.498500', $collection[2]->key());
//		$this->assertSame('Place', $collection[2]->getSourceType());
	}

	/**
	 * URLs after 2022-06-20.
	 *
	 * Type is place because gallery is just original source
	 * @group request
	 */
	public function testProcessGalleryNew(): void
	{
		$collection = SumavaCzService::processStatic('http://www.sumava.cz/galerie/mesta-a-obce/mesta-a-obce/tedrazice/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.265100,13.520600', $collection->getFirst()->key());
		$this->assertSame('Place', $collection->getFirst()->getSourceType());

		$collection = SumavaCzService::processStatic('http://www.sumava.cz/galerie/zabava/odpocinek/zamecky-park-hradek-u-susice/')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('49.261300,13.498500', $collection->getFirst()->key());
		$this->assertSame('Place', $collection->getFirst()->getSourceType());
		$this->assertSame('49.261100,13.498100', $collection[1]->key());
		$this->assertSame('Place', $collection[1]->getSourceType());
	}

	/**
	 * URLs before 2022-06-20.
	 *
	 * Gallery is not linked to any specific place
	 * @group request
	 */
	public function testGalleryNotRelatedOriginal()
	{
		$this->assertCount(0, SumavaCzService::processStatic('http://www.sumava.cz/galerie_sekce/4688-strovsk-skotsk-horalsk-hry-2020/')->getCollection());
	}

	/**
	 * URLs after 2022-06-20.
	 *
	 * Gallery is not linked to any specific place
	 * @group request
	 */
	public function testGalleryNotRelatedNew()
	{
		$this->assertCount(0, SumavaCzService::processStatic('http://www.sumava.cz/galerie/kultura-a-pamatky/akce/strazovske-skotske-horalske-hry-2020/')->getCollection());
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
