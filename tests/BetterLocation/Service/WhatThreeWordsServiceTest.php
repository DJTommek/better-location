<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\FromTelegramMessage;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\WhatThreeWordService;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\Utils\Requestor;
use PHPUnit\Framework\TestCase;
use Tests\HttpTestClients;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;

final class WhatThreeWordsServiceTest extends TestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	private function assertApiKeyExists(): void
	{
		if (!Config::isW3W()) {
			$this->markTestSkipped('WhatThreeWords service is not properly configured.');
		}
	}

	/**
	 * @noinspection PhpUnhandledExceptionInspection
	 * @group request
	 */
	public function testGenerateShareLink(): void
	{
		$this->assertApiKeyExists();

		$this->assertSame('https://w3w.co/paves.fans.piston', WhatThreeWordService::getLink(50.087451, 14.420671));
		$this->assertSame('https://w3w.co/perkily.salon.receive', WhatThreeWordService::getLink(50.1, 14.5));
		$this->assertSame('https://w3w.co/proximity.moaned.laxatives', WhatThreeWordService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://w3w.co/hardly.underpriced.frustrate', WhatThreeWordService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://w3w.co/stampedes.foresees.prow', WhatThreeWordService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		WhatThreeWordService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValidWords(): void
	{
		$this->assertTrue(WhatThreeWordService::validateStatic('///aaaa.bbbb.cccc'));
		$this->assertTrue(WhatThreeWordService::validateStatic('///aaaa.BBBB.cccc'));
		$this->assertTrue(WhatThreeWordService::validateStatic('///a.b.c'));
		$this->assertTrue(WhatThreeWordService::validateStatic('///a.B.c'));
		$this->assertTrue(WhatThreeWordService::validateStatic('///stampedes.foresees.prow'));
		$this->assertTrue(WhatThreeWordService::validateStatic('stampedes.foresees.prow'));
		$this->assertTrue(WhatThreeWordService::validateStatic('///chladná.naopak.vložit'));
		$this->assertTrue(WhatThreeWordService::validateStatic('///井水.组装.湖泊'));
		$this->assertTrue(WhatThreeWordService::validateStatic('///шейна.читалня.мишле'));

		$this->assertFalse(WhatThreeWordService::validateStatic(''));
		$this->assertFalse(WhatThreeWordService::validateStatic('///a.b.c.d'));
		$this->assertFalse(WhatThreeWordService::validateStatic('///a-b.c'));
		$this->assertFalse(WhatThreeWordService::validateStatic('///a b.c'));
		$this->assertFalse(WhatThreeWordService::validateStatic('//stampedes.foresees.prow'));
		$this->assertFalse(WhatThreeWordService::validateStatic('/// stampedes.foresees.prow'));
	}

	public function testIsValidShortUrl(): void
	{
		$this->assertTrue(WhatThreeWordService::validateStatic('https://w3w.co/aaaa.bbbb.cccc'));
		$this->assertTrue(WhatThreeWordService::validateStatic('http://w3w.co/aaaa.bbbb.cccc'));
		$this->assertTrue(WhatThreeWordService::validateStatic('https://www.w3w.co/aaaa.bbbb.cccc'));
		$this->assertTrue(WhatThreeWordService::validateStatic('http://www.w3w.co/aaaa.bbbb.cccc'));
		$this->assertTrue(WhatThreeWordService::validateStatic('https://w3w.co/井水.组装.湖泊'));
		$this->assertTrue(WhatThreeWordService::validateStatic('https://w3w.co/%E4%BA%95%E6%B0%B4.%E7%BB%84%E8%A3%85.%E6%B9%96%E6%B3%8A'));
		$this->assertTrue(WhatThreeWordService::validateStatic('https://w3w.co/kobry.sedátko.vývozy'));
		$this->assertTrue(WhatThreeWordService::validateStatic('https://w3w.co/%EB%A7%A4%EC%B6%9C.%EC%88%98%ED%96%89.%EC%B9%BC%EA%B5%AD%EC%88%98?alias=매출.수행.칼국수'));
//		$this->assertTrue(WhatThreeWordService::isValidStatic('https://w3w.co/útlum.hravost.rohlíky')); // @TODO for some reason it is returning invalid character: "útlum.hravost.rohl�_ky"
		$this->assertTrue(WhatThreeWordService::validateStatic('https://w3w.co/%D1%88%D0%B5%D0%B9%D0%BD%D0%B0.%D1%87%D0%B8%D1%82%D0%B0%D0%BB%D0%BD%D1%8F.%D0%BC%D0%B8%D1%88%D0%BB%D0%B5?alias=шейна.читалня.мишле'));

		$this->assertFalse(WhatThreeWordService::validateStatic(''));
		$this->assertFalse(WhatThreeWordService::validateStatic('https://w3w.co/aaaa.bbbb.cccc.ddd'));
		$this->assertFalse(WhatThreeWordService::validateStatic('https://w3w.co/aaaa-bbbb.cccc'));
	}

	public function testIsValidNormalUrl(): void
	{
		$this->assertTrue(WhatThreeWordService::validateStatic('https://what3words.com/aaaa.bbbb.cccc'));
		$this->assertTrue(WhatThreeWordService::validateStatic('http://what3words.com/aaaa.bbbb.cccc'));
		$this->assertTrue(WhatThreeWordService::validateStatic('https://www.what3words.com/aaaa.bbbb.cccc'));
		$this->assertTrue(WhatThreeWordService::validateStatic('http://www.what3words.com/aaaa.bbbb.cccc'));
		$this->assertTrue(WhatThreeWordService::validateStatic('https://what3words.com/井水.组装.湖泊'));
		$this->assertTrue(WhatThreeWordService::validateStatic('https://what3words.com/%E4%BA%95%E6%B0%B4.%E7%BB%84%E8%A3%85.%E6%B9%96%E6%B3%8A'));
		$this->assertTrue(WhatThreeWordService::validateStatic('https://what3words.com/kobry.sedátko.vývozy'));
		$this->assertTrue(WhatThreeWordService::validateStatic('https://what3words.com/%EB%A7%A4%EC%B6%9C.%EC%88%98%ED%96%89.%EC%B9%BC%EA%B5%AD%EC%88%98?alias=매출.수행.칼국수'));
//		$this->assertTrue(WhatThreeWordService::isValidStatic('https://what3words.com/útlum.hravost.rohlíky')); // @TODO for some reason it is returning invalid character: "útlum.hravost.rohl�_ky"
		$this->assertTrue(WhatThreeWordService::validateStatic('https://what3words.com/%D1%88%D0%B5%D0%B9%D0%BD%D0%B0.%D1%87%D0%B8%D1%82%D0%B0%D0%BB%D0%BD%D1%8F.%D0%BC%D0%B8%D1%88%D0%BB%D0%B5?alias=шейна.читалня.мишле'));

		$this->assertFalse(WhatThreeWordService::validateStatic(''));
		$this->assertFalse(WhatThreeWordService::validateStatic('https://invalid.com/aaaa.bbbb.cccc'));
	}

	/**
	 * @group request
	 */
	public function testGeneralReal(): void
	{
		$this->assertApiKeyExists();
		$this->testGeneral($this->httpTestClients->realRequestor);
	}

	public function testGeneralOffline(): void
	{
		$this->markTestSkipped('Offline requests are not possible, because What3Words library is used and that is using real HTTP requests.');
	}

	private function testGeneral(Requestor $requestor): void
	{
		$servicesManager = new ServicesManager();
		$fromTelegramMessage = new FromTelegramMessage($servicesManager, $requestor);

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
		$result = $fromTelegramMessage->getCollection('Hello ///smaller.biggest.money there! Random URL https://tomas.palider.cz/ there...', $entities);
		$this->assertCount(1, $result);
		$this->assertSame('50.086258,14.423709', $result[0]->__toString());
	}

	/**
	 * @group request
	 */
	public function testWords(): void
	{
		$this->assertApiKeyExists();

		$collection = WhatThreeWordService::processStatic('///define.readings.cucumber')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.297286,14.126510', $collection[0]->__toString());

		$collection = WhatThreeWordService::processStatic('///define.READINGS.cucumber')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.297286,14.126510', $collection[0]->__toString());

		$collection = WhatThreeWordService::processStatic('///define.reading.cucumber')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('57.224356,65.452627', $collection[0]->__toString());

		$collection = WhatThreeWordService::processStatic('///chladná.naopak.vložit')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.297286,14.126510', $collection[0]->__toString());

		$collection = WhatThreeWordService::processStatic('///dispersant.cuts.authentication')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-25.066260,-130.100342', $collection[0]->__toString());

		$collection = WhatThreeWordService::processStatic('///smaller.biggest.money')->getCollection(); // TG is thinking, that this is URL (probably .money is valid domain)
		$this->assertCount(1, $collection);
		$this->assertSame('50.086258,14.423709', $collection[0]->__toString());
	}

	/**
	 * @group request
	 */
	public function testShortUrls(): void
	{
		$this->assertApiKeyExists();

		$collection = WhatThreeWordService::processStatic('https://w3w.co/define.readings.cucumber')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.297286,14.126510', $collection[0]->__toString());

		$collection = WhatThreeWordService::processStatic('https://w3w.co/chladná.naopak.vložit')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.297286,14.126510', $collection[0]->__toString());

		$collection = WhatThreeWordService::processStatic('https://w3w.co/chladn%C3%A1.naopak.vlo%C5%BEit')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.297286,14.126510', $collection[0]->__toString());

		$collection = WhatThreeWordService::processStatic('https://w3w.co/dispersant.cuts.authentication')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-25.066260,-130.100342', $collection[0]->__toString());
	}

	/**
	 * @group request
	 */
	public function testNormalUrls(): void
	{
		$this->assertApiKeyExists();

		$collection = WhatThreeWordService::processStatic('https://what3words.com/define.readings.cucumber')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.297286,14.126510', $collection[0]->__toString());

		$collection = WhatThreeWordService::processStatic('https://what3words.com/chladná.naopak.vložit')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.297286,14.126510', $collection[0]->__toString());

		$collection = WhatThreeWordService::processStatic('https://what3words.com/chladn%C3%A1.naopak.vlo%C5%BEit')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.297286,14.126510', $collection[0]->__toString());

		$collection = WhatThreeWordService::processStatic('https://what3words.com/dispersant.cuts.authentication')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-25.066260,-130.100342', $collection[0]->__toString());
	}
}
