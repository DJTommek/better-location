<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\Icons;
use App\TelegramCustomWrapper\Events\Command\FavouritesCommand;
use App\TelegramCustomWrapper\Events\FavouritesTrait;
use App\TelegramCustomWrapper\TelegramHelper;

class FavouritesButton extends Button
{
	use FavouritesTrait;

	const CMD = FavouritesCommand::CMD;

	const ACTION_REFRESH = 'refresh';

	public function handleWebhookUpdate(): void
	{
		$params = TelegramHelper::getParams($this->update);
		$action = array_shift($params);

		switch ($action) {
			case self::ACTION_REFRESH:
				[$text, $markup, $options] = $this->processFavouritesList();
				$this->replyButton($text, $markup, $options);
				$this->flash(sprintf('%s List of favourite locations was refreshed.', Icons::REFRESH));
				break;
			default:
				$this->flash(sprintf('%s This button (favourite) is invalid.%sIf you believe that this is error, please contact admin', Icons::ERROR, PHP_EOL), true);
				break;
		}
	}
}
