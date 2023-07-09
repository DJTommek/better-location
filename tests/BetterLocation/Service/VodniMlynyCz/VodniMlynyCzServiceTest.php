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

	public function testIsValid(): void
	{
		$this->assertTrue(VodniMlynyCzService::isValidStatic('https://www.vodnimlyny.cz/en/mlyny/estates/detail/1509-stukhejlsky-mlyn'));
		$this->assertTrue(VodniMlynyCzService::isValidStatic('http://vodnimlyny.cz/ru/mlyny/estates/detail/7673-schwarzenbersky-mlyn'));

		$this->assertFalse(VodniMlynyCzService::isValidStatic('http://www.vodnimlyny.cz/'));
		$this->assertFalse(VodniMlynyCzService::isValidStatic('https://www.vodnimlyny.cz/en/mlyny/estates/map/?do=estateInfo&estateId=8286'));
		$this->assertFalse(VodniMlynyCzService::isValidStatic('http://www.vodnimlyny.cz/ru/mlyny/estates/detail/schwarzenbersky-mlyn'));
		$this->assertFalse(VodniMlynyCzService::isValidStatic('something random'));
	}

	/**
	 * @group request
	 */
	public function testProcess(): void
	{
		$this->assertLocation('https://www.vodnimlyny.cz/en/mlyny/estates/detail/1509-stukhejlsky-mlyn', 49.592579, 15.686811);
		$this->assertLocation('http://vodnimlyny.cz/ru/mlyny/estates/detail/7673-schwarzenbersky-mlyn', 49.509421682, 14.179542392);
	}

	/**
	 * Non existing estate
	 *
	 * @group request
	 */
	public function testInvalid(): void
	{
		$locations = VodniMlynyCzService::processStatic('https://www.vodnimlyny.cz/en/mlyny/estates/detail/9999999-stukhejlsky-mlyn')->getCollection();
		$this->assertCount(0, $locations);
	}
}
