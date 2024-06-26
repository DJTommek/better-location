<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\FavouriteNameGenerator;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\Command\FavouritesCommand;
use App\TelegramCustomWrapper\Events\FavouritesTrait;
use App\TelegramCustomWrapper\TelegramHelper;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class FavouritesButton extends Button
{
	use FavouritesTrait;

	const CMD = FavouritesCommand::CMD;

	const ACTION_ADD = 'add';
	const ACTION_DELETE = 'delete';
	const ACTION_REFRESH = 'refresh';

	public function __construct(
		private readonly FavouriteNameGenerator $favouriteNameGenerator,
	) {
	}

	public function handleWebhookUpdate(): void
	{
		$params = TelegramHelper::getParams($this->update);
		$action = array_shift($params);

		switch ($action) {
			case self::ACTION_ADD:
				$lat = floatval($params[0]);
				$lon = floatval($params[1]);
				$this->addFavourite($lat, $lon);
				break;
			case self::ACTION_DELETE:
				$lat = floatval($params[0]);
				$lon = floatval($params[1]);
				$this->deleteFavourite($lat, $lon);

				[$text, $markup, $options] = $this->processFavouritesList();
				$this->replyButton($text, $markup, $options);
				$this->flash(sprintf('%s List of favourite locations was refreshed.', Icons::REFRESH));

				break;
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

	/**
	 * @param float $lat
	 * @param float $lon
	 * @throws \Exception
	 */
	private function addFavourite(float $lat, float $lon): void
	{
		try {
			$favourite = $this->user->getFavourite($lat, $lon);
			if ($favourite) {
				$this->flash(sprintf('%s This location (%s) is already saved in favourite list as %s.', Icons::INFO, $favourite->__toString(), $favourite->getPrefixMessage()), true);
			} else {
				$betterLocation = BetterLocation::fromLatLon($lat, $lon);
				$name = $this->favouriteNameGenerator->generate($betterLocation);
				$betterLocation = $this->user->addFavourite($betterLocation, $name);
				$this->flash(sprintf('%s Location %s was saved as %s.%sYou can now use it inline in any chat by typing @%s.',
					Icons::SUCCESS, $betterLocation->__toString(), $betterLocation->getPrefixMessage(), PHP_EOL, Config::TELEGRAM_BOT_NAME
				), true);
			}
		} catch (\Exception $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			$this->flash(sprintf('%s Can\'t save this location to favourites.%sIf you believe that this is error, please contact admin.', Icons::ERROR, PHP_EOL), true);
		}
	}

	/**
	 * @param float $lat
	 * @param float $lon
	 */
	private function deleteFavourite(float $lat, float $lon)
	{
		try {
			$replyMarkup = new Markup();
			$replyMarkup->inline_keyboard = [];

			$refreshFavouriteButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
			$refreshFavouriteButton->text = sprintf('%s Show list', Icons::REFRESH);
			$refreshFavouriteButton->callback_data = sprintf('%s %s', FavouritesButton::CMD, FavouritesButton::ACTION_REFRESH);
			$buttonRow[] = $refreshFavouriteButton;

			$deleteFavouriteButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
			$deleteFavouriteButton->text = sprintf('%s Add back', Icons::FAVOURITE);
			$deleteFavouriteButton->callback_data = sprintf('%s %s %F %F', FavouritesButton::CMD, FavouritesButton::ACTION_ADD, $lat, $lon);
			$buttonRow[] = $deleteFavouriteButton;

			$replyMarkup->inline_keyboard[] = $buttonRow;

			$favourite = $this->user->getFavourite($lat, $lon);
			if (is_null($favourite)) {
				$this->reply(sprintf('%s Location <code>%F,%F</code> was already removed from favourites.', Icons::INFO, $lat, $lon), $replyMarkup);
			} else {
				$this->user->deleteFavourite($favourite);
				$this->reply(sprintf('%s Location %s <code>%s</code> was removed from favourites.', Icons::SUCCESS, $favourite->getPrefixMessage(), $favourite->__toString()), $replyMarkup);
			}
		} catch (\Exception $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			$this->flash(sprintf('%s Unexpected error while removing location (%F,%F) from favourites.%sIf you believe that this is error, please contact admin.', Icons::ERROR, $lat, $lon, PHP_EOL), true);
		}
	}
}
