<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\WhatThreeWord\Helper;
use Nette\Utils\Arrays;

final class WhatThreeWordService extends AbstractServiceNew
{
	const NAME = 'W3W';

	const LINK = 'https://what3words.com/';
	const LINK_SHORT = 'https://w3w.co/';

	/** @throws NotSupportedException|\Exception */
	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			$data = Helper::coordsToWords($lat, $lon);
			return $data->map;
		}
	}

	public function isValid(): bool
	{
		return $this->isWords() || $this->isUrl();
	}

	public function process(): void
	{
		$data = Helper::wordsToCoords($this->data->words);
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
		if ($words = Helper::validateWords($this->input)) {
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
			if ($words = Helper::validateWords($words)) {
				$this->data->words = $words;
				return true;
			}
		}
		return false;
	}
}
