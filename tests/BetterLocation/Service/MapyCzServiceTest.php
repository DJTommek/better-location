<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\MapyCzService;
use PHPUnit\Framework\TestCase;

final class MapyCzServiceTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://mapy.cz/zakladni?y=50.087451&x=14.420671&source=coor&id=14.420671%2C50.087451', MapyCzService::getLink(50.087451, 14.420671));
		$this->assertSame('https://mapy.cz/zakladni?y=50.100000&x=14.500000&source=coor&id=14.500000%2C50.100000', MapyCzService::getLink(50.1, 14.5));
		$this->assertSame('https://mapy.cz/zakladni?y=-50.200000&x=14.600000&source=coor&id=14.600000%2C-50.200000', MapyCzService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://mapy.cz/zakladni?y=50.300000&x=-14.700001&source=coor&id=-14.700001%2C50.300000', MapyCzService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://mapy.cz/zakladni?y=-50.400000&x=-14.800008&source=coor&id=-14.800008%2C-50.400000', MapyCzService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->assertSame('https://mapy.cz/zakladni?y=50.087451&x=14.420671&source=coor&id=14.420671%2C50.087451', MapyCzService::getLink(50.087451, 14.420671, true));
		$this->assertSame('https://mapy.cz/zakladni?y=50.100000&x=14.500000&source=coor&id=14.500000%2C50.100000', MapyCzService::getLink(50.1, 14.5, true));
		$this->assertSame('https://mapy.cz/zakladni?y=-50.200000&x=14.600000&source=coor&id=14.600000%2C-50.200000', MapyCzService::getLink(-50.2, 14.6000001, true)); // round down
		$this->assertSame('https://mapy.cz/zakladni?y=50.300000&x=-14.700001&source=coor&id=-14.700001%2C50.300000', MapyCzService::getLink(50.3, -14.7000009, true)); // round up
		$this->assertSame('https://mapy.cz/zakladni?y=-50.400000&x=-14.800008&source=coor&id=-14.800008%2C-50.400000', MapyCzService::getLink(-50.4, -14.800008, true));
	}

	public function testGenerateCollectionLink(): void
	{
		$collection = new BetterLocationCollection();
		$collection->add(BetterLocation::fromLatLon(50.087451, 14.420671));
		$collection->add(BetterLocation::fromLatLon(50.3, -14.7000009));
		$this->assertSame('https://mapy.cz/?query=50.087451,14.420671;50.300000,-14.700001', MapyCzService::getCollectionLink($collection));
	}

	public function testIsValidMap(): void
	{
		$this->assertTrue(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15'));
		$this->assertTrue(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?x=-14.4508239&y=50.0695244&z=15'));
		$this->assertTrue(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=-50.0695244&z=15'));
		$this->assertTrue(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?x=-14.4508239&y=-50.0695244&z=15'));
		$this->assertTrue(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzService::isValidStatic('https://mapy.cz/zakladni?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzService::isValidStatic('http://mapy.cz/textova?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?x=14&y=50'));

		$this->assertFalse(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?xx=14.4508239&y=50.0695244'));
		$this->assertFalse(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?y=50.0695244'));
		$this->assertFalse(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?x=50.0695244'));
		$this->assertFalse(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?x=14.4508.239&y=50.0695244'));
		$this->assertFalse(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695.244'));
		$this->assertFalse(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239a&y=50.0695244'));
		$this->assertFalse(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239a&y=50.0695244a'));
		$this->assertFalse(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?x=14.a4508239&y=50.a0695244'));
		$this->assertFalse(MapyCzService::isValidStatic('http://mapy.cz/zemepisna?x=14.a4508239&y=50.a0695244'));
		$this->assertFalse(MapyCzService::isValidStatic('http://mapy.cz/zakladni?x=114.4508239&y=50.0695244&'));
		$this->assertFalse(MapyCzService::isValidStatic('http://mapy.cz/zakladni?x=14.4508239&y=250.0695244'));
	}

	public function testCoordsMap(): void
	{
		$this->assertSame('50.069524,14.450824', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,-14.450824', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=-14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('-50.069524,14.450824', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=-50.0695244')->getFirst()->__toString());
		$this->assertSame('-50.069524,-14.450824', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=-14.4508239&y=-50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzService::processStatic('https://mapy.cz/zakladni?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzService::processStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzService::processStatic('http://mapy.cz/textova?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzService::processStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzService::processStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzService::processStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzService::processStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzService::processStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.000000,14.000000', MapyCzService::processStatic('http://mapy.cz/zemepisna?x=14&y=50')->getFirst()->__toString());
	}

	public function testIsValidCoordId(): void
	{
		$this->assertTrue(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.0695244&z=15'));
		$this->assertTrue(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14,50.0695244&z=15'));
		$this->assertTrue(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50&z=15'));
		$this->assertTrue(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14,50'));
		$this->assertTrue(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.0695244'));
		$this->assertTrue(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,-50.0695244'));
		$this->assertTrue(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=-14.4508239,50.0695244'));
		$this->assertTrue(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=-14.4508239,-50.0695244'));

		$this->assertFalse(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.0695244a'));
		$this->assertFalse(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239a,50.0695244'));
		$this->assertFalse(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.450.8239,50.0695244'));
		$this->assertFalse(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.06.95244'));
		$this->assertFalse(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,150.0695244'));
		$this->assertFalse(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,-150.0695244'));
		$this->assertFalse(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=514.4508239,15.0695244'));
		$this->assertFalse(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=-514.4508239,15.0695244'));
		$this->assertFalse(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239-50.0695244'));
		$this->assertFalse(MapyCzService::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239'));
	}

	/**
	 * ID parameter is in coordinates format
	 */
	public function testValidCoordinatesMapyCzId(): void
	{
		$collection = MapyCzService::processStatic('https://en.mapy.cz/zemepisna?x=14.6666666666&y=48.222&z=16&source=coor&id=14.33333333333333%2C48.77777777777')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('48.777778,14.333333', $collection[0]->__toString());
		$this->assertSame('48.222000,14.666667', $collection[1]->__toString());

		$collection = MapyCzService::processStatic('https://en.mapy.cz/zemepisna?source=coor&id=14.33333333333333%2C48.77777777777')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.777778,14.333333', $collection[0]->__toString());

		$this->assertSame('48.873288,14.578971', MapyCzService::processStatic('https://en.mapy.cz/zemepisna?x=14.5702160&y=48.8734857&z=16&source=coor&id=14.57897074520588%2C48.87328807384455')->getFirst()->__toString());
		$this->assertSame('50.077886,14.371990', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=14.3985113&y=50.0696783&z=15&source=coor&id=14.371989590930184%2C50.07788610486586')->getFirst()->__toString());
		$this->assertSame('49.205899,14.257356', MapyCzService::processStatic('https://en.mapy.cz/textova?x=14.2573931&y=49.2063073&z=18&source=coor&id=14.257355545997598%2C49.205899024478754')->getFirst()->__toString()); // iQuest textova
		$this->assertSame('7.731071,-80.551001', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=-80.5308310&y=7.7192491&z=15&source=coor&id=-80.55100118168951%2C7.731071318967728')->getFirst()->__toString());
		$this->assertSame('65.608884,-168.088871', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=-168.0916515&y=65.6066015&z=15&source=coor&id=-168.08887063564356%2C65.60888429109842')->getFirst()->__toString());
		$this->assertSame('-1.138011,9.034823', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=8.9726814&y=-1.2094073&z=11&source=coor&id=9.034822831066833%2C-1.1380111329277875')->getFirst()->__toString());
	}

	public function testMapyCzXY(): void
	{
		$this->assertSame('50.069524,14.450824', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzService::processStatic('https://en.mapy.cz/zakladni?y=50.0695244&x=14.4508239&z=15')->getFirst()->__toString());
	}

	/**
	 * ID parameter is in coordinates format
	 * @group request
	 */
	public function testValidCoordinatesMapyCzIdShort(): void
	{
		$collection = MapyCzService::processStatic('https://mapy.cz/s/cekahebefu')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('48.873288,14.578971', $collection[0]->__toString());
		$this->assertSame('Place coords', $collection[0]->getSourceType());
		$this->assertSame('48.873486,14.570216', $collection[1]->__toString());
		$this->assertSame('Map center', $collection[1]->getSourceType());

		$collection = MapyCzService::processStatic('https://en.mapy.cz/s/gacegelola')->getCollection(); // same as above just generated later by different user and on different IP and PC
		$this->assertCount(2, $collection);
		$this->assertSame('48.873288,14.578971', $collection[0]->__toString());
		$this->assertSame('Place coords', $collection[0]->getSourceType());
		$this->assertSame('48.873486,14.570216', $collection[1]->__toString());
		$this->assertSame('Map center', $collection[1]->getSourceType());

		$collection = MapyCzService::processStatic('https://en.mapy.cz/s/bosafonabo')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('50.077886,14.371990', $collection[0]->__toString());
		$this->assertSame('Place coords', $collection[0]->getSourceType());
		$this->assertSame('50.069678,14.398511', $collection[1]->__toString());
		$this->assertSame('Map center', $collection[1]->getSourceType());

		$this->assertSame('7.731071,-80.551001', MapyCzService::processStatic('https://en.mapy.cz/s/godumokefu')->getFirst()->__toString());
		$this->assertSame('65.608884,-168.088871', MapyCzService::processStatic('https://en.mapy.cz/s/nopovehuhu')->getFirst()->__toString());
		$this->assertSame('-1.138011,9.034823', MapyCzService::processStatic('https://en.mapy.cz/s/lozohefobu')->getFirst()->__toString());
	}

	/**
	 * Coordinates in ID parameter are INVALID
	 */
	public function testInvalidLatCoordinatesMapyCzId(): void
	{
		// Latitude coordinate must be between or equal from -90 to 90 degrees.
		$collection = MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=14.3985113&y=50.0696783&z=15&source=coor&id=14.371989590930184%2C150.07788610486586')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.069678,14.398511', $collection[0]->__toString());

		// Longitude coordinate must be between or equal from -180 to 180 degrees.
		$collection = MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=14.3985113&y=50.0696783&z=15&source=coor&id=190.371989590930184%2C50.07788610486586')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.069678,14.398511', $collection[0]->__toString());
	}

	/**
	 * Translate MapyCZ panorama ID to coordinates
	 * @group request
	 */
	public function testValidMapyCzPanoramaId(): void
	{
		$this->assertSame('50.075959,15.016772', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=15.0162139&y=50.0820182&z=16&pano=1&pid=68059377&yaw=5.522&fov=1.257&pitch=0.101')->getFirst()->__toString());
		$this->assertSame('50.123351,16.284569', MapyCzService::processStatic('https://en.mapy.cz/turisticka?x=16.2845693&y=50.1233926&z=17&pano=1&source=base&id=2107710&pid=66437731&yaw=6.051&fov=1.257&pitch=0.157')->getFirst()->__toString()); // Viribus Unitis 2019
		$this->assertSame('50.094953,15.023081', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=15.0483153&y=50.1142203&z=15&pano=1&source=firm&id=216358&pid=68007689&yaw=3.985&fov=1.257&pitch=0.033')->getFirst()->__toString()); // Three different locations: map, place and panorama
		// First neighbour of this panorama ID don't have original neighbour, so coordinates are little off (Original test using "get neighbour of neighbour" result was '50.078499,14.488475')
		$this->assertSame('50.078496,14.488369', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=14.4883693&y=50.0784958&z=15&pano=1&pid=70254688&yaw=0.424&fov=1.257&pitch=0.088')->getFirst()->__toString());
	}

	/**
	 * Translate MapyCZ INVALID place ID to coordinates
	 * @group request
	 */
	public function testInvalidPanoramaId(): void
	{
		$collection = MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=15.0162139&y=50.0820182&z=16&pano=1&pid=99999999999&yaw=5.522&fov=1.257&pitch=0.101')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.082018,15.016214', $collection[0]->__toString());

		$collection = MapyCzService::processStatic('https://en.mapy.cz/zakladni?pano=1&pid=99999999999&yaw=5.522&fov=1.257&pitch=0.101')->getCollection();
		$this->assertCount(0, $collection);
	}

	/**
	 * Translate MapyCZ place ID to coordinates
	 * @group request
	 */
	public function testValidMapyCzId(): void
	{
		$this->assertSame('50.073784,14.422105', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15&source=base&id=2111676')->getFirst()->__toString());
		$this->assertSame('50.084007,14.440339', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=14.4527551&y=50.0750056&z=15&source=pubt&id=15308193')->getFirst()->__toString());
		$this->assertSame('50.084747,14.454012', MapyCzService::processStatic('https://mapy.cz/zakladni?x=14.4651576&y=50.0796325&z=15&source=firm&id=468797')->getFirst()->__toString());
		$this->assertSame('50.093312,14.455159', MapyCzService::processStatic('https://mapy.cz/zakladni?x=14.4367048&y=50.0943640&z=15&source=traf&id=15659817')->getFirst()->__toString());
		$this->assertSame('49.993611,14.205278', MapyCzService::processStatic('https://en.mapy.cz/fotografie?x=14.2029782&y=49.9929235&z=17&source=foto&id=1080344')->getFirst()->__toString());
		$this->assertSame('50.106624,14.366203', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=14.3596717&y=50.0997874&z=15&source=base&id=1833337')->getFirst()->__toString()); // area
		// some other places than Czechia (source OSM)
		$this->assertSame('49.444980,11.109055', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=11.0924687&y=49.4448356&z=15&source=osm&id=112448327')->getFirst()->__toString());
		// negative coordinates (source OSM)
		$this->assertSame('54.766918,-101.873729', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=-101.8754373&y=54.7693842&z=15&source=osm&id=1000536418')->getFirst()->__toString());
		$this->assertSame('-18.917167,47.535756', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=47.5323757&y=-18.9155159&z=16&source=osm&id=1040985945')->getFirst()->__toString());
		$this->assertSame('-45.870289,-67.507777', MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=-67.5159386&y=-45.8711989&z=15&source=osm&id=17164289')->getFirst()->__toString());
	}

	/**
	 * @see MapyCzServiceTest::testValidMapyCzId() exactly the same just shortened links
	 * @group request
	 */
	public function testValidMapyCzIdShortUrl(): void
	{
		$this->assertSame('50.533111,16.155906', MapyCzService::processStatic('https://en.mapy.cz/s/devevemoje')->getFirst()->__toString());
		$this->assertSame('50.084007,14.440339', MapyCzService::processStatic('https://en.mapy.cz/s/degogalazo')->getFirst()->__toString());
		$this->assertSame('50.084747,14.454012', MapyCzService::processStatic('https://en.mapy.cz/s/cavukepuba')->getFirst()->__toString());
		$this->assertSame('50.093312,14.455159', MapyCzService::processStatic('https://en.mapy.cz/s/fuvatavode')->getFirst()->__toString());
		$this->assertSame('50.106624,14.366203', MapyCzService::processStatic('https://en.mapy.cz/s/gesaperote')->getFirst()->__toString()); // area
		// some other places than Czechia (source OSM)
		$this->assertSame('49.444980,11.109055', MapyCzService::processStatic('https://en.mapy.cz/s/hozogeruvo')->getFirst()->__toString());
		// negative coordinates (source OSM)
		$this->assertSame('54.766918,-101.873729', MapyCzService::processStatic('https://en.mapy.cz/s/dasorekeja')->getFirst()->__toString());
//			$this->assertSame('-18.917167,47.535756', MapyCzServiceNew::processStatic('https://en.mapy.cz/s/maposedeso')->getFirst()->__toString());
		$this->assertSame('-18.917167,47.535756', MapyCzService::processStatic('https://en.mapy.cz/s/maposedeso')->getFirst()->__toString());
		$this->assertSame('-45.870289,-67.507777', MapyCzService::processStatic('https://en.mapy.cz/s/robelevuja')->getFirst()->__toString());
	}

	/**
	 * INVALID Place ID
	 * @group request
	 */
	public function testInvalidPlaceId(): void
	{
		$collection = MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15&source=base&id=1234')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.069524,14.450824', $collection[0]->__toString());
		$this->assertSame('Map center', $collection[0]->getSourceType());

		$collection = MapyCzService::processStatic('https://en.mapy.cz/zakladni?source=base&id=1234')->getCollection();
		$this->assertCount(0, $collection);

		// Method is using constant from local config, which can't be changed, so "fake" place ID and put some non-numeric char there which is invalid and it will run fallback to X/Y
		// @TODO refactor this to be able to run true tests
		$collection = MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15&source=base&id=2111676a')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.069524,14.450824', $collection[0]->__toString());
		$this->assertSame('Map center', $collection[0]->getSourceType());
	}

	/**
	 * INVALID Place coordinates
	 */
	public function testInvalidPlaceCoordinates1(): void
	{
		$collection = MapyCzService::processStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&source=coor&id=14.4508239,50.0695244aaa&z=15')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.069524,14.450824', $collection[0]->__toString());
		$this->assertSame('Map center', $collection[0]->getSourceType());
	}

	/**
	 * INVALID Place coordinates
	 */
	public function testInvalidPlaceCoordinates2(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Input is not valid.');
		MapyCzService::processStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.0695244aaa&z=15')->getCollection();
	}
}
