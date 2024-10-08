<?php declare(strict_types=1);

namespace App\Utils;

class StringUtils
{
	public const NEWLINE_WIN = "\r\n";
	public const NEWLINE_UNIX = "\n";
	public const NEWLINE_MAC = "\r";

	public const ELLIPSIS = '&#8230';

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

	/** @author https://stackoverflow.com/a/10473026/3334403 */
	public static function startWith(string $haystack, string $needle): bool
	{
		return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
	}

	/** @author https://stackoverflow.com/a/10473026/3334403 */
	public static function endWith(string $haystack, string $needle): bool
	{
		return substr_compare($haystack, $needle, -strlen($needle)) === 0;
	}

	public static function isGuid(string $guid, bool $supportParenthess = true): bool
	{
		$regex = '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}';
		if ($supportParenthess) {
			$regex = '{?' . $regex . '}?';
		}
		return !!preg_match('/^' . $regex . '$/i', $guid);
	}

	/**
	 * Replace only X times
	 *
	 * @author Inspiration from https://stackoverflow.com/a/1252710/3334403
	 */
	public static function replaceLimit(string $from, string $to, string $content, int $limit = 1): string
	{
		$i = 0;
		do {
			$pos = strpos($content, $from);
			if ($pos !== false) {
				$content = substr_replace($content, $to, $pos, strlen($from));
			}
			$i++;
		} while ($i < $limit);
		return $content;
	}

	public static function camelize(string $input, string $separator = '_'): string
	{
		return str_replace($separator, '', lcfirst(ucwords($input, $separator)));
	}

	public static function replaceNewlines(string $input, string $replace = ''): string
	{
		return str_replace([self::NEWLINE_WIN, self::NEWLINE_UNIX, self::NEWLINE_MAC], $replace, $input);
	}
}
