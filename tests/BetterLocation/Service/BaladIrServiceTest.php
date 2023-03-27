<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\BaladIrService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;

final class BaladIrServiceTest extends AbstractServiceTestCase
{

	protected function getServiceClass(): string
	{
		return BaladIrService::class;
	}

	public function getShareLinks(): array
	{
		return [
			'https://balad.ir/location?latitude=50.087451&longitude=14.420671',
			'https://balad.ir/location?latitude=50.1&longitude=14.5',
			'https://balad.ir/location?latitude=-50.2&longitude=14.6000001',
			'https://balad.ir/location?latitude=50.3&longitude=-14.7000009',
			'https://balad.ir/location?latitude=-50.4&longitude=-14.800008',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public function testGenerateDriveLinkAndValidate(): void
	{
		$this->expectException(NotSupportedException::class);
		parent::testGenerateDriveLinkAndValidate();
	}

	public function testIsValid(): void
	{
		$this->assertTrue(BaladIrService::isValidStatic('https://balad.ir/location?latitude=50.087451&longitude=14.420671'));
		$this->assertTrue(BaladIrService::isValidStatic('https://balad.ir/location?latitude=35.826644&longitude=50.968268&zoom=16.500000#15/35.83347/50.95417'));
		$this->assertTrue(BaladIrService::isValidStatic('https://balad.ir/#6/29.513/53.574'));

		$this->assertFalse(BaladIrService::isValidStatic('https://balad.ir/location?longitude=-14.420671'));
		$this->assertFalse(BaladIrService::isValidStatic('https://different-domain.ir/location?latitude=-99.087451&longitude=-14.420671'));
		$this->assertFalse(BaladIrService::isValidStatic('non url'));
	}

	public function testProcess(): void
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

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValidUsingProvider(bool $expectedIsValid, string $link): void
	{
		$this->assertSame($expectedIsValid, BaladIrService::isValidStatic($link));
	}

	/**
	 * @return array<array{bool, string}>
	 */
	public function isValidProvider(): array
	{
		return [
			[true, 'https://balad.ir/location?latitude=50.087451&longitude=14.420671'],
			[true, 'https://balad.ir/location?latitude=50&longitude=14'],
			[true, 'https://balad.ir/location?latitude=-50.087451&longitude=-14.420671'],
			[true, 'https://balad.ir/location?latitude=35.826644&longitude=50.968268&zoom=16.500000#15/35.83347/50.95417'],
			[true, 'https://balad.ir/location?latitude=9999.826644&longitude=50.968268&zoom=16.500000#15/35.83347/50.95417'],
			[true, 'https://balad.ir/location?latitude=35.826644&longitude=50.968268&zoom=16.500000#15/999.83347/50.95417'],
			[true, 'https://balad.ir/#6.04/29.513/53.574'],
			[true, 'https://balad.ir/#6.04/29/53'],
			[true, 'https://balad.ir/#6/29.513/53.574'],

			[false, 'https://balad.ir/location?longitude=-14.420671'],
			[false, 'https://balad.ir/location?latitude=-99.087451&longitude=-14.420671'],
			[false, 'https://balad.ir/#6/99.513/53.574'],
			[false, 'https://different-domain.ir/location?latitude=-99.087451&longitude=-14.420671'],
			[false, 'https://balad.ir/location?latitude=99.826644&longitude=50.968268&zoom=16.500000#15/999.83347/50.95417'],
			[false, 'non url'],
		];
	}
}
