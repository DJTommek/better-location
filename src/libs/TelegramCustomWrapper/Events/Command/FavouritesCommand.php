<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\FavouritesTrait;
use App\TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class FavouritesCommand extends Command
{
	use FavouritesTrait;

	const CMD = '/favourites';
	const ICON = Icons::FAVOURITE;
	const DESCRIPTION = 'Manage your saved favourite locations';

	public function handleWebhookUpdate(): void
	{
		if ($this->isTgPm() === true) {
			[$text, $markup, $options] = $this->processFavouritesList();
			$this->reply($text, $markup, $options);
		} else {
			$replyMarkup = new Markup();
			$replyMarkup->inline_keyboard = [
				[ // row of buttons
					new Button([
						'text' => sprintf('%s Open in PM', Icons::FAVOURITE),
						'url' => TelegramHelper::generateStart(sprintf('%s %s', StartCommand::FAVOURITE, StartCommand::FAVOURITE_LIST)),
					]),
				],
			];

			$this->reply(sprintf('%s Command <code>%s</code> is available only in private message, open @%s.', Icons::ERROR, HelpCommand::getTgCmd(), Config::TELEGRAM_BOT_NAME), $replyMarkup);
		}
	}
}
