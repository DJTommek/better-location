<?php

namespace TelegramCustomWrapper\Events\Special;

use \BetterLocation\BetterLocation;
use TelegramCustomWrapper\TelegramHelper;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
use \Icons;
use unreal4u\TelegramAPI\Telegram\Methods\GetFile;

class File extends \TelegramCustomWrapper\Events\Special\Special
{
	const MIME_TYPE_IMAGE_JPEG = 'image/jpeg';

	/**
	 * FileCommand constructor.
	 *
	 * @param $update
	 * @throws \Exception
	 */
	public function __construct($update) {
		parent::__construct($update);

		$buttonsRows = [];

		$replyMessage = '';
		// PM or whitelisted group
		$document = $this->update->message->document;
		if ($document->mime_type === self::MIME_TYPE_IMAGE_JPEG) {
			$this->sendAction();
			$getFile = new GetFile();
			$getFile->file_id = $document->file_id;

			$response = $this->run($getFile);

			$fileLink = TelegramHelper::getFileUrl(TELEGRAM_BOT_TOKEN, $response->file_path);
			try {
				$betterLocationExif = BetterLocation::fromExif($fileLink);
				if ($betterLocationExif instanceof BetterLocation) {
					$replyMessage .= $betterLocationExif->generateBetterLocation();
					$exifButtons = $betterLocationExif->generateDriveButtons();
					$exifButtons[] = $betterLocationExif->generateAddToFavouriteButtton();
					$buttonsRows[] = $exifButtons;
				}
			} catch (\Throwable $exception) {
				Debugger::log($exception, ILogger::EXCEPTION);
				$this->reply(sprintf('%s Unexpected error occured while processing EXIF data from image for Better location. Contact Admin for more info.', Icons::ERROR));
				return;
			}
		}
		$betterLocationsMessage = BetterLocation::generateFromTelegramMessage(
			$this->update->message->caption,
			$this->update->message->caption_entities
		);

		foreach ($betterLocationsMessage->getAll() as $betterLocation) {
			$replyMessage .= $betterLocation->generateBetterLocation();
			if (count($buttonsRows) === 0) { // show only one row of buttons
				$exifButtons = $betterLocation->generateDriveButtons();
				$exifButtons[] = $betterLocation->generateAddToFavouriteButtton();
				$buttonsRows[] = $exifButtons;
			}
		}

		if ($replyMessage) {
			$markup = (new Markup());
			$markup->inline_keyboard = $buttonsRows;
			$this->reply(
				TelegramHelper::MESSAGE_PREFIX . $replyMessage,
				[
					'disable_web_page_preview' => true,
					'reply_markup' => $markup,
				],
			);
		} else if ($this->isPm()) {
			$this->reply('Thanks for the file in PM! But I\'m not sure, what to do... No location in EXIF was found.');
		}
	}
}


