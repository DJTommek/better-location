<?php declare(strict_types=1);

use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotSupportedException;
use BetterLocation\Service\GlympseService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../src/bootstrap.php';


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
}
