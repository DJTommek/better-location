<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Edit;

use App\TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram;

class LocationEdit extends Edit
{
	/** @var bool is sended location live location */
	private $live;

	public function handleWebhookUpdate()
	{
		$this->live = TelegramHelper::isLocation($this->update, true);

		if ($this->live) {
			$this->user->setLastKnownLocation($this->update->edited_message->location->latitude, $this->update->edited_message->location->longitude);
		}
	}
}


