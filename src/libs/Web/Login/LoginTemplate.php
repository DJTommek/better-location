<?php declare(strict_types=1);

namespace App\Web\Login;

use App\TelegramCustomWrapper\Events\Command\LoginCommand;
use App\TelegramCustomWrapper\Events\Command\StartCommand;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Web\LayoutTemplate;

class LoginTemplate extends LayoutTemplate
{
	public string $loginCommand = LoginCommand::CMD;
	public string $botLinkLogin;

	public function prepare(): void
	{
		$this->botLinkLogin = TelegramHelper::generateStart(StartCommand::LOGIN);
	}
}

