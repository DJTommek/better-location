<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\IngressIntelService;
use App\BetterLocation\Service\IngressPrimeService;
use App\Cache\NetteCachePsr16;
use App\Config;
use App\IngressLanchedRu\Client;
use App\IngressPortalnik\Client as PortalnikClient;
use App\Utils\Ingress;
use Nette\Caching\Storages\DevNullStorage;
use Psr\Http\Client\ClientInterface;
use Tests\HttpTestClients;

final class IngressPrimeServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	#[\Override]
 protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	#[\Override]
 protected function getServiceClass(): string
	{
		return IngressPrimeService::class;
	}

	#[\Override]
 protected function getShareLinks(): array
	{
		return [];
	}

	#[\Override]
 protected function getDriveLinks(): array
	{
		return [];
	}

	public function isValidProvider(): array
	{
		return [
			[true, 'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671'],
			[true, 'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12'],
			// Missing main link but valid OFL is available
			[true, 'https://link.ingress.com/?apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671'],
			// Invalid main link but valid OFL
			[true, 'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9999.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671'],

			// Links since circa 2025-08-20, see https://t.me/IUENG_Extra/470
			[true, 'https://link.ingress.com/portal/cf2e28687bfe34fca1c2fdbb966a484f.16'],
			[true, 'https://link.ingress.com/portal/0bd94fac5de84105b6eef6e7e1639ad9.12'],

			[true, 'https://intel.ingress.com/mission/0a3f101657ac441286a56b391f969e15.1c'],
			// Mission links, older format
			[true, 'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fb9d1285bdf184780a79f1a00b68e893e.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fb9d1285bdf184780a79f1a00b68e893e.1c'],
			[true, 'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fb9d1285bdf184780a79f1a00b68e893e.1c'],
			// Mission links, newer format
			[true, 'https://link.ingress.com/mission/b9d1285bdf184780a79f1a00b68e893e.1c'],
			[false, 'https://link.ingress.com/mission/'],
			[false, 'https://link.ingress.com/mission/b9d1285bdf184780a79f1a00b68e893e999.1c'],
			[false, 'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2F'],

			[false, 'non link'],
			[false, 'https://intel.ingress.com/'],
			[false, 'https://intel.ingress.com/portal/'],
			[false, 'https://intel.ingress.com/mission/'],
			[false, 'https://link.ingress.com/'],
			[false, 'https://ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671'],
			// Missing main link and invalid valid OFL
			[false, 'https://link.ingress.com/?apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14999.420671'],
		];
	}

	public static function processProvider(): array
	{
		return [
			'Basic example 1' => [
				[
					[
						50.087451,
						14.420671,
						IngressPrimeService::TYPE_PORTAL,
						'<a href="https://link.ingress.com/portal/0bd94fac5de84105b6eef6e7e1639ad9.12">Staroměstské náměstí 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087451,14.420671">🖥</a> <a href="https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.087451,14.420671,12.66,,0bd94fac5de84105b6eef6e7e1639ad9.12">🎦</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => '',
						],
					],
				],
				'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671',
			],

			'Basic example 2' => [
				[
					[
						50.087451,
						14.420671,
						IngressPrimeService::TYPE_PORTAL,
						'<a href="https://link.ingress.com/portal/0bd94fac5de84105b6eef6e7e1639ad9.12">Staroměstské náměstí 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087451,14.420671">🖥</a> <a href="https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.087451,14.420671,12.66,,0bd94fac5de84105b6eef6e7e1639ad9.12">🎦</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => '',
						],
					],
				],
				'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12',
			],

			'Missing main link but valid OFL is available' => [
				[
					[
						50.087451,
						14.420671,
						IngressPrimeService::TYPE_PORTAL,
						'<a href="https://link.ingress.com/portal/0bd94fac5de84105b6eef6e7e1639ad9.12">Staroměstské náměstí 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087451,14.420671">🖥</a> <a href="https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.087451,14.420671,12.66,,0bd94fac5de84105b6eef6e7e1639ad9.12">🎦</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => '',
						],
					],
				],
				'https://link.ingress.com/?apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671',
			],

			'OFL and main link different coordinates' => [
				[
					[
						50.087451,
						14.420671,
						IngressPrimeService::TYPE_PORTAL,
						'<a href="https://link.ingress.com/portal/0bd94fac5de84105b6eef6e7e1639ad9.12">Staroměstské náměstí 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087451,14.420671">🖥</a> <a href="https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.087451,14.420671,12.66,,0bd94fac5de84105b6eef6e7e1639ad9.12">🎦</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => '',
						],
					],
					[
						50.083698,
						14.433817,
						IngressPrimeService::TYPE_PORTAL,
						'<a href="https://link.ingress.com/portal/ea93169b8a824881b2807292f6ab088b.11">Praha hlavní nádraží 📱</a> <a href="https://intel.ingress.com/intel?pll=50.083698,14.433817">🖥</a> <a href="https://lh3.googleusercontent.com/cP86_718iH92b8kzEv_jkTt8hua-xx2L-fCEuL4G592hOxwsNG3KYevvTMW8W01Gqiqpzttc83x350NYvz9KUCz9Lw=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.083698,14.433817,12.66,,ea93169b8a824881b2807292f6ab088b.11">🎦</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => '',
						],
					],
				],
				'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.083698%2C14.433817',
			],

			'Simple portal link 1' => [
				[
					[
						50.087451,
						14.420671,
						IngressPrimeService::TYPE_PORTAL,
						'<a href="https://link.ingress.com/portal/0bd94fac5de84105b6eef6e7e1639ad9.12">Staroměstské náměstí 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087451,14.420671">🖥</a> <a href="https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.087451,14.420671,12.66,,0bd94fac5de84105b6eef6e7e1639ad9.12">🎦</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => '',
						],
					],
				],
				'https://link.ingress.com/portal/0bd94fac5de84105b6eef6e7e1639ad9.12',
			],

			'Simple portal link 2' => [
				[
					[
						50.015707,
						15.195986,
						IngressPrimeService::TYPE_PORTAL,
						'<a href="https://link.ingress.com/portal/cf2e28687bfe34fca1c2fdbb966a484f.16">Pingpongový stůl 📱</a> <a href="https://intel.ingress.com/intel?pll=50.015707,15.195986">🖥</a> <a href="https://lh3.googleusercontent.com/XuexkrbaCPAhaNJ4-9Bf6kuALkGHQlmxKysLKCzt1A-OpBHJFcFipT8Bi5sGICsi8zA3ahI76RewLh2YbAoDmUTaVbgPYEKvwEhU0TQ4dg=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.015707,15.195986,12.66,,cf2e28687bfe34fca1c2fdbb966a484f.16">🎦</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => '',
						],
					],
				],
				'https://link.ingress.com/portal/cf2e28687bfe34fca1c2fdbb966a484f.16',
			],

			'GUID does not exists' => [
				[],
				'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Faaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.12',
			],

			'Simple mission link 1' => [ // Not supported, yet
				[],
				'https://intel.ingress.com/mission/0a3f101657ac441286a56b391f969e15.1c',
			],

			'Mission link older format' => [ // Not supported, yet
				[],
				'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fb9d1285bdf184780a79f1a00b68e893e.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fb9d1285bdf184780a79f1a00b68e893e.1c',
			],

			'Mission link newer format' => [ // Not supported, yet
				[],
				'https://link.ingress.com/mission/b9d1285bdf184780a79f1a00b68e893e.1c',
			],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$ingressClient = new Client($this->httpTestClients->mockedRequestor);
		$ingressIntelService = new IngressIntelService($ingressClient);
		$service = new IngressPrimeService($ingressClient, $ingressIntelService);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @dataProvider processProvider
	 * @group request
	 */
	public function testProcessReal(array $expectedResults,
		string $input,
	): void {
		$ingressClient = new Client($this->httpTestClients->realRequestor);
		$ingressIntelService = new IngressIntelService($ingressClient);
		$service = new IngressPrimeService($ingressClient, $ingressIntelService);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(array $expectedResults, string $input): void
	{
		$ingressClient = new Client($this->httpTestClients->offlineRequestor);
		$ingressIntelService = new IngressIntelService($ingressClient);
		$service = new IngressPrimeService($ingressClient, $ingressIntelService);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	public static function processMissionProvider(): array
	{
		$prague = [
			'50.087451,14.420671',
			'Mission MD 2019: Prague, Old Town Square Execution',
			'Mission <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2F0a3f101657ac441286a56b391f969e15.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2F0a3f101657ac441286a56b391f969e15.1c">MD 2019: Prague, Old Town Square Execution 📱</a> <a href="https://intel.ingress.com/mission/0a3f101657ac441286a56b391f969e15.1c">🖥</a> <a href="https://lh3.googleusercontent.com/kIJMTATIQyDn5UTw9X7Y3-weF3lUDxygiEiW7YegZdyvJJ8fb-lRVd2EnjV_fxac3_U3LjhIaIqi6LDlsGw=s10000">🖼</a>',
			'7 waypoints, any order',
			'Start portal: <a href="https://link.ingress.com/portal/0bd94fac5de84105b6eef6e7e1639ad9.12">Staroměstské náměstí 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087451,14.420671">🖥</a>',
		];
		$horseshoeLake = [
			'40.857356,-74.636430',
			'Mission 1/6 Horseshoe Lake Second Sunday June ‘22',
			'Mission <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fb9d1285bdf184780a79f1a00b68e893e.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fb9d1285bdf184780a79f1a00b68e893e.1c">1/6 Horseshoe Lake Second Sunday June ‘22 📱</a> <a href="https://intel.ingress.com/mission/b9d1285bdf184780a79f1a00b68e893e.1c">🖥</a> <a href="https://lh3.googleusercontent.com/aECVP5ae77QzpsnFkMVi3wgl7QsfiAowtNrtSvNqbKk5ZECXLq33vdDfP3qXJz6mpM_Xk_BmBk3uF2NlEQ0vYncsqJqRHlw68g=s10000">🖼</a>',
			'8 waypoints, any order',
			'Start portal: <a href="https://link.ingress.com/portal/0a4769e7089141c6993d27d1c9d266e6.16">Alligator Play Structure 📱</a> <a href="https://intel.ingress.com/intel?pll=40.857356,-74.636430">🖥</a>',
		];

		return [
			'Intel link' => ['https://intel.ingress.com/mission/0a3f101657ac441286a56b391f969e15.1c', ...$prague],
			'Prime link newer format' => ['https://link.ingress.com/mission/b9d1285bdf184780a79f1a00b68e893e.1c', ...$horseshoeLake],
			'Prime link older format' => ['https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fb9d1285bdf184780a79f1a00b68e893e.1c', ...$horseshoeLake],
			'Prime link older format with OFL' => ['https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fb9d1285bdf184780a79f1a00b68e893e.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fb9d1285bdf184780a79f1a00b68e893e.1c', ...$horseshoeLake],
		];
	}

	/**
	 * @group request
	 * @dataProvider processMissionProvider
	 */
	public function testProcessMissionReal(string $input, string $expectedLatLon, string $expectedInlinePrefix, string $expectedPrefix, string $expectedSummary, string $expectedStartPortal): void
	{
		$service = $this->createServiceWithPortalnik($this->createRealPortalnikClient());
		$this->assertMissionLocation($service, $input, $expectedLatLon, $expectedInlinePrefix, $expectedPrefix, $expectedSummary, $expectedStartPortal);
	}

	/**
	 * @dataProvider processMissionProvider
	 */
	public function testProcessMissionOffline(string $input, string $expectedLatLon, string $expectedInlinePrefix, string $expectedPrefix, string $expectedSummary, string $expectedStartPortal): void
	{
		$service = $this->createServiceWithPortalnik($this->createOfflinePortalnikClient());
		$this->assertMissionLocation($service, $input, $expectedLatLon, $expectedInlinePrefix, $expectedPrefix, $expectedSummary, $expectedStartPortal);
	}

	private function assertMissionLocation(IngressPrimeService $service, string $input, string $expectedLatLon, string $expectedInlinePrefix, string $expectedPrefix, string $expectedSummary, string $expectedStartPortal): void
	{
		$service->setInput($input);
		$this->assertTrue($service->validate());
		$service->process();
		$collection = $service->getCollection();
		$this->assertCount(1, $collection);

		$location = $collection->getFirst();
		$this->assertSame($expectedLatLon, $location->getLatLon());
		$this->assertSame(IngressPrimeService::TYPE_MISSION, $location->getSourceType());
		$this->assertSame($expectedInlinePrefix, $location->getInlinePrefixMessage());
		$this->assertSame($expectedPrefix, $location->getPrefixMessage());

		$descriptions = $location->getDescriptions();
		$this->assertCount(2, $descriptions);
		$this->assertSame($expectedSummary, $descriptions[0]->content);
		$this->assertSame(Ingress::BETTER_LOCATION_KEY_PORTAL, $descriptions[1]->key);
		$this->assertSame($expectedStartPortal, $descriptions[1]->content);
	}

	/**
	 * @group request
	 */
	public function testProcessMissionNotFoundReal(): void
	{
		$service = $this->createServiceWithPortalnik($this->createRealPortalnikClient());
		$this->assertServiceNoLocation($service, 'https://link.ingress.com/mission/aaaabbbbccccdddd1111222233339999.1c');
	}

	public function testProcessMissionNotFoundOffline(): void
	{
		$service = $this->createServiceWithPortalnik($this->createOfflinePortalnikClient());
		$this->assertServiceNoLocation($service, 'https://link.ingress.com/mission/aaaabbbbccccdddd1111222233339999.1c');
	}

	private function createRealPortalnikClient(): PortalnikClient
	{
		$apiUrl = Config::getPortalnikApiUrl();
		$apiKey = Config::getPortalnikApiKey();
		if ($apiUrl === null || $apiKey === null) {
			self::markTestSkipped('Missing IvoPortalnik API URL and/or key');
		}
		return $this->createPortalnikClient($this->httpTestClients->realHttpClient, $apiUrl, $apiKey);
	}

	/**
	 * Saved responses are stored based on API URL, so it must be configured even for offline tests.
	 */
	private function createOfflinePortalnikClient(): PortalnikClient
	{
		$apiUrl = Config::getPortalnikApiUrl();
		if ($apiUrl === null) {
			self::markTestSkipped('Missing IvoPortalnik API URL');
		}
		return $this->createPortalnikClient($this->httpTestClients->offlineHttpClient, $apiUrl, '');
	}

	private function createPortalnikClient(ClientInterface $httpClient, string $apiUrl, string $apiKey): PortalnikClient
	{
		return new PortalnikClient(
			httpClient: $httpClient,
			cache: new NetteCachePsr16(new DevNullStorage()),
			apiUrl: $apiUrl,
			apiKey: $apiKey,
		);
	}

	private function createServiceWithPortalnik(PortalnikClient $portalnikClient): IngressPrimeService
	{
		return new IngressPrimeService($portalnikClient, new IngressIntelService($portalnikClient), $portalnikClient);
	}
}
