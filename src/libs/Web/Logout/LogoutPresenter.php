<?php declare(strict_types=1);

namespace App\Web\Logout;

use App\Config;
use App\Web\MainPresenter;

class LogoutPresenter extends MainPresenter
{
	public function action(): void
	{
		if ($this->login->isLogged()) {
			$this->login->logout();
		}
		$this->flashMessage(sprintf('You were logged out. <a href="%s">Log back in</a>.', Config::getLoginUrl()));
		$this->redirect(Config::getAppUrl());
	}
}

