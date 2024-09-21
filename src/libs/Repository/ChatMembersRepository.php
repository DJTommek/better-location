<?php declare(strict_types=1);

namespace App\Repository;

class ChatMembersRepository extends Repository
{
	public function deleteByChatId(int $chatId): void
	{
		$this->db->query('DELETE FROM better_location_chat_members WHERE chat_member_chat_id = ?', $chatId);
	}

	public function insert(int $chatId, int $userId, string $role): void
	{
		$this->db->query(
			'INSERT INTO better_location_chat_members (chat_member_chat_id, chat_member_user_id, chat_member_role) VALUES (?, ?, ?)',
			$chatId,
			$userId,
			$role,
		);
	}

	public function isAdmin(int $chatId, int $userId): bool
	{
		$query = $this->db->query(
			'SELECT 1 FROM better_location_chat_members WHERE chat_member_chat_id = ? AND chat_member_user_id = ? AND chat_member_role IN (?, ?)',
			$chatId,
			$userId,
			ChatMemberEntity::ROLE_CREATOR,
			ChatMemberEntity::ROLE_ADMINISTRATOR,
		);

		return $query->rowCount() > 0;
	}
}
