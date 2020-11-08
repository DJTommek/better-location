<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\Icons;
use Tracy\Debugger;
use Tracy\ILogger;

class InvalidButton extends Button
{
	public function __construct($update)
	{
		parent::__construct($update);
		Debugger::log(sprintf('Invalid button detected, check update ID %s ', $this->update->update_id), ILogger::WARNING);
		$this->flash(sprintf('%s This button is invalid.%sIf you believe that this is error, please contact admin.', Icons::ERROR, PHP_EOL), true);
	}
}
