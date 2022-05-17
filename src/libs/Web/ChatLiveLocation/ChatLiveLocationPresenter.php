<?php declare(strict_types=1);

namespace App\Web\ChatLiveLocation;

use App\TelegramCustomWrapper\Events\Special\LocationEvent;
use App\TelegramUpdateDb;
use App\Utils\General;
use App\Utils\Strict;
use App\Web\MainPresenter;
use unreal4u\TelegramAPI\Telegram;

class ChatLiveLocationPresenter extends MainPresenter
{

	public function action()
	{
		$result = new \stdClass();
		$result->locations = [];
		if (Strict::isInt($_GET['telegramId'] ?? false) === false) {
			$this->apiError('Invalid or missing Telegram chat ID');
		}
		$chatTelegramId = Strict::intval($_GET['telegramId']);
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
				$responseLocation->telegram_id = $locationEvent->getFromId();
				$responseLocation->telegram_displayname = $locationEvent->getFromDisplayname();
				$responseLocation->lat = $location->getLat();
				$responseLocation->lon = $location->getLon();
				$responseLocation->elevation = $location->getCoordinates()->getElevation();
				$responseLocation->address = $location->getAddress();
				$responseLocation->timezone = $location->getTimezoneData();
				$responseLocation->lastUpdate = $update->getLastUpdate()->getTimestamp();
				$result->locations[] = $responseLocation;

			}
		}
		$this->apiResponse($result);
	}
}

