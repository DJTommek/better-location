<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\Button\FavouritesButton;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Coordinates;
use App\Utils\Strict;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class StartCommand extends Command
{
	const CMD = '/start';

	const FAVOURITE = 'f';
	const FAVOURITE_ADD = 'a';
	const FAVOURITE_RENAME = 'r';
	const FAVOURITE_DELETE = 'd';
	const FAVOURITE_LIST = 'l';
	const FAVOURITE_ERROR = 'e';
	const FAVOURITE_ERROR_TOO_LONG = 'too-long';

	const SETTINGS = SettingsCommand::CMD;

	public function handleWebhookUpdate()
	{
		$encodedParams = TelegramHelper::getParams($this->update);
		if (count($encodedParams) === 0) {
			$this->processHelp();
		} else if (count($encodedParams) === 1 && preg_match('/^(-?[0-9]{1,8})_(-?[0-9]{1,9})$/', $encodedParams[0], $matches)) {
			$this->processStartCoordinates($matches);
		} else {
			$params = explode(' ', TelegramHelper::InlineTextDecode($encodedParams[0]));
			$action = array_shift($params);
			switch ($action) {
				case self::FAVOURITE;
					$this->processFavourites($params);
					break;
				case self::SETTINGS;
					$this->processSettings();
					break;
				default:
					// Bot indexers can add their own start parameters, so if no valid parameter is detected, continue just like /start without parameter
					Debugger::log(sprintf('Hidden start parameter "%s" is unknown.', $this->getText()), ILogger::DEBUG);
					$this->processHelp();
					break;
			}
		}
	}

	private function processStartCoordinates(array $matches)
	{
		$lat = Strict::intval($matches[1]) / 1000000;
		$lon = Strict::intval($matches[2]) / 1000000;
		if (BetterLocation::isLatValid($lat) === false || BetterLocation::isLonValid($lon) === false) {
			$this->reply(sprintf('%s Coordinates <code>%F,%F</code> are not valid.', Icons::ERROR, $lat, $lon));
		} else {
			try {
				$collection = WGS84DegreesService::processStatic($lat . ',' . $lon)->getCollection();
				$processedCollection = new ProcessedMessageResult($collection);
				$processedCollection->process();
				$this->reply($processedCollection->getText(), $processedCollection->getMarkup(1), ['disable_web_page_preview' => !$this->user->settings()->getPreview()]);
			} catch (\Throwable $exception) {
				Debugger::log($exception, ILogger::EXCEPTION);
				$this->reply(sprintf('%s Unexpected error occured while processing coordinates in start command for Better location. Contact Admin for more info.', Icons::ERROR));
			}
		}
	}

	/**
	 * @param array $params
	 * @throws \Exception
	 */
	private function processFavourites(array $params)
	{
		$action = array_shift($params);
		switch ($action) {
			case self::FAVOURITE_LIST:
				$this->processFavouritesList(false);
				break;
			case self::FAVOURITE_ADD:
				$replyMarkup = new Markup();
				$replyMarkup->inline_keyboard = [];

				$refreshFavouriteButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
				$refreshFavouriteButton->text = sprintf('%s Show list', Icons::REFRESH);
				$refreshFavouriteButton->callback_data = sprintf('%s %s', FavouritesButton::CMD, FavouritesButton::ACTION_REFRESH);
				$buttonRow[] = $refreshFavouriteButton;

				if (Coordinates::isLat($params[0]) && Coordinates::isLon($params[1])) {
					$lat = Strict::floatval($params[0]);
					$lon = Strict::floatval($params[1]);

					$deleteFavouriteButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
					$deleteFavouriteButton->text = sprintf('%s Remove', Icons::FAVOURITE_REMOVE);
					$deleteFavouriteButton->callback_data = sprintf('%s %s %F %F', FavouritesButton::CMD, FavouritesButton::ACTION_DELETE, $lat, $lon);
					$buttonRow[] = $deleteFavouriteButton;

					$replyMarkup->inline_keyboard[] = $buttonRow;

					if ($favourite = $this->user->getFavourite($lat, $lon)) {
						$this->reply(sprintf('%s This location (<code>%s</code>) is already saved in favourite list as %s.', Icons::INFO, $favourite->__toString(), $favourite->getPrefixMessage()), $replyMarkup);
					} else {
						$location = BetterLocation::fromLatLon($lat, $lon);
						$favourite = $this->user->addFavourite($location, BetterLocation::generateFavouriteName($lat, $lon));

						$text = sprintf('%s Location <code>%s</code> was successfully saved to favourites as %s.',
							Icons::SUCCESS, $favourite->__toString(), $favourite->getPrefixMessage()
						) . PHP_EOL;
						$text .= sprintf('You can now use it inline in any chat by typing @%s.', Config::TELEGRAM_BOT_NAME);
						$this->reply($text, $replyMarkup);
					}
				} else {
					$this->reply(sprintf('%s Invalid hiden parameters (coordinates) for adding to favourites.', Icons::ERROR));
				}
				break;
			case self::FAVOURITE_RENAME:
				$lat = floatval($params[0]);
				$lon = floatval($params[1]);
				$newName = join(' ', array_slice($params, 2));
				$favourite = $this->user->getFavourite($lat, $lon);
				if ($favourite) {
					try {
						$oldName = $favourite->getPrefixMessage();
						$favourite = $this->user->renameFavourite($favourite, $newName);
						$newName = $favourite->getPrefixMessage();
						$replyMarkup = new Markup();
						$replyMarkup->inline_keyboard = [];

						$refreshFavouriteButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
						$refreshFavouriteButton->text = sprintf('%s Show list', Icons::REFRESH);
						$refreshFavouriteButton->callback_data = sprintf('%s %s', FavouritesButton::CMD, FavouritesButton::ACTION_REFRESH);
						$buttonRow[] = $refreshFavouriteButton;

						$replyMarkup->inline_keyboard[] = $buttonRow;

						$this->reply(sprintf('%s Location %s was successfully renamed from <b>%s</b> to <b>%s</b>.',
							Icons::SUCCESS, $favourite->__toString(), $oldName, $newName
						), $replyMarkup);
					} catch (\Throwable $exception) {
						Debugger::log($exception, ILogger::EXCEPTION);
						$this->reply(sprintf('%s Unexpected error occured while renaming favourite. Contact Admin for more info.', Icons::ERROR));
					}
				} else {
					$this->reply(sprintf('%s Can\'t rename location %F,%F: not saved in your favourite locations.', Icons::ERROR, $lat, $lon));
					// @TODO offer to add to favourites now
				}
				break;
			case self::FAVOURITE_ERROR:
				switch ($params[0]) {
					case self::FAVOURITE_ERROR_TOO_LONG;
						$this->reply(sprintf('%s New name of favourite location is too long, try something shorter.', Icons::ERROR));
						break;
					default;
						$this->reply(sprintf('%s Unspecified error while processing favourite inline command.%sIf you believe that this is error, please contact admin.', Icons::ERROR, PHP_EOL));
						break;
				}
				break;
//			case self::FAVOURITE_DELETE:
//				$lat = floatval($params[0]);
//				$lon = floatval($params[1]);
//				$favourite = $this->user->getFavourite($lat, $lon);
//				if ($favourite) {
//					if ($this->user->removeFavourite($favourite) === true) {
//						$this->reply(sprintf('%s Location %s (<code>%s</code>) was deleted.', Icons::SUCCESS, $favourite->getPrefixMessage(), $favourite->__toString()));
//					} else {
//						$this->reply(sprintf('%s Unexpected error while deleting location %s (<code>%s</code>) was deleted.%sIf you believe that this is error, please contact admin.', Icons::ERROR, $favourite->getPrefixMessage(), $favourite->__toString(), PHP_EOL));
//					}
//				} else {
//					$this->reply(sprintf('%s Location <code>%F,%F</code> was already deleted from your favourite locations.', Icons::INFO, $lat, $lon));
//				}
//				break;
			default:
				$this->reply(sprintf('%s Hidden start parameter for favourite is unknown.%sIf you believe that this is error, please contact admin.', Icons::ERROR, PHP_EOL));
				break;
		}

	}
}
