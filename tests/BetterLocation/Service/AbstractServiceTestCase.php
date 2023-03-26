<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\AbstractService;
use PHPUnit\Framework\TestCase;

abstract class AbstractServiceTestCase extends TestCase
{
	public const EXAMPLE_COORDS = [
		[50.087451, 14.420671],
		[50.1, 14.5],
		[-50.2, 14.6000001], // round down
		[50.3, -14.7000009], // round up
		[-50.4, -14.800008],
	];

	/**
	 * @return class-string<AbstractService>
	 */
	abstract protected function getServiceClass(): string;

	abstract protected function getShareLinks(): array;

	abstract protected function getDriveLinks(): array;

	public function generateLinkDataProvider(): array
	{
		return [
			[50.087451, 14.420671],
			[50.1, 14.5],
			[-50.2, 14.6000001], // round down
			[50.3, -14.7000009], // round up
			[-50.4, -14.800008],
		];
	}

	public function testGenerateShareLinkAndValidate(): void
	{
		$service = $this->getServiceClass();
		$expectedShareLinks = $this->getShareLinks();

		foreach (self::EXAMPLE_COORDS as $i => [$lat, $lon]) {
			$link = $service::getShareLink($lat, $lon);
			$this->assertSame($expectedShareLinks[$i], $link);
			$this->assertTrue($service::isValidStatic($link), sprintf('[%s] Automatically generated share link "%s" is not valid location.', $service, $link));
		}
	}

	public function testGenerateDriveLinkAndValidate(): void
	{
		$service = $this->getServiceClass();
		$expectedShareLinks = $this->getDriveLinks();

		foreach (self::EXAMPLE_COORDS as $i => [$lat, $lon]) {
			$link = $service::getDriveLink($lat, $lon);
			$this->assertSame($expectedShareLinks[$i], $link);
			$this->assertTrue($service::isValidStatic($link), sprintf('[%s] Automatically generated drive link "%s" is not valid location.', $service, $link));
		}
	}

	abstract public function testIsValid(): void;

	abstract public function testProcess(): void;
}
