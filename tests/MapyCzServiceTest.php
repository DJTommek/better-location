<?php declare(strict_types=1);

use BetterLocation\Service\Exceptions\NotImplementedException;
use PHPUnit\Framework\TestCase;
use \BetterLocation\Service\MapyCzService;
use \BetterLocation\Service\Exceptions\InvalidLocationException;

require_once __DIR__ . '/../src/config.php';

final class MapyCzServiceTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateShareLink(): void {
		$this->assertEquals('https://mapy.cz/zakladni?y=50.087451&x=14.420671&source=coor&id=14.420671%2C50.087451', MapyCzService::getLink(50.087451, 14.420671));
		$this->assertEquals('https://mapy.cz/zakladni?y=50.100000&x=14.500000&source=coor&id=14.500000%2C50.100000', MapyCzService::getLink(50.1, 14.5));
		$this->assertEquals('https://mapy.cz/zakladni?y=-50.200000&x=14.600000&source=coor&id=14.600000%2C-50.200000', MapyCzService::getLink(-50.2, 14.6000001)); // round down
		$this->assertEquals('https://mapy.cz/zakladni?y=50.300000&x=-14.700001&source=coor&id=-14.700001%2C50.300000', MapyCzService::getLink(50.3, -14.7000009)); // round up
		$this->assertEquals('https://mapy.cz/zakladni?y=-50.400000&x=-14.800008&source=coor&id=-14.800008%2C-50.400000', MapyCzService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void {
		$this->expectException(NotImplementedException::class);
		$this->expectExceptionMessage('Drive link is not implemented.');
		MapyCzService::getLink(50.087451, 14.420671, true);
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testMapyCzXY(): void {
		$this->assertEquals('50.069524,14.450824', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15')->__toString());
		$this->assertEquals('50.069524,14.450824', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?y=50.0695244&x=14.4508239&z=15')->__toString());
	}

	/**
	 * ID parameter is in coordinates format
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testValidCoordinatesMapyCzId(): void {
		$this->assertEquals('48.873288,14.578971', MapyCzService::parseCoords('https://en.mapy.cz/zemepisna?x=14.5702160&y=48.8734857&z=16&source=coor&id=14.57897074520588%2C48.87328807384455')->__toString());
		$this->assertEquals('50.077886,14.371990', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=14.3985113&y=50.0696783&z=15&source=coor&id=14.371989590930184%2C50.07788610486586')->__toString());
		$this->assertEquals('49.205899,14.257356', MapyCzService::parseCoords('https://en.mapy.cz/textova?x=14.2573931&y=49.2063073&z=18&source=coor&id=14.257355545997598%2C49.205899024478754')->__toString()); // iQuest textova
		$this->assertEquals('7.731071,-80.551001', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=-80.5308310&y=7.7192491&z=15&source=coor&id=-80.55100118168951%2C7.731071318967728')->__toString());
		$this->assertEquals('65.608884,-168.088871', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=-168.0916515&y=65.6066015&z=15&source=coor&id=-168.08887063564356%2C65.60888429109842')->__toString());
		$this->assertEquals('-1.138011,9.034823', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=8.9726814&y=-1.2094073&z=11&source=coor&id=9.034822831066833%2C-1.1380111329277875')->__toString());
	}

	/**
	 * ID parameter is in coordinates format
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testValidCoordinatesMapyCzIdShort(): void {
		$this->assertEquals('48.873288,14.578971', MapyCzService::parseCoords('https://mapy.cz/s/cekahebefu')->__toString());
		$this->assertEquals('48.873288,14.578971', MapyCzService::parseCoords('https://en.mapy.cz/s/gacegelola')->__toString()); // same as above just generated later by different user and on different IP and PC
		$this->assertEquals('50.077886,14.371990', MapyCzService::parseCoords('https://en.mapy.cz/s/bosafonabo')->__toString());
		$this->assertEquals('7.731071,-80.551001', MapyCzService::parseCoords('https://en.mapy.cz/s/godumokefu')->__toString());
		$this->assertEquals('65.608884,-168.088871', MapyCzService::parseCoords('https://en.mapy.cz/s/nopovehuhu')->__toString());
		$this->assertEquals('-1.138011,9.034823', MapyCzService::parseCoords('https://en.mapy.cz/s/lozohefobu')->__toString());
	}

	/**
	 * ID parameter is in INVALID latitude coordinates format
	 */
	public function testInvalidLatCoordinatesMapyCzId(): void {
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Latitude coordinate must be between or equal from -90 to 90 degrees.');
		$this->assertEquals('50.077886,14.371990', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=14.3985113&y=50.0696783&z=15&source=coor&id=14.371989590930184%2C150.07788610486586')->__toString());
	}

	/**
	 * ID parameter is in INVALID longitude coordinates format
	 */
	public function testInvalidLonCoordinatesMapyCzId(): void {
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Longitude coordinate must be between or equal from -180 to 180 degrees.');
		MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=14.3985113&y=50.0696783&z=15&source=coor&id=190.371989590930184%2C50.07788610486586');
	}

	/**
	 * Translate MapyCZ panorama ID to coordinates
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testValidMapyCzPanoramaId(): void {
		if (!is_null(MAPY_CZ_DUMMY_SERVER_URL)) {
			$this->assertEquals('50.075959,15.016772', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=15.0162139&y=50.0820182&z=16&pano=1&pid=68059377&yaw=5.522&fov=1.257&pitch=0.101')->__toString());
			$this->assertEquals('50.123351,16.284569', MapyCzService::parseCoords('https://en.mapy.cz/turisticka?x=16.2845693&y=50.1233926&z=17&pano=1&source=base&id=2107710&pid=66437731&yaw=6.051&fov=1.257&pitch=0.157')->__toString()); // Viribus Unitis 2019
			$this->assertEquals('50.094953,15.023081', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=15.0483153&y=50.1142203&z=15&pano=1&source=firm&id=216358&pid=68007689&yaw=3.985&fov=1.257&pitch=0.033')->__toString()); // Three different locations: map, place and panorama
			$this->assertEquals('50.078499,14.488475', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=14.4883693&y=50.0784958&z=15&pano=1&pid=70254688&yaw=0.424&fov=1.257&pitch=0.088')->__toString()); // First neighbour of this panorama ID don't have original neighbour, so coordinates are little off
		}
	}

	/**
	 * Translate MapyCZ INVALID place ID to coordinates
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testInvalidMapyCzPanoramaId(): void {
		if (!is_null(MAPY_CZ_DUMMY_SERVER_URL)) {
			$this->expectExceptionMessage('Unable to get valid coordinates from panorama ID "99999999999".');
			MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=15.0162139&y=50.0820182&z=16&pano=1&pid=99999999999&yaw=5.522&fov=1.257&pitch=0.101');
		}
	}

	/**
	 * Translate MapyCZ place ID to coordinates
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testValidMapyCzId(): void {
		if (!is_null(MAPY_CZ_DUMMY_SERVER_URL)) {
			$this->assertEquals('50.073784,14.422105', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15&source=base&id=2111676')->__toString());
			$this->assertEquals('50.084007,14.440339', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=14.4527551&y=50.0750056&z=15&source=pubt&id=15308193')->__toString());
			$this->assertEquals('50.084747,14.454012', MapyCzService::parseCoords('https://mapy.cz/zakladni?x=14.4651576&y=50.0796325&z=15&source=firm&id=468797')->__toString());
			$this->assertEquals('50.093312,14.455159', MapyCzService::parseCoords('https://mapy.cz/zakladni?x=14.4367048&y=50.0943640&z=15&source=traf&id=15659817')->__toString());
			$this->assertEquals('49.993611,14.205278', MapyCzService::parseCoords('https://en.mapy.cz/fotografie?x=14.2029782&y=49.9929235&z=17&source=foto&id=1080344')->__toString());
			$this->assertEquals('50.106624,14.366203', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=14.3596717&y=50.0997874&z=15&source=base&id=1833337')->__toString()); // area
			// some other places than Czechia (source OSM)
			$this->assertEquals('49.444980,11.109055', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=11.0924687&y=49.4448356&z=15&source=osm&id=112448327')->__toString());
			// negative coordinates (source OSM)
			$this->assertEquals('54.766918,-101.873729', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=-101.8754373&y=54.7693842&z=15&source=osm&id=1000536418')->__toString());
			$this->assertEquals('-18.917167,47.535756', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=47.5323757&y=-18.9155159&z=16&source=osm&id=1040985945')->__toString());
			$this->assertEquals('-45.870330,-67.507560', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=-67.5159386&y=-45.8711989&z=15&source=osm&id=17164289')->__toString());
		}
	}

	/**
	 * @see MapyCzServiceTest::testValidMapyCzId() exactly the same just shortened links
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testValidMapyCzIdShortUrl(): void {
		if (!is_null(MAPY_CZ_DUMMY_SERVER_URL)) {
			$this->assertEquals('50.533111,16.155906', MapyCzService::parseCoords('https://en.mapy.cz/s/devevemoje')->__toString());
			$this->assertEquals('50.084007,14.440339', MapyCzService::parseCoords('https://en.mapy.cz/s/degogalazo')->__toString());
			$this->assertEquals('50.084747,14.454012', MapyCzService::parseCoords('https://en.mapy.cz/s/cavukepuba')->__toString());
			$this->assertEquals('50.093312,14.455159', MapyCzService::parseCoords('https://en.mapy.cz/s/fuvatavode')->__toString());
			$this->assertEquals('50.106624,14.366203', MapyCzService::parseCoords('https://en.mapy.cz/s/gesaperote')->__toString()); // area
			// some other places than Czechia (source OSM)
			$this->assertEquals('49.444980,11.109055', MapyCzService::parseCoords('https://en.mapy.cz/s/hozogeruvo')->__toString());
			// negative coordinates (source OSM)
			$this->assertEquals('54.766918,-101.873729', MapyCzService::parseCoords('https://en.mapy.cz/s/dasorekeja')->__toString());
			$this->assertEquals('-18.917167,47.535756', MapyCzService::parseCoords('https://en.mapy.cz/s/maposedeso')->__toString());
			$this->assertEquals('-45.870330,-67.507560', MapyCzService::parseCoords('https://en.mapy.cz/s/robelevuja')->__toString());
		}
	}

	/**
	 * Translate MapyCZ INVALID place ID to coordinates
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testInvalidMapyCzId(): void {
		if (!is_null(MAPY_CZ_DUMMY_SERVER_URL)) {
			$this->expectExceptionMessage('Unable to get valid coordinates from place ID "1234".');
			MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15&source=base&id=1234');
		}
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testMapyCzIdFallback(): void {
		// Method is using constant from local config, which can't be changed, so "fake" place ID and put some non-numeric char there which is invalid and it will run fallback to X/Y
		// @TODO refactor this to be able to run true tests
		$this->assertEquals('50.069524,14.450824', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15&source=base&id=2111676a')->__toString());
		$this->assertEquals('50.069524,14.450824', MapyCzService::parseCoords('https://en.mapy.cz/zakladni?y=50.0695244&x=14.4508239&z=15&source=base&id=2111676a')->__toString());
	}

}
