<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\NianticLightshipService;
use App\IngressLanchedRu\Client;
use App\Utils\Ingress;
use Tests\HttpTestClients;

final class NianticLightshipServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return NianticLightshipService::class;
	}

	protected function getShareLinks(): array
	{
		return [
			'https://lightship.dev/account/geospatial-browser/50.087451,14.420671,12.66',
			'https://lightship.dev/account/geospatial-browser/50.1,14.5,12.66',
			'https://lightship.dev/account/geospatial-browser/-50.2,14.6000001,12.66', // round down
			'https://lightship.dev/account/geospatial-browser/50.3,-14.7000009,12.66', // round up
			'https://lightship.dev/account/geospatial-browser/-50.4,-14.800008,12.66',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function isValidProvider(): array
	{
		return [
			[true, 'https://lightship.dev/account/geospatial-browser/50.086337251910294,14.424664534280282,15.77,BE422C1F28F641BA851A61EF1E62DC1A,7b0430b1d9d6465fb3348e08e8e6212a.16'],
			[true, 'https://lightship.dev/account/geospatial-browser/50.086337251910294,14.424664534280282,15.77,,7b0430b1d9d6465fb3348e08e8e6212a.16'],
			[true, 'https://lightship.dev/account/geospatial-browser/50.086337251910294,14.424664534280282,15.77,7b0430b1d9d6465fb3348e08e8e6212a.16'], // valid but it does not open venue details
			[true, 'https://lightship.dev/ACCOUNT/geospatial-browser/50.086337251910294,14.424664534280282,15.77,BE422C1F28F641BA851A61EF1E62DC1A,7b0430b1d9d6465fb3348e08e8e6212a.16'],
			[true, 'https://lightship.dev/account/geospatial-browser/50.086337251910294,14.424664534280282,15.77,BE422C1F28F641BA851A61EF1E62DC1A,7b0430b1d9d6465fb3348e08e8e6212a.16/scan-wayspot'],
			[true, 'https://lightship.dev/account/geospatial-browser/-1.246368299787207,-78.62100019937833,16.28,,f5da8bbf6f3a3379929891c7e13648fa.16'],
			[true, 'https://lightship.dev/account/geospatial-browser/50.0830485642698,14.42820958675955'],
			[true, 'https://lightship.dev/account/geospatial-browser/,,,,f5da8bbf6f3a3379929891c7e13648fa.16'],

			[false, 'https://lightship.dev/'],
			[false, 'https://intel.lightship.dev/'],
			[false, 'http://intel.lightship.dev'],
			[false, 'https://intel.lightship.dev/intel'],
			[false, 'https://lightship.dev/intel'],
			[false, 'http://lightship.dev/intel'],
			[false, 'https://lightship.dev/account/geospatial-browser/'],
			[false, 'https://lightship.dev/account/geospatial-browser/,,,,'],
			[false, 'https://lightship.dev/account/geospatial-browser/50.0830485642698'],
			[false, 'https://lightship.dev/account/geospatial-browser/50.0830485642698,999.42820958675955'],
			[false, 'https://lightship.dev/foo/bar/50.0830485642698,14.42820958675955'],
		];
	}

	public static function processPortalProvider(): array
	{
		return [
			[
				[
					[
						50.083594,
						14.424841,
						NianticLightshipService::TYPE_VENUE,
						'<a href="https://lightship.dev/account/geospatial-browser/50.0830485642698,14.42820958675955,15.69,13102D0F2EDC41BAB400A4D3FD672CEF,6a01961a5fc54df8b7efe45fc1f983f9.16">Lightship venue</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => 'Ingress portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F6a01961a5fc54df8b7efe45fc1f983f9.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.083594%2C14.424841">Zlata Husa Statues 📱</a> <a href="https://intel.ingress.com/intel?pll=50.083594,14.424841">🖥</a> <a href="https://lh3.googleusercontent.com/k1D3QBhuIjx8D4-srruvtDXqfSJE1YE4JDoDlKaLksOwv6XPsTaHMVn5wb-YdEKHUszHvw3OaBoCo551-_e72EYluKE=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.083594,14.424841,12.66,,6a01961a5fc54df8b7efe45fc1f983f9.16">🎦</a>',
						],
					],
					[
						50.08304856427,
						14.42820958676,
						NianticLightshipService::TYPE_MAP_CENTER,
						'<a href="https://lightship.dev/account/geospatial-browser/50.0830485642698,14.42820958675955,15.69,13102D0F2EDC41BAB400A4D3FD672CEF,6a01961a5fc54df8b7efe45fc1f983f9.16">Lightship map center</a>',
					],
				],
				'https://lightship.dev/account/geospatial-browser/50.0830485642698,14.42820958675955,15.69,13102D0F2EDC41BAB400A4D3FD672CEF,6a01961a5fc54df8b7efe45fc1f983f9.16',
			],
			'Venue GUID only' => [ // as of 2025-10-24 link is not working but contains valid data so it can be parsed
				[
					[
						50.083594,
						14.424841,
						NianticLightshipService::TYPE_VENUE,
						'<a href="https://lightship.dev/account/geospatial-browser/,,,,6a01961a5fc54df8b7efe45fc1f983f9.16">Lightship venue</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => 'Ingress portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F6a01961a5fc54df8b7efe45fc1f983f9.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.083594%2C14.424841">Zlata Husa Statues 📱</a> <a href="https://intel.ingress.com/intel?pll=50.083594,14.424841">🖥</a> <a href="https://lh3.googleusercontent.com/k1D3QBhuIjx8D4-srruvtDXqfSJE1YE4JDoDlKaLksOwv6XPsTaHMVn5wb-YdEKHUszHvw3OaBoCo551-_e72EYluKE=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.083594,14.424841,12.66,,6a01961a5fc54df8b7efe45fc1f983f9.16">🎦</a>',
						],
					],
				],
				'https://lightship.dev/account/geospatial-browser/,,,,6a01961a5fc54df8b7efe45fc1f983f9.16',
			],
			'Map center only' => [
				[
					[
						50.08304856427,
						14.42820958676,
						NianticLightshipService::TYPE_MAP_CENTER,
						'<a href="https://lightship.dev/account/geospatial-browser/50.0830485642698,14.42820958675955">Lightship map center</a>',
					],
				],
				'https://lightship.dev/account/geospatial-browser/50.0830485642698,14.42820958675955',
			],
			[
				[
					[
						50.087451,
						14.420671,
						NianticLightshipService::TYPE_VENUE,
						'<a href="https://lightship.dev/account/geospatial-browser/50.08771687314254,14.421156827774865,16.55,,0bd94fac5de84105b6eef6e7e1639ad9.12">Lightship venue</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => 'Ingress portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671">Staroměstské náměstí 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087451,14.420671">🖥</a> <a href="https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/50.087451,14.420671,12.66,,0bd94fac5de84105b6eef6e7e1639ad9.12">🎦</a>',
						],
					],
					[
						50.087716873143,
						14.421156827775,
						NianticLightshipService::TYPE_MAP_CENTER,
						'<a href="https://lightship.dev/account/geospatial-browser/50.08771687314254,14.421156827774865,16.55,,0bd94fac5de84105b6eef6e7e1639ad9.12">Lightship map center</a>',
						[],
					],
				],
				'https://lightship.dev/account/geospatial-browser/50.08771687314254,14.421156827774865,16.55,,0bd94fac5de84105b6eef6e7e1639ad9.12',
			],
			[
				[
					[
						17.971243,
						-76.792813,
						NianticLightshipService::TYPE_VENUE,
						'<a href="https://lightship.dev/account/geospatial-browser/17.97103042151872,-76.79266115330043,17.56,,3315a6699a314ebfb06d24242b6eecbc.11">Lightship venue</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => 'Ingress portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F3315a6699a314ebfb06d24242b6eecbc.11&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D17.971243%2C-76.792813">Parade Square Fountain 📱</a> <a href="https://intel.ingress.com/intel?pll=17.971243,-76.792813">🖥</a> <a href="https://lh3.googleusercontent.com/chD5pZ3JmZqtGmKB1MNIasg20J-lcXb6oBePXKs4C3-xq1wFB5OWd6j0o2nF63uFoZc423_-8jLWjaikMz7dQMdnPeI=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/17.971243,-76.792813,12.66,,3315a6699a314ebfb06d24242b6eecbc.11">🎦</a>',
						],
					],
					[
						17.971030421519,
						-76.7926611533,
						NianticLightshipService::TYPE_MAP_CENTER,
						'<a href="https://lightship.dev/account/geospatial-browser/17.97103042151872,-76.79266115330043,17.56,,3315a6699a314ebfb06d24242b6eecbc.11">Lightship map center</a>',
						[],
					],
				],
				'https://lightship.dev/account/geospatial-browser/17.97103042151872,-76.79266115330043,17.56,,3315a6699a314ebfb06d24242b6eecbc.11',
			],
			[
				[
					[
						-1.247257,
						-78.620714,
						NianticLightshipService::TYPE_VENUE,
						'<a href="https://lightship.dev/account/geospatial-browser/-1.246368299787207,-78.62100019937833,16.28,,f5da8bbf6f3a3379929891c7e13648fa.16">Lightship venue</a>',
						[
							Ingress::BETTER_LOCATION_KEY_PORTAL => 'Ingress portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Ff5da8bbf6f3a3379929891c7e13648fa.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D-1.247257%2C-78.620714">El Futbolista 📱</a> <a href="https://intel.ingress.com/intel?pll=-1.247257,-78.620714">🖥</a> <a href="https://lh3.googleusercontent.com/F6iBz9gUWuRV-QCKdO-78KS9Ge32p32e2jBn9NnL9gZCQIrPEQhH795YwWGM_U1GpQVxD3tHAaWFwlUEmXpV0mhzBXbX2D-GE5Mxe9o=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/-1.247257,-78.620714,12.66,,f5da8bbf6f3a3379929891c7e13648fa.16">🎦</a>',
						],
					],
					[
						-1.2463682997872,
						-78.621000199378,
						NianticLightshipService::TYPE_MAP_CENTER,
						'<a href="https://lightship.dev/account/geospatial-browser/-1.246368299787207,-78.62100019937833,16.28,,f5da8bbf6f3a3379929891c7e13648fa.16">Lightship map center</a>',
						[],
					],
				],
				'https://lightship.dev/account/geospatial-browser/-1.246368299787207,-78.62100019937833,16.28,,f5da8bbf6f3a3379929891c7e13648fa.16',
			],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new NianticLightshipService(new Client($this->httpTestClients->mockedRequestor));
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @dataProvider processPortalProvider
	 * @group request
	 */
	public function testProcessReal(array $expectedResults,
		string $input,
	): void {
		$service = new NianticLightshipService(new Client($this->httpTestClients->realRequestor));
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * @dataProvider processPortalProvider
	 */
	public function testProcessOffline(array $expectedResults, string $input): void
	{
		$service = new NianticLightshipService(new Client($this->httpTestClients->offlineRequestor));
		$this->assertServiceLocations($service, $input, $expectedResults);
	}
}
