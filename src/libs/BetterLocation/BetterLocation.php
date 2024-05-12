<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Address\Address;
use App\Address\AddressInterface;
use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\MapyCzService;
use App\Config;
use App\Factory;
use App\Geonames\GeonamesApiException;
use App\Geonames\Types\TimezoneType;
use App\Icons;
use App\MiniCurl\Exceptions\TimeoutException;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\Events\Button\RefreshButton;
use App\Utils\Coordinates;
use App\Utils\CoordinatesInterface;
use App\Utils\Formatter;
use App\Utils\Strict;
use maxh\Nominatim\Exceptions\NominatimException;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;
use OpenLocationCode\OpenLocationCode;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram\Types;

class BetterLocation implements CoordinatesInterface
{
	private Coordinates $coords;
	/**
	 * @var list<Description>
	 */
	private array $descriptions = [];
	private string $prefixMessage;
	/** Can be ommited if is the same as $prefixMessage */
	private ?string $inlinePrefixMessage = null;
	private ?string $coordinateSuffixMessage = null;
	private ?Address $address = null;
	/** String representation of input (including links) */
	private string $input;
	/** Input as link, if is possible */
	private ?UrlImmutable $inputUrl = null;
	/**
	 * @var class-string<AbstractService>
	 */
	private string $sourceService;
	/** If service class has multiple type of output, source type must be included */
	private ?string $sourceType;
	/**
	 * Pregenerated link for service(s) if available
	 *
	 * @var array<class-string<AbstractService>,string>
	 */
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

	public function getInput(): string
	{
		return $this->input;
	}

	public function getSourceType(): ?string
	{
		return $this->sourceType;
	}

	public function getSourceService(): string
	{
		return $this->sourceService;
	}

	/**
	 * @return array{lat: float, lon: float, service: string}
	 */
	public function export(): array
	{
		return [
			'lat' => $this->getLat(),
			'lon' => $this->getLon(),
			'service' => strip_tags($this->getPrefixMessage()),
		];
	}

	public function setAddress(string|AddressInterface|null $address): void
	{
		if ($address === null) {
			$this->address = null;
			return;
		}

		if (is_string($address)) {
			$this->address = new Address($address);
			return;
		}

		$this->address = $address->getAddress();
	}

	public function getAddress(): ?string
	{
		return $this->address?->toString(true);
	}

	public function hasAddress(): bool
	{
		return $this->address?->getAddress() !== null;
	}

	public function generateAddress(): void
	{
		if ($this->hasAddress()) {
			return;
		}

		if (Config::isGoogleGeocodingApi()) {
			try {
				$googleGeocoding = Factory::googleGeocodingApi();
				$result = $googleGeocoding->reverse($this);
				if ($result?->getAddress() !== null) {
					$this->setAddress($result);
					return;
				}
			} catch (\GuzzleHttp\Exception\GuzzleException $exception) {
				Debugger::log($exception, Debugger::EXCEPTION);
			}
		}

		try {
			$result = \App\Nominatim\Nominatim::reverse($this);
			if ($result?->getAddress() !== null) {
				$this->setAddress($result);
				return;
			}
		} catch (NominatimException|\GuzzleHttp\Exception\GuzzleException $exception) {
			Debugger::log($exception, Debugger::EXCEPTION);
		}
	}

