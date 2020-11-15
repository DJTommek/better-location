<?php declare(strict_types=1);

use App\Geocaching\Types\GeocachePreviewType;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../src/bootstrap.php';


final class GeocachePreviewTypeTest extends TestCase
{
	private static $GC3DYC4;
	private static $GC7X2M6;
	private static $GC7B7HB;

	public static function setUpBeforeClass(): void
	{
		$content = file_get_contents(__DIR__ . '/../fixtures/GC3DYC4.json');
		$json = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
		self::$GC3DYC4 = GeocachePreviewType::createFromVariable($json);

		$content = file_get_contents(__DIR__ . '/../fixtures/GC7X2M6.json');
		$json = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
		self::$GC7X2M6 = GeocachePreviewType::createFromVariable($json);

		$content = file_get_contents(__DIR__ . '/../fixtures/GC7B7HB.json');
		$json = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
		self::$GC7B7HB = GeocachePreviewType::createFromVariable($json);
	}

	public function testInitial(): void
	{
		$this->assertInstanceOf(GeocachePreviewType::class, self::$GC3DYC4);
		$this->assertEquals('GC3DYC4', self::$GC3DYC4->code);
		$this->assertEquals('Find the bug', self::$GC3DYC4->name);
		$this->assertEquals(2.5, self::$GC3DYC4->difficulty);
		$this->assertEquals(1.5, self::$GC3DYC4->terrain);
		$this->assertEquals(8, self::$GC3DYC4->geocacheType);
		$this->assertEquals(2, self::$GC3DYC4->containerType);
		$this->assertEquals('/geocache/GC3DYC4', self::$GC3DYC4->detailsUrl);
		$this->assertEquals(0, self::$GC3DYC4->cacheStatus);
		$this->assertEquals(50.087717, self::$GC3DYC4->postedCoordinates->latitude);
		$this->assertEquals(14.42115, self::$GC3DYC4->postedCoordinates->longitude);
		$this->assertIsArray(self::$GC3DYC4->recentActivities);

		$this->assertInstanceOf(GeocachePreviewType::class, self::$GC7X2M6);
		$this->assertEquals('GC7X2M6', self::$GC7X2M6->code);
		$this->assertEquals('TJ SOKOL Praha - Krc (080)', self::$GC7X2M6->name);
		$this->assertEquals(1.0, self::$GC7X2M6->difficulty);
		$this->assertEquals(1.5, self::$GC7X2M6->terrain);
		$this->assertEquals(5, self::$GC7X2M6->geocacheType);
		$this->assertEquals(4, self::$GC7X2M6->containerType);
		$this->assertEquals('/geocache/GC7X2M6', self::$GC7X2M6->detailsUrl);
		$this->assertEquals(0, self::$GC7X2M6->cacheStatus);
		$this->assertEquals(50.036817, self::$GC7X2M6->postedCoordinates->latitude);
		$this->assertEquals(14.448567, self::$GC7X2M6->postedCoordinates->longitude);
		$this->assertIsArray(self::$GC7X2M6->recentActivities);

		$this->assertInstanceOf(GeocachePreviewType::class, self::$GC7B7HB);
		$this->assertEquals('GC7B7HB', self::$GC7B7HB->code);
		$this->assertEquals('Praha - Vysehrad', self::$GC7B7HB->name);
		$this->assertEquals(1.0, self::$GC7B7HB->difficulty);
		$this->assertEquals(1.0, self::$GC7B7HB->terrain);
		$this->assertEquals(4, self::$GC7B7HB->geocacheType);
		$this->assertEquals(5, self::$GC7B7HB->containerType);
		$this->assertEquals('/geocache/GC7B7HB', self::$GC7B7HB->detailsUrl);
		$this->assertEquals(0, self::$GC7B7HB->cacheStatus);
		$this->assertEquals(50.065933, self::$GC7B7HB->postedCoordinates->latitude);
		$this->assertEquals(14.417417, self::$GC7B7HB->postedCoordinates->longitude);
		$this->assertIsArray(self::$GC7B7HB->recentActivities);
	}

	public function testUpdated(): void
	{
		$this->assertInstanceOf(DateTimeImmutable::class, self::$GC3DYC4->placedDate);
		$this->assertEquals('2012.03.05 00:00:00', self::$GC3DYC4->placedDate->format('Y.m.d H:i:s'));

		$this->assertInstanceOf(DateTimeImmutable::class, self::$GC7X2M6->placedDate);
		$this->assertEquals('2018.08.31 00:00:00', self::$GC7X2M6->placedDate->format('Y.m.d H:i:s'));

		$this->assertInstanceOf(DateTimeImmutable::class, self::$GC7B7HB->placedDate);
		$this->assertEquals('2017.08.30 00:00:00', self::$GC7B7HB->placedDate->format('Y.m.d H:i:s'));
	}

	public function testMethods(): void
	{
		$this->assertEquals('https://www.geocaching.com/geocache/GC3DYC4', self::$GC3DYC4->getLink());
		$this->assertEquals('Mystery', self::$GC3DYC4->getType());
		$this->assertEquals('micro', self::$GC3DYC4->getSize());
		$this->assertEquals('active', self::$GC3DYC4->getStatus());
		$this->assertFalse(self::$GC3DYC4->isDisabled());
		$this->assertEquals('Mystery micro', self::$GC3DYC4->getTypeAndSize());

		$this->assertEquals('https://www.geocaching.com/geocache/GC7X2M6', self::$GC7X2M6->getLink());
		$this->assertEquals('Letterbox Hybrid', self::$GC7X2M6->getType());
		$this->assertEquals('large', self::$GC7X2M6->getSize());
		$this->assertEquals('active', self::$GC7X2M6->getStatus());
		$this->assertFalse(self::$GC7X2M6->isDisabled());
		$this->assertEquals('Letterbox Hybrid large', self::$GC7X2M6->getTypeAndSize());

		$this->assertEquals('https://www.geocaching.com/geocache/GC7B7HB', self::$GC7B7HB->getLink());
		$this->assertEquals('Virtual', self::$GC7B7HB->getType());
		$this->assertEquals('virtual', self::$GC7B7HB->getSize());
		$this->assertEquals('active', self::$GC7B7HB->getStatus());
		$this->assertFalse(self::$GC7B7HB->isDisabled());
		$this->assertEquals('Virtual', self::$GC7B7HB->getTypeAndSize());
	}
}
