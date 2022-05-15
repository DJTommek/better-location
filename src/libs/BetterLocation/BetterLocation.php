<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\MapyCzService;
use App\Config;
use App\Factory;
use App\Geonames\Geonames;
use App\Geonames\Types\TimezoneType;
use App\Icons;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\Events\Button\RefreshButton;
use App\TelegramCustomWrapper\Events\Command\StartCommand;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Coordinates;
use App\Utils\CoordinatesInterface;
use App\Utils\Utils;
use App\Utils\Strict;
use JetBrains\PhpStorm\Pure;
use maxh\Nominatim\Exceptions\NominatimException;
use Nette\Http\UrlImmutable;
use Nette\Http\Url;
use OpenLocationCode\OpenLocationCode;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram\Types;

class BetterLocation implements CoordinatesInterface
{
	private Coordinates $coords;
	private ?string $description = null;
	private string $prefixMessage;
	/** Can be ommited if is the same as $prefixMessage */
	private ?string $inlinePrefixMessage = null;
	private ?string $coordinateSuffixMessage = null;
	private ?string $address = null;
	/** String representation of input (including links) */
	private string $input;
	/** Input as link, if is possible */
	private ?UrlImmutable $inputUrl = null;
	/** String representation of child classname of AbstractService::class */
	private AbstractService|string $sourceService;
	/** If service class has multiple type of output, source type must be included */
	private ?string $sourceType;
	/** Pregenerated link for service(s) if available */
	private array $pregeneratedLinks = [];
	/** Can location change with same input? */
	private bool $refreshable = false;
	private ?TimezoneType $timezoneData = null;
	private ?UrlImmutable $staticMapUrl = null;

	/**
	 * @param string $sourceService has to be name of class extending \BetterLocation\Service\AbstractService
	 * @param ?string $sourceType if $sourceService class has multiple type of source, this must be included
	 * @throws InvalidLocationException|Service\Exceptions\NotSupportedException
	 */
	public function __construct(UrlImmutable|Url|string $input, float $lat, float $lon, string $sourceService, ?string $sourceType = null)
	{
		$this->validateInput($input);
		$this->validateCoords($lat, $lon);
		$this->validateSourceService($sourceService);
		$this->validateSourceType($sourceType);
		$this->generateDefaultPrefix();

		// pregenerate link for MapyCz if contains source and ID (@see issue #17)
		if ($this->inputUrl && $this->sourceService === MapyCzService::class && $this->sourceType === MapyCzService::TYPE_PLACE_ID
			&& // extra check if original URL really contain these parameters (might be missing for shorted url, see issue #73)
			$this->inputUrl->getQueryParameter('id') && $this->inputUrl->getQueryParameter('source')
		) {
			$generatedUrl = new \Nette\Http\Url(MapyCzService::getShareLink($this->getLat(), $this->getLon()));
			$generatedUrl->setQueryParameter('id', $this->inputUrl->getQueryParameter('id'));
			$generatedUrl->setQueryParameter('source', $this->inputUrl->getQueryParameter('source'));
			$this->pregeneratedLinks[MapyCzService::class] = $generatedUrl->getAbsoluteUrl();
		}
	}

	public function getName(): string
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

	public function generateScreenshotLink(string $serviceClass): string
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

	public function setAddress(string $address): void
	{
		$this->address = $address;
	}

	public function getAddress(): ?string
	{
		return $this->address;
	}

	public function hasAddress(): bool
	{
		return !is_null($this->address);
	}

	public function generateAddress(): ?string
	{
		if (is_null($this->address)) {
			try {
				if ($result = \App\Nominatim\Nominatim::reverse($this->getLat(), $this->getLon())) {
					$this->address = $result['display_name'];
				}
			} catch (NominatimException | \GuzzleHttp\Exception\GuzzleException $exception) {
				Debugger::log($exception, Debugger::EXCEPTION);
			}
		}
		return $this->address;
	}

	public function generateDateTimeZone(): ?TimezoneType
	{
		if (is_null($this->timezoneData)) {
			try {
				$this->timezoneData = Geonames::timezone($this->getLat(), $this->getLon());
			} catch (\GuzzleHttp\Exception\GuzzleException $exception) {
				Debugger::log($exception, Debugger::EXCEPTION);
			}
		}
		return $this->timezoneData;
	}

