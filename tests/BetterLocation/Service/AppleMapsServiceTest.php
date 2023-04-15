<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\AppleMapsService;

final class AppleMapsServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return AppleMapsService::class;
	}

	protected function getShareLinks(): array
	{
		return [
			'https://maps.apple.com/?ll=50.087451,14.420671&q=50.087451,14.420671',
			'https://maps.apple.com/?ll=50.100000,14.500000&q=50.100000,14.500000',
			'https://maps.apple.com/?ll=-50.200000,14.600000&q=-50.200000,14.600000',
			'https://maps.apple.com/?ll=50.300000,-14.700001&q=50.300000,-14.700001',
			'https://maps.apple.com/?ll=-50.400000,-14.800008&q=-50.400000,-14.800008',
		];
	}

	protected function getDriveLinks(): array
	{
		return [
			'https://maps.apple.com/?daddr=50.087451,14.420671&dirflg=d',
			'https://maps.apple.com/?daddr=50.100000,14.500000&dirflg=d',
			'https://maps.apple.com/?daddr=-50.200000,14.600000&dirflg=d',
			'https://maps.apple.com/?daddr=50.300000,-14.700001&dirflg=d',
			'https://maps.apple.com/?daddr=-50.400000,-14.800008&dirflg=d',
		];
	}

	public function testIsValid(): void
	{
		$this->assertTrue(AppleMapsService::isValidStatic('https://maps.apple.com/?ll=50.087451,14.420671'));

		$this->assertFalse(AppleMapsService::isValidStatic('https://example.com/?ll=50.087451,14.420671'));
		$this->assertFalse(AppleMapsService::isValidStatic('non url'));
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValidUsingProvider(bool $expectedIsValid, string $link): void
	{
		$this->assertSame($expectedIsValid, AppleMapsService::isValidStatic($link));
	}

	/**
	 * @return array<array{bool, string}>
	 */
	public static function isValidProvider(): array
	{
		return [
			[true, 'https://maps.apple.com/?daddr=50.087451,14.420671&dirflg=d'],
			[true, 'https://maps.apple.com/?daddr=50.087451,14.420671'],
			[true, 'https://maps.apple.com/?ll=50.087451,14.420671&q=50.087451,14.420671'],
			[true, 'https://maps.apple.com/?ll=50.087451,14.420671'],
			[true, 'https://maps.apple.com/?ll=-50.087451,-0.420671'],
			[true, 'https://maps.apple.com/?ll=35.825322,50.966627&q=Dropped%20Pin&_ext=EiYpXh/UKxXpQUAxKWoz8wZ7SUA53PT5hzvqQUBBibo9/3F8SUBQBA%3D%3D&t=m'],
			[true, 'https://maps.apple.com/?address=Samota%201331,%20594%2001%20Velk%C3%A9%20Mezi%C5%99%C3%AD%C4%8D%C3%AD,%20%C4%8Cesk%C3%A1%20republika&auid=8134755443791322339&ll=49.354211,16.032766&lsp=9902&q=McDonald\'s&_ext=ChkKBAgEEG8KBAgFEAMKBQgGENEBCgQIChAAEiQpjxYMUwCkSEAxIH/EChKuL0A5I2sNpfa2SEBBLT9wlScoMEA%3D&t=m'],

			[false, 'non url'],
			[false, 'https://example.com/?ll=50.087451,14.420671'],
			[false, 'https://maps.apple.com/'],
			[false, 'https://maps.apple.com/?ll=950.087451,14.420671'],
			[false, 'https://maps.apple.com/?ll=50.087451,194.420671'],
		];
	}

	public function testProcess(): void
	{
		$collection = AppleMapsService::processStatic('https://maps.apple.com/?ll=50.087451,14.420671')->getCollection();
		$this->assertCount(1, $collection);
		$location = $collection->getFirst();
		$this->assertSame('Map center', $location->getSourceType());
		$this->assertSame('50.087451,14.420671', (string)$location);

		$collection = AppleMapsService::processStatic('https://maps.apple.com/?daddr=50.087451,14.420671')->getCollection();
		$this->assertCount(1, $collection);
		$location = $collection->getFirst();
		$this->assertSame('Destination', $location->getSourceType());
		$this->assertSame('50.087451,14.420671', (string)$location);

		$collection = AppleMapsService::processStatic('https://maps.apple.com/?ll=35.825322,50.966627&daddr=50.087451,14.420671')->getCollection();
		$this->assertCount(2, $collection);
		$location = $collection[0];
		$this->assertSame('Map center', $location->getSourceType());
		$this->assertSame('35.825322,50.966627', (string)$location);
		$location = $collection[1];
		$this->assertSame('Destination', $location->getSourceType());
		$this->assertSame('50.087451,14.420671', (string)$location);

		$collection = AppleMapsService::processStatic('https://maps.apple.com/?ll=35.825322,50.966627')->getCollection();
		$this->assertCount(1, $collection);
		$location = $collection->getFirst();
		$this->assertSame('Map center', $location->getSourceType());
		$this->assertSame('35.825322,50.966627', (string)$location);

		$collection = AppleMapsService::processStatic('https://maps.apple.com/?ll=35.825322,50.966627&q=Dropped%20Pin&_ext=EiYpXh/UKxXpQUAxKWoz8wZ7SUA53PT5hzvqQUBBibo9/3F8SUBQBA%3D%3D&t=m')->getCollection();
		$this->assertCount(1, $collection);
		$location = $collection->getFirst();
		$this->assertSame('Place', $location->getSourceType());
		$this->assertSame('35.825322,50.966627', (string)$location);

		$collection = AppleMapsService::processStatic('https://maps.apple.com/?address=Samota%201331,%20594%2001%20Velk%C3%A9%20Mezi%C5%99%C3%AD%C4%8D%C3%AD,%20%C4%8Cesk%C3%A1%20republika&auid=8134755443791322339&ll=49.354211,16.032766&lsp=9902&q=McDonald\'s&_ext=ChkKBAgEEG8KBAgFEAMKBQgGENEBCgQIChAAEiQpjxYMUwCkSEAxIH/EChKuL0A5I2sNpfa2SEBBLT9wlScoMEA%3D&t=m')->getCollection();
		$this->assertCount(1, $collection);
		$location = $collection->getFirst();
		$this->assertSame('Place', $location->getSourceType());
		$this->assertSame('49.354211,16.032766', (string)$location);
	}
}
