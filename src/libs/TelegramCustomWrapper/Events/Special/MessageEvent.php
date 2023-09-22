<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocationCollection;
use App\Config;
use App\Factory;
use App\Icons;
use App\TelegramCustomWrapper\Events\Command\HelpCommand;
use App\TelegramCustomWrapper\UniversalHandleLocationTrait;
use Tracy\Debugger;

class MessageEvent extends Special
{
	use UniversalHandleLocationTrait;

	private ?BetterLocationCollection $collection = null;

	public function getCollection(): BetterLocationCollection
	{
		if ($this->collection === null) {
			$this->collection = BetterLocationCollection::fromTelegramMessage(
				$this->getTgText(),
				$this->getTgMessage()->entities,
			);
		}
		return $this->collection;
	}

	public function handleWebhookUpdate(): void
	{
		$collection = $this->getCollection();

		if (
			$this->isTgPm()
			&& $collection->isEmpty()
			&& mb_strlen($this->getTgText()) >= Config::GOOGLE_SEARCH_MIN_LENGTH
			&& Config::isGooglePlaceApi()
		) {
			try {
				$placeApi = Factory::googlePlaceApi();
				$googleCollection = $placeApi->searchPlace(
					$this->getTgText(),
					$this->getTgFrom()->language_code ?? null,
					$this->user->getLastKnownLocation(),
				);
				$collection->add($googleCollection);
			} catch (\Exception $exception) {
				Debugger::log($exception, Debugger::EXCEPTION);
			}
		}

		if ($this->getCollection()->isEmpty()) {
			$this->replyEmpty();
			return;
		}

		$this->universalHandle();
	}

	private function replyEmpty(): void
	{
		if ($this->isTgPm() !== true) {
			return;
		}

		$message = 'Hi there in PM!' . PHP_EOL;
		$message .= 'Thanks for the ';
		if ($this->isTgForward()) {
			$message .= 'forwarded ';
		}
		$message .= sprintf('message, but I didn\'t detected any location in that message. Use %s command to get info how to use me.', HelpCommand::getTgCmd()) . PHP_EOL;
		$message .= sprintf('%s Most used tips: ', Icons::INFO) . PHP_EOL;
		$message .= '- send me any message with location data (coords, links, Telegram location...)' . PHP_EOL;
		$message .= '- send me Telegram location' . PHP_EOL;
		$message .= '- send me <b>uncompressed</b> photos (as file) to process location from EXIF' . PHP_EOL;
		$message .= '- forward me any of above' . PHP_EOL;
		$this->reply($message);
	}
}


