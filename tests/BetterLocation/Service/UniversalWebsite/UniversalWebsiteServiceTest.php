<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\UniversalWebsite;

use App\BetterLocation\Service\UniversalWebsiteService\LdJsonProcessor;
use App\BetterLocation\Service\UniversalWebsiteService\UniversalWebsiteService;
use Tests\BetterLocation\Service\AbstractServiceTestCase;
use Tests\HttpTestClients;

final class UniversalWebsiteServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;
	private readonly LdJsonProcessor $ldJsonProcessor;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
		$this->ldJsonProcessor = new LdJsonProcessor();
	}

	protected function getServiceClass(): string
	{
		return UniversalWebsiteService::class;
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
			[true, 'https://www.tripadvisor.com/Restaurant_Review-g274707-d4154273-Reviews-Tavern_U_Krale_Brabantskeho-Prague_Bohemia.html'],
			[true, 'https://www.hornbach.cz/moje-prodejna/prodejna-hornbach-hradec-kralove/'],
			[true, 'https://www.pronto-ny.com/'],

			[false, 'something random'],
		];
	}

	public static function processLdJsonGeoProvider(): array
	{
		return [
			'hornbach.cz Hradec Králové' => [[[50.182083, 15.801783, UniversalWebsiteService::TYPE_SCHEMA_JSON_GEO, '🪄<a href="https://www.hornbach.cz/moje-prodejna/prodejna-hornbach-hradec-kralove/">HORNBACH Hradec Králové</a>']], 'https://www.hornbach.cz/moje-prodejna/prodejna-hornbach-hradec-kralove/'],
			'hornbach.cz Ostrava' => [[[49.826736, 18.208172, UniversalWebsiteService::TYPE_SCHEMA_JSON_GEO, '🪄<a href="https://www.hornbach.cz/moje-prodejna/prodejna-hornbach-ostrava/">HORNBACH Ostrava</a>']], 'https://www.hornbach.cz/moje-prodejna/prodejna-hornbach-ostrava/'],
			'hornbach.sk Bratislava - Devínska Nová Ves' => [[[48.2056, 17.020494, UniversalWebsiteService::TYPE_SCHEMA_JSON_GEO, '🪄<a href="https://www.hornbach.sk/moja-predajna/predajna-hornbach-bratislava-devinska-nova-ves/">HORNBACH Bratislava - Devínska Nová Ves</a>']], 'https://www.hornbach.sk/moja-predajna/predajna-hornbach-bratislava-devinska-nova-ves/'],
			'hornbach.ch Luzern-Littau' => [[[47.057755, 8.257341, UniversalWebsiteService::TYPE_SCHEMA_JSON_GEO, '🪄<a href="https://www.hornbach.ch/mein-markt/baumarkt-hornbach-luzern-littau/">HORNBACH Luzern-Littau</a>']], 'https://www.hornbach.ch/mein-markt/baumarkt-hornbach-luzern-littau/'],
			'pronto-ny.com' => [[[40.770766050505, -73.96467034232786, UniversalWebsiteService::TYPE_SCHEMA_JSON_GEO, '🪄<a href="https://www.pronto-ny.com/">Restaurant Pronto</a>']], 'https://www.pronto-ny.com/'],
			'vietnamnet.vn' => [[[21.0140338034431, 105.83156603015266, UniversalWebsiteService::TYPE_SCHEMA_JSON_GEO, '🪄<a href="https://vietnamnet.vn/">VietNamNet</a>']], 'https://vietnamnet.vn/'],
			'JSON available but no location' => [[], 'https://restauraceoaza.cz'],
			'No json available' => [[], 'https://tomas.palider.cz'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new UniversalWebsiteService($this->httpTestClients->mockedRequestor, $this->ldJsonProcessor);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 *
	 * @dataProvider processLdJsonGeoProvider
	 */
	public function testProcessReal(array $expectedResults, string $input): void
	{
		$service = new UniversalWebsiteService($this->httpTestClients->realRequestor, $this->ldJsonProcessor);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * @dataProvider processLdJsonGeoProvider
	 */
	public function testProcessOffline(array $expectedResults, string $input): void
	{
		$service = new UniversalWebsiteService($this->httpTestClients->offlineRequestor, $this->ldJsonProcessor);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}
}
