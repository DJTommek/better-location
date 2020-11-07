<?php declare(strict_types=1);

namespace App;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidApiKeyException;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\TelegramCustomWrapper\Exceptions\MessageDeletedException;
use App\TelegramCustomWrapper\SendMessage;
use unreal4u\TelegramAPI\Telegram;

class TelegramUpdateDb
{
	const STATUS_DISABLED = 0;
	const STATUS_ENABLED = 1;
	const STATUS_DELETED = 2;

	/** @var Database */
	private $db;

	/** @var Telegram\Types\Update */
	private $update;
	/** @var int */
	private $telegramChatId;
	/** @var int */
	private $telegramMessageId;
	/** @var int */
	private $status;
	/** @var \DateTimeImmutable */
	private $lastUpdate;

	public function __construct(Telegram\Types\Update $update, int $status, \DateTimeImmutable $lastUpdate)
	{
		$this->db = Factory::Database();
		$chatId = $update->message->chat->id ?? null;
		if (is_int($chatId) === false || $chatId === 0) {
			throw new MessageDeletedException(sprintf('Chat ID "%s" in Update object is not valid.', $chatId));
		}

		$messageId = $update->message->message_id ?? null;
		if (is_int($chatId) === false || $chatId === 0) {
			throw new MessageDeletedException(sprintf('Message ID "%s" in Update object is not valid.', $messageId));
		}

		$this->telegramChatId = $chatId;
		$this->telegramMessageId = $messageId;
		$this->update = $update;
		$this->status = $status;
		$this->lastUpdate = $lastUpdate;
	}

	public static function fromDb(int $chatId, int $messageId): self
	{
		$row = Factory::Database()->query('SELECT * FROM better_location_telegram_updates WHERE chat_id = ? AND message_id = ?',
			$chatId, $messageId
		)->fetch();
		$dataJson = json_decode($row['update_object'], true, 512, JSON_THROW_ON_ERROR);
		return new self(new Telegram\Types\Update($dataJson), intval($row['autorefresh_status']), new \DateTimeImmutable($row['last_update']));
	}

	public function insert(): void
	{
		$this->db->query('INSERT INTO better_location_telegram_updates (chat_id, message_id, update_object, autorefresh_status, last_update) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())',
			$this->telegramChatId, $this->telegramMessageId, json_encode($this->update), $this->status
		);
		$this->status = self::STATUS_DISABLED;
	}

	public function autorefreshEnable()
	{
		$this->setAutorefresh(self::STATUS_ENABLED);
	}

	public function isAutorefreshEnabled()
	{
		return $this->status === self::STATUS_ENABLED;
	}

	public function autorefreshDisable()
	{
		$this->setAutorefresh(self::STATUS_DISABLED);
	}

	private function setAutorefresh(int $status): void
	{
		$this->db->query('UPDATE better_location_telegram_updates SET autorefresh_status = ? WHERE chat_id = ? AND message_id = ?',
			$status, $this->telegramChatId, $this->telegramMessageId
		);
		$this->status = $status;
	}

	public function getLastUpdate(): \DateTimeImmutable {
		return $this->lastUpdate;
	}

	public function touchLastUpdate(): void
	{
		$this->db->query('UPDATE better_location_telegram_updates SET last_update = UTC_TIMESTAMP() WHERE chat_id = ? AND message_id = ?',
			$this->telegramChatId, $this->telegramMessageId
		);
		$this->lastUpdate = new \DateTimeImmutable();
	}

	/** @return self[] */
	public static function loadAll(): array
	{
		$results = [];
		$rows = Factory::Database()->query('SELECT * FROM better_location_telegram_updates')->fetchAll();
		foreach ($rows as $row) {
			$dataJson = json_decode($row['update_object'], true, 512, JSON_THROW_ON_ERROR);
			$results[] = new self(new Telegram\Types\Update($dataJson), intval($row['autorefresh_status']), new \DateTimeImmutable($row['last_update']));
		}
		return $results;
	}

	public function run()
	{
		// @TODO move somewhere else
		$loop = \React\EventLoop\Factory::create();
		$tgLog = new \unreal4u\TelegramAPI\TgLog(Config::TELEGRAM_BOT_TOKEN, new \unreal4u\TelegramAPI\HttpClientRequestHandler($loop));
		$collection = BetterLocation::generateFromTelegramMessage(
			$this->update->callback_query->message->reply_to_message->text,
			$this->update->callback_query->message->reply_to_message->entities,
		);
		$result = '';
		$buttonLimit = 1; // @TODO move to config (chat settings)
		$buttons = [];
		foreach ($collection->getAll() as $betterLocation) {
			if ($betterLocation instanceof BetterLocation) {
				$result .= $betterLocation->generateBetterLocation();
				if (count($buttons) < $buttonLimit) {
					$driveButtons = $betterLocation->generateDriveButtons();
					$driveButtons[] = $betterLocation->generateAddToFavouriteButtton();
					$buttons[] = $driveButtons;
				}
			} else if (
				$betterLocation instanceof InvalidLocationException ||
				$betterLocation instanceof InvalidApiKeyException
			) {
				$result .= Icons::ERROR . $betterLocation->getMessage() . PHP_EOL . PHP_EOL;
			} else {
				$result .= Icons::ERROR . 'Unexpected error occured while proceessing message for locations.' . PHP_EOL . PHP_EOL;
				\Tracy\Debugger::log($betterLocation, \Tracy\Debugger::EXCEPTION);
			}
		}
		$buttons[] = BetterLocation::generateRefreshButtons(true);

		$now = new \DateTimeImmutable();
		$result .= sprintf('%s Last refresh: %s', Icons::REFRESH, $now->format(Config::DATETIME_FORMAT_ZONE));
		$markup = (new Telegram\Types\Inline\Keyboard\Markup());
		$markup->inline_keyboard = $buttons;

		$sendMessage = new SendMessage($this->telegramChatId, $result, null, null, $this->telegramBetterMessageId);
		$sendMessage->disableWebPagePreview(true);
		$sendMessage->setReplyMarkup($markup);
		$promise = $tgLog->performApiRequest($sendMessage->msg);
		$promise->then(
			function (Telegram\Types\Message $message) {
				printf('<h1>Success</h1><p>Message ID <b>%d</b> in chat ID <b>%d</b> was successfully edited.</p>',
					$message->message_id, $message->chat->id
				);
			},
			function (\Exception $exception) {
				printf('<h1>Error</h1><p>Failed to edit message: <b>%s</b>.</p>', $exception->getMessage());
				\Tracy\Debugger::log($exception, \Tracy\ILogger::EXCEPTION);
			}
		);
		$loop->run();
	}

	public function getUpdate(): Telegram\Types\Update
	{
		return $this->update;
	}
}
