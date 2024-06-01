<?php declare(strict_types=1);

namespace Tests\OpenElevation;

use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\OpenElevation\OpenElevation;
use App\Utils\Coordinates;
use PHPUnit\Framework\TestCase;
use Tests\HttpTestClients;
use Tests\TestUtils;

/**
 * @group request
 */
final class OpenElevationTest extends TestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	public function testLookup(): void
	{
		$result = $this->createApi()->lookup(50.087451, 14.420671);
		$this->assertInstanceOf(Coordinates::class, $result);
		$this->assertSame(50.087451, $result->getLat());
		$this->assertSame(14.420671, $result->getLon());
		$this->assertSame(206.0, $result->getElevation());
	}

	public function testLookupBatch(): void
	{
		$input = [
			[50.087451, 14.420671],
			[-33.4255422, -70.6328236],
		];
		$result = $this->createApi()->lookupBatch($input);
		$this->assertCount(2, $result);
		$this->assertInstanceOf(Coordinates::class, $result[0]);
		$this->assertSame(50.087451, $result[0]->getLat());
		$this->assertSame(14.420671, $result[0]->getLon());
		$this->assertSame(206.0, $result[0]->getElevation());
		$this->assertInstanceOf(Coordinates::class, $result[1]);
		$this->assertSame(-33.4255422, $result[1]->getLat());
		$this->assertSame(-70.6328236, $result[1]->getLon());
		$this->assertSame(823.0, $result[1]->getElevation());
	}

	public function testLookupBatchIndexedArray(): void
	{
		$input = [
			[50.087451, 14.420671],
			'foo' => [-33.4255422, -70.6328236],
		];
		$result = $this->createApi()->lookupBatch($input);
		$this->assertCount(2, $result);
		$this->assertInstanceOf(Coordinates::class, $result[0]);
		$this->assertSame(50.087451, $result[0]->getLat());
		$this->assertSame(14.420671, $result[0]->getLon());
		$this->assertSame(206.0, $result[0]->getElevation());
		$this->assertInstanceOf(Coordinates::class, $result['foo']);
		$this->assertSame(-33.4255422, $result['foo']->getLat());
		$this->assertSame(-70.6328236, $result['foo']->getLon());
		$this->assertSame(823.0, $result['foo']->getElevation());
	}

	public function testFill(): void
	{
		$coords = new Coordinates(50.087451, 14.420671);
		$this->assertNull($coords->getElevation());
		$this->createApi()->fill($coords);
		$this->assertSame(206.0, $coords->getElevation());
	}

	public function testFillBatch(): void
	{
		$inputs = [
			new Coordinates(50.087451, 14.420671),
			new Coordinates(36.246600, -116.816900),
		];
		$this->assertNull($inputs[0]->getElevation());
		$this->assertNull($inputs[1]->getElevation());
		$this->createApi()->fillBatch($inputs);
		$this->assertSame(206.0, $inputs[0]->getElevation());
		$this->assertSame(-81.0, $inputs[1]->getElevation());
	}

	public function testFillBatchIndexedArray(): void
	{
		$inputs = [
			'foo' => new Coordinates(50.087451, 14.420671),
			new Coordinates(36.246600, -116.816900),
		];
		$this->assertNull($inputs['foo']->getElevation());
		$this->assertNull($inputs[0]->getElevation());
		$this->createApi()->fillBatch($inputs);
		$this->assertSame(206.0, $inputs['foo']->getElevation());
		$this->assertSame(-81.0, $inputs[0]->getElevation());
	}

	public function testFillBatchInvalidObjects(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Array value on index 0 is not instance of App\Utils\Coordinates.');
		$this->createApi()->fillBatch(['aaa']);
	}

	public function testFillBatchInvalidObjects2(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Array value on index 1 is not instance of App\Utils\Coordinates.');
		$a = [
			new Coordinates(36.246600, -116.816900),
			'aaa',
		];
		$this->createApi()->fillBatch($a);
	}

	public function testFillBatchEmptyArray(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Must provide at least one location');
		$this->createApi()->lookupBatch([]);
	}

	public function testLookupInvalidCoords(): void
	{
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Latitude coordinate must be numeric between or equal from -90 to 90 degrees.');
		$this->createApi()->lookup(99.087451, 14.420671);
	}

	public function testLookupBatchInvalidCoords(): void
	{
		$input = [
			[99.087451, 14.420671],
			[-33.4255422, -70.6328236],
		];
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Latitude coordinate must be numeric between or equal from -90 to 90 degrees.');
		$this->createApi()->lookupBatch($input);
	}

	public function testLookupBatchInvalidArray(): void
	{
		$input = [
			[50.087451, 14.420671],
			[-33.4255422], // missing latitude
		];
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Longitude coordinate must be numeric between or equal from -180 to 180 degrees.');
		$this->createApi()->lookupBatch($input);
	}

	public function testLookupBatchEmptyArray(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Must provide at least one location');
		$this->createApi()->lookupBatch([]);
	}

	private function createApi(): OpenElevation
	{
		return new OpenElevation($this->httpTestClients->realHttpClient, TestUtils::createDevNullCache());
	}
}
