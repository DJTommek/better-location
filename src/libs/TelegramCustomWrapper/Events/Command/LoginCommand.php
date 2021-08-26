<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Icons;


class LoginCommand extends Command
{
	const CMD = '/login';
	const ICON = Icons::LOGIN;
	const DESCRIPTION = 'Sign in to website';

	public function handleWebhookUpdate()
	{
		$this->processLogin();
	}
}
