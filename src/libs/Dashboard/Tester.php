<?php declare(strict_types=1);

namespace App\Dashboard;

use App\BetterLocation\BetterLocationCollection;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;

class Tester
{
	/** @var ?string */
	private $input;

	private $outputText = '';
	private $outputButtons = [];

	public function __construct(?string $input)
	{
		$this->input = is_string($input) ? trim($input) : null;
	}

	public function getInput(): ?string
	{
		return $this->input;
	}

	public function getTextareaInput(): ?string
	{
		return $this->input ?? '';
	}

	public function isInput()
	{
		return is_null($this->input) === false;
	}

	public function handleInput(): void
	{
		$entities = TelegramHelper::generateEntities($this->getInput());
		$collection = BetterLocationCollection::fromTelegramMessage($this->getInput(), $entities);
		$processedCollection = new ProcessedMessageResult($collection);
		$processedCollection->process(true);
		if ($collection->count() > 0) {
			$this->outputText = trim($processedCollection->getText());
			$this->outputButtons = $processedCollection->getButtons(1);
		}
	}

	public function getOutputText(): string
	{
		return $this->outputText;
	}

	public function isOutputTextEmpty(): bool
	{
		return empty($this->outputText);
	}

	/**
	 * @return Button[][]
	 */
	public function getOutputButtons(): array
	{
		return $this->outputButtons;
	}
}
