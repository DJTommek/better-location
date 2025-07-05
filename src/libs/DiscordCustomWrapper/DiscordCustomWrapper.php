<?php declare(strict_types=1);

namespace App\DiscordCustomWrapper;

use App\Address\AddressProvider;
use App\BetterLocation\FromTelegramMessage;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\SimpleLogger;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\WebSockets\Event;
use unreal4u\TelegramAPI\Telegram;

readonly class DiscordCustomWrapper
{
	public function __construct(
		private Discord $discord,
		private FromTelegramMessage $fromTelegramMessage,
		private DiscordMessageGenerator $discordMessageGenerator,
		private ?AddressProvider $addressProvider,
	) {
	}

	public function run(): void
	{
		$this->discord->getLogger()->debug('Initializing Discord wrapper...');
		$this->discord->on('init', fn() => $this->onInit());
		$this->discord->run();
	}

	private function onInit(): void
	{
		$this->discord->getLogger()->info(sprintf(
			'[' . __FUNCTION__ . '] Discord initialization is connected, bot ID: %s, username: "%s"',
			$this->discord->id,
			$this->discord->username,
		));
		$this->discord->on(Event::MESSAGE_CREATE, fn(\Discord\Parts\Channel\Message $message) => $this->onMessageCreate($message));
		$this->refreshGuilds();
	}

	private function onMessageCreate(\Discord\Parts\Channel\Message $message): void
	{
		try {
			SimpleLogger::log(SimpleLogger::NAME_DISCORD_INPUT, $message);

			if ($message->author->id === $this->discord->user->id) {
				$this->discord->getLogger()->debug(sprintf('Message ID %s is from myself, ignoring...', $message->id));
				return;
			}

			$entities = TelegramHelper::generateEntities($message->content);
			$collection = $this->fromTelegramMessage->getCollection($message->content, $entities);

			$this->discord->getLogger()->debug(sprintf('In message %d discovered %d locations', $message->id, $collection->count()));
			if ($collection->isEmpty()) {
				if ($message->guild_id === null) { // If no location found, send message only if chat is private.
					$message->reply('No locations were found.');
				}

				return;
			}

			$replyContent = '';
			$settings = new \App\TelegramCustomWrapper\BetterLocationMessageSettings();

			foreach ($collection as $location) {
				if ($location->hasAddress() === false) {
					$location->setAddress($this->addressProvider?->reverse($location)?->getAddress());
				}

				$replyContent .= PHP_EOL . $location->generateMessage(
						settings: $settings,
						generator: $this->discordMessageGenerator,
					);
			}
			SimpleLogger::log(SimpleLogger::NAME_DISCORD_OUTPUT, $replyContent);

			$message->reply(MessageBuilder::new()->setContent($replyContent));
		} catch (\Throwable $exception) {
			$this->discord->getLogger()->error(
				sprintf('[%s] Error when handling event: "%s".', __FUNCTION__, $exception->getMessage()),
				['exception' => $exception],
			);
		}
	}

	private function refreshGuilds(): void
	{
		$this->discord->getLogger()->debug('Refreshing guilds...');
		$this->discord->guilds->freshen()->then(function () {
			$guilds = $this->discord->guilds;
			$this->discord->getLogger()->debug(sprintf('List of guilds refreshed, found %d guilds.', $guilds->count()));
			foreach ($guilds as $guild) {
				assert($guild instanceof \Discord\Parts\Guild\Guild);
				$this->discord->getLogger()->debug(sprintf(
					'I\'m member of guild "%s" (id=%s) with %d members.',
					$guild->name,
					$guild->id,
					$guild->member_count,
				));
			}
		}, function (\Throwable $exception) {
			$this->discord->getLogger()->error(
				sprintf('Unable to refresh guilds: "%s"', $exception->getMessage()),
				['exception' => $exception],
			);
		});
	}
}
