<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidApiKeyException;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Icons;
use App\TelegramCustomWrapper\Events\Command\HelpCommand;
use App\TelegramCustomWrapper\TelegramHelper;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class MessageEvent extends Special
{
	public function __construct($update)
	{
		parent::__construct($update);

		// PM or whitelisted group
		$result = null;
		try {
			$betterLocations = BetterLocation::generateFromTelegramMessage($this->getText(), $this->update->message->entities);
			$result = '';
			$buttonLimit = 1; // @TODO move to config (chat settings)
			$buttons = [];
			foreach ($betterLocations->getAll() as $betterLocation) {
				if ($betterLocation instanceof BetterLocation) {
					$result .= $betterLocation->generateBetterLocation();
					if (count($buttons) < $buttonLimit) {
						$driveButtons = $betterLocation->generateDriveButtons();
						$driveButtons[] = $betterLocation->generateAddToFavouriteButtton();
						$buttons[] = $driveButtons;
					}
				} else if (
					$betterLocation instanceof InvalidLocationException ||
					$betterLocation instanceof InvalidApiKeyException
				) {
					$result .= Icons::ERROR . $betterLocation->getMessage() . PHP_EOL . PHP_EOL;
				} else {
					$result .= Icons::ERROR . 'Unexpected error occured while proceessing message for locations.' . PHP_EOL . PHP_EOL;
					Debugger::log($betterLocation, Debugger::EXCEPTION);
				}
			}
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			$this->reply(sprintf('%s Unexpected error occured while processing message for Better location. Contact Admin for more info.', Icons::ERROR));
			return;
		}
		if ($result) {
			$markup = (new Markup());
			$markup->inline_keyboard = $buttons;
			$this->reply(
				TelegramHelper::MESSAGE_PREFIX . $result,
				[
					'disable_web_page_preview' => true,
					'reply_markup' => $markup,
				],
			);
		} else if ($this->isPm() === true) {
			$message = 'Hi there in PM!' . PHP_EOL;
			if ($this->isForward()) {
				$message .= 'Thanks for forwarded message, but ';
			}
			$message .= sprintf('I didn\'t detected any location in that message. Use %s command to get info how to use me.', HelpCommand::getCmd(!$this->isPm())) . PHP_EOL;
			$message .= sprintf('%s Most used tips: ', Icons::INFO) . PHP_EOL;
			$message .= '- send me any message with location data (coords, links, Telegram location...)' . PHP_EOL;
			$message .= '- send me Telegram location' . PHP_EOL;
			$message .= '- send me <b>uncompressed</b> photos (as file) to process location from EXIF' . PHP_EOL;
			$message .= '- forward me any of above' . PHP_EOL;
			$this->reply($message);
		}
	}
}


