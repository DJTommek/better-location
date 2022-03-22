<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ServiceManagerTest extends TestCase
{
	private static \App\BetterLocation\ServicesManager $manager;

	public static function setUpBeforeClass(): void
	{
		self::$manager = new \App\BetterLocation\ServicesManager();
	}

	/**
	 * Ensure, that all services are setup properly.
	 */
	public function testRequiredDataInServices(): void
	{
		$ids = [];
		foreach(self::$manager->getServices() as $service) {
			$reflection = new ReflectionClass($service);
			$constantName = 'ID';
			$this->assertTrue($reflection->hasConstant('NAME'), sprintf('Service class "%s" does not have required constant "NAME"', $service));
			$this->assertTrue($reflection->hasConstant($constantName), sprintf('Service class "%s" does not have required constant "%s"', $service, $constantName));
			$id = $reflection->getConstant($constantName);
			$this->assertIsInt($id, sprintf('Service class "%s" has invalid constant %s "%s", must be int.', $service, $constantName, $id));
			$this->assertGreaterThan(0, $id, sprintf('Service class "%s" has invalid constant %s "%s", must be positive int.', $service, $constantName, $id));
			$duplicatedServiceName = $ids[$id] ?? null;
			$this->assertNull($duplicatedServiceName, sprintf('ID %d is assigned to multiple services: "%s" and "%s"', $id, $duplicatedServiceName, $service));
			$ids[$id] = $service;
		}
	}
}
