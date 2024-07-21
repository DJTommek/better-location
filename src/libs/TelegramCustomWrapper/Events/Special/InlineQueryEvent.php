<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\FromTelegramMessage;
use App\BetterLocation\GooglePlaceApi;
use App\BetterLocation\Service\MapyCzService;
use App\Chat;
use App\Config;
use App\Geonames\Geonames;
use App\Icons;
use App\Repository\ChatEntity;
use App\Repository\ChatRepository;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Formatter;
use DJTommek\Coordinates\CoordinatesInterface;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Methods\AnswerInlineQuery;
use unreal4u\TelegramAPI\Telegram\Types\Inline;
use unreal4u\TelegramAPI\Telegram\Types\InputMessageContent\Text;

class InlineQueryEvent extends Special
{
	/**
	 * How many favourite locations will be shown?
	 *
	 * @TODO info to user, if he has more saved location than is now shown
	 */
	const MAX_FAVOURITES = 10;

	/**
	 * How long is location considered still "live", see usages.
	 *
	 * @var int in seconds
	 */
	private const LIVE_LOCATION_THRESHOLD = 600;

	/**
	 * Instance of Chat for this user's private chat (only for lazy load)
	 *
	 * @internal Only for lazy load, do not use directly. Use $this->getUserPrivateChatEntity() instead
	 * @see getMessageSettings()
	 */
	private ?Chat $userPrivateChatEntity = null;
	private BetterLocationCollection $collection;

	public function __construct(
		private readonly FromTelegramMessage $fromTelegramMessage,
		private readonly ChatRepository $chatRepository,
		private readonly MapyCzService $mapyCzService,
		private readonly Geonames $geonames,
		private readonly ?GooglePlaceApi $googlePlaceApi = null,
	) {
	}

	public function getMessageSettings(): BetterLocationMessageSettings
	{
		$messageSettings = parent::getMessageSettings();
		$messageSettings->showAddress($this->getUserPrivateChatEntity()->settingsShowAddress());
		$messageSettings->tryLoadIngressPortal($this->getUserPrivateChatEntity()->settingsTryLoadIngressPortal());
		return $messageSettings;
	}

	private function getUserPrivateChatEntity(): Chat
	{
		if ($this->userPrivateChatEntity === null) {
			$this->userPrivateChatEntity = new Chat(
				$this->chatRepository,
				$this->getTgFromId(),
				ChatEntity::CHAT_TYPE_PRIVATE,
				$this->getTgFromDisplayname(),
			);
		}
		return $this->userPrivateChatEntity;
	}


	public function getCollection(): BetterLocationCollection
	{
		if (isset($this->collection) === false) {
			$this->collection = $this->getCollectionInner();
		}

		return $this->collection;
	}

	private function getCollectionInner(): BetterLocationCollection
	{
		$queryInput = $this->getQueryInput();
		$collection = new BetterLocationCollection();

		$userLastCoordinates = $this->user->getLastCoordinates();

		if ($queryInput === '') {
			$this->processUserLocation($collection, $userLastCoordinates);
			$this->processUserFavorites($collection);
		} else {
			$this->processQueryInput($collection, $queryInput, $userLastCoordinates);
		}

		return $collection;
	}

	/**
	 * Loads user last location and store it into provided $collection
	 */
	private function processUserLocation(BetterLocationCollection $collection, ?CoordinatesInterface $userLastCoordinates): void
	{
		if ($userLastCoordinates === null) {
			return;
		}

		$location = BetterLocation::fromCoords($userLastCoordinates);
		$location->setPrefixMessage(sprintf('%s Last location', Icons::CURRENT_LOCATION));

		$now = new \DateTimeImmutable();
		$userLastCoordinatesDatetime = $this->user->getLastCoordinatesDatetime();

		$diff = $now->getTimestamp() - $userLastCoordinatesDatetime->getTimestamp();
		if ($diff <= self::LIVE_LOCATION_THRESHOLD) { // if last update was just few minutes ago, behave just like current location @TODO time border move to config
			$location->setPrefixMessage(sprintf('%s Current location', Icons::CURRENT_LOCATION));
		} else { // Show datetime of last location update in local timezone based on timezone on that location itself
			$geonames = $this->geonames->timezone($userLastCoordinates->getLat(), $userLastCoordinates->getLon());
			$location->addDescription(sprintf(
				'Last update %s',
				$userLastCoordinatesDatetime->setTimezone($geonames->timezone)->format(\App\Config::DATETIME_FORMAT_ZONE),
			));
		}

		$inlineText = sprintf('%s (%s ago)', $location->getPrefixMessage(), Formatter::seconds($diff, true));
		$location->setInlinePrefixMessage($inlineText);
		$collection->add($location);
	}