	public function generateDateTimeZone(): ?TimezoneType
	{
		if (is_null($this->timezoneData)) {
			try {
				$this->timezoneData = Factory::geonames()->timezone($this->getLat(), $this->getLon());
			} catch (GeonamesApiException $exception) {
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
				Factory::openElevation()->fill($this->coords);
			} catch (TimeoutException) {
				Debugger::log('Unable to fill coordinates elevation, request timeouted.', Debugger::WARNING);
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

	public function generateMessage(BetterLocationMessageSettings $settings): string
	{
		$generator = new MessageGenerator();
		return $generator->generate(
			$this->coords,
			$settings,
			$this->prefixMessage,
			$this->coordinateSuffixMessage,
			$this->pregeneratedLinks,
			$this->descriptions,
			$this->address,
		);
	}

	/** @return Types\Inline\Keyboard\Button[] */
	public function generateDriveButtons(BetterLocationMessageSettings $settings): array
	{
		$buttons = [];
		foreach ($settings->getButtonServices() as $service) {
			$driveLink = $service::getDriveLink($this->getLat(), $this->getLon());
			if ($driveLink !== null) {
				$button = new Types\Inline\Keyboard\Button();
				$button->text = sprintf('%s %s', $service::getName(true), Icons::CAR);
				$button->url = $service::getDriveLink($this->getLat(), $this->getLon());
				$buttons[] = $button;
			}
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

	public function appendToPrefixMessage(string $suffix): self
	{
		$this->prefixMessage .= $suffix;
		return $this;
	}

	public function prependToPrefixMessage(string $prefix): self
	{
		$this->prefixMessage = $prefix . $this->prefixMessage;
		return $this;
	}

	public function getPrefixMessage(): string
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

	public function getLat(): float
	{
		return $this->coords->getLat();
	}

	public function getLon(): float
	{
		return $this->coords->getLon();
	}

	/**
	 * @return array{float, float}
	 */
	public function getLatLon(): array
	{
		return [$this->getLat(), $this->getLon()];
	}

	public function __toString(): string
	{
		return $this->coords->__toString();
	}

	/**
	 * @deprecated use ->addDescription() instead
	 */
	public function setDescription(?string $description): void
	{
		if ($description !== null) {
			$this->addDescription($description);
		}
	}

	/**
	 * Set or append description.
	 *
	 * @param string|int|null $key Set null to add new description.
	 *    If type is int or string is used, it will store on specific position. If already exists, will be overwritten.
	 */
	public function addDescription(string $description, string|int|null $key = null): void
	{
		if ($key === null) {
			$this->descriptions[] = new Description($description);
			return;
		}

		$storedDescription = $this->getDescription($key);
		if ($storedDescription === null) {
			$this->descriptions[] = new Description($description, $key);
			return;
		}

		$storedDescription->content = $description;
	}

	public function hasDescription(string $key): bool
	{
		return $this->getDescription($key) !== null;
	}

	public function clearDescriptions(): void
	{
		$this->descriptions = [];
	}

	public function getDescription(string $key): ?Description
	{
		foreach ($this->descriptions as $description) {
			assert($description instanceof Description);
			if ($description->key === $key) {
				return $description;
			}
		}
		return null;
	}

	/**
	 * @return list<Description>
	 */
	public function getDescriptions(): array
	{
		return $this->descriptions;
	}

	public function isRefreshable(): bool
	{
		return $this->refreshable;
	}

	public function setRefreshable(bool $refreshable): void
	{
		$this->refreshable = $refreshable;
	}

	public function getStaticMapUrl(): ?UrlImmutable
	{
		if (is_null($this->staticMapUrl)) {
			$staticMapProxyFactory = Factory::staticMapProxyFactory();
			$staticMapProxy = $staticMapProxyFactory->fromLocations([$this]);
			if ($staticMapProxy !== null) {
				$this->staticMapUrl = $staticMapProxy->publicUrl();
			}
		}
		return $this->staticMapUrl;
	}

	/**
	 * @param class-string<AbstractService> $service
	 */
	public static function fromLatLon(
		float $lat,
		float $lon,
		string $service = WGS84DegreesService::class,
		?string $type = null,
	): self {
		return new BetterLocation(sprintf('%F,%F', $lat, $lon), $lat, $lon, $service, $type);
	}

	public static function fromCoords(
		\DJTommek\Coordinates\CoordinatesInterface $coordinates,
		string $service = WGS84DegreesService::class,
		?string $type = null,
	): self {
		return self::fromLatLon($coordinates->getLat(), $coordinates->getLon(), $service, $type);
	}

	private function validateInput(UrlImmutable|Url|string $input): void
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
		$this->setPrefixTextInLink('', true, true);
	}

	/**
	 * Override prefix by generating default link (inputUrl) by providing custom text.
	 * Optionally prepend with service name and service type
	 */
	public function setPrefixTextInLink(string $text, bool $usePrefixServiceName = true, bool $usePrefixServiceType = false): self
	{
		$texts = [];
		if ($usePrefixServiceName === true) {
			$texts[] = $this->sourceService::getName();
		}
		if ($this->sourceType !== null && $usePrefixServiceType === true) {
			$texts[] = $this->sourceType;
		}
		$texts[] = $text;
		$htmlText = trim(implode(' ', array_filter($texts)));
		assert($htmlText !== '');
		$htmlTag = Formatter::htmlLink((string)$this->inputUrl, $htmlText);
		$this->setPrefixMessage($htmlTag);
		return $this;
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
			$result = Factory::whatThreeWords()?->convertTo3wa($lat, $lon);
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

	public function key(): string
	{
		return $this->coords->key();
	}

	public function getLink(string $format = null): UrlImmutable
	{
		$result = Config::getAppUrl('/' . $this->key());
		return $result->withQueryParameter('format', $format);
	}
}
