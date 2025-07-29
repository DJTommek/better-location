<?php declare(strict_types=1);

namespace App\Repository;

/**
 * General-purpose table to maintain relations between chat(s) and user(s).
 */
class ChatUserRepository extends Repository
{
	/**
	 * @return list<int>
	 */
	public function findUserIds(ChatUserRelation $relation, int $chatId): array
	{
		$sql = 'SELECT user_id FROM better_location_chat_user WHERE relation = ? AND chat_id = ?';
		return $this->db->query($sql, $relation->value, $chatId)->fetchAll(\PDO::FETCH_COLUMN);
	}

	/**
	 * @return list<UserEntity>
	 */
	public function findUsers(ChatUserRelation $relation, int $chatId): array
	{
		$sql = 'SELECT better_location_user.* FROM better_location_chat_user LEFT JOIN better_location_user USING (user_id) WHERE relation = ? AND chat_id = ?';
		$rows = $this->db->query($sql, $relation->value, $chatId)->fetchAll();
		return UserEntity::fromRows($rows);
	}

	public function add(ChatUserRelation $relation, int $chatId, int $userId): void
	{
		$sql = 'INSERT INTO better_location_chat_user (relation, chat_id, user_id) VALUES (?, ?, ?)';
		$sql .= ' ON DUPLICATE KEY UPDATE relation = relation';
		$this->db->query($sql, $relation->value, $chatId, $userId);
	}

	public function delete(ChatUserRelation $relation, int $chatId, int $userId): void
	{
		$sql = 'DELETE FROM better_location_chat_user WHERE relation = ? AND chat_id = ? AND user_id = ?';
		$this->db->query($sql, $relation->value, $chatId, $userId);
	}
}
