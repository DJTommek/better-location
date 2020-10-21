<?php declare(strict_types=1);

namespace TelegramCustomWrapper\Events\Command;

use TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class FavouritesCommand extends Command
{
	const CMD = '/favourites';

	/**
	 * FavouriteCommand constructor.
	 *
	 * @param $update
	 * @throws \Exception
	 */
	public function __construct($update) {
		parent::__construct($update);
		if ($this->isPm() === true) {
			$this->processFavouritesList(false);
		} else {
			$replyMarkup = new Markup();
			$replyMarkup->inline_keyboard = [
				[ // row of buttons
					[ // button
						'text' => sprintf('%s Open in PM', \Icons::FAVOURITE),
						'url' => TelegramHelper::generateStart(sprintf('%s %s', StartCommand::FAVOURITE, StartCommand::FAVOURITE_LIST)),
					],
				],
			];
			$messageSettings = [
				'disable_web_page_preview' => true,
				'reply_markup' => $replyMarkup,
			];

			$this->reply(sprintf('%s Command <code>%s</code> is available only in private message, open @%s.', \Icons::ERROR, HelpCommand::getCmd(), \Config::TELEGRAM_BOT_NAME), $messageSettings);
		}
	}
}
