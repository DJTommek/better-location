<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Coordinates;

use App\BetterLocation\Service\Coordinates\SJTSKService;
use Tests\BetterLocation\Service\AbstractServiceTestCase;

final class SJTSKServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return SJTSKService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function getShareTextProvider(): array
	{
		return [
			['X: -742851, Y: -1043009', 50.087451, 14.420671],
			['X: -725986, Y: -1022094', 50.294246, 14.615147],
			['X: -597984, Y: -1159144', 49.209667, 16.608056],
			['X: -822934, Y: -1070110', 49.743111, 13.371111],
			['X: -573682, Y: -1280324', 48.148596, 17.107748],
			['X: -263393, Y: -1239969', 48.716667, 21.25],
			['X: -688107, Y: -974024', 50.767222, 15.056111],
		];
	}

	/**
	 * @dataProvider getShareTextProvider
	 */
	public function testGetShareText(string $expected, float $lat, float $lon): void
	{
		$this->assertSame($expected, SJTSKService::getShareText($lat, $lon));
	}
}
