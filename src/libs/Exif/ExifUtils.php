<?php declare(strict_types=1);

namespace App\Exif;

class ExifUtils
{
	public const NORTH = 'N';
	public const SOUTH = 'S';
	public const EAST = 'E';
	public const WEST = 'W';

	/**
	 * Convert EXIF format of coordinates to decimal
	 *
	 * @see https://en.wikipedia.org/wiki/Geotagging
	 * @example exifGpsToDecimal(['57/1', '38/1', '5683/100'], S) === -57.64911
	 * @param string[] $exifCoord
	 */
	public static function exifToDecimal(array $exifCoord, string $hemisphere): float
	{
		$degrees = count($exifCoord) > 0 ? self::gpsSubIFDToFloat($exifCoord[0]) : 0;
		$minutes = count($exifCoord) > 1 ? self::gpsSubIFDToFloat($exifCoord[1]) : 0;
		$seconds = count($exifCoord) > 2 ? self::gpsSubIFDToFloat($exifCoord[2]) : 0;

		return self::flip($hemisphere) * ($degrees + $minutes / 60 + $seconds / 3600);
	}

	/**
	 * Convert rational number in the GPS sub-IFD to float
	 *
	 * @see https://en.wikipedia.org/wiki/Geotagging
	 * @example gpsSubIFDToFloat('5683/100') === 56.83
	 */
	public static function gpsSubIFDToFloat(string $coordPart): float
	{
		if (preg_match('/^([0-9]+)\/([1-9][0-9]*)$/', $coordPart, $matches)) {
			return (float)$matches[1] / (float)$matches[2];
		} else {
			throw new ExifException(sprintf('Provided part of coordination "%s" is not valid.', $coordPart));
		}
	}

	/**
	 * Get info if decimal coordinates should be negative
	 *
	 * @return int 1|-1
	 */
	private static function flip(string $hemisphere): int
	{
		$hemisphere = mb_strtoupper($hemisphere);
		if (in_array($hemisphere, [self::NORTH, self::EAST, '', '+'], true)) {
			return 1;
		} else if (in_array($hemisphere, [self::WEST, self::SOUTH, '-'], true)) {
			return -1;
		} else {
			throw new ExifException('Invalid hemisphere');
		}
	}
}
