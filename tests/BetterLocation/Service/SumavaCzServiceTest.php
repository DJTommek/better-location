<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\SumavaCzService;
use PHPUnit\Framework\TestCase;

final class SumavaCzServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Share link is not supported.');
		SumavaCzService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
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

		// Invalid
		$this->assertFalse(SumavaCzService::isValidStatic('some invalid url'));
		$this->assertFalse(SumavaCzService::isValidStatic('http://www.sumava.cz/mapa-stranek/'));
		$this->assertFalse(SumavaCzService::isValidStatic('http://www.sumava.cz/rozcestnik-kategorie/3-infocentra/'));
	}

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

	public function testInvalidId(): void
	{
		$this->assertCount(0, SumavaCzService::processStatic('https://www.sumava.cz/objekt_az/99999999')->getCollection());
		$this->assertCount(0, SumavaCzService::processStatic('https://www.sumava.cz/objekt/99999999')->getCollection());
	}
}
