<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\BetterLocationService;
use App\BetterLocation\Service\CoordinatesRender\WGS84DegreeCompactService;
use App\BetterLocation\Service\DuckDuckGoService;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\Service\HereWeGoService;
use App\BetterLocation\Service\Interfaces\ShareCollectionLinkInterface;
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
	const TYPE_TEXT = 4;

	const TYPES = [
		self::TYPE_SHARE,
		self::TYPE_DRIVE,
		self::TYPE_SCREENSHOT,
		self::TYPE_TEXT,
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
	const DEFAULT_BULK_SHARE_SERVICES = [
		BetterLocationService::class,
		MapyCzService::class,
	];
	const DEFAULT_DRIVE_SERVICES = [
		GoogleMapsService::class,
		WazeService::class,
		HereWeGoService::class,
		OsmAndService::class,
	];
	const DEFAULT_SCREENSHOT_SERVICE = MapyCzService::class;
	const DEFAULT_TEXT_SERVICES = [
		WGS84DegreeCompactService::class,
	];

	/**
	 *
	 * @var array<int,class-string<AbstractService>> Ordered list of services, to show as links.
	 * There will be always at least one item which is BetterLocationService, reserved as 0
	 */
	private array $linkServices;
	/**
	 * @var array<int,class-string<AbstractService&ShareCollectionLinkInterface>> Ordered list of services, to show multiple locations at once
	 * There will be always at least one item which is BetterLocationService, reserved as index 0
	 */
	private array $bulkLinkServices;
	/**
	 * @var array<int,class-string<AbstractService>> Ordered list of services, to show as buttons.
	 * Might be empty
	 */
	private array $buttonServices;
	/**
	 * @var array<int,class-string<AbstractService>> List of services, to show as text representing location.
	 */
	private array $textServices;
	/**
	 * @var class-string<AbstractService> Service, which is providing static map image of location
	 */
	private string $screenshotLinkService;
	/**
	 * If address for locations should be generated and displayed in Better Location message
	 */
	private bool $showAddress;

	public function __construct(
		array  $shareServices = self::DEFAULT_SHARE_SERVICES,
		array  $bulkLinkServices = self::DEFAULT_BULK_SHARE_SERVICES,
		array  $buttonServices = self::DEFAULT_DRIVE_SERVICES,
		array  $textServices = self::DEFAULT_TEXT_SERVICES,
		string $screenshotLinkService = self::DEFAULT_SCREENSHOT_SERVICE,
		bool   $address = true
	)
	{
		$this->linkServices = $shareServices;
		$this->bulkLinkServices = $bulkLinkServices;
		$this->buttonServices = $buttonServices;
		$this->textServices = $textServices;
		$this->screenshotLinkService = $screenshotLinkService;
		$this->showAddress = $address;
	}

	public static function loadByChatId(int $chatId): self
	{
		$db = Factory::database();
		$rows = $db->query('SELECT * FROM better_location_chat_services WHERE chat_id = ? ORDER BY type, service_id DESC', $chatId)->fetchAll();
		$result = new self();
		$services = (new ServicesManager())->getServices();
		if ($filtered = self::processRows($services, $rows, self::TYPE_SHARE)) {
			$result->setLinkServices($filtered);
		}
		if ($filtered = self::processRows($services, $rows, self::TYPE_SCREENSHOT)) {
			$result->setScreenshotLinkService($filtered[0]);
		}
		if ($filtered = self::processRows($services, $rows, self::TYPE_TEXT)) {
			$result->setTextServices($filtered);
		}

		// Allow having 0 button services
		if (count($rows)) {
			// if user has previously saved settings it means, that he do not want to have buttons
			// otherwise, if user does not have any settings, default list of buttons will be used
			$result->setButtonServices(self::processRows($services, $rows, self::TYPE_DRIVE));
		}
		return $result;
	}

	/**
	 * Process rows from database to return ordered services by serviceType (link, button, ...)
	 *
	 * @param array<int,class-string<AbstractService>> $services List of all available services
	 * @param array $rows Raw rows loaded from from database
	 * @param int $serviceType What type of services will be returned
	 * @return array<int,class-string<AbstractService>> Ordered list of services
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

	/** @param array<class-string<AbstractService>> $services */
	public function setLinkServices(array $services): void
	{
		// Ensure, that first service is always BetterLocation, even if it is already set
		$services = array_unique(array_merge([BetterLocationService::class], $services));

		$services = array_filter($services, function ($service) { // remove services, that can't generate share link
			return $service::hasTag(ServicesManager::TAG_GENERATE_LINK_SHARE);
		});
		$this->linkServices = $services;
	}

	/** @param array<class-string<AbstractService>> $services */
	public function setButtonServices(array $services): void
	{
		$services = array_filter($services, function ($service) { // remove services, that can't generate drive link
			return $service::hasTag(ServicesManager::TAG_GENERATE_LINK_DRIVE);
		});
		$services = array_slice($services, 0, TelegramHelper::INLINE_KEYBOARD_MAX_BUTTON_PER_ROW);
		$this->buttonServices = $services;
	}

	/** @param class-string<AbstractService> $service */
	public function setScreenshotLinkService(string $service): void
	{
		if ($service::hasTag(ServicesManager::TAG_GENERATE_LINK_IMAGE)) {
			$this->screenshotLinkService = $service;
		} else {
			throw new \InvalidArgumentException(sprintf('Service "%s" (ID %s) could not be used as screenshot link service.', $service::getName(), $service::ID));
		}
	}

	/** @param array<class-string<AbstractService>> $services */
	public function setTextServices(array $services): void
	{
		$services = array_filter($services, function ($service) { // remove services, that can't generate text
			return $service::hasTag(ServicesManager::TAG_GENERATE_TEXT);
		});
		$this->textServices = $services;
	}

	/** @return array<class-string<AbstractService>> */
	public function getLinkServices(): array
	{
		return $this->linkServices;
	}

	/** @return array<class-string<AbstractService&ShareCollectionLinkInterface>> */
	public function getBulkLinkServices(): array
	{
		return $this->bulkLinkServices;
	}

	/** @return array<class-string<AbstractService>> */
	public function getButtonServices(): array
	{
		return $this->buttonServices;
	}

	/** @return array<class-string<AbstractService>> */
	public function getTextServices(): array
	{
		return $this->textServices;
	}

	/** @return class-string<AbstractService> */
	public function getScreenshotLinkService(): string
	{
		return $this->screenshotLinkService;
	}

	public function showAddress(?bool $showAddress = null): bool
	{
		if ($showAddress !== null) {
			$this->showAddress = $showAddress;
		}
		return $this->showAddress;
	}

	public function saveToDb(int $chatId): void
	{
		$query = 'INSERT INTO better_location_chat_services (`chat_id`, `service_id`, `type`, `order`) VALUES';
		$params = [];

		$i = 0;
		foreach ($this->getLinkServices() as $linkService) {
			$query .= '(?, ?, ?, ?), ';
			$params = array_merge($params, [$chatId, $linkService::ID, self::TYPE_SHARE, $i++]);
		}

		$i = 0;
		foreach ($this->getButtonServices() as $linkService) {
			$query .= '(?, ?, ?, ?), ';
			$params = array_merge($params, [$chatId, $linkService::ID, self::TYPE_DRIVE, $i++]);
		}

		$i = 0;
		foreach ($this->getTextServices() as $linkService) {
			$query .= '(?, ?, ?, ?), ';
			$params = array_merge($params, [$chatId, $linkService::ID, self::TYPE_TEXT, $i++]);
		}

		$query .= '(?, ?, ?, ?)';
		$params = array_merge($params, [$chatId, $this->getScreenshotLinkService()::ID, self::TYPE_SCREENSHOT, 0]);

		$db = Factory::database();
		$dbLink = $db->getLink();
		$dbLink->beginTransaction();
		try {
			$db->query('DELETE FROM better_location_chat_services WHERE chat_id = ?', $chatId);
			$db->query($query, ...$params);
			$db->getLink()->commit();
		} catch (\PDOException $exception) {
			$dbLink->rollBack();
			throw $exception;
		}
	}
}