	/**
	 * Loads user favorites store them into provided $collection
	 */
	private function processUserFavorites(BetterLocationCollection $collection): void
	{
		$collection->add($this->user->getFavourites());
	}

	/**
	 * @TODO workaround how to detect last location (this will not work if pluginer changes text)
	 */
	private function isLocationUserLastLocation(BetterLocation $location): bool
	{
		$prefix = $location->getPrefixMessage();
		return (
			str_contains(sprintf('%s Last location', Icons::CURRENT_LOCATION), $prefix)
			|| str_contains(sprintf('%s Current location', Icons::CURRENT_LOCATION), $prefix)
		);
	}

	/**
	 * Parse provided query as entities and process them via Better Location Services.
	 * If no location is found, then use provided query in external API and try to find suitable location.
	 */
	private function processQueryInput(BetterLocationCollection $collection, string $queryInput, ?CoordinatesInterface $userLastCoordinates): void
	{
		assert($queryInput !== '');

		$entities = TelegramHelper::generateEntities($queryInput);

		$collectionFromEntities = $this->fromTelegramMessage->getCollection($queryInput, $entities);
		if ($collectionFromEntities->isEmpty() === false) {
			$collection->add($collectionFromEntities);
			return;
		}

		if ($this->googlePlaceApi === null || mb_strlen($queryInput) <= Config::GOOGLE_SEARCH_MIN_LENGTH) {
			return;
		}

		// Try Google search if no there are no locations from previous processing
		$googleCollection = $this->googlePlaceApi->searchPlace(
			queryInput: $queryInput,
			languageCode: $this->getTgFrom()->language_code ?? null,
			location: $userLastCoordinates,
		);
		$collection->add($googleCollection);
	}

	public function handleWebhookUpdate(): void
	{
		$answerInlineQuery = new AnswerInlineQuery();
		$answerInlineQuery->inline_query_id = $this->update->inline_query->id;
		$answerInlineQuery->cache_time = Config::TELEGRAM_INLINE_CACHE;
		$answerInlineQuery->is_personal = true;

		// If user agrees to share location, and is using device, where is possible to get location (typically mobile devices)
		$userInlineLocation = $this->update->inline_query->location;
		if ($userInlineLocation !== null) {
			// Telegram API does not provide datetime, when user is searching
			$this->user->setLastKnownLocation($userInlineLocation->latitude, $userInlineLocation->longitude);
		}

		try {
			$collection = $this->getCollection();
			$processedMessageResult = $this->processedMessageResultFactory->create($collection, $this->getMessageSettings(), $this->getPluginer());
			$processedMessageResult->process();

			if ($processedMessageResult->getCollection()->isEmpty()) {
				if ($this->getQueryInput() === '') {
					$answerInlineQuery->switch_pm_text = 'Search location (coordinates, link, etc)';
					$answerInlineQuery->switch_pm_parameter = 'inline-empty';
				} else {
					$answerInlineQuery->switch_pm_text = 'No valid location found...';
					$answerInlineQuery->switch_pm_parameter = 'inline-notfound';
				}

				return;
			}

			if (
				$processedMessageResult->validLocationsCount() > 1
				&& $this->getUserPrivateChatEntity()->getSendNativeLocation() === false // There cannot be multiple locations at once if sending native location
			) {
				$answerInlineQuery->addResult($this->getAllLocationsInlineQueryResultArticle($processedMessageResult));
			}

			foreach ($processedMessageResult->getCollection() as $index => $betterLocation) {
				// @TODO limit only to 50 answer inline query results (maximum according Telegram API documentation)
				//       https://core.telegram.org/bots/api#answerinlinequery
				$text = $processedMessageResult->getOneLocationText($index);

				$markup = new Inline\Keyboard\Markup();
				$markup->inline_keyboard = $processedMessageResult->getOneLocationButtonRow($index);

				$calculateDistance = $this->isLocationUserLastLocation($betterLocation) === false;

				$inlineResult = $this->getInlineQueryResult($betterLocation, $text, $markup, $calculateDistance);

				$answerInlineQuery->addResult($inlineResult);
			}
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			$answerInlineQuery->switch_pm_text = 'Error occured while processing. Try again later.';
			$answerInlineQuery->switch_pm_parameter = 'inline-exception';
		} finally {
			$this->runSmart($answerInlineQuery);
		}
	}

	private function getQueryInput(): string
	{
		return trim(preg_replace('/\s+/', ' ', $this->update->inline_query->query));
	}

	public function hasTgMessage(): bool
	{
		return false;
	}

	public function getTgMessage(): Telegram\Types\Message
	{
		throw new \Exception(sprintf('Type %s doesn\'t support getMessage().', static::class));
	}

	public function getTgFrom(): Telegram\Types\User
	{
		return $this->update->inline_query->from;
	}

