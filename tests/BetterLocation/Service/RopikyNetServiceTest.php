<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\MapyCzService;
use App\BetterLocation\Service\RopikyNetService;
use DJTommek\MapyCzApi\MapyCzApi;
use Tests\HttpTestClients;

final class RopikyNetServiceTest extends AbstractServiceTestCase
{
	private readonly MapyCzService $mapyCzServiceMocked;

	protected function setUp(): void
	{
		parent::setUp();

		$httpTestClients = new HttpTestClients();
		$this->mapyCzServiceMocked = new MapyCzService(
			$httpTestClients->mockedRequestor,
			(new MapyCzApi)->setClient($httpTestClients->mockedHttpClient),
		);
	}

	protected function getServiceClass(): string
	{
		return RopikyNetService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function isValidProvider(): array
	{
		return [
			[true, 'https://www.ropiky.net/dbase_objekt.php?id=1183840757'],
			[true, 'https://ropiky.net/dbase_objekt.php?id=1183840757'],
			[true, 'http://www.ropiky.net/dbase_objekt.php?id=1183840757'],
			[true, 'http://ropiky.net/dbase_objekt.php?id=1183840757'],

			[true, 'https://www.ropiky.net/nerop_objekt.php?id=1397407312'],
			[true, 'https://ropiky.net/nerop_objekt.php?id=1397407312'],
			[true, 'http://www.ropiky.net/nerop_objekt.php?id=1397407312'],
			[true, 'http://ropiky.net/nerop_objekt.php?id=1397407312'],

			[false, 'https://www.ropiky.net/dbase_objekt.php?id=abcd'],
			[false, 'https://www.ropiky.net/dbase_objekt.php?id='],
			[false, 'https://www.ropiky.net/dbase_objekt.blabla?id=1183840757'],
			[false, 'https://www.ropiky.net/nerop_objekt.php?id=abcd'],
			[false, 'https://www.ropiky.net/nerop_objekt.php?id='],
			[false, 'https://www.ropiky.net/nerop_objekt.blabla?id=1183840757'],
			[false, 'https://www.ropiky.net/aaaaa.php?id=1183840757'],
			[false, 'https://www.ropiky.net'],

			[false, 'some invalid url'],
		];
	}

	public static function processDBaseObjektProvider(): array
	{
		return [
			[[[48.325750, 20.233450]], 'https://ropiky.net/dbase_objekt.php?id=1183840757'],
			[[[48.331710, 20.240140]], 'https://ropiky.net/dbase_objekt.php?id=1183840760'],
			[[[50.127520, 16.601080]], 'https://ropiky.net/dbase_objekt.php?id=1075717726'],
			[[[49.346390, 16.974210]], 'https://ropiky.net/dbase_objekt.php?id=1075718529'],
			[[[47.999410, 18.780630]], 'https://ropiky.net/dbase_objekt.php?id=1075728128'],

			__FUNCTION__ . '-NoValidLocation' => [[], 'https://ropiky.net/dbase_objekt.php?id=1121190152'],
			__FUNCTION__ . '-InvalidId' => [[], 'https://ropiky.net/dbase_objekt.php?id=123'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new RopikyNetService($this->mapyCzServiceMocked);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processDBaseObjektProvider
	 */
	public function testProcess(array $expectedResults, string $input): void
	{
		$service = new RopikyNetService($this->mapyCzServiceMocked);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}
}
