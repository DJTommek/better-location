<?php declare(strict_types=1);

namespace App\Utils;

use DJTommek\Coordinates\CoordinatesImmutable;

/**
 * Converter between WGS84 and UTM coordinate systems.
 *
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 *
 * Various sources, on which is this class based
 * @see https://eo.wikipedia.org/wiki/Modulo:Coordinates Code for WGS84 to URM
 * @see https://stackoverflow.com/a/18336137 Code for UTM to WGS84
 * @see https://gist.github.com/degerstrom/5180824 Inspiration
 * @see https://en.wikipedia.org/wiki/Universal_Transverse_Mercator_coordinate_system
 * @see https://www.usgs.gov/faqs/how-are-utm-coordinates-measured-usgs-topographic-maps
 * @see https://www.maptools.com/tutorials/utm/details
 * @see https://gist.github.com/degerstrom/5180824
 */
class UTM implements \DJTommek\Coordinates\CoordinatesInterface
{
	public const BANDS_NORTH = ['X', 'W', 'V', 'U', 'T', 'S', 'R', 'Q', 'P', 'N'];
	public const BANDS_SOUTH = ['C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M'];

	private CoordinatesImmutable $coordinates;

	public readonly Hemisphere $hemisphere;

	public function __construct(
		public readonly int $zoneNumber,
		public readonly string $zoneBand,
		public readonly float $easting,
		public readonly float $northing,
	) {
		if ($this->zoneNumber < 0 || $this->zoneNumber > 60) {
			throw new \InvalidArgumentException(sprintf('Zone number "%d" is out of allowed range.', $this->zoneNumber));
		}

		// Easting and northing limits are just approximate based on Wikipedia:
		// https://en.wikipedia.org/wiki/Universal_Transverse_Mercator_coordinate_system
		if ($this->easting < 100_000 || $this->easting >= 900_000) {
			throw new \InvalidArgumentException(sprintf('Easting "%s" is out of allowed range.', $this->easting));
		}
		if ($this->northing < 0 || $this->northing >= 10_000_000) {
			throw new \InvalidArgumentException(sprintf('Northing "%s" is out of allowed range.', $this->northing));
		}

		if (in_array($this->zoneBand, self::BANDS_NORTH, true)) {
			$this->hemisphere = Hemisphere::NORTH;
		} else if (in_array($this->zoneBand, self::BANDS_SOUTH, true)) {
			$this->hemisphere = Hemisphere::SOUTH;
		} else {
			throw new \InvalidArgumentException(sprintf('UTM Zone band "%s" is not valid.', $this->zoneBand));
		}
	}

	/**
	 * @TODO extract into service
	 */
	public static function fromString(string $input): self
	{
		[$zone, $easting, $northing] = explode(' ', $input);
		return new self(
			(int)substr($zone, 0, 2),
			substr($zone, 2, 1),
			(float)$easting,
			(float)$northing,
		);
	}

	public static function fromCoordinates(\DJTommek\Coordinates\CoordinatesInterface $coordinates): self
	{
		$lat = $coordinates->getLat();
		$lon = $coordinates->getLon();

		$zonenumber = (int)ceil($lon / 6) + 30;
		$zoneBand = self::calculateZone($lat, $lon);
		$k_0 = 0.9996;
		$a = 6378137;
		$A = ($lon - ceil($lon / 6) * 6 + 3) / 180 * M_PI * cos($lat / 180 * M_PI);
		$T = pow(tan($lat / 180 * M_PI), 2);
		$C = pow(0.0067394968 * cos($lat / 180 * M_PI), 2);
		$y = 500000 + $k_0 * $a / pow(1 - 0.00669438 * pow(sin($lat / 180 * M_PI), 2), 0.5) * self::easting($A, $T, $C);
		if ($lat < 0) {
			$x1 = 1;
		} else {
			$x1 = 0;
		}

		$x = $x1 * 10000000 + $k_0 * $a * (0.9983243 * ($lat / 180 * M_PI) - 2.51460708e-3 * sin(2 * $lat / 180 * M_PI)
				+ 2.63904664e-6 * sin(4 * $lat / 180 * M_PI) - 3.41804618e-9 * sin(6 * $lat / 180 * M_PI)
				+ tan($lat / 180 * M_PI) / pow(1 - 0.00669438 * pow(sin($lat / 180 * M_PI), 2), 0.5) * (self::northing($A, $T, $C)));

		$result = new self($zonenumber, $zoneBand, $y, $x);
		$result->coordinates = new CoordinatesImmutable($lat, $lon);

		return $result;
	}

	private static function calculateZone(float $lat, float $lon): string
	{
		$zone = match ((int)floor($lat / 8)) {
			-10 => 'C',
			-9 => 'D',
			-8 => 'E',
			-7 => 'F',
			-6 => 'G',
			-5 => 'H',
			-4 => 'J',
			-3 => 'K',
			-2 => 'L',
			-1 => 'M',
			0 => 'N',
			1 => 'P',
			2 => 'Q',
			3 => 'R',
			4 => 'S',
			5 => 'T',
			6 => 'U',
			7 => 'V',
			8 => 'W',
			9 => 'X',
			default => null, // more calculations is necessary
		};
		if (abs($lat - 78) <= 6) {
			return 'X';
		}
		if ($zone !== null) {
			return $zone;
		}

		throw new \InvalidArgumentException(sprintf('Latitude %f is out of range', $lat));
		// @TODO according some specifications and online tools, UTM out of regular range still can be calculated,
		//       letters in that case would be B,A,Z and Y.
		// @phpstan-ignore-next-line
		if ($lat > 0) {
			if ($lon > 0) {
				return 'B';
			} else {
				return 'A';
			}
		} else {
			if ($lon > 0) {
				return 'Z';
			} else {
				return 'Y';
			}
		}
	}

