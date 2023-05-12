<?php declare(strict_types=1);

namespace App\Web\Login;

use App\Config;
use App\TelegramCustomWrapper\Events\Command\LoginCommand;
use App\TelegramCustomWrapper\Events\Command\StartCommand;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Web\LayoutTemplate;

class LoginTemplate extends LayoutTemplate
{
	public string $loginCommand = LoginCommand::CMD;
	public string $botUsername = Config::TELEGRAM_BOT_NAME;
	public string $botLink;
	public string $botLinkLogin;

	public function prepare(): void
	{
		$this->botLink = TelegramHelper::userLink($this->botUsername);
		$this->botLinkLogin = TelegramHelper::generateStart(StartCommand::LOGIN);
	}
}

