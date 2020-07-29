<?php

namespace TelegramCustomWrapper\Events\Command;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Coordinates\WG84DegreesService;
use BetterLocation\Service\WazeService;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use \Icons;

class HelpCommand extends Command
{
	/**
	 * HelpCommand constructor.
	 *
	 * @param $update
	 * @throws InvalidLocationException
	 * @throws \Exception
	 */
	public function __construct($update) {
		parent::__construct($update);
		$this->processHelp();
	}
}