<?php declare(strict_types=1);

namespace App\WhatThreeWord;

use App\Config;
use App\Factory;
use Nette\Caching\Cache;

class Helper
{
	// https://developer.what3words.com/tutorial/detecting-if-text-is-in-the-format-of-a-3-word-address/
	private const REGEX_PREFIX = '(?:\/{3})';
	private const REGEX_WORD = '(?:\p{L}\p{M}*){1,}';
	private const REGEX_WORD_DIVIDER = '[・.。]';

	private const LANG = 'en';

	private const REGEX_WORDS = '(' . self::REGEX_WORD . self::REGEX_WORD_DIVIDER . self::REGEX_WORD . self::REGEX_WORD_DIVIDER . self::REGEX_WORD . ')';
	private const REGEX_WORDS_PREFIX_REQUIRED = self::REGEX_PREFIX . self::REGEX_WORDS;
	private const REGEX_WORDS_PREFIX_OPTIONAL = self::REGEX_PREFIX . '?' . self::REGEX_WORDS;

	public function __construct(
		private readonly \What3words\Geocoder\Geocoder $apiClient,
	) {
	}

	/**
	 * @return string|null Validate words and return updated string ready to use in API or null if invalid
	 * @example paves.fans.piston -> paves.fans.piston
	 * @example ///paves.FANS.piston -> paves.fans.piston
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
	 *
	 * @return array<string>
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

	public function wordsToCoords(string $words): \stdClass
	{
		$words = self::validateWords($words);
		if ($words === null) {
			throw new \InvalidArgumentException('Words are invalid according REGEX.');
		}

		$cacheKey = sprintf('words-to-coords-%s', $words);
		return self::cache()->load($cacheKey, function () use ($words) {
			return $this->wordsToCoordsReal($words);
		});
	}

	public function wordsToCoordsReal(string $words): \stdClass
	{
		$response = $this->apiClient->convertToCoordinates($words);
		if ($error = $this->apiClient->getError()) {
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

	public function coordsToWords(float $lat, float $lon, string $lang = self::LANG): \stdClass
	{
		$cacheKey = sprintf('coords-to-words-%F-%F-%s', $lat, $lon, $lang);
		return self::cache()->load($cacheKey, function () use ($lat, $lon, $lang) {
			return $this->coordsToWordsReal($lat, $lon, $lang);
		});
	}

	private function coordsToWordsReal(float $lat, float $lon, string $lang): \stdClass
	{
		$response = $this->apiClient->convertTo3wa($lat, $lon, $lang);
		$error = $this->apiClient->getError();
		if ($error !== false) {
			assert(is_array($error));
			throw new ResponseException(sprintf('Unable to get W3W from coordinates: %s', $error['message']));
		}
		return json_decode(json_encode($response)); // @TODO dirty hack to get stdclass instead of associated array
	}

	private static function cache(): Cache
	{
		return Factory::permaCache(Config::CACHE_NAMESPACE_W3W);
	}
}
