<?php declare(strict_types=1);

use BetterLocation\Service\Exceptions\NotSupportedException;
use BetterLocation\Service\WhatThreeWordService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/config.php';


final class WhatThreeWordsServiceTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateShareLink(): void {
		$this->assertEquals('https://w3w.co/paves.fans.piston', WhatThreeWordService::getLink(50.087451, 14.420671));
		$this->assertEquals('https://w3w.co/perkily.salon.receive', WhatThreeWordService::getLink(50.1, 14.5));
		$this->assertEquals('https://w3w.co/proximity.moaned.laxatives', WhatThreeWordService::getLink(-50.2, 14.6000001)); // round down
		$this->assertEquals('https://w3w.co/hardly.underpriced.frustrate', WhatThreeWordService::getLink(50.3, -14.7000009)); // round up
		$this->assertEquals('https://w3w.co/stampedes.foresees.prow', WhatThreeWordService::getLink(-50.4, -14.800008));
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateDriveLink(): void {
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		WhatThreeWordService::getLink(50.087451, 14.420671, true);
	}

}
