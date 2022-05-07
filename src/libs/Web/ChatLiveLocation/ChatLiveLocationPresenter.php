<?php declare(strict_types=1);

namespace App\Web\ChatLiveLocation;

use App\Config;
use App\TelegramCustomWrapper\Events\Special\LocationEvent;
use App\TelegramUpdateDb;
use App\Utils\General;
use App\Utils\Strict;
use App\Web\MainPresenter;
use Nette\Utils\Json;
use unreal4u\TelegramAPI\Telegram;

class ChatLiveLocationPresenter extends MainPresenter
{

	public function action()
	{
		$result = new \stdClass();
		$result->locations = [];
		if (Strict::isInt($_GET['telegramId'] ?? null)) {
			$chatTelegramId = Strict::intval($_GET['telegramId'] ?? null);
			$updates = TelegramUpdateDb::findByChatId($chatTelegramId);

			foreach ($updates as $update) {
				$locationEvent = new LocationEvent($update->getOriginalUpdateObject());
				$locations = $locationEvent->getCollection();
				if (in_array(General::globalGetToBool('address'), [true, null], true)) { // if not set, default is true
					$locations->fillAddresses();
				}
				if (General::globalGetToBool('datetimezone') === true) {
					$locations->fillDatetimeZone();
				}
				if (General::globalGetToBool('elevation') === true) {
					$locations->fillElevations();
				}
				foreach ($locations as $location) {
					$responseLocation = new \stdClass();
//					$update->getChatId();
					$responseLocation->telegram_id = $locationEvent->getFromId();
					$responseLocation->telegram_displayname = $locationEvent->getFromDisplayname();
					$responseLocation->lat = $location->getLat();
					$responseLocation->lon = $location->getLon();
					$responseLocation->elevation = $location->getCoordinates()->getElevation();
					$responseLocation->address = $location->getAddress();
					$responseLocation->timezone = $location->getTimezoneData();
					$responseLocation->lastUpdate = $update->getLastUpdate()->format(Config::DATETIME_FORMAT_ZONE);
					$result->locations[] = $responseLocation;
				}
			}
		} else {
			die('no valid telegramId');
		}
		header('Content-Type: application/json');
		header('Access-Control-Allow-Origin: *');
		die(Json::encode($result, JSON_PRETTY_PRINT));
	}
}

