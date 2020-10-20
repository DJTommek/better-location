<?php declare(strict_types=1);

use \unreal4u\TelegramAPI\Telegram;
use \TelegramCustomWrapper\Exceptions\MessageDeletedException;

class Cron
{
	/** @var Database */
	private $db;

	/** @var Telegram\Types\Update */
	private $update;
	/** @var int */
	private $telegramChatId;
	/** @var int */
	private $telegramMessageId;

	/**
	 * Cron constructor.
	 *
	 * @param Telegram\Types\Update $update Full update object from button click in BetterLocation message with reply to original message
	 */
	public function __construct(Telegram\Types\Update $update)
	{
		$this->db = Factory::Database();

		$this->update = $update;

		$chatId = $this->update->callback_query->message->reply_to_message->chat->id ?? null;
		if (is_int($chatId) === false || $chatId === 0) {
			throw new MessageDeletedException(sprintf('Chat ID "%s" in Update object is not valid.', $chatId));
		}
		$this->telegramChatId = $chatId;

		$messageId = $this->update->callback_query->message->reply_to_message->message_id ?? null;
		if (is_int($chatId) === false || $chatId === 0) {
			throw new MessageDeletedException(sprintf('Message ID "%s" in Update object is not valid.', $messageId));
		}
		$this->telegramMessageId = $messageId;
	}

	public function isInDb(): bool
	{
		$numberOfRows = $this->db->query('SELECT COUNT(*) FROM better_location_cron WHERE cron_telegram_chat_id = ? AND cron_telegram_message_id = ?',
			$this->telegramChatId, $this->telegramMessageId
		)->fetchColumn();
		return ($numberOfRows === 1);
	}

	private static function generateFromDb(array $row): self
	{
		$dataJson = json_decode($row['cron_data'], false, 512, JSON_THROW_ON_ERROR);
		$update = new Telegram\Types\Update($dataJson);
		return new self($update);
	}

	/** @return self[] */
	public static function loadAll(): array
	{
		$result = [];
		$rows = Factory::Database()->query('SELECT * FROM better_location_cron')->fetchAll();
		foreach ($rows as $row) {
			$result[] = self::generateFromDb($row);
		}
		return $result;
	}

	public function insert(): void
	{
		$this->db->query('INSERT INTO better_location_cron (cron_telegram_chat_id, cron_telegram_message_id, cron_telegram_update_object) VALUES (?, ?, ?)',
			$this->telegramChatId, $this->telegramMessageId, json_encode($this->update),
		);
	}

	public function delete(): void
	{
		$this->db->query('DELETE FROM better_location_cron WHERE cron_telegram_chat_id = ? AND cron_telegram_message_id = ?',
			$this->telegramChatId, $this->telegramMessageId
		);
	}
}
