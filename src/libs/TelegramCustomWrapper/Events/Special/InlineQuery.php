<?php

namespace TelegramCustomWrapper\Events\Special;

use BetterLocation\BetterLocation;
use BetterLocation\Service\MapyCzService;
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

			// If user agrees to share location, this is filled
			if (empty($update->inline_query->location) === false) {
				$answerInlineQuery->addResult($this->getInlineQueryResult(new BetterLocation(
					$update->inline_query->location->latitude,
					$update->inline_query->location->longitude,
					sprintf('%s Current location', \Icons::CURRENT_LOCATION),
				)));
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