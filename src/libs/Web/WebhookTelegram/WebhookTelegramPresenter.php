<?php declare(strict_types=1);

namespace App\Web\WebhookTelegram;

use App\Config;
use App\Logger\CustomTelegramLogger;
use App\TelegramCustomWrapper\Exceptions\EventNotSupportedException;
use App\TelegramCustomWrapper\TelegramCustomWrapper;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\SimpleLogger;
use App\Web\MainPresenter;
use Nette\Utils\Json;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram;

class WebhookTelegramPresenter extends MainPresenter
{
	public function __construct(
		private readonly TelegramCustomWrapper $telegramCustomWrapper,
		private readonly CustomTelegramLogger $telegramCustomLogger,
	) {

	}

	public function action(): void
	{
		\Tracy\Debugger::enable(\Tracy\Debugger::Production, Config::getTracyPath());

		if (Config::isTelegramWebhookPassword() === false) {
			$this->renderReply(self::HTTP_INTERNAL_SERVER_ERROR, 'Error: Telegram password in local config is not set.');
		}

		if ($this->request->getHeader(TelegramHelper::WEBHOOK_SECRET_TOKEN_HEADER_KEY) !== Config::TELEGRAM_WEBHOOK_PASSWORD) {
			$this->renderReply(self::HTTP_FORBIDDEN, 'Error: Secret HTTP token is not valid.');
		}

		$input = trim($this->request->getRawBody() ?? '');
		if ($input === '') {
			$this->renderReply(self::HTTP_BAD_REQUEST, 'Error: Telegram webhook API data are missing! This page should be requested only from Telegram servers via webhook.');
		}

		try {
			$updateRaw = Json::decode($input, true);
		} catch (\JsonException $exception) {
			Debugger::log($exception);
			$this->renderReply(self::HTTP_BAD_REQUEST, sprintf('Error: Telegram webhook API data are not valid JSON, error: "%s".', $exception->getMessage()));
		}

		try {
			SimpleLogger::log(SimpleLogger::NAME_TELEGRAM_INPUT, $updateRaw);

			$update = new \unreal4u\TelegramAPI\Telegram\Types\Update($updateRaw, $this->telegramCustomLogger);
			$event = $this->telegramCustomWrapper->analyze($update);
			$timerName = 'eventHandling';
			\Tracy\Debugger::timer($timerName);
			$this->telegramCustomWrapper->executeEventHandler($event);
			\Tracy\Debugger::log(sprintf(
				'Handling event %s took %F seconds. Log ID = %d',
				$event::class,
				\Tracy\Debugger::timer($timerName),
				LOG_ID,
			),
				\Tracy\Debugger::DEBUG);

			$this->renderReply(self::HTTP_OK, 'Ok, end.');
		} catch (EventNotSupportedException $exception) {
			$this->renderReply(self::HTTP_OK, sprintf('%s', $exception->getMessage()));
		} catch (\Throwable $exception) {
			if ($this->request->getQuery('exception') === '0') {
				$this->renderReply(self::HTTP_INTERNAL_SERVER_ERROR, sprintf('Error: "%s".', $exception->getMessage()));
			}

			throw $exception;
		}
	}

	private function renderReply(int $httpCode, string $message): never
	{
		http_response_code($httpCode);
		die($message);
	}

	public function beforeRender(): never
	{
		$this->renderReply(self::HTTP_OK, 'End.');
	}
}

