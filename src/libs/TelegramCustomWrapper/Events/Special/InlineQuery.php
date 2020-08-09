<?php

namespace TelegramCustomWrapper\Events\Special;

use BetterLocation\BetterLocation;
use BetterLocation\Service\MapyCzService;
use TelegramCustomWrapper\Events\Command\StartCommand;
use TelegramCustomWrapper\TelegramHelper;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram\Methods\AnswerInlineQuery;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
use unreal4u\TelegramAPI\Telegram\Types\Inline;
use unreal4u\TelegramAPI\Telegram\Types\InputMessageContent\Text;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class InlineQuery extends Special
{
	/**
	 * How many favourite locations will be shown?
	 *
	 * @TODO info to user, if he has more saved location than is now shown
	 */
	const MAX_FAVOURITES = 10;

	/**
	 * InlineQuery constructor.
	 *
	 * @param Update $update
	 * @throws \Exception
	 */
	public function __construct(Update $update) {
		parent::__construct($update);

		$answerInlineQuery = new AnswerInlineQuery();
		$answerInlineQuery->inline_query_id = $update->inline_query->id;
		$answerInlineQuery->cache_time = TELEGRAM_INLINE_CACHE;

		$queryInput = trim($update->inline_query->query);


		if (empty($queryInput)) {
			// If user agrees to share location, this is filled
			if (empty($update->inline_query->location) === false) {
				$answerInlineQuery->addResult($this->getInlineQueryResult(new BetterLocation(
					$update->inline_query->location->latitude,
					$update->inline_query->location->longitude,
					sprintf('%s Current location', \Icons::CURRENT_LOCATION),
				)));
			}

			// Show list of favourites
			$favourites = $this->user->loadFavourites();
			$index = 0;
			foreach ($favourites as $favourite) {
				if ($index++ < self::MAX_FAVOURITES) {
					$answerInlineQuery->addResult($this->getInlineQueryResult($favourite));
				}
			}
		} else if (preg_match(sprintf('/^%s %s (-?[0-9]{1,2}\.[0-9]{1,6}) (-?[0-9]{1,3}\.[0-9]{1,6}) (.+)$/', StartCommand::FAVOURITE, StartCommand::FAVOURITE_RENAME), $queryInput, $matches)) {
			$newName = strip_tags($matches[3]);
			$newNameCommandDecoded = TelegramHelper::InlineTextEncode(
				sprintf('%s %s %f %f %s', StartCommand::FAVOURITE, StartCommand::FAVOURITE_RENAME, $matches[1], $matches[2], $newName)
			);
			if (mb_strlen($newNameCommandDecoded) > 64) {
				$answerInlineQuery->switch_pm_text = sprintf('New name is too long.');
				$answerInlineQuery->switch_pm_parameter = TelegramHelper::InlineTextEncode(
					sprintf('%s %s %s', StartCommand::FAVOURITE, StartCommand::FAVOURITE_ERROR, StartCommand::FAVOURITE_ERROR_TOO_LONG)
				);
			} else {
				$answerInlineQuery->switch_pm_text = sprintf('Rename to "%s"', $newName);
				$answerInlineQuery->switch_pm_parameter = $newNameCommandDecoded;
			}
		} else if (preg_match(sprintf('/^%s %s (-?[0-9]{1,2}\.[0-9]{1,6}) (-?[0-9]{1,3}\.[0-9]{1,6})$/', StartCommand::FAVOURITE, StartCommand::FAVOURITE_DELETE), $queryInput, $matches)) {
			list(, $lat, $lon) = $matches;
			$lat = floatval($lat);
			$lon = floatval($lon);
			$answerInlineQuery->switch_pm_text = sprintf('Delete %s,%s', $lat, $lon);
			$answerInlineQuery->switch_pm_parameter = TelegramHelper::InlineTextEncode(sprintf('%s %s %f %f', StartCommand::FAVOURITE, StartCommand::FAVOURITE_DELETE, $lat, $lon));
		} else {
			$urls = \Utils\General::getUrls($queryInput);

			// Simulate Telegram message by creating URL entities
			$entities = [];
			foreach ($urls as $url) {
				$entity = new \stdClass();
				$entity->type = 'url';
				$entity->offset = mb_strpos($queryInput, $url);
				$entity->length = mb_strlen($url);
				$entities[] = $entity;
			}
			try {
				$betterLocations = BetterLocation::generateFromTelegramMessage($queryInput, $entities);
				foreach ($betterLocations as $betterLocation) {
					if ($betterLocation instanceof BetterLocation) {
						$answerInlineQuery->addResult($this->getInlineQueryResult($betterLocation));
					} else if ($betterLocation instanceof \BetterLocation\Service\Exceptions\InvalidLocationException) {
						continue; // Ignore this error in inline query
					} else {
						Debugger::log($betterLocation, Debugger::EXCEPTION);
					}
				}

				if (count($answerInlineQuery->getResults()) === 0) {
					$answerInlineQuery->switch_pm_text = 'No valid location found...';
					$answerInlineQuery->switch_pm_parameter = 'inline-notfound';
				} /** @noinspection PhpStatementHasEmptyBodyInspection */ else {
					// @TODO set some user-defined location (eg home, work, ...) if no query is defined or at least one location was found
//				$betterLocation = new BetterLocation(50.087451, 14.420671, 'TEST');
//				$answerInlineQuery->addResult($this->getInlineQueryResult($betterLocation));
				}
			} catch (\Exception $exception) {
				$answerInlineQuery->switch_pm_text = 'Error occured while processing. Try again later.';
				$answerInlineQuery->switch_pm_parameter = 'inline-exception';
				Debugger::log($exception, ILogger::EXCEPTION);
			}
		}
		$this->run($answerInlineQuery);
	}

	private function getInlineQueryResult(BetterLocation $betterLocation): Inline\Query\Result\Location {
		$inlineQueryResult = new Inline\Query\Result\Location();
		$inlineQueryResult->id = rand(100000, 999999);
		$inlineQueryResult->latitude = $betterLocation->getLat();
		$inlineQueryResult->longitude = $betterLocation->getLon();
		$inlineQueryResult->title = strip_tags($betterLocation->getPrefixMessage());
		$inlineQueryResult->thumb_url = MapyCzService::getScreenshotLink($betterLocation->getLat(), $betterLocation->getLon());
		$inlineQueryResult->reply_markup = new Markup();
		$inlineQueryResult->reply_markup->inline_keyboard = [$betterLocation->generateDriveButtons()];
		$inlineQueryResult->input_message_content = new Text();
		$inlineQueryResult->input_message_content->message_text = TelegramHelper::MESSAGE_PREFIX . $betterLocation->generateBetterLocation();
		$inlineQueryResult->input_message_content->parse_mode = 'HTML';
		$inlineQueryResult->input_message_content->disable_web_page_preview = true;
		return $inlineQueryResult;
	}
}