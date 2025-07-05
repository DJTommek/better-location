<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events;

use App\BetterLocation\MessageGeneratorInterface;
use App\Chat;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\Button\FavouritesButton;
use App\TelegramCustomWrapper\Events\Button\HelpButton;
use App\User;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

trait FavouritesTrait
{
	abstract function getUser(): User;

	abstract function getChat(): ?Chat;

	abstract function getMessageGenerator(): MessageGeneratorInterface;

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
				$text .= $favourite->generateMessage($this->getMessageSettings(), $this->getMessageGenerator());

				$shareFavouriteButton = new Button();
				$shareFavouriteButton->text = sprintf('Share %s', htmlspecialchars_decode($favourite->getPrefixMessage()));
				$shareFavouriteButton->switch_inline_query = (string)$favourite;

				$replyMarkup->inline_keyboard[] = [$shareFavouriteButton];
			}
		}
		$text .= sprintf('%s To add a location to your favourites, open any link leading to BetterLocation website in any location and click on %s icon. Just make sure, that you are logged in.', Icons::INFO, Icons::FAVOURITE) . PHP_EOL;

		return [
			$text,
			$replyMarkup,
			[
				'disable_web_page_preview' => !$this->getChat()->settingsPreview(),
			],
		];
	}
}