	/** Load and save elevation from API */
	public function generateElevation(): ?float
	{
		if (is_null($this->coords->getElevation())) {
			try {
				Factory::OpenElevation()->fill($this->coords);
			} catch (\Exception $exception) {
				Debugger::log($exception, Debugger::EXCEPTION);
			}
		}
		return $this->coords->getElevation();
	}

	public function getTimezoneData(): ?TimezoneType
	{
		return $this->timezoneData;
	}

	/**
	 * @param string|resource $input Path or URL link to file or resource (see https://php.net/manual/en/function.exif-read-data.php)
	 * @return BetterLocation|null
	 * @throws InvalidLocationException
	 */
	public static function fromExif($input): ?BetterLocation
	{
		if (is_string($input) === false && is_resource($input) === false) {
			throw new \InvalidArgumentException('Input must be string or resource.');
		}
		try {
			$exif = Utils::exifReadData($input);
		} catch (\RuntimeException $exception) {
			Debugger::log($exception, Debugger::WARNING);
			return null;
		}
		if (
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

	public function generateMessage(BetterLocationMessageSettings $settings): string
	{
		$text = sprintf('%s <a href="%s" target="_blank">%s</a> ',
			$this->prefixMessage,
			$this->generateScreenshotLink($settings->getScreenshotLinkService()),
			Icons::MAP_SCREEN,
		);

		// Generate copyable text representing location
		$text .= implode(' | ', array_map(function ($service) {
			return sprintf('<code>%s</code>', $service::getShareText($this->getLat(), $this->getLon()));
		}, $settings->getTextServices()));

		if ($this->getCoordinateSuffixMessage()) {
			$text .= ' ' . $this->getCoordinateSuffixMessage();
		}
		$text .= PHP_EOL;

		// Generate links
		$textLinks = \array_map(function (string $service) {
			return sprintf('<a href="%s" target="_blank">%s</a>',
				$this->pregeneratedLinks[$service] ?? $service::getShareLink($this->getLat(), $this->getLon()),
				$service::getName(true),
			);
		}, $settings->getLinkServices());

		$text .= join(' | ', $textLinks) . PHP_EOL;

		if ($settings->showAddress() && is_null($this->address) === false) {
			$text .= $this->getAddress() . PHP_EOL;
		}

		if ($this->description) {
			$text .= $this->description . PHP_EOL;
		}

		return $text . PHP_EOL;
	}

	/** @return Types\Inline\Keyboard\Button[] */
	public function generateDriveButtons(BetterLocationMessageSettings $settings): array
	{
		$buttons = [];
		foreach ($settings->getButtonServices() as $service) {
			$button = new Types\Inline\Keyboard\Button();
			$button->text = sprintf('%s %s', $service::getName(true), Icons::CAR);
			$button->url = $service::getDriveLink($this->getLat(), $this->getLon());
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

	#[Pure] public function getLat(): float
	{
		return $this->coords->getLat();
	}

	#[Pure] public function getLon(): float
	{
		return $this->coords->getLon();
	}

	#[Pure] public function getLatLon(): array
	{
		return [$this->getLat(), $this->getLon()];
	}

	public function __toString(): string
	{
		return $this->coords->__toString();
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

	public function getStaticMapUrl(): UrlImmutable
	{
		if (is_null($this->staticMapUrl)) {
			$this->staticMapUrl = StaticMapProxy::fromLocations($this)->publicUrl();
		}
		return $this->staticMapUrl;
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
		$this->coords = new Coordinates($lat, $lon);
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
		} catch (\Exception) {
			return OpenLocationCode::encode($lat, $lon);
		}
	}

	public function getCoordinates(): Coordinates
	{
		return $this->coords;
	}

	#[Pure] public function key(): string
	{
		return $this->coords->key();
	}

	public function getLink(string $format = null): UrlImmutable
	{
		$result = Config::getAppUrl('/' . $this->key());
		return $result->withQueryParameter('format', $format);
	}
}
