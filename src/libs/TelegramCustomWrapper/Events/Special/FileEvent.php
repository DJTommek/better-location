<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\FromExif;
use App\BetterLocation\FromTelegramMessage;
use App\Config;
use App\TelegramCustomWrapper\TelegramHelper;
use App\TelegramCustomWrapper\UniversalHandleLocationTrait;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types;

class FileEvent extends Special
{
	use UniversalHandleLocationTrait;

	private const MIME_TYPE_IMAGE_JPEG = 'image/jpeg';
	private const MAX_FILE_SIZE_DOWNLOAD = 20 * 1024 * 1024; // in bytes

	private bool $fileTooBig = false;
	private ?BetterLocationCollection $collection = null;

	public function __construct(
		private readonly FromTelegramMessage $fromTelegramMessage,
	) {
	}

	public function getCollection(): BetterLocationCollection
	{
		if ($this->collection === null) {
			$this->collection = new BetterLocationCollection();

			$document = $this->getProcessableDocument();
			if ($document !== null) {
				$locationFromFile = $this->getLocationFromDocument($document);
				if ($locationFromFile !== null) {
					$this->collection->add($locationFromFile);
				}
			}
			$tgMessage = $this->getTgMessage();

			$this->collection->add($this->fromTelegramMessage->getCollection(
				$tgMessage->caption,
				$tgMessage->caption_entities,
			));
		}

		return $this->collection;
	}

	private function getProcessableDocument(): ?Telegram\Types\Document
	{
		$document = $this->getTgMessage()->document;
		if ($document->mime_type !== self::MIME_TYPE_IMAGE_JPEG) {
			return null;
		}

		if ($document->file_size > self::MAX_FILE_SIZE_DOWNLOAD) {
			$this->fileTooBig = true;
			return null;
		}
		return $document;
	}

	private function getLocationFromDocument(Telegram\Types\Document $document): ?BetterLocation
	{
		try {
			$getFile = new Telegram\Methods\GetFile();
			$getFile->file_id = $document->file_id;
			$response = $this->runSmart($getFile);
			assert($response instanceof Telegram\Types\File);
			$fileLink = TelegramHelper::getFileUrl(Config::TELEGRAM_BOT_TOKEN, $response->file_path);
			$fromExif = new FromExif($fileLink);
			$fromExif->run();
			return $fromExif->location;
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
		}

		return null;
	}

	public function handleWebhookUpdate(): void
	{
		if ($this->isTgChannelPost() === false && $this->getProcessableDocument() !== null) {
			$this->sendAction();
		}

		if ($this->getCollection()->isEmpty()) {
			$this->replyEmpty();
			return;
		}

		$this->universalHandle();
	}

	private function replyEmpty(): void
	{
		if ($this->isTgPm() !== true) {
			return;
		}

		$isRefreshable = $this->collection->hasRefreshableLocation();
		if ($isRefreshable) {
			$markup = new Types\Inline\Keyboard\Markup();
			$markup->inline_keyboard = [
				BetterLocation::generateRefreshButtons(false),
			];
		}

		$message = 'Hi there!' . PHP_EOL;
		$message .= 'Thanks for the ';
		if ($this->isTgForward()) {
			$message .= 'forwarded ';
		}
		$message .= 'file but ';
		if ($this->fileTooBig) {
			$message .= 'it is too big (> 20 MB, Telegram bot API limit), so I can\'t process it.';
		} else {
			$message .= 'I\'m not sure, what to do... No location in EXIF was found.';
		}
		$response = $this->reply($message, $markup ?? null);

		if ($isRefreshable) {
			$this->addToUpdateDb(
				$response,
				$message,
				$markup,
			);
		}
	}
}


