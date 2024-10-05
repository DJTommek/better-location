<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Coordinates;

use App\BetterLocation\Service\Coordinates\USNGService;
use Tests\BetterLocation\Service\AbstractServiceTestCase;

final class USNGServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return USNGService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

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
