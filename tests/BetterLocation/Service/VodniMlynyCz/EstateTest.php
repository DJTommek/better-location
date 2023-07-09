<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\VodniMlynyCz;

use App\BetterLocation\Service\VodniMlynyCz\Estate;
use PHPUnit\Framework\TestCase;

final class EstateTest extends TestCase
{
	/**
	 * @return array<mixed>|\Generator
	 */
	public function parseRawProvider(): array|\Generator
	{
		return [
			yield [$this->readJsonFixture(__DIR__ . '/fixtures/estate-basic.json'), 2318, 51.046, 14.5008061, 'mlýn ve Fukově'],
			yield [$this->readJsonFixture(__DIR__ . '/fixtures/estate-coords-string.json'), 10591, 51.03776189561927, 14.311489045619936, 'mlýn v Severni I'],
		];
	}

	/**
	 * @return array<mixed>|\Generator
	 */
	public function parseRawBatchProvider(): array|\Generator
	{
		return [
			yield [$this->readJsonFixture(__DIR__ . '/fixtures/estates-all.json')],
		];
	}

	/**
	 * @dataProvider parseRawProvider
	 */
	public function testParseRaw(
		\stdClass $raw,
		int $expectedId,
		float $expectedLat,
		float $expectedLon,
		string $expectedName,
	): void
	{
		$estate = Estate::fromResponse($raw);
		$this->assertEstate($estate);

		$this->assertSame($estate->id, $expectedId);
		$this->assertSame($estate->lat, $expectedLat);
		$this->assertSame($estate->lng, $expectedLon);
		$this->assertSame($estate->coords->lat, $expectedLat);
		$this->assertSame($estate->coords->lon, $expectedLon);
		$this->assertSame($estate->name, $expectedName);
	}

	/**
	 * @dataProvider parseRawBatchProvider
	 *
	 * @param array<mixed> $rawBatch
	 */
	public function testParseRawBatch(array $rawBatch): void
	{
		$estates = Estate::fromResponseBatch($rawBatch);
		foreach ($estates as $estateId => $estate) {
			$this->assertSame($estateId, $estate->id);
			$this->assertEstate($estate);
		}
	}

	/**
	 * @return array<mixed>|\stdClass
	 */
	private function readJsonFixture(string $path): array|\stdClass
	{
		return json_decode(file_get_contents($path));
	}

	private function assertEstate(Estate $estate): void
	{
		$this->assertIsInt($estate->id);

		$this->assertIsFloat($estate->lat);
		$this->assertIsFloat($estate->lng);
		$this->assertSame($estate->lat, $estate->coords->lat);
		$this->assertSame($estate->lng, $estate->coords->lon);

		$this->assertIsString($estate->name);
		$this->assertIsString($estate->icon);
	}
}
