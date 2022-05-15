<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\WikipediaService;
use PHPUnit\Framework\TestCase;

final class WikipediaServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		WikipediaService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		WikipediaService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValid(): void
	{
		$this->assertTrue(WikipediaService::isValidStatic('https://en.wikipedia.org/wiki/Conneaut_High_School'));
		$this->assertTrue(WikipediaService::isValidStatic('https://cs.wikipedia.org/wiki/City_Tower'));
		// mobile URLs
		$this->assertTrue(WikipediaService::isValidStatic('https://en.m.wikipedia.org/wiki/Conneaut_High_School'));
		$this->assertTrue(WikipediaService::isValidStatic('https://cs.m.wikipedia.org/wiki/City_Tower'));
		// permanent URLs
		$this->assertTrue(WikipediaService::isValidStatic('https://cs.wikipedia.org/w/index.php?title=Nejvy%C5%A1%C5%A1%C3%AD_soud_%C4%8Cesk%C3%A9_republiky&oldid=18532372'));
		$this->assertTrue(WikipediaService::isValidStatic('https://cs.wikipedia.org/w/index.php?oldid=18532372'));
		$this->assertTrue(WikipediaService::isValidStatic('https://cs.wikipedia.org/w/?oldid=18532372'));

		$this->assertFalse(WikipediaService::isValidStatic('https://wikipedia.org/'));
		$this->assertFalse(WikipediaService::isValidStatic('https://en.wikipedia.org/'));
		$this->assertFalse(WikipediaService::isValidStatic('https://cs.wikipedia.org/'));

		$this->assertFalse(WikipediaService::isValidStatic('some invalid url'));
	}

	/**
	 * @group request
	 */
	public function testProcessNormalUrl(): void
	{
		$collection = WikipediaService::processStatic('https://cs.wikipedia.org/wiki/City_Tower')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.050278,14.436111', $collection[0]->__toString());

		$collection = WikipediaService::processStatic('https://cs.wikipedia.org/wiki/Praha')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087500,14.421389', $collection[0]->__toString());

		$collection = WikipediaService::processStatic('https://cs.wikipedia.org/wiki/Nejvy%C5%A1%C5%A1%C3%AD_soud_%C4%8Cesk%C3%A9_republiky')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.205193,16.602196', $collection[0]->__toString());

		// @TODO skipper, for some reason it is requesting with invalid character: "https://cs.wikipedia.org/wiki/Nejvyšš�__soud_České_republiky". But it works fine via Telegram or web Tester...
//		$collection = WikipediaService::processStatic('https://cs.wikipedia.org/wiki/Nejvyšší_soud_České_republiky')->getCollection(); // same as above just urldecoded
//		$this->assertCount(1, $collection);
//		$this->assertSame('49.205193,16.602196', $collection[0]->__toString());

		// pages from "Random article" on en.wikipedia.org
		$collection = WikipediaService::processStatic('https://en.wikipedia.org/wiki/Conneaut_High_School')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('41.947222,-80.560833', $collection[0]->__toString());

		$collection = WikipediaService::processStatic('https://en.wikipedia.org/wiki/Birken_Forest_Buddhist_Monastery')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.431697,-120.185119', $collection[0]->__toString());

		$collection = WikipediaService::processStatic('https://en.wikipedia.org/wiki/Christchurch_F.C.')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.772835,-1.817606', $collection[0]->__toString());

		$collection = WikipediaService::processStatic('https://en.wikipedia.org/wiki/Samba,_Togo')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('9.600000,0.883333', $collection[0]->__toString());

		$collection = WikipediaService::processStatic('https://en.wikipedia.org/wiki/S%C3%A3o_Paulo')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-23.550000,-46.633333', $collection[0]->__toString());

		// mobile URL
		$collection = WikipediaService::processStatic('https://cs.m.wikipedia.org/wiki/City_Tower')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.050278,14.436111', $collection[0]->__toString());

		$collection = WikipediaService::processStatic('https://en.m.wikipedia.org/wiki/Conneaut_High_School')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('41.947222,-80.560833', $collection[0]->__toString());

		// No location
		$this->assertCount(0, WikipediaService::processStatic('https://be.wikipedia.org/wiki/%D0%9F%D0%B0%D0%BD%D0%BA%D1%80%D0%B0%D1%86')->getCollection());
		$this->assertCount(0, WikipediaService::processStatic('https://ka.wikipedia.org/wiki/%E1%83%9E%E1%83%90%E1%83%9C%E1%83%99%E1%83%A0%E1%83%90%E1%83%AA%E1%83%98')->getCollection());

	}

	/**
	 * @group request
	 */
	public function testPermanentUrl(): void
	{
		// all links leads to same location
		$collection = WikipediaService::processStatic('https://cs.wikipedia.org/w/index.php?title=Nejvy%C5%A1%C5%A1%C3%AD_soud_%C4%8Cesk%C3%A9_republiky&oldid=18532372')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.205194,16.602194', $collection[0]->__toString());

		$collection = WikipediaService::processStatic('https://cs.wikipedia.org/w/index.php?oldid=18532372')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.205194,16.602194', $collection[0]->__toString());

		$collection = WikipediaService::processStatic('https://cs.wikipedia.org/w/?oldid=18532372')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.205194,16.602194', $collection[0]->__toString());
	}

	/**
	 * Same page in different languages
	 * @group request
	 */
	public function testNormalUrlMultipleLanguages(): void
	{
		$collection = WikipediaService::processStatic('https://cs.wikipedia.org/wiki/Pankr%C3%A1c_(Praha)')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.057888,14.430914', $collection[0]->__toString());

		// different location
		$collection = WikipediaService::processStatic('https://en.wikipedia.org/wiki/Pankr%C3%A1c')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.056394,14.434878', $collection[0]->__toString());
	}
}
