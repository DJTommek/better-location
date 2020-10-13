<?php declare(strict_types=1);

namespace Utils;

class StringUtils
{
	/**
	 * Replace or remove some characters
	 *
	 * @param string $text
	 * @return string
	 */
	public static function translit(string $text) {
		$chars = [
			'"' => ['″'],
			'\'' => ['′'],
		];
		foreach ($chars as $validChar => $invalidChars) {
			$text = str_replace($invalidChars, $validChar, $text);
		}
		return $text;
	}

	public static function camelize($input, $separator = '_') {
		return str_replace($separator, '', lcfirst(ucwords($input, $separator)));
	}
}
