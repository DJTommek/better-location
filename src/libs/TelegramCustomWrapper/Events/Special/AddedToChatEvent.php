<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\ProcessExample;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\Command\HelpCommand;
use App\TelegramCustomWrapper\TelegramHelper;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class AddedToChatEvent extends Special
{
	public function __construct(
		private readonly ProcessExample $processExample,
	) {
	}

	public function handleWebhookUpdate(): void
	{
		$this->recalculateChatMembers();

		$markup = new Markup();
		$markup->inline_keyboard = [];

		$text = sprintf(
				'%s Hi <b>%s</b>, @%s here!',
				Icons::LOCATION,
				$this->getTgChatDisplayname(),
				Config::TELEGRAM_BOT_NAME,
			) . PHP_EOL;
		$text .= sprintf(
				'Thanks for adding me to this chat. I will be checking every message here if it contains any form of location (coordinates, links, photos with EXIF...) and send a nicely formatted message. More info in %s.',
				HelpCommand::getTgCmd(!$this->isTgPm()),
			) . PHP_EOL;

		$betterLocationLocalGroup = $this->getChatLocation();
		if ($betterLocationLocalGroup === null) {
			$text .= sprintf('For example if you send %s I will respond with this:', $this->processExample->getExampleInput()) . PHP_EOL;
			$collection = $this->processExample->getExampleCollection();
		} else {
			$text .= 'I noticed, that this is local group so here is nice message as example:' . PHP_EOL;
			$collection = (new BetterLocationCollection())->add($betterLocationLocalGroup);
		}

		$processedCollection = $this->processedMessageResultFactory->create(
			$collection,
			$this->getMessageSettings(),
			$this->getPluginer(),
		);
		$processedCollection->process();

		$text .= PHP_EOL;
		$text .= $processedCollection->getText();
		$rows = $processedCollection->getOneLocationButtonRow(0, false);
		assert(count($rows) === 1);
		$markup->inline_keyboard[] = $rows[0];

		$chatSettingsUrl = Config::getAppUrl('/chat/' . $this->getTgChatId());
		$markup->inline_keyboard[] = [
			TelegramHelper::loginUrlButton('Open settings', $chatSettingsUrl),
		];

		$this->reply($text, $markup, [
			'disable_web_page_preview' => true,
		]);
	}

	private function getChatLocation(): ?BetterLocation
	{
		$betterLocation = null;
		try {
			$getChatRequest = new Telegram\Methods\GetChat();
			$getChatRequest->chat_id = $this->getTgChatId();
			$chat = $this->runSmart($getChatRequest);
			if ($chat === null) {
				return null;
			}

			assert($chat instanceof Telegram\Types\Chat);
			if ($chat?->location === null) {
				return null;
			}

			$betterLocation = BetterLocation::fromLatLon($chat->location->location->latitude, $chat->location->location->longitude);
			$betterLocation->setAddress($chat->location->address);
			$betterLocation->setPrefixMessage('Local group');
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
		}
		return $betterLocation;
	}
}


