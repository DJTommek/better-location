<?php declare(strict_types=1);

namespace App\Web\Admin;

use App\Config;
use App\Web\Flash;
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
			$this->actionDeleteTracyEmailFile();
		}
	}

	public function actionDeleteTracyEmailFile(): never
	{
		try {
			\Nette\Utils\FileSystem::delete(Config::getTracyEmailPath());
			$this->flashMessage('Tracy\'s "email-sent" file was deleted.', Flash::SUCCESS);
		} catch (\Nette\IOException $exception) {
			$this->flashMessage(
				sprintf('Error while deleting Tracy\'s "email-sent" file: "%s"', $exception->getMessage()),
				Flash::ERROR,
			);
		}
		$this->redirect('/admin');
	}

	public function beforeRender(): void
	{
		$this->setTemplateFilename('admin.latte');
		$this->template->prepare($this->request);
	}
}

