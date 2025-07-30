<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\FromTelegramMessage;
use App\TelegramCustomWrapper\UniversalHandleLocationTrait;
use unreal4u\TelegramAPI\Telegram;

class ChannelPostEvent extends Special
{
	use UniversalHandleLocationTrait;

	private ?BetterLocationCollection $collection = null;

	public function __construct(
		private readonly FromTelegramMessage $fromTelegramMessage,
	) {
	}

	public function getTgMessage(): Telegram\Types\Message
	{
		return $this->update->channel_post;
	}

	public function getCollection(): BetterLocationCollection
	{
		if ($this->collection === null) {
			$this->collection = $this->fromTelegramMessage->getCollection(
				$this->getTgText(),
				$this->getTgMessage()->entities,
			);
		}
		return $this->collection;
	}

	public function handleWebhookUpdate(): void
	{
		if ($this->matchesIgnoreFilter()) {
			return;
		}

		$this->universalHandle();
	}
}
