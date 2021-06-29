<?php
declare(strict_types=1);

namespace App\Utils;

/**
 * Decode Ge0 used in maps.me or omaps.app and  as sharing coordinates
 *
 * @author Alex Zolotarev from Minsk, Belarus <alex@maps.me> - Original author of decode functions and Algorithm (https://github.com/mapsme/ge0_url_decoder)
 * @author Tomas Palider (DJTommek) <tomas@palider.cz> - Refactored to class, added encoding, optimized,
 * @link https://github.com/mapsme/omim/tree/1892903b63f2c85b16ed4966d21fe76aba06b9ba/ge0 original source code written in C++
 */
class Ge0
{
	private const MAX_POINT_BYTES = 10;
	private const MAX_COORD_BITS = self::MAX_POINT_BYTES * 3;

	private const WGS84_PRECISION = 6;

	private const BASE64_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

	/** @var string */
	public $code;
	/** @var float */
	public $lat;
	/** @var float */
	public $lon;
	/** @var float */
	public $zoom;

	private function __construct()
	{
		// Create via self::decode() or self::encode()
	}

	public static function decode(string $code): self
	{
		$self = new self();
		$self->code = $code;
		$self->zoom = self::decodeCodeToZoom($code);
		[$self->lat, $self->lon] = self::decodeCodeToLatLon($code);
		return $self;
	}

	public static function encode(float $lat, float $lon, float $zoom = 15): self
	{
		$self = new self();
		$self->lat = $lat;
		$self->lon = $lon;
		$self->zoom = $zoom;
		$self->code = self::zoomToCode($zoom) . self::latLonToCode($lat, $lon);
		return $self;
	}

	/**
	 * Map latitude: [-90, 90] -> [0, maxValue]
	 */
	private static function latToInt(float $lat, int $maxValue): int
	{
		// M = maxValue, L = maxValue-1
		// lat: -90                        90
		//   x:  0     1     2       L     M
		//       |--+--|--+--|--...--|--+--|
		//       000111111222222...LLLLLMMMM

		$x = ($lat + 90) / 180 * $maxValue;
		return $x < 0 ? 0 : ($x > $maxValue ? $maxValue : (int)($x + 0.5));
	}

	// Make lon in [-180, 180)
	private static function lonIn180180(float $lon): float
	{
		if ($lon >= 0) {
			return fmod($lon + 180, 360) - 180;
		}

		// Handle the case of l = -180
		$l = fmod($lon - 180, 360) + 180;
		return $l < 180 ? $l : $l - 360;
	}

// Map longitude: [-180, 180) -> [0, maxValue]
	private static function lonToInt(float $lon, int $maxValue): int
	{
		$x = (self::lonIn180180($lon) + 180) / 360 * ($maxValue + 1) + 0.5;
		return ($x <= 0 || $x >= $maxValue + 1) ? 0 : (int)$x;
	}

	private static function zoomToCode(float $zoom): string
	{
		$zoomRaw = ($zoom <= 4 ? 0 : ($zoom >= 19.75 ? 63 : (int)(($zoom - 4) * 4)));
		return self::BASE64_ALPHABET[$zoomRaw];
	}

	private static function latLonToCode(float $lat, float $lon): string
	{
		$latI = self::latToInt($lat, (1 << self::MAX_COORD_BITS) - 1);
		$lonI = self::lonToInt($lon, (1 << self::MAX_COORD_BITS) - 1);

		$result = [];
		// bits - 3 and bytes - 1 is because of skipping extra character dedicated to zoom
		for ($i = 0, $shift = self::MAX_COORD_BITS - 3; $i < self::MAX_POINT_BYTES - 1; ++$i, $shift -= 3) {
			$latBits = $latI >> $shift & 7;
			$lonBits = $lonI >> $shift & 7;

			$nextByte =
				($latBits >> 2 & 1) << 5 |
				($lonBits >> 2 & 1) << 4 |
				($latBits >> 1 & 1) << 3 |
				($lonBits >> 1 & 1) << 2 |
				($latBits & 1) << 1 |
				($lonBits & 1);

			$result[$i] = self::BASE64_ALPHABET[$nextByte];
		}
		return implode('', $result);
	}

	private static function decodeCodeToZoom(string $code): float
	{
		$base64ReverseArray = self::base64reversed();
		$zoomRaw = substr($code, 0, 1);
		$zoomCode = ord($zoomRaw);
		$zoomDecoded = $base64ReverseArray[$zoomCode];
		if ($zoomDecoded > 63) {
			throw new \InvalidArgumentException(sprintf('Invalid code "%s": zoom is not valid', $code));
		}
		return ($zoomDecoded / 4) + 4;
	}

	private static function decodeCodeToLatLon(string $code): array
	{
		$base64ReverseArray = self::base64reversed();
		$latLonStr = substr($code, 1);
		$latLonBytes = strlen($latLonStr);
		$lat = 0;
		$lon = 0;
		for ($i = 0, $shift = self::MAX_COORD_BITS - 3; $i < $latLonBytes; $i++, $shift -= 3) {
			$a = $base64ReverseArray[ord($latLonStr[$i])];
			$lat1 = ((($a >> 5) & 1) << 2 |
				(($a >> 3) & 1) << 1 |
				(($a >> 1) & 1));
			$lon1 = ((($a >> 4) & 1) << 2 |
				(($a >> 2) & 1) << 1 |
				($a & 1));
			$lat |= $lat1 << $shift;
			$lon |= $lon1 << $shift;
		}

		$middleOfSquare = 1 << (3 * (self::MAX_POINT_BYTES - $latLonBytes) - 1);
		$lat += $middleOfSquare;
		$lon += $middleOfSquare;

		$lat = round($lat / ((1 << self::MAX_COORD_BITS) - 1) * 180 - 90, self::WGS84_PRECISION);
		$lon = round($lon / (1 << self::MAX_COORD_BITS) * 360 - 180, self::WGS84_PRECISION);

		if ($lat <= -90 || $lat >= 90) {
			throw new \InvalidArgumentException(sprintf('Invalid code "%s", : latitude coordinate is out of bounds', $code));
		}
		if ($lon <= -180 || $lon >= 180) {
			throw new \InvalidArgumentException(sprintf('Invalid code "%s": longitude coordinate is out of bounds', $code));
		}
		return [$lat, $lon];
	}

// This sample php code decodes ge0 urls which MAPS.ME app uses for sharing coordinates.
//

// Returns array(lat, lon, zoom) or empty array in the case of error
	/**
	 * @param $input
	 * @return array<float> lat, lon, zoom
	 */
	public function DecodeGe0LatLonZoom($input): array
	{
		$this->zoom = $this->decodeCodeToZoom($input);
		[$this->lat, $this->lon] = $this->decodeCodeToLatLon($input);
		return array($this->lat, $this->lon, $this->zoom);
	}

	private static function base64reversed(): array
	{
		$result = [];
		for ($i = 0; $i < strlen(self::BASE64_ALPHABET); $i++) {
			$char = self::BASE64_ALPHABET[$i];
			$charCode = ord($char);
			$result[$charCode] = $i;
		}
		return $result;
	}
}
