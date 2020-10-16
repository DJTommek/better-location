<?php declare(strict_types=1);

namespace Dashboard;

use BetterLocation\BetterLocation;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;
use unreal4u\TelegramAPI\Telegram\Types\WebhookInfo;
use unreal4u\TelegramAPI\TgLog;
use Utils\General;

class Tester
{
	/** @var @var ?string */
	private $input;

	private $outputText;
	private $outputButtons;

	public function __construct(?string $input) {
		$this->input = is_string($input) ? trim($input) : null;
	}

	public function getInput(): ?string {
		return $this->input;
	}

	public function getTextareaInput(): ?string {
		return $this->input ?? '';
	}

	public function isInput() {
		return is_null($this->input) === false;
	}

	public function handleInput(): void {
		$urls = \Utils\General::getUrls($this->getInput());
		// Simulate Telegram message by creating URL entities
		$entities = [];
		foreach ($urls as $url) {
			$entity = new MessageEntity();
			$entity->type = 'url';
			$entity->offset = mb_strpos($this->getInput(), $url);
			$entity->length = mb_strlen($url);
			$entities[] = $entity;
		}
		$this->outputText = '';
		$this->outputButtons = [];
		try {
			$betterLocations = \BetterLocation\BetterLocation::generateFromTelegramMessage($this->getInput(), $entities);
			$buttonLimit = 1; // @TODO move to config (chat settings)
			foreach ($betterLocations->getLocations() as $betterLocation) {
				$this->outputText .= $betterLocation->generateBetterLocation();
				if (count($this->outputButtons) < $buttonLimit) {
					$driveButtons = $betterLocation->generateDriveButtons();
					$driveButtons[] = $betterLocation->generateAddToFavouriteButtton();
					$this->outputButtons[] = $driveButtons;
				}
			}
			foreach ($betterLocations->getErrors() as $betterLocationError) {
				$this->outputText .= sprintf('<p>%s Error: <b>%s</b></p>', \Icons::ERROR, htmlentities($betterLocationError->getMessage()));
			}
		} catch (\Throwable $exception) {
			\Tracy\Debugger::log($exception, \Tracy\ILogger::EXCEPTION);
			$this->outputText .= sprintf('%s Error occured while processing input: %s', \Icons::ERROR, $exception->getMessage());
		}
		$this->outputText = trim($this->outputText);  // Telegram is doing trim too
	}

	public function getOutputText(): string {
		return $this->outputText;
	}

	public function isOutputTextEmpty(): bool {
		return empty($this->outputText);
	}

	/**
	 * @return Button[][]
	 */
	public function getOutputButtons(): array {
		return $this->outputButtons;
	}
}
