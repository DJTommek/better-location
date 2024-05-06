<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events;

use App\Logger\CustomTelegramLogger;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class EventFactory
{
	/**
	 * @var array<class-string,Events>
	 */
	private readonly array $events;

	/**
	 * @param CustomTelegramLogger $customTelegramLogger
	 * @param iterable<object> $events
	 */
	public function __construct(
		private readonly CustomTelegramLogger $customTelegramLogger,
		iterable $events,
	) {
		$eventsClean = [];
		foreach ($events as $event) {
			// @TODO {rqd9s3z9i9} Condition can be removed once linked TODO is resolved
			if (($event instanceof Events) === false) {
				continue;
			}

			$eventsClean[$event::class] = $event;
		}

		$this->events = $eventsClean;
	}

	/**
	 * @param class-string $event
	 */
	public function create(string $event, Update $update): Events
	{
		$eventInstance = $this->events[$event];
		assert($eventInstance instanceof Events);
		$eventInstance->init($this->customTelegramLogger, $update);
		return $eventInstance;
	}
}
