<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Factory;
use App\WhatThreeWord;

final class WhatThreeWordService extends AbstractService
{
	const ID = 30;
	const NAME = 'What3Words';
	const NAME_SHORT = 'W3W';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_ONLINE,
		ServicesManager::TAG_GENERATE_PAID,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
		ServicesManager::TAG_GENERATE_TEXT,
		ServicesManager::TAG_GENERATE_TEXT_ONLINE,
	];

	const LINK = 'https://what3words.com/';
	const LINK_SHORT = 'https://w3w.co/';

	public function __construct(
		private readonly ?WhatThreeWord\Helper $w3wHelper = null,
	) {
	}

	/** @throws NotSupportedException|\Exception */
	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			$helper = Factory::whatThreeWordsHelper();
			return $helper?->coordsToWords($lat, $lon)->map;
		}
	}

	public function validate(): bool
	{
		return $this->isWords() || $this->isUrl();
	}

	public function process(): void
	{
		if ($this->w3wHelper === null) {
			throw new \RuntimeException('What3Words API is not available.');
		}

		$data = $this->w3wHelper->wordsToCoords($this->data->words);
		$betterLocation = new BetterLocation($this->input, $data->coordinates->lat, $data->coordinates->lng, self::class);
		$betterLocation->setPrefixMessage(sprintf('<a href="%s">%s</a>: <code>%s</code>', $data->map, self::NAME, $data->words));
		$this->collection->add($betterLocation);
	}

	/**
	 * @example chladná.naopak.vložit
	 * @example ///chladná.naopak.vložit
	 */
	private function isWords(): bool
	{
		$words = WhatThreeWord\Helper::validateWords($this->input);
		if ($words === null) {
			return false;
		}

		$this->data->words = $words;
		return true;
	}

	/**
	 * @example https://w3w.co/chladná.naopak.vložit
	 * @example https://what3words.com/define.readings.cucumber
	 */
	private function isUrl(): bool
	{
		if (
			$this->url &&
			in_array($this->url->getDomain(2), ['what3words.com', 'w3w.co'], true)
		) {
			$words = ltrim(urldecode($this->url->getPath()), '/');
			$validatedWords = WhatThreeWord\Helper::validateWords($words);
			if ($validatedWords !== null) {
				$this->data->words = $validatedWords;
				return true;
			}
		}
		return false;
	}

	public static function getShareText(float $lat, float $lon): ?string
	{
		$helper = Factory::whatThreeWordsHelper();
		if ($helper === null) {
			return null;
		}

		$data = $helper->coordsToWords($lat, $lon);
		return '///' . $data->words;
	}

	public static function findInText(string $text): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		$wordsAddresses = WhatThreeWord\Helper::findInText($text);
		foreach ($wordsAddresses as $wordsAddress) {
			// It is ok to use processStatic since words should be already valid
			$collection->add(self::processStatic($wordsAddress)->getCollection());
		}
		return $collection;
	}
}
