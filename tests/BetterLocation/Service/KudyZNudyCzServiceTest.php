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
		$this->assertTrue(KudyZNudyCzService::validateStatic('https://www.kudyznudy.cz/aktivity/vyhlidka-maj-jeden-z-nejkrasnejsich-rozhledu-na'));
		$this->assertTrue(KudyZNudyCzService::validateStatic('https://www.kudyznudy.cz/akce/veteran-rallye-z-lazni-do-lazni-1'));

		$this->assertFalse(KudyZNudyCzService::validateStatic('https://example.com/?ll=50.087451,14.420671'));
		$this->assertFalse(KudyZNudyCzService::validateStatic('non url'));
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValidUsingProvider(bool $expectedIsValid, string $link): void
	{
		$this->assertSame($expectedIsValid, KudyZNudyCzService::validateStatic($link));
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

			[true, 'https://www.kudyznudy.cz/akce/veteran-rallye-z-lazni-do-lazni-1'],
			[true, 'https://www.kudyznudy.cz/akce/koniny-2'],

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
		$this->assertLocation('https://www.kudyznudy.cz/aktivity/vyhlidka-maj-jeden-z-nejkrasnejsich-rozhledu-na', 49.831093, 14.455982, KudyZNudyCzService::TYPE_ACTIVITY);
		$this->assertLocation('https://www.kudyznudy.cz/aktivity/kostel-sv-jiri-v-lukove-se-sadrovymi-duchy-verici', 50.016474, 13.160622, KudyZNudyCzService::TYPE_ACTIVITY);
		$this->assertLocation('https://www.kudyznudy.cz/aktivity/prirodni-biotop-v-laskove', 49.584091, 17.000861, KudyZNudyCzService::TYPE_ACTIVITY);

		$this->assertLocation('https://www.kudyznudy.cz/akce/veteran-rallye-z-lazni-do-lazni-1', 49.562374, 17.096406, KudyZNudyCzService::TYPE_EVENT);
		$this->assertLocation('https://www.kudyznudy.cz/akce/koniny-2', 49.644920, 17.139199, KudyZNudyCzService::TYPE_EVENT);
	}
}
