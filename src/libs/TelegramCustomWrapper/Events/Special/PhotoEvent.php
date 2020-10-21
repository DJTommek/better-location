<?php declare(strict_types=1);

namespace TelegramCustomWrapper\Events\Special;

use \Icons;
use BetterLocation\BetterLocation;
use TelegramCustomWrapper\TelegramHelper;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class PhotoEvent extends Special
{
	/**
	 * PhotoCommand constructor.
	 *
	 * @param $update
	 * @throws \Exception
	 */
	public function __construct($update) {
		parent::__construct($update);

		$result = '';
		$buttonsRows = [];

		try {
			$betterLocations = BetterLocation::generateFromTelegramMessage(
				$this->update->message->caption,
				$this->update->message->caption_entities,
			);
			foreach ($betterLocations->getAll() as $betterLocation) {
				if (count($buttonsRows) === 0) { // show only one row of buttons
					$buttons = $betterLocation->generateDriveButtons();
					$buttons[] = $betterLocation->generateAddToFavouriteButtton();
					$buttonsRows[] = $buttons;
				}
				$result .= $betterLocation->generateBetterLocation();
			}
		} catch (\Exception $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			$this->reply(sprintf('%s Unexpected error occured while processing photo caption for Better location. Contact Admin for more info.', Icons::ERROR));
			return;
		}
		if ($result) {
			$markup = (new Markup());
			$markup->inline_keyboard = $buttonsRows;
			$this->reply(
				TelegramHelper::MESSAGE_PREFIX . $result,
				[
					'disable_web_page_preview' => true,
					'reply_markup' => $markup,
				],
			);
			return;
		} else if ($this->isPm() === true) {
			$this->reply('Thanks for the photo in PM! But I\'m not sure, what to do... If you want to process location from EXIF, you have to send <b>uncompressed</b> photo (send as file).');
		}
	}
}


