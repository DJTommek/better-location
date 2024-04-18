<?php declare(strict_types=1);

namespace App\Utils;

class Utils
{
	private const EMOJI_COUNTRY_CODE_OFFSET = 127397;

	/**
	 * Format seconds into human readable format
	 *
	 * @param int $seconds (1386203)
	 * @return string human readable formatted (16d 1h 3m 23s)
	 * @deprecated Use Formatter class instead
	 * @see \App\Utils\Formatter::seconds()
	 */
	public static function sToHuman(int $seconds): string
	{
		$s = $seconds % 60;
		$m = (int)($seconds / 60) % 60;
		$h = (int)($seconds / (60 * 60)) % 24;
		$d = (int)($seconds / (60 * 60 * 24));

		$result = '';
		$result .= ($d > 0 ? ' ' . $d . 'd' : '');
		$result .= ($h > 0 ? ' ' . $h . 'h' : '');
		$result .= ($m > 0 ? ' ' . $m . 'm' : '');
		$result .= ($s > 0 ? ' ' . $s . 's' : '');

		return trim($result);
	}

	/** See tests for this method for detail info. */
	public static function checkIfValueInHeaderMatchArray(string $headerValue, array $haystack): bool
	{
		$values = explode(';', mb_strtolower($headerValue));
		foreach ($values as $value) {
			if (in_array($value, $haystack)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get all available URLs from string
	 *
	 * @author https://stackoverflow.com/a/36564776/3334403
	 */
	public static function getUrls(string $string): array
	{
		$customPunct = '#\$%&\'\*\+,-\.\/:;<=>\?@\[\\]\^`\{|\}\~'; // whole [:punct:] without _
		preg_match_all('/\bhttps?:\/\/[^\s<>]+(?:\([\w\d]+\)|(?:[^,' . $customPunct . '\s]|\/))/', $string, $matches);
		return $matches[0];
	}

	/**
	 * Swap content of two variables with each other
	 *
	 * @param mixed $var1
	 * @param mixed $var2
	 */
	public static function swap(&$var1, &$var2): void
	{
		$tmp = $var1;
		$var1 = $var2;
		$var2 = $tmp;
	}

	/** Smarter parse_url() */
	public static function parseUrl(string $url): array
	{
		$parsedUrl = parse_url($url);
		if (isset($parsedUrl['query'])) {
			parse_str($parsedUrl['query'], $parsedUrl['query']);
		}
		return $parsedUrl;
	}

	/**
	 * @param ?string $prefix constant name must start with $prefix
	 * @return array indexed array of constantName => constantValue
	 * @throws \ReflectionException
	 */
	public static function getClassConstants(string $class, ?string $prefix = null): array
	{
		$reflection = new \ReflectionClass($class);
		$constants = $reflection->getConstants();

		if (is_null($prefix)) {
			return $constants;
		} else {
			return array_filter($constants, function ($constant) use ($prefix) {
				return StringUtils::startWith($constant, $prefix);
			}, ARRAY_FILTER_USE_KEY);
		}
	}

	/**
	 * Get last X lines from file
	 * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
	 *
	 * @throws \Exception
	 * @author Torleif Berger, Lorenzo Stanco
	 * @link http://stackoverflow.com/a/15025877/995958
	 * @link https://gist.github.com/lorenzos/1711e81a9162320fde20
	 * @license http://creativecommons.org/licenses/by/3.0/
	 */
	public static function tail(string $filepath, int $lines = 1, bool $adaptive = true): string
	{
		// Open file
		$f = @fopen($filepath, "rb");
		if ($f === false) {
			throw new \Exception(error_get_last()['message']);
		}

		// Sets buffer size, according to the number of lines to retrieve.
		// This gives a performance boost when reading a few lines from the file.
		if (!$adaptive) $buffer = 4096;
		else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

		// Jump to last character
		fseek($f, -1, SEEK_END);

		// Read it and adjust line number if necessary
		// (Otherwise the result would be wrong if file doesn't end with a blank line)
		if (fread($f, 1) != "\n") $lines -= 1;

		// Start reading
		$output = '';
		$chunk = '';

		// While we would like more
		while (ftell($f) > 0 && $lines >= 0) {
			// Figure out how far back we should jump
			$seek = min(ftell($f), $buffer);
			// Do the jump (backwards, relative to where we are)
			fseek($f, -$seek, SEEK_CUR);
			// Read a chunk and prepend it to our output
			$output = ($chunk = fread($f, $seek)) . $output;
			// Jump back to where we started reading
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
			// Decrease our line counter
			$lines -= substr_count($chunk, "\n");
		}
		// While we have too many lines
		// (Because of buffer size we might have read too many)
		while ($lines++ < 0) {
			// Find first newline and remove all text before that
			$output = substr($output, strpos($output, "\n") + 1);
		}
		// Close file and return
		fclose($f);
		return trim($output);
	}

	/** @return int|float */
	public static function clamp(float $value, float $min, float $max)
	{
		return max($min, min($max, $value));
	}

	/**
	 * Search initialization of https://api.mapy.cz/loader.js to get location
	 * @link https://api.mapy.cz/doc-simple/SMap.Coords.html#fromWGS84
	 */
	public static function findMapyCzApiCoords(string $html): ?Coordinates
	{
		if (preg_match('/SMap\.Coords\.fromWGS84\(\s*(-?[0-9.]+),\s*(-?[0-9.]+)\s*\)/', $html, $matches)) {
			return new Coordinates($matches[2], $matches[1]);
		} else {
			return null;
		}
	}

	/**
	 * Search initialization of Leaflet library to get location
	 *
	 * @example map = L.map('map_canvas', { attributionControl: false }).setView([47.648967, -122.348117], 13);
	 */
	public static function findLeafletApiCoords(string $html): ?Coordinates
	{
		if (preg_match('/\.setView\(\[\s*(-?[0-9.]+),\s*(-?[0-9.]+)]/', $html, $matches)) {
			return new Coordinates($matches[1], $matches[2]);
		} else {
			return null;
		}
	}

	/**
	 * Access global variable $_GET and return bool if value is truthy/falsey according URL standards.
	 *
	 * @param string $key Query key, that will be accessed as $_GET[key]
	 * @param mixed $default Default value, that will be returned if key is not set or non booleable
	 * @return mixed True if value is URL Truthy (1, true), False for 0 or false and null if undefined or not able to detect
	 */
	public static function globalGetToBool(string $key, mixed $default = null): mixed
	{
		if (isset($_GET[$key])) {
			$value = mb_strtolower($_GET[$key]);
			if (in_array($value, ['1', 'true', 't'])) {
				return true;
			} else if (in_array($value, ['0', 'false', 'f'])) {
				return false;
			}
		}
		return $default;
	}

	/**
	 * Recalculate all values in array to fit between provided minimum and maximum while keeping ratio.
	 * See tests for example usages.
	 *
	 * @param array<int|float> $range
	 * @return  array<int|float>
	 */
	public static function recalculateRange(array $range, float $newMin = 0, float $newMax = 100): array
	{
		$oldMax = max($range);
		$oldMin = min($range);
		array_walk($range, function (&$number) use ($oldMax, $oldMin, $newMin, $newMax) {
			$number = self::recalculateRangeOne($number, $oldMin, $oldMax, $newMin, $newMax);
		});
		return $range;
	}

	/**
	 * Recalculate one provided number into scale between $newMin and $newMax.
	 * See tests for example usages.
	 */
	public static function recalculateRangeOne(
		float $number,
		float $oldMin,
		float $oldMax,
		float $newMin = 0,
		float $newMax = 100,
	): float
	{
		if ($oldMin === $oldMax) { // prevent division by zero
			return ($newMax + $newMin) / 2;
		}
		$newMinMax = ($newMin - $newMax);
		$oldMinMax = ($oldMin - $oldMax);
		return ((($number - $newMin) * $newMinMax) / $oldMinMax) + $newMin;
	}

	public static function domFromUTF8(string $data): \DOMDocument {
		$dom = new \DOMDocument();
		// @HACK to force UTF-8 encoding. Page itself is in UTF-8 encoding but it is not saying explicitely so parser is confused.
		// @Author: https://stackoverflow.com/a/18721144/3334403
		@$dom->loadHTML('<?xml encoding="utf-8"?>' . $data);
		return $dom;
	}

	public static function parseLdJson(\DOMDocument $document): ?\stdClass
	{
		$finder = new \DOMXPath($document);
		$jsonEl = $finder->query('//script[@type="application/ld+json"]')->item(0);
		return $jsonEl ? json_decode($jsonEl->textContent) : null;
	}

	/**
	 * Convert country code to emoji representing flag of that country
	 *
	 * @example 'CZ' => 🇨🇿 (https://emojipedia.org/flag-czechia/)
	 * @author https://dev.to/jorik/country-code-to-flag-emoji-a21 (in Javascript)
	 *
	 * @param string $countryCode in Alpha-2 code - two characters, for example CZ (Czechia), US (United States of America), CH (Switzerland)
	 * @return string Multi-byte string representing flag, eg 🇨🇿
	 */
	public static function flagEmojiFromCountryCode(string $countryCode): string
	{
		if (!preg_match('/^[a-z]{2}$/i', $countryCode)) {
			throw new \InvalidArgumentException('Country code must be two characters within A-Z range.');
		}
		$chars = str_split(strtoupper($countryCode));

		$codesForFlag = array_map(
			fn($char) => ord($char) + self::EMOJI_COUNTRY_CODE_OFFSET,
			$chars,
		);

		$flagBytes = array_map(
			fn($charCode) => mb_chr($charCode),
			$codesForFlag,
		);

		return join('', $flagBytes);
	}
}
