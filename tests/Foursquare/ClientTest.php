<?php declare(strict_types=1);

namespace Tests\Foursquare;

use App\Config;
use App\Foursquare\Client as FoursquareClient;
use PHPUnit\Framework\TestCase;
use Tests\HttpTestClients;

final class ClientTest extends TestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	public static function loadVenueProvider(): array
	{
		return [
			['Typika', 50.062101, 14.442558, 'Svatoslavova 319/21 (Vladimírova 319/4) 140 00 Praha Česká republika', 'https://foursquare.com/v/typika/5bfe5f9e54b7a90025543a66', '5bfe5f9e54b7a90025543a66'],
			['Staroměstské náměstí | Old Town Square (Staroměstské náměstí)', 50.08728390792172, 14.420980775372074, 'Staroměstské nám. 110 00 Praha Česká republika', 'https://foursquare.com/v/starom%C4%9Bstsk%C3%A9-n%C3%A1m%C4%9Bst%C3%AD--old-town-square/4bbdfa09f57ba593a6b3aeb9', '4bbdfa09f57ba593a6b3aeb9'],
			['King of Snake', -43.52274891995727, 172.62915788554935, '145 Victoria St Christchurch 8011 New Zealand', 'https://foursquare.com/v/king-of-snake/50372df3e4b06ff8767430aa', '50372df3e4b06ff8767430aa'],
			['순천만 갈대밭', 34.88568980360366, 127.50945459380536, '대대동 순천시 전라남도 대한민국', 'https://foursquare.com/v/%EC%88%9C%EC%B2%9C%EB%A7%8C-%EA%B0%88%EB%8C%80%EB%B0%AD/4bdd313f4ffaa59381056ff7', '4bdd313f4ffaa59381056ff7'],
		];
	}

	/**
	 * @group request
	 *
	 * @dataProvider loadVenueProvider
	 */
	public function testLoadVenueReal(
		string $expectedName,
		float $expectedLat,
		float $expectedLon,
		string $expectedAddress,
		string $expectedCanonicalUrl,
		string $venueId,
	): void {
		if (!Config::isFoursquare()) {
			self::markTestSkipped('Missing Foursquare API credentials');
		}
		// @phpstan-ignore-next-line API credentials might be null, in that case tests are skipped
		$api = new FoursquareClient($this->httpTestClients->realRequestor, Config::FOURSQUARE_CLIENT_ID, Config::FOURSQUARE_CLIENT_SECRET);
		$this->testLoadVenue($api, $expectedName, $expectedLat, $expectedLon, $expectedAddress, $expectedCanonicalUrl, $venueId);
	}

	/**
	 * @dataProvider loadVenueProvider
	 */
	public function testLoadVenueOffline(
		string $expectedName,
		float $expectedLat,
		float $expectedLon,
		string $expectedAddress,
		string $expectedCanonicalUrl,
		string $venueId,
	): void {
		$api = new FoursquareClient($this->httpTestClients->offlineRequestor, '', '');
		$this->testLoadVenue($api, $expectedName, $expectedLat, $expectedLon, $expectedAddress, $expectedCanonicalUrl, $venueId);
	}

	private function testLoadVenue(
		FoursquareClient $api,
		string $expectedName,
		float $expectedLat,
		float $expectedLon,
		string $expectedAddress,
		string $expectedCanonicalUrl,
		string $venueId,
	): void {
		$venue = $api->loadVenue($venueId);
		$this->assertSame($venueId, $venue->id);
		$this->assertSame($expectedName, $venue->name);
		$this->assertSame($expectedLat, $venue->location->lat);
		$this->assertSame($expectedLon, $venue->location->lng);
		$this->assertSame($expectedAddress, $venue->location->getFormattedAddress());
		$this->assertSame($expectedCanonicalUrl, $venue->canonicalUrl);
	}
}
