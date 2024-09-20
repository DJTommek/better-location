<?php declare(strict_types=1);

namespace App\Repository;

class ChatMembersRepository extends Repository
{
	public function deleteByChatId(int $chatId): void
	{
		$this->db->query('DELETE FROM better_location_chat_members WHERE chat_member_chat_id = ?', $chatId);
	}

	public function insert(int $chatId, int $userId): void
	{
		$this->db->query('INSERT INTO better_location_chat_members (chat_member_chat_id, chat_member_user_id) VALUES (?, ?)',
			$chatId,
			$userId,
		);
	}
}
