<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\Factory;
use App\Icons;
use App\TelegramCustomWrapper\Events\Command\FavouritesCommand;
use App\TelegramCustomWrapper\TelegramHelper;
use OpenLocationCode\OpenLocationCode;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class FavouritesButton extends Button
{
	const CMD = FavouritesCommand::CMD;

	const ACTION_ADD = 'add';
	const ACTION_DELETE = 'delete';
	const ACTION_REFRESH = 'refresh';

	public function handleWebhookUpdate()
	{
		$params = TelegramHelper::getParams($this->update);
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
				$this->processFavouritesList(true);
				break;
			case self::ACTION_REFRESH:
				$this->processFavouritesList(true);
				break;
			default:
				$this->flash(sprintf('%s This button (favourite) is invalid.%sIf you believe that this is error, please contact admin', Icons::ERROR, PHP_EOL), true);
				break;
		}
	}

	/**
	 * @param float $lat
	 * @param float $lon
	 * @throws \Exception
	 */
	private function addFavourite(float $lat, float $lon): void
	{
		try {
			$favourite = $this->user->getFavourite($lat, $lon);
			if ($favourite) {
				$this->flash(sprintf('%s This location (%s) is already saved in favourite list as %s.', Icons::INFO, $favourite->__toString(), $favourite->getPrefixMessage()), true);
			} else {
				$generatedLocationName = $this->generateFavouriteName($lat, $lon);
				$betterLocation = BetterLocation::fromLatLon($lat, $lon);
				$betterLocation->setPrefixMessage($generatedLocationName);
				$betterLocation = $this->user->addFavourite($betterLocation, $generatedLocationName);
				$this->flash(sprintf('%s Location %s was saved as %s.%sYou can now use it inline in any chat by typing @%s.',
					Icons::SUCCESS, $betterLocation->__toString(), $betterLocation->getPrefixMessage(), PHP_EOL, Config::TELEGRAM_BOT_NAME
				), true);
			}
		} catch (\Exception $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			$this->flash(sprintf('%s Can\'t save this location to favourites.%sIf you believe that this is error, please contact admin.', Icons::ERROR, PHP_EOL), true);
		}
	}

	/**
	 * @param float $lat
	 * @param float $lon
	 */
	private function deleteFavourite(float $lat, float $lon)
	{
		try {
			$replyMarkup = new Markup();
			$replyMarkup->inline_keyboard = [];

			$refreshFavouriteButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
			$refreshFavouriteButton->text = sprintf('%s Show list', Icons::REFRESH);
			$refreshFavouriteButton->callback_data = sprintf('%s %s', FavouritesButton::CMD, FavouritesButton::ACTION_REFRESH);
			$buttonRow[] = $refreshFavouriteButton;

			$deleteFavouriteButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
			$deleteFavouriteButton->text = sprintf('%s Add back', Icons::FAVOURITE);
			$deleteFavouriteButton->callback_data = sprintf('%s %s %F %F', FavouritesButton::CMD, FavouritesButton::ACTION_ADD, $lat, $lon);
			$buttonRow[] = $deleteFavouriteButton;

			$replyMarkup->inline_keyboard[] = $buttonRow;
			$messageSettings = [
				'disable_web_page_preview' => true,
				'reply_markup' => $replyMarkup,
			];

			$favourite = $this->user->getFavourite($lat, $lon);
			if (is_null($favourite)) {
				$this->reply(sprintf('%s Location <code>%F,%F</code> was already removed from favourites.', Icons::INFO, $lat, $lon), $messageSettings);
			} else {
				$this->user->deleteFavourite($favourite);
				$this->reply(sprintf('%s Location %s <code>%s</code> was removed from favourites.', Icons::SUCCESS, $favourite->getPrefixMessage(), $favourite->__toString()), $messageSettings);
			}
		} catch (\Exception $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			$this->flash(sprintf('%s Unexpected error while removing location (%F,%F) from favourites.%sIf you believe that this is error, please contact admin.', Icons::ERROR, $lat, $lon, PHP_EOL), true);
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
	private function generateFavouriteName(float $lat, float $lon): string
	{
		try {
			$result = Factory::WhatThreeWords()->convertTo3wa($lat, $lon);
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
