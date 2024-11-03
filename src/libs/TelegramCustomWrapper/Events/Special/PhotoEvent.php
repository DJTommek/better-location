<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\FromTelegramMessage;
use App\TelegramCustomWrapper\UniversalHandleLocationTrait;
use unreal4u\TelegramAPI\Telegram\Types;

class PhotoEvent extends Special
{
	use UniversalHandleLocationTrait;

	private ?BetterLocationCollection $collection = null;

	public function __construct(
		private readonly FromTelegramMessage $fromTelegramMessage,
	) {
	}

	public function getCollection(): BetterLocationCollection
	{
		if ($this->collection === null) {
			$this->collection = $this->fromTelegramMessage->getCollection(
				$this->getTgMessage()->caption,
				$this->getTgMessage()->caption_entities,
			);
		}

		return $this->collection;
	}

	public function handleWebhookUpdate(): void
	{
		if ($this->matchesIgnoreFilter()) {
			return;
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

		$message = 'Hi there in PM!' . PHP_EOL;
		$message .= 'Thanks for the ';
		if ($this->isTgForward()) {
			$message .= 'forwarded ';
		}
		$message .= 'photo but I\'m not sure, what to do... If you want to process location from EXIF, you have to send <b>uncompressed</b> photo (send as file).';
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


