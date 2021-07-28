<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use PHPUnit\Framework\TestCase;

final class GeneralTest extends TestCase
{
	public function testCheckIfValueInHeaderMatchArray(): void
	{
		$this->assertTrue(\App\Utils\General::checkIfValueInHeaderMatchArray('image/webp;charset=utf-8', ['image/jpeg', 'image/webp']));
		$this->assertTrue(\App\Utils\General::checkIfValueInHeaderMatchArray('ImaGE/JpEg; CHarsEt=utF-8', ['image/jpeg', 'image/webp']));
	}

	public function testGetUrls(): void
	{
		$this->assertSame(\App\Utils\General::getUrls('No link in this message...'), []);

		$this->assertSame(\App\Utils\General::getUrls('https://tomas.palider.cz'), ['https://tomas.palider.cz']);
		$this->assertSame(\App\Utils\General::getUrls('https://tomas.palider.cz/'), ['https://tomas.palider.cz/']);
		$this->assertSame(\App\Utils\General::getUrls('bla https://tomas.palider.cz/ https://ladislav.palider.cz/'), ['https://tomas.palider.cz/', 'https://ladislav.palider.cz/']);
		$this->assertSame(\App\Utils\General::getUrls('https://tomas.palider.cz/, blabla https://ladislav.palider.cz/'), ['https://tomas.palider.cz/', 'https://ladislav.palider.cz/']);
		$this->assertSame(\App\Utils\General::getUrls('Hi there!https://tomas.palider.cz, http://ladislav.palider.cz/ haha'), ['https://tomas.palider.cz', 'http://ladislav.palider.cz/']);
		$this->assertSame(\App\Utils\General::getUrls('Some link https://tomas.palider.cz this is real end.'), ['https://tomas.palider.cz']);
		$this->assertSame(\App\Utils\General::getUrls('Some link https://tomas.palider.cz/ this is real end.'), ['https://tomas.palider.cz/']);
		$this->assertSame(\App\Utils\General::getUrls('Some link from wikipedia https://cs.wikipedia.org/wiki/Piastovsk%C3%A1_v%C4%9B%C5%BE_(T%C4%9B%C5%A1%C3%ADn) this is real end.'), ['https://cs.wikipedia.org/wiki/Piastovsk%C3%A1_v%C4%9B%C5%BE_(T%C4%9B%C5%A1%C3%ADn)']);
		$this->assertSame(\App\Utils\General::getUrls('Some link from wikipedia https://cs.wikipedia.org/wiki/Piastovská_věž_(Těšín) this is real end.'), ['https://cs.wikipedia.org/wiki/Piastovská_věž_(Těšín)']);
	}

	public final function testFindMapyCzApiCoords(): void
	{
		$this->assertSame('48.890900,13.485400', \App\Utils\General::findMapyCzApiCoords('var center = SMap.Coords.fromWGS84(13.4854,48.8909);')->__toString());
		$this->assertSame('-48.890900,-13.485400', \App\Utils\General::findMapyCzApiCoords('var center = SMap.Coords.fromWGS84(-13.4854,-48.8909);')->__toString());
		$this->assertSame('48.890900,13.485400', \App\Utils\General::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(13.4854, 48.8909) some more text')->__toString());
		$this->assertSame('48.890900,13.485400', \App\Utils\General::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(   13.4854,    48.8909  )')->__toString());
		$this->assertSame('48.890900,13.485400', \App\Utils\General::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(13.4854, 
48.8909) some text')->__toString());
		$this->assertSame('48.890900,13.485400', \App\Utils\General::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(
		13.4854, 
  48.8909
) some text')->__toString());

		$this->assertNull(\App\Utils\General::findMapyCzApiCoords('some random text'));
		$this->assertNull(\App\Utils\General::findMapyCzApiCoords('var center = SMap.      Coords.fromWGS84(13.4854,48.8909);'));
	}
	public final function testFindMapyCzApiCoordsInvalid(): void
	{
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Latitude coordinate must be numeric between or equal from -90 to 90 degrees.');
		\App\Utils\General::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(13.4854, 98.8909)');

	}
}
