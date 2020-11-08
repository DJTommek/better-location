<?php declare(strict_types=1);

namespace App;

use App\TelegramCustomWrapper\Exceptions\MessageDeletedException;
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
	/** @var int */
	private $botReplyMessageId;

	public function __construct(Telegram\Types\Update $update, int $botReplyMessageId, int $status, \DateTimeImmutable $lastUpdate)
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

		$this->botReplyMessageId = $botReplyMessageId;
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
		return new self(new Telegram\Types\Update($dataJson), intval($row['bot_reply_message_id']), intval($row['autorefresh_status']), new \DateTimeImmutable($row['last_update']));
	}

	public function insert(): void
	{
		$this->db->query('INSERT INTO better_location_telegram_updates (chat_id, message_id, update_object, bot_reply_message_id, autorefresh_status, last_update) VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())',
			$this->telegramChatId, $this->telegramMessageId, json_encode($this->update), $this->botReplyMessageId, $this->status
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

	public function getLastUpdate(): \DateTimeImmutable
	{
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
	public static function loadAll(int $status): array
	{
		$results = [];
		$rows = Factory::Database()->query('SELECT * FROM better_location_telegram_updates WHERE autorefresh_status = ?', $status)->fetchAll();
		foreach ($rows as $row) {
			$dataJson = json_decode($row['update_object'], true, 512, JSON_THROW_ON_ERROR);
			$results[] = new self(new Telegram\Types\Update($dataJson), intval($row['bot_reply_message_id']), intval($row['autorefresh_status']), new \DateTimeImmutable($row['last_update']));
		}
		return $results;
	}

	public function getUpdate(): Telegram\Types\Update
	{
		return $this->update;
	}

	public function getChatId(): int
	{
		return $this->telegramChatId;
	}

	public function getMessageId(): int
	{
		return $this->telegramMessageId;
	}

	public function getBotReplyMessageId(): int
	{
		return $this->botReplyMessageId;
	}
}
