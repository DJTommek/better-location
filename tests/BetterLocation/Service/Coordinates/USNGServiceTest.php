<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Coordinates;

use App\BetterLocation\Service\Coordinates\USNGService;
use Tests\BetterLocation\Service\AbstractServiceTestCase;

final class USNGServiceTest extends AbstractServiceTestCase
{
	#[\Override]
 protected function getServiceClass(): string
	{
		return USNGService::class;
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

	public function testValidLocation(): void
	{
		$service = new USNGService();
		$this->assertServiceIsValid($service, 'Nothing valid', false);
	}

	public function testNothingInText(): void
	{
		$this->assertSame([], USNGService::findInText('Nothing valid')->getLocations());
	}
}
