<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events;

use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\Button\FavouritesButton;
use App\TelegramCustomWrapper\Events\Button\HelpButton;
use App\TelegramCustomWrapper\Events\Command\FavouritesCommand;
use App\TelegramCustomWrapper\Events\Command\FeedbackCommand;
use App\TelegramCustomWrapper\Events\Command\HelpCommand;
use App\TelegramCustomWrapper\Events\Command\SettingsCommand;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

trait HelpTrait
{
	/**
	 * @throws \App\BetterLocation\Service\Exceptions\NotSupportedException
	 */
	protected function processHelp(): array
	{
		$text = sprintf('%s Welcome to @%s!', Icons::LOCATION, Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('I\'m a simple but smart bot to catch all possible location formats in any chats you invite me to, and generate links to your favourite location services such as Google maps, Waze, OpenStreetMap etc.') . PHP_EOL;
		$text .= sprintf(
				'For example, if you send a message containing the coordinates "<code>%s</code>" or the link "%s" I will respond with this:',
				$this->processExample->getLatLon(),
				$this->processExample->getExampleInput(),
			) . PHP_EOL;
		$text .= PHP_EOL;
		$text .= $this->processExample->getExampleLocation()->generateMessage($this->getMessageSettings());
		// @TODO newline is filled in $result (yeah, it shouldn't be like that..)
		$text .= sprintf('%s <b>Formats I can read:</b>', Icons::FEATURES) . PHP_EOL;
		$text .= sprintf('- coordinates: <a href="%s">WGS84</a>, <a href="%s">USNG</a>, <a href="%s">MGRS</a>, <a href="%s">UTM</a>, ...',
				'https://en.wikipedia.org/wiki/World_Geodetic_System',
				'https://en.wikipedia.org/wiki/United_States_National_Grid',
				'https://en.wikipedia.org/wiki/Military_Grid_Reference_System',
				'https://en.wikipedia.org/wiki/Universal_Transverse_Mercator_coordinate_system',
			) . PHP_EOL;
		$text .= sprintf('- codes: <a href="%s">Geocaching GCxxx</a>, <a href="%s">What3Words</a>, <a href="%s">Open Location Codes</a>, ...',
				'https://geocaching.com/',
				'https://what3words.com/',
				'https://plus.codes/',
			) . PHP_EOL;
		$text .= sprintf('- links: google.com, glympse.com, mapy.cz, intel.ingress.com, ...') . PHP_EOL;
		$text .= sprintf('- short links: goo.gl, bit.ly, tinyurl.com, t.co, tiny.cc, ...') . PHP_EOL;
		$text .= sprintf('- Telegram (live) location and venues') . PHP_EOL;
		$text .= sprintf('- <a href="%s">Exif</a> from <b>uncompressed</b> images', 'https://wikipedia.org/wiki/Exif') . PHP_EOL;
		$text .= PHP_EOL;
		$text .= sprintf('%s <b>Inline:</b>', Icons::INLINE) . PHP_EOL;
		$text .= sprintf('To send my Better locations to a group I am not in, or to a private chat, just type <code>@%s</code>', Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('- add any link, text, special code etc and choose one of the output') . PHP_EOL;
		$text .= sprintf('- send your current position (on mobile devices only)') . PHP_EOL;
		$text .= sprintf('- send previously saved favourited locations') . PHP_EOL;
		$text .= sprintf('- search literally anything via Google search API') . PHP_EOL;
		$text .= sprintf('%s <a href="https://t.me/BetterLocationInfo/8">See video here</a>', Icons::VIDEO) . PHP_EOL;
		$text .= PHP_EOL;
//		$text .= sprintf('%s <b>Private chat:</b>', Icons::USER) . PHP_EOL;
//		$text .= sprintf('Just send me some link to or coordinate and I will generate message <b>just</b> for you.') . PHP_EOL;
//		$text .= PHP_EOL;
//		$text .= sprintf('%s <b>Group chat:</b>', Icons::GROUP) . PHP_EOL;
//		$text .= sprintf('Almost everything is the same as in private message except:') . PHP_EOL;
//		$text .= sprintf('I will be quiet unless someone use command which I know or send some location') . PHP_EOL;
//		$text .= PHP_EOL;
//		$text .= sprintf('%s <b>Channel:</b>', Icons::CHANNEL) . PHP_EOL;
//		$text .= sprintf('Currently not supported. Don\'t hesitate to ping author if you are interested in this feature.') . PHP_EOL;
//		$text .= PHP_EOL;
		$text .= sprintf('%s <b>Commands:</b>', Icons::COMMAND) . PHP_EOL;
		$text .= sprintf('%s - %s Learn more about me (this text)', HelpCommand::getTgCmd(!$this->isTgPm()), Icons::INFO) . PHP_EOL;
		$text .= sprintf('%s - %s Report invalid location or just contact the author', FeedbackCommand::getTgCmd(!$this->isTgPm()), Icons::FEEDBACK) . PHP_EOL;
		$text .= sprintf('%s - %s Manage your saved favourite locations (works only in PM)', FavouritesCommand::getTgCmd(!$this->isTgPm()), Icons::FAVOURITE) . PHP_EOL;
		$text .= sprintf('%s - %s Adjust your settings', SettingsCommand::getTgCmd(!$this->isTgPm()), Icons::SETTINGS) . PHP_EOL;
		$text .= PHP_EOL;
		$text .= sprintf('%s For more info check out the <a href="%s">@BetterLocationInfo</a> channel.', Icons::INFO, 'https://t.me/BetterLocationInfo/3') . PHP_EOL;
		$text .= PHP_EOL;

//		$text .= sprintf(Icons::WARNING . ' <b>Warning</b>: Bot is currently in active development so there is no guarantee that it will work at all times. Check Github for more info.') . PHP_EOL;

		$markup = $this->getHelpButtons();

		return [
			$text,
			$markup,
			[
				'disable_web_page_preview' => true,
			],
		];
	}

	private function getHelpButtons(): Markup
	{
		$replyMarkup = new Markup();
		$replyMarkup->inline_keyboard = [
			[ // first row
				new Button([
					'text' => sprintf('%s Help', Icons::REFRESH),
					'callback_data' => HelpButton::CMD,
				]),
			],
			[ // second row
				new Button([
					'text' => sprintf('%s Try inline searching', Icons::INLINE),
					'switch_inline_query_current_chat' => 'Czechia Prague',
				]),
			],
		];

		if ($this->isTgPm() === true) {
			// add buton into first row
			$replyMarkup->inline_keyboard[0][] = new Button([
				'text' => sprintf('%s Favourites', Icons::FAVOURITE),
				'callback_data' => sprintf('%s %s', FavouritesButton::CMD, FavouritesButton::ACTION_REFRESH),
			]);
		}
		return $replyMarkup;
	}
}
