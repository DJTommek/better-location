<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use App\BetterLocation\Service\DuckDuckGoService;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\Service\HereWeGoService;
use App\BetterLocation\Service\MapyCzService;
use App\BetterLocation\Service\OpenStreetMapService;
use App\BetterLocation\Service\OsmAndService;
use App\BetterLocation\Service\WazeService;
use App\Factory;
use App\Icons;
use App\TelegramCustomWrapper\Events\Button\RefreshButton;
use App\TelegramCustomWrapper\Events\Command\StartCommand;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Coordinates;
use App\Utils\Strict;
use Nette\Http\UrlImmutable;
use OpenLocationCode\OpenLocationCode;
use unreal4u\TelegramAPI\Telegram\Types;

class BetterLocation
{
	private $lat;
	private $lon;
	private $description;
	private $prefixMessage;
	private $coordinateSuffixMessage;
	private $address;
	/** @var string */
	private $input;
	/** @var ?UrlImmutable */
	private $inputUrl;
	private $sourceService;
	private $sourceType;
	private $pregeneratedLinks = [];
	private $inlinePrefixMessage;
	/** @var bool Can location change with same input? */
	private $refreshable = false;

	/**
	 * BetterLocation constructor.
	 *
	 * @param string|\Nette\Http\Url|\Nette\Http\UrlImmutable $input
	 * @param string $sourceService has to be name of class extending \BetterLocation\Service\AbstractService
	 * @param ?string $sourceType if $sourceService class has multiple type of source, this must be included
	 * @throws InvalidLocationException
	 */
	public function __construct($input, float $lat, float $lon, string $sourceService, ?string $sourceType = null)
	{
		$this->validateInput($input);
		$this->validateCoords($lat, $lon);
		$this->validateSourceService($sourceService);
		$this->validateSourceType($sourceType);
		$this->generateDefaultPrefix();

		// pregenerate link for MapyCz if contains source and ID (@see https://github.com/DJTommek/better-location/issues/17)
		if ($this->inputUrl && $this->sourceService === MapyCzService::class && $this->sourceType === MapyCzService::TYPE_PLACE_ID) {
			$generatedUrl = MapyCzService::getLink($this->lat, $this->lon);
			$generatedUrl = str_replace(sprintf('%F%%2C%F', $this->lon, $this->lat), $this->inputUrl->getQueryParameter('id'), $generatedUrl);
			$generatedUrl = str_replace('source=coor', 'source=' . $this->inputUrl->getQueryParameter('source'), $generatedUrl);
			$this->pregeneratedLinks[MapyCzService::class] = $generatedUrl;
		}
	}

	public function getName()
	{
		return $this->sourceType;
	}

	public function export(): array
	{
		return [
			'lat' => $this->getLat(),
			'lon' => $this->getLon(),
			'service' => strip_tags($this->getPrefixMessage()),
		];
	}

	public function generateScreenshotLink(string $serviceClass)
	{
		if (class_exists($serviceClass) === false) {
			throw new \InvalidArgumentException(sprintf('Invalid location service: "%s".', $serviceClass));
		}
		if (is_subclass_of($serviceClass, AbstractService::class) === false && is_subclass_of($serviceClass, AbstractService::class) === false) {
			throw new \InvalidArgumentException(sprintf('Source service has to be subclass of "%s".', AbstractService::class));
		}
		if (method_exists($serviceClass, 'getScreenshotLink') === false) {
			throw new \InvalidArgumentException(sprintf('Source service "%s" does not supports screenshot links.', $serviceClass));
		}
		return $serviceClass::getScreenshotLink($this->getLat(), $this->getLon());
	}

	public function setAddress(string $address)
	{
		$this->address = $address;
	}

