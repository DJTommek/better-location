<?php declare(strict_types=1);

namespace Tests\Geonames;

use App\Factory;
use PHPUnit\Framework\TestCase;

final class GeonamesTest extends TestCase
{
	/**
	 * @group request
	 */
	public function testRequest(): void
	{
		$geonames = Factory::geonames();
		$prague = new \DJTommek\Coordinates\Coordinates(50.087451, 14.420671);
		$timezone = $geonames->timezone($prague->lat, $prague->lon);

		$this->assertSame($prague->lat, $timezone->lat);
		$this->assertSame($prague->lon, $timezone->lng);
		$this->assertSame('Europe/Prague', $timezone->timezoneId);
	}
}
