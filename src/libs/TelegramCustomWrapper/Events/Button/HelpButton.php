<?php declare(strict_types=1);

namespace TelegramCustomWrapper\Events\Button;

class HelpButton extends Button
{
	/**
	 * HelpButton constructor.
	 *
	 * @param $update
	 * @throws \BetterLocation\Service\Exceptions\InvalidLocationException
	 * @throws \Exception
	 */
	public function __construct($update) {
		parent::__construct($update);
		$this->processHelp(true);
	}
}
