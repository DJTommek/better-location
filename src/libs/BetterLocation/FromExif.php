<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use App\Exif\Exif;
use App\Exif\ExifException;
use App\Icons;
use App\Utils\Formatter;
use Tracy\Debugger;

/**
 * Load EXIF information from image and generate BetterLocation
 *
 * @template Input of string|resource Path or URL link to file or resource (see https://php.net/manual/en/function.exif-read-data.php)
 */
class FromExif
{
	/**
	 * What is the limit if location is still considered accurate in meters.
	 */
	private const PRECISION_THRESHOLD = 50;

	/**
	 * @var Input
	 * @readonly
	 */
	private $input;
	public Exif $exif;
	public ?BetterLocation $location = null;

	/**
	 * @param Input $input
	 */
	public function __construct(string $input)
	{
		if (is_string($input) === false && is_resource($input) === false) {
			throw new \InvalidArgumentException('Input must be string or resource.');
		}

		$this->input = $input;
	}

	public function run(
		?string $linkInMessage = null,
		string $sourceService = WGS84DegreesService::class,
		?string $sourceType = null,
	): self {
		try {
			$this->exif = new Exif($this->input);
		} catch (ExifException $exception) {
			if (str_contains($exception->getMessage(), 'File not supported')) {
				return $this; // Do not log as error
			}

			Debugger::log($exception, Debugger::WARNING);
			return $this;
		}

		$coords = $this->exif->getCoordinates();
		if ($coords === null) {
			return $this;
		}

		$this->location = new BetterLocation(
			'EXIF ' . $coords->getLatLon(),
			$coords->getLat(),
			$coords->getLon(),
			$sourceService,
			$sourceType,
		);

		if ($linkInMessage !== null) {
			$this->location->setPrefixMessage(sprintf('<a href="%s" target="_blank">EXIF</a>', htmlentities($linkInMessage)));
		} else {
			$this->location->setPrefixMessage('EXIF');
		}

		$this->appendProcessingMethodIcon();
		$this->addPrecisionWarning();

		return $this;
	}

	private function appendProcessingMethodIcon(): void
	{
		$processingMethod = $this->exif->getGpsProcessingMethod();
		if ($processingMethod === null) {
			return;
		}

		// Based on values described on https://exiftool.org/TagNames/GPS.html
		$processingMethodIcon = match (mb_strtoupper($processingMethod)) {
			'GPS' => Icons::LOCATION_GPS,
			'CELLID' => Icons::LOCATION_CELL_ID,
			'WLAN' => Icons::LOCATION_WLAN,
			default => null, // 'MANUAL' and any non-standard value
		};

		if ($processingMethodIcon === null) {
			return;
		}

		$this->location->appendToPrefixMessage($processingMethodIcon);
	}

	private function addPrecisionWarning(): void
	{
		$locationPrecision = $this->exif->getCoordinatesPrecision();
		if ($locationPrecision === null) {
			return;
		}
		if ($locationPrecision <= self::PRECISION_THRESHOLD) {
			return;
		}
		$this->location->addDescription(
			sprintf('%s Location accuracy is %s.', Icons::WARNING, Formatter::distance($locationPrecision)),
			Description::KEY_PRECISION,
		);
	}
}
