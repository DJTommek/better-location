<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Edit;

use App\TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class LocationEditEvent extends Edit
{
	/** @var bool is sended location live location */
	private $live;

	public function __construct(Update $update)
	{
		parent::__construct($update);
		$this->live = TelegramHelper::isLocation($update, true);

		if ($this->live) {
			$this->user->setLastKnownLocation($update->edited_message->location->latitude, $update->edited_message->location->longitude);
		}
	}
}


