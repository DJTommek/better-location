<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\ABetterRoutePlannerService;

final class ABetterRoutePlannerServiceTest extends AbstractServiceTestCase
{
	protected bool $revalidateGeneratedShareLink = false;
	protected bool $revalidateGeneratedDriveLink = false;

	#[\Override]
	protected function getServiceClass(): string
	{
		return ABetterRoutePlannerService::class;
	}

	#[\Override]
	protected function getShareLinks(): array
	{
		return [
			'https://abetterrouteplanner.com/?lat=50.087451&lon=14.420671',
			'https://abetterrouteplanner.com/?lat=50.100000&lon=14.500000',
			'https://abetterrouteplanner.com/?lat=-50.200000&lon=14.600000', // round down
			'https://abetterrouteplanner.com/?lat=50.300000&lon=-14.700001', // round up
			'https://abetterrouteplanner.com/?lat=-50.400000&lon=-14.800008',
		];
	}

	#[\Override]
	protected function getDriveLinks(): array
	{
		return $this->getShareLinks(); // Drive link is not supported, share link is used instead
	}

	public function testIsValid(): void
	{
		// Parsing links is not implemented yet
		$this->assertFalse(ABetterRoutePlannerService::validateStatic('https://abetterrouteplanner.com/?lat=50.087451&lon=14.420671'));
	}
}
