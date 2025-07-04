<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\MapyCzService;
use DJTommek\MapyCzApi\MapyCzApi;
use Tests\HttpTestClients;

final class MapyCzServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return MapyCzService::class;
	}

	protected function getShareLinks(): array
	{
		return [
			'https://mapy.cz/zakladni?y=50.087451&x=14.420671&source=coor&id=14.420671%2C50.087451',
			'https://mapy.cz/zakladni?y=50.100000&x=14.500000&source=coor&id=14.500000%2C50.100000',
			'https://mapy.cz/zakladni?y=-50.200000&x=14.600000&source=coor&id=14.600000%2C-50.200000',
			'https://mapy.cz/zakladni?y=50.300000&x=-14.700001&source=coor&id=-14.700001%2C50.300000',
			'https://mapy.cz/zakladni?y=-50.400000&x=-14.800008&source=coor&id=-14.800008%2C-50.400000',
		];
	}

	protected function getDriveLinks(): array
	{
		return [
			'https://mapy.cz/zakladni?y=50.087451&x=14.420671&source=coor&id=14.420671%2C50.087451',
			'https://mapy.cz/zakladni?y=50.100000&x=14.500000&source=coor&id=14.500000%2C50.100000',
			'https://mapy.cz/zakladni?y=-50.200000&x=14.600000&source=coor&id=14.600000%2C-50.200000',
			'https://mapy.cz/zakladni?y=50.300000&x=-14.700001&source=coor&id=-14.700001%2C50.300000',
			'https://mapy.cz/zakladni?y=-50.400000&x=-14.800008&source=coor&id=-14.800008%2C-50.400000',
		];

	}

	public function testGenerateCollectionLink(): void
	{
		$collection = new BetterLocationCollection();
		$collection->add(BetterLocation::fromLatLon(50.087451, 14.420671));
		$collection->add(BetterLocation::fromLatLon(50.3, -14.7000009));
		$this->assertSame('https://mapy.cz/zakladni?vlastni-body&uc=9hAK0xXxOKtSIx3xZH5y', MapyCzService::getShareCollectionLink($collection));
	}

	public static function isValidMapProvider(): array
	{
		return [
			[true, 'https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15'],
			[true, 'https://en.mapy.cz/zakladni?x=-14.4508239&y=50.0695244&z=15'],
			[true, 'https://en.mapy.cz/zakladni?x=14.4508239&y=-50.0695244&z=15'],
			[true, 'https://en.mapy.cz/zakladni?x=-14.4508239&y=-50.0695244&z=15'],
			[true, 'https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244'],
			[true, 'https://mapy.cz/zakladni?x=14.4508239&y=50.0695244'],
			[true, 'http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244'],
			[true, 'http://mapy.cz/textova?x=14.4508239&y=50.0695244'],
			[true, 'http://mapy.cz/zemepisna?x=14&y=50'],
			[true, 'http://mapy.cz/zakladni?x=114.4508239&y=50.0695244&'],
			[true, 'https://mapy.cz/?ma_x=15.278244&ma_y=49.691235'],
			[true, 'https://mapy.cz/?ma_x=-115.278244&ma_y=-49.691235'],
			'mapy.com default link' => [true, 'https://mapy.com/en/zakladni?x=13.8866508&y=50.0603764&z=9'],
			'mapy.com haptic maps' => [true, 'https://hapticke.mapy.cz/?x=14.81028&y=49.52817&z=15&lang=en'],

			[false, 'http://mapy.cz/'],
			[false, 'http://mapy.cz/zemepisna?xx=14.4508239&y=50.0695244'],
			[false, 'http://mapy.cz/zemepisna?y=50.0695244'],
			[false, 'http://mapy.cz/zemepisna?x=50.0695244'],
			[false, 'http://mapy.cz/zemepisna?x=14.4508.239&y=50.0695244'],
			[false, 'http://mapy.cz/zemepisna?x=14.4508239&y=50.0695.244'],
			[false, 'http://mapy.cz/zemepisna?x=14.4508239a&y=50.0695244'],
			[false, 'http://mapy.cz/zemepisna?x=14.4508239a&y=50.0695244a'],
			[false, 'http://mapy.cz/zemepisna?x=14.a4508239&y=50.a0695244'],
			[false, 'http://mapy.cz/zakladni?x=14.4508239&y=150.0695244&'],
			[false, 'http://mapy.cz/zakladni?x=14.4508239&y=250.0695244'],
			[false, 'https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.0695244aaa&z=15'],
			[false, 'https://mapy.cz/?ma_x=15.278244&ma_y=149.691235'],
			[false, 'https://mapy.cz/?ma_x=-215.278244&ma_y=49.691235'],
			[false, 'https://mapy.com/'],
		];
	}

	public static function processCoordsMapProvider(): array
	{
		return [
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244'],
			[[[50.069524, -14.450824, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=-14.4508239&y=50.0695244'],
			[[[-50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=14.4508239&y=-50.0695244'],
			[[[-50.069524, -14.450824, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=-14.4508239&y=-50.0695244'],
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://mapy.cz/zakladni?x=14.4508239&y=50.0695244'],
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244'],
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'http://mapy.cz/textova?x=14.4508239&y=50.0695244'],
			[[[50.000000, 14.000000, MapyCzService::TYPE_MAP]], 'http://mapy.cz/zemepisna?x=14&y=50'],
			'mapy.com default link' => [[[50.0603764, 13.8866508, MapyCzService::TYPE_MAP]], 'https://mapy.com/en/zakladni?x=13.8866508&y=50.0603764&z=9'],
		];
	}

	public static function isValidCoordIdProvider(): array
	{
		return [
			[true, 'https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.0695244&z=15'],
			[true, 'https://en.mapy.cz/zakladni?source=coor&id=14,50.0695244&z=15'],
			[true, 'https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50&z=15'],
			[true, 'https://en.mapy.cz/zakladni?source=coor&id=14,50'],
			[true, 'https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.0695244'],
			[true, 'https://en.mapy.cz/zakladni?source=coor&id=14.4508239,-50.0695244'],
			[true, 'https://en.mapy.cz/zakladni?source=coor&id=-14.4508239,50.0695244'],
			[true, 'https://en.mapy.cz/zakladni?source=coor&id=-14.4508239,-50.0695244'],
			'mapy.com point at coordinates' => [true, 'https://mapy.com/en/turisticka?source=coor&id=14.540299245643524%2C49.577679153649974&x=14.5411120&y=49.5770548&z=19&ovl=8'],

			[false, 'https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.0695244a'],
			[false, 'https://en.mapy.cz/zakladni?source=coor&id=14.4508239a,50.0695244'],
			[false, 'https://en.mapy.cz/zakladni?source=coor&id=14.450.8239,50.0695244'],
			[false, 'https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.06.95244'],
			[false, 'https://en.mapy.cz/zakladni?source=coor&id=14.4508239,150.0695244'],
			[false, 'https://en.mapy.cz/zakladni?source=coor&id=14.4508239,-150.0695244'],
			[false, 'https://en.mapy.cz/zakladni?source=coor&id=514.4508239,15.0695244'],
			[false, 'https://en.mapy.cz/zakladni?source=coor&id=-514.4508239,15.0695244'],
			[false, 'https://en.mapy.cz/zakladni?source=coor&id=14.4508239-50.0695244'],
			[false, 'https://en.mapy.cz/zakladni?source=coor&id=14.4508239'],
		];
	}


	public static function isValidSourcePhotoProvider(): array
	{
		return [
			[true, 'https://en.mapy.cz/fotografie?sourcep=foto&idp=3255831'],
			[true, 'https://en.mapy.cz/fotografie?x=14.4569172&y=49.2930016&z=16&q=bo%C5%BE%C3%AD%20muka&source=base&id=2273700&ds=2&sourcep=foto&idp=3255831'],
			'mapy.com image of place' => [true, 'https://mapy.com/en/zakladni?source=base&id=2137432&gallery=1&x=14.8099400&y=49.5231500&z=15'],

			[false, 'https://en.mapy.cz/fotografie?sourcep=foto'],
			[false, 'https://en.mapy.cz/fotografie?idp=3255831'],
			[false, 'https://en.mapy.cz/fotografie?sourcep=foto&idp=aabc'],
		];
	}

	public static function processSourcePhotoProvider(): array
	{
		return [
			[[[49.295782, 14.447919, MapyCzService::TYPE_PHOTO]], 'https://en.mapy.cz/fotografie?sourcep=foto&idp=3255831'],
			[[[49.295782, 14.447919, MapyCzService::TYPE_PHOTO], [49.292865, 14.466637, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/fotografie?x=14.4569172&y=49.2930016&z=16&q=bo%C5%BE%C3%AD%20muka&source=base&id=2273700&ds=2&sourcep=foto&idp=3255831'],
			[[[50.209226, 15.832547, MapyCzService::TYPE_PHOTO]], 'https://en.mapy.cz/fotografie?sourcep=foto&idp=4769603'],
			[[[50.209226, 15.832547, MapyCzService::TYPE_PHOTO], [49.295782, 14.447919, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/fotografie?x=15.8324297&y=50.2090275&z=19&q=49.295782%2C14.447919&source=coor&id=14.447919%2C49.295782&ds=1&sourcep=foto&idp=4769603'],
			'mapy.com image of place' => [[[49.5318497, 14.8126764, MapyCzService::TYPE_PHOTO], [49.5318497, 14.8126764, MapyCzService::TYPE_PLACE_ID]], 'https://mapy.com/en/zakladni?source=base&id=2137432&gallery=1&sourcep=foto&idp=1291893&x=14.8099400&y=49.5231500&z=15'],
		];
	}

	public static function processSearchCoordinateProvider(): array
	{
		return [
			[[[50.080658862378314, 14.436680347203437, MapyCzService::TYPE_SEARCH_COORDS]], 'https://mapy.cz?q=50.080658862378314%2C14.436680347203437'],
			[[[50.080658862378314, 14.436680347203437, MapyCzService::TYPE_SEARCH_COORDS]], 'https://mapy.com?q=50.080658862378314%2C14.436680347203437'],
		];
	}

	/**
	 * ID parameter is in coordinates format
	 */
	public static function processValidCoordinatesMapyCzIdProvider(): array
	{
		return [
			[[[48.777778, 14.333333, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/zemepisna?x=14.6666666666&y=48.222&z=16&source=coor&id=14.33333333333333%2C48.77777777777'],

			// @EXPERIMENTAL Process map center only if no valid location was detected (place, photo, panorama..)
			// $this->assertSame('48.222000,14.666667', $collection[1]->__toString());

			[[[48.777778, 14.333333, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/zemepisna?source=coor&id=14.33333333333333%2C48.77777777777'],

			[[[48.873288, 14.578971, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/zemepisna?x=14.5702160&y=48.8734857&z=16&source=coor&id=14.57897074520588%2C48.87328807384455'],
			[[[50.077886, 14.371990, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/zakladni?x=14.3985113&y=50.0696783&z=15&source=coor&id=14.371989590930184%2C50.07788610486586'],
			[[[49.205899, 14.257356, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/textova?x=14.2573931&y=49.2063073&z=18&source=coor&id=14.257355545997598%2C49.205899024478754'], // iQuest textova
			[[[7.731071, -80.551001, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/zakladni?x=-80.5308310&y=7.7192491&z=15&source=coor&id=-80.55100118168951%2C7.731071318967728'],
			[[[65.608884, -168.088871, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/zakladni?x=-168.0916515&y=65.6066015&z=15&source=coor&id=-168.08887063564356%2C65.60888429109842'],
			[[[-1.138011, 9.034823, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/zakladni?x=8.9726814&y=-1.2094073&z=11&source=coor&id=9.034822831066833%2C-1.1380111329277875'],
			'mapy.com point at coordinates' => [[[49.5776792, 14.5402992, MapyCzService::TYPE_PLACE_COORDS]], 'https://mapy.com/en/turisticka?source=coor&id=14.540299245643524%2C49.577679153649974&x=14.5411120&y=49.5770548&z=19&ovl=8'],
		];
	}

	public static function processMapyCzXYProvider(): array
	{
		return [
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15'],
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?y=50.0695244&x=14.4508239&z=15'],
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://mapy.com/zakladni?y=50.0695244&x=14.4508239&z=15'],

			[[[49.691235, 15.278244, MapyCzService::TYPE_MAP_V2]], 'https://mapy.cz/?ma_x=15.278244&ma_y=49.691235'],
			[[[49.691235, 15.278244, MapyCzService::TYPE_MAP_V2]], 'https://mapy.com/?ma_x=15.278244&ma_y=49.691235'],
		];
	}

	/**
	 * ID parameter is in coordinates format
	 */
	public static function processValidCoordinatesMapyCzIdShortProvider(): array
	{
		return [
			[[[48.873288, 14.578971, MapyCzService::TYPE_PLACE_COORDS]], 'https://mapy.cz/s/cekahebefu'],
			[[[48.873288, 14.578971, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/s/gacegelola'], // same as above just generated later by different user and on different IP and PC
			[[[50.077886, 14.371990, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/s/bosafonabo'],
			[[[7.731071, -80.551001, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/s/godumokefu'],
			[[[65.608884, -168.088871, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/s/nopovehuhu'],
			[[[-1.138011, 9.034823, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/s/lozohefobu'],
			[[[-1.138011, 9.034823, MapyCzService::TYPE_PLACE_COORDS]], 'https://mapy.com/s/judebusepu'],
		];
	}

	/**
	 * Coordinates in ID parameter are INVALID
	 */
	public static function processInvalidLatCoordinatesMapyCzIdProvider(): array
	{
		return [
			// Latitude coordinate must be between or equal from -90 to 90 degrees.
			[[[50.069678, 14.398511, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=14.3985113&y=50.0696783&z=15&source=coor&id=14.371989590930184%2C150.07788610486586'],

			// Longitude coordinate must be between or equal from -180 to 180 degrees.
			[[[50.069678, 14.398511, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=14.3985113&y=50.0696783&z=15&source=coor&id=190.371989590930184%2C50.07788610486586'],
		];
	}

	/**
	 * Translate MapyCZ panorama ID to coordinates
	 */
	public static function processValidMapyCzPanoramaIdProvider(): array
	{
		return [
			[[[50.075959, 15.016772, MapyCzService::TYPE_PANORAMA]], 'https://en.mapy.cz/zakladni?x=15.0162139&y=50.0820182&z=16&pano=1&pid=68059377&yaw=5.522&fov=1.257&pitch=0.101'],
			[ // Viribus Unitis 2019
				[
					[50.123351, 16.284569, MapyCzService::TYPE_PANORAMA],
					[50.1321314, 16.3137672, MapyCzService::TYPE_PLACE_ID],
				],
				'https://en.mapy.cz/turisticka?x=16.2845693&y=50.1233926&z=17&pano=1&source=base&id=2107710&pid=66437731&yaw=6.051&fov=1.257&pitch=0.157',
			],
			[ // Three different locations: map, place and panorama
				[
					[50.094953, 15.023081, MapyCzService::TYPE_PANORAMA],
					[50.090464828, 15.0305530363, MapyCzService::TYPE_PLACE_ID],
				],
				'https://en.mapy.cz/zakladni?x=15.0483153&y=50.1142203&z=15&pano=1&source=firm&id=216358&pid=68007689&yaw=3.985&fov=1.257&pitch=0.033',
			],
			// First neighbour of this panorama ID don't have original neighbour, so coordinates are little off (Original test using "get neighbour of neighbour" result was '50.078499,14.488475')
			[[[50.078496, 14.488369, MapyCzService::TYPE_PANORAMA]], 'https://en.mapy.cz/zakladni?x=14.4883693&y=50.0784958&z=15&pano=1&pid=70254688&yaw=0.424&fov=1.257&pitch=0.088'],
			'mapy.com streeetview' => [
				[
					[49.5778687, 14.5403717, MapyCzService::TYPE_PANORAMA],
					[49.5779597505, 14.5404130797, MapyCzService::TYPE_PLACE_ID]
				],
				'https://mapy.com/en/turisticka?source=base&id=1920943&pid=92682479&newest=1&yaw=0.233&fov=0.234&pitch=0.152&x=14.5403717&y=49.5778687&z=19&ovl=8'
			],
		];
	}

	/**
	 * Translate MapyCZ INVALID place ID to coordinates
	 */
	public static function processInvalidPanoramaIdProvider(): array
	{
		return [
			[[[50.082018, 15.016214, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=15.0162139&y=50.0820182&z=16&pano=1&pid=99999999999&yaw=5.522&fov=1.257&pitch=0.101'],
			[[], 'https://en.mapy.cz/zakladni?pano=1&pid=99999999999&yaw=5.522&fov=1.257&pitch=0.101'],
		];
	}

	/**
	 * Translate MapyCZ place ID to coordinates
	 */
	public static function processValidMapyCzIdProvider(): array
	{
		return [
			[[[50.073784, 14.422105, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15&source=base&id=2111676'],
			[[[50.084007, 14.440339, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/zakladni?x=14.4527551&y=50.0750056&z=15&source=pubt&id=15308193'],
			[[[50.084748, 14.454010, MapyCzService::TYPE_PLACE_ID]], 'https://mapy.cz/zakladni?x=14.4651576&y=50.0796325&z=15&source=firm&id=468797'],
			[[[49.993611, 14.205278, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/fotografie?x=14.2029782&y=49.9929235&z=17&source=foto&id=1080344'],
			[[[50.106624, 14.366203, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/zakladni?x=14.3596717&y=50.0997874&z=15&source=base&id=1833337'], // area
			// some other places than Czechia (source OSM)
			[[[49.444980, 11.109055, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/zakladni?x=11.0924687&y=49.4448356&z=15&source=osm&id=112448327'],
			// negative coordinates (source OSM)
			[[[54.766918, -101.873729, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/zakladni?x=-101.8754373&y=54.7693842&z=15&source=osm&id=1000536418'],
			[[[-18.917167, 47.535756, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/zakladni?x=47.5323757&y=-18.9155159&z=16&source=osm&id=1040985945'],
			[[[-45.870289, -67.507777, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/zakladni?x=-67.5159386&y=-45.8711989&z=15&source=osm&id=17164289'],
			'mapy.com point at place' => [[[49.5318497, 14.8126764, MapyCzService::TYPE_PLACE_ID]], 'https://mapy.com/en/zakladni?source=base&id=2137432&x=14.8099400&y=49.5231500&z=15'],
		];
	}

	/**
	 * @see MapyCzServiceTest::processValidMapyCzIdProvider() exactly the same just shortened links
	 */
	public static function processValidMapyCzIdShortUrlProvider(): array
	{
		return [
			[[[50.533111, 16.155906, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/devevemoje'],
			[[[50.084007, 14.440339, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/degogalazo'],
			[[[50.084748, 14.454010, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/cavukepuba'],
			[[[50.106624, 14.366203, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/gesaperote'], // area
			// some other places than Czechia (source OSM)
			[[[49.444980, 11.109055, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/hozogeruvo'],
			// negative coordinates (source OSM)
			[[[54.766918, -101.873729, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/dasorekeja'],
			//			$this->assertSame('-18.917167,47.535756', MapyCzServiceNew::processStatic('https://en.mapy.cz/s/maposedeso')->getFirst()->__toString());
			[[[-18.917167, 47.535756, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/maposedeso'],
			[[[-45.870289, -67.507777, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/robelevuja'],
			'mapy.com point at place' => [[[49.5318497, 14.8126764, MapyCzService::TYPE_PLACE_ID]], 'https://mapy.com/s/casapototu'],
		];
	}

	public static function isValidMapyCzCustomPointsUrlProvider(): array
	{
		return [
			// shortest custom points url possible
			[true, 'https://en.mapy.cz/turisticka?vlastni-body&uc=9fJgGxW.Hq'],
			[true, 'https://mapy.cz/letecka?vlastni-body&uc=9fJgGxW.Hq'],

			[true, 'https://en.mapy.cz/turisticka?vlastni-body&x=13.9183152&y=49.9501554&z=11&ut=New%20%20POI&ut=New%20%20POI&ut=New%20%20POI&ut=New%20%20POI&uc=9fJgGxW.HqkQ0xWn3F9fWDGxX0wGlQ0xW9oq&ud=49%C2%B055%2710.378%22N%2C%2013%C2%B046%2749.078%22E&ud=13%C2%B048%2734.135%22E%2049%C2%B052%2746.280%22N&ud=Broumy%2C%20Beroun&ud=B%C5%99ezov%C3%A1%2C%20Beroun'],

			// valid according validator, but invalid according parser
			[true, 'https://en.mapy.cz/turisticka?vlastni-body&uc=aa'],

			[false, 'https://en.mapy.cz/turisticka?aaaa&uc=9fJgGxW.Hq'],
			[false, 'https://en.mapy.cz/turisticka?vlastni-body&uc='],
		];
	}

	public static function isValidSearchCoordinateProvider(): array
	{
		return [
			[true, 'https://mapy.cz?q=50.0806%2C14.4366'],
			[true, 'https://mapy.cz?q=-50.0806%2C14.4366'],
			[true, 'https://mapy.cz?q=50.0806%2C-14.4366'],
			[true, 'https://mapy.cz?q=50.0806%2C114.4366'],
			[true, 'https://mapy.com?q=50.0806%2C114.4366'],

			[false, 'https://mapy.cz?q=hello%2C14.4366'],
			[false, 'https://mapy.cz?q=50.0806%2Cworld'],
			[false, 'https://mapy.cz?q=150.0806%2C14.4366'],
			[false, 'https://mapy.cz?q=someSearchString'],
			[false, 'https://mapy.com?q=someSearchString'],
		];
	}

	public static function processValidMapyCzCustomPointsUrlProvider(): array
	{
		return [
			// shortest custom points url possible
			[[[49.919550, 13.780299, MapyCzService::TYPE_CUSTOM_POINT]], 'https://mapy.cz/letecka?vlastni-body&uc=9fJgGxW.Hq'],

			[
				[
					[49.919550, 13.780299, MapyCzService::TYPE_CUSTOM_POINT],
					[49.879522, 13.809482, MapyCzService::TYPE_CUSTOM_POINT],
					[49.924412, 13.859607, MapyCzService::TYPE_CUSTOM_POINT],
					[49.902083, 13.894283, MapyCzService::TYPE_CUSTOM_POINT],
				],
				'https://en.mapy.cz/turisticka?vlastni-body&x=13.9183152&y=49.9501554&z=11&ut=New%20%20POI&ut=New%20%20POI&ut=New%20%20POI&ut=New%20%20POI&uc=9fJgGxW.HqkQ0xWn3F9fWDGxX0wGlQ0xW9oq&ud=49%C2%B055%2710.378%22N%2C%2013%C2%B046%2749.078%22E&ud=13%C2%B048%2734.135%22E%2049%C2%B052%2746.280%22N&ud=Broumy%2C%20Beroun&ud=B%C5%99ezov%C3%A1%2C%20Beroun',
			],

			[
				[
					[49.919550, 13.780299, MapyCzService::TYPE_CUSTOM_POINT],
					[0.270943, -70.173100, MapyCzService::TYPE_CUSTOM_POINT],
				],
				'https://en.mapy.cz/letecka?vlastni-body&x=-76.8527877&y=20.8861373&z=4&ut=New%20%20POI&ut=New%20%20POI&uc=9fJgGxW.Hqq9U8G9AbhW&ud=49%C2%B055%2710.378%22N%2C%2013%C2%B046%2749.078%22E&ud=Kolumbie',
			],

			[
				[
					[49.919550, 13.780299, MapyCzService::TYPE_CUSTOM_POINT],
					[0.270943, -70.173100, MapyCzService::TYPE_CUSTOM_POINT],
				],
				'https://mapy.com/letecka?vlastni-body&x=-76.8527877&y=20.8861373&z=4&ut=New%20%20POI&ut=New%20%20POI&uc=9fJgGxW.Hqq9U8G9AbhW&ud=49%C2%B055%2710.378%22N%2C%2013%C2%B046%2749.078%22E&ud=Kolumbie',
			],

			// Invalid encoded coordinates
			[[], 'https://en.mapy.cz/turisticka?vlastni-body&uc=1'],
			[[], 'https://mapy.com/turisticka?vlastni-body&uc=1'],
		];
	}

	/**
	 * INVALID Place ID
	 */
	public static function processInvalidPlaceIdProvider(): array
	{
		return [
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15&source=base&id=1234'],

			[[], 'https://en.mapy.cz/zakladni?source=base&id=1234'],
			[[], 'https://mapy.com/zakladni?source=base&id=1234'],

			// Method is using constant from local config, which can't be changed, so "fake" place ID and put some non-numeric char there which is invalid and it will run fallback to X/Y
			// @TODO refactor this to be able to run true tests
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15&source=base&id=2111676a'],
		];
	}

	/**
	 * INVALID Place coordinates
	 */
	public static function processInvalidPlaceCoordinatesProvider(): array
	{
		return [
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&source=coor&id=14.4508239,50.0695244aaa&z=15'],
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://mapy.com/zakladni?x=14.4508239&y=50.0695244&source=coor&id=14.4508239,50.0695244aaa&z=15'],
		];
	}

	/**
	 * @dataProvider isValidMapProvider
	 * @dataProvider isValidCoordIdProvider
	 * @dataProvider isValidSourcePhotoProvider
	 * @dataProvider isValidMapyCzCustomPointsUrlProvider
	 * @dataProvider isValidSearchCoordinateProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$mapyCzApi = (new MapyCzApi())->setClient($this->httpTestClients->mockedHttpClient);
		$service = new MapyCzService($this->httpTestClients->mockedRequestor, $mapyCzApi);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * No API requests
	 * No short URLs
	 *
	 * @dataProvider processValidCoordinatesMapyCzIdProvider
	 * @dataProvider processMapyCzXYProvider
	 * @dataProvider processInvalidLatCoordinatesMapyCzIdProvider
	 * @dataProvider processValidMapyCzCustomPointsUrlProvider
	 * @dataProvider processInvalidPlaceCoordinatesProvider
	 * @dataProvider processCoordsMapProvider
	 * @dataProvider processSearchCoordinateProvider
	 */
	public function testProcessNoApiRequestsNoShortUrl(array $expectedResults, string $input): void
	{
		$mapyCzApi = (new MapyCzApi())->setClient($this->httpTestClients->mockedHttpClient);
		$service = new MapyCzService($this->httpTestClients->mockedRequestor, $mapyCzApi);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * Real API requests
	 * No short URLs requests
	 *
	 * @group request
	 *
	 * @dataProvider processSourcePhotoProvider
	 * @dataProvider processValidMapyCzPanoramaIdProvider
	 * @dataProvider processInvalidPanoramaIdProvider
	 * @dataProvider processValidMapyCzIdProvider
	 * @dataProvider processInvalidPlaceIdProvider
	 */
	public function testProcessRealApiRequestsNoShortUrl(array $expectedResults, string $input): void
	{
		$mapyCzApi = (new MapyCzApi())->setClient($this->httpTestClients->realHttpClient);
		$service = new MapyCzService($this->httpTestClients->mockedRequestor, $mapyCzApi);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * Offline API requests
	 * No short URLs requests
	 *
	 * @dataProvider processSourcePhotoProvider
	 * @dataProvider processValidMapyCzPanoramaIdProvider
	 * @dataProvider processInvalidPanoramaIdProvider
	 * @dataProvider processValidMapyCzIdProvider
	 * @dataProvider processInvalidPlaceIdProvider
	 */
	public function testProcessOfflineApiRequestsNoShortUrl(array $expectedResults, string $input): void
	{
		$mapyCzApi = (new MapyCzApi())->setClient($this->httpTestClients->offlineHttpClient);
		$service = new MapyCzService($this->httpTestClients->mockedRequestor, $mapyCzApi);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * No API requests
	 * Real short URLs requests
	 *
	 * @group request
	 *
	 * @dataProvider processValidCoordinatesMapyCzIdShortProvider
	 */
	public function testProcessNoApiRequestsRealShortUrl(array $expectedResults, string $input): void
	{
		$mapyCzApi = (new MapyCzApi())->setClient($this->httpTestClients->mockedHttpClient);
		$service = new MapyCzService($this->httpTestClients->realRequestor, $mapyCzApi);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * No API requests
	 * Offline short URLs requests
	 *
	 * @group request
	 *
	 * @dataProvider processValidCoordinatesMapyCzIdShortProvider
	 */
	public function testProcessNoApiRequestsOfflineShortUrl(array $expectedResults, string $input): void
	{
		$mapyCzApi = (new MapyCzApi())->setClient($this->httpTestClients->mockedHttpClient);
		$service = new MapyCzService($this->httpTestClients->offlineRequestor, $mapyCzApi);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * Real API requests
	 * Real short URLs
	 *
	 * @group request
	 *
	 * @dataProvider processValidMapyCzIdShortUrlProvider
	 */
	public function testProcessRealApiRequestsShortUrl(array $expectedResults, string $input): void
	{
		$mapyCzApi = (new MapyCzApi())->setClient($this->httpTestClients->realHttpClient);
		$service = new MapyCzService($this->httpTestClients->realRequestor, $mapyCzApi);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * Offline API requests
	 * Offline short URLs
	 *
	 * @dataProvider processValidMapyCzIdShortUrlProvider
	 */
	public function testProcessOfflineApiRequestsShortUrl(array $expectedResults, string $input): void
	{
		$mapyCzApi = (new MapyCzApi())->setClient($this->httpTestClients->offlineHttpClient);
		$service = new MapyCzService($this->httpTestClients->offlineRequestor, $mapyCzApi);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}
}
