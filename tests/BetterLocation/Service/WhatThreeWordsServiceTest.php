<?php declare(strict_types=1);

use BetterLocation\Service\Exceptions\NotSupportedException;
use BetterLocation\Service\WhatThreeWordService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../src/bootstrap.php';


final class WhatThreeWordsServiceTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateShareLink(): void {
		if (is_null(\Config::W3W_API_KEY)) {
            $this->markTestSkipped('Missing What3Words API Key.');
        } else {
			$this->assertEquals('https://w3w.co/paves.fans.piston', WhatThreeWordService::getLink(50.087451, 14.420671));
			$this->assertEquals('https://w3w.co/perkily.salon.receive', WhatThreeWordService::getLink(50.1, 14.5));
			$this->assertEquals('https://w3w.co/proximity.moaned.laxatives', WhatThreeWordService::getLink(-50.2, 14.6000001)); // round down
			$this->assertEquals('https://w3w.co/hardly.underpriced.frustrate', WhatThreeWordService::getLink(50.3, -14.7000009)); // round up
			$this->assertEquals('https://w3w.co/stampedes.foresees.prow', WhatThreeWordService::getLink(-50.4, -14.800008));
		}
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateDriveLink(): void {
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		WhatThreeWordService::getLink(50.087451, 14.420671, true);
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testWords(): void {
		if (is_null(\Config::W3W_API_KEY)) {
            $this->markTestSkipped('Missing What3Words API Key.');
        } else {
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('///define.readings.cucumber')->__toString());
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('///chladná.naopak.vložit')->__toString());
			$this->assertEquals('-25.066260,-130.100342', WhatThreeWordService::parseCoords('///dispersant.cuts.authentication')->__toString());
		}
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testShortUrls(): void {
		if (is_null(\Config::W3W_API_KEY)) {
            $this->markTestSkipped('Missing What3Words API Key.');
        } else {
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('https://w3w.co/define.readings.cucumber')->__toString());
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('https://w3w.co/chladná.naopak.vložit')->__toString());
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('https://w3w.co/chladn%C3%A1.naopak.vlo%C5%BEit')->__toString());
			$this->assertEquals('-25.066260,-130.100342', WhatThreeWordService::parseCoords('https://w3w.co/dispersant.cuts.authentication')->__toString());
		}
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testNormalUrls(): void {
		if (is_null(\Config::W3W_API_KEY)) {
            $this->markTestSkipped('Missing What3Words API Key.');
        } else {
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('https://what3words.com/define.readings.cucumber')->__toString());
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('https://what3words.com/chladná.naopak.vložit')->__toString());
			$this->assertEquals('49.297286,14.126510', WhatThreeWordService::parseCoords('https://what3words.com/chladn%C3%A1.naopak.vlo%C5%BEit')->__toString());
			$this->assertEquals('-25.066260,-130.100342', WhatThreeWordService::parseCoords('https://what3words.com/dispersant.cuts.authentication')->__toString());
		}
	}

}
