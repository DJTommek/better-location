<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;
use Tests\LocationTrait;

abstract class AbstractServiceTestCase extends TestCase
{
	use LocationTrait;

	final public const EXAMPLE_COORDS = [
		[50.087451, 14.420671],
		[50.1, 14.5],
		[-50.2, 14.6000001], // round down
		[50.3, -14.7000009], // round up
		[-50.4, -14.800008],
	];

	/**
	 * Generate share link from coordinates and check if that link is valid for service.
	 */
	protected bool $revalidateGeneratedShareLink = true;
	/**
	 * Generate drive link from coordinates and check if that link is valid for service.
	 */
	protected bool $revalidateGeneratedDriveLink = true;

	public function tearDown(): void
	{
		$this->revalidateGeneratedShareLink = true;
		$this->revalidateGeneratedDriveLink = true;
	}

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
			if ($this->revalidateGeneratedShareLink === true) {
				$this->assertTrue($service::validateStatic($link), sprintf('[%s] Automatically generated share link "%s" is not valid location.', $service, $link));
			}
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
			if ($this->revalidateGeneratedDriveLink === true) {
				$this->assertTrue($service::validateStatic($link), sprintf('[%s] Automatically generated drive link "%s" is not valid location.', $service, $link));
			}
		}
	}

	protected function assertLocation(string $input, float $expectedLat, float $expectedLon, ?string $expectedSourceType = null, float $delta = 0.000_001): BetterLocation
	{
		$serviceName = $this->getServiceClass();
		$service = new $serviceName();
		$service->setInput($input);

		$this->assertInstanceOf(AbstractService::class, $service);

		$this->assertTrue($service->validate());
		$service->process();

		$collection = $service->getCollection();
		$this->assertCount(1, $collection);

		$location = $collection->getFirst();
		$this->assertCoordsWithDelta($expectedLat, $expectedLon, $location, $delta);

		$this->assertSame($expectedSourceType, $location->getSourceType());

		return $location;
	}

	protected function assertServiceIsValid(
		AbstractService $service,
		string $input,
		bool $expectedIsValid,
	): void {
		$this->assertInstanceOf($this->getServiceClass(), $service);
		$service->setInput($input);

		$this->assertSame($expectedIsValid, $service->validate());
	}

	protected function assertServiceLocation(
		AbstractService $service,
		string $input,
		float $expectedLat,
		float $expectedLon,
		?string $expectedSourceType = null,
		float $delta = 0.000_001,
	): BetterLocation {
		$this->assertInstanceOf($this->getServiceClass(), $service);
		$service->setInput($input);

		$this->assertTrue($service->validate());
		$service->process();

		$collection = $service->getCollection();
		$this->assertCount(1, $collection);

		$location = $collection->getFirst();
		$this->assertCoordsWithDelta($expectedLat, $expectedLon, $location, $delta);

		$this->assertSame($expectedSourceType, $location->getSourceType());

		return $location;
	}

	/**
	 * @param array<array{float, float, ?string, ?string}> $expectedResults List of expected results {lat, lon,
	 *      ?sourceType, ?expectedPrefix} If expectedPrefix is not provided or null, it is not being checked.
	 */
	protected function assertServiceLocations(
		AbstractService $service,
		string $input,
		array $expectedResults,
		float $delta = 0.000_001,
	): void {
		$this->assertInstanceOf($this->getServiceClass(), $service);
		$service->setInput($input);

		$this->assertTrue($service->validate());
		$service->process();

		$collection = $service->getCollection();
		$this->assertCount(count($expectedResults), $collection);

		foreach ($expectedResults as $key => $expectedResult) {
			$expectedLat = $expectedResult[0];
			$expectedLon = $expectedResult[1];
			$location = $collection[$key];
			$this->assertCoordsWithDelta($expectedLat, $expectedLon, $location, $delta);
			$expectedSourceType = $expectedResult[2] ?? null;
			$this->assertSame($expectedSourceType, $location->getSourceType());

			$expectedPrefix = $expectedResult[3] ?? null;
			if ($expectedPrefix !== null) {
				$this->assertSame($expectedPrefix, $location->getPrefixMessage());
			}
		}
	}

	protected function assertServiceNoLocation(
		AbstractService $service,
		string $input,
	): void {
		$this->assertInstanceOf($this->getServiceClass(), $service);
		$service->setInput($input);

		$this->assertTrue($service->validate());
		$service->process();

		$collection = $service->getCollection();
		$this->assertCount(0, $collection);
	}
}
