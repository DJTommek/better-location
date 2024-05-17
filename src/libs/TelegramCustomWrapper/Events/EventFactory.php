<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events;

use App\Factory\ObjectsFilterTrait;
use App\Logger\CustomTelegramLogger;
use App\Repository\ChatRepository;
use App\Repository\FavouritesRepository;
use App\Repository\UserRepository;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class EventFactory
{
	use ObjectsFilterTrait;

	/**
	 * @var array<class-string,Events>
	 */
	private readonly array $events;

	/**
	 * @param CustomTelegramLogger $customTelegramLogger
	 * @param iterable<object> $events
	 */
	public function __construct(
		private readonly UserRepository $userRepository ,
		private readonly ChatRepository $chatRepository,
		private readonly FavouritesRepository $favouritesRepository,
		private readonly CustomTelegramLogger $customTelegramLogger,
		iterable $events,
	) {
		// @TODO {rqd9s3z9i9} Filter can be removed once linked TODO is resolved
		$this->events = iterator_to_array($this->filter($events, Events::class, true));
	}

	/**
	 * @param class-string $event
	 */
	public function create(string $event, Update $update): Events
	{
		$eventInstance = $this->events[$event];
		assert($eventInstance instanceof Events);
		$eventInstance->init(
			$this->userRepository,
			$this->chatRepository,
			$this->favouritesRepository,
			$this->customTelegramLogger,
			$update
		);
		return $eventInstance;
	}
}
