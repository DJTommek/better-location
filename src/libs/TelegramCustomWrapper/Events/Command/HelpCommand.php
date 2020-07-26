<?php

namespace TelegramCustomWrapper\Events\Command;

use BetterLocation\BetterLocation;
use BetterLocation\Service\WazeService;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use \Icons;

class HelpCommand extends Command
{
	/**
	 * HelpCommand constructor.
	 *
	 * @param $update
	 * @param $tgLog
	 * @param $loop
	 * @throws InvalidLocationException
	 * @throws \Exception
	 */
	public function __construct($update, $tgLog, $loop) {
		parent::__construct($update, $tgLog, $loop);

		$lat = 50.0877258;
		$lon = 14.4211267;
		$wazeLink = WazeService::getLink($lat, $lon);
		$betterLocationWaze = WazeService::parseCoords($wazeLink);
		$betterLocationCoords = new BetterLocation($lat, $lon, 'Coords');

		$text = sprintf('%s Welcome to @%s!', Icons::LOCATION, TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('I\'m simple but smart bot to catch all possible location formats and generate links to most used location services as Google maps, Waze, OpenStreetMaps etc.') . PHP_EOL;
		$text .= sprintf('I can work in group too! Just add me and thats all.') . PHP_EOL;
		$text .= sprintf('Example if you send coordinates "<code>%1$f,%2$f</code>" or link "https://www.waze.com/ul?ll=%1$f,%2$f" I will respond with this:', $lat, $lon) . PHP_EOL;
		$text .= PHP_EOL;
		$text .= $betterLocationCoords->generateBetterLocation();
		$text .= $betterLocationWaze->generateBetterLocation();
		// @TODO newline is filled in $result (yeah, it shouldn't be like that..)
		$text .= sprintf('%s <b>Features:</b>', Icons::FEATURES) . PHP_EOL;
		$text .= sprintf('- coordinates: WGS84 (decimal, degrees and even seconds) etc.') . PHP_EOL;
		$text .= sprintf('- special codes: <a href="%s">What3Words</a>, <a href="%s">Open Location Codes</a> etc.', 'https://what3words.com/', 'https://plus.codes/') . PHP_EOL;
		$text .= sprintf('- URL links: google.com, mapy.cz, intel.ingress.com etc.') . PHP_EOL;
		$text .= sprintf('- short URL links: goo.gl, mapy.cz, <a href="%s">Waze</a> etc.', 'https://www.waze.com/') . PHP_EOL;
		$text .= sprintf('- Telegram location') . PHP_EOL;
		$text .= sprintf('- EXIF from <b>uncompressed</b> images') . PHP_EOL;
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
		$text .= sprintf('/help - this text') . PHP_EOL;
//		$text .= sprintf('/debug - get your and chat ID') . PHP_EOL;
//		$text .= sprintf('/settings - adjust behaviour in this chat') . PHP_EOL;
		$text .= sprintf('/feedback - report invalid location, ask for adding new or just contact author') . PHP_EOL;
		$text .= PHP_EOL;
		$text .= sprintf('Official Github: <a href="%1$s%2$s">%2$s</a>', 'https://github.com/', 'DJTommek/better-location') . PHP_EOL;
		$text .= sprintf('Author: <a href="%1$s%2$s">@%2$s</a>', 'https://t.me/', 'DJTommek') . PHP_EOL;
		$text .= PHP_EOL;

//		$text .= sprintf(Icons::WARNING . ' <b>Warning</b>: Bot is currently in active development so there is no guarantee that it will work at all times. Check Github for more info.') . PHP_EOL;
		$this->reply($text, ['disable_web_page_preview' => true]);
	}
}