	private static function easting(float $A, float $T, float $C): float
	{
		return $A + (1 - $T + $C) * pow($A, 3) / 6 + (5 - 18 * ($T) + pow($T, 2) + 72 * ($C) - 0.39089) * pow($A, 5) / 120;
	}

	private static function northing(float $A, float $T, float $C): float
	{
		return pow($A, 2) / 2 + (5 - $T + 9 * $C + 4 * pow($C, 2)) * pow($A, 4) / 24 + (61 - 58 * $T + pow($T, 2) + 600 * $C - 2.22403) * pow($A, 6) / 720;
	}

	public function format(UTMFormat $format): string
	{
		return match ($format) {
			UTMFormat::ZONE_COORDS => sprintf('%d%s %d %d', $this->zoneNumber, $this->zoneBand, $this->easting, $this->northing),
			default => throw new \Exception('To be implemented'),
		};
	}

	private function calculateLatLon(): void
	{
		if (isset($this->coordinates)) {
			return;
		}

		$utmZone = $this->zoneNumber;
		$east = $this->easting;
		$north = $this->northing;

		// This is the lambda knot value in the reference
		$LngOrigin = Deg2Rad($utmZone * 6 - 183);

		// The following set of class constants define characteristics of the
		// ellipsoid, as defined my the WGS84 datum.  These values need to be
		// changed if a different dataum is used.

		$falseNorth = $this->hemisphere === Hemisphere::SOUTH ? 1_000_0000 : 0;

		$Ecc = 0.081819190842622;       // Eccentricity
		$EccSq = $Ecc * $Ecc;
		$Ecc2Sq = $EccSq / (1. - $EccSq);
		$Ecc2 = sqrt($Ecc2Sq);      // Secondary eccentricity
		$E1 = (1 - sqrt(1 - $EccSq)) / (1 + sqrt(1 - $EccSq));
		$E12 = $E1 * $E1;
		$E13 = $E12 * $E1;
		$E14 = $E13 * $E1;


		$SemiMajor = 6378137.0;         // Ellipsoidal semi-major axis (Meters)
		$FalseEast = 500000.0;          // UTM East bias (Meters)
		$ScaleFactor = 0.9996;          // Scale at natural origin

		// Calculate the Cassini projection parameters

		$M1 = ($north - $falseNorth) / $ScaleFactor;
		$Mu1 = $M1 / ($SemiMajor * (1 - $EccSq / 4.0 - 3.0 * $EccSq * $EccSq / 64.0 - 5.0 * $EccSq * $EccSq * $EccSq / 256.0));

		$Phi1 = $Mu1 + (3.0 * $E1 / 2.0 - 27.0 * $E13 / 32.0) * sin(2.0 * $Mu1)
			+ (21.0 * $E12 / 16.0 - 55.0 * $E14 / 32.0) * sin(4.0 * $Mu1)
			+ (151.0 * $E13 / 96.0) * sin(6.0 * $Mu1)
			+ (1097.0 * $E14 / 512.0) * sin(8.0 * $Mu1);

		$sin2phi1 = sin($Phi1) * sin($Phi1);
		$Rho1 = ($SemiMajor * (1.0 - $EccSq)) / pow(1.0 - $EccSq * $sin2phi1, 1.5);
		$Nu1 = $SemiMajor / sqrt(1.0 - $EccSq * $sin2phi1);

		// Compute parameters as defined in the POSC specification.  T, C and D

		$T1 = tan($Phi1) * tan($Phi1);
		$T12 = $T1 * $T1;
		$C1 = $Ecc2Sq * cos($Phi1) * cos($Phi1);
		$C12 = $C1 * $C1;
		$D = ($east - $FalseEast) / ($ScaleFactor * $Nu1);
		$D2 = $D * $D;
		$D3 = $D2 * $D;
		$D4 = $D3 * $D;
		$D5 = $D4 * $D;
		$D6 = $D5 * $D;

		// Compute the Latitude and Longitude and convert to degrees
		$lat = $Phi1 - $Nu1 * tan($Phi1) / $Rho1 * ($D2 / 2.0 - (5.0 + 3.0 * $T1 + 10.0 * $C1 - 4.0 * $C12 - 9.0 * $Ecc2Sq) * $D4 / 24.0 + (61.0 + 90.0 * $T1 + 298.0 * $C1 + 45.0 * $T12 - 252.0 * $Ecc2Sq - 3.0 * $C12) * $D6 / 720.0);

		$lat = Rad2Deg($lat);

		$lon = $LngOrigin + ($D - (1.0 + 2.0 * $T1 + $C1) * $D3 / 6.0 + (5.0 - 2.0 * $C1 + 28.0 * $T1 - 3.0 * $C12 + 8.0 * $Ecc2Sq + 24.0 * $T12) * $D5 / 120.0) / cos($Phi1);

		$lon = Rad2Deg($lon);

		$this->coordinates = new CoordinatesImmutable($lat, $lon);
	}

	public function getLat(): float
	{
		$this->calculateLatLon();
		return $this->coordinates->getLat();
	}

	public function getLon(): float
	{
		$this->calculateLatLon();
		return $this->coordinates->getLon();
	}

	public function getLatLon(string $delimiter = ','): string
	{
		return $this->coordinates->getLatLon($delimiter);
	}
}
