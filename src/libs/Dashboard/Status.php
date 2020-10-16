<?php declare(strict_types=1);

namespace Dashboard;

use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Types\WebhookInfo;
use unreal4u\TelegramAPI\TgLog;
use Utils\DateImmutableUtils;

class Status
{
	/**
	 * @var \Database
	 */
	public static $db;
	/** @var ?\PDOException */
	public static $dbError = null;
	/** @var ?\PDOException */
	public static $tablesError = null;
	/** @var WebhookInfo */
	public static $webhookResponseRaw = null;
	/** @var \stdClass */
	public static $webhookResponse = null;
	/** @var ?ClientException */
	public static $webhookError = null;
	/** @var bool */
	public static $webhookOk = false;

	public static function isTGWebhookUrSet(): bool {
		return (\Config::TELEGRAM_WEBHOOK_URL !== \DefaultConfig::TELEGRAM_WEBHOOK_URL);
	}

	public static function isTGTokenSet(): bool {
		return (\Config::TELEGRAM_BOT_TOKEN !== \DefaultConfig::TELEGRAM_BOT_TOKEN);
	}

	public static function isTGBotNameSet(): bool {
		return (\Config::TELEGRAM_BOT_NAME !== \DefaultConfig::TELEGRAM_BOT_NAME);
	}

	public static function isTGset(): bool {
		return (self::isTGWebhookUrSet() && self::isTGTokenSet() && self::isTGBotNameSet());
	}

	public static function getLocalConfigPath(bool $absolute = false): string {
		if ($absolute === true) {
			return \Config::FOLDER_DATA . '/config.local.php';
		} else {
			return 'data/config.local.php';
		}
	}

	public static function getTracyEmailSentFilePath() {
		return \Config::FOLDER_DATA . '/tracy-log/email-sent';
	}

	public static function getTracyEmailIcon() {
		if (is_null(\Config::TRACY_DEBUGGER_EMAIL)) {
			return \Icons::SUCCESS;
		} else if (file_exists(\Dashboard\Status::getTracyEmailSentFilePath()) === true) {
			return \Icons::WARNING;
		} else {
			return \Icons::SUCCESS;
		}
	}

	public static function isDatabaseConnectionSet(): bool {
		try {
			self::$db = \Factory::Database();
			return true;
		} catch (\Exception $exception) {
			self::$dbError = $exception;
			return false;
		}
	}

	public static function isDatabaseTablesSet(): bool {
		try {
			self::$db->query('SELECT user_id, user_telegram_id, user_telegram_name, user_registered, user_last_update, user_location_lat, user_location_lon, user_location_last_update FROM better_location_user LIMIT 1');
			self::$db->query('SELECT chat_id, chat_telegram_id, chat_telegram_type, chat_telegram_name, chat_registered, chat_last_update FROM better_location_chat LIMIT 1');
			self::$db->query('SELECT id, user_id, status, lat, lon, title FROM better_location_favourites LIMIT 1');
		} catch (\Exception $exception) {
			self::$tablesError = $exception;
			return false;
		}
		return true;
	}

	public static function getUsersCount(): int {
		return self::$db->query('SELECT COUNT(*) AS count FROM better_location_user')->fetch()['count'];
	}

	public static function getChatsStats(): array {
		return self::$db->query('SELECT chat_telegram_type, COUNT(*) as count FROM `better_location_chat` GROUP BY chat_telegram_type')->fetchAll(\PDO::FETCH_KEY_PAIR);
	}

	public static function getNewestUser(): ?array {
		$user = self::$db->query('SELECT * FROM better_location_user ORDER BY user_last_update DESC LIMIT 1')->fetch();
		if ($user) {
			$user['user_registered'] = new \DateTimeImmutable($user['user_registered']);
			$user['user_last_update'] = new \DateTimeImmutable($user['user_last_update']);
			return $user;
		} else {
			return null;
		}
	}

