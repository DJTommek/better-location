<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\MapyCzService;

final class MapyCzServiceTest extends AbstractServiceTestCase
{
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

			[false, 'http://mapy.cz/zemepisna?xx=14.4508239&y=50.0695244'],
			[false, 'http://mapy.cz/zemepisna?y=50.0695244'],
			[false, 'http://mapy.cz/zemepisna?x=50.0695244'],
			[false, 'http://mapy.cz/zemepisna?x=14.4508.239&y=50.0695244'],
			[false, 'http://mapy.cz/zemepisna?x=14.4508239&y=50.0695.244'],
			[false, 'http://mapy.cz/zemepisna?x=14.4508239a&y=50.0695244'],
			[false, 'http://mapy.cz/zemepisna?x=14.4508239a&y=50.0695244a'],
			[false, 'http://mapy.cz/zemepisna?x=14.a4508239&y=50.a0695244'],
			[false, 'http://mapy.cz/zakladni?x=114.4508239&y=50.0695244&'],
			[false, 'http://mapy.cz/zakladni?x=14.4508239&y=250.0695244'],
			[false, 'https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.0695244aaa&z=15'],
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

			[false, 'https://en.mapy.cz/fotografie?sourcep=foto'],
			[false, 'https://en.mapy.cz/fotografie?idp=3255831'],
			[false, 'https://en.mapy.cz/fotografie?sourcep=foto&idp=aabc'],
		];
	}

	/**
	 * @group request
	 */
	public function processSourcePhotoProvider(): array
	{
		return [
			[[[49.295782, 14.447919, MapyCzService::TYPE_PHOTO]], 'https://en.mapy.cz/fotografie?sourcep=foto&idp=3255831'],
			[[[49.295782, 14.447919, MapyCzService::TYPE_PHOTO], [49.292865, 14.466637, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/fotografie?x=14.4569172&y=49.2930016&z=16&q=bo%C5%BE%C3%AD%20muka&source=base&id=2273700&ds=2&sourcep=foto&idp=3255831'],
			[[[50.209226, 15.832547, MapyCzService::TYPE_PHOTO]], 'https://en.mapy.cz/fotografie?sourcep=foto&idp=4769603'],
			[[[50.209226, 15.832547, MapyCzService::TYPE_PHOTO], [49.295782, 14.447919, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/fotografie?x=15.8324297&y=50.2090275&z=19&q=49.295782%2C14.447919&source=coor&id=14.447919%2C49.295782&ds=1&sourcep=foto&idp=4769603'],
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
		];
	}

	public static function processMapyCzXYProvider(): array
	{
		return [
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15'],
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?y=50.0695244&x=14.4508239&z=15'],
		];
	}

	/**
	 * ID parameter is in coordinates format
	 */
	public function processValidCoordinatesMapyCzIdShortProvider(): array
	{
		return [
			[[[48.873288, 14.578971, MapyCzService::TYPE_PLACE_COORDS]], 'https://mapy.cz/s/cekahebefu'],
			[[[48.873288, 14.578971, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/s/gacegelola'], // same as above just generated later by different user and on different IP and PC
			[[[50.077886, 14.371990, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/s/bosafonabo'],
			[[[7.731071, -80.551001, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/s/godumokefu'],
			[[[65.608884, -168.088871, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/s/nopovehuhu'],
			[[[-1.138011, 9.034823, MapyCzService::TYPE_PLACE_COORDS]], 'https://en.mapy.cz/s/lozohefobu'],
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
	 *
	 * @group request
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
		];
	}

	/**
	 * Translate MapyCZ INVALID place ID to coordinates
	 *
	 * @group request
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
	 *
	 * @group request
	 */
	public static function processValidMapyCzIdProvider(): array
	{
		return [
			[[[50.073784, 14.422105, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15&source=base&id=2111676'],
			[[[50.084007, 14.440339, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/zakladni?x=14.4527551&y=50.0750056&z=15&source=pubt&id=15308193'],
			[[[50.084748, 14.454012, MapyCzService::TYPE_PLACE_ID]], 'https://mapy.cz/zakladni?x=14.4651576&y=50.0796325&z=15&source=firm&id=468797'],
			[[[49.993611, 14.205278, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/fotografie?x=14.2029782&y=49.9929235&z=17&source=foto&id=1080344'],
			[[[50.106624, 14.366203, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/zakladni?x=14.3596717&y=50.0997874&z=15&source=base&id=1833337'], // area
			// some other places than Czechia (source OSM)
			[[[49.444980, 11.109055, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/zakladni?x=11.0924687&y=49.4448356&z=15&source=osm&id=112448327'],
			// negative coordinates (source OSM)
			[[[54.766918, -101.873729, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/zakladni?x=-101.8754373&y=54.7693842&z=15&source=osm&id=1000536418'],
			[[[-18.917167, 47.535756, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/zakladni?x=47.5323757&y=-18.9155159&z=16&source=osm&id=1040985945'],
			[[[-45.870289, -67.507777, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/zakladni?x=-67.5159386&y=-45.8711989&z=15&source=osm&id=17164289'],
		];
	}

	/**
	 * @see MapyCzServiceTest::processValidMapyCzIdProvider() exactly the same just shortened links
	 * @group request
	 */
	public static function processValidMapyCzIdShortUrlProvider(): array
	{
		return [
			[[[50.533111, 16.155906, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/devevemoje'],
			[[[50.084007, 14.440339, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/degogalazo'],
			[[[50.084748, 14.454012, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/cavukepuba'],
			[[[50.106624, 14.366203, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/gesaperote'], // area
			// some other places than Czechia (source OSM)
			[[[49.444980, 11.109055, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/hozogeruvo'],
			// negative coordinates (source OSM)
			[[[54.766918, -101.873729, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/dasorekeja'],
			//			$this->assertSame('-18.917167,47.535756', MapyCzServiceNew::processStatic('https://en.mapy.cz/s/maposedeso')->getFirst()->__toString());
			[[[-18.917167, 47.535756, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/maposedeso'],
			[[[-45.870289, -67.507777, MapyCzService::TYPE_PLACE_ID]], 'https://en.mapy.cz/s/robelevuja'],
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

	public function processValidMapyCzCustomPointsUrlProvider(): array
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

			// Invalid encoded coordinates
			[[], 'https://en.mapy.cz/turisticka?vlastni-body&uc=1'],
		];
	}

	/**
	 * INVALID Place ID
	 *
	 * @group request
	 */
	public function processInvalidPlaceIdProvider(): array
	{
		return [
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15&source=base&id=1234'],

			[[], 'https://en.mapy.cz/zakladni?source=base&id=1234'],

			// Method is using constant from local config, which can't be changed, so "fake" place ID and put some non-numeric char there which is invalid and it will run fallback to X/Y
			// @TODO refactor this to be able to run true tests
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15&source=base&id=2111676a'],
		];
	}

	/**
	 * INVALID Place coordinates
	 */
	public function processInvalidPlaceCoordinates1Provider(): array
	{
		return [
			[[[50.069524, 14.450824, MapyCzService::TYPE_MAP]], 'https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&source=coor&id=14.4508239,50.0695244aaa&z=15'],
		];
	}

	/**
	 * @dataProvider isValidMapProvider
	 * @dataProvider isValidCoordIdProvider
	 * @dataProvider isValidSourcePhotoProvider
	 * @dataProvider isValidMapyCzCustomPointsUrlProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new MapyCzService();
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processCoordsMapProvider
	 * @dataProvider processSourcePhotoProvider
	 * @dataProvider processValidCoordinatesMapyCzIdProvider
	 * @dataProvider processMapyCzXYProvider
	 * @dataProvider processValidCoordinatesMapyCzIdShortProvider
	 * @dataProvider processInvalidLatCoordinatesMapyCzIdProvider
	 * @dataProvider processValidMapyCzPanoramaIdProvider
	 * @dataProvider processInvalidPanoramaIdProvider
	 * @dataProvider processValidMapyCzIdProvider
	 * @dataProvider processValidMapyCzIdShortUrlProvider
	 * @dataProvider processValidMapyCzCustomPointsUrlProvider
	 * @dataProvider processInvalidPlaceIdProvider
	 * @dataProvider processInvalidPlaceCoordinates1Provider
	 */
	public function testProcess(array $expectedResults, string $input): void
	{
		$service = new MapyCzService();
		$this->assertServiceLocations($service, $input, $expectedResults);
	}
}
