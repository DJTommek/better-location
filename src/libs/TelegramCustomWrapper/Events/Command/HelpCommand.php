<?php

namespace TelegramCustomWrapper\Events\Command;

use BetterLocation\BetterLocation;
use \Icons;

class HelpCommand extends Command
{
	public function __construct($update, $tgLog, $loop) {
		parent::__construct($update, $tgLog, $loop);

		$lat = 50.0877258;
		$lon = 14.4211267;
		$betterLocation = new BetterLocation($lat, $lon, 'Coords');
		$result = $betterLocation->generateBetterLocation();

		$text = sprintf('%s Welcome to @%s!', Icons::LOCATION, TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('I\'m simple but smart bot to catch all possible location formats and generate links to most used location services as Google maps, Waze, OpenStreetMaps etc.') . PHP_EOL;
		$text .= sprintf('Example if you send coordinates <code>%1$f,%2$f</code> or link https://www.waze.com/ul?ll=%1$f,%2$f I will respond with this:', $lat, $lon) . PHP_EOL;
		$text .= PHP_EOL;
		$text .= $result;
		// @TODO newline is filled in $result (yeah, it shouldn't be like that..)
		$text .= sprintf('%s <b>Features:</b>', Icons::FEATURES) . PHP_EOL;
		$text .= sprintf('- coordinates: WGS84 (decimal, degrees and even seconds) etc.') . PHP_EOL;
		$text .= sprintf('- special codes: What3Words, Open Location Codes etc.') . PHP_EOL;
		$text .= sprintf('- URL links: google.com, mapy.cz, intel.ingress.com etc.') . PHP_EOL;
		$text .= sprintf('- short URL links: goo.gl, mapy.cz, Waze etc.') . PHP_EOL;
		$text .= sprintf('- Telegram location') . PHP_EOL;
		$text .= sprintf('- EXIF from <b>uncompressed</b> files') . PHP_EOL;
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
		$text .= sprintf('/debug - get your and chat ID') . PHP_EOL;
		$text .= PHP_EOL;

		$text .= sprintf(Icons::INFO . ' Note: Bot is currently in active development so there is no guarantee that it will work at all times. Check source code on Github <a href="%1$s%2$s">%2$s</a> for more info.', 'https://github.com/', 'DJTommek/better-location') . PHP_EOL;
		$this->reply($text, ['disable_web_page_preview' => true]);
	}
}