	public function getAddress(): ?string
	{
		return $this->address;
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	public function generateAddress()
	{
		if (is_null($this->address)) {
			try {
				$result = Factory::WhatThreeWords()->convertTo3wa($this->getLat(), $this->getLon());
			} catch (\Exception $exception) {
				throw new \Exception('Unable to get address from W3W API');
			}
			if ($result) {
				$this->address = sprintf('Nearby: %s, %s', $result['nearestPlace'], $result['country']);
			} else {
				throw new \Exception('Unable to get address from W3W API');
			}
		}
		return $this->address;
	}

	/**
	 * @param string|resource $input Path or URL link to file or resource (see https://php.net/manual/en/function.exif-read-data.php)
	 * @return BetterLocation|null
	 * @throws InvalidLocationException|Service\Exceptions\NotImplementedException
	 */
	public static function fromExif($input): ?BetterLocation
	{
		if (is_string($input) === false && is_resource($input) === false) {
			throw new \InvalidArgumentException('Input must be string or resource.');
		}
		// Bug on older versions of PHP "Warning: exif_read_data(): Process tag(x010D=DocumentNam): Illegal components(0)" Tested with:
		// WEDOS Linux 7.3.1 (NOT OK)
		// WAMP Windows 7.3.5 (NOT OK)
		// WAMP Windows 7.4.7 (OK)
		// https://bugs.php.net/bug.php?id=77142
		$exif = @exif_read_data($input);
		if (
			$exif &&
			isset($exif['GPSLatitude']) &&
			isset($exif['GPSLongitude']) &&
			isset($exif['GPSLatitudeRef']) &&
			isset($exif['GPSLongitudeRef'])
		) {
			$betterLocationExif = new BetterLocation(
				json_encode([$exif['GPSLatitude'], $exif['GPSLatitudeRef'], $exif['GPSLongitude'], $exif['GPSLongitudeRef']]),
				Coordinates::exifToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef']),
				Coordinates::exifToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef']),
				WGS84DegreesService::class,
			);
			$betterLocationExif->setPrefixMessage('EXIF');
			return $betterLocationExif;
		} else {
			return null;
		}
	}

	public function generateMessage($withAddress = true): string
	{
		/** @var AbstractService[] $services */
		$services = [
			GoogleMapsService::class,
			MapyCzService::class,
			DuckDuckGoService::class,
			WazeService::class,
			HereWeGoService::class,
			OpenStreetMapService::class,
			OsmAndService::class,
		];
		$text = '';
		$text .= sprintf('%s <a href="%s" target="_blank">%s</a> <code>%s</code>',
			$this->prefixMessage,
			$this->generateScreenshotLink(MapyCzService::class),
			Icons::MAP_SCREEN,
			$this->__toString()
		);
		if ($this->getCoordinateSuffixMessage()) {
			$text .= ' ' . $this->getCoordinateSuffixMessage();
		}
		$text .= PHP_EOL;

		// Generate links
		$textLinks = \array_map(function (string $service) {
			return sprintf('<a href="%s" target="_blank">%s</a>',
				$this->pregeneratedLinks[$service] ?? $service::getLink($this->lat, $this->lon),
				$service::getName(true),
			);
		}, $services);
		// Add to favourites
		$textLinks[] = sprintf('<a href="%s" target="_blank">%s</a>',
			TelegramHelper::generateStart(sprintf('%s %s %s %s', StartCommand::FAVOURITE, StartCommand::FAVOURITE_ADD, $this->getLat(), $this->getLon())),
			Icons::FAVOURITE,
		);
		$text .= join(' | ', $textLinks) . PHP_EOL;

		if ($withAddress && is_null($this->address) === false) {
			$text .= $this->getAddress() . PHP_EOL;
		}

		if ($this->description) {
			$text .= $this->description . PHP_EOL;
		}

		return $text . PHP_EOL;
	}

	/** @return Types\Inline\Keyboard\Button[] */
	public function generateDriveButtons(): array
	{
		/** @var AbstractService[] $services */
		$services = [
			GoogleMapsService::class,
			WazeService::class,
			HereWeGoService::class,
			OsmAndService::class,
		];
		$buttons = [];
		foreach ($services as $service) {
			$button = new Types\Inline\Keyboard\Button();
			$button->text = sprintf('%s %s', $service::getName(true), Icons::CAR);
			$button->url = $service::getLink($this->lat, $this->lon, true);
			$buttons[] = $button;
		}
		return $buttons;
	}

	/** @return Types\Inline\Keyboard\Button[] */
	public static function generateRefreshButtons(bool $autorefreshEnabled): array
	{
		$autoRefresh = new Types\Inline\Keyboard\Button();
		if ($autorefreshEnabled) {
			$autoRefresh->text = sprintf('Autorefresh: %s enabled', Icons::ENABLED);
			$autoRefresh->callback_data = sprintf('%s %s', RefreshButton::CMD, RefreshButton::ACTION_STOP);
		} else {
			$autoRefresh->text = sprintf('Autorefresh: %s disabled', Icons::DISABLED);
			$autoRefresh->callback_data = sprintf('%s %s', RefreshButton::CMD, RefreshButton::ACTION_START);
		}
		$buttons[] = $autoRefresh;

		$manualRefresh = new Types\Inline\Keyboard\Button();
		$manualRefresh->text = sprintf('Manual refresh %s', Icons::REFRESH);
		$manualRefresh->callback_data = sprintf('%s %s', RefreshButton::CMD, RefreshButton::ACTION_REFRESH);
		$buttons[] = $manualRefresh;
		return $buttons;
	}

	public function setPrefixMessage(string $prefixMessage): void
	{
		$this->prefixMessage = $prefixMessage;
	}

	public function getPrefixMessage(): ?string
	{
		return $this->prefixMessage;
	}

	public function setInlinePrefixMessage(string $inlinePrefixMessage): void
	{
		$this->inlinePrefixMessage = $inlinePrefixMessage;
	}

	public function getInlinePrefixMessage(): ?string
	{
		return $this->inlinePrefixMessage;
	}

	/**
	 * @param string $coordinateSuffixMessage
	 */
	public function setCoordinateSuffixMessage(string $coordinateSuffixMessage): void
	{
		$this->coordinateSuffixMessage = $coordinateSuffixMessage;
	}

	public function getCoordinateSuffixMessage(): ?string
	{
		return $this->coordinateSuffixMessage;
	}

	public function getLink($class, bool $drive = false)
	{
		if ($class instanceof AbstractService === false) {
			throw new \InvalidArgumentException(sprintf('Class must be instance of "%s"', AbstractService::class));
		}
		return $class::getLink($this->lat, $this->lon, $drive);
	}

	public function getLat(): float
	{
		return $this->lat;
	}

	public function getLon(): float
	{
		return $this->lon;
	}

	public function getLatLon(): array
	{
		return [$this->lat, $this->lon];
	}

	public function __toString()
	{
		return sprintf('%F,%F', $this->lat, $this->lon);
	}

	/**
	 * @param string|null $description
	 */
	public function setDescription(?string $description): void
	{
		$this->description = $description;
	}

	public function isRefreshable(): bool
	{
		return $this->refreshable;
	}

	public function setRefreshable(bool $refreshable): void
	{
		$this->refreshable = $refreshable;
	}

	public function getStaticMapUrl(): string
	{
		$staticMap = Factory::StaticMapProxy();
		$staticMap->addMarker($this)->downloadAndCache();
		return $staticMap->getUrl();
	}

	public static function fromLatLon(float $lat, float $lon): self
	{
		return new BetterLocation(sprintf('%F,%F', $lat, $lon), $lat, $lon, WGS84DegreesService::class);
	}

	private function validateInput($input): void
	{
		if ($input instanceof \Nette\Http\UrlImmutable) {
			$this->input = $input->getAbsoluteUrl();
			$this->inputUrl = $input;
		} else if ($input instanceof \Nette\Http\Url) {
			$this->input = $input->getAbsoluteUrl();
			$this->inputUrl = new \Nette\Http\UrlImmutable($input);
		} else if (is_string($input)) {
			$this->input = $input;
			if (Strict::isUrl($input)) {
				$this->inputUrl = Strict::urlImmutable($input);
			}
		} else {
			throw new \InvalidArgumentException(sprintf('Input must be string, instance of "%s" or "%s"', \Nette\Http\Url::class, \Nette\Http\UrlImmutable::class));
		}
	}

	/**
	 * @throws InvalidLocationException
	 */
	private function validateCoords(float $lat, float $lon): void
	{
		if (Coordinates::isLat($lat) === false) {
			throw new InvalidLocationException('Latitude coordinate must be between or equal from -90 to 90 degrees.');
		}
		$this->lat = $lat;
		if (Coordinates::isLon($lon) === false) {
			throw new InvalidLocationException('Longitude coordinate must be between or equal from -180 to 180 degrees.');
		}
		$this->lon = $lon;
	}

	private function validateSourceService(string $sourceService): void
	{
		if (class_exists($sourceService) === false) {
			throw new \InvalidArgumentException(sprintf('Invalid source service: "%s".', $sourceService));
		}
		if (is_subclass_of($sourceService, AbstractService::class) === false && is_subclass_of($sourceService, AbstractService::class) === false) {
			throw new \InvalidArgumentException(sprintf('Source service has to be subclass of "%s".', AbstractService::class));
		}
		$this->sourceService = $sourceService;
	}

	private function validateSourceType(?string $sourceType = null): void
	{
		$sourceTypes = $this->sourceService::getConstants();
		if (count($sourceTypes) === 0 && $sourceType !== null) {
			throw new \InvalidArgumentException(sprintf('Service "%s" doesn\'t contain any types so $sourceType has to be null, not "%s"', $this->sourceService, $sourceType));
		}
		if (count($sourceTypes) > 0) {
			if ($sourceType === null) {
				throw new \InvalidArgumentException(sprintf('Missing source type for service "%s"', $this->sourceService));
			}
			if (in_array($sourceType, $sourceTypes) === false) {
				throw new \InvalidArgumentException(sprintf('Invalid source type "%s" for service "%s".', $sourceType, $this->sourceService));
			}
		}
		$this->sourceType = $sourceType;
	}

	private function generateDefaultPrefix(): void
	{
		$generatedPrefix = $this->sourceService::NAME;
		if ($this->sourceType) {
			$generatedPrefix .= ' ' . $this->sourceType;
		}
		if ($this->inputUrl) {
			$generatedPrefix = sprintf('<a href="%s">%s</a>', $this->inputUrl, $generatedPrefix);
		}
		$this->setPrefixMessage($generatedPrefix);
	}

	/**
	 * Generate name for newly added favourite item from as what3words with error fallback to OpenLocationCode
	 *
	 * @param float $lat
	 * @param float $lon
	 * @return string
	 * @throws \Exception
	 */
	public static function generateFavouriteName(float $lat, float $lon): string
	{
		try {
			$result = Factory::WhatThreeWords()->convertTo3wa($lat, $lon);
			if ($result) {
				return sprintf('///%s', $result['words']);
			} else {
				return OpenLocationCode::encode($lat, $lon);
			}
		} catch (\Exception $exception) {
			return OpenLocationCode::encode($lat, $lon);
		}
	}
}
