<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\SochyAMestaCzService;
use Tests\HttpTestClients;

final class SochyAMestaCzServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	#[\Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	#[\Override]
	protected function getServiceClass(): string
	{
		return SochyAMestaCzService::class;
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

	public static function isValidProvider(): array
	{
		return [
			[true, 'https://sochyamesta.cz/zaznam/2319'],
			[true, 'http://sochyamesta.cz/zaznam/2319'],
			[true, 'https://www.sochyamesta.cz/zaznam/2319'],
			[true, 'https://sochyamesta.cz/zaznam/2319/'],
			[true, 'https://sochyamesta.cz/zaznam/11725'],
			[true, 'https://sochyamesta.cz/zaznam/11725?foo=bar'],

			[false, 'some invalid url'],
			[false, 'https://sochyamesta.cz/'],
			[false, 'https://sochyamesta.cz/zaznam'],
			[false, 'https://sochyamesta.cz/zaznam/'],
			[false, 'https://sochyamesta.cz/zaznam/abc'],
			[false, 'https://sochyamesta.cz/zaznam/123abc'],
			[false, 'https://sochyamesta.cz/zaznamy?field_lokalita_tid=Písek'],
			[false, 'https://sochyamesta.cz/print/tisk/2319'],
			[false, 'https://sochyamesta.example.com/zaznam/2319'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[49.308789604289366, 14.147263169288635, 'Státní znak a žardiniéry pro obřadní síň', 'https://sochyamesta.cz/zaznam/2319'],
			[49.74340304443831, 15.08782306689659, 'Nástěnné hodiny', 'https://sochyamesta.cz/zaznam/11725'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new SochyAMestaCzService($this->httpTestClients->mockedRequestor);
		$service->setInput($input);
		$isValid = $service->validate();
		$this->assertSame($expectedIsValid, $isValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $expectedName, string $input): void
	{
		$service = new SochyAMestaCzService($this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
		$this->assertPrefix($expectedName, $input, $service);
		$this->assertSame([], $service->getCollection()->getFirst()->getDescriptions());
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $expectedName, string $input): void
	{
		$service = new SochyAMestaCzService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
		$this->assertPrefix($expectedName, $input, $service);
		$this->assertSame([], $service->getCollection()->getFirst()->getDescriptions());
	}

	/**
	 * @group request
	 */
	public function testMissingCoordinatesReal(): void
	{
		$service = new SochyAMestaCzService($this->httpTestClients->realRequestor);
		$service->setInput('https://sochyamesta.cz/zaznam/99999999');
		$this->assertTrue($service->validate());

		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Coordinates on SochyAMesta.cz page are missing.');

		$service->process();
	}

	public function testMissingCoordinatesOffline(): void
	{
		$service = new SochyAMestaCzService($this->httpTestClients->offlineRequestor);
		$service->setInput('https://sochyamesta.cz/zaznam/99999999');
		$this->assertTrue($service->validate());

		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Coordinates on SochyAMesta.cz page are missing.');

		$service->process();
	}

	private function assertPrefix(string $expectedName, string $input, SochyAMestaCzService $service): void
	{
		$this->assertSame(
			sprintf('<a href="%s">SochyAMesta.cz %s</a>', $input, $expectedName),
			$service->getCollection()->getFirst()->getPrefixMessage(),
		);
	}
}
