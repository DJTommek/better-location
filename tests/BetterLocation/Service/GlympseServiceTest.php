<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\GlympseService;
use PHPUnit\Framework\TestCase;

final class GlympseServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Share link is not supported.');
		GlympseService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		GlympseService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValidInvite(): void
	{
		$this->assertTrue(GlympseService::isValid('https://glympse.com/ABCD-EFGH'));
		$this->assertTrue(GlympseService::isValid('https://www.glympse.com/ABCD-EFGH'));
		$this->assertTrue(GlympseService::isValid('http://glympse.com/abCD-EFgh'));
		$this->assertTrue(GlympseService::isValid('http://www.glympse.com/ABCD-EFGH'));
		$this->assertTrue(GlympseService::isValid('https://gLYMpse.com/ABCD-EFGH'));
		$this->assertTrue(GlympseService::isValid('https://glympse.com/AB12-EF34'));
		$this->assertTrue(GlympseService::isValid('https://glympse.com/AB1-EF3'));

		$this->assertFalse(GlympseService::isValid('https://glympse.com/'));
		$this->assertFalse(GlympseService::isValid('https://glympse.cz/'));
		$this->assertFalse(GlympseService::isValid('https://glympse.com'));
		$this->assertFalse(GlympseService::isValid('https://glympse.cz'));
		$this->assertFalse(GlympseService::isValid('https://glympse.cz/ABCD-EFGH'));
		$this->assertFalse(GlympseService::isValid('https://glympse.com/ABCDEFGH'));
	}

	public function testIsValidGroup(): void
	{
		$this->assertTrue(GlympseService::isValid('https://glympse.com/!BetterLocationBot'));
		$this->assertTrue(GlympseService::isValid('https://www.glympse.com/!BetterLocationBot'));
		$this->assertTrue(GlympseService::isValid('http://glympse.com/!BetterLocationBot'));
		$this->assertTrue(GlympseService::isValid('http://www.glympse.com/!BetterLocationBot'));
		$this->assertTrue(GlympseService::isValid('https://gLYMpse.com/!BetterLocationBot'));
		$this->assertTrue(GlympseService::isValid('https://glympse.com/!BetterLocationBot123'));
		$this->assertTrue(GlympseService::isValid('https://glympse.com/!Better157LocationBot'));
	}

	public function testGetGroupId(): void
	{
		$class = new ReflectionClass(GlympseService::class);
		$method = $class->getMethod('getGroupIdFromUrl');
		$method->setAccessible(true);

		$this->assertSame('BetterLocationBot', $method->invokeArgs(null, ['https://glympse.com/!BetterLocationBot']));
		$this->assertSame('BetterLocationBot', $method->invokeArgs(null, ['https://www.glympse.com/!BetterLocationBot']));
		$this->assertSame('BetterLocationBot', $method->invokeArgs(null, ['http://glympse.com/!BetterLocationBot']));
		$this->assertSame('BetterLocationBot', $method->invokeArgs(null, ['http://www.glympse.com/!BetterLocationBot']));
		$this->assertSame('BetterLocationBot', $method->invokeArgs(null, ['https://gLYMpse.com/!BetterLocationBot']));
		$this->assertSame('BetterLocationBot123', $method->invokeArgs(null, ['https://glympse.com/!BetterLocationBot123']));
		$this->assertSame('Better157LocationBot', $method->invokeArgs(null, ['https://glympse.com/!Better157LocationBot']));
	}

	public function testGetInviteId(): void
	{
		$class = new ReflectionClass(GlympseService::class);
		$method = $class->getMethod('getInviteIdFromUrl');
		$method->setAccessible(true);

		$this->assertSame('ABCD-EFGH', $method->invokeArgs(null, ['https://glympse.com/ABCD-EFGH']));
		$this->assertSame('ABCD-EFGH', $method->invokeArgs(null, ['https://www.glympse.com/ABCD-EFGH']));
		$this->assertSame('abCD-EFgh', $method->invokeArgs(null, ['http://glympse.com/abCD-EFgh']));
		$this->assertSame('ABCD-EFGH', $method->invokeArgs(null, ['http://www.glympse.com/ABCD-EFGH']));
		$this->assertSame('ABCD-EFGH', $method->invokeArgs(null, ['https://gLYMpse.com/ABCD-EFGH']));
		$this->assertSame('AB12-EF34', $method->invokeArgs(null, ['https://glympse.com/AB12-EF34']));
		$this->assertSame('AB1-EF3', $method->invokeArgs(null, ['https://glympse.com/AB1-EF3']));
	}
}
