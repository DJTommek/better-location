<?php declare(strict_types=1);

namespace App\Web\Admin;

use App\Config;
use App\Database;
use App\DefaultConfig;
use App\Icons;
use App\TelegramUpdateDb;
use App\Utils\DateImmutableUtils;
use App\Web\LayoutTemplate;
use Nette\Http\Request;
use Nette\Http\UrlImmutable;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\Telegram\Types\WebhookInfo;

class AdminTemplate extends LayoutTemplate
{
	private readonly Database $database;
	public readonly Request $request;
	public readonly bool $isAppUrlSet;
	public readonly UrlImmutable $appUrl;
	public readonly UrlImmutable $tgWebhookUrl;

	public readonly int $autorefreshAllCount;
	public readonly ?TelegramUpdateDb $newestRefresh;
	public readonly ?TelegramUpdateDb $oldestRefresh;

	// Status
	public readonly string $installTabIcon;
	public readonly string $tracyEmailIcon;
	public readonly bool $isDatabaseConnectionSet;
	public readonly bool $isDatabaseTablesSet;
	public readonly ?\PDOException $dbError;
	public readonly ?\PDOException $tablesError;
	public readonly ?WebhookInfo $webhookResponseRaw;
	public readonly \stdClass $webhookResponse;
	public readonly ?ClientException $webhookError;
	public readonly bool $webhookOk;

	public function prepare(
		Database $database,
		Request $request,
		?WebhookInfo $webhookInfo,
		?ClientException $webhookError,
	): void {
		$this->database = $database;
		$this->request = $request;
		$this->webhookResponseRaw = $webhookInfo;
		$this->webhookError = $webhookError;

		$this->appUrl = Config::getAppUrl();
		$this->isAppUrlSet = $this->appUrl->isEqual(DefaultConfig::getAppUrl()) === false;
		$this->tgWebhookUrl = Config::getTelegramWebhookUrl();

		$autorefreshAll = \App\TelegramUpdateDb::loadAll(\App\TelegramUpdateDb::STATUS_ENABLED);
		$this->autorefreshAllCount = count($autorefreshAll);
		$this->oldestRefresh = $autorefreshAll[0] ?? null;
		$this->newestRefresh = $autorefreshAll[$this->autorefreshAllCount - 1] ?? null;

		$this->isDatabaseConnectionSet = $this->isDatabaseConnectionSet();
		$this->isDatabaseTablesSet = $this->isDatabaseTablesSet();
		$this->installTabIcon = $this->getInstallTabIcon();
		$this->tracyEmailIcon = $this->getTracyEmailIcon();
		$this->formatWebhookInfo();
	}

	private function getInstallTabIcon(): string
	{
		if (
			Config::isTelegram()
			&& $this->dbError === null
			&& $this->tablesError === null
		) {
			return Icons::SUCCESS;
		} else {
			return Icons::ERROR;
		}
	}

	private function isDatabaseConnectionSet(): bool
	{
		try {
			$this->database->query('SELECT 1');
			$this->dbError = null;
			return true;
		} catch (\PDOException $exception) {
			$this->dbError = $exception;
			return false;
		}
	}

	private function isDatabaseTablesSet(): bool
	{
		try {
			$this->database->query('SELECT user_id, user_telegram_id, user_telegram_name, user_registered, user_last_update, user_location_lat, user_location_lon, user_location_last_update FROM better_location_user LIMIT 1');
			$this->database->query('SELECT chat_id, chat_telegram_id, chat_telegram_type, chat_telegram_name, chat_registered, chat_last_update FROM better_location_chat LIMIT 1');
			$this->database->query('SELECT id, user_id, status, lat, lon, title FROM better_location_favourites LIMIT 1');
			$this->tablesError = null;
		} catch (\PDOException $exception) {
			$this->tablesError = $exception;
			return false;
		}
		return true;
	}

	private function getTracyEmailIcon(): string
	{
		if (is_null(Config::TRACY_DEBUGGER_EMAIL)) {
			return Icons::SUCCESS;
		} else if (file_exists(Config::getTracyEmailPath()) === true) {
			return Icons::WARNING;
		} else {
			return Icons::SUCCESS;
		}
	}


	#[\Latte\Attributes\TemplateFunction]
	public function getUsersCount(): int
	{
		return $this->database->query('SELECT COUNT(*) AS count FROM better_location_user')->fetch()['count'];
	}

	#[\Latte\Attributes\TemplateFunction]
	public function getChatsStats(): array
	{
		return $this->database->query('SELECT chat_telegram_type, COUNT(*) as count FROM `better_location_chat` GROUP BY chat_telegram_type')->fetchAll(\PDO::FETCH_KEY_PAIR);
	}

