<?php declare(strict_types=1);

namespace Tests\Utils;

use App\Utils\Ingress;
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

	/**
	 * @dataProvider isGuidDataProvider
	 */
	public function testIsGuid(bool $expectedIsValid, string $guid): void
	{
		$this->assertSame($expectedIsValid, Ingress::isGuid($guid));
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
			'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671',
			(string)Ingress::generatePrimePortalLink('0bd94fac5de84105b6eef6e7e1639ad9.12', 50.087451, 14.420671),
		);
	}

	public function testGeneratePrimeMissionLink(): void
	{
		$this->assertSame(
			'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fb9d1285bdf184780a79f1a00b68e893e.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fb9d1285bdf184780a79f1a00b68e893e.1c',
			(string)Ingress::generatePrimeMissionLink('b9d1285bdf184780a79f1a00b68e893e.1c'),
		);
	}
}
