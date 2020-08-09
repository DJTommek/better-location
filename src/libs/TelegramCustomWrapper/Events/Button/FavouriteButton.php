<?php

namespace TelegramCustomWrapper\Events\Button;

use BetterLocation\BetterLocation;
use OpenLocationCode\OpenLocationCode;
use TelegramCustomWrapper\Events\Command\FavouriteCommand;
use TelegramCustomWrapper\Events\Command\StartCommand;
use TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class FavouriteButton extends Button
{
	const ACTION_ADD = 'add';
	const ACTION_DELETE = 'delete';
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
			case self::ACTION_DELETE:
				$lat = floatval($params[0]);
				$lon = floatval($params[1]);
				$this->deleteFavourite($lat, $lon);
//				$this->processFavouriteList(true);
				break;
			case self::ACTION_REFRESH:
				$this->processFavouriteList(true);
				break;
			default:
				$this->flash(sprintf('%s This button (favourite) is invalid.%sIf you believe that this is error, please contact admin', \Icons::ERROR, PHP_EOL), true);
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
			} else if ($favourite = $this->user->getFavourite($lat, $lon)) {
				$this->flash(sprintf('%s This location (%f,%f) is already saved in favourite list as %s.',
					\Icons::INFO, $favourite->getLat(), $favourite->getLon(), $favourite->getPrefixMessage()
				), true);
				return;
			} else {
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
	private function deleteFavourite(float $lat, float $lon) {
		try {
			$replyMarkup = new Markup();
			$replyMarkup->inline_keyboard = [];

			$refreshFavouriteButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
			$refreshFavouriteButton->text = sprintf('%s Show list', \Icons::REFRESH);
			$refreshFavouriteButton->callback_data = sprintf('%s %s', FavouriteCommand::CMD, FavouriteButton::ACTION_REFRESH);
			$buttonRow[] = $refreshFavouriteButton;

			$deleteFavouriteButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
			$deleteFavouriteButton->text = sprintf('%s Add back', \Icons::FAVOURITE);
			$deleteFavouriteButton->callback_data = sprintf('%s %s %f %f', FavouriteCommand::CMD, FavouriteButton::ACTION_ADD, $lat, $lon);
			$buttonRow[] = $deleteFavouriteButton;

			$replyMarkup->inline_keyboard[] = $buttonRow;
			$messageSettings = [
				'disable_web_page_preview' => true,
				'reply_markup' => $replyMarkup,
			];

			$favourite = $this->user->getFavourite($lat, $lon);
			if (is_null($favourite)) {
				$this->reply(sprintf('%s Location <code>%f,%f</code> was already removed from favourites.', \Icons::INFO, $lat, $lon), $messageSettings);
				$this->processFavouriteList(true);
				return;
			} else if ($this->user->deleteFavourite($favourite)) {
				$this->reply(sprintf('%s Location %s <code>%f,%f</code> was removed from favourites.',
					\Icons::SUCCESS, $favourite->getPrefixMessage(), $favourite->getLat(), $favourite->getLon()
				), $messageSettings);
				$this->processFavouriteList(true);
			} else {
				$this->flash(sprintf('%s Unexpected error while removing location %s (%f,%f) from favourites.%sIf you believe that this is error, please contact admin.',
					\Icons::ERROR, $favourite->getPrefixMessage(), $favourite->getLat(), $favourite->getLon(), PHP_EOL
				), true);
			}
		} catch (\Exception $exception) {
			$this->flash(sprintf('%s Unexpected error while removing location (%f,%f) from favourites.%sIf you believe that this is error, please contact admin.',
				\Icons::ERROR, $lat, $lon, PHP_EOL
			), true);
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