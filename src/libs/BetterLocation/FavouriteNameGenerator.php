<?php declare(strict_types=1);

namespace App\BetterLocation;

use DJTommek\Coordinates\CoordinatesInterface;
use OpenLocationCode\OpenLocationCode;
use Tracy\Debugger;

class FavouriteNameGenerator
{
	public function __construct(
		private readonly ?\App\WhatThreeWord\Helper $w3wHelper = null,
	) {
	}

	public function generate(CoordinatesInterface $coordinates): string
	{
		return $this->generateW3W($coordinates) ?? $this->generateOpenLocationCode($coordinates);
	}

	private function generateW3W(CoordinatesInterface $coordinates): ?string
	{
		if ($this->w3wHelper === null) {
			return null;
		}

		try {
			$w3wData = $this->w3wHelper->coordsToWords($coordinates->getLat(), $coordinates->getLon());
			return '///' . $w3wData->words;
		} catch (\Throwable $exception) {
			Debugger::log($exception);
			return null;
		}
	}

	private function generateOpenLocationCode(CoordinatesInterface $coordinates): string
	{
		return OpenLocationCode::encode($coordinates->getLat(), $coordinates->getLon());
	}
}
