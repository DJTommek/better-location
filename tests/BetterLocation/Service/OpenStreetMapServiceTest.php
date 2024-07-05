<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\OpenStreetMapService;
use Tests\HttpTestClients;

final class OpenStreetMapServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return OpenStreetMapService::class;
	}

	protected function getShareLinks(): array
	{
		return [
			'https://www.openstreetmap.org/search?whereami=1&query=50.087451,14.420671&mlat=50.087451&mlon=14.420671#map=17/50.087451/14.420671',
			'https://www.openstreetmap.org/search?whereami=1&query=50.100000,14.500000&mlat=50.100000&mlon=14.500000#map=17/50.100000/14.500000',
			'https://www.openstreetmap.org/search?whereami=1&query=-50.200000,14.600000&mlat=-50.200000&mlon=14.600000#map=17/-50.200000/14.600000', // round down
			'https://www.openstreetmap.org/search?whereami=1&query=50.300000,-14.700001&mlat=50.300000&mlon=-14.700001#map=17/50.300000/-14.700001', // round up
			'https://www.openstreetmap.org/search?whereami=1&query=-50.400000,-14.800008&mlat=-50.400000&mlon=-14.800008#map=17/-50.400000/-14.800008',
		];
	}

	protected function getDriveLinks(): array
	{
		$this->revalidateGeneratedDriveLink = false;
		return [
			'https://www.openstreetmap.org/directions?from=&to=50.087451,14.420671',
			'https://www.openstreetmap.org/directions?from=&to=50.100000,14.500000',
			'https://www.openstreetmap.org/directions?from=&to=-50.200000,14.600000', // round down
			'https://www.openstreetmap.org/directions?from=&to=50.300000,-14.700001', // round up
			'https://www.openstreetmap.org/directions?from=&to=-50.400000,-14.800008',
		];
	}

	public static function isValidNormalUrlProvider(): array
	{
		return [
			[true, 'https://www.openstreetmap.org/#map=17/49.355164/14.272819'],
			[true, 'https://openstreetmap.org/#map=17/49.355164/14.272819'],
			[true, 'http://openstreetmap.org/#map=17/49.355164/14.272819'],
			[true, 'https://www.OPENstreetmap.org/#map=17/49.32085/14.16402&layers=N'],
			[true, 'https://www.openstreetmap.org/#map=18/50.05215/14.45283'],
			[true, 'https://www.openstreetmap.org/?mlat=50.05215&mlon=14.45283#map=18/50.05215/14.45283'],
			[true, 'https://www.openstreetmap.org/?mlat=50.05328&mlon=14.45640#map=18/50.05328/14.45640'],
			[true, 'https://www.openstreetmap.org/#map=15/-34.6101/-58.3641'],
			[true, 'https://www.openstreetmap.org/?mlat=-36.9837&mlon=174.8765#map=15/-36.9837/174.8765&layers=N'],

			[true, 'https://osm.org/#map=17/49.355164/14.272819'],
			[true, 'https://osm.org/?mlat=-36.9837&mlon=174.8765#map=15/-36.9837/174.8765&layers=N'],
			[true, 'https://www.openstreetmap.org/?mlat=-30.12345&mlon=170.55555#map=15/-36.9837/174.8765&layers=N'],

			[false, 'https://osmm.org/#map=17/49.355164/14.272819'], // invalid domain
			[false, 'https://osm.org/#map=17/149.355164/14.272819'], // invalid lat
			[false, 'https://osm.org/#map=17/49.355164/514.272819'], // invalid lon
			[false, 'https://osm.org/#map=17/49.355164'], // missing lon
		];
	}

	public static function isValidNoteUrlProvider(): array
	{
		return [
			[true, 'https://www.openstreetmap.org/note/3480481'],
			[true, 'https://www.openstreetmap.org/note/3480481/'],
			[true, 'https://openstreetmap.org/note/3480481'],

			[false, 'https://www.openstreetmap.org/note/3480481a'],
			[false, 'https://www.openstreetmap.org/note/3480481/something'],
			[false, 'https://www.openstreetmap.org/note'],
			[false, 'https://www.openstreetmap.org/note/'],
			[false, 'https://www.openstreetmap.org/note/aa'],
		];
	}

	public static function isValidGoUrlProvider(): array
	{
		return [
			[true, 'https://osm.org/go/0J0kf83sQ--?m='],
			[true, 'http://osm.org/go/0EEQjE=='],
			[true, 'https://OSM.org/go/0EEQjEEb'],
			[true, 'https://osm.org/go/0J0kf3lAU--'],
			[true, 'https://osm.org/go/0J0kf3lAU--?m='],
			[true, 'https://osm.org/go/Mnx6vllJ--'],
			[true, 'https://www.osm.org/go/uuU2nmSl--?layers=N&m='],

			[true, 'https://openstreetmap.org/go/0J0kf83sQ--?m='],
			[true, 'https://openstreetmap.org/go/uuU2nmSl--?layers=N&m='],

			[false, 'https://openstreetmapp.org/go/uuU2nmSl--?layers=N&m='], // invalid domain
			[false, 'https://openstreetmap.org/goo/uuU2nmSl--?layers=N&m='], // invalid path
		];
	}

	public static function processNormalUrlProvider(): array
	{
		return [
			[[[49.355164, 14.272819, OpenStreetMapService::TYPE_MAP]], 'https://www.openstreetmap.org/#map=17/49.355164/14.272819'],
			[[[49.320850, 14.164020, OpenStreetMapService::TYPE_MAP]], 'https://www.openstreetmap.org/#map=17/49.32085/14.16402&layers=N'],
			[[[50.052150, 14.452830, OpenStreetMapService::TYPE_MAP]], 'https://www.openstreetmap.org/#map=18/50.05215/14.45283'],
			[[[50.052150, 14.452830, OpenStreetMapService::TYPE_POINT], [50.052150, 14.452830, OpenStreetMapService::TYPE_MAP]], 'https://www.openstreetmap.org/?mlat=50.05215&mlon=14.45283#map=18/50.05215/14.45283'],

			[[[50.053280, 14.456400, OpenStreetMapService::TYPE_POINT], [50.053280, 14.456400, OpenStreetMapService::TYPE_MAP]], 'https://www.openstreetmap.org/?mlat=50.05328&mlon=14.45640#map=18/50.05328/14.45640'],
			[[[-34.610100, -58.364100, OpenStreetMapService::TYPE_MAP]], 'https://www.openstreetmap.org/#map=15/-34.6101/-58.3641'],
			[[[-36.983700, 174.876500, OpenStreetMapService::TYPE_POINT], [-36.983700, 174.876500, OpenStreetMapService::TYPE_MAP]], 'https://www.openstreetmap.org/?mlat=-36.9837&mlon=174.8765#map=15/-36.9837/174.8765&layers=N'],
			[[[-30.12345, 170.55555, OpenStreetMapService::TYPE_POINT], [-36.983700, 174.876500, OpenStreetMapService::TYPE_MAP]], 'https://www.openstreetmap.org/?mlat=-30.12345&mlon=170.55555#map=15/-36.9837/174.8765&layers=N'],
		];
	}

	public static function processShortUrlProvider(): array
	{
		return [
			// https://www.openstreetmap.org/?mlat=50.05296528339386&mlon=14.45624828338623#map=18/50.05296528339386/14.45624828338623
			[[[50.052965, 14.456248, OpenStreetMapService::TYPE_POINT], [50.052965, 14.456248, OpenStreetMapService::TYPE_MAP]], 'https://osm.org/go/0J0kf83sQ--?m='],
			// https://www.openstreetmap.org/#map=9/51.510772705078125/0.054931640625
			[[[51.510773, 0.054932, OpenStreetMapService::TYPE_MAP]], 'https://osm.org/go/0EEQjE=='],
			// https://www.openstreetmap.org/#map=16/51.510998010635376/0.05499601364135742
			[[[51.510998, 0.054996, OpenStreetMapService::TYPE_MAP]], 'https://osm.org/go/0EEQjEEb'],
			// https://www.openstreetmap.org/#map=18/50.05328983068466/14.454574584960938
			[[[50.053290, 14.454575, OpenStreetMapService::TYPE_MAP]], 'https://osm.org/go/0J0kf3lAU--'],
			// https://www.openstreetmap.org/?mlat=50.05328983068466&mlon=14.454574584960938#map=18/50.05328983068466/14.454574584960938
			[[[50.053290, 14.454575, OpenStreetMapService::TYPE_POINT], [50.053290, 14.454575, OpenStreetMapService::TYPE_MAP]], 'https://osm.org/go/0J0kf3lAU--?m='],
			// https://www.openstreetmap.org/#map=15/-34.61009860038757/-58.36413860321045
			[[[-34.610099, -58.364139, OpenStreetMapService::TYPE_MAP]], 'https://osm.org/go/Mnx6vllJ--'],
			// https://www.openstreetmap.org/?mlat=-36.98372483253479&mlon=174.87650871276855#map=15/-36.98372483253479/174.87650871276855&layers=N
			[[[-36.983725, 174.876509, OpenStreetMapService::TYPE_POINT], [-36.983725, 174.876509, OpenStreetMapService::TYPE_MAP]], 'https://osm.org/go/uuU2nmSl--?layers=N&m='],
			// https://www.openstreetmap.org/?mlat=50.05296528339386&mlon=14.45624828338623#map=18/50.05296528339386/14.45624828338623
			[[[50.052965, 14.456248, OpenStreetMapService::TYPE_POINT], [50.052965, 14.456248, OpenStreetMapService::TYPE_MAP]], 'https://openstreetmap.org/go/0J0kf83sQ--?m='],
			// https://www.openstreetmap.org/?mlat=-30.12345&mlon=170.55555#map=15/-36.9837/174.8765&layers=N
			[[[-36.983692, 174.871144, OpenStreetMapService::TYPE_MAP]], 'https://osm.org/go/uuU2nGYc--?layers=N'],
		];
	}

	public static function processNoteUrlProvider(): array
	{
		return [
			[[[-36.9826866, 174.8747769, OpenStreetMapService::TYPE_NOTE]], 'https://www.openstreetmap.org/note/3480481/'],
			[
				[
					[50.1075434, 14.2669984, OpenStreetMapService::TYPE_NOTE],
					[50.10461, 14.26674, OpenStreetMapService::TYPE_MAP],
				],
				'https://www.openstreetmap.org/note/3324337#map=17/50.10461/14.26674&layers=N',
			],
			[[], 'https://www.openstreetmap.org/note/999999999999'],
		];
	}

	public static function processNoteShortUrlProvider(): array
	{
		return [
			[[[-36.9826866, 174.8747769, OpenStreetMapService::TYPE_NOTE]], 'https://osm.org/go/uuU2nQAN--?layers=N&note=3480481'],
			[
				[
					[50.1075434, 14.2669984, OpenStreetMapService::TYPE_NOTE],
					// [50.10461, 14.26674, OpenStreetMapService::TYPE_MAP], // @TODO info about map is for some reason lost but it is working via browser
				],
				'https://osm.org/go/0J0YJOoVR-?layers=N&note=3324337',
			],
		];
	}

	/**
	 * @dataProvider isValidNormalUrlProvider
	 * @dataProvider isValidGoUrlProvider
	 * @dataProvider isValidNoteUrlProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new OpenStreetMapService($this->httpTestClients->mockedRequestor);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @dataProvider processNormalUrlProvider
	 */
	public function testProcessNoRequest(array $expectedResults, string $input): void
	{
		$service = new OpenStreetMapService($this->httpTestClients->mockedRequestor);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * @group request
	 *
	 * @dataProvider processShortUrlProvider
	 * @dataProvider processNoteUrlProvider
	 * @dataProvider processNoteShortUrlProvider
	 */
	public function testProcessRequestsReal(array $expectedResults, string $input): void
	{
		$service = new OpenStreetMapService($this->httpTestClients->realRequestor);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * @dataProvider processShortUrlProvider
	 * @dataProvider processNoteUrlProvider
	 * @dataProvider processNoteShortUrlProvider
	 */
	public function testProcessRequestsOffline(array $expectedResults, string $input): void
	{
		$service = new OpenStreetMapService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}
}
