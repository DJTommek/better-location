<?php declare(strict_types=1);

namespace Tests\BetterLocation;

use App\BetterLocation\GooglePlaceApi;
use App\BetterLocation\VcardLocationParser;
use App\Config;
use App\Factory;
use PHPUnit\Framework\TestCase;

final class VcardLocationParserTest extends TestCase
{
	private static GooglePlaceApi $api;

	public static function setUpBeforeClass(): void
	{
		if (!Config::isGooglePlaceApi()) {
			self::markTestSkipped('Missing Google API key');
		}

		self::$api = Factory::googlePlaceApi();
	}

	/**
	 * @group request
	 */
	public function testBasic(): void
	{
		$parser = new VcardLocationParser('BEGIN:VCARD
VERSION:3.0
FN:Tomas Palider
ADR;HOME;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:;;22 Mikul=C3=A1=C5=A1sk=C3=A1;;;;Czechia
ADR;type=WORK:345 Spear Street;;;San Francisco;California;94105;
ADR;OTHER:;;;Stewart Duff Drive;Wellington;;6022;NZ
URL:https://tomas.palider.cz/
BDAY:1993-07-25
TEL;CELL;PREF:+420123456789
END:VCARD', self::$api);
		$parser->process();

		$collection = $parser->getCollection();
		$this->assertCount(3, $collection);

		$this->assertSame('50.087400,14.419857', (string)$collection[0]);
		$this->assertSame('Contact Tomas Palider HOME address', $collection[0]->getPrefixMessage());

		$this->assertSame('37.789955,-122.389913', (string)$collection[1]);
		$this->assertSame('Contact Tomas Palider WORK address', $collection[1]->getPrefixMessage());

		$this->assertSame('-41.330520,174.812066', (string)$collection[2]);
		$this->assertSame('Contact Tomas Palider OTHER address', $collection[2]->getPrefixMessage());
	}

	public function testEmpty(): void
	{
		$parser = new VcardLocationParser('BEGIN:VCARD
VERSION:3.0
FN:Tomas Palider
URL:https://tomas.palider.cz/
END:VCARD', self::$api);
		$parser->process();

		$collection = $parser->getCollection();
		$this->assertTrue($collection->isEmpty());
	}

	public function testUnprocessedYet(): void
	{
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Run App\BetterLocation\VcardLocationParser::process() first.');
		$parser = new VcardLocationParser('anything here', self::$api);
		$parser->getCollection();
	}
}
