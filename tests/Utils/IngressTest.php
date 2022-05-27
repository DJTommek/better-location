<?php declare(strict_types=1);

namespace Tests\Utils;

use App\BetterLocation\BetterLocation;
use App\Factory;
use App\Utils\Ingress;
use PHPUnit\Framework\TestCase;

final class IngressTest extends TestCase
{
	/**
	 * @group request
	 */
	public function testAddPortalDataWithoutPortal(): void
	{
		$location = BetterLocation::fromLatLon(50.087451, 14.420671);
		$this->assertSame('WGS84', $location->getPrefixMessage());
		Ingress::addPortalData($location);
		$this->assertSame(
			'WGS84 <a href="https://intel.ingress.com/?ll=50.087451,14.420671&pll=50.087451,14.420671">Staroměstské náměstí</a> <a href="https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm">🖼</a> <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671">📱</a>',
			$location->getPrefixMessage()
		);
	}

	/**
	 * @group request
	 */
	public function testAddPortalDataWithPortal(): void
	{
		$location = BetterLocation::fromLatLon(50.087451, 14.420671);
		$portal = Factory::IngressLanchedRu()->getPortalByCoords(50.087451, 14.420671);
		$this->assertSame('WGS84', $location->getPrefixMessage());
		Ingress::addPortalData($location, $portal);
		$this->assertSame(
			'WGS84 <a href="https://intel.ingress.com/?ll=50.087451,14.420671&pll=50.087451,14.420671">Staroměstské náměstí</a> <a href="https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm">🖼</a> <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671">📱</a>',
			$location->getPrefixMessage()
		);
	}

	public function testGeneratePrimePortalLink(): void
	{
		$this->assertSame(
			'https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671',
			(string)Ingress::generatePrimePortalLink('0bd94fac5de84105b6eef6e7e1639ad9.12', 50.087451, 14.420671)
		);
	}

}
