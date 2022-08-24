<?php declare(strict_types=1);

namespace App\WhatThreeWord;

use App\Factory;

class Helper
{
	// https://developer.what3words.com/tutorial/detecting-if-text-is-in-the-format-of-a-3-word-address/
	const REGEX_PREFIX = '(?:\/{3})';
	const REGEX_WORD = '(?:\p{L}\p{M}*){1,}';
	const REGEX_WORD_DIVIDER = '[・.。]';

	const REGEX_WORDS = '(' . self::REGEX_WORD . self::REGEX_WORD_DIVIDER . self::REGEX_WORD . self::REGEX_WORD_DIVIDER . self::REGEX_WORD . ')';
	const REGEX_WORDS_PREFIX_REQUIRED = self::REGEX_PREFIX . self::REGEX_WORDS;
	const REGEX_WORDS_PREFIX_OPTIONAL = self::REGEX_PREFIX . '?' . self::REGEX_WORDS;

	/**
	 * @return string|null Validate words and return updated string ready to use in API or null if invalid
	 * @example paves.fans.piston -> paves.fans.piston
	 * @example ///paves.fans.piston -> paves.fans.piston
	 * @example \\\paves.fans.piston -> paves.fans.piston
	 */
	public static function validateWords(string $input): ?string
	{
		if (preg_match('/^' . self::REGEX_WORDS_PREFIX_OPTIONAL . '$/u', trim($input), $matches)) {
			return mb_strtolower($matches[1]);
		}
		return null;
	}

	/**
	 * Find ///words and return array of them, ready to use for API request (without ///)
	 */
	public static function findInText(string $input): array
	{
		$result = [];
		if (preg_match_all('/' . self::REGEX_WORDS_PREFIX_REQUIRED . '/u', $input, $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				if ($words = self::validateWords($matches[0][$i])) {
					$result[] = $words;
				}
			}
		}
		return $result;
	}

	public static function wordsToCoords(string $words): \stdClass
	{
		if ($words = self::validateWords($words)) {
			$cacheKey = sprintf('words-to-coords-%s', $words);
			return Factory::Cache('w3w')->load($cacheKey, function () use ($words) {
				return self::wordsToCoordsReal($words);
			});
		} else {
			throw new \InvalidArgumentException('Words are invalid according REGEX.');
		}
	}

	public static function wordsToCoordsReal(string $words): \stdClass
	{
		$w3wApi = Factory::WhatThreeWords();
		$response = $w3wApi->convertToCoordinates($words);
		if ($error = $w3wApi->getError()) {
			$error = json_decode(json_encode($error)); // @TODO dirty hack to get stdclass instead of associated array
			if ($error->code === 'BadWords') {
				throw new ResponseException(sprintf('What3Words "%s" are not valid words: "%s"', $words, $error->message));
			} else if ($error->code === 'InvalidKey') {
				throw new ResponseException(sprintf('What3Words API responded with error: "%s"', $error->message));
			} else {
				throw new ResponseException(sprintf('Detected What3Words "%s" but unable to get coordinates, invalid response from API: "%s"', $words, $error->message));
			}
		} else {
			return json_decode(json_encode($response)); // @TODO dirty hack to get stdclass instead of associated array
		}
	}

	public static function coordsToWords(float $lat, float $lon): \stdClass
	{
		$cacheKey = sprintf('coords-to-words-%F-%F', $lat, $lon);
		return Factory::Cache('w3w')->load($cacheKey, function () use ($lat, $lon) {
			return self::coordsToWordsReal($lat, $lon);
		});
	}

	private static function coordsToWordsReal(float $lat, float $lon): \stdClass
	{
		$w3wApi = Factory::WhatThreeWords();
		$response = $w3wApi->convertTo3wa($lat, $lon);
		if ($error = $w3wApi->getError()) {
			throw new ResponseException(sprintf('Unable to get W3W from coordinates: %s', $error['message']));
		}
		return json_decode(json_encode($response)); // @TODO dirty hack to get stdclass instead of associated array
	}
}
