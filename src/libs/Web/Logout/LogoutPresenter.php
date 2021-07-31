<?php declare(strict_types=1);

namespace App\Web\Logout;

use App\Config;
use App\Web\MainPresenter;

class LogoutPresenter extends MainPresenter
{
	public function __construct()
	{
		parent::__construct();
		if ($this->login->isLogged()) {
			$this->login->logout();
		}
		$this->redirect(Config::APP_URL);
	}

	public function render(): void
	{
		// not aplicable
	}
}

