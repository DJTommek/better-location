<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\IngressIntelService;
use App\IngressLanchedRu\Client;
use App\Utils\Ingress;
use Tests\HttpTestClients;

final class IngressIntelServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return IngressIntelService::class;
	}

	protected function getShareLinks(): array
	{
		return [
			'https://intel.ingress.com/?ll=50.087451,14.420671&pll=50.087451,14.420671',
			'https://intel.ingress.com/?ll=50.100000,14.500000&pll=50.100000,14.500000',
			'https://intel.ingress.com/?ll=-50.200000,14.600000&pll=-50.200000,14.600000', // round down
			'https://intel.ingress.com/?ll=50.300000,-14.700001&pll=50.300000,-14.700001', // round up
			'https://intel.ingress.com/?ll=-50.400000,-14.800008&pll=-50.400000,-14.800008',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function isValidMapProvider(): array
	{
		return [
			[true, 'https://intel.ingress.com/?ll=50.087451,14.420671'],
			[true, 'https://intel.ingress.com/?ll=50.087451,144.420671'],
			[true, 'https://intel.ingress.com/?ll=50.087451,-14.420671'],
			[true, 'https://intel.ingress.com/?ll=-50.087451,14.420671'],
			[true, 'https://intel.ingress.com/?ll=-50.087451,-14.420671'],
			[true, 'http://intel.ingress.com/?ll=50.087451,14.420671'],
			[true, 'http://intel.ingress.com/?ll=50.087451,14.420671'],
			[true, 'http://intel.ingress.com/intel?ll=50.087451,14.420671'],
			[true, 'https://intel.ingress.com/?ll=50,14.420671'],
			[true, 'https://intel.ingress.com/?ll=50.087451,14'],
			[true, 'https://intel.ingress.com/?ll=50.123456789,14.987654321'],

			[false, 'https://intel.ingress.com/?ll=50.087451,14.420671a'],
			[false, 'https://intel.ingress.com/?ll=50.087451a,14.420671'],
			[false, 'https://intel.ingress.com/?ll=150.087451,14.420671'],
			[false, 'https://intel.ingress.com/?ll=50.087451,214.420671'],
			[false, 'https://intel.ingress.com/?ll=-150.087451,14.420671'],
			[false, 'https://intel.ingress.com/?ll=50.087451,214.420671'],
			[false, 'https://intel.ingress.com/?ll=50.08.7451,14.420671'],
			[false, 'https://intel.ingress.com/?ll=50.087451,14.420.671'],
			[false, 'https://intel.ingress.com/?ll=50.08745114.420671'],
			[false, 'https://intel.ingress.com/?ll=50.087451-14.420671'],
			[false, 'https://intel.ingress.com/?l=50.087451,14.420671'],
		];
	}

	public static function isValidPortalProvider(): array
	{
		return [
			[true, 'https://intel.ingress.com/?pll=50.087451,14.420671'],
			[true, 'https://intel.ingress.com/?pll=50.087451,144.420671'],
			[true, 'https://intel.ingress.com/?pll=50.087451,-14.420671'],
			[true, 'https://intel.ingress.com/?pll=-50.087451,14.420671'],
			[true, 'https://intel.ingress.com/?pll=-50.087451,-14.420671'],
			[true, 'http://intel.ingress.com/?pll=50.087451,14.420671'],
			[true, 'http://intel.ingress.com/?pll=50.087451,14.420671'],
			[true, 'http://intel.ingress.com/intel?pll=50.087451,14.420671'],
			[true, 'https://intel.ingress.com/?pll=50,14.420671'],
			[true, 'https://intel.ingress.com/?pll=50.087451,14'],

			[false, 'https://intel.ingress.com/?pll=50.087451,14.420671a'],
			[false, 'https://intel.ingress.com/?pll=50.087451a,14.420671'],
			[false, 'https://intel.ingress.com/?pll=150.087451,14.420671'],
			[false, 'https://intel.ingress.com/?pll=50.087451,214.420671'],
			[false, 'https://intel.ingress.com/?pll=-150.087451,14.420671'],
			[false, 'https://intel.ingress.com/?pll=50.087451,214.420671'],
			[false, 'https://intel.ingress.com/?pll=50.08.7451,14.420671'],
			[false, 'https://intel.ingress.com/?pll=50.087451,14.420.671'],
			[false, 'https://intel.ingress.com/?pll=50.08745114.420671'],
			[false, 'https://intel.ingress.com/?pll=50.087451-14.420671'],
			[false, 'https://intel.ingress.com/?l=50.087451,14.420671'],
		];
	}

	public static function isValidPortalAndMapProvider(): array
	{
		return [

			[true, 'https://intel.ingress.com/?pll=50.087451,14.420671&ll=50.087451,14.420671'],
			[true, 'https://intel.ingress.com/?pll=50.087451,144.420671&ll=50.087451,144.420671'],
			[true, 'https://intel.ingress.com/?pll=50.087451,-14.420671&ll=50.087451,-14.420671'],
			[true, 'https://intel.ingress.com/?pll=-50.087451,14.420671&ll=-50.087451,14.420671'],
			[true, 'https://intel.ingress.com/?pll=-50.087451,-14.420671&ll=-50.087451,-14.420671'],
			[true, 'http://intel.ingress.com/?pll=50.087451,14.420671&ll=50.087451,14.420671'],
			[true, 'http://intel.ingress.com/?pll=50.087451,14.420671&ll=50.087451,14.420671'],
			[true, 'http://intel.ingress.com/intel?pll=50.087451,14.420671&ll=50.087451,14.420671'],
			[true, 'https://intel.ingress.com/?pll=50,14.420671&ll=50,14.420671'],
			[true, 'https://intel.ingress.com/?pll=50.087451,14&ll=50.087451,14'],

			[false, 'https://intel.ingress.com/?pll=50.087451,14.420671a&ll=50.087451,14.420671a'],
			[false, 'https://intel.ingress.com/?pll=50.087451a,14.420671&ll=50.087451a,14.420671'],
			[false, 'https://intel.ingress.com/?pll=150.087451,14.420671&ll=150.087451,14.420671'],
			[false, 'https://intel.ingress.com/?pll=50.087451,214.420671&ll=50.087451,214.420671'],
			[false, 'https://intel.ingress.com/?pll=-150.087451,14.420671&ll=-150.087451,14.420671'],
			[false, 'https://intel.ingress.com/?pll=50.087451,214.420671&ll=50.087451,214.420671'],
			[false, 'https://intel.ingress.com/?pll=50.08.7451,14.420671&ll=50.08.7451,14.420671'],
			[false, 'https://intel.ingress.com/?pll=50.087451,14.420.671&ll=50.087451,14.420.671'],
			[false, 'https://intel.ingress.com/?pll=50.08745114.420671&ll=50.08745114.420671'],
			[false, 'https://intel.ingress.com/?pll=50.087451-14.420671&ll=50.087451-14.420671'],
		];
	}

	public static function isValidOnlyPortalProvider(): array
	{
		return [

			[true, 'https://intel.ingress.com/?pll=50.087451,14.420671&ll=fdassafd'],
			[true, 'https://intel.ingress.com/?pll=50.087451,14.420671&ll=50.087451----14.420671'],
		];
	}

	public static function isValidOnlyMapProvider(): array
	{
		return [

			[true, 'https://intel.ingress.com/?ll=50.087451,14.420671&pll=fdassafd'],
			[true, 'https://intel.ingress.com/?ll=50.087451,14.420671&pll=50.087451----14.420671'],
		];
	}

	/**
	 * Old format without subdomain "intel." but with path "/intel".
	 * As of 2022-05-22 "/intel" redirects to "intel.ingress.com/intel"
	 */
	public static function isValidOldFormatProvider(): array
	{
		return [
			[true, 'https://ingress.com/intel?ll=50.087451,14.420671'],
			[true, 'https://ingress.com/intel?pll=50.087451,14.420671&ll=50.087451,14.420671'],
			[true, 'https://ingress.com/intel?pll=-50.087451,-14.420671&ll=-50.087451,-14.420671'],
			[true, 'http://ingress.com/intel?pll=-50.087451,-14.420671&ll=-50.087451,-14.420671'],
			[true, 'https://intel.ingress.com/intel?pll=-50.087451,-14.420671&ll=-50.087451,-14.420671'],
			[true, 'http://intel.ingress.com/intel?pll=-50.087451,-14.420671&ll=-50.087451,-14.420671'],

			// As of 2022-05-22 links are not valid, but process them as ok
			[true, 'https://ingress.com/?pll=50.087451,14.420671&ll=50.087451,14.420671'],
			[true, 'https://ingress.com/?ll=50.087451,14.420671'],
			[true, 'https://ingress.com/?pll=50.087451,14.420671'],
		];
	}

	public static function processMapProvider(): array
	{
		return [
			[
				[
					[
						50.087451,
						14.420671,
						IngressIntelService::TYPE_MAP,
						'<a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671">Staroměstské náměstí 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087451,14.420671">🖥</a> <a href="https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm=s10000">🖼</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => '',
						],
					],
				],
				'https://intel.ingress.com/?ll=50.087451,14.420671',
			],
			[
				[
					[
						50.123456789,
						14.987654321,
						IngressIntelService::TYPE_MAP,
						'<a href="https://intel.ingress.com/?ll=50.123456789%2C14.987654321">Ingress map</a>',
						[],
					],
				],
				'https://intel.ingress.com/?ll=50.123456789,14.987654321',
			],
		];
	}

	public static function processPortalProvider(): array
	{
		return [
			[
				[
					[
						50.087451,
						14.420671,
						IngressIntelService::TYPE_PORTAL,
						'<a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671">Staroměstské náměstí 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087451,14.420671">🖥</a> <a href="https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm=s10000">🖼</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => '',
						],
					],
				],
				'https://intel.ingress.com/?pll=50.087451,14.420671',
			],
			[
				[
					[
						50.123456789,
						14.987654321,
						IngressIntelService::TYPE_PORTAL,
						'<a href="https://intel.ingress.com/?pll=50.123456789%2C14.987654321">Ingress portal</a>',
						[],
					],
				],
				'https://intel.ingress.com/?pll=50.123456789,14.987654321',
			],
		];
	}

	public static function processMapAndPortalProvider(): array
	{
		return [
			[
				[
					[
						43.123456789,
						12.987654321,
						IngressIntelService::TYPE_PORTAL,
						'<a href="https://intel.ingress.com/?ll=50.123456789%2C14.987654321&pll=43.123456789%2C12.987654321">Ingress portal</a>',
						[],
					],
					[
						50.123456789,
						14.987654321,
						IngressIntelService::TYPE_MAP,
						'<a href="https://intel.ingress.com/?ll=50.123456789%2C14.987654321&pll=43.123456789%2C12.987654321">Ingress map</a>',
						[],
					],
				],
				'https://intel.ingress.com/?ll=50.123456789,14.987654321&pll=43.123456789,12.987654321',
			],
			[
				[
					[
						-0.11,
						14.987654321,
						IngressIntelService::TYPE_PORTAL,
						'<a href="https://intel.ingress.com/?pll=-0.11%2C14.987654321&ll=89.123456789%2C12.987654321">Ingress portal</a>',
						[],
					],
					[
						89.123456789,
						12.987654321,
						IngressIntelService::TYPE_MAP,
						'<a href="https://intel.ingress.com/?pll=-0.11%2C14.987654321&ll=89.123456789%2C12.987654321">Ingress map</a>',
						[],
					],
				],
				'https://intel.ingress.com/?pll=-0.11,14.987654321&ll=89.123456789,12.987654321',
			],
		];
	}

	/**
	 * @dataProvider isValidMapProvider
	 * @dataProvider isValidPortalProvider
	 * @dataProvider isValidPortalAndMapProvider
	 * @dataProvider isValidOnlyPortalProvider
	 * @dataProvider isValidOnlyMapProvider
	 * @dataProvider isValidOldFormatProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new IngressIntelService(new Client($this->httpTestClients->mockedRequestor));
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @dataProvider processMapProvider
	 * @dataProvider processPortalProvider
	 * @dataProvider processMapAndPortalProvider
	 * @group request
	 */
	public function testProcessReal(array $expectedResults,
		string $input,
	): void {
		$service = new IngressIntelService(new Client($this->httpTestClients->realRequestor));
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * @dataProvider processMapProvider
	 * @dataProvider processPortalProvider
	 * @dataProvider processMapAndPortalProvider
	 */
	public function testProcessOffline(array $expectedResults, string $input): void
	{
		$service = new IngressIntelService(new Client($this->httpTestClients->offlineRequestor));
		$this->assertServiceLocations($service, $input, $expectedResults);
	}
}
