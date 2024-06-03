<?php declare(strict_types=1);

namespace App\Web\Admin;

use App\Config;
use App\Database;
use App\TelegramCustomWrapper\Events\Command\Command;
use App\TelegramCustomWrapper\TelegramCustomWrapper;
use App\Web\Flash;
use App\Web\MainPresenter;
use unreal4u\TelegramAPI\Telegram\Methods\SetMyCommands;
use unreal4u\TelegramAPI\Telegram\Types\BotCommand;
use unreal4u\TelegramAPI\Telegram\Types\BotCommandScope;

class AdminPresenter extends MainPresenter
{
	public function __construct(
		private readonly TelegramCustomWrapper $telegramCustomWrapper,
		private readonly Database $database,
		AdminTemplate $template,
	) {
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
		if ($this->request->getQuery('telegram-configure') !== null) {
			$this->actionTelegramConfigure();
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

	public function actionTelegramConfigure(): never
	{
		$webhookUrl = Config::getTelegramWebhookUrl()->getAbsoluteUrl();
		$dropPendingUpdates = $this->request->getQuery('telegram-drop-pending-updates') !== null;

		$setWebhook = new \unreal4u\TelegramAPI\Telegram\Methods\SetWebhook();
		$setWebhook->url = $webhookUrl;
		$setWebhook->max_connections = Config::TELEGRAM_MAX_CONNECTIONS;
		$setWebhook->secret_token = Config::TELEGRAM_WEBHOOK_PASSWORD;

		$setWebhook->drop_pending_updates = $dropPendingUpdates;

		$this->telegramCustomWrapper->run($setWebhook);

		$commandConfigurations = [];
		foreach (Config::TELEGRAM_COMMANDS as $scope => $classStrings) {
			$setCommands = new SetMyCommands();
			$setCommands->scope = new BotCommandScope();
			$setCommands->scope->type = $scope;

			foreach ($classStrings as $classString) {
				/** @var Command $classString */
				$command = ltrim($classString::getTgCmd(), '/');

				$result = new BotCommand();
				$result->command = $command;
				$result->description = sprintf('%s %s', $classString::ICON, $classString::DESCRIPTION);
				$setCommands->commands[] = $result;
				$commandConfigurations[$command] = $command;
			}
			$this->telegramCustomWrapper->run($setCommands);
		}

		$resultMessage = sprintf('Telegram webhook was configured <b>%s</b> droping pending updates and <b>%d</b> commands.',
			$dropPendingUpdates ? 'with' : 'without',
			count($commandConfigurations),
		);

		$this->flashMessage($resultMessage, Flash::SUCCESS);
		$this->redirect('/admin');
	}

	public function beforeRender(): void
	{
		$this->setTemplateFilename('admin.latte');
		$this->template->prepare($this->database, $this->request);
	}
}