	#[\Latte\Attributes\TemplateFunction]
	public function getNewestUser(): ?array
	{
		$user = $this->database->query('SELECT * FROM better_location_user ORDER BY user_last_update DESC LIMIT 1')->fetch();
		if ($user) {
			$user['user_registered'] = new \DateTimeImmutable($user['user_registered']);
			$user['user_last_update'] = new \DateTimeImmutable($user['user_last_update']);
			return $user;
		} else {
			return null;
		}
	}

	#[\Latte\Attributes\TemplateFunction]
	public function getLatestChangedUser(): ?array
	{
		$user = $this->database->query('SELECT * FROM better_location_user ORDER BY user_registered DESC LIMIT 1')->fetch();
		if ($user) {
			$user['user_registered'] = new \DateTimeImmutable($user['user_registered']);
			$user['user_last_update'] = new \DateTimeImmutable($user['user_last_update']);
			return $user;
		} else {
			return null;
		}
	}

	#[\Latte\Attributes\TemplateFunction]
	public static function getLocalConfigPath(bool $absolute = false): string
	{
		if ($absolute === true) {
			return Config::FOLDER_DATA . '/config.local.php';
		} else {
			return 'data/config.local.php';
		}
	}

	public function formatWebhookInfo(): void
	{
		if ($this->webhookResponseRaw === null) {
			return;
		}

		$responseFormatted = new \stdClass();
		$webhookOk = true;
		foreach (get_object_vars($this->webhookResponseRaw) as $key => $value) {
			if ($key === 'url') {
				if (empty($value)) {
					$responseFormatted->{$key} = sprintf('%s According to Telegram API response, webhook URL is not set. Did you run Telegram setup?', Icons::ERROR);
					$webhookOk = false;
				} else {
					$webhookUrlFromApi = new UrlImmutable($value);
					$webhookUrlFromConfig = Config::getTelegramWebhookUrl();

					if ($webhookUrlFromConfig->isEqual($webhookUrlFromApi)) {
						$responseFormatted->{$key} = sprintf('%s <a href="%2$s" target="_blank">%2$s</a> (matching with Config)', Icons::SUCCESS, $webhookUrlFromConfig);
					} else {
						$stringValue = sprintf('%s Webhook URL is set according to webhook response but it\'s different than in Config:<br>', Icons::WARNING);
						$stringValue .= sprintf('<a href="%1$s" target="_blank">%1$s</a> (Webhook response)<br>', $webhookUrlFromApi);
						$stringValue .= sprintf('<a href="%1$s" target="_blank">%1$s</a> (Config)', $webhookUrlFromConfig);
						$responseFormatted->{$key} = $stringValue;
						$webhookOk = false;
					}
				}
			} else if ($key === 'pending_update_count') {
				if ($value === 0) {
					$responseFormatted->{$key} = Icons::SUCCESS . ' None';
				} else {
					$webhookOk = false;
					$responseFormatted->{$key} = Icons::WARNING . ' ' . $value;
				}
			} else if ($key === 'last_error_message' && $value === '') {
				$responseFormatted->{$key} = Icons::SUCCESS . ' None';
			} else if ($key === 'max_connections' && $value !== Config::TELEGRAM_MAX_CONNECTIONS) {
				$webhookOk = false;
				$responseFormatted->{$key} = sprintf(
					'%s <b>%d</b> - number is different than in Config (<b>%d</b>), run Telegram configure to fix.',
					Icons::WARNING,
					$value,
					Config::TELEGRAM_MAX_CONNECTIONS,
				);
			} else if ($key === 'ip_address') {
				$responseFormatted->{$key} = sprintf('<a href="http://%1$s/" target="_blank">%1$s</a>', $value);
			} else if ($key === 'last_error_date') {
				if ($value === 0) {
					$responseFormatted->{$key} = Icons::SUCCESS . ' Never';
				} else {
					$lastErrorDate = DateImmutableUtils::fromTimestamp($value);
					$now = new \DateTimeImmutable();
					$diff = $now->getTimestamp() - $lastErrorDate->getTimestamp();

					$responseFormatted->{$key} = sprintf('%d<br>%s<br>%s ago',
						$lastErrorDate->getTimestamp(),
						$lastErrorDate->format(DATE_ISO8601),
						\App\Utils\Utils::sToHuman($diff),
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
		$this->webhookOk = $webhookOk;
		$this->webhookResponse = $responseFormatted;
	}
}
