<?php declare(strict_types=1);

namespace App\Web\Login;

use App\Config;
use App\TelegramCustomWrapper\Events\Command\LoginCommand;
use App\TelegramCustomWrapper\Events\Command\StartCommand;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Web\LayoutTemplate;

class LoginTemplate extends LayoutTemplate
{
	/** @var string */
	public $loginCommand = LoginCommand::CMD;
	/** @var string */
	public $botUsername = Config::TELEGRAM_BOT_NAME;
	/** @var string */
	public $botLink;
	/** @var string */
	public $botLinkLogin;

	public function prepare()
	{
		$this->botLink = TelegramHelper::userLink($this->botUsername);
		$this->botLinkLogin = TelegramHelper::generateStart(StartCommand::LOGIN);
	}
}

