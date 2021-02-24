<?php declare(strict_types=1);

namespace App\Utils;

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
}
