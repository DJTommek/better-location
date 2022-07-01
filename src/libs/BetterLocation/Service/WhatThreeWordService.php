<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\WhatThreeWord;
use Nette\Utils\Arrays;

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

	/** @throws NotSupportedException|\Exception */
	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return WhatThreeWord\Helper::coordsToWords($lat, $lon)->map;
		}
	}

	public function isValid(): bool
	{
		return $this->isWords() || $this->isUrl();
	}

	public function process(): void
	{
		$data = WhatThreeWord\Helper::wordsToCoords($this->data->words);
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
		if ($words = WhatThreeWord\Helper::validateWords($this->input)) {
			$this->data->words = $words;
			return true;
		}
		return false;
	}

	/**
	 * @example https://w3w.co/chladná.naopak.vložit
	 * @example https://what3words.com/define.readings.cucumber
	 */
	private function isUrl(): bool
	{
		if (
			$this->url &&
			Arrays::contains(['what3words.com', 'w3w.co'], $this->url->getDomain(2))
		) {
			$words = ltrim(urldecode($this->url->getPath()), '/');
			if ($words = WhatThreeWord\Helper::validateWords($words)) {
				$this->data->words = $words;
				return true;
			}
		}
		return false;
	}

	public static function getShareText(float $lat, float $lon): string
	{
		$data = WhatThreeWord\Helper::coordsToWords($lat, $lon);
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
