<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\FacebookService;
use PHPUnit\Framework\TestCase;

final class FacebookServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Share link is not implemented.');
		FacebookService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		FacebookService::getLink(50.087451, 14.420671, true);
	}

	public function testIsUrl(): void
	{
		$this->assertTrue(FacebookService::isUrl('https://facebook.com/burgerzelva'));
		$this->assertTrue(FacebookService::isUrl('http://facebook.com/burgerzelva'));
		$this->assertTrue(FacebookService::isUrl('http://www.facebook.com/burgerzelva'));
		$this->assertTrue(FacebookService::isUrl('https://www.facebook.com/burgerzelva'));
		$this->assertTrue(FacebookService::isUrl('https://facebook.com/burgerzelva/'));
		$this->assertTrue(FacebookService::isUrl('https://facebook.com/burgerzelva/menu'));
		$this->assertTrue(FacebookService::isUrl('https://facebook.com/burgerzelva/menu/?ref=page_internal'));
		$this->assertTrue(FacebookService::isUrl('https://facebook.com/burgerzelva?ref=page_internal'));
		$this->assertTrue(FacebookService::isUrl('https://m.facebook.com/burgerzelva'));
		$this->assertTrue(FacebookService::isUrl('https://pt-br.facebook.com/burgerzelva'));
		$this->assertTrue(FacebookService::isUrl('https://m.facebook.com/gentlegiantcafex/'));
		$this->assertTrue(FacebookService::isUrl('https://pt-br.facebook.com/fantaziecafe/'));
		$this->assertTrue(FacebookService::isUrl('https://www.facebook.com/FlotaVacaDiezSCZ/'));
		$this->assertTrue(FacebookService::isUrl('https://www.facebook.com/Bodegas-Alfaro-730504807012751/'));
		$this->assertTrue(FacebookService::isUrl('https://www.facebook.com/Biggie-Express-251025431718109/about/?ref=page_internal'));

		$this->assertFalse(FacebookService::isUrl('https://facebook.com/'));
		$this->assertFalse(FacebookService::isUrl('https://facebook.com'));
		$this->assertFalse(FacebookService::isUrl('https://facebook.com?foo=bar'));

		$this->assertFalse(FacebookService::isUrl('some invalid url'));
	}

	public function testUrl(): void
	{
		$this->assertSame('50.087244,14.469230', FacebookService::parseUrl('https://pt-br.facebook.com/burgerzelva/menu/?ref=page_internal')->__toString());
		$this->assertSame('50.061790,14.437030', FacebookService::parseUrl('https://pt-br.facebook.com/fantaziecafe/')->__toString());
		$this->assertSame('40.411600,-3.700390', FacebookService::parseUrl('https://www.facebook.com/Bodegas-Alfaro-730504807012751/')->__toString());
		$this->assertSame('-43.538899,172.652603', FacebookService::parseUrl('https://m.facebook.com/gentlegiantcafex/')->__toString());
		$this->assertSame('-25.285736,-57.559743', FacebookService::parseUrl('https://www.facebook.com/Biggie-Express-251025431718109/about/?ref=page_internal')->__toString());
	}

	public function testMissingCoordinates(): void
	{
		$this->assertNull(FacebookService::parseUrl('https://www.facebook.com/FlotaVacaDiezSCZ/'));
	}
}
