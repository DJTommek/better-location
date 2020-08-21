<?php

namespace TelegramCustomWrapper\Events\Command;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Coordinates\WG84DegreesService;
use \Icons;
use TelegramCustomWrapper\Events\Button\FavouritesButton;
use TelegramCustomWrapper\TelegramHelper;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class StartCommand extends Command
{
	const FAVOURITE = 'f';
	const FAVOURITE_RENAME = 'r';
	const FAVOURITE_DELETE = 'd';
	const FAVOURITE_LIST = 'l';
	const FAVOURITE_ERROR = 'e';
	const FAVOURITE_ERROR_TOO_LONG = 'too-long';

	/**
	 * HelpCommand constructor.
	 *
	 * @param $update
	 * @throws \Exception
	 */
	public function __construct($update) {
		parent::__construct($update);
		$encodedParams = TelegramHelper::getParams($update);
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
				default:
					$this->reply(sprintf('%s Hidden start parameter is unknown.', Icons::ERROR));
					break;
			}
		}
	}

	private function processStartCoordinates(array $matches) {
		$lat = intval($matches[1]) / 1000000;
		$lon = intval($matches[2]) / 1000000;
		if (BetterLocation::isLatValid($lat) === false || BetterLocation::isLonValid($lon) === false) {
			$this->reply(sprintf('%s Coordinates <code>%f,%f</code> are not valid.', Icons::ERROR, $lat, $lon));
		} else {
			try {
				$betterLocation = new BetterLocation($lat, $lon, WG84DegreesService::NAME);
				$result = $betterLocation->generateBetterLocation();
				$buttons = $betterLocation->generateDriveButtons();
				$buttons[] = $betterLocation->generateAddToFavouriteButtton();
				$markup = (new Markup());
				if (isset($buttons)) {
					$markup->inline_keyboard = [$buttons];
				}
				$this->reply(
					TelegramHelper::MESSAGE_PREFIX . $result,
					[
						'disable_web_page_preview' => true,
						'reply_markup' => $markup,
					],
				);
			} catch (\Exception $exception) {
				$this->reply(sprintf('%s Unexpected error occured while processing start command for Better location. Contact Admin for more info.', Icons::ERROR));
				Debugger::log($exception, ILogger::EXCEPTION);
			}
		}
	}

	/**
	 * @param array $params
	 * @throws \Exception
	 */
	private function processFavourites(array $params) {
		$action = array_shift($params);
		switch ($action) {
			case self::FAVOURITE_LIST:
				$this->processFavouritesList(false);
				break;
			case self::FAVOURITE_RENAME:
				$lat = floatval($params[0]);
				$lon = floatval($params[1]);
				$newName = join(' ', array_slice($params, 2));
				$favourite = $this->user->getFavourite($lat, $lon);
				if ($favourite) {
					if ($this->user->renameFavourite($favourite, $newName) === true) {

						$replyMarkup = new Markup();
						$replyMarkup->inline_keyboard = [];

						$refreshFavouriteButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
						$refreshFavouriteButton->text = sprintf('%s Show list', \Icons::REFRESH);
						$refreshFavouriteButton->callback_data = sprintf('%s %s', FavouritesCommand::CMD, FavouritesButton::ACTION_REFRESH);
						$buttonRow[] = $refreshFavouriteButton;

						$replyMarkup->inline_keyboard[] = $buttonRow;
						$messageSettings = [
							'disable_web_page_preview' => true,
							'reply_markup' => $replyMarkup,
						];

						$this->reply(sprintf('%s Location %f,%f was successfully renamed from <b>%s</b> to <b>%s %s</b>.',
							Icons::SUCCESS, $lat, $lon, $favourite->getPrefixMessage(), Icons::FAVOURITE, $newName
						), $messageSettings);
					} else {
						$this->reply(sprintf('%s Unexpected error while renaming location <code>%f,%f</code>.%sIf you believe that this is error, please contact admin.', Icons::ERROR, $lat, $lon, PHP_EOL));
					}
				} else {
					$this->reply(sprintf('%s Can\'t rename location %f,%f: not saved in your favourite locations.', Icons::ERROR, $lat, $lon));
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
//						$this->reply(sprintf('%s Location %s (<code>%f,%f</code>) was deleted.', Icons::SUCCESS, $favourite->getPrefixMessage(), $lat, $lon));
//					} else {
//						$this->reply(sprintf('%s Unexpected error while deleting location %s (<code>%f,%f</code>) was deleted.%sIf you believe that this is error, please contact admin.', Icons::ERROR, $favourite->getPrefixMessage(), $lat, $lon, PHP_EOL));
//					}
//				} else {
//					$this->reply(sprintf('%s Location <code>%f,%f</code> was already deleted from your favourite locations.', Icons::INFO, $lat, $lon));
//				}
//				break;
			default:
				$this->reply(sprintf('%s Hidden start parameter for favourite is unknown.%sIf you believe that this is error, please contact admin.', Icons::ERROR, PHP_EOL));
				break;
		}

	}
}