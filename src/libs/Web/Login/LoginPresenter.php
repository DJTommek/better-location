<?php declare(strict_types=1);

namespace App\Web\Login;

use App\Config;
use App\Factory;
use App\Web\MainPresenter;
use Nette\Http\Url;

class LoginPresenter extends MainPresenter
{
	/** @var Url */
	private $redirectUrl;

	public function __construct()
	{
		parent::__construct();
		if ($this->login->isLogged()) {
			$this->redirect(Config::getAppUrl());
		} else if (\App\TelegramCustomWrapper\Login::hasRequiredGetParams($_GET)) {
			$tgLoginWrapper = new \App\TelegramCustomWrapper\Login($_GET);
			if ($tgLoginWrapper->isTooOld()) {
				$this->template->setError('Login URL is no longer valid. Try it again or log in via web.');
			} else if ($tgLoginWrapper->isVerified()) {
				$this->login->saveToDatabase($tgLoginWrapper);
				$this->login->setCookie($tgLoginWrapper->hash());
				$this->redirect(Config::getAppUrl());
			} else {
				$this->template->setError('Could not verify Telegram login URL. Try again or log in via web.');
			}
		}
	}

	public function render(): void
	{
		Factory::Latte('login.latte', $this->template);
	}
}

