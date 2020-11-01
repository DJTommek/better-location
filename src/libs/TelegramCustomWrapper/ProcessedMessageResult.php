<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Icons;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class ProcessedMessageResult
{
	/** @var BetterLocationCollection */
	private $collection;

	private $resultText = '';
	private $buttons = [];
	private $autorefreshEnabled = false;

	private $validLocationsCount = 0;

	public function __construct(BetterLocationCollection $collection)
	{
		$this->collection = $collection;
	}

	public function setAutorefresh(bool $enabled): void
	{
		$this->autorefreshEnabled = $enabled;
	}

	public function process(bool $printAllErrors = false): self
	{
		foreach ($this->collection->getLocations() as $betterLocation) {
			$this->resultText .= $betterLocation->generateMessage();
			$rowButtons = $betterLocation->generateDriveButtons();
			$rowButtons[] = $betterLocation->generateAddToFavouriteButtton();
			$this->buttons[] = $rowButtons;
			$this->validLocationsCount++;
		}
		foreach ($this->collection->getErrors() as $error) {
			if ($error instanceof InvalidLocationException) {
				$this->resultText .= Icons::ERROR . $error->getMessage() . PHP_EOL;
			} else {
				if ($printAllErrors) {
					$this->resultText .= Icons::ERROR . ' Unexpected error occured while proceessing message for locations.' . PHP_EOL;
				}
				Debugger::log($error, Debugger::EXCEPTION);
			}
		}
		return $this;
	}

	public function getButtons(?int $maxRows = null): array
	{
		$result = $this->buttons;
		if ($maxRows > 0) {
			$result = array_slice($result, 0, $maxRows);
		}
		if ($this->collection->hasRefreshableLocation()) {
			$result[] = BetterLocation::generateRefreshButtons($this->autorefreshEnabled);
		}
		return $result;
	}

	public function getMarkup(?int $maxRows = null): Markup
	{
		$markup = new Markup();
		$markup->inline_keyboard = $this->getButtons($maxRows);
		return $markup;
	}

	public function getText(): string
	{
		return $this->resultText;
	}

	public function validLocationsCount(): int
	{
		return $this->validLocationsCount;
	}

	public function getCollection(): BetterLocationCollection
	{
		return $this->collection;
	}

}
