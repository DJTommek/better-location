<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events;

use App\Factory\ObjectsFilterTrait;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class EventFactory
{
	use ObjectsFilterTrait;

	/**
	 * @var array<class-string<Events>,Events>
	 */
	private array $eventsArray = [];

	/**
	 * @param iterable<object> $events
	 */
	public function __construct(
		private readonly iterable $events,
	) {
	}

	/**
	 * @param class-string $event
	 */
	public function create(string $event, Update $update): Events
	{
		if ($this->eventsArray === []) {
			// @TODO {rqd9s3z9i9} Filter can be removed once linked TODO is resolved
			$filteredEvents = $this->filter($this->events, Events::class, true);
			$this->eventsArray = iterator_to_array($filteredEvents);
		}

		$eventInstance = $this->eventsArray[$event];
		assert($eventInstance instanceof Events);
		$eventInstance->setUpdateObject($update);
		return $eventInstance;
	}
}
