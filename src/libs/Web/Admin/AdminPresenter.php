<?php declare(strict_types=1);

namespace App\Web\Admin;

use App\BetterLocation\FromTelegramMessage;
use App\BetterLocation\GooglePlaceApi;
use App\Config;
use App\Database;
use App\IngressLanchedRu\Client as LanchedRuClient;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\Events\Command\Command;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramCustomWrapper;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\SimpleLogger;
use App\Utils\Utils;
use App\Web\Flash;
use App\Web\MainPresenter;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\Telegram\Methods\GetWebhookInfo;
use unreal4u\TelegramAPI\Telegram\Methods\SetMyCommands;
use unreal4u\TelegramAPI\Telegram\Types\BotCommand;
use unreal4u\TelegramAPI\Telegram\Types\BotCommandScope;

class AdminPresenter extends MainPresenter
{
	private const MAX_LOG_LINES = 10;

	private TesterResult $testerResult;

	public function __construct(
		private readonly Database $database,
		private readonly TelegramCustomWrapper $telegramCustomWrapper,
		private readonly FromTelegramMessage $fromTelegramMessage,
		private readonly ?GooglePlaceApi $googlePlaceApi,
		private readonly ?LanchedRuClient $lanchedRuClient,
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

		$this->testerResult = $this->handleTester();
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

	public function handleTester(): TesterResult
	{
		$testerInput = $this->request->getPost('input');
		if ($testerInput === null) {
			return new TesterResult('', 'Fill and send some data.', Flash::INFO);
		}

		$entities = TelegramHelper::generateEntities($testerInput);
		$collection = $this->fromTelegramMessage->getCollection($testerInput, $entities);
		if (
			$collection->count() === 0
			&& mb_strlen($testerInput) >= Config::GOOGLE_SEARCH_MIN_LENGTH
			&& $this->googlePlaceApi !== null
		) {
			try {
				$collection->add($this->googlePlaceApi->searchPlace($testerInput));
			} catch (\Exception $exception) {
				Debugger::log($exception, Debugger::EXCEPTION);
			}
		}

		$processedCollection = new ProcessedMessageResult(
			collection: $collection,
			messageSettings: new BetterLocationMessageSettings(),
			lanchedRuClient: $this->lanchedRuClient,
		);
		$processedCollection->process(true);

		if ($collection->isEmpty()) {
			return new TesterResult($testerInput, 'No location was detected.', Flash::WARNING);
		}

		return new TesterResult(
			input: $testerInput,
			betterLocationTextHtml: trim($processedCollection->getText()),
			betterLocationButtons: $processedCollection->getButtons(1),
		);
	}

	public function beforeRender(): void
	{
		$this->setTemplateFilename('admin.latte');

		$webhookError = null;
		$webhookInfo = null;
		try {
			$webhookInfo = Config::isTelegramBotToken() ? $this->telegramCustomWrapper->run(new GetWebhookInfo()) : null;
		} catch (ClientException $clientException) {
			$webhookError = $clientException;
		}
		$simpleLogsDate = new \DateTime();

		$this->template->prepare(
			$this->database,
			$this->request,
			$webhookInfo,
			$webhookError,
			$simpleLogsDate,
			$this->getLogs($simpleLogsDate, self::MAX_LOG_LINES),
			$this->getTracyLogs(self::MAX_LOG_LINES),
			$this->testerResult,
		);
	}

	/**
	 * @return array<string, list<\stdClass>>
	 */
	public function getLogs(\DateTimeInterface $date, int $maxLines): array
	{
		$logContents = [];
		foreach (SimpleLogger::getLogNames() as $logName) {
			$logContents[$logName] = SimpleLogger::getLogContent($logName, $date, $maxLines);
		}
		return $logContents;
	}

	/**
	 * @return array<string, list<string>>
	 */
	public function getTracyLogs(int $maxLines): array
	{
		$logsContent = [];
		foreach (Utils::getClassConstants(ILogger::class) as $logName) {
			$logContent = self::getTracyLogContent($logName, $maxLines);
			if (count($logContent) > 0) {
				$logsContent[$logName] = $logContent;
			}
		}
		return $logsContent;
	}

	/**
	 * @return list<string>
	 */
	private function getTracyLogContent(string $logName, int $maxLines): array
	{
		$tracyLogPath = Config::getTracyPath() . '/' . $logName . '.log';
		if (file_exists($tracyLogPath)) {
			$fileContent = Utils::tail($tracyLogPath, $maxLines);
			return explode(PHP_EOL, $fileContent);
		} else {
			return [];
		}
	}
}

