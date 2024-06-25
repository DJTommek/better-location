<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\HradyCzService;
use Tests\HttpTestClients;

final class HradyCzServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return HradyCzService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function isValidProvider(): array
	{
		return [
			[true, 'https://www.hrady.cz/hrad-najstejn'],
			[true, 'https://www.hrady.cz/zamek-reckovice'],
			[true, 'https://www.hrady.cz/certovy-hlavy-zelizy'],
			[true, 'https://hrady.cz/certovy-hlavy-zelizy'],
			[true, 'http://hrady.cz/certovy-hlavy-zelizy'],
			[true, 'https://www.hrady.cz/certovy-hlavy-zelizy/'],
			[true, 'https://www.hrady.cz/certovy-hlavy-zelizy/komentare'],
			[true, 'https://www.hrady.cz/certovy-hlavy-zelizy/komentare/new'],
			[true, 'https://www.hrady.cz/kaple-nanebevzeti-panny-marie-miletice/ubytovani'],
			[true, 'https://www.hrady.cz/pevnost-bunkr-lo-vz-37-a-124az1z-vaha'],
			[true, 'https://www.hrady.cz/aaa-bbb-ccc'], // No location but valid

			[false, 'some invalid url'],
			[false, 'https://www.hrady.cz/aaa'],
			[false, 'https://www.hrady.cz/mapa'],
			[false, 'https://www.hrady.cz/clanky/pohadkovemu-jicinu-predchazela-jedna-z-nejvetsich-katastrof-17-stoleti'],
			[false, 'https://www.hrady.cz/search?typ_dop=105'],
			[false, 'https://www.hrady.cz/pamatky/kraj-vysocina'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[[[50.420540, 14.464405, null, '<a href="https://www.hrady.cz/certovy-hlavy-zelizy">Hrady.cz Čertovy hlavy</a>']], 'https://www.hrady.cz/certovy-hlavy-zelizy'],
			[[[50.306440, 14.288090, null, '<a href="https://www.hrady.cz/pevnost-bunkr-lo-vz-37-a-124az1z-vaha">Hrady.cz LO vz. 37 A-1/24a/Z1Z Váha</a>']], 'https://www.hrady.cz/pevnost-bunkr-lo-vz-37-a-124az1z-vaha'],
			[[[50.305519, 14.235415, null, '<a href="https://www.hrady.cz/kaple-nanebevzeti-panny-marie-miletice/ubytovani">Hrady.cz</a> <a href="https://www.hrady.cz/kaple-nanebevzeti-panny-marie-miletice">kaple Nanebevzetí Panny Marie</a>']], 'https://www.hrady.cz/kaple-nanebevzeti-panny-marie-miletice/ubytovani'],
		];
	}

	public static function processProviderInvalid(): array
	{
		return [
			['https://www.hrady.cz/aaa-bbb-ccc'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new HradyCzService($this->httpTestClients->mockedRequestor);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new HradyCzService($this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(array $expectedResults, string $input): void
	{
		$service = new HradyCzService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * @group request
	 * @dataProvider processProviderInvalid
	 */
	public function testInvalidIdReal(string $input): void
	{
		$service = new HradyCzService($this->httpTestClients->realRequestor);
		$this->assertServiceNoLocation($service, $input);
	}

	/**
	 * @dataProvider processProviderInvalid
	 */
	public function testInvalidIdOffline(string $input): void
	{
		$service = new HradyCzService($this->httpTestClients->offlineRequestor);
		$this->assertServiceNoLocation($service, $input);
	}
}