	public static function getLatestChangedUser(): ?array {
		$user = self::$db->query('SELECT * FROM better_location_user ORDER BY user_registered DESC LIMIT 1')->fetch();
		if ($user) {
			$user['user_registered'] = new \DateTimeImmutable($user['user_registered']);
			$user['user_last_update'] = new \DateTimeImmutable($user['user_last_update']);
			return $user;
		} else {
			return null;
		}
	}

	public static function getInstallTabIcon(): string {
		if (self::isTGWebhookUrSet() && self::isTGTokenSet() && self::isTGBotNameSet() && self::isDatabaseConnectionSet() && self::isDatabaseTablesSet()) {
			return \Icons::SUCCESS;
		} else {
			return \Icons::ERROR;
		}
	}

	public static function runGetWebhookStatus() {
		$loop = \React\EventLoop\Factory::create();
		$tgLog = new TgLog(\Config::TELEGRAM_BOT_TOKEN, new HttpClientRequestHandler($loop));

		$getWebhook = new \unreal4u\TelegramAPI\Telegram\Methods\GetWebhookInfo();
		$promise = $tgLog->performApiRequest($getWebhook);
		$promise->then(
			function (\unreal4u\TelegramAPI\Telegram\Types\WebhookInfo $response) {
				self::$webhookResponseRaw = $response;
				$responseFormatted = new \stdClass();
				$webhookOk = true;
				foreach (get_object_vars($response) as $key => $value) {
					if ($key === 'url') {
						if (empty($value)) {
							$responseFormatted->{$key} = sprintf('%s Webhook URL is not set according to webhook response.', \Icons::ERROR);
							$webhookOk = false;
						} else {
							if ($value === \Config::TELEGRAM_WEBHOOK_URL) {
								$responseFormatted->{$key} = sprintf('%s <a href="%2$s" target="_blank">%2$s</a>', \Icons::SUCCESS, $value);
							} else {
								$stringValue = sprintf('%s Webhook URL is set according to webhook response but it\'s different than in Config:<br>', \Icons::WARNING);
								$stringValue .= sprintf('<a href="%1$s" target="_blank">%1$s</a> (Webhook response)<br>', $value);
								$stringValue .= sprintf('<a href="%1$s" target="_blank">%1$s</a> (Config)', \Config::TELEGRAM_WEBHOOK_URL);
								$responseFormatted->{$key} = $stringValue;
								$webhookOk = false;
							}
						}
					} else if ($key === 'pending_update_count') {
						if ($value === 0) {
							$responseFormatted->{$key} = \Icons::SUCCESS . ' None';
						} else {
							$webhookOk = false;
							$responseFormatted->{$key} = \Icons::WARNING . ' ' . $value;
						}
					} else if ($key === 'last_error_message' && $value === '') {
						$responseFormatted->{$key} = \Icons::SUCCESS . ' None';
					} else if ($key === 'last_error_date') {
						if ($value === 0) {
							$responseFormatted->{$key} = \Icons::SUCCESS . ' Never';
						} else {
							$lastErrorDate = DateImmutableUtils::fromTimestamp($value);
							$now = new \DateTimeImmutable();
							$diff = $now->getTimestamp() - $lastErrorDate->getTimestamp();

							$responseFormatted->{$key} = sprintf('%d<br>%s<br>%s ago',
								$lastErrorDate->getTimestamp(),
								$lastErrorDate->format(DATE_ISO8601),
								\Utils\General::sToHuman($diff),
							);
						}
					} else if (is_bool($value)) {
						$responseFormatted->{$key} = $value ? 'true' : 'false';
					} else if (is_array($value)) {
						$responseFormatted->{$key} = sprintf('Array of <b>%d</b> values: %s', count($value), print_r($value, true));
					} else {
						$responseFormatted->{$key} = $value;
					}
				}
				self::$webhookOk = $webhookOk;
				self::$webhookResponse = $responseFormatted;
			},
			function (\Throwable $exception) {
				self::$webhookError = $exception;
			}
		);
		$loop->run();
	}

}
