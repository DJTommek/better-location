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
		$this->assertTrue(FacebookService::isValidStatic('https://facebook.com/burgerzelva'));
		$this->assertTrue(FacebookService::isValidStatic('http://facebook.com/burgerzelva'));
		$this->assertTrue(FacebookService::isValidStatic('http://www.facebook.com/burgerzelva'));
		$this->assertTrue(FacebookService::isValidStatic('https://www.facebook.com/burgerzelva'));
		$this->assertTrue(FacebookService::isValidStatic('https://facebook.com/burgerzelva/'));
		$this->assertTrue(FacebookService::isValidStatic('https://facebook.com/burgerzelva/menu'));
		$this->assertTrue(FacebookService::isValidStatic('https://facebook.com/burgerzelva/menu/?ref=page_internal'));
		$this->assertTrue(FacebookService::isValidStatic('https://facebook.com/burgerzelva?ref=page_internal'));
		$this->assertTrue(FacebookService::isValidStatic('https://m.facebook.com/burgerzelva'));
		$this->assertTrue(FacebookService::isValidStatic('https://pt-br.facebook.com/burgerzelva'));
		$this->assertTrue(FacebookService::isValidStatic('https://m.facebook.com/gentlegiantcafex/'));
		$this->assertTrue(FacebookService::isValidStatic('https://pt-br.facebook.com/fantaziecafe/'));
		$this->assertTrue(FacebookService::isValidStatic('https://www.facebook.com/FlotaVacaDiezSCZ/'));
		$this->assertTrue(FacebookService::isValidStatic('https://www.facebook.com/Bodegas-Alfaro-730504807012751/'));
		$this->assertTrue(FacebookService::isValidStatic('https://www.facebook.com/Biggie-Express-251025431718109/about/?ref=page_internal'));

		$this->assertFalse(FacebookService::isValidStatic('https://facebook.com/'));
		$this->assertFalse(FacebookService::isValidStatic('https://facebook.com'));
		$this->assertFalse(FacebookService::isValidStatic('https://facebook.com?foo=bar'));

		$this->assertFalse(FacebookService::isValidStatic('some invalid url'));
	}

	public function testUrl(): void
	{
		$collection = FacebookService::processStatic('https://pt-br.facebook.com/burgerzelva/menu/?ref=page_internal')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087244,14.469230', $collection[0]->__toString());

		$collection = FacebookService::processStatic('https://pt-br.facebook.com/fantaziecafe/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.061790,14.437030', $collection[0]->__toString());

		$collection = FacebookService::processStatic('https://www.facebook.com/Bodegas-Alfaro-730504807012751/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('40.411600,-3.700390', $collection[0]->__toString());

		$collection = FacebookService::processStatic('https://m.facebook.com/gentlegiantcafex/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-43.538899,172.652603', $collection[0]->__toString());

		$collection = FacebookService::processStatic('https://www.facebook.com/Biggie-Express-251025431718109/about/?ref=page_internal')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-25.285736,-57.559743', $collection[0]->__toString());

		$collection = FacebookService::processStatic('https://www.facebook.com/FlotaVacaDiezSCZ/')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-17.792721,-63.155202', $collection[0]->__toString());
	}

	public function testMissingCoordinates(): void
	{
		$collection = FacebookService::processStatic('https://www.facebook.com/ThePokeHaus')->getCollection();
		$this->assertCount(0, $collection);
	}
}
