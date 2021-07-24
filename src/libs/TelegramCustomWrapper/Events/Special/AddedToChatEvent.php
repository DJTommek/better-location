<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\WazeService;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\Command\HelpCommand;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class AddedToChatEvent extends Special
{
	public function handleWebhookUpdate()
	{
		$lat = 50.087451;
		$lon = 14.420671;
		$wazeLink = WazeService::getLink($lat, $lon);
		$betterLocationWaze = WazeService::processStatic($wazeLink)->getFirst();

		$markup = new Markup();
		$markup->inline_keyboard = [$betterLocationWaze->generateDriveButtons($this->getMessageSettings())];

		$text = sprintf('%s Hi <b>%s</b>, @%s here!', Icons::LOCATION, $this->getChatDisplayname(), Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('Thanks for adding me to this chat. I will be checking every message here if it contains any form of location (coordinates, links, photos with EXIF...) and send a nicely formatted message. More info in %s.', HelpCommand::getCmd(!$this->isPm())) . PHP_EOL;
		$text .= sprintf('For example if you send %s I will respond with this:', $wazeLink) . PHP_EOL;
		$text .= $betterLocationWaze->generateMessage($this->getMessageSettings());
		if ($betterLocationLocalGroup = $this->getChatLocation()) {
			$text .= sprintf('I noticed, that this is local group so here is nice message:') . PHP_EOL;
			$text .= $betterLocationLocalGroup->generateMessage($this->getMessageSettings());
			$markup->inline_keyboard[] = $betterLocationLocalGroup->generateDriveButtons($this->getMessageSettings());
		}

		$this->reply($text, $markup, [
			'disable_web_page_preview' => true,
		]);
	}

	private function getChatLocation(): ?BetterLocation
	{
		$betterLocation = null;
		try {
			$getChatRequest = new Telegram\Methods\GetChat();
			$getChatRequest->chat_id = $this->getChatId();
			$getChatResponse = $this->run($getChatRequest);
			/** @var Telegram\Types\Chat $getChatResponse */
			if (empty($getChatResponse->location) === false) {
				if (is_array($getChatResponse->location)) { // @TODO workaround until unreal4u/telegram-api is updated, then this block should be removed
					$location = new \stdClass();
					$location->address = $getChatResponse->location['address'];
					$location->location = new Telegram\Types\Location($getChatResponse->location['location']);
					$getChatResponse->location = $location;
				}
				$betterLocation = BetterLocation::fromLatLon($getChatResponse->location->location->latitude, $getChatResponse->location->location->longitude);
				$betterLocation->setAddress($getChatResponse->location->address);
				$betterLocation->setPrefixMessage('Local group');
			}
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
		}
		return $betterLocation;
	}
}


