<?php declare(strict_types=1);

use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\WhatThreeWordService;
use PHPUnit\Framework\TestCase;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;

final class WhatThreeWordsServiceTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateShareLink(): void
	{
		if (is_null(\App\Config::W3W_API_KEY)) {
			$this->markTestSkipped('Missing What3Words API Key.');
		} else {
			$this->assertEquals('https://w3w.co/paves.fans.piston', WhatThreeWordService::getLink(50.087451, 14.420671));
			$this->assertEquals('https://w3w.co/perkily.salon.receive', WhatThreeWordService::getLink(50.1, 14.5));
			$this->assertEquals('https://w3w.co/proximity.moaned.laxatives', WhatThreeWordService::getLink(-50.2, 14.6000001)); // round down
			$this->assertEquals('https://w3w.co/hardly.underpriced.frustrate', WhatThreeWordService::getLink(50.3, -14.7000009)); // round up
			$this->assertEquals('https://w3w.co/stampedes.foresees.prow', WhatThreeWordService::getLink(-50.4, -14.800008));
		}
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		WhatThreeWordService::getLink(50.087451, 14.420671, true);
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGeneral(): void
	{
		if (is_null(\App\Config::W3W_API_KEY)) {
			$this->markTestSkipped('Missing What3Words API Key.');
		} else {
			$entity = new MessageEntity();
			$entity->type = 'url';
			$entity->offset = 9;
			$entity->length = 21;
			$entities[] = $entity;
			$entity = new MessageEntity();
			$entity->type = 'url';
			$entity->offset = 49;
			$entity->length = 25;
			$entities[] = $entity;
			$result = BetterLocationCollection::fromTelegramMessage('Hello ///smaller.biggest.money there! Random URL https://tomas.palider.cz/ there...', $entities);
			$this->assertCount(1, $result);
			$this->assertEquals('50.086258,14.423709', $result[0]->__toString());
		}
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testWords(): void
	{
		if (is_null(\App\Config::W3W_API_KEY)) {
			$this->markTestSkipped('Missing What3Words API Key.');
		} else {
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('///define.readings.cucumber')->__toString());
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('///chladná.naopak.vložit')->__toString());
			$this->assertEquals('-25.066260,-130.100342', WhatThreeWordService::parseCoords('///dispersant.cuts.authentication')->__toString());
			$this->assertEquals('50.086258,14.423709', WhatThreeWordService::parseCoords('///smaller.biggest.money')->__toString()); // TG is thinking, that this is URL (probably .money is valid domain)
		}
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testShortUrls(): void
	{
		if (is_null(\App\Config::W3W_API_KEY)) {
			$this->markTestSkipped('Missing What3Words API Key.');
		} else {
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('https://w3w.co/define.readings.cucumber')->__toString());
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('https://w3w.co/chladná.naopak.vložit')->__toString());
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('https://w3w.co/chladn%C3%A1.naopak.vlo%C5%BEit')->__toString());
			$this->assertEquals('-25.066260,-130.100342', WhatThreeWordService::parseCoords('https://w3w.co/dispersant.cuts.authentication')->__toString());
		}
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testNormalUrls(): void
	{
		if (is_null(\App\Config::W3W_API_KEY)) {
			$this->markTestSkipped('Missing What3Words API Key.');
		} else {
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('https://what3words.com/define.readings.cucumber')->__toString());
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('https://what3words.com/chladná.naopak.vložit')->__toString());
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('https://what3words.com/chladn%C3%A1.naopak.vlo%C5%BEit')->__toString());
			$this->assertEquals('-25.066260,-130.100342', WhatThreeWordService::parseCoords('https://what3words.com/dispersant.cuts.authentication')->__toString());
		}
	}
}
