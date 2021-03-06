<?php declare(strict_types=1);

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
}
