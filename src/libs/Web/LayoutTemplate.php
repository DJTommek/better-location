<?php declare(strict_types=1);

namespace App\Web;

use App\Web\Login\LoginFacade;

class LayoutTemplate
{
	/** @var LoginFacade */
	public $login;

	public $flashMessages = [];

	public function setError(string $errorText): void
	{
		$this->flashMessage($errorText, FlashMessage::FLASH_ERROR);
	}

	public function flashMessage($text, $type = FlashMessage::FLASH_INFO)
	{
		$this->flashMessages[] = new FlashMessage($text, $type);
	}
}
