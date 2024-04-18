<?php declare(strict_types=1);

namespace App\Exif;

use DJTommek\Coordinates\CoordinatesImmutable;

/**
 * @template ExifData of array<string, mixed>
 */
class Exif implements \JsonSerializable
{
	/**
	 * @var ExifData
	 */
	private readonly array $raw;
	private ?CoordinatesImmutable $coordinates = null;
	private bool $coordinatesProcessed = false;

	public function __construct(string $input)
	{
		$this->raw = self::exifReadData($input);
	}

	/**
	 * Return true, if if all provided keys are available in EXIF data, false otherwise.
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

	private function processCoordinates(): ?CoordinatesImmutable
	{
		if ($this->has('GPSLatitude', 'GPSLongitude', 'GPSLatitudeRef', 'GPSLongitudeRef') === false) {
			return null;
		}

		$lat = ExifUtils::exifToDecimal($this->raw['GPSLatitude'], $this->raw['GPSLatitudeRef']);
		$lon = ExifUtils::exifToDecimal($this->raw['GPSLongitude'], $this->raw['GPSLongitudeRef']);

		return CoordinatesImmutable::safe($lat, $lon);
	}

	/**
	 * Read the EXIF headers from and image file. Works similarly as native exif_read_data()
	 * but throws exception if notice or warning occure.
	 *
	 * @param string $input Path or URL
	 * @return ExifData
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
