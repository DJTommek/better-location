<?php declare(strict_types=1);

namespace App\Utils;

use Nette;

class Strict
{
	public static function isInt($input): bool
	{
		if (is_int($input)) {
			return true;
		} else if (is_string($input)) {
			return !!preg_match('/^-?[0-9]+$/', $input);
		} else {
			return false;
		}
	}

	public static function isPositiveInt($input): bool
	{
		if (is_int($input)) {
			return true;
		} else if (is_string($input)) {
			return !!preg_match('/^[1-9][0-9]*$/', $input);
		} else {
			return false;
		}
	}

	public static function intval($input): int
	{
		if (self::isInt($input)) {
			return (int)$input;
		} else {
			throw new \InvalidArgumentException('Input is not valid int');
		}
	}

	public static function isFloat($input, bool $allowInt = true): bool
	{
		if (is_float($input)) {
			return true;
		} else if ($allowInt === true && self::isInt($input)) {
			return true;
		} else if (is_string($input)) {
			return !!preg_match('/^-?[0-9]+\.[0-9]+$/', $input);
		} else {
			return false;
		}
	}

	public static function isPositiveFloat($input, bool $allowInt = true): bool
	{
		if (is_float($input)) {
			return true;
		} else if ($allowInt === true && self::isPositiveInt($input)) {
			return true;
		} else if (is_string($input)) {
			return !!preg_match('/^[1-9][0-9]*\.[0-9]+$/', $input);
		} else {
			return false;
		}
	}

	public static function floatval($input, bool $allowInt = true): float
	{
		if (self::isFloat($input, $allowInt)) {
			return (float)$input;
		} else {
			throw new \InvalidArgumentException('Input is not valid float');
		}
	}

	public static function isBool($input): bool
	{
		if (is_bool($input)) {
			return true;
		} elseif (is_string($input)) {
			return in_array(mb_strtolower($input), ['true', 'false', '0', '1'], true);
		} else if (is_int($input)) {
			return $input === 0 || $input === 1;
		} else {
			return false;
		}
	}

	public static function boolval($input): bool
	{
		if (is_bool($input)) {
			return $input;
		} elseif (is_string($input)) {
			$input = mb_strtolower($input);
			if ($input === 'true' || $input === '1')
				return true;
			else if ($input === 'false' || $input === '0') {
				return false;
			} else {
				throw new \InvalidArgumentException('Input is not valid bool');
			}
		} else if (is_int($input)) {
			if ($input === 1) {
				return true;
			} else if ($input === 0) {
				return false;
			} else {
				throw new \InvalidArgumentException('Input is not valid bool');
			}
		} else {
			throw new \InvalidArgumentException('Input is not valid bool');
		}
	}

	/**
	 * Stricter creator of \Nette\Http\Url:
	 * - requiring at least second-level domain ("palider.cz", "tomas.palider.cz", "foo.tomas.palider.cz" but not "cz")
	 * - requiring http or https scheme
	 * - not allowing IP address
	 *
	 * @param string|Nette\Http\UrlImmutable|Nette\Http\Url $input
	 */
	public static function url($input): Nette\Http\Url
	{
		if (self::isUrl($input) === false) {
			throw new Nette\InvalidArgumentException;
		}
		return new Nette\Http\Url($input);
	}

	/**
	 * Stricter creator of \Nette\Http\UrlImmutable:
	 * - requiring at least second-level domain ("palider.cz", "tomas.palider.cz", "foo.tomas.palider.cz" but not "cz")
	 * - requiring http or https scheme
	 * - not allowing IP address
	 *
	 * @param string|Nette\Http\UrlImmutable|Nette\Http\Url $input
	 */
	public static function urlImmutable($input): Nette\Http\UrlImmutable
	{
		return new Nette\Http\UrlImmutable(self::url($input));
	}

	/**
	 * Stricter checker for URL:
	 * - requiring at least second-level domain ("palider.cz", "tomas.palider.cz", "foo.tomas.palider.cz" but not "cz")
	 * - requiring http or https scheme
	 * - not allowing IP address
	 */
	public static function isUrl(
		string|Nette\Http\UrlImmutable|Nette\Http\Url|null $input,
		bool $allowIpAddress = false
	): bool
	{
		if ($input === null) {
			return false;
		}

		if (is_string($input)) {
			if ($allowIpAddress && filter_var($input, FILTER_VALIDATE_IP) !== false) {
				return true;
			}

			try {
				$input = new Nette\Http\Url($input);
			} catch (\Nette\InvalidArgumentException $exception) {
				return false;
			}
		}

		if ($allowIpAddress && filter_var($input->getDomain(), FILTER_VALIDATE_IP) !== false) {
			return true;
		}

		return (
			$input->getDomain(-1) && // filtering out IP adresses and first-level domains
			in_array($input->getScheme(), ['https', 'http'], true) === true
		);
	}
}
