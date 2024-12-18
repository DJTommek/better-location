<?php declare(strict_types=1);

namespace App\Utils;

use DJTommek\Coordinates\CoordinatesImmutable;

/**
 * Class to convert coordinates between WGS84 and Maidenhead Locator system (QTH, IARU)
 *
 * @author Tomas Palider (DJTommek) <tomas@palider.cz> - Rewritten code from JS, optimized
 * @link https://qsl.design/en/find-qth-locator/ original source code in JS
 * @link https://www.giangrandi.org/electronics/radio/qthloccalc.shtml original source code in JS
 */
class MaidenheadLocator implements \DJTommek\Coordinates\CoordinatesInterface
{
	private const CHARACTERS_LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWX';
	private const DIVIDERS = [10, 1, 1 / 24, 1 / 240, 1 / 240 / 24];

	private string $code;
	private CoordinatesImmutable $coordinates;

	/**
	 * Create via self::decode() or self::encode()
	 */
	private function __construct()
	{
	}

	/**
	 * @param string $code Code must be at least four characters long in pairs of two letters, two numbers.
	 */
	public static function isValid(string $code): bool
	{
		// @TODO:
		// - even count of characters allowed
		// - first pair must be alphabetic
		// - second pair must be numbers
		// - third pair and every pair must be alphabetic
		$codeLength = strlen($code);
		if ($codeLength < 4 || $codeLength > 10) {
			return false;
		}
		if ($codeLength % 2 !== 0) {
			return false;
		}

		// @TODO this checks only for first 4 characters. Update so it validates full string.
		return !!preg_match('/^[a-s][a-x]\d\d/i', $code);
	}

	public static function fromCode(string $code): self
	{
		if (self::isValid($code) === false) {
			throw new \InvalidArgumentException(sprintf('Invalid code "%s": check for invalid characters or incorrect length', $code));
		}

		$self = new self();
		$self->code = $code;
		return $self;
	}

	public static function fromCoordinates(\DJTommek\Coordinates\CoordinatesInterface $coordinates): self
	{
		$self = new self();
		$self->coordinates = new CoordinatesImmutable($coordinates->getLat(), $coordinates->getLon());
		return $self;
	}

	public function getPrecision(): ?int
	{
		if (isset($this->code) === false) {
			throw new \RuntimeException('To get precision you must provide or calculate code first.');
		}
		$code = $this->getCode();
		return strlen($code) / 2;
	}

	public function getCoordinates(): CoordinatesImmutable
	{
		if (isset($this->coordinates) === false) {
			assert(isset($this->code));
			$chars = str_split(self::CHARACTERS_LETTERS);
			$codeLength = strlen($this->code);

			// First pair: letters
			$lat = array_search(substr($this->code, 1, 1), $chars, true) * self::DIVIDERS[0];
			$lon = array_search(substr($this->code, 0, 1), $chars, true) * self::DIVIDERS[0] * 2;

			// Second pair: numbers
			$lat += (int)substr($this->code, 3, 1) * self::DIVIDERS[1];
			$lon += (int)substr($this->code, 2, 1) * self::DIVIDERS[1] * 2;

			// Third pair: letters
			if ($codeLength > 4) {
				$lat += array_search(substr($this->code, 5, 1), $chars, true) * self::DIVIDERS[2];
				$lon += array_search(substr($this->code, 4, 1), $chars, true) * self::DIVIDERS[2] * 2;
			}

			// Fourth pair: numbers
			if ($codeLength > 6) {
				$lat += (int)substr($this->code, 7, 1) * self::DIVIDERS[3];
				$lon += (int)substr($this->code, 6, 1) * self::DIVIDERS[3] * 2;
			}

			// Fifth pair: letters
			if ($codeLength > 8) {
				$lat += array_search(substr($this->code, 9, 1), $chars, true) * self::DIVIDERS[4];
				$lon += array_search(substr($this->code, 8, 1), $chars, true) * self::DIVIDERS[4] * 2;
			}

			// Calculate center of the square based on precision of the code
			if ($codeLength === 10) {
				$lat += self::DIVIDERS[4] * 0.5;
				$lon += self::DIVIDERS[4];
			} else if ($codeLength === 8) {
				$lat += self::DIVIDERS[3] * 0.5;
				$lon += self::DIVIDERS[3];
			} else if ($codeLength === 6) {
				$lat += self::DIVIDERS[2] * 0.5;
				$lon += self::DIVIDERS[2];
			} else if ($codeLength === 4) {
				$lat += self::DIVIDERS[1] * 0.5;
				$lon += self::DIVIDERS[1];
			} else if ($codeLength === 2) {
				$lat += self::DIVIDERS[0] * 0.5;
				$lon += self::DIVIDERS[0];
			}

			$this->coordinates = new CoordinatesImmutable($lat - 90, $lon - 180);
		}
		return $this->coordinates;
	}

	public function getCode(int $precision = 4): string
	{
		if (isset($this->code) === false) {
			assert(isset($this->coordinates));
			$y = $this->coordinates->getLat() + 90;
			$x = $this->coordinates->getLon() + 180;

			$locator = self::CHARACTERS_LETTERS[(int)floor($x / 20)] . self::CHARACTERS_LETTERS[(int)floor($y / 10)];
			for ($i = 0; $i < $precision; $i++) {
				$rlon = fmod($x, (self::DIVIDERS[$i] * 2));
				$rlat = fmod($y, self::DIVIDERS[$i]);
				if (($i % 2) == 0) {
					$locator .= floor($rlon / (self::DIVIDERS[$i + 1] * 2)) . floor($rlat / (self::DIVIDERS[$i + 1]));
				} else {
					$locator .= self::CHARACTERS_LETTERS[(int)floor($rlon / (self::DIVIDERS[$i + 1] * 2))]
						. self::CHARACTERS_LETTERS[(int)floor($rlat / (self::DIVIDERS[$i + 1]))];
				}
			}
			$this->code = $locator;
		}
		return $this->code;
	}

	public function getLat(): float
	{
		return $this->getCoordinates()->getLat();
	}

	public function getLon(): float
	{
		return $this->getCoordinates()->getLon();
	}

	public function getLatLon(string $delimiter = ','): string
	{
		return $this->getCoordinates()->getLatLon($delimiter);
	}
}
