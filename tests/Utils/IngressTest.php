<?php declare(strict_types=1);

namespace Tests\Utils;

use App\Utils\Ingress;
use DJTommek\Coordinates\CoordinatesImmutable;
use PHPUnit\Framework\TestCase;

final class IngressTest extends TestCase
{
	public static function isGuidDataProvider(): array
	{
		return [
			[true, 'b9d1285bdf184780a79f1a00b68e893e.1c'],
			[true, '0BD94FAC5DE84105B6EEF6E7E1639AD9.12'],

			[false, ''],
			[false, 'fdasfdsafsd'],
			[false, 'b9d1285bdf184780a79f1a00b68e893e.1z'],
			[false, 'b9d1285bdf184780a79f1a00b68e893e1c'],
			[false, 'b9d1285bdf184780a79f1a00b68e893e.1cf'],
			[false, 'fb9d1285bdf184780a79f1a00b68e893e.1c'],
		];
	}

	public static function generateNianticLightshipLinkDataProvider(): array
	{
		return [
			[
				'https://lightship.dev/account/geospatial-browser/50.0830485,14.4282095,15.69,13102D0F2EDC41BAB400A4D3FD672CEF,6a01961a5fc54df8b7efe45fc1f983f9.16',
				50.0830485,
				14.4282095,
				15.69,
				'13102D0F2EDC41BAB400A4D3FD672CEF',
				'6a01961a5fc54df8b7efe45fc1f983f9.16',
			],
			['https://lightship.dev/account/geospatial-browser/-50.4,-14.800008,12.66', -50.4, -14.800008],
			['https://lightship.dev/account/geospatial-browser/-50.4,-14.800008,12.66', -50.4, -14.800008, 12.66],
			[
				'https://lightship.dev/account/geospatial-browser/37.4271971,-122.14444,12.66,,fdaa23231c2a375db81fb5d1e32e96d5.16',
				37.4271971,
				-122.14444,
				null,
				null,
				'fdaa23231c2a375db81fb5d1e32e96d5.16',
			],
		];
	}

	/**
	 * @dataProvider isGuidDataProvider
	 */
	public function testIsGuid(bool $expectedIsValid, string $guid): void
	{
		$this->assertSame($expectedIsValid, Ingress::isGuid($guid));
	}

	/**
	 * @dataProvider generateNianticLightshipLinkDataProvider
	 */
	public function testGenerateNianticLightshipLinkDataProvider(
		string $expected,
		float $lat,
		float $lon,
		float|null $zoom = null,
		string|null $meshId = null,
		?string $venueGuid = null,
	): void {
		$this->assertSame($expected, (string)Ingress::generateNianticLightshipLink(new CoordinatesImmutable($lat, $lon), $zoom, $meshId, $venueGuid));
	}

	public function testGenerateIntelMissionLink(): void
	{
		$this->assertSame(
			'https://intel.ingress.com/mission/b9d1285bdf184780a79f1a00b68e893e.1c',
			Ingress::generateIntelMissionLink('b9d1285bdf184780a79f1a00b68e893e.1c'),
		);
	}

	public function testGenerateIntelPortalLink(): void
	{
		$this->assertSame(
			'https://intel.ingress.com/intel?pll=50.087451,14.420671',
			Ingress::generateIntelPortalLink(50.087451, 14.420671),
		);
	}

	public function testGeneratePrimePortalLink(): void
	{
		$this->assertSame(
			'https://link.ingress.com/portal/0bd94fac5de84105b6eef6e7e1639ad9.12',
			(string)Ingress::generatePrimePortalLink('0bd94fac5de84105b6eef6e7e1639ad9.12'),
		);
	}

	public function testGeneratePrimeMissionLink(): void
	{
		$this->assertSame(
			'https://link.ingress.com/mission/b9d1285bdf184780a79f1a00b68e893e.1c',
			(string)Ingress::generatePrimeMissionLink('b9d1285bdf184780a79f1a00b68e893e.1c'),
		);
	}
}
