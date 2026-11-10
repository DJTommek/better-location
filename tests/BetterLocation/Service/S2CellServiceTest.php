<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\S2CellService;

final class S2CellServiceTest extends AbstractServiceTestCase
{
	#[\Override]
	protected function getServiceClass(): string
	{
		return S2CellService::class;
	}

	#[\Override]
	protected function getShareLinks(): array
	{
		return [];
	}

	#[\Override]
	protected function getDriveLinks(): array
	{
		return [];
	}

	public function testShareText(): void
	{
		$expected = [
			'470b94e93ddc8715',
			'470b9334f90f27ab',
			'b64bef13fffcfe89',
			'49b4058f1c6d7d77',
			'b8f40280319349bb',
		];
		foreach (self::EXAMPLE_COORDS as $i => [$lat, $lon]) {
			$this->assertSame($expected[$i], S2CellService::getShareText($lat, $lon));
		}
	}

	/**
	 * Reading tokens is intentionally not supported, it would cause too many false positives (findInText() is not implemented).
	 */
	public function testIsNotValid(): void
	{
		$this->assertServiceIsValid(new S2CellService(), '470b94e93ddc8715', false);
	}
}
