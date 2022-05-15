<?php declare(strict_types=1);

namespace Tests\Utils;

use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Utils\Utils;
use PHPUnit\Framework\TestCase;

final class UtilsTest extends TestCase
{
	public function testCheckIfValueInHeaderMatchArray(): void
	{
		$this->assertTrue(Utils::checkIfValueInHeaderMatchArray('image/webp;charset=utf-8', ['image/jpeg', 'image/webp']));
		$this->assertTrue(Utils::checkIfValueInHeaderMatchArray('ImaGE/JpEg; CHarsEt=utF-8', ['image/jpeg', 'image/webp']));
	}

	public function testGetUrls(): void
	{
		$this->assertSame(Utils::getUrls('No link in this message...'), []);

		$this->assertSame(Utils::getUrls('https://tomas.palider.cz'), ['https://tomas.palider.cz']);
		$this->assertSame(Utils::getUrls('https://tomas.palider.cz/'), ['https://tomas.palider.cz/']);
		$this->assertSame(Utils::getUrls('bla https://tomas.palider.cz/ https://ladislav.palider.cz/'), ['https://tomas.palider.cz/', 'https://ladislav.palider.cz/']);
		$this->assertSame(Utils::getUrls('https://tomas.palider.cz/, blabla https://ladislav.palider.cz/'), ['https://tomas.palider.cz/', 'https://ladislav.palider.cz/']);
		$this->assertSame(Utils::getUrls('Hi there!https://tomas.palider.cz, http://ladislav.palider.cz/ haha'), ['https://tomas.palider.cz', 'http://ladislav.palider.cz/']);
		$this->assertSame(Utils::getUrls('Some link https://tomas.palider.cz this is real end.'), ['https://tomas.palider.cz']);
		$this->assertSame(Utils::getUrls('Some link https://tomas.palider.cz/ this is real end.'), ['https://tomas.palider.cz/']);
		$this->assertSame(Utils::getUrls('Some link from wikipedia https://cs.wikipedia.org/wiki/Piastovsk%C3%A1_v%C4%9B%C5%BE_(T%C4%9B%C5%A1%C3%ADn) this is real end.'), ['https://cs.wikipedia.org/wiki/Piastovsk%C3%A1_v%C4%9B%C5%BE_(T%C4%9B%C5%A1%C3%ADn)']);
		$this->assertSame(Utils::getUrls('Some link from wikipedia https://cs.wikipedia.org/wiki/Piastovská_věž_(Těšín) this is real end.'), ['https://cs.wikipedia.org/wiki/Piastovská_věž_(Těšín)']);
	}

	public final function testFindMapyCzApiCoords(): void
	{
		$this->assertSame('48.890900,13.485400', Utils::findMapyCzApiCoords('var center = SMap.Coords.fromWGS84(13.4854,48.8909);')->__toString());
		$this->assertSame('-48.890900,-13.485400', Utils::findMapyCzApiCoords('var center = SMap.Coords.fromWGS84(-13.4854,-48.8909);')->__toString());
		$this->assertSame('48.890900,13.485400', Utils::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(13.4854, 48.8909) some more text')->__toString());
		$this->assertSame('48.890900,13.485400', Utils::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(   13.4854,    48.8909  )')->__toString());
		$this->assertSame('48.890900,13.485400', Utils::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(13.4854, 
48.8909) some text')->__toString());
		$this->assertSame('48.890900,13.485400', Utils::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(
		13.4854, 
  48.8909
) some text')->__toString());

		$this->assertNull(Utils::findMapyCzApiCoords('some random text'));
		$this->assertNull(Utils::findMapyCzApiCoords('var center = SMap.      Coords.fromWGS84(13.4854,48.8909);'));
	}

	public final function testFindMapyCzApiCoordsInvalid(): void
	{
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Latitude coordinate must be numeric between or equal from -90 to 90 degrees.');
		Utils::findMapyCzApiCoords('some text blabla SMap.Coords.fromWGS84(13.4854, 98.8909)');
	}
}
