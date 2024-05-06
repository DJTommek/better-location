<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events;

use App\Factory;
use App\Logger\CustomTelegramLogger;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class EventFactory
{
	public function __construct(
		private readonly  CustomTelegramLogger $customTelegramLogger
	) {

	}

	/**
	 * @param class-string $event
	 */
	public function create(string $event, Update $update): Events
	{
		$event = Factory::getContainer()->get($event);
		assert($event instanceof Events);
		$event->init($this->customTelegramLogger, $update);
		return $event;
	}
}
