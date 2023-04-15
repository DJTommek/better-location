<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
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

	/**
	 * Return array of share link as strings generated from coordinates from self::EXAMPLE_COORDS
	 * If generating share links is not supported, return empty array instead.
	 *
	 * @return string[]
	 */
	abstract protected function getShareLinks(): array;

	/**
	 * Return array of drive link as strings generated from coordinates from self::EXAMPLE_COORDS
	 * If generating drive links is not supported, return empty array instead.
	 *
	 * @return string[]
	 */
	abstract protected function getDriveLinks(): array;

	public function testGenerateShareLinkAndValidate(): void
	{
		$service = $this->getServiceClass();
		$expectedShareLinks = $this->getShareLinks();

		if ($expectedShareLinks === []) {
			$this->expectException(NotSupportedException::class);
			[$lat, $lon] = self::EXAMPLE_COORDS[0];
			$link = $service::getShareLink($lat, $lon);
			$this->fail(sprintf('[%s] Generating share link returned "%s" but should fail.', $service, $link));
		}

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

		if ($expectedShareLinks === []) {
			$this->expectException(NotSupportedException::class);
			[$lat, $lon] = self::EXAMPLE_COORDS[0];
			$link = $service::getDriveLink($lat, $lon);
			$this->fail(sprintf('[%s] Generating drive link returned "%s" but should fail.', $service, $link));
		}

		foreach (self::EXAMPLE_COORDS as $i => [$lat, $lon]) {
			$link = $service::getDriveLink($lat, $lon);
			$this->assertSame($expectedShareLinks[$i], $link);
			$this->assertTrue($service::isValidStatic($link), sprintf('[%s] Automatically generated drive link "%s" is not valid location.', $service, $link));
		}
	}

	protected function assertLocation(string $input, float $expectedLat, float $expectedLon, ?string $expectedSourceType = null): void
	{
		$serviceName = $this->getServiceClass();
		$service = new $serviceName($input);
		$this->assertInstanceOf(AbstractService::class, $service);

		$this->assertTrue($service->isValid());
		$service->process();

		$collection = $service->getCollection();
		$this->assertCount(1, $collection);

		$location = $collection->getFirst();
		$this->assertEqualsWithDelta($expectedLat, $location->getLat(), 0.000_001);
		$this->assertEqualsWithDelta($expectedLon, $location->getLon(), 0.000_001);

		$this->assertSame($expectedSourceType, $location->getSourceType());
	}

	abstract public function testIsValid(): void;

	abstract public function testProcess(): void;
}
