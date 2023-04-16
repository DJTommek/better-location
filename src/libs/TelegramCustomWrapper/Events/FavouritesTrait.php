<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events;

use App\Chat;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\Button\FavouritesButton;
use App\TelegramCustomWrapper\Events\Button\HelpButton;
use App\TelegramCustomWrapper\Events\Command\StartCommand;
use App\User;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

trait FavouritesTrait
{
	abstract function getUser(): User;

	abstract function getChat(): ?Chat;

	protected function processFavouritesList(): array
	{
		$replyMarkup = new Markup();
		$replyMarkup->inline_keyboard = [
			[ // row of buttons
				new Button([ // button
					'text' => sprintf('%s Help', Icons::BACK),
					'callback_data' => HelpButton::CMD,
				]),
				new Button([ // button
					'text' => sprintf('%s Refresh list', Icons::REFRESH),
					'callback_data' => sprintf('%s %s', FavouritesButton::CMD, FavouritesButton::ACTION_REFRESH),
				]),
			],
		];

		$text = sprintf('%s A list of <b>favourite</b> locations saved by @%s.', Icons::FAVOURITE, Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('Here you can manage your favourite locations, which will appear as soon as you type @%s in any chat.', Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('I don\'t have to be in that chat in order for it to work!') . PHP_EOL;
		$text .= PHP_EOL;
		if (count($this->getUser()->getFavourites()) === 0) {
			$text .= sprintf('%s Sadly, you don\'t have any favourite locations saved yet.', Icons::INFO) . PHP_EOL;
		} else {
			$staticMapUrl = $this->getUser()->getFavourites()->getStaticMapUrl();
			if ($staticMapUrl === null) {
				$text = Icons::INFO;
			} else {
				$text .= sprintf('<a href="%s">%s</a>', (string)$staticMapUrl, Icons::INFO);
			}
			$text .= sprintf(' You have %d favourite location(s):', count($this->getUser()->getFavourites())) . PHP_EOL;
			foreach ($this->getUser()->getFavourites() as $favourite) {
				$text .= $favourite->generateMessage($this->getMessageSettings());

				$shareFavouriteButton = new Button();
				$shareFavouriteButton->text = sprintf('Share %s', $favourite->getPrefixMessage());
				$shareFavouriteButton->switch_inline_query = $favourite->__toString();

				$replyMarkup->inline_keyboard[] = [$shareFavouriteButton];
				$buttonRow = [];

				$renameFavouriteButton = new Button();
				$renameFavouriteButton->text = sprintf('%s Rename', Icons::CHANGE);
				$renameFavouriteButton->switch_inline_query_current_chat = sprintf('%s %s %F %F %s',
					StartCommand::FAVOURITE,
					StartCommand::FAVOURITE_RENAME,
					$favourite->getLat(),
					$favourite->getLon(),
					mb_substr($favourite->getPrefixMessage(), 2), // Remove favourites icon and space (@TODO should not use getPrefixMessage())
				);
				$buttonRow[] = $renameFavouriteButton;

				$deleteFavouriteButton = new Button();
				$deleteFavouriteButton->text = sprintf('%s Delete', Icons::DELETE);
				$deleteFavouriteButton->callback_data = sprintf('%s %s %F %F', FavouritesButton::CMD, FavouritesButton::ACTION_DELETE, $favourite->getLat(), $favourite->getLon());
				$buttonRow[] = $deleteFavouriteButton;

				$replyMarkup->inline_keyboard[] = $buttonRow;
			}
		}
		$text .= sprintf('%s To add a location to your favourites, just send any link, coordinates etc. to me via PM and click on the %s button in my response.', Icons::INFO, Icons::FAVOURITE) . PHP_EOL;

		return [$text, $replyMarkup, [
			'disable_web_page_preview' => !$this->getChat()->settingsPreview()
		]];
	}
}
