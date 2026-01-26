<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\IngressIntelService;
use App\BetterLocation\Service\IngressPrimeService;
use App\IngressLanchedRu\Client;
use App\Utils\Ingress;
use Tests\HttpTestClients;

final class IngressPrimeServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return IngressPrimeService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

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

			[false, 'non link'],
			[false, 'https://link.ingress.com/'],
			[false, 'https://ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671'],
			// Missing main link and invalid valid OFL
			[false, 'https://link.ingress.com/?apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14999.420671'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[
				[
					[
						50.087451,
						14.420671,
						IngressPrimeService::TYPE_PORTAL,
						'<a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671">Staroměstské náměstí 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087451,14.420671">🖥</a> <a href="https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.087451,14.420671,12.66,,0bd94fac5de84105b6eef6e7e1639ad9.12">🎦</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => '',
						],
					],
				],
				'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671',
			],

			[
				[
					[
						50.087451,
						14.420671,
						IngressPrimeService::TYPE_PORTAL,
						'<a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671">Staroměstské náměstí 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087451,14.420671">🖥</a> <a href="https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.087451,14.420671,12.66,,0bd94fac5de84105b6eef6e7e1639ad9.12">🎦</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => '',
						],
					],
				],
				'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12',
			],

			// Missing main link but valid OFL is available
			[
				[
					[
						50.087451,
						14.420671,
						IngressPrimeService::TYPE_PORTAL,
						'<a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671">Staroměstské náměstí 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087451,14.420671">🖥</a> <a href="https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.087451,14.420671,12.66,,0bd94fac5de84105b6eef6e7e1639ad9.12">🎦</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => '',
						],
					],
				],
				'https://link.ingress.com/?apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671',
			],

			// OFL link has different valid coordinates than main link
			[
				[
					[
						50.087451,
						14.420671,
						IngressPrimeService::TYPE_PORTAL,
						'<a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671">Staroměstské náměstí 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087451,14.420671">🖥</a> <a href="https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.087451,14.420671,12.66,,0bd94fac5de84105b6eef6e7e1639ad9.12">🎦</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => '',
						],
					],
					[
						50.083698,
						14.433817,
						IngressPrimeService::TYPE_PORTAL,
						'<a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Fea93169b8a824881b2807292f6ab088b.11&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.083698%2C14.433817">Praha hlavní nádraží 📱</a> <a href="https://intel.ingress.com/intel?pll=50.083698,14.433817">🖥</a> <a href="https://lh3.googleusercontent.com/cP86_718iH92b8kzEv_jkTt8hua-xx2L-fCEuL4G592hOxwsNG3KYevvTMW8W01Gqiqpzttc83x350NYvz9KUCz9Lw=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.083698,14.433817,12.66,,ea93169b8a824881b2807292f6ab088b.11">🎦</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => '',
						],
					],
				],
				'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.083698%2C14.433817',
			],

			// Non existing GUID
			[
				[],
				'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Faaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.12',
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
}
