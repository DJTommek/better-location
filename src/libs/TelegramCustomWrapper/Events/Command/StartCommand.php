<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\BetterLocation\ProcessExample;
use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use App\Icons;
use App\TelegramCustomWrapper\Events\FavouritesTrait;
use App\TelegramCustomWrapper\Events\HelpTrait;
use App\TelegramCustomWrapper\Events\LoginTrait;
use App\TelegramCustomWrapper\Events\SettingsTrait;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Coordinates;
use App\Utils\Strict;
use Tracy\Debugger;
use Tracy\ILogger;

class StartCommand extends Command
{
	use HelpTrait;
	use FavouritesTrait;
	use SettingsTrait;
	use LoginTrait;

	const CMD = '/start';

	const FAVOURITE = 'f';
	const FAVOURITE_LIST = 'l';

	const SETTINGS = SettingsCommand::CMD;
	const LOGIN = LoginCommand::CMD;

	public function __construct(
		private readonly ProcessExample $processExample,
	) {
	}

	public function handleWebhookUpdate(): void
	{
		$encodedParams = TelegramHelper::getParams($this->update);
		if (count($encodedParams) === 0) {
			[$text, $markup, $options] = $this->processHelp();
			$this->reply($text, $markup, $options);
		} else if (count($encodedParams) === 1 && preg_match('/^(-?[0-9]{1,8})_(-?[0-9]{1,9})$/', $encodedParams[0], $matches)) {
			$this->processStartCoordinates($matches);
		} else {
			$params = explode(' ', TelegramHelper::InlineTextDecode($encodedParams[0]));
			$action = array_shift($params);
			switch ($action) {
				case self::FAVOURITE;
					$this->processFavourites($params);
					break;
				case self::SETTINGS;
					[$text, $markup, $options] = $this->processSettings();
					$this->reply($text, $markup, $options);
					break;
				case self::LOGIN;
					[$text, $markup, $options] = $this->processLogin2();
					$this->reply($text, $markup, $options);
					break;
				default:
					// Bot indexers can add their own start parameters, so if no valid parameter is detected, continue just like /start without parameter
					Debugger::log(sprintf('Hidden start parameter "%s" is unknown.', $this->getTgText()), ILogger::DEBUG);
					[$text, $markup, $options] = $this->processHelp();
					$this->reply($text, $markup, $options);
					break;
			}
		}
	}

	private function processStartCoordinates(array $matches)
	{
		$lat = Strict::intval($matches[1]) / 1000000;
		$lon = Strict::intval($matches[2]) / 1000000;
		if (Coordinates::isLat($lat) === false || Coordinates::isLon($lon) === false) {
			$this->reply(sprintf('%s Coordinates <code>%F,%F</code> are not valid.', Icons::ERROR, $lat, $lon));
		} else {
			try {
				$collection = WGS84DegreesService::processStatic($lat . ',' . $lon)->getCollection();
				$processedCollection = new ProcessedMessageResult($collection, $this->getMessageSettings(), $this->getPluginer(), $this->getIngressLanchedRuClient());
				$processedCollection->process();
				$this->reply($processedCollection->getText(), $processedCollection->getMarkup(1), ['disable_web_page_preview' => !$this->chat->settingsPreview()]);
			} catch (\Throwable $exception) {
				Debugger::log($exception, ILogger::EXCEPTION);
				$this->reply(sprintf('%s Unexpected error occured while processing coordinates in start command for Better location. Contact Admin for more info.', Icons::ERROR));
			}
		}
	}

	/**
	 * @param array $params
	 * @throws \Exception
	 */
	private function processFavourites(array $params)
	{
		[$text, $markup, $options] = $this->processFavouritesList();
		$this->reply($text, $markup, $options);
	}
}
