<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Icons;
use App\TelegramCustomWrapper\Events\LoginTrait;


class LoginCommand extends Command
{
	use LoginTrait;

	const CMD = '/login';
	const ICON = Icons::LOGIN;
	const DESCRIPTION = 'Sign in to website';

	public function handleWebhookUpdate()
	{
		[$text, $markup, $options] = $this->processLogin2();
		$this->reply($text, $markup, $options);
	}
}
