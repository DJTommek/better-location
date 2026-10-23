<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\DeadDropsCom;

use App\BetterLocation\Description;
use App\BetterLocation\Service\DeadDropsCom\DeadDrop;
use App\BetterLocation\Service\DeadDropsCom\DeadDropsComService;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Icons;
use DJTommek\Coordinates\CoordinatesImmutable;
use Tests\BetterLocation\Service\AbstractServiceTestCase;
use Tests\HttpTestClients;

final class DeadDropsComServiceTest extends AbstractServiceTestCase
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
		return DeadDropsComService::class;
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
			[true, 'https://www.deaddrops.com/db/?page=view&id=2772'],
			[true, 'https://deaddrops.com/db/?page=view&id=2413'],
			[true, 'http://deaddrops.com/db/?page=view&id=2413'],
			[true, 'https://www.deaddrops.com/db/?page=view&id=2413'],
			[true, 'http://www.deaddrops.com/db/?page=view&id=2413'],
			[true, 'https://deaddrops.com/db/?id=2413&page=view'],
			[true, 'https://deaddrops.com/db?page=view&id=2413'],
			[true, 'https://deaddrops.com/db/index.php?page=view&id=2413'],

			[false, 'some invalid url'],
			[false, 'https://deaddrops.com/'],
			[false, 'https://deaddrops.com/db/'],
			[false, 'https://deaddrops.com/db/?page=view'],
			[false, 'https://deaddrops.com/db/?page=view&id='],
			[false, 'https://deaddrops.com/db/?page=view&id=abc'],
			[false, 'https://deaddrops.com/db/?page=view&id=123abc'],
			[false, 'https://deaddrops.com/db/?page=list&id=2413'],
			[false, 'https://deaddrops.com/other/?page=view&id=2413'],
			[false, 'https://deaddrops.example.com/db/?page=view&id=2413'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[49.7583, 15.0961, 'Babka', 'https://www.deaddrops.com/db/?page=view&id=2772'],
			[48.8105, 14.3183, 'USB v Myší díře', 'https://deaddrops.com/db/?page=view&id=2413'],
			[38.6973, -77.4428, 'Lake Jackson BridgeDrop', 'https://deaddrops.com/db/?page=view&id=100'],
			[51.4754, -0.037693, '&quot;Tripoli&quot;', 'https://deaddrops.com/db/?page=view&id=500'],
			[44.4683, 26.046, 'Memoria Colectivă', 'https://deaddrops.com/db/?page=view&id=2000'],
			[57.9719, -3.98007, 'Golspie Dead Drop', 'https://deaddrops.com/db/?page=view&id=1579'],
			[36.6, 89.4, 'Julia', 'https://deaddrops.com/db/?page=view&id=1580'],
			[47.2561, 11.3785, 'FALCON', 'https://deaddrops.com/db/?page=view&id=2412'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new DeadDropsComService($this->httpTestClients->mockedRequestor);
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
		$service = new DeadDropsComService($this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
		$this->assertPrefix($expectedName, $input, $service);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $expectedName, string $input): void
	{
		$service = new DeadDropsComService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
		$this->assertPrefix($expectedName, $input, $service);
	}

	/**
	 * USB drop with all pictures and status "working"
	 */
	public function testDescriptionsWorkingWithPicturesOffline(): void
	{
		$descriptions = $this->processDescriptions('https://deaddrops.com/db/?page=view&id=2413');

		// Status line is not checked here, it depends on current date (see testStatusDescription())
		$picturesDescription = $this->findDescription($descriptions, 'Pictures: ');
		$this->assertSame(
			'Pictures: '
			. '<a href="https://deaddrops.com/db/images/fullsize/2413/172689.jpg">Overview</a>, '
			. '<a href="https://deaddrops.com/db/images/fullsize/2413/210934.jpg">Medium distance</a>, '
			. '<a href="https://deaddrops.com/db/images/fullsize/2413/665303.jpg">Closeup</a>',
			$picturesDescription,
		);
		$this->assertSame($picturesDescription, end($descriptions));
	}

	/**
	 * Wireless drop without pictures and with status "unconfirmed"
	 */
	public function testDescriptionsWirelessWithoutPicturesOffline(): void
	{
		$descriptions = $this->processDescriptions('https://deaddrops.com/db/?page=view&id=1579');

		$this->assertCount(1, $descriptions);
		$this->assertStringStartsWith(Icons::WARNING . ' Status: <b>unconfirmed, may be broken</b> since <tg-time unix="1733877069"', $descriptions[0]);
		$this->assertNull($this->findDescription($descriptions, 'Pictures: '));
	}

	/**
	 * Wireless drop with only one picture and status "broken"
	 */
	public function testDescriptionsBrokenWithOnePictureOffline(): void
	{
		$descriptions = $this->processDescriptions('https://deaddrops.com/db/?page=view&id=1580');

		$this->assertCount(2, $descriptions);
		$this->assertStringStartsWith(Icons::WARNING . ' Status: <b>broken/dead/stolen/gone</b> since ', $descriptions[0]);
		$this->assertSame('Pictures: <a href="https://deaddrops.com/db/images/fullsize/1580/234888.jpg">Overview</a>', $descriptions[1]);
	}

	/**
	 * Status "working" but last changed in 2021
	 */
	public function testDescriptionsWorkingOutdatedOffline(): void
	{
		$descriptions = $this->processDescriptions('https://deaddrops.com/db/?page=view&id=2412');

		$statusDescription = $this->findDescription($descriptions, Icons::WARNING);
		$this->assertNotNull($statusDescription);
		$this->assertStringStartsWith(Icons::WARNING . ' Status: working since <tg-time unix="1639773731"', $statusDescription);
		$this->assertStringEndsWith(', might be outdated', $statusDescription);
	}

	public static function statusDescriptionProvider(): array
	{
		return [
			'working, recently changed' => [null, DeadDrop::STATUS_WORKING, '2026-09-25 20:50:33', '2026-10-22 12:00:00'],
			'working, changed almost year ago' => [null, DeadDrop::STATUS_WORKING, '2025-10-23 12:00:00', '2026-10-22 12:00:00'],
			'working, changed more than year ago' => [
				Icons::WARNING . ' Status: working since '
				. '<tg-time unix="1729684800" format="DT">2024-10-23 12:00:00 UTC</tg-time> '
				. '(<tg-time unix="1729684800" format="r">2024-10-23 12:00:00 UTC</tg-time>), might be outdated',
				DeadDrop::STATUS_WORKING,
				'2024-10-23 12:00:00',
				'2026-10-22 12:00:00',
			],
			'unconfirmed, recently changed' => [
				Icons::WARNING . ' Status: <b>unconfirmed, may be broken</b> since <tg-time unix="1790800000" format="DT">',
				DeadDrop::STATUS_UNCONFIRMED,
				'@1790800000',
				'2026-10-22 12:00:00',
			],
			'broken, without date' => [
				Icons::WARNING . ' Status: <b>broken/dead/stolen/gone</b>',
				DeadDrop::STATUS_BROKEN,
				null,
				'2026-10-22 12:00:00',
			],
		];
	}

	/**
	 * @dataProvider statusDescriptionProvider
	 */
	public function testStatusDescription(?string $expectedPrefix, string $status, ?string $statusChanged, string $now): void
	{
		$utc = new \DateTimeZone('UTC');
		$deadDrop = new DeadDrop(
			coords: new CoordinatesImmutable(50, 14),
			name: 'Test',
			type: null,
			size: null,
			dateCreated: null,
			address: null,
			status: $status,
			statusChanged: $statusChanged === null ? null : new \DateTimeImmutable($statusChanged, $utc),
			about: null,
			pictures: [],
		);
		$service = new DeadDropsComService($this->httpTestClients->mockedRequestor);

		$result = $service->statusDescription($deadDrop, new \DateTimeImmutable($now, $utc));
		if ($expectedPrefix === null) {
			$this->assertNull($result);
		} else {
			$this->assertNotNull($result);
			$this->assertStringStartsWith($expectedPrefix, $result);
		}
	}

	/**
	 * @group request
	 */
	public function testMissingCoordinatesReal(): void
	{
		$service = new DeadDropsComService($this->httpTestClients->realRequestor);
		$service->setInput('https://deaddrops.com/db/?page=view&id=9999999');
		$this->assertTrue($service->validate());

		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Coordinates on DeadDrops.com page are missing.');

		$service->process();
	}

	public function testMissingCoordinatesOffline(): void
	{
		$service = new DeadDropsComService($this->httpTestClients->offlineRequestor);
		$service->setInput('https://deaddrops.com/db/?page=view&id=9999999');
		$this->assertTrue($service->validate());

		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Coordinates on DeadDrops.com page are missing.');

		$service->process();
	}

	private function assertPrefix(string $expectedName, string $input, DeadDropsComService $service): void
	{
		$this->assertSame(
			sprintf('<a href="%s">DeadDrops.com %s</a>', $input, $expectedName),
			$service->getCollection()->getFirst()->getPrefixMessage(),
		);
	}

	/**
	 * @return list<string>
	 */
	private function processDescriptions(string $input): array
	{
		$service = new DeadDropsComService($this->httpTestClients->offlineRequestor);
		$service->setInput($input);
		$this->assertTrue($service->validate());
		$service->process();
		return array_map(
			fn(Description $description): string => $description->content,
			$service->getCollection()->getFirst()->getDescriptions(),
		);
	}

	/**
	 * @param list<string> $descriptions
	 */
	private function findDescription(array $descriptions, string $prefix): ?string
	{
		foreach ($descriptions as $description) {
			if (str_starts_with($description, $prefix)) {
				return $description;
			}
		}
		return null;
	}
}
