<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\Service\MapyCzService;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\Command\StartCommand;
use App\TelegramCustomWrapper\TelegramHelper;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram\Methods\AnswerInlineQuery;
use unreal4u\TelegramAPI\Telegram\Types\Inline;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
use unreal4u\TelegramAPI\Telegram\Types\InputMessageContent\Text;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class InlineQueryEvent extends Special
{
	/**
	 * How many favourite locations will be shown?
	 *
	 * @TODO info to user, if he has more saved location than is now shown
	 */
	const MAX_FAVOURITES = 10;

	const GOOGLE_SEARCH_MIN_LENGTH = 3;

	public function __construct(Update $update)
	{
		parent::__construct($update);

		$answerInlineQuery = new AnswerInlineQuery();
		$answerInlineQuery->inline_query_id = $update->inline_query->id;
		$answerInlineQuery->cache_time = Config::TELEGRAM_INLINE_CACHE;

		$queryInput = trim($update->inline_query->query);

		if (empty($queryInput)) {
			// If user agrees to share location, and is using device, where is possible to get location (typically mobile devices)
			if (empty($update->inline_query->location) === false) {
				$betterLocation = BetterLocation::fromLatLon($update->inline_query->location->latitude, $update->inline_query->location->longitude);
				$betterLocation->setPrefixMessage(sprintf('%s Current location', Icons::CURRENT_LOCATION));
				$answerInlineQuery->addResult($this->getInlineQueryResult($betterLocation));
			} else if ($this->user->getLastKnownLocation() instanceof BetterLocation) {
				$lastKnownLocation = clone $this->user->getLastKnownLocation();
				$now = new \DateTimeImmutable();
				$diff = $now->getTimestamp() - $this->user->getLastKnownLocationDatetime()->getTimestamp();
				if ($diff <= 600) { // if last update was just few minutes ago, behave just like current location @TODO time border move to config
					$lastKnownLocation->setPrefixMessage(sprintf('%s Current location', Icons::CURRENT_LOCATION));
					$lastKnownLocation->setDescription(null);
				}
				$inlineText = sprintf('%s (%s ago)', $lastKnownLocation->getPrefixMessage(), \App\Utils\General::sToHuman($diff));
				$answerInlineQuery->addResult($this->getInlineQueryResult($lastKnownLocation, $inlineText));
			}

			// Show list of favourites
			$favourites = $this->user->loadFavourites();
			$index = 0;
			foreach ($favourites as $favourite) {
				if ($index++ < self::MAX_FAVOURITES) {
					$answerInlineQuery->addResult($this->getInlineQueryResult($favourite));
				}
			}
			if (count($answerInlineQuery->getResults()) === 0) {
				$answerInlineQuery->switch_pm_text = 'Search location (coordinates, link, etc)';
				$answerInlineQuery->switch_pm_parameter = 'inline-empty';
			}
		} else if (preg_match(sprintf('/^%s %s (-?[0-9]{1,2}\.[0-9]{1,6}) (-?[0-9]{1,3}\.[0-9]{1,6}) (.+)$/', StartCommand::FAVOURITE, StartCommand::FAVOURITE_RENAME), $queryInput, $matches)) {
			$newName = strip_tags($matches[3]);
			$newNameCommandDecoded = TelegramHelper::InlineTextEncode(
				sprintf('%s %s %F %F %s', StartCommand::FAVOURITE, StartCommand::FAVOURITE_RENAME, $matches[1], $matches[2], $newName)
			);
			if (mb_strlen($newNameCommandDecoded) > 64) {
				$answerInlineQuery->switch_pm_text = sprintf('New name is too long.');
				$answerInlineQuery->switch_pm_parameter = TelegramHelper::InlineTextEncode(
					sprintf('%s %s %s', StartCommand::FAVOURITE, StartCommand::FAVOURITE_ERROR, StartCommand::FAVOURITE_ERROR_TOO_LONG)
				);
			} else {
				$answerInlineQuery->switch_pm_text = sprintf('%s Rename to "%s"', Icons::CHANGE, $newName);
				$answerInlineQuery->switch_pm_parameter = $newNameCommandDecoded;
			}
//		} else if (preg_match(sprintf('/^%s %s (-?[0-9]{1,2}\.[0-9]{1,6}) (-?[0-9]{1,3}\.[0-9]{1,6})$/', StartCommand::FAVOURITE, StartCommand::FAVOURITE_DELETE), $queryInput, $matches)) {
//			list(, $lat, $lon) = $matches;
//			$lat = floatval($lat);
//			$lon = floatval($lon);
//			$answerInlineQuery->switch_pm_text = sprintf('%s Delete %s,%s', \Icons::DELETE, $lat, $lon);
//			$answerInlineQuery->switch_pm_parameter = TelegramHelper::InlineTextEncode(sprintf('%s %s %F %F', StartCommand::FAVOURITE, StartCommand::FAVOURITE_DELETE, $lat, $lon));
		} else {
			$entities = TelegramHelper::generateEntities($queryInput);
			try {
				$collection = BetterLocationCollection::fromTelegramMessage($queryInput, $entities);
				foreach ($collection->getLocations() as $betterLocation) {
					$answerInlineQuery->addResult($this->getInlineQueryResult($betterLocation));
				}
				foreach ($collection->getErrors() as $errors) {
					Debugger::log($errors, Debugger::EXCEPTION);
				}

				// only if there is no match from previous processing
				if (mb_strlen($queryInput) >= self::GOOGLE_SEARCH_MIN_LENGTH && count($answerInlineQuery->getResults()) === 0 && is_null(Config::GOOGLE_PLACE_API_KEY) === false) {
					$placeApi = new \App\BetterLocation\GooglePlaceApi();
					$placeCandidates = $placeApi->runPlaceSearch(
						$queryInput,
						['formatted_address', 'name', 'geometry', 'place_id'],
						$this->update->inline_query->from->language_code ?? 'en',
						$this->user->getLastKnownLocation(),
					);
					foreach ($placeCandidates as $placeCandidate) {
						$betterLocation = new BetterLocation(
							$queryInput,
							$placeCandidate->geometry->location->lat,
							$placeCandidate->geometry->location->lng,
							GoogleMapsService::class,
							GoogleMapsService::TYPE_INLINE_SEARCH,
						);
						try {
							$placeDetails = $placeApi->getPlaceDetails($placeCandidate->place_id, ['url', 'website']);
							$betterLocation->setPrefixMessage(sprintf('<a href="%s">%s</a>', ($placeDetails->website ?? $placeDetails->url), $placeCandidate->name));
						} catch (\Throwable $exception) {
							Debugger::log($exception, ILogger::EXCEPTION);
							$betterLocation->setPrefixMessage($placeCandidate->name);
						}
						$betterLocation->setAddress($placeCandidate->formatted_address);
						$answerInlineQuery->addResult($this->getInlineQueryResult($betterLocation));
					}
				}

				if (count($answerInlineQuery->getResults()) === 0) {
					$answerInlineQuery->switch_pm_text = 'No valid location found...';
					$answerInlineQuery->switch_pm_parameter = 'inline-notfound';
				}
			} catch (\Throwable $exception) {
				Debugger::log($exception, ILogger::EXCEPTION);
				$answerInlineQuery->switch_pm_text = 'Error occured while processing. Try again later.';
				$answerInlineQuery->switch_pm_parameter = 'inline-exception';
			}
		}
		$this->run($answerInlineQuery);
	}

	private function getInlineQueryResult(BetterLocation $betterLocation, string $inlineTitle = null): Inline\Query\Result\Location
	{
		$inlineQueryResult = new Inline\Query\Result\Location();
		$inlineQueryResult->id = rand(100000, 999999);
		$inlineQueryResult->latitude = $betterLocation->getLat();
		$inlineQueryResult->longitude = $betterLocation->getLon();
		$inlineQueryResult->title = $inlineTitle ?? strip_tags($betterLocation->getPrefixMessage());
		if ($betterLocation->getAddress()) {
			$inlineQueryResult->title .= sprintf(' (%s)', $betterLocation->getAddress());
		}
		$inlineQueryResult->thumb_url = MapyCzService::getScreenshotLink($betterLocation->getLat(), $betterLocation->getLon());
		$inlineQueryResult->reply_markup = new Markup();

		$buttons = $betterLocation->generateDriveButtons();
		$buttons[] = $betterLocation->generateAddToFavouriteButtton();

		$inlineQueryResult->reply_markup->inline_keyboard = [$buttons];
		$inlineQueryResult->input_message_content = new Text();
		$inlineQueryResult->input_message_content->message_text = TelegramHelper::MESSAGE_PREFIX . $betterLocation->generateMessage();
		$inlineQueryResult->input_message_content->parse_mode = 'HTML';
		$inlineQueryResult->input_message_content->disable_web_page_preview = true;
		return $inlineQueryResult;
	}
}
