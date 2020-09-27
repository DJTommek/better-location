<?php declare(strict_types=1);

use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotSupportedException;
use BetterLocation\Service\WikipediaService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../src/bootstrap.php';


final class WikipediaServiceTest extends TestCase
{
	public function testGenerateShareLink(): void {
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Share link is not supported.');
		WikipediaService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void {
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		WikipediaService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValid(): void {
		$this->assertTrue(WikipediaService::isValid('https://en.wikipedia.org/wiki/Conneaut_High_School'));
		$this->assertTrue(WikipediaService::isValid('https://cs.wikipedia.org/wiki/City_Tower'));
		// permanent URLs
		$this->assertTrue(WikipediaService::isValid('https://cs.wikipedia.org/w/index.php?title=Nejvy%C5%A1%C5%A1%C3%AD_soud_%C4%8Cesk%C3%A9_republiky&oldid=18532372'));
		$this->assertTrue(WikipediaService::isValid('https://cs.wikipedia.org/w/index.php?oldid=18532372'));
		$this->assertTrue(WikipediaService::isValid('https://cs.wikipedia.org/w/?oldid=18532372'));

		$this->assertFalse(WikipediaService::isValid('https://wikipedia.org/'));
		$this->assertFalse(WikipediaService::isValid('https://en.wikipedia.org/'));
		$this->assertFalse(WikipediaService::isValid('https://cs.wikipedia.org/'));

		$this->assertFalse(WikipediaService::isValid('some invalid url'));
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testNormalUrl(): void {
		$this->assertEquals('50.050278,14.436111', WikipediaService::parseCoords('https://cs.wikipedia.org/wiki/City_Tower')->__toString());
		$this->assertEquals('50.083333,14.416667', WikipediaService::parseCoords('https://cs.wikipedia.org/wiki/Praha')->__toString());
		$this->assertEquals('49.205300,16.602200', WikipediaService::parseCoords('https://cs.wikipedia.org/wiki/Nejvy%C5%A1%C5%A1%C3%AD_soud_%C4%8Cesk%C3%A9_republiky')->__toString());

		// pages from "Random article" on en.wikipedia.org
		$this->assertEquals('41.947222,-80.560833', WikipediaService::parseCoords('https://en.wikipedia.org/wiki/Conneaut_High_School')->__toString());
		$this->assertEquals('50.431697,-120.185119', WikipediaService::parseCoords('https://en.wikipedia.org/wiki/Birken_Forest_Buddhist_Monastery')->__toString());
		$this->assertEquals('50.772835,-1.817606', WikipediaService::parseCoords('https://en.wikipedia.org/wiki/Christchurch_F.C.')->__toString());
		$this->assertEquals('9.600000,0.883333', WikipediaService::parseCoords('https://en.wikipedia.org/wiki/Samba,_Togo')->__toString());
		$this->assertEquals('-23.550000,-46.633333', WikipediaService::parseCoords('https://en.wikipedia.org/wiki/S%C3%A3o_Paulo')->__toString());
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testPermanentUrl(): void {
		// all links leads to same location
		$this->assertEquals('49.205300,16.602200', WikipediaService::parseCoords('https://cs.wikipedia.org/w/index.php?title=Nejvy%C5%A1%C5%A1%C3%AD_soud_%C4%8Cesk%C3%A9_republiky&oldid=18532372')->__toString());
		$this->assertEquals('49.205300,16.602200', WikipediaService::parseCoords('https://cs.wikipedia.org/w/index.php?oldid=18532372')->__toString());
		$this->assertEquals('49.205300,16.602200', WikipediaService::parseCoords('https://cs.wikipedia.org/w/?oldid=18532372')->__toString());
	}

	/**
	 * Same page in different languages (part 1)
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testNormalUrlMultipleLanguages1(): void {
		$this->assertEquals('50.057888,14.430914', WikipediaService::parseCoords('https://cs.wikipedia.org/wiki/Pankr%C3%A1c_(Praha)')->__toString());
		// different location
		$this->assertEquals('50.056394,14.434878', WikipediaService::parseCoords('https://en.wikipedia.org/wiki/Pankr%C3%A1c')->__toString());
	}

	/**
	 * Same page in different languages (part 2)
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testNormalUrlMultipleLanguages2(): void {
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('No valid location found');
		WikipediaService::parseCoords('https://be.wikipedia.org/wiki/%D0%9F%D0%B0%D0%BD%D0%BA%D1%80%D0%B0%D1%86');
	}

	/**
	 * Same page in different languages (part 3)
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testNormalUrlMultipleLanguages3(): void {
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('No valid location found');
		WikipediaService::parseCoords('https://ka.wikipedia.org/wiki/%E1%83%9E%E1%83%90%E1%83%9C%E1%83%99%E1%83%A0%E1%83%90%E1%83%AA%E1%83%98');
	}
}
