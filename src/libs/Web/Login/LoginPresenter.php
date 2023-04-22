<?php declare(strict_types=1);

namespace App\Web\Login;

use App\Config;
use App\Factory;
use App\Utils\Strict;
use App\Web\FlashMessage;
use App\Web\MainPresenter;
use Nette\Http\UrlImmutable;

class LoginPresenter extends MainPresenter
{
	public function __construct()
	{
		$this->template = new LoginTemplate();
		parent::__construct();
	}

	public function action(): void
	{
		if (Strict::isUrl($_GET['redirect'] ?? null)) {
			$redirectUrl = new UrlImmutable($_GET['redirect']);
		} else {
			$redirectUrl = Config::getAppUrl();
		}

		if ($this->login->isLogged()) {
			$this->redirect($redirectUrl);
		} else if (\App\TelegramCustomWrapper\Login::hasRequiredGetParams($_GET)) {
			$tgLoginWrapper = new \App\TelegramCustomWrapper\Login($_GET);
			if ($tgLoginWrapper->isTooOld()) {
				$this->flashMessage('Login URL is no longer valid. Try it again or log in via web.', FlashMessage::FLASH_ERROR, null);
			} else if ($tgLoginWrapper->isVerified()) {
				$this->login->saveToDatabase($tgLoginWrapper);
				$this->login->setCookie($tgLoginWrapper->hash());
				$this->redirect($redirectUrl);
			} else {
				$this->flashMessage('Could not verify Telegram login URL. Try again or log in via web.', FlashMessage::FLASH_ERROR, null);
			}
		}
	}

	public function render(): void
	{
		$this->template->prepare();
		Factory::latte('login.latte', $this->template);
	}
}