	private function getInlineQueryResult(
		BetterLocation $betterLocation,
		string $text,
		Inline\Keyboard\Markup $replyMarkup,
		bool $calculateDistance = true,
	): Inline\Query\Result\Location|Inline\Query\Result\Article {
		if ($this->getUserPrivateChatEntity()->getSendNativeLocation()) {
			return $this->getInlineQueryResultNativeLocation($betterLocation, $replyMarkup, $calculateDistance);
		} else {
			return $this->getInlineQueryResultArticle($betterLocation, $text, $replyMarkup, $calculateDistance);
		}
	}

	/**
	 * Prepare formatted text showing distance between user's last location and provided location. Returns empty string if
	 * cannot be calculated (eg. user's location is unknown)
	 */
	private function addDistanceText(BetterLocation $betterLocation): string
	{
		$usersLastLocation = $this->user->getLastCoordinates();
		if ($usersLastLocation === null) {
			return '';
		}

		$distance = $usersLastLocation->distance($betterLocation);
		return sprintf(
			' (%s away)',
			htmlspecialchars(Formatter::distance($distance)),
		);
	}

	private function getInlineQueryResultArticle(
		BetterLocation $betterLocation,
		string $text,
		Inline\Keyboard\Markup $replyMarkup,
		bool $calculateDistance,
	): Inline\Query\Result\Article {
		$inlineQueryResult = new Inline\Query\Result\Article();
		$inlineQueryResult->id = rand(100000, 999999);
		$inlineTitle = $betterLocation->getInlinePrefixMessage() ?? $betterLocation->getPrefixMessage();
		if ($calculateDistance) {
			$inlineTitle .= $this->addDistanceText($betterLocation);
		}
		$inlineQueryResult->title = strip_tags($inlineTitle);
		$inlineQueryResult->description = $betterLocation->getLatLon();

		if ($this->showAddress() && $betterLocation->hasAddress()) {
			$inlineQueryResult->description .= sprintf(' (%s)', $betterLocation->getAddress());
		}

		$inlineQueryResult->thumbnail_url = $this->mapyCzService->getScreenshotLink($betterLocation);
		$inlineQueryResult->reply_markup = $replyMarkup;
		$inlineQueryResult->input_message_content = new Text();
		$inlineQueryResult->input_message_content->message_text = $text;
		$inlineQueryResult->input_message_content->parse_mode = 'HTML';
		$inlineQueryResult->input_message_content->disable_web_page_preview = !$this->getUserPrivateChatEntity()->settingsPreview();
		return $inlineQueryResult;
	}

	private function getInlineQueryResultNativeLocation(
		BetterLocation $betterLocation,
		Inline\Keyboard\Markup $markup,
		bool $calculateDistance,
	): Inline\Query\Result\Location {
		$inlineQueryResult = new Inline\Query\Result\Location();
		$inlineQueryResult->id = rand(100000, 999999);
		$inlineTitle = $betterLocation->getInlinePrefixMessage() ?? $betterLocation->getPrefixMessage();
		if ($calculateDistance) {
			$inlineTitle .= $this->addDistanceText($betterLocation);
		}

		$inlineQueryResult->latitude = $betterLocation->getLat();
		$inlineQueryResult->longitude = $betterLocation->getLon();
		$inlineQueryResult->title = strip_tags($inlineTitle);
		$inlineQueryResult->thumbnail_url = $this->mapyCzService->getScreenshotLink($betterLocation);
		$inlineQueryResult->reply_markup = $markup;
		return $inlineQueryResult;
	}

	private function getAllLocationsInlineQueryResultArticle(ProcessedMessageResult $processedCollection): Inline\Query\Result\Article
	{
		$inlineQueryResult = new Inline\Query\Result\Article();
		$inlineQueryResult->id = rand(100000, 999999);
		$inlineQueryResult->title = sprintf('%s Multiple locations', Icons::LOCATION);
		$inlineQueryResult->description = sprintf('Send all %d locations listed below as one message', $processedCollection->validLocationsCount());
		$inlineQueryResult->reply_markup = $processedCollection->getMarkup(1);
		// @TODO workaround until resolving https://github.com/DJTommek/better-location/issues/2 (Secure public access)
		$inlineQueryResult->thumbnail_url = 'https://raw.githubusercontent.com/DJTommek/better-location/master/asset/map-icon-bot%20v1.png';

		$inlineQueryResult->input_message_content = new Text();
		$inlineQueryResult->input_message_content->message_text = $processedCollection->getText();
		$inlineQueryResult->input_message_content->parse_mode = 'HTML';
		$inlineQueryResult->input_message_content->disable_web_page_preview = !$this->getUserPrivateChatEntity()->settingsPreview();
		return $inlineQueryResult;
	}

	private function showAddress(): bool
	{
		return $this->getUserPrivateChatEntity()->settingsShowAddress();
	}
}
