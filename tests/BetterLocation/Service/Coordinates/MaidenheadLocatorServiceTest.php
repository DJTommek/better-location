<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Coordinates;

use App\BetterLocation\Service\Coordinates\MaidenheadLocatorService;
use Tests\BetterLocation\Service\AbstractServiceTestCase;
use Tests\LocationTrait;

final class MaidenheadLocatorServiceTest extends AbstractServiceTestCase
{
	use LocationTrait;

	protected function getServiceClass(): string
	{
		return MaidenheadLocatorService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public function generateShareTextProvider(): array
	{
		return [
			['JO70FC00LX', 50.087451, 14.420671], // Prague
			['BL11BH16IU', 21.320225694444, -157.90540],
			['RD47NK66AW', -52.554355, 169.133373], // Loneliest tree, Ranfurly Tree
		];
	}

	/**
	 * @dataProvider generateShareTextProvider
	 */
	public function testGenerateShareText(?string $expected, float $lat, float $lon): void
	{
		$real = MaidenheadLocatorService::getShareText($lat, $lon);

		$this->assertSame($expected, $real);
	}
}
