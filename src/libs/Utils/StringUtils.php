<?php declare(strict_types=1);

namespace App\Utils;

class StringUtils
{
	/** Replace or remove some characters */
	public static function translit(string $text): string
	{
		$chars = [
			'"' => ['″'],
			'\'' => ['′'],
		];
		foreach ($chars as $validChar => $invalidChars) {
			$text = str_replace($invalidChars, $validChar, $text);
		}
		return $text;
	}
}
