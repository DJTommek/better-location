<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\BaladIrService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

final class BaladIrServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://balad.ir/location?latitude=50.087451&longitude=14.420671', BaladIrService::getLink(50.087451, 14.420671));
		$this->assertSame('https://balad.ir/location?latitude=50.1&longitude=14.5', BaladIrService::getLink(50.1, 14.5));
		$this->assertSame('https://balad.ir/location?latitude=-50.2&longitude=14.6000001', BaladIrService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://balad.ir/location?latitude=50.3&longitude=-14.7000009', BaladIrService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://balad.ir/location?latitude=-50.4&longitude=-14.800008', BaladIrService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		BaladIrService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValidMap(): void
	{
		$this->assertTrue(BaladIrService::isValidStatic('https://balad.ir/location?latitude=50.087451&longitude=14.420671'));
		$this->assertTrue(BaladIrService::isValidStatic('https://balad.ir/location?latitude=50&longitude=14'));
		$this->assertTrue(BaladIrService::isValidStatic('https://balad.ir/location?latitude=-50.087451&longitude=-14.420671'));
		$this->assertTrue(BaladIrService::isValidStatic('https://balad.ir/location?latitude=35.826644&longitude=50.968268&zoom=16.500000#15/35.83347/50.95417'));
		$this->assertTrue(BaladIrService::isValidStatic('https://balad.ir/location?latitude=9999.826644&longitude=50.968268&zoom=16.500000#15/35.83347/50.95417'));
		$this->assertTrue(BaladIrService::isValidStatic('https://balad.ir/location?latitude=35.826644&longitude=50.968268&zoom=16.500000#15/999.83347/50.95417'));
		$this->assertTrue(BaladIrService::isValidStatic('https://balad.ir/#6.04/29.513/53.574'));
		$this->assertTrue(BaladIrService::isValidStatic('https://balad.ir/#6.04/29/53'));
		$this->assertTrue(BaladIrService::isValidStatic('https://balad.ir/#6/29.513/53.574'));

		$this->assertFalse(BaladIrService::isValidStatic('https://balad.ir/location?longitude=-14.420671'));
		$this->assertFalse(BaladIrService::isValidStatic('https://balad.ir/location?latitude=-99.087451&longitude=-14.420671'));
		$this->assertFalse(BaladIrService::isValidStatic('https://balad.ir/#6/99.513/53.574'));
		$this->assertFalse(BaladIrService::isValidStatic('https://different-domain.ir/location?latitude=-99.087451&longitude=-14.420671'));
		$this->assertFalse(BaladIrService::isValidStatic('https://balad.ir/location?latitude=99.826644&longitude=50.968268&zoom=16.500000#15/999.83347/50.95417'));
		$this->assertFalse(BaladIrService::isValidStatic('non url'));
	}

	public function testProcessSourceP(): void
	{
		$collection = BaladIrService::processStatic('https://balad.ir/location?latitude=35.826644&longitude=50.968268&zoom=16.500000#15/35.83347/50.95417')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('35.826644,50.968268', (string)$collection->getFirst());

		$collection = BaladIrService::processStatic('https://balad.ir/location?latitude=35.826644&longitude=999.968268&zoom=16.500000#15/35.83347/50.95417')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('35.833470,50.954170', (string)$collection->getFirst());

		$collection = BaladIrService::processStatic('https://balad.ir/location?latitude=35.826644&longitude=50.968268&zoom=16.5')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('35.826644,50.968268', (string)$collection->getFirst());

		$collection = BaladIrService::processStatic('https://balad.ir/#15/35.826644/50.968268')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('35.826644,50.968268', (string)$collection->getFirst());
	}
}
