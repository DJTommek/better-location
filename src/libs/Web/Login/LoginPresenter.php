<?php declare(strict_types=1);

namespace App\Web\Login;

use App\Config;
use App\Factory;
use App\Utils\Strict;
use App\Web\Flash;
use App\Web\MainPresenter;
use Nette\Http\UrlImmutable;

class LoginPresenter extends MainPresenter
{
	public function __construct(LoginTemplate $template)
	{
		$this->template = $template;
	}

	public function action(): void
	{
		if (Strict::isUrl($_GET['redirect'] ?? null)) {
			$redirectUrl = new UrlImmutable($_GET['redirect']);
			$customRedirectUrl = true;
		} else {
			$redirectUrl = Config::getAppUrl();
			$customRedirectUrl = false;
		}

		if (\App\TelegramCustomWrapper\Login::hasRequiredGetParams($_GET)) {
			$this->handleLogin();
			$this->redirect($redirectUrl);
		}

		if ($this->login->isLogged()) {
			if ($customRedirectUrl === false) {
				$this->flashMessage(sprintf('You are already logged in as <b>%s</b>.', $this->login->getDisplayName()));
			}
			$this->redirect($redirectUrl);
		}
	}

	private function handleLogin(): void
	{
		$tgLoginWrapper = new \App\TelegramCustomWrapper\Login($_GET);
		if ($tgLoginWrapper->isTooOld()) {
			$this->flashMessage('Login URL is no longer valid. Try it again or log in via web.', Flash::ERROR, null);
			return;
		}

		if ($tgLoginWrapper->isVerified() === false) {
			$this->flashMessage('Could not verify Telegram login URL. Try again or log in via web.', Flash::ERROR, null);
			return;
		}

		$userTgIdOld = $this->login->getTelegramId(); // null is non-logged-in user
		$userTgIdNew = $tgLoginWrapper->userTelegramId();

		if ($userTgIdOld === $userTgIdNew) {
			return;
		}

		$this->login->saveToDatabase($tgLoginWrapper);
		$this->login->setCookie($tgLoginWrapper->hash());

		$this->flashMessage(sprintf('You were logged in as <b>%s</b>.', $tgLoginWrapper->displayname()), Flash::SUCCESS);
	}

	public function beforeRender(): void
	{
		$this->template->prepare();
		$this->setTemplateFilename('login.latte');
	}
}

