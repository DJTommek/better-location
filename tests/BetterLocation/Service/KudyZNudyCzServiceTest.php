<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\KudyZNudyCzService;

final class KudyZNudyCzServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return KudyZNudyCzService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public function testIsValid(): void
	{
		$this->assertTrue(KudyZNudyCzService::isValidStatic('https://www.kudyznudy.cz/aktivity/vyhlidka-maj-jeden-z-nejkrasnejsich-rozhledu-na'));

		$this->assertFalse(KudyZNudyCzService::isValidStatic('https://example.com/?ll=50.087451,14.420671'));
		$this->assertFalse(KudyZNudyCzService::isValidStatic('non url'));
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValidUsingProvider(bool $expectedIsValid, string $link): void
	{
		$this->assertSame($expectedIsValid, KudyZNudyCzService::isValidStatic($link));
	}

	/**
	 * @return array<array{bool, string}>
	 */
	public static function isValidProvider(): array
	{
		return [
			[true, 'https://www.kudyznudy.cz/aktivity/vyhlidka-maj-jeden-z-nejkrasnejsich-rozhledu-na'],
			[true, 'https://www.kudyznudy.cz/aktivity/kostel-sv-jiri-v-lukove-se-sadrovymi-duchy-verici'],
			[true, 'https://www.kudyznudy.cz/aktivity/prirodni-biotop-v-laskove'],

			[false, 'non url'],
			[false, 'https://example.com/?ll=50.087451,14.420671'],
			[false, 'https://www.kudyznudy.cz/'],
			[false, 'https://www.kudyznudy.cz/aktuality/70-tipu-na-nejlepsi-mista-ke-koupani-2018-aneb-tro'],
		];
	}

	/**
	 * @group request
	 */
	public function testProcess(): void
	{
		$this->assertLocation('https://www.kudyznudy.cz/aktivity/vyhlidka-maj-jeden-z-nejkrasnejsich-rozhledu-na', 49.831093, 14.455982);
		$this->assertLocation('https://www.kudyznudy.cz/aktivity/kostel-sv-jiri-v-lukove-se-sadrovymi-duchy-verici', 50.016474, 13.160622);
		$this->assertLocation('https://www.kudyznudy.cz/aktivity/prirodni-biotop-v-laskove', 49.584091, 17.000861);
	}
}
