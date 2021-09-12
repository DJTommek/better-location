<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\BetterLocationService;
use App\BetterLocation\Service\DuckDuckGoService;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\Service\HereWeGoService;
use App\BetterLocation\Service\MapyCzService;
use App\BetterLocation\Service\OpenStreetMapService;
use App\BetterLocation\Service\OsmAndService;
use App\BetterLocation\Service\WazeService;
use App\BetterLocation\ServicesManager;
use App\Factory;
use App\Utils\Strict;

class BetterLocationMessageSettings
{
	const TYPE_SHARE = 1;
	const TYPE_DRIVE = 2;
	const TYPE_SCREENSHOT = 3;

	const TYPES = [
		self::TYPE_SHARE,
		self::TYPE_DRIVE,
		self::TYPE_SCREENSHOT,
	];

	const DEFAULT_SHARE_SERVICES = [
		BetterLocationService::class,
		GoogleMapsService::class,
		MapyCzService::class,
		DuckDuckGoService::class,
		WazeService::class,
		HereWeGoService::class,
		OpenStreetMapService::class,
	];
	const DEFAULT_DRIVE_SERVICES = [
		GoogleMapsService::class,
		WazeService::class,
		HereWeGoService::class,
		OsmAndService::class,
	];
	const DEFAULT_SCREENSHOT_SERVICE = MapyCzService::class;

	/**
	 * @var array<int,AbstractService> Ordered list of services, to show as links.
	 * There will be always at least one item which is BetterLocationService, reserved as 0
	 */
	private $linkServices;
	/**
	 * @var array<int,AbstractService> Ordered list of services, to show as buttons.
	 * Might be empty
	 */
	private $buttonServices;
	/**
	 * @var AbstractService Service, which is providing static map image of location
	 */
	private $screenshotLinkService;
	/**
	 * @var bool Should be address displayed
	 */
	private $address;

	public function __construct(
		array $shareServices = self::DEFAULT_SHARE_SERVICES,
		array $buttonServices = self::DEFAULT_DRIVE_SERVICES,
		string $screenshotLinkService = self::DEFAULT_SCREENSHOT_SERVICE,
		bool $address = true
	)
	{
		$this->linkServices = $shareServices;
		$this->buttonServices = $buttonServices;
		$this->screenshotLinkService = $screenshotLinkService;
		$this->address = $address;
	}

	public static function loadByChatId(int $chatId): self
	{
		$db = Factory::Database();
		$rows = $db->query('SELECT * FROM better_location_chat_services WHERE chat_id = ? ORDER BY type, service_id DESC', $chatId)->fetchAll();
		$result = new self();
		$services = (new ServicesManager())->getServices();
		if ($filtered = self::processRows($services, $rows, self::TYPE_SHARE)) {
			$result->setLinkServices($filtered);
		}
		if ($filtered = self::processRows($services, $rows, self::TYPE_DRIVE)) {
			$result->setButtonServices($filtered);
		}
		if ($filtered = self::processRows($services, $rows, self::TYPE_SCREENSHOT)) {
			$result->setScreenshotLinkService($filtered[0]);
		}
		return $result;
	}

	/**
	 * Process rows from database to return ordered services by serviceType (link, button, ...)
	 *
	 * @param array<int,AbstractService> $services List of all available services
	 * @param array $rows Raw rows loaded from from database
	 * @param int $serviceType What type of services will be returned
	 * @return array<int,AbstractService> Ordered list of services
	 */
	private static function processRows(array $services, array $rows, int $serviceType): array
	{
		$filteredRows = array_filter($rows, function ($row) use ($serviceType) {
			return Strict::intval($row['type']) === $serviceType;
		});
		$result = [];
		foreach ($filteredRows as $filteredRow) {
			$order = Strict::intval($filteredRow['order']);
			$serviceId = $filteredRow['service_id'];
			$result[$order] = $services[$serviceId];
		}
		return $result;
	}

	/** @param AbstractService[] $services */
	public function setLinkServices(array $services): void
	{
		// Ensure, that first service is always BetterLocation, even if it is already set
		$services = array_unique([BetterLocationService::class] + $services);
		ksort($services, SORT_NUMERIC);
		$this->linkServices = $services;
	}

	/** @param AbstractService[] $services */
	public function setButtonServices(array $services): void
	{
		ksort($services, SORT_NUMERIC);
		$this->buttonServices = $services;
	}

	public function setScreenshotLinkService(string $service): void
	{
		$this->screenshotLinkService = $service;
	}

	public function setAddress(bool $address): void
	{
		$this->address = $address;
	}

	/** @return AbstractService[] */
	public function getLinkServices(): array
	{
		return $this->linkServices;
	}

	/** @return AbstractService[] */
	public function getButtonServices(): array
	{
		return $this->buttonServices;
	}

	public function getScreenshotLinkService(): string
	{
		return $this->screenshotLinkService;
	}

	public function showAddress(): bool
	{
		return $this->address;
	}
}
