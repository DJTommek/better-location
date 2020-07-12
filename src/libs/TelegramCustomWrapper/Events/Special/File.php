<?php

namespace TelegramCustomWrapper\Events\Special;

use \BetterLocation\BetterLocation;
use Tracy\Debugger;
use Tracy\ILogger;
use \Utils\Coordinates;
use \Icons;
use unreal4u\TelegramAPI\Telegram\Methods\GetFile;

class File extends \TelegramCustomWrapper\Events\Special\Special
{
	const MIME_TYPE_IMAGE_JPEG = 'image/jpeg';

	/**
	 * FileCommand constructor.
	 *
	 * @param $update
	 * @param $tgLog
	 * @param $loop
	 * @throws \Exception
	 */
	public function __construct($update, $tgLog, $loop) {
		parent::__construct($update, $tgLog, $loop);

		$replyMessage = '';
		$betterLocation = new BetterLocation($this->update->message->caption, $this->update->message->caption_entities);
		// PM or whitelisted group
		if ($this->isPm() || in_array($this->getChatId(), BetterLocation::TELEGRAM_GROUP_WHITELIST)) {
			$document = $this->update->message->document;
			if ($document->mime_type === self::MIME_TYPE_IMAGE_JPEG) {
				$this->sendAction();
				$getFile = new GetFile();
				$getFile->file_id = $document->file_id;

				$response = $this->run($getFile);

				$fileLink = \TelegramCustomWrapper\TelegramHelper::getFileUrl(TELEGRAM_BOT_TOKEN, $response->file_path);
				// Bug on older versions of PHP "Warning: exif_read_data(): Process tag(x010D=DocumentNam): Illegal components(0)" Tested with:
				// WEDOS Linux 7.3.1 (NOT OK)
				// WAMP Windows 7.3.5 (NOT OK)
				// WAMP Windows 7.4.7 (OK)
				//  https://bugs.php.net/bug.php?id=77142
				$exif = @exif_read_data($fileLink);
				if ($this->isExifLocation($exif)) {
					try {
						$replyMessage .= sprintf('EXIF: ');
						$replyMessage .= $betterLocation->generateBetterLocation(
							Coordinates::exifToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef']),
							Coordinates::exifToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef']),
						);
					} catch (\Exception $exception) {
						$this->reply(
							sprintf('%s Unexpected error occured while processing EXIF data from image for Better location. Contact Admin for more info.', Icons::ERROR),
							['disable_web_page_preview' => true],
						);
						Debugger::log($exception, ILogger::EXCEPTION);
						return;
					}
				}
			}
		}
		$result = $betterLocation->processMessage();
		if ($result) {
			$replyMessage .= $result;
		}
		if ($replyMessage) {
			$this->reply(sprintf('%s <b>Better location</b>', Icons::LOCATION) . PHP_EOL . $replyMessage);
		} else if ($this->isPm()) {
			$this->reply('Thanks for the file in PM! But I\'m not sure, what to do...');
		}
	}

	private function isExifLocation($exif) {
		return (
			$exif &&
			isset($exif['GPSLatitude']) &&
			isset($exif['GPSLongitude']) &&
			isset($exif['GPSLatitudeRef']) &&
			isset($exif['GPSLongitudeRef'])
		);

	}
}


