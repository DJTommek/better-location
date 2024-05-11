<?php declare(strict_types=1);

namespace App\Web\Admin;

use App\Config;
use App\Web\MainPresenter;

class AdminPresenter extends MainPresenter
{
	public function __construct(AdminTemplate $template)
	{
		$this->template = $template;
	}

	public function action(): void
	{
		if (!Config::isAdminPasswordSet()) {
			die('Set ADMIN_PASSWORD in your local config file first');
		}

		/** @var string $adminPassword */
		$adminPassword = Config::ADMIN_PASSWORD;

		if ($this->request->getPost('password') === $adminPassword) {
			$response = new \Nette\Http\Response();
			$response->setCookie(\App\Config::ADMIN_PASSWORD_COOKIE, $adminPassword, '1 year');
			$url = Config::getAppUrl('/admin');
			$response->redirect((string)$url);
			die();
		}

		if ($this->request->getCookie(\App\Config::ADMIN_PASSWORD_COOKIE) !== $adminPassword) {
			die('Missing or invalid password. <form method="POST">Password: <input type="password" name="password"><button type="submit">Sign in</button></form>');
		}

		if ($this->request->getQuery('delete-tracy-email-sent') !== null) {
			try {
				\Nette\Utils\FileSystem::delete(Config::getTracyEmailPath());
				printf('<p>%s Tracy\'s "email-sent" file was deleted.</p>', \App\Icons::SUCCESS);
			} catch (\Nette\IOException $exception) {
				printf('<p>%s Error while deleting Tracy\'s "email-sent" file: <b>%s</b></p>', \App\Icons::ERROR, $exception->getMessage());
			}
			die('<p>Go back to <a href="./index.php">index.php</a></p>');
		}

	}

	public function beforeRender(): void
	{
		$this->setTemplateFilename('admin.latte');
		$this->template->prepare($this->request);
	}
}

