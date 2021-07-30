<?php declare(strict_types=1);

namespace App\Web\Login;

use App\Factory;
use Nette\Http\Url;

class LoginPresenter
{
	/** @var Url */
	private $redirectUrl;
	/** @var LoginTemplate */
	private $template;

	public function __construct()
	{
		$this->template = new LoginTemplate();
	}

	public function render(): void
	{
		if (\App\TelegramCustomWrapper\Login::hasRequiredGetParams($_GET)) {
			$this->template->loginWrapper = new \App\TelegramCustomWrapper\Login($_GET);
			if ($this->template->loginWrapper->isTooOld()) {
				$this->template->setError('Login URL is no longer valid. Try it again or log in via web.');
			} else if ($this->template->loginWrapper->isVerified()) {
				$this->template->logged = true;
			} else {
				$this->template->setError('Could not verify URL, try log in via web.');
			}
			dump($this->template->loginWrapper);
		} else {
			$this->template->setError('Missing required data in GET parameter.');
		}
		Factory::Latte('login.latte', $this->template);
	}
}

