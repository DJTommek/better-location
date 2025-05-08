<?php declare(strict_types=1);

namespace App\Exif;

use DJTommek\Coordinates\CoordinatesImmutable;

/**
 * @template ExifData of array<string, mixed>
 */
class Exif implements \JsonSerializable
{
	/**
	 * Also called as 'GPSHPositioningError' or 'GPS Horizontal Positioning Error' (in exiftool)
	 * @link https://exiftool.org/TagNames/GPS.html
	 */
	private const TAG_GPS_HORIZONZAL_POSITIONING_ERROR = 'UndefinedTag:0x001F';

	/**
	 * According EXIF specification it is 'GPSProcessingMethod' but in PHP parser it is called 'GPSProcessingMode'
	 */
	private const TAG_GPS_PROCESSING_METHOD = 'GPSProcessingMode';

	/**
	 * @var ExifData
	 */
	private readonly array $raw;
	private ?CoordinatesImmutable $coordinates = null;
	private bool $coordinatesProcessed = false;

	public function __construct(string $input)
	{
		if (!self::isAvailable()) {
			throw new ExifException('Internal library to read EXIF data is not available.');
		}

		$this->raw = self::exifReadData($input);
	}

	/**
	 * Return true, if if all provided keys are available in EXIF data, false otherwise.
	 *
	 * @param self::TAG_*|string ...$keys
	 */
	public function has(string ...$keys): bool
	{
		foreach ($keys as $key) {
			if (array_key_exists($key, $this->raw) === false) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param self::TAG_*|string $key
	 */
	public function get(string $key): mixed
	{
		return $this->raw[$key] ?? null;
	}

	/**
	 * @return ExifData
	 */
	public function getAll(): array
	{
		return $this->raw;
	}

	public function hasCoordinates(): bool
	{
		return $this->getCoordinates() !== null;
	}

	public function getCoordinates(): ?CoordinatesImmutable
	{
		if ($this->coordinatesProcessed === false) {
			$this->coordinates = $this->processCoordinates();
			$this->coordinatesProcessed = true;
		}

		return $this->coordinates;
	}

	/**
	 * Returns GPS coordinates precision in meters if available, null otherwise.
	 */
	public function getCoordinatesPrecision(): ?float
	{
		$precisionRaw = $this->get(self::TAG_GPS_HORIZONZAL_POSITIONING_ERROR);
		if ($precisionRaw === null) {
			return null;
		}

		try {
			return ExifUtils::floatConvert($precisionRaw);
		} catch (\Throwable) {
			// Swallow, no valid information is available
		}
		return null;
	}

	/**
	 *
	 * GPS Processing method should return one of these values according EXIF specifications, but it can be anything.
	 * Raw format contains ASCII prefix, which is removed in this method. Use ->get() method if you want raw value.
	 * Expected values: 'GPS', 'CELLID', 'WLAN' or 'MANUAL'
	 *
	 * @link https://exiftool.org/TagNames/GPS.html
	 */
	public function getGpsProcessingMethod(): ?string
	{
		$value = $this->raw[self::TAG_GPS_PROCESSING_METHOD] ?? null;
		if ($value === null) {
			return null;
		}

		return str_starts_with($value, "ASCII\u{0000}\u{0000}\u{0000}")
			? mb_substr($value, 8)
			: $value;
	}

	private function processCoordinates(): ?CoordinatesImmutable
	{
		if ($this->has('GPSLatitude', 'GPSLongitude') === false) {
			return null;
		}

		$gpsLatitudeRef = $this->get('GPSLatitudeRef') ?? ExifUtils::NORTH;
		$gpsLongitudeRef = $this->get('GPSLongitudeRef') ?? ExifUtils::EAST;

		$lat = ExifUtils::exifToDecimal($this->get('GPSLatitude'), $gpsLatitudeRef);
		$lon = ExifUtils::exifToDecimal($this->get('GPSLongitude'), $gpsLongitudeRef);

		return CoordinatesImmutable::safe($lat, $lon);
	}

	public static function isAvailable(): bool
	{
		return function_exists('exif_read_data');
	}

	/**
	 * Read the EXIF headers from and image file. Works similarly as native exif_read_data()
	 * but throws exception if notice or warning occure.
	 *
	 * @param string $input Path, URL or data as pseudo-url
	 * @return ExifData
	 *
	 * @example Exif::exifReadData(__DIR__ . '/some/path.jpg');
	 * @example Exif::exifReadData('https://tomas.palider.cz/profile-photo-original.jpg');
	 * @example Exif::exifReadData('data://image/jpeg;base64,' . base64_encode($imageAsString)));
	 *
	 * @throws ExifException No file, unsupported file, ...
	 * @see https://www.php.net/manual/en/function.exif-read-data.php
	 */
	public static function exifReadData(string $input): array
	{
		$result = @\exif_read_data($input); // @ is escalated to exception
		if ($result === false) {
			$lastError = error_get_last();
			throw new ExifException(sprintf('Unable to read exif data: "%s"',
				$lastError['message'] ?? 'unknown error'),
			);
		}

		return $result;
	}

	/**
	 * @return ExifData
	 */
	public function jsonSerialize(): array
	{
		return $this->raw;
	}
}
