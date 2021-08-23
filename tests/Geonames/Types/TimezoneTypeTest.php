<?php declare(strict_types=1);

use App\Geonames\Types\TimezoneType;
use App\Utils\Coordinates;
use PHPUnit\Framework\TestCase;

final class TimezoneTypeTest extends TestCase
{
	private static $prague;
	private static $newYork;
	private static $tehran;

	public static function setUpBeforeClass(): void
	{
		$content = file_get_contents(__DIR__ . '/../fixtures/prague.json');
		$json = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
		self::$prague = TimezoneType::fromResponse($json);

		$content = file_get_contents(__DIR__ . '/../fixtures/new_york.json');
		$json = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
		self::$newYork = TimezoneType::fromResponse($json);

		$content = file_get_contents(__DIR__ . '/../fixtures/tehran.json');
		$json = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
		self::$tehran = TimezoneType::fromResponse($json);
	}

	public function testPrague(): void
	{
		$this->assertInstanceOf(TimezoneType::class, self::$prague);
		$this->assertSame(50.087451, self::$prague->lat);
		$this->assertSame(14.420671, self::$prague->lng);

		$this->assertInstanceOf(Coordinates::class, self::$prague->coords);
		$this->assertSame(50.087451, self::$prague->coords->getLat());
		$this->assertSame(14.420671, self::$prague->coords->getLon());

		$this->assertInstanceOf(DateTimeImmutable::class, self::$prague->time);
		$this->assertInstanceOf(DateTimeImmutable::class, self::$prague->sunrise);
		$this->assertInstanceOf(DateTimeImmutable::class, self::$prague->sunset);

		$this->assertSame('2021-08-22T22:32:00+02:00', self::$prague->time->format(DateTime::W3C));
		$this->assertSame('2021-08-22T06:01:00+02:00', self::$prague->sunrise->format(DateTime::W3C));
		$this->assertSame('2021-08-22T20:08:00+02:00', self::$prague->sunset->format(DateTime::W3C));

		$this->assertSame('CZ', self::$prague->countryCode);
		$this->assertSame('Czechia', self::$prague->countryName);

		$this->assertSame(1, self::$prague->gmtOffset);
		$this->assertSame(1, self::$prague->rawOffset);
		$this->assertSame(2, self::$prague->dstOffset);
		$this->assertSame(2, self::$prague->nowOffset);

		$this->assertSame('+01:00', self::$prague->formatGmtOffset());
		$this->assertSame('+01:00', self::$prague->formatRawOffset());
		$this->assertSame('+02:00', self::$prague->formatDstOffset());
		$this->assertSame('+02:00', self::$prague->formatNowOffset());

		$this->assertTrue(self::$prague->isDst());

		$this->assertSame('Europe/Prague', self::$prague->timezoneId);
		$this->assertInstanceOf(DateTimeZone::class, self::$prague->timezone);
		$this->assertSame('Europe/Prague', self::$prague->timezone->getName());
	}

	public function testNewYork(): void
	{
		$this->assertInstanceOf(TimezoneType::class, self::$newYork);
		$this->assertSame(40.7113025, self::$newYork->lat);
		$this->assertSame(-74.0091831, self::$newYork->lng);

		$this->assertInstanceOf(Coordinates::class, self::$newYork->coords);
		$this->assertSame(40.7113025, self::$newYork->coords->getLat());
		$this->assertSame(-74.0091831, self::$newYork->coords->getLon());

		$this->assertInstanceOf(DateTimeImmutable::class, self::$newYork->time);
		$this->assertInstanceOf(DateTimeImmutable::class, self::$newYork->sunrise);
		$this->assertInstanceOf(DateTimeImmutable::class, self::$newYork->sunset);

		$this->assertSame('2021-08-22T16:42:00-04:00', self::$newYork->time->format(DateTime::W3C));
		$this->assertSame('2021-08-22T06:12:00-04:00', self::$newYork->sunrise->format(DateTime::W3C));
		$this->assertSame('2021-08-22T19:44:00-04:00', self::$newYork->sunset->format(DateTime::W3C));

		$this->assertSame('US', self::$newYork->countryCode);
		$this->assertSame('United States', self::$newYork->countryName);

		$this->assertSame(-5, self::$newYork->gmtOffset);
		$this->assertSame(-5, self::$newYork->rawOffset);
		$this->assertSame(-4, self::$newYork->dstOffset);
		$this->assertSame(-4, self::$newYork->nowOffset);

		$this->assertSame('-05:00', self::$newYork->formatGmtOffset());
		$this->assertSame('-05:00', self::$newYork->formatRawOffset());
		$this->assertSame('-04:00', self::$newYork->formatDstOffset());
		$this->assertSame('-04:00', self::$newYork->formatDstOffset());

		$this->assertTrue(self::$newYork->isDst());

		$this->assertSame('America/New_York', self::$newYork->timezoneId);
		$this->assertInstanceOf(DateTimeZone::class, self::$newYork->timezone);
		$this->assertSame('America/New_York', self::$newYork->timezone->getName());
	}

	public function testTehran(): void
	{
		$this->assertInstanceOf(TimezoneType::class, self::$tehran);
		$this->assertSame(35.6931492, self::$tehran->lat);
		$this->assertSame(51.3226672, self::$tehran->lng);

		$this->assertInstanceOf(Coordinates::class, self::$tehran->coords);
		$this->assertSame(35.6931492, self::$tehran->coords->getLat());
		$this->assertSame(51.3226672, self::$tehran->coords->getLon());

		$this->assertInstanceOf(DateTimeImmutable::class, self::$tehran->time);
		$this->assertInstanceOf(DateTimeImmutable::class, self::$tehran->sunrise);
		$this->assertInstanceOf(DateTimeImmutable::class, self::$tehran->sunset);

		$this->assertSame('2021-08-23T01:17:00+04:30', self::$tehran->time->format(DateTime::W3C));
		$this->assertSame('2021-08-22T06:28:00+04:30', self::$tehran->sunrise->format(DateTime::W3C)); // possible bug in API data? Should be 2021-08-23
		$this->assertSame('2021-08-22T19:46:00+04:30', self::$tehran->sunset->format(DateTime::W3C)); // possible bug in API data? Should be 2021-08-23

		$this->assertSame('IR', self::$tehran->countryCode);
		$this->assertSame('Iran', self::$tehran->countryName);

		$this->assertSame(3.5, self::$tehran->gmtOffset);
		$this->assertSame(3.5, self::$tehran->rawOffset);
		$this->assertSame(4.5, self::$tehran->dstOffset);
		$this->assertSame(4.5, self::$tehran->nowOffset);

		$this->assertSame('+03:30', self::$tehran->formatGmtOffset());
		$this->assertSame('+03:30', self::$tehran->formatRawOffset());
		$this->assertSame('+04:30', self::$tehran->formatDstOffset());
		$this->assertSame('+04:30', self::$tehran->formatNowOffset());

		$this->assertTrue(self::$tehran->isDst());

		$this->assertSame('Asia/Tehran', self::$tehran->timezoneId);
		$this->assertInstanceOf(DateTimeZone::class, self::$tehran->timezone);
		$this->assertSame('Asia/Tehran', self::$tehran->timezone->getName());
	}

}
