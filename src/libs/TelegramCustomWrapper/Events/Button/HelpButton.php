<?php

namespace TelegramCustomWrapper\Events\Button;

class HelpButton extends Button
{
	public function __construct($update) {
		parent::__construct($update);
		$this->flash(sprintf('This button actually doesn\'t doing anything right now...%sBut it will be!', PHP_EOL), true);
//		$this->processHelp(true);
	}
}