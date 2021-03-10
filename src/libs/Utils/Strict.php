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

	/**
	 * Stricter creator of Nette\Http\Url requiring URL to contain host and http(s) scheme.
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
	 * Stricter creator of Nette\Http\UrlImmutable requiring URL to contain host and http(s) scheme.
	 *
	 * @param string|Nette\Http\UrlImmutable|Nette\Http\Url $input
	 */
	public static function urlImmutable($input): Nette\Http\UrlImmutable
	{
		return new Nette\Http\UrlImmutable(self::url($input));
	}

	/**
	 * Stricter checker for URL requiring URL containing host and http(s) scheme.
	 *
	 * @param string|Nette\Http\UrlImmutable|Nette\Http\Url $input
	 */
	public static function isUrl($input): bool
	{
		if (is_string($input)) {
			$input = new Nette\Http\Url($input);
		}
		if ($input instanceof \Nette\Http\UrlImmutable || $input instanceof \Nette\Http\Url) {
			return ($input->getHost() && in_array($input->getScheme(), ['https', 'http'], true) === true);
		}
		return false;
	}
}
