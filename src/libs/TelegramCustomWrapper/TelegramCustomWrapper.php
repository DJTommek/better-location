<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

use App\Config;
use App\Factory;
use App\Repository\ChatLocationHistory;
use App\TelegramCustomWrapper\Events\Button\FavouritesButton;
use App\TelegramCustomWrapper\Events\Button\HelpButton;
use App\TelegramCustomWrapper\Events\Button\InvalidButton;
use App\TelegramCustomWrapper\Events\Button\RefreshButton;
use App\TelegramCustomWrapper\Events\Button\SettingsButton;
use App\TelegramCustomWrapper\Events\Command\DebugCommand;
use App\TelegramCustomWrapper\Events\Command\FavouritesCommand;
use App\TelegramCustomWrapper\Events\Command\FeedbackCommand;
use App\TelegramCustomWrapper\Events\Command\HelpCommand;
use App\TelegramCustomWrapper\Events\Command\LoginCommand;
use App\TelegramCustomWrapper\Events\Command\SettingsCommand;
use App\TelegramCustomWrapper\Events\Command\StartCommand;
use App\TelegramCustomWrapper\Events\Command\UnknownCommand;
use App\TelegramCustomWrapper\Events\Edit\LocationEdit;
use App\TelegramCustomWrapper\Events\Events;
use App\TelegramCustomWrapper\Events\Special\AddedToChatEvent;
use App\TelegramCustomWrapper\Events\Special\ChannelPostEvent;
use App\TelegramCustomWrapper\Events\Special\ContactEvent;
use App\TelegramCustomWrapper\Events\Special\FileEvent;
use App\TelegramCustomWrapper\Events\Special\InlineQueryEvent;
use App\TelegramCustomWrapper\Events\Special\LocationEvent;
use App\TelegramCustomWrapper\Events\Special\MessageEvent;
use App\TelegramCustomWrapper\Events\Special\PhotoEvent;
use App\TelegramCustomWrapper\Exceptions\EventNotSupportedException;
use App\TelegramCustomWrapper\Exceptions\TelegramCustomWrapperException;
use React\EventLoop\LoopInterface;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Abstracts\TelegramTypes;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\TgLog;

use function Clue\React\Block\await;

class TelegramCustomWrapper
{
	private readonly TgLog $tgLog;
	private readonly LoopInterface $loop;

	public function __construct()
	{
		$this->loop = \React\EventLoop\Factory::create();
		$this->tgLog = new TgLog(Config::TELEGRAM_BOT_TOKEN, new HttpClientRequestHandler($this->loop));
	}

	/**
	 * Analyze Telegram API update object and return event type if there is one.
	 * @throws EventNotSupportedException When event is not have its specific handler
	 */
	public function analyze(Telegram\Types\Update $update): Events
	{
		if ($update->update_id === 0) { // default value
			throw new TelegramCustomWrapperException('Telegram webhook API data are missing!');
		}

		if (TelegramHelper::isEdit($update)) {
			if (TelegramHelper::isLocation($update)) {
				return new LocationEdit($update);
			} else {
				throw new EventNotSupportedException('Edit\'s are ignored');
			}
		}

		if (TelegramHelper::addedToChat($update, Config::TELEGRAM_BOT_NAME)) {
			return new AddedToChatEvent($update);
		}

		if (TelegramHelper::isViaBot($update, Config::TELEGRAM_BOT_NAME)) {
			throw new EventNotSupportedException('I will ignore my own via_bot (from inline) messages.');
		}

		if (TelegramHelper::isChosenInlineQuery($update)) {
			throw new EventNotSupportedException('ChosenInlineQuery handler is not implemented');
		}

		if (TelegramHelper::isInlineQuery($update)) {
			return new InlineQueryEvent($update);
		}

		$isChannelPost = TelegramHelper::isChannelPost($update);

		$command = null;
		if ($isChannelPost === false) { // Messages in channels are never commands
			$command = TelegramHelper::getCommand($update, Config::TELEGRAM_COMMAND_STRICT);
		}

		if (TelegramHelper::isButtonClick($update)) {
			return match ($command) {
				HelpButton::CMD => new HelpButton($update),
				FavouritesButton::CMD => new FavouritesButton($update),
				RefreshButton::CMD => new RefreshButton($update),
				SettingsButton::CMD => new SettingsButton($update),
				default => new InvalidButton($update),
			};
		}

		if (TelegramHelper::isLocation($update)) {
			return new LocationEvent($update);
		}

		if (TelegramHelper::hasDocument($update)) {
			return new FileEvent($update);
		}

		if (TelegramHelper::hasContact($update)) {
			return new ContactEvent($update);
		}

		if (TelegramHelper::hasPhoto($update)) {
			return new PhotoEvent($update);
		}

		if ($isChannelPost) {
			return new ChannelPostEvent($update);
		}

		if ($command === null) {
			if (TelegramHelper::isMessage($update)) {
				return new MessageEvent($update);
			}
			throw new EventNotSupportedException('Telegram event type was not recognized.');
		}

		return match ($command) {
			StartCommand::CMD => new StartCommand($update),
			HelpCommand::CMD => new HelpCommand($update),
			DebugCommand::CMD => new DebugCommand($update),
			SettingsCommand::CMD => new SettingsCommand($update),
			FavouritesCommand::CMD => new FavouritesCommand($update),
			FeedbackCommand::CMD => new FeedbackCommand($update),
			LoginCommand::CMD => new LoginCommand($update),
			default => new UnknownCommand($update),
		};
	}

	public function executeEventHandler(Events $event): void
	{
		$event->handleWebhookUpdate();

		try {
			$this->saveToChatHistory($event);
		} catch (\Exception $exception) {
			Debugger::log($exception, Debugger::EXCEPTION);
		}
	}

	private function saveToChatHistory(Events $event): void
	{
		$collections = $event->getCollection();
		if ($collections === null || $collections->isEmpty() || $event->getChat() === null) {
			return;
		}

		$db = Factory::database();
		$chatLocationHistory = new ChatLocationHistory($db);

		foreach ($collections as $location) {
			$chatLocationHistory->insert(
				$event->getTgUpdateId(),
				$event->getChat()->getEntity()->id,
				$event->getUser()->getEntity()->id,
				$event->getTgMessageSentDate(),
				$location->getCoordinates(),
				$location->getInput(),
			);
		}
	}

	/**
	 * @throws ClientException Errors from API
	 */
	public function run(TelegramMethods $telegramMethod): ?TelegramTypes
	{
		return await($this->tgLog->performApiRequest($telegramMethod), $this->loop);
	}
}
