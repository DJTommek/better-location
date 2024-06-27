<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\VodniMlynyCz;

use App\BetterLocation\Service\VodniMlynyCz\VodniMlynyCzService;
use Tests\BetterLocation\Service\AbstractServiceTestCase;

final class VodniMlynyCzServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return VodniMlynyCzService::class;
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
			[true, 'https://www.vodnimlyny.cz/en/mlyny/estates/detail/1509-stukhejlsky-mlyn'],
			[true, 'http://vodnimlyny.cz/ru/mlyny/estates/detail/7673-schwarzenbersky-mlyn'],

			[false, 'http://www.vodnimlyny.cz/'],
			[false, 'https://www.vodnimlyny.cz/en/mlyny/estates/map/?do=estateInfo&estateId=8286'],
			[false, 'http://www.vodnimlyny.cz/ru/mlyny/estates/detail/schwarzenbersky-mlyn'],
			[false, 'something random'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[[[49.592579, 15.686811]], 'https://www.vodnimlyny.cz/en/mlyny/estates/detail/1509-stukhejlsky-mlyn'],
			[[[49.509421, 14.179542]], 'http://vodnimlyny.cz/ru/mlyny/estates/detail/7673-schwarzenbersky-mlyn'],

			// Non existing estate
			[[], 'https://www.vodnimlyny.cz/en/mlyny/estates/detail/9999999-stukhejlsky-mlyn'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new VodniMlynyCzService();
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 *
	 * @dataProvider processProvider
	 */
	public function testProcess(array $expectedResults, string $input): void
	{
		$service = new VodniMlynyCzService();
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

}
