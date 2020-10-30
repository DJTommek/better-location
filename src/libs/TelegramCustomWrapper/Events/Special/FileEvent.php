<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram;

class FileEvent extends Special
{
	const MIME_TYPE_IMAGE_JPEG = 'image/jpeg';

	const MAX_FILE_SIZE_DOWNLOAD = 20 * 1024 * 1024; // in bytes

	public function __construct($update)
	{
		parent::__construct($update);

		$collection = new BetterLocationCollection();

		$document = $this->update->message->document;
		if ($document->mime_type === self::MIME_TYPE_IMAGE_JPEG) {
			if ($document->file_size > self::MAX_FILE_SIZE_DOWNLOAD) {
				if ($this->isPm() === true) { // Send error only if PM
					$collection->add(new InvalidLocationException(sprintf('%s I can\'t check for location in image\'s EXIF, because file is too big (> 20 MB, Telegram bot API limit).', Icons::ERROR)));
				}
			} else {
				$this->sendAction();
				try {
					$getFile = new Telegram\Methods\GetFile();
					$getFile->file_id = $document->file_id;
					/** @var Telegram\Types\File $response */
					$response = $this->run($getFile);
					$fileLink = TelegramHelper::getFileUrl(Config::TELEGRAM_BOT_TOKEN, $response->file_path);
					$betterLocationExif = BetterLocation::fromExif($fileLink);
					if ($betterLocationExif instanceof BetterLocation) {
						$collection->add($betterLocationExif);
					}
				} catch (\Throwable $exception) {
					Debugger::log($exception, ILogger::EXCEPTION);
					if ($this->isPm() === true) { // Send error only if PM
						$collection->add(new InvalidLocationException(sprintf('%s Unexpected error occured while searching EXIF in image. Contact Admin for more info.', Icons::ERROR)));
					}
				}
			}
		}
		
		$collection->mergeCollection(BetterLocation::generateFromTelegramMessage(
			$this->update->message->caption,
			$this->update->message->caption_entities
		));

		$processedCollection = new ProcessedMessageResult($collection);
		$processedCollection->process();
		if ($collection->count() > 0) {
			$this->reply(
				TelegramHelper::MESSAGE_PREFIX . $processedCollection->getText(),
				[
					'disable_web_page_preview' => true,
					'reply_markup' => $processedCollection->getMarkup(1),
				],
			);
		} else { // No detected locations or occured errors
			if ($this->isPm() === true) {
				$message = 'Hi there in PM!' . PHP_EOL;
				$message .= 'Thanks for the ';
				if ($this->isForward()) {
					$message .= 'forwarded ';
				}
				$message .= 'file but I\'m not sure, what to do... No location in EXIF was found.';
				$this->reply($message);
			} else {
				// do not send anything to chat
			}
		}
	}
}


