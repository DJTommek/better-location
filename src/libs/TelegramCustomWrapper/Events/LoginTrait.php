<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events;

use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\Command\LoginCommand;
use App\TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

trait LoginTrait
{
	abstract function isTgPm(): ?bool;

	protected function processLogin2(): array
	{
		if ($this->isTgPm() === false) {
			$text = sprintf(
				'%s Command <code>%s</code> is available only in private message, open @%s.',
				Icons::ERROR,
				LoginCommand::getTgCmd(),
				Config::TELEGRAM_BOT_NAME
			);
			return [$text, null, []];
		}

		$appUrl = Config::getAppUrl();
		$text = sprintf('%s <b>Login</b> for <a href="%s">%s</a>.', Icons::LOGIN, $appUrl->getAbsoluteUrl(), $appUrl->getDomain(0)) . PHP_EOL;
		$text .= sprintf('Click on button below to login to access your settings, favourites, etc. on %s website', $appUrl->getDomain(0));

		$replyMarkup = new Markup();
		$replyMarkup->inline_keyboard[] = [
			TelegramHelper::loginUrlButton('Login in browser')
		];

		return [$text, $replyMarkup, []];
	}
}
