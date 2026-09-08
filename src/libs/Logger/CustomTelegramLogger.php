<?php

declare(strict_types=1);

namespace App\Logger;

use App\Utils\SimpleLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Logger dedicated for usage in unreal4u/telegram-api library.
 * For purpose of this application is not necessary to see everything, so some of levels are doing nothing on purpose.
 * Main goal is to catch new attributes in TG API
 */
final class CustomTelegramLogger implements LoggerInterface
{
	#[\Override]
	public function emergency(string|\Stringable $message, array $context = []): void
	{
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	#[\Override]
	public function alert(string|\Stringable $message, array $context = []): void
	{
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	#[\Override]
	public function critical(string|\Stringable $message, array $context = []): void
	{
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	#[\Override]
	public function error(string|\Stringable $message, array $context = []): void
	{
		$this->log(LogLevel::ERROR, $message, $context);
	}

	#[\Override]
	public function warning(string|\Stringable $message, array $context = []): void
	{
		$this->log(LogLevel::WARNING, $message, $context);
	}

	#[\Override]
	public function notice(string|\Stringable $message, array $context = []): void
	{
		$this->log(LogLevel::WARNING, $message, $context);
	}

	#[\Override]
	public function info(string|\Stringable $message, array $context = []): void
	{
		// Do nothing
	}

	#[\Override]
	public function debug(string|\Stringable $message, array $context = []): void
	{
		// Do nothing
	}

	#[\Override]
	public function log($level, string|\Stringable $message, array $context = []): void
	{
		if ($context) {
			$message = [
				'message' => $message,
				'context' => $context,
			];
		}
		SimpleLogger::log(SimpleLogger::NAME_TELEGRAM_LOGGER, $message);
	}
}
