<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class LoginCommand extends Command
{
	const CMD = '/login';
	const ICON = Icons::LOGIN;
	const DESCRIPTION = 'Sign in to website';

	public function handleWebhookUpdate()
	{
		$appUrl = Config::getAppUrl();
		$text = sprintf('%s <b>Login</b> for <a href="%s">%s</a>.', Icons::LOGIN, $appUrl->getAbsoluteUrl(), $appUrl->getDomain(0)) . PHP_EOL;
		$text .= sprintf('Click on button below to login to access your settings, favourites, etc. on %s website', $appUrl->getDomain(0));

		$replyMarkup = new Markup();
		$replyMarkup->inline_keyboard[] = [
			TelegramHelper::loginUrlButton('Login in browser')
		];

		$this->reply($text, $replyMarkup);
	}
}
