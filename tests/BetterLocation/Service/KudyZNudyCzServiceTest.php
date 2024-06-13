<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\KudyZNudyCzService;
use Tests\HttpTestClients;

final class KudyZNudyCzServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

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

	public static function processProvider(): array
	{
		return [
			[49.831093, 14.455982, KudyZNudyCzService::TYPE_ACTIVITY, 'https://www.kudyznudy.cz/aktivity/vyhlidka-maj-jeden-z-nejkrasnejsich-rozhledu-na'],
			[50.016474, 13.160622, KudyZNudyCzService::TYPE_ACTIVITY, 'https://www.kudyznudy.cz/aktivity/kostel-sv-jiri-v-lukove-se-sadrovymi-duchy-verici'],
			[49.584091, 17.000861, KudyZNudyCzService::TYPE_ACTIVITY, 'https://www.kudyznudy.cz/aktivity/prirodni-biotop-v-laskove'],

			[49.562374, 17.096406, KudyZNudyCzService::TYPE_EVENT, 'https://www.kudyznudy.cz/akce/veteran-rallye-z-lazni-do-lazni-1'],
			[49.644920, 17.139199, KudyZNudyCzService::TYPE_EVENT, 'https://www.kudyznudy.cz/akce/koniny-2'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new KudyZNudyCzService($this->httpTestClients->mockedRequestor);
		$service->setInput($input);
		$isValid = $service->validate();
		$this->assertSame($expectedIsValid, $isValid);
	}

	/**
	 * @dataProvider processProvider
	 * @group request
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $expectedSourceType, string $input): void
	{
		$service = new KudyZNudyCzService($this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon, $expectedSourceType);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $expectedSourceType, string $input): void
	{
		$service = new KudyZNudyCzService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon, $expectedSourceType);
	}
}
