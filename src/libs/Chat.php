<?php declare(strict_types=1);

class Chat
{
	private $db;

	private $id;
	private $telegramChatId;
	private $telegramChatName;
	private $telegramChatType;

	/**
	 * Chat constructor.
	 *
	 * @param int $telegramChatId
	 * @param string $telegramChatType
	 * @param string|null $telegramChatName
	 */
	public function __construct(int $telegramChatId, string $telegramChatType, ?string $telegramChatName = null) {
		$this->telegramChatId = $telegramChatId;
		$this->telegramChatName = $telegramChatName;
		$this->telegramChatType = $telegramChatType;
		$this->db = Factory::Database();
		$chatData = $this->register();
		$this->updateCachedData($chatData);
	}

	private function updateCachedData($newChatData) {
		$this->id = $newChatData['chat_id'];
		$this->telegramChatType = $newChatData['chat_telegram_type'];
		$this->telegramChatId = $newChatData['chat_telegram_id'];
		$this->telegramChatName = $newChatData['chat_telegram_name'];
	}

	private function register() {
		$this->db->query('INSERT INTO better_location_chat (chat_telegram_id, chat_telegram_type, chat_telegram_name, chat_last_update, chat_registered) VALUES (?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP()) 
			ON DUPLICATE KEY UPDATE chat_telegram_type = ?, chat_telegram_name = ?, chat_last_update = UTC_TIMESTAMP()',
			$this->telegramChatId, $this->telegramChatType, $this->telegramChatName,
			$this->telegramChatType, $this->telegramChatName,
		);
		return $this->load();
	}

	public function load() {
		return $this->db->query('SELECT * FROM better_location_chat WHERE chat_telegram_id = ?', $this->telegramChatId)->fetchAll()[0];
	}

	public function update(?string $telegramChatType = null, ?string $telegramChatName = null) {
		$queries = [];
		$params = [];
		if (is_string($telegramChatType)) {
			$queries[] = 'chat_telegram_type = ?';
			$params[] = $telegramChatType;
		}
		if (is_string($telegramChatName)) {
			$queries[] = 'chat_telegram_name = ?';
			$params[] = $telegramChatName;
		}
		if (count($params) > 0) {
			$query = sprintf('UPDATE better_location_chat SET %s WHERE chat_telegram_id = ?', join($queries, ', '));

			$params[] = $this->telegramChatId;
			call_user_func_array([$this->db, 'query'], array_merge([$query], $params));
			$newData = $this->load();
			$this->updateCachedData($newData);
		}
		return $this->get();
	}

	public function get() {
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getTelegramChatId() {
		return $this->telegramChatId;
	}

	/**
	 * @return string
	 */
	public function getTelegramChatType(): string {
		return $this->telegramChatType;
	}

	/**
	 * @return mixed
	 */
	public function getTelegramChatName() {
		return $this->telegramChatName;
	}
}
