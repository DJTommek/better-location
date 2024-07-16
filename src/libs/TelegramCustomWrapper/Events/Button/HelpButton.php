<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\BetterLocation\ProcessExample;
use App\Icons;
use App\TelegramCustomWrapper\Events\Command\HelpCommand;
use App\TelegramCustomWrapper\Events\HelpTrait;

class HelpButton extends Button
{
	use HelpTrait;

	const CMD = HelpCommand::CMD;

	public function __construct(
		private readonly ProcessExample $processExample,
	) {
	}

	public function handleWebhookUpdate(): void
	{
		[$text, $markup, $options] = $this->processHelp();
		$this->replyButton($text, $markup, $options);
		$this->flash(sprintf('%s Help was refreshed.', Icons::REFRESH));
	}
}
