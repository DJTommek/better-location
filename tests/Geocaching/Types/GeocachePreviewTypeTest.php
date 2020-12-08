<?php declare(strict_types=1);

use App\Geocaching\Types\GeocachePreviewType;
use PHPUnit\Framework\TestCase;

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
		$this->assertSame('GC3DYC4', self::$GC3DYC4->code);
		$this->assertSame('Find the bug', self::$GC3DYC4->name);
		$this->assertSame(2.5, self::$GC3DYC4->difficulty);
		$this->assertSame(1.5, self::$GC3DYC4->terrain);
		$this->assertSame(8, self::$GC3DYC4->geocacheType);
		$this->assertSame(2, self::$GC3DYC4->containerType);
		$this->assertSame('/geocache/GC3DYC4', self::$GC3DYC4->detailsUrl);
		$this->assertSame(0, self::$GC3DYC4->cacheStatus);
		$this->assertSame(50.087717, self::$GC3DYC4->postedCoordinates->latitude);
		$this->assertSame(14.42115, self::$GC3DYC4->postedCoordinates->longitude);
		$this->assertIsArray(self::$GC3DYC4->recentActivities);

		$this->assertInstanceOf(GeocachePreviewType::class, self::$GC7X2M6);
		$this->assertSame('GC7X2M6', self::$GC7X2M6->code);
		$this->assertSame('TJ SOKOL Praha - Krc (080)', self::$GC7X2M6->name);
		$this->assertSame(1.0, self::$GC7X2M6->difficulty);
		$this->assertSame(1.5, self::$GC7X2M6->terrain);
		$this->assertSame(5, self::$GC7X2M6->geocacheType);
		$this->assertSame(4, self::$GC7X2M6->containerType);
		$this->assertSame('/geocache/GC7X2M6', self::$GC7X2M6->detailsUrl);
		$this->assertSame(0, self::$GC7X2M6->cacheStatus);
		$this->assertSame(50.036817, self::$GC7X2M6->postedCoordinates->latitude);
		$this->assertSame(14.448567, self::$GC7X2M6->postedCoordinates->longitude);
		$this->assertIsArray(self::$GC7X2M6->recentActivities);

		$this->assertInstanceOf(GeocachePreviewType::class, self::$GC7B7HB);
		$this->assertSame('GC7B7HB', self::$GC7B7HB->code);
		$this->assertSame('Praha - Vysehrad', self::$GC7B7HB->name);
		$this->assertSame(1.0, self::$GC7B7HB->difficulty);
		$this->assertSame(1.0, self::$GC7B7HB->terrain);
		$this->assertSame(4, self::$GC7B7HB->geocacheType);
		$this->assertSame(5, self::$GC7B7HB->containerType);
		$this->assertSame('/geocache/GC7B7HB', self::$GC7B7HB->detailsUrl);
		$this->assertSame(0, self::$GC7B7HB->cacheStatus);
		$this->assertSame(50.065933, self::$GC7B7HB->postedCoordinates->latitude);
		$this->assertSame(14.417417, self::$GC7B7HB->postedCoordinates->longitude);
		$this->assertIsArray(self::$GC7B7HB->recentActivities);
	}

	public function testUpdated(): void
	{
		$this->assertInstanceOf(DateTimeImmutable::class, self::$GC3DYC4->placedDate);
		$this->assertSame('2012.03.05 00:00:00', self::$GC3DYC4->placedDate->format('Y.m.d H:i:s'));

		$this->assertInstanceOf(DateTimeImmutable::class, self::$GC7X2M6->placedDate);
		$this->assertSame('2018.08.31 00:00:00', self::$GC7X2M6->placedDate->format('Y.m.d H:i:s'));

		$this->assertInstanceOf(DateTimeImmutable::class, self::$GC7B7HB->placedDate);
		$this->assertSame('2017.08.30 00:00:00', self::$GC7B7HB->placedDate->format('Y.m.d H:i:s'));
	}

	public function testMethods(): void
	{
		$this->assertSame('https://www.geocaching.com/geocache/GC3DYC4', self::$GC3DYC4->getLink());
		$this->assertSame('Mystery', self::$GC3DYC4->getType());
		$this->assertSame('micro', self::$GC3DYC4->getSize());
		$this->assertSame('active', self::$GC3DYC4->getStatus());
		$this->assertFalse(self::$GC3DYC4->isDisabled());
		$this->assertSame('Mystery micro', self::$GC3DYC4->getTypeAndSize());

		$this->assertSame('https://www.geocaching.com/geocache/GC7X2M6', self::$GC7X2M6->getLink());
		$this->assertSame('Letterbox Hybrid', self::$GC7X2M6->getType());
		$this->assertSame('large', self::$GC7X2M6->getSize());
		$this->assertSame('active', self::$GC7X2M6->getStatus());
		$this->assertFalse(self::$GC7X2M6->isDisabled());
		$this->assertSame('Letterbox Hybrid large', self::$GC7X2M6->getTypeAndSize());

		$this->assertSame('https://www.geocaching.com/geocache/GC7B7HB', self::$GC7B7HB->getLink());
		$this->assertSame('Virtual', self::$GC7B7HB->getType());
		$this->assertSame('virtual', self::$GC7B7HB->getSize());
		$this->assertSame('active', self::$GC7B7HB->getStatus());
		$this->assertFalse(self::$GC7B7HB->isDisabled());
		$this->assertSame('Virtual', self::$GC7B7HB->getTypeAndSize());
	}
}
