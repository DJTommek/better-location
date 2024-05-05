<?php declare(strict_types=1);

namespace App\Web\WebhookTelegram;

use App\Config;
use App\TelegramCustomWrapper\Exceptions\EventNotSupportedException;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\SimpleLogger;
use App\Web\MainPresenter;
use Nette\Utils\Json;
use unreal4u\TelegramAPI\Telegram;

class WebhookTelegramPresenter extends MainPresenter
{
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
			$updateData = Json::decode($input, Json::FORCE_ARRAY);
			SimpleLogger::log(SimpleLogger::NAME_TELEGRAM_INPUT, $updateData);

			\App\Factory::database(); // Just check if database connection is valid, otherwise throw Exception and end script now.

			$telegramCustomWrapper = \App\Factory::telegram();
			$update = new \unreal4u\TelegramAPI\Telegram\Types\Update(
				$updateData,
				\App\Factory::telegramCustomLogger(),
			);
			$event = $telegramCustomWrapper->analyze($update);
			$timerName = 'eventHandling';
			\Tracy\Debugger::timer($timerName);
			$telegramCustomWrapper->executeEventHandler($event);
			\Tracy\Debugger::log(sprintf(
				'Handling event %s took %F seconds. Log ID = %d',
				$event::class,
				\Tracy\Debugger::timer($timerName),
				LOG_ID,
			),
				\Tracy\Debugger::DEBUG);
			printf('OK.');
		} catch (EventNotSupportedException $exception) {
			printf('<p>%s</p>', $exception->getMessage());
		} catch (\Throwable $exception) {
			if ($this->request->getQuery('exception') === '0') {
				printf('Error: "%s".', $exception->getMessage());
			} else {
				/** @noinspection PhpUnhandledExceptionInspection */
				throw $exception;
			}
		}
	}

	private function renderReply(int $httpCode, string $message): never
	{
		http_response_code($httpCode);
		die($message);
	}

	public function render(): never
	{
		$this->renderReply(self::HTTP_OK, 'End.');
	}
}

