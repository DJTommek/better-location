<?php

namespace TelegramCustomWrapper\Events\Button;

use BetterLocation\BetterLocation;
use OpenLocationCode\OpenLocationCode;
use TelegramCustomWrapper\TelegramHelper;

class FavouriteButton extends Button
{
	const ACTION_ADD = 'add';
	const ACTION_REMOVE = 'remove';
	const ACTION_REFRESH = 'refresh';

	/**
	 * FavouriteButton constructor.
	 *
	 * @param $update
	 * @throws \Exception
	 */
	public function __construct($update) {
		parent::__construct($update);

		$params = TelegramHelper::getParams($update);
		$action = array_shift($params);

		switch ($action) {
			case self::ACTION_ADD:
				$lat = floatval($params[0]);
				$lon = floatval($params[1]);
				$this->addFavourite($lat, $lon);
				break;
			case self::ACTION_REMOVE:
				$lat = floatval($params[0]);
				$lon = floatval($params[1]);
				$this->removeFavourite($lat, $lon);
				$this->processFavouriteList(true);
				break;
			case self::ACTION_REFRESH:
				$this->processFavouriteList(true);
				break;
			default:
				$this->flash(sprintf('%s This button is invalid.%sIf you believe that this is error, please contact admin', \Icons::ERROR, PHP_EOL), true);
				break;
		}
	}

	/**
	 * @param float $lat
	 * @param float $lon
	 * @throws \Exception
	 */
	private function addFavourite(float $lat, float $lon) {
		try {
			if (BetterLocation::isLatValid($lat) === false || BetterLocation::isLonValid($lon) === false) {
				$this->flash(sprintf('%s Coordinates are invalid.%sIf you believe that this is error, please contact admin.', \Icons::ERROR, PHP_EOL), true);
				return;
			}
			if ($favourite = $this->user->getFavourite($lat, $lon)) {
				$this->flash(sprintf('%s This location (%f,%f) is already saved in favourite list as %s.',
					\Icons::INFO, $favourite->getLat(), $favourite->getLon(), $favourite->getPrefixMessage()
				), true);
				return;
			}

			$generatedLocationName = $this->generateFavouriteName($lat, $lon);
			$betterLocation = new BetterLocation($lat, $lon, $generatedLocationName);
			if ($this->user->addFavourites($betterLocation, $generatedLocationName)) {
				$this->flash(sprintf('%s Location %f,%f was saved as %s %s.%sYou can now use it inline in any chat or PM by typing @%s.',
					\Icons::SUCCESS, $betterLocation->getLat(), $betterLocation->getLon(), \Icons::FAVOURITE, $betterLocation->getPrefixMessage(),
					PHP_EOL,
					TELEGRAM_BOT_NAME
				), true);
			} else {
				$this->flash(sprintf('%s Unable to save location %f,%f.%sIf you believe that this is error, please contact admin.',
					\Icons::ERROR, $betterLocation->getLat(), $betterLocation->getLon(), PHP_EOL,
				), true);
			}
		} catch (\Exception $exception) {
			$this->flash(sprintf('%s Can\'t save this location to favourites.%sIf you believe that this is error, please contact admin.', \Icons::ERROR, PHP_EOL), true);
		}
	}

	/**
	 * @param float $lat
	 * @param float $lon
	 * @throws \Exception
	 */
	private function removeFavourite(float $lat, float $lon) {
		try {
			$favourite = $this->user->getFavourite($lat, $lon);
			if (is_null($favourite)) {
				$this->flash(sprintf('%s This location was already removed from favourites.', \Icons::INFO), true);
				return;
			}
			if ($this->user->removeFavourite($favourite)) {
				$this->flash(sprintf('%s Location %s (%f,%f) was removed from favourites.',
					\Icons::SUCCESS, $favourite->getPrefixMessage(), $favourite->getLat(), $favourite->getLon()
				), true);
			} else {
				$this->flash(sprintf('%s Unexpected error while removing location %s (%f,%f) from favourites.%sIf you believe that this is error, please contact admin.',
					\Icons::ERROR, $favourite->getPrefixMessage(), $favourite->getLat(), $favourite->getLon(), PHP_EOL
				), true);
			}
		} catch (\Exception $exception) {
			$this->flash(sprintf('%s Can\'t save this location to favourites.%sIf you believe that this is error, please contact admin.', \Icons::ERROR, PHP_EOL), true);
		}
	}

	/**
	 * Generate name for newly added favourite item from as what3words with error fallback to OpenLocationCode
	 *
	 * @param float $lat
	 * @param float $lon
	 * @return string
	 * @throws \Exception
	 */
	private function generateFavouriteName(float $lat, float $lon): string {
		try {
			$w3wApi = new \What3words\Geocoder\Geocoder(W3W_API_KEY);
			$result = $w3wApi->convertTo3wa($lat, $lon);
			if ($result) {
				return sprintf('///%s', $result['words']);
			} else {
				return OpenLocationCode::encode($lat, $lon);
			}
		} catch (\Exception $exception) {
			return OpenLocationCode::encode($lat, $lon);
		}
	}
}