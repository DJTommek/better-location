<?php

namespace TelegramCustomWrapper\Events\Command;

use \Icons;
use TelegramCustomWrapper\TelegramHelper;

class StartCommand extends Command
{
	const FAVOURITE = 'f';
	const FAVOURITE_RENAME = 'r';
	const FAVOURITE_DELETE = 'd';
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
		} else {
			$params = explode(' ', TelegramHelper::InlineTextDecode($encodedParams[0]));
			$action = array_shift($params);
			switch ($action) {
				case self::FAVOURITE;
					$this->processFavourite($params);
					break;
				default:
					$this->reply(sprintf('%s Hidden start parameter is unknown.', Icons::ERROR));
					break;
			}
		}
	}

	/**
	 * @param array $params
	 * @throws \Exception
	 */
	private function processFavourite(array $params) {
		$action = array_shift($params);
		switch ($action) {
			case self::FAVOURITE_RENAME:
				$lat = floatval($params[0]);
				$lon = floatval($params[1]);
				$newName = join(' ', array_slice($params, 2));
				$favourite = $this->user->getFavourite($lat, $lon);
				if ($favourite) {
					if ($this->user->renameFavourite($favourite, $newName) === true) {
						$this->reply(sprintf('%s Location %f,%f was successfully renamed to %s %s.', Icons::SUCCESS, $lat, $lon, Icons::FAVOURITE, $newName));
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
			case self::FAVOURITE_DELETE:
				$lat = floatval($params[0]);
				$lon = floatval($params[1]);
				$favourite = $this->user->getFavourite($lat, $lon);
				if ($favourite) {
					if ($this->user->removeFavourite($favourite) === true) {
						$this->reply(sprintf('%s Location %s (<code>%f,%f</code>) was deleted.', Icons::SUCCESS, $favourite->getPrefixMessage(), $lat, $lon));
					} else {
						$this->reply(sprintf('%s Unexpected error while deleting location %s (<code>%f,%f</code>) was deleted.%sIf you believe that this is error, please contact admin.', Icons::ERROR, $favourite->getPrefixMessage(), $lat, $lon, PHP_EOL));
					}
				} else {
					$this->reply(sprintf('%s Location %f,%f was already deleted from your favourite locations.', Icons::INFO, $lat, $lon));
				}
				break;
			default:
				$this->reply(sprintf('%s Hidden start parameter for favourite is unknown.%sIf you believe that this is error, please contact admin.', Icons::ERROR, PHP_EOL));
				break;
		}

	}
}