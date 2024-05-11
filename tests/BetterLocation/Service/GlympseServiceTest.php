<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\GlympseService;
use App\Config;
use PHPUnit\Framework\TestCase;

final class GlympseServiceTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		if (!Config::isGlympse()) {
			self::markTestSkipped('Glympse is not configured');
		}
	}

	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		GlympseService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		GlympseService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValidInvite(): void
	{
		$this->assertTrue(GlympseService::isValidStatic('https://glympse.com/ABCD-EFGH'));
		$this->assertTrue(GlympseService::isValidStatic('https://www.glympse.com/ABCD-EFGH'));
		$this->assertTrue(GlympseService::isValidStatic('http://glympse.com/abCD-EFgh'));
		$this->assertTrue(GlympseService::isValidStatic('http://www.glympse.com/ABCD-EFGH'));
		$this->assertTrue(GlympseService::isValidStatic('https://gLYMpse.com/ABCD-EFGH'));
		$this->assertTrue(GlympseService::isValidStatic('https://glympse.com/AB12-EF34'));
		$this->assertTrue(GlympseService::isValidStatic('https://glympse.com/AB1-EF3'));

		$this->assertFalse(GlympseService::isValidStatic('https://glympse.com/'));
		$this->assertFalse(GlympseService::isValidStatic('https://glympse.cz/'));
		$this->assertFalse(GlympseService::isValidStatic('https://glympse.com'));
		$this->assertFalse(GlympseService::isValidStatic('https://glympse.cz'));
		$this->assertFalse(GlympseService::isValidStatic('https://glympse.cz/ABCD-EFGH'));
		$this->assertFalse(GlympseService::isValidStatic('https://glympse.com/ABCDEFGH'));
	}

	public function testIsValidGroup(): void
	{
		$this->assertTrue(GlympseService::isValidStatic('https://glympse.com/!BetterLocationBot'));
		$this->assertTrue(GlympseService::isValidStatic('https://www.glympse.com/!BetterLocationBot'));
		$this->assertTrue(GlympseService::isValidStatic('http://glympse.com/!BetterLocationBot'));
		$this->assertTrue(GlympseService::isValidStatic('http://www.glympse.com/!BetterLocationBot'));
		$this->assertTrue(GlympseService::isValidStatic('https://gLYMpse.com/!BetterLocationBot'));
		$this->assertTrue(GlympseService::isValidStatic('https://glympse.com/!BetterLocationBot123'));
		$this->assertTrue(GlympseService::isValidStatic('https://glympse.com/!Better157LocationBot'));
	}

	public function testGetGroupId(): void
	{
		$this->assertSame('BetterLocationBot', GlympseService::getGroupIdFromUrl('https://glympse.com/!BetterLocationBot'));
		$this->assertSame('BetterLocationBot', GlympseService::getGroupIdFromUrl('https://www.glympse.com/!BetterLocationBot'));
		$this->assertSame('BetterLocationBot', GlympseService::getGroupIdFromUrl('http://glympse.com/!BetterLocationBot'));
		$this->assertSame('BetterLocationBot', GlympseService::getGroupIdFromUrl('http://www.glympse.com/!BetterLocationBot'));
		$this->assertSame('BetterLocationBot', GlympseService::getGroupIdFromUrl('https://gLYMpse.com/!BetterLocationBot'));
		$this->assertSame('BetterLocationBot123', GlympseService::getGroupIdFromUrl('https://glympse.com/!BetterLocationBot123'));
		$this->assertSame('Better157LocationBot', GlympseService::getGroupIdFromUrl('https://glympse.com/!Better157LocationBot'));
	}

	public function testGetInviteId(): void
	{
		$this->assertSame('ABCD-EFGH', GlympseService::getInviteIdFromUrl('https://glympse.com/ABCD-EFGH'));
		$this->assertSame('ABCD-EFGH', GlympseService::getInviteIdFromUrl('https://www.glympse.com/ABCD-EFGH'));
		$this->assertSame('abCD-EFgh', GlympseService::getInviteIdFromUrl('http://glympse.com/abCD-EFgh'));
		$this->assertSame('ABCD-EFGH', GlympseService::getInviteIdFromUrl('http://www.glympse.com/ABCD-EFGH'));
		$this->assertSame('ABCD-EFGH', GlympseService::getInviteIdFromUrl('https://gLYMpse.com/ABCD-EFGH'));
		$this->assertSame('AB12-EF34', GlympseService::getInviteIdFromUrl('https://glympse.com/AB12-EF34'));
		$this->assertSame('AB1-EF3', GlympseService::getInviteIdFromUrl('https://glympse.com/AB1-EF3'));
	}
}
