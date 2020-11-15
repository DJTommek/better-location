<?php declare(strict_types=1);

use App\Geocaching\Types\GeocachePreviewType;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../src/bootstrap.php';


final class GeocachePreviewTypeTest extends TestCase
{
	private static $GC3DYC4;

	public static function setUpBeforeClass(): void
	{
		$content = file_get_contents(__DIR__ . '/../fixtures/GC3DYC4.json');
		$json = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
		self::$GC3DYC4 = GeocachePreviewType::createFromVariable($json);
	}

	public function testInitial(): void
	{
		$this->assertInstanceOf(GeocachePreviewType::class, self::$GC3DYC4);
		$this->assertEquals('GC3DYC4', self::$GC3DYC4->code);
		$this->assertEquals('Find the bug', self::$GC3DYC4->name);
		$this->assertEquals(2.5, self::$GC3DYC4->difficulty);
		$this->assertEquals(8, self::$GC3DYC4->geocacheType);
		$this->assertEquals(2, self::$GC3DYC4->containerType);
		$this->assertEquals('/geocache/GC3DYC4', self::$GC3DYC4->detailsUrl);
		$this->assertEquals(0, self::$GC3DYC4->cacheStatus);
		$this->assertEquals(50.087717, self::$GC3DYC4->postedCoordinates->latitude);
		$this->assertEquals(14.42115, self::$GC3DYC4->postedCoordinates->longitude);
		$this->assertIsArray(self::$GC3DYC4->recentActivities);
	}

	public function testUpdated(): void
	{
		$this->assertInstanceOf(DateTimeImmutable::class, self::$GC3DYC4->placedDate);
		$this->assertEquals('2012.03.05 00:00:00', self::$GC3DYC4->placedDate->format('Y.m.d H:i:s'));
	}

	public function testMethods(): void
	{
		$this->assertEquals('https://www.geocaching.com/geocache/GC3DYC4', self::$GC3DYC4->getLink());
		$this->assertEquals('Mystery', self::$GC3DYC4->getType());
		$this->assertEquals('micro', self::$GC3DYC4->getSize());
		$this->assertEquals('active', self::$GC3DYC4->getStatus());
		$this->assertFalse(self::$GC3DYC4->isDisabled());
		$this->assertEquals('Mystery micro', self::$GC3DYC4->getTypeAndSize());
	}
}
