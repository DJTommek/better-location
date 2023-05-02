<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\VojenskoCzService;
use PHPUnit\Framework\TestCase;

final class VojenskoCzServiceTest extends TestCase
{
	private function assertLocation(string $url, float $lat, float $lon): void
	{
		$collection = VojenskoCzService::processStatic($url)->getCollection();
		$this->assertCount(1, $collection);
		$location = $collection->getFirst();
		$this->assertEqualsWithDelta($lat, $location->getLat(), 0.000001);
		$this->assertEqualsWithDelta($lon, $location->getLon(), 0.000001);
	}

	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		VojenskoCzService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		VojenskoCzService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValid(): void
	{
		$this->assertTrue(VojenskoCzService::isValidStatic('http://www.vojensko.cz/vu-5849-jachymov-vrsek'));
		$this->assertTrue(VojenskoCzService::isValidStatic('http://www.vojensko.cz/pavlova-hut'));
		$this->assertTrue(VojenskoCzService::isValidStatic('http://vojensko.cz/pavlova-hut'));
		$this->assertTrue(VojenskoCzService::isValidStatic('http://www.vojensko.cz/velka-hledsebe-klimentov'));
		$this->assertTrue(VojenskoCzService::isValidStatic('http://www.vojensko.cz/poddustojnicka-skola-psovodu-libejovice?image=89'));

		// Invalid
		$this->assertFalse(VojenskoCzService::isValidStatic('some invalid url'));
		$this->assertFalse(VojenskoCzService::isValidStatic('http://www.vojensko.cz/'));
		$this->assertFalse(VojenskoCzService::isValidStatic('https://www.vojensko.cz/vu-5849-jachymov-vrsek')); // https is not working
		$this->assertFalse(VojenskoCzService::isValidStatic('http://www.some-domain.cz/'));
		$this->assertFalse(VojenskoCzService::isValidStatic('http://www.some-domain.cz/some-path'));
	}

	/**
	 * @group request
	 */
	public function testProcessPlace(): void
	{
		$this->assertLocation('http://www.vojensko.cz/vu-5849-jachymov-vrsek', 50.375738, 12.863950);
		$this->assertLocation('http://www.vojensko.cz/vu-5849-jachymov-vrsek?image=4', 50.375738, 12.863950);
		$this->assertLocation('http://www.vojensko.cz/vu-5849-jachymov-vrsek?sort=fibre#vasekomentare', 50.375738, 12.863950);

		$this->assertLocation('http://www.vojensko.cz/velka-hledsebe-klimentov', 49.965879166667, 12.667772777778);
		$this->assertLocation('http://www.vojensko.cz/1-rps-trojmezi', 50.302519444444, 12.143930555556);
		$this->assertLocation('http://www.vojensko.cz/11-rps-cerchov', 49.389722222222, 12.770575);
		$this->assertLocation('http://www.vojensko.cz/poddustojnicka-skola-psovodu-libejovice?image=89', 49.111813888889, 14.182483055556);
		$this->assertLocation('http://www.vojensko.cz/muzeum-pohranicni-straze-kota-rozvadov', 49.673671388889, 12.545002777778);
		$this->assertLocation('http://www.vojensko.cz/ph-164-mrakov', 49.39109, 12.950441111111);
	}

	/**
	 * Pages, that do not have any location
	 * @group request
	 */
	public function testInvalid(): void
	{
		// specific pages
		$this->assertCount(0, VojenskoCzService::processStatic('http://www.vojensko.cz/pohranicnici-na-dunaji')->getCollection());
		$this->assertCount(0, VojenskoCzService::processStatic('http://www.vojensko.cz/hlavni-stranka')->getCollection());
		$this->assertCount(0, VojenskoCzService::processStatic('http://www.vojensko.cz/borova-lada-r-1989-90')->getCollection());
		$this->assertCount(0, VojenskoCzService::processStatic('http://www.vojensko.cz/knizeci-plane-r-1975-77')->getCollection());
		$this->assertCount(0, VojenskoCzService::processStatic('http://www.vojensko.cz/vite-co-je-na-snimku-08')->getCollection());

		// general web pages
		$this->assertCount(0, VojenskoCzService::processStatic('http://www.vojensko.cz/mapa-stranek')->getCollection());
		$this->assertCount(0, VojenskoCzService::processStatic('http://www.vojensko.cz/rss')->getCollection());
		$this->assertCount(0, VojenskoCzService::processStatic('https://www.vojensko.cz/feed/rss/aktuality.php')->getCollection());
		$this->assertCount(0, VojenskoCzService::processStatic('http://www.vojensko.cz/kontakt')->getCollection());
	}
}
