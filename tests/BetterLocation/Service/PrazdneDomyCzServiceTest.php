<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\PrazdneDomyCzService;
use App\MiniCurl\Exceptions\InvalidResponseException;
use PHPUnit\Framework\TestCase;

final class PrazdneDomyCzServiceTest extends TestCase
{
	public function testIsValid(): void
	{
		// Place
		$this->assertTrue(PrazdneDomyCzService::isValidStatic('https://prazdnedomy.cz/domy/objekty/detail/2732-kasarna-u-sloupu'));
		$this->assertTrue(PrazdneDomyCzService::isValidStatic('https://prazdnedomy.cz/domy/objekty/detail/96-dum-u-tri-bilych-lilii'));
		$this->assertTrue(PrazdneDomyCzService::isValidStatic('https://prazdnedomy.cz/domy/objekty/detail/96'));
		$this->assertTrue(PrazdneDomyCzService::isValidStatic('https://www.prazdnedomy.cz/domy/objekty/detail/96'));
		$this->assertTrue(PrazdneDomyCzService::isValidStatic('http://www.prazdnedomy.cz/domy/objekty/detail/96'));

		// Invalid
		$this->assertFalse(PrazdneDomyCzService::isValidStatic('some invalid url'));
		$this->assertFalse(PrazdneDomyCzService::isValidStatic('https://prazdnedomy.cz/domy/objekty/detail/kasarna-u-sloupu'));
		$this->assertFalse(PrazdneDomyCzService::isValidStatic('https://prazdnedomy.cz/clanky/'));
		$this->assertFalse(PrazdneDomyCzService::isValidStatic('https://prazdnedomy.cz/clanky/prazdne-domy-na-vedlejsi-koleji-aneb-prazdna-nadrazi-jako-prilezitost/'));
	}

	/**
	 * @group request
	 */
	public function testProcessPlace(): void
	{
		$collection = PrazdneDomyCzService::processStatic('https://prazdnedomy.cz/domy/objekty/detail/2732-kasarna-u-sloupu')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.060201,13.736212', $collection[0]->key());

		$collection = PrazdneDomyCzService::processStatic('https://prazdnedomy.cz/domy/objekty/detail/96-dum-u-tri-bilych-lilii')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087720,14.398980', $collection[0]->key());
	}

	/**
	 * @group request
	 */
	public function testInvalidId(): void
	{
		$this->expectException(InvalidResponseException::class);
		$this->expectExceptionCode(500);
		$this->expectExceptionMessage('Invalid response code "500" but required "200" for URL "https://prazdnedomy.cz/domy/objekty/detail/999999999"');
		PrazdneDomyCzService::processStatic('https://prazdnedomy.cz/domy/objekty/detail/999999999');
	}
}
