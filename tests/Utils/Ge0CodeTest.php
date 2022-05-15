<?php declare(strict_types=1);

namespace Tests\Utils;

use App\Utils\Ge0Code;
use PHPUnit\Framework\TestCase;

final class Ge0CodeTest extends TestCase
{
	public function testDecode(): void
	{
		$result = Ge0Code::decode('B4srhdHVVt');
		$this->assertSame(64.523401, $result->lat);
		$this->assertSame(12.123401, $result->lon);
		$this->assertSame(4.25, $result->zoom);

		$result = Ge0Code::decode('44G4Yn7Psx');
		$this->assertSame(50.042366, $result->lat);
		$this->assertSame(14.454461, $result->lon);
		$this->assertSame(18.0, $result->zoom);
	}

	public function testDecodeBorders(): void
	{
		$result = Ge0Code::decode('9_________');
		$this->assertSame(89.999999, $result->lat);
		$this->assertSame(179.999999, $result->lon);
		$this->assertSame(19.25, $result->zoom);

		$result = Ge0Code::decode('9qqqqqqqqq');
		$this->assertSame(89.999999, $result->lat);
		$this->assertSame(-179.999999, $result->lon);
		$this->assertSame(19.25, $result->zoom);

		$result = Ge0Code::decode('AVVVVVVVVV');
		$this->assertSame(-89.999999, $result->lat);
		$this->assertSame(179.999999, $result->lon);
		$this->assertSame(4.0, $result->zoom);

		$result = Ge0Code::decode('AAAAAAAAAA');
		$this->assertSame(-89.999999, $result->lat);
		$this->assertSame(-179.999999, $result->lon);
		$this->assertSame(4.0, $result->zoom);
	}

	public function testEncode(): void
	{
		$this->assertSame('B4srhdHVVt', Ge0Code::encode(64.523401, 12.123401, 4.25)->code);
		$this->assertSame('44G4Yn7Psx', Ge0Code::encode(50.042366, 14.454461, 18)->code);

	}

	public function testEncodeBorders(): void
	{
		$this->assertSame('9_________', Ge0Code::encode(89.999999, 179.999999, 19.25)->code);
		$this->assertSame('9qqqqqqqqq', Ge0Code::encode(89.999999, -180, 19.25)->code);
		$this->assertSame('AVVVVVVVVV', Ge0Code::encode(-90, 179.999999, 4)->code);
		$this->assertSame('AAAAAAAAAA', Ge0Code::encode(-90, -180, 4)->code);
	}


	/**
	 * Generate random coordinate, convert them to code, this code convert back to coordinates and compare them with these randomly generated.
	 * Aaaaaand do it multiple time.
	 */
	public function testRandom(): void
	{
		for ($i = 0; $i < 1000; $i++) {
			$lat = $this->generateRandomLat();
			$lon = $this->generateRandomLon();
			$zoom = rand(4, 19);
			$result = Ge0Code::encode($lat, $lon, $zoom);
			$result2 = Ge0Code::decode($result->code);

			$this->assertTrue(Ge0Code::isValid($result->code));
			$this->assertSame($result->code, $result2->code);
			$this->assertSame($result->zoom, $result2->zoom);
			// precision might be lost while converting back and forth (eg 50.042366 can became 50.042365 or 50.042367)
			$this->assertEqualsWithDelta($lat, $result2->lat, 0.00001);
			$this->assertEqualsWithDelta($lon, $result2->lon, 0.00001);
		}
	}

	private function generateRandomLat(): float
	{
		return rand(-89999999, 89999999) / 1000000;
	}

	private function generateRandomLon(): float
	{
		return rand(-179999999, 179999999) / 1000000;
	}


}
