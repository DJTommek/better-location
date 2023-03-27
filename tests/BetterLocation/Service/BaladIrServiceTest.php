<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\BaladIrService;

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
	 * @group request
	 */
	public function testProcessPlaceId(): void
	{
		$collection = BaladIrService::processStatic('https://balad.ir/p/3j08MFNHbCGvnu?preview=true')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('27.192955,56.290765', (string)$collection->getFirst());

		$collection = BaladIrService::processStatic('https://balad.ir/p/%DA%A9%D9%88%DB%8C-%D8%A2%DB%8C%D8%AA-%D8%A7%D9%84%D9%84%D9%87-%D8%BA%D9%81%D8%A7%D8%B1%DB%8C-bandar-abbas_residential-complex-3j08MFNHbCGvnu?preview=true')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('27.192955,56.290765', (string)$collection->getFirst());

		$collection = BaladIrService::processStatic('https://balad.ir/p/%DA%A9%D9%88%DB%8C-%D8%A2%DB%8C%D8%AA-%D8%A7%D9%84%D9%84%D9%87-%D8%BA%D9%81%D8%A7%D8%B1%DB%8C-bandar-abbas_residential-complex-3j08MFNHbCGvnu?preview=true#16.01/27.19771/56.287317')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('27.192955,56.290765', (string)$collection->getFirst());

		$collection = BaladIrService::processStatic('https://balad.ir/p/blah-blah-abcd?preview=true#16.01/27.19771/56.287317')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('27.197710,56.287317', (string)$collection->getFirst());

		$collection = BaladIrService::processStatic('https://balad.ir/p/405uvqx6JfALrs?preview=true')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('35.699738,51.338060', (string)$collection->getFirst());
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValidUsingProvider(bool $expectedIsValid, string $link): void
	{
		$isValid = BaladIrService::isValidStatic($link);
		$this->assertSame($expectedIsValid, $isValid, $link);
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
			// Place ID
			[true, 'https://balad.ir/p/405uvqx6JfALrs'],
			[true, 'https://balad.ir/p/3j08MFNHbCGvnu?preview=true'],
			[true, 'https://balad.ir/p/%DA%A9%D9%88%DB%8C-%D8%A2%DB%8C%D8%AA-%D8%A7%D9%84%D9%84%D9%87-%D8%BA%D9%81%D8%A7%D8%B1%DB%8C-bandar-abbas_residential-complex-3j08MFNHbCGvnu?preview=true'],

			[false, 'https://balad.ir/location?longitude=-14.420671'],
			[false, 'https://balad.ir/location?latitude=-99.087451&longitude=-14.420671'],
			[false, 'https://balad.ir/#6/99.513/53.574'],
			[false, 'https://different-domain.ir/location?latitude=-99.087451&longitude=-14.420671'],
			[false, 'https://balad.ir/location?latitude=99.826644&longitude=50.968268&zoom=16.500000#15/999.83347/50.95417'],
			[false, 'non url'],

			// @TODO add support for processing areas (/maps/xyz)
			[false, 'https://balad.ir/maps/qazvin?preview=true'],
			[false, 'https://balad.ir/maps/mashhad?preview=true'],
			[true, 'https://balad.ir/maps/mashhad?preview=true#11.03/36.3084/59.6476'], // map center is valid
		];
	}
}
