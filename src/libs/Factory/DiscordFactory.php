<?php declare(strict_types=1);

namespace App\Factory;

use App\Config;
use Discord\Discord;

readonly class DiscordFactory
{
	public function __construct(
		#[\SensitiveParameter] private string $token,
	) {
	}

	public function create(): Discord
	{
		$monolog = new \Monolog\Logger('Discord');
		$monolog->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout'));
		$monolog->pushHandler(new \Monolog\Handler\RotatingFileHandler(Config::FOLDER_DATA . '/monolog/discord.log'));

		return new Discord([
			'token' => $this->token,
			'intents' => [
				\Discord\WebSockets\Intents::MESSAGE_CONTENT,
				\Discord\WebSockets\Intents::GUILD_MESSAGES,
				\Discord\WebSockets\Intents::DIRECT_MESSAGES,
			],
			'logger' => $monolog,
		]);
	}
}
