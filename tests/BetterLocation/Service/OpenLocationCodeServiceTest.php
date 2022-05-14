<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\OpenLocationCodeService;
use PHPUnit\Framework\TestCase;

final class OpenLocationCodeServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://plus.codes/9F2P3CPC+X7M3', OpenLocationCodeService::getLink(50.087451, 14.420671));
		$this->assertSame('https://plus.codes/9F2P3GX2+X2XX', OpenLocationCodeService::getLink(50.1, 14.5));
		$this->assertSame('https://plus.codes/3FXPQJX2+X2RR', OpenLocationCodeService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://plus.codes/9C27872X+2X55', OpenLocationCodeService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://plus.codes/3CX7J52X+2X54', OpenLocationCodeService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		OpenLocationCodeService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValidUrl(): void
	{
		$this->assertTrue(OpenLocationCodeService::isValidStatic('https://plus.codes/8FXP74WG+XHW'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('https://plus.codes/8FXP74WG+XH'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('http://plus.codes/8FXP74WG+XHW'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('https://plus.codes/9F2P3CQC+3F'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('https://plus.codes/87G8Q2WV+8P'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('https://plus.codes/7JVW52GR+3V'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('https://plus.codes/47C9G3F3+V2'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('https://plus.codes/4VCPPQ3V+HP'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('https://plus.codes/6GCRMQQH+W7'));

		$this->assertFalse(OpenLocationCodeService::isValidStatic('http://plus.codes/'));
		$this->assertFalse(OpenLocationCodeService::isValidStatic('http://bla.codes/8FXP74WG+XHW'));
		$this->assertFalse(OpenLocationCodeService::isValidStatic('https://plus.codes/8FXP74WG+X')); // invalid number of characters
		$this->assertFalse(OpenLocationCodeService::isValidStatic('https://plus.codes/8FXP71WG+XHW')); // invalid character, number 1
	}

	public function testIsValidPlusCode(): void
	{
		$this->assertTrue(OpenLocationCodeService::isValidStatic('8FXP74WG+XHW'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('8FXP74WG+XH'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('9F2P3CQC+3F'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('87G8Q2WV+8P'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('7JVW52GR+3V'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('47C9G3F3+V2'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('4VCPPQ3V+HP'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('6GCRMQQH+W7'));

		$this->assertFalse(OpenLocationCodeService::isValidStatic('8FXP74WG+X')); // invalid number of characters
		$this->assertFalse(OpenLocationCodeService::isValidStatic('8FXP71WG+XHW')); // invalid character, number 1
	}

	public function testProcessUrl(): void
	{
		$collection = OpenLocationCodeService::processStatic('https://plus.codes/8FXP74WG+XHW')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.297487,14.126453', $collection->getFirst()->__toString());

		$collection = OpenLocationCodeService::processStatic('http://plus.codes/8FXP74WG+XHW')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.297487,14.126453', $collection->getFirst()->__toString());

		$collection = OpenLocationCodeService::processStatic('http://plus.codes/9F2P3CQC+3F')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087688,14.421188', $collection->getFirst()->__toString());

		$collection = OpenLocationCodeService::processStatic('http://plus.codes/87G8Q2WV+8P')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('40.795812,-73.955687', $collection->getFirst()->__toString());

		$collection = OpenLocationCodeService::processStatic('http://plus.codes/7JVW52GR+3V')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('27.175188,78.042188', $collection->getFirst()->__toString());

		$collection = OpenLocationCodeService::processStatic('http://plus.codes/47C9G3F3+V2')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-41.475313,-72.947438', $collection->getFirst()->__toString());

		$collection = OpenLocationCodeService::processStatic('http://plus.codes/4VCPPQ3V+HP')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-41.296062,174.794313', $collection->getFirst()->__toString());
	}

	/**
	 * Full codes from https://github.com/google/open-location-code/blob/d47d9f9b95e9f306628396e1b30aaf275f83a5d4/test_data/validityTests.csv
	 */
	public function testIsValidFullPlusCodeRepo(): void
	{
		$this->assertTrue(OpenLocationCodeService::isValidStatic('8FWC2345+G6'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('8FWC2345+G6G'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('8fwc2345+'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('8FWCX400+'));
		// Invalid codes
		$this->assertFalse(OpenLocationCodeService::isValidStatic('G+'));
		$this->assertFalse(OpenLocationCodeService::isValidStatic('+'));
		$this->assertFalse(OpenLocationCodeService::isValidStatic('8FWC2345+G'));
		$this->assertFalse(OpenLocationCodeService::isValidStatic('8FWC2_45+G6'));
		$this->assertFalse(OpenLocationCodeService::isValidStatic('8FWC2η45+G6'));
		$this->assertFalse(OpenLocationCodeService::isValidStatic('8FWC2345+G6+'));
		$this->assertFalse(OpenLocationCodeService::isValidStatic('8FWC2345G6+'));
		$this->assertFalse(OpenLocationCodeService::isValidStatic('8FWC2300+G6'));
		$this->assertFalse(OpenLocationCodeService::isValidStatic('WC2300+G6g'));
		$this->assertFalse(OpenLocationCodeService::isValidStatic('WC2345+G'));

		// This plus code should be invalid but validator claims that is valid. If you try get coordinates, it throw "Exception : Passed Open Location Code is not a valid full code"
		// $this->assertFalse(OpenLocationCodeService::isValidStatic('WC2300+'));

		// Validate that codes at and exceeding 15 digits are still valid when all their
		// digits are valid, and invalid when not.
		$this->assertTrue(OpenLocationCodeService::isValidStatic('849VGJQF+VX7QR3J'));
		$this->assertFalse(OpenLocationCodeService::isValidStatic('849VGJQF+VX7QR3U'));
		$this->assertTrue(OpenLocationCodeService::isValidStatic('849VGJQF+VX7QR3JW'));
		$this->assertFalse(OpenLocationCodeService::isValidStatic('849VGJQF+VX7QR3JU'));
	}

	/**
	 * Codes from https://github.com/google/open-location-code/blob/d47d9f9b95e9f306628396e1b30aaf275f83a5d4/test_data/validityTests.csv
	 */
	public function testProcessFullPlusCodeRepo(): void
	{
		$collection = OpenLocationCodeService::processStatic('8FWC2345+G6')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.006312,8.058062', $collection[0]->__toString());

		$collection = OpenLocationCodeService::processStatic('8FWC2345+G6G')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.006312,8.058078', $collection[0]->__toString());

		$collection = OpenLocationCodeService::processStatic('8fwc2345+')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.006250,8.058750', $collection[0]->__toString());

		$collection = OpenLocationCodeService::processStatic('8FWCX400+')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.975000,8.125000', $collection[0]->__toString());

		$collection = OpenLocationCodeService::processStatic('849VGJQF+VX7QR3J')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('37.539669,-122.375070', $collection[0]->__toString());

		$collection = OpenLocationCodeService::processStatic('849VGJQF+VX7QR3JW')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('37.539669,-122.375070', $collection[0]->__toString());
	}

	public function testSearchInText(): void
	{
		$collection = OpenLocationCodeService::findInText('some random text before 8FXP74WG+XHW and after');
		$this->assertCount(1, $collection);
		$this->assertSame('49.297487,14.126453', $collection[0]->__toString());

		// Plus code is valid, but to improve success detection rate, two or three codes after + sign are required
		$collection = OpenLocationCodeService::findInText('some random text before 8FWCX400+ and after');
		$this->assertCount(0, $collection);

		$this->assertSame(1, preg_match_all(OpenLocationCodeService::RE_IN_STRING, 'some random text before 8FXP74WG+XHW and after', $matches));
		$this->assertCount(1, $matches[1]);
		$this->assertSame('8FXP74WG+XHW', $matches[1][0]);
		$this->assertTrue(OpenLocationCodeService::isValidStatic($matches[1][0]));
		$collection = OpenLocationCodeService::processStatic($matches[1][0])->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.297487,14.126453', $collection[0]->__toString());

		$this->assertSame(0, preg_match_all(OpenLocationCodeService::RE_IN_STRING, 'Some string without any valid plus code', $matches));
		$this->assertCount(0, $matches[1]);
	}
}
