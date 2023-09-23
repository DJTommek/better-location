<?php declare(strict_types=1);

namespace App;

use App\Repository\Repository;
use Nette\Utils\Json;
use unreal4u\TelegramAPI\Telegram;

class TelegramUpdateDb
{
	public const STATUS_DISABLED = Repository::DISABLED;
	public const STATUS_ENABLED = Repository::ENABLED;
	public const STATUS_DELETED = Repository::DELETED;

	private readonly Database $db;

	private ?string $lastResponseText;
	private ?Telegram\Types\Inline\Keyboard\Markup $lastResponseReplyMarkup;

	public function __construct(
		public readonly Telegram\Types\Update $originalUpdateObject,
		public readonly int $telegramChatId,
		private readonly int $inputMessageId,
		public readonly int $messageIdToEdit,
		private int $status,
		private \DateTimeImmutable $lastUpdate,
	) {
		$this->db = Factory::database();
	}

	public function setLastSendData(
		string $text,
		Telegram\Types\Inline\Keyboard\Markup $replyMarkup,
		bool $updateInDb = false,
	): void {
		$this->lastResponseText = $text;
		$this->lastResponseReplyMarkup = $replyMarkup;

		if ($updateInDb) {
			$this->db->query('UPDATE better_location_telegram_updates 
SET last_response_text = ?, last_response_reply_markup = ?
WHERE chat_id = ? AND bot_reply_message_id = ?',
				$this->lastResponseText,
				Json::encode($this->lastResponseReplyMarkup),
				$this->telegramChatId,
				$this->messageIdToEdit,
			);
		}
	}

	public function getLastResponseText(): ?string
	{
		return $this->lastResponseText;
	}

	public function getLastResponseReplyMarkup(bool $clone = false): ?Telegram\Types\Inline\Keyboard\Markup
	{
		$result = $this->lastResponseReplyMarkup;
		if ($clone) {
			return clone $result;
		}
		return $result;
	}

	public static function fromDb(int $chatId, int $botReplyMessageId): self
	{
		$row = Factory::database()->query('SELECT * FROM better_location_telegram_updates WHERE chat_id = ? AND bot_reply_message_id = ?',
			$chatId,
			$botReplyMessageId,
		)->fetch();
		return self::fromRow($row);
	}

	public function insert(): void
	{
		$this->db->query('INSERT INTO better_location_telegram_updates (chat_id, input_message_id, bot_reply_message_id, original_update_object, autorefresh_status, last_update) VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())',
			$this->telegramChatId,
			$this->inputMessageId,
			$this->messageIdToEdit,
			Json::encode($this->originalUpdateObject),
			$this->status,
		);
		$this->status = self::STATUS_DISABLED;
	}

	public function autorefreshEnable(): void
	{
		$this->setAutorefresh(self::STATUS_ENABLED);
	}

	public function isAutorefreshEnabled(): bool
	{
		return $this->status === self::STATUS_ENABLED;
	}

	public function autorefreshDisable(): void
	{
		$this->setAutorefresh(self::STATUS_DISABLED);
	}

	private function setAutorefresh(int $status): void
	{
		$this->db->query('UPDATE better_location_telegram_updates SET autorefresh_status = ? WHERE chat_id = ? AND bot_reply_message_id = ?',
			$status,
			$this->telegramChatId,
			$this->messageIdToEdit,
		);
		$this->status = $status;
	}

	public function getLastUpdate(): \DateTimeImmutable
	{
		return $this->lastUpdate;
	}

	public function touchLastUpdate(): void
	{
		$this->db->query('UPDATE better_location_telegram_updates SET last_update = UTC_TIMESTAMP() WHERE chat_id = ? AND bot_reply_message_id = ?',
			$this->telegramChatId,
			$this->messageIdToEdit,
		);
		$this->lastUpdate = new \DateTimeImmutable();
	}

	/** @return self[] */
	public static function loadAll(int $status, int $chatId = null, int $limit = null, \DateTimeInterface $olderThan = null): array
	{
		$results = [];
		$sqlQuery = 'SELECT * FROM better_location_telegram_updates WHERE autorefresh_status = ?';
		$sqlParams = [
			$status,
		];
		if ($chatId) {
			$sqlQuery .= ' AND chat_id = ?';
			$sqlParams[] = $chatId;
		}
		if ($olderThan) {
			$sqlQuery .= ' AND last_update < ?';
			$sqlParams[] = $olderThan->format('Y-m-d\TH:i:s'); // @TODO Now it is not respecting timezone in object
		}
		$sqlQuery .= ' ORDER BY last_update ASC';
		if ($limit) {
			$sqlQuery .= ' LIMIT ?';
			$sqlParams[] = $limit;
		}
		$rows = Factory::database()->query($sqlQuery, ...$sqlParams)->fetchAll();
		foreach ($rows as $row) {
			$results[] = self::fromRow($row);
		}
		return $results;
	}

	public static function loadByOriginalMessageId(int $chatId, int $originalMessageId): ?self
	{
		$sqlQuery = 'SELECT * FROM better_location_telegram_updates WHERE chat_id = ? AND input_message_id = ?';
		if ($row = Factory::database()->query($sqlQuery, $chatId, $originalMessageId)->fetch()) {
			return self::fromRow($row);
		} else {
			return null;
		}
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private static function fromRow(array $row): self
	{
		$dataJson = Json::decode($row['original_update_object'], true);
		$update = new Telegram\Types\Update($dataJson);

		$self = new self(
			originalUpdateObject: $update,
			telegramChatId: $row['chat_id'],
			inputMessageId: $row['input_message_id'],
			messageIdToEdit: $row['bot_reply_message_id'],
			status: $row['autorefresh_status'],
			lastUpdate: new \DateTimeImmutable($row['last_update']),
		);

		if ($row['last_response_text'] && $row['last_response_reply_markup']) {
			$lastResponseMarkupRaw = Json::decode($row['last_response_reply_markup'], true);
			$lastResponseMarkup = new Telegram\Types\Inline\Keyboard\Markup($lastResponseMarkupRaw);
			$self->setLastSendData($row['last_response_text'], $lastResponseMarkup);
		}
		return $self;
	}
}
